<?php

namespace App\Services\Partner;

use App\Models\Business;
use App\Models\Partner\Partner;
use App\Models\Partner\PartnerClick;
use App\Models\Partner\PartnerReferral;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Referral code tracking — cookie-based attribution.
 *
 * Lifecycle:
 *  1. /refer/{code} chaqiriladi → cookie o'rnatiladi (90 kun)
 *  2. AuthController::register cookie'dan code o'qiydi
 *  3. attachToBusiness() orqali business.referral_partner_id yoziladi
 */
class PartnerReferralTracker
{
    public const COOKIE_NAME = 'bp_ref';
    public const COOKIE_LIFETIME_DAYS = 90;

    /**
     * /refer/{code} hit'ida chaqiriladi. Cookie o'rnatadi va click yozadi.
     */
    public function trackClick(Request $request, string $code): ?Partner
    {
        $partner = Partner::where('code', $code)->where('status', Partner::STATUS_ACTIVE)->first();

        if (! $partner) {
            return null;
        }

        PartnerClick::create([
            'partner_id' => $partner->id,
            'code' => $code,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'utm_source' => $request->query('utm_source'),
            'utm_medium' => $request->query('utm_medium'),
            'utm_campaign' => $request->query('utm_campaign'),
            'landing_page' => substr((string) $request->query('to', '/'), 0, 500),
            'referrer' => substr((string) $request->header('Referer'), 0, 500),
            'created_at' => now(),
        ]);

        return $partner;
    }

    /**
     * Cookie'ni request'dan olish — signup paytida ishlatiladi.
     */
    public function getCodeFromRequest(Request $request): ?string
    {
        $code = $request->cookie(self::COOKIE_NAME)
            ?: $request->input('ref')
            ?: $request->query('ref');

        return $code ? substr((string) $code, 0, 32) : null;
    }

    /**
     * Yangi biznesga partner attribute qilish.
     *
     * AuthController::register ichida chaqiriladi yoki onboarding'da.
     */
    public function attachToBusiness(Business $business, ?string $code = null, string $via = 'link'): ?PartnerReferral
    {
        if (! $code) {
            return null;
        }

        $partner = Partner::where('code', $code)->where('status', Partner::STATUS_ACTIVE)->first();
        if (! $partner) {
            return null;
        }

        // Self-referral oldini olish
        if ($partner->user_id === $business->user_id) {
            Log::info('PartnerReferral: self-referral blocked', [
                'partner_id' => $partner->id,
                'business_id' => $business->id,
            ]);
            return null;
        }

        $business->update([
            'referral_partner_id' => $partner->id,
            'referral_code_used' => $code,
        ]);

        return PartnerReferral::firstOrCreate(
            [
                'partner_id' => $partner->id,
                'business_id' => $business->id,
            ],
            [
                'referred_via' => $via,
                'ref_code_snapshot' => $code,
                'status' => PartnerReferral::STATUS_PENDING,
            ]
        );
    }

    /**
     * Response'ga tracking cookie qo'shish.
     */
    public function makeCookie(string $code): \Symfony\Component\HttpFoundation\Cookie
    {
        return Cookie::make(
            self::COOKIE_NAME,
            $code,
            self::COOKIE_LIFETIME_DAYS * 24 * 60,
            '/',
            null,
            null, // secure
            false, // httpOnly = false — partner code public
        );
    }

    /**
     * Partner tomonidan to'g'ridan-to'g'ri mijoz yaratish ("Invite Client").
     *
     * Atomik ravishda yaratadi:
     *   1. User (random password, email_verified)
     *   2. Business (owner = yangi user)
     *   3. Business user pivot (role='owner')
     *   4. PartnerReferral (status=attributed, via=manual)
     *
     * BusinessObserver avtomatik Trial subscription yaratib beradi.
     *
     * @return array{user: User, business: Business, referral: PartnerReferral, temp_password: string}
     *
     * @throws \RuntimeException self-referral yoki duplicate email/phone
     */
    public function inviteClient(Partner $partner, array $data): array
    {
        // Self-referral blok — partner o'zini ro'yxatdan o'tkazolmaydi
        $partnerUser = $partner->user;
        if ($partnerUser) {
            if (
                (! empty($data['email']) && strcasecmp((string) $partnerUser->email, (string) $data['email']) === 0) ||
                (! empty($data['phone']) && $partnerUser->phone === $data['phone'])
            ) {
                throw new \RuntimeException("O'zingizni referral qila olmaysiz.");
            }
        }

        // Email/phone dublikat tekshiruvi
        if (! empty($data['email']) && User::where('email', $data['email'])->exists()) {
            throw new \RuntimeException("Bu email allaqachon ro'yxatdan o'tgan.");
        }
        if (! empty($data['phone']) && User::where('phone', $data['phone'])->exists()) {
            throw new \RuntimeException("Bu telefon raqam allaqachon ro'yxatdan o'tgan.");
        }

        // Parol manbalari:
        //   1. Partner formdan kiritgan bo'lsa — ishlatamiz (min 8 belgi)
        //   2. Bo'sh bo'lsa — crypto-strong random generatsiya qilamiz
        $partnerProvidedPassword = isset($data['password']) && strlen($data['password']) >= 8;
        $tempPassword = $partnerProvidedPassword
            ? (string) $data['password']
            : $this->generateStrongPassword();

        return DB::transaction(function () use ($partner, $data, $tempPassword) {
            // 1. User yaratish
            $user = User::create([
                'name' => $data['full_name'],
                'login' => $this->generateUniqueLogin($data['full_name']),
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($tempPassword),
                'email_verified_at' => now(), // partner allaqachon tekshirgan
            ]);

            // 2. Business yaratish
            $slug = Str::slug($data['business_name']);
            $original = $slug;
            $i = 1;
            while (Business::where('slug', $slug)->exists()) {
                $slug = $original . '-' . $i++;
            }

            $business = Business::create([
                'user_id' => $user->id,
                'name' => $data['business_name'],
                'slug' => $slug,
                'category' => $data['category'],
                'region' => $data['region'] ?? 'Tashkent',
                'country' => "O'zbekiston",
                'status' => 'active',
                'onboarding_status' => 'in_progress',
                'onboarding_current_step' => 'basic_info',
                'referral_partner_id' => $partner->id,
                'referral_code_used' => $partner->code,
            ]);

            // 3. User'ni business'ga owner sifatida biriktirish
            $business->users()->attach($user->id, [
                'role' => 'owner',
                'joined_at' => now(),
            ]);

            // 'owner' spatie rolini berish
            if (! $user->hasRole('owner')) {
                $user->assignRole('owner');
            }

            // 4. PartnerReferral yaratish (attributed holatida)
            $referral = PartnerReferral::create([
                'partner_id' => $partner->id,
                'business_id' => $business->id,
                'referred_via' => 'manual',
                'ref_code_snapshot' => $partner->code,
                'attributed_at' => now(),
                'status' => PartnerReferral::STATUS_ATTRIBUTED,
            ]);

            // Audit log
            PartnerClick::create([
                'partner_id' => $partner->id,
                'code' => $partner->code,
                'ip' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 500),
                'utm_source' => 'partner_invite',
                'utm_medium' => 'manual',
                'landing_page' => '/partner/referrals/invite',
                'converted_business_id' => $business->id,
                'created_at' => now(),
            ]);

            Log::info('Partner invited client', [
                'partner_id' => $partner->id,
                'partner_code' => $partner->code,
                'client_user_id' => $user->id,
                'client_email' => $user->email,
                'business_id' => $business->id,
            ]);

            return [
                'user' => $user,
                'business' => $business,
                'referral' => $referral,
                'temp_password' => $tempPassword,
            ];
        });
    }

    protected function generateStrongPassword(int $length = 12): string
    {
        $upper = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        $lower = 'abcdefghjkmnpqrstuvwxyz';
        $digits = '23456789';
        $symbols = '!@#$%&*';

        // Har bir toifadan kamida bittadan (password policy)
        $pwd = [
            $upper[random_int(0, strlen($upper) - 1)],
            $lower[random_int(0, strlen($lower) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
            $symbols[random_int(0, strlen($symbols) - 1)],
        ];

        $all = $upper . $lower . $digits . $symbols;
        for ($i = 0; $i < $length - 4; $i++) {
            $pwd[] = $all[random_int(0, strlen($all) - 1)];
        }

        shuffle($pwd);
        return implode('', $pwd);
    }

    protected function generateUniqueLogin(string $fullName): string
    {
        $base = strtolower(Str::slug(Str::limit($fullName, 12, ''), ''));
        $base = preg_replace('/[^a-z0-9]/', '', $base) ?: 'user';

        do {
            $login = $base . random_int(100, 999);
        } while (User::where('login', $login)->exists());

        return $login;
    }
}

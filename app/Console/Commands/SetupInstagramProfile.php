<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\InstagramAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Instagram Messenger Profile Setup Command
 *
 * Bu command Instagram botning profilini sozlaydi:
 * - Get Started tugmasi (Boshlash)
 * - Persistent Menu (Doimiy menyu)
 * - Ice Breakers (Salomlashish matni)
 *
 * Usage:
 *   php artisan instagram:setup-profile                   # Barcha akkauntlar uchun
 *   php artisan instagram:setup-profile {accountId}       # Aniq akkaunt uchun
 *   php artisan instagram:setup-profile --info            # Joriy sozlamalarni ko'rsatish
 *   php artisan instagram:setup-profile --reset           # Sozlamalarni o'chirish
 *
 * @see https://developers.facebook.com/docs/messenger-platform/instagram/features/ice-breakers
 * @see https://developers.facebook.com/docs/messenger-platform/send-messages/persistent-menu
 */
class SetupInstagramProfile extends Command
{
    protected $signature = 'instagram:setup-profile
                            {accountId? : Instagram Account ID yoki UUID}
                            {--info : Joriy profil sozlamalarini ko\'rsatish}
                            {--reset : Barcha sozlamalarni o\'chirish}
                            {--menu-only : Faqat Persistent Menu sozlash}
                            {--ice-only : Faqat Ice Breakers sozlash}';

    protected $description = 'Instagram bot profilini sozlash (Get Started, Menu, Ice Breakers)';

    protected string $graphApiUrl;

    /**
     * Default Get Started payload
     */
    protected const GET_STARTED_PAYLOAD = 'start_flow';

    /**
     * Default Ice Breakers (salomlashish savollari)
     */
    protected array $defaultIceBreakers = [
        [
            'question' => "Sizda qanday xizmatlar bor? 🤔",
            'payload' => 'FLOW:services',
        ],
        [
            'question' => "Narxlar qancha? 💰",
            'payload' => 'FLOW:prices',
        ],
        [
            'question' => "Men bilan bog'laning 📞",
            'payload' => 'ACTION:human_handoff',
        ],
        [
            'question' => "Boshlash 🚀",
            'payload' => 'start_flow',
        ],
    ];

    /**
     * Default Persistent Menu
     */
    protected array $defaultPersistentMenu = [
        [
            'type' => 'postback',
            'title' => '🏠 Bosh sahifa',
            'payload' => 'start_flow',
        ],
        [
            'type' => 'postback',
            'title' => '💰 Narxlar',
            'payload' => 'FLOW:prices',
        ],
        [
            'type' => 'postback',
            'title' => '👨‍💼 Operator',
            'payload' => 'ACTION:human_handoff',
        ],
        [
            'type' => 'web_url',
            'title' => '🌐 Saytga o\'tish',
            'url' => '', // Will be filled from business settings
        ],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->graphApiUrl = 'https://graph.facebook.com/' . config('services.meta.api_version', 'v21.0');
    }

    public function handle(): int
    {
        $accountId = $this->argument('accountId');

        // Get account(s)
        if ($accountId) {
            $account = InstagramAccount::where('id', $accountId)
                ->orWhere('instagram_id', $accountId)
                ->first();

            if (! $account) {
                $this->error("Instagram akkaunt topilmadi: {$accountId}");

                return self::FAILURE;
            }

            $accounts = collect([$account]);
        } else {
            $accounts = InstagramAccount::where('is_active', true)->get();

            if ($accounts->isEmpty()) {
                $this->error('Hech qanday aktiv Instagram akkaunt topilmadi!');
                $this->line('');
                $this->line('Avval Instagram akkaunтni ulang: Settings > Integrations > Instagram');

                return self::FAILURE;
            }
        }

        $this->info("Instagram profil sozlash ({$accounts->count()} ta akkaunt)...");
        $this->line('');

        $successCount = 0;
        $failCount = 0;

        foreach ($accounts as $account) {
            $this->line("📱 Akkaunt: @{$account->username} ({$account->instagram_id})");

            $result = $this->processAccount($account);

            if ($result) {
                $successCount++;
                $this->info("   ✅ Muvaffaqiyatli sozlandi!");
            } else {
                $failCount++;
                $this->error("   ❌ Xatolik yuz berdi");
            }

            $this->line('');
        }

        $this->line('────────────────────────────────────');
        $this->info("Natija: {$successCount} muvaffaqiyatli, {$failCount} xato");

        return $failCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function processAccount(InstagramAccount $account): bool
    {
        $accessToken = $this->getAccessToken($account);

        if (! $accessToken) {
            $this->error("   Access token topilmadi");

            return false;
        }

        // Show info only
        if ($this->option('info')) {
            return $this->showProfileInfo($account, $accessToken);
        }

        // Reset settings
        if ($this->option('reset')) {
            return $this->resetProfile($account, $accessToken);
        }

        // Setup specific features only
        if ($this->option('menu-only')) {
            return $this->setupPersistentMenu($account, $accessToken);
        }

        if ($this->option('ice-only')) {
            return $this->setupIceBreakers($account, $accessToken);
        }

        // Full setup
        $success = true;

        // 1. Get Started Button
        $this->line("   1️⃣ Get Started tugmasi...");
        if (! $this->setupGetStarted($account, $accessToken)) {
            $success = false;
        }

        // 2. Persistent Menu
        $this->line("   2️⃣ Doimiy menyu (Persistent Menu)...");
        if (! $this->setupPersistentMenu($account, $accessToken)) {
            $success = false;
        }

        // 3. Ice Breakers
        $this->line("   3️⃣ Ice Breakers (salomlashish savollari)...");
        if (! $this->setupIceBreakers($account, $accessToken)) {
            $success = false;
        }

        return $success;
    }

    /**
     * Setup Get Started button
     *
     * API: POST /me/messenger_profile
     * Agar foydalanuvchi birinchi marta chatga kirsa, "Boshlash" tugmasini ko'radi.
     */
    protected function setupGetStarted(InstagramAccount $account, string $accessToken): bool
    {
        try {
            $response = Http::post($this->graphApiUrl . '/me/messenger_profile', [
                'platform' => 'instagram',
                'get_started' => [
                    'payload' => self::GET_STARTED_PAYLOAD,
                ],
                'access_token' => $accessToken,
            ]);

            if ($response->successful()) {
                $this->line("      ✓ Get Started: payload='" . self::GET_STARTED_PAYLOAD . "'");

                return true;
            }

            $error = $response->json('error.message') ?? 'Unknown error';
            $this->warn("      ⚠ Get Started xatosi: {$error}");

            return false;

        } catch (\Exception $e) {
            $this->warn("      ⚠ Exception: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Setup Persistent Menu
     *
     * Doimiy menyu - foydalanuvchi har doim ko'radigan tugmalar.
     * Instagram'da bu "Menu" tugmasi orqali ochiladi.
     */
    protected function setupPersistentMenu(InstagramAccount $account, string $accessToken): bool
    {
        try {
            // Get website URL from business if available
            $websiteUrl = $account->business?->website ?? config('app.url');

            $menu = $this->defaultPersistentMenu;

            // Fill website URL
            foreach ($menu as &$item) {
                if ($item['type'] === 'web_url' && empty($item['url'])) {
                    $item['url'] = $websiteUrl;
                }
            }

            // Remove web_url items without URL
            $menu = array_filter($menu, function ($item) {
                return $item['type'] !== 'web_url' || ! empty($item['url']);
            });

            $response = Http::post($this->graphApiUrl . '/me/messenger_profile', [
                'platform' => 'instagram',
                'persistent_menu' => [
                    [
                        'locale' => 'default',
                        'composer_input_disabled' => false,
                        'call_to_actions' => array_values($menu),
                    ],
                ],
                'access_token' => $accessToken,
            ]);

            if ($response->successful()) {
                $this->line('      ✓ Persistent Menu: ' . count($menu) . ' ta tugma');

                foreach ($menu as $item) {
                    $this->line("        - {$item['title']}");
                }

                return true;
            }

            $error = $response->json('error.message') ?? 'Unknown error';
            $this->warn("      ⚠ Persistent Menu xatosi: {$error}");

            return false;

        } catch (\Exception $e) {
            $this->warn("      ⚠ Exception: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Setup Ice Breakers
     *
     * Ice Breakers - yangi foydalanuvchi uchun tavsiya qilinadigan savollar.
     * Bu savollar chat ochilganda ko'rinadi va foydalanuvchiga boshlashni osonlashtiradi.
     */
    protected function setupIceBreakers(InstagramAccount $account, string $accessToken): bool
    {
        try {
            $iceBreakers = $this->defaultIceBreakers;

            // Instagram limit: maksimum 4 ta ice breaker
            $iceBreakers = array_slice($iceBreakers, 0, 4);

            $response = Http::post($this->graphApiUrl . '/me/messenger_profile', [
                'platform' => 'instagram',
                'ice_breakers' => $iceBreakers,
                'access_token' => $accessToken,
            ]);

            if ($response->successful()) {
                $this->line('      ✓ Ice Breakers: ' . count($iceBreakers) . ' ta savol');

                foreach ($iceBreakers as $ib) {
                    $this->line("        - \"{$ib['question']}\"");
                }

                return true;
            }

            $error = $response->json('error.message') ?? 'Unknown error';
            $this->warn("      ⚠ Ice Breakers xatosi: {$error}");

            return false;

        } catch (\Exception $e) {
            $this->warn("      ⚠ Exception: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Show current profile settings
     */
    protected function showProfileInfo(InstagramAccount $account, string $accessToken): bool
    {
        try {
            $response = Http::get($this->graphApiUrl . '/me/messenger_profile', [
                'platform' => 'instagram',
                'fields' => 'get_started,persistent_menu,ice_breakers',
                'access_token' => $accessToken,
            ]);

            if (! $response->successful()) {
                $error = $response->json('error.message') ?? 'Unknown error';
                $this->error("   Profil ma'lumotlarini olishda xatolik: {$error}");

                return false;
            }

            $data = $response->json('data')[0] ?? [];

            $this->line('   📋 Joriy sozlamalar:');
            $this->line('');

            // Get Started
            if (isset($data['get_started'])) {
                $payload = $data['get_started']['payload'] ?? 'N/A';
                $this->line("   🚀 Get Started: payload=\"{$payload}\"");
            } else {
                $this->line('   🚀 Get Started: ❌ sozlanmagan');
            }

            // Persistent Menu
            if (isset($data['persistent_menu'])) {
                $this->line('');
                $this->line('   📋 Persistent Menu:');
                foreach ($data['persistent_menu'] as $menu) {
                    $locale = $menu['locale'] ?? 'default';
                    $this->line("      Locale: {$locale}");
                    foreach ($menu['call_to_actions'] ?? [] as $action) {
                        $title = $action['title'] ?? 'N/A';
                        $type = $action['type'] ?? 'N/A';
                        $this->line("        - [{$type}] {$title}");
                    }
                }
            } else {
                $this->line('   📋 Persistent Menu: ❌ sozlanmagan');
            }

            // Ice Breakers
            if (isset($data['ice_breakers'])) {
                $this->line('');
                $this->line('   ❄️ Ice Breakers:');
                foreach ($data['ice_breakers'] as $ib) {
                    $question = $ib['question'] ?? 'N/A';
                    $this->line("        - \"{$question}\"");
                }
            } else {
                $this->line('   ❄️ Ice Breakers: ❌ sozlanmagan');
            }

            return true;

        } catch (\Exception $e) {
            $this->error("   Exception: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Reset all profile settings
     */
    protected function resetProfile(InstagramAccount $account, string $accessToken): bool
    {
        if (! $this->confirm('Barcha profil sozlamalarini o\'chirishni xohlaysizmi?', false)) {
            $this->line('   Bekor qilindi.');

            return true;
        }

        try {
            $response = Http::delete($this->graphApiUrl . '/me/messenger_profile', [
                'platform' => 'instagram',
                'fields' => ['get_started', 'persistent_menu', 'ice_breakers'],
                'access_token' => $accessToken,
            ]);

            if ($response->successful()) {
                $this->info('   ✅ Barcha sozlamalar o\'chirildi!');

                return true;
            }

            $error = $response->json('error.message') ?? 'Unknown error';
            $this->error("   Xatolik: {$error}");

            return false;

        } catch (\Exception $e) {
            $this->error("   Exception: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Get access token for account
     */
    protected function getAccessToken(InstagramAccount $account): ?string
    {
        // First check account's own token
        if ($account->access_token) {
            return $account->access_token;
        }

        // Get from integration
        return $account->integration?->getAccessToken();
    }
}

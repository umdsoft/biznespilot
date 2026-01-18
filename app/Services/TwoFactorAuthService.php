<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA;
    }

    /**
     * Generate a new secret key for 2FA
     */
    public function generateSecretKey(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Get QR code URL for Google Authenticator
     */
    public function getQRCodeUrl(User $user, string $secret): string
    {
        $companyName = config('app.name', 'BiznesPilot');
        $email = $user->email ?: $user->login;

        return $this->google2fa->getQRCodeUrl(
            $companyName,
            $email,
            $secret
        );
    }

    /**
     * Generate QR code as inline SVG
     */
    public function getQRCodeInline(User $user, string $secret): string
    {
        $qrCodeUrl = $this->getQRCodeUrl($user, $secret);

        return $this->google2fa->getQRCodeInline(
            config('app.name', 'BiznesPilot'),
            $user->email ?: $user->login,
            $secret
        );
    }

    /**
     * Verify the TOTP code
     */
    public function verifyCode(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code);
    }

    /**
     * Generate recovery codes
     */
    public function generateRecoveryCodes(int $count = 8): Collection
    {
        return collect(range(1, $count))->map(function () {
            return bin2hex(random_bytes(4)).'-'.bin2hex(random_bytes(4));
        });
    }

    /**
     * Enable 2FA for user
     */
    public function enable(User $user, string $secret, string $code): bool
    {
        // Verify the code first
        if (! $this->verifyCode($secret, $code)) {
            return false;
        }

        // Generate recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();

        // Encrypt and save
        $user->update([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($recoveryCodes->toArray())),
            'two_factor_enabled' => true,
            'two_factor_enabled_at' => now(),
        ]);

        return true;
    }

    /**
     * Disable 2FA for user
     */
    public function disable(User $user): void
    {
        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_enabled' => false,
            'two_factor_enabled_at' => null,
        ]);
    }

    /**
     * Verify user's 2FA code
     */
    public function verifyUserCode(User $user, string $code): bool
    {
        if (! $user->two_factor_enabled || ! $user->two_factor_secret) {
            return false;
        }

        $secret = Crypt::decryptString($user->two_factor_secret);

        return $this->verifyCode($secret, $code);
    }

    /**
     * Verify recovery code
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        if (! $user->two_factor_enabled || ! $user->two_factor_recovery_codes) {
            return false;
        }

        $recoveryCodes = json_decode(
            Crypt::decryptString($user->two_factor_recovery_codes),
            true
        );

        // Check if code exists
        if (! in_array($code, $recoveryCodes)) {
            return false;
        }

        // Remove used code
        $remainingCodes = array_values(array_diff($recoveryCodes, [$code]));

        // Update recovery codes
        $user->update([
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($remainingCodes)),
        ]);

        return true;
    }

    /**
     * Get decrypted recovery codes for display
     */
    public function getRecoveryCodes(User $user): ?Collection
    {
        if (! $user->two_factor_recovery_codes) {
            return null;
        }

        $codes = json_decode(
            Crypt::decryptString($user->two_factor_recovery_codes),
            true
        );

        return collect($codes);
    }

    /**
     * Regenerate recovery codes
     */
    public function regenerateRecoveryCodes(User $user): Collection
    {
        $recoveryCodes = $this->generateRecoveryCodes();

        $user->update([
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($recoveryCodes->toArray())),
        ]);

        return $recoveryCodes;
    }
}

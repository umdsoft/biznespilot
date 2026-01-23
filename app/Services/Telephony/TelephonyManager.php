<?php

namespace App\Services\Telephony;

use App\Contracts\TelephonyProviderInterface;
use App\Models\MoiZvonkiAccount;
use App\Models\PbxAccount;
use App\Models\SipuniAccount;
use App\Models\UtelAccount;
use App\Services\OnlinePbxService;
use Illuminate\Support\Facades\Log;

/**
 * TelephonyManager - Unified telephony provider manager
 * Factory class to get the appropriate provider for a business
 */
class TelephonyManager
{
    protected OnlinePbxService $onlinePbxService;

    public function __construct(OnlinePbxService $onlinePbxService)
    {
        $this->onlinePbxService = $onlinePbxService;
    }

    /**
     * Get the active provider for a business
     *
     * @return array{provider: TelephonyProviderInterface|null, name: string|null, account: mixed}
     */
    public function getActiveProvider(string $businessId): array
    {
        // Check providers in priority order

        // 1. OnlinePBX
        $pbxAccount = PbxAccount::where('business_id', $businessId)
            ->where('is_active', true)
            ->first();

        if ($pbxAccount) {
            $provider = new OnlinePbxProvider($this->onlinePbxService);
            $provider->setAccount($pbxAccount);

            return [
                'provider' => $provider,
                'name' => 'onlinepbx',
                'account' => $pbxAccount,
            ];
        }

        // 2. UTEL
        $utelAccount = UtelAccount::where('business_id', $businessId)
            ->where('is_active', true)
            ->first();

        if ($utelAccount) {
            $provider = new UtelProvider();
            $provider->setAccount($utelAccount);

            return [
                'provider' => $provider,
                'name' => 'utel',
                'account' => $utelAccount,
            ];
        }

        // 3. SipUni (future implementation)
        $sipuniAccount = SipuniAccount::where('business_id', $businessId)
            ->where('is_active', true)
            ->first();

        if ($sipuniAccount) {
            // TODO: Implement SipuniProvider
            return [
                'provider' => null,
                'name' => 'sipuni',
                'account' => $sipuniAccount,
            ];
        }

        // 4. MoiZvonki (future implementation)
        $moiZvonkiAccount = MoiZvonkiAccount::where('business_id', $businessId)
            ->where('is_active', true)
            ->first();

        if ($moiZvonkiAccount) {
            // TODO: Implement MoiZvonkiProvider
            return [
                'provider' => null,
                'name' => 'moizvonki',
                'account' => $moiZvonkiAccount,
            ];
        }

        return [
            'provider' => null,
            'name' => null,
            'account' => null,
        ];
    }

    /**
     * Get provider by name
     */
    public function getProviderByName(string $providerName, string $businessId): ?TelephonyProviderInterface
    {
        switch (strtolower($providerName)) {
            case 'onlinepbx':
            case 'pbx':
                $account = PbxAccount::where('business_id', $businessId)
                    ->where('is_active', true)
                    ->first();

                if ($account) {
                    $provider = new OnlinePbxProvider($this->onlinePbxService);
                    $provider->setAccount($account);
                    return $provider;
                }
                break;

            case 'utel':
                $account = UtelAccount::where('business_id', $businessId)
                    ->where('is_active', true)
                    ->first();

                if ($account) {
                    $provider = new UtelProvider();
                    $provider->setAccount($account);
                    return $provider;
                }
                break;
        }

        return null;
    }

    /**
     * Get UTEL provider for a business
     */
    public function getUtelProvider(string $businessId): ?UtelProvider
    {
        $account = UtelAccount::where('business_id', $businessId)
            ->where('is_active', true)
            ->first();

        if (!$account) {
            return null;
        }

        $provider = new UtelProvider();
        $provider->setAccount($account);

        return $provider;
    }

    /**
     * Get OnlinePBX provider for a business
     */
    public function getOnlinePbxProvider(string $businessId): ?OnlinePbxProvider
    {
        $account = PbxAccount::where('business_id', $businessId)
            ->where('is_active', true)
            ->first();

        if (!$account) {
            return null;
        }

        $provider = new OnlinePbxProvider($this->onlinePbxService);
        $provider->setAccount($account);

        return $provider;
    }

    /**
     * Get all configured providers for a business
     *
     * @return array<string, array{provider: TelephonyProviderInterface, account: mixed}>
     */
    public function getAllProviders(string $businessId): array
    {
        $providers = [];

        // OnlinePBX
        $pbxAccount = PbxAccount::where('business_id', $businessId)->first();
        if ($pbxAccount) {
            $provider = new OnlinePbxProvider($this->onlinePbxService);
            $provider->setAccount($pbxAccount);
            $providers['onlinepbx'] = [
                'provider' => $provider,
                'account' => $pbxAccount,
                'is_active' => $pbxAccount->is_active,
            ];
        }

        // UTEL
        $utelAccount = UtelAccount::where('business_id', $businessId)->first();
        if ($utelAccount) {
            $provider = new UtelProvider();
            $provider->setAccount($utelAccount);
            $providers['utel'] = [
                'provider' => $provider,
                'account' => $utelAccount,
                'is_active' => $utelAccount->is_active,
            ];
        }

        return $providers;
    }

    /**
     * Get webhook URLs for all providers
     */
    public function getWebhookUrls(string $businessId): array
    {
        $baseUrl = config('app.url');

        return [
            'onlinepbx' => "{$baseUrl}/api/webhooks/pbx/onlinepbx/{$businessId}",
            'utel' => "{$baseUrl}/api/webhooks/utel/{$businessId}",
            'moizvonki' => "{$baseUrl}/api/webhooks/moizvonki/{$businessId}",
            'sipuni' => "{$baseUrl}/api/webhooks/sipuni/{$businessId}",
        ];
    }

    /**
     * Get supported provider names
     */
    public function getSupportedProviders(): array
    {
        return [
            'onlinepbx' => [
                'name' => 'OnlinePBX',
                'description' => 'Rossiya va MDH uchun bulutli ATS',
                'country' => 'RU',
                'implemented' => true,
            ],
            'utel' => [
                'name' => 'UTEL',
                'description' => 'O\'zbekiston IP telefoniya',
                'country' => 'UZ',
                'implemented' => true,
            ],
            'sipuni' => [
                'name' => 'SipUni',
                'description' => 'Rossiya SIP provayderi',
                'country' => 'RU',
                'implemented' => false,
            ],
            'moizvonki' => [
                'name' => 'Moi Zvonki',
                'description' => 'Rossiya VoIP xizmati',
                'country' => 'RU',
                'implemented' => false,
            ],
        ];
    }
}

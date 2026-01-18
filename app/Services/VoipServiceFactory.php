<?php

namespace App\Services;

use App\Contracts\PbxServiceInterface;
use App\Models\Business;
use App\Models\PbxAccount;
use App\Models\SipuniAccount;
use Illuminate\Support\Facades\Log;

/**
 * Factory for creating VoIP service instances
 * Supports multiple VoIP providers (OnlinePBX, SipUni, etc.)
 */
class VoipServiceFactory
{
    /**
     * Supported VoIP providers
     */
    public const PROVIDER_ONLINEPBX = 'onlinepbx';

    public const PROVIDER_SIPUNI = 'sipuni';

    /**
     * Get all active VoIP services for a business
     *
     * @return array<PbxServiceInterface>
     */
    public static function getServicesForBusiness(Business $business): array
    {
        $services = [];

        // Check for OnlinePBX
        $pbxAccount = PbxAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        if ($pbxAccount) {
            $service = app(OnlinePbxService::class);
            $service->setAccount($pbxAccount);
            $services[self::PROVIDER_ONLINEPBX] = $service;
        }

        // Check for SipUni
        $sipuniAccount = SipuniAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->first();

        if ($sipuniAccount) {
            // TODO: Implement SipuniService
            // $service = app(SipuniService::class);
            // $service->setAccount($sipuniAccount);
            // $services[self::PROVIDER_SIPUNI] = $service;
        }

        return $services;
    }

    /**
     * Get a specific VoIP service by provider name
     */
    public static function getService(string $provider, Business $business): ?PbxServiceInterface
    {
        switch ($provider) {
            case self::PROVIDER_ONLINEPBX:
                $account = PbxAccount::where('business_id', $business->id)
                    ->where('is_active', true)
                    ->first();

                if ($account) {
                    $service = app(OnlinePbxService::class);
                    $service->setAccount($account);

                    return $service;
                }
                break;

            case self::PROVIDER_SIPUNI:
                // TODO: Implement when SipuniService is ready
                break;
        }

        return null;
    }

    /**
     * Check which VoIP providers are active for a business
     *
     * @return array<string, bool>
     */
    public static function getActiveProviders(Business $business): array
    {
        return [
            self::PROVIDER_ONLINEPBX => PbxAccount::where('business_id', $business->id)
                ->where('is_active', true)
                ->exists(),
            self::PROVIDER_SIPUNI => SipuniAccount::where('business_id', $business->id)
                ->where('is_active', true)
                ->exists(),
        ];
    }

    /**
     * Get provider display name
     */
    public static function getProviderName(string $provider): string
    {
        return match ($provider) {
            self::PROVIDER_ONLINEPBX => 'OnlinePBX',
            self::PROVIDER_SIPUNI => 'SipUni',
            default => ucfirst($provider),
        };
    }

    /**
     * Sync calls from all active VoIP providers for a business
     *
     * @return array{synced: int, errors: array}
     */
    public static function syncAllProviders(Business $business, \Carbon\Carbon $dateFrom): array
    {
        $totalSynced = 0;
        $errors = [];

        $services = self::getServicesForBusiness($business);

        foreach ($services as $provider => $service) {
            try {
                $result = $service->syncCallHistory($dateFrom);
                $totalSynced += $result['synced'] ?? 0;

                if (! empty($result['error'])) {
                    $errors[$provider] = $result['error'];
                }

                Log::info("VoIP sync completed for {$provider}", [
                    'business_id' => $business->id,
                    'synced' => $result['synced'] ?? 0,
                ]);
            } catch (\Exception $e) {
                $errors[$provider] = $e->getMessage();
                Log::error("VoIP sync failed for {$provider}", [
                    'business_id' => $business->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'synced' => $totalSynced,
            'errors' => $errors,
        ];
    }
}

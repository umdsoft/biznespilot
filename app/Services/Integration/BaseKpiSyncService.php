<?php

declare(strict_types=1);

namespace App\Services\Integration;

use App\Services\Integration\Contracts\KpiSyncServiceInterface;

/**
 * BaseKpiSyncService
 *
 * Integratsiya KPI sinxronizatsiya xizmatlari uchun base class.
 * Facebook/Instagram/Pos kabi konkret implementatsiyalar shu klassdan
 * meros oladi va `KpiSyncServiceInterface` interface'ini bajaradi.
 *
 * Hozircha bu base class umumiy yordamchi metodlar uchun joy egallaydi —
 * konkret children'lar barcha interface metodlarini o'zlari implement
 * qiladi (parent::method() chaqiruvi yo'q). Class faqat type-hint va
 * polymorphism uchun mavjud.
 *
 * Kelajakda umumiy logika (rate limit, circuit breaker, sync monitoring)
 * shu yerda joylashtirilishi mumkin.
 */
abstract class BaseKpiSyncService implements KpiSyncServiceInterface
{
    /**
     * Default supported KPI codes — children override qiladi.
     *
     * @var array<int, string>
     */
    protected array $supportedKpis = [];

    public function getSupportedKpis(): array
    {
        return $this->supportedKpis;
    }
}

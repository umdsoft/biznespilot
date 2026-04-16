<?php

namespace App\Services\Agent\CallCenter\Tools;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Qo'ng'iroq markazi va operator dashboard tahlili
 *
 * Nodira (Sifat) agentiga chuqur ma'lumot beradi.
 */
class OperatorDashboardTool
{
    public function analyze(string $businessId): array
    {
        $cacheKey = "operator_dashboard:{$businessId}";

        return Cache::remember($cacheKey, 300, function () use ($businessId) {
            try {
                $hasCallTable = DB::getSchemaBuilder()->hasTable('call_analyses');
                $hasOperatorTable = DB::getSchemaBuilder()->hasTable('operators');

                $result = [
                    'success' => true,
                    'has_telephony' => false,
                    'operators_count' => 0,
                    'calls_30d' => 0,
                    'avg_score' => 0,
                ];

                // Operatorlar
                if ($hasOperatorTable) {
                    $result['operators_count'] = DB::table('operators')
                        ->where('business_id', $businessId)
                        ->count();
                }

                // Qo'ng'iroq tahlillari
                if ($hasCallTable) {
                    $calls = DB::table('call_analyses')
                        ->where('business_id', $businessId)
                        ->where('created_at', '>=', now()->subDays(30))
                        ->selectRaw('COUNT(*) as cnt, AVG(overall_score) as avg_score')
                        ->first();

                    $result['has_telephony'] = ($calls->cnt ?? 0) > 0;
                    $result['calls_30d'] = (int) ($calls->cnt ?? 0);
                    $result['avg_score'] = round($calls->avg_score ?? 0);
                }

                // Telephoniya integratsiyasi
                $hasIntegration = DB::table('integrations')
                    ->where('business_id', $businessId)
                    ->whereIn('provider', ['sipuni', 'utel'])
                    ->where('is_active', true)
                    ->exists();
                $result['integration_active'] = $hasIntegration;

                return $result;
            } catch (\Exception $e) {
                Log::warning('OperatorDashboardTool xato', ['error' => $e->getMessage()]);
                return ['success' => false, 'error' => $e->getMessage()];
            }
        });
    }

    public function asContext(string $businessId): string
    {
        $a = $this->analyze($businessId);
        if (!($a['success'] ?? false)) return '';

        $parts = ['QO\'NG\'IROQ MARKAZI:'];
        $parts[] = '- IP telefoniya: ' . ($a['integration_active'] ? 'ulangan ✅' : 'ulanmagan ❌');
        $parts[] = "- Operatorlar: {$a['operators_count']} ta";
        $parts[] = "- 30 kunlik qo'ng'iroqlar: {$a['calls_30d']}";
        if ($a['avg_score'] > 0) {
            $parts[] = "- O'rtacha sifat balli: {$a['avg_score']}/100";
        }

        return implode("\n", $parts);
    }
}

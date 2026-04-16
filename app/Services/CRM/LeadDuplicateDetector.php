<?php

namespace App\Services\CRM;

use App\Models\Lead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Lead Duplicate Detector — bir xil mijozlarni topish va birlashtirish.
 *
 * Strategiya:
 *   - Telefon raqam (oxirgi 9 raqam) match
 *   - Email match
 *   - Name + phone fuzzy match
 */
class LeadDuplicateDetector
{
    /**
     * Bitta lid uchun dublikatlarni topish
     */
    public function findDuplicates(Lead $lead): array
    {
        $duplicates = collect();

        // Telefon orqali
        if ($lead->phone) {
            $clean = preg_replace('/[^0-9]/', '', $lead->phone);
            $last9 = substr($clean, -9);

            if (strlen($last9) >= 9) {
                $byPhone = Lead::where('business_id', $lead->business_id)
                    ->where('id', '!=', $lead->id)
                    ->where(function ($q) use ($last9) {
                        $q->where('phone', 'LIKE', '%' . $last9);
                    })
                    ->limit(10)
                    ->get();

                $duplicates = $duplicates->merge($byPhone);
            }
        }

        // Email orqali
        if ($lead->email) {
            $byEmail = Lead::where('business_id', $lead->business_id)
                ->where('id', '!=', $lead->id)
                ->where('email', $lead->email)
                ->limit(5)
                ->get();

            $duplicates = $duplicates->merge($byEmail);
        }

        return $duplicates->unique('id')->values()->toArray();
    }

    /**
     * Biznesdagi barcha duplicate guruhlarni topish
     */
    public function findAllDuplicates(string $businessId): array
    {
        // Telefon raqamlar bo'yicha guruhlash (oxirgi 9 raqam)
        $leads = Lead::where('business_id', $businessId)
            ->whereNotNull('phone')
            ->get(['id', 'name', 'phone', 'email', 'status', 'created_at']);

        $byPhone = [];
        foreach ($leads as $lead) {
            $clean = preg_replace('/[^0-9]/', '', $lead->phone);
            $last9 = substr($clean, -9);
            if (strlen($last9) < 9) continue;

            if (!isset($byPhone[$last9])) {
                $byPhone[$last9] = [];
            }
            $byPhone[$last9][] = $lead;
        }

        // Faqat 2+ ta lid bo'lganlar
        $duplicateGroups = [];
        foreach ($byPhone as $phone => $group) {
            if (count($group) >= 2) {
                $duplicateGroups[] = [
                    'phone' => '+998' . $phone,
                    'count' => count($group),
                    'leads' => array_map(fn($l) => [
                        'id' => $l->id,
                        'name' => $l->name,
                        'phone' => $l->phone,
                        'email' => $l->email,
                        'status' => $l->status,
                        'created_at' => $l->created_at,
                    ], $group),
                ];
            }
        }

        usort($duplicateGroups, fn($a, $b) => $b['count'] - $a['count']);

        return [
            'total_groups' => count($duplicateGroups),
            'total_duplicates' => array_sum(array_column($duplicateGroups, 'count')),
            'groups' => $duplicateGroups,
        ];
    }

    /**
     * Lidlarni birlashtirish (eng eskini saqlash, qolganlarni soft-delete)
     */
    public function mergeDuplicates(array $leadIds): array
    {
        if (count($leadIds) < 2) {
            return ['success' => false, 'error' => 'Kamida 2 ta lid kerak'];
        }

        try {
            $leads = Lead::whereIn('id', $leadIds)->orderBy('created_at')->get();
            if ($leads->count() < 2) {
                return ['success' => false, 'error' => 'Lidlar topilmadi'];
            }

            // Birinchi (eng eski) — master
            $master = $leads->first();
            $duplicates = $leads->slice(1);

            // Master'ni eng to'liq ma'lumot bilan yangilash
            $updates = [];
            foreach ($duplicates as $dup) {
                if (!$master->email && $dup->email) $updates['email'] = $dup->email;
                if (!$master->company && $dup->company) $updates['company'] = $dup->company;
                if (($master->score ?? 0) < ($dup->score ?? 0)) $updates['score'] = $dup->score;
                if (($master->estimated_value ?? 0) < ($dup->estimated_value ?? 0)) {
                    $updates['estimated_value'] = $dup->estimated_value;
                }
            }
            if (!empty($updates)) {
                $master->update($updates);
            }

            // Activity'larni master'ga ko'chirish
            DB::table('lead_activities')
                ->whereIn('lead_id', $duplicates->pluck('id'))
                ->update(['lead_id' => $master->id]);

            // Call_logs ni master'ga
            DB::table('call_logs')
                ->whereIn('lead_id', $duplicates->pluck('id'))
                ->update(['lead_id' => $master->id]);

            // Duplicate'larni o'chirish
            $deletedIds = $duplicates->pluck('id')->toArray();
            Lead::whereIn('id', $deletedIds)->delete();

            return [
                'success' => true,
                'master_id' => $master->id,
                'merged_count' => count($deletedIds),
                'deleted_ids' => $deletedIds,
            ];
        } catch (\Exception $e) {
            Log::error('Lead merge xato', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

<?php

namespace App\Services\Agent;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TaskProposalService
{
    /**
     * AI javobidan vazifalar ro'yxatini yaratish
     */
    public function extractTasksFromInspection(string $businessId): array
    {
        $inspector = app(\App\Services\Agent\Knowledge\BusinessDataInspector::class);
        $data = $inspector->inspect($businessId);

        $tasks = [];

        // Marketing missing
        foreach ($data['marketing']['missing'] ?? [] as $missing) {
            $tasks[] = [
                'title' => $this->cleanMissing($missing),
                'priority' => 'high',
                'estimated_minutes' => 15,
                'due_days' => 1,
            ];
        }

        // Sales missing
        foreach ($data['sales']['missing'] ?? [] as $missing) {
            $tasks[] = [
                'title' => $this->cleanMissing($missing),
                'priority' => 'high',
                'estimated_minutes' => 15,
                'due_days' => 1,
            ];
        }

        // Analytics missing
        foreach ($data['analytics']['missing'] ?? [] as $missing) {
            $tasks[] = [
                'title' => $this->cleanMissing($missing),
                'priority' => 'medium',
                'estimated_minutes' => 20,
                'due_days' => 3,
            ];
        }

        return $tasks;
    }

    /**
     * Vazifalarni DB ga saqlash
     */
    public function createTasks(array $tasks, string $businessId, string $userId): int
    {
        $created = 0;

        foreach ($tasks as $task) {
            try {
                DB::table('todos')->insert([
                    'id' => Str::uuid(),
                    'business_id' => $businessId,
                    'user_id' => $userId,
                    'title' => $task['title'],
                    'priority' => $task['priority'] ?? 'medium',
                    'status' => 'pending',
                    'due_date' => now()->addDays($task['due_days'] ?? 1),
                    'source' => 'ai_agent',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $created++;
            } catch (\Exception $e) {
                Log::warning('TaskProposal: vazifa yaratishda xato', ['error' => $e->getMessage()]);
            }
        }

        return $created;
    }

    private function cleanMissing(string $text): string
    {
        // "(Bosh sahifa > Lidlar)" qismini olib tashlash
        return trim(preg_replace('/\(.*?\)/', '', $text));
    }
}

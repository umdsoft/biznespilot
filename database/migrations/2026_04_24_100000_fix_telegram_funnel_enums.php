<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Telegram Funnel tizimidagi 3 ta buzuq ENUM'ni tuzatadi (backend audit).
 *
 * 1. `telegram_funnel_steps.step_type` — `trigger_keyword` qo'shilmagan,
 *    lekin engine ishlatadi → MariaDB strict mode'da INSERT fail.
 * 2. `telegram_triggers.type` — `text` va `start_payload` qo'shilmagan,
 *    engine ularni query qiladi, lekin ENUM ruxsat bermaydi → hech qachon
 *    mos kelmaydi.
 * 3. `telegram_user_states.waiting_for` — `contact`, `subscribe_check`,
 *    `quiz_answer` qo'shilmagan, engine ularni yozadi → DB strict error.
 *
 * Safe: ENUM kengaytirish eski qiymatlarga ta'sir qilmaydi.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. telegram_funnel_steps.step_type — trigger_keyword qo'shamiz
        if (Schema::hasColumn('telegram_funnel_steps', 'step_type')) {
            DB::statement("
                ALTER TABLE telegram_funnel_steps
                MODIFY COLUMN step_type ENUM(
                    'message', 'input', 'condition', 'action', 'delay',
                    'subscribe_check', 'quiz', 'ab_test', 'tag',
                    'trigger_keyword', 'start'
                ) DEFAULT 'message'
            ");
        }

        // 2. telegram_triggers.type — text va start_payload qo'shamiz
        if (Schema::hasColumn('telegram_triggers', 'type')) {
            DB::statement("
                ALTER TABLE telegram_triggers
                MODIFY COLUMN type ENUM(
                    'command', 'callback', 'keyword', 'event',
                    'text', 'start_payload'
                )
            ");
        }

        // 3. telegram_user_states.waiting_for — contact/subscribe_check/quiz_answer
        if (Schema::hasColumn('telegram_user_states', 'waiting_for')) {
            DB::statement("
                ALTER TABLE telegram_user_states
                MODIFY COLUMN waiting_for ENUM(
                    'none', 'callback', 'text', 'phone', 'email', 'number',
                    'photo', 'location', 'any',
                    'contact', 'subscribe_check', 'quiz_answer'
                ) DEFAULT 'none'
            ");
        }
    }

    public function down(): void
    {
        // Reverse qilmaymiz — eski ENUM'ni tiklash yangi qiymatlar bilan
        // yozilgan row'larni sindiradi. Migration idempotent, down no-op.
    }
};

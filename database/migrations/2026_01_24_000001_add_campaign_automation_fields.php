<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            if (!Schema::hasColumn('campaigns', 'channel')) {
                $table->string('channel', 50)->nullable()->after('type');
            }
            if (!Schema::hasColumn('campaigns', 'message_template')) {
                $table->text('message_template')->nullable()->after('description');
            }
            if (!Schema::hasColumn('campaigns', 'schedule_type')) {
                $table->string('schedule_type', 20)->default('immediate')->after('message_template');
            }
            if (!Schema::hasColumn('campaigns', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('schedule_type');
            }
            if (!Schema::hasColumn('campaigns', 'sent_count')) {
                $table->integer('sent_count')->default(0)->after('metrics');
            }
            if (!Schema::hasColumn('campaigns', 'failed_count')) {
                $table->integer('failed_count')->default(0)->after('sent_count');
            }
            if (!Schema::hasColumn('campaigns', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('failed_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'channel',
                'message_template',
                'schedule_type',
                'scheduled_at',
                'sent_count',
                'failed_count',
                'completed_at',
            ]);
        });
    }
};

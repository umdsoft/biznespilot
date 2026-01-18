<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add algorithmic reports columns to existing generated_reports table
     */
    public function up(): void
    {
        // Add new columns to existing generated_reports table
        Schema::table('generated_reports', function (Blueprint $table) {
            // New relationships (if not exists)
            if (! Schema::hasColumn('generated_reports', 'user_id')) {
                $table->uuid('user_id')->nullable()->after('business_id');
            }
            if (! Schema::hasColumn('generated_reports', 'report_schedule_id')) {
                $table->unsignedBigInteger('report_schedule_id')->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('generated_reports', 'report_template_id')) {
                $table->unsignedBigInteger('report_template_id')->nullable()->after('report_schedule_id');
            }

            // Report identification
            if (! Schema::hasColumn('generated_reports', 'title')) {
                $table->string('title')->nullable()->after('report_type');
            }
            if (! Schema::hasColumn('generated_reports', 'type')) {
                $table->string('type', 20)->default('manual')->after('title');
            }
            if (! Schema::hasColumn('generated_reports', 'category')) {
                $table->string('category', 50)->default('comprehensive')->after('type');
            }

            // Period covered
            if (! Schema::hasColumn('generated_reports', 'period_start')) {
                $table->date('period_start')->nullable()->after('category');
            }
            if (! Schema::hasColumn('generated_reports', 'period_end')) {
                $table->date('period_end')->nullable()->after('period_start');
            }
            if (! Schema::hasColumn('generated_reports', 'period_type')) {
                $table->string('period_type', 20)->default('weekly')->after('period_end');
            }

            // Report data
            if (! Schema::hasColumn('generated_reports', 'metrics_data')) {
                $table->json('metrics_data')->nullable()->after('period_type');
            }
            if (! Schema::hasColumn('generated_reports', 'trends_data')) {
                $table->json('trends_data')->nullable()->after('metrics_data');
            }
            if (! Schema::hasColumn('generated_reports', 'insights')) {
                $table->json('insights')->nullable()->after('trends_data');
            }
            if (! Schema::hasColumn('generated_reports', 'recommendations')) {
                $table->json('recommendations')->nullable()->after('insights');
            }
            if (! Schema::hasColumn('generated_reports', 'comparisons')) {
                $table->json('comparisons')->nullable()->after('recommendations');
            }
            if (! Schema::hasColumn('generated_reports', 'anomalies')) {
                $table->json('anomalies')->nullable()->after('comparisons');
            }

            // Health score
            if (! Schema::hasColumn('generated_reports', 'health_score')) {
                $table->decimal('health_score', 5, 2)->nullable()->after('anomalies');
            }
            if (! Schema::hasColumn('generated_reports', 'health_breakdown')) {
                $table->json('health_breakdown')->nullable()->after('health_score');
            }

            // Output formats
            if (! Schema::hasColumn('generated_reports', 'content_text')) {
                $table->longText('content_text')->nullable()->after('health_breakdown');
            }
            if (! Schema::hasColumn('generated_reports', 'content_html')) {
                $table->longText('content_html')->nullable()->after('content_text');
            }
            if (! Schema::hasColumn('generated_reports', 'excel_path')) {
                $table->string('excel_path')->nullable()->after('content_html');
            }

            // Delivery tracking
            if (! Schema::hasColumn('generated_reports', 'delivery_status')) {
                $table->json('delivery_status')->nullable();
            }
            if (! Schema::hasColumn('generated_reports', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable();
            }
            if (! Schema::hasColumn('generated_reports', 'delivery_errors')) {
                $table->json('delivery_errors')->nullable();
            }

            // Metadata
            if (! Schema::hasColumn('generated_reports', 'language')) {
                $table->string('language', 5)->default('uz');
            }
            if (! Schema::hasColumn('generated_reports', 'generation_time_ms')) {
                $table->unsignedInteger('generation_time_ms')->nullable();
            }
            if (! Schema::hasColumn('generated_reports', 'metadata')) {
                $table->json('metadata')->nullable();
            }

            // Status
            if (! Schema::hasColumn('generated_reports', 'status')) {
                $table->string('status', 20)->default('completed');
            }
            if (! Schema::hasColumn('generated_reports', 'error_message')) {
                $table->text('error_message')->nullable();
            }
            if (! Schema::hasColumn('generated_reports', 'view_count')) {
                $table->unsignedInteger('view_count')->default(0);
            }
            if (! Schema::hasColumn('generated_reports', 'last_viewed_at')) {
                $table->timestamp('last_viewed_at')->nullable();
            }
            if (! Schema::hasColumn('generated_reports', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Add foreign keys separately (only if tables exist)
        if (Schema::hasTable('report_schedules') && Schema::hasColumn('generated_reports', 'report_schedule_id')) {
            Schema::table('generated_reports', function (Blueprint $table) {
                $table->foreign('report_schedule_id')->references('id')->on('report_schedules')->nullOnDelete();
            });
        }

        if (Schema::hasTable('report_templates') && Schema::hasColumn('generated_reports', 'report_template_id')) {
            Schema::table('generated_reports', function (Blueprint $table) {
                $table->foreign('report_template_id')->references('id')->on('report_templates')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generated_reports', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['report_schedule_id']);
            $table->dropForeign(['report_template_id']);

            // Drop columns
            $columns = [
                'user_id', 'report_schedule_id', 'report_template_id',
                'title', 'type', 'category',
                'period_start', 'period_end', 'period_type',
                'metrics_data', 'trends_data', 'insights', 'recommendations', 'comparisons', 'anomalies',
                'health_score', 'health_breakdown',
                'content_text', 'content_html', 'excel_path',
                'delivery_status', 'delivered_at', 'delivery_errors',
                'language', 'generation_time_ms', 'metadata',
                'status', 'error_message', 'view_count', 'last_viewed_at',
                'deleted_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('generated_reports', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

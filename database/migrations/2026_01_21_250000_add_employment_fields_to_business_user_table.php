<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Xodim lavozimi, oylik va ish turi uchun yangi ustunlar
     */
    public function up(): void
    {
        Schema::table('business_user', function (Blueprint $table) {
            // Lavozim (JobDescription ga bog'langan)
            if (!Schema::hasColumn('business_user', 'job_description_id')) {
                $table->uuid('job_description_id')->nullable()->after('department');
            }

            // Oylik maosh
            if (!Schema::hasColumn('business_user', 'salary')) {
                $table->decimal('salary', 15, 2)->nullable()->after('job_description_id');
            }

            // Ish turi (full_time, part_time, contract, etc.)
            if (!Schema::hasColumn('business_user', 'employment_type')) {
                $table->string('employment_type', 50)->default('full_time')->after('salary');
            }

            // Shartnoma turi
            if (!Schema::hasColumn('business_user', 'contract_type')) {
                $table->string('contract_type', 50)->default('unlimited')->after('employment_type');
            }

            // Shartnoma boshlanish sanasi
            if (!Schema::hasColumn('business_user', 'contract_start_date')) {
                $table->date('contract_start_date')->nullable()->after('contract_type');
            }

            // Shartnoma tugash sanasi
            if (!Schema::hasColumn('business_user', 'contract_end_date')) {
                $table->date('contract_end_date')->nullable()->after('contract_start_date');
            }
        });

        // Foreign key qo'shish
        try {
            Schema::table('business_user', function (Blueprint $table) {
                $table->foreign('job_description_id')
                    ->references('id')
                    ->on('job_descriptions')
                    ->onDelete('set null');
            });
        } catch (\Exception $e) {
            // Foreign key already exists, skip
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_user', function (Blueprint $table) {
            try {
                $table->dropForeign(['job_description_id']);
            } catch (\Exception $e) {
                // Ignore if doesn't exist
            }

            $columns = ['job_description_id', 'salary', 'employment_type', 'contract_type', 'contract_start_date', 'contract_end_date'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('business_user', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

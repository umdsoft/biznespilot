<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lead Qualification tarixi - MQL/SQL o'tishlarini kuzatish
     */
    public function up(): void
    {
        Schema::create('lead_qualifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('lead_id');

            $table->enum('from_status', ['new', 'mql', 'sql', 'disqualified']);
            $table->enum('to_status', ['new', 'mql', 'sql', 'disqualified']);

            $table->uuid('qualified_by')->nullable();

            $table->text('reason')->nullable();
            $table->json('criteria_snapshot')->nullable(); // Qualification paytidagi lead ma'lumotlari

            $table->timestamps();

            // Foreign keys
            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
                ->cascadeOnDelete();

            $table->foreign('lead_id')
                ->references('id')
                ->on('leads')
                ->cascadeOnDelete();

            $table->foreign('qualified_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Indexes
            $table->index(['business_id', 'lead_id'], 'lead_qualifications_business_lead_idx');
            $table->index(['business_id', 'to_status', 'created_at'], 'lead_qualifications_status_date_idx');
            $table->index(['business_id', 'created_at'], 'lead_qualifications_business_date_idx');
        });
    }

    /**
     * Rollback
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_qualifications');
    }
};

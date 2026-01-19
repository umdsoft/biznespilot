<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('competitor_insights', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();

            // Tavsiya turi
            $table->string('type', 50); // price, marketing, product, opportunity, threat, sales_script

            // Muhimlik darajasi
            $table->string('priority', 20)->default('medium'); // high, medium, low

            // Tavsiya mazmuni
            $table->string('title');
            $table->string('competitor_name')->nullable();
            $table->text('description');
            $table->text('recommendation');

            // Qo'shimcha ma'lumotlar (JSON)
            $table->json('action_data')->nullable(); // Amal qilish uchun ma'lumotlar
            $table->json('raw_data')->nullable(); // Xom tahlil ma'lumotlari

            // Status
            $table->string('status', 20)->default('active'); // active, completed, dismissed, archived

            // O'qilganlik
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();

            // Bajarilganlik
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->text('completion_notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'type']);
            $table->index(['business_id', 'priority']);
            $table->index('created_at');
        });

        // Business jadvaliga insights_generated_at qo'shish
        if (! Schema::hasColumn('businesses', 'insights_generated_at')) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->timestamp('insights_generated_at')->nullable()->after('swot_updated_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competitor_insights');

        if (Schema::hasColumn('businesses', 'insights_generated_at')) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->dropColumn('insights_generated_at');
            });
        }
    }
};

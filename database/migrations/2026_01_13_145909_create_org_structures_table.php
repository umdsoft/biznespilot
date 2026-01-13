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
        Schema::create('org_structures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignId('business_type_id')->nullable()->constrained('business_types');
            $table->string('name')->default('Asosiy struktura'); // Filiallar uchun: "Toshkent filiali", etc
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_template_based')->default(false); // Template'dan yaratilganmi?
            $table->foreignId('created_from_template_id')->nullable()->constrained('business_types'); // Qaysi template asosida
            $table->timestamps();

            // Index
            $table->index(['business_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('org_structures');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('step_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->integer('phase')->default(1);
            $table->enum('category', ['profile', 'integration', 'framework']);
            $table->string('name_uz');
            $table->string('name_en');
            $table->text('description_uz')->nullable();
            $table->text('description_en')->nullable();
            $table->boolean('is_required')->default(true);
            $table->json('depends_on')->nullable();
            $table->json('required_fields')->nullable();
            $table->json('completion_rules')->nullable();
            $table->string('icon', 50)->nullable();
            $table->integer('estimated_time_minutes')->default(5);
            $table->string('help_url', 500)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['phase', 'category', 'sort_order']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('step_definitions');
    }
};

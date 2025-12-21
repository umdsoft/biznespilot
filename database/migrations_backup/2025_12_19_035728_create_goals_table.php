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
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Goal owner
            $table->enum('type', ['revenue', 'leads', 'customers', 'orders', 'traffic', 'conversion_rate', 'custom'])->default('custom');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('target_value', 15, 2);
            $table->decimal('current_value', 15, 2)->default(0);
            $table->string('unit')->default('number'); // number, percentage, currency, etc.
            $table->date('start_date')->nullable();
            $table->date('deadline');
            $table->enum('status', ['not_started', 'in_progress', 'completed', 'failed', 'canceled'])->default('not_started');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->json('milestones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'status']);
            $table->index('deadline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};

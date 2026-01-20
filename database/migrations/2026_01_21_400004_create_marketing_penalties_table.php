<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_penalties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            // Bonus reference
            $table->foreignUuid('bonus_id')
                ->nullable()
                ->constrained('marketing_bonuses')
                ->cascadeOnDelete();

            // Penalty details
            $table->date('date');
            $table->string('type', 50);
            $table->string('reason');
            $table->text('description')->nullable();

            // Amount
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('percentage', 5, 2)->default(0);

            // Reference
            $table->string('reference_type')->nullable();
            $table->uuid('reference_id')->nullable();

            // Status
            $table->enum('status', ['pending', 'applied', 'disputed', 'waived'])->default('pending');
            $table->foreignUuid('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->text('dispute_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['business_id', 'user_id', 'date']);
            $table->index(['business_id', 'type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_penalties');
    }
};

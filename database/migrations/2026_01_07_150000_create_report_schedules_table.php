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
        Schema::create('report_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            // Schedule configuration
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('frequency', ['daily', 'weekly', 'monthly'])->default('weekly');
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])->nullable();
            $table->unsignedTinyInteger('day_of_month')->nullable();
            $table->time('send_time')->default('09:00:00');
            $table->string('timezone')->default('Asia/Tashkent');

            // Report configuration
            $table->enum('report_type', ['summary', 'detailed', 'executive', 'custom'])->default('summary');
            $table->json('metrics')->nullable()->comment('Selected metrics to include');
            $table->json('sections')->nullable()->comment('Report sections configuration');
            $table->enum('period', ['daily', 'weekly', 'monthly', 'quarterly'])->default('weekly');
            $table->boolean('include_trends')->default(true);
            $table->boolean('include_insights')->default(true);
            $table->boolean('include_recommendations')->default(true);
            $table->boolean('include_comparison')->default(true);

            // Delivery configuration
            $table->json('delivery_channels')->nullable()->comment('telegram, email, etc.');
            $table->string('telegram_chat_id')->nullable();
            $table->string('email')->nullable();
            $table->enum('format', ['text', 'pdf', 'excel', 'html'])->default('text');
            $table->string('language', 5)->default('uz');

            // Status and tracking
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('next_scheduled_at')->nullable();
            $table->unsignedInteger('send_count')->default(0);
            $table->unsignedInteger('failure_count')->default(0);
            $table->text('last_error')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['business_id', 'is_active']);
            $table->index(['next_scheduled_at', 'is_active']);
            $table->index('frequency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_schedules');
    }
};

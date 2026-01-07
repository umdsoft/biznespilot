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
        Schema::create('lead_forms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->foreignId('default_source_id')->nullable()->constrained('lead_sources')->nullOnDelete();

            // Form identity
            $table->string('name'); // Internal name
            $table->string('title'); // Public title
            $table->text('description')->nullable();
            $table->string('slug')->unique();

            // Form configuration
            $table->json('fields'); // Array of field configurations
            $table->string('submit_button_text')->default('Yuborish');
            $table->string('theme_color')->default('#6366f1');

            // Lead Magnet configuration
            $table->enum('lead_magnet_type', ['none', 'file', 'link', 'coupon', 'text'])->default('none');
            $table->string('lead_magnet_title')->nullable(); // "Sizning sovg'angiz"
            $table->string('lead_magnet_file')->nullable(); // File path for download
            $table->string('lead_magnet_link')->nullable(); // External URL
            $table->text('lead_magnet_text')->nullable(); // Coupon code or custom text

            // After submission
            $table->text('success_message')->default('Rahmat! Ma\'lumotlaringiz qabul qilindi.');
            $table->string('redirect_url')->nullable(); // Optional redirect after success
            $table->boolean('show_lead_magnet_on_success')->default(true);

            // Lead settings
            $table->string('default_status')->default('new');
            $table->integer('default_score')->default(50);

            // Tracking
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('submissions_count')->default(0);

            // UTM tracking
            $table->boolean('track_utm')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('business_id');
            $table->index('slug');
            $table->index('is_active');
            $table->index(['business_id', 'is_active']);

            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
                ->onDelete('cascade');
        });

        // Lead form submissions tracking (for analytics)
        Schema::create('lead_form_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lead_form_id');
            $table->uuid('lead_id')->nullable(); // Created lead reference
            $table->uuid('business_id');

            // Submission data
            $table->json('form_data'); // Raw form submission
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device_type')->nullable();
            $table->string('referrer')->nullable();

            // UTM parameters
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();

            // Lead magnet delivery
            $table->boolean('lead_magnet_delivered')->default(false);
            $table->timestamp('lead_magnet_downloaded_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('lead_form_id');
            $table->index('lead_id');
            $table->index('business_id');
            $table->index('created_at');

            $table->foreign('lead_form_id')
                ->references('id')
                ->on('lead_forms')
                ->onDelete('cascade');

            $table->foreign('lead_id')
                ->references('id')
                ->on('leads')
                ->onDelete('set null');

            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_form_submissions');
        Schema::dropIfExists('lead_forms');
    }
};

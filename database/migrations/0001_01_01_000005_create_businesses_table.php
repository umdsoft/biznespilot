<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id'); // Owner
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category', 100)->nullable();
            $table->uuid('industry_id')->nullable();
            $table->uuid('sub_industry_id')->nullable();
            $table->string('business_type', 50)->nullable();
            $table->string('business_model', 50)->nullable();
            $table->string('business_stage', 50)->nullable();
            $table->date('founding_date')->nullable();
            $table->string('team_size', 20)->nullable();
            $table->string('employee_count', 50)->nullable();
            $table->string('monthly_revenue', 50)->nullable();
            $table->text('target_audience')->nullable();
            $table->json('main_goals')->nullable();
            $table->integer('maturity_score')->nullable();
            $table->string('maturity_level', 50)->nullable();
            $table->boolean('is_onboarding_completed')->default(false);
            $table->string('onboarding_status', 50)->default('pending');
            $table->string('onboarding_current_step', 50)->nullable();
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamp('launched_at')->nullable();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('website')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('country', 100)->default("O'zbekiston");
            $table->string('status', 20)->default('active');
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('industry_id')->references('id')->on('industries')->onDelete('set null');
            $table->foreign('sub_industry_id')->references('id')->on('industries')->onDelete('set null');

            $table->index('user_id');
            $table->index('category');
            $table->index('region');
            $table->index('status');
            $table->index('is_onboarding_completed');
            $table->index('created_at');
        });

        // Business-User pivot (team members)
        Schema::create('business_user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id');
            $table->string('role', 50)->default('member');
            $table->json('permissions')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['business_id', 'user_id']);
            $table->index('role');
        });

        // User settings
        Schema::create('user_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_settings');
        Schema::dropIfExists('business_user');
        Schema::dropIfExists('businesses');
    }
};

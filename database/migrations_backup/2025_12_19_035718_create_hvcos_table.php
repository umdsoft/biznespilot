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
        Schema::create('hvcos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('content_type', ['ebook', 'whitepaper', 'guide', 'checklist', 'template', 'video', 'webinar', 'course', 'other'])->default('other');
            $table->string('file_path')->nullable();
            $table->string('thumbnail')->nullable();
            $table->integer('downloads_count')->default(0);
            $table->integer('views_count')->default(0);
            $table->integer('leads_generated')->default(0);
            $table->boolean('requires_email')->default(true);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'is_active']);
            $table->index('content_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hvcos');
    }
};

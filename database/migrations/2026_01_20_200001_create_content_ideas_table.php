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
        // G'oyalar banki - barcha bizneslar uchun umumiy g'oyalar
        Schema::create('content_ideas', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Manba
            $table->foreignUuid('business_id')->nullable()->constrained()->nullOnDelete(); // null = global shablon
            $table->foreignUuid('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('industry_id')->nullable()->constrained()->nullOnDelete();

            // G'oya mazmuni
            $table->string('title'); // Qisqa sarlavha: "Yangi yil aksiyasi"
            $table->text('description'); // Batafsil tavsif
            $table->text('example_content')->nullable(); // Namuna matn
            $table->json('key_points')->nullable(); // Asosiy fikrlar ["chegirma", "muddat", "CTA"]
            $table->json('suggested_hashtags')->nullable();
            $table->json('suggested_emojis')->nullable();

            // Kategoriya
            $table->enum('content_type', ['post', 'story', 'reel', 'ad', 'carousel', 'article'])->default('post');
            $table->enum('purpose', ['educate', 'inspire', 'sell', 'engage', 'announce', 'entertain'])->default('engage');
            $table->string('category')->nullable(); // "aksiya", "bayram", "mahsulot", "mijoz_hikoya"...
            $table->json('tags')->nullable(); // Qo'shimcha teglar

            // Vaqt konteksti
            $table->boolean('is_seasonal')->default(false); // Mavsumiy g'oya
            $table->string('season')->nullable(); // "winter", "ramadan", "back_to_school"...
            $table->json('best_months')->nullable(); // [1, 2, 12] - yanvar, fevral, dekabr

            // Samaradorlik statistikasi (ishlatilganda yangilanadi)
            $table->integer('times_used')->default(0);
            $table->integer('times_published')->default(0);
            $table->float('avg_engagement_rate')->default(0);
            $table->float('avg_likes')->default(0);
            $table->float('avg_comments')->default(0);
            $table->float('success_rate')->default(0); // published/used ratio
            $table->float('quality_score')->default(50); // 0-100 hisoblangan ball

            // Qaysi biznes turlari uchun mos
            $table->json('suitable_industries')->nullable(); // ["retail", "restaurant", "education"]
            $table->json('suitable_business_types')->nullable(); // ["b2c", "b2b"]

            // Holat
            $table->boolean('is_global')->default(false); // Barcha bizneslar uchun
            $table->boolean('is_verified')->default(false); // Admin tasdiqlagan
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['industry_id', 'content_type']);
            $table->index(['category', 'purpose']);
            $table->index(['is_global', 'is_active']);
            $table->index('quality_score');
        });

        // G'oya ishlatilish tarixi
        Schema::create('content_idea_usages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('content_idea_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('content_generation_id')->nullable()->constrained()->nullOnDelete();

            // Natija
            $table->enum('outcome', ['draft', 'published', 'rejected'])->default('draft');
            $table->float('engagement_rate')->nullable();
            $table->integer('likes_count')->nullable();
            $table->integer('comments_count')->nullable();
            $table->integer('shares_count')->nullable();

            // Feedback
            $table->enum('user_rating', ['helpful', 'neutral', 'not_helpful'])->nullable();
            $table->text('user_notes')->nullable();

            $table->timestamps();

            $table->index(['content_idea_id', 'outcome']);
            $table->index(['business_id', 'created_at']);
        });

        // G'oyalar to'plami (kategoriyalar)
        Schema::create('content_idea_collections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('name'); // "Ramazon aksiyalari", "Yangi yil g'oyalari"
            $table->text('description')->nullable();
            $table->string('icon')->nullable(); // emoji yoki icon nomi
            $table->string('color')->nullable(); // HEX rang

            // Qaysi biznes turlari uchun
            $table->foreignUuid('industry_id')->nullable()->constrained()->nullOnDelete();
            $table->json('suitable_industries')->nullable();

            $table->boolean('is_global')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->integer('ideas_count')->default(0);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });

        // G'oya va to'plam bog'lanishi
        Schema::create('content_idea_collection_items', function (Blueprint $table) {
            $table->foreignUuid('collection_id')->constrained('content_idea_collections')->cascadeOnDelete();
            $table->foreignUuid('idea_id')->constrained('content_ideas')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->primary(['collection_id', 'idea_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_idea_collection_items');
        Schema::dropIfExists('content_idea_collections');
        Schema::dropIfExists('content_idea_usages');
        Schema::dropIfExists('content_ideas');
    }
};

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
        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->uuid('business_id');
            $table->string('name', 100);
            $table->string('slug', 50);
            $table->string('color', 50)->default('blue');
            $table->integer('order')->default(0);
            $table->boolean('is_system')->default(false); // Yangi va Won/Lost - o'zgartirib bo'lmaydi
            $table->boolean('is_won')->default(false); // Yutilgan bosqich
            $table->boolean('is_lost')->default(false); // Yo'qotilgan bosqich
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->unique(['business_id', 'slug']);
            $table->index(['business_id', 'order']);
        });

        // Har bir biznes uchun default bosqichlarni yaratish
        $businesses = \App\Models\Business::all();

        $defaultStages = [
            ['name' => 'Yangi', 'slug' => 'new', 'color' => 'blue', 'order' => 1, 'is_system' => true],
            ['name' => "Bog'lanildi", 'slug' => 'contacted', 'color' => 'indigo', 'order' => 2, 'is_system' => false],
            ['name' => 'Keyinroq bog\'lanish qilamiz', 'slug' => 'callback', 'color' => 'purple', 'order' => 3, 'is_system' => false],
            ['name' => "O'ylab ko'radi", 'slug' => 'considering', 'color' => 'orange', 'order' => 4, 'is_system' => false],
            ['name' => 'Uchrashuv belgilandi', 'slug' => 'meeting_scheduled', 'color' => 'yellow', 'order' => 5, 'is_system' => false],
            ['name' => 'Uchrashuvga keldi', 'slug' => 'meeting_attended', 'color' => 'teal', 'order' => 6, 'is_system' => false],
            ['name' => 'Sotuv', 'slug' => 'won', 'color' => 'green', 'order' => 100, 'is_system' => true, 'is_won' => true],
            ['name' => 'Sifatsiz lid', 'slug' => 'lost', 'color' => 'red', 'order' => 101, 'is_system' => true, 'is_lost' => true],
        ];

        foreach ($businesses as $business) {
            foreach ($defaultStages as $stage) {
                \DB::table('pipeline_stages')->insert([
                    'business_id' => $business->id,
                    'name' => $stage['name'],
                    'slug' => $stage['slug'],
                    'color' => $stage['color'],
                    'order' => $stage['order'],
                    'is_system' => $stage['is_system'],
                    'is_won' => $stage['is_won'] ?? false,
                    'is_lost' => $stage['is_lost'] ?? false,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pipeline_stages');
    }
};

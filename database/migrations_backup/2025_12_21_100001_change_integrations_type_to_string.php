<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SQLite da enum ustunini o'zgartirish uchun jadval qayta yaratish kerak
     */
    public function up(): void
    {
        // SQLite uchun maxsus usul
        if (DB::getDriverName() === 'sqlite') {
            // 1. Eski ma'lumotlarni saqlash
            $integrations = DB::table('integrations')->get();

            // 2. Eski jadvalni o'chirish
            Schema::dropIfExists('integrations');

            // 3. Yangi jadval yaratish (type string sifatida)
            Schema::create('integrations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->onDelete('cascade');
                $table->string('type')->default('other'); // enum o'rniga string
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(false);
                $table->string('status')->default('disconnected');
                $table->timestamp('connected_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->text('credentials')->nullable();
                $table->json('config')->nullable();
                $table->timestamp('last_sync_at')->nullable();
                $table->timestamp('last_error_at')->nullable();
                $table->text('last_error_message')->nullable();
                $table->integer('sync_count')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['business_id', 'is_active']);
                $table->index('type');
            });

            // 4. Eski ma'lumotlarni qayta tiklash
            foreach ($integrations as $integration) {
                DB::table('integrations')->insert([
                    'id' => $integration->id,
                    'business_id' => $integration->business_id,
                    'type' => $integration->type,
                    'name' => $integration->name,
                    'description' => $integration->description,
                    'is_active' => $integration->is_active,
                    'status' => $integration->status ?? 'disconnected',
                    'connected_at' => $integration->connected_at ?? null,
                    'expires_at' => $integration->expires_at ?? null,
                    'credentials' => $integration->credentials,
                    'config' => $integration->config,
                    'last_sync_at' => $integration->last_sync_at,
                    'last_error_at' => $integration->last_error_at,
                    'last_error_message' => $integration->last_error_message,
                    'sync_count' => $integration->sync_count,
                    'created_at' => $integration->created_at,
                    'updated_at' => $integration->updated_at,
                    'deleted_at' => $integration->deleted_at,
                ]);
            }
        } else {
            // MySQL/PostgreSQL uchun oddiy usul
            Schema::table('integrations', function (Blueprint $table) {
                $table->string('type')->default('other')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Qaytarib bo'lmaydi chunki ma'lumotlar yo'qolishi mumkin
    }
};

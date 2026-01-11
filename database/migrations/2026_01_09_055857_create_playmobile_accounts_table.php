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
        Schema::create('playmobile_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->string('login');
            $table->text('password'); // Will be encrypted
            $table->string('originator')->default('3700'); // Sender name
            $table->string('api_url')->default('https://send.smsxabar.uz/broker-api/send');
            $table->boolean('is_active')->default(true);
            $table->integer('balance')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['business_id']);
            $table->index(['business_id', 'is_active']);
        });

        // Add provider column to sms_messages table if it exists
        if (Schema::hasTable('sms_messages')) {
            Schema::table('sms_messages', function (Blueprint $table) {
                if (!Schema::hasColumn('sms_messages', 'provider')) {
                    $table->string('provider')->default('eskiz')->after('business_id');
                }
                if (!Schema::hasColumn('sms_messages', 'playmobile_account_id')) {
                    $table->foreignUuid('playmobile_account_id')->nullable()->after('eskiz_account_id')
                        ->constrained('playmobile_accounts')->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sms_messages')) {
            Schema::table('sms_messages', function (Blueprint $table) {
                if (Schema::hasColumn('sms_messages', 'playmobile_account_id')) {
                    $table->dropForeign(['playmobile_account_id']);
                    $table->dropColumn('playmobile_account_id');
                }
                if (Schema::hasColumn('sms_messages', 'provider')) {
                    $table->dropColumn('provider');
                }
            });
        }

        Schema::dropIfExists('playmobile_accounts');
    }
};

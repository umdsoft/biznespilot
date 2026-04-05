<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration:
     * 1. Changes column types to TEXT (encrypted strings are much longer)
     * 2. Encrypts existing plaintext credentials
     */
    public function up(): void
    {
        // First, change column types to TEXT to accommodate encrypted strings
        Schema::table('payment_accounts', function (Blueprint $table) {
            $table->text('merchant_key')->nullable()->change();
            $table->text('secret_key')->nullable()->change();
        });

        // Encrypt existing plaintext credentials using raw DB queries
        // to avoid double-encryption (since the model now auto-encrypts)
        $accounts = DB::table('payment_accounts')
            ->whereNotNull('merchant_key')
            ->orWhereNotNull('secret_key')
            ->get();

        foreach ($accounts as $account) {
            $updates = [];

            // Only encrypt if the value exists and is not already encrypted
            // Laravel encrypted strings start with 'eyJpdiI6' (base64 of '{"iv":')
            if ($account->merchant_key && !str_starts_with($account->merchant_key, 'eyJpdiI6')) {
                $updates['merchant_key'] = Crypt::encryptString($account->merchant_key);
            }

            if ($account->secret_key && !str_starts_with($account->secret_key, 'eyJpdiI6')) {
                $updates['secret_key'] = Crypt::encryptString($account->secret_key);
            }

            if (!empty($updates)) {
                DB::table('payment_accounts')
                    ->where('id', $account->id)
                    ->update($updates);
            }
        }
    }

    /**
     * Reverse the migrations.
     * 
     * Decrypts credentials back to plaintext and reverts column types.
     */
    public function down(): void
    {
        // Decrypt credentials back to plaintext
        $accounts = DB::table('payment_accounts')
            ->whereNotNull('merchant_key')
            ->orWhereNotNull('secret_key')
            ->get();

        foreach ($accounts as $account) {
            $updates = [];

            // Only decrypt if the value appears to be encrypted
            if ($account->merchant_key && str_starts_with($account->merchant_key, 'eyJpdiI6')) {
                try {
                    $updates['merchant_key'] = Crypt::decryptString($account->merchant_key);
                } catch (\Exception $e) {
                    // Value might not be encrypted or key mismatch, leave as-is
                }
            }

            if ($account->secret_key && str_starts_with($account->secret_key, 'eyJpdiI6')) {
                try {
                    $updates['secret_key'] = Crypt::decryptString($account->secret_key);
                } catch (\Exception $e) {
                    // Value might not be encrypted or key mismatch, leave as-is
                }
            }

            if (!empty($updates)) {
                DB::table('payment_accounts')
                    ->where('id', $account->id)
                    ->update($updates);
            }
        }

        // Revert column types to VARCHAR
        Schema::table('payment_accounts', function (Blueprint $table) {
            $table->string('merchant_key')->nullable()->change();
            $table->string('secret_key')->nullable()->change();
        });
    }
};

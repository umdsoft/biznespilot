<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_user', function (Blueprint $table) {
            $table->string('department', 50)->nullable()->after('role');
            $table->uuid('invited_by')->nullable()->after('joined_at');
            $table->string('invitation_token', 100)->nullable()->after('invited_by');
            $table->timestamp('invitation_expires_at')->nullable()->after('invitation_token');

            $table->foreign('invited_by')->references('id')->on('users')->onDelete('set null');
            $table->index('department');
            $table->index('invitation_token');
        });
    }

    public function down(): void
    {
        Schema::table('business_user', function (Blueprint $table) {
            $table->dropForeign(['invited_by']);
            $table->dropIndex(['department']);
            $table->dropIndex(['invitation_token']);
            $table->dropColumn(['department', 'invited_by', 'invitation_token', 'invitation_expires_at']);
        });
    }
};

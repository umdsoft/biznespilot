<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('title');
            $table->json('form_fields')->nullable()->after('requirements'); // qo'shimcha savollar
            $table->boolean('is_public')->default(true)->after('status');
            $table->text('success_message')->nullable()->after('is_public');
        });
    }

    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropColumn(['slug', 'form_fields', 'is_public', 'success_message']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->timestamp('stage_changed_at')->nullable()->after('status');
        });

        // Mavjud leadlar uchun stage_changed_at ni updated_at bilan to'ldirish
        DB::table('leads')->whereNull('stage_changed_at')->update([
            'stage_changed_at' => DB::raw('updated_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('stage_changed_at');
        });
    }
};

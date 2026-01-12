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
        Schema::table('content_calendar', function (Blueprint $table) {
            if (!Schema::hasColumn('content_calendar', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
            }
            if (!Schema::hasColumn('content_calendar', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable();
            }
            if (!Schema::hasColumn('content_calendar', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_calendar', function (Blueprint $table) {
            $columns = ['created_by', 'approved_by', 'approved_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('content_calendar', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

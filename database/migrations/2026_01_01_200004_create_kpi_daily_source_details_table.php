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
        Schema::create('kpi_daily_source_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('daily_entry_id');
            $table->foreign('daily_entry_id')->references('id')->on('kpi_daily_entries')->onDelete('cascade');
            $table->unsignedBigInteger('lead_source_id');
            $table->foreign('lead_source_id')->references('id')->on('lead_sources')->onDelete('cascade');

            $table->integer('leads_count')->default(0);
            $table->decimal('spend_amount', 15, 2)->default(0);
            $table->integer('conversions')->default(0); // Sales from this source
            $table->decimal('revenue', 15, 2)->default(0);

            $table->timestamps();

            // Unique constraint
            $table->unique(['daily_entry_id', 'lead_source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_daily_source_details');
    }
};

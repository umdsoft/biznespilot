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
        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->char('lead_id', 36);
            $table->char('user_id', 36)->nullable();
            $table->string('type'); // created, updated, status_changed, note_added, assigned, contacted, etc.
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('changes')->nullable(); // Store field changes for updates
            $table->json('metadata')->nullable(); // Additional data
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['lead_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_activities');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliverables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id')->nullable();
            $table->string('agent', 30); // imronbek, salomatxon, jasurbek, nodira
            $table->string('type', 50); // content_plan, lead_responses, roas_report, operator_training, etc.
            $table->string('title');
            $table->json('data'); // to'liq deliverable mazmuni
            $table->json('preview')->nullable(); // qisqacha ko'rish uchun
            $table->enum('status', ['pending_approval', 'approved', 'rejected', 'completed', 'expired'])->default('pending_approval');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('user_feedback')->nullable();
            $table->string('conversation_id')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'agent']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliverables');
    }
};

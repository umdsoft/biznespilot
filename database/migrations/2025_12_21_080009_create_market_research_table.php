<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_research', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->enum('research_type', ['market_size', 'competitor', 'customer', 'industry_trends']);
            $table->string('title');
            $table->enum('methodology', ['survey', 'interview', 'secondary', 'observation'])->nullable();
            $table->integer('sample_size')->nullable();
            $table->text('findings_summary')->nullable();
            $table->json('key_insights')->nullable();
            $table->json('data_sources')->nullable();
            $table->date('conducted_at')->nullable();
            $table->date('valid_until')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'research_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_research');
    }
};

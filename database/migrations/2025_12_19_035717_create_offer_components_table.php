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
        Schema::create('offer_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['core_product', 'bonus', 'guarantee', 'support', 'training', 'other'])->default('other');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('value', 10, 2)->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_highlighted')->default(false);
            $table->timestamps();

            $table->index('offer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offer_components');
    }
};

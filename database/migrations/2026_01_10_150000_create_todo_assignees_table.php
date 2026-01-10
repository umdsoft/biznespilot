<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('todo_assignees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('todo_id');
            $table->uuid('user_id');
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('todo_id')
                ->references('id')
                ->on('todos')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->unique(['todo_id', 'user_id']);
        });

        // Add team_completed_count to todos for caching
        Schema::table('todos', function (Blueprint $table) {
            $table->integer('assignees_count')->default(0)->after('is_recurring');
            $table->integer('completed_assignees_count')->default(0)->after('assignees_count');
        });
    }

    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropColumn(['assignees_count', 'completed_assignees_count']);
        });

        Schema::dropIfExists('todo_assignees');
    }
};

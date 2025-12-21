<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Widget Config
            $table->enum('widget_type', [
                'kpi_card', 'chart_line', 'chart_bar', 'chart_pie',
                'chart_funnel', 'table', 'list', 'gauge', 'heatmap'
            ]);
            $table->string('widget_code', 50);
            $table->string('title');

            // Data Source
            $table->string('data_source', 100)->nullable();
            $table->string('metric', 100)->nullable();
            $table->enum('aggregation', ['sum', 'avg', 'count', 'min', 'max'])->default('sum');
            $table->enum('period', [
                'today', 'yesterday', 'this_week', 'last_week',
                'this_month', 'last_month', 'this_quarter', 'this_year', 'custom'
            ])->default('this_month');
            $table->enum('comparison_period', [
                'previous_period', 'same_period_last_year', 'target'
            ])->nullable();

            // Layout
            $table->integer('position_x')->default(0);
            $table->integer('position_y')->default(0);
            $table->integer('width')->default(1);
            $table->integer('height')->default(1);
            $table->integer('sort_order')->default(0);

            // Styling
            $table->string('color_scheme', 50)->default('default');
            $table->boolean('show_trend')->default(true);
            $table->boolean('show_comparison')->default(true);

            // Visibility
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_pinned')->default(false);

            $table->timestamps();

            $table->index(['business_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
    }
};

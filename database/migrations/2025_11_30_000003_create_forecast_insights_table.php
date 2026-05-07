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
        Schema::create('forecast_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forecast_data_id')->constrained('forecast_data')->onDelete('cascade');
            $table->string('insight_type'); // peak_income, peak_expense, loss_risk, break_even, max_margin, growth_rate
            $table->string('title');
            $table->text('description');
            $table->string('value')->nullable();
            $table->integer('month')->nullable();
            $table->integer('year')->nullable();
            $table->string('severity')->default('info'); // positive, warning, critical, info
            $table->timestamps();

            // Indexes
            $table->index('forecast_data_id');
            $table->index('insight_type');
            $table->index('severity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forecast_insights');
    }
};

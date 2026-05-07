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
        Schema::table('forecast_data', function (Blueprint $table) {
            // Make month nullable to support year-level forecasts
            $table->integer('month')->nullable()->change();

            // Add unique constraint for (user_id, year, month, financial_simulation_id) to prevent duplicates
            // This allows month=null for year-level forecasts
            $table->unique(['user_id', 'year', 'month', 'financial_simulation_id'], 'unique_forecast_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forecast_data', function (Blueprint $table) {
            $table->dropUnique('unique_forecast_data');
            $table->integer('month')->nullable(false)->change();
        });
    }
};

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
        Schema::create('forecast_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forecast_data_id')->constrained('forecast_data')->onDelete('cascade');
            $table->integer('month');
            $table->integer('year');
            $table->decimal('forecast_income', 15, 2);
            $table->decimal('forecast_expense', 15, 2);
            $table->decimal('forecast_profit', 15, 2);
            $table->decimal('forecast_margin', 5, 2);
            $table->decimal('confidence_level', 5, 2)->default(85);
            $table->string('method')->default('arima'); // arima, exponential_smoothing, manual
            $table->timestamps();

            // Indexes
            $table->index('forecast_data_id');
            $table->index('month');
            $table->index('year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forecast_results');
    }
};

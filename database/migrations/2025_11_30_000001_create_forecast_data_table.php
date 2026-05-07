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
        Schema::create('forecast_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('financial_simulation_id')->nullable()->constrained('financial_simulations')->onDelete('set null');
            $table->integer('month');
            $table->integer('year');
            $table->decimal('income_sales', 15, 2)->default(0);
            $table->decimal('income_other', 15, 2)->default(0);
            $table->decimal('expense_operational', 15, 2)->default(0);
            $table->decimal('expense_other', 15, 2)->default(0);
            $table->decimal('seasonal_factor', 5, 2)->default(1.0);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('year');
            $table->index('month');
            $table->index(['user_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forecast_data');
    }
};

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
        Schema::create('financial_projections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_background_id')->constrained()->onDelete('cascade');
            $table->string('projection_name');
            $table->integer('base_year'); // Tahun dasar untuk proyeksi
            $table->enum('scenario_type', ['optimistic', 'realistic', 'pessimistic']);

            // Input assumptions
            $table->decimal('growth_rate', 8, 2); // Persentase pertumbuhan revenue
            $table->decimal('inflation_rate', 8, 2); // Persentase kenaikan cost
            $table->decimal('discount_rate', 8, 2); // Discount rate untuk NPV
            $table->decimal('initial_investment', 15, 2); // Modal awal investasi

            // Baseline data (tahun 0)
            $table->decimal('base_revenue', 15, 2);
            $table->decimal('base_cost', 15, 2);
            $table->decimal('base_net_profit', 15, 2);

            // Proyeksi data 5 tahun (JSON format)
            $table->json('yearly_projections'); // [{year: 1, revenue: x, cost: y, net_profit: z}, ...]

            // Analysis results
            $table->decimal('roi', 8, 2)->nullable(); // Return on Investment (%)
            $table->decimal('npv', 15, 2)->nullable(); // Net Present Value
            $table->decimal('irr', 8, 2)->nullable(); // Internal Rate of Return (%)
            $table->decimal('payback_period', 8, 2)->nullable(); // Payback period (years)

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Index untuk performance
            $table->index(['user_id', 'business_background_id']);
            $table->index(['base_year', 'scenario_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_projections');
    }
};

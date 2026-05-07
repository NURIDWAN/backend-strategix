<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('business_background_id')->constrained('business_backgrounds')->onDelete('cascade');

            $table->json('capital_sources')->nullable(); // [{source: 'Pribadi', amount: 10000000, percentage: 50}]
            $table->decimal('total_initial_capital', 15, 2)->default(0);

            $table->json('initial_capex')->nullable(); // [{category: 'Peralatan', amount: 5000000}]
            $table->decimal('total_capex', 15, 2)->default(0);

            $table->json('monthly_opex')->nullable(); // [{category: 'Gaji', amount: 3000000}]
            $table->decimal('total_monthly_opex', 15, 2)->default(0);

            $table->json('sales_projections')->nullable(); // [{product: 'Produk A', price: 100000, volume: 100, monthly_income: 10000000}]
            $table->decimal('total_monthly_income', 15, 2)->default(0);
            $table->decimal('total_yearly_income', 15, 2)->default(0);

            $table->decimal('gross_profit', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('interest_expense', 15, 2)->default(0);
            $table->decimal('net_profit', 15, 2)->default(0);

            $table->json('cash_flow_simulation')->nullable(); // [{date: '2024-01-15', type: 'income', category: 'Penjualan', description: 'Penjualan Produk A', amount: 5000000, payment_method: 'Transfer'}]

            $table->json('financial_summary')->nullable();

            $table->decimal('roi_percentage', 8, 2)->default(0);
            $table->decimal('payback_period', 8, 2)->default(0);
            $table->decimal('bep_amount', 15, 2)->default(0);
            $table->decimal('profit_margin', 8, 2)->default(0);
            $table->enum('feasibility_status', ['Layak', 'Cukup Layak', 'Tidak Layak'])->default('Cukup Layak');
            $table->text('feasibility_notes')->nullable();

            $table->string('plan_name');
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'active', 'completed'])->default('draft');
            $table->integer('plan_duration_months')->default(12); // Durasi rencana dalam bulan

            $table->timestamps();

            $table->index(['user_id', 'business_background_id']);
            $table->index('feasibility_status');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_plans');
    }
};

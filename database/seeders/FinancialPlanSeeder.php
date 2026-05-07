<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FinancialPlan;

class FinancialPlanSeeder extends Seeder
{
    public function run()
    {
        FinancialPlan::create([
            'user_id' => 1,
            'business_background_id' => 1, // pastikan ada datanya di DB

            // === Sumber Modal ===
            'capital_sources' => [
                [
                    'source' => 'Modal Pribadi',
                    'amount' => 15000000,
                    'percentage' => 60
                ],
                [
                    'source' => 'Investor',
                    'amount' => 10000000,
                    'percentage' => 40
                ],
            ],
            'total_initial_capital' => 25000000,

            // === CAPEX (Investasi Awal) ===
            'initial_capex' => [
                ['category' => 'Mesin Espresso', 'amount' => 8000000],
                ['category' => 'Renovasi', 'amount' => 5000000],
                ['category' => 'Furniture', 'amount' => 3000000],
            ],
            'total_capex' => 16000000,

            // === OPEX Bulanan ===
            'monthly_opex' => [
                ['category' => 'Gaji Karyawan', 'amount' => 4000000],
                ['category' => 'Sewa Tempat', 'amount' => 3000000],
                ['category' => 'Bahan Baku', 'amount' => 2500000],
                ['category' => 'Listrik & Air', 'amount' => 750000],
            ],
            'total_monthly_opex' => 10250000,

            // === Proyeksi Penjualan ===
            'sales_projections' => [
                ['product' => 'Latte', 'price' => 25000, 'volume' => 600, 'monthly_income' => 15000000],
                ['product' => 'Americano', 'price' => 15000, 'volume' => 400, 'monthly_income' => 6000000],
                ['product' => 'Cappuccino', 'price' => 22000, 'volume' => 500, 'monthly_income' => 11000000],
            ],
            'total_monthly_income' => 32000000,
            'total_yearly_income' => 32000000 * 12,

            // === Profit Calculation ===
            'gross_profit' => 32000000 - 10250000,
            'tax_rate' => 10,
            'tax_amount' => (32000000 - 10250000) * 0.1,
            'interest_expense' => 0,
            'net_profit' => (32000000 - 10250000) - ((32000000 - 10250000) * 0.1),

            // === Simulasi Arus Kas ===
            'cash_flow_simulation' => [
                [
                    'date' => '2025-01-05',
                    'type' => 'income',
                    'category' => 'Penjualan',
                    'description' => 'Penjualan awal bulan',
                    'amount' => 500000,
                    'payment_method' => 'Transfer'
                ],
                [
                    'date' => '2025-01-10',
                    'type' => 'expense',
                    'category' => 'Operasional',
                    'description' => 'Pembelian bahan baku',
                    'amount' => 200000,
                    'payment_method' => 'Cash'
                ],
            ],

            // === Summary Breakdowns ===
            'financial_summary' => [
                'monthly_profit' => 32000000 - 10250000,
                'yearly_profit' => (32000000 - 10250000) * 12,
                'total_investment' => 25000000 + 16000000,
            ],

            // === KPI ===
            'roi_percentage' => 45.5,
            'payback_period' => 18.5, // bulan
            'bep_amount' => 12000000,
            'profit_margin' => 35.2,
            'feasibility_status' => 'Layak',
            'feasibility_notes' => 'Proyek menunjukkan ROI kuat dan arus kas positif.',

            // === Meta ===
            'plan_name' => 'Rencana Keuangan Bisnis Kopi Premium',
            'notes' => 'Seeder otomatis untuk testing penuh.',
            'status' => 'active',
            'plan_duration_months' => 12,
        ]);
    }
}

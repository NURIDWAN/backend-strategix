<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\ManagementFinancial\FinancialSummary;
use App\Models\User;
use Carbon\Carbon;

class FinancialSummarySeeder extends Seeder
{
    public function run(): void
    {
        // Truncate untuk bisa run berulang kali
        DB::table('financial_summaries')->delete();

        $users = User::all();
        $businessBackgroundId = 1; // Sesuai yang Anda tentukan

        // 6 bulan terakhir
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        foreach ($users as $user) {
            for ($i = 5; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $month = $date->month;
                $year = $date->year;

                // Generate nilai realistis
                // Income: semakin ke bulan sekarang, semakin tinggi (growth trend)
                $baseIncome = rand(15000000, 25000000); // 15jt - 25jt
                $growthFactor = (6 - $i) * 0.1; // 0% - 50% growth
                $totalIncome = $baseIncome * (1 + $growthFactor);

                // Expense: sekitar 60-75% dari income
                $expenseRatio = rand(60, 75) / 100;
                $totalExpense = $totalIncome * $expenseRatio;

                // Gross Profit = Total Income - Total Expense
                $grossProfit = $totalIncome - $totalExpense;

                // Net Profit: sekitar 80-90% dari gross profit (setelah pajak, dll)
                $netProfit = $grossProfit * (rand(80, 90) / 100);

                // Cash Position: akumulatif
                if ($i === 5) {
                    // Bulan pertama: cash awal
                    $cashPosition = rand(5000000, 10000000);
                } else {
                    // Ambil cash position bulan sebelumnya
                    $previousSummary = FinancialSummary::where('user_id', $user->id)
                        ->where('business_background_id', $businessBackgroundId)
                        ->where('month', $date->copy()->subMonth()->month)
                        ->where('year', $date->copy()->subMonth()->year)
                        ->first();

                    $cashPosition = ($previousSummary ? $previousSummary->cash_position : 5000000) + $netProfit;
                }

                // Income breakdown (JSON)
                $incomeBreakdown = [
                    'Penjualan Produk' => round($totalIncome * 0.70, 2), // 70%
                    'Penjualan Jasa' => round($totalIncome * 0.25, 2),   // 25%
                    'Pendapatan Lain-lain' => round($totalIncome * 0.05, 2), // 5%
                ];

                // Expense breakdown (JSON)
                $expenseBreakdown = [
                    'Pembelian Bahan Baku' => round($totalExpense * 0.35, 2),    // 35%
                    'Gaji Karyawan' => round($totalExpense * 0.30, 2),           // 30%
                    'Biaya Operasional' => round($totalExpense * 0.15, 2),       // 15%
                    'Listrik & Air' => round($totalExpense * 0.05, 2),           // 5%
                    'Sewa Tempat' => round($totalExpense * 0.08, 2),             // 8%
                    'Marketing & Promosi' => round($totalExpense * 0.04, 2),     // 4%
                    'Pengeluaran Lain-lain' => round($totalExpense * 0.03, 2),  // 3%
                ];

                FinancialSummary::create([
                    'user_id' => $user->id,
                    'business_background_id' => $businessBackgroundId,
                    'month' => $month,
                    'year' => $year,
                    'total_income' => round($totalIncome, 2),
                    'total_expense' => round($totalExpense, 2),
                    'gross_profit' => round($grossProfit, 2),
                    'net_profit' => round($netProfit, 2),
                    'cash_position' => round($cashPosition, 2),
                    'income_breakdown' => $incomeBreakdown,
                    'expense_breakdown' => $expenseBreakdown,
                    'notes' => 'Summary bulan ' . $date->format('F Y'),
                ]);
            }
        }
    }
}

<?php

namespace App\Http\Controllers\ManagementFinancial;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ManagementFinancial\FinancialSummary;
use App\Models\ManagementFinancial\FinancialSimulation;
use App\Models\ManagementFinancial\FinancialCategory;
use Illuminate\Support\Facades\Auth;

class MonthlyReportController extends Controller
{
    /**
     * GET /api/management-financial/reports/monthly?year=YYYY
     * Mengembalikan laporan keuangan bulanan (Laba Rugi, Arus Kas, Neraca sederhana, dan Tren)
     */
    public function getMonthlyReport(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:1900|max:3000',
            'business_background_id' => 'nullable|integer',
        ]);
        $year = (int) $validated['year'];
        $businessId = $validated['business_background_id'] ?? null;
        $userId = Auth::id();

        // Siapkan array 12 bulan
        $months = range(1, 12);

        $incomeStatement = [];
        $cashFlow = [];
        $balanceSheet = [];

        $trends = [
            'revenue' => array_fill(0, 12, 0.0),
            'netIncome' => array_fill(0, 12, 0.0),
            'cashEnding' => array_fill(0, 12, 0.0),
            'totalAssets' => array_fill(0, 12, 0.0),
        ];

        // Ambil summary per bulan dari FinancialSummary
        $query = FinancialSummary::query()->forYear($year);
        if ($businessId) $query->forBusiness($businessId);
        if ($userId) $query->where('user_id', $userId);

        $rows = $query->orderBy('month')->get()->keyBy('month');

        $prevCashEnding = 0.0;
        $cumRetained = 0.0; // akumulasi laba ditahan sederhana

        foreach ($months as $m) {
            $r = $rows[$m] ?? null;

            // Query simulations untuk bulan ini berdasarkan category_subtype
            $simulationsQuery = FinancialSimulation::with('category')
                ->where('year', $year)
                ->whereMonth('simulation_date', $m)
                ->where('status', 'completed'); // hanya yang completed

            if ($businessId) $simulationsQuery->where('business_background_id', $businessId);
            if ($userId) $simulationsQuery->where('user_id', $userId);

            $simulations = $simulationsQuery->get();

            if ($simulations->isEmpty() && $r) {
                // Fallback to FinancialSummary if no simulations
                $revenue = (float) $r->total_income;
                $cogs = 0.0;
                $grossProfit = (float) $r->gross_profit;
                $operatingExpense = (float) $r->total_expense;
                $operatingIncome = $grossProfit - $operatingExpense;
                $nonOperatingRevenue = 0.0;
                $interestExpense = 0.0;
                $taxExpense = 0.0;
                $netIncome = (float) $r->net_profit;

                $totalIncome = $revenue;
                $additionalCapital = 0.0;
                $cashIn = $totalIncome;

                $totalExpenses = $operatingExpense;
                $cashOut = $totalExpenses;
                $netCashFlow = $cashIn - $cashOut;

                $cashBeginning = $r ? $prevCashEnding : 0.0;
                $cashEnding = (float) $r->cash_position;

                $cash = $cashEnding;
                $fixedAssets = $operatingExpense * 0.1;
                $receivables = 0.0;
                $totalAssets = $cash + $fixedAssets + $receivables;

                $debt = 0.0;
                $otherLiabilities = 0.0;
                $totalLiabilities = $debt + $otherLiabilities;
            } else {
                // Hitung berdasarkan category_subtype
                $operatingRevenue = $simulations->filter(function ($sim) {
                    return $sim->type === 'income' &&
                        $sim->category &&
                        $sim->category->category_subtype === 'operating_revenue';
                })->sum('amount');

                $nonOperatingRevenue = $simulations->filter(function ($sim) {
                    return $sim->type === 'income' &&
                        $sim->category &&
                        $sim->category->category_subtype === 'non_operating_revenue';
                })->sum('amount');

                $cogs = $simulations->filter(function ($sim) {
                    return $sim->type === 'expense' &&
                        $sim->category &&
                        $sim->category->category_subtype === 'cogs';
                })->sum('amount');

                $operatingExpense = $simulations->filter(function ($sim) {
                    return $sim->type === 'expense' &&
                        $sim->category &&
                        $sim->category->category_subtype === 'operating_expense';
                })->sum('amount');

                $interestExpense = $simulations->filter(function ($sim) {
                    return $sim->type === 'expense' &&
                        $sim->category &&
                        $sim->category->category_subtype === 'interest_expense';
                })->sum('amount');

                $taxExpense = $simulations->filter(function ($sim) {
                    return $sim->type === 'expense' &&
                        $sim->category &&
                        $sim->category->category_subtype === 'tax_expense';
                })->sum('amount');

                // Calculate financial metrics
                $revenue = $operatingRevenue;
                $grossProfit = $revenue - $cogs;
                $operatingIncome = $grossProfit - $operatingExpense;
                $incomeBeforeTax = $operatingIncome + $nonOperatingRevenue - $interestExpense;
                $netIncome = $incomeBeforeTax - $taxExpense;

                // Cash Flow - Simplified approach: Cash In vs Cash Out
                $cashBeginning = $r ? $prevCashEnding : 0.0;

                // Cash In (Arus Kas Masuk)
                $totalIncome = $operatingRevenue + $nonOperatingRevenue; // Semua pendapatan
                $additionalCapital = $nonOperatingRevenue; // Modal tambahan dari pendapatan lain-lain
                $cashIn = $totalIncome;

                // Cash Out (Arus Kas Keluar) 
                $totalExpenses = $cogs + $operatingExpense + $interestExpense + $taxExpense; // Semua pengeluaran
                $cashOut = $totalExpenses;

                // Net Cash Flow & Saldo Akhir
                $netCashFlow = $cashIn - $cashOut;
                $cashEnding = $cashBeginning + $netCashFlow;

                // Balance Sheet - Simple approach
                // ASET
                $cash = $cashEnding; // Kas dari cash position

                // Aset Tetap: dari kategori "Perawatan & Maintenance" atau estimasi dari operating expense
                $maintenanceAssets = $simulations->filter(function ($sim) {
                    return $sim->type === 'expense' &&
                        $sim->category &&
                        $sim->category->name === 'Perawatan & Maintenance';
                })->sum('amount');

                // Jika tidak ada maintenance, gunakan estimasi 10% dari operating expense
                $fixedAssets = $maintenanceAssets > 0 ? $maintenanceAssets : ($operatingExpense * 0.1);

                // Piutang: untuk sementara set 0 (bisa ditambah kategori khusus nanti)
                $receivables = 0.0;

                $totalAssets = $cash + $fixedAssets + $receivables;

                // LIABILITAS  
                // Utang: dari beban bunga (indikator ada pinjaman)
                $debt = $interestExpense > 0 ? ($interestExpense * 10) : 0.0; // Estimasi pokok utang

                // Kewajiban Lain: dari pajak terutang saja (bukan estimasi)
                $otherLiabilities = $taxExpense; // Hanya pajak yang belum dibayar

                $totalLiabilities = $debt + $otherLiabilities;
            }

            $incomeStatement[$m] = [
                'revenue' => round($revenue, 2),
                'cogs' => round($cogs, 2),
                'grossProfit' => round($grossProfit, 2),
                'opex' => round($operatingExpense, 2),
                'operatingIncome' => round($operatingIncome, 2),
                'otherIncome' => round($nonOperatingRevenue, 2),
                'interest' => round($interestExpense, 2),
                'tax' => round($taxExpense, 2),
                'netIncome' => round($netIncome, 2),
            ];

            $cashFlow[$m] = [
                'cashIn' => round($cashIn, 2),
                'totalIncome' => round($totalIncome, 2),
                'additionalCapital' => round($additionalCapital, 2),
                'cashOut' => round($cashOut, 2),
                'totalExpenses' => round($totalExpenses, 2),
                'netCashFlow' => round($netCashFlow, 2),
                'cashBeginning' => round($cashBeginning, 2),
                'cashEnding' => round($cashEnding, 2),
            ];

            // EKUITAS
            $cumRetained += $netIncome;
            $equity = $totalAssets - $totalLiabilities; // Ekuitas = Aset - Liabilitas

            // Pastikan neraca balance
            $totalLiabilitiesEquity = $totalLiabilities + $equity;

            $balanceSheet[$m] = [
                'assets' => [
                    'cash' => round($cash, 2),
                    'fixedAssets' => round($fixedAssets, 2),
                    'receivables' => round($receivables, 2),
                    'total' => round($totalAssets, 2),
                ],
                'liabilities' => [
                    'debt' => round($debt, 2),
                    'otherLiabilities' => round($otherLiabilities, 2),
                    'total' => round($totalLiabilities, 2),
                ],
                'equity' => [
                    'total' => round($equity, 2),
                    'retainedEarnings' => round($cumRetained, 2),
                ],
                'totalLiabilitiesEquity' => round($totalLiabilitiesEquity, 2),
            ];

            $trends['revenue'][$m - 1] = round($revenue, 2);
            $trends['netIncome'][$m - 1] = round($netIncome, 2);
            $trends['cashEnding'][$m - 1] = round($cashEnding, 2);
            $trends['totalAssets'][$m - 1] = round($totalAssets, 2);

            $prevCashEnding = $cashEnding;
        }

        return response()->json([
            'year' => $year,
            'incomeStatement' => $incomeStatement,
            'cashFlow' => $cashFlow,
            'balanceSheet' => $balanceSheet,
            'trends' => $trends,
        ]);
    }
}

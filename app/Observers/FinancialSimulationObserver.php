<?php

namespace App\Observers;

use App\Models\ManagementFinancial\FinancialSimulation;
use App\Models\ManagementFinancial\FinancialSummary;
use App\Models\ManagementFinancial\FinancialCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FinancialSimulationObserver
{
    /**
     * Handle the FinancialSimulation "created" event.
     */
    public function created(FinancialSimulation $financialSimulation): void
    {
        $this->updateFinancialSummary($financialSimulation);
    }

    /**
     * Handle the FinancialSimulation "updated" event.
     */
    public function updated(FinancialSimulation $financialSimulation): void
    {
        // Update summary for old date if date changed
        if ($financialSimulation->isDirty('simulation_date')) {
            $oldDate = Carbon::parse($financialSimulation->getOriginal('simulation_date'));
            $this->recalculateSummary(
                $financialSimulation->user_id,
                $financialSimulation->business_background_id,
                $oldDate->month,
                $oldDate->year
            );
        }

        // Update summary for current date
        $this->updateFinancialSummary($financialSimulation);
    }

    /**
     * Handle the FinancialSimulation "deleted" event.
     */
    public function deleted(FinancialSimulation $financialSimulation): void
    {
        $this->updateFinancialSummary($financialSimulation);
    }

    /**
     * Handle the FinancialSimulation "restored" event.
     */
    public function restored(FinancialSimulation $financialSimulation): void
    {
        $this->updateFinancialSummary($financialSimulation);
    }

    /**
     * Update or create financial summary for the simulation's month/year
     */
    private function updateFinancialSummary(FinancialSimulation $simulation): void
    {
        try {
            $date = Carbon::parse($simulation->simulation_date);
            $month = $date->month;
            $year = $date->year;

            $this->recalculateSummary(
                $simulation->user_id,
                $simulation->business_background_id,
                $month,
                $year
            );
        } catch (\Exception $e) {
            Log::error('Failed to update financial summary: ' . $e->getMessage(), [
                'simulation_id' => $simulation->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Recalculate and save financial summary for a specific month/year
     */
    private function recalculateSummary($userId, $businessId, $month, $year): void
    {
        // Get all simulations for this period
        $simulations = FinancialSimulation::with('category')
            ->where('user_id', $userId)
            ->where('business_background_id', $businessId)
            ->whereYear('simulation_date', $year)
            ->whereMonth('simulation_date', $month)
            ->where('status', 'completed')
            ->get();

        if ($simulations->isEmpty()) {
            // Delete summary if no simulations
            FinancialSummary::forPeriod($month, $year)
                ->forBusiness($businessId)
                ->where('user_id', $userId)
                ->delete();
            return;
        }

        // Calculate by category subtype
        $operatingRevenue = $simulations->filter(fn($s) =>
            $s->type === 'income' && $s->category?->category_subtype === 'operating_revenue'
        )->sum('amount');

        $nonOperatingRevenue = $simulations->filter(fn($s) =>
            $s->type === 'income' && $s->category?->category_subtype === 'non_operating_revenue'
        )->sum('amount');

        $cogs = $simulations->filter(fn($s) =>
            $s->type === 'expense' && $s->category?->category_subtype === 'cogs'
        )->sum('amount');

        $operatingExpense = $simulations->filter(fn($s) =>
            $s->type === 'expense' && $s->category?->category_subtype === 'operating_expense'
        )->sum('amount');

        $interestExpense = $simulations->filter(fn($s) =>
            $s->type === 'expense' && $s->category?->category_subtype === 'interest_expense'
        )->sum('amount');

        $taxExpense = $simulations->filter(fn($s) =>
            $s->type === 'expense' && $s->category?->category_subtype === 'tax_expense'
        )->sum('amount');

        // Calculate financial metrics
        $totalIncome = $operatingRevenue + $nonOperatingRevenue;
        $totalExpense = $cogs + $operatingExpense + $interestExpense + $taxExpense;
        $grossProfit = $operatingRevenue - $cogs;
        $operatingIncome = $grossProfit - $operatingExpense;
        $incomeBeforeTax = $operatingIncome + $nonOperatingRevenue - $interestExpense;
        $netProfit = $incomeBeforeTax - $taxExpense;

        // Get previous month's cash ending for cash beginning
        $prevMonth = $month - 1;
        $prevYear = $year;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear = $year - 1;
        }

        $previousSummary = FinancialSummary::forPeriod($prevMonth, $prevYear)
            ->forBusiness($businessId)
            ->where('user_id', $userId)
            ->first();

        $cashBeginning = $previousSummary?->cash_ending ?? 0;

        // Cash Flow
        $cashIn = $totalIncome;
        $cashOut = $totalExpense;
        $netCashFlow = $cashIn - $cashOut;
        $cashEnding = $cashBeginning + $netCashFlow;

        // Balance Sheet - Assets
        $cash = $cashEnding;

        // Fixed Assets: from maintenance category
        $maintenanceAssets = $simulations->filter(fn($s) =>
            $s->type === 'expense' && $s->category?->name === 'Perawatan & Maintenance'
        )->sum('amount');
        $fixedAssets = $maintenanceAssets > 0 ? $maintenanceAssets : ($operatingExpense * 0.1);

        $receivables = 0; // Placeholder
        $totalAssets = $cash + $fixedAssets + $receivables;

        // Balance Sheet - Liabilities
        $debt = $interestExpense > 0 ? ($interestExpense * 10) : 0;
        $otherLiabilities = $taxExpense;
        $totalLiabilities = $debt + $otherLiabilities;

        // Balance Sheet - Equity
        $equity = $totalAssets - $totalLiabilities;

        // Get accumulated retained earnings from previous months
        $accumulatedEarnings = FinancialSummary::where('user_id', $userId)
            ->where('business_background_id', $businessId)
            ->where('year', $year)
            ->where('month', '<', $month)
            ->sum('net_profit');

        $retainedEarnings = $accumulatedEarnings + $netProfit;

        // Income & Expense Breakdown
        $incomeBreakdown = [];
        $expenseBreakdown = [];

        foreach ($simulations as $sim) {
            if (!$sim->category) continue;

            $categoryName = $sim->category->name;
            if ($sim->type === 'income') {
                $incomeBreakdown[$categoryName] = ($incomeBreakdown[$categoryName] ?? 0) + $sim->amount;
            } else {
                $expenseBreakdown[$categoryName] = ($expenseBreakdown[$categoryName] ?? 0) + $sim->amount;
            }
        }

        // Update or create summary
        FinancialSummary::updateOrCreate(
            [
                'user_id' => $userId,
                'business_background_id' => $businessId,
                'month' => $month,
                'year' => $year,
            ],
            [
                // Original fields
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'gross_profit' => $grossProfit,
                'net_profit' => $netProfit,
                'cash_position' => $cashEnding,
                'income_breakdown' => $incomeBreakdown,
                'expense_breakdown' => $expenseBreakdown,
                // Cash Flow
                'cash_beginning' => $cashBeginning,
                'cash_in' => $cashIn,
                'cash_out' => $cashOut,
                'net_cash_flow' => $netCashFlow,
                'cash_ending' => $cashEnding,
                // Income Statement details
                'operating_revenue' => $operatingRevenue,
                'non_operating_revenue' => $nonOperatingRevenue,
                'cogs' => $cogs,
                'operating_expense' => $operatingExpense,
                'interest_expense' => $interestExpense,
                'tax_expense' => $taxExpense,
                'operating_income' => $operatingIncome,
                // Balance Sheet - Assets
                'fixed_assets' => $fixedAssets,
                'receivables' => $receivables,
                'total_assets' => $totalAssets,
                // Balance Sheet - Liabilities
                'debt' => $debt,
                'other_liabilities' => $otherLiabilities,
                'total_liabilities' => $totalLiabilities,
                // Balance Sheet - Equity
                'equity' => $equity,
                'retained_earnings' => $retainedEarnings,
            ]
        );

        // Update subsequent months' cash_beginning if this is not the latest month
        $this->updateSubsequentMonths($userId, $businessId, $month, $year, $cashEnding);

        Log::info('✅ Financial Summary Updated', [
            'user_id' => $userId,
            'business_id' => $businessId,
            'month' => $month,
            'year' => $year,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_profit' => $netProfit
        ]);
    }

    /**
     * Update cash_beginning for subsequent months
     */
    private function updateSubsequentMonths($userId, $businessId, $month, $year, $cashEnding): void
    {
        // Get next month
        $nextMonth = $month + 1;
        $nextYear = $year;
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear = $year + 1;
        }

        // Check if next month summary exists
        $nextSummary = FinancialSummary::forPeriod($nextMonth, $nextYear)
            ->forBusiness($businessId)
            ->where('user_id', $userId)
            ->first();

        if ($nextSummary && $nextSummary->cash_beginning != $cashEnding) {
            // Recalculate next month to update cash_beginning
            $this->recalculateSummary($userId, $businessId, $nextMonth, $nextYear);
        }
    }
}

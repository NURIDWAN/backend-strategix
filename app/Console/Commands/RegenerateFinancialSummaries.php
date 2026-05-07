<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ManagementFinancial\FinancialSimulation;
use App\Models\ManagementFinancial\FinancialSummary;
use Carbon\Carbon;

class RegenerateFinancialSummaries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'financial:regenerate-summaries
                            {--user_id= : Specific user ID}
                            {--business_id= : Specific business ID}
                            {--year= : Specific year}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate all financial summaries from simulations data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting Financial Summaries Regeneration...');

        // Build query
        $query = FinancialSimulation::query()->where('status', 'completed');

        if ($userId = $this->option('user_id')) {
            $query->where('user_id', $userId);
            $this->info("Filtering by user_id: {$userId}");
        }

        if ($businessId = $this->option('business_id')) {
            $query->where('business_background_id', $businessId);
            $this->info("Filtering by business_id: {$businessId}");
        }

        if ($year = $this->option('year')) {
            $query->whereYear('simulation_date', $year);
            $this->info("Filtering by year: {$year}");
        }

        // Get unique combinations of user, business, month, year
        $periods = $query->selectRaw('
                user_id,
                business_background_id,
                MONTH(simulation_date) as month,
                YEAR(simulation_date) as year
            ')
            ->groupByRaw('user_id, business_background_id, YEAR(simulation_date), MONTH(simulation_date)')
            ->orderByRaw('YEAR(simulation_date), MONTH(simulation_date)')
            ->get();

        if ($periods->isEmpty()) {
            $this->warn('⚠️  No simulations found to process.');
            return 0;
        }

        $this->info("Found {$periods->count()} periods to regenerate.");

        // Clear existing summaries for these periods
        $this->info('🗑️  Clearing existing summaries...');

        $deleteQuery = FinancialSummary::query();
        if ($userId) $deleteQuery->where('user_id', $userId);
        if ($businessId) $deleteQuery->where('business_background_id', $businessId);
        if ($year) $deleteQuery->where('year', $year);

        $deleted = $deleteQuery->delete();
        $this->info("Deleted {$deleted} existing summaries.");

        // Progress bar
        $bar = $this->output->createProgressBar($periods->count());
        $bar->start();

        $success = 0;
        $failed = 0;

        foreach ($periods as $period) {
            try {
                $this->regeneratePeriod(
                    $period->user_id,
                    $period->business_background_id,
                    $period->month,
                    $period->year
                );
                $success++;
            } catch (\Exception $e) {
                $failed++;
                $this->error("\n❌ Failed for User:{$period->user_id} Business:{$period->business_background_id} {$period->year}-{$period->month}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Regeneration Complete!");
        $this->info("Success: {$success}");

        if ($failed > 0) {
            $this->warn("Failed: {$failed}");
        }

        return 0;
    }

    /**
     * Regenerate summary for specific period
     */
    private function regeneratePeriod($userId, $businessId, $month, $year): void
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
            return;
        }

        // Calculate by category subtype
        $operatingRevenue = $simulations->filter(
            fn($s) =>
            $s->type === 'income' && $s->category?->category_subtype === 'operating_revenue'
        )->sum('amount');

        $nonOperatingRevenue = $simulations->filter(
            fn($s) =>
            $s->type === 'income' && $s->category?->category_subtype === 'non_operating_revenue'
        )->sum('amount');

        $cogs = $simulations->filter(
            fn($s) =>
            $s->type === 'expense' && $s->category?->category_subtype === 'cogs'
        )->sum('amount');

        $operatingExpense = $simulations->filter(
            fn($s) =>
            $s->type === 'expense' && $s->category?->category_subtype === 'operating_expense'
        )->sum('amount');

        $interestExpense = $simulations->filter(
            fn($s) =>
            $s->type === 'expense' && $s->category?->category_subtype === 'interest_expense'
        )->sum('amount');

        $taxExpense = $simulations->filter(
            fn($s) =>
            $s->type === 'expense' && $s->category?->category_subtype === 'tax_expense'
        )->sum('amount');

        // Calculate financial metrics
        $totalIncome = $operatingRevenue + $nonOperatingRevenue;
        $totalExpense = $cogs + $operatingExpense + $interestExpense + $taxExpense;
        $grossProfit = $operatingRevenue - $cogs;
        $operatingIncome = $grossProfit - $operatingExpense;
        $incomeBeforeTax = $operatingIncome + $nonOperatingRevenue - $interestExpense;
        $netProfit = $incomeBeforeTax - $taxExpense;

        // Get previous month's cash ending
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

        $maintenanceAssets = $simulations->filter(
            fn($s) =>
            $s->type === 'expense' && $s->category?->name === 'Perawatan & Maintenance'
        )->sum('amount');
        $fixedAssets = $maintenanceAssets > 0 ? $maintenanceAssets : ($operatingExpense * 0.1);

        $receivables = 0;
        $totalAssets = $cash + $fixedAssets + $receivables;

        // Balance Sheet - Liabilities
        $debt = $interestExpense > 0 ? ($interestExpense * 10) : 0;
        $otherLiabilities = $taxExpense;
        $totalLiabilities = $debt + $otherLiabilities;

        // Balance Sheet - Equity
        $equity = $totalAssets - $totalLiabilities;

        $accumulatedEarnings = FinancialSummary::where('user_id', $userId)
            ->where('business_background_id', $businessId)
            ->where('year', $year)
            ->where('month', '<', $month)
            ->sum('net_profit');

        $retainedEarnings = $accumulatedEarnings + $netProfit;

        // Breakdown
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

        // Create summary
        FinancialSummary::create([
            'user_id' => $userId,
            'business_background_id' => $businessId,
            'month' => $month,
            'year' => $year,
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
            // Income Statement
            'operating_revenue' => $operatingRevenue,
            'non_operating_revenue' => $nonOperatingRevenue,
            'cogs' => $cogs,
            'operating_expense' => $operatingExpense,
            'interest_expense' => $interestExpense,
            'tax_expense' => $taxExpense,
            'operating_income' => $operatingIncome,
            // Balance Sheet
            'fixed_assets' => $fixedAssets,
            'receivables' => $receivables,
            'total_assets' => $totalAssets,
            'debt' => $debt,
            'other_liabilities' => $otherLiabilities,
            'total_liabilities' => $totalLiabilities,
            'equity' => $equity,
            'retained_earnings' => $retainedEarnings,
        ]);
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ManagementFinancial\FinancialProjection;

class RecalculateProjections extends Command
{
    protected $signature = 'projections:recalculate';
    protected $description = 'Recalculate all financial projection metrics';

    public function handle()
    {
        $this->info('Starting projection metrics recalculation...');

        $projections = FinancialProjection::all();
        $count = 0;

        foreach ($projections as $projection) {
            $this->info("Processing projection ID: {$projection->id} - {$projection->projection_name}");

            if ($projection->calculateMetrics()) {
                $count++;
                $this->line("  ✓ NPV: {$projection->formatted_npv}");
                $this->line("  ✓ ROI: {$projection->formatted_roi}");
                $this->line("  ✓ IRR: {$projection->formatted_irr}");
                $this->line("  ✓ Payback: {$projection->formatted_payback}");
            } else {
                $this->error("  ✗ Failed to calculate metrics");
            }

            $this->line('');
        }

        $this->info("Recalculation completed. Updated {$count}/{$projections->count()} projections.");
        return 0;
    }
}

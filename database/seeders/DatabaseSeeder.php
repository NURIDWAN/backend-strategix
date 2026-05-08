<?php

namespace Database\Seeders;

use App\Models\FinancialPlan;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class, // Harus dipanggil pertama!
            BusinessBackgroundSeeder::class,
            MarketAnalysisSeeder::class,
            MarketAnalysisCompetitorSeeder::class,
            ProductServiceSeeder::class,
            MarketingStrategySeeder::class,
            OperationalPlanSeeder::class,
            TeamStructureSeeder::class,
            FinancialPlanSeeder::class,
            // Financial Management Seeders
            FinancialCategorySeeder::class,
            FinancialSimulationSeeder::class,
            FinancialSummarySeeder::class,
            // Affiliate Seeders
            AffiliateLinkSeeder::class,
            // Payment & Package Seeders
            PremiumPdfSeeder::class,
            // Settings & Feature Flags
            SettingSeeder::class,
            // Article Seeders
            ArticleSeeder::class,
            // Activity Logs
            ActivityLogSeeder::class,
            // Extra data from sevendre_strategix dump
            SevendreExtraDataSeeder::class,
            SqlDumpSeeder::class,
        ]);
    }
}

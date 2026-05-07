<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SevendreExtraDataSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('seeders/data/sevendre_extra_data.json');

        if (!file_exists($jsonPath)) {
            throw new RuntimeException("Seed data file not found: {$jsonPath}");
        }

        $payload = json_decode(file_get_contents($jsonPath), true);

        if (!is_array($payload)) {
            throw new RuntimeException('Invalid JSON in sevendre_extra_data.json');
        }

        $tableOrder = [
            'users',
            'affiliate_links',
            'business_backgrounds',
            'financial_categories',
            'financial_projections',
            'financial_simulations',
            'financial_summaries',
            'forecast_data',
            'forecast_insights',
            'forecast_results',
            'marketing_strategies',
            'market_analyses',
            'market_analysis_competitors',
            'operational_plans',
            'premium_pdfs',
            'pdf_purchases',
            'payment_transactions',
            'product_services',
            'team_structures',
            'activity_logs',
        ];

        $jsonColumns = [
            'activity_logs' => ['properties'],
            'financial_projections' => ['yearly_projections'],
            'financial_summaries' => ['income_breakdown', 'expense_breakdown'],
            'operational_plans' => ['employees', 'operational_hours', 'suppliers', 'workflow_diagram'],
            'payment_transactions' => ['singapay_request', 'singapay_response', 'webhook_data'],
            'premium_pdfs' => ['features'],
            'product_services' => ['bmc_alignment'],
        ];

        foreach ($tableOrder as $table) {
            $rows = $payload[$table] ?? [];

            if (!is_array($rows) || empty($rows)) {
                continue;
            }

            $columns = $jsonColumns[$table] ?? [];
            if (!empty($columns)) {
                foreach ($rows as &$row) {
                    foreach ($columns as $column) {
                        if (!array_key_exists($column, $row)) {
                            continue;
                        }

                        $row[$column] = $this->normalizeJsonString($row[$column]);
                    }
                }
                unset($row);
            }

            DB::table($table)->insertOrIgnore($rows);
        }

        $this->command?->info('Sevendre extra data seeded from JSON.');
    }

    private function normalizeJsonString(mixed $value): mixed
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return $value;
        }

        $decoded = json_decode($trimmed, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $trimmed;
        }

        $unescaped = str_replace('\\"', '"', $trimmed);
        $decoded = json_decode($unescaped, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $unescaped;
        }

        return $value;
    }
}

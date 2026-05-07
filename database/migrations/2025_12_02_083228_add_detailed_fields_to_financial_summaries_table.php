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
        Schema::table('financial_summaries', function (Blueprint $table) {
            // CASH FLOW FIELDS
            $table->decimal('cash_beginning', 15, 2)->default(0)->after('cash_position');
            $table->decimal('cash_in', 15, 2)->default(0)->after('cash_beginning');
            $table->decimal('cash_out', 15, 2)->default(0)->after('cash_in');
            $table->decimal('net_cash_flow', 15, 2)->default(0)->after('cash_out');
            $table->decimal('cash_ending', 15, 2)->default(0)->after('net_cash_flow');

            // INCOME STATEMENT DETAILS
            $table->decimal('operating_revenue', 15, 2)->default(0)->after('total_income');
            $table->decimal('non_operating_revenue', 15, 2)->default(0)->after('operating_revenue');
            $table->decimal('cogs', 15, 2)->default(0)->after('total_expense');
            $table->decimal('operating_expense', 15, 2)->default(0)->after('cogs');
            $table->decimal('interest_expense', 15, 2)->default(0)->after('operating_expense');
            $table->decimal('tax_expense', 15, 2)->default(0)->after('interest_expense');
            $table->decimal('operating_income', 15, 2)->default(0)->after('gross_profit');

            // BALANCE SHEET - ASSETS
            $table->decimal('fixed_assets', 15, 2)->default(0)->after('cash_ending');
            $table->decimal('receivables', 15, 2)->default(0)->after('fixed_assets');
            $table->decimal('total_assets', 15, 2)->default(0)->after('receivables');

            // BALANCE SHEET - LIABILITIES
            $table->decimal('debt', 15, 2)->default(0)->after('total_assets');
            $table->decimal('other_liabilities', 15, 2)->default(0)->after('debt');
            $table->decimal('total_liabilities', 15, 2)->default(0)->after('other_liabilities');

            // BALANCE SHEET - EQUITY
            $table->decimal('equity', 15, 2)->default(0)->after('total_liabilities');
            $table->decimal('retained_earnings', 15, 2)->default(0)->after('equity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_summaries', function (Blueprint $table) {
            // Drop all added columns
            $table->dropColumn([
                'cash_beginning',
                'cash_in',
                'cash_out',
                'net_cash_flow',
                'cash_ending',
                'operating_revenue',
                'non_operating_revenue',
                'cogs',
                'operating_expense',
                'interest_expense',
                'tax_expense',
                'operating_income',
                'fixed_assets',
                'receivables',
                'total_assets',
                'debt',
                'other_liabilities',
                'total_liabilities',
                'equity',
                'retained_earnings',
            ]);
        });
    }
};

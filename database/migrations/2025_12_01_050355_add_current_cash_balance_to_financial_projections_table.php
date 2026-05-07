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
        Schema::table('financial_projections', function (Blueprint $table) {
            $table->decimal('current_cash_balance', 15, 2)->default(0)->after('initial_investment')
                ->comment('Saldo kas terkini dari akumulasi semua simulasi');
            $table->decimal('accumulated_income', 15, 2)->default(0)->after('current_cash_balance')
                ->comment('Total pendapatan dari semua simulasi completed');
            $table->decimal('accumulated_expense', 15, 2)->default(0)->after('accumulated_income')
                ->comment('Total pengeluaran dari semua simulasi completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_projections', function (Blueprint $table) {
            $table->dropColumn(['current_cash_balance', 'accumulated_income', 'accumulated_expense']);
        });
    }
};

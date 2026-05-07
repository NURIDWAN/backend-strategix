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
        Schema::table('financial_categories', function (Blueprint $table) {
            $table->enum('category_subtype', [
                'operating_revenue',      // Pendapatan Operasional (pendapatan utama bisnis)
                'non_operating_revenue',  // Pendapatan Lain-lain (bunga, dividen, dll)
                'cogs',                   // HPP / Cost of Goods Sold
                'operating_expense',      // Beban Operasional (gaji, sewa, listrik, dll)
                'interest_expense',       // Beban Bunga (bunga pinjaman)
                'tax_expense',            // Pajak Penghasilan
                'other'                   // Lainnya
            ])->default('other')->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_categories', function (Blueprint $table) {
            $table->dropColumn('category_subtype');
        });
    }
};

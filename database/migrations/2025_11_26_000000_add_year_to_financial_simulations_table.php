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
        Schema::table('financial_simulations', function (Blueprint $table) {
            // Tambahkan kolom year untuk mendukung CRUD tahun dan auto-increment
            $table->integer('year')->default(now()->year)->after('simulation_date');

            // Index untuk performa query berdasarkan tahun
            $table->index('year');

            // Unique constraint untuk mencegah duplikasi dengan kombinasi tertentu
            $table->unique(['user_id', 'business_background_id', 'year', 'simulation_code'], 'financial_sim_user_business_year_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_simulations', function (Blueprint $table) {
            $table->dropUnique('financial_sim_user_business_year_code_unique');
            $table->dropIndex(['year']);
            $table->dropColumn('year');
        });
    }
};

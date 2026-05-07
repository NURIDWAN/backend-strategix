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
        Schema::table('business_backgrounds', function (Blueprint $table) {
            $table->longText('business_overview')->nullable()->after('description')->comment('Gambaran umum usaha');
            $table->longText('business_legality')->nullable()->after('business_overview')->comment('Legalitas usaha (perizinan, NIB, dll)');
            $table->longText('business_objectives')->nullable()->after('business_legality')->comment('Maksud dan tujuan pendirian usaha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_backgrounds', function (Blueprint $table) {
            $table->dropColumn(['business_overview', 'business_legality', 'business_objectives']);
        });
    }
};

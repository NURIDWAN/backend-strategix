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
            $table->string('org_chart_image')->nullable()->after('logo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_backgrounds', function (Blueprint $table) {
            $table->dropColumn('org_chart_image');
        });
    }
};

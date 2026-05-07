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
        Schema::table('premium_pdfs', function (Blueprint $table) {
            $table->integer('consultation_credits')->default(0)->after('duration_days');
        });
    }

    public function down(): void
    {
        Schema::table('premium_pdfs', function (Blueprint $table) {
            $table->dropColumn('consultation_credits');
        });
    }
};

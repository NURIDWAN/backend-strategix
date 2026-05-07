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
        Schema::table('consultation_credits', function (Blueprint $table) {
            if (!Schema::hasColumn('consultation_credits', 'remaining_sessions')) {
                $table->integer('remaining_sessions')->default(0)->after('used_sessions');
            }
            if (!Schema::hasColumn('consultation_credits', 'status')) {
                $table->string('status')->default('active')->after('remaining_sessions');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultation_credits', function (Blueprint $table) {
            $table->dropColumn(['remaining_sessions', 'status']);
        });
    }
};

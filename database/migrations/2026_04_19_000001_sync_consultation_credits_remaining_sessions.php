<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Sync remaining_sessions to match total - used for all records
        // SQLite compatible: use CASE WHEN instead of GREATEST
        if (DB::getDriverName() === 'sqlite') {
            DB::table('consultation_credits')->update([
                'remaining_sessions' => DB::raw('CASE WHEN (total_sessions - used_sessions) < 0 THEN 0 ELSE (total_sessions - used_sessions) END')
            ]);
        } else {
            DB::table('consultation_credits')->update([
                'remaining_sessions' => DB::raw('GREATEST(0, total_sessions - used_sessions)')
            ]);
        }

        // Ensure status is correctly set to 'used' if sessions are exhausted
        DB::table('consultation_credits')
            ->where('total_sessions', '>', 0)
            ->whereRaw('used_sessions >= total_sessions')
            ->update(['status' => 'used']);
            
        // Ensure status is 'active' if sessions are available
        DB::table('consultation_credits')
            ->whereRaw('used_sessions < total_sessions')
            ->update(['status' => 'active']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse action needed for data repair
    }
};

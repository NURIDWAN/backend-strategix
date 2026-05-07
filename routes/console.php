<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('consultation:credits-sync-check {--user_id= : Check specific user only}', function () {
    $query = User::query()
        ->withSum('consultationCredits', 'remaining_sessions')
        ->withSum('consultationCredits', 'used_sessions')
        ->withSum('consultationCredits', 'total_sessions');

    if ($this->option('user_id')) {
        $query->where('id', (int) $this->option('user_id'));
    }

    $users = $query->get(['id', 'name', 'username']);

    if ($users->isEmpty()) {
        $this->warn('No users found for provided filters.');
        return 0;
    }

    $rows = [];
    foreach ($users as $user) {
        $adminRemaining = (int) ($user->consultation_credits_sum_remaining_sessions ?? 0);
        $calcRemaining = max(
            0,
            (int) ($user->consultation_credits_sum_total_sessions ?? 0)
            - (int) ($user->consultation_credits_sum_used_sessions ?? 0)
        );

        if ($adminRemaining !== $calcRemaining) {
            $rows[] = [
                $user->id,
                $user->username,
                $adminRemaining,
                $calcRemaining,
                $adminRemaining - $calcRemaining,
            ];
        }
    }

    if (empty($rows)) {
        $this->info('All checked users are in sync.');
        return 0;
    }

    $this->warn('Found out-of-sync consultation credits:');
    $this->table(
        ['user_id', 'username', 'admin_remaining', 'calculated_remaining', 'delta'],
        $rows
    );

    return 1;
})->purpose('Check consultation credit sync between admin sum and calculated remaining');

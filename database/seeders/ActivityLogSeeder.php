<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use Illuminate\Database\Seeder;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $logs = [
            [
                'user_id' => 1,
                'action' => 'login',
                'description' => 'Admin berhasil login ke sistem.',
                'model_type' => 'App\\Models\\User',
                'model_id' => 1,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Seeder',
                'properties' => json_encode(['method' => 'phone']),
                'created_at' => now()->subDays(7)->setHour(8)->setMinute(15),
            ],
            [
                'user_id' => 2,
                'action' => 'login',
                'description' => 'User Pandu berhasil login ke sistem.',
                'model_type' => 'App\\Models\\User',
                'model_id' => 2,
                'ip_address' => '192.168.1.10',
                'user_agent' => 'Mozilla/5.0 (Linux; Android 14) Seeder',
                'properties' => json_encode(['method' => 'phone']),
                'created_at' => now()->subDays(6)->setHour(9)->setMinute(30),
            ],
            [
                'user_id' => 1,
                'action' => 'user.role_changed',
                'description' => 'Admin mengubah role user Pandu.',
                'model_type' => 'App\\Models\\User',
                'model_id' => 2,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Seeder',
                'properties' => json_encode(['old_role' => 'user', 'new_role' => 'user']),
                'created_at' => now()->subDays(5)->setHour(10)->setMinute(0),
            ],
            [
                'user_id' => 1,
                'action' => 'user.status_changed',
                'description' => 'Admin mengubah status akun user Pandu.',
                'model_type' => 'App\\Models\\User',
                'model_id' => 2,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Seeder',
                'properties' => json_encode(['old_status' => 'active', 'new_status' => 'active']),
                'created_at' => now()->subDays(5)->setHour(10)->setMinute(5),
            ],
            [
                'user_id' => 2,
                'action' => 'login',
                'description' => 'User Pandu berhasil login ke sistem.',
                'model_type' => 'App\\Models\\User',
                'model_id' => 2,
                'ip_address' => '192.168.1.10',
                'user_agent' => 'Mozilla/5.0 (Linux; Android 14) Seeder',
                'properties' => json_encode(['method' => 'phone']),
                'created_at' => now()->subDays(4)->setHour(14)->setMinute(20),
            ],
            [
                'user_id' => 2,
                'action' => 'logout',
                'description' => 'User Pandu logout dari sistem.',
                'model_type' => 'App\\Models\\User',
                'model_id' => 2,
                'ip_address' => '192.168.1.10',
                'user_agent' => 'Mozilla/5.0 (Linux; Android 14) Seeder',
                'properties' => null,
                'created_at' => now()->subDays(4)->setHour(17)->setMinute(45),
            ],
            [
                'user_id' => 1,
                'action' => 'login',
                'description' => 'Admin berhasil login ke sistem.',
                'model_type' => 'App\\Models\\User',
                'model_id' => 1,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Seeder',
                'properties' => json_encode(['method' => 'phone']),
                'created_at' => now()->subDays(3)->setHour(8)->setMinute(0),
            ],
            [
                'user_id' => 1,
                'action' => 'setting.updated',
                'description' => 'Admin mengubah pengaturan sistem.',
                'model_type' => 'App\\Models\\Setting',
                'model_id' => null,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Seeder',
                'properties' => json_encode(['key' => 'site_name', 'old' => 'Grapadi Strategix', 'new' => 'Grapadi Strategix']),
                'created_at' => now()->subDays(2)->setHour(11)->setMinute(30),
            ],
            [
                'user_id' => 1,
                'action' => 'login',
                'description' => 'Admin berhasil login ke sistem.',
                'model_type' => 'App\\Models\\User',
                'model_id' => 1,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Seeder',
                'properties' => json_encode(['method' => 'phone']),
                'created_at' => now()->subDays(1)->setHour(9)->setMinute(0),
            ],
            [
                'user_id' => 1,
                'action' => 'logout',
                'description' => 'Admin logout dari sistem.',
                'model_type' => 'App\\Models\\User',
                'model_id' => 1,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Seeder',
                'properties' => null,
                'created_at' => now()->subHours(2),
            ],
        ];

        foreach ($logs as $log) {
            ActivityLog::create($log);
        }

        $this->command->info('Activity logs berhasil di-seed! (' . count($logs) . ' entries)');
    }
}

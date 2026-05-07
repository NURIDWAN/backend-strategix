<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Akun admin
        User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@grapadi.id',
            'phone' => '628000000000',
            'password' => Hash::make('admin123'),
            'profile_photo' => null,
            'account_status' => 'active',
            'role' => 'admin',
            'phone_verified_at' => now(),
            'otp_code' => null,
            'otp_expires_at' => null,
            'reset_otp_code' => null,
            'reset_otp_expires_at' => null,
        ]);

        // Akun user biasa
        User::create([
            'name' => 'Pandu',
            'username' => 'pandu123',
            'email' => 'user@grapadi.id',
            'phone' => '628123456789',
            'password' => Hash::make('password'),
            'profile_photo' => null,
            'account_status' => 'active',
            'role' => 'user',
            'phone_verified_at' => now(),
            'otp_code' => null,
            'otp_expires_at' => null,
            'reset_otp_code' => null,
            'reset_otp_expires_at' => null,
        ]);
    }
}

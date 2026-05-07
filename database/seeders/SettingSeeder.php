<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General settings
            [
                'group' => 'general',
                'key' => 'site_name',
                'value' => 'Grapadi Strategix',
                'type' => 'string',
                'description' => 'Nama aplikasi yang ditampilkan di header dan email.',
            ],
            [
                'group' => 'general',
                'key' => 'site_description',
                'value' => 'Platform manajemen bisnis dan keuangan untuk UMKM Indonesia.',
                'type' => 'string',
                'description' => 'Deskripsi singkat aplikasi.',
            ],
            [
                'group' => 'general',
                'key' => 'maintenance_mode',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Aktifkan mode maintenance untuk menonaktifkan akses member.',
            ],
            [
                'group' => 'general',
                'key' => 'maintenance_message',
                'value' => 'Sistem sedang dalam pemeliharaan. Silakan coba beberapa saat lagi.',
                'type' => 'string',
                'description' => 'Pesan yang ditampilkan saat mode maintenance aktif.',
            ],
            [
                'group' => 'general',
                'key' => 'otp_enabled',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Aktifkan verifikasi OTP via Email.',
            ],

            // Payment settings
            [
                'group' => 'payment',
                'key' => 'payment_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Aktifkan/nonaktifkan fitur pembayaran.',
            ],
            [
                'group' => 'payment',
                'key' => 'payment_mode',
                'value' => 'sandbox',
                'type' => 'string',
                'description' => 'Mode pembayaran Singapay: mock, sandbox, atau production.',
            ],
            [
                'group' => 'payment',
                'key' => 'auto_activate_payment',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Otomatis aktifkan akses Pro setelah pembayaran terverifikasi.',
            ],

            // Affiliate settings
            [
                'group' => 'affiliate',
                'key' => 'affiliate_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Aktifkan/nonaktifkan program affiliate.',
            ],
            [
                'group' => 'affiliate',
                'key' => 'commission_percentage',
                'value' => '17',
                'type' => 'number',
                'description' => 'Persentase komisi affiliate (%).',
            ],
            [
                'group' => 'affiliate',
                'key' => 'minimum_withdrawal',
                'value' => '50000',
                'type' => 'number',
                'description' => 'Minimal saldo untuk penarikan komisi (Rp).',
            ],
            [
                'group' => 'affiliate',
                'key' => 'max_slug_changes',
                'value' => '3',
                'type' => 'number',
                'description' => 'Jumlah maksimal perubahan slug link affiliate.',
            ],

            // Feature flags
            [
                'group' => 'features',
                'key' => 'feature_forecast',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Aktifkan fitur Forecast Keuangan.',
            ],
            [
                'group' => 'features',
                'key' => 'feature_pdf_export',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Aktifkan fitur export PDF (memerlukan akses Pro).',
            ],
            [
                'group' => 'features',
                'key' => 'feature_articles',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Aktifkan fitur artikel publik.',
            ],
            [
                'group' => 'features',
                'key' => 'registration_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Aktifkan registrasi pengguna baru.',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}

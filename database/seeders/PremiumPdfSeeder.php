<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Singapay\PremiumPdf;

class PremiumPdfSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'package_type' => 'monthly',
                'name' => 'Paket Bulanan Export PDF Pro',
                'description' => 'Akses Export PDF Pro selama 30 hari dengan fitur lengkap untuk kebutuhan business plan dan financial report Anda.',
                'price' => 200000, // Rp 200.000
                'duration_days' => 30,
                'features' => [
                    'Export Business Plan ke PDF',
                    'Export Financial Report ke PDF',
                    'Export Forecast Report ke PDF',
                    'Layout profesional dan rapi',
                    'Unlimited export selama masa aktif',
                    'Watermark SmartPlan (opsional)',
                    'Download langsung',
                    'Support email',
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'package_type' => 'yearly',
                'name' => 'Paket Tahunan Export PDF Pro',
                'description' => 'Akses Export PDF Pro selama 365 hari dengan hemat 30%! Solusi terbaik untuk kebutuhan jangka panjang.',
                'price' => 1680000, // Rp 1.680.000 (hemat Rp 720.000 dari harga normal Rp 2.400.000)
                'duration_days' => 365,
                'features' => [
                    'Semua fitur Paket Bulanan',
                    'Export Business Plan ke PDF',
                    'Export Financial Report ke PDF',
                    'Export Forecast Report ke PDF',
                    'Layout profesional dan rapi',
                    'Unlimited export selama masa aktif',
                    'Watermark SmartPlan (opsional)',
                    'Download langsung',
                    'Priority support email',
                    'Hemat 30% (Rp 720.000)',
                    'Cocok untuk penggunaan jangka panjang',
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'package_type' => 'consultation',
                'name' => 'Paket Konsultasi 1 Sesi (Diskon 60%)',
                'description' => 'Harga normal Rp 1.500.000, promo diskon 60% menjadi Rp 600.000 untuk 1 kali sesi konsultasi.',
                'price' => 600000,
                'duration_days' => 30,
                'consultation_credits' => 1,
                'features' => [
                    '1x sesi konsultasi pakar',
                    'Durasi sesi hingga 60 menit',
                    'Berlaku 30 hari sejak pembayaran',
                    'Cocok untuk konsultasi cepat dan terarah',
                    'Harga promo diskon 60% dari Rp 1.500.000',
                ],
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($packages as $package) {
            PremiumPdf::updateOrCreate(
                ['package_type' => $package['package_type']],
                $package
            );
        }

        $this->command->info('Premium PDF packages seeded successfully!');
    }
}

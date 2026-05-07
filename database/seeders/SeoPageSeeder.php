<?php

namespace Database\Seeders;

use App\Models\SeoPage;
use Illuminate\Database\Seeder;

class SeoPageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'page_identifier' => 'home',
                'page_name'       => 'Beranda',
                'title'           => 'Grapadi Strategix — Platform Manajemen Bisnis & Keuangan UMKM',
                'meta_description' => 'Grapadi Strategix adalah platform digital untuk membantu UMKM Indonesia mengelola bisnis plan, keuangan, dan forecast secara profesional.',
                'meta_keywords'   => 'grapadi strategix, manajemen bisnis, keuangan UMKM, bisnis plan, platform UMKM',
                'og_title'        => 'Grapadi Strategix — Platform Manajemen Bisnis UMKM',
                'og_description'  => 'Platform digital untuk UMKM Indonesia. Kelola bisnis plan, keuangan, dan forecast bisnis Anda secara profesional.',
            ],
            [
                'page_identifier' => 'features',
                'page_name'       => 'Fitur',
                'title'           => 'Fitur — Grapadi Strategix',
                'meta_description' => 'Jelajahi fitur lengkap Grapadi Strategix: Business Plan, Manajemen Keuangan, Forecast, Export PDF, dan lainnya.',
                'meta_keywords'   => 'fitur grapadi, business plan, manajemen keuangan, forecast bisnis, export PDF',
                'og_title'        => 'Fitur Grapadi Strategix',
                'og_description'  => 'Business Plan, Manajemen Keuangan, Forecast, Export PDF — semua dalam satu platform.',
            ],
            [
                'page_identifier' => 'pricing',
                'page_name'       => 'Harga',
                'title'           => 'Harga & Paket — Grapadi Strategix',
                'meta_description' => 'Pilih paket berlangganan Grapadi Strategix yang cocok untuk bisnis Anda. Mulai dari paket gratis hingga premium.',
                'meta_keywords'   => 'harga grapadi, paket berlangganan, pricing, UMKM premium',
                'og_title'        => 'Harga & Paket Grapadi Strategix',
                'og_description'  => 'Pilih paket yang sesuai kebutuhan bisnis Anda.',
            ],
            [
                'page_identifier' => 'faq',
                'page_name'       => 'FAQ',
                'title'           => 'FAQ — Grapadi Strategix',
                'meta_description' => 'Pertanyaan yang sering diajukan tentang Grapadi Strategix. Temukan jawaban untuk membantu Anda memulai.',
                'meta_keywords'   => 'FAQ grapadi, tanya jawab, bantuan, pertanyaan umum',
                'og_title'        => 'FAQ — Grapadi Strategix',
                'og_description'  => 'Temukan jawaban atas pertanyaan umum tentang platform Grapadi Strategix.',
            ],
            [
                'page_identifier' => 'terms',
                'page_name'       => 'Syarat & Ketentuan',
                'title'           => 'Syarat & Ketentuan — Grapadi Strategix',
                'meta_description' => 'Baca syarat dan ketentuan penggunaan platform Grapadi Strategix.',
                'meta_keywords'   => 'syarat ketentuan, terms of service, kebijakan',
                'og_title'        => 'Syarat & Ketentuan',
                'og_description'  => 'Syarat dan ketentuan penggunaan Grapadi Strategix.',
            ],
            [
                'page_identifier' => 'articles',
                'page_name'       => 'Artikel',
                'title'           => 'Artikel & Insight — Grapadi Strategix',
                'meta_description' => 'Baca artikel, tips, dan insight seputar manajemen bisnis dan keuangan UMKM di Indonesia.',
                'meta_keywords'   => 'artikel bisnis, tips UMKM, insight keuangan, blog grapadi',
                'og_title'        => 'Artikel & Insight — Grapadi Strategix',
                'og_description'  => 'Tips dan insight seputar manajemen bisnis dan keuangan UMKM.',
            ],
            [
                'page_identifier' => 'login',
                'page_name'       => 'Login',
                'title'           => 'Masuk — Grapadi Strategix',
                'meta_description' => 'Masuk ke akun Grapadi Strategix Anda untuk mengelola bisnis plan dan keuangan.',
                'meta_keywords'   => 'login grapadi, masuk akun',
                'og_title'        => 'Masuk ke Grapadi Strategix',
                'og_description'  => 'Akses dashboard bisnis Anda.',
            ],
            [
                'page_identifier' => 'register',
                'page_name'       => 'Daftar',
                'title'           => 'Daftar Akun — Grapadi Strategix',
                'meta_description' => 'Buat akun gratis di Grapadi Strategix dan mulai kelola bisnis Anda secara profesional.',
                'meta_keywords'   => 'daftar grapadi, register, buat akun, gratis',
                'og_title'        => 'Daftar Akun Grapadi Strategix',
                'og_description'  => 'Daftar gratis dan mulai kelola bisnis Anda.',
            ],
            [
                'page_identifier' => 'dashboard',
                'page_name'       => 'Dashboard',
                'title'           => 'Dashboard — Grapadi Strategix',
                'meta_description' => 'Akses ringkasan bisnis, manajemen keuangan, dan business plan Anda di satu tempat.',
                'meta_keywords'   => 'dashboard grapadi, ringkasan bisnis, manajemen keuangan UMKM',
                'og_title'        => 'Dashboard — Grapadi Strategix',
                'og_description'  => 'Akses ringkasan bisnis dan manajemen keuangan Anda.',
            ],
        ];

        foreach ($pages as $page) {
            SeoPage::updateOrCreate(
                ['page_identifier' => $page['page_identifier']],
                $page
            );
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Article\ArticleCategory;
use App\Models\Article\Article;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        // Buat kategori artikel
        $categories = [
            [
                'name' => 'Tips Bisnis',
                'description' => 'Kumpulan tips dan trik untuk mengembangkan bisnis Anda.',
            ],
            [
                'name' => 'Keuangan UMKM',
                'description' => 'Panduan mengelola keuangan untuk usaha mikro, kecil, dan menengah.',
            ],
            [
                'name' => 'Pemasaran Digital',
                'description' => 'Strategi pemasaran online untuk meningkatkan jangkauan bisnis.',
            ],
            [
                'name' => 'Berita & Update',
                'description' => 'Informasi terbaru seputar fitur dan layanan Grapadi Strategix.',
            ],
        ];

        $categoryModels = [];
        foreach ($categories as $cat) {
            $categoryModels[$cat['name']] = ArticleCategory::updateOrCreate(
                ['name' => $cat['name']],
                $cat
            );
        }

        // Buat sample artikel
        $articles = [
            [
                'user_id' => 1, // Admin
                'article_category_id' => $categoryModels['Tips Bisnis']->id,
                'title' => '5 Langkah Menyusun Business Plan yang Efektif',
                'excerpt' => 'Business plan yang baik adalah fondasi kesuksesan bisnis. Pelajari 5 langkah penting untuk menyusun rencana bisnis yang solid dan meyakinkan investor.',
                'body' => '<h2>Mengapa Business Plan Penting?</h2><p>Business plan bukan sekadar dokumen formalitas. Ini adalah peta jalan yang memandu arah bisnis Anda. Dengan business plan yang terstruktur, Anda dapat mengidentifikasi peluang, mengantisipasi tantangan, dan mengukur kemajuan bisnis.</p><h3>1. Tentukan Visi dan Misi</h3><p>Langkah pertama adalah menentukan visi jangka panjang dan misi bisnis Anda. Visi menggambarkan ke mana bisnis akan dibawa, sementara misi menjelaskan bagaimana cara mencapainya.</p><h3>2. Lakukan Analisis Pasar</h3><p>Pahami pasar target Anda. Siapa pelanggan ideal Anda? Apa kebutuhan mereka? Bagaimana kompetitor memenuhi kebutuhan tersebut? Analisis SWOT sangat membantu di tahap ini.</p><h3>3. Rancang Strategi Pemasaran</h3><p>Tentukan bagaimana Anda akan menjangkau dan menarik pelanggan. Marketing mix (Product, Price, Place, Promotion) menjadi kerangka yang baik untuk merancang strategi.</p><h3>4. Susun Proyeksi Keuangan</h3><p>Buat proyeksi pendapatan, biaya operasional, dan arus kas untuk 1-5 tahun ke depan. Ini menunjukkan kelayakan finansial bisnis Anda.</p><h3>5. Review dan Perbarui Secara Berkala</h3><p>Business plan bukanlah dokumen statis. Perbarui secara berkala sesuai perkembangan bisnis dan kondisi pasar.</p>',
                'status' => 'published',
                'published_at' => now()->subDays(14),
            ],
            [
                'user_id' => 1,
                'article_category_id' => $categoryModels['Keuangan UMKM']->id,
                'title' => 'Panduan Lengkap Mengelola Arus Kas untuk UMKM',
                'excerpt' => 'Arus kas adalah nyawa bisnis. Pelajari cara mengelola cash flow agar bisnis UMKM Anda tetap sehat dan berkembang.',
                'body' => '<h2>Pentingnya Manajemen Arus Kas</h2><p>Banyak UMKM yang memiliki penjualan bagus namun tetap mengalami kesulitan keuangan. Penyebab utamanya adalah manajemen arus kas yang buruk. Arus kas positif berarti uang masuk lebih besar dari uang keluar.</p><h3>Catat Semua Pemasukan dan Pengeluaran</h3><p>Langkah pertama yang paling dasar adalah mencatat semua transaksi keuangan. Gunakan aplikasi seperti Grapadi Strategix untuk mempermudah pencatatan.</p><h3>Pisahkan Keuangan Pribadi dan Bisnis</h3><p>Kesalahan fatal yang sering dilakukan pelaku UMKM adalah mencampurkan keuangan pribadi dengan bisnis. Buat rekening terpisah untuk bisnis Anda.</p><h3>Buat Proyeksi Arus Kas</h3><p>Proyeksikan arus kas 3-6 bulan ke depan. Ini membantu Anda mengantisipasi periode kekurangan kas dan merencanakan langkah mitigasi.</p><h3>Kelola Piutang dengan Ketat</h3><p>Jika bisnis Anda memberikan kredit kepada pelanggan, pastikan ada sistem penagihan yang teratur. Piutang yang menumpuk bisa membunuh arus kas bisnis.</p>',
                'status' => 'published',
                'published_at' => now()->subDays(7),
            ],
            [
                'user_id' => 1,
                'article_category_id' => $categoryModels['Pemasaran Digital']->id,
                'title' => 'Strategi Social Media Marketing untuk UMKM di 2026',
                'excerpt' => 'Media sosial adalah alat pemasaran paling efektif dan terjangkau untuk UMKM. Simak strategi terkini yang bisa langsung Anda terapkan.',
                'body' => '<h2>Kenapa Social Media Penting untuk UMKM?</h2><p>Dengan lebih dari 190 juta pengguna media sosial di Indonesia, platform seperti Instagram, TikTok, dan WhatsApp Business menjadi saluran pemasaran yang sangat potensial untuk UMKM.</p><h3>Pilih Platform yang Tepat</h3><p>Tidak perlu aktif di semua platform. Fokus pada 2-3 platform di mana target pasar Anda paling aktif. Instagram dan TikTok cocok untuk produk visual, LinkedIn untuk B2B.</p><h3>Buat Konten yang Bernilai</h3><p>Jangan hanya berjualan. Berikan konten edukatif, tips, behind-the-scenes, dan testimoni pelanggan. Rasio ideal: 80% konten bernilai, 20% promosi.</p><h3>Konsisten dalam Posting</h3><p>Buat jadwal posting dan patuhi. Konsistensi lebih penting dari frekuensi. Lebih baik 3x seminggu secara konsisten daripada sehari 5 post lalu menghilang.</p><h3>Manfaatkan Fitur Gratis</h3><p>Reels Instagram, TikTok Stories, WhatsApp Catalog — semua ini gratis dan sangat efektif untuk menjangkau pelanggan baru.</p>',
                'status' => 'published',
                'published_at' => now()->subDays(3),
            ],
            [
                'user_id' => 1,
                'article_category_id' => $categoryModels['Berita & Update']->id,
                'title' => 'Fitur Baru: Forecast Keuangan dengan AI',
                'excerpt' => 'Grapadi Strategix kini dilengkapi fitur forecast keuangan berbasis AI yang membantu Anda memprediksi tren pendapatan dan pengeluaran bisnis.',
                'body' => '<h2>Apa itu Forecast Keuangan?</h2><p>Forecast keuangan adalah prediksi masa depan berdasarkan data historis bisnis Anda. Dengan fitur baru ini, Grapadi Strategix menganalisis pola transaksi Anda dan memberikan proyeksi yang akurat.</p><h3>Fitur Utama</h3><ul><li><strong>Prediksi Pendapatan:</strong> Lihat estimasi pendapatan 3-12 bulan ke depan berdasarkan tren historis.</li><li><strong>Analisis Pengeluaran:</strong> Identifikasi pola pengeluaran dan area yang bisa dioptimalkan.</li><li><strong>Insights Otomatis:</strong> Dapatkan rekomendasi aksi berdasarkan hasil forecast.</li><li><strong>Visualisasi Grafik:</strong> Tampilan grafik interaktif untuk memudahkan pemahaman data.</li></ul><h3>Cara Menggunakan</h3><p>Buka menu Forecast Keuangan di dashboard Anda, pilih bisnis dan periode data, lalu klik Generate Forecast. Sistem akan memproses data dan menampilkan hasil dalam beberapa detik.</p><p>Fitur ini tersedia untuk semua pengguna. Mulai gunakan sekarang dan rencanakan masa depan bisnis Anda dengan lebih baik!</p>',
                'status' => 'published',
                'published_at' => now()->subDays(1),
            ],
            [
                'user_id' => 1,
                'article_category_id' => $categoryModels['Tips Bisnis']->id,
                'title' => 'Memahami Break Even Point untuk Bisnis Anda',
                'excerpt' => 'Break Even Point (BEP) adalah titik di mana pendapatan sama dengan total biaya. Ketahui cara menghitung dan memanfaatkan BEP untuk pengambilan keputusan bisnis.',
                'body' => '<h2>Apa itu Break Even Point?</h2><p>Break Even Point (BEP) atau titik impas adalah kondisi di mana total pendapatan sama dengan total biaya, sehingga bisnis tidak untung dan tidak rugi. Memahami BEP sangat penting untuk mengetahui berapa minimal penjualan yang harus dicapai.</p><h3>Rumus BEP</h3><p>BEP (unit) = Biaya Tetap / (Harga Jual per Unit - Biaya Variabel per Unit)</p><h3>Contoh Perhitungan</h3><p>Jika biaya tetap bulanan Rp 10.000.000, harga jual per produk Rp 50.000, dan biaya variabel per produk Rp 20.000, maka BEP = 10.000.000 / (50.000 - 20.000) = 334 unit per bulan.</p><h3>Manfaat Mengetahui BEP</h3><ul><li>Menentukan target penjualan minimum</li><li>Mengevaluasi kelayakan harga jual</li><li>Merencanakan strategi pengurangan biaya</li><li>Mengambil keputusan investasi yang lebih baik</li></ul>',
                'status' => 'draft',
                'published_at' => null,
            ],
        ];

        foreach ($articles as $article) {
            Article::updateOrCreate(
                ['title' => $article['title']],
                $article
            );
        }

        $this->command->info('Article categories dan articles berhasil di-seed!');
    }
}

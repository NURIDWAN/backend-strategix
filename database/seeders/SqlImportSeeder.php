<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Import data dari file .sql ke database.
 *
 * Cara pakai:
 * 1. Taruh file .sql di folder: database/seeders/data/
 * 2. Daftarkan nama file di array $files di bawah
 * 3. Jalankan: php artisan db:seed --class=SqlImportSeeder
 */
class SqlImportSeeder extends Seeder
{
    /**
     * Daftar file .sql yang akan diimport (urut dari atas ke bawah).
     * Path relatif dari database/seeders/data/
     */
    protected array $files = [
        // 'nama_file.sql',
    ];

    public function run(): void
    {
        if (empty($this->files)) {
            $this->command?->warn('SqlImportSeeder: tidak ada file SQL yang didaftarkan di $files.');
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach ($this->files as $filename) {
            $path = database_path("seeders/data/{$filename}");

            if (!file_exists($path)) {
                throw new RuntimeException("File SQL tidak ditemukan: {$path}");
            }

            $sql = file_get_contents($path);

            if (empty(trim($sql))) {
                $this->command?->warn("File kosong, dilewati: {$filename}");
                continue;
            }

            DB::unprepared($sql);

            $this->command?->info("✓ Imported: {$filename}");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command?->info('SqlImportSeeder selesai.');
    }
}

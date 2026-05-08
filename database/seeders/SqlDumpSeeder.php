<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SqlDumpSeeder extends Seeder
{
    protected array $files = [
        'sevendre_strategix.sql',
    ];

    /**
     * Tabel yang dikelola Laravel sendiri, skip dari import.
     */
    protected array $skipTables = [
        'migrations',
        'sessions',
    ];

    public function run(): void
    {
        $config   = config('database.connections.' . config('database.default'));
        $host     = $config['host'] ?? '127.0.0.1';
        $port     = $config['port'] ?? 3306;
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'];

        foreach ($this->files as $filename) {
            $path = database_path("seeders/data/{$filename}");

            if (!file_exists($path)) {
                throw new RuntimeException("File SQL tidak ditemukan: {$path}");
            }

            $cleanPath = $this->buildCleanSql($path);

            $escapedPath     = escapeshellarg($cleanPath);
            $escapedDb       = escapeshellarg($database);
            $escapedUser     = escapeshellarg($username);
            $escapedPassword = escapeshellarg($password);
            $escapedHost     = escapeshellarg($host);

            $cmd = "mysql -h {$escapedHost} -P {$port} -u {$escapedUser} -p{$escapedPassword} {$escapedDb} < {$escapedPath} 2>&1";

            exec($cmd, $output, $exitCode);

            @unlink($cleanPath);

            if ($exitCode !== 0) {
                throw new RuntimeException(
                    "Gagal import {$filename}:\n" . implode("\n", $output)
                );
            }

            $this->command?->info("✓ Imported: {$filename}");
        }
    }

    /**
     * Baca SQL asli, hapus block tabel yang di-skip dan
     * baris VALUES orphan (tanpa INSERT INTO) yang corrupt.
     */
    private function buildCleanSql(string $originalPath): string
    {
        $lines         = file($originalPath, FILE_IGNORE_NEW_LINES);
        $result        = [];
        $skipTable     = false;
        $insideInsert  = false;

        foreach ($lines as $line) {
            $trimmed = ltrim($line);

            // ── Deteksi masuk ke block tabel yang di-skip ──────────────
            foreach ($this->skipTables as $table) {
                $q = preg_quote($table, '/');
                if (preg_match('/^-- (Dumping data|Table structure) for table `' . $q . '`/', $trimmed)
                    || preg_match('/^(INSERT INTO|CREATE TABLE|DROP TABLE|LOCK TABLES|TRUNCATE TABLE)\s+`' . $q . '`/', $trimmed)
                ) {
                    $skipTable    = true;
                    $insideInsert = false;
                    break;
                }
            }

            // ── Keluar dari block skip ──────────────────────────────────
            if ($skipTable) {
                $skipPattern = implode('|', array_map(fn($t) => preg_quote($t, '/'), $this->skipTables));
                if (preg_match('/^-- (Dumping data|Table structure) for table `(?!' . $skipPattern . '`)/', $trimmed)) {
                    $skipTable = false;
                } elseif (preg_match('/^UNLOCK TABLES/', $trimmed)) {
                    $skipTable = false;
                    continue;
                } else {
                    continue;
                }
            }

            // ── Track apakah kita sedang di dalam INSERT statement ──────
            if (preg_match('/^INSERT\s+(?:IGNORE\s+)?INTO\s+/i', $trimmed)) {
                $insideInsert = true;
            }

            // ── Hapus VALUES orphan (muncul di luar INSERT statement) ───
            // Cek SEBELUM menutup insert block, agar baris VALUES+semicolon
            // terakhir dalam INSERT yang valid tidak ikut terhapus.
            if (!$insideInsert && preg_match('/^\(\d+,/', $trimmed)) {
                continue;
            }

            // Tutup insert block setelah baris ini diproses
            if ($insideInsert && str_contains($line, ';')) {
                $insideInsert = false;
            }

            $result[] = $line;
        }

        $sql = implode("\n", $result);

        // Skip duplicate entries instead of failing
        $sql = preg_replace('/\bINSERT INTO\b/', 'INSERT IGNORE INTO', $sql);

        $tmpPath = sys_get_temp_dir() . '/sql_import_' . uniqid() . '.sql';
        file_put_contents($tmpPath, $sql);

        return $tmpPath;
    }
}

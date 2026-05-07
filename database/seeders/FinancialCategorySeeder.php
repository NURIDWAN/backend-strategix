<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\ManagementFinancial\FinancialCategory;
use App\Models\User;

class FinancialCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Truncate untuk bisa run berulang kali (gunakan DB::table karena truncate bypass model events)
        DB::table('financial_categories')->delete();

        $users = User::all();

        // Kategori Income default
        $incomeCategories = [
            ['name' => 'Penjualan Produk', 'color' => '#10B981', 'description' => 'Pendapatan dari penjualan produk utama', 'category_subtype' => 'operating_revenue'],
            ['name' => 'Penjualan Jasa', 'color' => '#059669', 'description' => 'Pendapatan dari layanan jasa', 'category_subtype' => 'operating_revenue'],
            ['name' => 'Pendapatan Bunga', 'color' => '#34D399', 'description' => 'Pendapatan bunga dari investasi atau deposito', 'category_subtype' => 'non_operating_revenue'],
            ['name' => 'Pendapatan Lain-lain', 'color' => '#22D3EE', 'description' => 'Pendapatan di luar operasional utama', 'category_subtype' => 'non_operating_revenue'],
        ];

        // Kategori Expense default
        $expenseCategories = [
            ['name' => 'HPP - Bahan Baku', 'color' => '#EF4444', 'description' => 'Harga Pokok Penjualan: Bahan baku produksi', 'category_subtype' => 'cogs'],
            ['name' => 'HPP - Tenaga Kerja Langsung', 'color' => '#DC2626', 'description' => 'Harga Pokok Penjualan: Biaya tenaga kerja produksi', 'category_subtype' => 'cogs'],
            ['name' => 'Gaji Karyawan', 'color' => '#F87171', 'description' => 'Pengeluaran untuk gaji dan tunjangan karyawan', 'category_subtype' => 'operating_expense'],
            ['name' => 'Biaya Operasional', 'color' => '#FB923C', 'description' => 'Biaya operasional harian', 'category_subtype' => 'operating_expense'],
            ['name' => 'Listrik & Air', 'color' => '#FBBF24', 'description' => 'Biaya utilitas listrik dan air', 'category_subtype' => 'operating_expense'],
            ['name' => 'Sewa Tempat', 'color' => '#F59E0B', 'description' => 'Biaya sewa lokasi usaha', 'category_subtype' => 'operating_expense'],
            ['name' => 'Marketing & Promosi', 'color' => '#8B5CF6', 'description' => 'Biaya untuk iklan dan promosi', 'category_subtype' => 'operating_expense'],
            ['name' => 'Transportasi', 'color' => '#3B82F6', 'description' => 'Biaya transportasi dan distribusi', 'category_subtype' => 'operating_expense'],
            ['name' => 'Perawatan & Maintenance', 'color' => '#6366F1', 'description' => 'Biaya perawatan peralatan dan fasilitas', 'category_subtype' => 'operating_expense'],
            ['name' => 'Beban Bunga Pinjaman', 'color' => '#EC4899', 'description' => 'Bunga dari pinjaman bank atau kredit', 'category_subtype' => 'interest_expense'],
            ['name' => 'Pajak Penghasilan', 'color' => '#14B8A6', 'description' => 'Pajak penghasilan badan atau pribadi', 'category_subtype' => 'tax_expense'],
            ['name' => 'Pengeluaran Lain-lain', 'color' => '#6B7280', 'description' => 'Pengeluaran di luar kategori utama', 'category_subtype' => 'operating_expense'],
        ];

        foreach ($users as $user) {
            // Insert Income Categories
            foreach ($incomeCategories as $category) {
                FinancialCategory::create([
                    'user_id' => $user->id,
                    'business_background_id' => 1,
                    'name' => $category['name'],
                    'type' => 'income',
                    'color' => $category['color'],
                    'status' => 'actual',
                    'description' => $category['description'],
                    'category_subtype' => $category['category_subtype'],
                ]);
            }

            // Insert Expense Categories
            foreach ($expenseCategories as $category) {
                FinancialCategory::create([
                    'user_id' => $user->id,
                    'business_background_id' => 1,
                    'name' => $category['name'],
                    'type' => 'expense',
                    'color' => $category['color'],
                    'status' => 'actual',
                    'description' => $category['description'],
                    'category_subtype' => $category['category_subtype'],
                ]);
            }

            // Tambahkan beberapa kategori dengan status 'plan' untuk simulasi
            FinancialCategory::create([
                'user_id' => $user->id,
                'business_background_id' => 1,
                'name' => 'Rencana Ekspansi',
                'type' => 'expense',
                'color' => '#A855F7',
                'status' => 'plan',
                'description' => 'Rencana biaya untuk ekspansi bisnis',
                'category_subtype' => 'operating_expense',
            ]);

            FinancialCategory::create([
                'user_id' => $user->id,
                'business_background_id' => 1,
                'name' => 'Proyeksi Pendapatan Baru',
                'type' => 'income',
                'color' => '#22C55E',
                'status' => 'plan',
                'description' => 'Proyeksi pendapatan dari produk baru',
                'category_subtype' => 'operating_revenue',
            ]);
        }
    }
}

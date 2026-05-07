<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\ManagementFinancial\FinancialSimulation;
use App\Models\ManagementFinancial\FinancialCategory;
use App\Models\BusinessBackground;
use App\Models\User;
use Carbon\Carbon;

class FinancialSimulationSeeder extends Seeder
{
    public function run(): void
    {
        // Truncate untuk bisa run berulang kali
        DB::table('financial_simulations')->delete();

        $users = User::all();
        $paymentMethods = ['cash', 'bank_transfer', 'credit_card', 'digital_wallet', 'other'];
        $statuses = ['completed', 'completed', 'completed', 'completed', 'planned'];

        foreach ($users as $user) {
            $businessBackground = BusinessBackground::where('user_id', $user->id)->first();

            if (!$businessBackground) {
                continue;
            }

            $categories = FinancialCategory::where('user_id', $user->id)
                ->where('status', 'actual')
                ->get();

            // OPTIMASI: Hanya generate 6 bulan terakhir (bukan 2 tahun)
            $months = [];
            for ($m = 6; $m >= 0; $m--) {
                $date = Carbon::now()->subMonths($m);
                if ($date->isFuture()) continue;
                $months[] = ['year' => $date->year, 'month' => $date->month];
            }

            $recordsToInsert = [];
            $counter = 0;

            foreach ($categories as $category) {
                foreach ($months as $monthData) {
                    $year = $monthData['year'];
                    $month = $monthData['month'];

                    // OPTIMASI: Kurangi jumlah transaksi
                    if ($category->type === 'income') {
                        $transactionCount = rand(2, 4); // Dari 5-10 jadi 2-4
                    } else {
                        $transactionCount = rand(1, 2); // Dari 2-4 jadi 1-2
                    }

                    for ($i = 0; $i < $transactionCount; $i++) {
                        $randomDay = rand(1, Carbon::create($year, $month, 1)->daysInMonth);
                        $randomDate = Carbon::create($year, $month, $randomDay);

                        if ($randomDate->isFuture()) {
                            continue;
                        }

                        if ($category->type === 'income') {
                            if ($category->category_subtype === 'operating_revenue') {
                                $amount = rand(5000000, 15000000);
                            } else {
                                $amount = rand(500000, 2000000);
                            }
                            $descriptions = ['Penjualan ' . $category->name, 'Pendapatan ' . $category->name];
                        } else {
                            switch ($category->category_subtype) {
                                case 'cogs':
                                    $amount = rand(1000000, 3000000);
                                    break;
                                case 'operating_expense':
                                    $amount = rand(500000, 2000000);
                                    break;
                                default:
                                    $amount = rand(100000, 1000000);
                            }
                            $descriptions = ['Pembayaran ' . $category->name, 'Biaya ' . $category->name];
                        }

                        $status = $statuses[array_rand($statuses)];
                        $simulationCode = 'SIM' . $randomDate->format('YmdHis') . str_pad($counter, 4, '0', STR_PAD_LEFT);
                        $counter++;

                        $recordsToInsert[] = [
                            'user_id' => $user->id,
                            'business_background_id' => $businessBackground->id,
                            'financial_category_id' => $category->id,
                            'simulation_code' => $simulationCode,
                            'type' => $category->type,
                            'amount' => $amount,
                            'simulation_date' => $randomDate,
                            'year' => $year,
                            'description' => $descriptions[array_rand($descriptions)],
                            'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                            'status' => $status,
                            'is_recurring' => false,
                            'recurring_frequency' => null,
                            'recurring_end_date' => null,
                            'notes' => $status === 'planned' ? 'Rencana ' . $randomDate->format('M Y') : null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        // OPTIMASI: Batch insert setiap 500 records
                        if (count($recordsToInsert) >= 500) {
                            DB::table('financial_simulations')->insert($recordsToInsert);
                            $recordsToInsert = [];
                        }
                    }
                }
            }

            // Insert sisa records
            if (count($recordsToInsert) > 0) {
                DB::table('financial_simulations')->insert($recordsToInsert);
            }

            // TAMBAHAN: Generate data gaji karyawan khusus (12 bulan regular payment)
            $gajiCategory = $categories->where('name', 'Gaji Karyawan')->first();

            if ($gajiCategory) {
                $gajiRecords = [];
                $staffNames = ['Rizky Pratama', 'Andi Setiawan', 'Siti Nurhaliza', 'Dina Cahyani', 'Budi Santoso', 'Lina Mardiana', 'Ahmad Hidayat', 'Rina Oktaviani'];
                $gajiAmount = [4500000, 3800000, 3200000, 2800000, 2800000, 3000000, 2500000, 2700000]; // Sesuai seeder tim

                // Buat gaji untuk setiap bulan dalam 6 bulan terakhir + bulan ini
                for ($m = 6; $m >= 0; $m--) {
                    $date = Carbon::now()->subMonths($m);
                    if ($date->isFuture()) continue;

                    // Setiap bulan, buat satu entry untuk total gaji seluruh staff
                    $totalGaji = array_sum($gajiAmount); // Total: Rp 25.2 juta/bulan

                    $gajiRecords[] = [
                        'user_id' => $user->id,
                        'business_background_id' => $businessBackground->id,
                        'financial_category_id' => $gajiCategory->id,
                        'simulation_code' => 'SIM-GAJI-' . $date->format('Y-m'),
                        'type' => 'expense',
                        'amount' => $totalGaji,
                        'simulation_date' => $date->endOfMonth(),
                        'year' => $date->year,
                        'description' => 'Pembayaran gaji karyawan bulan ' . $date->format('F Y') . ' (8 staff)',
                        'payment_method' => 'bank_transfer',
                        'status' => $date->isBefore(Carbon::now()) ? 'completed' : 'planned',
                        'is_recurring' => true,
                        'recurring_frequency' => 'monthly',
                        'recurring_end_date' => Carbon::now()->addYear(),
                        'notes' => 'Pembayaran gaji rutin: ' . implode(', ', $staffNames),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // BONUS: Tambah individual gaji per staff untuk detail
                    foreach ($staffNames as $index => $staffName) {
                        $gajiRecords[] = [
                            'user_id' => $user->id,
                            'business_background_id' => $businessBackground->id,
                            'financial_category_id' => $gajiCategory->id,
                            'simulation_code' => 'SIM-GAJI-' . $date->format('Y-m') . '-' . str_pad($index + 1, 2, '0', STR_PAD_LEFT),
                            'type' => 'expense',
                            'amount' => $gajiAmount[$index] ?? 2500000,
                            'simulation_date' => $date->endOfMonth(),
                            'year' => $date->year,
                            'description' => 'Gaji ' . $staffName,
                            'payment_method' => 'bank_transfer',
                            'status' => $date->isBefore(Carbon::now()) ? 'completed' : 'planned',
                            'is_recurring' => true,
                            'recurring_frequency' => 'monthly',
                            'recurring_end_date' => Carbon::now()->addYear(),
                            'notes' => 'Gaji bulanan ' . $staffName,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                // Batch insert gaji records
                if (count($gajiRecords) > 0) {
                    DB::table('financial_simulations')->insert($gajiRecords);
                }
            }
        }
    }
}

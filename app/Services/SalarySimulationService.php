<?php

namespace App\Services;

use App\Models\TeamStructure;
use App\Models\ManagementFinancial\FinancialCategory;
use App\Models\ManagementFinancial\FinancialSimulation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SalarySimulationService
{
    /**
     * Generate atau update salary simulation untuk bulan tertentu
     *
     * @param int $userId
     * @param int $businessBackgroundId
     * @param int $month (1-12)
     * @param int $year
     * @return array
     */
    public function generateMonthlySalary($userId, $businessBackgroundId, $month, $year)
    {
        try {
            DB::beginTransaction();

            // 1. Get active team members dengan salary > 0
            $teamMembers = TeamStructure::where('user_id', $userId)
                ->where('business_background_id', $businessBackgroundId)
                ->where('status', 'active')
                ->where('salary', '>', 0)
                ->get();

            if ($teamMembers->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'Tidak ada karyawan aktif dengan gaji yang perlu di-generate.',
                    'data' => null
                ];
            }

            // 2. Calculate total salary
            $totalSalary = $teamMembers->sum('salary');

            // 3. Get atau create kategori "Gaji Karyawan"
            $salaryCategory = FinancialCategory::firstOrCreate(
                [
                    'user_id' => $userId,
                    'business_background_id' => $businessBackgroundId,
                    'name' => 'Gaji Karyawan',
                    'type' => 'expense'
                ],
                [
                    'color' => '#F87171',
                    'status' => 'actual',
                    'description' => 'Pengeluaran untuk gaji dan tunjangan karyawan',
                    'category_subtype' => 'operating_expense'
                ]
            );

            // 4. Tanggal simulasi (tanggal 1 bulan tersebut)
            $simulationDate = Carbon::createFromDate($year, $month, 1);

            // 5. Check apakah sudah ada simulation untuk bulan ini
            $existingSimulation = FinancialSimulation::where('user_id', $userId)
                ->where('business_background_id', $businessBackgroundId)
                ->where('financial_category_id', $salaryCategory->id)
                ->whereYear('simulation_date', $year)
                ->whereMonth('simulation_date', $month)
                ->first();

            // 6. Prepare breakdown detail untuk notes
            $breakdown = $teamMembers->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->member_name,
                    'position' => $member->position,
                    'salary' => (float) $member->salary
                ];
            })->toArray();

            $notesData = [
                'source' => 'team_structure',
                'generated_at' => now()->toDateTimeString(),
                'total_members' => $teamMembers->count(),
                'breakdown' => $breakdown
            ];

            $description = sprintf(
                'Gaji Karyawan Bulan %s %d (%d orang)',
                $simulationDate->format('F'),
                $year,
                $teamMembers->count()
            );

            // 7. Create atau update simulation
            if ($existingSimulation) {
                // Update existing
                $existingSimulation->update([
                    'amount' => $totalSalary,
                    'description' => $description,
                    'notes' => json_encode($notesData),
                ]);

                $simulation = $existingSimulation;
                $action = 'updated';
            } else {
                // Create new
                $simulation = FinancialSimulation::create([
                    'user_id' => $userId,
                    'business_background_id' => $businessBackgroundId,
                    'financial_category_id' => $salaryCategory->id,
                    'simulation_code' => $this->generateSimulationCode(),
                    'type' => 'expense',
                    'amount' => $totalSalary,
                    'simulation_date' => $simulationDate,
                    'year' => $year,
                    'description' => $description,
                    'payment_method' => 'bank_transfer',
                    'status' => 'planned',
                    'is_recurring' => false,
                    'notes' => json_encode($notesData)
                ]);

                $action = 'created';
            }

            DB::commit();

            return [
                'success' => true,
                'message' => $action === 'created'
                    ? 'Gaji karyawan berhasil di-generate.'
                    : 'Data gaji karyawan berhasil diperbarui.',
                'action' => $action,
                'data' => [
                    'simulation' => $simulation,
                    'total_salary' => $totalSalary,
                    'total_members' => $teamMembers->count(),
                    'breakdown' => $breakdown,
                    'month' => $month,
                    'year' => $year,
                    'month_name' => $simulationDate->format('F Y')
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Gagal generate salary: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Check apakah sudah ada simulation untuk bulan tertentu
     *
     * @param int $userId
     * @param int $businessBackgroundId
     * @param int $month
     * @param int $year
     * @return array|null
     */
    public function checkExistingSalary($userId, $businessBackgroundId, $month, $year)
    {
        $salaryCategory = FinancialCategory::where('user_id', $userId)
            ->where('business_background_id', $businessBackgroundId)
            ->where('name', 'Gaji Karyawan')
            ->where('type', 'expense')
            ->first();

        if (!$salaryCategory) {
            return null;
        }

        $existingSimulation = FinancialSimulation::where('user_id', $userId)
            ->where('business_background_id', $businessBackgroundId)
            ->where('financial_category_id', $salaryCategory->id)
            ->whereYear('simulation_date', $year)
            ->whereMonth('simulation_date', $month)
            ->first();

        if (!$existingSimulation) {
            return null;
        }

        return [
            'exists' => true,
            'simulation' => $existingSimulation,
            'amount' => $existingSimulation->amount,
            'description' => $existingSimulation->description,
            'notes' => json_decode($existingSimulation->notes, true)
        ];
    }

    /**
     * Get salary summary untuk preview
     *
     * @param int $userId
     * @param int $businessBackgroundId
     * @return array
     */
    public function getSalarySummary($userId, $businessBackgroundId)
    {
        try {
            $teamMembers = TeamStructure::where('user_id', $userId)
                ->where('business_background_id', $businessBackgroundId)
                ->where('status', 'active')
                ->get();

            // Filter members with salary > 0 and calculate total
            $membersWithSalary = $teamMembers->filter(function ($member) {
                return $member->salary && $member->salary > 0;
            });

            $totalSalary = $membersWithSalary->sum(function ($member) {
                return (float) $member->salary;
            });

            return [
                'total_members' => $membersWithSalary->count(),
                'total_salary' => $totalSalary,
                'members' => $membersWithSalary->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'name' => $member->member_name,
                        'position' => $member->position,
                        'salary' => (float) ($member->salary ?? 0),
                        'category' => $member->team_category
                    ];
                })->values()->toArray()
            ];
        } catch (\Exception $e) {
            Log::error('Error in getSalarySummary: ' . $e->getMessage(), [
                'user_id' => $userId,
                'business_background_id' => $businessBackgroundId,
                'trace' => $e->getTraceAsString()
            ]);

            // Return empty summary instead of throwing
            return [
                'total_members' => 0,
                'total_salary' => 0,
                'members' => []
            ];
        }
    }

    /**
     * Generate unique simulation code
     *
     * @return string
     */
    private function generateSimulationCode()
    {
        $prefix = 'SLR';
        $timestamp = now()->format('ymd');
        $random = strtoupper(Str::random(4));

        return "{$prefix}-{$timestamp}-{$random}";
    }
}

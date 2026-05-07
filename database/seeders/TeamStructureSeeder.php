<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TeamStructure;

class TeamStructureSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // ============================================
            // LEVEL 1: OWNER
            // ============================================
            [
                'user_id' => 1,
                'business_background_id' => 1,
                'operational_plan_id' => 1,
                'team_category' => 'Owner',
                'member_name' => 'Rizky Pratama',
                'position' => 'Founder & CEO',
                'experience' => 'Berpengalaman 10 tahun dalam manajemen bisnis dan pengembangan strategi perusahaan.',
                'photo' => null,
                'sort_order' => 1,
                'status' => 'active',
                'salary' => 4500000,
            ],

            // ============================================
            // LEVEL 2: MANAGER
            // ============================================
            [
                'user_id' => 1,
                'business_background_id' => 1,
                'operational_plan_id' => 1,
                'team_category' => 'Manager',
                'member_name' => 'Andi Setiawan',
                'position' => 'Operations Manager',
                'experience' => '5 tahun pengalaman mengelola operasional harian dan supervisi tim.',
                'photo' => null,
                'sort_order' => 2,
                'status' => 'active',
                'salary' => 3800000,
            ],
            [
                'user_id' => 1,
                'business_background_id' => 1,
                'operational_plan_id' => 1,
                'team_category' => 'Manager',
                'member_name' => 'Siti Nurhaliza',
                'position' => 'Marketing Manager',
                'experience' => '6 tahun pengalaman dalam strategi pemasaran digital dan brand management.',
                'photo' => null,
                'sort_order' => 3,
                'status' => 'active',
                'salary' => 3500000,
            ],
            [
                'user_id' => 1,
                'business_background_id' => 1,
                'operational_plan_id' => 1,
                'team_category' => 'Manager',
                'member_name' => 'Bagas Nurrahman',
                'position' => 'Technical Manager',
                'experience' => '7 tahun pengalaman dalam pengembangan sistem dan maintenance server.',
                'photo' => null,
                'sort_order' => 4,
                'status' => 'active',
                'salary' => 3600000,
            ],

            // ============================================
            // LEVEL 3: SUPERVISOR
            // ============================================
            [
                'user_id' => 1,
                'business_background_id' => 1,
                'operational_plan_id' => 1,
                'team_category' => 'Supervisor',
                'member_name' => 'Rina Oktaviani',
                'position' => 'Customer Service Supervisor',
                'experience' => '4 tahun pengalaman dalam layanan pelanggan dan handling komplain.',
                'photo' => null,
                'sort_order' => 5,
                'status' => 'active',
                'salary' => 2700000,
            ],
            [
                'user_id' => 1,
                'business_background_id' => 1,
                'operational_plan_id' => 1,
                'team_category' => 'Supervisor',
                'member_name' => 'Ahmad Hidayat',
                'position' => 'Sales Supervisor',
                'experience' => '5 tahun pengalaman dalam penjualan dan manajemen target penjualan.',
                'photo' => null,
                'sort_order' => 6,
                'status' => 'active',
                'salary' => 2500000,
            ],

            // ============================================
            // LEVEL 4: STAFF
            // ============================================
            [
                'user_id' => 1,
                'business_background_id' => 1,
                'operational_plan_id' => 1,
                'team_category' => 'Staff',
                'member_name' => 'Dina Cahyani',
                'position' => 'Customer Support Staff',
                'experience' => '2 tahun pengalaman dalam customer service dan handling support tickets.',
                'photo' => null,
                'sort_order' => 7,
                'status' => 'active',
                'salary' => 2800000,
            ],
            [
                'user_id' => 1,
                'business_background_id' => 1,
                'operational_plan_id' => 1,
                'team_category' => 'Staff',
                'member_name' => 'Budi Santoso',
                'position' => 'Customer Support Staff',
                'experience' => '2 tahun pengalaman dalam customer support dan problem solving.',
                'photo' => null,
                'sort_order' => 8,
                'status' => 'active',
                'salary' => 2800000,
            ],
            [
                'user_id' => 1,
                'business_background_id' => 1,
                'operational_plan_id' => 1,
                'team_category' => 'Staff',
                'member_name' => 'Hendra Wijaya',
                'position' => 'Sales Staff',
                'experience' => '3 tahun pengalaman dalam penjualan dan prospecting.',
                'photo' => null,
                'sort_order' => 9,
                'status' => 'active',
                'salary' => 3000000,
            ],
            [
                'user_id' => 1,
                'business_background_id' => 1,
                'operational_plan_id' => 1,
                'team_category' => 'Staff',
                'member_name' => 'Lina Mardiana',
                'position' => 'Marketing Staff',
                'experience' => '2 tahun pengalaman dalam content creation dan social media management.',
                'photo' => null,
                'sort_order' => 10,
                'status' => 'active',
                'salary' => 3500000,
            ],
            [
                'user_id' => 1,
                'business_background_id' => 1,
                'operational_plan_id' => 1,
                'team_category' => 'Staff',
                'member_name' => 'Ricky Gunawan',
                'position' => 'Backend Developer',
                'experience' => '4 tahun pengalaman dalam pengembangan API dan database design.',
                'photo' => null,
                'sort_order' => 11,
                'status' => 'active',
                'salary' => 3200000,
            ],
            [
                'user_id' => 1,
                'business_background_id' => 1,
                'operational_plan_id' => 1,
                'team_category' => 'Staff',
                'member_name' => 'Eka Putri',
                'position' => 'Frontend Developer',
                'experience' => '3 tahun pengalaman dalam React development dan UI/UX implementation.',
                'photo' => null,
                'sort_order' => 12,
                'status' => 'active',
                'salary' => 3000000,
            ],
        ];

        foreach ($data as $item) {
            TeamStructure::create($item);
        }
    }
}

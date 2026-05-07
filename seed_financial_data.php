<?php

$user = App\Models\User::where('username', 'demouser')->first();
$business = App\Models\BusinessBackground::where('user_id', $user->id)->first();

if (!$business) {
    echo "Business Background not found!\n";
    exit;
}

echo "Memulai seeding data Manajemen Keuangan untuk business: {$business->name}\n";

// 1. Financial Categories (Capex, Opex, COGS)
$categories = [
    // CAPEX
    ['type' => 'capex', 'name' => 'Renovasi Kantor', 'amount' => 150000000, 'category_subtype' => 'fixed_asset'],
    ['type' => 'capex', 'name' => 'Peralatan IT & Server', 'amount' => 200000000, 'category_subtype' => 'fixed_asset'],
    ['type' => 'capex', 'name' => 'Lisensi Software Tahunan', 'amount' => 50000000, 'category_subtype' => 'intangible_asset'],
    ['type' => 'capex', 'name' => 'Kendaraan Operasional', 'amount' => 300000000, 'category_subtype' => 'fixed_asset'],
    
    // OPEX (Fixed & Variable)
    ['type' => 'opex', 'name' => 'Gaji Karyawan', 'amount' => 120000000, 'category_subtype' => 'fixed'],
    ['type' => 'opex', 'name' => 'Sewa Kantor Bulanan', 'amount' => 25000000, 'category_subtype' => 'fixed'],
    ['type' => 'opex', 'name' => 'Biaya Marketing', 'amount' => 30000000, 'category_subtype' => 'variable'],
    ['type' => 'opex', 'name' => 'Listrik & Internet', 'amount' => 5000000, 'category_subtype' => 'variable'],
    
    // COGS
    ['type' => 'cogs', 'name' => 'Biaya Server AWS', 'amount' => 15000000, 'category_subtype' => 'direct_material'],
    ['type' => 'cogs', 'name' => 'Lisensi Pihak Ketiga API', 'amount' => 5000000, 'category_subtype' => 'direct_material'],
];

foreach ($categories as $cat) {
    App\Models\ManagementFinancial\FinancialCategory::create([
        'user_id' => $user->id,
        'business_background_id' => $business->id,
        'type' => $cat['type'],
        'name' => $cat['name'],
        'amount' => $cat['amount'],
        'category_subtype' => $cat['category_subtype'],
        'description' => 'Contoh data deskripsi ' . $cat['name']
    ]);
}
echo "✓ Financial Categories (Capex, Opex, COGS) seeded.\n";


// 2. Financial Simulations (Income/Expense per month/year)
$months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

for ($i = 0; $i < 12; $i++) {
    // Income
    App\Models\ManagementFinancial\FinancialSimulation::create([
        'user_id' => $user->id,
        'business_background_id' => $business->id,
        'type' => 'income',
        'category' => 'Pendapatan Berlangganan',
        'amount' => 250000000 + ($i * 10000000), // Growth tiap bulan
        'month' => $months[$i],
        'year' => date('Y'),
        'description' => 'Pendapatan langganan software ERP bulan ' . $months[$i]
    ]);
    
    App\Models\ManagementFinancial\FinancialSimulation::create([
        'user_id' => $user->id,
        'business_background_id' => $business->id,
        'type' => 'income',
        'category' => 'Jasa Implementasi/Setup',
        'amount' => 50000000 + ($i * 2000000), 
        'month' => $months[$i],
        'year' => date('Y'),
        'description' => 'Jasa setup awal ERP bulan ' . $months[$i]
    ]);

    // Expense
    App\Models\ManagementFinancial\FinancialSimulation::create([
        'user_id' => $user->id,
        'business_background_id' => $business->id,
        'type' => 'expense',
        'category' => 'Biaya Operasional',
        'amount' => 180000000, // Fixed
        'month' => $months[$i],
        'year' => date('Y'),
        'description' => 'Total biaya operasional bulanan'
    ]);
    
    App\Models\ManagementFinancial\FinancialSimulation::create([
        'user_id' => $user->id,
        'business_background_id' => $business->id,
        'type' => 'expense',
        'category' => 'Biaya Pemasaran',
        'amount' => 30000000 + ($i * 1000000),
        'month' => $months[$i],
        'year' => date('Y'),
        'description' => 'Biaya iklan dan promosi bulanan'
    ]);
}
echo "✓ Financial Simulations (Income & Expense) seeded.\n";


// 3. Financial Projections
for ($year = 1; $year <= 5; $year++) {
    // Base revenue 3.6M, growth 30% per year
    $projectedRevenue = 3600000000 * pow(1.3, $year - 1);
    
    // Base expense 2.5M, growth 10% per year
    $projectedExpense = 2520000000 * pow(1.1, $year - 1);
    
    $netProfit = $projectedRevenue - $projectedExpense;
    $cashBalance = 1000000000 + ($netProfit * 0.8); // Asumsi 80% profit jadi cash

    App\Models\ManagementFinancial\FinancialProjection::create([
        'user_id' => $user->id,
        'business_background_id' => $business->id,
        'year' => $year,
        'projected_revenue' => $projectedRevenue,
        'projected_expense' => $projectedExpense,
        'net_profit' => $netProfit,
        'cash_balance' => $cashBalance,
        'assumptions' => "Asumsi pertumbuhan pendapatan 30% dan pengeluaran 10% di Tahun ke-{$year}"
    ]);
}
echo "✓ Financial Projections (Year 1-5) seeded.\n";


// 4. Financial Summary
$capexTotal = 700000000;
$opexTotal = 180000000;
$cogsTotal = 20000000;
$initialInvestment = 2000000000;

App\Models\ManagementFinancial\FinancialSummary::create([
    'user_id' => $user->id,
    'business_background_id' => $business->id,
    'total_capex' => $capexTotal,
    'total_opex' => $opexTotal,
    'total_cogs' => $cogsTotal,
    'initial_investment_required' => $capexTotal + ($opexTotal * 6), // Capex + 6 bulan Opex runway
    'expected_roi_months' => 18,
    'break_even_point_amount' => 250000000,
    'gross_profit_margin' => 85.5,
    'net_profit_margin' => 35.2,
    
    // Detailed fields based on migration
    'cash_on_hand' => 1500000000,
    'accounts_receivable' => 300000000,
    'inventory_value' => 0, // Software company
    'total_current_assets' => 1800000000,
    
    'fixed_assets_value' => 700000000, // Dari capex
    'intangible_assets' => 500000000, // IP / Software value
    'total_non_current_assets' => 1200000000,
    
    'total_assets' => 3000000000,
    
    'accounts_payable' => 150000000,
    'short_term_loans' => 0,
    'total_current_liabilities' => 150000000,
    
    'long_term_loans' => 500000000,
    'total_non_current_liabilities' => 500000000,
    
    'total_liabilities' => 650000000,
    
    'owners_equity' => $initialInvestment,
    'retained_earnings' => 350000000,
    'total_equity' => 2350000000
]);
echo "✓ Financial Summary seeded.\n";

echo "Semua data Manajemen Keuangan berhasi digenerate!\n";

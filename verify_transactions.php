<?php

use App\Models\User;
use App\Models\Singapay\PremiumPdf;
use App\Models\Affiliate\AffiliateCommission;
use App\Models\Affiliate\AffiliateWithdrawal;
use App\Services\Singapay\PdfPaymentService;
use App\Services\AffiliateCommissionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

// FORCE MOCK MODE FOR TESTING
Config::set('singapay.mode', 'mock');
Config::set('singapay.mock.auto_approve_delay', 1);

echo "\n=============================================\n";
echo "🚀 STARTING MOCK TRANSACTION VERIFICATION 🚀\n";
echo "=============================================\n\n";

$user = User::find(1);
if (!$user) {
    die("❌ Error: User ID 1 not found.\n");
}
echo "👤 User: {$user->name} ({$user->email})\n\n";

// --- TEST 1: Langganan Pro PDF (Mock) ---
echo "--- [TEST 1] Pro PDF Subscription (Mock) ---\n";

// 1. Ensure Package Exists
$package = PremiumPdf::first();
if (!$package) {
    $package = PremiumPdf::create([
        'package_type' => 'monthly',
        'name' => 'Monthly Pro',
        'price' => 50000,
        'duration_days' => 30,
        'description' => 'Test Package',
        'is_active' => true
    ]);
    echo "📦 Created Mock Package: {$package->name}\n";
} else {
    echo "📦 Using Package: {$package->name}\n";
}

// 2. Create Purchase
$pdfService = app(PdfPaymentService::class);
echo "🔄 Creating Purchase (Virtual Account - BCA)...\n";
$result = $pdfService->createPurchase($user, $package->id, 'virtual_account', 'BCA');

if (!$result['success']) {
    echo "❌ Purchase Failed: " . $result['message'] . "\n";
    if (str_contains($result['message'], 'already have an active subscription')) {
        echo "ℹ️  User already has active subscription. Clearing it for test...\n";
        $user->update(['pdf_access_active' => false, 'pdf_access_expires_at' => null]);
        $user->pdfPurchases()->update(['status' => 'failed']); // Invalidate distinct purchases
        echo "🔄 Retrying purchase...\n";
        $result = $pdfService->createPurchase($user, $package->id, 'virtual_account', 'BCA');
    }
}

if ($result['success']) {
    $purchase = $result['purchase'];
    $payment = $result['payment'];
    echo "✅ Purchase Created!\n";
    echo "   Order ID: {$purchase['transaction_code']}\n";
    echo "   VA Number: " . ($payment['va_number'] ?? 'N/A') . " (Mock)\n";
    echo "   Status: {$purchase['status']}\n";

    // 3. Simulate Waiting / Check Status
    echo "⏳ Waiting 2 seconds for Mock Auto-Approval...\n";
    sleep(2);

    $statusResult = $pdfService->checkPaymentStatus($purchase['transaction_code']);

    if ($statusResult['success'] && $statusResult['paid']) {
        echo "🎉 PAYMENT SUCCESS! (Auto-Approved)\n";
        echo "   Status: {$statusResult['status']}\n";

        $user->refresh();
        echo "   User Access Active: " . ($user->pdf_access_active ? "YES ✅" : "NO ❌") . "\n";
        echo "   Expires At: {$user->pdf_access_expires_at}\n";
    } else {
        echo "❌ Payment Verification Failed or Pending: " . ($statusResult['status'] ?? 'unknown') . "\n";
        print_r($statusResult);
    }
} else {
    echo "❌ Failed to create purchase: {$result['message']}\n";
}

echo "\n";

// --- TEST 2: Withdraw Komisi (Mock) ---
echo "--- [TEST 2] Affiliate Withdraw (Mock) ---\n";

// 1. Ensure Balance
$commService = app(AffiliateCommissionService::class);
$currentBalance = $commService->getWithdrawableBalance($user->id);

echo "💰 Current Balance: Rp " . number_format($currentBalance) . "\n";

if ($currentBalance < 50000) {
    echo "➕ Adding Mock Commission of Rp 100,000...\n";
    // Add dummy commission
    AffiliateCommission::create([
        'user_id' => $user->id,
        'amount' => 1000000, // Sales amount
        'commission_amount' => 170000, // 17%
        'status' => 'approved',
        'type' => 'sale',
        'description' => 'Mock Sale for Testing',
    ]);
    $currentBalance += 170000;
    echo "💰 New Balance: Rp " . number_format($currentBalance) . "\n";
}

// 2. Request Withdraw
$withdrawAmount = 50000;
echo "🔄 Requesting Withdraw: Rp " . number_format($withdrawAmount) . "...\n";

// We need to call Controller logic or duplicate it, calling Service directly is cleaner but Controller handles Rate Limiting.
// Let's call Controller logical part (Service + Rate Limit check manually if needed, but here we just test system capability)
// We'll call service directly to test the core logic.

$withdrawalController = app(\App\Http\Controllers\Affiliate\AffiliateWithdrawalController::class);
// Simulate Request
$request = new \Illuminate\Http\Request();
$request->replace([
    'amount' => $withdrawAmount,
    'bank_name' => 'BCA',
    'bank_code' => 'BCA',
    'account_number' => '123456789',
    'account_name' => 'Test User',
    'notes' => 'Mock Withdraw Test'
]);
$request->setUserResolver(function () use ($user) {
    return $user;
});

// Call withdraw method directly? simpler to use service directly for testing logic
try {
    // Check limit manually just to be sure we don't hit it during test
    $withdrawalsToday = AffiliateWithdrawal::where('user_id', $user->id)
        ->where('created_at', '>=', now()->subDay())
        ->count();

    if ($withdrawalsToday >= 5) {
        echo "⚠️ Limit hit ($withdrawalsToday/5), resetting limit for test...\n";
        AffiliateWithdrawal::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDay())
            ->delete(); // Dangerous but ok for testing script
    }

    $result = $commService->withdraw($user->id, $withdrawAmount, [
        'bank_name' => 'BCA',
        'account_number' => '123456789',
        'account_name' => 'Test User'
    ]);

    echo "✅ Withdraw Result: {$result['message']}\n";
    $withdrawalId = $result['data']['id'];

    // 3. Verify Database
    $withdrawal = AffiliateWithdrawal::find($withdrawalId);
    echo "   Status in DB: {$withdrawal->status} (Expected: processed)\n";
    echo "   Ref Code: {$withdrawal->singapay_reference}\n";

    if ($withdrawal->status === 'processed') {
        echo "🎉 WITHDRAW SUCCESS! (Mock Processed)\n";
    } else {
        echo "❌ Withdraw status not processed.\n";
    }
} catch (\Exception $e) {
    echo "❌ Withdraw Failed: " . $e->getMessage() . "\n";
}

echo "\n=============================================\n";
echo "✅ VERIFICATION COMPLETE\n";
echo "=============================================\n";

<?php
/**
 * Simple script to test packages API without authentication
 * Run: php test-packages-api.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing Packages API ===\n\n";

// Test 1: Check if packages exist in database
echo "1. Checking database...\n";
$packages = \App\Models\Singapay\PremiumPdf::all();
echo "   Found: " . $packages->count() . " packages\n\n";

foreach ($packages as $pkg) {
    echo "   - {$pkg->name}: {$pkg->formatted_price} ({$pkg->duration_text})\n";
    echo "     Features: " . implode(', ', $pkg->features ?? []) . "\n";
    echo "     Active: " . ($pkg->is_active ? 'Yes' : 'No') . "\n\n";
}

// Test 2: Test service method
echo "2. Testing PdfPaymentService...\n";
$service = new \App\Services\Singapay\PdfPaymentService(
    new \App\Services\Singapay\SingapayApiService(),
    new \App\Services\Singapay\VirtualAccountService(new \App\Services\Singapay\SingapayApiService()),
    new \App\Services\Singapay\QrisService(new \App\Services\Singapay\SingapayApiService())
);

$result = $service->getAvailablePackages();

if ($result['success']) {
    echo "   ✓ Service returned successfully\n";
    echo "   Packages in response: " . count($result['packages']) . "\n";
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "   ✗ Service failed\n";
}

echo "\n=== Test Complete ===\n";

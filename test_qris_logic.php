<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Singapay\SingapayApiService;
use App\Services\Singapay\QrisService;

// Mock SingapayApiService
$apiService = new SingapayApiService();

// Initialize QrisService
$qrisService = new QrisService($apiService);

// Sample QR String from user log
$qrString = '00020101021226640017ID.CO.DANAMON.WWW0118936000110000245777021010448706130303UKE51440014ID.CO.QRIS.WWW0215ID20243090024650303UKE625605023306023307112919416152508023399190002000109402880213530336054062000006009TANGERANG520489995908SINGAPAY5802ID61051533163049F15';

// Test Generation
$imageUrl = $qrisService->getQrisImageUrl($qrString);

echo PHP_EOL . "--- RESULT ---" . PHP_EOL;
echo "Input Length: " . strlen($qrString) . PHP_EOL;
echo "Output URL: " . $imageUrl . PHP_EOL;

if (str_starts_with($imageUrl, 'data:image')) {
    echo "TYPE: Base64 Image (Logic Salah - Ini bukan valid base64)" . PHP_EOL;
} elseif (str_starts_with($imageUrl, 'http')) {
    echo "TYPE: External URL (Success - Menggunakan QR Generator)" . PHP_EOL;
} else {
    echo "TYPE: Unknown" . PHP_EOL;
}
echo "--------------" . PHP_EOL;

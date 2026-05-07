<?php

namespace App\Services\Singapay;

use App\Models\Singapay\PaymentTransaction;
use App\Models\Singapay\PdfPurchase;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class VirtualAccountService
{
    protected $apiService;

    public function __construct(SingapayApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Create Virtual Account for payment
     * 🔧 FIXED: Type casting untuk expiry calculation
     */
    public function createVirtualAccount(PdfPurchase $purchase, string $bankCode): array
    {
        try {
            // Amount validation sesuai dokumentasi Singapay (10K - 100M IDR)
            $minAmount = config('singapay.virtual_account.min_amount', 10000);
            $maxAmount = config('singapay.virtual_account.max_amount', 100000000);

            if ($purchase->amount_paid < $minAmount || $purchase->amount_paid > $maxAmount) {
                Log::warning('[VA Service] Amount out of range', [
                    'amount' => $purchase->amount_paid,
                    'min' => $minAmount,
                    'max' => $maxAmount,
                    'purchase_id' => $purchase->id,
                ]);

                return [
                    'success' => false,
                    'message' => sprintf(
                        'Amount must be between Rp %s and Rp %s',
                        number_format($minAmount, 0, ',', '.'),
                        number_format($maxAmount, 0, ',', '.')
                    ),
                    'error_code' => 'AMOUNT_OUT_OF_RANGE',
                ];
            }

            $expiryHours = (int) config('singapay.virtual_account.expiry_hours', 24);

            Log::info('[VA Service] Config values', [
                'expiry_hours_raw' => config('singapay.virtual_account.expiry_hours'),
                'expiry_hours_casted' => $expiryHours,
                'type' => gettype($expiryHours),
            ]);

            $expiredAt = Carbon::now()->addHours($expiryHours);
            $accountId = $this->apiService->getMerchantAccountId();

            // Prepare VA data sesuai dokumentasi Singapay
            $data = [
                'bank_code' => strtoupper($bankCode),
                'amount' => $purchase->amount_paid,
                'name' => $this->sanitizeName($purchase->user->name ?? 'Grapadi Strategix User'),
                'kind' => config('singapay.virtual_account.kind', 'temporary'),
                'currency' => 'IDR',
            ];

            // Add fields for temporary VA
            if ($data['kind'] === 'temporary') {
                // Convert to milliseconds timestamp
                $data['expired_at'] = $expiredAt->timestamp * 1000;
                $data['max_usage'] = (int) config('singapay.virtual_account.max_usage', 1);
            }

            Log::info('[VA Service] Creating Virtual Account', [
                'purchase_id' => $purchase->id,
                'bank_code' => $bankCode,
                'amount' => $purchase->amount_paid,
                'account_id' => $accountId,
                'expired_at' => $expiredAt->toDateTimeString(),
            ]);

            // Send request to SingaPay API
            $response = $this->apiService->sendRequest(
                "/api/v1.0/virtual-accounts/{$accountId}",
                $data,
                'POST'
            );

            if (!$response['success']) {
                Log::error('[VA Service] Failed to create VA', [
                    'response' => $response,
                    'purchase_id' => $purchase->id,
                ]);

                return [
                    'success' => false,
                    'message' => $response['message'] ?? 'Failed to create Virtual Account',
                    'error_code' => $response['error_code'] ?? null,
                ];
            }

            // Singapay response structure: { data: { data: { ... } } }
            // Extract the actual VA data from nested structure
            $vaData = $response['data']['data'] ?? $response['data'];

            // Log full response structure untuk debugging
            Log::info('[VA Service] VA API Response Structure', [
                'response_keys' => array_keys($vaData),
                'full_response' => $vaData,
                'purchase_id' => $purchase->id,
            ]);

            // Singapay menggunakan key "number" bukan "va_number"
            $vaNumber = $vaData['number'] ?? $vaData['va_number'] ?? $vaData['vaNumber'] ?? $vaData['account_number'] ?? null;

            if (!$vaNumber) {
                Log::error('[VA Service] VA number not found in response', [
                    'response_structure' => array_keys($vaData),
                    'full_response' => $vaData,
                    'purchase_id' => $purchase->id,
                ]);

                return [
                    'success' => false,
                    'message' => 'VA number not found in Singapay response',
                    'error_code' => 'VA_NUMBER_MISSING',
                    'debug_info' => [
                        'response_keys' => array_keys($vaData),
                    ],
                ];
            }

            Log::info('[VA Service] VA created successfully', [
                'va_number' => $vaNumber,
                'va_id' => $vaData['id'] ?? null,
                'purchase_id' => $purchase->id,
            ]);

            // Create payment transaction record
            $transaction = PaymentTransaction::create([
                'pdf_purchase_id' => $purchase->id,
                'transaction_code' => $purchase->transaction_code,
                'reference_no' => $vaData['id'] ?? $vaData['reff_no'] ?? $vaData['reference_no'] ?? PaymentTransaction::generateReferenceNo(),
                'payment_method' => 'virtual_account',
                'bank_code' => $bankCode,
                'va_number' => $vaNumber,
                'amount' => $purchase->amount_paid,
                'currency' => 'IDR',
                'status' => 'pending',
                'mode' => $this->apiService->getMode(),
                'expired_at' => $expiredAt,
                'singapay_request' => $data,
                'singapay_response' => $vaData,
            ]);

            return [
                'success' => true,
                'message' => 'Virtual Account created successfully',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'transaction_code' => $transaction->transaction_code,
                    'bank_code' => $bankCode,
                    'bank_name' => $this->getBankName($bankCode),
                    'va_number' => $vaNumber,
                    'amount' => $purchase->amount_paid,
                    'formatted_amount' => $transaction->formatted_amount,
                    'expired_at' => $expiredAt->toIso8601String(),
                    'expired_at_formatted' => $expiredAt->format('d M Y H:i'),
                    'payment_instructions' => $this->getPaymentInstructions($bankCode, $vaNumber),
                ],
            ];
        } catch (\Exception $e) {
            Log::error('[VA Service] Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'purchase_id' => $purchase->id,
            ]);

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Sanitize name for VA (remove special characters)
     */
    protected function sanitizeName(string $name): string
    {
        // Remove special characters, keep only alphanumeric and spaces
        $sanitized = preg_replace('/[^A-Za-z0-9\s]/', '', $name);
        // Limit to 50 characters
        return substr($sanitized, 0, 50);
    }

    /**
     * Check Virtual Account payment status
     */
    public function checkPaymentStatus(PaymentTransaction $transaction): array
    {
        if ($this->apiService->isMockMode()) {
            return $this->checkMockPaymentStatus($transaction);
        }

        try {
            $accountId = $this->apiService->getMerchantAccountId();

            Log::info('[VA Service] Checking payment status', [
                'transaction_id' => $transaction->id,
                'va_number' => $transaction->va_number,
            ]);

            $response = $this->apiService->sendRequest(
                "/api/v1.0/va-transactions/{$accountId}",
                [],
                'GET'
            );

            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => $response['message'] ?? 'Failed to check payment status',
                ];
            }

            // Find transaction by VA number
            $transactions = $response['data'] ?? [];
            $vaTransaction = collect($transactions)->firstWhere('va_number', $transaction->va_number);

            if (!$vaTransaction) {
                return [
                    'success' => true,
                    'status' => 'pending',
                    'paid' => false,
                    'message' => 'Transaction not found in VA transactions list',
                ];
            }

            $status = $vaTransaction['status'] ?? 'pending';

            return [
                'success' => true,
                'status' => $status,
                'paid' => $status === 'paid',
                'data' => $vaTransaction,
            ];
        } catch (\Exception $e) {
            Log::error('[VA Service] Check status exception', [
                'message' => $e->getMessage(),
                'transaction_id' => $transaction->id,
            ]);

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check mock payment status (simulate auto-approval)
     */
    protected function checkMockPaymentStatus(PaymentTransaction $transaction): array
    {
        $autoApprove = (int) config('singapay.mock.auto_approve_delay', 0);

        if ($autoApprove > 0) {
            $createdTime = $transaction->created_at->timestamp;
            $currentTime = now()->timestamp;
            $elapsed = $currentTime - $createdTime;

            if ($elapsed >= $autoApprove) {
                $successRate = (int) config('singapay.mock.success_rate', 100);
                $isPaid = rand(1, 100) <= $successRate;

                Log::info('[VA Service] Mock auto-approval', [
                    'transaction_id' => $transaction->id,
                    'elapsed' => $elapsed,
                    'paid' => $isPaid,
                ]);

                return [
                    'success' => true,
                    'status' => $isPaid ? 'paid' : 'failed',
                    'paid' => $isPaid,
                    'data' => [
                        'status' => $isPaid ? 'paid' : 'failed',
                        'paid_at' => $isPaid ? now()->toIso8601String() : null,
                        'mock_mode' => true,
                        'elapsed_seconds' => $elapsed,
                    ],
                ];
            }
        }

        return [
            'success' => true,
            'status' => 'pending',
            'paid' => false,
            'data' => [
                'status' => 'pending',
                'mock_mode' => true,
            ],
        ];
    }

    /**
     * Get bank name
     */
    protected function getBankName(string $bankCode): string
    {
        $banks = [
            'BRI' => 'Bank Rakyat Indonesia',
            'BNI' => 'Bank Negara Indonesia',
            'DANAMON' => 'Bank Danamon',
            'MAYBANK' => 'Maybank Indonesia',
        ];

        return $banks[strtoupper($bankCode)] ?? $bankCode;
    }

    /**
     * Get payment instructions
     */
    public function getPaymentInstructions(string $bankCode, string $vaNumber): array
    {
        $bankName = $this->getBankName($bankCode);

        $instructions = [
            'BRI' => [
                'ATM' => [
                    'Masukkan kartu ATM dan PIN Anda',
                    'Pilih menu "Transaksi Lain"',
                    'Pilih menu "Pembayaran"',
                    'Pilih menu "Lainnya"',
                    'Pilih menu "BRIVA"',
                    'Masukkan nomor Virtual Account: ' . $vaNumber,
                    'Periksa informasi pembayaran',
                    'Ikuti instruksi untuk menyelesaikan pembayaran',
                ],
                'Mobile Banking' => [
                    'Login ke aplikasi BRI Mobile',
                    'Pilih menu "Pembayaran"',
                    'Pilih menu "BRIVA"',
                    'Masukkan nomor Virtual Account: ' . $vaNumber,
                    'Masukkan nominal pembayaran',
                    'Masukkan PIN',
                    'Pembayaran selesai',
                ],
            ],
            'BNI' => [
                'ATM' => [
                    'Masukkan kartu ATM dan PIN Anda',
                    'Pilih menu "Menu Lainnya"',
                    'Pilih menu "Transfer"',
                    'Pilih menu "Rekening Tabungan"',
                    'Masukkan nomor Virtual Account: ' . $vaNumber,
                    'Masukkan nominal pembayaran',
                    'Konfirmasi pembayaran',
                ],
                'Mobile Banking' => [
                    'Login ke aplikasi BNI Mobile Banking',
                    'Pilih menu "Transfer"',
                    'Pilih menu "Virtual Account Billing"',
                    'Masukkan nomor Virtual Account: ' . $vaNumber,
                    'Masukkan nominal pembayaran',
                    'Konfirmasi dengan PIN',
                ],
            ],
            'DANAMON' => [
                'ATM/Mobile Banking' => [
                    'Login ke aplikasi D-Bank atau kunjungi ATM Danamon',
                    'Pilih menu "Pembayaran"',
                    'Pilih "Virtual Account"',
                    'Masukkan nomor Virtual Account: ' . $vaNumber,
                    'Periksa detail pembayaran',
                    'Konfirmasi pembayaran dengan PIN',
                ],
            ],
            'MAYBANK' => [
                'ATM/Mobile Banking' => [
                    'Login ke Maybank2u atau kunjungi ATM Maybank',
                    'Pilih menu "Transfer & Pay"',
                    'Pilih "JomPAY"',
                    'Masukkan nomor Virtual Account: ' . $vaNumber,
                    'Periksa detail pembayaran',
                    'Konfirmasi pembayaran',
                ],
            ],
        ];

        return $instructions[strtoupper($bankCode)] ?? [
            'ATM/Mobile Banking' => [
                'Pilih menu Pembayaran atau Transfer',
                'Masukkan nomor Virtual Account: ' . $vaNumber,
                'Periksa detail pembayaran',
                'Konfirmasi pembayaran',
            ],
        ];
    }

    /**
     * Get supported banks
     */
    public function getSupportedBanks(): array
    {
        $banks = config('singapay.virtual_account.banks', ['BRI', 'BNI', 'DANAMON', 'MAYBANK']);

        return collect($banks)->map(fn($code) => [
            'code' => $code,
            'name' => $this->getBankName($code),
        ])->toArray();
    }
}

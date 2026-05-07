<?php

namespace App\Services\Singapay;

use App\Models\Singapay\PremiumPdf;
use App\Models\Singapay\PdfPurchase;
use App\Models\Singapay\PaymentTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PdfPaymentService
{
    protected $apiService;
    protected $vaService;
    protected $qrisService;

    public function __construct(
        SingapayApiService $apiService,
        VirtualAccountService $vaService,
        QrisService $qrisService
    ) {
        $this->apiService = $apiService;
        $this->vaService = $vaService;
        $this->qrisService = $qrisService;
    }

    /**
     * Get available packages
     * 🔧 IMPROVED: Better data formatting
     */
    public function getAvailablePackages(): array
    {
        $packages = PremiumPdf::active()->ordered()->get();

        return [
            'success' => true,
            'data' => $packages->map(function ($package) {
                return [
                    'id' => $package->id,
                    'package_type' => $package->package_type,
                    'name' => $package->name,
                    'description' => $package->description,
                    'price' => $package->price,
                    'formatted_price' => $package->formatted_price,
                    'duration_days' => (int) $package->duration_days, // 🔧 Ensure integer
                    'duration_text' => $package->duration_text,
                    'features' => $package->features ?? [],
                ];
            })->toArray(),
        ];
    }

    /**
     * Check user Pro access status from database
     */
    public function checkAccess(User $user): array
    {
        $hasAccess = $user->pdf_access_active
            && $user->pdf_access_expires_at
            && $user->pdf_access_expires_at->isFuture();

        $remainingDays = null;
        if ($hasAccess && $user->pdf_access_expires_at) {
            $remainingDays = now()->diffInDays($user->pdf_access_expires_at, false);
            $remainingDays = max(0, (int)$remainingDays);
        }

        return [
            'success' => true,
            'has_access' => $hasAccess,
            'package' => $hasAccess ? $user->pdf_access_package : null,
            'expires_at' => $hasAccess ? $user->pdf_access_expires_at : null,
            'remaining_days' => $remainingDays,
        ];
    }

    /**
     * Create purchase and initiate payment
     * 🔧 IMPROVED: Better validation and error handling
     */
    public function createPurchase(User $user, int $packageId, string $paymentMethod, ?string $bankCode = null): array
    {
        DB::beginTransaction();

        try {
            // Get package with validation
            $package = PremiumPdf::active()->find($packageId);

            if (!$package) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Package not found or inactive',
                ];
            }

            // Validate duration_days
            $durationDays = (int) $package->duration_days;
            if ($durationDays <= 0) {
                DB::rollBack();
                Log::error('[PDF Payment] Invalid duration_days', [
                    'package_id' => $packageId,
                    'duration_days' => $package->duration_days,
                ]);
                return [
                    'success' => false,
                    'message' => 'Invalid package configuration',
                ];
            }

            // Check if user already has active subscription
            // Note: Consultation packages can be bought multiple times
            if ($package->package_type !== 'consultation') {
                $activePurchase = $user->pdfPurchases()
                    ->active()
                    ->where('package_type', '!=', 'consultation')
                    ->first();

                if ($activePurchase) {
                    DB::rollBack();
                    return [
                        'success' => false,
                        'message' => 'You already have an active subscription',
                        'current_subscription' => [
                            'package_type' => $activePurchase->package_type,
                            'expires_at' => $activePurchase->expires_at,
                            'remaining_days' => $activePurchase->remaining_days,
                        ],
                    ];
                }
            }

            // Validate payment method
            if ($paymentMethod === 'virtual_account' && !$bankCode) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Bank code is required for Virtual Account payment',
                ];
            }

            // Create purchase record
            $purchase = PdfPurchase::create([
                'user_id' => $user->id,
                'premium_pdf_id' => $package->id,
                'transaction_code' => PdfPurchase::generateTransactionCode(),
                'package_type' => $package->package_type,
                'amount_paid' => $package->price,
                'payment_method' => $paymentMethod,
                'status' => 'pending',
            ]);

            Log::info('[PDF Payment] Purchase created', [
                'purchase_id' => $purchase->id,
                'user_id' => $user->id,
                'package_id' => $packageId,
                'duration_days' => $durationDays,
            ]);

            // Process based on payment method
            // Process using Unified Payment Link (as per new documentation)
            $paymentResult = $this->processPaymentLink($purchase, $paymentMethod, $bankCode);

            if (!$paymentResult['success']) {
                DB::rollBack();
                return $paymentResult;
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Purchase created successfully',
                'purchase' => [
                    'id' => $purchase->id,
                    'transaction_code' => $purchase->transaction_code,
                    'package_type' => $purchase->package_type,
                    'amount' => $purchase->amount_paid,
                    'status' => $purchase->status,
                ],
                'payment' => $paymentResult['data'] ?? [],
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('[PDF Payment] Failed to create purchase', [
                'user_id' => $user->id,
                'package_id' => $packageId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to create purchase: ' . $e->getMessage(),
            ];
        }
    }


    /**
     * Check payment status
     */
    public function checkPaymentStatus(string $transactionCode): array
    {
        try {
            $purchase = PdfPurchase::where('transaction_code', $transactionCode)
                ->with(['paymentTransaction', 'premiumPdf'])
                ->first();

            if (!$purchase) {
                return [
                    'success' => false,
                    'message' => 'Purchase not found',
                ];
            }

            $transaction = $purchase->paymentTransaction;

            if (!$transaction) {
                return [
                    'success' => false,
                    'message' => 'Payment transaction not found',
                ];
            }

            // Check if already paid
            if ($transaction->isPaid()) {
                return [
                    'success' => true,
                    'status' => 'paid',
                    'paid' => true,
                    'paid_at' => $transaction->paid_at,
                    'expires_at' => $purchase->expires_at,
                ];
            }

            // Check with payment gateway
            $result = match ($transaction->payment_method) {
                'virtual_account' => $this->vaService->checkPaymentStatus($transaction),
                'qris' => $this->qrisService->checkPaymentStatus($transaction),
                default => ['success' => false, 'message' => 'Invalid payment method'],
            };

            // Sync local database status with gateway result when possible
            if (($result['success'] ?? false) && isset($result['status'])) {
                $this->syncTransactionStatus($purchase, $transaction, (string) $result['status'], $result);
            }

            return [
                'success' => $result['success'],
                'status' => $result['status'] ?? 'unknown',
                'paid' => $result['paid'] ?? false,
                'message' => $result['message'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('[PDF Payment] Failed to check status', [
                'transaction_code' => $transactionCode,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to check status: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Sync payment status from gateway to local records.
     */
    protected function syncTransactionStatus(PdfPurchase $purchase, PaymentTransaction $transaction, string $status, array $gatewayResult = []): void
    {
        $normalizedStatus = strtolower($status);

        // Already finalized, no need to process again
        if ($transaction->status === 'paid' && $purchase->status === 'paid') {
            return;
        }

        if (in_array($normalizedStatus, ['paid', 'success'], true)) {
            DB::transaction(function () use ($purchase, $transaction, $gatewayResult) {
                if (!$transaction->isPaid()) {
                    $paidAt = data_get($gatewayResult, 'data.paid_at') ?? now();
                    $transaction->markAsPaid($paidAt, $gatewayResult);
                }

                if ($purchase->status !== 'paid') {
                    $purchase->activate();

                    $user = $purchase->user;
                    if ($user && $purchase->package_type !== 'consultation') {
                        $user->update([
                            'pdf_access_expires_at' => $purchase->expires_at,
                            'pdf_access_package' => $purchase->package_type,
                            'pdf_access_active' => true,
                        ]);
                    }

                    if ($user && (int) ($purchase->premiumPdf->consultation_credits ?? 0) > 0) {
                        $user->addConsultationCredits((int) $purchase->premiumPdf->consultation_credits);
                    }

                    app(\App\Services\AffiliateCommissionService::class)->calculateCommission($purchase);
                }
            });

            return;
        }

        if (in_array($normalizedStatus, ['failed'], true)) {
            if ($transaction->status !== 'failed') {
                $transaction->markAsFailed();
            }

            if ($purchase->status === 'pending') {
                $purchase->markAsFailed();
            }

            return;
        }

        if (in_array($normalizedStatus, ['expired', 'closed'], true)) {
            if ($transaction->status !== 'expired') {
                $transaction->markAsExpired();
            }

            if ($purchase->status === 'pending') {
                $purchase->markAsExpired();
            }
        }
    }

    /**
     * Get user subscription info
     */
    public function getUserSubscription(User $user): array
    {
        $activePurchase = $user->pdfPurchases()
            ->active()
            ->where('package_type', '!=', 'consultation')
            ->with('premiumPdf')
            ->first();

        if (!$activePurchase) {
            return [
                'success' => true,
                'has_subscription' => false,
                'message' => 'No active subscription',
            ];
        }

        return [
            'success' => true,
            'has_subscription' => true,
            'subscription' => [
                'id' => $activePurchase->id,
                'package_type' => $activePurchase->package_type,
                'package_name' => $activePurchase->premiumPdf->name ?? null,
                'started_at' => $activePurchase->started_at,
                'expires_at' => $activePurchase->expires_at,
                'remaining_days' => $activePurchase->remaining_days,
                'is_active' => $activePurchase->isActive(),
            ],
        ];
    }

    /**
     * Cancel purchase
     */
    public function cancelPurchase(User $user, string $transactionCode): array
    {
        try {
            $purchase = $user->pdfPurchases()
                ->where('transaction_code', $transactionCode)
                ->where('status', 'pending')
                ->first();

            if (!$purchase) {
                return [
                    'success' => false,
                    'message' => 'Purchase not found or cannot be cancelled',
                ];
            }

            $purchase->update(['status' => 'cancelled']);

            return [
                'success' => true,
                'message' => 'Purchase cancelled successfully',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to cancel purchase: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get transaction history for user
     */
    public function getTransactionHistory(User $user): array
    {
        try {
            $purchases = $user->pdfPurchases()
                ->with(['premiumPdf', 'paymentTransaction'])
                ->orderBy('created_at', 'desc')
                ->get();

            $history = $purchases->map(function ($purchase) {
                $transaction = $purchase->paymentTransaction;

                return [
                    'id' => $purchase->id,
                    'transaction_code' => $purchase->transaction_code,
                    'package_name' => $purchase->premiumPdf->name ?? 'Unknown Package',
                    'package_type' => $purchase->package_type,
                    'amount' => $purchase->amount_paid,
                    'formatted_amount' => 'Rp ' . number_format($purchase->amount_paid, 0, ',', '.'),
                    'payment_method' => $purchase->payment_method,
                    'payment_method_label' => $this->getPaymentMethodLabel($purchase->payment_method),
                    'status' => $purchase->status,
                    'status_label' => $this->getStatusLabel($purchase->status),
                    'created_at' => $purchase->created_at->toIso8601String(),
                    'formatted_date' => $purchase->created_at->format('d M Y H:i'),
                    'expires_at' => $purchase->expires_at?->toIso8601String(),
                    'formatted_expires_at' => $purchase->expires_at?->format('d M Y'),
                    // Payment details (if available)
                    'va_number' => $transaction?->va_number,
                    'bank_code' => $transaction?->bank_code,
                    'qris_url' => $transaction?->qris_url,
                    'qris_content' => $transaction?->qris_content,
                    'payment_url' => $transaction?->payment_url,
                    'payment_instructions' => match ($purchase->payment_method) {
                        'virtual_account' => ($transaction?->bank_code && $transaction?->va_number)
                            ? $this->vaService->getPaymentInstructions($transaction->bank_code, $transaction->va_number)
                            : [],
                        'qris' => $this->qrisService->getPaymentInstructions(),
                        default => [],
                    },
                ];
            });

            return [
                'success' => true,
                'data' => $history->toArray(),
            ];
        } catch (\Exception $e) {
            Log::error('[PDF Payment] Failed to get transaction history', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to get transaction history: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get payment method label
     */
    protected function getPaymentMethodLabel(string $method): string
    {
        return match ($method) {
            'virtual_account' => 'Virtual Account',
            'qris' => 'QRIS',
            default => ucfirst($method),
        };
    }

    /**
     * Get status label
     */
    protected function getStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Menunggu Pembayaran',
            'paid' => 'Berhasil',
            'active' => 'Aktif',
            'expired' => 'Kadaluarsa',
            'failed' => 'Gagal',
            'cancelled' => 'Dibatalkan',
            default => ucfirst($status),
        };
    }

    /**
     * Process Payment Link (Unified)
     */
    private function processPaymentLink(PdfPurchase $purchase, string $method, ?string $bankCode = null): array
    {
        try {
            // Map to Singapay whitelist codes
            $whitelist = match ($method) {
                'virtual_account' => match ($bankCode) {
                    'BRI' => ['VA_BRI'],
                    'BNI' => ['VA_BNI'],
                    'MANDIRI' => ['VA_MANDIRI'],
                    'PERMATA' => ['VA_PERMATA'],
                    'DANAMON' => ['VA_DANAMON'],
                    'CIMB' => ['VA_CIMB'],
                    'BSI' => ['VA_BSI'],
                    default => ['VA_BRI', 'VA_BNI', 'VA_MANDIRI', 'VA_PERMATA', 'VA_DANAMON'] // Fallback all
                },
                'qris' => ['QRIS'],
                default => []
            };

            $expiryMinutes = 60;

            // Generate return URL untuk redirect setelah pembayaran sukses
            // Support subdirectory deployment (e.g., /grapadistrategix/)
            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));
            // Remove trailing slash to avoid double slash
            $frontendUrl = rtrim($frontendUrl, '/');
            $returnUrl = $frontendUrl . '/payment/success?transaction_code=' . $purchase->transaction_code;

            $paymentLinkData = [
                'reff_no' => $purchase->transaction_code,
                'title' => "Pembayaran PDF Pro - {$purchase->package_type}",
                'total_amount' => $purchase->amount_paid,
                'expired_at' => now()->addMinutes($expiryMinutes)->timestamp * 1000,
                'whitelisted_payment_method' => $whitelist,
                'return_url' => $returnUrl, // URL redirect setelah payment sukses
                'items' => [
                    [
                        'name' => "Paket PDF Pro ({$purchase->package_type})",
                        'quantity' => 1,
                        'unit_price' => $purchase->amount_paid,
                    ]
                ],
                'customer_detail' => [
                    'name' => $purchase->user->name,
                    'email' => $purchase->user->email,
                    'phone' => '08123456789', // Placeholder
                ]
            ];

            $response = $this->apiService->createPaymentLink($paymentLinkData);

            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => $response['message'] ?? 'Payment Gateway Error'
                ];
            }

            $responseData = $response['data'];

            // Create Transaction Record
            // Use expired_at from SingaPay response if available, otherwise fallback to local calculation
            $expiredAt = now()->addMinutes($expiryMinutes);
            if (isset($responseData['expired_at'])) {
                // SingaPay returns timestamp in milliseconds
                $expiredAt = Carbon::createFromTimestampMs($responseData['expired_at']);
            }
            $transaction = $purchase->paymentTransaction()->create([
                'transaction_code' => $purchase->transaction_code,
                'reference_no' => $responseData['reff_no'] ?? $purchase->transaction_code,
                'payment_method' => $method, // 'virtual_account' or 'qris'
                'bank_code' => $bankCode,
                'payment_url' => $responseData['payment_url'] ?? null,
                'amount' => $purchase->amount_paid,
                'currency' => 'IDR',
                'status' => 'pending',
                'mode' => $this->apiService->getMode(),
                'expired_at' => $expiredAt,
                'singapay_request' => $paymentLinkData,
                'singapay_response' => $responseData,
            ]);

            return [
                'success' => true,
                'message' => 'Payment link created successfully',
                'data' => [
                    'transaction_code' => $transaction->transaction_code,
                    'payment_url' => $transaction->payment_url,
                    'is_redirect' => true,
                    'expires_at' => $expiredAt->toIso8601String(),
                ]
            ];
        } catch (\Exception $e) {
            Log::error('[Payment Link] Error', ['msg' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

<?php

namespace App\Services\Singapay;

use App\Models\Affiliate\AffiliateWithdrawal;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SingaPayPayoutService
{
    protected $apiService;
    protected $isMock;

    public function __construct(SingapayApiService $apiService)
    {
        $this->apiService = $apiService;
        $this->isMock = $apiService->isMockMode();
    }

    /**
     * Request a payout (disbursement)
     */
    public function requestPayout(AffiliateWithdrawal $withdrawal): array
    {
        if ($this->isMock) {
            return $this->mockPayout($withdrawal);
        }

        try {
            $swiftCode = $this->getBankSwiftCode($withdrawal->bank_name, $withdrawal->bank_code);

            if (!$swiftCode) {
                throw new \Exception("Unsupported bank or SWIFT code missing for: " . $withdrawal->bank_name);
            }

            $payload = [
                'reference_number' => 'WD-' . $withdrawal->id . '-' . time(),
                'amount' => (float) $withdrawal->amount,
                'bank_swift_code' => $swiftCode,
                'bank_account_number' => $withdrawal->account_number,
                'notes' => $withdrawal->notes ?? 'Withdrawal GrapadiStrategix Affiliate',
            ];

            Log::info('[SingaPay Payout] Sending real payout request', [
                'withdrawal_id' => $withdrawal->id,
                'payload' => $payload
            ]);

            $response = $this->apiService->createDisbursement($payload);

            if ($response['success']) {
                $withdrawal->update([
                    'singapay_reference' => $response['data']['transaction_id'] ?? $response['data']['reference_number'] ?? null,
                    'status' => AffiliateWithdrawal::STATUS_PROCESSED,
                    'singapay_response' => $response['data'],
                    'scheduled_date' => now(),
                ]);

                return [
                    'success' => true,
                    'message' => 'Permintaan withdraw berhasil diproses oleh SingaPay.',
                    'data' => $withdrawal->refresh()
                ];
            }

            throw new \Exception($response['message'] ?? 'SingaPay Payout API failed');
        } catch (\Exception $e) {
            Log::error('[SingaPay Payout] Real payout failed', [
                'withdrawal_id' => $withdrawal->id,
                'error' => $e->getMessage()
            ]);

            $withdrawal->update([
                'status' => AffiliateWithdrawal::STATUS_FAILED,
                'notes' => ($withdrawal->notes ? $withdrawal->notes . ' | ' : '') . 'Error SingaPay: ' . $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Map bank name/code to SWIFT code
     */
    protected function getBankSwiftCode(string $bankName, ?string $bankCode = null): ?string
    {
        // If bankCode already looks like a SWIFT code (8 or 11 chars), use it
        if ($bankCode && (strlen($bankCode) === 8 || strlen($bankCode) === 11)) {
            return strtoupper($bankCode);
        }

        $bankMap = [
            'BCA' => 'CENAIDJA',
            'MANDIRI' => 'BMRIIDJA',
            'BNI' => 'BNINIDJA',
            'BRI' => 'BRINIDJA',
            'CIMB' => 'BNLIIDJA',
            'PERMATA' => 'BBAKIDJA',
            'DANAMON' => 'BDNIIDJA',
            'MAYBANK' => 'IBBKIDJA',
            'PANIN' => 'PNINIDJA',
            'BSI' => 'BSINIDJA',
            'BTN' => 'BBTNIDJA',
            'OCBC' => 'NISPIDJA',
            'MUAMALAT' => 'BMUIIDJA',
        ];

        $key = strtoupper($bankName);
        return $bankMap[$key] ?? $bankMap[$bankCode] ?? null;
    }

    /**
     * Simulate a payout request
     */
    protected function mockPayout(AffiliateWithdrawal $withdrawal): array
    {
        Log::info('[SingaPay Payout] Mock payout processed', [
            'withdrawal_id' => $withdrawal->id,
            'amount' => $withdrawal->amount,
            'bank' => $withdrawal->bank_name,
        ]);

        // Simulate success response
        $scheduledDate = now()->addDay(); // Tomorrow

        // Update withdrawal with mock response
        $withdrawal->update([
            'singapay_reference' => 'MOCK-DISB-' . uniqid(),
            'status' => AffiliateWithdrawal::STATUS_PROCESSED,
            'scheduled_date' => $scheduledDate,
            'singapay_response' => [
                'status' => 'SUCCESS',
                'message' => 'Mock disbursement scheduled',
                'reference_no' => 'MOCK-' . time(),
            ]
        ]);

        return [
            'success' => true,
            'message' => 'Permintaan withdraw berhasil dikirim (MOCK).',
            'data' => [
                'scheduled_date' => $scheduledDate->toIso8601String(),
                'status' => 'processed'
            ]
        ];
    }
}

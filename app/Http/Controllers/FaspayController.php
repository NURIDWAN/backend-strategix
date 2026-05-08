<?php

namespace App\Http\Controllers;

use App\Models\Singapay\PaymentTransaction;
use App\Models\Singapay\PdfPurchase;
use App\Services\FaspayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FaspayController extends Controller
{
    protected FaspayService $faspayService;

    public function __construct(FaspayService $faspayService)
    {
        $this->faspayService = $faspayService;
    }

    /**
     * Handle Faspay payment notification (webhook).
     *
     * Faspay POSTs the notification with bill_no, payment_status_code,
     * signature, etc. We validate Faspay's own signature, then update
     * the linked PdfPurchase / PaymentTransaction.
     */
    public function notification(Request $request): JsonResponse
    {
        $data = $request->json()->all();
        if (empty($data)) {
            $data = $request->all();
        }

        Log::info('[Faspay] Notification received', $data);

        $result = $this->faspayService->handleNotification($data);

        if (!$result['success']) {
            Log::warning('[Faspay] Notification rejected', $result);

            return response()->json([
                'response'      => 'Payment Notification',
                'trx_id'        => $data['trx_id'] ?? null,
                'merchant_id'   => config('faspay.merchant_id'),
                'bill_no'       => $data['bill_no'] ?? null,
                'response_code' => $result['response_code'] ?? '99',
                'response_desc' => $result['response_desc'] ?? 'Failed',
                'response_date' => now()->format('Y-m-d H:i:s'),
            ]);
        }

        $billNo = (string) ($result['bill_no'] ?? '');
        $statusCode = (string) ($result['payment_status_code'] ?? '9');

        $purchase = PdfPurchase::where('transaction_code', $billNo)->first();
        $transaction = PaymentTransaction::where('transaction_code', $billNo)->first();

        if (!$purchase && !$transaction) {
            Log::warning('[Faspay] Notification: transaction not found', ['bill_no' => $billNo]);

            return response()->json([
                'response'      => 'Payment Notification',
                'trx_id'        => $data['trx_id'] ?? null,
                'merchant_id'   => config('faspay.merchant_id'),
                'bill_no'       => $billNo,
                'response_code' => '01',
                'response_desc' => 'Bill not found',
                'response_date' => now()->format('Y-m-d H:i:s'),
            ]);
        }

        $this->applyStatus($purchase, $transaction, $statusCode, $data);

        return response()->json([
            'response'      => 'Payment Notification',
            'trx_id'        => $data['trx_id'] ?? null,
            'merchant_id'   => config('faspay.merchant_id'),
            'bill_no'       => $billNo,
            'response_code' => '00',
            'response_desc' => 'Success',
            'response_date' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Apply Faspay payment_status_code to local models.
     *
     * Faspay codes: 2=paid, 3=failed, 7=expired, 8=cancelled, others=processing.
     */
    private function applyStatus(?PdfPurchase $purchase, ?PaymentTransaction $transaction, string $code, array $payload): void
    {
        switch ($code) {
            case '2':
                if ($purchase && $purchase->status !== 'paid') {
                    $purchase->payment_method = $payload['payment_channel'] ?? $purchase->payment_method;
                    $purchase->metadata = array_merge((array) $purchase->metadata, [
                        'faspay_trx_id'      => $payload['trx_id'] ?? null,
                        'faspay_payment_date'=> $payload['payment_date'] ?? null,
                        'faspay_channel'     => $payload['payment_channel'] ?? null,
                    ]);
                    $purchase->activate();
                    Log::info('[Faspay] Purchase activated', ['purchase_id' => $purchase->id]);
                }
                if ($transaction) {
                    $transaction->update([
                        'status'  => 'success',
                        'paid_at' => now(),
                    ]);
                }
                break;

            case '3':
            case '7':
            case '8':
                if ($purchase && $purchase->status === 'pending') {
                    $purchase->markAsFailed();
                }
                if ($transaction) {
                    $transaction->update(['status' => 'failed']);
                }
                break;

            default:
                if ($transaction) {
                    $transaction->update(['status' => 'processing']);
                }
                break;
        }
    }
}

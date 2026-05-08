<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FaspayService
{
    protected string $merchantId;
    protected string $userId;
    protected string $password;
    protected string $apiKey;
    protected string $environment;
    protected string $baseUrl;
    protected string $paymentUrl;
    protected bool $loggingEnabled;

    public function __construct()
    {
        $this->merchantId     = (string) config('faspay.merchant_id');
        $this->userId         = (string) config('faspay.user_id');
        $this->password       = (string) config('faspay.password');
        $this->apiKey         = (string) config('faspay.api_key', '');
        $this->environment    = (string) config('faspay.environment', 'sandbox');
        $this->loggingEnabled = (bool) config('faspay.logging.enabled', true);

        $endpoints = config('faspay.endpoints', []);
        $env = $endpoints[$this->environment] ?? ($endpoints['sandbox'] ?? []);

        $this->baseUrl    = (string) ($env['base_url'] ?? '');
        $this->paymentUrl = (string) ($env['payment_url'] ?? '');
    }

    public function isConfigured(): bool
    {
        return !empty($this->merchantId) && !empty($this->userId) && !empty($this->password);
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function getMerchantId(): string
    {
        return $this->merchantId;
    }

    public function getSupportedChannels(): array
    {
        return config('faspay.supported_channels', []);
    }

    /**
     * Create a Faspay invoice and return the payment redirect URL.
     *
     * Required keys in $data:
     *   bill_no, bill_total, cust_name, cust_email, cust_phone
     * Optional:
     *   bill_desc, return_url, notif_url, bill_expired_date
     */
    public function createInvoice(array $data): array
    {
        try {
            $billNo             = (string) ($data['bill_no'] ?? '');
            $billDescription    = (string) ($data['bill_desc'] ?? 'Test Payment');
            $normalizedBillTotal = (string) ((int) round((float) ($data['bill_total'] ?? 0)));
            $phone              = (string) ($data['cust_phone'] ?? '');
            $email              = (string) ($data['cust_email'] ?? 'customer@example.com');
            $billDate           = now()->format('Y-m-d H:i:s');

            try {
                $billExpiredAt = Carbon::parse((string) ($data['bill_expired_date'] ?? now()->addMinutes(config('faspay.invoice_expiration', 30))->toDateTimeString()));
            } catch (\Throwable $e) {
                $billExpiredAt = now()->addMinutes(30);
            }

            if ($billExpiredAt->isPast()) {
                $billExpiredAt = now()->addMinutes(30);
            }

            $frontendUrl = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');

            $invoiceData = [
                'merchant_id'      => $this->merchantId,
                'merchant_user_id' => $this->userId,
                'bill_no'          => $billNo,
                'bill_date'        => $billDate,
                'bill_expired'     => $billExpiredAt->format('Y-m-d H:i:s'),
                'bill_desc'        => $billDescription,
                'bill_total'       => $normalizedBillTotal,
                'cust_no'          => $billNo,
                'cust_name'        => (string) ($data['cust_name'] ?? 'Customer'),
                'return_url'       => (string) ($data['return_url'] ?? $frontendUrl . '/payment/success'),
                'msisdn'           => $phone,
                'email'            => $email,
                'item'             => $data['item'] ?? [[
                    'product' => substr($billDescription, 0, 50),
                    'qty'     => '1',
                    'amount'  => $normalizedBillTotal,
                ]],
                'merchant_logo'    => (string) ($data['merchant_logo'] ?? ''),
                'signature'        => $this->generateInvoiceSignature($this->userId, $billNo, $normalizedBillTotal),
            ];

            if ($this->loggingEnabled) {
                Log::info('[Faspay] Creating invoice', [
                    'bill_no'    => $billNo,
                    'bill_total' => $normalizedBillTotal,
                    'env'        => $this->environment,
                ]);
            }

            $request = Http::timeout(30)->retry(2, 300, null, false);

            if ($this->environment === 'sandbox') {
                $request = $request->withoutVerifying();
            }

            $apiReq      = $request->post($this->paymentUrl, $invoiceData);
            $responseBody = $apiReq->body();
            $response     = $apiReq->json() ?? [];

            // Retry with merchantId as identifier if signature fails
            if (($response['response_desc'] ?? '') === 'invalid signature') {
                $invoiceData['signature'] = $this->generateInvoiceSignature($this->merchantId, $billNo, $normalizedBillTotal);
                $apiReq       = $request->post($this->paymentUrl, $invoiceData);
                $responseBody = $apiReq->body();
                $response     = $apiReq->json() ?? [];
            }

            if ($this->loggingEnabled) {
                Log::info('[Faspay] Invoice response', [
                    'status' => $apiReq->status(),
                    'body'   => $responseBody,
                ]);
            }

            $paymentUrl = $response['redirect_url'] ?? $response['payment_url'] ?? null;
            $isSuccess  = ($response['response_code'] ?? null) === '00';
            $message    = $response['response_desc'] ?? null;

            if (!$isSuccess && empty($message) && !$apiReq->successful()) {
                $message = 'Faspay gateway error HTTP ' . $apiReq->status();
            }

            return [
                'success'     => $isSuccess,
                'data'        => $response,
                'payment_url' => $paymentUrl,
                'trx_id'      => $response['trx_id'] ?? null,
                'message'     => $message ?? ($isSuccess ? 'Success' : 'Invoice creation failed'),
            ];
        } catch (ConnectionException|RequestException $e) {
            if ($this->loggingEnabled) {
                Log::error('[Faspay] Invoice request error', ['error' => $e->getMessage()]);
            }

            return [
                'success'     => false,
                'data'        => [],
                'payment_url' => null,
                'trx_id'      => null,
                'message'     => 'Faspay gateway tidak merespons. Coba lagi beberapa menit.',
            ];
        } catch (\Throwable $e) {
            if ($this->loggingEnabled) {
                Log::error('[Faspay] Invoice creation failed', ['error' => $e->getMessage()]);
            }

            return [
                'success'     => false,
                'data'        => [],
                'payment_url' => null,
                'trx_id'      => null,
                'message'     => 'Invoice creation failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Query payment status by bill_no.
     */
    public function getPaymentStatus(string $billNo): array
    {
        $request = Http::timeout(30);

        if ($this->environment === 'sandbox') {
            $request = $request->withoutVerifying();
        }

        $response = $request->post("{$this->baseUrl}/api/queryStatus", [
            'merchant_id' => $this->merchantId,
            'bill_no'     => $billNo,
            'user_id'     => $this->userId,
            'password'    => $this->password,
        ])->json();

        return [
            'success' => isset($response['status']) && ($response['status'] == '0' || $response['status'] === true),
            'data'    => $response,
        ];
    }

    private function generateInvoiceSignature(string $identifier, string $billNo, string $billTotal): string
    {
        return sha1(md5($identifier . $this->password . $billNo . $billTotal));
    }
}

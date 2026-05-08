<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Singapay\WebhookController as SingapayWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Receives webhooks forwarded by the central Payment Callback Hub.
 *
 * Verifies the shared HMAC signature (X-Hub-Signature) using HUB_SECRET,
 * then delegates to the existing gateway handler so downstream business
 * logic (PdfPurchase activation, etc.) runs unchanged.
 */
class HubWebhookController extends Controller
{
    public function singapay(Request $request, SingapayWebhookController $delegate)
    {
        $this->verifyHubSignature($request);
        return $delegate->handlePayment($request);
    }

    public function faspay(Request $request, FaspayController $delegate)
    {
        $this->verifyHubSignature($request);
        return $delegate->notification($request);
    }

    private function verifyHubSignature(Request $request): void
    {
        $secret = (string) env('HUB_SECRET', '');

        if ($secret === '') {
            Log::error('[HubWebhook] HUB_SECRET not configured; rejecting');
            throw new HttpException(500, 'hub secret not configured');
        }

        $rawBody  = $request->getContent();
        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);
        $received = (string) $request->header('X-Hub-Signature', '');

        if ($received === '' || !hash_equals($expected, $received)) {
            Log::warning('[HubWebhook] Signature mismatch', [
                'event_id' => $request->header('X-Hub-Event-Id'),
                'reff_no'  => $request->header('X-Hub-Reff-No'),
                'provider' => $request->header('X-Hub-Provider'),
            ]);
            throw new HttpException(401, 'invalid hub signature');
        }

        Log::info('[HubWebhook] Signature verified', [
            'event_id' => $request->header('X-Hub-Event-Id'),
            'reff_no'  => $request->header('X-Hub-Reff-No'),
            'provider' => $request->header('X-Hub-Provider'),
            'attempt'  => $request->header('X-Hub-Attempt'),
        ]);
    }
}

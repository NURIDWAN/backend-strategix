<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $apiUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('services.fonnte.api_url') . '/send';
        $this->apiKey = config('services.fonnte.api_key');
    }

    /**
     * Send OTP via WhatsApp using Fonnte API
     */
    public function sendOtp($phone, $otp)
    {
        try {
            // Format nomor telepon untuk Indonesia
            $formattedPhone = $this->formatPhoneNumber($phone);

            // Pesan OTP
            $message = "Kode verifikasi Grapadi Strategix Anda: *{$otp}*\n\nKode ini berlaku selama 10 menit.\nJangan berikan kode ini kepada siapapun.";

            Log::info("Sending WhatsApp OTP to {$formattedPhone}: {$otp}");

            // Kirim via Fonnte API
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
            ])->post($this->apiUrl, [
                'target' => $formattedPhone,
                'message' => $message,
                'countryCode' => '62', // Indonesia
            ]);

            Log::info("Fonnte API Response: " . $response->body());

            if ($response->successful()) {
                $result = $response->json();
                if ($result['status'] === true) {
                    Log::info("WhatsApp OTP sent successfully to {$formattedPhone}");
                    return true;
                } else {
                    Log::error("Fonnte API error: " . $result['message']);
                    return false;
                }
            } else {
                Log::error("Fonnte API failed: " . $response->body());
                return false;
            }

        } catch (\Exception $e) {
            Log::error('WhatsApp service error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send reset password OTP
     */
    public function sendResetOtp($phone, $otp)
    {
        try {
            $formattedPhone = $this->formatPhoneNumber($phone);

            $message = "Kode reset password Grapadi Strategix Anda: *{$otp}*\n\nKode ini berlaku selama 10 menit.\nJika Anda tidak meminta reset password, abaikan pesan ini.";

            Log::info("Sending WhatsApp Reset OTP to {$formattedPhone}: {$otp}");

            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
            ])->post($this->apiUrl, [
                'target' => $formattedPhone,
                'message' => $message,
                'countryCode' => '62',
            ]);

            if ($response->successful()) {
                $result = $response->json();
                if ($result['status'] === true) {
                    Log::info("WhatsApp Reset OTP sent successfully to {$formattedPhone}");
                    return true;
                } else {
                    Log::error("Fonnte API error for reset OTP: " . $result['message']);
                    return false;
                }
            } else {
                Log::error("Fonnte API failed for reset OTP: " . $response->body());
                return false;
            }

        } catch (\Exception $e) {
            Log::error('WhatsApp reset OTP error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Format phone number for Fonnte API
     */
    protected function formatPhoneNumber($phone)
    {
        // Hapus semua karakter non-digit
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        // Jika diawali dengan 0, ganti dengan 62
        if (substr($cleanPhone, 0, 1) === '0') {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        }

        // Jika diawali dengan 62, biarkan saja
        // Jika diawali dengan +62, hapus +
        if (substr($cleanPhone, 0, 3) === '+62') {
            $cleanPhone = '62' . substr($cleanPhone, 3);
        }

        return $cleanPhone;
    }
}

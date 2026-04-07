<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class OtpService
{
    private string $baseUrl = 'https://otp.arqam.tech/api';

    private function getApiKey(): string
    {
        // Try DB first, then ENV
        $key = Setting::get('whatsapp_api_key', env('WHATSAPP_OTP_API_KEY'));
        
        if (empty($key)) {
            Log::critical('WhatsApp API Key is missing! Please set whatsapp_api_key in settings or WHATSAPP_OTP_API_KEY in .env');
            throw new \Exception('خدمة الرسائل غير مهيأة حالياً لدى الإدارة.');
        }

        return $key;
    }

    /**
     * Normalize Iraqi phone numbers to +964 format
     */
    public static function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/^0/', '', trim($phone));
        if (!str_starts_with($phone, '+')) {
            $phone = (!str_starts_with($phone, '964')) ? '+964' . $phone : '+' . $phone;
        }
        return $phone;
    }

    public function sendOtp(string $phone): array
    {
        try {
            $response = Http::timeout(10)
                ->retry(2, 200)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->getApiKey()}",
                    'Accept'        => 'application/json',
                ])->post("{$this->baseUrl}/sms/otp", [
                    'phoneNumber' => self::normalizePhone($phone),
                    'channel'     => 'whatsapp',
                ]);

            if (!$response->successful()) {
                Log::error('OTP API Failed: ' . $response->status() . ' - ' . $response->body());
                return ['success' => false, 'message' => 'تعذر الاتصال بمزود خدمة الإرسال حالياً.'];
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('OTP Service Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ في خدمة الإرسال: ' . $e->getMessage()];
        }
    }

    public function verifyOtp(string $messageId, string $code): array
    {
        try {
            $response = Http::timeout(10)
                ->retry(2, 200)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->getApiKey()}",
                    'Accept'        => 'application/json',
                ])->post("{$this->baseUrl}/sms/verify", [
                    'messageId' => $messageId,
                    'code'      => $code,
                ]);

            if (!$response->successful()) {
                return ['success' => false, 'message' => 'خدمة التحقق غير متاحة لدى المزود حالياً.'];
            }

            return $response->json();
            
        } catch (\Exception $e) {
            Log::error('OTP Verify Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ أثناء فحص الرمز.'];
        }
    }
}

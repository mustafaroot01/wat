<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class OtpService
{
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

    private function getApiKey(): string
    {
        $key = Setting::get('arqam_api_key')
            ?: Setting::get('whatsapp_api_key')
            ?: env('ARQAM_API_KEY', env('WHATSAPP_OTP_API_KEY', ''));

        if (empty($key)) {
            Log::critical('Arqam API key missing in settings/env.');
            throw new \Exception('خدمة الرسائل غير مهيأة حالياً. تواصل مع المشرف.');
        }

        return $key;
    }

    public function sendOtp(string $phone): array
    {
        try {
            $response = Http::timeout(10)->retry(2, 200)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->getApiKey(),
                    'Accept'        => 'application/json',
                ])
                ->post('https://otp.arqam.tech/api/sms/otp', [
                    'phoneNumber' => self::normalizePhone($phone),
                    'channel'     => 'whatsapp',
                ]);

            if (!$response->successful()) {
                Log::error('Arqam sendOtp failed: ' . $response->status() . ' — ' . $response->body());
                return ['success' => false, 'message' => 'تعذر إرسال رمز التحقق، يرجى المحاولة مجدداً.'];
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('OtpService::sendOtp: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function verifyOtp(string $messageId, string $code): array
    {
        try {
            $response = Http::timeout(10)->retry(2, 200)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->getApiKey(),
                    'Accept'        => 'application/json',
                ])
                ->post('https://otp.arqam.tech/api/sms/verify', [
                    'messageId' => $messageId,
                    'code'      => $code,
                ]);

            if (!$response->successful()) {
                return ['success' => false, 'message' => 'خدمة التحقق غير متاحة لدى المزود حالياً.'];
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('OtpService::verifyOtp: ' . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ أثناء فحص الرمز.'];
        }
    }
}

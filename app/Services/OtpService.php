<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
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
        $key = Setting::get('otpiq_api_key', env('OTPIQ_API_KEY', ''));

        if (empty($key)) {
            Log::critical('OTPIQ API key missing in settings/env.');
            throw new \Exception('خدمة الرسائل غير مهيأة حالياً. تواصل مع المشرف.');
        }

        return $key;
    }

    /**
     * Send OTP via OTPIQ — generates code locally, sends via WhatsApp, caches it.
     */
    public function sendOtp(string $phone): array
    {
        try {
            // Block if no OTP credits
            if ((int) Setting::get('otp_credits', 0) <= 0) {
                Log::warning('OtpService: otp_credits exhausted, blocking send.');
                return ['success' => false, 'message' => 'خدمة التحقق متوقفة مؤقتاً، تواصل مع الإدارة.'];
            }

            $phone = self::normalizePhone($phone);
            $code  = (string) random_int(100000, 999999);

            $response = Http::timeout(15)->retry(2, 300)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->getApiKey(),
                    'Accept'        => 'application/json',
                ])
                ->post('https://api.otpiq.com/api/sms', [
                    'phoneNumber'      => ltrim($phone, '+'),
                    'smsType'          => 'verification',
                    'verificationCode' => $code,
                    'provider'         => 'whatsapp',
                ]);

            if (!$response->successful()) {
                Log::error('OTPIQ sendOtp failed: ' . $response->status() . ' — ' . $response->body());
                return ['success' => false, 'message' => 'تعذر إرسال رمز التحقق، يرجى المحاولة مجدداً.'];
            }

            Cache::put('otp_' . $phone, $code, now()->addMinutes(5));

            // Decrement OTP credits if available (non-blocking)
            $credits = (int) Setting::get('otp_credits', 0);
            if ($credits > 0) {
                Setting::set('otp_credits', $credits - 1);
            }

            return ['success' => true, 'messageId' => $phone];

        } catch (\Exception $e) {
            Log::error('OtpService::sendOtp: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Verify OTP locally from cache (OTPIQ has no verify endpoint).
     * $messageId = phone number returned from sendOtp.
     */
    public function verifyOtp(string $messageId, string $code): array
    {
        $phone    = self::normalizePhone($messageId);
        $cacheKey = 'otp_' . $phone;
        $stored   = Cache::get($cacheKey);

        if (!$stored) {
            return ['success' => false, 'message' => 'انتهت صلاحية رمز التحقق، يرجى طلب رمز جديد.'];
        }

        if ($stored !== trim($code)) {
            return ['success' => false, 'message' => 'رمز التحقق غير صحيح.'];
        }

        Cache::forget($cacheKey);
        return ['success' => true];
    }
}

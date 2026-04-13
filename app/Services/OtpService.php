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

    private function getProvider(): string
    {
        return Setting::get('otp_provider', env('OTP_PROVIDER', 'arqam'));
    }

    // ══════════════════════════════════════════════════════════════
    //  PUBLIC INTERFACE
    // ══════════════════════════════════════════════════════════════

    public function sendOtp(string $phone): array
    {
        return match ($this->getProvider()) {
            'otpiq'  => $this->otpiqSend($phone),
            'arqam'  => $this->arqamSend($phone),
            default  => $this->twilioSend($phone),
        };
    }

    public function verifyOtp(string $messageId, string $code): array
    {
        return match ($this->getProvider()) {
            'otpiq'  => $this->otpiqVerify($messageId, $code),
            'arqam'  => $this->arqamVerify($messageId, $code),
            default  => $this->twilioVerify($messageId, $code),
        };
    }

    // ══════════════════════════════════════════════════════════════
    //  TWILIO VERIFY
    // ══════════════════════════════════════════════════════════════

    private function twilioCredentials(): array
    {
        $sid     = Setting::get('twilio_account_sid',        env('TWILIO_ACCOUNT_SID',        ''));
        $token   = Setting::get('twilio_auth_token',         env('TWILIO_AUTH_TOKEN',         ''));
        $service = Setting::get('twilio_verify_service_sid', env('TWILIO_VERIFY_SERVICE_SID', ''));

        if (empty($sid) || empty($token) || empty($service)) {
            Log::critical('Twilio credentials missing in settings/env.');
            throw new \Exception('خدمة الرسائل غير مهيأة حالياً. تواصل مع المشرف.');
        }

        return ['sid' => $sid, 'token' => $token, 'service' => $service];
    }

    private function twilioSend(string $phone): array
    {
        try {
            $creds = $this->twilioCredentials();
            $phone = self::normalizePhone($phone);

            $response = Http::timeout(15)->retry(2, 300)
                ->withBasicAuth($creds['sid'], $creds['token'])
                ->asForm()
                ->post("https://verify.twilio.com/v2/Services/{$creds['service']}/Verifications", [
                    'To'      => $phone,
                    'Channel' => 'whatsapp',
                ]);

            if (!$response->successful()) {
                Log::error('Twilio sendOtp failed: ' . $response->status() . ' — ' . $response->body());
                return ['success' => false, 'message' => 'تعذر إرسال رمز التحقق، يرجى المحاولة مجدداً.'];
            }

            if (($response->json()['status'] ?? '') === 'pending') {
                return ['success' => true, 'messageId' => $phone];
            }

            return ['success' => false, 'message' => 'استجابة غير متوقعة من مزود الخدمة.'];

        } catch (\Exception $e) {
            Log::error('twilioSend: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function twilioVerify(string $messageId, string $code): array
    {
        try {
            $creds = $this->twilioCredentials();
            $phone = self::normalizePhone($messageId);

            $response = Http::timeout(15)->retry(2, 300)
                ->withBasicAuth($creds['sid'], $creds['token'])
                ->asForm()
                ->post("https://verify.twilio.com/v2/Services/{$creds['service']}/VerificationChecks", [
                    'To'   => $phone,
                    'Code' => $code,
                ]);

            if (!$response->successful()) {
                Log::error('Twilio verifyOtp failed: ' . $response->status() . ' — ' . $response->body());
                return ['success' => false, 'message' => 'خدمة التحقق غير متاحة لدى المزود حالياً.'];
            }

            return ['success' => ($response->json()['status'] ?? '') === 'approved'];

        } catch (\Exception $e) {
            Log::error('twilioVerify: ' . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ أثناء فحص الرمز.'];
        }
    }

    // ══════════════════════════════════════════════════════════════
    //  ARQAM TECH (otp.arqam.tech)
    // ══════════════════════════════════════════════════════════════

    private function arqamApiKey(): string
    {
        // Fall back to old key name for backward compatibility
        $key = Setting::get('arqam_api_key')
            ?: Setting::get('whatsapp_api_key')
            ?: env('ARQAM_API_KEY', env('WHATSAPP_OTP_API_KEY', ''));

        if (empty($key)) {
            Log::critical('Arqam API key missing in settings/env.');
            throw new \Exception('خدمة الرسائل غير مهيأة حالياً. تواصل مع المشرف.');
        }

        return $key;
    }

    private function arqamSend(string $phone): array
    {
        try {
            $response = Http::timeout(10)->retry(2, 200)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->arqamApiKey(),
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
            Log::error('arqamSend: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function arqamVerify(string $messageId, string $code): array
    {
        try {
            $response = Http::timeout(10)->retry(2, 200)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->arqamApiKey(),
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
            Log::error('arqamVerify: ' . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ أثناء فحص الرمز.'];
        }
    }

    // ══════════════════════════════════════════════════════════════
    //  OTPIQ
    //  - No verify endpoint → generate code locally, cache it
    // ══════════════════════════════════════════════════════════════

    private function otpiqApiKey(): string
    {
        $key = Setting::get('otpiq_api_key', env('OTPIQ_API_KEY', ''));

        if (empty($key)) {
            Log::critical('OTPIQ API key missing in settings/env.');
            throw new \Exception('خدمة الرسائل غير مهيأة حالياً. تواصل مع المشرف.');
        }

        return $key;
    }

    /** OTPIQ phone format: without + (e.g. 9647XXXXXXXX) */
    private static function otpiqPhone(string $phone): string
    {
        return ltrim(self::normalizePhone($phone), '+');
    }

    private function otpiqSend(string $phone): array
    {
        try {
            $apiKey = $this->otpiqApiKey();
            $phone  = self::normalizePhone($phone);
            $code   = (string) random_int(100000, 999999);

            $response = Http::timeout(15)->retry(2, 300)
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Accept'        => 'application/json',
                ])
                ->post('https://api.otpiq.com/api/sms', [
                    'phoneNumber'      => self::otpiqPhone($phone),
                    'smsType'          => 'verification',
                    'verificationCode' => $code,
                    'provider'         => 'whatsapp',
                ]);

            if (!$response->successful()) {
                Log::error('OTPIQ sendOtp failed: ' . $response->status() . ' — ' . $response->body());
                return ['success' => false, 'message' => 'تعذر إرسال رمز التحقق، يرجى المحاولة مجدداً.'];
            }

            // Store code in cache for 5 minutes (keyed by phone)
            Cache::put('otpiq_code_' . $phone, $code, now()->addMinutes(5));

            return ['success' => true, 'messageId' => $phone];

        } catch (\Exception $e) {
            Log::error('otpiqSend: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function otpiqVerify(string $messageId, string $code): array
    {
        $phone     = self::normalizePhone($messageId);
        $cacheKey  = 'otpiq_code_' . $phone;
        $stored    = Cache::get($cacheKey);

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

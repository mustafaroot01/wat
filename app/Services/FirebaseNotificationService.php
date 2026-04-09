<?php

namespace App\Services;

use App\Models\User;
use Kreait\Firebase\Messaging\CloudMessage;
use Illuminate\Support\Facades\Log;

class FirebaseNotificationService
{
    private function getMessaging()
    {
        return app('firebase.messaging');
    }

    private function buildPayload(string $title, string $body, ?string $imageUrl): array
    {
        $payload = [
            'notification' => [
                'title' => $title,
                'body'  => $body,
            ],
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'sound'      => 'default',
                    'channel_id' => 'high_importance_channel',
                ],
            ],
            'apns' => [
                'headers' => ['apns-priority' => '10'],
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'badge' => 1,
                    ],
                ],
            ],
        ];

        if ($imageUrl) {
            $payload['notification']['image']            = $imageUrl;
            $payload['android']['notification']['image'] = $imageUrl;
        }

        return $payload;
    }

    /**
     * Send to all registered device tokens (multicast).
     * Falls back to topic if no tokens found.
     */
    public function sendToTopic(string $topic, string $title, string $body, ?string $imageUrl = null): array
    {
        try {
            $messaging = $this->getMessaging();
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'لم يتم إعداد ملف Firebase JSON بشكل صحيح في الإعدادات.'];
        }

        $payload = $this->buildPayload($title, $body, $imageUrl);

        // جلب كل التوكنز المسجّلة
        $tokens = User::whereNotNull('fcm_token')
            ->where('is_active', true)
            ->pluck('fcm_token')
            ->unique()
            ->values()
            ->toArray();

        $sent = 0;
        $errors = [];

        // إرسال لكل الأجهزة المسجّلة
        if (!empty($tokens)) {
            $chunks = array_chunk($tokens, 500); // FCM limit per multicast
            foreach ($chunks as $chunk) {
                try {
                    $messages = array_map(function ($token) use ($payload) {
                        return CloudMessage::fromArray(array_merge($payload, ['token' => $token]));
                    }, $chunk);

                    $report = $messaging->sendAll($messages);
                    $sent  += $report->successes()->count();

                    foreach ($report->failures()->getItems() as $failure) {
                        $errors[] = $failure->error()->getMessage();
                        // حذف التوكن إذا أصبح غير صالح
                        if (str_contains($failure->error()->getMessage(), 'registration-token-not-registered')) {
                            User::where('fcm_token', $failure->target()->value())->update(['fcm_token' => null]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('FCM Multicast Error: ' . $e->getMessage());
                }
            }
        }

        // إرسال للـ Topic أيضاً (للأجهزة التي لم تُسجّل token بعد)
        try {
            $topicMessage = CloudMessage::fromArray(array_merge($payload, ['topic' => $topic]));
            $messaging->send($topicMessage);
        } catch (\Exception $e) {
            Log::warning('FCM Topic fallback failed: ' . $e->getMessage());
        }

        if ($sent > 0 || empty($tokens)) {
            return ['success' => true, 'message' => "تم الإرسال لـ {$sent} جهاز.", 'sent' => $sent];
        }

        return [
            'success' => false,
            'error'   => 'فشل الإرسال لجميع الأجهزة. ' . implode(' | ', array_slice($errors, 0, 3)),
        ];
    }
}

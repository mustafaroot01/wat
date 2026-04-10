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
                        'sound'           => 'default',
                        'badge'           => 1,
                        'mutable-content' => 1,
                    ],
                ],
            ],
        ];

        if ($imageUrl) {
            $payload['notification']['image']              = $imageUrl;
            $payload['android']['notification']['image']   = $imageUrl;
            $payload['apns']['fcm_options']['image']       = $imageUrl;
        }

        return $payload;
    }

    /**
     * Send to Firebase Topic (primary) + stored device tokens (supplementary).
     * Topic reaches all Flutter devices that called subscribeToTopic('all_users').
     * Tokens are a backup for devices that didn't subscribe to the topic.
     */
    public function sendToTopic(string $topic, string $title, string $body, ?string $imageUrl = null): array
    {
        try {
            $messaging = $this->getMessaging();
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'لم يتم إعداد ملف Firebase JSON بشكل صحيح في الإعدادات.'];
        }

        $payload = $this->buildPayload($title, $body, $imageUrl);

        // 1) إرسال للـ Topic (الطريقة الرئيسية — تصل لكل الأجهزة المشتركة)
        try {
            $messaging->send(CloudMessage::fromArray(array_merge($payload, ['topic' => $topic])));
        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            Log::error('FCM Topic Error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'خطأ من Firebase: ' . $e->getMessage()];
        } catch (\Exception $e) {
            Log::error('FCM Generic Error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'فشل الاتصال بـ Firebase: ' . $e->getMessage()];
        }

        // 2) إرسال للتوكنز المخزّنة (إضافي — للأجهزة غير المشتركة بالـ Topic)
        $tokens = User::whereNotNull('fcm_token')
            ->where('is_active', true)
            ->pluck('fcm_token')
            ->unique()
            ->values()
            ->toArray();

        if (!empty($tokens)) {
            foreach (array_chunk($tokens, 500) as $chunk) {
                try {
                    $messages = array_map(
                        fn($t) => CloudMessage::fromArray(array_merge($payload, ['token' => $t])),
                        $chunk
                    );
                    $report = $messaging->sendAll($messages);
                    foreach ($report->failures()->getItems() as $failure) {
                        if (str_contains($failure->error()->getMessage(), 'registration-token-not-registered')) {
                            User::where('fcm_token', $failure->target()->value())->update(['fcm_token' => null]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('FCM Token Batch Error: ' . $e->getMessage());
                }
            }
        }

        return ['success' => true, 'message' => 'تم الإرسال بنجاح عبر Topic + ' . count($tokens) . ' جهاز مسجّل.'];
    }
}

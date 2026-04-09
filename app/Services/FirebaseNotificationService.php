<?php

namespace App\Services;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class FirebaseNotificationService
{
    /**
     * Send a notification to a specific topic.
     */
    public function sendToTopic(string $topic, string $title, string $body, ?string $imageUrl = null): array
    {
        try {
            try {
                $messaging = app('firebase.messaging');
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'error' => 'لم يتم إعداد ملف Firebase JSON بشكل صحيح في الإعدادات.'
                ];
            }

            $messageData = [
                'topic' => $topic,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'sound'      => 'default',
                        'channel_id' => 'high_importance_channel',
                        'priority'   => 'high',
                    ],
                ],
                'apns' => [
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1,
                        ],
                    ],
                ],
            ];

            if ($imageUrl) {
                $messageData['notification']['image']              = $imageUrl;
                $messageData['android']['notification']['image']   = $imageUrl;
            }

            $message = CloudMessage::fromArray($messageData);
            $messaging->send($message);

            return [
                'success' => true,
                'message' => 'Notification sent to topic: ' . $topic,
            ];
        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            Log::error('Firebase Messaging Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'خطأ من Firebase: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('Firebase Generic Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'فشل في الاتصال بـ Firebase: ' . $e->getMessage()
            ];
        }
    }
}

<?php

namespace App\Services;

use App\Models\Order;
use App\Models\StoreSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private ?string $botToken;
    private ?string $chatId;
    private bool $enabled;

    public function __construct()
    {
        $this->botToken = StoreSetting::get('telegram_bot_token');
        $this->chatId = StoreSetting::get('telegram_chat_id');
        $this->enabled = (bool) StoreSetting::get('telegram_enabled', false);
    }

    /**
     * Send new order notification to Telegram
     */
    public function sendNewOrderNotification(Order $order): bool
    {
        if (!$this->enabled || !$this->botToken || !$this->chatId) {
            return false;
        }

        try {
            $message = $this->formatOrderMessage($order);
            $keyboard = $this->buildInlineKeyboard($order);

            $response = Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode($keyboard)
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Telegram notification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Format order details into a readable message
     */
    private function formatOrderMessage(Order $order): string
    {
        $order->load(['items.product', 'district', 'area']);

        $message = "🆕 طلب جديد! #{$order->invoice_code}\n";
        $message .= "━━━━━━━━━━━━━━━━━━━\n";
        $message .= "👤 {$order->customer_name}\n";
        $message .= "📞 {$order->customer_phone}\n\n";

        $message .= "📍 العنوان:\n";
        $message .= "   🏛️ القضاء: {$order->district->name}\n";
        $message .= "   🏘️ المنطقة: {$order->area->name}\n";
        if ($order->nearest_landmark) {
            $message .= "   📌 قرب: {$order->nearest_landmark}\n";
        }
        $message .= "\n";

        $itemsCount = $order->items->sum('quantity');
        $message .= "🛍️ المنتجات ({$itemsCount}):\n";
        foreach ($order->items as $item) {
            $message .= "   • {$item->product_name} × {$item->quantity}\n";
        }
        $message .= "\n";

        $message .= "💰 المبلغ الإجمالي: " . number_format($order->total) . " IQD\n\n";

        $message .= "⏰ " . $order->created_at->locale('ar')->isoFormat('D MMMM - h:mm A');
        $message .= "\n━━━━━━━━━━━━━━━━━━━";

        return $message;
    }

    /**
     * Build inline keyboard with action buttons
     */
    private function buildInlineKeyboard(Order $order): array
    {
        $invoiceUrl = url("/invoice/{$order->invoice_token}");
        $phoneNumber = preg_replace('/[^0-9]/', '', $order->customer_phone);
        $whatsappUrl = "https://wa.me/964{$phoneNumber}?text=" . urlencode("مرحباً، بخصوص طلبك رقم {$order->invoice_code}");

        return [
            'inline_keyboard' => [
                [
                    ['text' => '📋 نسخ الرقم', 'callback_data' => "copy_phone_{$order->id}"],
                    ['text' => '📞 اتصال', 'url' => "tel:{$order->customer_phone}"],
                ],
                [
                    ['text' => '💬 واتساب', 'url' => $whatsappUrl],
                    ['text' => '🔗 فتح الفاتورة', 'url' => $invoiceUrl],
                ]
            ]
        ];
    }

    /**
     * Test connection to Telegram bot
     */
    public function testConnection(): array
    {
        if (!$this->botToken) {
            return ['success' => false, 'message' => 'Bot Token مفقود'];
        }

        if (!$this->chatId) {
            return ['success' => false, 'message' => 'Chat ID مفقود'];
        }

        try {
            $response = Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id' => $this->chatId,
                'text' => "✅ تم الاتصال بنجاح!\n\nبوت التيليجرام جاهز لاستقبال الطلبات.",
            ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'تم الاتصال بنجاح!'];
            }

            return ['success' => false, 'message' => 'فشل الاتصال: ' . $response->body()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'خطأ: ' . $e->getMessage()];
        }
    }
}

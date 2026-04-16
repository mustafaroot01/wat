<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'admin_id',
        'action',
        'model_type',
        'model_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'device_type',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Generate Arabic description for the action
     */
    public static function generateDescription(string $action, string $modelType, $modelId = null, array $context = []): string
    {
        $modelNames = [
            'Product'      => 'منتج',
            'Category'     => 'قسم',
            'Brand'        => 'شركة',
            'Order'        => 'طلب',
            'Customer'     => 'عميل',
            'Coupon'       => 'كود خصم',
            'Banner'       => 'بانر',
            'Discount'     => 'خصم مميز',
            'District'     => 'قضاء',
            'Area'         => 'منطقة',
            'Notification' => 'إشعار',
            'Admin'        => 'مشرف',
            'Setting'      => 'إعداد',
        ];

        $modelName = $modelNames[$modelType] ?? $modelType;
        $name = $context['name'] ?? '';

        return match($action) {
            'created' => "أضاف {$modelName} جديد" . ($name ? ": {$name}" : ''),
            'updated' => "عدّل {$modelName}" . ($name ? ": {$name}" : ''),
            'deleted' => "حذف {$modelName}" . ($name ? ": {$name}" : ''),
            'toggled_active' => "غيّر حالة تفعيل {$modelName}" . ($name ? ": {$name}" : ''),
            'toggled_stock' => "غيّر حالة المخزون لـ {$modelName}" . ($name ? ": {$name}" : ''),
            'status_changed' => "غيّر حالة {$modelName}" . ($name ? " {$name}" : '') . ($context['status'] ? " إلى: {$context['status']}" : ''),
            default => "نفّذ عملية {$action} على {$modelName}",
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    const STATUS_SENT               = 'sent';
    const STATUS_RECEIVED_PREPARING = 'received_preparing';
    const STATUS_OUT_FOR_DELIVERY   = 'out_for_delivery';
    const STATUS_DELIVERED          = 'delivered';
    const STATUS_REJECTED           = 'rejected';
    const STATUS_CANCELLED          = 'cancelled';

    const STATUSES = [
        self::STATUS_SENT,
        self::STATUS_RECEIVED_PREPARING,
        self::STATUS_OUT_FOR_DELIVERY,
        self::STATUS_DELIVERED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'user_id',
        'invoice_code',
        'invoice_token',
        'customer_name',
        'customer_phone',
        'province',
        'district',
        'nearest_landmark',
        'status',
        'total_amount',
        'discount_amount',
        'final_amount',
        'coupon_id',
        'notes',
        'rejection_reason',
    ];

    protected $appends = ['current_customer_name'];

    protected $casts = [
        'total_amount'    => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount'    => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Order $order) {
            if (empty($order->invoice_code)) {
                $order->invoice_code = self::generateInvoiceCode();
            }
            if (empty($order->invoice_token)) {
                $order->invoice_token = Str::random(64);
            }
        });
    }

    public function getCurrentCustomerNameAttribute(): string
    {
        return $this->user?->full_name ?? $this->customer_name;
    }

    private static function generateInvoiceCode(): string
    {
        $last = self::max('id') ?? 0;
        return 'INV-' . str_pad($last + 1, 5, '0', STR_PAD_LEFT);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}

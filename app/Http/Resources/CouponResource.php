<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'code'             => $this->code,
            'type'             => $this->type,
            'type_label'       => $this->type === 'percentage' ? 'نسبة مئوية' : 'مبلغ ثابت',
            'value'            => (float) $this->value,
            'min_order_amount' => $this->min_order_amount ? (float) $this->min_order_amount : null,
            'max_uses'         => $this->max_uses,
            'used_count'       => $this->used_count,
            'remaining_uses'   => $this->max_uses !== null ? max(0, $this->max_uses - $this->used_count) : null,
            'expires_at'       => $this->expires_at?->toDateTimeString(),
            'is_active'        => (bool) $this->is_active,
            'is_valid'         => $this->isValid(),
            'created_at'       => $this->created_at?->toDateTimeString(),
        ];
    }
}

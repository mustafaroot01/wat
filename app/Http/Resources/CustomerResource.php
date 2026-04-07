<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'first_name'   => $this->first_name,
            'last_name'    => $this->last_name,
            'full_name'    => $this->full_name,
            'phone'        => $this->phone,
            'gender'       => $this->gender,
            'birth_date'   => $this->birth_date?->format('Y-m-d'),
            'district_id'  => $this->district_id,
            'area_id'      => $this->area_id,
            'district'     => $this->whenLoaded('district', fn() => [
                'id'   => $this->district->id,
                'name' => $this->district->name,
            ]),
            'area'         => $this->whenLoaded('area', fn() => [
                'id'   => $this->area->id,
                'name' => $this->area->name,
            ]),
            'is_active'    => $this->is_active,
            'is_deleted'   => !is_null($this->deleted_at),
            'deleted_at'   => $this->deleted_at?->format('Y-m-d H:i'),
            'created_at'   => $this->created_at?->format('Y-m-d'),
        ];
    }
}

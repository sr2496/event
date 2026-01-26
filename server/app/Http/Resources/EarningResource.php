<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EarningResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gross_amount' => (float) $this->gross_amount,
            'platform_commission' => (float) $this->platform_commission,
            'net_amount' => (float) $this->net_amount,
            'status' => $this->status,
            'available_at' => $this->available_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),

            // Event info
            'event' => $this->when($this->relationLoaded('event'), [
                'id' => $this->event->id,
                'title' => $this->event->title,
                'type' => $this->event->type,
                'event_date' => $this->event->event_date->toDateString(),
                'status' => $this->event->status,
            ]),

            // Payout info if paid
            'payout' => $this->when($this->payout_id && $this->relationLoaded('payout'), [
                'id' => $this->payout?->id,
                'reference' => $this->payout?->reference,
                'status' => $this->payout?->status,
            ]),
        ];
    }
}

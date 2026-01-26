<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayoutResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'amount' => (float) $this->amount,
            'processing_fee' => (float) $this->processing_fee,
            'net_amount' => (float) $this->net_amount,
            'status' => $this->status,
            'status_badge' => $this->getStatusBadge(),

            'payment_method' => $this->payment_method,
            'payment_details' => $this->when(
                $request->user()?->isAdmin() || $request->user()?->vendor?->id === $this->vendor_id,
                $this->payment_details
            ),
            'transaction_reference' => $this->transaction_reference,

            'failure_reason' => $this->when($this->status === 'failed', $this->failure_reason),
            'admin_notes' => $this->when($request->user()?->isAdmin(), $this->admin_notes),

            'processed_at' => $this->processed_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),

            // Include vendor info for admin views
            'vendor' => $this->when($this->relationLoaded('vendor'), [
                'id' => $this->vendor->id,
                'business_name' => $this->vendor->business_name,
                'slug' => $this->vendor->slug,
            ]),

            // Include processor info
            'processed_by' => $this->when($this->relationLoaded('processedBy') && $this->processedBy, [
                'id' => $this->processedBy?->id,
                'name' => $this->processedBy?->name,
            ]),

            // Include earnings count
            'earnings_count' => $this->when($this->relationLoaded('earnings'), $this->earnings->count()),
        ];
    }
}

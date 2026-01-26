<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),
            'title' => $this->title,
            'description' => $this->description,

            // Amounts
            'subtotal' => (float) $this->subtotal,
            'discount_amount' => (float) $this->discount_amount,
            'discount_description' => $this->discount_description,
            'tax_rate' => (float) $this->tax_rate,
            'tax_amount' => (float) $this->tax_amount,
            'total_amount' => (float) $this->total_amount,
            'currency' => $this->currency,
            'formatted_total' => $this->getFormattedTotal(),

            // Status
            'status' => $this->status,
            'status_color' => $this->getStatusColor(),

            // Dates
            'issued_at' => $this->issued_at?->toIso8601String(),
            'due_at' => $this->due_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'formatted_issued_at' => $this->issued_at?->format('M d, Y'),
            'formatted_due_at' => $this->due_at?->format('M d, Y'),
            'formatted_paid_at' => $this->paid_at?->format('M d, Y'),

            // Line items
            'line_items' => $this->line_items,
            'billing_address' => $this->billing_address,
            'notes' => $this->notes,

            // PDF
            'has_pdf' => !empty($this->pdf_path),
            'pdf_generated_at' => $this->pdf_generated_at?->toIso8601String(),

            // Relations
            'event' => $this->whenLoaded('event', fn() => [
                'id' => $this->event->id,
                'title' => $this->event->title,
                'type' => $this->event->type,
                'event_date' => $this->event->event_date->toDateString(),
                'city' => $this->event->city,
                'status' => $this->event->status,
            ]),
            'user' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'vendor' => $this->whenLoaded('vendor', fn() => [
                'id' => $this->vendor->id,
                'business_name' => $this->vendor->business_name,
            ]),

            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    protected function getTypeLabel(): string
    {
        return match ($this->type) {
            'proforma' => 'Proforma Invoice',
            'booking' => 'Booking Invoice',
            'receipt' => 'Payment Receipt',
            'vendor_earning' => 'Vendor Earning Statement',
            default => 'Invoice',
        };
    }

    protected function getStatusColor(): string
    {
        return match ($this->status) {
            'draft' => 'yellow',
            'issued' => 'blue',
            'paid' => 'green',
            'cancelled' => 'red',
            'refunded' => 'purple',
            default => 'gray',
        };
    }
}

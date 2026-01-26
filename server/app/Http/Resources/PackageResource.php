<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
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
            'vendor_id' => $this->vendor_id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'formatted_price' => '₹' . number_format($this->price),
            'compare_price' => $this->compare_price ? (float) $this->compare_price : null,
            'formatted_compare_price' => $this->compare_price ? '₹' . number_format($this->compare_price) : null,
            'has_discount' => $this->hasDiscount(),
            'discount_percentage' => $this->getDiscountPercentage(),
            'savings_amount' => $this->getSavingsAmount(),
            'duration_hours' => $this->duration_hours,
            'formatted_duration' => $this->getFormattedDuration(),
            'features' => $this->features ?? [],
            'deliverables' => $this->deliverables ?? [],
            'max_revisions' => $this->max_revisions,
            'delivery_days' => $this->delivery_days,
            'is_featured' => $this->is_featured,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'bookings_count' => $this->bookings_count,
            'vendor' => $this->whenLoaded('vendor', fn() => [
                'id' => $this->vendor->id,
                'business_name' => $this->vendor->business_name,
                'slug' => $this->vendor->slug,
                'category' => $this->vendor->category,
                'average_rating' => $this->vendor->average_rating,
            ]),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

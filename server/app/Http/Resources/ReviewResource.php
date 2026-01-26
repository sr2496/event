<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'title' => $this->title,
            'comment' => $this->comment,
            'is_verified' => $this->is_verified,
            'created_at' => $this->created_at->toISOString(),
            'time_ago' => $this->created_at->diffForHumans(),

            // Client info (reviewer)
            'client' => [
                'id' => $this->client->id,
                'name' => $this->client->name,
                'avatar' => $this->client->avatar,
            ],

            // Event info
            'event' => [
                'id' => $this->event->id,
                'title' => $this->event->title,
                'type' => $this->event->type,
                'event_date' => $this->event->event_date->toDateString(),
            ],

            // Vendor response
            'vendor_response' => $this->vendor_response,
            'vendor_responded_at' => $this->vendor_responded_at?->toISOString(),
            'has_vendor_response' => $this->hasVendorResponse(),

            // Conditionally include vendor (when viewing from client perspective)
            'vendor' => $this->when($this->relationLoaded('vendor') && $request->routeIs('client.*'), [
                'id' => $this->vendor->id,
                'business_name' => $this->vendor->business_name,
                'slug' => $this->vendor->slug,
            ]),
        ];
    }
}

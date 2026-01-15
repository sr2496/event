<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => $this->when($this->relationLoaded('user'), function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'avatar' => $this->user->avatar ? asset('storage/' . $this->user->avatar) : null,
                ];
            }),
            'business_name' => $this->business_name,
            'slug' => $this->slug,
            'category' => $this->category,
            'city' => $this->city,
            'service_radius_km' => $this->service_radius_km,
            'experience_years' => $this->experience_years,
            'price_range' => $this->getPriceRange(),
            'min_price' => $this->min_price,
            'max_price' => $this->max_price,
            'description' => $this->description,
            'is_verified' => $this->is_verified,
            'accepts_emergency' => $this->accepts_emergency,
            'backup_ready' => $this->backup_ready,
            
            // Reliability Section - Core Differentiator
            'reliability' => [
                'score' => number_format($this->reliability_score, 1),
                'badge' => $this->getReliabilityBadge(),
                'total_events' => $this->total_events_completed,
                'cancellations' => $this->cancellations_count,
                'no_shows' => $this->no_shows_count,
                'emergency_accepts' => $this->emergency_accepts_count,
                'avg_response_minutes' => $this->avg_response_minutes,
            ],
            
            'profile' => $this->when($this->relationLoaded('profile') && $this->profile, function () {
                return [
                    'profile_image' => $this->profile->profile_image ? asset('storage/' . $this->profile->profile_image) : null,
                    'cover_image' => $this->profile->cover_image ? asset('storage/' . $this->profile->cover_image) : null,
                    'phone' => $this->profile->phone,
                    'whatsapp' => $this->profile->whatsapp,
                    'email' => $this->profile->email,
                    'instagram' => $this->profile->instagram,
                    'services_offered' => $this->profile->services_offered,
                ];
            }),
            
            'portfolio' => $this->when($this->relationLoaded('portfolios'), function () {
                return $this->portfolios->map(fn($p) => [
                    'id' => $p->id,
                    'title' => $p->title,
                    'image' => asset('storage/' . $p->image_path),
                    'thumbnail' => $p->thumbnail_path ? asset('storage/' . $p->thumbnail_path) : asset('storage/' . $p->image_path),
                    'is_featured' => $p->is_featured,
                ]);
            }),
            
            'verified_at' => $this->verified_at,
            'created_at' => $this->created_at,
        ];
    }
}

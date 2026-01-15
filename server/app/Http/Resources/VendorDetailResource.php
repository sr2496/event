<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar ? asset('storage/' . $this->user->avatar) : null,
            ],
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
            
            // Full Profile
            'profile' => $this->when($this->profile, function () {
                return [
                    'profile_image' => $this->profile->profile_image ? asset('storage/' . $this->profile->profile_image) : null,
                    'cover_image' => $this->profile->cover_image ? asset('storage/' . $this->profile->cover_image) : null,
                    'phone' => $this->profile->phone,
                    'whatsapp' => $this->profile->whatsapp,
                    'email' => $this->profile->email,
                    'website' => $this->profile->website,
                    'instagram' => $this->profile->instagram,
                    'facebook' => $this->profile->facebook,
                    'services_offered' => $this->profile->services_offered ?? [],
                    'equipment_list' => $this->profile->equipment_list ?? [],
                    'terms_conditions' => $this->profile->terms_conditions,
                    'cancellation_policy' => $this->profile->cancellation_policy,
                ];
            }),
            
            // Full Portfolio
            'portfolio' => $this->portfolios->map(fn($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'description' => $p->description,
                'image' => asset('storage/' . $p->image_path),
                'thumbnail' => $p->thumbnail_path ? asset('storage/' . $p->thumbnail_path) : asset('storage/' . $p->image_path),
                'type' => $p->type,
                'is_featured' => $p->is_featured,
            ]),
            
            // Trust Timeline (Real Event History)
            'trust_timeline' => $this->when($this->relationLoaded('reliabilityLogs'), function () {
                return $this->reliabilityLogs->map(fn($log) => [
                    'action' => $log->action,
                    'score_change' => $log->score_change,
                    'date' => $log->created_at->format('M d, Y'),
                    'details' => $log->details,
                ]);
            }),
            
            'verified_at' => $this->verified_at,
            'created_at' => $this->created_at,
        ];
    }
}

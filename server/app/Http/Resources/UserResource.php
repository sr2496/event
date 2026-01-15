<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->getRoleNames()->first(), // Get primary role from Spatie
            'roles' => $this->getRoleNames(), // All roles
            'permissions' => $this->getAllPermissions()->pluck('name'), // All permissions
            'phone' => $this->phone,
            'avatar' => $this->avatar ? asset('storage/' . $this->avatar) : null,
            'city' => $this->city,
            'is_active' => $this->is_active,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'vendor' => $this->when($this->relationLoaded('vendor') && $this->vendor, function () {
                return [
                    'id' => $this->vendor->id,
                    'business_name' => $this->vendor->business_name,
                    'slug' => $this->vendor->slug,
                    'is_verified' => $this->vendor->is_verified,
                    'reliability_score' => $this->vendor->reliability_score,
                ];
            }),
        ];
    }
}

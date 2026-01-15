<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'event_date' => $this->event_date->format('Y-m-d'),
            'event_date_formatted' => $this->event_date->format('M d, Y'),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'venue' => $this->venue,
            'city' => $this->city,
            'description' => $this->description,
            'expected_guests' => $this->expected_guests,
            'status' => $this->status,
            'has_emergency' => $this->has_emergency,
            'is_upcoming' => $this->isUpcoming(),
            'can_trigger_emergency' => $this->canTriggerEmergency(),
            
            'financials' => [
                'total_amount' => $this->total_amount,
                'assurance_fee' => $this->assurance_fee,
                'platform_commission' => $this->platform_commission,
            ],
            
            'client' => $this->when($this->relationLoaded('client'), function () {
                return [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                    'email' => $this->client->email,
                    'phone' => $this->client->phone,
                ];
            }),
            
            'vendors' => $this->when($this->relationLoaded('eventVendors'), function () {
                return $this->eventVendors->map(fn($ev) => [
                    'id' => $ev->id,
                    'role' => $ev->role,
                    'status' => $ev->status,
                    'agreed_price' => $ev->agreed_price,
                    'advance_paid' => $ev->advance_paid,
                    'vendor' => $ev->vendor ? [
                        'id' => $ev->vendor->id,
                        'business_name' => $ev->vendor->business_name,
                        'slug' => $ev->vendor->slug,
                        'category' => $ev->vendor->category,
                        'reliability_score' => $ev->vendor->reliability_score,
                        'user' => $ev->vendor->user ? [
                            'name' => $ev->vendor->user->name,
                            'avatar' => $ev->vendor->user->avatar ? asset('storage/' . $ev->vendor->user->avatar) : null,
                        ] : null,
                    ] : null,
                    'confirmed_at' => $ev->confirmed_at,
                ]);
            }),
            
            'payments' => $this->when($this->relationLoaded('payments'), function () {
                return $this->payments->map(fn($p) => [
                    'id' => $p->id,
                    'type' => $p->type,
                    'amount' => $p->amount,
                    'status' => $p->status,
                    'paid_at' => $p->paid_at,
                ]);
            }),
            
            'emergency_requests' => $this->when($this->relationLoaded('emergencyRequests'), function () {
                return $this->emergencyRequests->map(fn($er) => [
                    'id' => $er->id,
                    'status' => $er->status,
                    'failure_reason' => $er->failure_reason,
                    'resolution_time_minutes' => $er->resolution_time_minutes,
                    'assigned_backup' => $er->assignedBackup ? [
                        'id' => $er->assignedBackup->id,
                        'business_name' => $er->assignedBackup->business_name,
                    ] : null,
                    'created_at' => $er->created_at,
                ]);
            }),
            
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'title',
        'type',
        'event_date',
        'start_time',
        'end_time',
        'venue',
        'city',
        'description',
        'expected_guests',
        'status',
        'total_amount',
        'assurance_fee',
        'platform_commission',
        'has_emergency',
        'completed_at',
    ];

    protected $casts = [
        'event_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'total_amount' => 'decimal:2',
        'assurance_fee' => 'decimal:2',
        'platform_commission' => 'decimal:2',
        'has_emergency' => 'boolean',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function eventVendors(): HasMany
    {
        return $this->hasMany(EventVendor::class);
    }

    public function primaryVendor(): HasOne
    {
        return $this->hasOne(EventVendor::class)->where('role', 'primary');
    }

    public function backupVendors(): HasMany
    {
        return $this->hasMany(EventVendor::class)->where('role', 'backup');
    }

    public function backupAssignments(): HasMany
    {
        return $this->hasMany(BackupAssignment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function emergencyRequests(): HasMany
    {
        return $this->hasMany(EmergencyRequest::class);
    }

    // Scopes
    public function scopeUpcoming($query)
    {
        return $query->where('event_date', '>=', now()->toDateString())
                     ->whereIn('status', ['pending', 'confirmed']);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByCity($query, $city)
    {
        return $query->where('city', $city);
    }

    public function scopeInDateRange($query, $start, $end)
    {
        return $query->whereBetween('event_date', [$start, $end]);
    }

    // Helpers
    public function isUpcoming(): bool
    {
        return $this->event_date >= now()->toDateString() && 
               in_array($this->status, ['pending', 'confirmed']);
    }

    public function canTriggerEmergency(): bool
    {
        return $this->status === 'confirmed' && 
               $this->event_date >= now()->toDateString() &&
               !$this->has_emergency;
    }

    public function markAsEmergency(): void
    {
        $this->update([
            'status' => 'emergency',
            'has_emergency' => true,
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Update vendor stats
        foreach ($this->eventVendors as $eventVendor) {
            if ($eventVendor->status === 'confirmed') {
                $eventVendor->vendor->increment('total_events_completed');
                $eventVendor->vendor->updateReliabilityScore(0.1, 'event_completed', $this->id);
                $eventVendor->update(['status' => 'completed']);
            }
        }
    }
}

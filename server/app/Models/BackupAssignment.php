<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'event_vendor_id',
        'backup_vendor_id',
        'priority_order',
        'status',
        'notified_at',
        'responded_at',
        'response_time_minutes',
        'offered_price',
        'rejection_reason',
    ];

    protected $casts = [
        'notified_at' => 'datetime',
        'responded_at' => 'datetime',
        'offered_price' => 'decimal:2',
    ];

    // Relationships
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function eventVendor(): BelongsTo
    {
        return $this->belongsTo(EventVendor::class);
    }

    public function backupVendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'backup_vendor_id');
    }

    // Scopes
    public function scopeStandby($query)
    {
        return $query->where('status', 'standby');
    }

    public function scopeNotified($query)
    {
        return $query->where('status', 'notified');
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority_order');
    }

    // Helpers
    public function notify(): void
    {
        $this->update([
            'status' => 'notified',
            'notified_at' => now(),
        ]);
        // Notification is dispatched in EmergencyService::notifyNextBackup()
    }

    public function accept(): void
    {
        $responseTime = $this->notified_at ? now()->diffInMinutes($this->notified_at) : null;
        
        $this->update([
            'status' => 'accepted',
            'responded_at' => now(),
            'response_time_minutes' => $responseTime,
        ]);

        // Update vendor stats
        $this->backupVendor->increment('emergency_accepts_count');
        $this->backupVendor->updateReliabilityScore(0.3, 'emergency_accepted', $this->event_id);

        // Update avg response time
        $vendor = $this->backupVendor;
        $newAvg = (($vendor->avg_response_minutes * $vendor->emergency_accepts_count) + $responseTime) 
                  / ($vendor->emergency_accepts_count + 1);
        $vendor->update(['avg_response_minutes' => round($newAvg)]);
    }

    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'responded_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->backupVendor->updateReliabilityScore(-0.1, 'emergency_rejected', $this->event_id);
    }
}

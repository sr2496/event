<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventVendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'vendor_id',
        'role',
        'status',
        'agreed_price',
        'advance_paid',
        'final_payment',
        'special_requirements',
        'confirmed_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'agreed_price' => 'decimal:2',
        'advance_paid' => 'decimal:2',
        'final_payment' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // Relationships
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function backupAssignments(): HasMany
    {
        return $this->hasMany(BackupAssignment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Scopes
    public function scopePrimary($query)
    {
        return $query->where('role', 'primary');
    }

    public function scopeBackup($query)
    {
        return $query->where('role', 'backup');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    // Helpers
    public function confirm(): void
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        // Mark date as booked for vendor
        VendorAvailability::updateOrCreate(
            ['vendor_id' => $this->vendor_id, 'date' => $this->event->event_date],
            ['status' => 'booked']
        );
    }

    public function cancel(string $reason): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        // Update vendor reliability
        $this->vendor->increment('cancellations_count');
        $this->vendor->updateReliabilityScore(-0.5, 'event_cancelled_by_vendor', $this->event_id);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);
        $this->vendor->increment('no_shows_count');
        $this->vendor->updateReliabilityScore(-1.0, 'no_show', $this->event_id);
    }
}

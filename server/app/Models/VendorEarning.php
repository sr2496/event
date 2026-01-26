<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorEarning extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'event_id',
        'event_vendor_id',
        'gross_amount',
        'platform_commission',
        'net_amount',
        'status',
        'available_at',
        'payout_id',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'platform_commission' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'available_at' => 'datetime',
    ];

    // Relationships
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function eventVendor(): BelongsTo
    {
        return $this->belongsTo(EventVendor::class);
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(Payout::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['pending', 'available']);
    }

    // Helpers
    public function markAsAvailable(): void
    {
        $this->update([
            'status' => 'available',
            'available_at' => now(),
        ]);

        // Update vendor's available balance
        $this->vendor->increment('available_balance', $this->net_amount);
        $this->vendor->decrement('pending_balance', $this->net_amount);
    }

    public function markAsPaid(Payout $payout): void
    {
        $this->update([
            'status' => 'paid',
            'payout_id' => $payout->id,
        ]);
    }
}

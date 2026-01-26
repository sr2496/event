<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'reference',
        'amount',
        'processing_fee',
        'net_amount',
        'status',
        'payment_method',
        'payment_details',
        'transaction_reference',
        'processed_by',
        'processed_at',
        'admin_notes',
        'failure_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processing_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'payment_details' => 'array',
        'processed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Payout $payout) {
            if (empty($payout->reference)) {
                $payout->reference = 'PAY-' . strtoupper(Str::random(8));
            }
            if (empty($payout->net_amount)) {
                $payout->net_amount = $payout->amount - ($payout->processing_fee ?? 0);
            }
        });
    }

    // Relationships
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(VendorEarning::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // Helpers
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending']);
    }

    public function canBeProcessed(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    public function markAsProcessing(User $admin): void
    {
        $this->update([
            'status' => 'processing',
            'processed_by' => $admin->id,
        ]);
    }

    public function markAsCompleted(string $transactionReference, ?string $notes = null): void
    {
        $this->update([
            'status' => 'completed',
            'transaction_reference' => $transactionReference,
            'processed_at' => now(),
            'admin_notes' => $notes,
        ]);

        // Update vendor balance
        $this->vendor->decrement('available_balance', $this->amount);
        $this->vendor->increment('total_withdrawn', $this->net_amount);

        // Mark associated earnings as paid
        $this->earnings()->update(['status' => 'paid']);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
            'processed_at' => now(),
        ]);

        // Return earnings to available status
        $this->earnings()->update([
            'status' => 'available',
            'payout_id' => null,
        ]);
    }

    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'admin_notes' => $reason,
        ]);

        // Return earnings to available status
        $this->earnings()->update([
            'status' => 'available',
            'payout_id' => null,
        ]);
    }

    /**
     * Get status badge color for UI.
     */
    public function getStatusBadge(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'processing' => 'info',
            'completed' => 'success',
            'failed' => 'danger',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }
}

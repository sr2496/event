<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penalty extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'event_id',
        'type',
        'amount',
        'score_penalty',
        'reason',
        'status',
        'appeal_reason',
        'applied_by',
        'applied_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'score_penalty' => 'decimal:2',
        'applied_at' => 'datetime',
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

    public function appliedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    // Helpers
    public function apply(): void
    {
        $this->update([
            'status' => 'applied',
            'applied_at' => now(),
        ]);

        if ($this->score_penalty > 0) {
            $this->vendor->updateReliabilityScore(-$this->score_penalty, 'policy_violation', $this->event_id);
        }
    }

    public function waive(): void
    {
        $this->update(['status' => 'waived']);
    }
}

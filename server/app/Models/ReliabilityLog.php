<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReliabilityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'event_id',
        'action',
        'score_change',
        'score_before',
        'score_after',
        'details',
        'admin_id',
    ];

    protected $casts = [
        'score_change' => 'decimal:2',
        'score_before' => 'decimal:2',
        'score_after' => 'decimal:2',
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

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    // Scopes
    public function scopePositive($query)
    {
        return $query->where('score_change', '>', 0);
    }

    public function scopeNegative($query)
    {
        return $query->where('score_change', '<', 0);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }
}

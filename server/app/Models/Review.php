<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'vendor_id',
        'client_id',
        'rating',
        'title',
        'comment',
        'vendor_response',
        'vendor_responded_at',
        'is_verified',
        'is_visible',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_verified' => 'boolean',
        'is_visible' => 'boolean',
        'vendor_responded_at' => 'datetime',
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

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    // Scopes
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeByRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    public function scopeWithResponse($query)
    {
        return $query->whereNotNull('vendor_response');
    }

    public function scopeWithoutResponse($query)
    {
        return $query->whereNull('vendor_response');
    }

    // Helpers
    public function hasVendorResponse(): bool
    {
        return !is_null($this->vendor_response);
    }

    public function addVendorResponse(string $response): void
    {
        $this->update([
            'vendor_response' => $response,
            'vendor_responded_at' => now(),
        ]);
    }

    /**
     * Boot method to update vendor rating when review is created/updated/deleted.
     */
    protected static function booted(): void
    {
        static::created(function (Review $review) {
            $review->vendor->updateAverageRating();
        });

        static::updated(function (Review $review) {
            if ($review->isDirty(['rating', 'is_visible'])) {
                $review->vendor->updateAverageRating();
            }
        });

        static::deleted(function (Review $review) {
            $review->vendor->updateAverageRating();
        });
    }
}

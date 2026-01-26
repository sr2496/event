<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'business_name',
        'slug',
        'category',
        'city',
        'service_radius_km',
        'experience_years',
        'min_price',
        'max_price',
        'description',
        'identity_proof',
        'is_verified',
        'is_active',
        'accepts_emergency',
        'reliability_score',
        'average_rating',
        'total_reviews',
        'total_events_completed',
        'cancellations_count',
        'no_shows_count',
        'emergency_accepts_count',
        'avg_response_minutes',
        'backup_ready',
        'verified_at',
        'bank_details',
        'pending_balance',
        'available_balance',
        'total_withdrawn',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'accepts_emergency' => 'boolean',
        'backup_ready' => 'boolean',
        'reliability_score' => 'decimal:2',
        'average_rating' => 'decimal:1',
        'total_reviews' => 'integer',
        'min_price' => 'decimal:2',
        'max_price' => 'decimal:2',
        'verified_at' => 'datetime',
        'bank_details' => 'array',
        'pending_balance' => 'decimal:2',
        'available_balance' => 'decimal:2',
        'total_withdrawn' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($vendor) {
            if (empty($vendor->slug)) {
                $vendor->slug = Str::slug($vendor->business_name) . '-' . Str::random(6);
            }
        });
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(VendorProfile::class);
    }

    public function portfolios(): HasMany
    {
        return $this->hasMany(VendorPortfolio::class);
    }

    public function availability(): HasMany
    {
        return $this->hasMany(VendorAvailability::class);
    }

    public function eventVendors(): HasMany
    {
        return $this->hasMany(EventVendor::class);
    }

    public function backupAssignments(): HasMany
    {
        return $this->hasMany(BackupAssignment::class, 'backup_vendor_id');
    }

    public function reliabilityLogs(): HasMany
    {
        return $this->hasMany(ReliabilityLog::class);
    }

    public function penalties(): HasMany
    {
        return $this->hasMany(Penalty::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function visibleReviews(): HasMany
    {
        return $this->hasMany(Review::class)->visible()->latest();
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(VendorEarning::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(VendorPackage::class);
    }

    public function activePackages(): HasMany
    {
        return $this->hasMany(VendorPackage::class)->active()->ordered();
    }

    // Scopes
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByCity($query, $city)
    {
        return $query->where('city', $city);
    }

    public function scopeBackupReady($query)
    {
        return $query->where('backup_ready', true)->where('accepts_emergency', true);
    }

    public function scopeHighReliability($query, $minScore = 4.0)
    {
        return $query->where('reliability_score', '>=', $minScore);
    }

    public function scopeMinRating($query, $minRating = 4.0)
    {
        return $query->where('average_rating', '>=', $minRating);
    }

    public function scopeHasReviews($query)
    {
        return $query->where('total_reviews', '>', 0);
    }

    // Helpers
    public function isAvailableOn($date): bool
    {
        $availability = $this->availability()->where('date', $date)->first();
        return !$availability || $availability->status === 'available';
    }

    public function getReliabilityBadge(): string
    {
        if ($this->reliability_score >= 4.5) return 'excellent';
        if ($this->reliability_score >= 4.0) return 'good';
        if ($this->reliability_score >= 3.0) return 'average';
        return 'poor';
    }

    public function getPriceRange(): string
    {
        if (!$this->min_price && !$this->max_price) return 'Contact for pricing';
        if ($this->min_price && $this->max_price) {
            return '₹' . number_format($this->min_price) . ' - ₹' . number_format($this->max_price);
        }
        return '₹' . number_format($this->min_price ?? $this->max_price) . '+';
    }

    public function updateReliabilityScore(float $change, string $action, ?int $eventId = null): void
    {
        $scoreBefore = $this->reliability_score;
        $newScore = max(0, min(5, $this->reliability_score + $change));
        
        $this->update(['reliability_score' => $newScore]);

        ReliabilityLog::create([
            'vendor_id' => $this->id,
            'event_id' => $eventId,
            'action' => $action,
            'score_change' => $change,
            'score_before' => $scoreBefore,
            'score_after' => $newScore,
        ]);
    }

    /**
     * Recalculate and update the vendor's average rating from visible reviews.
     */
    public function updateAverageRating(): void
    {
        $stats = $this->reviews()
            ->visible()
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total')
            ->first();

        $this->update([
            'average_rating' => round($stats->avg_rating ?? 0, 1),
            'total_reviews' => $stats->total ?? 0,
        ]);
    }

    /**
     * Get rating distribution for display.
     */
    public function getRatingDistribution(): array
    {
        $distribution = $this->reviews()
            ->visible()
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        // Ensure all ratings 1-5 are present
        return [
            5 => $distribution[5] ?? 0,
            4 => $distribution[4] ?? 0,
            3 => $distribution[3] ?? 0,
            2 => $distribution[2] ?? 0,
            1 => $distribution[1] ?? 0,
        ];
    }

    /**
     * Get rating badge based on average rating.
     */
    public function getRatingBadge(): string
    {
        if ($this->total_reviews === 0) return 'new';
        if ($this->average_rating >= 4.5) return 'excellent';
        if ($this->average_rating >= 4.0) return 'very_good';
        if ($this->average_rating >= 3.5) return 'good';
        if ($this->average_rating >= 3.0) return 'average';
        return 'below_average';
    }

    // ========== Earnings & Payout Methods ==========

    /**
     * Get total lifetime earnings (gross).
     */
    public function getTotalEarnings(): float
    {
        return (float) $this->earnings()->sum('gross_amount');
    }

    /**
     * Get total net earnings after commission.
     */
    public function getTotalNetEarnings(): float
    {
        return (float) $this->earnings()->sum('net_amount');
    }

    /**
     * Get total commission paid to platform.
     */
    public function getTotalCommissionPaid(): float
    {
        return (float) $this->earnings()->sum('platform_commission');
    }

    /**
     * Check if vendor has bank details configured.
     */
    public function hasBankDetails(): bool
    {
        return !empty($this->bank_details);
    }

    /**
     * Check if vendor can request a payout.
     */
    public function canRequestPayout(): bool
    {
        return $this->hasBankDetails()
            && $this->available_balance > 0
            && !$this->payouts()->whereIn('status', ['pending', 'processing'])->exists();
    }

    /**
     * Get the minimum payout amount (could be from settings).
     */
    public static function getMinimumPayoutAmount(): float
    {
        return 500.00; // Minimum payout is ₹500
    }

    /**
     * Recalculate and sync balance from earnings.
     */
    public function recalculateBalance(): void
    {
        $pending = $this->earnings()->pending()->sum('net_amount');
        $available = $this->earnings()->available()->sum('net_amount');
        $withdrawn = $this->payouts()->completed()->sum('net_amount');

        $this->update([
            'pending_balance' => $pending,
            'available_balance' => $available,
            'total_withdrawn' => $withdrawn,
        ]);
    }

    /**
     * Get earnings summary for dashboard.
     */
    public function getEarningsSummary(): array
    {
        return [
            'total_gross' => $this->getTotalEarnings(),
            'total_net' => $this->getTotalNetEarnings(),
            'total_commission' => $this->getTotalCommissionPaid(),
            'pending_balance' => (float) $this->pending_balance,
            'available_balance' => (float) $this->available_balance,
            'total_withdrawn' => (float) $this->total_withdrawn,
            'can_request_payout' => $this->canRequestPayout(),
            'has_bank_details' => $this->hasBankDetails(),
            'minimum_payout' => self::getMinimumPayoutAmount(),
        ];
    }
}

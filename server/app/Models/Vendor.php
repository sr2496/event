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
        'total_events_completed',
        'cancellations_count',
        'no_shows_count',
        'emergency_accepts_count',
        'avg_response_minutes',
        'backup_ready',
        'verified_at',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'accepts_emergency' => 'boolean',
        'backup_ready' => 'boolean',
        'reliability_score' => 'decimal:2',
        'min_price' => 'decimal:2',
        'max_price' => 'decimal:2',
        'verified_at' => 'datetime',
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
}

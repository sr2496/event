<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'name',
        'description',
        'price',
        'compare_price',
        'duration_hours',
        'features',
        'deliverables',
        'max_revisions',
        'delivery_days',
        'is_featured',
        'is_active',
        'sort_order',
        'bookings_count',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'features' => 'array',
        'deliverables' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'package_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    public function scopePopular($query)
    {
        return $query->orderByDesc('bookings_count');
    }

    // Helpers
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isFeatured(): bool
    {
        return $this->is_featured;
    }

    public function hasDiscount(): bool
    {
        return $this->compare_price && $this->compare_price > $this->price;
    }

    public function getDiscountPercentage(): ?int
    {
        if (!$this->hasDiscount()) {
            return null;
        }

        return (int) round((($this->compare_price - $this->price) / $this->compare_price) * 100);
    }

    public function getSavingsAmount(): ?float
    {
        if (!$this->hasDiscount()) {
            return null;
        }

        return $this->compare_price - $this->price;
    }

    /**
     * Get formatted duration string.
     */
    public function getFormattedDuration(): ?string
    {
        if (!$this->duration_hours) {
            return null;
        }

        if ($this->duration_hours < 24) {
            return $this->duration_hours . ' hour' . ($this->duration_hours > 1 ? 's' : '');
        }

        $days = floor($this->duration_hours / 24);
        $hours = $this->duration_hours % 24;

        $result = $days . ' day' . ($days > 1 ? 's' : '');
        if ($hours > 0) {
            $result .= ' ' . $hours . ' hour' . ($hours > 1 ? 's' : '');
        }

        return $result;
    }

    /**
     * Create a snapshot of the package for booking records.
     */
    public function toSnapshot(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'duration_hours' => $this->duration_hours,
            'features' => $this->features,
            'deliverables' => $this->deliverables,
            'max_revisions' => $this->max_revisions,
            'delivery_days' => $this->delivery_days,
            'captured_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Increment bookings count.
     */
    public function incrementBookings(): void
    {
        $this->increment('bookings_count');
    }

    /**
     * Get the next sort order for a vendor's packages.
     */
    public static function getNextSortOrder(int $vendorId): int
    {
        return self::where('vendor_id', $vendorId)->max('sort_order') + 1;
    }

    /**
     * Reorder packages for a vendor.
     */
    public static function reorder(int $vendorId, array $packageIds): void
    {
        foreach ($packageIds as $index => $packageId) {
            self::where('id', $packageId)
                ->where('vendor_id', $vendorId)
                ->update(['sort_order' => $index]);
        }
    }
}

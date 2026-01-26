<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class VendorAvailability extends Model
{
    use HasFactory;

    protected $table = 'vendor_availability';

    protected $fillable = [
        'vendor_id',
        'date',
        'status',
        'note',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    const STATUS_AVAILABLE = 'available';
    const STATUS_BOOKED = 'booked';
    const STATUS_BLOCKED = 'blocked';
    const STATUS_TENTATIVE = 'tentative';

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public function scopeBooked($query)
    {
        return $query->where('status', self::STATUS_BOOKED);
    }

    public function scopeBlocked($query)
    {
        return $query->where('status', self::STATUS_BLOCKED);
    }

    public function scopeTentative($query)
    {
        return $query->where('status', self::STATUS_TENTATIVE);
    }

    public function scopeUnavailable($query)
    {
        return $query->whereIn('status', [self::STATUS_BOOKED, self::STATUS_BLOCKED]);
    }

    public function scopeOnDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeInMonth($query, int $year, int $month)
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        return $query->whereBetween('date', [$start, $end]);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', now()->toDateString());
    }

    // Helpers
    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    public function isBooked(): bool
    {
        return $this->status === self::STATUS_BOOKED;
    }

    public function isBlocked(): bool
    {
        return $this->status === self::STATUS_BLOCKED;
    }

    public function isTentative(): bool
    {
        return $this->status === self::STATUS_TENTATIVE;
    }

    public function canBeModified(): bool
    {
        // Booked dates cannot be manually changed
        return $this->status !== self::STATUS_BOOKED;
    }

    /**
     * Get status color for calendar UI.
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_AVAILABLE => 'green',
            self::STATUS_BOOKED => 'blue',
            self::STATUS_BLOCKED => 'red',
            self::STATUS_TENTATIVE => 'yellow',
            default => 'gray',
        };
    }

    /**
     * Set or update availability for a vendor on a specific date.
     */
    public static function setAvailability(int $vendorId, string $date, string $status, ?string $note = null): self
    {
        return self::updateOrCreate(
            ['vendor_id' => $vendorId, 'date' => $date],
            ['status' => $status, 'note' => $note]
        );
    }

    /**
     * Set availability for multiple dates at once.
     */
    public static function bulkSetAvailability(int $vendorId, array $dates, string $status, ?string $note = null): int
    {
        $count = 0;
        foreach ($dates as $date) {
            // Skip if date is already booked (can't change booked dates)
            $existing = self::where('vendor_id', $vendorId)
                ->where('date', $date)
                ->first();

            if ($existing && $existing->isBooked()) {
                continue;
            }

            self::setAvailability($vendorId, $date, $status, $note);
            $count++;
        }
        return $count;
    }

    /**
     * Block a date range for the vendor.
     */
    public static function blockDateRange(int $vendorId, string $startDate, string $endDate, ?string $note = null): int
    {
        $dates = [];
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($current->lte($end)) {
            $dates[] = $current->toDateString();
            $current->addDay();
        }

        return self::bulkSetAvailability($vendorId, $dates, self::STATUS_BLOCKED, $note);
    }

    /**
     * Clear availability entries (reset to default available).
     */
    public static function clearAvailability(int $vendorId, array $dates): int
    {
        return self::where('vendor_id', $vendorId)
            ->whereIn('date', $dates)
            ->where('status', '!=', self::STATUS_BOOKED) // Don't clear booked dates
            ->delete();
    }
}

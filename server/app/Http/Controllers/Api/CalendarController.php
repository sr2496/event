<?php

namespace App\Http\Controllers\Api;

/**
 * @group Vendor Calendar
 *
 * APIs for managing vendor availability calendar. Vendors can view, set, and manage their availability.
 * @authenticated
 */

use App\Http\Controllers\Controller;
use App\Models\VendorAvailability;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    /**
     * Get calendar month view
     *
     * Get availability and bookings for a specific month.
     *
     * @queryParam year integer required Year. Example: 2026
     * @queryParam month integer required Month (1-12). Example: 1
     */
    public function monthView(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $year = $validated['year'];
        $month = $validated['month'];

        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        // Get availability entries for the month
        $availability = $vendor->availability()
            ->inMonth($year, $month)
            ->get()
            ->keyBy(fn($item) => $item->date->toDateString());

        // Get bookings for the month
        $bookings = $vendor->eventVendors()
            ->with(['event' => fn($q) => $q->select('id', 'title', 'type', 'event_date', 'city', 'status')])
            ->whereHas('event', function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('event_date', [$startOfMonth, $endOfMonth])
                  ->whereNotIn('status', ['cancelled', 'rejected']);
            })
            ->get()
            ->groupBy(fn($item) => $item->event->event_date->toDateString());

        // Build calendar data
        $calendarDays = [];
        $current = $startOfMonth->copy();

        while ($current->lte($endOfMonth)) {
            $dateStr = $current->toDateString();
            $availabilityEntry = $availability->get($dateStr);
            $dayBookings = $bookings->get($dateStr, collect());

            $status = 'available'; // Default
            $note = null;

            if ($availabilityEntry) {
                $status = $availabilityEntry->status;
                $note = $availabilityEntry->note;
            }

            // If has confirmed booking, mark as booked
            if ($dayBookings->isNotEmpty()) {
                $hasConfirmed = $dayBookings->contains(fn($b) => in_array($b->status, ['confirmed', 'completed']));
                if ($hasConfirmed) {
                    $status = 'booked';
                }
            }

            $calendarDays[] = [
                'date' => $dateStr,
                'day' => $current->day,
                'day_of_week' => $current->dayOfWeek,
                'day_name' => $current->shortDayName,
                'is_past' => $current->lt(now()->startOfDay()),
                'is_today' => $current->isToday(),
                'status' => $status,
                'color' => $this->getStatusColor($status),
                'note' => $note,
                'bookings' => $dayBookings->map(fn($b) => [
                    'event_id' => $b->event->id,
                    'title' => $b->event->title,
                    'type' => $b->event->type,
                    'city' => $b->event->city,
                    'status' => $b->event->status,
                    'role' => $b->role,
                ])->values(),
                'has_booking' => $dayBookings->isNotEmpty(),
            ];

            $current->addDay();
        }

        // Summary stats
        $stats = [
            'total_days' => count($calendarDays),
            'available_days' => collect($calendarDays)->where('status', 'available')->where('is_past', false)->count(),
            'booked_days' => collect($calendarDays)->where('status', 'booked')->count(),
            'blocked_days' => collect($calendarDays)->where('status', 'blocked')->count(),
            'tentative_days' => collect($calendarDays)->where('status', 'tentative')->count(),
        ];

        return response()->json([
            'year' => $year,
            'month' => $month,
            'month_name' => $startOfMonth->monthName,
            'starts_on' => $startOfMonth->dayOfWeek,
            'days_in_month' => $startOfMonth->daysInMonth,
            'calendar' => $calendarDays,
            'stats' => $stats,
        ]);
    }

    /**
     * Get availability for date range
     *
     * Get availability entries for a specific date range.
     *
     * @queryParam start_date date required Start date. Example: 2026-01-01
     * @queryParam end_date date required End date. Example: 2026-01-31
     */
    public function dateRange(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $availability = $vendor->availability()
            ->inDateRange($validated['start_date'], $validated['end_date'])
            ->orderBy('date')
            ->get()
            ->map(fn($item) => [
                'date' => $item->date->toDateString(),
                'status' => $item->status,
                'note' => $item->note,
                'color' => $item->getStatusColor(),
                'can_modify' => $item->canBeModified(),
            ]);

        return response()->json([
            'data' => $availability,
        ]);
    }

    /**
     * Set availability for a single date
     *
     * Set or update availability status for a specific date.
     *
     * @bodyParam date date required The date. Example: 2026-02-15
     * @bodyParam status string required Status: available, blocked, tentative. Example: blocked
     * @bodyParam note string optional Note for this date. Example: Personal day off
     */
    public function setAvailability(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'status' => ['required', 'in:available,blocked,tentative'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        // Check if date is already booked
        $existing = $vendor->availability()
            ->where('date', $validated['date'])
            ->first();

        if ($existing && $existing->isBooked()) {
            return response()->json([
                'message' => 'Cannot modify availability for a booked date',
            ], 422);
        }

        $availability = VendorAvailability::setAvailability(
            $vendor->id,
            $validated['date'],
            $validated['status'],
            $validated['note'] ?? null
        );

        return response()->json([
            'message' => 'Availability updated successfully',
            'data' => [
                'date' => $availability->date->toDateString(),
                'status' => $availability->status,
                'note' => $availability->note,
                'color' => $availability->getStatusColor(),
            ],
        ]);
    }

    /**
     * Set availability for multiple dates
     *
     * Bulk update availability for multiple dates at once.
     *
     * @bodyParam dates array required Array of dates. Example: ["2026-02-15", "2026-02-16", "2026-02-17"]
     * @bodyParam status string required Status: available, blocked, tentative. Example: blocked
     * @bodyParam note string optional Note for these dates. Example: Vacation
     */
    public function bulkSetAvailability(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $validated = $request->validate([
            'dates' => ['required', 'array', 'min:1', 'max:31'],
            'dates.*' => ['required', 'date', 'after_or_equal:today'],
            'status' => ['required', 'in:available,blocked,tentative'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $updatedCount = VendorAvailability::bulkSetAvailability(
            $vendor->id,
            $validated['dates'],
            $validated['status'],
            $validated['note'] ?? null
        );

        $skippedCount = count($validated['dates']) - $updatedCount;

        return response()->json([
            'message' => 'Availability updated successfully',
            'updated_count' => $updatedCount,
            'skipped_count' => $skippedCount,
            'note' => $skippedCount > 0 ? 'Some dates were skipped because they are already booked' : null,
        ]);
    }

    /**
     * Block date range
     *
     * Block a range of dates (e.g., for vacation).
     *
     * @bodyParam start_date date required Start date. Example: 2026-02-15
     * @bodyParam end_date date required End date. Example: 2026-02-20
     * @bodyParam note string optional Reason for blocking. Example: Annual vacation
     */
    public function blockDateRange(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $validated = $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        // Limit range to 90 days
        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);

        if ($start->diffInDays($end) > 90) {
            return response()->json([
                'message' => 'Date range cannot exceed 90 days',
            ], 422);
        }

        $blockedCount = VendorAvailability::blockDateRange(
            $vendor->id,
            $validated['start_date'],
            $validated['end_date'],
            $validated['note'] ?? null
        );

        return response()->json([
            'message' => 'Date range blocked successfully',
            'blocked_count' => $blockedCount,
        ]);
    }

    /**
     * Clear availability
     *
     * Remove custom availability entries (reset to default available).
     *
     * @bodyParam dates array required Array of dates to clear. Example: ["2026-02-15", "2026-02-16"]
     */
    public function clearAvailability(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $validated = $request->validate([
            'dates' => ['required', 'array', 'min:1', 'max:31'],
            'dates.*' => ['required', 'date'],
        ]);

        $clearedCount = VendorAvailability::clearAvailability(
            $vendor->id,
            $validated['dates']
        );

        return response()->json([
            'message' => 'Availability cleared successfully',
            'cleared_count' => $clearedCount,
        ]);
    }

    /**
     * Get upcoming bookings
     *
     * Get list of upcoming confirmed bookings for calendar sidebar.
     *
     * @queryParam limit integer Number of bookings to return. Example: 10
     */
    public function upcomingBookings(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $limit = $request->get('limit', 10);

        $bookings = $vendor->eventVendors()
            ->with(['event' => fn($q) => $q->select('id', 'title', 'type', 'event_date', 'city', 'venue', 'status', 'client_id')])
            ->with(['event.client' => fn($q) => $q->select('id', 'name', 'phone')])
            ->whereHas('event', function ($q) {
                $q->where('event_date', '>=', now()->toDateString())
                  ->whereIn('status', ['confirmed', 'approved', 'awaiting_vendor']);
            })
            ->orderBy(
                Event::select('event_date')
                    ->whereColumn('events.id', 'event_vendors.event_id')
            )
            ->limit($limit)
            ->get()
            ->map(fn($b) => [
                'event_vendor_id' => $b->id,
                'event_id' => $b->event->id,
                'title' => $b->event->title,
                'type' => $b->event->type,
                'event_date' => $b->event->event_date->toDateString(),
                'formatted_date' => $b->event->event_date->format('M d, Y'),
                'days_until' => now()->startOfDay()->diffInDays($b->event->event_date, false),
                'city' => $b->event->city,
                'venue' => $b->event->venue,
                'status' => $b->event->status,
                'role' => $b->role,
                'client_name' => $b->event->client?->name,
                'client_phone' => $b->event->client?->phone,
                'agreed_price' => $b->agreed_price,
            ]);

        return response()->json([
            'data' => $bookings,
            'total' => $bookings->count(),
        ]);
    }

    /**
     * Get calendar summary
     *
     * Get summary statistics for the vendor's calendar.
     */
    public function summary(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $today = now()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();
        $nextMonth = now()->addMonth()->endOfMonth()->toDateString();

        // This month stats
        $thisMonthBookings = $vendor->eventVendors()
            ->whereHas('event', function ($q) use ($today, $endOfMonth) {
                $q->whereBetween('event_date', [$today, $endOfMonth])
                  ->whereIn('status', ['confirmed', 'approved']);
            })
            ->count();

        $thisMonthBlocked = $vendor->availability()
            ->blocked()
            ->whereBetween('date', [$today, $endOfMonth])
            ->count();

        // Next 30 days
        $next30DaysBookings = $vendor->eventVendors()
            ->whereHas('event', function ($q) {
                $q->whereBetween('event_date', [now()->toDateString(), now()->addDays(30)->toDateString()])
                  ->whereIn('status', ['confirmed', 'approved']);
            })
            ->count();

        // Pending requests
        $pendingRequests = $vendor->eventVendors()
            ->whereHas('event', function ($q) {
                $q->where('status', 'awaiting_vendor');
            })
            ->count();

        return response()->json([
            'this_month' => [
                'bookings' => $thisMonthBookings,
                'blocked_days' => $thisMonthBlocked,
            ],
            'next_30_days' => [
                'bookings' => $next30DaysBookings,
            ],
            'pending_requests' => $pendingRequests,
            'total_upcoming' => $vendor->eventVendors()
                ->whereHas('event', fn($q) => $q->upcoming())
                ->count(),
        ]);
    }

    /**
     * Get status color for calendar display.
     */
    private function getStatusColor(string $status): string
    {
        return match ($status) {
            'available' => 'green',
            'booked' => 'blue',
            'blocked' => 'red',
            'tentative' => 'yellow',
            default => 'gray',
        };
    }
}

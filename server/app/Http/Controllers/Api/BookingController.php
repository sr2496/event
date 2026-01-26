<?php

namespace App\Http\Controllers\Api;

/**
 * @group Bookings
 *
 * APIs for creating and managing event bookings. Clients can create bookings, confirm payments, and cancel events.
 */

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\Vendor;
use App\Models\EventVendor;
use App\Models\BackupAssignment;
use App\Models\Payment;
use App\Services\BookingService;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function index(Request $request)
    {
        $events = Event::with(['eventVendors.vendor.user', 'payments'])
            ->where('client_id', $request->user()->id)
            ->latest()
            ->paginate($request->get('per_page', 10));

        return EventResource::collection($events);
    }

    public function show(Request $request, $id)
    {
        $event = Event::with([
            'eventVendors.vendor.user',
            'eventVendors.vendor.profile',
            'payments',
            'emergencyRequests',
        ])
            ->where('client_id', $request->user()->id)
            ->findOrFail($id);

        return new EventResource($event);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:wedding,pre_wedding,corporate,influencer_shoot,birthday,anniversary,other'],
            'event_date' => ['required', 'date', 'after:today'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'venue' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'expected_guests' => ['nullable', 'integer', 'min:1'],
            'special_requirements' => ['nullable', 'string'],
        ]);

        $vendor = Vendor::verified()->active()->findOrFail($validated['vendor_id']);

        // Check availability
        if (!$vendor->isAvailableOn($validated['event_date'])) {
            return response()->json([
                'message' => 'Vendor is not available on the selected date',
            ], 422);
        }

        $booking = $this->bookingService->createBooking(
            $request->user(),
            $vendor,
            $validated
        );

        return response()->json([
            'message' => 'Booking created successfully',
            'event' => new EventResource($booking['event']->load(['eventVendors.vendor'])),
            'payment_details' => $booking['payment_details'],
        ], 201);
    }

    public function confirmPayment(Request $request, $id)
    {
        $event = Event::where('client_id', $request->user()->id)
            ->where('status', 'pending')
            ->findOrFail($id);

        $validated = $request->validate([
            'payment_method' => ['required', 'string'],
            'transaction_id' => ['required', 'string'],
        ]);

        $this->bookingService->confirmPayment($event, $validated);

        return response()->json([
            'message' => 'Payment confirmed successfully',
            'event' => new EventResource($event->fresh(['eventVendors.vendor'])),
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $event = Event::where('client_id', $request->user()->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->findOrFail($id);

        $validated = $request->validate([
            'reason' => ['required', 'string'],
        ]);

        $this->bookingService->cancelBooking($event, $validated['reason']);

        return response()->json([
            'message' => 'Booking cancelled successfully',
        ]);
    }

    public function markCompleted(Request $request, $id)
    {
        $event = Event::where('client_id', $request->user()->id)
            ->where('status', 'confirmed')
            ->where('event_date', '<=', now()->toDateString())
            ->findOrFail($id);

        $event->markAsCompleted();

        return response()->json([
            'message' => 'Event marked as completed successfully',
            'event' => new EventResource($event->fresh()),
        ]);
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();

        $stats = [
            'upcoming_count' => Event::where('client_id', $user->id)->upcoming()->count(),
            'completed_count' => Event::where('client_id', $user->id)->where('status', 'completed')->count(),
            'total_spent' => Event::where('client_id', $user->id)
                ->where('status', 'completed')
                ->sum(\DB::raw('total_amount + assurance_fee')),
        ];

        $upcomingEvents = Event::with(['eventVendors.vendor.user'])
            ->where('client_id', $user->id)
            ->upcoming()
            ->orderBy('event_date')
            ->limit(5)
            ->get();

        return response()->json([
            'stats' => $stats,
            'upcoming_events' => EventResource::collection($upcomingEvents),
        ]);
    }

    public function upcoming(Request $request)
    {
        $events = Event::with(['eventVendors.vendor.user'])
            ->where('client_id', $request->user()->id)
            ->upcoming()
            ->orderBy('event_date')
            ->get();

        return EventResource::collection($events);
    }
}

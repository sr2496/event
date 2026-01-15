<?php

namespace App\Http\Controllers\Api;

// ... (existing imports)
use App\Models\Event;
use App\Services\BookingService;

// ...

    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    // ... (append to class)

    public function getPendingRequests(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $requests = $vendor->eventVendors()
            ->with(['event.client', 'event.eventVendors.vendor'])
            ->where('role', 'primary')
            ->whereHas('event', function ($q) {
                $q->where('status', 'awaiting_vendor');
            })
            ->get()
            ->map(function ($ev) {
                return [
                    'id' => $ev->event->id,
                    'title' => $ev->event->title,
                    'type' => $ev->event->type,
                    'event_date' => $ev->event->event_date->toDateString(),
                    'city' => $ev->event->city,
                    'client_name' => $ev->event->client->name,
                    'budget' => $ev->agreed_price,
                    // calculate payout (minus commission)
                    'payout' => $ev->agreed_price * 0.90, 
                ];
            });

        return response()->json(['data' => $requests]);
    }

    public function acceptBooking(Request $request, $id)
    {
        $vendor = $request->user()->vendor;
        // Verify ownership
        $event = Event::findOrFail($id);
        $assignment = $event->eventVendors()
            ->where('vendor_id', $vendor->id)
            ->where('role', 'primary')
            ->firstOrFail();

        $this->bookingService->vendorAccept($event);

        return response()->json(['message' => 'Booking accepted successfully']);
    }

    public function rejectBooking(Request $request, $id)
    {
        $vendor = $request->user()->vendor;
        // Verify ownership
        $event = Event::findOrFail($id);
        $assignment = $event->eventVendors()
            ->where('vendor_id', $vendor->id)
            ->where('role', 'primary')
            ->firstOrFail();

        $validated = $request->validate(['reason' => 'required|string']);

        $this->bookingService->vendorReject($event, $validated['reason']);

        return response()->json(['message' => 'Booking rejected successfully']);
    }
}

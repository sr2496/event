<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VendorResource;
use App\Http\Resources\VendorDetailResource;
use App\Models\Vendor;
use App\Models\VendorProfile;
use App\Models\VendorPortfolio;
use App\Models\Category;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function index(Request $request)
    {
        $query = Vendor::with(['user', 'profile', 'portfolios' => fn($q) => $q->featured()->limit(4)])
            ->verified()
            ->active();

        // Filters
        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        if ($request->filled('city')) {
            $query->byCity($request->city);
        }

        if ($request->filled('min_price')) {
            $query->where('min_price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('max_price', '<=', $request->max_price);
        }

        if ($request->filled('min_reliability')) {
            $query->highReliability($request->min_reliability);
        }

        if ($request->boolean('backup_ready')) {
            $query->backupReady();
        }

        // Sorting
        $sortBy = $request->get('sort', 'reliability');
        switch ($sortBy) {
            case 'price_low':
                $query->orderBy('min_price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('max_price', 'desc');
                break;
            case 'experience':
                $query->orderBy('experience_years', 'desc');
                break;
            case 'events':
                $query->orderBy('total_events_completed', 'desc');
                break;
            default:
                $query->orderBy('reliability_score', 'desc');
        }

        $vendors = $query->paginate($request->get('per_page', 12));

        return VendorResource::collection($vendors);
    }

    public function show($slug)
    {
        $vendor = Vendor::with([
            'user',
            'profile',
            'portfolios' => fn($q) => $q->ordered(),
            'reliabilityLogs' => fn($q) => $q->latest()->limit(10),
        ])
            ->where('slug', $slug)
            ->verified()
            ->active()
            ->firstOrFail();

        return new VendorDetailResource($vendor);
    }

    public function categories()
    {
        $categories = Category::active()
            ->ordered()
            ->withCount(['vendors' => fn($q) => $q->verified()->active()])
            ->get();

        return response()->json([
            'data' => $categories,
        ]);
    }

    public function cities()
    {
        $cities = Vendor::verified()
            ->active()
            ->distinct()
            ->pluck('city')
            ->filter()
            ->values();

        return response()->json([
            'data' => $cities,
        ]);
    }

    public function featured()
    {
        $vendors = Vendor::with(['user', 'profile', 'portfolios' => fn($q) => $q->featured()->limit(4)])
            ->verified()
            ->active()
            ->highReliability(4.5)
            ->orderBy('total_events_completed', 'desc')
            ->limit(8)
            ->get();

        return VendorResource::collection($vendors);
    }

    public function checkAvailability(Request $request, $slug)
    {
        $vendor = Vendor::where('slug', $slug)->firstOrFail();

        $request->validate([
            'date' => ['required', 'date', 'after:today'],
        ]);

        $isAvailable = $vendor->isAvailableOn($request->date);

        return response()->json([
            'available' => $isAvailable,
            'date' => $request->date,
        ]);
    }

    // Vendor Dashboard Methods
    public function dashboard(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        // Stats
        $upcomingEventsCount = $vendor->eventVendors()
            ->whereHas('event', fn($q) => $q->upcoming())
            ->count();

        $backupStandby = $vendor->backupAssignments()
            ->standby()
            ->count();
            
        // Calculate Total Earnings (completed events)
        $totalEarnings = $vendor->eventVendors()
            ->where('status', 'completed')
            ->sum('agreed_price');

        // Upcoming Assignments List
        $upcomingAssignments = $vendor->eventVendors()
            ->with(['event'])
            ->whereHas('event', fn($q) => $q->upcoming())
            ->get()
            ->map(function ($ev) {
                return [
                    'id' => $ev->event->id,
                    'event_date' => $ev->event->event_date->toDateString(),
                    'event_title' => $ev->event->title,
                    'event_type' => $ev->event->type,
                    'city' => $ev->event->city,
                    'role' => $ev->role, // primary or backup
                    'status' => $ev->status,
                ];
            });

        // Emergency Requests (Notified backups)
        // We look for backup assignments where status is 'notified'
        // And match them with the emergency request details
        $emergencyRequests = $vendor->backupAssignments()
            ->with(['emergencyRequest.event'])
            ->where('status', 'notified')
            ->get()
            ->map(function ($ba) {
                $er = $ba->emergencyRequest;
                $event = $er ? $er->event : null;
                return [
                    'id' => $ba->id, // Assignment ID for accepting/rejecting
                    'emergency_id' => $er ? $er->id : null,
                    'event_title' => $event ? $event->title : 'Unknown Event',
                    'event_date' => $event ? $event->event_date->toDateString() : '',
                    'city' => $event ? $event->city : '',
                    'category' => $er ? 'Service' : 'Unknown', // Ideally get needed category
                    'payout' => 5000, // Placeholder or calculated from event budget
                ];
            });

        return response()->json([
            'vendor' => new VendorResource($vendor->load(['profile', 'portfolios'])),
            'stats' => [
                'upcoming_events' => $upcomingEventsCount,
                'backup_standby' => $backupStandby,
                'emergency_requests' => $emergencyRequests->count(),
                'reliability_score' => $vendor->reliability_score,
                'total_events_completed' => $vendor->total_events_completed,
                'cancellations' => $vendor->cancellations_count,
                'total_earnings' => $totalEarnings,
                'emergency_accepts' => $vendor->emergency_accepts_count ?? 0,
                'no_shows' => $vendor->no_shows_count ?? 0,
            ],
            'upcoming_assignments' => $upcomingAssignments,
            'emergency_requests_list' => $emergencyRequests,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $validated = $request->validate([
            'business_name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'service_radius_km' => ['nullable', 'integer', 'min:1'],
            'accepts_emergency' => ['sometimes', 'boolean'],
            'backup_ready' => ['sometimes', 'boolean'],
        ]);

        $vendor->update($validated);

        return new VendorResource($vendor->fresh(['profile', 'portfolios']));
    }

    public function updateContactProfile(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'services_offered' => ['nullable', 'array'],
            'terms_conditions' => ['nullable', 'string'],
            'cancellation_policy' => ['nullable', 'string'],
        ]);

        $profile = $vendor->profile()->updateOrCreate(
            ['vendor_id' => $vendor->id],
            $validated
        );

        return response()->json([
            'message' => 'Profile updated successfully',
            'profile' => $profile,
        ]);
    }

    public function uploadPortfolio(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $validated = $request->validate([
            'image' => ['required', 'image', 'max:5120'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_featured' => ['sometimes', 'boolean'],
        ]);

        $path = $request->file('image')->store('portfolios/' . $vendor->id, 'public');

        $portfolio = $vendor->portfolios()->create([
            'image_path' => $path,
            'title' => $validated['title'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_featured' => $validated['is_featured'] ?? false,
            'sort_order' => $vendor->portfolios()->count(),
        ]);

        return response()->json([
            'message' => 'Portfolio image uploaded successfully',
            'portfolio' => $portfolio,
        ], 201);
    }

    public function deletePortfolio(Request $request, $id)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $portfolio = $vendor->portfolios()->findOrFail($id);
        $portfolio->delete();

        return response()->json([
            'message' => 'Portfolio image deleted successfully',
        ]);
    }
}
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

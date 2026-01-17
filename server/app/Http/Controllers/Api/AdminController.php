<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VendorResource;
use App\Http\Resources\EventResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Event;
use App\Models\EmergencyRequest;
use App\Models\AdminAction;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        $stats = [
            'total_users' => User::count(),
            'total_vendors' => Vendor::count(),
            'verified_vendors' => Vendor::verified()->count(),
            'pending_verification' => Vendor::where('is_verified', false)->count(),
            'total_events' => Event::count(),
            'upcoming_events' => Event::upcoming()->count(),
            'completed_events' => Event::where('status', 'completed')->count(),
            'emergency_events' => Event::where('has_emergency', true)->count(),
            'pending_emergencies' => EmergencyRequest::whereIn('status', ['pending', 'searching'])->count(),
            'total_revenue' => Event::where('status', '!=', 'cancelled')->sum('assurance_fee') + 
                               Event::where('status', '!=', 'cancelled')->sum('platform_commission'),
        ];

        $recentEvents = Event::with(['client', 'eventVendors.vendor'])
            ->latest()
            ->limit(10)
            ->get();

        $recentVendors = Vendor::with(['user'])
            ->latest()
            ->limit(10)
            ->get();
            
        $activeEmergencies = EmergencyRequest::with(['event', 'eventVendor.vendor'])
            ->whereIn('status', ['pending', 'searching', 'assigned'])
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'stats' => $stats,
            'recent_events' => EventResource::collection($recentEvents),
            'recent_vendors' => VendorResource::collection($recentVendors),
            'active_emergencies' => $activeEmergencies,
        ]);
    }

    // Vendor Management
    public function vendors(Request $request)
    {
        $query = Vendor::with(['user', 'profile']);

        if ($request->filled('verified')) {
            $query->where('is_verified', $request->boolean('verified'));
        }

        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                    ->orWhereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        $vendors = $query->latest()->paginate($request->get('per_page', 20));

        return VendorResource::collection($vendors);
    }

    public function verifyVendor(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);
        $dataBefore = $vendor->toArray();

        $vendor->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        AdminAction::log(
            $request->user()->id,
            'vendor_verified',
            'vendor',
            $vendor->id,
            $dataBefore,
            $vendor->fresh()->toArray(),
            $request->reason
        );

        return response()->json([
            'message' => 'Vendor verified successfully',
            'vendor' => new VendorResource($vendor->fresh(['user', 'profile'])),
        ]);
    }

    public function suspendVendor(Request $request, $id)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string'],
        ]);

        $vendor = Vendor::findOrFail($id);
        $dataBefore = $vendor->toArray();

        $vendor->update(['is_active' => false]);

        AdminAction::log(
            $request->user()->id,
            'vendor_suspended',
            'vendor',
            $vendor->id,
            $dataBefore,
            $vendor->fresh()->toArray(),
            $validated['reason']
        );

        return response()->json([
            'message' => 'Vendor suspended successfully',
        ]);
    }

    public function adjustReliability(Request $request, $id)
    {
        $validated = $request->validate([
            'score_change' => ['required', 'numeric', 'between:-2,2'],
            'reason' => ['required', 'string'],
        ]);

        $vendor = Vendor::findOrFail($id);
        $vendor->updateReliabilityScore($validated['score_change'], 'admin_adjustment');

        AdminAction::log(
            $request->user()->id,
            'reliability_adjusted',
            'vendor',
            $vendor->id,
            ['reliability_score' => $vendor->reliability_score - $validated['score_change']],
            ['reliability_score' => $vendor->reliability_score],
            $validated['reason']
        );

        return response()->json([
            'message' => 'Reliability score adjusted',
            'new_score' => $vendor->reliability_score,
        ]);
    }

    // Emergency Management
    public function emergencies(Request $request)
    {
        $query = EmergencyRequest::with([
            'event.client',
            'eventVendor.vendor',
            'assignedBackup.user',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $emergencies = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json($emergencies);
    }

    public function overrideBackup(Request $request, $emergencyId)
    {
        $validated = $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'reason' => ['required', 'string'],
        ]);

        $emergency = EmergencyRequest::findOrFail($emergencyId);
        $dataBefore = $emergency->toArray();

        $emergency->assignBackup($validated['vendor_id']);

        AdminAction::log(
            $request->user()->id,
            'backup_override',
            'emergency_request',
            $emergency->id,
            $dataBefore,
            $emergency->fresh()->toArray(),
            $validated['reason']
        );

        return response()->json([
            'message' => 'Backup vendor assigned successfully',
            'emergency' => $emergency->fresh(['assignedBackup']),
        ]);
    }

    // Category Management
    public function categories()
    {
        return response()->json([
            'data' => Category::withCount('vendors')->ordered()->get(),
        ]);
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $category = Category::create($validated);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category,
        ], 201);
    }

    public function updateCategory(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $category->update($validated);

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category,
        ]);
    }

    // Users
    public function users(Request $request)
    {
        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate($request->get('per_page', 20));

        return UserResource::collection($users);
    }

    public function toggleUserStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'Cannot deactivate your own account',
            ], 422);
        }

        $user->update(['is_active' => !$user->is_active]);

        AdminAction::log(
            $request->user()->id,
            $user->is_active ? 'user_activated' : 'user_deactivated',
            'user',
            $user->id
        );

        return response()->json([
            'message' => $user->is_active ? 'User activated' : 'User deactivated',
            'user' => new UserResource($user),
        ]);
    }

    // Audit Log
    public function auditLog(Request $request)
    {
        $logs = AdminAction::with('admin')
            ->latest()
            ->paginate($request->get('per_page', 50));

        return response()->json($logs);
    }

    // Reports
    public function reports(Request $request)
    {
        $period = $request->get('period', 'month'); // week, month, year
        
        // Date range based on period
        $startDate = match($period) {
            'week' => now()->startOfWeek(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        // Booking Stats
        $bookingStats = [
            'total' => Event::where('created_at', '>=', $startDate)->count(),
            'confirmed' => Event::where('created_at', '>=', $startDate)->where('status', 'confirmed')->count(),
            'completed' => Event::where('created_at', '>=', $startDate)->where('status', 'completed')->count(),
            'cancelled' => Event::where('created_at', '>=', $startDate)->where('status', 'cancelled')->count(),
            'emergency' => Event::where('created_at', '>=', $startDate)->where('has_emergency', true)->count(),
        ];

        // Revenue Breakdown
        $revenueStats = [
            'total_booking_value' => Event::where('created_at', '>=', $startDate)
                ->whereIn('status', ['confirmed', 'completed'])
                ->sum('total_amount'),
            'assurance_fees' => Event::where('created_at', '>=', $startDate)
                ->whereIn('status', ['confirmed', 'completed'])
                ->sum('assurance_fee'),
            'platform_commission' => Event::where('created_at', '>=', $startDate)
                ->whereIn('status', ['confirmed', 'completed'])
                ->sum('platform_commission'),
        ];
        $revenueStats['platform_revenue'] = $revenueStats['assurance_fees'] + $revenueStats['platform_commission'];

        // Vendor Performance
        $topVendors = Vendor::with('user')
            ->withCount(['eventVendors as completed_events' => function ($q) use ($startDate) {
                $q->where('status', 'completed')
                  ->where('created_at', '>=', $startDate);
            }])
            ->orderBy('completed_events', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($v) => [
                'id' => $v->id,
                'business_name' => $v->business_name,
                'category' => $v->category,
                'reliability_score' => $v->reliability_score,
                'completed_events' => $v->completed_events,
            ]);

        // Emergency Stats
        $emergencyStats = [
            'total' => EmergencyRequest::where('created_at', '>=', $startDate)->count(),
            'resolved' => EmergencyRequest::where('created_at', '>=', $startDate)->where('status', 'resolved')->count(),
            'avg_resolution_time' => round(EmergencyRequest::where('created_at', '>=', $startDate)
                ->whereNotNull('resolution_time_minutes')
                ->avg('resolution_time_minutes') ?? 0, 1),
        ];
        $emergencyStats['resolution_rate'] = $emergencyStats['total'] > 0 
            ? round(($emergencyStats['resolved'] / $emergencyStats['total']) * 100, 1) 
            : 100;

        // Category Breakdown
        $categoryStats = Vendor::selectRaw('category, COUNT(*) as vendor_count')
            ->verified()
            ->active()
            ->groupBy('category')
            ->get()
            ->map(fn($c) => [
                'category' => $c->category,
                'vendor_count' => $c->vendor_count,
            ]);

        // User Growth
        $userStats = [
            'total_users' => User::count(),
            'new_users' => User::where('created_at', '>=', $startDate)->count(),
            'total_vendors' => Vendor::count(),
            'new_vendors' => Vendor::where('created_at', '>=', $startDate)->count(),
        ];

        return response()->json([
            'period' => $period,
            'start_date' => $startDate->toDateString(),
            'booking_stats' => $bookingStats,
            'revenue_stats' => $revenueStats,
            'top_vendors' => $topVendors,
            'emergency_stats' => $emergencyStats,
            'category_stats' => $categoryStats,
            'user_stats' => $userStats,
        ]);
    }

    // Settings
    public function getSettings()
    {
        return response()->json([
            'settings' => Setting::getAllSettings(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'platform_name' => ['sometimes', 'string', 'max:255'],
            'support_email' => ['sometimes', 'email', 'max:255'],
            'support_phone' => ['sometimes', 'string', 'max:50'],
            'assurance_fee_percent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'commission_percent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'advance_percent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'emergency_bonus_multiplier' => ['sometimes', 'numeric', 'min:1', 'max:5'],
            'emergency_window_hours' => ['sometimes', 'integer', 'min:1', 'max:48'],
            'max_backup_assignments' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'default_reliability_score' => ['sometimes', 'numeric', 'min:1', 'max:5'],
            'min_backup_score' => ['sometimes', 'numeric', 'min:1', 'max:5'],
            'auto_verify_vendors' => ['sometimes', 'boolean'],
        ]);

        $typeMap = [
            'platform_name' => ['type' => 'string', 'group' => 'general'],
            'support_email' => ['type' => 'string', 'group' => 'general'],
            'support_phone' => ['type' => 'string', 'group' => 'general'],
            'assurance_fee_percent' => ['type' => 'float', 'group' => 'financial'],
            'commission_percent' => ['type' => 'float', 'group' => 'financial'],
            'advance_percent' => ['type' => 'float', 'group' => 'financial'],
            'emergency_bonus_multiplier' => ['type' => 'float', 'group' => 'emergency'],
            'emergency_window_hours' => ['type' => 'integer', 'group' => 'emergency'],
            'max_backup_assignments' => ['type' => 'integer', 'group' => 'emergency'],
            'default_reliability_score' => ['type' => 'float', 'group' => 'vendor'],
            'min_backup_score' => ['type' => 'float', 'group' => 'vendor'],
            'auto_verify_vendors' => ['type' => 'boolean', 'group' => 'vendor'],
        ];

        foreach ($validated as $key => $value) {
            $meta = $typeMap[$key] ?? ['type' => 'string', 'group' => 'general'];
            Setting::setValue($key, $value, $meta['type'], $meta['group']);
        }

        return response()->json([
            'message' => 'Settings updated successfully',
            'settings' => Setting::getAllSettings(),
        ]);
    }
}

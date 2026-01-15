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
}

<?php

namespace App\Http\Controllers\Api;

/**
 * @group Service Packages
 *
 * APIs for managing vendor service packages. Vendors can create, update, and manage their service packages.
 * Clients can view packages when browsing vendors.
 */

use App\Http\Controllers\Controller;
use App\Http\Resources\PackageResource;
use App\Models\Vendor;
use App\Models\VendorPackage;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    // ========== Public Methods ==========

    /**
     * List vendor packages (Public)
     *
     * Get all active packages for a vendor by their slug.
     *
     * @urlParam slug string required The vendor's slug. Example: john-photography-abc123
     */
    public function vendorPackages(string $slug)
    {
        $vendor = Vendor::where('slug', $slug)
            ->verified()
            ->active()
            ->firstOrFail();

        $packages = $vendor->packages()
            ->active()
            ->ordered()
            ->get();

        return PackageResource::collection($packages)->additional([
            'vendor' => [
                'id' => $vendor->id,
                'business_name' => $vendor->business_name,
                'slug' => $vendor->slug,
                'category' => $vendor->category,
            ],
        ]);
    }

    /**
     * Get single package (Public)
     *
     * Get details of a specific package.
     *
     * @urlParam slug string required The vendor's slug. Example: john-photography-abc123
     * @urlParam packageId integer required The package ID. Example: 1
     */
    public function showPublic(string $slug, int $packageId)
    {
        $vendor = Vendor::where('slug', $slug)
            ->verified()
            ->active()
            ->firstOrFail();

        $package = $vendor->packages()
            ->active()
            ->findOrFail($packageId);

        return new PackageResource($package);
    }

    // ========== Vendor Methods ==========

    /**
     * List my packages
     *
     * Get all packages for the authenticated vendor.
     *
     * @authenticated
     * @queryParam include_inactive boolean Include inactive packages. Example: true
     */
    public function index(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $query = $vendor->packages()->ordered();

        if (!$request->boolean('include_inactive', false)) {
            $query->active();
        }

        $packages = $query->get();

        return PackageResource::collection($packages)->additional([
            'stats' => [
                'total' => $vendor->packages()->count(),
                'active' => $vendor->packages()->active()->count(),
                'inactive' => $vendor->packages()->where('is_active', false)->count(),
                'featured' => $vendor->packages()->featured()->count(),
            ],
        ]);
    }

    /**
     * Create package
     *
     * Create a new service package.
     *
     * @authenticated
     * @bodyParam name string required Package name. Example: Basic Photography Package
     * @bodyParam description string Package description. Example: Perfect for small events and parties.
     * @bodyParam price number required Package price. Example: 15000
     * @bodyParam compare_price number Original price for showing discount. Example: 20000
     * @bodyParam duration_hours integer Service duration in hours. Example: 4
     * @bodyParam features array List of included features. Example: ["4 hours coverage", "100 edited photos", "Online gallery"]
     * @bodyParam deliverables array List of deliverables. Example: ["Digital photos", "USB drive"]
     * @bodyParam max_revisions integer Number of revisions included. Example: 2
     * @bodyParam delivery_days integer Estimated delivery time in days. Example: 7
     * @bodyParam is_featured boolean Feature this package. Example: false
     * @bodyParam is_active boolean Package is active. Example: true
     */
    public function store(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_price' => ['nullable', 'numeric', 'min:0', 'gt:price'],
            'duration_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
            'features' => ['nullable', 'array', 'max:20'],
            'features.*' => ['string', 'max:255'],
            'deliverables' => ['nullable', 'array', 'max:20'],
            'deliverables.*' => ['string', 'max:255'],
            'max_revisions' => ['nullable', 'integer', 'min:0', 'max:100'],
            'delivery_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // If setting as featured, ensure only one featured package
        if ($request->boolean('is_featured')) {
            $vendor->packages()->update(['is_featured' => false]);
        }

        $package = $vendor->packages()->create([
            ...$validated,
            'sort_order' => VendorPackage::getNextSortOrder($vendor->id),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Package created successfully',
            'package' => new PackageResource($package),
        ], 201);
    }

    /**
     * Get package details
     *
     * Get details of a specific package.
     *
     * @authenticated
     * @urlParam id integer required The package ID. Example: 1
     */
    public function show(Request $request, int $id)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $package = $vendor->packages()->findOrFail($id);

        return new PackageResource($package);
    }

    /**
     * Update package
     *
     * Update an existing service package.
     *
     * @authenticated
     * @urlParam id integer required The package ID. Example: 1
     * @bodyParam name string Package name. Example: Premium Photography Package
     * @bodyParam description string Package description. Example: Our most comprehensive package.
     * @bodyParam price number Package price. Example: 25000
     * @bodyParam compare_price number Original price for showing discount. Example: 35000
     * @bodyParam duration_hours integer Service duration in hours. Example: 8
     * @bodyParam features array List of included features. Example: ["8 hours coverage", "300 edited photos", "Online gallery", "Photo album"]
     * @bodyParam deliverables array List of deliverables. Example: ["Digital photos", "Photo album", "USB drive"]
     * @bodyParam max_revisions integer Number of revisions included. Example: 5
     * @bodyParam delivery_days integer Estimated delivery time in days. Example: 14
     * @bodyParam is_featured boolean Feature this package. Example: true
     * @bodyParam is_active boolean Package is active. Example: true
     */
    public function update(Request $request, int $id)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $package = $vendor->packages()->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'compare_price' => ['nullable', 'numeric', 'min:0'],
            'duration_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
            'features' => ['nullable', 'array', 'max:20'],
            'features.*' => ['string', 'max:255'],
            'deliverables' => ['nullable', 'array', 'max:20'],
            'deliverables.*' => ['string', 'max:255'],
            'max_revisions' => ['nullable', 'integer', 'min:0', 'max:100'],
            'delivery_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // Validate compare_price > price if both are provided
        $newPrice = $validated['price'] ?? $package->price;
        $newComparePrice = array_key_exists('compare_price', $validated)
            ? $validated['compare_price']
            : $package->compare_price;

        if ($newComparePrice && $newComparePrice <= $newPrice) {
            return response()->json([
                'message' => 'Compare price must be greater than price',
            ], 422);
        }

        // If setting as featured, ensure only one featured package
        if ($request->boolean('is_featured') && !$package->is_featured) {
            $vendor->packages()->where('id', '!=', $package->id)->update(['is_featured' => false]);
        }

        $package->update($validated);

        return response()->json([
            'message' => 'Package updated successfully',
            'package' => new PackageResource($package->fresh()),
        ]);
    }

    /**
     * Delete package
     *
     * Delete a service package. Cannot delete if package has been used in bookings.
     *
     * @authenticated
     * @urlParam id integer required The package ID. Example: 1
     */
    public function destroy(Request $request, int $id)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $package = $vendor->packages()->findOrFail($id);

        // Check if package has been used in any bookings
        if ($package->bookings_count > 0) {
            return response()->json([
                'message' => 'Cannot delete a package that has been booked. Consider deactivating it instead.',
                'bookings_count' => $package->bookings_count,
            ], 422);
        }

        $package->delete();

        return response()->json([
            'message' => 'Package deleted successfully',
        ]);
    }

    /**
     * Toggle package status
     *
     * Activate or deactivate a package.
     *
     * @authenticated
     * @urlParam id integer required The package ID. Example: 1
     */
    public function toggleStatus(Request $request, int $id)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $package = $vendor->packages()->findOrFail($id);
        $package->update(['is_active' => !$package->is_active]);

        return response()->json([
            'message' => $package->is_active ? 'Package activated' : 'Package deactivated',
            'is_active' => $package->is_active,
        ]);
    }

    /**
     * Reorder packages
     *
     * Update the display order of packages.
     *
     * @authenticated
     * @bodyParam package_ids array required Array of package IDs in desired order. Example: [3, 1, 2]
     */
    public function reorder(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $validated = $request->validate([
            'package_ids' => ['required', 'array', 'min:1'],
            'package_ids.*' => ['integer', 'exists:vendor_packages,id'],
        ]);

        // Verify all packages belong to this vendor
        $vendorPackageIds = $vendor->packages()->pluck('id')->toArray();
        $invalidIds = array_diff($validated['package_ids'], $vendorPackageIds);

        if (!empty($invalidIds)) {
            return response()->json([
                'message' => 'Some package IDs do not belong to your account',
            ], 422);
        }

        VendorPackage::reorder($vendor->id, $validated['package_ids']);

        return response()->json([
            'message' => 'Packages reordered successfully',
        ]);
    }

    /**
     * Duplicate package
     *
     * Create a copy of an existing package.
     *
     * @authenticated
     * @urlParam id integer required The package ID to duplicate. Example: 1
     */
    public function duplicate(Request $request, int $id)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $original = $vendor->packages()->findOrFail($id);

        $newPackage = $original->replicate();
        $newPackage->name = $original->name . ' (Copy)';
        $newPackage->is_featured = false;
        $newPackage->bookings_count = 0;
        $newPackage->sort_order = VendorPackage::getNextSortOrder($vendor->id);
        $newPackage->save();

        return response()->json([
            'message' => 'Package duplicated successfully',
            'package' => new PackageResource($newPackage),
        ], 201);
    }
}

<?php

namespace App\Http\Controllers\Api;

/**
 * @group Reviews
 *
 * APIs for managing vendor reviews. Clients can submit reviews after completed events, and vendors can respond to reviews.
 */

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Models\Event;
use App\Models\Vendor;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Get vendor reviews
     *
     * List all visible reviews for a vendor.
     *
     * @urlParam slug string required The vendor's slug. Example: john-photography-abc123
     * @queryParam rating integer Filter by specific rating (1-5). Example: 5
     * @queryParam sort string Sort order: latest, oldest, highest, lowest. Example: latest
     * @queryParam per_page integer Results per page. Example: 10
     *
     * @unauthenticated
     */
    public function vendorReviews(Request $request, $slug)
    {
        $vendor = Vendor::where('slug', $slug)
            ->verified()
            ->active()
            ->firstOrFail();

        $query = $vendor->reviews()
            ->visible()
            ->with(['client', 'event']);

        // Filter by rating
        if ($request->filled('rating')) {
            $query->byRating($request->integer('rating'));
        }

        // Sorting
        $sort = $request->get('sort', 'latest');
        switch ($sort) {
            case 'oldest':
                $query->oldest();
                break;
            case 'highest':
                $query->orderBy('rating', 'desc')->latest();
                break;
            case 'lowest':
                $query->orderBy('rating', 'asc')->latest();
                break;
            default:
                $query->latest();
        }

        $reviews = $query->paginate($request->get('per_page', 10));

        return ReviewResource::collection($reviews)->additional([
            'meta' => [
                'average_rating' => $vendor->average_rating,
                'total_reviews' => $vendor->total_reviews,
                'rating_distribution' => $vendor->getRatingDistribution(),
            ],
        ]);
    }

    /**
     * Get client's reviews
     *
     * List all reviews submitted by the authenticated client.
     *
     * @authenticated
     */
    public function myReviews(Request $request)
    {
        $reviews = Review::with(['vendor', 'event'])
            ->where('client_id', $request->user()->id)
            ->latest()
            ->paginate($request->get('per_page', 10));

        return ReviewResource::collection($reviews);
    }

    /**
     * Get events pending review
     *
     * List completed events that the client hasn't reviewed yet.
     *
     * @authenticated
     */
    public function pendingReviews(Request $request)
    {
        $user = $request->user();

        // Get completed events where client hasn't submitted a review
        $events = Event::with(['eventVendors.vendor'])
            ->where('client_id', $user->id)
            ->where('status', 'completed')
            ->whereDoesntHave('reviews', function ($query) use ($user) {
                $query->where('client_id', $user->id);
            })
            ->latest('completed_at')
            ->get();

        $pendingReviews = $events->flatMap(function ($event) {
            return $event->eventVendors
                ->filter(fn($ev) => in_array($ev->status, ['completed', 'confirmed']))
                ->map(fn($ev) => [
                    'event_id' => $event->id,
                    'event_title' => $event->title,
                    'event_type' => $event->type,
                    'event_date' => $event->event_date->toDateString(),
                    'completed_at' => $event->completed_at?->toISOString(),
                    'vendor_id' => $ev->vendor->id,
                    'vendor_name' => $ev->vendor->business_name,
                    'vendor_slug' => $ev->vendor->slug,
                    'vendor_category' => $ev->vendor->category,
                ]);
        });

        return response()->json([
            'data' => $pendingReviews->values(),
            'total' => $pendingReviews->count(),
        ]);
    }

    /**
     * Submit a review
     *
     * Submit a review for a vendor after a completed event.
     *
     * @authenticated
     * @bodyParam event_id integer required The event ID. Example: 1
     * @bodyParam vendor_id integer required The vendor ID. Example: 1
     * @bodyParam rating integer required Rating from 1 to 5. Example: 5
     * @bodyParam title string optional Review title. Example: Excellent service!
     * @bodyParam comment string optional Detailed review. Example: The photographer was professional and delivered amazing photos.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'vendor_id' => ['required', 'exists:vendors,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        // Verify the event belongs to this client and is completed
        $event = Event::where('id', $validated['event_id'])
            ->where('client_id', $user->id)
            ->where('status', 'completed')
            ->firstOrFail();

        // Verify the vendor was part of this event
        $eventVendor = $event->eventVendors()
            ->where('vendor_id', $validated['vendor_id'])
            ->whereIn('status', ['completed', 'confirmed'])
            ->firstOrFail();

        // Check if review already exists
        $existingReview = Review::where('event_id', $event->id)
            ->where('vendor_id', $validated['vendor_id'])
            ->where('client_id', $user->id)
            ->first();

        if ($existingReview) {
            return response()->json([
                'message' => 'You have already reviewed this vendor for this event',
            ], 422);
        }

        // Create the review
        $review = Review::create([
            'event_id' => $event->id,
            'vendor_id' => $validated['vendor_id'],
            'client_id' => $user->id,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'comment' => $validated['comment'] ?? null,
            'is_verified' => true, // From completed booking
        ]);

        $review->load(['client', 'event', 'vendor']);

        return response()->json([
            'message' => 'Review submitted successfully',
            'review' => new ReviewResource($review),
        ], 201);
    }

    /**
     * Update a review
     *
     * Update an existing review (only within 7 days of creation).
     *
     * @authenticated
     * @urlParam id integer required The review ID. Example: 1
     */
    public function update(Request $request, $id)
    {
        $review = Review::where('client_id', $request->user()->id)
            ->findOrFail($id);

        // Only allow updates within 7 days
        if ($review->created_at->diffInDays(now()) > 7) {
            return response()->json([
                'message' => 'Reviews can only be edited within 7 days of submission',
            ], 422);
        }

        $validated = $request->validate([
            'rating' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $review->update($validated);
        $review->load(['client', 'event']);

        return response()->json([
            'message' => 'Review updated successfully',
            'review' => new ReviewResource($review),
        ]);
    }

    /**
     * Delete a review
     *
     * Delete an existing review (only within 7 days of creation).
     *
     * @authenticated
     * @urlParam id integer required The review ID. Example: 1
     */
    public function destroy(Request $request, $id)
    {
        $review = Review::where('client_id', $request->user()->id)
            ->findOrFail($id);

        // Only allow deletion within 7 days
        if ($review->created_at->diffInDays(now()) > 7) {
            return response()->json([
                'message' => 'Reviews can only be deleted within 7 days of submission',
            ], 422);
        }

        $review->delete();

        return response()->json([
            'message' => 'Review deleted successfully',
        ]);
    }

    // ========== Vendor Methods ==========

    /**
     * Get vendor's received reviews
     *
     * List all reviews received by the authenticated vendor.
     *
     * @authenticated
     */
    public function vendorReceivedReviews(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $query = $vendor->reviews()
            ->with(['client', 'event']);

        // Filter by response status
        if ($request->boolean('needs_response')) {
            $query->withoutResponse();
        }

        $reviews = $query->latest()->paginate($request->get('per_page', 10));

        return ReviewResource::collection($reviews)->additional([
            'meta' => [
                'average_rating' => $vendor->average_rating,
                'total_reviews' => $vendor->total_reviews,
                'rating_distribution' => $vendor->getRatingDistribution(),
                'pending_responses' => $vendor->reviews()->visible()->withoutResponse()->count(),
            ],
        ]);
    }

    /**
     * Respond to a review
     *
     * Add a vendor response to a review.
     *
     * @authenticated
     * @urlParam id integer required The review ID. Example: 1
     * @bodyParam response string required The vendor's response. Example: Thank you for your kind words!
     */
    public function respondToReview(Request $request, $id)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $review = Review::where('vendor_id', $vendor->id)
            ->findOrFail($id);

        if ($review->hasVendorResponse()) {
            return response()->json([
                'message' => 'You have already responded to this review',
            ], 422);
        }

        $validated = $request->validate([
            'response' => ['required', 'string', 'max:1000'],
        ]);

        $review->addVendorResponse($validated['response']);
        $review->load(['client', 'event']);

        return response()->json([
            'message' => 'Response added successfully',
            'review' => new ReviewResource($review),
        ]);
    }

    // ========== Admin Methods ==========

    /**
     * List all reviews (Admin)
     *
     * List all reviews with filtering options.
     *
     * @authenticated
     */
    public function adminIndex(Request $request)
    {
        $query = Review::with(['client', 'vendor', 'event']);

        // Filters
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->filled('rating')) {
            $query->byRating($request->integer('rating'));
        }

        if ($request->has('visible')) {
            $query->where('is_visible', $request->boolean('visible'));
        }

        $reviews = $query->latest()->paginate($request->get('per_page', 20));

        return ReviewResource::collection($reviews);
    }

    /**
     * Toggle review visibility (Admin)
     *
     * Hide or show a review.
     *
     * @authenticated
     * @urlParam id integer required The review ID. Example: 1
     */
    public function toggleVisibility(Request $request, $id)
    {
        $review = Review::findOrFail($id);

        $review->update([
            'is_visible' => !$review->is_visible,
        ]);

        return response()->json([
            'message' => $review->is_visible ? 'Review is now visible' : 'Review is now hidden',
            'is_visible' => $review->is_visible,
        ]);
    }
}

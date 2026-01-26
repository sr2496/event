<?php

namespace App\Http\Controllers\Api;

/**
 * @group Payouts
 *
 * APIs for managing vendor payouts. Vendors can view earnings, request payouts, and track payout history.
 * Admins can process and manage all payouts.
 */

use App\Http\Controllers\Controller;
use App\Http\Resources\PayoutResource;
use App\Http\Resources\EarningResource;
use App\Models\Payout;
use App\Models\VendorEarning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayoutController extends Controller
{
    // ========== Vendor Methods ==========

    /**
     * Get earnings summary
     *
     * Get the vendor's earnings summary including balances and payout eligibility.
     *
     * @authenticated
     */
    public function earningsSummary(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        return response()->json([
            'data' => $vendor->getEarningsSummary(),
        ]);
    }

    /**
     * Get earnings history
     *
     * List all earnings for the authenticated vendor.
     *
     * @authenticated
     * @queryParam status string Filter by status: pending, available, paid. Example: available
     * @queryParam per_page integer Results per page. Example: 15
     */
    public function earnings(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $query = $vendor->earnings()->with(['event', 'payout']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $earnings = $query->latest()->paginate($request->get('per_page', 15));

        return EarningResource::collection($earnings);
    }

    /**
     * Get payout history
     *
     * List all payout requests for the authenticated vendor.
     *
     * @authenticated
     * @queryParam status string Filter by status: pending, processing, completed, failed, cancelled. Example: completed
     * @queryParam per_page integer Results per page. Example: 15
     */
    public function payouts(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $query = $vendor->payouts()->with(['processedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payouts = $query->latest()->paginate($request->get('per_page', 15));

        return PayoutResource::collection($payouts);
    }

    /**
     * Get single payout details
     *
     * Get details of a specific payout request.
     *
     * @authenticated
     * @urlParam id integer required The payout ID. Example: 1
     */
    public function showPayout(Request $request, $id)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $payout = $vendor->payouts()
            ->with(['processedBy', 'earnings.event'])
            ->findOrFail($id);

        return new PayoutResource($payout);
    }

    /**
     * Update bank details
     *
     * Update the vendor's bank account details for payouts.
     *
     * @authenticated
     * @bodyParam account_holder_name string required Account holder name. Example: John Doe
     * @bodyParam account_number string required Bank account number. Example: 1234567890
     * @bodyParam ifsc_code string required IFSC code. Example: HDFC0001234
     * @bodyParam bank_name string required Bank name. Example: HDFC Bank
     * @bodyParam upi_id string optional UPI ID for instant transfers. Example: john@upi
     */
    public function updateBankDetails(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $validated = $request->validate([
            'account_holder_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:50'],
            'ifsc_code' => ['required', 'string', 'max:20'],
            'bank_name' => ['required', 'string', 'max:255'],
            'upi_id' => ['nullable', 'string', 'max:100'],
        ]);

        $vendor->update([
            'bank_details' => $validated,
        ]);

        return response()->json([
            'message' => 'Bank details updated successfully',
            'bank_details' => $vendor->bank_details,
        ]);
    }

    /**
     * Request a payout
     *
     * Request a payout of available balance.
     *
     * @authenticated
     * @bodyParam amount number optional Specific amount to withdraw (defaults to full available balance). Example: 5000
     * @bodyParam payment_method string optional Preferred payment method: bank_transfer, upi. Example: bank_transfer
     */
    public function requestPayout(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        if (!$vendor->hasBankDetails()) {
            return response()->json([
                'message' => 'Please add your bank details before requesting a payout',
            ], 422);
        }

        // Check for pending payouts
        if ($vendor->payouts()->whereIn('status', ['pending', 'processing'])->exists()) {
            return response()->json([
                'message' => 'You already have a pending payout request',
            ], 422);
        }

        $validated = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:' . $vendor::getMinimumPayoutAmount()],
            'payment_method' => ['nullable', 'in:bank_transfer,upi'],
        ]);

        $amount = $validated['amount'] ?? $vendor->available_balance;

        if ($amount > $vendor->available_balance) {
            return response()->json([
                'message' => 'Requested amount exceeds available balance',
                'available_balance' => $vendor->available_balance,
            ], 422);
        }

        if ($amount < $vendor::getMinimumPayoutAmount()) {
            return response()->json([
                'message' => 'Minimum payout amount is ₹' . number_format($vendor::getMinimumPayoutAmount()),
                'minimum_amount' => $vendor::getMinimumPayoutAmount(),
            ], 422);
        }

        $payout = DB::transaction(function () use ($vendor, $amount, $validated) {
            // Create payout request
            $payout = Payout::create([
                'vendor_id' => $vendor->id,
                'amount' => $amount,
                'processing_fee' => 0, // Can be calculated based on payment method
                'net_amount' => $amount,
                'status' => 'pending',
                'payment_method' => $validated['payment_method'] ?? 'bank_transfer',
                'payment_details' => $vendor->bank_details,
            ]);

            // Link available earnings to this payout (FIFO)
            $remainingAmount = $amount;
            $availableEarnings = $vendor->earnings()
                ->available()
                ->orderBy('created_at')
                ->get();

            foreach ($availableEarnings as $earning) {
                if ($remainingAmount <= 0) break;

                if ($earning->net_amount <= $remainingAmount) {
                    $earning->update([
                        'payout_id' => $payout->id,
                    ]);
                    $remainingAmount -= $earning->net_amount;
                }
            }

            return $payout;
        });

        return response()->json([
            'message' => 'Payout request submitted successfully',
            'payout' => new PayoutResource($payout),
        ], 201);
    }

    /**
     * Cancel payout request
     *
     * Cancel a pending payout request.
     *
     * @authenticated
     * @urlParam id integer required The payout ID. Example: 1
     */
    public function cancelPayout(Request $request, $id)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $payout = $vendor->payouts()->findOrFail($id);

        if (!$payout->canBeCancelled()) {
            return response()->json([
                'message' => 'This payout cannot be cancelled',
                'status' => $payout->status,
            ], 422);
        }

        $payout->cancel('Cancelled by vendor');

        return response()->json([
            'message' => 'Payout request cancelled successfully',
        ]);
    }

    // ========== Admin Methods ==========

    /**
     * List all payouts (Admin)
     *
     * List all payout requests with filtering options.
     *
     * @authenticated
     * @queryParam status string Filter by status. Example: pending
     * @queryParam vendor_id integer Filter by vendor. Example: 1
     * @queryParam per_page integer Results per page. Example: 20
     */
    public function adminIndex(Request $request)
    {
        $query = Payout::with(['vendor', 'processedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Default to showing pending first
        $query->orderByRaw("FIELD(status, 'pending', 'processing', 'completed', 'failed', 'cancelled')")
              ->latest();

        $payouts = $query->paginate($request->get('per_page', 20));

        // Summary stats
        $stats = [
            'pending_count' => Payout::pending()->count(),
            'pending_amount' => Payout::pending()->sum('amount'),
            'processing_count' => Payout::processing()->count(),
            'processing_amount' => Payout::processing()->sum('amount'),
            'completed_today' => Payout::completed()->whereDate('processed_at', today())->count(),
            'completed_today_amount' => Payout::completed()->whereDate('processed_at', today())->sum('net_amount'),
        ];

        return PayoutResource::collection($payouts)->additional([
            'stats' => $stats,
        ]);
    }

    /**
     * Get payout details (Admin)
     *
     * Get full details of a payout including earnings breakdown.
     *
     * @authenticated
     * @urlParam id integer required The payout ID. Example: 1
     */
    public function adminShow(Request $request, $id)
    {
        $payout = Payout::with(['vendor.user', 'processedBy', 'earnings.event'])
            ->findOrFail($id);

        return response()->json([
            'payout' => new PayoutResource($payout),
            'earnings' => EarningResource::collection($payout->earnings),
            'vendor_bank_details' => $payout->vendor->bank_details,
        ]);
    }

    /**
     * Process payout (Admin)
     *
     * Mark a payout as processing (being worked on).
     *
     * @authenticated
     * @urlParam id integer required The payout ID. Example: 1
     */
    public function processPayout(Request $request, $id)
    {
        $payout = Payout::findOrFail($id);

        if (!$payout->isPending()) {
            return response()->json([
                'message' => 'Only pending payouts can be processed',
                'status' => $payout->status,
            ], 422);
        }

        $payout->markAsProcessing($request->user());

        return response()->json([
            'message' => 'Payout marked as processing',
            'payout' => new PayoutResource($payout->fresh()),
        ]);
    }

    /**
     * Complete payout (Admin)
     *
     * Mark a payout as completed after transfer.
     *
     * @authenticated
     * @urlParam id integer required The payout ID. Example: 1
     * @bodyParam transaction_reference string required External transaction/reference ID. Example: TXN123456789
     * @bodyParam notes string optional Admin notes. Example: Transferred via NEFT
     */
    public function completePayout(Request $request, $id)
    {
        $payout = Payout::findOrFail($id);

        if (!$payout->canBeProcessed()) {
            return response()->json([
                'message' => 'This payout cannot be completed',
                'status' => $payout->status,
            ], 422);
        }

        $validated = $request->validate([
            'transaction_reference' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payout->markAsCompleted($validated['transaction_reference'], $validated['notes'] ?? null);

        return response()->json([
            'message' => 'Payout completed successfully',
            'payout' => new PayoutResource($payout->fresh()),
        ]);
    }

    /**
     * Fail payout (Admin)
     *
     * Mark a payout as failed.
     *
     * @authenticated
     * @urlParam id integer required The payout ID. Example: 1
     * @bodyParam reason string required Reason for failure. Example: Invalid bank account details
     */
    public function failPayout(Request $request, $id)
    {
        $payout = Payout::findOrFail($id);

        if (!$payout->canBeProcessed()) {
            return response()->json([
                'message' => 'This payout status cannot be changed',
                'status' => $payout->status,
            ], 422);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $payout->markAsFailed($validated['reason']);

        return response()->json([
            'message' => 'Payout marked as failed',
            'payout' => new PayoutResource($payout->fresh()),
        ]);
    }
}

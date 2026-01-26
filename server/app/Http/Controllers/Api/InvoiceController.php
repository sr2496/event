<?php

namespace App\Http\Controllers\Api;

/**
 * @group Invoices
 *
 * APIs for managing invoices and receipts. Clients can view and download invoices for their bookings.
 * @authenticated
 */

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    /**
     * List my invoices
     *
     * Get all invoices for the authenticated user.
     *
     * @queryParam type string Filter by type: proforma, booking, receipt. Example: booking
     * @queryParam status string Filter by status: draft, issued, paid, cancelled. Example: paid
     * @queryParam per_page integer Results per page. Example: 15
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Invoice::where('user_id', $user->id)
            ->with(['event']);

        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->latest()->paginate($request->get('per_page', 15));

        return InvoiceResource::collection($invoices);
    }

    /**
     * Get invoice details
     *
     * Get details of a specific invoice.
     *
     * @urlParam id integer required The invoice ID. Example: 1
     */
    public function show(Request $request, int $id)
    {
        $user = $request->user();

        $invoice = Invoice::where('user_id', $user->id)
            ->with(['event', 'vendor'])
            ->findOrFail($id);

        return new InvoiceResource($invoice);
    }

    /**
     * Get booking invoice
     *
     * Get or generate an invoice for a specific booking.
     *
     * @urlParam bookingId integer required The booking/event ID. Example: 1
     */
    public function getBookingInvoice(Request $request, int $bookingId)
    {
        $user = $request->user();

        $event = Event::where('client_id', $user->id)
            ->with(['eventVendors.vendor', 'payments'])
            ->findOrFail($bookingId);

        // Check if invoice already exists
        $invoice = Invoice::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('type', Invoice::TYPE_BOOKING)
            ->first();

        if (!$invoice) {
            // Create new invoice
            $invoice = Invoice::createForBooking($event);
            $invoice->issue();
        }

        $invoice->load(['event', 'vendor']);

        return new InvoiceResource($invoice);
    }

    /**
     * Download invoice PDF
     *
     * Download the invoice as a PDF file.
     *
     * @urlParam id integer required The invoice ID. Example: 1
     */
    public function download(Request $request, int $id)
    {
        $user = $request->user();

        $invoice = Invoice::where('user_id', $user->id)
            ->with(['event', 'user', 'vendor'])
            ->findOrFail($id);

        return $invoice->downloadPdf();
    }

    /**
     * View invoice PDF
     *
     * Stream the invoice PDF for viewing in browser.
     *
     * @urlParam id integer required The invoice ID. Example: 1
     */
    public function viewPdf(Request $request, int $id)
    {
        $user = $request->user();

        $invoice = Invoice::where('user_id', $user->id)
            ->with(['event', 'user', 'vendor'])
            ->findOrFail($id);

        return $invoice->streamPdf();
    }

    /**
     * Get payment receipt
     *
     * Get or generate a receipt for a specific payment.
     *
     * @urlParam bookingId integer required The booking/event ID. Example: 1
     * @urlParam paymentId integer required The payment ID. Example: 1
     */
    public function getPaymentReceipt(Request $request, int $bookingId, int $paymentId)
    {
        $user = $request->user();

        $event = Event::where('client_id', $user->id)->findOrFail($bookingId);

        $payment = Payment::where('id', $paymentId)
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->firstOrFail();

        // Check if receipt already exists
        $invoice = Invoice::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('type', Invoice::TYPE_RECEIPT)
            ->whereJsonContains('metadata->payment_id', $payment->id)
            ->first();

        if (!$invoice) {
            // Create new receipt
            $invoice = Invoice::createReceipt($event, $payment);
        }

        $invoice->load(['event']);

        return new InvoiceResource($invoice);
    }

    /**
     * Download booking invoice PDF
     *
     * Directly download the booking invoice as PDF.
     *
     * @urlParam bookingId integer required The booking/event ID. Example: 1
     */
    public function downloadBookingInvoice(Request $request, int $bookingId)
    {
        $user = $request->user();

        $event = Event::where('client_id', $user->id)
            ->with(['eventVendors.vendor', 'payments'])
            ->findOrFail($bookingId);

        // Get or create invoice
        $invoice = Invoice::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('type', Invoice::TYPE_BOOKING)
            ->first();

        if (!$invoice) {
            $invoice = Invoice::createForBooking($event);
            $invoice->issue();
        }

        $invoice->load(['event', 'user', 'vendor']);

        return $invoice->downloadPdf();
    }

    // ========== Vendor Methods ==========

    /**
     * List vendor invoices
     *
     * Get all invoices/statements for the authenticated vendor.
     *
     * @queryParam type string Filter by type. Example: vendor_earning
     * @queryParam per_page integer Results per page. Example: 15
     */
    public function vendorInvoices(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $query = Invoice::where('vendor_id', $vendor->id)
            ->with(['event']);

        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        $invoices = $query->latest()->paginate($request->get('per_page', 15));

        return InvoiceResource::collection($invoices);
    }

    // ========== Admin Methods ==========

    /**
     * List all invoices (Admin)
     *
     * Get all invoices with filtering options.
     *
     * @queryParam type string Filter by type. Example: booking
     * @queryParam status string Filter by status. Example: issued
     * @queryParam user_id integer Filter by user. Example: 1
     * @queryParam per_page integer Results per page. Example: 20
     */
    public function adminIndex(Request $request)
    {
        $query = Invoice::with(['event', 'user', 'vendor']);

        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $invoices = $query->latest()->paginate($request->get('per_page', 20));

        // Stats
        $stats = [
            'total_invoices' => Invoice::count(),
            'total_amount' => Invoice::paid()->sum('total_amount'),
            'pending_amount' => Invoice::issued()->sum('total_amount'),
            'this_month' => Invoice::whereMonth('created_at', now()->month)->count(),
        ];

        return InvoiceResource::collection($invoices)->additional([
            'stats' => $stats,
        ]);
    }

    /**
     * View any invoice (Admin)
     *
     * Get details of any invoice.
     *
     * @urlParam id integer required The invoice ID. Example: 1
     */
    public function adminShow(Request $request, int $id)
    {
        $invoice = Invoice::with(['event', 'user', 'vendor'])->findOrFail($id);

        return new InvoiceResource($invoice);
    }

    /**
     * Download any invoice PDF (Admin)
     *
     * Download any invoice as PDF.
     *
     * @urlParam id integer required The invoice ID. Example: 1
     */
    public function adminDownload(Request $request, int $id)
    {
        $invoice = Invoice::with(['event', 'user', 'vendor'])->findOrFail($id);

        return $invoice->downloadPdf();
    }

    /**
     * Mark invoice as paid (Admin)
     *
     * Manually mark an invoice as paid.
     *
     * @urlParam id integer required The invoice ID. Example: 1
     */
    public function markAsPaid(Request $request, int $id)
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->isPaid()) {
            return response()->json([
                'message' => 'Invoice is already marked as paid',
            ], 422);
        }

        $invoice->markAsPaid();

        return response()->json([
            'message' => 'Invoice marked as paid',
            'invoice' => new InvoiceResource($invoice->fresh()),
        ]);
    }

    /**
     * Cancel invoice (Admin)
     *
     * Cancel an invoice.
     *
     * @urlParam id integer required The invoice ID. Example: 1
     */
    public function cancelInvoice(Request $request, int $id)
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->isPaid()) {
            return response()->json([
                'message' => 'Cannot cancel a paid invoice',
            ], 422);
        }

        if ($invoice->isCancelled()) {
            return response()->json([
                'message' => 'Invoice is already cancelled',
            ], 422);
        }

        $invoice->cancel();

        return response()->json([
            'message' => 'Invoice cancelled',
            'invoice' => new InvoiceResource($invoice->fresh()),
        ]);
    }
}

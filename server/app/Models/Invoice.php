<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'vendor_id',
        'invoice_number',
        'type',
        'title',
        'description',
        'subtotal',
        'discount_amount',
        'discount_description',
        'tax_rate',
        'tax_amount',
        'total_amount',
        'currency',
        'issued_at',
        'due_at',
        'paid_at',
        'status',
        'notes',
        'line_items',
        'billing_address',
        'metadata',
        'pdf_path',
        'pdf_generated_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'issued_at' => 'datetime',
        'due_at' => 'datetime',
        'paid_at' => 'datetime',
        'pdf_generated_at' => 'datetime',
        'line_items' => 'array',
        'billing_address' => 'array',
        'metadata' => 'array',
    ];

    const TYPE_PROFORMA = 'proforma';
    const TYPE_BOOKING = 'booking';
    const TYPE_RECEIPT = 'receipt';
    const TYPE_VENDOR_EARNING = 'vendor_earning';

    const STATUS_DRAFT = 'draft';
    const STATUS_ISSUED = 'issued';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    // Relationships
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    // Scopes
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeIssued($query)
    {
        return $query->where('status', self::STATUS_ISSUED);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    // Helpers
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isIssued(): bool
    {
        return $this->status === self::STATUS_ISSUED;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function getFormattedTotal(): string
    {
        return '₹' . number_format($this->total_amount, 2);
    }

    public function getFormattedSubtotal(): string
    {
        return '₹' . number_format($this->subtotal, 2);
    }

    /**
     * Generate a unique invoice number.
     */
    public static function generateInvoiceNumber(string $type = 'INV'): string
    {
        $prefix = match ($type) {
            self::TYPE_PROFORMA => 'PRO',
            self::TYPE_RECEIPT => 'RCP',
            self::TYPE_VENDOR_EARNING => 'VEN',
            default => 'INV',
        };

        $year = now()->format('Y');
        $lastInvoice = self::where('invoice_number', 'like', "{$prefix}-{$year}-%")
            ->orderByDesc('id')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -6);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s-%s-%06d', $prefix, $year, $newNumber);
    }

    /**
     * Create a booking invoice for an event.
     */
    public static function createForBooking(Event $event): self
    {
        $lineItems = [];

        // Add package or service line item
        if ($event->package_snapshot) {
            $lineItems[] = [
                'description' => $event->package_snapshot['name'] ?? 'Service Package',
                'details' => $event->package_snapshot['description'] ?? null,
                'quantity' => 1,
                'unit_price' => $event->total_amount,
                'amount' => $event->total_amount,
            ];
        } else {
            $lineItems[] = [
                'description' => 'Event Services - ' . ucfirst($event->type),
                'details' => $event->title,
                'quantity' => 1,
                'unit_price' => $event->total_amount,
                'amount' => $event->total_amount,
            ];
        }

        // Add assurance fee
        $lineItems[] = [
            'description' => 'Assurance Fee',
            'details' => 'Platform protection and backup vendor guarantee',
            'quantity' => 1,
            'unit_price' => $event->assurance_fee,
            'amount' => $event->assurance_fee,
        ];

        $subtotal = $event->total_amount + $event->assurance_fee;

        return self::create([
            'event_id' => $event->id,
            'user_id' => $event->client_id,
            'invoice_number' => self::generateInvoiceNumber(self::TYPE_BOOKING),
            'type' => self::TYPE_BOOKING,
            'title' => 'Invoice for ' . $event->title,
            'description' => 'Booking invoice for event on ' . $event->event_date->format('M d, Y'),
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_amount' => $subtotal,
            'currency' => 'INR',
            'status' => self::STATUS_DRAFT,
            'line_items' => $lineItems,
            'billing_address' => [
                'name' => $event->client->name,
                'email' => $event->client->email,
                'phone' => $event->client->phone,
            ],
        ]);
    }

    /**
     * Create a receipt for completed payment.
     */
    public static function createReceipt(Event $event, Payment $payment): self
    {
        $lineItems = [
            [
                'description' => ucfirst(str_replace('_', ' ', $payment->type)),
                'details' => 'Payment for ' . $event->title,
                'quantity' => 1,
                'unit_price' => $payment->amount,
                'amount' => $payment->amount,
            ],
        ];

        return self::create([
            'event_id' => $event->id,
            'user_id' => $event->client_id,
            'invoice_number' => self::generateInvoiceNumber(self::TYPE_RECEIPT),
            'type' => self::TYPE_RECEIPT,
            'title' => 'Payment Receipt',
            'description' => 'Receipt for ' . ucfirst(str_replace('_', ' ', $payment->type)),
            'subtotal' => $payment->amount,
            'total_amount' => $payment->amount,
            'currency' => 'INR',
            'status' => self::STATUS_PAID,
            'issued_at' => now(),
            'paid_at' => $payment->paid_at,
            'line_items' => $lineItems,
            'billing_address' => [
                'name' => $event->client->name,
                'email' => $event->client->email,
                'phone' => $event->client->phone,
            ],
            'metadata' => [
                'payment_id' => $payment->id,
                'payment_method' => $payment->payment_method,
                'transaction_id' => $payment->transaction_id,
            ],
        ]);
    }

    /**
     * Issue the invoice.
     */
    public function issue(): self
    {
        $this->update([
            'status' => self::STATUS_ISSUED,
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        return $this;
    }

    /**
     * Mark as paid.
     */
    public function markAsPaid(): self
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
        ]);

        return $this;
    }

    /**
     * Cancel the invoice.
     */
    public function cancel(): self
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);

        return $this;
    }

    /**
     * Generate PDF for the invoice.
     */
    public function generatePdf(): string
    {
        $pdf = Pdf::loadView('invoices.template', [
            'invoice' => $this,
            'event' => $this->event,
            'user' => $this->user,
            'vendor' => $this->vendor,
        ]);

        $filename = 'invoices/' . $this->invoice_number . '.pdf';
        Storage::disk('public')->put($filename, $pdf->output());

        $this->update([
            'pdf_path' => $filename,
            'pdf_generated_at' => now(),
        ]);

        return $filename;
    }

    /**
     * Get or generate PDF path.
     */
    public function getPdfPath(): string
    {
        if (!$this->pdf_path || !Storage::disk('public')->exists($this->pdf_path)) {
            $this->generatePdf();
        }

        return $this->pdf_path;
    }

    /**
     * Download the PDF.
     */
    public function downloadPdf()
    {
        $path = $this->getPdfPath();
        return Storage::disk('public')->download($path, $this->invoice_number . '.pdf');
    }

    /**
     * Stream the PDF (for viewing in browser).
     */
    public function streamPdf()
    {
        $pdf = Pdf::loadView('invoices.template', [
            'invoice' => $this,
            'event' => $this->event,
            'user' => $this->user,
            'vendor' => $this->vendor,
        ]);

        return $pdf->stream($this->invoice_number . '.pdf');
    }
}

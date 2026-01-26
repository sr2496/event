<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            padding: 40px;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 40px;
        }
        .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }
        .header-right {
            display: table-cell;
            width: 40%;
            vertical-align: top;
            text-align: right;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }
        .company-tagline {
            font-size: 11px;
            color: #666;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 10px;
        }
        .invoice-number {
            font-size: 14px;
            color: #666;
        }
        .invoice-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 10px;
        }
        .status-draft { background: #fef3c7; color: #92400e; }
        .status-issued { background: #dbeafe; color: #1e40af; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .info-box {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .info-box h3 {
            font-size: 11px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        .info-box p {
            margin-bottom: 3px;
        }
        .info-box .name {
            font-weight: bold;
            font-size: 14px;
            color: #1f2937;
        }

        .event-details {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .event-details h3 {
            font-size: 14px;
            color: #1f2937;
            margin-bottom: 15px;
        }
        .event-grid {
            display: table;
            width: 100%;
        }
        .event-item {
            display: table-cell;
            width: 25%;
        }
        .event-item .label {
            font-size: 10px;
            text-transform: uppercase;
            color: #666;
        }
        .event-item .value {
            font-size: 13px;
            font-weight: bold;
            color: #1f2937;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table.items th {
            background: #f3f4f6;
            padding: 12px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            color: #666;
            border-bottom: 2px solid #e5e7eb;
        }
        table.items th:last-child {
            text-align: right;
        }
        table.items td {
            padding: 15px 12px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        table.items td:last-child {
            text-align: right;
            white-space: nowrap;
        }
        table.items .item-description {
            font-weight: bold;
            color: #1f2937;
        }
        table.items .item-details {
            font-size: 11px;
            color: #666;
            margin-top: 3px;
        }

        .totals {
            width: 300px;
            margin-left: auto;
        }
        .totals table {
            width: 100%;
        }
        .totals td {
            padding: 8px 0;
        }
        .totals td:last-child {
            text-align: right;
            font-weight: bold;
        }
        .totals .subtotal {
            border-bottom: 1px solid #e5e7eb;
        }
        .totals .total {
            font-size: 16px;
            border-top: 2px solid #1f2937;
            padding-top: 12px;
        }
        .totals .total td {
            color: #1f2937;
        }

        .notes {
            margin-top: 40px;
            padding: 20px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 8px;
        }
        .notes h4 {
            font-size: 12px;
            color: #92400e;
            margin-bottom: 8px;
        }
        .notes p {
            font-size: 11px;
            color: #78350f;
        }

        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
        .footer p {
            margin-bottom: 5px;
        }

        .payment-info {
            margin-top: 30px;
            padding: 20px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
        }
        .payment-info h4 {
            font-size: 12px;
            color: #166534;
            margin-bottom: 10px;
        }
        .payment-info p {
            font-size: 11px;
            color: #14532d;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div class="company-name">Event Reliability Platform</div>
            <div class="company-tagline">Reliable Event Vendor Marketplace</div>
        </div>
        <div class="header-right">
            <div class="invoice-title">
                @if($invoice->type === 'receipt')
                    RECEIPT
                @elseif($invoice->type === 'proforma')
                    PROFORMA INVOICE
                @else
                    INVOICE
                @endif
            </div>
            <div class="invoice-number">#{{ $invoice->invoice_number }}</div>
            <div class="invoice-status status-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</div>
        </div>
    </div>

    <!-- Bill To / Invoice Details -->
    <div class="info-section">
        <div class="info-box">
            <h3>Bill To</h3>
            <p class="name">{{ $invoice->billing_address['name'] ?? $user->name }}</p>
            <p>{{ $invoice->billing_address['email'] ?? $user->email }}</p>
            @if(!empty($invoice->billing_address['phone']))
                <p>{{ $invoice->billing_address['phone'] }}</p>
            @endif
        </div>
        <div class="info-box" style="text-align: right;">
            <h3>Invoice Details</h3>
            <p><strong>Date:</strong> {{ $invoice->issued_at ? $invoice->issued_at->format('M d, Y') : now()->format('M d, Y') }}</p>
            @if($invoice->due_at)
                <p><strong>Due Date:</strong> {{ $invoice->due_at->format('M d, Y') }}</p>
            @endif
            @if($invoice->paid_at)
                <p><strong>Paid On:</strong> {{ $invoice->paid_at->format('M d, Y') }}</p>
            @endif
        </div>
    </div>

    <!-- Event Details -->
    @if($event)
    <div class="event-details">
        <h3>Event Information</h3>
        <div class="event-grid">
            <div class="event-item">
                <div class="label">Event</div>
                <div class="value">{{ $event->title }}</div>
            </div>
            <div class="event-item">
                <div class="label">Type</div>
                <div class="value">{{ ucfirst($event->type) }}</div>
            </div>
            <div class="event-item">
                <div class="label">Date</div>
                <div class="value">{{ $event->event_date->format('M d, Y') }}</div>
            </div>
            <div class="event-item">
                <div class="label">Location</div>
                <div class="value">{{ $event->city }}</div>
            </div>
        </div>
    </div>
    @endif

    <!-- Line Items -->
    <table class="items">
        <thead>
            <tr>
                <th style="width: 60%;">Description</th>
                <th style="width: 10%;">Qty</th>
                <th style="width: 15%;">Unit Price</th>
                <th style="width: 15%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->line_items ?? [] as $item)
            <tr>
                <td>
                    <div class="item-description">{{ $item['description'] }}</div>
                    @if(!empty($item['details']))
                        <div class="item-details">{{ $item['details'] }}</div>
                    @endif
                </td>
                <td>{{ $item['quantity'] ?? 1 }}</td>
                <td>{{ number_format($item['unit_price'], 2) }}</td>
                <td>{{ number_format($item['amount'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals">
        <table>
            <tr class="subtotal">
                <td>Subtotal</td>
                <td>{{ number_format($invoice->subtotal, 2) }}</td>
            </tr>
            @if($invoice->discount_amount > 0)
            <tr>
                <td>Discount {{ $invoice->discount_description ? "({$invoice->discount_description})" : '' }}</td>
                <td>-{{ number_format($invoice->discount_amount, 2) }}</td>
            </tr>
            @endif
            @if($invoice->tax_amount > 0)
            <tr>
                <td>Tax ({{ $invoice->tax_rate }}%)</td>
                <td>{{ number_format($invoice->tax_amount, 2) }}</td>
            </tr>
            @endif
            <tr class="total">
                <td>Total ({{ $invoice->currency }})</td>
                <td>{{ number_format($invoice->total_amount, 2) }}</td>
            </tr>
        </table>
    </div>

    <!-- Payment Info (for receipts) -->
    @if($invoice->type === 'receipt' && !empty($invoice->metadata['payment_method']))
    <div class="payment-info">
        <h4>Payment Information</h4>
        <p><strong>Payment Method:</strong> {{ ucfirst(str_replace('_', ' ', $invoice->metadata['payment_method'])) }}</p>
        @if(!empty($invoice->metadata['transaction_id']))
            <p><strong>Transaction ID:</strong> {{ $invoice->metadata['transaction_id'] }}</p>
        @endif
    </div>
    @endif

    <!-- Notes -->
    @if($invoice->notes)
    <div class="notes">
        <h4>Notes</h4>
        <p>{{ $invoice->notes }}</p>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Thank you for choosing Event Reliability Platform!</p>
        <p>For questions about this invoice, please contact support@eventplatform.com</p>
        <p>This is a computer-generated document and does not require a signature.</p>
    </div>
</body>
</html>

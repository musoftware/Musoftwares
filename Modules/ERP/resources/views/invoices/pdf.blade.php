<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=JetBrains+Mono:wght@400;500;700&family=Sora:wght@400;600;700&display=swap');

        :root {
            --background: #f8f9fc;
            --surface: #ffffff;
            --surface-raised: #f1f4f9;
            --border: #e2e8f0;
            --border-strong: #cbd5e1;
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --primary-light: #eef2ff;
            --success: #10b981;
            --success-light: #ecfdf5;
            --warning: #f59e0b;
            --warning-light: #fffbeb;
            --danger: #ef4444;
            --danger-light: #fef2f2;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #94a3b8;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            color: #0f172a;
            margin: 0;
            padding: 0;
            position: relative;
        }

        .container {
            width: 100%;
            padding: 48px;
            box-sizing: border-box;
            background-color: #ffffff;
        }

        /* Fonts */
        .heading {
            font-family: 'Sora', sans-serif;
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
        }

        .money-code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            opacity: 0.1;
            z-index: -1;
            pointer-events: none;
            white-space: nowrap;
        }

        .watermark.draft {
            color: #ef4444; /* danger */
        }

        .watermark.paid {
            color: #10b981; /* success */
        }

        /* Header Layout */
        .header-table {
            width: 100%;
            margin-bottom: 48px;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo {
            max-width: 150px;
            max-height: 50px;
        }

        .company-info {
            color: #475569;
            font-size: 13px;
            line-height: 1.5;
        }

        .invoice-title {
            font-family: 'Sora', sans-serif;
            font-size: 32px;
            font-weight: 700;
            color: #6366f1; /* primary */
            text-align: right;
            margin-bottom: 8px;
        }

        .invoice-details {
            text-align: right;
            font-size: 13px;
            color: #475569;
            line-height: 1.5;
        }

        /* Bill To Section */
        .bill-to-section {
            margin-bottom: 32px;
        }

        .bill-to-title {
            font-size: 12px;
            font-weight: 700;
            color: #94a3b8; /* text-muted */
            text-transform: uppercase;
            margin-bottom: 8px;
            letter-spacing: 0.05em;
        }

        .client-info {
            font-size: 14px;
            color: #0f172a;
            line-height: 1.5;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 32px;
        }

        .items-table th {
            background-color: #f8f9fc;
            padding: 12px;
            text-align: left;
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            border-bottom: 1px solid #cbd5e1; /* border-strong */
            border-top: 1px solid #e2e8f0;
        }

        .items-table th.text-right {
            text-align: right;
        }

        .items-table th.text-center {
            text-align: center;
        }

        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
            vertical-align: top;
        }

        .items-table tr:nth-child(even) td {
            background-color: #f8f9fc;
        }

        .items-table td.text-right {
            text-align: right;
        }

        .items-table td.text-center {
            text-align: center;
        }

        .item-title {
            font-weight: 500;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .item-desc {
            font-size: 12px;
            color: #475569;
        }

        /* Totals Section */
        .totals-container {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table {
            width: 350px;
            float: right;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 8px 12px;
            font-size: 14px;
        }

        .totals-table td:first-child {
            text-align: right;
            color: #475569;
        }

        .totals-table td:last-child {
            text-align: right;
        }

        .totals-table tr.total-row td {
            font-weight: 700;
            color: #6366f1; /* primary */
            font-size: 16px;
            border-top: 1px solid #cbd5e1; /* border-strong */
            padding-top: 12px;
        }

        .totals-table tr.currency-row td {
            color: #475569;
            font-size: 13px;
        }

        /* Notes Section */
        .notes-section {
            margin-top: 64px;
            clear: both;
            border-top: 1px solid #e2e8f0;
            padding-top: 16px;
        }

        .notes-title {
            font-size: 12px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            margin-bottom: 8px;
            letter-spacing: 0.05em;
        }

        .notes-content {
            font-size: 13px;
            color: #475569;
            line-height: 1.5;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    @if($invoice->status === 'draft')
        <div class="watermark draft">DRAFT</div>
    @elseif($invoice->status === 'paid' || $invoice->paid_at)
        <div class="watermark paid">PAID</div>
    @endif

    <div class="container">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td style="width: 50%;">
                    @if(config('app.logo'))
                        <!-- Ensure logo is accessible to dompdf -->
                        <img src="{{ public_path(config('app.logo')) }}" alt="Business Logo" class="logo" style="margin-bottom: 16px;">
                    @else
                        <!-- Fallback Business Name if no logo -->
                        <div class="heading" style="margin-bottom: 16px;">{{ config('app.name', 'Business Name') }}</div>
                    @endif

                    <div class="company-info">
                        Business Name<br>
                        Address Line 1<br>
                        Address Line 2<br>
                        Phone / Email
                    </div>
                </td>
                <td style="width: 50%;">
                    <div class="invoice-title">INVOICE</div>
                    <div class="invoice-details">
                        <span class="money-code">{{ $invoice->invoice_number }}</span><br>
                        Issued: {{ $invoice->issued_at ? $invoice->issued_at->format('M j, Y') : $invoice->created_at->format('M j, Y') }}<br>
                        Due: {{ $invoice->due_date ? $invoice->due_date->format('M j, Y') : '-' }}
                    </div>
                </td>
            </tr>
        </table>

        <!-- Bill To -->
        <div class="bill-to-section">
            <div class="bill-to-title">BILL TO:</div>
            <div class="client-info">
                <strong>{{ $invoice->client->name }}</strong><br>
                @if($invoice->client->email)
                    {{ $invoice->client->email }}<br>
                @endif
                @if($invoice->client->phone)
                    {{ $invoice->client->phone }}<br>
                @endif
                {{-- Add additional client address fields if they exist on the model --}}
                Cairo, Egypt
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;" class="text-center">#</th>
                    <th style="width: 50%;">Description</th>
                    <th style="width: 15%;" class="text-center">Qty</th>
                    <th style="width: 15%;" class="text-right">Rate</th>
                    <th style="width: 15%;" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoice->items as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            <div class="item-title">{{ $item->title }}</div>
                            @if($item->description)
                                <div class="item-desc">{{ $item->description }}</div>
                            @endif

                            {{-- Timer sessions handling --}}
                            @if($item->type === 'timer' || ($item->quantity > 0 && $item->unit_price > 0 && strpos($item->description, 'min') !== false))
                                <div class="item-desc money-code" style="margin-top: 4px;">{{ $item->quantity }} min &times; {{ number_format($item->unit_price, 2) }} {{ $invoice->amount_currency }}</div>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($item->type === 'timer' || strpos(strtolower($item->title), 'min') !== false)
                                {{ $item->quantity }}m
                            @else
                                {{ $item->quantity }}
                            @endif
                        </td>
                        <td class="text-right money-code">
                            @if($item->type === 'timer')
                                {{ number_format($item->unit_price, 2) }}/min
                            @else
                                {{ number_format($item->unit_price, 2) }}
                            @endif
                        </td>
                        <td class="text-right money-code">{{ number_format($item->total, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center" style="padding: 24px; color: #94a3b8;">No items found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-container">
            <table class="totals-table">
                @php
                    $subtotal = $invoice->items->sum('total') ?? $invoice->amount;
                @endphp
                <tr>
                    <td>Subtotal:</td>
                    <td class="money-code">{{ number_format($subtotal, 2) }}</td>
                </tr>
                @if($invoice->discount_amount > 0)
                    <tr>
                        <td>Discount:</td>
                        <td class="money-code">-{{ number_format($invoice->discount_amount, 2) }}</td>
                    </tr>
                @endif
                @if($invoice->tax_amount > 0)
                    <tr>
                        <td>Tax ({{ number_format($invoice->tax_rate, 0) }}%):</td>
                        <td class="money-code">{{ number_format($invoice->tax_amount, 2) }}</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td>TOTAL:</td>
                    <td class="money-code">{{ number_format($invoice->amount, 2) }}</td>
                </tr>
                <tr class="currency-row">
                    <td>Currency:</td>
                    <td>{{ $invoice->amount_currency }}</td>
                </tr>
                @if($invoice->business_amount && $invoice->amount_currency !== $invoice->business_currency)
                    <tr class="currency-row">
                        <td>In {{ $invoice->business_currency }}:</td>
                        <td class="money-code">{{ number_format($invoice->business_amount, 2) }}</td>
                    </tr>
                @endif
            </table>
            <div style="clear: both;"></div>
        </div>

        <!-- Notes -->
        <div class="notes-section">
            <div class="notes-title">NOTES:</div>
            <div class="notes-content">
                @if($invoice->notes)
                    {!! nl2br(e($invoice->notes)) !!}
                @else
                    Thank you for your business.<br><br>
                    @if($invoice->due_date)
                        Payment due by {{ $invoice->due_date->format('M j, Y') }}.
                    @else
                        Payment due upon receipt.
                    @endif
                @endif
            </div>
        </div>

        <!-- Page Number footer using DomPDF features -->
        <script type="text/php">
            if (isset($pdf)) {
                $x = 540;
                $y = 820;
                $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
                $font = $fontMetrics->get_font("DM Sans", "normal");
                $size = 10;
                $color = array(0.58, 0.64, 0.72); // #94a3b8 text-muted
                $word_space = 0.0;
                $char_space = 0.0;
                $angle = 0.0;
                $pdf->page_text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
            }
        </script>
    </div>
</body>
</html>
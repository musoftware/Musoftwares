<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ __('erp.receipt') }} {{ $invoice->invoice_number }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap');

        body {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 8px;
            width: 200px; /* Leave some margin for 226pt total width */
        }

        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-start { text-align: left; }
        .font-bold { font-weight: 700; }
        .mb-1 { margin-bottom: 4px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-4 { margin-bottom: 16px; }
        .mt-2 { margin-top: 8px; }
        .mt-4 { margin-top: 16px; }
        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }

        .header h1 {
            font-size: 14px;
            margin: 0 0 4px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            vertical-align: top;
            padding: 2px 0;
        }

        .items-table th {
            border-bottom: 1px dashed #000;
            padding-bottom: 4px;
            margin-bottom: 4px;
        }

        .item-name {
            display: block;
            margin-bottom: 2px;
        }

        .totals td {
            padding: 4px 0;
        }

        .totals .grand-total td {
            font-size: 13px;
            font-weight: 700;
            border-top: 1px dashed #000;
        }
    </style>
</head>
<body>

    <div class="header text-center mb-4">
        @if(config('app.logo'))
            <img src="{{ public_path(config('app.logo')) }}" alt="Logo" style="max-width: 100px; max-height: 40px; margin-bottom: 8px;">
        @endif
        <h1>{{ $invoice->client->name }}</h1>
        @if($invoice->client->email)
            <div>{{ $invoice->client->email }}</div>
        @endif
        @if($invoice->client->phone)
            <div>{{ $invoice->client->phone }}</div>
        @endif
    </div>

    <div class="mb-4">
        <div class="font-bold">{{ strtoupper(__('erp.invoice')) }}: {{ $invoice->invoice_number }}</div>
        <div>{{ __('erp.date') }}: {{ $invoice->issued_at ? $invoice->issued_at->format('M j, Y') : $invoice->created_at->format('M j, Y') }}</div>
        <div>{{ __('erp.status') }}: {{ strtoupper(__('erp.status_' . $invoice->status)) }}</div>
    </div>

    <div class="divider"></div>

    <table class="items-table mb-2">
        <thead>
            <tr>
                <th class="text-start">{{ __('erp.item') }}</th>
                <th class="text-center">{{ __('erp.qty') }}</th>
                <th class="text-end">{{ __('erp.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td colspan="3" class="text-start font-bold" style="padding-top:4px;">{{ $item->title }}</td>
                </tr>
                <tr>
                    <td class="text-start" style="color: #444;">{{ \App\Helpers\FinanceHelper::instance()->format_money($item->unit_price, $invoice->currency_id) }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-end">{{ \App\Helpers\FinanceHelper::instance()->format_money($item->total, $invoice->currency_id) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <table class="totals mb-4">
        @php
            $subtotal = $invoice->items->sum('total') ?? $invoice->amount;
        @endphp
        <tr>
            <td class="text-start">{{ __('erp.subtotal') }}</td>
            <td class="text-end">{{ \App\Helpers\FinanceHelper::instance()->format_money($subtotal, $invoice->currency_id) }}</td>
        </tr>
        @if($invoice->discount_amount > 0)
            <tr>
                <td class="text-start">{{ __('erp.discount') }}</td>
                <td class="text-end">-{{ \App\Helpers\FinanceHelper::instance()->format_money($invoice->discount_amount, $invoice->currency_id) }}</td>
            </tr>
        @endif
        @if($invoice->tax_amount > 0)
            <tr>
                <td class="text-start">{{ __('erp.tax') }} ({{ number_format($invoice->tax_rate, 0) }}%)</td>
                <td class="text-end">{{ \App\Helpers\FinanceHelper::instance()->format_money($invoice->tax_amount, $invoice->currency_id) }}</td>
            </tr>
        @endif
        <tr class="grand-total">
            <td class="text-start">{{ strtoupper(__('erp.total')) }}</td>
            <td class="text-end">{{ \App\Helpers\FinanceHelper::instance()->format_money($invoice->amount, $invoice->currency_id) }}</td>
        </tr>
    </table>

    <div class="text-center mt-4">
        <div class="font-bold mb-1">{{ __('erp.thank_you') }}</div>
        @if($invoice->notes)
            <div style="font-size: 10px;">{{ $invoice->notes }}</div>
        @endif

        @if(!($invoice->tenant?->user?->hasModuleSubscription('erp-white-label')))
            <div style="font-size: 10px; margin-top: 12px; color: #666;">
                {{ __('erp.powered_by') }}
            </div>
        @endif
    </div>

</body>
</html>

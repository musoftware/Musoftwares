<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>{{ $user->name }} - {{ __('general.balance_sheet') }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: sans-serif;
            background: #ffffff;
            color: #374151;
            line-height: 1.5;
            font-size: 13px;
        }

        .container {
            width: 100%;
            margin: 0 auto;
            background: #ffffff;
        }

        /* Header - Using table layout for DomPDF compatibility */
        .header-table {
            width: 100%;
            background: #1a1a1a;
            color: white;
            padding: 20px 25px;
        }

        .header-table td {
            vertical-align: top;
            color: white;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .header-subtitle {
            font-size: 12px;
            opacity: 0.9;
        }

        .company-info {
            text-align: right;
            font-size: 12px;
            line-height: 1.8;
        }

        /* Client Info */
        .client-section {
            padding: 20px 25px;
        }

        .client-box {
            background: #f8f9fa;
            border-left: 4px solid #1a1a1a;
            padding: 15px 20px;
        }

        .client-name {
            font-size: 16px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 8px;
        }

        .client-details {
            font-size: 12px;
            color: #6b7280;
            line-height: 1.8;
        }

        /* Section Title */
        .section-title {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #1a1a1a;
            padding: 15px 25px 10px;
            border-bottom: 2px solid #e5e7eb;
            margin: 0 25px;
        }

        /* Invoice Table */
        .table-section {
            padding: 10px 25px 20px;
        }

        table.invoice-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        table.invoice-table thead th {
            background: #f3f4f6;
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1a1a1a;
            border-bottom: 2px solid #d1d5db;
        }

        table.invoice-table thead th.text-end {
            text-align: right;
        }

        table.invoice-table tbody td {
            padding: 10px 8px;
            border-bottom: 1px solid #f3f4f6;
            color: #374151;
        }

        table.invoice-table tbody td.text-end {
            text-align: right;
        }

        .invoice-id {
            font-weight: bold;
            color: #1a1a1a;
        }

        .amount-unpaid {
            color: #dc2626;
            font-weight: bold;
        }

        .date-cell {
            color: #6b7280;
            font-size: 11px;
        }

        .empty-row td {
            text-align: center;
            padding: 20px;
            color: #9ca3af;
        }

        /* Summary Section */
        .summary-section {
            padding: 0 25px 25px;
        }

        table.summary-table {
            width: 100%;
            border-collapse: collapse;
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
        }

        table.summary-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
        }

        table.summary-table tr:last-child td {
            border-bottom: none;
        }

        .summary-label {
            font-weight: bold;
            color: #1a1a1a;
        }

        .summary-value {
            text-align: right;
            font-weight: bold;
            font-size: 14px;
            color: #1a1a1a;
        }

        .highlight-label {
            color: #dc2626;
            font-weight: bold;
        }

        .highlight-value {
            text-align: right;
            color: #dc2626;
            font-weight: bold;
            font-size: 16px;
        }

        .positive-value {
            text-align: right;
            color: #059669;
            font-weight: bold;
            font-size: 16px;
        }

        .total-row td {
            border-top: 2px solid #d1d5db;
            padding-top: 15px;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 15px;
            font-size: 10px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>

<body>
    <div class="container">
        {{-- Header --}}
        <table class="header-table" cellpadding="0" cellspacing="0">
            <tr>
                <td style="width: 60%;">
                    <div class="company-name">{{ \App\Models\AdminSettings::GetValue('business_name', 'Musoftware') }}</div>
                    <div class="header-subtitle">{{ __('general.balance_sheet') }}</div>
                </td>
                <td class="company-info">
                    @if (!empty(\App\Models\AdminSettings::GetValue('business_phone')))
                        <div>{{ \App\Models\AdminSettings::GetValue('business_phone') }}</div>
                    @endif
                    @if (!empty(\App\Models\AdminSettings::GetValue('business_address')))
                        <div>{{ \App\Models\AdminSettings::GetValue('business_address') }}</div>
                    @endif
                    @if (!empty(\App\Models\AdminSettings::GetValue('business_email')))
                        <div>{{ \App\Models\AdminSettings::GetValue('business_email') }}</div>
                    @endif
                </td>
            </tr>
        </table>

        {{-- Client Info --}}
        <div class="client-section">
            <div class="client-box">
                <div class="client-name">{{ $user->name }}</div>
                <div class="client-details">
                    @php
                        $phones = array_filter([
                            $user->phone ?? null,
                            $user->mobile_1 ?? null,
                            $user->mobile_2 ?? null,
                            $user->whatsapp_number ?? null,
                        ]);
                    @endphp
                    @if (count($phones) > 0)
                        <div>{{ implode(' / ', $phones) }}</div>
                    @endif
                    @if ($user->email)
                        <div>{{ $user->email }}</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Invoice Table --}}
        <div class="section-title">{{ __('general.invoice_history') }}</div>
        <div class="table-section">
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>{{ __('erp.invoice_2') }}</th>
                        <th class="text-end">{{ __('general.paid') }}</th>
                        <th class="text-end">{{ __('general.total') }}</th>
                        <th class="text-end">{{ __('general.discount') }}</th>
                        <th class="text-end">{{ __('general.remaining') }}</th>
                        <th class="text-end">{{ __('general.date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td class="invoice-id">{{ $invoice->enc_id() }}</td>
                            <td class="text-end">{{ $invoice->paid_str() }}</td>
                            <td class="text-end">{{ $invoice->sub_total_str() }}</td>
                            <td class="text-end">{{ $invoice->discount }}</td>
                            <td class="text-end amount-unpaid">{{ $invoice->unpaid_str() }}</td>
                            <td class="text-end date-cell">{{ $invoice->created_at->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="6">{{ __('general.no_unpaid_invoices') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Summary --}}
        <div class="summary-section">
            <table class="summary-table" cellpadding="0" cellspacing="0">
                @if ($user->user_balance != 0)
                    <tr>
                        <td class="summary-label">{{ __('general.sub_remain') }}</td>
                        <td class="summary-value">{{ App\Helpers\FinanceHelper::instance()->format_money($unpaid, $user->currency_id) }}</td>
                    </tr>
                    @if ($user->user_balance < $unpaid)
                        <tr>
                            <td class="summary-label">{{ __('general.balance') }}</td>
                            <td class="summary-value">{{ App\Helpers\FinanceHelper::instance()->format_money($user->user_balance, $user->currency_id) }}</td>
                        </tr>
                        <tr class="total-row">
                            <td class="highlight-label">{{ __('general.client_pay') }}</td>
                            <td class="highlight-value">{{ App\Helpers\FinanceHelper::instance()->format_money($unpaid - $user->user_balance, $user->currency_id) }}</td>
                        </tr>
                    @else
                        <tr>
                            <td class="summary-label">{{ __('general.balance') }}</td>
                            <td class="summary-value">{{ App\Helpers\FinanceHelper::instance()->format_money($user->user_balance, $user->currency_id) }}</td>
                        </tr>
                        <tr class="total-row">
                            <td class="summary-label">{{ __('general.client_remaining') }}</td>
                            <td class="positive-value">{{ App\Helpers\FinanceHelper::instance()->format_money($user->user_balance - $unpaid, $user->currency_id) }}</td>
                        </tr>
                    @endif
                @elseif ($user->user_balance == 0)
                    <tr class="total-row">
                        <td class="highlight-label">{{ __('general.client_pay') }}</td>
                        <td class="highlight-value">{{ App\Helpers\FinanceHelper::instance()->format_money($unpaid, $user->currency_id) }}</td>
                    </tr>
                @endif
            </table>
        </div>

        {{-- Footer --}}
        <div class="footer">
            {{ __('general.generated_on') }}: {{ now()->format('Y-m-d H:i') }}
        </div>
    </div>
</body>

</html>

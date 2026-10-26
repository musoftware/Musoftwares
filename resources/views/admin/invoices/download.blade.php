@php
    // Rendered by DomPDF: only CSS 2.1 tables/floats, and the bundled DejaVu Sans font (remote fonts are not loaded).
    $money = fn ($amount, $currencyId = null) => \App\Helpers\FinanceHelper::instance()->format_money($amount, $currencyId ?? $invoice->currency_id);
    $business = [
        'name' => \App\Models\AdminSettings::GetValue('business_name', 'Musoftware') ?: 'Musoftware',
        'address' => \App\Models\AdminSettings::GetValue('business_address', 'Suez, Egypt') ?: 'Suez, Egypt',
        'email' => \App\Models\AdminSettings::GetValue('business_email', 'admin@musoftwares.com') ?: 'admin@musoftwares.com',
        'phone' => \App\Models\AdminSettings::GetValue('business_phone', '') ?: '',
    ];
    $logo = file_exists(public_path('favicon.png')) ? public_path('favicon.png') : null;

    $status = $invoice->status ?: 'unpaid';
    $paid = round((float) $invoice->paid, 2);
    $total = round((float) $invoice->total(), 2);
    $balance = max(0, round($total - $paid, 2));
    $dueDate = $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date) : null;
    $isOpen = in_array($status, ['unpaid', 'partially_paid'], true) && $balance > 0;
    $isOverdue = $isOpen && $dueDate && $dueDate->copy()->endOfDay()->isPast();
    $statusColors = [
        'paid' => ['#ecfdf5', '#047857'], 'partially_paid' => ['#fffbeb', '#b45309'], 'unpaid' => ['#eff6ff', '#0071e3'],
        'cancelled' => ['#f5f5f7', '#6e6e73'], 'refunded' => ['#f5f5f7', '#6e6e73'], 'draft' => ['#f5f5f7', '#6e6e73'],
    ];
    [$statusBg, $statusFg] = $isOverdue ? ['#fef2f2', '#dc2626'] : ($statusColors[$status] ?? $statusColors['unpaid']);
    $statusLabel = $isOverdue ? __('invoice_pdf.overdue') : __('invoice_pdf.status.'.$status);

    $subtotal = $invoice->sub_total();
    $discount = (float) $invoice->discount + (float) $invoice->second_discount;
    $tax = (float) $invoice->tax();
    $taxRate = $user && $user->invoice_taxable == '1' ? \App\Models\AdminSettings::GetValue('business_tax', 22.5) : 0;
    $businessCurrencyId = \App\Models\AdminSettings::GetValue('business_currency', 2);
    $businessCurrency = \App\Models\Currency::find($businessCurrencyId);
    $payUrl = $invoice->uuid && \Illuminate\Support\Facades\Route::has('billing.invoices.pay') ? route('billing.invoices.pay', $invoice->uuid) : null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ __('invoice_pdf.invoice') }} {{ $invoice->enc_id() }}</title>
    <style>
        @page { margin: 40px 44px 60px; }
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { font-size: 10.5px; color: #1d1d1f; margin: 0; line-height: 1.45; }
        table { border-collapse: collapse; width: 100%; }
        td, th { vertical-align: top; }
        .muted { color: #6e6e73; }
        .small { font-size: 9px; }
        .right { text-align: right; }
        .center { text-align: center; }
        .brand { font-size: 17px; font-weight: bold; color: #1d1d1f; }
        .title { font-size: 26px; font-weight: bold; color: #0071e3; letter-spacing: 1px; }
        .status { display: inline-block; padding: 4px 10px; border-radius: 10px; font-size: 10px; font-weight: bold; }
        .box { background: #f5f5f7; border-radius: 8px; padding: 12px 14px; }
        .label { font-size: 8.5px; font-weight: bold; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 4px; }
        .items th { background: #1d1d1f; color: #ffffff; font-size: 9px; font-weight: bold; padding: 8px 10px; text-align: left; }
        .items th.right { text-align: right; }
        .items th.center { text-align: center; }
        .items td { padding: 9px 10px; border-bottom: 1px solid #e8e8ed; }
        .items tr.alt td { background: #fafafa; }
        .totals td { padding: 5px 10px; }
        .totals .grand td { font-size: 13px; font-weight: bold; border-top: 1px solid #d2d2d7; padding-top: 9px; }
        .totals .balance td { font-size: 13px; font-weight: bold; color: #ffffff; background: #0071e3; }
        .num { white-space: nowrap; }
        .pay { border: 1px solid #0071e3; border-radius: 8px; padding: 12px 14px; }
        .pay a { color: #0071e3; text-decoration: none; word-break: break-all; }
        .footer { position: fixed; bottom: -36px; left: 0; right: 0; font-size: 8.5px; color: #86868b; border-top: 1px solid #e8e8ed; padding-top: 6px; }
    </style>
</head>
<body>
    <div class="footer">
        <table><tr>
            <td>{{ $business['name'] }} · {{ $business['address'] }} · {{ $business['email'] }}@if($business['phone']) · {{ $business['phone'] }}@endif</td>
            <td class="right">{{ __('invoice_pdf.invoice') }} {{ $invoice->enc_id() }}</td>
        </tr></table>
    </div>

    {{-- Header: who issues the invoice, and the invoice itself --}}
    <table>
        <tr>
            <td style="width: 55%;">
                <table style="width: auto;"><tr>
                    @if($logo)<td style="padding-right: 10px; vertical-align: middle;"><img src="{{ $logo }}" style="width: 38px; height: 38px;" alt=""></td>@endif
                    <td style="vertical-align: middle;"><div class="brand">{{ $business['name'] }}</div><div class="muted small">{{ $business['address'] }}</div></td>
                </tr></table>
                <div class="muted small" style="margin-top: 8px;">
                    {{ $business['email'] }}@if($business['phone'])<br>{{ $business['phone'] }}@endif<br>www.musoftwares.com
                </div>
            </td>
            <td style="width: 45%;" class="right">
                <div class="title">{{ strtoupper(__('invoice_pdf.invoice')) }}</div>
                <div style="margin-top: 6px;"><span class="status" style="background: {{ $statusBg }}; color: {{ $statusFg }};">{{ $statusLabel }}</span></div>
            </td>
        </tr>
    </table>

    {{-- Bill to + invoice facts --}}
    <table style="margin-top: 22px;">
        <tr>
            <td style="width: 55%; padding-right: 12px;">
                <div class="box">
                    <div class="label">{{ __('invoice_pdf.bill_to') }}</div>
                    <div style="font-size: 12px; font-weight: bold;">{{ $user->name ?? '-' }}</div>
                    @if($user?->email)<div class="muted">{{ $user->email }}</div>@endif
                    @if($user?->phone_number ?? $user?->phone)<div class="muted">{{ $user->phone_number ?? $user->phone }}</div>@endif
                    @if($project)<div class="muted" style="margin-top: 4px;">{{ __('invoice_pdf.project') }}: {{ $project->project_name ?? $project->name }}</div>@endif
                </div>
            </td>
            <td style="width: 45%;">
                <table>
                    <tr><td class="muted">{{ __('invoice_pdf.number') }}</td><td class="right" style="font-weight: bold;">{{ $invoice->enc_id() }}</td></tr>
                    <tr><td class="muted">{{ __('invoice_pdf.issued') }}</td><td class="right">{{ $invoice->created_at?->format('M j, Y') }}</td></tr>
                    <tr><td class="muted">{{ __('invoice_pdf.due') }}</td><td class="right" @if($isOverdue) style="color: #dc2626; font-weight: bold;" @endif>{{ $dueDate ? $dueDate->format('M j, Y') : __('invoice_pdf.upon_receipt') }}</td></tr>
                    <tr><td class="muted">{{ __('invoice_pdf.balance') }}</td><td class="right num" style="font-weight: bold;">{{ $money($balance) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Items --}}
    <table class="items" style="margin-top: 22px;">
        <thead>
            <tr>
                <th style="width: 6%;" class="center">#</th>
                <th style="width: 50%;">{{ __('invoice_pdf.item') }}</th>
                <th style="width: 10%;" class="center">{{ __('invoice_pdf.qty') }}</th>
                <th style="width: 17%;" class="right">{{ __('invoice_pdf.unit') }}</th>
                <th style="width: 17%;" class="right">{{ __('invoice_pdf.amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice_items as $index => $item)
                @php $qty = (float) ($item->qty ?: 1); @endphp
                <tr @if($index % 2) class="alt" @endif>
                    <td class="center muted">{{ $index + 1 }}</td>
                    <td style="font-weight: bold;">{{ $item->item_title }}</td>
                    <td class="center">{{ rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.') }}</td>
                    <td class="right num">{{ $money($qty > 0 ? $item->total() / $qty : 0) }}</td>
                    <td class="right num" style="font-weight: bold;">{{ $money($item->total()) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="center muted" style="padding: 18px;">{{ __('invoice_pdf.no_items') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Payment instructions (left) and totals (right) --}}
    <table style="margin-top: 18px;">
        <tr>
            <td style="width: 52%; padding-right: 18px;">
                @if($isOpen && $payUrl)
                    <div class="pay">
                        <div class="label" style="color: #0071e3;">{{ __('invoice_pdf.pay_online') }}</div>
                        <div class="muted">{{ __('invoice_pdf.pay_hint') }}</div>
                        <div style="margin-top: 4px;"><a href="{{ $payUrl }}">{{ $payUrl }}</a></div>
                    </div>
                @endif
            </td>
            <td style="width: 48%;">
                <table class="totals">
                    <tr><td class="muted">{{ __('invoice_pdf.subtotal') }}</td><td class="right num">{{ $money($subtotal) }}</td></tr>
                    @if($discount > 0)<tr><td class="muted">{{ __('invoice_pdf.discount') }}</td><td class="right num">-{{ $money($discount) }}</td></tr>@endif
                    @if($tax > 0)<tr><td class="muted">{{ __('invoice_pdf.tax', ['rate' => number_format((float) $taxRate, 0)]) }}</td><td class="right num">{{ $money($tax) }}</td></tr>@endif
                    <tr class="grand"><td>{{ __('invoice_pdf.total') }}</td><td class="right num">{{ $money($total) }}</td></tr>
                    @if($paid > 0)<tr><td class="muted">{{ __('invoice_pdf.paid') }}</td><td class="right num">-{{ $money($paid) }}</td></tr>@endif
                    @if($status !== 'cancelled')
                        @if($balance > 0)
                            <tr class="balance"><td>{{ __('invoice_pdf.balance') }}</td><td class="right num">{{ $money($balance) }}</td></tr>
                        @else
                            <tr class="balance"><td style="background: #047857;">{{ __('invoice_pdf.paid_in_full') }}</td><td class="right" style="background: #047857;">&#10003;</td></tr>
                        @endif
                    @endif
                    @if($invoice->currency_id != $businessCurrencyId && $invoice->business_total())
                        <tr><td class="muted small">{{ __('invoice_pdf.in_currency', ['currency' => $businessCurrency?->currency ?? '']) }}</td><td class="right num muted small">{{ $money($invoice->business_total(), $businessCurrencyId) }}</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- Notes --}}
    <div style="margin-top: 26px; border-top: 1px solid #e8e8ed; padding-top: 12px;">
        <div class="label">{{ __('invoice_pdf.notes') }}</div>
        <div class="muted">
            @if(! empty($invoice->notes))
                {!! nl2br(e($invoice->notes)) !!}
            @else
                {{ __('invoice_pdf.thanks') }}
            @endif
        </div>
        <div class="muted small" style="margin-top: 8px;">{{ __('invoice_pdf.questions', ['contact' => $business['email']]) }}</div>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->get_font("DejaVu Sans", "normal");
            $pdf->page_text($pdf->get_width() - 120, $pdf->get_height() - 28, "{!! __('invoice_pdf.page') !!}", $font, 8, array(0.53, 0.53, 0.55));
        }
    </script>
</body>
</html>

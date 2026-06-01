<!DOCTYPE html>
<html dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('general.investment_report') }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; line-height: 1.6; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
        h1 { margin: 0; font-size: 24px; color: #1e3a8a; }
        .meta { font-size: 14px; color: #666; margin-top: 5px; }
        .section { margin-bottom: 30px; }
        .section-title { font-size: 18px; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-bottom: 15px; color: #1e3a8a; }
        .summary-box { border: 1px solid #e5e7eb; padding: 15px; background: #f9fafb; border-radius: 5px; }
        .summary-item { margin-bottom: 10px; }
        .summary-label { font-weight: bold; width: 150px; display: inline-block; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; }
        th, td { border: 1px solid #e5e7eb; padding: 10px; text-align: {{ app()->getLocale() == 'ar' ? 'right' : 'left' }}; }
        th { background: #f3f4f6; color: #374151; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('gold_saver.gold_investment_report') }}</h1>
        <div class="meta">{{ __('gold_saver.generated_for') }}: {{ $user->name }} | {{ __('gold_saver.date') }}: {{ now()->format('M d, Y') }}</div>
        <div class="meta">{{ __('gold_saver.period') }}: {{ ucfirst(__("gold_saver." . $period)) }} | {{ __('gold_saver.wallet') }}: {{ $walletName }}</div>
    </div>

    <div class="section">
        <h2 class="section-title">{{ __('gold_saver.portfolio_summary') }}</h2>
        <div class="summary-box">
            <div class="summary-item"><span class="summary-label">{{ __('gold_saver.total_wallets') }}:</span> {{ $stats['total_wallets'] }}</div>
            <div class="summary-item"><span class="summary-label">{{ __('gold_saver.total_grams') }}:</span> {{ number_format($stats['total_grams'], 2) }} {{ __('gold_saver.g') }}</div>
            <div class="summary-item"><span class="summary-label">{{ __('gold_saver.total_invested') }}:</span> {{ number_format($stats['total_invested'], 2) }} {{ $currency }}</div>
            <div class="summary-item"><span class="summary-label">{{ __('gold_saver.total_buys') }}:</span> {{ $stats['total_buys'] }} {{ __('gold_saver.transactions_count') }}</div>
            <div class="summary-item"><span class="summary-label">{{ __('gold_saver.total_sells') }}:</span> {{ $stats['total_sells'] }} {{ __('gold_saver.transactions_count') }}</div>
        </div>
    </div>

    <div class="section">
        <h2 class="section-title">{{ __('gold_saver.recent_transactions') }}</h2>
        @if(count($transactions) > 0)
        <table>
            <thead>
                <tr>
                    <th>{{ __('gold_saver.date') }}</th>
                    <th>{{ __('gold_saver.type') }}</th>
                    <th>{{ __('gold_saver.grams') }}</th>
                    <th>{{ __('gold_saver.total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $t)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($t->transaction_date)->format('Y-m-d') }}</td>
                    <td>{{ __("gold_saver." . $t->type) }}</td>
                    <td>{{ number_format($t->grams, 2) }}</td>
                    <td>{{ number_format($t->total_amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p>{{ __('gold_saver.no_transactions_found_for_this_period') }}</p>
        @endif
    </div>
</body>
</html>

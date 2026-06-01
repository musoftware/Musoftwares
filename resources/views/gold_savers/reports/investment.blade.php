<!DOCTYPE html>
<html>
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
        th, td { border: 1px solid #e5e7eb; padding: 10px; text-align: left; }
        th { background: #f3f4f6; color: #374151; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('general.gold_investment_report') }}</h1>
        <div class="meta">Generated for: {{ $user->name }} | Date: {{ now()->format('M d, Y') }}</div>
        <div class="meta">Period: {{ ucfirst($period) }} | Wallet: {{ $walletName }}</div>
    </div>

    <div class="section">
        <h2 class="section-title">{{ __('general.portfolio_summary') }}</h2>
        <div class="summary-box">
            <div class="summary-item"><span class="summary-label">Total Wallets:</span> {{ $stats['total_wallets'] }}</div>
            <div class="summary-item"><span class="summary-label">Total Grams:</span> {{ number_format($stats['total_grams'], 2) }} g</div>
            <div class="summary-item"><span class="summary-label">Total Invested:</span> {{ number_format($stats['total_invested'], 2) }} {{ $currency }}</div>
            <div class="summary-item"><span class="summary-label">Total Buys:</span> {{ $stats['total_buys'] }} transactions</div>
            <div class="summary-item"><span class="summary-label">Total Sells:</span> {{ $stats['total_sells'] }} transactions</div>
        </div>
    </div>

    <div class="section">
        <h2 class="section-title">{{ __('general.recent_transactions') }}</h2>
        @if(count($transactions) > 0)
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Grams</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $t)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($t->transaction_date)->format('Y-m-d') }}</td>
                    <td>{{ ucfirst($t->type) }}</td>
                    <td>{{ number_format($t->grams, 2) }}</td>
                    <td>{{ number_format($t->total_amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p>{{ __('general.no_transactions_found_for_this_period') }}</p>
        @endif
    </div>
</body>
</html>

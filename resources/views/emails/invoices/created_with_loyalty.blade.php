<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoiceNumber }} — Settle Early to Earn Loyalty Points</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4f4f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; }
        .wrapper { width: 100%; background-color: #f4f4f7; padding: 36px 12px; }
        .container { max-width: 580px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border: 1px solid #e5e7eb; }
        .header { background-color: #0f172a; padding: 32px 24px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 700; letter-spacing: -0.02em; color: #ffffff; }
        .header p { margin: 6px 0 0; font-size: 13px; color: #94a3b8; }
        .body-content { padding: 32px 36px; color: #334155; font-size: 14px; line-height: 1.6; }
        .badge-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; text-align: center; margin: 20px 0; }
        .tier-img { width: 84px; height: 84px; display: block; margin: 0 auto 10px; border: none; }
        .tier-title { font-size: 16px; font-weight: 800; color: #0f172a; margin: 0; text-transform: uppercase; letter-spacing: 0.04em; }
        .tier-discount { display: inline-block; background: #0071e3; color: #ffffff; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px; margin-top: 6px; }
        .metrics-grid { width: 100%; margin: 18px 0; border-collapse: separate; border-spacing: 10px 0; }
        .metric-cell { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; text-align: center; width: 50%; }
        .metric-val { font-size: 18px; font-weight: 800; color: #0f172a; margin: 0; }
        .metric-lbl { font-size: 11px; color: #64748b; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.02em; font-weight: 600; }
        .card-incentive { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 16px 20px; margin: 20px 0; }
        .card-incentive h4 { margin: 0 0 6px; color: #166534; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; }
        .card-incentive p { margin: 0; font-size: 13px; color: #15803d; line-height: 1.5; }
        .card-tickets { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 16px 20px; margin: 16px 0; }
        .card-tickets h4 { margin: 0 0 6px; color: #1e40af; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; }
        .card-tickets p { margin: 0; font-size: 13px; color: #1d4ed8; line-height: 1.5; }
        .btn-primary { display: block; background-color: #0071e3; color: #ffffff !important; text-decoration: none; padding: 14px 28px; border-radius: 10px; font-size: 14px; font-weight: 700; text-align: center; margin: 26px 0 14px; box-shadow: 0 2px 8px rgba(0, 113, 227, 0.25); }
        .btn-secondary { display: block; background-color: #f1f5f9; color: #0f172a !important; text-decoration: none; padding: 12px 24px; border-radius: 10px; font-size: 13px; font-weight: 600; text-align: center; border: 1px solid #e2e8f0; }
        .footer { background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 32px; text-align: center; font-size: 12px; color: #94a3b8; line-height: 1.6; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Musoftwares Billing &amp; Client Loyalty</h1>
            <p>A new invoice has been prepared for your corporate account</p>
        </div>

        <!-- Body -->
        <div class="body-content">
            <p>Dear <strong>{{ $clientName }}</strong>,</p>
            <p>
                Invoice <strong>{{ $invoiceNumber }}</strong> has been officially issued for your account.
                Below is a summary of the amount due and how you can maximize your loyalty rewards and invoice discounts today.
            </p>

            <!-- Invoice Summary Table -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 18px 0; border: 1px solid #e2e8f0; border-radius: 10px; background: #ffffff;">
                <tr>
                    <td style="padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #64748b; font-size: 13px;">Invoice Number</td>
                    <td style="padding: 14px 20px; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: 700; color: #0f172a;">{{ $invoiceNumber }}</td>
                </tr>
                <tr>
                    <td style="padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #64748b; font-size: 13px;">Total Amount</td>
                    <td style="padding: 14px 20px; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: 800; font-size: 16px; color: #0f172a;">{{ $totalAmountFormatted }}</td>
                </tr>
                <tr>
                    <td style="padding: 14px 20px; color: #64748b; font-size: 13px;">Payment Due Date</td>
                    <td style="padding: 14px 20px; text-align: right; font-weight: 600; color: #0f172a;">{{ $dueDateFormatted }} (Cairo Time)</td>
                </tr>
            </table>

            <!-- Client Loyalty Status Card -->
            <div class="badge-box">
                @if(!empty($tierSlug))
                    <img src="{{ rtrim(config('app.url'), '/') }}/images/tiers/{{ $tierSlug }}.png" class="tier-img" alt="{{ $tierName }}" />
                @endif
                <h3 class="tier-title">{{ $tierName }} Tier Status</h3>
                @if($tierDiscount > 0)
                    <span class="tier-discount">{{ $tierDiscount }}% Automatic Invoice Discount Active</span>
                @endif

                <table class="metrics-grid" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="metric-cell">
                            <div class="metric-val">{{ number_format($pointsBalance) }} PTS</div>
                            <div class="metric-lbl">Available Points</div>
                        </td>
                        <td class="metric-cell">
                            <div class="metric-val">{{ $pointsCashValueFormatted }}</div>
                            <div class="metric-lbl">Direct Discount Value</div>
                        </td>
                    </tr>
                </table>
                <p style="margin: 8px 0 0; font-size: 12px; color: #64748b;">
                    You can redeem available points directly toward this invoice or upcoming deliverables.
                </p>
            </div>

            <!-- Incentive 1: Early Payment Multiplier -->
            <div class="card-incentive">
                <h4>Early Payment Incentive (Up to 2x Points)</h4>
                <p>
                    Pay this invoice today or before the due date to earn up to <strong>{{ number_format($potentialEarlyPoints) }} loyalty points</strong>.
                    Settling early accelerates your progression toward higher tiers (up to 25% automatic discount on all services).
                </p>
            </div>

            <!-- Incentive 2: Portal Support Tickets Engagement -->
            <div class="card-tickets">
                <h4>Earn Points via Client Portal &amp; Support Tickets</h4>
                <p>
                    Did you know? Opening support tickets through the <strong>Client Portal</strong> grants you <strong>+15 Loyalty Points</strong> immediately, plus <strong>+25 Points</strong> upon resolution. Your {{ $tierName }} status also ensures top-tier priority routing.
                </p>
            </div>

            <!-- Action Buttons -->
            <a href="{{ $invoiceUrl }}" class="btn-primary">
                View &amp; Settle Invoice Online
            </a>
            <a href="{{ rtrim(config('app.url'), '/') }}/tickets/create" class="btn-secondary">
                Open Support Ticket via Client Portal
            </a>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="margin: 0;">
                Musoftwares &bull; Strategic Software &amp; Digital Engineering Services<br>
                This notification is generated automatically based on Cairo Time (Africa/Cairo).
            </p>
        </div>
    </div>
</div>
</body>
</html>

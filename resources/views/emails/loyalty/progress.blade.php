<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You're almost there — {{ $nextTierName }} is within reach</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; }
        .wrapper { background-color: #f4f4f5; padding: 40px 16px; }
        .card { background-color: #ffffff; border-radius: 12px; max-width: 560px; margin: 0 auto; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .header { background-color: #0f172a; padding: 32px 40px; text-align: center; }
        .header h1 { color: #ffffff; font-size: 22px; margin: 0; font-weight: 600; }
        .header p { color: #94a3b8; font-size: 14px; margin: 8px 0 0; }
        .body { padding: 36px 40px; }
        .greeting { font-size: 16px; color: #1e293b; margin-bottom: 20px; }
        .stat-row { display: flex; gap: 16px; margin-bottom: 28px; }
        .stat { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 20px; flex: 1; text-align: center; }
        .stat-number { font-size: 24px; font-weight: 700; color: #0f172a; }
        .stat-label { font-size: 12px; color: #64748b; margin-top: 4px; }
        .section-label { font-size: 13px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px; }
        .progress-track { background-color: #e2e8f0; border-radius: 9999px; height: 14px; overflow: hidden; }
        .progress-fill { height: 14px; border-radius: 9999px; }
        .progress-meta { display: flex; justify-content: space-between; margin-top: 8px; font-size: 12px; color: #64748b; }
        .tier-badge { display: inline-block; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; color: #0f172a; margin-bottom: 20px; }
        .cta { display: block; background-color: #0f172a; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-size: 15px; font-weight: 600; text-align: center; margin: 28px 0 0; }
        .benefits { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin: 24px 0; }
        .benefits h3 { font-size: 14px; color: #1e293b; font-weight: 600; margin: 0 0 12px; }
        .benefit-item { font-size: 13px; color: #475569; padding: 4px 0; }
        .footer { background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 40px; text-align: center; }
        .footer p { font-size: 12px; color: #94a3b8; margin: 0; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="card">
        <div class="header">
            <h1>Musoftwares Loyalty</h1>
            <p>Your progress update</p>
        </div>

        <div class="body">
            <p class="greeting">Hi {{ $clientFirstName }},</p>
            <p style="color:#475569; font-size:15px; line-height:1.6;">
                You're so close to unlocking <strong>{{ $nextTierName }}</strong> tier and all its perks.
                Here's where you stand right now:
            </p>

            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                <tr>
                    <td width="48%" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:14px 20px; text-align:center;">
                        <div style="font-size:24px; font-weight:700; color:#0f172a;">{{ number_format($currentBalance) }}</div>
                        <div style="font-size:12px; color:#64748b; margin-top:4px;">Available Points</div>
                    </td>
                    <td width="4%"></td>
                    <td width="48%" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:14px 20px; text-align:center;">
                        <div style="font-size:24px; font-weight:700; color:#0f172a;">{{ number_format($pointsNeeded) }}</div>
                        <div style="font-size:12px; color:#64748b; margin-top:4px;">Points to {{ $nextTierName }}</div>
                    </td>
                </tr>
            </table>

            <div class="section-label">Your progress to {{ $nextTierName }}</div>

            {{-- Progress bar (table-based for email client compatibility) --}}
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                        <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#e2e8f0; border-radius:9999px; overflow:hidden; height:14px;">
                            <tr>
                                <td width="{{ $progressPct }}%" style="background-color:{{ $nextTierColor }}; height:14px;"></td>
                                <td width="{{ 100 - $progressPct }}%" style="height:14px;"></td>
                            </tr>
                        </table>
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:8px;">
                            <tr>
                                <td style="font-size:12px; color:#64748b;">{{ $currentTierName }}</td>
                                <td align="right" style="font-size:12px; color:#64748b; font-weight:600;">{{ $progressPct }}% — {{ $nextTierName }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <div class="benefits" style="margin-top:24px;">
                <h3>Unlock with {{ $nextTierName }}:</h3>
                <div class="benefit-item">{{ $nextTierDiscount }}% discount on all future invoices</div>
                <div class="benefit-item">{{ ucfirst($nextTierPriority) }} priority on support tickets</div>
            </div>

            <p style="color:#475569; font-size:14px; line-height:1.6;">
                Pay your next invoice early, or open a support request through the portal to earn more points faster.
                Tickets opened on the portal earn points — direct messages do not.
            </p>

            <a href="{{ config('app.url') }}/client/loyalty" class="cta" style="display:block; background-color:#0f172a; color:#ffffff; text-decoration:none; padding:14px 28px; border-radius:8px; font-size:15px; font-weight:600; text-align:center; margin-top:28px;">
                View My Loyalty Dashboard
            </a>
        </div>

        <div class="footer">
            <p>Musoftwares &bull; This is an automated loyalty update for your account.</p>
        </div>
    </div>
</div>
</body>
</html>

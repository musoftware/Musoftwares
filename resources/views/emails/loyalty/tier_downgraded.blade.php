<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Partnership Tier Update</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; }
    </style>
</head>
<body>
<div style="background-color:#f4f4f5; padding:40px 16px;">
<div style="background-color:#ffffff; border-radius:12px; max-width:560px; margin:0 auto; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.08);">

    <div style="background-color:#1e293b; padding:36px 24px; text-align:center;">
        <img src="{{ rtrim(config('app.url'), '/') }}/images/tiers/{{ $newTierSlug }}.png" width="80" height="80" style="display:block; margin:0 auto 14px; border:none; outline:none;" alt="{{ $newTierName }}" onerror="this.style.display='none'" />
        <h1 style="color:#ffffff; font-size:22px; margin:0 0 6px; font-weight:800; letter-spacing:-0.02em;">Partnership Tier Update</h1>
        <p style="color:#94a3b8; font-size:14px; margin:0; font-weight:500;">Your account tier is now {{ $newTierName }}</p>
    </div>

    <div style="padding:36px 40px; color:#475569; font-size:15px; line-height:1.7;">
        <p>Hi <strong>{{ $clientFirstName }}</strong>,</p>
        <p>
            We are writing to let you know that your partnership tier has been adjusted from <strong>{{ $previousTierName }}</strong> to
            <strong style="color:#0f172a;">{{ $newTierName }}</strong>.
        </p>

        @if(!empty($reason))
            <div style="background:#fffbeb; border:1px solid #fef3c7; border-left:4px solid #f59e0b; border-radius:6px; padding:14px 16px; margin:20px 0; color:#92400e; font-size:13.5px;">
                <strong>Reason for adjustment:</strong> {{ $reason }}
            </div>
        @endif

        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:20px; margin:24px 0;">
            <h3 style="color:#0f172a; font-size:13px; margin:0 0 14px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em;">Current {{ $newTierName }} Tier Privileges</h3>
            
            <table style="width:100%; border-collapse:collapse; font-size:13.5px; color:#1e293b;">
                <tr>
                    <td style="padding:6px 0; font-weight:600; width:50%;">Invoice Discount:</td>
                    <td style="padding:6px 0; text-align:right; font-weight:700; color:#0284c7;">{{ $newTierDiscount }}% automatic discount</td>
                </tr>
                <tr>
                    <td style="padding:6px 0; font-weight:600;">Support Priority:</td>
                    <td style="padding:6px 0; text-align:right; font-weight:700; text-transform:capitalize;">{{ $newTierPriority }} routing</td>
                </tr>
                <tr>
                    <td style="padding:6px 0; font-weight:600;">Available Points:</td>
                    <td style="padding:6px 0; text-align:right; font-weight:700;">{{ number_format($loyaltyBalance) }} PTS</td>
                </tr>
            </table>

            @if(!empty($perks))
                <div style="margin-top:14px; padding-top:14px; border-top:1px dashed #cbd5e1;">
                    @foreach($perks as $perk)
                        <div style="font-size:13px; color:#475569; padding:3px 0;">
                            &bull; {{ $perk }}
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        @if($pointsToRegain > 0)
            <div style="background:#f1f5f9; border-radius:8px; padding:16px 20px; margin-bottom:24px;">
                <p style="margin:0; font-size:13.5px; color:#334155;">
                    To regain your <strong>{{ $previousTierName }}</strong> tier, you need <strong>{{ number_format($pointsToRegain) }} points</strong>. Settling invoices promptly and participating on the platform restores your tier automatically.
                </p>
            </div>
        @endif

        <p style="font-size:14px; color:#64748b; margin-top:20px;">
            If you have questions about your invoices or need assistance, our support team is available via your client portal.
        </p>

        <a href="{{ config('app.url') }}/client/loyalty" style="display:block;background:#0f172a;color:#ffffff;text-decoration:none;padding:14px 28px;border-radius:8px;font-size:14.5px;font-weight:600;text-align:center;margin-top:24px;">
            View Loyalty Dashboard
        </a>
    </div>

    <div style="background:#f8fafc; border-top:1px solid #e2e8f0; padding:16px 40px; text-align:center;">
        <p style="font-size:11px; color:#94a3b8; margin:0;">Musoftwares &bull; Partnership Loyalty Notification.</p>
    </div>

</div>
</div>
</body>
</html>

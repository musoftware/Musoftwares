<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>You've reached {{ $newTierName }} tier!</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; }
    </style>
</head>
<body>
<div style="background-color:#f4f4f5; padding:40px 16px;">
<div style="background-color:#ffffff; border-radius:12px; max-width:560px; margin:0 auto; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.08);">

    <div style="background-color:{{ $newTierColor }}; padding:36px 24px; text-align:center;">
        <img src="{{ rtrim(config('app.url'), '/') }}/images/tiers/{{ $newTierSlug }}.png" width="96" height="96" style="display:block; margin:0 auto 14px; border:none; outline:none;" alt="{{ $newTierName }}" />
        <h1 style="color:#0f172a; font-size:24px; margin:0 0 4px; font-weight:800; letter-spacing:-0.02em;">{{ $newTierName }} Tier Unlocked</h1>
        <p style="color:#0f172a; opacity:0.8; font-size:14px; margin:0; font-weight:500;">Congratulations — you have earned elite partnership status.</p>
    </div>

    <div style="padding:36px 40px; color:#475569; font-size:15px; line-height:1.7;">
        <p>Hi <strong>{{ $clientFirstName }}</strong>,</p>
        <p>
            You've just been upgraded from <strong>{{ $previousTierName }}</strong> to
            <strong style="color:#0f172a;">{{ $newTierName }}</strong> tier.
            This is a real milestone, and it comes with real benefits.
        </p>

        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:20px; margin:24px 0;">
            <h3 style="color:#0f172a; font-size:14px; margin:0 0 12px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em;">Your {{ $newTierName }} Privileges</h3>
            
            <div style="font-size:14px; color:#0f172a; padding:6px 0; font-weight:600;">
                ⭐ <span style="color:#0071e3;">{{ $newTierDiscount }}%</span> automatic discount on all invoices
            </div>
            <div style="font-size:14px; color:#0f172a; padding:6px 0; font-weight:600;">
                ⚡ <span style="text-transform:capitalize;">{{ $newTierPriority }}</span> priority routing on support tickets
            </div>

            @if(!empty($perks))
                <div style="margin-top:12px; padding-top:12px; border-top:1px dashed #cbd5e1;">
                    @foreach($perks as $perk)
                        <div style="font-size:13px; color:#334155; padding:3px 0;">
                            ✓ {{ $perk }}
                        </div>
                    @endforeach
                </div>
            @endif

            <div style="margin-top:12px; padding-top:12px; border-top:1px solid #e2e8f0; font-size:13px; color:#64748b;">
                Available Points Balance: <strong style="color:#0f172a;">{{ number_format($loyaltyBalance) }} PTS</strong>
            </div>
        </div>

        <p>
            Keep paying invoices early and using the portal for support — every interaction brings you closer to the next level.
        </p>

        <a href="{{ config('app.url') }}/client/loyalty" style="display:block;background:#0f172a;color:#fff;text-decoration:none;padding:14px 28px;border-radius:8px;font-size:15px;font-weight:600;text-align:center;margin-top:24px;">
            View My Loyalty Dashboard
        </a>
    </div>

    <div style="background:#f8fafc; border-top:1px solid #e2e8f0; padding:16px 40px; text-align:center;">
        <p style="font-size:11px; color:#94a3b8; margin:0;">Musoftwares &bull; Automated loyalty update.</p>
    </div>

</div>
</div>
</body>
</html>

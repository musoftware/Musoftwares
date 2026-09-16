<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        @switch($stageSlug)
            @case('gentle_reminder') We miss you at Musoftwares @break
            @case('value_reminder') What's new at Musoftwares @break
            @case('urgency_incentive') A special bonus is waiting for you @break
            @case('direct_check') Everything okay? @break
            @default Musoftwares — Staying in touch @endswitch
    </title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; }
        .card { background-color: #ffffff; border-radius: 12px; max-width: 560px; margin: 40px auto; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .header { background-color: #0f172a; padding: 32px 40px; text-align: center; }
        .header h1 { color: #ffffff; font-size: 22px; margin: 0; font-weight: 600; }
        .header p { color: #94a3b8; font-size: 14px; margin: 8px 0 0; }
        .body { padding: 36px 40px; color: #475569; font-size: 15px; line-height: 1.7; }
        .loyalty-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .loyalty-box h3 { color: #0f172a; font-size: 14px; margin: 0 0 10px; font-weight: 600; }
        .loyalty-stat { color: #0f172a; font-size: 22px; font-weight: 700; }
        .loyalty-label { color: #64748b; font-size: 12px; }
        .cta { display: block; background-color: #0f172a; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-size: 15px; font-weight: 600; text-align: center; margin: 24px 0; }
        .incentive-box { background: #fefce8; border: 1px solid #fde047; border-radius: 8px; padding: 18px 20px; margin: 20px 0; }
        .incentive-box p { color: #713f12; font-size: 14px; margin: 0; }
        .incentive-box strong { font-size: 18px; }
        .footer { background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 16px 40px; text-align: center; }
        .footer p { font-size: 11px; color: #94a3b8; margin: 0; }
        .footer a { color: #94a3b8; }
    </style>
</head>
<body>
<div style="background-color:#f4f4f5; padding:40px 16px;">
<div class="card">
    <div class="header">
        <h1>Musoftwares</h1>
        <p>
            @switch($stageSlug)
                @case('gentle_reminder') A quick hello @break
                @case('value_reminder') What's been happening @break
                @case('urgency_incentive') A limited-time offer for you @break
                @case('direct_check') Checking in @break
                @default Staying in touch @endswitch
        </p>
    </div>

    <div class="body">
        <p>Hi <strong>{{ $clientFirstName }}</strong>,</p>

        @switch($stageSlug)

            @case('gentle_reminder')
                <p>
                    It's been a while since we've seen you, and we genuinely miss working with you.
                    Your loyalty points are still here, waiting for you.
                </p>
                <div class="loyalty-box">
                    <h3>Your Loyalty Summary</h3>
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                                <div class="loyalty-stat">{{ number_format($loyaltyBalance) }}</div>
                                <div class="loyalty-label">Available Points</div>
                            </td>
                            <td>
                                <div class="loyalty-stat">{{ $currentTierName }}</div>
                                <div class="loyalty-label">Current Tier</div>
                            </td>
                            @if($pointsToNextTier > 0)
                            <td>
                                <div class="loyalty-stat">{{ number_format($pointsToNextTier) }}</div>
                                <div class="loyalty-label">Points to Next Tier</div>
                            </td>
                            @endif
                        </tr>
                    </table>
                </div>
                <p>
                    Whenever you're ready, your dashboard and your team are here.
                    Every invoice you pay through the portal — especially early — earns you more points.
                </p>
                <a href="{{ config('app.url') }}/client/dashboard" class="cta" style="display:block;background:#0f172a;color:#fff;text-decoration:none;padding:14px 28px;border-radius:8px;font-size:15px;font-weight:600;text-align:center;margin:24px 0;">
                    Return to My Dashboard
                </a>
            @break

            @case('value_reminder')
                <p>
                    A lot has been happening on Musoftwares since you were last active.
                    Here's a quick snapshot of what's new and waiting for you:
                </p>
                <ul style="padding-left:20px; color:#475569;">
                    <li>Improved project board with real-time collaboration</li>
                    <li>Faster invoice management with due-date reminders</li>
                    <li>Your loyalty points are growing — pay early to unlock bonus multipliers</li>
                    <li>Priority support tickets for {{ $currentTierName }} tier and above</li>
                </ul>
                <div class="loyalty-box">
                    <h3>Still Yours</h3>
                    <table width="100%" cellpadding="0" cellspacing="0"><tr>
                        <td><div class="loyalty-stat">{{ number_format($loyaltyBalance) }}</div><div class="loyalty-label">Points Balance</div></td>
                        <td><div class="loyalty-stat">{{ $currentTierName }}</div><div class="loyalty-label">Your Tier</div></td>
                    </tr></table>
                </div>
                <a href="{{ config('app.url') }}/client/dashboard" class="cta" style="display:block;background:#0f172a;color:#fff;text-decoration:none;padding:14px 28px;border-radius:8px;font-size:15px;font-weight:600;text-align:center;margin:24px 0;">
                    See What's New
                </a>
            @break

            @case('urgency_incentive')
                <p>
                    We want to give you a real reason to come back. As a thank-you,
                    we're reserving a special bonus for you — but only for a limited time.
                </p>
                @if($hasIncentive && $incentivePoints > 0)
                <div class="incentive-box" style="background:#fefce8;border:1px solid #fde047;border-radius:8px;padding:18px 20px;margin:20px 0;">
                    <p style="color:#713f12; font-size:14px; margin:0;">
                        <strong style="font-size:18px;">{{ number_format($incentivePoints) }} bonus points</strong>
                        will be added to your account the next time you pay an invoice or open a support request through the portal within the next 7 days.
                    </p>
                </div>
                @endif
                <p>
                    These points count toward your tier progression — every point brings you closer to discounts and priority support.
                </p>
                <a href="{{ config('app.url') }}/client/dashboard" class="cta" style="display:block;background:#0f172a;color:#fff;text-decoration:none;padding:14px 28px;border-radius:8px;font-size:15px;font-weight:600;text-align:center;margin:24px 0;">
                    Claim My Bonus
                </a>
            @break

            @case('direct_check')
                <p>
                    We noticed it's been quite some time since your last activity on Musoftwares,
                    and we just wanted to check in personally.
                </p>
                <p>
                    <strong>Did something not go as expected?</strong>
                    Was there an issue with a project, an invoice, or anything else?
                    We'd genuinely like to know — your feedback helps us improve for everyone.
                </p>
                <a href="{{ config('app.url') }}/client/support/new" class="cta" style="display:block;background:#0f172a;color:#fff;text-decoration:none;padding:14px 28px;border-radius:8px;font-size:15px;font-weight:600;text-align:center;margin:24px 0;">
                    Share Your Feedback
                </a>
                <p style="font-size:14px;">
                    Or simply reply to this email and we'll get back to you directly.
                    No forms, no queues.
                </p>
            @break

        @endswitch

        <p style="font-size:13px; color:#64748b;">
            Points earned on the portal only — tickets or payments made through direct messages (WhatsApp, phone) do not earn loyalty points.
        </p>
    </div>

    <div class="footer">
        <p>
            Musoftwares &bull; You're receiving this because you're a valued client.
            &bull; <a href="{{ $unsubscribeUrl }}" style="color:#94a3b8;">Unsubscribe from reminders</a>
        </p>
    </div>
</div>
</div>
</body>
</html>

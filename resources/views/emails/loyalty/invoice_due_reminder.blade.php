<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice due in 5 days — earn {{ $potentialPoints }} points by paying now</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;">
<div style="background-color:#f4f4f5;padding:40px 16px;">
<div style="background-color:#ffffff;border-radius:12px;max-width:560px;margin:0 auto;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">

    <div style="background-color:#0f172a;padding:32px 40px;text-align:center;">
        <h1 style="color:#ffffff;font-size:22px;margin:0;font-weight:600;">Musoftwares</h1>
        <p style="color:#94a3b8;font-size:14px;margin:8px 0 0;">Invoice Payment Reminder</p>
    </div>

    <div style="padding:36px 40px;color:#475569;font-size:15px;line-height:1.7;">
        <p>Hi <strong>{{ $clientFirstName }}</strong>,</p>
        <p>
            You have an invoice due on <strong>{{ $dueDate }}</strong>.
            Pay it today and earn <strong style="color:#0f172a;">{{ number_format($potentialPoints) }} loyalty points</strong>
            — that's a <strong>{{ $earlyMultiplier }}x</strong> early-payment bonus.
        </p>

        <table width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;">
            <tr>
                <td width="48%" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px 20px;text-align:center;">
                    <div style="font-size:22px;font-weight:700;color:#0f172a;">{{ number_format($potentialPoints) }}</div>
                    <div style="font-size:12px;color:#64748b;margin-top:4px;">Points if you pay today</div>
                </td>
                <td width="4%"></td>
                <td width="48%" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px 20px;text-align:center;">
                    <div style="font-size:22px;font-weight:700;color:#0f172a;">{{ $earlyMultiplier }}x</div>
                    <div style="font-size:12px;color:#64748b;margin-top:4px;">Early-payment multiplier</div>
                </td>
            </tr>
        </table>

        <p style="font-size:13px;color:#64748b;background:#fefce8;border:1px solid #fde047;border-radius:8px;padding:12px 16px;">
            Waiting until the due date gives you only the base <strong>{{ number_format($basePoints) }} points</strong>.
            Paying after the due date earns zero points. Pay now to get the full bonus.
        </p>

        <a href="{{ $invoiceUrl }}" style="display:block;background:#0f172a;color:#fff;text-decoration:none;padding:14px 28px;border-radius:8px;font-size:15px;font-weight:600;text-align:center;margin-top:24px;">
            Pay Invoice Now
        </a>
    </div>

    <div style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:16px 40px;text-align:center;">
        <p style="font-size:11px;color:#94a3b8;margin:0;">
            Musoftwares &bull; Automated invoice reminder.
            Points are awarded only for portal payments, not direct transfers.
        </p>
    </div>

</div>
</div>
</body>
</html>

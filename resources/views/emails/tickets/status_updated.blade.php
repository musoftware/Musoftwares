<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحديث حالة تذكرتك — Musoftwares</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4f4f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; -webkit-font-smoothing: antialiased; }
        .wrapper { width: 100%; background-color: #f4f4f7; padding: 36px 12px; }
        .container { max-width: 580px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06); border: 1px solid #e5e7eb; }
        .header { background-color: #0f172a; padding: 32px 24px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 700; letter-spacing: -0.01em; color: #ffffff; }
        .header p { margin: 6px 0 0; font-size: 13px; color: #94a3b8; }
        .body-content { padding: 32px 28px; color: #334155; font-size: 14px; line-height: 1.7; text-align: right; direction: rtl; }
        .status-pill { display: inline-block; background-color: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; padding: 6px 16px; border-radius: 9999px; font-size: 12px; font-weight: 700; margin-bottom: 20px; }
        .details-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; margin: 20px 0; }
        .comment-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; margin: 16px 0; font-size: 13px; color: #475569; white-space: pre-wrap; }
        .btn-primary { display: block; background-color: #0071e3; color: #ffffff !important; text-decoration: none; padding: 14px 28px; border-radius: 12px; font-size: 14px; font-weight: 700; text-align: center; margin: 28px 0 12px; box-shadow: 0 2px 10px rgba(0, 113, 227, 0.25); }
        .footer { background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 22px 28px; text-align: center; font-size: 12px; color: #94a3b8; line-height: 1.6; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>مركز الدعم الفني — Musoftwares</h1>
            <p>تحديث حالة التذكرة (#{{ $ticketId }})</p>
        </div>

        <!-- Body -->
        <div class="body-content">
            <div style="text-align: center;">
                <div class="status-pill">
                    الحالة الحالية: {{ $statusArabic }}
                </div>
            </div>

            <p style="font-size: 15px; margin-top: 0;">مرحباً <strong>{{ $clientName }}</strong>،</p>
            <p>
                نود إعلامك بأنه تم تحديث حالة تذكرتك <strong>"{{ $ticketSubject }}"</strong> لتصبح: <strong>{{ $statusArabic }}</strong>.
            </p>

            @if(!empty($comment))
                <p style="margin-bottom: 6px; font-weight: 600; color: #475569;">ملاحظات فريق الدعم:</p>
                <div class="comment-box">{{ $comment }}</div>
            @endif

            <div class="details-box">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 6px 0; color: #64748b; font-weight: 600; font-size: 13px;">رقم التذكرة:</td>
                        <td style="padding: 6px 0; text-align: left; color: #0f172a; font-weight: 700; font-size: 13px;">#{{ $ticketId }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b; font-weight: 600; font-size: 13px;">موضوع التذكرة:</td>
                        <td style="padding: 6px 0; text-align: left; color: #0f172a; font-weight: 700; font-size: 13px;">{{ $ticketSubject }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b; font-weight: 600; font-size: 13px;">تاريخ التحديث:</td>
                        <td style="padding: 6px 0; text-align: left; color: #0f172a; font-weight: 600; font-size: 13px;">{{ $updatedAtCairo }}</td>
                    </tr>
                </table>
            </div>

            <a href="{{ $ticketUrl }}" class="btn-primary">
                عرض التذكرة وتفاصيلها
            </a>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="margin: 0;">هذا البريد مرسل تلقائياً من نظام التذاكر في Musoftwares.</p>
            <p style="margin: 4px 0 0;">جميع الحقوق محفوظة © {{ date('Y') }} Musoftwares.</p>
        </div>
    </div>
</div>
</body>
</html>

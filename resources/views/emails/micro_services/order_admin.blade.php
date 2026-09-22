<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلب خدمة مصغرة جديد — Musoftwares Admin</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4f4f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; -webkit-font-smoothing: antialiased; }
        .wrapper { width: 100%; background-color: #f4f4f7; padding: 36px 12px; }
        .container { max-width: 580px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06); border: 1px solid #e5e7eb; }
        .header { background-color: #0f172a; padding: 32px 24px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 700; color: #ffffff; }
        .header p { margin: 6px 0 0; font-size: 13px; color: #94a3b8; }
        .body-content { padding: 32px 28px; color: #334155; font-size: 14px; line-height: 1.7; text-align: right; direction: rtl; }
        .alert-pill { display: inline-block; background-color: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; padding: 6px 16px; border-radius: 9999px; font-size: 12px; font-weight: 700; margin-bottom: 20px; }
        .details-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; margin: 20px 0; }
        .description-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; margin: 16px 0; font-size: 13px; color: #475569; white-space: pre-wrap; }
        .btn-primary { display: block; background-color: #0f172a; color: #ffffff !important; text-decoration: none; padding: 14px 28px; border-radius: 12px; font-size: 14px; font-weight: 700; text-align: center; margin: 28px 0 12px; }
        .footer { background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 22px 28px; text-align: center; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>إدارة الخدمات المصغرة — Musoftwares</h1>
            <p>طلب خدمة مصغرة جديد (#{{ $orderId }})</p>
        </div>

        <!-- Body -->
        <div class="body-content">
            <div style="text-align: center;">
                <div class="alert-pill">
                    تم دفع قيمة الخدمة بنجاح والطلب بانتظار التنفيذ
                </div>
            </div>

            <p style="font-size: 15px; margin-top: 0;">مرحباً فريق الإدارة،</p>
            <p>
                قام العميل <strong>{{ $clientName }}</strong> بشراء خدمة مصغرة وتم خصم قيمتها من رصيد محفظته تلقائياً.
            </p>

            <div class="details-box">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 6px 0; color: #64748b; font-weight: 600; font-size: 13px;">رقم الطلب:</td>
                        <td style="padding: 6px 0; text-align: left; color: #0f172a; font-weight: 700; font-size: 13px;">#{{ $orderId }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b; font-weight: 600; font-size: 13px;">الخدمة:</td>
                        <td style="padding: 6px 0; text-align: left; color: #0f172a; font-weight: 700; font-size: 13px;">{{ $serviceTitle }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b; font-weight: 600; font-size: 13px;">العميل:</td>
                        <td style="padding: 6px 0; text-align: left; color: #0f172a; font-weight: 700; font-size: 13px;">{{ $clientName }} ({{ $clientEmail }})</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b; font-weight: 600; font-size: 13px;">المبلغ المدفوع:</td>
                        <td style="padding: 6px 0; text-align: left; color: #16a34a; font-weight: 700; font-size: 13px;">{{ $amountFormatted }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b; font-weight: 600; font-size: 13px;">تاريخ الطلب:</td>
                        <td style="padding: 6px 0; text-align: left; color: #0f172a; font-size: 12px;">{{ $createdAtCairo }}</td>
                    </tr>
                </table>
            </div>

            <p style="font-weight: 700; color: #0f172a; margin-bottom: 6px;">بيانات وتفاصيل الطلب المكتوبة من العميل:</p>
            <div class="description-box">
                {{ $requirements }}
            </div>

            <a href="{{ $adminOrderUrl }}" class="btn-primary">
                عرض الطلب وتنفيذه في لوحة التحكم ←
            </a>
        </div>

        <!-- Footer -->
        <div class="footer">
            تم إرسال هذا البريد تلقائياً من منظومة Musoftwares.<br>
            جميع الحقوق محفوظة &copy; {{ date('Y') }} Musoftwares.
        </div>
    </div>
</div>
</body>
</html>

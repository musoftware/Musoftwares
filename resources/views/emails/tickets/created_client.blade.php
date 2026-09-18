<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تم استلام تذكرتك بنجاح — Musoftwares</title>
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
        .details-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #e2e8f0; font-size: 13px; }
        .details-row:last-child { border-bottom: none; }
        .details-label { color: #64748b; font-weight: 600; }
        .details-value { color: #0f172a; font-weight: 700; }
        .description-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; margin: 16px 0; font-size: 13px; color: #475569; white-space: pre-wrap; }
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
            <p>تأكيد استلام طلب الدعم الفني (#{{ $ticketId }})</p>
        </div>

        <!-- Body -->
        <div class="body-content">
            <div style="text-align: center;">
                <div class="status-pill">
                    كلامك وصل للإدارة وجارٍ متابعة طلبك
                </div>
            </div>

            <p style="font-size: 15px; margin-top: 0;">مرحباً <strong>{{ $clientName }}</strong>،</p>
            <p>
                تم استلام تذكرتك بنجاح ووصلت لإدارة Musoftwares. فريق العمل يراجع تفاصيل طلبك حالياً وسنقوم بالرد عليك والبدء في الإجراءات في أسرع وقت.
            </p>

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
                    @if(!empty($projectName))
                    <tr>
                        <td style="padding: 6px 0; color: #64748b; font-weight: 600; font-size: 13px;">المشروع المرتبط:</td>
                        <td style="padding: 6px 0; text-align: left; color: #0f172a; font-weight: 700; font-size: 13px;">{{ $projectName }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding: 6px 0; color: #64748b; font-weight: 600; font-size: 13px;">الأولوية:</td>
                        <td style="padding: 6px 0; text-align: left; color: #0f172a; font-weight: 700; font-size: 13px;">{{ $priority }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b; font-weight: 600; font-size: 13px;">تاريخ الإنشاء (توقيت القاهرة):</td>
                        <td style="padding: 6px 0; text-align: left; color: #0f172a; font-weight: 600; font-size: 13px;">{{ $createdAtCairo }}</td>
                    </tr>
                </table>
            </div>

            @if(!empty($ticketDescription))
            <p style="margin-bottom: 6px; font-weight: 600; color: #0f172a;">تفاصيل طلبك:</p>
            <div class="description-box">{{ $ticketDescription }}</div>
            @endif

            <a href="{{ $ticketUrl }}" class="btn-primary">متابعة التذكرة في حسابك</a>
            
            <p style="font-size: 12px; color: #64748b; margin-top: 20px; text-align: center;">
                يمكنك الرد أو إضافة أي مرفقات جديدة مباشرة من خلال صفحة التذكرة في بوابة العميل.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            &copy; {{ date('Y') }} Musoftwares. جميع الحقوق محفوظة.<br>
            نحن هنا لمساعدتك في بناء وتطوير أعمالك البرمجية بكل احترافية.
        </div>
    </div>
</div>
</body>
</html>

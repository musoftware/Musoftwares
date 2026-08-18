<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>رابط تحميل كتابك</title>
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #09090b; color: #f4f4f5; padding: 40px 20px; margin: 0;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #121215; border: 1px solid #27272a; border-radius: 16px; overflow: hidden;">
        <tr>
            <td style="padding: 30px; text-align: center; background: linear-gradient(135deg, #4f46e5, #6366f1);">
                <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: bold;">Musoftware Digital Library</h1>
                <p style="color: #e0e7ff; margin: 5px 0 0 0; font-size: 13px;">المكتبة الرقمية للكتب والأدلة التطبيقية</p>
            </td>
        </tr>
        <tr>
            <td style="padding: 30px; text-align: right;">
                <h2 style="color: #ffffff; font-size: 18px; margin-top: 0;">مرحباً بك،</h2>
                <p style="color: #a1a1aa; font-size: 14px; line-height: 1.6;">
                    شكراً لطلبك كتاب <strong style="color: #ffffff;">{{ $product->title }}</strong>. نسختك الرقمية بصيغة PDF جاهزة للتحميل الفوري عبر الزر أدناه:
                </p>

                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{ $downloadUrl }}" style="background-color: #10b981; color: #ffffff; padding: 14px 30px; border-radius: 12px; font-weight: bold; text-decoration: none; display: inline-block; font-size: 15px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">
                        تحميل الكتاب الآن (PDF)
                    </a>
                </div>

                <div style="background-color: #18181b; border: 1px solid #27272a; border-radius: 10px; padding: 15px; margin-top: 20px; font-size: 12px; color: #71717a;">
                    <p style="margin: 0;"><strong>ملاحظة:</strong> هذا الرابط صالح للتحميل لمدة 48 ساعة فقط.</p>
                </div>
            </td>
        </tr>
        <tr>
            <td style="padding: 20px 30px; background-color: #09090b; border-top: 1px solid #27272a; text-align: center; font-size: 11px; color: #52525b;">
                © {{ date('Y') }} Musoftware. جميع الحقوق محفوظة.
            </td>
        </tr>
    </table>
</body>
</html>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تنبيه انتهاء صلاحية رصيد نقاط الولاء — Musoftwares</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4f4f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; -webkit-font-smoothing: antialiased; }
        .wrapper { width: 100%; background-color: #f4f4f7; padding: 36px 12px; }
        .container { max-width: 580px; margin: 0 auto; background-color: #ffffff; border-radius: 18px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06); border: 1px solid #e5e7eb; }
        .header { background-color: #0f172a; padding: 32px 24px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 700; letter-spacing: -0.01em; color: #ffffff; }
        .header p { margin: 6px 0 0; font-size: 13px; color: #94a3b8; }
        .body-content { padding: 32px 32px; color: #334155; font-size: 14px; line-height: 1.7; text-align: right; direction: rtl; }
        .urgent-pill { display: inline-block; background-color: #fffbeb; border: 1px solid #fde68a; color: #b45309; padding: 6px 16px; border-radius: 9999px; font-size: 12px; font-weight: 700; margin-bottom: 18px; }
        .stats-grid { width: 100%; margin: 20px 0; border-collapse: separate; border-spacing: 10px 0; }
        .stat-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; text-align: center; width: 50%; }
        .stat-value { font-size: 22px; font-weight: 800; color: #0f172a; margin: 0; font-family: -apple-system, BlinkMacSystemFont, sans-serif; }
        .stat-label { font-size: 11px; color: #64748b; margin-top: 4px; font-weight: 600; text-transform: uppercase; }
        .policy-box { background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 18px 20px; margin: 22px 0; }
        .policy-box h4 { margin: 0 0 6px; color: #991b1b; font-size: 13px; font-weight: 700; }
        .policy-box p { margin: 0; font-size: 13px; color: #b91c1c; line-height: 1.6; }
        .tier-safe-box { background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 14px 18px; margin: 18px 0; display: flex; align-items: center; }
        .tier-safe-box p { margin: 0; font-size: 12px; color: #166534; line-height: 1.5; font-weight: 600; }
        .action-list { margin: 20px 0; padding: 0; list-style: none; }
        .action-item { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; margin-bottom: 10px; display: block; font-size: 13px; color: #1e293b; }
        .action-item strong { color: #0071e3; margin-left: 6px; }
        .btn-primary { display: block; background-color: #0071e3; color: #ffffff !important; text-decoration: none; padding: 14px 28px; border-radius: 12px; font-size: 14px; font-weight: 700; text-align: center; margin: 26px 0 12px; box-shadow: 0 2px 10px rgba(0, 113, 227, 0.25); }
        .btn-secondary { display: block; background-color: #f8fafc; color: #0f172a !important; text-decoration: none; padding: 12px 24px; border-radius: 12px; font-size: 13px; font-weight: 600; text-align: center; border: 1px solid #e2e8f0; }
        .footer { background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 22px 32px; text-align: center; font-size: 12px; color: #94a3b8; line-height: 1.6; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">
        
        <!-- Header -->
        <div class="header">
            <h1>برنامج ولاء ومكافآت Musoftwares</h1>
            <p>تنبيه هام بشأن دورة انتهاء صلاحية النقاط الربع سنوية ({{ $quarterName }})</p>
        </div>

        <!-- Body -->
        <div class="body-content">
            
            <div style="text-align: center;">
                <div class="urgent-pill">
                    ينتهي الرصيد خلال {{ $daysRemaining }} يوماً ({{ $expiryDateArabic ?? $expiryDateFormatted }})
                </div>
            </div>

            <p style="font-size: 15px; margin-top: 0;">مرحباً <strong>{{ $clientName }}</strong>،</p>
            <p>
                نود إحاطتك علماً بأن لديك رصيداً نشطاً من <strong>نقاط الولاء القابلة للإنفاق</strong> لم يتم استخدامه بعد. نكتب إليك لنذكرك بالاستفادة الكاملة من قيمته المالية قبل موعد إعادة الضبط الربع سنوي القادم.
            </p>

            <!-- Stats Grid -->
            <table class="stats-grid" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="stat-card">
                        <div class="stat-value" style="color: #0071e3;">{{ number_format($pointsBalance) }} PTS</div>
                        <div class="stat-label">رصيدك المتاح حالياً</div>
                    </td>
                    <td class="stat-card">
                        <div class="stat-value" style="color: #16a34a;">{{ $pointsCashValueFormatted }}</div>
                        <div class="stat-label">القيمة المالية للخصم المباشر</div>
                    </td>
                </tr>
            </table>

            <!-- Policy Warning Box -->
            <div class="policy-box">
                <h4>سياسة تجديد دورات برنامج الولاء (كل 3 أشهر):</h4>
                <p>
                    تطبيقاً لقواعد برنامج المكافآت، تنتهي صلاحية نقاط الولاء غير المستبدلة كل <strong>3 أشهر (بنهاية كل ربع سنوي)</strong> وتعود إلى <strong>الصفر</strong>، وذلك لفتح دورة حوافز جديدة للعملاء النشطين. موعد التصفير القادم هو: <strong>{{ $expiryDateFormatted }} (بتوقيت القاهرة)</strong>.
                </p>
            </div>

            <!-- Tier Safety Reassurance -->
            <div class="tier-safe-box">
                <p>
                    رتبتك الدائمة <strong>({{ $tierName }} Tier)</strong> ونسبة الخصم المعتمدة لحسابك <strong>({{ $tierDiscount }}% خصم)</strong> محفوظة بالكامل مدى الحياة، ولا تتأثر إطلاقاً بتصفير النقاط الدورية؛ التصفير ينطبق فقط على النقاط القابلة للإنفاق غير المستخدمة.
                </p>
            </div>

            <h3 style="font-size: 14px; font-weight: 800; color: #0f172a; margin: 24px 0 10px;">
                كيف يمكنك الاستفادة من رصيد نقاطك الآن قبل تصفيره؟
            </h3>

            <!-- Action List -->
            <div class="action-list">
                <div class="action-item">
                    <strong>1. خصم فوري على الفواتير:</strong> اطلب تحويل نقاطك المتاحة كرصيد خصم مباشر على فواتيرك القادمة أو الحالية.
                </div>
                <div class="action-item">
                    <strong>2. كتالوج المكافآت الحصري:</strong> استبدل نقاطك بحزم استشارية برمجية أو خدمات دعم وصيانة دورية مجاناً.
                </div>
                <div class="action-item">
                    <strong>3. تذاكر الدعم والطلبات الفنية:</strong> افتح تذكرة جديدة لطلب ميزات إضافية لمشروعك واستغل الرصيد لتغطية التكلفة.
                </div>
            </div>

            <!-- Call to Actions -->
            <a href="{{ $portalUrl }}" class="btn-primary">
                الانتقال لبوابة الولاء واستبدال النقاط الآن
            </a>

            <a href="{{ $ticketsUrl }}" class="btn-secondary">
                فتح تذكرة دعم للاستفادة من رصيدك في مشروعك
            </a>

            <!-- English Summary Section for International / Corporate Alignment -->
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px dashed #cbd5e1; direction: ltr; text-align: left; font-size: 12px; color: #64748b;">
                <p style="margin: 0 0 6px; font-weight: 700; color: #0f172a;">Executive English Summary:</p>
                <p style="margin: 0; line-height: 1.5;">
                    Your account holds <strong>{{ number_format($pointsBalance) }} spendable loyalty points</strong> (valued at <strong>{{ $pointsCashValueFormatted }}</strong>) which will expire and reset to zero at the end of the quarter on <strong>{{ $expiryDateFormatted }} (Cairo Time)</strong>.
                    Your <strong>{{ $tierName }}</strong> lifetime tier status and permanent {{ $tierDiscount }}% invoice discount remain 100% unaffected. Log in to your client portal to redeem your points on invoices, support tickets, or service perks before expiry.
                </p>
            </div>

        </div>

        <!-- Footer -->
        <div class="footer">
            <p>
                تم إرسال هذا الإشعار الآلي من منصة <strong>Musoftwares Business Studio</strong>.<br>
                جميع المواعيد والمهل تُحسب بتوقيت القاهرة الرسمي (Africa/Cairo).<br>
                لأي مساعدة، يمكنك الرد على هذا الإيميل مباشرة أو التواصل مع مدير حسابك عبر التذاكر.
            </p>
        </div>

    </div>
</div>
</body>
</html>

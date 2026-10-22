<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Musoftwares — عرض خاص لعملائنا المميزين</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f5f7;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #1e293b;
            direction: rtl;
            text-align: right;
        }
        .wrapper {
            background-color: #f4f5f7;
            padding: 32px 16px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .header {
            background-color: #09090b;
            padding: 32px 40px;
            text-align: center;
        }
        .brand-title {
            color: #ffffff;
            font-size: 22px;
            font-weight: 700;
            margin: 0 0 6px 0;
            letter-spacing: -0.5px;
        }
        .brand-subtitle {
            color: #a1a1aa;
            font-size: 13px;
            margin: 0;
        }
        .content {
            padding: 36px 40px;
            font-size: 15px;
            line-height: 1.8;
            color: #334155;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #0f172a;
            margin-top: 0;
            margin-bottom: 16px;
        }
        .lead-text {
            margin-bottom: 20px;
        }
        .coupon-card {
            background-color: #fafafa;
            border: 2px dashed #09090b;
            border-radius: 10px;
            padding: 24px;
            margin: 28px 0;
            text-align: center;
        }
        .coupon-label {
            font-size: 13px;
            color: #71717a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .coupon-code {
            display: inline-block;
            background-color: #09090b;
            color: #ffffff;
            font-family: 'Courier New', Courier, monospace;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 2px;
            padding: 10px 24px;
            border-radius: 6px;
            margin: 6px 0 12px 0;
        }
        .coupon-desc {
            font-size: 13px;
            color: #52525b;
            margin: 0;
        }
        .bullet-list {
            padding-right: 20px;
            margin: 18px 0;
            color: #475569;
        }
        .bullet-list li {
            margin-bottom: 8px;
        }
        .cta-container {
            margin: 32px 0 20px 0;
            text-align: center;
        }
        .btn-primary {
            display: block;
            background-color: #09090b;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 12px;
        }
        .btn-whatsapp {
            display: block;
            background-color: #10b981;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            text-align: center;
        }
        .footer {
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 20px 40px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
        }
        .footer a {
            color: #64748b;
            text-decoration: underline;
        }
        @media only screen and (max-width: 600px) {
            .container {
                border-radius: 0;
                border-left: none;
                border-right: none;
            }
            .content, .header, .footer {
                padding-left: 20px;
                padding-right: 20px;
            }
            .coupon-code {
                font-size: 20px;
                padding: 8px 16px;
            }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1 class="brand-title">Musoftwares</h1>
            <p class="brand-subtitle">أنظمة برمجية متقدمة وحلول تقنية مخصصة</p>
        </div>

        <!-- Body Content -->
        <div class="content">
            <p class="greeting">أهلاً بك يا {{ $clientFirstName }}،</p>

            @if($reminderNumber === 1)
                <p class="lead-text">
                    بقالنا فترة ما اشتغلناش مع بعض وحبينا نسلم عليك ونفكرك إننا دايماً موجودين وجاهزين نكون شريكك التقني في أي وقت.
                </p>
                <p>
                    لو بتفكر تطور السيستم الحالي أو تنفذ أي فكرة سوفت وير جديدة، عملنا لحضرتك عرض خاص تقديراً لثقتك السابقة فينا:
                </p>
            @elseif($reminderNumber === 2)
                <p class="lead-text">
                    بنفكرك بعرض الـ 50% اللي كنا شاركناه معاك بخصوص أي مشروع برمجي جديد أو تطوير لنظامك.
                </p>
                <p>
                    فريق العمل في Musoftwares جاهز لتحويل فكرتك لحل تقني متكامل، سواء كان سيستم إدارة، منصة إلكترونية، أو تطبيق مخصص.
                </p>
            @elseif($reminderNumber === 3)
                <p class="lead-text">
                    هل فيه أي مشروع برمجي أو أتمتة بتخطط ليها؟ لسه بنحتفظ ليك بعرض الـ 50% المخصص لعملائنا السابقين.
                </p>
                <p>
                    ممكن نتناقش مباشرة في التفاصيل ونحدد المتطلبات والجدول الزمني مع تطبيق الخصم فوراً.
                </p>
            @else
                <p class="lead-text">
                    حبينا نبعتلك تذكير أخير بخصوص خصم الـ 50% على تطوير البرمجيات قبل انتهاء فترة المبادرة.
                </p>
                <p>
                    سواء كنت محتاج استشارة تقنية سريعة أو حابب تبدأ مشروع متكامل، الباب دايماً مفتوح ومستعدين لخدمتك.
                </p>
            @endif

            <!-- Coupon Box -->
            <div class="coupon-card">
                <div class="coupon-label">كود الخصم الحصري (50%)</div>
                <div class="coupon-code">{{ $couponCode }}</div>
                <p class="coupon-desc">صالح للاستخدام على أي مشروع جديد أو تطوير برمجي عبر المنصة أو في جلسة التخطيط</p>
            </div>

            <p style="font-weight: 600; margin-bottom: 8px;">إيه الجديد عندنا في Musoftwares؟</p>
            <ul class="bullet-list">
                <li>أنظمة إدارة ومحاسبة سحابية متقدمة (ERP) مجهزة بالكامل.</li>
                <li>حلول أتمتة وربط ذكي للواتساب وقنوات التواصل.</li>
                <li>تطوير مخصص فائق السرعة والأمان بأحدث المعايير البرمجية.</li>
                <li>دعم فني وتواصل مباشر خلال مراحل المشروع.</li>
            </ul>

            <!-- Call To Actions -->
            <div class="cta-container">
                <a href="{{ $projectUrl }}" class="btn-primary">
                    بدء وتخطيط مشروعك البرمجي الآن
                </a>
                <a href="{{ $whatsappUrl }}" class="btn-whatsapp">
                    تواصل معي مباشرة عبر واتساب
                </a>
            </div>

            <p style="font-size: 13px; color: #64748b; text-align: center; margin-top: 24px;">
                يمكنك أيضاً الرد مباشرة على هذا الإيميل وسيتم التواصل معك خلال ساعات العمل.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="margin: 0 0 6px 0;">
                Musoftwares &bull; حلول برمجية مخصصة للشركات والأعمال
            </p>
            <p style="margin: 0;">
                تصلك هذه الرسالة لأنك عميل مسجل لدينا.
                &bull;
                <a href="{{ $unsubscribeUrl }}">إلغاء الاشتراك من رسائل التذكير</a>
            </p>
        </div>
    </div>
</div>
</body>
</html>

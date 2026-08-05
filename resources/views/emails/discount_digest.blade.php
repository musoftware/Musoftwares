<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Marketplace Discounts</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f4f7;
            color: #333333;
            margin: 0;
            padding: 0;
            direction: rtl;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .header {
            background: linear-gradient(135deg, #1e1e2d 0%, #2d2d44 100%);
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
        }
        .header p {
            margin: 8px 0 0;
            font-size: 14px;
            color: #a1a5b7;
        }
        .content {
            padding: 25px 20px;
        }
        .service-card {
            display: flex;
            border: 1px solid #eef2f7;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #fafbfc;
        }
        .service-info {
            flex: 1;
        }
        .service-title {
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 6px 0;
            color: #1e1e2d;
        }
        .service-title a {
            color: #1e1e2d;
            text-decoration: none;
        }
        .service-seller {
            font-size: 12px;
            color: #7e8299;
            margin-bottom: 10px;
        }
        .badge {
            display: inline-block;
            background-color: #ef4444;
            color: #ffffff;
            font-size: 12px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            margin-left: 8px;
        }
        .price-box {
            font-size: 15px;
            font-weight: 700;
            color: #10b981;
        }
        .old-price {
            text-decoration: line-through;
            color: #a1a5b7;
            font-size: 13px;
            margin-left: 6px;
            font-weight: 400;
        }
        .cta-btn {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 16px;
            background-color: #2563eb;
            color: #ffffff !important;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }
        .footer {
            background-color: #f9fafb;
            padding: 15px 20px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            border-top: 1px solid #eef2f7;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔥 عروض وخصومات مميزة لك اليوم!</h1>
            <p>مرحباً {{ $user->name }}، لقد اخترنا لك أفضل الخدمات ذات الخصومات الحصرية اليوم في متجر Musoftware</p>
        </div>

        <div class="content">
            @foreach($discountedServices as $service)
                @php
                    $discountPkg = $service->packages->first(fn($p) => $p->has_discount);
                @endphp
                @if($discountPkg)
                    <div class="service-card">
                        <div class="service-info">
                            <h3 class="service-title">
                                <a href="{{ $service->url }}">{{ $service->title }}</a>
                            </h3>
                            <div class="service-seller">البائع: {{ $service->seller->name ?? 'Musoftware Marketplace' }}</div>
                            
                            <div>
                                <span class="badge">-{{ $discountPkg->discount_percentage }}% OFF</span>
                                <span class="price-box">
                                    ${{ number_format($discountPkg->price, 2) }}
                                    <span class="old-price">${{ number_format($discountPkg->old_price, 2) }}</span>
                                </span>
                            </div>

                            <a href="{{ $service->url }}" class="cta-btn">عرض الخدمة والاستفادة من الخصم ←</a>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} Musoftware Marketplace. جميع الحقوق محفوظة.</p>
            <p>تصلك هذه الرسالة بصفتك عضواً في منصة Musoftware.</p>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلب جديد مدفوع</title>
</head>
<body style="margin:0;padding:0;background:#f5f5f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Tahoma,Arial,sans-serif;color:#1d1d1f;direction:rtl;">
    <div style="max-width:560px;margin:24px auto;background:#ffffff;border-radius:12px;padding:32px;">
        <h1 style="margin:0 0 16px;font-size:20px;">طلب جديد مدفوع {{ $reference }}</h1>
        <table style="width:100%;border-collapse:collapse;font-size:15px;line-height:2;">
            <tr><td style="color:#86868b;width:130px;">المنتج</td><td>{{ $productName }}@if ($planName) ({{ $planName }})@endif</td></tr>
            <tr><td style="color:#86868b;">المبلغ</td><td>{{ $amount }}</td></tr>
            <tr><td style="color:#86868b;">الاسم</td><td>{{ $clientName }}</td></tr>
            <tr><td style="color:#86868b;">الإيميل</td><td dir="ltr" style="text-align:right;">{{ $clientEmail }}</td></tr>
            <tr><td style="color:#86868b;">واتساب</td><td dir="ltr" style="text-align:right;">{{ $clientWhatsapp }}</td></tr>
            @if ($deviceId)
                <tr><td style="color:#86868b;">رقم الجهاز</td><td dir="ltr" style="text-align:right;word-break:break-all;">{{ $deviceId }}</td></tr>
            @endif
        </table>
        <p style="margin:20px 0 0;font-size:14px;"><a href="{{ $adminUrl }}" style="color:#0071e3;">فتح لوحة المنتجات</a></p>
    </div>
</body>
</html>

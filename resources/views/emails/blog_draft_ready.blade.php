<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مسودة مقال جديدة</title>
</head>
<body style="margin:0;padding:0;background:#f5f5f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Tahoma,Arial,sans-serif;color:#1d1d1f;direction:rtl;">
    <div style="max-width:560px;margin:24px auto;background:#ffffff;border-radius:12px;padding:32px;">
        <h1 style="margin:0 0 12px;font-size:20px;">مسودة مقال جديدة جاهزة للمراجعة</h1>
        <p style="margin:0 0 20px;font-size:15px;line-height:1.9;color:#515154;">
            اتكتبت بالذكاء الاصطناعي وعدّت فحص الجودة، لكنها <strong>ما اتنشرتش</strong>. اقراها وراجع المعلومات والأرقام وصحّح اللغة، وبعدين انشرها بنفسك.
        </p>
        <ul style="margin:0;padding:0;list-style:none;font-size:15px;line-height:2.2;">
            @foreach ($drafts as $draft)
                <li><span style="color:#86868b;">{{ strtoupper($draft['language']) }}</span> &nbsp; <a href="{{ $draft['editUrl'] }}" style="color:#0071e3;">{{ $draft['title'] }}</a></li>
            @endforeach
        </ul>
    </div>
</body>
</html>

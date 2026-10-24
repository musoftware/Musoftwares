<!DOCTYPE html>
<html lang="{{ $isAr ? 'ar' : 'en' }}" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isAr ? 'طلبك لسه ما اتدفعش' : 'Your order is not paid yet' }}</title>
</head>
<body style="margin:0;padding:0;background:#f5f5f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Tahoma,Arial,sans-serif;color:#1d1d1f;direction:{{ $isAr ? 'rtl' : 'ltr' }};">
    <div style="max-width:560px;margin:24px auto;background:#ffffff;border-radius:12px;padding:32px;">
        <p style="margin:0 0 16px;font-size:16px;">{{ $isAr ? 'مرحبًا' : 'Hello' }} {{ $name }}،</p>

        <p style="margin:0 0 16px;font-size:16px;line-height:1.9;">
            @if ($isAr)
                بدأت طلب <strong>{{ $productName }}</strong>@if ($planName) (باقة {{ $planName }})@endif بمبلغ <strong>{{ $amount }}</strong>، ولسه الدفع ما اكتملش.
            @else
                You started an order for <strong>{{ $productName }}</strong>@if ($planName) ({{ $planName }} plan)@endif for <strong>{{ $amount }}</strong>, and the payment was not completed.
            @endif
        </p>

        <p style="margin:0 0 24px;font-size:16px;line-height:1.9;">
            {{ $isAr
                ? 'لو حصلت مشكلة وقت الدفع أو عندك سؤال، ردّ على الرسالة دي أو كلمنا على واتساب. والزرار تحت بيكمّل نفس الطلب.'
                : 'If something went wrong while paying, or you have a question, reply to this email or message us on WhatsApp. The button below continues the same order.' }}
        </p>

        @if ($discountPercent > 0 && $discountedAmount)
            <p style="margin:0 0 24px;font-size:16px;line-height:1.9;background:#f5f5f7;border-radius:8px;padding:16px;">
                @if ($isAr)
                    خصم <strong>{{ $discountPercent }}%</strong> على نفس الطلب: بدل {{ $amount }} بتدفع <strong>{{ $discountedAmount }}</strong>. الخصم بيتطبق تلقائيًا لما تكمّل من الزرار تحت، وصالح 72 ساعة من وصول الرسالة دي.
                @else
                    <strong>{{ $discountPercent }}% off</strong> this same order: instead of {{ $amount }} you pay <strong>{{ $discountedAmount }}</strong>. The discount is applied automatically when you continue with the button below, and it is valid for 72 hours from this email.
                @endif
            </p>
        @endif

        <p style="margin:0 0 24px;text-align:center;">
            <a href="{{ $resumeUrl }}" style="display:inline-block;background:#0071e3;color:#ffffff;text-decoration:none;font-weight:600;font-size:16px;padding:14px 32px;border-radius:8px;">{{ $isAr ? 'كمّل الدفع' : 'Continue to payment' }}</a>
        </p>

        <p style="margin:0 0 8px;font-size:14px;color:#515154;line-height:1.8;">
            {{ $isAr ? 'رقم الطلب' : 'Order number' }}: {{ $reference }}<br>
            {{ $isAr ? 'اللينك صالح 72 ساعة. لو غيّرت رأيك تجاهل الرسالة، ومش هنبعتلك تاني.' : 'The link is valid for 72 hours. If you changed your mind, ignore this email. We will not send another one.' }}
        </p>

        <p style="margin:16px 0 0;font-size:14px;"><a href="{{ $whatsappUrl }}" style="color:#0071e3;">{{ $isAr ? 'كلمنا على واتساب' : 'Message us on WhatsApp' }}</a></p>
    </div>
    <p style="text-align:center;font-size:12px;color:#86868b;">Musoftware</p>
</body>
</html>

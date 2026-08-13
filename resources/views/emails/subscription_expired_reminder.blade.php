<!DOCTYPE html>
<html lang="{{ $isArabic ? 'ar' : 'en' }}" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('billing.subscription_expired_subject') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 0;
            color: #333333;
            text-align: {{ $isArabic ? 'right' : 'left' }};
        }
        .email-wrapper {
            width: 100%;
            background-color: #f4f6f8;
            padding: 40px 0;
        }
        .email-content {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .email-header {
            background-color: #0f172a;
            color: #ffffff;
            padding: 24px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        .email-body {
            padding: 32px 24px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            margin-top: 0;
            margin-bottom: 16px;
        }
        .message-text {
            font-size: 15px;
            line-height: 1.6;
            color: #475569;
            margin-bottom: 24px;
        }
        .expired-card {
            background-color: #fef2f2;
            border: 1px solid #fee2e2;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 28px;
        }
        .expired-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #fca5a5;
            font-size: 14px;
        }
        .expired-row:last-child {
            border-bottom: none;
        }
        .expired-label {
            color: #7f1d1d;
            font-weight: 500;
        }
        .expired-value {
            font-weight: 600;
            color: #991b1b;
        }
        .cta-container {
            text-align: center;
            margin: 32px 0;
        }
        .btn-primary {
            background-color: #dc2626;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            display: inline-block;
            box-shadow: 0 2px 4px rgba(220, 38, 38, 0.2);
        }
        .email-footer {
            background-color: #f8fafc;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-content">
            <div class="email-header">
                <h1>{{ __('billing.subscription_expired_subject') }}</h1>
            </div>
            <div class="email-body">
                <div class="greeting">{{ __('billing.subscription_expired_greeting', ['name' => $userName]) }}</div>
                
                <div class="message-text">
                    {{ __('billing.subscription_expired_body', ['module' => $moduleName, 'date' => $expiresAt]) }}
                </div>
                
                <div class="expired-card">
                    <div class="expired-row">
                        <span class="expired-label">{{ $isArabic ? 'الخدمة / الموديل:' : 'Service / Module:' }}</span>
                        <span class="expired-value">{{ $moduleName }}</span>
                    </div>
                    <div class="expired-row">
                        <span class="expired-label">{{ $isArabic ? 'تاريخ انتهاء الصلاحية:' : 'Expiration Date:' }}</span>
                        <span class="expired-value">{{ $expiresAt }}</span>
                    </div>
                </div>

                <div class="cta-container">
                    <a href="{{ $renewUrl }}" class="btn-primary">{{ __('billing.subscription_expired_action') }}</a>
                </div>

                <div class="message-text" style="font-size: 13px; color: #64748b; margin-bottom: 0;">
                    {{ __('billing.subscription_expired_footer') }}
                </div>
            </div>
            <div class="email-footer">
                {{ $isArabic ? 'شكراً لتعاملك معنا! • Musoftware' : 'Thank you for your business! • Musoftware' }}
            </div>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('privacy_compliance.blocked_title') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f5f7;
            color: #1d1d1f;
            font-family: 'Cairo', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            padding: 16px;
            box-sizing: border-box;
        }
        .card {
            max-width: 600px;
            width: 100%;
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 20px;
            padding: 32px 24px;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.06);
        }
        h1 { font-size: 22px; margin: 0 0 12px; line-height: 1.4; }
        p { font-size: 14px; color: #515154; line-height: 1.7; margin: 0 0 20px; }
        .actions { display: flex; flex-direction: column; gap: 10px; }
        button {
            width: 100%;
            min-height: 44px;
            border-radius: 12px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            padding: 10px 16px;
        }
        .btn-primary { background: #0071e3; color: #fff; border: none; }
        .btn-secondary { background: #ffffff; color: #1d1d1f; border: 1px solid rgba(0, 0, 0, 0.18); }
        .links {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid rgba(0, 0, 0, 0.08);
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            font-size: 13px;
        }
        .links a { color: #0066cc; text-decoration: none; font-weight: 600; }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <main class="card" role="main">
        <h1>{{ __('privacy_compliance.blocked_title') }}</h1>
        <p>{{ __('privacy_compliance.blocked_description') }}</p>

        <div class="actions">
            <form method="POST" action="{{ route('privacy.consent.gate') }}">
                @csrf
                <input type="hidden" name="essential_consent" value="1">
                <input type="hidden" name="cookies_tracking" value="0">
                <button type="submit" class="btn-secondary">
                    {{ __('privacy_compliance.blocked_accept_and_unlock_btn') }}
                </button>
            </form>

            <form method="POST" action="{{ route('privacy.consent.gate') }}">
                @csrf
                <input type="hidden" name="essential_consent" value="1">
                <input type="hidden" name="cookies_tracking" value="1">
                <button type="submit" class="btn-primary">
                    {{ __('privacy_compliance.blocked_accept_all_and_unlock_btn') }}
                </button>
            </form>
        </div>

        <nav class="links" aria-label="Legal policies">
            <a href="/privacy-policy">{{ __('privacy_compliance.read_privacy_policy') }}</a>
            <a href="/terms-of-service">{{ __('privacy_compliance.read_terms') }}</a>
            <a href="/cookie-policy">{{ __('privacy_compliance.read_cookies') }}</a>
        </nav>
    </main>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>503 - Service Unavailable</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px;
            box-sizing: border-box;
        }
        .container {
            max-width: 480px;
            width: 100%;
            text-align: center;
            padding: 40px;
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.5), 0 8px 10px -6px rgb(0 0 0 / 0.5);
        }
        .icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: rgba(244, 63, 94, 0.1);
            border: 1px solid rgba(244, 63, 94, 0.2);
            color: #f43f5e;
            margin-bottom: 24px;
        }
        h1 {
            font-size: 72px;
            font-weight: 800;
            margin: 0;
            line-height: 1;
            background: linear-gradient(135deg, #f43f5e, #fb7185);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        h2 {
            font-size: 20px;
            font-weight: 600;
            margin: 16px 0 8px;
            color: #f1f5f9;
        }
        p {
            font-size: 15px;
            color: #94a3b8;
            line-height: 1.6;
            margin: 0 0 32px 0;
        }
        .btn {
            display: inline-block;
            background: #ffffff;
            color: #0f172a;
            font-weight: 500;
            font-size: 14px;
            padding: 12px 24px;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn:hover {
            background: #e2e8f0;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        </div>
        <h1>503</h1>
        <h2>{{ __('general.under_maintenance') }}</h2>
        <p>{{ __('general.we_are_currently_undergoing_scheduled_maintenance_please_check_back_shortly') }}</p>
        <a href="/" class="btn">{{ __('general.go_home') }}</a>
    </div>
</body>
</html>

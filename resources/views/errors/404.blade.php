<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - Page Not Found</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #fafafa;
            --text-main: #18181b;
            --text-muted: #71717a;
            --btn-bg: #18181b;
            --btn-text: #ffffff;
            --btn-hover: #27272a;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #09090b;
                --text-main: #f4f4f5;
                --text-muted: #a1a1aa;
                --btn-bg: #f4f4f5;
                --btn-text: #18181b;
                --btn-hover: #e4e4e7;
            }
        }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px;
            box-sizing: border-box;
            transition: background-color 0.3s, color 0.3s;
        }
        .container {
            max-width: 672px; /* max-w-2xl */
            width: 100%;
            text-align: center;
            padding: 40px;
            animation: slideUp 0.5s ease-out forwards;
            opacity: 0;
            transform: translateY(20px);
        }
        @keyframes slideUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        h1 {
            font-size: 72px;
            font-weight: 800;
            letter-spacing: -0.05em; /* tracking-tighter */
            margin: 0 0 16px 0;
            line-height: 1;
            color: var(--text-main);
        }
        @media (min-width: 640px) {
            h1 {
                font-size: 128px; /* sm:text-9xl */
            }
        }
        h2 {
            font-size: 24px;
            font-weight: 600;
            letter-spacing: -0.025em; /* tracking-tight */
            margin: 0 0 8px 0;
            color: var(--text-main);
        }
        @media (min-width: 640px) {
            h2 {
                font-size: 30px; /* sm:text-3xl */
            }
        }
        p {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.5;
            margin: 0 auto 32px auto;
            max-width: 448px; /* max-w-md */
        }
        @media (min-width: 640px) {
            p {
                font-size: 16px; /* sm:text-base */
            }
        }
        .btn-wrapper {
            animation: fadeIn 0.5s ease-out 0.2s forwards;
            opacity: 0;
        }
        @keyframes fadeIn {
            to { opacity: 1; }
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--btn-bg);
            color: var(--btn-text);
            font-weight: 500;
            font-size: 14px; /* text-sm */
            height: 44px; /* h-11 */
            padding: 0 32px; /* px-8 */
            border-radius: 9999px; /* rounded-full */
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn:hover {
            background: var(--btn-hover);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>404</h1>
        <h2>{{ __('general.page_not_found') }}</h2>
        <p>{{ __('general.the_page_you_are_looking_for_does_not_exist_or_has_been_moved') }}</p>
        <div class="btn-wrapper">
            <a href="/" class="btn">{{ __('general.go_back_home') }}</a>
        </div>
    </div>
</body>
</html>

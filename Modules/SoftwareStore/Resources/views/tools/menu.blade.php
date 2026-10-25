@php
    $lang = $data['lang'] === 'en' ? 'en' : 'ar';
    $t = fn (string $key) => __("free_tools.tools.qr-menu-maker.menu.{$key}", [], $lang);
    $price = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ','), '0'), '.');
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}" dir="{{ $lang === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="{{ $data['color'] }}">
    <title>{{ $data['name'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --accent: {{ $data['color'] }}; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Inter", "Cairo", system-ui, sans-serif; background: #faf9f7; color: #1d1d1f; -webkit-font-smoothing: antialiased; }
        header { background: var(--accent); color: #fff; padding: 36px 20px 28px; text-align: center; }
        header h1 { margin: 0; font-size: 28px; font-weight: 700; }
        header p { margin: 8px 0 0; opacity: .9; font-size: 15px; }
        .call { display: inline-flex; gap: 8px; align-items: center; margin-top: 16px; background: #fff; color: var(--accent); border-radius: 999px; padding: 9px 18px; font-weight: 700; font-size: 14px; text-decoration: none; }
        nav { position: sticky; top: 0; z-index: 5; background: #fff; border-bottom: 1px solid rgba(0,0,0,.06); display: flex; gap: 8px; overflow-x: auto; padding: 10px 16px; scrollbar-width: none; }
        nav a { flex: none; padding: 7px 14px; border-radius: 999px; background: #f2f1ee; color: #1d1d1f; text-decoration: none; font-size: 14px; font-weight: 600; }
        main { max-width: 720px; margin: 0 auto; padding: 8px 16px 40px; }
        section { scroll-margin-top: 64px; }
        h2 { font-size: 20px; margin: 28px 0 12px; color: var(--accent); }
        .item { background: #fff; border-radius: 16px; padding: 14px 16px; margin-bottom: 10px; display: flex; justify-content: space-between; gap: 16px; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
        .item b { font-size: 16px; }
        .item small { display: block; margin-top: 4px; color: rgba(29,29,31,.6); font-size: 13px; line-height: 1.6; }
        .price { flex: none; font-weight: 700; white-space: nowrap; }
        footer { text-align: center; padding: 24px 16px 40px; font-size: 13px; color: rgba(29,29,31,.55); }
        footer a { color: #0071e3; font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>
    <header>
        <h1>{{ $data['name'] }}</h1>
        @if (! empty($data['tagline']))<p>{{ $data['tagline'] }}</p>@endif
        @if (! empty($data['phone']))
            <a class="call" href="tel:{{ preg_replace('/[^0-9+]/', '', $data['phone']) }}">☎ {{ $t('call') }} <span dir="ltr">{{ $data['phone'] }}</span></a>
        @endif
    </header>

    @if (count($data['sections']) > 1)
        <nav>
            @foreach ($data['sections'] as $section)
                <a href="#s{{ $loop->index }}">{{ $section['title'] }}</a>
            @endforeach
        </nav>
    @endif

    <main>
        @foreach ($data['sections'] as $section)
            <section id="s{{ $loop->index }}">
                <h2>{{ $section['title'] }}</h2>
                @foreach ($section['items'] as $item)
                    <div class="item">
                        <div><b>{{ $item['name'] }}</b>@if (! empty($item['note']))<small>{{ $item['note'] }}</small>@endif</div>
                        @if ($item['price'] !== null)
                            <span class="price" dir="ltr">{{ $price($item['price']) }} {{ $data['currency'] }}</span>
                        @endif
                    </div>
                @endforeach
            </section>
        @endforeach
    </main>

    <footer>
        {{ $t('made_with') }} ·
        <a href="{{ \Modules\SoftwareStore\Services\FreeTools::url('qr-menu-maker') }}{{ $lang === 'ar' ? '?lang=ar' : '' }}">{{ $t('make_yours') }}</a>
    </footer>
</body>
</html>

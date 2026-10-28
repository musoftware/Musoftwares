@php
    use Modules\SoftwareStore\Services\QrMenuKit;

    $lang = ($data['lang'] ?? 'ar') === 'en' ? 'en' : 'ar';
    $t = fn (string $key) => __("free_tools.tools.qr-menu-maker.menu.{$key}", [], $lang);
    $currency = $lang === 'ar' ? (['EGP' => 'ج.م', 'SAR' => 'ر.س', 'AED' => 'د.إ', 'KWD' => 'د.ك', 'QAR' => 'ر.ق', 'USD' => '$'][$data['currency']] ?? $data['currency']) : $data['currency'];
    $price = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ','), '0'), '.').' '.$currency;
    $dark = ($data['theme'] ?? 'light') === 'dark';
    $cover = QrMenuKit::url($data['cover'] ?? null);
    $logo = QrMenuKit::url($data['logo'] ?? null);
    $digits = fn (?string $v) => preg_replace('/[^0-9+]/', '', (string) $v);
    $popular = collect($data['sections'])->flatMap(fn ($s) => $s['items'])
        ->filter(fn ($i) => in_array('popular', $i['badges'] ?? [], true) && ! empty($i['image']) && empty($i['sold_out']))->take(8)->values();
    $badgeIcon = ['popular' => '★', 'new' => '✦', 'spicy' => '🌶', 'veg' => '🌿'];
    // what the dish sheet shows when an item with a photo is tapped
    $dish = fn (array $item) => json_encode(['name' => $item['name'], 'note' => $item['note'] ?? null, 'price' => $item['price'] !== null ? $price($item['price']) : null, 'image' => QrMenuKit::url($item['image'] ?? null)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}" dir="{{ $lang === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="{{ $data['color'] }}">
    <title>{{ $data['name'] }}</title>
    @if ($cover)<meta property="og:image" content="{{ $cover }}">@endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=El+Messiri:wght@600;700&family=Tajawal:wght@400;500;700&family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --accent: {{ $data['color'] }};
            --bg: {{ $dark ? '#0f0f10' : '#faf7f2' }};
            --card: {{ $dark ? '#1a1a1c' : '#ffffff' }};
            --text: {{ $dark ? '#f4f1ec' : '#1c1917' }};
            --muted: {{ $dark ? 'rgba(244,241,236,.6)' : 'rgba(28,25,23,.58)' }};
            --line: {{ $dark ? 'rgba(255,255,255,.08)' : 'rgba(28,25,23,.08)' }};
            --chip: {{ $dark ? '#242427' : '#f1ece4' }};
            --head: {!! $lang === 'ar' ? '"El Messiri"' : '"Playfair Display"' !!}, serif;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; background: var(--bg); color: var(--text); font-family: {!! $lang === 'ar' ? '"Tajawal", "Inter"' : '"Inter", "Tajawal"' !!}, system-ui, sans-serif; -webkit-font-smoothing: antialiased; }
        img { display: block; max-width: 100%; }
        .wrap { max-width: 760px; margin: 0 auto; }

        .cover { height: 230px; background: var(--accent) center/cover no-repeat; position: relative; }
        .cover::after { content: ""; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(0,0,0,.05) 30%, rgba(0,0,0,.55)); }
        .brand { position: relative; margin: -64px 16px 0; background: var(--card); border-radius: 24px; padding: 20px; box-shadow: 0 12px 40px rgba(0,0,0,.10); z-index: 2; text-align: center; }
        .logo { width: 84px; height: 84px; margin: -62px auto 10px; border-radius: 50%; border: 4px solid var(--card); background: var(--accent) center/cover no-repeat; color: #fff; display: grid; place-items: center; font: 700 34px var(--head); box-shadow: 0 6px 18px rgba(0,0,0,.18); }
        h1 { margin: 0; font: 700 30px/1.25 var(--head); }
        .tagline { margin: 6px 0 0; color: var(--muted); font-size: 15px; line-height: 1.6; }
        .facts { display: flex; flex-wrap: wrap; justify-content: center; gap: 6px 14px; margin-top: 12px; font-size: 13px; color: var(--muted); }
        .actions { display: flex; gap: 8px; justify-content: center; margin-top: 16px; flex-wrap: wrap; }
        .btn { display: inline-flex; align-items: center; gap: 6px; height: 40px; padding: 0 16px; border-radius: 999px; font-weight: 700; font-size: 14px; text-decoration: none; }
        .btn-main { background: var(--accent); color: #fff; }
        .btn-wa { background: #25d366; color: #fff; }

        nav { position: sticky; top: 0; z-index: 10; background: color-mix(in srgb, var(--bg) 92%, transparent); backdrop-filter: blur(10px); border-bottom: 1px solid var(--line); margin-top: 18px; }
        nav .wrap { display: flex; gap: 8px; overflow-x: auto; padding: 10px 16px; scrollbar-width: none; }
        nav .wrap::-webkit-scrollbar { display: none; }
        nav a { flex: none; padding: 8px 16px; border-radius: 999px; background: var(--chip); color: var(--text); text-decoration: none; font-size: 14px; font-weight: 600; transition: background .2s, color .2s; }
        nav a.on { background: var(--accent); color: #fff; }

        main { padding: 4px 16px 32px; }
        h2 { font: 700 23px var(--head); margin: 30px 0 14px; display: flex; align-items: center; gap: 10px; }
        h2::after { content: ""; flex: 1; height: 1px; background: var(--line); }
        section { scroll-margin-top: 70px; }

        .strip { display: flex; gap: 12px; overflow-x: auto; padding-bottom: 6px; scroll-snap-type: x mandatory; scrollbar-width: none; }
        .strip::-webkit-scrollbar { display: none; }
        .hot { flex: none; width: 168px; scroll-snap-align: start; background: var(--card); border-radius: 18px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,.06); cursor: pointer; border: 0; padding: 0; text-align: start; color: inherit; font: inherit; }
        .hot img { width: 100%; aspect-ratio: 4/3; object-fit: cover; }
        .hot div { padding: 10px 12px 12px; }
        .hot b { display: block; font-size: 14px; }
        .hot span { color: var(--accent); font-weight: 700; font-size: 14px; }

        .item { width: 100%; display: flex; gap: 14px; align-items: stretch; background: var(--card); border: 0; border-radius: 20px; padding: 14px; margin-bottom: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.05); text-align: start; color: inherit; font: inherit; }
        .item.has-photo { cursor: pointer; }
        .item .body { flex: 1; min-width: 0; display: flex; flex-direction: column; }
        .item b { font-size: 16px; line-height: 1.4; }
        .item p { margin: 4px 0 0; color: var(--muted); font-size: 13.5px; line-height: 1.6; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .item .price { margin-top: auto; padding-top: 8px; font-weight: 700; color: var(--accent); font-size: 15px; }
        .item img { flex: none; width: 104px; height: 104px; border-radius: 16px; object-fit: cover; }
        .item.out { opacity: .5; }
        .badges { display: inline-flex; gap: 4px; flex-wrap: wrap; margin-inline-start: 6px; vertical-align: middle; }
        .badge { font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 999px; background: color-mix(in srgb, var(--accent) 14%, transparent); color: var(--accent); }
        .badge.out { background: var(--chip); color: var(--muted); }

        dialog { border: 0; padding: 0; width: min(560px, 100%); max-height: 92vh; margin: auto auto 0; border-radius: 24px 24px 0 0; background: var(--card); color: var(--text); }
        dialog::backdrop { background: rgba(0,0,0,.55); }
        dialog img { width: 100%; aspect-ratio: 4/3; object-fit: cover; }
        dialog .in { padding: 18px 20px 26px; }
        dialog h3 { margin: 0; font: 700 22px var(--head); }
        dialog p { color: var(--muted); line-height: 1.7; }
        dialog .close { position: absolute; top: 12px; inset-inline-end: 12px; width: 36px; height: 36px; border-radius: 50%; border: 0; background: rgba(0,0,0,.55); color: #fff; font-size: 20px; cursor: pointer; }
        @media (min-width: 640px) { dialog { margin: auto; border-radius: 24px; } }

        .demo { background: #111; color: #fff; text-align: center; padding: 10px 16px; font-size: 14px; }
        .demo a { color: #fbbf24; font-weight: 700; }
        footer { text-align: center; padding: 10px 16px 40px; font-size: 13px; color: var(--muted); }
        footer a { color: var(--accent); font-weight: 700; text-decoration: none; }
    </style>
</head>
<body>
    @if ($demo ?? false)
        <div class="demo">{{ $t('demo_note') }} <a href="{{ \Modules\SoftwareStore\Services\FreeTools::url('qr-menu-maker') }}{{ $lang === 'ar' ? '?lang=ar' : '' }}#demo">{{ $t('make_yours') }}</a></div>
    @endif

    <div class="cover" @if ($cover) style="background-image: url('{{ $cover }}')" @endif></div>

    <div class="wrap">
        <div class="brand">
            <div class="logo" @if ($logo) style="background-image: url('{{ $logo }}')" @endif>@unless ($logo){{ mb_substr($data['name'], 0, 1) }}@endunless</div>
            <h1>{{ $data['name'] }}</h1>
            @if (! empty($data['tagline']))<p class="tagline">{{ $data['tagline'] }}</p>@endif
            @if (! empty($data['hours']) || ! empty($data['address']))
                <div class="facts">
                    @if (! empty($data['hours']))<span>🕒 {{ $data['hours'] }}</span>@endif
                    @if (! empty($data['address']))<span>📍 {{ $data['address'] }}</span>@endif
                </div>
            @endif
            @if (! empty($data['phone']) || ! empty($data['whatsapp']))
                <div class="actions">
                    @if (! empty($data['phone']))<a class="btn btn-main" href="tel:{{ $digits($data['phone']) }}">☎ {{ $t('call') }}</a>@endif
                    @if (! empty($data['whatsapp']))<a class="btn btn-wa" href="https://wa.me/{{ ltrim($digits($data['whatsapp']), '+') }}" target="_blank" rel="noopener">💬 {{ $t('whatsapp') }}</a>@endif
                </div>
            @endif
        </div>
    </div>

    @if (count($data['sections']) > 1)
        <nav><div class="wrap">
            @if ($popular->isNotEmpty())<a href="#popular">★ {{ $t('popular') }}</a>@endif
            @foreach ($data['sections'] as $section)
                <a href="#s{{ $loop->index }}">{{ $section['title'] }}</a>
            @endforeach
        </div></nav>
    @endif

    <main class="wrap">
        @if ($popular->isNotEmpty())
            <section id="popular">
                <h2>{{ $t('popular') }}</h2>
                <div class="strip">
                    @foreach ($popular as $item)
                        <button type="button" class="hot" data-item="{{ $dish($item) }}">
                            <img src="{{ QrMenuKit::url($item['image']) }}" alt="" loading="lazy">
                            <div><b>{{ $item['name'] }}</b>@if ($item['price'] !== null)<span dir="ltr">{{ $price($item['price']) }}</span>@endif</div>
                        </button>
                    @endforeach
                </div>
            </section>
        @endif

        @foreach ($data['sections'] as $section)
            <section id="s{{ $loop->index }}">
                <h2>{{ $section['title'] }}</h2>
                @foreach ($section['items'] as $item)
                    @php
                        $photo = QrMenuKit::url($item['image'] ?? null);
                        $out = ! empty($item['sold_out']);
                    @endphp
                    <button type="button" class="item {{ $photo ? 'has-photo' : '' }} {{ $out ? 'out' : '' }}" @if ($photo) data-item="{{ $dish($item) }}" @else tabindex="-1" @endif>
                        <span class="body">
                            <span>
                                <b>{{ $item['name'] }}</b>
                                @if (! empty($item['badges']) || $out)
                                    <span class="badges">
                                        @foreach ($item['badges'] ?? [] as $badge)<span class="badge">{{ $badgeIcon[$badge] ?? '' }} {{ $t("badges.{$badge}") }}</span>@endforeach
                                        @if ($out)<span class="badge out">{{ $t('sold_out') }}</span>@endif
                                    </span>
                                @endif
                            </span>
                            @if (! empty($item['note']))<p>{{ $item['note'] }}</p>@endif
                            @if ($item['price'] !== null)<span class="price" dir="ltr">{{ $price($item['price']) }}</span>@endif
                        </span>
                        @if ($photo)<img src="{{ $photo }}" alt="" loading="lazy">@endif
                    </button>
                @endforeach
            </section>
        @endforeach
    </main>

    <dialog id="dish">
        <button type="button" class="close" aria-label="{{ $t('close') }}">×</button>
        <img alt="">
        <div class="in"><h3></h3><p></p><b class="price" style="color: var(--accent); font-size: 18px" dir="ltr"></b></div>
    </dialog>

    <footer>
        {{ $t('made_with') }} · <a href="{{ \Modules\SoftwareStore\Services\FreeTools::url('qr-menu-maker') }}{{ $lang === 'ar' ? '?lang=ar' : '' }}">{{ $t('make_yours') }}</a>
    </footer>

    <script>
        (function () {
            var dish = document.getElementById('dish');
            document.querySelectorAll('[data-item]').forEach(function (el) {
                el.addEventListener('click', function () {
                    var d = JSON.parse(el.dataset.item);
                    dish.querySelector('img').src = d.image;
                    dish.querySelector('h3').textContent = d.name;
                    dish.querySelector('p').textContent = d.note || '';
                    dish.querySelector('.price').textContent = d.price || '';
                    dish.showModal();
                });
            });
            dish.querySelector('.close').addEventListener('click', function () { dish.close(); });
            dish.addEventListener('click', function (e) { if (e.target === dish) dish.close(); });

            // the tab of the section on screen is highlighted and kept in view
            var links = {};
            document.querySelectorAll('nav a').forEach(function (a) { links[a.getAttribute('href').slice(1)] = a; });
            var bar = document.querySelector('nav .wrap');
            if (!bar || !('IntersectionObserver' in window)) return;
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    var link = links[entry.target.id];
                    if (!entry.isIntersecting || !link) return;
                    Object.keys(links).forEach(function (id) { links[id].classList.remove('on'); });
                    link.classList.add('on');
                    bar.scrollTo({ left: link.offsetLeft - bar.clientWidth / 2 + link.clientWidth / 2, behavior: 'smooth' });
                });
            }, { rootMargin: '-80px 0px -70% 0px' });
            document.querySelectorAll('main section').forEach(function (section) { observer.observe(section); });
        })();
    </script>
</body>
</html>

@php
    $locale = app()->getLocale();
    $brand = __('badge_card.brand');
    $headline = __('badge_card.headline', ['tier' => $card['tier_name']]);
    $pageTitle = __('badge_card.page_title', ['name' => $card['first_name'], 'tier' => $card['tier_name'], 'brand' => $brand]);
    $memberSince = $card['member_since'] ? __('badge_card.member_since', ['year' => $card['member_since']]) : '';
    $shareText = __('badge_card.share_text', ['tier' => $card['tier_name'], 'brand' => $brand]);
    $badgePng = asset("images/tiers/{$card['tier_slug']}.png");
    $inviteLabel = preg_replace('#^https?://#', '', $card['invite_url']);
    $script = [
        'badge' => $badgePng,
        'color' => $card['color'],
        'headline' => $headline,
        'name' => $card['first_name'],
        'since' => $memberSince,
        'thanks' => __('badge_card.thanks'),
        'brand' => $brand,
        'invite' => $inviteLabel,
        'shareText' => $shareText,
        'shareUrl' => $shareUrl,
        'fileName' => 'badge-'.$card['tier_slug'].'.png',
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ __('badge_card.thanks') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ __('badge_card.thanks') }}">
    <meta property="og:image" content="{{ $badgePng }}">
    <meta property="og:url" content="{{ $shareUrl }}">
    <meta name="twitter:card" content="summary">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root { --tier: {{ $card['color'] }}; --ink: #1d1d1f; --muted: #6e6e73; --page: #f5f5f7; --blue: #0071e3; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: var(--page); color: var(--ink); overflow-x: clip;
               font-family: "IBM Plex Sans Arabic", system-ui, -apple-system, "Segoe UI", sans-serif; }
        main { max-width: 420px; margin: 0 auto; padding: 32px 16px 48px; }
        .card { background: #fff; border-radius: 28px; overflow: hidden; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        .card-top { padding: 40px 24px 24px; background: color-mix(in srgb, var(--tier) 14%, #fff); }
        .card-top img { width: 160px; height: 160px; display: block; margin: 0 auto; }
        .card-body { padding: 24px 24px 32px; }
        h1 { margin: 0; font-size: 32px; line-height: 1.25; }
        .name { margin: 8px 0 0; font-size: 20px; font-weight: 700; }
        .since { margin: 4px 0 0; font-size: 14px; color: var(--muted); }
        .thanks { margin: 20px 0 0; font-size: 15px; color: var(--muted); }
        .brand { margin: 24px 0 0; padding-top: 20px; border-top: 1px solid rgba(0,0,0,.06); font-size: 15px; font-weight: 700; }
        .actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 20px; }
        .btn { min-height: 48px; padding: 0 16px; border: 0; border-radius: 999px; font: inherit; font-size: 15px; font-weight: 700;
               display: inline-flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; text-decoration: none; }
        .btn:focus-visible { outline: 3px solid var(--blue); outline-offset: 2px; }
        .btn-primary { grid-column: 1 / -1; background: var(--ink); color: #fff; }
        .btn-plain { background: #fff; color: var(--ink); box-shadow: inset 0 0 0 1px rgba(0,0,0,.1); }
        .btn svg { width: 18px; height: 18px; flex-shrink: 0; }
        .cta { display: block; margin-top: 28px; text-align: center; color: var(--blue); font-weight: 700; text-decoration: none; }
        .cta:hover { text-decoration: underline; }
    </style>
</head>
<body>
<main>
    <article class="card" aria-label="{{ $pageTitle }}">
        <div class="card-top">
            <img src="{{ asset("images/tiers/{$card['tier_slug']}.svg") }}" alt="{{ $headline }}" width="160" height="160">
        </div>
        <div class="card-body">
            <h1>{{ $headline }}</h1>
            <p class="name">{{ $card['first_name'] }}</p>
            @if ($memberSince)
                <p class="since">{{ $memberSince }}</p>
            @endif
            <p class="thanks">{{ __('badge_card.thanks') }}</p>
            <p class="brand">{{ $brand }}</p>
        </div>
    </article>

    <div class="actions">
        <button type="button" class="btn btn-primary" id="share-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
            {{ __('badge_card.share') }}
        </button>
        <button type="button" class="btn btn-plain" id="download-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            {{ __('badge_card.download') }}
        </button>
        <button type="button" class="btn btn-plain" id="copy-btn" data-copied="{{ __('badge_card.copied') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            <span>{{ __('badge_card.copy') }}</span>
        </button>
        <a class="btn btn-plain" style="grid-column: 1 / -1" target="_blank" rel="noopener"
           href="https://wa.me/?text={{ rawurlencode($shareText.' '.$shareUrl) }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-12.5 7.4L3 21l2.1-5.4A8.4 8.4 0 1 1 21 11.5z"/></svg>
            {{ __('badge_card.whatsapp') }}
        </a>
    </div>

    <a class="cta" href="{{ $card['invite_url'] }}">{{ __('badge_card.cta') }}</a>
</main>

<script>
(() => {
    const CARD = @json($script);
    const W = 1080, H = 1350, FONT = '"IBM Plex Sans Arabic", system-ui, sans-serif';

    function loadImage(src) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => resolve(img);
            img.onerror = reject;
            img.src = src;
        });
    }

    function roundedRect(ctx, x, y, w, h, r) {
        ctx.beginPath();
        ctx.roundRect(x, y, w, h, r);
        ctx.fill();
    }

    function text(ctx, value, y, size, weight, color) {
        if (!value) return;
        ctx.font = `${weight} ${size}px ${FONT}`;
        ctx.fillStyle = color;
        ctx.fillText(value, W / 2, y, W - 200);
    }

    function drawFrame(ctx, badge) {
        ctx.fillStyle = '#f5f5f7';
        ctx.fillRect(0, 0, W, H);
        ctx.fillStyle = '#ffffff';
        roundedRect(ctx, 60, 60, W - 120, H - 120, 56);
        ctx.save();
        ctx.beginPath();
        ctx.roundRect(60, 60, W - 120, 560, [56, 56, 0, 0]);
        ctx.clip();
        ctx.fillStyle = CARD.color + '24';
        ctx.fillRect(60, 60, W - 120, 560);
        ctx.restore();
        ctx.drawImage(badge, (W - 400) / 2, 140, 400, 400);
    }

    function drawText(ctx) {
        ctx.textAlign = 'center';
        ctx.direction = document.documentElement.dir || 'ltr';
        text(ctx, CARD.headline, 730, 84, 700, '#1d1d1f');
        text(ctx, CARD.name, 820, 52, 700, '#1d1d1f');
        text(ctx, CARD.since, 885, 36, 400, '#6e6e73');
        text(ctx, CARD.thanks, 990, 36, 400, '#6e6e73');
        ctx.fillStyle = 'rgba(0,0,0,0.06)';
        ctx.fillRect(160, 1080, W - 320, 2);
        text(ctx, CARD.brand, 1165, 44, 700, '#1d1d1f');
        ctx.direction = 'ltr';
        text(ctx, CARD.invite, 1220, 30, 400, '#6e6e73');
    }

    async function renderCard() {
        await document.fonts.load(`700 40px ${FONT}`).catch(() => null);
        const badge = await loadImage(CARD.badge);
        const canvas = document.createElement('canvas');
        canvas.width = W;
        canvas.height = H;
        const ctx = canvas.getContext('2d');
        drawFrame(ctx, badge);
        drawText(ctx);
        return new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
    }

    function saveBlob(blob) {
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = CARD.fileName;
        link.click();
        setTimeout(() => URL.revokeObjectURL(link.href), 1000);
    }

    async function shareCard() {
        const blob = await renderCard();
        const file = new File([blob], CARD.fileName, { type: 'image/png' });
        const data = { files: [file], text: `${CARD.shareText} ${CARD.shareUrl}` };
        if (navigator.canShare && navigator.canShare(data)) return navigator.share(data).catch(() => null);
        if (navigator.share) return navigator.share({ text: data.text, url: CARD.shareUrl }).catch(() => null);
        saveBlob(blob);
    }

    async function copyLink(button) {
        try { await navigator.clipboard.writeText(CARD.shareUrl); } catch (e) { window.prompt('', CARD.shareUrl); return; }
        const label = button.querySelector('span');
        const original = label.textContent;
        label.textContent = button.dataset.copied;
        setTimeout(() => { label.textContent = original; }, 2000);
    }

    function guard(task) {
        return () => task().catch((error) => console.error('[badge-card]', error));
    }

    document.getElementById('share-btn').addEventListener('click', guard(shareCard));
    document.getElementById('download-btn').addEventListener('click', guard(async () => saveBlob(await renderCard())));
    const copyBtn = document.getElementById('copy-btn');
    copyBtn.addEventListener('click', guard(() => copyLink(copyBtn)));
})();
</script>
</body>
</html>

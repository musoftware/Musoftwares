@php
    $f = $copy['form'];
    $ar = app()->getLocale() === 'ar';
    $sample = $ar
        ? ['title' => 'تصميم مواقع وأنظمة للشركات الصغيرة | أكمي', 'desc' => 'مواقع ومتاجر إلكترونية وأنظمة إدارة مصممة لشركتك. أسعار واضحة وتسليم سريع ودعم بعد الإطلاق.', 'site' => 'أكمي']
        : ['title' => 'Custom Software & Websites for Small Businesses | Acme', 'desc' => 'Websites, online stores and business systems built for your company. Clear prices, fast delivery and support after launch.', 'site' => 'Acme'];
@endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <form id="mt-form" class="space-y-4" onsubmit="return false">
        <div>
            <label class="ft-label" for="mt-title">{{ $f['page_title'] }}</label>
            <input id="mt-title" class="ft-input" dir="auto" value="{{ $sample['title'] }}">
            <p class="mt-1 text-xs" id="mt-title-count"></p>
        </div>
        <div>
            <label class="ft-label" for="mt-desc">{{ $f['page_desc'] }}</label>
            <textarea id="mt-desc" class="ft-input" rows="3" dir="auto">{{ $sample['desc'] }}</textarea>
            <p class="mt-1 text-xs" id="mt-desc-count"></p>
        </div>
        <div><label class="ft-label" for="mt-url">{{ $f['url'] }}</label><input id="mt-url" class="ft-input" type="url" dir="ltr" value="https://www.example.com/services"></div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="ft-label" for="mt-site">{{ $f['site_name'] }}</label><input id="mt-site" class="ft-input" dir="auto" value="{{ $sample['site'] }}"></div>
            <div><label class="ft-label" for="mt-image">{{ $f['image'] }}</label><input id="mt-image" class="ft-input" type="url" dir="ltr" placeholder="https://"></div>
        </div>
    </form>

    <div class="space-y-5">
        <div>
            <span class="ft-label">{{ $f['preview'] }}</span>
            <div class="rounded-2xl border border-black/10 bg-white p-4" dir="auto" style="font-family: Arial, sans-serif;">
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-[#f1f3f4] text-xs font-bold text-[#5f6368]" id="mt-fav">A</span>
                    <div class="min-w-0 leading-tight"><p class="text-[13px] text-[#202124] truncate" id="mt-pv-site"></p><p class="text-[12px] text-[#4d5156] truncate" dir="ltr" id="mt-pv-url"></p></div>
                </div>
                <p class="mt-2 text-[19px] leading-snug text-[#1a0dab] line-clamp-2" id="mt-pv-title"></p>
                <p class="mt-1 text-[13px] leading-relaxed text-[#4d5156] line-clamp-2" id="mt-pv-desc"></p>
            </div>
        </div>
        <div>
            <span class="ft-label">{{ $f['tags'] }}</span>
            <textarea id="mt-code" class="ft-input font-mono text-xs" rows="9" dir="ltr" readonly></textarea>
            <button type="button" id="mt-copy" class="ft-btn mt-3">{{ $f['copy'] }}</button>
        </div>
    </div>
</div>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        const T = @json($f);
        const esc = s => String(s || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        const cut = (s, n) => s.length > n ? s.slice(0, n - 1).trimEnd() + '…' : s;

        function count(el, value, limit) {
            el.textContent = T.chars.replace(':count', value.length) + (value.length > limit ? ' · ' + T.too_long : '');
            el.className = 'mt-1 text-xs ' + (value.length > limit ? 'text-red-600 font-semibold' : 'text-[#1d1d1f]/50');
        }

        function render() {
            const title = $('mt-title').value.trim(), desc = $('mt-desc').value.trim(), url = $('mt-url').value.trim(), site = $('mt-site').value.trim(), image = $('mt-image').value.trim();
            count($('mt-title-count'), title, 60);
            count($('mt-desc-count'), desc, 155);
            let crumbs = url;
            try { const u = new URL(url); crumbs = u.origin + u.pathname.replace(/\/$/, '').split('/').filter(Boolean).map(p => ' › ' + p).join(''); } catch (e) {}
            $('mt-pv-site').textContent = site; $('mt-fav').textContent = (site || 'A').charAt(0).toUpperCase();
            $('mt-pv-url').textContent = crumbs;
            $('mt-pv-title').textContent = cut(title, 60);
            $('mt-pv-desc').textContent = cut(desc, 158);
            $('mt-code').value = [
                `<title>${esc(title)}</title>`,
                `<meta name="description" content="${esc(desc)}">`,
                url && `<link rel="canonical" href="${esc(url)}">`,
                `<meta property="og:type" content="website">`,
                `<meta property="og:title" content="${esc(title)}">`,
                `<meta property="og:description" content="${esc(desc)}">`,
                url && `<meta property="og:url" content="${esc(url)}">`,
                site && `<meta property="og:site_name" content="${esc(site)}">`,
                image && `<meta property="og:image" content="${esc(image)}">`,
                `<meta name="twitter:card" content="${image ? 'summary_large_image' : 'summary'}">`,
                `<meta name="twitter:title" content="${esc(title)}">`,
                `<meta name="twitter:description" content="${esc(desc)}">`,
                image && `<meta name="twitter:image" content="${esc(image)}">`,
            ].filter(Boolean).join('\n');
        }

        $('mt-form').addEventListener('input', render);
        $('mt-copy').addEventListener('click', e => FT.copy($('mt-code').value, e.currentTarget));
        render();
    });
</script>

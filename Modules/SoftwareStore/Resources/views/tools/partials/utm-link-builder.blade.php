@php $f = $copy['form']; @endphp
<div class="space-y-4">
    <form id="utm-form" class="space-y-3" onsubmit="return false">
        <div><label class="ft-label" for="utm-url">{{ $f['url'] }}</label><input id="utm-url" class="ft-input" type="url" dir="ltr" placeholder="https://example.com/offer"></div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div><label class="ft-label" for="utm-source">{{ $f['source'] }}</label><input id="utm-source" class="ft-input" dir="ltr" placeholder="facebook"><p class="mt-1 text-xs text-[#1d1d1f]/50">{{ $f['source_hint'] }}</p></div>
            <div><label class="ft-label" for="utm-medium">{{ $f['medium'] }}</label><input id="utm-medium" class="ft-input" dir="ltr" placeholder="cpc"><p class="mt-1 text-xs text-[#1d1d1f]/50">{{ $f['medium_hint'] }}</p></div>
            <div><label class="ft-label" for="utm-campaign">{{ $f['campaign'] }}</label><input id="utm-campaign" class="ft-input" dir="ltr" placeholder="black_friday"><p class="mt-1 text-xs text-[#1d1d1f]/50">{{ $f['campaign_hint'] }}</p></div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div><label class="ft-label" for="utm-term">{{ $f['term'] }}</label><input id="utm-term" class="ft-input" dir="ltr"></div>
            <div><label class="ft-label" for="utm-content">{{ $f['content'] }}</label><input id="utm-content" class="ft-input" dir="ltr"></div>
        </div>
    </form>
    <p id="utm-error" class="hidden text-sm text-red-600">{{ $f['invalid'] }}</p>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
        <div class="md:col-span-2">
            <span class="ft-label">{{ $f['result'] }}</span>
            <div class="flex gap-2"><textarea id="utm-result" class="ft-input font-mono text-sm" rows="3" dir="ltr" readonly></textarea></div>
            <button type="button" id="utm-copy" class="ft-btn mt-3">{{ __('free_tools.ui.copy') }}</button>
        </div>
        <div class="flex flex-col items-center gap-2">
            <canvas id="utm-qr" class="w-40 h-40 rounded-xl border border-black/5" aria-label="QR"></canvas>
            <button type="button" id="utm-download" class="text-sm font-semibold text-[#0071e3]">{{ __('free_tools.ui.download_png') }}</button>
        </div>
    </div>
</div>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        // lowercase, spaces to underscores: Analytics treats Facebook and facebook as different sources
        const clean = v => v.trim().toLowerCase().replace(/\s+/g, '_');

        function build() {
            const raw = $('utm-url').value.trim();
            let url;
            try { url = new URL(raw); } catch (e) { url = null; }
            $('utm-error').classList.toggle('hidden', !raw || !!url);
            if (!url) { $('utm-result').value = ''; FT.qr($('utm-qr'), ' ', 200); return; }
            [['utm_source', 'utm-source'], ['utm_medium', 'utm-medium'], ['utm_campaign', 'utm-campaign'], ['utm_term', 'utm-term'], ['utm_content', 'utm-content']].forEach(([key, id]) => {
                const value = clean($(id).value);
                value ? url.searchParams.set(key, value) : url.searchParams.delete(key);
            });
            $('utm-result').value = url.toString();
            FT.qr($('utm-qr'), url.toString(), 200);
        }

        $('utm-form').addEventListener('input', build);
        $('utm-copy').addEventListener('click', e => $('utm-result').value && FT.copy($('utm-result').value, e.currentTarget));
        $('utm-download').addEventListener('click', () => $('utm-qr').toBlob(blob => FT.download('utm-qr.png', blob)));
        build();
    });
</script>

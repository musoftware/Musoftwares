@php $f = $copy['form']; @endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <form id="es-form" class="space-y-3" onsubmit="return false">
        <div class="grid grid-cols-2 gap-3">
            <div><label class="ft-label" for="es-name">{{ $f['name'] }}</label><input id="es-name" class="ft-input" dir="auto" value="Alex Morgan"></div>
            <div><label class="ft-label" for="es-job">{{ $f['job'] }}</label><input id="es-job" class="ft-input" dir="auto" value="Marketing Manager"></div>
        </div>
        <div><label class="ft-label" for="es-company">{{ $f['company'] }}</label><input id="es-company" class="ft-input" dir="auto" value="Acme Inc."></div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="ft-label" for="es-phone">{{ $f['phone'] }}</label><input id="es-phone" class="ft-input" type="tel" dir="ltr" value="+1 555 010 2030"></div>
            <div><label class="ft-label" for="es-email">{{ $f['email'] }}</label><input id="es-email" class="ft-input" type="email" dir="ltr" value="alex@acme.com"></div>
        </div>
        <div><label class="ft-label" for="es-web">{{ $f['website'] }}</label><input id="es-web" class="ft-input" dir="ltr" value="acme.com"></div>
        <div><label class="ft-label" for="es-address">{{ $f['address'] }}</label><input id="es-address" class="ft-input" dir="auto"></div>
        <div>
            <label class="ft-label" for="es-photo">{{ $f['photo'] }}</label><input id="es-photo" class="ft-input" type="url" dir="ltr" placeholder="https://">
            <p class="mt-1 text-xs text-[#1d1d1f]/50">{{ $f['photo_hint'] }}</p>
        </div>
        <div class="flex items-center gap-3">
            <label class="ft-label !mb-0" for="es-color">{{ $f['color'] }}</label>
            <input id="es-color" type="color" value="#0071e3" class="h-10 w-14 rounded-lg border border-black/10 p-1">
        </div>
    </form>

    <div class="space-y-4">
        <span class="ft-label">{{ $f['preview'] }}</span>
        <div id="es-preview" class="rounded-2xl border border-black/10 bg-white p-5 overflow-x-auto" dir="ltr"></div>
        <div class="flex flex-wrap gap-2">
            <button type="button" id="es-copy" class="ft-btn"><i class="ri-file-copy-line" aria-hidden="true"></i>{{ $f['copy'] }}</button>
            <button type="button" id="es-copy-html" class="ft-btn ft-btn-light">{{ $f['copy_html'] }}</button>
        </div>
    </div>
</div>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        const esc = s => String(s || '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const href = u => /^https?:\/\//i.test(u) ? u : 'https://' + u;

        // Table layout with inline styles only: the format email clients keep.
        function html() {
            const c = $('es-color').value, v = id => $(id).value.trim();
            const photo = /^https:\/\//i.test(v('es-photo')) ? `<td style="padding-right:14px;vertical-align:top;"><img src="${esc(v('es-photo'))}" width="72" height="72" style="border-radius:50%;display:block;" alt=""></td>` : '';
            return `<table cellpadding="0" cellspacing="0" style="font-family:Arial,Helvetica,sans-serif;color:#1d1d1f;"><tr>${photo}<td style="vertical-align:top;border-left:3px solid ${c};padding-left:12px;"><table cellpadding="0" cellspacing="0">`
                + `<tr><td style="font-size:16px;font-weight:bold;color:#1d1d1f;">${esc(v('es-name'))}</td></tr>`
                + (v('es-job') || v('es-company') ? `<tr><td style="font-size:13px;color:${c};padding-bottom:6px;">${esc([v('es-job'), v('es-company')].filter(Boolean).join(' | '))}</td></tr>` : '')
                + (v('es-phone') ? `<tr><td style="padding:1px 0;font-size:13px;"><a href="tel:${esc(v('es-phone').replace(/[^+\d]/g, ''))}" style="color:#555;text-decoration:none;">${esc(v('es-phone'))}</a></td></tr>` : '')
                + (v('es-email') ? `<tr><td style="padding:1px 0;font-size:13px;"><a href="mailto:${esc(v('es-email'))}" style="color:#555;text-decoration:none;">${esc(v('es-email'))}</a></td></tr>` : '')
                + (v('es-web') ? `<tr><td style="padding:1px 0;font-size:13px;"><a href="${esc(href(v('es-web')))}" style="color:${c};text-decoration:none;font-weight:bold;">${esc(v('es-web').replace(/^https?:\/\//i, ''))}</a></td></tr>` : '')
                + (v('es-address') ? `<tr><td style="padding:1px 0;font-size:12px;color:#888;">${esc(v('es-address'))}</td></tr>` : '')
                + `</table></td></tr></table>`;
        }

        function render() { $('es-preview').innerHTML = html(); }

        async function copyRich(button) {
            const code = html();
            try {
                await navigator.clipboard.write([new ClipboardItem({ 'text/html': new Blob([code], { type: 'text/html' }), 'text/plain': new Blob([$('es-preview').innerText], { type: 'text/plain' }) })]);
                const old = button.textContent; button.textContent = @json(__('free_tools.ui.copied')); setTimeout(() => button.textContent = old, 1500);
            } catch (e) {
                // older browsers: select the rendered preview and copy it
                const range = document.createRange(); range.selectNodeContents($('es-preview'));
                const sel = window.getSelection(); sel.removeAllRanges(); sel.addRange(range); document.execCommand('copy'); sel.removeAllRanges();
            }
        }

        $('es-form').addEventListener('input', render);
        $('es-copy').addEventListener('click', e => copyRich(e.currentTarget));
        $('es-copy-html').addEventListener('click', e => FT.copy(html(), e.currentTarget));
        render();
    });
</script>

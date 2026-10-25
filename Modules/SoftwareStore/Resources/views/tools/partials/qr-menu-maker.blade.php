@php $f = $copy['form']; @endphp
<div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
    <form id="qm-form" class="lg:col-span-3 space-y-5" onsubmit="return false">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2"><label class="ft-label" for="qm-name">{{ $f['name'] }} *</label><input id="qm-name" class="ft-input" maxlength="80" required></div>
            <div class="sm:col-span-2"><label class="ft-label" for="qm-tagline">{{ $f['tagline'] }}</label><input id="qm-tagline" class="ft-input" maxlength="140"></div>
            <div><label class="ft-label" for="qm-phone">{{ $f['phone'] }}</label><input id="qm-phone" class="ft-input" type="tel" dir="ltr" maxlength="20"></div>
            <div class="grid grid-cols-3 gap-3">
                <div><label class="ft-label" for="qm-currency">{{ __('free_tools.ui.currency') }}</label>
                    <select id="qm-currency" class="ft-input !px-2">@foreach (['EGP', 'SAR', 'AED', 'KWD', 'QAR', 'USD'] as $c)<option>{{ $c }}</option>@endforeach</select></div>
                <div><label class="ft-label" for="qm-lang">{{ $f['menu_lang'] }}</label>
                    <select id="qm-lang" class="ft-input !px-2">@foreach ($f['langs'] as $k => $label)<option value="{{ $k }}" @selected($k === app()->getLocale())>{{ $label }}</option>@endforeach</select></div>
                <div><label class="ft-label" for="qm-color">{{ $f['color'] }}</label><input id="qm-color" type="color" value="#b45309" class="ft-input !p-1 h-[46px]"></div>
            </div>
        </div>

        <div id="qm-sections" class="space-y-4"></div>
        <button type="button" id="qm-add-section" class="ft-btn ft-btn-light"><i class="ri-add-line" aria-hidden="true"></i>{{ $f['add_section'] }}</button>

        <div class="flex flex-wrap items-center gap-3 pt-2">
            <button type="button" id="qm-save" class="ft-btn"><i class="ri-save-3-line" aria-hidden="true"></i><span>{{ $f['save'] }}</span></button>
            <p id="qm-status" class="text-sm" role="status"></p>
        </div>
    </form>

    <aside id="qm-result" class="lg:col-span-2 hidden">
        <div class="lg:sticky lg:top-24 space-y-4 rounded-[20px] bg-[#f5f5f7] p-5">
            <canvas id="qm-qr" class="mx-auto w-56 h-56 rounded-xl bg-white" aria-label="QR"></canvas>
            <button type="button" id="qm-download" class="ft-btn w-full"><i class="ri-download-2-line" aria-hidden="true"></i>{{ __('free_tools.ui.download_png') }}</button>
            <div>
                <span class="ft-label">{{ $f['menu_link'] }}</span>
                <div class="flex gap-2"><input id="qm-link" class="ft-input text-sm font-mono" dir="ltr" readonly><button type="button" class="ft-btn ft-btn-light shrink-0" data-copy="qm-link">{{ __('free_tools.ui.copy') }}</button></div>
                <a id="qm-view" href="#" target="_blank" rel="noopener" class="mt-2 inline-block text-sm font-semibold text-[#0071e3]">{{ $f['view'] }}</a>
            </div>
            <div>
                <span class="ft-label">{{ $f['edit_link'] }}</span>
                <div class="flex gap-2"><input id="qm-edit" class="ft-input text-sm font-mono" dir="ltr" readonly><button type="button" class="ft-btn ft-btn-light shrink-0" data-copy="qm-edit">{{ __('free_tools.ui.copy') }}</button></div>
                <p class="mt-2 text-xs text-amber-700">{{ $f['edit_warning'] }}</p>
            </div>
        </div>
    </aside>
</div>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        const T = @json($f);
        const DRAFT = 'musoftware_qr_menu_draft_v1';
        const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) };
        let sections = [{ title: '', items: [{ name: '', price: '', note: '' }] }];
        let saved = null; // { code, token }

        const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

        function render() {
            $('qm-sections').innerHTML = sections.map((section, s) => `
                <fieldset class="rounded-[18px] border border-black/10 p-4 space-y-3" data-s="${s}">
                    <div class="flex gap-2 items-end">
                        <div class="flex-1"><label class="ft-label">${esc(T.section)} ${s + 1}</label>
                            <input class="ft-input font-semibold" data-field="title" maxlength="60" placeholder="${esc(T.section_ph)}" value="${esc(section.title)}"></div>
                        <button type="button" class="ft-btn ft-btn-light !px-3" data-remove-section aria-label="{{ __('free_tools.ui.remove') }}"><i class="ri-delete-bin-line"></i></button>
                    </div>
                    ${section.items.map((item, i) => `
                        <div class="grid grid-cols-12 gap-2" data-i="${i}">
                            <input class="ft-input col-span-8 sm:col-span-5" data-item="name" maxlength="80" placeholder="${esc(T.item)}" value="${esc(item.name)}">
                            <input class="ft-input col-span-3 sm:col-span-2" data-item="price" inputmode="decimal" dir="ltr" placeholder="${esc(T.price)}" value="${esc(item.price)}">
                            <input class="ft-input col-span-11 sm:col-span-4" data-item="note" maxlength="140" placeholder="${esc(T.note)}" value="${esc(item.note)}">
                            <button type="button" class="col-span-1 text-red-500 text-lg" data-remove-item aria-label="{{ __('free_tools.ui.remove') }}"><i class="ri-close-line"></i></button>
                        </div>`).join('')}
                    <button type="button" class="text-sm font-semibold text-[#0071e3]" data-add-item><i class="ri-add-line"></i> ${esc(T.add_item)}</button>
                </fieldset>`).join('');

            $('qm-sections').querySelectorAll('[data-s]').forEach(box => {
                const section = sections[box.dataset.s];
                box.querySelector('[data-field="title"]').addEventListener('input', e => { section.title = e.target.value; draft(); });
                box.querySelectorAll('[data-i]').forEach(row => {
                    const item = section.items[row.dataset.i];
                    row.querySelectorAll('[data-item]').forEach(input => input.addEventListener('input', () => { item[input.dataset.item] = input.value; draft(); }));
                    row.querySelector('[data-remove-item]').addEventListener('click', () => { section.items.splice(row.dataset.i, 1); if (!section.items.length) section.items.push({ name: '', price: '', note: '' }); render(); });
                });
                box.querySelector('[data-add-item]').addEventListener('click', () => { section.items.push({ name: '', price: '', note: '' }); render(); });
                box.querySelector('[data-remove-section]').addEventListener('click', () => { sections.splice(box.dataset.s, 1); if (!sections.length) sections.push({ title: '', items: [{ name: '', price: '', note: '' }] }); render(); });
            });
            draft();
        }

        function payload() {
            return {
                name: $('qm-name').value.trim(), tagline: $('qm-tagline').value.trim(), phone: $('qm-phone').value.replace(/[^0-9+ ]/g, ''),
                currency: $('qm-currency').value, lang: $('qm-lang').value, color: $('qm-color').value,
                sections: sections.map(s => ({ title: s.title.trim(), items: s.items.filter(i => i.name.trim()).map(i => ({ name: i.name.trim(), price: i.price === '' ? null : FT.parse(i.price), note: i.note.trim() || null })) }))
                    .filter(s => s.title && s.items.length),
            };
        }

        function fill(data) {
            $('qm-name').value = data.name || ''; $('qm-tagline').value = data.tagline || ''; $('qm-phone').value = data.phone || '';
            $('qm-currency').value = data.currency || 'EGP'; $('qm-lang').value = data.lang || 'ar'; $('qm-color').value = data.color || '#b45309';
            sections = (data.sections || []).map(s => ({ title: s.title, items: s.items.map(i => ({ name: i.name, price: i.price ?? '', note: i.note ?? '' })) }));
            if (!sections.length) sections = [{ title: '', items: [{ name: '', price: '', note: '' }] }];
            render();
        }

        function draft() {
            if (saved) return;
            try { localStorage.setItem(DRAFT, JSON.stringify({ ...payload(), sections })); } catch (e) {}
        }

        function showResult(url) {
            const edit = location.origin + location.pathname + location.search + '#edit=' + saved.code + '.' + saved.token;
            history.replaceState(null, '', '#edit=' + saved.code + '.' + saved.token);
            $('qm-link').value = url; $('qm-view').href = url; $('qm-edit').value = edit;
            FT.qr($('qm-qr'), url, 320);
            $('qm-result').classList.remove('hidden');
            $('qm-save').querySelector('span').textContent = T.update;
        }

        async function save() {
            const body = payload();
            if (!body.name || !body.sections.length) { $('qm-status').textContent = T.error; $('qm-status').className = 'text-sm text-red-600'; return; }
            $('qm-status').textContent = T.saving; $('qm-status').className = 'text-sm text-[#1d1d1f]/60';
            const url = saved ? @json(url('/services/tools/qr-menu')) + '/' + saved.code : @json(route('services.tools.menu.store'));
            const response = await fetch(url, { method: saved ? 'PUT' : 'POST', headers: saved ? { ...headers, 'X-Menu-Token': saved.token } : headers, body: JSON.stringify(body), credentials: 'same-origin' });
            if (!response.ok) { $('qm-status').textContent = response.status === 429 ? T.too_many : T.error; $('qm-status').className = 'text-sm text-red-600'; return; }
            const json = await response.json();
            if (!saved) { saved = { code: json.code, token: json.token }; try { localStorage.removeItem(DRAFT); } catch (e) {} }
            $('qm-status').textContent = T.saved; $('qm-status').className = 'text-sm text-emerald-700';
            showResult(json.url);
        }

        async function start() {
            const match = location.hash.match(/^#edit=([a-z0-9]{8})\.([A-Za-z0-9]{40})$/);
            if (match) {
                const response = await fetch(@json(url('/services/tools/qr-menu')) + '/' + match[1] + '/data', { headers: { 'Accept': 'application/json', 'X-Menu-Token': match[2] } });
                if (response.ok) { const json = await response.json(); saved = { code: match[1], token: match[2] }; fill(json.data); showResult(json.url); return; }
            }
            let data = null;
            try { data = JSON.parse(localStorage.getItem(DRAFT)); } catch (e) {}
            data ? fill(data) : render();
        }

        ['qm-name', 'qm-tagline', 'qm-phone', 'qm-currency', 'qm-lang', 'qm-color'].forEach(id => $(id).addEventListener('input', draft));
        $('qm-add-section').addEventListener('click', () => { sections.push({ title: '', items: [{ name: '', price: '', note: '' }] }); render(); });
        $('qm-save').addEventListener('click', save);
        $('qm-download').addEventListener('click', () => $('qm-qr').toBlob(blob => FT.download('menu-qr.png', blob)));
        document.querySelectorAll('[data-copy]').forEach(b => b.addEventListener('click', e => FT.copy($(b.dataset.copy).value, e.currentTarget)));
        start();
    });
</script>

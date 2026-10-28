@php
    use Modules\SoftwareStore\Services\QrMenuKit;

    $f = $copy['form'];
    $badgeText = __('free_tools.tools.qr-menu-maker.menu.badges');
    $signedIn = auth()->check();
@endphp
{{-- QR menu maker: signed in, menus belong to the account; as a guest, the menu is edited with a private link. --}}
<div class="space-y-6">
    <div class="rounded-[18px] border border-black/10 bg-[#f5f5f7] p-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-[#1d1d1f]/75">{{ $signedIn ? $f['signed_in_note'] : $f['guest_note'] }}</p>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('services.tools.menu', QrMenuKit::DEMO_CODE) }}{{ app()->getLocale() === 'en' ? '?lang=en' : '' }}" target="_blank" rel="noopener" class="ft-btn ft-btn-light"><i class="ri-eye-line" aria-hidden="true"></i>{{ $f['see_demo'] }}</a>
            <button type="button" id="qm-demo" class="ft-btn ft-btn-light"><i class="ri-magic-line" aria-hidden="true"></i>{{ $f['start_demo'] }}</button>
            @unless ($signedIn)
                <a href="{{ route('login') }}?redirect={{ urlencode(request()->getRequestUri()) }}" class="ft-btn"><i class="ri-user-line" aria-hidden="true"></i>{{ $f['sign_in'] }}</a>
            @endunless
        </div>
    </div>

    @if ($signedIn)
        <div id="qm-mine" class="hidden">
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-bold">{{ $f['your_menus'] }}</h3>
                <button type="button" id="qm-new" class="text-sm font-semibold text-[#0071e3]"><i class="ri-add-line"></i> {{ $f['new_menu'] }}</button>
            </div>
            <div id="qm-mine-list" class="grid grid-cols-1 sm:grid-cols-2 gap-2"></div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
        <form id="qm-form" class="lg:col-span-3 space-y-5" onsubmit="return false">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2"><label class="ft-label" for="qm-name">{{ $f['name'] }} *</label><input id="qm-name" class="ft-input" maxlength="80" required></div>
                <div class="sm:col-span-2"><label class="ft-label" for="qm-tagline">{{ $f['tagline'] }}</label><input id="qm-tagline" class="ft-input" maxlength="140"></div>
                <div><label class="ft-label" for="qm-phone">{{ $f['phone'] }}</label><input id="qm-phone" class="ft-input" type="tel" dir="ltr" maxlength="20"></div>
                <div><label class="ft-label" for="qm-whatsapp">{{ $f['whatsapp'] }}</label><input id="qm-whatsapp" class="ft-input" type="tel" dir="ltr" maxlength="20"></div>
                <div><label class="ft-label" for="qm-address">{{ $f['address'] }}</label><input id="qm-address" class="ft-input" maxlength="140"></div>
                <div><label class="ft-label" for="qm-hours">{{ $f['hours'] }}</label><input id="qm-hours" class="ft-input" maxlength="80"></div>
                <div class="sm:col-span-2 grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div><label class="ft-label" for="qm-currency">{{ __('free_tools.ui.currency') }}</label>
                        <select id="qm-currency" class="ft-input !px-2">@foreach (['EGP', 'SAR', 'AED', 'KWD', 'QAR', 'USD'] as $c)<option>{{ $c }}</option>@endforeach</select></div>
                    <div><label class="ft-label" for="qm-lang">{{ $f['menu_lang'] }}</label>
                        <select id="qm-lang" class="ft-input !px-2">@foreach ($f['langs'] as $k => $label)<option value="{{ $k }}" @selected($k === app()->getLocale())>{{ $label }}</option>@endforeach</select></div>
                    <div><label class="ft-label" for="qm-theme">{{ $f['theme'] }}</label>
                        <select id="qm-theme" class="ft-input !px-2">@foreach ($f['themes'] as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach</select></div>
                    <div><label class="ft-label" for="qm-color">{{ $f['color'] }}</label><input id="qm-color" type="color" value="#c2410c" class="ft-input !p-1 h-[46px]"></div>
                </div>
            </div>

            <fieldset class="rounded-[18px] border border-black/10 p-4">
                <legend class="px-1 text-sm font-bold">{{ $f['photos'] }}</legend>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div data-photo-slot="cover"><span class="ft-label">{{ $f['cover'] }}</span></div>
                    <div data-photo-slot="logo"><span class="ft-label">{{ $f['logo'] }}</span></div>
                </div>
            </fieldset>

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
                <div id="qm-edit-box" class="hidden">
                    <span class="ft-label">{{ $f['edit_link'] }}</span>
                    <div class="flex gap-2"><input id="qm-edit" class="ft-input text-sm font-mono" dir="ltr" readonly><button type="button" class="ft-btn ft-btn-light shrink-0" data-copy="qm-edit">{{ __('free_tools.ui.copy') }}</button></div>
                    <p class="mt-2 text-xs text-amber-700">{{ $f['edit_warning'] }}</p>
                </div>
            </div>
        </aside>
    </div>

    <dialog id="qm-library" class="w-[min(720px,calc(100%-32px))] rounded-[20px] p-0 backdrop:bg-black/50">
        <div class="flex items-center justify-between border-b border-black/10 px-5 py-3">
            <h3 class="font-bold">{{ $f['library_title'] }}</h3>
            <button type="button" data-close-library class="text-2xl leading-none" aria-label="{{ __('free_tools.ui.remove') }}">×</button>
        </div>
        <div id="qm-library-grid" class="grid grid-cols-3 sm:grid-cols-4 gap-2 p-4 max-h-[70vh] overflow-y-auto"></div>
    </dialog>
    <input type="file" id="qm-file" accept="image/jpeg,image/png,image/webp" class="hidden">
</div>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        const T = @json($f);
        const BADGES = @json($badgeText);
        const LIBRARY = @json(QrMenuKit::library(app()->getLocale()));
        const DEMO = @json(QrMenuKit::demo(app()->getLocale()));
        const SIGNED_IN = @json($signedIn);
        const BASE = @json(url('/services/tools/qr-menu'));
        const DRAFT = 'musoftware_qr_menu_draft_v2';
        const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) };
        const blankItem = () => ({ name: '', price: '', note: '', image: null, badges: [], sold_out: false });
        let sections = [{ title: '', items: [blankItem()] }];
        let photos = { cover: null, logo: null };
        let saved = null; // { code, token } — token is null for a menu owned by the signed-in account
        let pick = null;  // where a chosen photo goes

        const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const photoUrl = ref => {
            if (!ref) return null;
            const lib = LIBRARY.find(p => p.ref === ref);
            return lib ? lib.url : (ref.startsWith('up:') ? @json(asset('uploads/qr-menus')) + '/' + ref.slice(3) + '.webp' : null);
        };
        const status = (text, cls) => { $('qm-status').textContent = text; $('qm-status').className = 'text-sm ' + cls; };

        // ── photo pickers ──
        function photoControl(ref, small) {
            const url = photoUrl(ref);
            const size = small ? 'w-14 h-14' : 'w-full h-28';
            return `<div class="flex items-center gap-2 ${small ? '' : 'flex-col items-stretch'}">
                <div class="${size} shrink-0 rounded-xl bg-[#f5f5f7] border border-black/10 bg-cover bg-center grid place-items-center text-[#1d1d1f]/30" ${url ? `style="background-image:url('${url}')"` : ''}>${url ? '' : '<i class="ri-image-line text-xl"></i>'}</div>
                <div class="flex flex-wrap gap-1">
                    <button type="button" class="ft-btn ft-btn-light !h-8 !px-2.5 !text-xs" data-upload><i class="ri-upload-2-line"></i>${small ? '' : esc(T.upload)}</button>
                    <button type="button" class="ft-btn ft-btn-light !h-8 !px-2.5 !text-xs" data-library><i class="ri-gallery-line"></i>${small ? '' : esc(T.from_library)}</button>
                    ${url ? `<button type="button" class="ft-btn ft-btn-light !h-8 !px-2.5 !text-xs text-red-600" data-clear><i class="ri-delete-bin-line"></i></button>` : ''}
                </div></div>`;
        }
        function bindPhoto(box, get, set) {
            box.querySelector('[data-upload]').addEventListener('click', () => { pick = set; $('qm-file').value = ''; $('qm-file').click(); });
            box.querySelector('[data-library]').addEventListener('click', () => { pick = set; $('qm-library').showModal(); });
            box.querySelector('[data-clear]')?.addEventListener('click', () => set(null));
        }
        function renderPhotos() {
            ['cover', 'logo'].forEach(slot => {
                const box = document.querySelector(`[data-photo-slot="${slot}"]`);
                box.querySelector('.qm-photo')?.remove();
                const holder = document.createElement('div'); holder.className = 'qm-photo';
                holder.innerHTML = photoControl(photos[slot], false);
                box.appendChild(holder);
                bindPhoto(holder, () => photos[slot], ref => { photos[slot] = ref; renderPhotos(); draft(); });
            });
        }
        $('qm-library-grid').innerHTML = LIBRARY.map(p => `<button type="button" class="group text-start" data-ref="${esc(p.ref)}"><img src="${esc(p.url)}" alt="" loading="lazy" class="w-full aspect-[4/3] object-cover rounded-xl group-hover:ring-2 ring-[#0071e3]"><span class="block mt-1 text-xs text-[#1d1d1f]/70">${esc(p.label)}</span></button>`).join('');
        $('qm-library-grid').querySelectorAll('[data-ref]').forEach(b => b.addEventListener('click', () => { pick?.(b.dataset.ref); $('qm-library').close(); }));
        document.querySelector('[data-close-library]').addEventListener('click', () => $('qm-library').close());
        $('qm-file').addEventListener('change', async () => {
            const file = $('qm-file').files[0];
            if (!file || !pick) return;
            const target = pick;
            status(T.uploading, 'text-[#1d1d1f]/60');
            const body = new FormData(); body.append('image', file);
            const response = await fetch(BASE + '/image', { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': headers['X-CSRF-TOKEN'] }, body, credentials: 'same-origin' });
            if (!response.ok) { status(T.image_error, 'text-red-600'); return; }
            status('', '');
            target((await response.json()).ref);
        });

        // ── sections and items ──
        function render() {
            $('qm-sections').innerHTML = sections.map((section, s) => `
                <fieldset class="rounded-[18px] border border-black/10 p-4 space-y-3" data-s="${s}">
                    <div class="flex gap-2 items-end">
                        <div class="flex-1"><label class="ft-label">${esc(T.section)} ${s + 1}</label>
                            <input class="ft-input font-semibold" data-field="title" maxlength="60" placeholder="${esc(T.section_ph)}" value="${esc(section.title)}"></div>
                        <button type="button" class="ft-btn ft-btn-light !px-3" data-remove-section aria-label="{{ __('free_tools.ui.remove') }}"><i class="ri-delete-bin-line"></i></button>
                    </div>
                    ${section.items.map((item, i) => `
                        <div class="rounded-xl bg-[#fafafa] border border-black/5 p-3 space-y-2" data-i="${i}">
                            <div class="flex gap-3">
                                <div data-item-photo>${photoControl(item.image, true)}</div>
                                <div class="flex-1 grid grid-cols-12 gap-2">
                                    <input class="ft-input col-span-8" data-item="name" maxlength="80" placeholder="${esc(T.item)}" value="${esc(item.name)}">
                                    <input class="ft-input col-span-3" data-item="price" inputmode="decimal" dir="ltr" placeholder="${esc(T.price)}" value="${esc(item.price)}">
                                    <button type="button" class="col-span-1 text-red-500 text-lg" data-remove-item aria-label="{{ __('free_tools.ui.remove') }}"><i class="ri-close-line"></i></button>
                                    <input class="ft-input col-span-12" data-item="note" maxlength="140" placeholder="${esc(T.note)}" value="${esc(item.note)}">
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5 text-xs">
                                <span class="text-[#1d1d1f]/55">${esc(T.badges_label)}:</span>
                                ${Object.keys(BADGES).map(b => `<button type="button" data-badge="${b}" class="h-7 px-2.5 rounded-full border ${item.badges.includes(b) ? 'bg-[#1d1d1f] text-white border-[#1d1d1f]' : 'border-black/15'}">${esc(BADGES[b])}</button>`).join('')}
                                <label class="ms-auto inline-flex items-center gap-1.5"><input type="checkbox" data-sold ${item.sold_out ? 'checked' : ''}> ${esc(T.sold_out)}</label>
                            </div>
                        </div>`).join('')}
                    <button type="button" class="text-sm font-semibold text-[#0071e3]" data-add-item><i class="ri-add-line"></i> ${esc(T.add_item)}</button>
                </fieldset>`).join('');

            $('qm-sections').querySelectorAll('[data-s]').forEach(box => {
                const section = sections[box.dataset.s];
                box.querySelector('[data-field="title"]').addEventListener('input', e => { section.title = e.target.value; draft(); });
                box.querySelectorAll('[data-i]').forEach(row => {
                    const item = section.items[row.dataset.i];
                    row.querySelectorAll('[data-item]').forEach(input => input.addEventListener('input', () => { item[input.dataset.item] = input.value; draft(); }));
                    bindPhoto(row.querySelector('[data-item-photo]'), () => item.image, ref => { item.image = ref; render(); });
                    row.querySelectorAll('[data-badge]').forEach(b => b.addEventListener('click', () => {
                        item.badges = item.badges.includes(b.dataset.badge) ? item.badges.filter(x => x !== b.dataset.badge) : [...item.badges, b.dataset.badge];
                        render();
                    }));
                    row.querySelector('[data-sold]').addEventListener('change', e => { item.sold_out = e.target.checked; draft(); });
                    row.querySelector('[data-remove-item]').addEventListener('click', () => { section.items.splice(row.dataset.i, 1); if (!section.items.length) section.items.push(blankItem()); render(); });
                });
                box.querySelector('[data-add-item]').addEventListener('click', () => { section.items.push(blankItem()); render(); });
                box.querySelector('[data-remove-section]').addEventListener('click', () => { sections.splice(box.dataset.s, 1); if (!sections.length) sections.push({ title: '', items: [blankItem()] }); render(); });
            });
            draft();
        }

        const fields = ['name', 'tagline', 'phone', 'whatsapp', 'address', 'hours', 'currency', 'lang', 'theme', 'color'];
        function payload() {
            const v = id => $('qm-' + id).value.trim();
            return {
                name: v('name'), tagline: v('tagline'), phone: v('phone').replace(/[^0-9+ ]/g, ''), whatsapp: v('whatsapp').replace(/[^0-9+ ]/g, ''),
                address: v('address'), hours: v('hours'), currency: v('currency'), lang: v('lang'), theme: v('theme'), color: v('color'),
                cover: photos.cover, logo: photos.logo,
                sections: sections.map(s => ({ title: s.title.trim(), items: s.items.filter(i => i.name.trim()).map(i => ({
                    name: i.name.trim(), price: i.price === '' || i.price === null ? null : FT.parse(String(i.price)), note: (i.note || '').trim() || null,
                    image: i.image || null, badges: i.badges, sold_out: !!i.sold_out,
                })) })).filter(s => s.title && s.items.length),
            };
        }
        function fill(data) {
            const defaults = { currency: 'EGP', lang: @json(app()->getLocale() === 'en' ? 'en' : 'ar'), theme: 'light', color: '#c2410c' };
            fields.forEach(k => { $('qm-' + k).value = data[k] ?? defaults[k] ?? ''; });
            photos = { cover: data.cover || null, logo: data.logo || null };
            sections = (data.sections || []).map(s => ({ title: s.title, items: s.items.map(i => ({ ...blankItem(), ...i, price: i.price ?? '', note: i.note ?? '', badges: i.badges || [] })) }));
            if (!sections.length) sections = [{ title: '', items: [blankItem()] }];
            renderPhotos(); render();
        }
        function draft() {
            if (saved) return;
            try { localStorage.setItem(DRAFT, JSON.stringify({ ...payload(), sections })); } catch (e) {}
        }

        function showResult(url) {
            $('qm-link').value = url; $('qm-view').href = url;
            if (saved.token) {
                const edit = location.origin + location.pathname + location.search + '#edit=' + saved.code + '.' + saved.token;
                history.replaceState(null, '', '#edit=' + saved.code + '.' + saved.token);
                $('qm-edit').value = edit; $('qm-edit-box').classList.remove('hidden');
            } else {
                history.replaceState(null, '', '#menu=' + saved.code);
                $('qm-edit-box').classList.add('hidden');
            }
            FT.qr($('qm-qr'), url, 320);
            $('qm-result').classList.remove('hidden');
            $('qm-save').querySelector('span').textContent = T.update;
        }

        async function save() {
            const body = payload();
            if (!body.name || !body.sections.length) { status(T.error, 'text-red-600'); return; }
            status(T.saving, 'text-[#1d1d1f]/60');
            const url = saved ? BASE + '/' + saved.code : @json(route('services.tools.menu.store'));
            const extra = saved?.token ? { 'X-Menu-Token': saved.token } : {};
            const response = await fetch(url, { method: saved ? 'PUT' : 'POST', headers: { ...headers, ...extra }, body: JSON.stringify(body), credentials: 'same-origin' });
            if (!response.ok) { status(response.status === 429 ? T.too_many : T.error, 'text-red-600'); return; }
            const json = await response.json();
            if (!saved) { saved = { code: json.code, token: json.token }; try { localStorage.removeItem(DRAFT); } catch (e) {} }
            status(T.saved, 'text-emerald-700');
            showResult(json.url);
            loadMine();
        }

        async function open(code, token) {
            const response = await fetch(BASE + '/' + code + '/data', { headers: { 'Accept': 'application/json', ...(token ? { 'X-Menu-Token': token } : {}) }, credentials: 'same-origin' });
            if (!response.ok) return false;
            const json = await response.json();
            saved = { code, token: token || null }; fill(json.data); showResult(json.url);
            return true;
        }

        async function loadMine() {
            if (!SIGNED_IN) return;
            const response = await fetch(BASE + '/mine', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) return;
            const menus = await response.json();
            $('qm-mine').classList.toggle('hidden', !menus.length);
            $('qm-mine-list').innerHTML = menus.map(m => `<button type="button" data-code="${esc(m.code)}" class="flex items-center justify-between gap-2 rounded-xl border px-3 py-2 text-start ${saved?.code === m.code ? 'border-[#0071e3] bg-[#0071e3]/5' : 'border-black/10 bg-white'}">
                <span class="font-semibold truncate">${esc(m.name)}</span><span class="text-xs text-[#1d1d1f]/55 shrink-0">${esc(T.views.replace(':count', m.views))}</span></button>`).join('');
            $('qm-mine-list').querySelectorAll('[data-code]').forEach(b => b.addEventListener('click', () => open(b.dataset.code)));
        }

        async function start() {
            const guest = location.hash.match(/^#edit=([a-z0-9]{8})\.([A-Za-z0-9]{40})$/);
            const own = location.hash.match(/^#menu=([a-z0-9]{8})$/);
            if (guest && await open(guest[1], guest[2])) return loadMine();
            if (own && SIGNED_IN && await open(own[1])) return loadMine();
            if (location.hash === '#demo') { fill(DEMO); return loadMine(); }
            let data = null;
            try { data = JSON.parse(localStorage.getItem(DRAFT)); } catch (e) {}
            data ? fill(data) : (renderPhotos(), render());
            loadMine();
        }

        fields.forEach(id => $('qm-' + id).addEventListener('input', draft));
        $('qm-add-section').addEventListener('click', () => { sections.push({ title: '', items: [blankItem()] }); render(); });
        $('qm-demo').addEventListener('click', () => {
            const empty = !$('qm-name').value.trim() && sections.every(s => !s.title && s.items.every(i => !i.name));
            if (empty || confirm(T.demo_confirm)) { saved = null; $('qm-result').classList.add('hidden'); $('qm-save').querySelector('span').textContent = T.save; history.replaceState(null, '', location.pathname + location.search); fill(DEMO); }
        });
        $('qm-new')?.addEventListener('click', () => { saved = null; $('qm-result').classList.add('hidden'); $('qm-save').querySelector('span').textContent = T.save; history.replaceState(null, '', location.pathname + location.search); fill({}); loadMine(); });
        $('qm-save').addEventListener('click', save);
        $('qm-download').addEventListener('click', () => $('qm-qr').toBlob(blob => FT.download('menu-qr.png', blob)));
        document.querySelectorAll('[data-copy]').forEach(b => b.addEventListener('click', e => FT.copy($(b.dataset.copy).value, e.currentTarget)));
        start();
    });
</script>

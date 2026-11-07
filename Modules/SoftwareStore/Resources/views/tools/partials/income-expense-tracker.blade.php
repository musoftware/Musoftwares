@php $f = $copy['form']; @endphp
<style>
    .iet-layout { display: grid; gap: 24px; }
    .iet-panel { background: #161618; color: #f5f5f7; border-radius: 20px; padding: 14px; min-width: 0; }
    .iet-tabs { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 4px; background: #2a2a2d; border-radius: 12px; padding: 4px; }
    .iet-tab { min-height: 40px; border-radius: 9px; font-size: 14px; font-weight: 600; color: #c7c7cc; }
    .iet-tab[aria-pressed="true"] { background: #48484c; color: #fff; }
    .iet-nav { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 8px; }
    .iet-nav-btn { width: 44px; height: 44px; flex: none; border-radius: 12px; font-size: 20px; color: #f5f5f7; }
    .iet-nav-btn:hover { background: #2a2a2d; }
    .iet-period { min-width: 0; text-align: center; font-size: 16px; font-weight: 700; overflow-wrap: anywhere; }
    .iet-stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; margin-top: 8px; }
    .iet-card { background: #232326; border-radius: 14px; padding: 12px; min-width: 0; }
    .iet-card dt { font-size: 13px; color: #c7c7cc; }
    .iet-card dd { margin-top: 4px; font-size: 22px; font-weight: 700; overflow-wrap: anywhere; }
    .iet-card small { font-size: 12px; font-weight: 500; color: #c7c7cc; }
    .iet-in { color: #5fd08f; }
    .iet-out { color: #f0646c; }
    .iet-chart-card { grid-column: 1 / -1; }
    .iet-chart-title { margin-top: 10px; font-size: 12px; color: #c7c7cc; }
    .iet-chart { position: relative; height: 170px; margin-top: 4px; touch-action: pan-y; direction: ltr; }
    .iet-chart svg { display: block; }
    .iet-chart-empty { display: flex; height: 100%; align-items: center; justify-content: center; text-align: center; font-size: 13px; color: #c7c7cc; padding: 0 12px; }
    .iet-tip { position: absolute; top: 0; z-index: 1; width: 164px; pointer-events: none; background: #fff; color: #1d1d1f; border-radius: 10px; padding: 8px 10px; font-size: 12px; line-height: 1.6; box-shadow: 0 4px 16px rgba(0,0,0,.4); }
    .iet-tip b { display: block; }
    .iet-tip span { display: flex; justify-content: space-between; gap: 8px; }
    .iet-add-link { display: flex; width: 100%; margin-top: 10px; }
    .iet-type { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
    .iet-type label { position: relative; display: flex; min-height: 44px; align-items: center; justify-content: center; gap: 6px; border: 1px solid rgba(0,0,0,.12); border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer; }
    .iet-type input { position: absolute; opacity: 0; width: 1px; height: 1px; }
    .iet-type input:focus-visible + span { outline: 2px solid #0071e3; outline-offset: 4px; }
    .iet-type label:has(input[value="in"]:checked) { background: #e7f7ee; border-color: #15803d; color: #166534; }
    .iet-type label:has(input[value="out"]:checked) { background: #fdecec; border-color: #b91c1c; color: #991b1b; }
    .iet-row { display: flex; align-items: center; gap: 8px; padding: 10px 0; border-bottom: 1px solid rgba(0,0,0,.06); }
    .iet-row-text { flex: 1; min-width: 0; }
    .iet-row-note { display: block; font-size: 14px; font-weight: 600; overflow-wrap: anywhere; }
    .iet-row-date { display: block; margin-top: 2px; font-size: 12px; color: rgba(29,29,31,.6); }
    .iet-row-amount { flex: none; max-width: 45%; font-size: 15px; font-weight: 700; overflow-wrap: anywhere; }
    .iet-row-amount.in { color: #166534; }
    .iet-row-amount.out { color: #b91c1c; }
    .iet-row-btn { width: 40px; height: 44px; flex: none; border-radius: 10px; font-size: 18px; color: rgba(29,29,31,.6); }
    .iet-row-btn:hover { background: #f5f5f7; color: #1d1d1f; }
    .iet-btn-white { background: #fff; color: #1d1d1f; }
    .iet-btn-white:hover { background: #e8e8ed; }
    .iet-btn-danger { background: transparent; color: #b91c1c; }
    .iet-btn-danger:hover { background: #fdecec; }
    .iet-currency { width: auto; padding-top: 8px; padding-bottom: 8px; }
    .iet-hide { display: none !important; }
    .iet-flip { display: inline-block; }
    [dir="rtl"] .iet-flip { transform: rotate(180deg); }
    .iet-msg { font-size: 13px; }
    .iet-msg.bad { color: #b91c1c; }
    .iet-msg.good { color: #166534; }
    @media (min-width: 768px) {
        .iet-layout { grid-template-columns: minmax(0, 3fr) minmax(0, 2fr); align-items: start; }
        .iet-add-link { display: none; }
        .iet-wide { grid-column: 1 / -1; }
    }
</style>

<div class="iet-layout">
    <div class="iet-panel">
        <div class="iet-tabs" role="group" aria-label="{{ $f['view_label'] }}">
            @foreach ($f['views'] as $key => $label)
                <button type="button" class="iet-tab" data-view="{{ $key }}" aria-pressed="false">{{ $label }}</button>
            @endforeach
        </div>
        <div class="iet-nav">
            <button type="button" id="iet-prev" class="iet-nav-btn" aria-label="{{ $f['previous'] }}"><i class="ri-arrow-left-s-line iet-flip" aria-hidden="true"></i></button>
            <button type="button" id="iet-today" class="iet-period" aria-live="polite" title="{{ $f['go_today'] }}"></button>
            <button type="button" id="iet-next" class="iet-nav-btn" aria-label="{{ $f['next'] }}"><i class="ri-arrow-right-s-line iet-flip" aria-hidden="true"></i></button>
        </div>
        <dl class="iet-stats">
            <div class="iet-card"><dt>{{ $f['total_in'] }}</dt><dd class="iet-in"><i class="ri-arrow-up-line" aria-hidden="true"></i> <span id="iet-in" dir="ltr">0</span> <small data-currency></small></dd></div>
            <div class="iet-card"><dt>{{ $f['total_out'] }}</dt><dd class="iet-out"><i class="ri-arrow-down-line" aria-hidden="true"></i> <span id="iet-out" dir="ltr">0</span> <small data-currency></small></dd></div>
            <div class="iet-card iet-chart-card">
                <dt>{{ $f['net'] }}</dt>
                <dd><i id="iet-net-icon" aria-hidden="true"></i> <span id="iet-net" dir="ltr">0</span> <small data-currency></small></dd>
                <p id="iet-chart-title" class="iet-chart-title"></p>
                <div id="iet-chart" class="iet-chart"></div>
            </div>
        </dl>
        <button type="button" id="iet-add-link" class="ft-btn iet-add-link"><i class="ri-add-line" aria-hidden="true"></i>{{ $f['add'] }}</button>
    </div>

    <form id="iet-form" class="space-y-4" novalidate>
        <h2 id="iet-form-title" class="text-lg font-bold">{{ $f['add'] }}</h2>
        <div class="iet-type" role="radiogroup" aria-label="{{ $f['type'] }}">
            <label><input type="radio" name="iet-type" value="out" checked><span><i class="ri-arrow-down-line" aria-hidden="true"></i> {{ $f['types']['out'] }}</span></label>
            <label><input type="radio" name="iet-type" value="in"><span><i class="ri-arrow-up-line" aria-hidden="true"></i> {{ $f['types']['in'] }}</span></label>
        </div>
        <div><label class="ft-label" for="iet-amount">{{ $f['amount'] }}</label><input id="iet-amount" class="ft-input text-lg" inputmode="decimal" dir="ltr" autocomplete="off"></div>
        <div><label class="ft-label" for="iet-date">{{ $f['date'] }}</label><input id="iet-date" type="date" class="ft-input" dir="ltr"></div>
        <div><label class="ft-label" for="iet-note">{{ $f['note'] }}</label><input id="iet-note" class="ft-input" maxlength="140" placeholder="{{ $f['note_ph'] }}" list="iet-notes" autocomplete="off"><datalist id="iet-notes"></datalist></div>
        <p id="iet-form-msg" class="iet-msg" role="status"></p>
        <div class="flex flex-wrap gap-2">
            <button type="submit" id="iet-submit" class="ft-btn flex-1"><i class="ri-check-line" aria-hidden="true"></i><span id="iet-submit-text">{{ $f['save'] }}</span></button>
            <button type="button" id="iet-cancel" class="ft-btn ft-btn-light iet-hide">{{ $f['cancel'] }}</button>
        </div>
    </form>

    <div class="iet-wide min-w-0">
        <h2 class="text-lg font-bold">{{ $f['list_title'] }}</h2>
        <div id="iet-list" class="mt-2"></div>
        <button type="button" id="iet-more" class="ft-btn ft-btn-light w-full mt-3 iet-hide">{{ $f['show_more'] }}</button>
    </div>

    <div class="iet-wide min-w-0 rounded-2xl bg-[#f5f5f7] p-4">
        <p class="text-sm font-semibold"><i class="ri-shield-check-line" aria-hidden="true"></i> {{ $f['backup_title'] }}</p>
        <p class="mt-1 text-sm text-[#1d1d1f]/70">{{ $f['backup_hint'] }}</p>
        <div class="mt-3 flex flex-wrap items-center gap-2">
            <button type="button" id="iet-backup" class="ft-btn"><i class="ri-download-2-line" aria-hidden="true"></i>{{ $f['backup'] }}</button>
            <button type="button" id="iet-restore" class="ft-btn iet-btn-white"><i class="ri-upload-2-line" aria-hidden="true"></i>{{ $f['restore'] }}</button>
            <input type="file" id="iet-restore-file" class="hidden" accept=".json,application/json" aria-label="{{ $f['restore'] }}">
            <button type="button" id="iet-csv" class="ft-btn iet-btn-white"><i class="ri-file-excel-2-line" aria-hidden="true"></i>{{ __('free_tools.ui.download_csv') }}</button>
            <label class="text-sm font-semibold" for="iet-currency">{{ __('free_tools.ui.currency') }}</label>
            <select id="iet-currency" class="ft-input iet-currency" title="{{ __('free_tools.ui.currency') }}">
                @foreach (['EGP', 'SAR', 'AED', 'KWD', 'QAR', 'USD', 'EUR'] as $currency)
                    <option>{{ $currency }}</option>
                @endforeach
            </select>
            <button type="button" id="iet-clear" class="ft-btn iet-btn-danger">{{ $f['clear_all'] }}</button>
        </div>
        <p id="iet-data-msg" class="iet-msg mt-2" role="status"></p>
    </div>
</div>

<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        const TEXT = @json($f);
        const LOCALE = @json(app()->getLocale() === 'ar' ? 'ar-EG-u-nu-latn' : 'en-GB');
        const KEY = 'musoftware_money_v1';
        const MILLI = 1000; // amounts are whole thousandths, so totals never pick up rounding errors
        const MAX_MILLI = 999999999 * MILLI;
        const PAGE_SIZE = 100;
        const KEY_LENGTH = { day: 10, month: 7, year: 4 };
        const IN_COLOR = '#5fd08f', OUT_COLOR = '#f0646c';

        const state = { view: 'month', anchor: todayIso(), editing: null, shown: PAGE_SIZE, currency: 'EGP', entries: [] };

        function pad(n) { return String(n).padStart(2, '0'); }
        function toIso(d) { return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`; }
        function todayIso() { return toIso(new Date()); }
        function fromIso(iso) { const [y, m, d] = iso.split('-').map(Number); return new Date(y, m - 1, d || 1); }
        function isRealDate(iso) { return /^\d{4}-\d{2}-\d{2}$/.test(iso) && toIso(fromIso(iso)) === iso; }
        function money(milli) { return FT.number(milli / MILLI, 3); }
        function escapeHtml(text) { const box = document.createElement('div'); box.textContent = text; return box.innerHTML; }
        function format(iso, options) { return new Intl.DateTimeFormat(LOCALE, options).format(fromIso(iso)); }

        /* ---------- storage ---------- */

        function cleanEntry(raw) {
            const valid = raw && (raw.type === 'in' || raw.type === 'out') && Number.isSafeInteger(raw.milli)
                && raw.milli > 0 && raw.milli <= MAX_MILLI && isRealDate(String(raw.date));
            if (!valid) return null;
            const id = /^[a-z0-9]{1,24}$/.test(String(raw.id)) ? String(raw.id) : newId();
            return { id, type: raw.type, milli: raw.milli, date: raw.date, note: String(raw.note ?? '').slice(0, 140) };
        }

        function cleanEntries(list) { return (Array.isArray(list) ? list : []).map(cleanEntry).filter(Boolean); }
        function newId() { return Date.now().toString(36) + Math.random().toString(36).slice(2, 8); }

        function load() {
            let data = null;
            try { data = JSON.parse(localStorage.getItem(KEY)); } catch (e) { console.warn('money tracker: could not read saved data', e); }
            state.entries = cleanEntries(data?.entries);
            state.currency = data?.currency || 'EGP';
        }

        function save() {
            try {
                localStorage.setItem(KEY, JSON.stringify({ v: 1, currency: state.currency, entries: state.entries }));
                return true;
            } catch (e) {
                console.error('money tracker: could not save', e);
                showMessage('iet-data-msg', TEXT.save_failed, 'bad');
                return false;
            }
        }

        /* ---------- numbers ---------- */

        const periodKey = () => state.anchor.slice(0, KEY_LENGTH[state.view]);
        const periodEntries = () => state.entries.filter(e => e.date.startsWith(periodKey()));

        function totals(entries) {
            return entries.reduce((sum, e) => ({ ...sum, [e.type]: sum[e.type] + e.milli }), { in: 0, out: 0 });
        }

        function clampToMonth(d, monthIndex) {
            const last = new Date(d.getFullYear(), monthIndex + 1, 0).getDate();
            return new Date(d.getFullYear(), monthIndex, Math.min(d.getDate(), last));
        }

        const SHIFT = {
            day: (d, step) => new Date(d.getFullYear(), d.getMonth(), d.getDate() + step),
            month: (d, step) => clampToMonth(d, d.getMonth() + step),
            year: (d, step) => clampToMonth(d, d.getMonth() + step * 12),
        };

        /** The time slots on the chart: the week ending on the chosen day, the days of a month, or the months of a year. */
        const SLOTS = {
            day: d => Array.from({ length: 7 }, (_, i) => toIso(new Date(d.getFullYear(), d.getMonth(), d.getDate() - 6 + i))),
            month: d => Array.from({ length: new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate() }, (_, i) => toIso(new Date(d.getFullYear(), d.getMonth(), i + 1))),
            year: d => Array.from({ length: 12 }, (_, i) => `${d.getFullYear()}-${pad(i + 1)}`),
        };

        const PERIOD_FORMAT = {
            day: { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' },
            month: { month: 'long', year: 'numeric' },
            year: { year: 'numeric' },
        };

        function slotLabel(key) {
            return format(key, key.length === 7 ? { month: 'long' } : { day: 'numeric', month: 'short' });
        }

        /** Running net per slot. Slots after today are dropped so the line stops at today. */
        function chartPoints() {
            const slots = SLOTS[state.view](fromIso(state.anchor));
            const length = slots[0].length;
            const today = todayIso().slice(0, length);
            let running = 0;
            const points = slots.filter(key => key <= today).map(key => {
                const slot = totals(state.entries.filter(e => e.date.slice(0, length) === key));
                running += slot.in - slot.out;
                return { key, in: slot.in, out: slot.out, running };
            });
            return { slots, points, hasData: points.some(p => p.in || p.out) };
        }

        /* ---------- chart ---------- */

        function compact(milli) {
            return new Intl.NumberFormat('en-US', { notation: 'compact', maximumFractionDigits: 1 }).format(milli / MILLI);
        }

        function chartScale(slots, points, width, height) {
            const box = { left: 44, right: width - 10, top: 10, bottom: height - 24 };
            const values = points.map(p => p.running);
            const max = Math.max(0, ...values), min = Math.min(0, ...values);
            const range = max - min || 1;
            const stepX = (box.right - box.left) / Math.max(slots.length - 1, 1);
            return {
                box, max, min, stepX,
                x: i => box.left + stepX * i,
                y: value => box.bottom - (value - min) / range * (box.bottom - box.top),
            };
        }

        function axisHtml(scale, slots) {
            const { box, max, min, y } = scale;
            const label = (value, at) => `<text x="${box.left - 6}" y="${at + 4}" text-anchor="end" font-size="11" fill="#c7c7cc">${compact(value)}</text>`;
            const labels = [label(0, y(0))];
            if (max > 0) labels.push(label(max, y(max)));
            if (min < 0) labels.push(label(min, y(min)));
            const xLabel = (key, at, anchor) => `<text x="${at}" y="${box.bottom + 18}" text-anchor="${anchor}" font-size="11" fill="#c7c7cc">${slotLabel(key)}</text>`;
            return `<line x1="${box.left}" x2="${box.right}" y1="${y(0)}" y2="${y(0)}" stroke="#636366" stroke-dasharray="3 4"/>`
                + labels.join('') + xLabel(slots[0], box.left, 'start') + xLabel(slots[slots.length - 1], box.right, 'end');
        }

        function lineHtml(scale, points) {
            const { box, x, y } = scale;
            const zero = y(0), last = points[points.length - 1];
            const path = points.map((p, i) => `${i ? 'L' : 'M'}${x(i)} ${y(p.running)}`).join(' ');
            const stroke = (clip, color) => `<path d="${path}" fill="none" stroke="${color}" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" clip-path="url(#${clip})"/>`;
            return `<defs>
                    <clipPath id="iet-above"><rect x="0" y="0" width="${box.right + 10}" height="${zero}"/></clipPath>
                    <clipPath id="iet-below"><rect x="0" y="${zero}" width="${box.right + 10}" height="${box.bottom + 24}"/></clipPath>
                </defs>${stroke('iet-above', IN_COLOR)}${stroke('iet-below', OUT_COLOR)}
                <circle cx="${x(points.length - 1)}" cy="${y(last.running)}" r="4" fill="${last.running < 0 ? OUT_COLOR : IN_COLOR}" stroke="#232326" stroke-width="2"/>`;
        }

        function showTip(box, scale, points, clientX) {
            const left = clientX - box.getBoundingClientRect().left;
            const index = Math.max(0, Math.min(points.length - 1, Math.round((left - scale.box.left) / (scale.stepX || 1))));
            const point = points[index], at = scale.x(index);
            box.querySelector('[data-cross]').setAttribute('x1', at);
            box.querySelector('[data-cross]').setAttribute('x2', at);
            box.querySelector('[data-cross]').style.display = '';
            const tip = box.querySelector('.iet-tip');
            tip.hidden = false;
            tip.style.left = `${Math.max(0, Math.min(box.clientWidth - 164, at - 82))}px`;
            tip.dir = document.documentElement.dir || 'rtl';
            tip.innerHTML = `<b>${slotLabel(point.key)}</b>`
                + `<span>${TEXT.types.in}<bdi>${money(point.in)}</bdi></span>`
                + `<span>${TEXT.types.out}<bdi>${money(point.out)}</bdi></span>`
                + `<span>${TEXT.net}<bdi>${money(point.running)}</bdi></span>`;
        }

        function hideTip(box) {
            box.querySelector('[data-cross]').style.display = 'none';
            box.querySelector('.iet-tip').hidden = true;
        }

        function renderChart() {
            const box = $('iet-chart');
            const { slots, points, hasData } = chartPoints();
            $('iet-chart-title').textContent = TEXT.chart_titles[state.view];
            if (!hasData) {
                box.innerHTML = `<p class="iet-chart-empty">${TEXT.chart_empty}</p>`;
                return;
            }
            const width = box.clientWidth, height = box.clientHeight;
            const scale = chartScale(slots, points, width, height);
            const summary = `${TEXT.net}: ${money(points[points.length - 1].running)} ${state.currency}`;
            box.innerHTML = `<svg width="${width}" height="${height}" role="img" aria-label="${escapeHtml(summary)}">${axisHtml(scale, slots)}${lineHtml(scale, points)}
                <line data-cross y1="${scale.box.top}" y2="${scale.box.bottom}" stroke="#c7c7cc" style="display:none"/></svg><div class="iet-tip" hidden></div>`;
            box.onpointermove = event => showTip(box, scale, points, event.clientX);
            box.onpointerdown = box.onpointermove;
            box.onpointerleave = () => hideTip(box);
        }

        /* ---------- screen ---------- */

        function renderSummary() {
            const sum = totals(periodEntries());
            const net = sum.in - sum.out;
            $('iet-in').textContent = money(sum.in);
            $('iet-out').textContent = money(sum.out);
            $('iet-net').textContent = money(net);
            $('iet-net-icon').className = net < 0 ? 'ri-arrow-down-line iet-out' : 'ri-arrow-up-line iet-in';
            document.querySelectorAll('[data-currency]').forEach(el => { el.textContent = state.currency; });
        }

        function renderPeriod() {
            document.querySelectorAll('.iet-tab').forEach(tab => tab.setAttribute('aria-pressed', String(tab.dataset.view === state.view)));
            $('iet-today').textContent = format(state.anchor, PERIOD_FORMAT[state.view]);
        }

        function rowHtml(entry) {
            const sign = entry.type === 'in' ? '+' : '-';
            const title = entry.note || TEXT.types[entry.type];
            const date = format(entry.date, { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
            return `<div class="iet-row" data-id="${entry.id}">
                <span class="iet-row-text"><span class="iet-row-note">${escapeHtml(title)}</span><span class="iet-row-date">${date}</span></span>
                <bdi class="iet-row-amount ${entry.type}">${sign}${money(entry.milli)}</bdi>
                <button type="button" class="iet-row-btn" data-edit aria-label="${TEXT.edit}"><i class="ri-pencil-line" aria-hidden="true"></i></button>
                <button type="button" class="iet-row-btn" data-remove aria-label="${@json(__('free_tools.ui.remove'))}"><i class="ri-delete-bin-line" aria-hidden="true"></i></button>
            </div>`;
        }

        function renderList() {
            const entries = periodEntries().sort((a, b) => b.date.localeCompare(a.date));
            $('iet-list').innerHTML = entries.length
                ? entries.slice(0, state.shown).map(rowHtml).join('')
                : `<p class="py-6 text-center text-sm text-[#1d1d1f]/60">${TEXT.list_empty}</p>`;
            $('iet-more').classList.toggle('iet-hide', entries.length <= state.shown);
        }

        function renderNoteSuggestions() {
            const notes = [...new Set(state.entries.map(e => e.note).filter(Boolean))].slice(-50);
            $('iet-notes').innerHTML = notes.map(note => `<option value="${escapeHtml(note).replace(/"/g, '&quot;')}"></option>`).join('');
        }

        function render() {
            renderPeriod();
            renderSummary();
            renderChart();
            renderList();
            renderNoteSuggestions();
        }

        function showMessage(id, text, kind) {
            $(id).textContent = text;
            $(id).className = `iet-msg ${id === 'iet-data-msg' ? 'mt-2 ' : ''}${kind}`;
        }

        /* ---------- form ---------- */

        function resetForm() {
            state.editing = null;
            $('iet-amount').value = '';
            $('iet-note').value = '';
            $('iet-form-title').textContent = TEXT.add;
            $('iet-submit-text').textContent = TEXT.save;
            $('iet-cancel').classList.add('iet-hide');
        }

        function readForm() {
            const milli = Math.round(FT.parse($('iet-amount').value) * MILLI);
            const date = $('iet-date').value;
            if (milli <= 0 || milli > MAX_MILLI) return { error: TEXT.bad_amount, field: 'iet-amount' };
            if (!isRealDate(date)) return { error: TEXT.bad_date, field: 'iet-date' };
            const type = document.querySelector('input[name="iet-type"]:checked').value;
            return { entry: { id: state.editing ?? newId(), type, milli, date, note: $('iet-note').value.trim().slice(0, 140) } };
        }

        function submitForm(event) {
            event.preventDefault();
            const { entry, error, field } = readForm();
            if (error) {
                showMessage('iet-form-msg', error, 'bad');
                $(field).focus();
                return;
            }
            const message = state.editing ? TEXT.updated : TEXT.saved;
            state.entries = [...state.entries.filter(e => e.id !== entry.id), entry];
            state.anchor = entry.date; // the screen follows the entry, so it is always seen after saving
            save();
            resetForm();
            render();
            showMessage('iet-form-msg', message, 'good');
            $('iet-amount').focus();
        }

        function startEdit(id) {
            const entry = state.entries.find(e => e.id === id);
            if (!entry) return;
            state.editing = id;
            document.querySelector(`input[name="iet-type"][value="${entry.type}"]`).checked = true;
            $('iet-amount').value = entry.milli / MILLI;
            $('iet-date').value = entry.date;
            $('iet-note').value = entry.note;
            $('iet-form-title').textContent = TEXT.edit;
            $('iet-submit-text').textContent = TEXT.save_changes;
            $('iet-cancel').classList.remove('iet-hide');
            showMessage('iet-form-msg', '', '');
            $('iet-amount').focus();
        }

        function removeEntry(id) {
            if (!confirm(TEXT.confirm_remove)) return;
            state.entries = state.entries.filter(e => e.id !== id);
            if (state.editing === id) resetForm();
            save();
            render();
        }

        /* ---------- files ---------- */

        function csvCell(value) {
            const text = String(value);
            const safe = /^[=+\-@\t\r]/.test(text) ? `'${text}` : text; // stops Excel reading a note as a formula
            return `"${safe.replace(/"/g, '""')}"`;
        }

        function downloadCsv() {
            const rows = periodEntries().sort((a, b) => a.date.localeCompare(b.date)).map(e => [e.date, TEXT.types[e.type], e.milli / MILLI, csvCell(e.note)].join(','));
            const head = [TEXT.date, TEXT.type, `${TEXT.amount} (${state.currency})`, TEXT.note].map(csvCell).join(',');
            FT.download(`money-${periodKey()}.csv`, '﻿' + [head, ...rows].join('\r\n'), 'text/csv;charset=utf-8');
        }

        function downloadBackup() {
            const data = JSON.stringify({ v: 1, currency: state.currency, entries: state.entries }, null, 1);
            FT.download(`money-backup-${todayIso()}.json`, data, 'application/json');
        }

        /** Adds what the file holds to what is here. Nothing already on this device is removed. */
        async function restoreBackup(file) {
            let incoming = [];
            try {
                incoming = cleanEntries(JSON.parse(await file.text())?.entries);
            } catch (e) {
                console.warn('money tracker: backup file could not be read', e);
            }
            if (!incoming.length) {
                showMessage('iet-data-msg', TEXT.restore_failed, 'bad');
                return;
            }
            const known = new Set(state.entries.map(e => e.id));
            const added = incoming.filter(e => !known.has(e.id));
            state.entries = [...state.entries, ...added];
            save();
            render();
            showMessage('iet-data-msg', TEXT.restored.replace(':count', added.length), 'good');
        }

        function clearAll() {
            if (!confirm(TEXT.confirm_clear)) return;
            state.entries = [];
            resetForm();
            save();
            render();
        }

        /* ---------- wiring ---------- */

        function changePeriod(changes) {
            Object.assign(state, changes, { shown: PAGE_SIZE });
            render();
        }

        function onListClick(event) {
            const id = event.target.closest('[data-id]')?.dataset.id;
            if (event.target.closest('[data-edit]')) startEdit(id);
            if (event.target.closest('[data-remove]')) removeEntry(id);
        }

        document.querySelectorAll('.iet-tab').forEach(tab => tab.addEventListener('click', () => changePeriod({ view: tab.dataset.view })));
        $('iet-prev').addEventListener('click', () => changePeriod({ anchor: toIso(SHIFT[state.view](fromIso(state.anchor), -1)) }));
        $('iet-next').addEventListener('click', () => changePeriod({ anchor: toIso(SHIFT[state.view](fromIso(state.anchor), 1)) }));
        $('iet-today').addEventListener('click', () => changePeriod({ anchor: todayIso() }));
        $('iet-add-link').addEventListener('click', () => $('iet-amount').focus());
        $('iet-form').addEventListener('submit', submitForm);
        $('iet-cancel').addEventListener('click', () => { resetForm(); showMessage('iet-form-msg', '', ''); });
        $('iet-list').addEventListener('click', onListClick);
        $('iet-more').addEventListener('click', () => { state.shown += PAGE_SIZE; renderList(); });
        $('iet-csv').addEventListener('click', downloadCsv);
        $('iet-backup').addEventListener('click', downloadBackup);
        $('iet-restore').addEventListener('click', () => $('iet-restore-file').click());
        $('iet-restore-file').addEventListener('change', event => {
            if (event.target.files[0]) restoreBackup(event.target.files[0]);
            event.target.value = '';
        });
        $('iet-clear').addEventListener('click', clearAll);
        $('iet-currency').addEventListener('change', event => { state.currency = event.target.value; save(); renderSummary(); });
        window.addEventListener('storage', event => { if (event.key === KEY) { load(); render(); } }); // another tab saved
        new ResizeObserver(renderChart).observe($('iet-chart'));

        load();
        $('iet-currency').value = state.currency;
        $('iet-date').value = state.anchor;
        render();
    });
</script>

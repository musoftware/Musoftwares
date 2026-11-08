@php $f = $copy['form']; @endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <div class="space-y-4">
        <div><label class="ft-label" for="dd-from">{{ $f['from'] }}</label><input id="dd-from" type="date" class="ft-input" dir="ltr"></div>
        <div><label class="ft-label" for="dd-to">{{ $f['to'] }}</label><input id="dd-to" type="date" class="ft-input" dir="ltr"></div>
        <div><label class="ft-label" for="dd-weekend">{{ $f['weekend'] }}</label>
            <select id="dd-weekend" class="ft-input">
                <option value="5,6">{{ $f['fri_sat'] }}</option>
                <option value="6,0">{{ $f['sat_sun'] }}</option>
                <option value="5">{{ $f['fri_only'] }}</option>
            </select>
        </div>
        <label class="flex items-center gap-2 text-sm"><input id="dd-include" type="checkbox"> {{ $f['include_end'] }}</label>
    </div>
    <div>
        <div class="ft-stat !bg-[#0071e3]/10"><dt>{{ $f['duration'] }}</dt><dd id="dd-out" class="!text-[#0071e3] !text-[24px]">—</dd></div>
        <dl class="mt-3 grid grid-cols-3 gap-3">
            <div class="ft-stat"><dt>{{ $f['total_days'] }}</dt><dd id="dd-days">—</dd></div>
            <div class="ft-stat"><dt>{{ $f['weeks'] }}</dt><dd id="dd-weeks">—</dd></div>
            <div class="ft-stat"><dt>{{ $f['working_days'] }}</dt><dd id="dd-working">—</dd></div>
        </dl>
        <p id="dd-error" class="mt-3 text-sm font-semibold text-red-600 hidden">{{ $f['error'] }}</p>
    </div>
</div>
<div class="mt-8 pt-6 border-t border-black/5">
    <h2 class="text-lg font-bold">{{ $f['add_title'] }}</h2>
    <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div><label class="ft-label" for="dd-base">{{ $f['base'] }}</label><input id="dd-base" type="date" class="ft-input" dir="ltr"></div>
        <div><label class="ft-label" for="dd-add">{{ $f['add_days'] }}</label><input id="dd-add" class="ft-input" inputmode="numeric" dir="ltr" value="30"></div>
        <div class="ft-stat"><dt>{{ $f['result_date'] }}</dt><dd id="dd-result">—</dd></div>
    </div>
</div>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        const TEXT = @json($f), DAY = 86400000, LOCALE = @json(app()->getLocale() === 'ar' ? 'ar-u-nu-latn-ca-gregory' : 'en-GB');
        const today = new Date().toLocaleDateString('en-CA');
        const put = (id, number) => { $(id).textContent = number === null ? '—' : FT.number(number, 1); };

        function workingDays(from, total) {
            const off = $('dd-weekend').value.split(',').map(Number), start = Date.parse(from + 'T00:00:00Z');
            let count = 0;
            for (let day = 0; day < total; day++) {
                if (!off.includes(new Date(start + day * DAY).getUTCDay())) count++;
            }
            return count;
        }

        function clearDuration(failed) {
            $('dd-out').textContent = '—';
            ['dd-days', 'dd-weeks', 'dd-working'].forEach(id => put(id, null));
            $('dd-error').classList.toggle('hidden', !failed);
        }

        function updateDuration() {
            const from = $('dd-from').value, to = $('dd-to').value, gap = from && to ? FT.dateDiff(from, to) : null;
            if (!gap) return clearDuration(Boolean(from && to));
            $('dd-error').classList.add('hidden');
            const total = gap.totalDays + ($('dd-include').checked ? 1 : 0);
            $('dd-out').textContent = `${gap.years} ${TEXT.years} ${gap.months} ${TEXT.months} ${gap.days} ${TEXT.days}`;
            put('dd-days', total);
            put('dd-weeks', total / 7);
            put('dd-working', workingDays(from, total));
        }

        function updateAdded() {
            const base = Date.parse($('dd-base').value + 'T00:00:00Z');
            if (!Number.isFinite(base)) { $('dd-result').textContent = '—'; return; }
            const date = new Date(base + FT.parse($('dd-add').value) * DAY);
            $('dd-result').textContent = date.toLocaleDateString(LOCALE, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', timeZone: 'UTC' });
        }

        ['dd-from', 'dd-to', 'dd-weekend', 'dd-include'].forEach(id => $(id).addEventListener('input', updateDuration));
        ['dd-base', 'dd-add'].forEach(id => $(id).addEventListener('input', updateAdded));
        $('dd-from').value = $('dd-base').value = today;
        updateAdded();
    });
</script>

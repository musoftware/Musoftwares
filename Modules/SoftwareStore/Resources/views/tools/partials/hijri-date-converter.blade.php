@php $f = $copy['form']; @endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="space-y-4">
        <h2 class="text-lg font-bold">{{ $f['to_hijri'] }}</h2>
        <div><label class="ft-label" for="hj-greg">{{ $f['gregorian_date'] }}</label><input id="hj-greg" type="date" class="ft-input" dir="ltr" value="{{ now()->toDateString() }}"></div>
        <div class="ft-stat"><p class="text-[12px] text-[#1d1d1f]/60">{{ $f['result_hijri'] }}</p><p id="hj-hijri-out" class="mt-1 text-xl font-bold">—</p></div>
    </div>
    <div class="space-y-4">
        <h2 class="text-lg font-bold">{{ $f['to_gregorian'] }}</h2>
        <div class="grid grid-cols-3 gap-2">
            <div><label class="ft-label" for="hj-day">{{ $f['day'] }}</label><input id="hj-day" class="ft-input" inputmode="numeric" dir="ltr"></div>
            <div><label class="ft-label" for="hj-month">{{ $f['month'] }}</label>
                <select id="hj-month" class="ft-input">@foreach ($f['hijri_months'] as $i => $month)<option value="{{ $i + 1 }}">{{ $month }}</option>@endforeach</select>
            </div>
            <div><label class="ft-label" for="hj-year">{{ $f['year'] }}</label><input id="hj-year" class="ft-input" inputmode="numeric" dir="ltr"></div>
        </div>
        <div class="ft-stat"><p class="text-[12px] text-[#1d1d1f]/60">{{ $f['result_gregorian'] }}</p><p id="hj-greg-out" class="mt-1 text-xl font-bold" data-invalid="{{ $f['invalid'] }}">—</p></div>
    </div>
</div>
<p class="mt-4 text-[13px] leading-[1.8] text-[#1d1d1f]/60">{{ $f['note'] }}</p>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        const locale = document.documentElement.lang === 'ar' ? 'ar' : 'en';
        const DAY = 86400000, HIJRI_START = Date.UTC(622, 6, 19), HIJRI_YEAR = 354.36667, HIJRI_MONTH = 29.53;
        const numeric = new Intl.DateTimeFormat('en-u-ca-islamic-umalqura-nu-latn', { day: 'numeric', month: 'numeric', year: 'numeric', timeZone: 'UTC' });
        const hijriText = new Intl.DateTimeFormat(locale + '-u-ca-islamic-umalqura-nu-latn', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' });
        const gregorianText = new Intl.DateTimeFormat(locale + '-u-nu-latn', { day: 'numeric', month: 'long', year: 'numeric', weekday: 'long', timeZone: 'UTC' });

        function hijriParts(time) {
            const parts = Object.fromEntries(numeric.formatToParts(new Date(time)).map(part => [part.type, parseInt(part.value, 10)]));
            return { day: parts.day, month: parts.month, year: parts.year };
        }

        function fromHijri(day, month, year) {
            const guess = HIJRI_START + Math.round((year - 1) * HIJRI_YEAR + (month - 1) * HIJRI_MONTH + day - 1) * DAY;
            for (let shift = -45; shift <= 45; shift++) {
                const time = guess + shift * DAY, parts = hijriParts(time);
                if (parts.day === day && parts.month === month && parts.year === year) return time;
            }
            return null;
        }

        function showHijri() {
            const time = Date.parse($('hj-greg').value + 'T00:00:00Z');
            $('hj-hijri-out').textContent = Number.isFinite(time) ? hijriText.format(new Date(time)) : '—';
        }

        function showGregorian() {
            const day = Math.round(FT.parse($('hj-day').value)), year = Math.round(FT.parse($('hj-year').value)), output = $('hj-greg-out');
            const time = day && year ? fromHijri(day, parseInt($('hj-month').value, 10), year) : null;
            output.textContent = time !== null ? gregorianText.format(new Date(time)) : (day && year ? output.dataset.invalid : '—');
        }

        const today = hijriParts(Date.now());
        $('hj-day').value = today.day; $('hj-month').value = today.month; $('hj-year').value = today.year;
        $('hj-greg').addEventListener('input', showHijri);
        ['hj-day', 'hj-month', 'hj-year'].forEach(id => $(id).addEventListener('input', showGregorian));
        showHijri(); showGregorian();
    });
</script>

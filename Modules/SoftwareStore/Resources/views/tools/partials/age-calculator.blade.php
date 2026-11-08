@php $f = $copy['form']; @endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <div class="space-y-4">
        <div><label class="ft-label" for="ag-birth">{{ $f['birth'] }}</label><input id="ag-birth" type="date" class="ft-input text-lg" dir="ltr" value="2000-01-01"></div>
        <div><label class="ft-label" for="ag-on">{{ $f['on_date'] }}</label><input id="ag-on" type="date" class="ft-input" dir="ltr" value="{{ now()->toDateString() }}"></div>
    </div>
    <dl class="grid grid-cols-3 gap-3 content-start">
        <div class="ft-stat !bg-[#0071e3]/10"><dt>{{ $f['years'] }}</dt><dd id="ag-years" class="!text-[#0071e3] !text-3xl">0</dd></div>
        <div class="ft-stat"><dt>{{ $f['months'] }}</dt><dd id="ag-months">0</dd></div>
        <div class="ft-stat"><dt>{{ $f['days'] }}</dt><dd id="ag-days">0</dd></div>
        <div class="ft-stat col-span-3 sm:col-span-2"><dt>{{ $f['total_days'] }}</dt><dd id="ag-total">0</dd></div>
        <div class="ft-stat col-span-3 sm:col-span-1"><dt>{{ $f['next'] }}</dt><dd id="ag-next">—</dd></div>
    </dl>
</div>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id), DAY = 86400000;
        function daysToBirthday(birth, on) {
            const born = new Date(birth + 'T00:00:00Z'), now = Date.parse(on + 'T00:00:00Z');
            let year = new Date(now).getUTCFullYear(), next = Date.UTC(year, born.getUTCMonth(), born.getUTCDate());
            if (next < now) next = Date.UTC(year + 1, born.getUTCMonth(), born.getUTCDate());
            return Math.round((next - now) / DAY);
        }
        function update() {
            const gap = FT.dateDiff($('ag-birth').value, $('ag-on').value);
            $('ag-years').textContent = gap ? gap.years : 0;
            $('ag-months').textContent = gap ? gap.months : 0;
            $('ag-days').textContent = gap ? gap.days : 0;
            $('ag-total').textContent = gap ? FT.number(gap.totalDays, 0) : 0;
            $('ag-next').textContent = gap ? daysToBirthday($('ag-birth').value, $('ag-on').value) : '—';
        }
        document.querySelectorAll('#tool input').forEach(el => el.addEventListener('input', update));
        update();
    });
</script>

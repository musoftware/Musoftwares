@php $f = $copy['form']; @endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <div class="space-y-4">
        <div><label class="ft-label" for="es-wage">{{ $f['wage'] }}</label><input id="es-wage" class="ft-input text-lg" inputmode="decimal" dir="ltr" value="6000"></div>
        <div><label class="ft-label" for="es-start">{{ $f['start'] }}</label><input id="es-start" type="date" class="ft-input" dir="ltr" value="{{ now()->subYears(6)->toDateString() }}"></div>
        <div><label class="ft-label" for="es-end">{{ $f['end'] }}</label><input id="es-end" type="date" class="ft-input" dir="ltr" value="{{ now()->toDateString() }}"></div>
        <div><label class="ft-label" for="es-reason">{{ $f['reason'] }}</label>
            <select id="es-reason" class="ft-input">@foreach ($f['reasons'] as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
        </div>
    </div>
    <div>
        <dl class="grid grid-cols-2 gap-3">
            <div class="ft-stat col-span-2 !bg-[#0071e3]/10"><dt>{{ $f['payable'] }}</dt><dd id="es-out" class="!text-[#0071e3] !text-3xl">0</dd></div>
            <div class="ft-stat"><dt>{{ $f['service'] }}</dt><dd id="es-service" data-years="{{ $f['years'] }}" data-months="{{ $f['months'] }}" data-days="{{ $f['days'] }}">—</dd></div>
            <div class="ft-stat"><dt>{{ $f['full'] }}</dt><dd id="es-full">0</dd></div>
            <div class="ft-stat col-span-2"><dt>{{ $f['share'] }}</dt><dd id="es-share">—</dd></div>
        </dl>
        <p class="mt-3 text-[13px] leading-[1.8] text-[#1d1d1f]/60">{{ $f['note'] }}</p>
    </div>
</div>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        // Saudi Labor Law: article 84 for the amount, article 85 for what a resigning worker keeps.
        const resignationShare = years => years < 2 ? 0 : years < 5 ? 1 / 3 : years < 10 ? 2 / 3 : 1;
        function update() {
            const gap = FT.dateDiff($('es-start').value, $('es-end').value), output = $('es-service');
            if (!gap) { ['es-out', 'es-full'].forEach(id => $(id).textContent = '0'); output.textContent = '—'; $('es-share').textContent = '—'; return; }
            const years = gap.years + gap.months / 12 + gap.days / 365, wage = FT.parse($('es-wage').value);
            const full = wage * (0.5 * Math.min(years, 5) + Math.max(years - 5, 0));
            const share = $('es-reason').value === 'resignation' ? resignationShare(years) : 1;
            $('es-out').textContent = FT.number(full * share);
            $('es-full').textContent = FT.number(full);
            $('es-share').textContent = FT.number(share * 100, 1) + '%';
            output.textContent = `${gap.years} ${output.dataset.years} - ${gap.months} ${output.dataset.months} - ${gap.days} ${output.dataset.days}`;
        }
        document.querySelectorAll('#tool input, #tool select').forEach(el => el.addEventListener('input', update));
        update();
    });
</script>

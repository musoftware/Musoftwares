@php $f = $copy['form']; @endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <form id="be-form" class="space-y-4" onsubmit="return false">
        <div><label class="ft-label" for="be-fixed">{{ $f['fixed'] }}</label><input id="be-fixed" class="ft-input text-lg" inputmode="decimal" dir="ltr" value="5000"></div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="ft-label" for="be-price">{{ $f['price'] }}</label><input id="be-price" class="ft-input text-lg" inputmode="decimal" dir="ltr" value="50"></div>
            <div><label class="ft-label" for="be-var">{{ $f['variable'] }}</label><input id="be-var" class="ft-input text-lg" inputmode="decimal" dir="ltr" value="30"></div>
        </div>
        <div><label class="ft-label" for="be-target">{{ $f['target'] }}</label><input id="be-target" class="ft-input" inputmode="decimal" dir="ltr"></div>
    </form>
    <div>
        <p id="be-error" class="hidden rounded-2xl bg-red-50 p-4 text-sm text-red-700">{{ $f['impossible'] }}</p>
        <dl id="be-results" class="grid grid-cols-2 gap-3 content-start">
            <div class="ft-stat col-span-2 !bg-[#0071e3]/10"><dt>{{ $f['units'] }}</dt><dd id="be-units" class="!text-[#0071e3] !text-3xl">0</dd></div>
            <div class="ft-stat"><dt>{{ $f['sales'] }}</dt><dd id="be-sales">0</dd></div>
            <div class="ft-stat"><dt>{{ $f['contribution'] }}</dt><dd id="be-contrib">0</dd></div>
            <div class="ft-stat col-span-2"><dt>{{ $f['target_units'] }}</dt><dd id="be-target-units">—</dd></div>
        </dl>
    </div>
</div>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        function update() {
            const fixed = FT.parse($('be-fixed').value), price = FT.parse($('be-price').value), cost = FT.parse($('be-var').value), target = FT.parse($('be-target').value);
            const perUnit = price - cost;
            const ok = perUnit > 0;
            $('be-error').classList.toggle('hidden', ok);
            $('be-results').classList.toggle('hidden', !ok);
            if (!ok) return;
            const units = Math.ceil(fixed / perUnit);
            $('be-units').textContent = FT.number(units, 0);
            $('be-sales').textContent = FT.number(units * price);
            $('be-contrib').textContent = FT.number(perUnit);
            $('be-target-units').textContent = target > 0 ? FT.number(Math.ceil((fixed + target) / perUnit), 0) : '—';
        }
        $('be-form').addEventListener('input', update);
        update();
    });
</script>

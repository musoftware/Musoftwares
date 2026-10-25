@php $f = $copy['form']; @endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <div class="space-y-4">
        <div><label class="ft-label" for="vat-amount">{{ $f['amount'] }}</label><input id="vat-amount" class="ft-input text-lg" inputmode="decimal" dir="ltr" value="1000"></div>
        <fieldset>
            <legend class="ft-label">{{ $f['mode'] }}</legend>
            <div class="space-y-2">
                @foreach ($f['modes'] as $key => $label)
                    <label class="flex items-center gap-2 text-sm"><input type="radio" name="vat-mode" value="{{ $key }}" @checked($loop->first)> {{ $label }}</label>
                @endforeach
            </div>
        </fieldset>
        <div>
            <label class="ft-label" for="vat-rate">{{ $f['rate'] }}</label>
            <div class="flex flex-wrap gap-2">
                <input id="vat-rate" class="ft-input !w-28" inputmode="decimal" dir="ltr" value="14">
                @foreach ([14, 15, 5] as $preset)
                    <button type="button" class="ft-btn ft-btn-light !px-3" data-rate="{{ $preset }}" dir="ltr">{{ $preset }}%</button>
                @endforeach
            </div>
        </div>
    </div>
    <dl class="grid grid-cols-1 gap-3 content-start">
        <div class="ft-stat"><dt>{{ $f['net'] }}</dt><dd id="vat-net">0</dd></div>
        <div class="ft-stat"><dt>{{ $f['vat'] }}</dt><dd id="vat-tax">0</dd></div>
        <div class="ft-stat !bg-[#0071e3]/10"><dt>{{ $f['gross'] }}</dt><dd id="vat-gross" class="!text-[#0071e3]">0</dd></div>
    </dl>
</div>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        function update() {
            const amount = FT.parse($('vat-amount').value), rate = FT.parse($('vat-rate').value) / 100;
            const add = document.querySelector('[name="vat-mode"]:checked').value === 'add';
            const net = add ? amount : amount / (1 + rate);
            $('vat-net').textContent = FT.number(net);
            $('vat-tax').textContent = FT.number(net * rate);
            $('vat-gross').textContent = FT.number(net * (1 + rate));
        }
        document.querySelectorAll('#vat-amount, #vat-rate, [name="vat-mode"]').forEach(el => el.addEventListener('input', update));
        document.querySelectorAll('[data-rate]').forEach(b => b.addEventListener('click', () => { $('vat-rate').value = b.dataset.rate; update(); }));
        update();
    });
</script>

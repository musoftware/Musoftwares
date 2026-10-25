@php $f = $copy['form']; @endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <div class="space-y-4">
        <fieldset>
            <legend class="ft-label">{{ $f['mode'] }}</legend>
            <div class="flex flex-wrap gap-4">
                @foreach ($f['modes'] as $key => $label)
                    <label class="flex items-center gap-2 text-sm"><input type="radio" name="pm-mode" value="{{ $key }}" @checked($loop->first)> {{ $label }}</label>
                @endforeach
            </div>
        </fieldset>
        <div><label class="ft-label" for="pm-cost">{{ $f['cost'] }}</label><input id="pm-cost" class="ft-input text-lg" inputmode="decimal" dir="ltr" value="80"></div>
        <div id="pm-price-box"><label class="ft-label" for="pm-price">{{ $f['price'] }}</label><input id="pm-price" class="ft-input text-lg" inputmode="decimal" dir="ltr" value="100"></div>
        <div id="pm-target-box" class="hidden"><label class="ft-label" for="pm-target">{{ $f['target'] }}</label><input id="pm-target" class="ft-input text-lg" inputmode="decimal" dir="ltr" value="30"></div>
        <div><label class="ft-label" for="pm-qty">{{ $f['qty'] }}</label><input id="pm-qty" class="ft-input" inputmode="numeric" dir="ltr"></div>
    </div>
    <dl class="grid grid-cols-2 gap-3 content-start">
        <div class="ft-stat col-span-2 !bg-[#0071e3]/10"><dt>{{ $f['selling_price'] }}</dt><dd id="pm-out-price" class="!text-[#0071e3] !text-3xl">0</dd></div>
        <div class="ft-stat"><dt>{{ $f['profit'] }}</dt><dd id="pm-out-profit">0</dd></div>
        <div class="ft-stat"><dt>{{ $f['margin'] }}</dt><dd id="pm-out-margin">0</dd></div>
        <div class="ft-stat"><dt>{{ $f['markup'] }}</dt><dd id="pm-out-markup">0</dd></div>
        <div class="ft-stat"><dt>{{ $f['total_profit'] }}</dt><dd id="pm-out-total">—</dd></div>
    </dl>
</div>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        function update() {
            const byMargin = document.querySelector('[name="pm-mode"]:checked').value === 'margin';
            $('pm-price-box').classList.toggle('hidden', byMargin);
            $('pm-target-box').classList.toggle('hidden', !byMargin);
            const cost = FT.parse($('pm-cost').value);
            const target = Math.min(FT.parse($('pm-target').value), 99.9) / 100;
            const price = byMargin ? cost / (1 - target) : FT.parse($('pm-price').value);
            const profit = price - cost, qty = FT.parse($('pm-qty').value);
            $('pm-out-price').textContent = FT.number(price);
            $('pm-out-profit').textContent = FT.number(profit);
            $('pm-out-margin').textContent = price ? FT.number(profit / price * 100, 1) + '%' : '—';
            $('pm-out-markup').textContent = cost ? FT.number(profit / cost * 100, 1) + '%' : '—';
            $('pm-out-total').textContent = qty ? FT.number(profit * qty) : '—';
        }
        document.querySelectorAll('#tool input').forEach(el => el.addEventListener('input', update));
        update();
    });
</script>

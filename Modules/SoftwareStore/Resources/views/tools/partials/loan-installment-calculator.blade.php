@php $f = $copy['form']; @endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <div class="space-y-4">
        <div><label class="ft-label" for="ln-price">{{ $f['price'] }}</label><input id="ln-price" class="ft-input text-lg" inputmode="decimal" dir="ltr" value="100000"></div>
        <div><label class="ft-label" for="ln-down">{{ $f['down'] }}</label><input id="ln-down" class="ft-input" inputmode="decimal" dir="ltr" value="20000"></div>
        <div><label class="ft-label" for="ln-rate">{{ $f['rate'] }}</label><input id="ln-rate" class="ft-input" inputmode="decimal" dir="ltr" value="20"></div>
        <div><label class="ft-label" for="ln-months">{{ $f['months'] }}</label><input id="ln-months" class="ft-input" inputmode="numeric" dir="ltr" value="36"></div>
        <fieldset>
            <legend class="ft-label">{{ $f['type'] }}</legend>
            <div class="space-y-2">
                @foreach ($f['types'] as $key => $label)
                    <label class="flex items-center gap-2 text-sm"><input type="radio" name="ln-type" value="{{ $key }}" @checked($loop->first)> {{ $label }}</label>
                @endforeach
            </div>
        </fieldset>
    </div>
    <div>
        <dl class="grid grid-cols-2 gap-3">
            <div class="ft-stat col-span-2 !bg-[#0071e3]/10"><dt>{{ $f['monthly'] }}</dt><dd id="ln-out" class="!text-[#0071e3] !text-3xl">0</dd></div>
            <div class="ft-stat"><dt>{{ $f['interest'] }}</dt><dd id="ln-interest">0</dd></div>
            <div class="ft-stat"><dt>{{ $f['total'] }}</dt><dd id="ln-total">0</dd></div>
        </dl>
        <p class="mt-3 text-[13px] leading-[1.8] text-[#1d1d1f]/60">{{ $f['note'] }}</p>
    </div>
</div>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        function monthlyPayment(principal, yearlyRate, months, type) {
            if (type === 'flat') return principal * (1 + yearlyRate * months / 12) / months;
            const rate = yearlyRate / 12;
            return rate ? principal * rate / (1 - (1 + rate) ** -months) : principal / months;
        }
        function update() {
            const principal = Math.max(FT.parse($('ln-price').value) - FT.parse($('ln-down').value), 0);
            const months = Math.max(Math.round(FT.parse($('ln-months').value)), 0);
            const type = document.querySelector('[name="ln-type"]:checked').value;
            const monthly = months ? monthlyPayment(principal, FT.parse($('ln-rate').value) / 100, months, type) : 0;
            $('ln-out').textContent = FT.number(monthly);
            $('ln-total').textContent = FT.number(monthly * months);
            $('ln-interest').textContent = FT.number(Math.max(monthly * months - principal, 0));
        }
        document.querySelectorAll('#tool input').forEach(el => el.addEventListener('input', update));
        update();
    });
</script>

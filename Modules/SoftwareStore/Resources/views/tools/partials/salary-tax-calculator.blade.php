@php $f = $copy['form']; @endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <div class="space-y-4">
        <div><label class="ft-label" for="st-gross">{{ $f['gross'] }}</label><input id="st-gross" class="ft-input text-lg" inputmode="decimal" dir="ltr" value="15000"></div>
        <div>
            <label class="ft-label" for="st-insured">{{ $f['insured'] }}</label>
            <input id="st-insured" class="ft-input" inputmode="decimal" dir="ltr">
            <p class="mt-1 text-xs text-[#1d1d1f]/50">{{ $f['insured_hint'] }}</p>
        </div>
        <dl class="grid grid-cols-1 gap-3">
            <div class="ft-stat !bg-[#0071e3]/10"><dt>{{ $f['net_monthly'] }}</dt><dd id="st-net" class="!text-[#0071e3] !text-3xl">0</dd></div>
            <div class="grid grid-cols-2 gap-3">
                <div class="ft-stat"><dt>{{ $f['tax_monthly'] }}</dt><dd id="st-tax-m">0</dd></div>
                <div class="ft-stat"><dt>{{ $f['insurance_monthly'] }}</dt><dd id="st-ins-m">0</dd></div>
            </div>
        </dl>
    </div>
    <div class="space-y-5">
        <div>
            <h3 class="font-bold">{{ $f['annual'] }}</h3>
            <dl class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between gap-4"><dt>{{ $f['annual_gross'] }}</dt><dd id="st-a-gross" dir="ltr">0</dd></div>
                <div class="flex justify-between gap-4"><dt>{{ $f['annual_insurance'] }}</dt><dd id="st-a-ins" dir="ltr">0</dd></div>
                <div class="flex justify-between gap-4"><dt>{{ $f['exemption'] }}</dt><dd dir="ltr">− 20,000</dd></div>
                <div class="flex justify-between gap-4 font-semibold border-t border-black/10 pt-2"><dt>{{ $f['taxable'] }}</dt><dd id="st-a-taxable" dir="ltr">0</dd></div>
                <div class="flex justify-between gap-4 font-semibold"><dt>{{ $f['annual_tax'] }}</dt><dd id="st-a-tax" dir="ltr">0</dd></div>
            </dl>
        </div>
        <div>
            <h3 class="font-bold">{{ $f['brackets'] }}</h3>
            <table class="mt-3 w-full text-sm" dir="ltr"><tbody id="st-brackets"></tbody></table>
        </div>
        <p class="text-xs text-amber-700">{{ $f['checked'] }}</p>
    </div>
</div>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        // Egypt 2026: yearly brackets on taxable income; above 600,000 the lower brackets are taxed at a higher rate.
        const BRACKETS = [[40000, 0], [55000, 0.10], [70000, 0.15], [200000, 0.20], [400000, 0.225], [1200000, 0.25], [Infinity, 0.275]];
        const EXEMPTION = 20000, INSURED_MIN = 2700, INSURED_MAX = 16700, INSURANCE_RATE = 0.11;
        let insuredTouched = false;

        function incomeTax(taxable) {
            const start = taxable > 1200000 ? 5 : taxable > 900000 ? 4 : taxable > 800000 ? 3 : taxable > 700000 ? 2 : taxable > 600000 ? 1 : 0;
            const rows = [];
            let previous = 0, total = 0;
            BRACKETS.forEach(([upper, rate], i) => {
                const portion = Math.min(taxable, upper) - previous;
                if (portion <= 0) return;
                const applied = i < start ? BRACKETS[start][1] : rate;
                rows.push([previous, Math.min(taxable, upper), applied, portion * applied]);
                total += portion * applied;
                previous = upper;
            });
            return { total, rows };
        }

        function update() {
            const gross = FT.parse($('st-gross').value);
            if (!insuredTouched) $('st-insured').value = Math.round(Math.min(Math.max(gross, INSURED_MIN), INSURED_MAX));
            const insured = gross > 0 ? Math.min(Math.max(FT.parse($('st-insured').value), INSURED_MIN), INSURED_MAX) : 0;
            const insurance = insured * INSURANCE_RATE * 12;
            const taxable = Math.max(0, gross * 12 - insurance - EXEMPTION);
            const { total, rows } = incomeTax(taxable);
            $('st-net').textContent = FT.number((gross * 12 - insurance - total) / 12);
            $('st-tax-m').textContent = FT.number(total / 12);
            $('st-ins-m').textContent = FT.number(insurance / 12);
            $('st-a-gross').textContent = FT.number(gross * 12);
            $('st-a-ins').textContent = '− ' + FT.number(insurance);
            $('st-a-taxable').textContent = FT.number(taxable);
            $('st-a-tax').textContent = FT.number(total);
            $('st-brackets').innerHTML = rows.map(([from, to, rate, tax]) =>
                `<tr class="border-b border-black/5"><td class="py-1.5">${FT.number(from, 0)} – ${FT.number(to, 0)}</td><td class="py-1.5 text-center">${FT.number(rate * 100, 1)}%</td><td class="py-1.5 text-right">${FT.number(tax)}</td></tr>`).join('');
        }

        $('st-gross').addEventListener('input', update);
        $('st-insured').addEventListener('input', () => { insuredTouched = true; update(); });
        update();
    });
</script>

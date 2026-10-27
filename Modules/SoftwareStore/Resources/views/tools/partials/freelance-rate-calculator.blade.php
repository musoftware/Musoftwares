@php $f = $copy['form']; @endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <form id="fr-form" class="space-y-4" onsubmit="return false">
        <div class="grid grid-cols-3 gap-3">
            <div class="col-span-2"><label class="ft-label" for="fr-income">{{ $f['income'] }}</label><input id="fr-income" class="ft-input text-lg" inputmode="decimal" dir="ltr" value="60000"></div>
            <div><label class="ft-label" for="fr-currency">{{ __('free_tools.ui.currency') }}</label>
                <select id="fr-currency" class="ft-input !px-2">@foreach (['USD', 'EUR', 'GBP', 'EGP', 'SAR', 'AED'] as $c)<option>{{ $c }}</option>@endforeach</select></div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="ft-label" for="fr-costs">{{ $f['costs'] }}</label><input id="fr-costs" class="ft-input" inputmode="decimal" dir="ltr" value="6000"></div>
            <div><label class="ft-label" for="fr-tax">{{ $f['tax'] }}</label><input id="fr-tax" class="ft-input" inputmode="decimal" dir="ltr" value="25"></div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="ft-label" for="fr-hours">{{ $f['hours'] }}</label><input id="fr-hours" class="ft-input" inputmode="decimal" dir="ltr" value="25"></div>
            <div><label class="ft-label" for="fr-off">{{ $f['weeks_off'] }}</label><input id="fr-off" class="ft-input" inputmode="decimal" dir="ltr" value="6"></div>
        </div>
    </form>
    <dl class="grid grid-cols-2 gap-3 content-start">
        <div class="ft-stat col-span-2 !bg-[#0071e3]/10"><dt>{{ $f['hourly'] }}</dt><dd id="fr-hourly" class="!text-[#0071e3] !text-3xl">0</dd></div>
        <div class="ft-stat"><dt>{{ $f['daily'] }}</dt><dd id="fr-daily">0</dd></div>
        <div class="ft-stat"><dt>{{ $f['monthly'] }}</dt><dd id="fr-monthly">0</dd></div>
        <div class="ft-stat col-span-2"><dt>{{ $f['yearly'] }}</dt><dd id="fr-yearly">0</dd><p id="fr-billable" class="mt-1 text-xs text-[#1d1d1f]/50"></p></div>
    </dl>
</div>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        const T = @json($f);
        function money(value) {
            try { return new Intl.NumberFormat('en-US', { style: 'currency', currency: $('fr-currency').value, maximumFractionDigits: 0 }).format(value); }
            catch (e) { return FT.number(value, 0); }
        }
        function update() {
            const income = FT.parse($('fr-income').value), costs = FT.parse($('fr-costs').value);
            const tax = Math.min(FT.parse($('fr-tax').value), 90) / 100;
            const hours = FT.parse($('fr-hours').value), weeks = Math.max(0, 52 - FT.parse($('fr-off').value));
            // revenue needed so that, after tax, take-home pay covers the income goal plus business costs
            const yearly = (income + costs) / (1 - tax);
            const billable = hours * weeks;
            const hourly = billable > 0 ? yearly / billable : 0;
            $('fr-hourly').textContent = money(hourly);
            $('fr-daily').textContent = money(hourly * 8);
            $('fr-monthly').textContent = money(yearly / 12);
            $('fr-yearly').textContent = money(yearly);
            $('fr-billable').textContent = T.billable.replace(':hours', FT.number(billable, 0));
        }
        $('fr-form').addEventListener('input', update);
        $('fr-form').addEventListener('change', update);
        update();
    });
</script>

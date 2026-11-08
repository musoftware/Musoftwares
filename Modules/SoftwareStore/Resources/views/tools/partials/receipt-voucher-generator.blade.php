@php $f = $copy['form']; @endphp
@include('softwarestore::tools.tafqeet-script')
<style>
    @media print {
        body * { visibility: hidden; }
        #rv-sheet, #rv-sheet * { visibility: visible; }
        #rv-sheet { position: absolute; inset: 0; border: 0; box-shadow: none; }
    }
</style>
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-3">
            <div><label class="ft-label" for="rv-no">{{ $f['number'] }}</label><input id="rv-no" class="ft-input" dir="ltr" value="1"></div>
            <div><label class="ft-label" for="rv-date">{{ $f['date'] }}</label><input id="rv-date" type="date" class="ft-input" dir="ltr" value="{{ now()->toDateString() }}"></div>
        </div>
        <div><label class="ft-label" for="rv-company">{{ $f['company'] }}</label><input id="rv-company" class="ft-input"></div>
        <div><label class="ft-label" for="rv-from">{{ $f['from'] }}</label><input id="rv-from" class="ft-input"></div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="ft-label" for="rv-amount">{{ $f['amount'] }}</label><input id="rv-amount" class="ft-input" inputmode="decimal" dir="ltr" value="1000"></div>
            <div><label class="ft-label" for="rv-currency">{{ __('free_tools.ui.currency') }}</label>
                <select id="rv-currency" class="ft-input">@foreach (__('free_tools.ui.arab_currencies') as $code => $label)<option value="{{ $code }}">{{ $label }}</option>@endforeach</select>
            </div>
        </div>
        <div><label class="ft-label" for="rv-for">{{ $f['for'] }}</label><input id="rv-for" class="ft-input"></div>
        <div><label class="ft-label" for="rv-method">{{ $f['method'] }}</label>
            <select id="rv-method" class="ft-input">@foreach ($f['methods'] as $method)<option>{{ $method }}</option>@endforeach</select>
        </div>
        <button type="button" id="rv-print" class="ft-btn"><i class="ri-printer-line" aria-hidden="true"></i>{{ __('free_tools.ui.print') }}</button>
    </div>

    <div id="rv-sheet" class="border border-black/10 rounded-2xl p-6 bg-white text-[15px] leading-[2]">
        <p id="rv-out-company" class="font-bold text-center"></p>
        <h2 class="text-xl font-bold text-center">{{ $f['title'] }}</h2>
        <div class="mt-3 flex justify-between text-[13px] text-[#515154]"><span>{{ $f['number'] }}: <span id="rv-out-no" dir="ltr"></span></span><span>{{ $f['date'] }}: <span id="rv-out-date" dir="ltr"></span></span></div>
        <p class="mt-4">{{ $f['received_from'] }}: <strong id="rv-out-from"></strong></p>
        <p>{{ $f['sum_of'] }}: <strong id="rv-out-amount" dir="ltr"></strong></p>
        <p id="rv-out-words" dir="rtl" lang="ar" class="font-semibold text-start"></p>
        <p>{{ $f['for_label'] }}: <strong id="rv-out-for"></strong></p>
        <p>{{ $f['method'] }}: <strong id="rv-out-method"></strong></p>
        <div class="mt-8 flex justify-between text-[13px]"><span>{{ $f['receiver'] }}: ............</span><span>{{ $f['signature'] }}: ............</span></div>
    </div>
</div>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        function update() {
            const amount = Math.abs(FT.parse($('rv-amount').value)), currency = $('rv-currency').value;
            ['company', 'no', 'date', 'from', 'for'].forEach(name => $('rv-out-' + name).textContent = $('rv-' + name).value);
            $('rv-out-method').textContent = $('rv-method').value;
            $('rv-out-amount').textContent = FT.number(amount) + ' ' + currency;
            $('rv-out-words').textContent = FTWords.amount(amount, currency);
        }
        document.querySelectorAll('#tool input, #tool select').forEach(el => el.addEventListener('input', update));
        $('rv-print').addEventListener('click', () => window.print());
        update();
    });
</script>

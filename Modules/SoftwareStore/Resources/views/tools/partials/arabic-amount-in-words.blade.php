@php $f = $copy['form']; @endphp
@include('softwarestore::tools.tafqeet-script')
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="space-y-4">
        <div><label class="ft-label" for="aw-amount">{{ $f['amount'] }}</label><input id="aw-amount" class="ft-input text-lg" inputmode="decimal" dir="ltr" value="1250.50"></div>
        <div><label class="ft-label" for="aw-currency">{{ __('free_tools.ui.currency') }}</label>
            <select id="aw-currency" class="ft-input">@foreach (__('free_tools.ui.arab_currencies') as $code => $label)<option value="{{ $code }}">{{ $label }}</option>@endforeach</select>
        </div>
    </div>
    <div class="ft-stat">
        <p class="text-[12px] text-[#1d1d1f]/60">{{ $f['result'] }}</p>
        <p id="aw-out" dir="rtl" lang="ar" class="mt-2 text-[18px] font-bold leading-[1.9] text-start"></p>
        <button type="button" id="aw-copy" class="ft-btn mt-4">{{ __('free_tools.ui.copy') }}</button>
    </div>
</div>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        const update = () => { $('aw-out').textContent = FTWords.amount(Math.abs(FT.parse($('aw-amount').value)), $('aw-currency').value); };
        $('aw-amount').addEventListener('input', update);
        $('aw-currency').addEventListener('change', update);
        $('aw-copy').addEventListener('click', e => FT.copy($('aw-out').textContent, e.currentTarget));
        update();
    });
</script>

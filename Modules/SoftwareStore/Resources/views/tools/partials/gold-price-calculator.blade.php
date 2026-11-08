@php $f = $copy['form']; @endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <div class="space-y-4">
        <div><label class="ft-label" for="gd-weight">{{ $f['weight'] }}</label><input id="gd-weight" class="ft-input" inputmode="decimal" dir="ltr" placeholder="8"></div>
        <div><label class="ft-label" for="gd-karat">{{ $f['karat'] }}</label>
            <select id="gd-karat" class="ft-input" dir="ltr">
                <option value="21">21</option>
                <option value="18">18</option>
                <option value="24">24</option>
                <option value="22">22</option>
            </select>
        </div>
        <div><label class="ft-label" for="gd-price">{{ $f['price'] }}</label><input id="gd-price" class="ft-input" inputmode="decimal" dir="ltr" placeholder="{{ $f['price_hint'] }}"></div>
        <div><label class="ft-label" for="gd-making">{{ $f['making'] }}</label><input id="gd-making" class="ft-input" inputmode="decimal" dir="ltr" placeholder="100"></div>
        <div><label class="ft-label" for="gd-fees">{{ $f['fees'] }}</label><input id="gd-fees" class="ft-input" inputmode="decimal" dir="ltr" placeholder="10"></div>
    </div>
    <div>
        <div class="ft-stat !bg-[#0071e3]/10"><dt>{{ $f['total'] }}</dt><dd id="gd-out" class="!text-[#0071e3] !text-[28px]">—</dd></div>
        <dl class="mt-3 grid grid-cols-2 gap-3">
            <div class="ft-stat"><dt>{{ $f['gold_value'] }}</dt><dd id="gd-value">—</dd></div>
            <div class="ft-stat"><dt>{{ $f['making_total'] }}</dt><dd id="gd-making-total">—</dd></div>
            <div class="ft-stat"><dt>{{ $f['karat_price'] }}</dt><dd id="gd-karat-price">—</dd></div>
            <div class="ft-stat"><dt>{{ $f['gram_final'] }}</dt><dd id="gd-gram">—</dd></div>
        </dl>
        <p class="mt-3 text-xs text-[#1d1d1f]/60">{{ $f['note'] }}</p>
    </div>
</div>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        const value = id => FT.parse($(id).value);
        const put = (id, number) => { $(id).textContent = number === null ? '—' : FT.number(number); };

        function update() {
            const weight = value('gd-weight'), price21 = value('gd-price'), karatPrice = price21 * Number($('gd-karat').value) / 21;
            const gold = weight * karatPrice, making = weight * value('gd-making'), total = gold + making + value('gd-fees');
            const ready = weight > 0 && price21 > 0;
            put('gd-karat-price', price21 > 0 ? karatPrice : null);
            put('gd-value', ready ? gold : null);
            put('gd-making-total', ready ? making : null);
            put('gd-out', ready ? total : null);
            put('gd-gram', ready ? total / weight : null);
        }

        ['gd-weight', 'gd-karat', 'gd-price', 'gd-making', 'gd-fees'].forEach(id => $(id).addEventListener('input', update));
    });
</script>

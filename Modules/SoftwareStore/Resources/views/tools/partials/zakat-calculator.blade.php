@php $f = $copy['form']; @endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <div class="space-y-4">
        @foreach (['cash', 'gold_value', 'goods', 'receivables', 'debts'] as $field)
            <div><label class="ft-label" for="zk-{{ $field }}">{{ $f[$field] }}</label><input id="zk-{{ $field }}" class="ft-input" inputmode="decimal" dir="ltr" placeholder="0"></div>
        @endforeach
        <div><label class="ft-label" for="zk-gram">{{ $f['gram'] }}</label><input id="zk-gram" class="ft-input" inputmode="decimal" dir="ltr"></div>
        <fieldset>
            <legend class="ft-label">{{ $f['karat'] }}</legend>
            <div class="flex flex-wrap gap-4">
                @foreach ($f['karats'] as $key => $label)
                    <label class="flex items-center gap-2 text-sm"><input type="radio" name="zk-karat" value="{{ $key }}" @checked($loop->first)> {{ $label }}</label>
                @endforeach
            </div>
        </fieldset>
    </div>
    <div>
        <dl class="grid grid-cols-2 gap-3">
            <div class="ft-stat col-span-2 !bg-[#0071e3]/10"><dt>{{ $f['zakat'] }}</dt><dd id="zk-out" class="!text-[#0071e3] !text-3xl">0</dd></div>
            <div class="ft-stat"><dt>{{ $f['net'] }}</dt><dd id="zk-net">0</dd></div>
            <div class="ft-stat"><dt>{{ $f['nisab'] }}</dt><dd id="zk-nisab">—</dd></div>
        </dl>
        <p id="zk-status" class="mt-3 text-sm font-semibold" data-due="{{ $f['due'] }}" data-below="{{ $f['below'] }}" data-need="{{ $f['need_gram'] }}"></p>
        <p class="mt-3 text-[13px] leading-[1.8] text-[#1d1d1f]/60">{{ $f['note'] }}</p>
    </div>
</div>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        const value = id => FT.parse($('zk-' + id).value);
        const NISAB_GRAMS = 85, RATE = 0.025;
        function update() {
            const net = Math.max(value('cash') + value('gold_value') + value('goods') + value('receivables') - value('debts'), 0);
            const nisab = value('gram') * NISAB_GRAMS, status = $('zk-status');
            const due = nisab > 0 && net >= nisab;
            $('zk-net').textContent = FT.number(net);
            $('zk-nisab').textContent = nisab ? FT.number(nisab) : '—';
            $('zk-out').textContent = FT.number(due ? net * RATE : 0);
            status.textContent = !nisab ? status.dataset.need : due ? status.dataset.due : status.dataset.below;
        }
        document.querySelectorAll('#tool input').forEach(el => el.addEventListener('input', update));
        update();
    });
</script>

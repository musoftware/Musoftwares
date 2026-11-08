@php
    $f = $copy['form'];
    $cards = [
        ['of', $f['of_title'], $f['percent'], $f['of_number'], '20', '500'],
        ['what', $f['what_title'], $f['part'], $f['whole'], '50', '200'],
        ['change', $f['change_title'], $f['old'], $f['new'], '100', '125'],
        ['discount', $f['discount_title'], $f['price'], $f['discount'], '1000', '15'],
    ];
@endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    @foreach ($cards as [$key, $title, $first, $second, $a, $b])
        <section class="border border-black/10 rounded-2xl p-5">
            <h2 class="font-bold">{{ $title }}</h2>
            <div class="mt-3 grid grid-cols-2 gap-3">
                <div><label class="ft-label" for="pc-{{ $key }}-a">{{ $first }}</label><input id="pc-{{ $key }}-a" class="ft-input" inputmode="decimal" dir="ltr" value="{{ $a }}"></div>
                <div><label class="ft-label" for="pc-{{ $key }}-b">{{ $second }}</label><input id="pc-{{ $key }}-b" class="ft-input" inputmode="decimal" dir="ltr" value="{{ $b }}"></div>
            </div>
            <div class="ft-stat mt-3"><p class="text-[12px] text-[#1d1d1f]/60">{{ $f['result'] }}</p><p id="pc-{{ $key }}-out" dir="ltr" class="mt-1 text-2xl font-bold text-[#0071e3] text-start">0</p></div>
        </section>
    @endforeach
</div>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        const percent = value => Number.isFinite(value) ? FT.number(value) + '%' : '—';
        const formulas = {
            of: (a, b) => FT.number(a / 100 * b),
            what: (a, b) => percent(a / b * 100),
            change: (a, b) => percent((b - a) / a * 100),
            discount: (a, b) => FT.number(a - a * b / 100),
        };
        function update() {
            for (const [key, formula] of Object.entries(formulas)) {
                $(`pc-${key}-out`).textContent = formula(FT.parse($(`pc-${key}-a`).value), FT.parse($(`pc-${key}-b`).value));
            }
        }
        document.querySelectorAll('#tool input').forEach(el => el.addEventListener('input', update));
        update();
    });
</script>

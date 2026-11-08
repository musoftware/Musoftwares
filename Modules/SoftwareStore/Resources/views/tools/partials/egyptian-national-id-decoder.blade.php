@php $f = $copy['form']; @endphp
<label class="ft-label" for="id-number">{{ $f['number'] }}</label>
<input id="id-number" class="ft-input text-[18px] tracking-wider" dir="ltr" inputmode="numeric" maxlength="14" autocomplete="off" placeholder="{{ $f['placeholder'] }}">
<p id="id-error" class="mt-3 text-sm font-semibold text-red-600 hidden"></p>
<dl class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
    @foreach (['birth', 'age', 'governorate', 'gender'] as $stat)
        <div class="ft-stat"><dt>{{ $f[$stat] }}</dt><dd id="id-{{ $stat }}">—</dd></div>
    @endforeach
</dl>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        const GOVERNORATES = @json($f['governorates']), TEXT = @json($f['messages']), FIELDS = ['birth', 'age', 'governorate', 'gender'];
        const CENTURIES = { 2: 1900, 3: 2000 };
        const today = () => new Date().toLocaleDateString('en-CA');
        const pad = number => String(number).padStart(2, '0');

        function birthDate(id) {
            const year = CENTURIES[id[0]] + Number(id.slice(1, 3)), month = Number(id.slice(3, 5)), day = Number(id.slice(5, 7));
            const date = new Date(Date.UTC(year, month - 1, day));
            if (!CENTURIES[id[0]] || date.getUTCMonth() !== month - 1 || date.getUTCDate() !== day) return null;
            const iso = year + '-' + pad(month) + '-' + pad(day);
            return iso <= today() ? iso : null;
        }

        function problemWith(id) {
            if (!/^\d{14}$/.test(id)) return TEXT.length;
            if (!birthDate(id)) return TEXT.date;
            return GOVERNORATES[id.slice(7, 9)] ? '' : TEXT.governorate;
        }

        function show(values) {
            FIELDS.forEach(field => { $('id-' + field).textContent = values[field] ?? '—'; });
        }

        function showResult(id) {
            const birth = birthDate(id), age = FT.dateDiff(birth, today());
            show({
                birth,
                age: age.years + ' ' + TEXT.years + ' ' + age.months + ' ' + TEXT.months,
                governorate: GOVERNORATES[id.slice(7, 9)],
                gender: Number(id[12]) % 2 ? TEXT.male : TEXT.female,
            });
        }

        function update() {
            const id = $('id-number').value.replace(/[٠-٩]/g, digit => '٠١٢٣٤٥٦٧٨٩'.indexOf(digit)).trim();
            const problem = id ? problemWith(id) : '';
            $('id-error').textContent = problem;
            $('id-error').classList.toggle('hidden', !problem);
            return id && !problem ? showResult(id) : show({});
        }

        $('id-number').addEventListener('input', update);
    });
</script>

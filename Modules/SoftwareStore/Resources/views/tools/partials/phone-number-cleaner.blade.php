@php $f = $copy['form']; @endphp
<div class="space-y-5">
    <div>
        <label class="ft-label" for="pc-input">{{ $f['input'] }}</label>
        <textarea id="pc-input" class="ft-input font-mono text-sm" rows="8" dir="ltr" placeholder="01012345678&#10;+20 112 345 6789&#10;00201223456789&#10;١٠١٢٣٤٥٦٧٨"></textarea>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="ft-label" for="pc-file">{{ $f['upload'] }}</label>
            <input id="pc-file" type="file" accept=".csv,.txt,.xlsx,.xls" class="ft-input text-sm">
            <p id="pc-reading" class="hidden mt-1 text-xs text-[#1d1d1f]/50">{{ $f['reading'] }}</p>
        </div>
        <div>
            <label class="ft-label" for="pc-format">{{ $f['format'] }}</label>
            <select id="pc-format" class="ft-input">
                @foreach ($f['formats'] as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <button type="button" id="pc-clean" class="ft-btn w-full sm:w-auto"><i class="ri-magic-line" aria-hidden="true"></i>{{ $f['clean'] }}</button>

    <div id="pc-result" class="hidden space-y-4">
        <dl class="grid grid-cols-3 gap-3">
            <div class="ft-stat"><dt>{{ $f['valid'] }}</dt><dd id="pc-valid" class="!text-emerald-700">0</dd></div>
            <div class="ft-stat"><dt>{{ $f['duplicates'] }}</dt><dd id="pc-dupes">0</dd></div>
            <div class="ft-stat"><dt>{{ $f['invalid'] }}</dt><dd id="pc-invalid" class="!text-red-600">0</dd></div>
        </dl>
        <textarea id="pc-output" class="ft-input font-mono text-sm" rows="8" dir="ltr" readonly></textarea>
        <div class="flex flex-wrap gap-2">
            <button type="button" id="pc-copy" class="ft-btn">{{ __('free_tools.ui.copy') }}</button>
            <button type="button" id="pc-download" class="ft-btn ft-btn-light"><i class="ri-download-2-line" aria-hidden="true"></i>{{ __('free_tools.ui.download_csv') }}</button>
        </div>
        <details id="pc-bad-wrap" class="hidden">
            <summary class="cursor-pointer text-sm font-semibold">{{ $f['invalid_list'] }}</summary>
            <pre id="pc-bad" class="mt-2 max-h-48 overflow-auto rounded-xl bg-[#f5f5f7] p-3 text-xs" dir="ltr"></pre>
        </details>
    </div>
</div>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        const toLatin = s => s.replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d));

        // Any common way of writing an Egyptian mobile number -> 01XXXXXXXXX, or null.
        function normalize(token) {
            let d = toLatin(token).replace(/\D/g, '');
            if (d.startsWith('0020')) d = d.slice(4);
            else if (d.startsWith('20') && d.length === 12) d = d.slice(2);
            if (d.length === 10 && d.startsWith('1')) d = '0' + d;
            return /^01[0125]\d{8}$/.test(d) ? d : null;
        }

        function clean() {
            const format = $('pc-format').value;
            const seen = new Set(), good = [], bad = [];
            let dupes = 0;
            for (const raw of $('pc-input').value.split(/[\n\r,;\t|]+/)) {
                const token = raw.trim().replace(/^"|"$/g, '');
                if (!/[0-9٠-٩۰-۹]{4,}/.test(token.replace(/[\s\-().]/g, ''))) continue; // names and headers
                const local = normalize(token);
                if (!local) { bad.push(token); continue; }
                if (seen.has(local)) { dupes++; continue; }
                seen.add(local);
                good.push(format === 'local' ? local : (format === 'intl' ? '+20' : '20') + local.slice(1));
            }
            $('pc-output').value = good.join('\n');
            $('pc-valid').textContent = FT.number(good.length);
            $('pc-dupes').textContent = FT.number(dupes);
            $('pc-invalid').textContent = FT.number(bad.length);
            $('pc-bad').textContent = bad.slice(0, 500).join('\n');
            $('pc-bad-wrap').classList.toggle('hidden', bad.length === 0);
            $('pc-result').classList.remove('hidden');
        }

        async function readFile(file) {
            $('pc-reading').classList.remove('hidden');
            try {
                if (/\.xlsx?$/i.test(file.name)) {
                    if (!window.XLSX) await new Promise((resolve, reject) => {
                        const s = document.createElement('script');
                        s.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
                        s.onload = resolve; s.onerror = reject; document.head.appendChild(s);
                    });
                    const book = XLSX.read(await file.arrayBuffer(), { type: 'array' });
                    $('pc-input').value = book.SheetNames.map(n => XLSX.utils.sheet_to_csv(book.Sheets[n])).join('\n');
                } else {
                    $('pc-input').value = await file.text();
                }
                clean();
            } finally {
                $('pc-reading').classList.add('hidden');
            }
        }

        $('pc-clean').addEventListener('click', clean);
        $('pc-file').addEventListener('change', e => e.target.files[0] && readFile(e.target.files[0]));
        $('pc-copy').addEventListener('click', e => FT.copy($('pc-output').value, e.currentTarget));
        $('pc-download').addEventListener('click', () => FT.download('clean-numbers.csv', '﻿phone\n' + $('pc-output').value + '\n', 'text/csv;charset=utf-8'));
    });
</script>

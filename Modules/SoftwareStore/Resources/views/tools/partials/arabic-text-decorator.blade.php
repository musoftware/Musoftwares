@php $f = $copy['form']; @endphp
<label class="ft-label" for="dc-in">{{ $f['input'] }}</label>
<input id="dc-in" dir="auto" class="ft-input text-[16px]" maxlength="120" placeholder="{{ $f['placeholder'] }}">
<ul id="dc-list" class="mt-5 space-y-2"></ul>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        const SAMPLE = @json($f['sample']), COPY = @json(__('free_tools.ui.copy'));
        const ARABIC = /[ء-ي]/g, JOINING = /([بتثجحخسشصضطظعغفقكلمنهي])(?=[ء-ي])/g;
        const ALTERNATES = { ك: 'ڪ', ه: 'ھ', ف: 'ڤ', ق: 'ڨ', ت: 'ٺ', ج: 'چ', ر: 'ڔ', ل: 'ڵ', م: '۾', و: 'ۆ', ب: 'پ', ث: 'ٿ', د: 'ڌ', ن: 'ڼ', ع: 'ڠ', ص: 'ڝ' };
        const LATIN = { boldSans: [0x1D5D4, 0x1D5EE], script: [0x1D4D0, 0x1D4EA], gothic: [0x1D56C, 0x1D586], mono: [0x1D670, 0x1D68A], circled: [0x24B6, 0x24D0], wide: [0xFF21, 0xFF41] };

        const kashida = text => text.replace(JOINING, '$1ـ');
        const mark = symbol => text => text.replace(ARABIC, letter => letter + symbol);
        const alternate = text => text.replace(ARABIC, letter => ALTERNATES[letter] ?? letter);
        const shadda = mark('ّ'), tanween = mark('ً'), alef = mark('ٰ');
        const latin = ([upper, lower]) => text => text.replace(/[A-Za-z]/g, c => String.fromCodePoint(c < 'a' ? upper + c.charCodeAt(0) - 65 : lower + c.charCodeAt(0) - 97));
        const chain = (...steps) => text => steps.reduce((result, step) => step(result), text);
        const wrap = (open, close) => text => open + ' ' + text + ' ' + close;

        const STYLES = [
            chain(kashida, latin(LATIN.boldSans), wrap('꧁', '꧂')),
            chain(shadda, latin(LATIN.script)),
            chain(tanween, latin(LATIN.gothic)),
            chain(alternate, latin(LATIN.mono)),
            chain(alternate, kashida, latin(LATIN.circled), wrap('『', '』')),
            chain(alef, latin(LATIN.wide)),
            chain(kashida, shadda, latin(LATIN.script), wrap('༺', '༻')),
            chain(shadda, latin(LATIN.boldSans), wrap('﴿', '﴾')),
            chain(kashida, latin(LATIN.gothic), wrap('《', '》')),
            chain(alternate, tanween, latin(LATIN.circled)),
            chain(alternate, latin(LATIN.script), wrap('᚛', '᚜')),
            chain(kashida, alef, latin(LATIN.mono), wrap('⟦', '⟧')),
        ];

        function row(text) {
            const item = document.createElement('li'), value = document.createElement('span'), button = document.createElement('button');
            item.className = 'flex items-center justify-between gap-3 bg-[#f5f5f7] rounded-xl p-3';
            value.className = 'min-w-0 break-words text-lg';
            value.dir = 'auto';
            value.textContent = text;
            button.type = 'button';
            button.className = 'ft-btn !bg-white !text-[#1d1d1f] shrink-0';
            button.textContent = COPY;
            button.addEventListener('click', () => FT.copy(text, button));
            item.append(value, button);
            return item;
        }

        function update() {
            const text = $('dc-in').value.trim() || SAMPLE;
            $('dc-list').replaceChildren(...STYLES.map(style => row(style(text))));
        }

        $('dc-in').addEventListener('input', update);
        update();
    });
</script>

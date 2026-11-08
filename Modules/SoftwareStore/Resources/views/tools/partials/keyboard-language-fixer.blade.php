@php $f = $copy['form']; @endphp
<label class="ft-label" for="kb-in">{{ $f['input'] }}</label>
<textarea id="kb-in" rows="5" dir="auto" class="ft-input text-[16px] leading-[1.9]" placeholder="{{ $f['placeholder'] }}"></textarea>
<div class="mt-4">
    <label class="ft-label" for="kb-direction">{{ $f['direction'] }}</label>
    <select id="kb-direction" class="ft-input">
        <option value="auto">{{ $f['auto'] }}</option>
        <option value="ar">{{ $f['to_arabic'] }}</option>
        <option value="en">{{ $f['to_english'] }}</option>
    </select>
</div>
<label class="ft-label mt-4" for="kb-out">{{ $f['output'] }}</label>
<textarea id="kb-out" rows="5" dir="auto" readonly class="ft-input text-[16px] leading-[1.9] !bg-[#f5f5f7]"></textarea>
<div class="mt-3 flex flex-wrap gap-2">
    <button type="button" id="kb-copy" class="ft-btn">{{ __('free_tools.ui.copy') }}</button>
    <button type="button" id="kb-clear" class="ft-btn ft-btn-light">{{ __('free_tools.ui.clear') }}</button>
</div>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        const KEYS = "qwertyuiop[]asdfghjkl;'zxcvnm,./`", LETTERS = 'ضصثقفغعهخحجدشسيبلاتنمكطئءؤرىةوزظذ';
        const BASE = Object.fromEntries([...KEYS].map((key, index) => [key, [...LETTERS][index]]));
        const SHIFTED = { b: 'لا', T: 'لإ', G: 'لأ', B: 'لآ', H: 'أ', Y: 'إ', N: 'آ', K: '،', P: '؛', '?': '؟', J: 'ـ', Q: 'َ', W: 'ً', E: 'ُ', R: 'ٌ', A: 'ِ', S: 'ٍ', X: 'ْ', '~': 'ّ' };
        const TO_ARABIC = { ...BASE, ...SHIFTED };
        const TO_ENGLISH = Object.fromEntries(Object.entries(TO_ARABIC).map(([key, letter]) => [letter, key]));
        const swap = (text, table, pattern) => text.replace(pattern, character => table[character] ?? character);
        const toArabic = text => swap(text, TO_ARABIC, /./gsu);
        const toEnglish = text => swap(text, TO_ENGLISH, /لا|لإ|لأ|لآ|./gsu);
        const countOf = (text, pattern) => (text.match(pattern) || []).length;
        const looksArabic = text => countOf(text, /[؀-ۿ]/g) > countOf(text, /[A-Za-z]/g);

        function targetLanguage(text) {
            const chosen = $('kb-direction').value;
            if (chosen !== 'auto') return chosen;
            return looksArabic(text) ? 'en' : 'ar';
        }

        function update() {
            const text = $('kb-in').value;
            $('kb-out').value = targetLanguage(text) === 'ar' ? toArabic(text) : toEnglish(text);
        }

        ['kb-in', 'kb-direction'].forEach(id => $(id).addEventListener('input', update));
        $('kb-copy').addEventListener('click', e => FT.copy($('kb-out').value, e.currentTarget));
        $('kb-clear').addEventListener('click', () => { $('kb-in').value = ''; update(); });
    });
</script>

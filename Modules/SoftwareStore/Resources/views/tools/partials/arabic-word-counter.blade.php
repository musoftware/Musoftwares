@php $f = $copy['form']; @endphp
<label class="ft-label" for="wc-text">{{ $f['text'] }}</label>
<textarea id="wc-text" rows="8" dir="auto" class="ft-input text-[16px] leading-[1.9]" placeholder="{{ $f['placeholder'] }}"></textarea>
<div class="mt-3 flex flex-wrap gap-2">
    <button type="button" id="wc-strip" class="ft-btn ft-btn-light">{{ $f['remove_marks'] }}</button>
    <button type="button" id="wc-copy" class="ft-btn ft-btn-light">{{ __('free_tools.ui.copy') }}</button>
    <button type="button" id="wc-clear" class="ft-btn ft-btn-light">{{ __('free_tools.ui.clear') }}</button>
</div>
<dl class="mt-6 grid grid-cols-2 md:grid-cols-3 gap-3">
    @foreach (['words', 'chars', 'chars_no_spaces', 'sentences', 'paragraphs', 'minutes'] as $stat)
        <div class="ft-stat"><dt>{{ $f[$stat] }}</dt><dd id="wc-{{ $stat }}">0</dd></div>
    @endforeach
</dl>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id), input = $('wc-text');
        const WORDS_PER_MINUTE = 180, MARKS = /[ً-ٰٟ]/g;
        const count = (text, pattern) => text.trim() ? text.split(pattern).filter(part => part.trim()).length : 0;
        function update() {
            const text = input.value, words = count(text, /\s+/);
            $('wc-words').textContent = FT.number(words, 0);
            $('wc-chars').textContent = FT.number([...text].length, 0);
            $('wc-chars_no_spaces').textContent = FT.number([...text.replace(/\s/g, '')].length, 0);
            $('wc-sentences').textContent = FT.number(count(text, /[.!?؟…]+/), 0);
            $('wc-paragraphs').textContent = FT.number(count(text, /\n+/), 0);
            $('wc-minutes').textContent = words ? Math.max(1, Math.ceil(words / WORDS_PER_MINUTE)) : 0;
        }
        input.addEventListener('input', update);
        $('wc-strip').addEventListener('click', () => { input.value = input.value.replace(MARKS, ''); update(); });
        $('wc-copy').addEventListener('click', e => FT.copy(input.value, e.currentTarget));
        $('wc-clear').addEventListener('click', () => { input.value = ''; update(); });
        update();
    });
</script>

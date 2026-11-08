@php $f = $copy['form']; @endphp
<label class="ft-label" for="fr-in">{{ $f['input'] }}</label>
<textarea id="fr-in" rows="5" dir="ltr" class="ft-input text-[16px] leading-[1.9]" placeholder="{{ $f['placeholder'] }}"></textarea>
<label class="ft-label mt-4" for="fr-out">{{ $f['output'] }}</label>
<textarea id="fr-out" rows="5" dir="rtl" class="ft-input text-[16px] leading-[1.9]"></textarea>
<p class="mt-2 text-xs text-[#1d1d1f]/60">{{ $f['hint'] }}</p>
<div class="mt-3 flex flex-wrap gap-2">
    <button type="button" id="fr-copy" class="ft-btn">{{ __('free_tools.ui.copy') }}</button>
    <button type="button" id="fr-clear" class="ft-btn ft-btn-light">{{ __('free_tools.ui.clear') }}</button>
</div>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        const WORDS = { mahmoud: 'محمود', mahmod: 'محمود', ahmed: 'أحمد', ahmad: 'أحمد', mohamed: 'محمد', mohammed: 'محمد', muhammad: 'محمد', hassan: 'حسن', hasan: 'حسن', hussein: 'حسين', hossam: 'حسام', ali: 'علي', omar: 'عمر', khaled: 'خالد', mostafa: 'مصطفى', moustafa: 'مصطفى', amin: 'أمين', ibrahim: 'إبراهيم', youssef: 'يوسف', yousef: 'يوسف', sara: 'سارة', fatma: 'فاطمة', mona: 'منى', nour: 'نور', mariam: 'مريم', maryam: 'مريم', aya: 'آية', hend: 'هند', ana: 'أنا', enta: 'إنت', enti: 'إنتي', ento: 'إنتو', e7na: 'إحنا', howa: 'هو', heya: 'هي', homa: 'هما', el: 'ال', w: 'و', we: 'و', wa: 'و', f: 'في', fe: 'في', fi: 'في', mn: 'من', men: 'من', min: 'من', '3la': 'على', '3ala': 'على', ala: 'على', ya: 'يا', la: 'لا', la2: 'لأ', aywa: 'أيوه', aiwa: 'أيوه', ah: 'آه', mesh: 'مش', mish: 'مش', msh: 'مش', ezayak: 'إزيك', ezayek: 'إزيك', izayak: 'إزيك', '3amel': 'عامل', '3amla': 'عاملة', kwayes: 'كويس', kwayesa: 'كويسة', tamam: 'تمام', shokran: 'شكرا', sho8l: 'شغل', '7abibi': 'حبيبي', '7abibty': 'حبيبتي', bet: 'بيت', beit: 'بيت', kol: 'كل', kolo: 'كله', kda: 'كده', keda: 'كده', kida: 'كده', dah: 'ده', da: 'ده', de: 'دي', di: 'دي', eh: 'إيه', leh: 'ليه', fen: 'فين', feen: 'فين', emta: 'إمتى', meen: 'مين', '2ahwa': 'قهوة', '2alb': 'قلب', '2oly': 'قولي', bokra: 'بكرة', delwa2ty: 'دلوقتي', dlwa2ty: 'دلوقتي', ne3mel: 'نعمل', '3ayez': 'عايز', '3ayza': 'عايزة', '3awz': 'عاوز', '3awza': 'عاوزة', yalla: 'يلا', inshallah: 'إن شاء الله', ensha2allah: 'إن شاء الله', alsalam: 'السلام', salam: 'سلام', '3alykom': 'عليكم', '3alaykom': 'عليكم', sabah: 'صباح', el5eir: 'الخير', masa2: 'مساء', rabena: 'ربنا', allah: 'الله' };
        const LETTERS = { kh: 'خ', sh: 'ش', ch: 'ش', gh: 'غ', th: 'ث', dh: 'ذ', "3'": 'غ', "7'": 'خ', ou: 'و', oo: 'و', ee: 'ي', ii: 'ي', ei: 'ي', ai: 'ي', aa: 'ا', b: 'ب', p: 'ب', t: 'ت', g: 'ج', j: 'ج', d: 'د', r: 'ر', z: 'ز', s: 'س', f: 'ف', v: 'ف', q: 'ق', k: 'ك', c: 'ك', l: 'ل', m: 'م', n: 'ن', h: 'ه', w: 'و', y: 'ي', x: 'كس', 3: 'ع', 4: 'ش', 5: 'خ', 6: 'ط', 7: 'ح', 8: 'غ', 9: 'ق' };
        const FIRST = { a: 'أ', e: 'إ', i: 'إ', o: 'أ', u: 'أ', 2: 'أ' };
        const LAST = { a: 'ا', e: 'ه', i: 'ي', o: 'و', u: 'و', 2: 'ء' };
        const CLOSING = { a: 'ا', i: 'ي', o: 'و', u: 'و', 2: 'ء' };
        const SOUNDS = /kh|sh|ch|gh|th|dh|3'|7'|ou|oo|ee|ii|ei|ai|aa|[a-z0-9]/g;
        const SHORT = new Set(['a', 'e', 'i', 'o', 'u', '2']), known = word => Object.hasOwn(WORDS, word) ? WORDS[word] : null;
        const isShort = sound => SHORT.has(sound);

        function shortSound(sound, index, sounds) {
            if (index === 0) return FIRST[sound];
            if (index === sounds.length - 1) return LAST[sound];
            const closesWord = index === sounds.length - 2 && !isShort(sounds[index + 1]);
            return closesWord ? CLOSING[sound] ?? '' : sound === '2' ? 'ء' : '';
        }

        function spell(word) {
            const sounds = word.match(SOUNDS) || [];
            const letters = sounds.map((sound, index, all) => isShort(sound) ? shortSound(sound, index, all) : LETTERS[sound] ?? sound);
            return letters.join('').replace(/([ء-ي])\1+/g, '$1');
        }

        function convertWord(raw) {
            const word = raw.toLowerCase();
            if (/^\d+$/.test(word)) return raw;
            if (known(word)) return known(word);
            return word.length > 3 && word.startsWith('el') ? 'ال' + (known(word.slice(2)) ?? spell(word.slice(2))) : spell(word);
        }

        const update = () => { $('fr-out').value = $('fr-in').value.replace(/\bel[- ](?=[a-z0-9])/gi, 'el').replace(/[a-z0-9']+/gi, convertWord); };
        $('fr-in').addEventListener('input', update);
        $('fr-copy').addEventListener('click', e => FT.copy($('fr-out').value, e.currentTarget));
        $('fr-clear').addEventListener('click', () => { $('fr-in').value = ''; update(); });
    });
</script>

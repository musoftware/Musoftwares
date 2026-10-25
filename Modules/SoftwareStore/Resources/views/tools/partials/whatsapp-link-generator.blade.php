@php
    $f = $copy['form'];
    $ar = app()->getLocale() === 'ar';
    $countries = [
        ['20', 'مصر', 'Egypt'], ['966', 'السعودية', 'Saudi Arabia'], ['971', 'الإمارات', 'United Arab Emirates'], ['965', 'الكويت', 'Kuwait'],
        ['974', 'قطر', 'Qatar'], ['973', 'البحرين', 'Bahrain'], ['968', 'عُمان', 'Oman'], ['962', 'الأردن', 'Jordan'], ['218', 'ليبيا', 'Libya'],
        ['964', 'العراق', 'Iraq'], ['212', 'المغرب', 'Morocco'], ['216', 'تونس', 'Tunisia'], ['213', 'الجزائر', 'Algeria'], ['249', 'السودان', 'Sudan'],
        ['1', 'الولايات المتحدة وكندا', 'United States and Canada'], ['44', 'المملكة المتحدة', 'United Kingdom'],
    ];
@endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <form id="wa-form" class="space-y-4" onsubmit="return false">
        <div class="grid grid-cols-5 gap-3">
            <div class="col-span-2">
                <label class="ft-label" for="wa-country">{{ $f['country'] }}</label>
                <select id="wa-country" class="ft-input">
                    @foreach ($countries as [$code, $nameAr, $nameEn])
                        <option value="{{ $code }}">{{ $ar ? $nameAr : $nameEn }} (+{{ $code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-3">
                <label class="ft-label" for="wa-phone">{{ $f['phone'] }}</label>
                <input id="wa-phone" class="ft-input" type="tel" inputmode="tel" dir="ltr" placeholder="1012345678" autocomplete="tel">
            </div>
        </div>
        <p class="text-xs text-[#1d1d1f]/50 -mt-2">{{ $f['phone_hint'] }}</p>
        <div>
            <label class="ft-label" for="wa-message">{{ $f['message'] }}</label>
            <textarea id="wa-message" class="ft-input" rows="4">{{ $f['message_default'] }}</textarea>
        </div>
        <p id="wa-error" class="hidden text-sm text-red-600">{{ $f['invalid'] }}</p>
    </form>

    <div class="flex flex-col items-center gap-4">
        <div class="w-full">
            <span class="ft-label">{{ $f['result'] }}</span>
            <div class="flex gap-2">
                <input id="wa-link" class="ft-input font-mono text-sm" dir="ltr" readonly value="">
                <button type="button" id="wa-copy" class="ft-btn shrink-0">{{ __('free_tools.ui.copy') }}</button>
            </div>
        </div>
        <canvas id="wa-qr" class="w-56 h-56 rounded-xl border border-black/5" aria-label="QR"></canvas>
        <div class="flex flex-wrap justify-center gap-2">
            <button type="button" id="wa-download" class="ft-btn ft-btn-light"><i class="ri-download-2-line" aria-hidden="true"></i>{{ __('free_tools.ui.download_png') }}</button>
            <a id="wa-open" href="#" target="_blank" rel="noopener noreferrer" class="ft-btn ft-btn-light"><i class="ri-external-link-line" aria-hidden="true"></i>{{ $f['open'] }}</a>
        </div>
    </div>
</div>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        function build() {
            let phone = $('wa-phone').value.replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/\D/g, '');
            const country = $('wa-country').value;
            if (phone.startsWith('00' + country)) phone = phone.slice(2 + country.length);
            else if (phone.startsWith(country) && phone.length > 10) phone = phone.slice(country.length);
            phone = phone.replace(/^0+/, '');
            const valid = phone.length >= 6 && phone.length <= 13;
            $('wa-error').classList.toggle('hidden', valid || phone === '');
            const message = $('wa-message').value.trim();
            const link = valid ? 'https://wa.me/' + country + phone + (message ? '?text=' + encodeURIComponent(message) : '') : '';
            $('wa-link').value = link;
            $('wa-open').href = link || '#';
            FT.qr($('wa-qr'), link || 'https://wa.me/', 280);
        }
        ['wa-phone', 'wa-message', 'wa-country'].forEach(id => $(id).addEventListener('input', build));
        $('wa-copy').addEventListener('click', e => $('wa-link').value && FT.copy($('wa-link').value, e.currentTarget));
        $('wa-download').addEventListener('click', () => $('wa-qr').toBlob(blob => FT.download('whatsapp-qr.png', blob)));
        build();
    });
</script>

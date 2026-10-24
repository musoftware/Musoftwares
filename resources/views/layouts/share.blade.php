{{-- Share links for a product or an article. Needs $shareUrl (page link), $shareTitle and $shareCampaign (slug used in utm_campaign). --}}
@php
    $isAr = app()->getLocale() === 'ar';
    $t = fn (string $ar, string $en) => $isAr ? $ar : $en;
    $tracked = fn (string $channel) => \App\Support\UtmLink::make($shareUrl, $channel, $shareCampaign);
    $text = rawurlencode($shareTitle);
    $shareLinks = [
        [$t('واتساب', 'WhatsApp'), 'https://wa.me/?text='.rawurlencode($shareTitle.' '.$tracked('whatsapp'))],
        [$t('فيسبوك', 'Facebook'), 'https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($tracked('facebook'))],
        [$t('لينكدإن', 'LinkedIn'), 'https://www.linkedin.com/sharing/share-offsite/?url='.rawurlencode($tracked('linkedin'))],
        ['X', 'https://twitter.com/intent/tweet?text='.$text.'&url='.rawurlencode($tracked('x'))],
    ];
@endphp
<div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-[14px]" data-share>
    <span class="font-semibold text-[#515154]">{{ $t('شارك:', 'Share:') }}</span>
    @foreach ($shareLinks as [$label, $href])
        <a href="{{ $href }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-[#0071e3] hover:underline">{{ $label }}</a>
    @endforeach
    <button type="button" class="font-semibold text-[#0071e3] hover:underline" data-copy-link="{{ $tracked('copy') }}" data-copied-text="{{ $t('اتنسخ', 'Copied') }}">{{ $t('انسخ اللينك', 'Copy link') }}</button>
</div>
<script>
    (function () {
        document.querySelectorAll('[data-copy-link]').forEach(function (button) {
            if (button.dataset.bound) return;
            button.dataset.bound = '1';
            button.addEventListener('click', function () {
                var link = button.getAttribute('data-copy-link');
                var original = button.textContent;
                var done = function () { button.textContent = button.getAttribute('data-copied-text'); setTimeout(function () { button.textContent = original; }, 1800); };
                if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(link).then(done); return; }
                window.prompt('', link);
            });
        });
    })();
</script>

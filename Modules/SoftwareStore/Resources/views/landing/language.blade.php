@php $current = app()->getLocale() === 'ar' ? 'ar' : 'en'; @endphp
<div class="w-full bg-white border-b border-black/5">
    <div class="max-w-[1100px] mx-auto px-4 sm:px-6 py-2 flex items-center justify-end gap-3 text-sm" aria-label="{{ __('software.language') }}">
        <a href="{{ request()->fullUrlWithQuery(['lang' => 'ar']) }}" hreflang="ar" lang="ar"
           class="px-2 py-1 rounded {{ $current === 'ar' ? 'font-bold text-[#0071e3]' : 'text-black/60 hover:text-black' }}"
           @if ($current === 'ar') aria-current="true" @endif>العربية</a>
        <span class="text-black/20" aria-hidden="true">|</span>
        <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}" hreflang="en" lang="en"
           class="px-2 py-1 rounded {{ $current === 'en' ? 'font-bold text-[#0071e3]' : 'text-black/60 hover:text-black' }}"
           @if ($current === 'en') aria-current="true" @endif>English</a>
    </div>
</div>

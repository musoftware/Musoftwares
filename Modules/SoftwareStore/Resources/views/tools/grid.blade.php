{{-- Cards for the free tools; used on /services and under each tool. --}}
<div class="mt-5 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
    @foreach (\Modules\SoftwareStore\Services\FreeTools::slugs() as $toolSlug)
        @continue(isset($except) && $except === $toolSlug)
        <a href="{{ \Modules\SoftwareStore\Services\FreeTools::url($toolSlug) }}" class="bg-white border border-black/5 rounded-[20px] shadow-sm hover:shadow-md transition-shadow p-5 flex gap-4">
            <span class="shrink-0 w-11 h-11 rounded-2xl bg-[#0071e3]/10 text-[#0071e3] flex items-center justify-center text-xl">
                <i class="{{ \Modules\SoftwareStore\Services\FreeTools::icon($toolSlug) }}" aria-hidden="true"></i>
            </span>
            <span class="min-w-0">
                <span class="flex items-center gap-2 font-bold">{{ __("free_tools.tools.{$toolSlug}.name") }}
                    <span class="rounded-full bg-emerald-50 text-emerald-700 px-2 py-0.5 text-[11px] font-semibold">{{ __('free_tools.section.free') }}</span>
                </span>
                <span class="mt-1 block text-sm text-[#1d1d1f]/60">{{ __("free_tools.tools.{$toolSlug}.tagline") }}</span>
            </span>
        </a>
    @endforeach
</div>

{{-- One footer for every public page. Content lives in App\Support\SiteNav::footer();
     resources/js/Components/Public/SiteFooter.tsx renders the same markup for React pages. --}}
@php($footer = \App\Support\SiteNav::footer())
<footer class="w-full bg-[#f5f5f7] border-t border-black/[0.08] pt-12 pb-10 px-6 sm:px-12 text-[#515154] text-[14px]">
    <div class="max-w-[1100px] mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
            <div class="lg:col-span-4">
                <p class="text-[18px] font-bold text-[#1d1d1f]">{{ $footer['brand'] }}</p>
                <p class="mt-3 leading-[1.9]">{{ $footer['tagline'] }}</p>
                <div class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 text-[13px]">
                    @foreach ($footer['social'] as $social)
                        <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer" class="hover:text-[#0071e3] transition-colors">{{ $social['label'] }}</a>
                    @endforeach
                </div>
            </div>
            <div class="lg:col-span-8 grid grid-cols-2 md:grid-cols-4 gap-8">
                @foreach ($footer['columns'] as $column)
                    <div>
                        <h3 class="text-[14px] font-semibold text-[#1d1d1f]">{{ $column['title'] }}</h3>
                        <ul class="mt-3 space-y-2.5">
                            @foreach ($column['links'] as $link)
                                <li><a href="{{ $link['url'] }}" @if ($link['external']) target="_blank" rel="noopener noreferrer" @endif class="hover:text-[#0071e3] transition-colors break-words">{{ $link['label'] }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-10 pt-6 border-t border-black/10 flex flex-col md:flex-row items-center justify-between gap-3 text-[13px] text-[#86868b]">
            <p>{{ $footer['copyright'] }}</p>
            <p><a href="{{ $footer['language_switch']['url'] }}" class="hover:text-[#1d1d1f] transition-colors">{{ $footer['language_switch']['label'] }}</a></p>
        </div>
    </div>
</footer>
@include('layouts.site-mobile-actions')

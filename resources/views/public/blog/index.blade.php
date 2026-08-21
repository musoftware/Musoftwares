@extends('layouts.public')

@section('content')
<div class="w-full bg-[#fbfbfd] text-[#1d1d1f] font-sans selection:bg-[#0071e3]/20 selection:text-[#0071e3] pt-16 sm:pt-24 pb-24 sm:pb-36">
    
    <!-- Hero Header -->
    <div class="max-w-4xl mx-auto px-6 text-center space-y-4 mb-16">
        <span class="inline-block px-3.5 py-1 bg-[#0071e3]/10 border border-[#0071e3]/20 text-[#0071e3] text-xs font-semibold uppercase tracking-wider rounded-full">
            Engineering Insights & Articles
        </span>
        <h1 class="text-3xl sm:text-5xl font-bold text-[#1d1d1f] font-sans tracking-tight">
            Musoftwares Technical Library
        </h1>
        <p class="text-sm sm:text-base text-[#1d1d1f]/60 font-sans leading-relaxed max-w-2xl mx-auto">
            Deep dives into enterprise ERP engineering, distributed systems, WhatsApp APIs, and scalable web architecture.
        </p>
    </div>

    <!-- Search / Filter Bar -->
    <div class="max-w-2xl mx-auto px-6 mb-16">
        <form method="GET" action="/blog" class="flex gap-2">
            <input 
                type="text" 
                name="search" 
                value="{{ request('search') }}" 
                placeholder="Search technical insights, articles, and architectures..." 
                class="flex-1 bg-white border border-black/10 rounded-full px-5 py-3 text-xs font-sans text-[#1d1d1f] shadow-sm focus:outline-none focus:border-[#0071e3]"
            >
            <button type="submit" class="px-6 py-3 bg-[#0071e3] hover:bg-[#0077ed] text-white font-semibold text-xs rounded-full uppercase tracking-wider shadow-sm transition-all">
                Search
            </button>
        </form>
    </div>

    <!-- Articles Grid -->
    <div class="max-w-[1400px] mx-auto px-6 sm:px-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            
            @forelse($articles as $article)
                <article class="bg-white border border-black/5 rounded-[24px] shadow-sm overflow-hidden group hover:shadow-xl hover:border-black/10 transition-all flex flex-col justify-between">
                    <div>
                        @if($article->featured_image)
                            <div class="h-48 bg-zinc-100 overflow-hidden">
                                <img src="{{ $article->featured_image }}" alt="{{ $article->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            </div>
                        @endif
                        <div class="p-6 space-y-3">
                            <div class="text-[11px] font-semibold text-[#0071e3] uppercase tracking-wider">
                                {{ $article->published_at ? $article->published_at->format('M d, Y') : 'Published' }}
                            </div>
                            <h2 class="text-lg font-bold text-[#1d1d1f] font-sans group-hover:text-[#0071e3] transition-colors">
                                <a href="/blog/{{ $article->slug }}">{{ $article->title }}</a>
                            </h2>
                            <p class="text-xs text-[#1d1d1f]/60 leading-relaxed font-sans line-clamp-3">
                                {{ $article->excerpt }}
                            </p>
                        </div>
                    </div>

                    <div class="p-6 pt-0">
                        <div class="pt-4 border-t border-black/5 flex justify-between items-center text-xs font-sans">
                            <span class="text-[#1d1d1f]/40">Technical Analysis</span>
                            <a href="/blog/{{ $article->slug }}" class="text-[#0071e3] hover:underline font-semibold flex items-center gap-1">
                                <span>Read Full Article</span> ➔
                            </a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full py-16 text-center text-[#1d1d1f]/40 font-sans text-xs">
                    No published articles found matching your query.
                </div>
            @endforelse

        </div>

        @if($articles->hasPages())
            <div class="mt-16 flex justify-center">
                {{ $articles->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

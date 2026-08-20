@extends('layouts.public')

@section('content')
<div class="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white pt-16 sm:pt-24 pb-24 sm:pb-36">
    
    <!-- Hero Header -->
    <div class="max-w-4xl mx-auto px-6 text-center space-y-4 mb-16">
        <span class="inline-block px-3 py-1 bg-[#1E2619] border border-[#748660]/40 text-[#748660] text-xs font-mono font-bold uppercase tracking-wider">
            Engineering Insights & Articles
        </span>
        <h1 class="text-3xl sm:text-5xl font-bold text-white font-sans tracking-tight">
            Musoftwares Technical Library
        </h1>
        <p class="text-sm sm:text-base text-zinc-400 font-sans leading-relaxed max-w-2xl mx-auto">
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
                class="flex-1 bg-[#161616] border border-[#262626] px-4 py-3 text-xs font-mono text-white focus:outline-none focus:border-[#748660]"
            >
            <button type="submit" class="px-6 py-3 bg-[#748660] text-[#0F140A] font-bold text-xs font-mono uppercase tracking-wider">
                Search
            </button>
        </form>
    </div>

    <!-- Articles Grid -->
    <div class="max-w-[1400px] mx-auto px-6 sm:px-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            
            @forelse($articles as $article)
                <article class="bg-[#161616] border border-[#262626] overflow-hidden group hover:border-[#748660] transition-colors flex flex-col justify-between">
                    <div>
                        @if($article->featured_image)
                            <div class="h-48 bg-zinc-900 overflow-hidden">
                                <img src="{{ $article->featured_image }}" alt="{{ $article->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            </div>
                        @endif
                        <div class="p-6 space-y-3">
                            <div class="text-[10px] font-mono text-[#748660] font-bold uppercase">
                                {{ $article->published_at ? $article->published_at->format('M d, Y') : 'Published' }}
                            </div>
                            <h2 class="text-lg font-bold text-white font-sans group-hover:text-[#748660] transition-colors">
                                <a href="/blog/{{ $article->slug }}">{{ $article->title }}</a>
                            </h2>
                            <p class="text-xs text-zinc-400 leading-relaxed font-sans line-clamp-3">
                                {{ $article->excerpt }}
                            </p>
                        </div>
                    </div>

                    <div class="p-6 pt-0">
                        <div class="pt-4 border-t border-[#222222] flex justify-between items-center text-xs font-mono">
                            <span class="text-zinc-500">Technical Analysis</span>
                            <a href="/blog/{{ $article->slug }}" class="text-[#748660] hover:text-white font-bold flex items-center gap-1">
                                <span>Read Full Article</span> ➔
                            </a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full py-16 text-center text-zinc-500 font-mono text-xs">
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

@extends('digitalproducts::layouts.library-master')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <!-- Hero Header -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-b from-brand-950/40 via-dark-800 to-dark-900 border border-brand-500/20 p-8 sm:p-12 mb-12 text-center shadow-2xl">
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-brand-600/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 max-w-3xl mx-auto">
            <span class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-brand-500/10 border border-brand-500/20 text-brand-400 text-xs font-semibold uppercase tracking-wider mb-4">
                <i class="ri-sparkling-fill"></i> إصدارات وأدلة حصرية
            </span>
            <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tight mb-4 leading-tight">
                المكتبة الرقمية للكتب والأدلة التطبيقية
            </h1>
            <p class="text-base sm:text-lg text-zinc-400 mb-8 leading-relaxed">
                اكتشف وحمّل أفضل الكتب الرقمية والأدلة العملية في البرمجة، إدارة الأعمال، الذكاء الاصطناعي والتسويق الرقمي بجودة فائقة وروابط مباشرة.
            </p>

            <!-- Search Form -->
            <form action="{{ route('library.index') }}" method="GET" class="relative max-w-xl mx-auto">
                @if(request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                @if(request('type'))
                    <input type="hidden" name="type" value="{{ request('type') }}">
                @endif
                <div class="relative flex items-center">
                    <i class="ri-search-2-line absolute right-4 text-zinc-400 text-lg"></i>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="ابحث عن كتاب، مؤلف، أو موضوع..." class="w-full h-14 pr-12 pl-28 rounded-2xl bg-zinc-900/90 border border-zinc-700/80 text-white placeholder-zinc-500 text-sm focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 transition-all shadow-inner">
                    <button type="submit" class="absolute left-2 px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold transition-all shadow-md shadow-brand-500/25">
                        بحث
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Filters & Categories Bar -->
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 mb-8 pb-6 border-b border-zinc-800/80">
        <!-- Categories Pills -->
        <div class="flex items-center gap-2 overflow-x-auto w-full lg:w-auto pb-2 lg:pb-0 scrollbar-none">
            <a href="{{ route('library.index', array_merge(request()->except(['category', 'page']))) }}" class="px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all {{ !request('category') ? 'bg-brand-600 text-white shadow-lg shadow-brand-500/20' : 'bg-zinc-800/70 text-zinc-400 hover:text-white hover:bg-zinc-800 border border-zinc-700/40' }}">
                الكل ({{ $products->total() }})
            </a>
            @foreach($categories as $category)
                <a href="{{ route('library.index', array_merge(request()->except(['page']), ['category' => $category->slug])) }}" class="px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all {{ request('category') === $category->slug ? 'bg-brand-600 text-white shadow-lg shadow-brand-500/20' : 'bg-zinc-800/70 text-zinc-400 hover:text-white hover:bg-zinc-800 border border-zinc-700/40' }}">
                    {{ $category->name }}
                    <span class="text-[10px] opacity-75 font-normal">({{ $category->published_products_count }})</span>
                </a>
            @endforeach
        </div>

        <!-- Type & Sort Options -->
        <div class="flex items-center gap-3 w-full lg:w-auto justify-between lg:justify-end">
            <!-- Free / Paid Toggle -->
            <div class="inline-flex p-1 rounded-xl bg-zinc-900 border border-zinc-800">
                <a href="{{ route('library.index', array_merge(request()->except(['type', 'page']))) }}" class="px-3 py-1 rounded-lg text-xs font-medium transition-colors {{ !request('type') ? 'bg-zinc-800 text-white' : 'text-zinc-400 hover:text-white' }}">
                    الكل
                </a>
                <a href="{{ route('library.index', array_merge(request()->except(['page']), ['type' => 'free'])) }}" class="px-3 py-1 rounded-lg text-xs font-medium transition-colors {{ request('type') === 'free' ? 'bg-emerald-600 text-white' : 'text-zinc-400 hover:text-emerald-400' }}">
                    مجاني
                </a>
                <a href="{{ route('library.index', array_merge(request()->except(['page']), ['type' => 'paid'])) }}" class="px-3 py-1 rounded-lg text-xs font-medium transition-colors {{ request('type') === 'paid' ? 'bg-amber-600 text-white' : 'text-zinc-400 hover:text-amber-400' }}">
                    مدفوع
                </a>
            </div>

            <!-- Sort Dropdown -->
            <form action="{{ route('library.index') }}" method="GET" id="sortForm">
                @foreach(request()->except(['sort', 'page']) as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <select name="sort" onchange="document.getElementById('sortForm').submit()" class="h-9 px-3 rounded-xl bg-zinc-900 border border-zinc-800 text-xs text-zinc-300 focus:outline-none focus:border-zinc-700">
                    <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>الأحدث</option>
                    <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>الأكثر تحميلاً</option>
                    <option value="views" {{ request('sort') == 'views' ? 'selected' : '' }}>الأكثر مشاهدة</option>
                    <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>السعر: الأقل للأعلى</option>
                    <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>السعر: الأعلى للأقل</option>
                </select>
            </form>
        </div>
    </div>

    <!-- Books Grid -->
    @if($products->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($products as $book)
                <div class="group relative rounded-2xl glass-card overflow-hidden border border-zinc-800 hover:border-zinc-700 flex flex-col book-hover transition-all duration-300">
                    
                    <!-- Cover Image Container -->
                    <a href="{{ route('library.show', $book->slug) }}" class="block relative aspect-[3/4] w-full overflow-hidden bg-zinc-950 p-4 flex items-center justify-center">
                        @if($book->cover_image_path)
                            <img src="{{ $book->cover_url }}" alt="{{ $book->title }}" class="w-full h-full object-contain rounded-lg book-shadow group-hover:scale-105 transition-transform duration-300">
                        @else
                            <div class="w-full h-full rounded-lg bg-gradient-to-br from-zinc-800 via-zinc-900 to-dark-900 border border-zinc-700/60 p-6 flex flex-col justify-between book-shadow">
                                <i class="ri-book-2-line text-4xl text-brand-400"></i>
                                <div>
                                    <span class="text-xs text-zinc-500 font-medium">{{ $book->category?->name ?? 'كتاب إلكتروني' }}</span>
                                    <h3 class="text-sm font-bold text-white line-clamp-3 mt-1">{{ $book->title }}</h3>
                                </div>
                                <span class="text-[11px] text-zinc-400 font-mono">{{ $book->author_name ?? 'Musoftware' }}</span>
                            </div>
                        @endif

                        <!-- Badge Top Right -->
                        <div class="absolute top-3 right-3">
                            @if($book->is_free)
                                <span class="px-2.5 py-1 rounded-lg bg-emerald-500/90 backdrop-blur-md text-white text-[11px] font-bold shadow-md flex items-center gap-1">
                                    <i class="ri-gift-line"></i> مجاناً
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-lg bg-brand-600/90 backdrop-blur-md text-white text-[11px] font-bold shadow-md">
                                    {{ $book->formatted_price }}
                                </span>
                            @endif
                        </div>

                        <!-- Pages count badge bottom left -->
                        @if($book->page_count)
                            <div class="absolute bottom-3 left-3 px-2 py-0.5 rounded-md bg-black/70 backdrop-blur-md text-zinc-300 text-[10px] font-medium border border-white/10 flex items-center gap-1">
                                <i class="ri-pages-line"></i> {{ $book->page_count }} صفحة
                            </div>
                        @endif
                    </a>

                    <!-- Card Body -->
                    <div class="p-5 flex-1 flex flex-col justify-between">
                        <div>
                            @if($book->category)
                                <a href="{{ route('library.index', ['category' => $book->category->slug]) }}" class="text-[11px] font-semibold text-brand-400 hover:text-brand-300 mb-1.5 inline-block">
                                    {{ $book->category->name }}
                                </a>
                            @endif

                            <h2 class="text-base font-bold text-white group-hover:text-brand-300 transition-colors line-clamp-2 leading-snug mb-2">
                                <a href="{{ route('library.show', $book->slug) }}">
                                    {{ $book->title }}
                                </a>
                            </h2>

                            @if($book->short_description)
                                <p class="text-xs text-zinc-400 line-clamp-2 leading-relaxed mb-4">
                                    {{ $book->short_description }}
                                </p>
                            @endif
                        </div>

                        <!-- Footer Info & CTA -->
                        <div class="pt-4 border-t border-zinc-800/80 flex items-center justify-between mt-auto">
                            <div class="flex items-center gap-1.5 text-xs text-zinc-400">
                                <i class="ri-download-2-line text-zinc-500"></i>
                                <span>{{ number_format($book->download_count) }}</span>
                            </div>

                            <a href="{{ route('library.show', $book->slug) }}" class="px-3.5 py-1.5 rounded-xl bg-zinc-800 hover:bg-brand-600 text-zinc-200 hover:text-white text-xs font-semibold transition-colors flex items-center gap-1">
                                <span>تفاصيل الكتاب</span>
                                <i class="ri-arrow-left-s-line"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-12">
            {{ $products->links() }}
        </div>
    @else
        <div class="text-center py-20 rounded-3xl glass-card border border-zinc-800 p-8">
            <div class="w-16 h-16 rounded-2xl bg-zinc-800 flex items-center justify-center text-zinc-500 mx-auto mb-4 text-3xl">
                <i class="ri-book-open-line"></i>
            </div>
            <h3 class="text-lg font-bold text-white mb-2">لم يتم العثور على كتب</h3>
            <p class="text-sm text-zinc-400 max-w-sm mx-auto mb-6">
                لا توجد كتب تطابق معايير البحث المحددة حالياً. جرّب تغيير الفلاتر أو البحث بكلمات أخرى.
            </p>
            <a href="{{ route('library.index') }}" class="px-5 py-2.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-white text-xs font-semibold transition-colors inline-block">
                عرض كل الكتب
            </a>
        </div>
    @endif

</div>
@endsection

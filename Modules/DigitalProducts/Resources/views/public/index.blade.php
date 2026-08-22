@extends('digitalproducts::layouts.library-master')

@section('content')
<div class="max-w-[1280px] mx-auto px-6 sm:px-10 py-8 sm:py-12">

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- 1. APPLE HERO SECTION (Library Header & Smart Search)                   -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <section class="rounded-[22px] bg-[#f5f5f7] p-8 sm:p-12 mb-10 overflow-hidden relative border border-black/5">
        <div class="max-w-[800px]">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white border border-black/5 text-[#0071e3] text-xs font-semibold uppercase tracking-wider mb-4 shadow-2xs">
                <i class="ri-book-open-line"></i>
                <span>{{ app()->getLocale() === 'ar' ? 'المكتبة الرقمية والأدلة الهندسية' : 'Digital Books & Engineering Playbooks' }}</span>
            </div>
            
            <h1 class="text-[32px] sm:text-[44px] lg:text-[50px] font-semibold tracking-[-0.03em] leading-[1.05] text-[#1d1d1f] mb-3">
                {{ app()->getLocale() === 'ar' ? 'أدلة هندسية وتطبيقية مباشرة.' : 'Engineering Playbooks & System Guides.' }}
            </h1>
            
            <p class="text-[16px] sm:text-[18px] text-[#86868b] leading-relaxed max-w-[640px] mb-8">
                {{ app()->getLocale() === 'ar' 
                    ? 'تصفح وحمّل أفضل الكتب الرقمية والأدلة العملية في معمارية البرمجيات، النظم السحابية، وإدارة المنتجات مجاناً أو مدفوعة بدون وسائط مشتتة.' 
                    : 'Download battle-tested architecture guides, full-stack playbooks, and production blueprints with zero fluff and instant delivery.' }}
            </p>

            <!-- Apple Search Pill -->
            <form action="{{ route('library.index') }}" method="GET" id="librarySearchForm" class="relative max-w-[560px]">
                @if(request('type'))
                    <input type="hidden" name="type" value="{{ request('type') }}">
                @endif
                @if(request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                @if(request('sort'))
                    <input type="hidden" name="sort" value="{{ request('sort') }}">
                @endif

                <div class="relative flex items-center shadow-sm rounded-full bg-white border border-black/10 focus-within:border-[#0071e3] transition-all">
                    <input 
                        type="text" 
                        name="q" 
                        value="{{ request('q') }}" 
                        placeholder="{{ app()->getLocale() === 'ar' ? 'ابحث عن كتاب، دليل تقني، أو موضوع...' : 'Search books, architecture guides, topics...' }}"
                        class="w-full h-12 {{ app()->getLocale() === 'ar' ? 'pr-5 pl-12' : 'pl-5 pr-12' }} rounded-full bg-transparent text-[#1d1d1f] placeholder-[#86868b] text-[14px] focus:outline-none"
                    >
                    <button type="submit" class="absolute {{ app()->getLocale() === 'ar' ? 'left-3' : 'right-3' }} w-8 h-8 rounded-full bg-[#0071e3] text-white hover:bg-[#0077ed] flex items-center justify-center transition-all shadow-xs" title="Search">
                        <i class="ri-search-2-line text-sm"></i>
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- 2. TYPE & CATEGORY TABS ROW (Apple Horizontal Pill Navigation)         -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="mb-8 space-y-4">
        
        <!-- Primary Type Selector Row (All vs Free vs Paid) -->
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div class="flex items-center gap-2 select-none overflow-x-auto pb-1 scrollbar-none">
                @php
                    $curType = request('type');
                    $isTypeAll = empty($curType);
                @endphp
                <a 
                    href="{{ route('library.index', array_merge(request()->except(['type', 'page']))) }}" 
                    class="px-4 py-2 rounded-full text-xs font-semibold whitespace-nowrap transition-all flex-shrink-0 {{ $isTypeAll ? 'bg-[#1d1d1f] text-white shadow-xs' : 'bg-[#f5f5f7] text-[#1d1d1f]/80 hover:bg-[#e8e8ed]' }}"
                >
                    {{ app()->getLocale() === 'ar' ? 'جميع الإصدارات' : 'All Books' }}
                </a>

                <a 
                    href="{{ route('library.index', array_merge(request()->except(['page']), ['type' => 'free'])) }}" 
                    class="px-4 py-2 rounded-full text-xs font-semibold whitespace-nowrap transition-all flex-shrink-0 flex items-center gap-1.5 {{ $curType === 'free' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-emerald-50 text-emerald-800 border border-emerald-200/80 hover:bg-emerald-100' }}"
                >
                    <i class="ri-gift-line"></i>
                    <span>{{ app()->getLocale() === 'ar' ? '⚡ كتب وأدلة مجانية' : '⚡ Free Downloads' }}</span>
                </a>

                <a 
                    href="{{ route('library.index', array_merge(request()->except(['page']), ['type' => 'paid'])) }}" 
                    class="px-4 py-2 rounded-full text-xs font-semibold whitespace-nowrap transition-all flex-shrink-0 flex items-center gap-1.5 {{ $curType === 'paid' ? 'bg-[#0071e3] text-white shadow-xs' : 'bg-[#f5f5f7] text-[#1d1d1f]/80 hover:bg-[#e8e8ed]' }}"
                >
                    <i class="ri-vip-diamond-line"></i>
                    <span>{{ app()->getLocale() === 'ar' ? '💎 الأدلة الاحترافية' : '💎 Premium Playbooks' }}</span>
                </a>
            </div>

            <!-- Sort Selector -->
            <form action="{{ route('library.index') }}" method="GET" id="libSortForm" class="flex items-center gap-2">
                @if(request('q'))
                    <input type="hidden" name="q" value="{{ request('q') }}">
                @endif
                @if(request('type'))
                    <input type="hidden" name="type" value="{{ request('type') }}">
                @endif
                @if(request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                <span class="text-xs text-[#86868b] hidden sm:inline">{{ app()->getLocale() === 'ar' ? 'الترتيب:' : 'Sort:' }}</span>
                <select 
                    name="sort" 
                    onchange="document.getElementById('libSortForm').submit()"
                    class="bg-[#f5f5f7] border border-black/5 rounded-full px-3 py-1.5 text-xs font-medium text-[#1d1d1f] focus:outline-none cursor-pointer"
                >
                    <option value="latest" {{ request('sort') === 'latest' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? 'الأحدث إضافتاً' : 'Latest' }}</option>
                    <option value="popular" {{ request('sort') === 'popular' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? 'الأكثر تحميلاً' : 'Most Downloaded' }}</option>
                    <option value="views" {{ request('sort') === 'views' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? 'الأكثر قراءة' : 'Most Viewed' }}</option>
                    <option value="price_low" {{ request('sort') === 'price_low' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? 'السعر: من الأقل' : 'Price: Low' }}</option>
                    <option value="price_high" {{ request('sort') === 'price_high' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? 'السعر: من الأعلى' : 'Price: High' }}</option>
                </select>
            </form>
        </div>

        <!-- Categories Pill Bar -->
        @if(isset($categories) && $categories->count() > 0)
            <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none select-none">
                <span class="text-[11px] font-semibold text-[#86868b] uppercase tracking-wider me-1 hidden sm:inline">
                    {{ app()->getLocale() === 'ar' ? 'الأقسام:' : 'Categories:' }}
                </span>
                
                @php
                    $isAllCat = !request('category');
                @endphp
                <a 
                    href="{{ route('library.index', array_merge(request()->except(['category', 'page']))) }}" 
                    class="px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap transition-all flex-shrink-0 {{ $isAllCat ? 'bg-[#f5f5f7] font-semibold text-[#0071e3] border border-black/5' : 'text-[#86868b] hover:text-[#1d1d1f]' }}"
                >
                    {{ app()->getLocale() === 'ar' ? 'الكل' : 'All' }}
                </a>

                @foreach($categories as $category)
                    @php
                        $isActiveCat = (request('category') === $category->slug);
                    @endphp
                    <a 
                        href="{{ route('library.index', array_merge(request()->except(['page']), ['category' => $category->slug])) }}" 
                        class="px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap transition-all flex-shrink-0 {{ $isActiveCat ? 'bg-[#f5f5f7] font-semibold text-[#0071e3] border border-black/5' : 'text-[#86868b] hover:text-[#1d1d1f]' }}"
                    >
                        {{ $category->name }}
                        @if(isset($category->published_products_count))
                            <span class="text-[10px] opacity-70 ms-0.5">({{ $category->published_products_count }})</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif

    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- 3. BENTO BOOKS GRID                                                    -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    @if($products->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            
            @foreach($products as $book)
                @php
                    $bookUrl = route('library.show', $book->slug);
                    $coverUrl = $book->cover_image ? (Str::startsWith($book->cover_image, ['http://', 'https://', '/']) ? $book->cover_image : asset($book->cover_image)) : asset('images/apple/web-mobile-suite.jpg');
                    $isFree = $book->is_free || (float)$book->price <= 0;
                    $currencySymbol = $book->currency->symbol ?? '$';
                @endphp

                <!-- Apple Bento Book Card -->
                <article class="apple-bento-card p-4 flex flex-col justify-between group">
                    
                    <div>
                        <!-- Top Book Cover Showcase (3D Perspective) -->
                        <a href="{{ $bookUrl }}" class="block relative aspect-[3/4] w-full rounded-[14px] overflow-hidden bg-[#f5f5f7] mb-3.5 border border-black/5 group-hover:scale-[1.02] transition-transform duration-300">
                            <img 
                                src="{{ $coverUrl }}" 
                                alt="{{ $book->title }}" 
                                class="w-full h-full object-cover"
                                loading="lazy"
                            >
                            
                            <!-- Top Overlay Badges -->
                            <div class="absolute top-2.5 inset-x-2.5 flex items-center justify-between gap-1 pointer-events-none">
                                @if($isFree)
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-600/90 backdrop-blur-xs text-white text-[10px] font-bold uppercase tracking-wider shadow-xs">
                                        {{ app()->getLocale() === 'ar' ? 'مجاني' : 'FREE' }}
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full bg-black/75 backdrop-blur-xs text-white text-[10px] font-bold tracking-wider font-mono shadow-xs">
                                        {{ $currencySymbol }}{{ number_format($book->price, 2) }}
                                    </span>
                                @endif

                                @if($book->format)
                                    <span class="px-2 py-0.5 rounded-full bg-white/90 backdrop-blur-xs text-[#1d1d1f] text-[10px] font-bold uppercase tracking-wider border border-black/5 shadow-xs">
                                        {{ strtoupper($book->format) }}
                                    </span>
                                @endif
                            </div>
                        </a>

                        <!-- Category & Language -->
                        <div class="flex items-center justify-between gap-2 mb-1.5 text-[11px] text-[#86868b]">
                            <span class="truncate">{{ $book->category->name ?? 'Architecture' }}</span>
                            @if($book->pages_count)
                                <span class="flex-shrink-0 font-medium">{{ $book->pages_count }} {{ app()->getLocale() === 'ar' ? 'صفحة' : 'pages' }}</span>
                            @endif
                        </div>

                        <!-- Book Title -->
                        <h3 class="font-semibold text-[14px] sm:text-[15px] text-[#1d1d1f] group-hover:text-[#0071e3] transition-colors leading-snug line-clamp-2 mb-1">
                            <a href="{{ $bookUrl }}">
                                {{ $book->title }}
                            </a>
                        </h3>

                        <!-- Author Name -->
                        @if($book->author_name)
                            <p class="text-xs text-[#86868b] truncate mb-3">
                                {{ $book->author_name }}
                            </p>
                        @endif
                    </div>

                    <!-- Card Bottom Action -->
                    <div class="pt-3 border-t border-black/5 flex items-center justify-between">
                        <div class="flex items-center gap-1 text-[11px] text-[#86868b]">
                            <i class="ri-download-cloud-2-line text-xs"></i>
                            <span>{{ $book->download_count ?? 0 }}</span>
                        </div>

                        <a 
                            href="{{ $bookUrl }}" 
                            class="inline-flex items-center gap-1 text-xs font-semibold {{ $isFree ? 'text-emerald-700 hover:text-emerald-800' : 'text-[#0066cc] hover:text-[#0071e3]' }} transition-colors"
                        >
                            <span>{{ $isFree ? (app()->getLocale() === 'ar' ? 'تحميل مجاني ➔' : 'Free Download ➔') : (app()->getLocale() === 'ar' ? 'تفاصيل وشراء ➔' : 'View & Buy ➔') }}</span>
                        </a>
                    </div>

                </article>
            @endforeach

        </div>

        <!-- Pagination (Apple Minimal Style) -->
        <div class="mt-10">
            {{ $products->links('pagination::tailwind') }}
        </div>

    @else
        <!-- Empty State (Apple Clean) -->
        <div class="text-center py-16 rounded-[22px] bg-[#f5f5f7] border border-black/5 p-8">
            <div class="w-12 h-12 rounded-full bg-white text-[#86868b] flex items-center justify-center mx-auto text-xl mb-3 shadow-xs">
                <i class="ri-book-open-line"></i>
            </div>
            <h3 class="text-base font-semibold text-[#1d1d1f] mb-1">
                {{ app()->getLocale() === 'ar' ? 'لم يتم العثور على كتب مطابقة' : 'No books found' }}
            </h3>
            <p class="text-xs text-[#86868b] max-w-sm mx-auto mb-5 leading-relaxed">
                {{ app()->getLocale() === 'ar' ? 'جرب البحث بكلمات مختلفة أو إزالة الفلاتر المحددة لاستعراض كافة الكتب.' : 'Try adjusting your search criteria or resetting filters to browse all available books.' }}
            </p>
            <a href="{{ route('library.index') }}" class="inline-flex items-center gap-1 px-4 py-2 rounded-full bg-[#0071e3] text-white text-xs font-medium hover:bg-[#0077ed] transition-all shadow-xs">
                <span>{{ app()->getLocale() === 'ar' ? 'عرض جميع الكتب' : 'View All Books' }}</span>
            </a>
        </div>
    @endif

</div>
@endsection

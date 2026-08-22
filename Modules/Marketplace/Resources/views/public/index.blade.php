@extends('marketplace::layouts.marketplace-master')

@section('content')
<div class="max-w-[1280px] mx-auto px-6 sm:px-10 py-8 sm:py-12">

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- 1. APPLE HERO SECTION (Storefront Header & Smart Search)                -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <section class="rounded-[22px] bg-[#f5f5f7] p-8 sm:p-12 mb-10 overflow-hidden relative border border-black/5">
        <div class="max-w-[800px]">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white border border-black/5 text-[#0071e3] text-xs font-semibold uppercase tracking-wider mb-4 shadow-2xs">
                <i class="ri-store-2-line"></i>
                <span>{{ app()->getLocale() === 'ar' ? 'سوق الخدمات والأدوات البرمجية' : 'Software Services & Tools Hub' }}</span>
            </div>
            
            <h1 class="text-[32px] sm:text-[44px] lg:text-[50px] font-semibold tracking-[-0.03em] leading-[1.05] text-[#1d1d1f] mb-3">
                {{ app()->getLocale() === 'ar' ? 'حلول برمجية فورية، جاهزة للإنتاج.' : 'Verified Software Services & Instant Tools.' }}
            </h1>
            
            <p class="text-[16px] sm:text-[18px] text-[#86868b] leading-relaxed max-w-[640px] mb-8">
                {{ app()->getLocale() === 'ar' 
                    ? 'اكتشف واشترِ أدوات الذكاء الاصطناعي، تكاملات الواتساب، وتطوير الأنظمة المخصصة بضمان مالي Escrow بنسبة 100% ودفع فوري من المحفظة.' 
                    : 'Deploy pre-built automation scripts, custom software architectures, and AI integrations with 100% cryptographic escrow guarantee.' }}
            </p>

            <!-- Apple Search Pill -->
            <form action="{{ route('marketplace.services.index') }}" method="GET" id="mainSearchForm" class="relative max-w-[560px]">
                @if(request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                @if(request('sort'))
                    <input type="hidden" name="sort" value="{{ request('sort') }}">
                @endif
                @if(request('delivery_time'))
                    <input type="hidden" name="delivery_time" value="{{ request('delivery_time') }}">
                @endif
                @if(request('min_price'))
                    <input type="hidden" name="min_price" value="{{ request('min_price') }}">
                @endif
                @if(request('max_price'))
                    <input type="hidden" name="max_price" value="{{ request('max_price') }}">
                @endif

                <div class="relative flex items-center shadow-sm rounded-full bg-white border border-black/10 focus-within:border-[#0071e3] transition-all">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') ?? request('q') }}" 
                        placeholder="{{ app()->getLocale() === 'ar' ? 'ابحث عن أداة، بوت، تكامل واتساب، أو خدمة برمجة...' : 'Search tools, WhatsApp APIs, AI integrations, or custom development...' }}"
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
    <!-- 2. CATEGORY TABS ROW (Apple Horizontal Pill Navigation)                -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="mb-8">
        <div class="flex items-center justify-between gap-4 mb-4">
            <div class="flex items-center gap-3">
                <!-- Mobile Filter Toggle -->
                <button 
                    type="button" 
                    onclick="toggleMobileFilters()" 
                    class="lg:hidden h-9 px-3.5 rounded-full bg-[#f5f5f7] border border-black/5 text-[#1d1d1f] text-xs font-semibold flex items-center gap-1.5 hover:bg-[#e8e8ed] transition-colors"
                >
                    <i class="ri-equalizer-line"></i>
                    <span>{{ app()->getLocale() === 'ar' ? 'الفلاتر' : 'Filters' }}</span>
                </button>

                <h2 class="text-[17px] font-semibold text-[#1d1d1f] tracking-[-0.01em]">
                    {{ app()->getLocale() === 'ar' ? 'تصفح حسب القسم' : 'Browse by Category' }}
                </h2>
            </div>

            <!-- Sort By Selector -->
            <form action="{{ route('marketplace.services.index') }}" method="GET" id="topSortForm" class="flex items-center gap-2">
                @if(request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif
                @if(request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                <span class="text-xs text-[#86868b] hidden sm:inline">{{ app()->getLocale() === 'ar' ? 'الترتيب:' : 'Sort:' }}</span>
                <select 
                    name="sort" 
                    onchange="document.getElementById('topSortForm').submit()"
                    class="bg-[#f5f5f7] border border-black/5 rounded-full px-3 py-1.5 text-xs font-medium text-[#1d1d1f] focus:outline-none cursor-pointer"
                >
                    <option value="popular" {{ request('sort') === 'popular' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? 'الأكثر طلباً' : 'Most Popular' }}</option>
                    <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? 'الأحدث إضافتاً' : 'Newest' }}</option>
                    <option value="rating" {{ request('sort') === 'rating' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? 'الأعلى تقييماً' : 'Highest Rated' }}</option>
                    <option value="price_low" {{ request('sort') === 'price_low' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? 'السعر: من الأقل' : 'Price: Low to High' }}</option>
                    <option value="price_high" {{ request('sort') === 'price_high' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? 'السعر: من الأعلى' : 'Price: High to Low' }}</option>
                </select>
            </form>
        </div>

        <!-- Scrollable Category Row -->
        <div class="flex items-center gap-2.5 overflow-x-auto pb-2 scrollbar-none select-none">
            @php
                $isAllActive = !request('category') && !request('category_id') && !request('category_slug') && empty($filters['category']);
            @endphp
            <a 
                href="{{ route('marketplace.services.index', array_merge(request()->except(['category', 'category_id', 'category_slug', 'page']))) }}" 
                class="px-4 py-2 rounded-full text-xs font-medium whitespace-nowrap transition-all flex-shrink-0 {{ $isAllActive ? 'bg-[#1d1d1f] text-white shadow-xs' : 'bg-[#f5f5f7] text-[#1d1d1f]/80 hover:bg-[#e8e8ed]' }}"
            >
                {{ app()->getLocale() === 'ar' ? 'جميع الخدمات' : 'All Services' }}
                <span class="text-[11px] opacity-70 ms-1">({{ $services->total() }})</span>
            </a>

            @if(isset($categories) && $categories->count() > 0)
                @foreach($categories as $category)
                    @php
                        $isActive = (request('category') === $category->slug || request('category') === (string)$category->id || (isset($filters['category']) && $filters['category'] === $category->slug));
                    @endphp
                    <a 
                        href="{{ route('marketplace.services.index', array_merge(request()->except(['page']), ['category' => $category->slug])) }}" 
                        class="px-4 py-2 rounded-full text-xs font-medium whitespace-nowrap transition-all flex-shrink-0 {{ $isActive ? 'bg-[#1d1d1f] text-white shadow-xs' : 'bg-[#f5f5f7] text-[#1d1d1f]/80 hover:bg-[#e8e8ed]' }}"
                    >
                        {{ $category->name }}
                        @if(isset($category->services_count))
                            <span class="text-[11px] opacity-70 ms-1">({{ $category->services_count }})</span>
                        @endif
                    </a>
                @endforeach
            @endif
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- 3. MAIN CONTENT GRID (Bento Filters Sidebar + 2-Col Cards)             -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Left Column: Apple Bento Filter Sidebar (3 cols) -->
        <aside id="filtersSidebar" class="hidden lg:block lg:col-span-4 xl:col-span-3 sticky top-24">
            <div class="rounded-[18px] bg-[#f5f5f7] border border-black/5 p-5 space-y-5">
                
                <!-- Header with Quick Reset -->
                <div class="flex items-center justify-between pb-3 border-b border-black/5">
                    <h3 class="text-sm font-semibold text-[#1d1d1f]">
                        {{ app()->getLocale() === 'ar' ? 'تخصيص النتائج' : 'Filter Results' }}
                    </h3>
                    <a 
                        href="{{ route('marketplace.services.index') }}" 
                        class="text-[11px] font-medium text-[#0066cc] hover:underline flex items-center gap-1"
                    >
                        <i class="ri-refresh-line text-xs"></i>
                        <span>{{ app()->getLocale() === 'ar' ? 'إعادة ضبط' : 'Reset' }}</span>
                    </a>
                </div>

                <!-- Filters Form -->
                <form action="{{ route('marketplace.services.index') }}" method="GET" id="sidebarFilterForm" class="space-y-4">
                    @if(request('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif
                    @if(request('sort'))
                        <input type="hidden" name="sort" value="{{ request('sort') }}">
                    @endif

                    <!-- Filter 1: Category Dropdown -->
                    <div>
                        <label class="block text-[11px] font-semibold text-[#86868b] uppercase tracking-wider mb-1.5">
                            {{ app()->getLocale() === 'ar' ? 'القسم' : 'Category' }}
                        </label>
                        <div class="relative">
                            <select 
                                name="category" 
                                onchange="document.getElementById('sidebarFilterForm').submit()"
                                class="w-full h-10 px-3 rounded-xl bg-white border border-black/10 text-xs font-medium text-[#1d1d1f] focus:outline-none focus:border-[#0071e3] appearance-none"
                            >
                                <option value="">{{ app()->getLocale() === 'ar' ? 'جميع الأقسام' : 'All Categories' }}</option>
                                @if(isset($categories))
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->slug }}" {{ (request('category') === $cat->slug || (isset($filters['category']) && $filters['category'] === $cat->slug)) ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            <i class="ri-arrow-down-s-line absolute {{ app()->getLocale() === 'ar' ? 'left-3' : 'right-3' }} top-3 text-[#86868b] pointer-events-none text-sm"></i>
                        </div>
                    </div>

                    <!-- Filter 2: Delivery Speed / Availability -->
                    <div>
                        <label class="block text-[11px] font-semibold text-[#86868b] uppercase tracking-wider mb-1.5">
                            {{ app()->getLocale() === 'ar' ? 'سرعة ونوع التسليم' : 'Delivery Model' }}
                        </label>
                        <div class="relative">
                            <select 
                                name="availability" 
                                onchange="document.getElementById('sidebarFilterForm').submit()"
                                class="w-full h-10 px-3 rounded-xl bg-white border border-black/10 text-xs font-medium text-[#1d1d1f] focus:outline-none focus:border-[#0071e3] appearance-none"
                            >
                                <option value="">{{ app()->getLocale() === 'ar' ? 'جميع النماذج' : 'All Models' }}</option>
                                <option value="instant" {{ request('availability') === 'instant' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? '⚡ تسليم فوري (< 24 ساعة)' : '⚡ Instant (< 24h)' }}</option>
                                <option value="project" {{ request('availability') === 'project' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? '📅 عمل بالمشروع' : '📅 Custom Project' }}</option>
                                <option value="hourly" {{ request('availability') === 'hourly' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? '💼 عقود دعم مستمر' : '💼 Support Retainer' }}</option>
                            </select>
                            <i class="ri-arrow-down-s-line absolute {{ app()->getLocale() === 'ar' ? 'left-3' : 'right-3' }} top-3 text-[#86868b] pointer-events-none text-sm"></i>
                        </div>
                    </div>

                    <!-- Filter 3: Price Range -->
                    @php
                        $currencySymbol = $viewerCurrency->symbol ?? $viewerCurrency->currency ?? '$';
                        $curMinPrice = (int)(request('min_price') ?? 5);
                        $curMaxPrice = (int)(request('max_price') ?? 500);
                        if ($curMinPrice < 5) $curMinPrice = 5;
                        if ($curMaxPrice > 5000) $curMaxPrice = 5000;
                        if ($curMinPrice > $curMaxPrice) $curMinPrice = $curMaxPrice;
                    @endphp
                    <div class="pt-2">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-[11px] font-semibold text-[#86868b] uppercase tracking-wider">
                                {{ app()->getLocale() === 'ar' ? 'نطاق السعر' : 'Price Range' }}
                            </label>
                            <span class="text-xs font-bold text-[#1d1d1f] font-mono" id="priceDisplayRange">
                                {{ $currencySymbol }}{{ $curMinPrice }} - {{ $currencySymbol }}{{ $curMaxPrice }}
                            </span>
                        </div>

                        <!-- Dual Range Slider Track -->
                        <div class="range-slider-wrapper mb-3 px-1">
                            <div class="range-slider-track"></div>
                            <div class="range-slider-progress" id="sliderProgress"></div>
                            <input 
                                type="range" 
                                id="rangeMinInput"
                                min="5" 
                                max="5000" 
                                step="5"
                                value="{{ $curMinPrice }}" 
                                class="range-slider-input"
                                oninput="onDualSliderInput('min')"
                                onchange="document.getElementById('sidebarFilterForm').submit()"
                            >
                            <input 
                                type="range" 
                                id="rangeMaxInput"
                                min="5" 
                                max="5000" 
                                step="5"
                                value="{{ $curMaxPrice }}" 
                                class="range-slider-input"
                                oninput="onDualSliderInput('max')"
                                onchange="document.getElementById('sidebarFilterForm').submit()"
                            >
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div class="rounded-xl bg-white border border-black/10 px-2.5 py-1.5">
                                <span class="block text-[9px] text-[#86868b] font-medium">{{ app()->getLocale() === 'ar' ? 'من' : 'Min' }}</span>
                                <input 
                                    type="number" 
                                    name="min_price" 
                                    id="fromPriceInput" 
                                    value="{{ $curMinPrice }}" 
                                    placeholder="5" 
                                    oninput="onBoxPriceInput('min')"
                                    onchange="document.getElementById('sidebarFilterForm').submit()"
                                    class="w-full bg-transparent text-xs font-bold text-[#1d1d1f] focus:outline-none p-0"
                                >
                            </div>
                            <div class="rounded-xl bg-white border border-black/10 px-2.5 py-1.5">
                                <span class="block text-[9px] text-[#86868b] font-medium">{{ app()->getLocale() === 'ar' ? 'إلى' : 'Max' }}</span>
                                <input 
                                    type="number" 
                                    name="max_price" 
                                    id="toPriceInput" 
                                    value="{{ $curMaxPrice }}" 
                                    placeholder="500" 
                                    oninput="onBoxPriceInput('max')"
                                    onchange="document.getElementById('sidebarFilterForm').submit()"
                                    class="w-full bg-transparent text-xs font-bold text-[#1d1d1f] focus:outline-none p-0"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Filter 4: Delivery Days -->
                    <div>
                        <label class="block text-[11px] font-semibold text-[#86868b] uppercase tracking-wider mb-1.5">
                            {{ app()->getLocale() === 'ar' ? 'الحد الأقصى للتسليم' : 'Max Delivery Time' }}
                        </label>
                        <div class="relative">
                            <select 
                                name="delivery_time" 
                                onchange="document.getElementById('sidebarFilterForm').submit()"
                                class="w-full h-10 px-3 rounded-xl bg-white border border-black/10 text-xs font-medium text-[#1d1d1f] focus:outline-none focus:border-[#0071e3] appearance-none"
                            >
                                <option value="">{{ app()->getLocale() === 'ar' ? 'أي مدة تسليم' : 'Any Timeline' }}</option>
                                <option value="1" {{ request('delivery_time') == '1' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? 'أقل من 24 ساعة' : 'Within 24 hours' }}</option>
                                <option value="3" {{ request('delivery_time') == '3' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? 'حتى 3 أيام' : 'Up to 3 days' }}</option>
                                <option value="7" {{ request('delivery_time') == '7' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? 'حتى 7 أيام' : 'Up to 7 days' }}</option>
                                <option value="14" {{ request('delivery_time') == '14' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? 'حتى 14 يوم' : 'Up to 14 days' }}</option>
                            </select>
                            <i class="ri-arrow-down-s-line absolute {{ app()->getLocale() === 'ar' ? 'left-3' : 'right-3' }} top-3 text-[#86868b] pointer-events-none text-sm"></i>
                        </div>
                    </div>

                </form>

            </div>
        </aside>

        <!-- Right Column: Apple Bento Cards Grid (8/9 cols) -->
        <main class="lg:col-span-8 xl:col-span-9">
            
            @if($services->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    @foreach($services as $index => $service)
                        @php
                            $serviceUrl = route('marketplace.services.show', ['id' => $service->id, 'slug' => $service->slug ?? 'service']);
                            $minPrice = $service->packages->min('price') ?? $service->price ?? 5;
                            $firstPackage = $service->packages->first();
                            $currencyCode = $firstPackage && $firstPackage->currency ? ($firstPackage->currency->symbol ?? $firstPackage->currency->code ?? '$') : '$';
                            $sellerName = $service->seller->name ?? 'Verified Engineer';
                            $sellerInitials = strtoupper(substr($sellerName, 0, 2));
                            $deliveryDays = $firstPackage->delivery_days ?? 2;
                            $isFavorited = !empty($service->is_favorited);
                        @endphp

                        <!-- Apple Bento Card Component -->
                        <article class="apple-bento-card p-6 flex flex-col justify-between group">
                            
                            <div>
                                <!-- Top Row: Creator Profile + Rating + Favorite -->
                                <div class="flex items-center justify-between gap-3 mb-4">
                                    
                                    <!-- Creator Info -->
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="w-8 h-8 rounded-full bg-[#1d1d1f] text-white flex items-center justify-center font-bold text-xs flex-shrink-0">
                                            {{ $sellerInitials }}
                                        </div>
                                        <div class="min-w-0">
                                            <h4 class="font-semibold text-xs sm:text-[13px] text-[#1d1d1f] truncate flex items-center gap-1">
                                                <span>{{ $sellerName }}</span>
                                                <i class="ri-verified-badge-fill text-[#0071e3] text-xs"></i>
                                            </h4>
                                            <p class="text-[11px] text-[#86868b] truncate">
                                                {{ $service->category->name ?? (app()->getLocale() === 'ar' ? 'حلول برمجية' : 'Software Solutions') }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Rating Pill & Favorite Action -->
                                    <div class="flex items-center gap-1.5 flex-shrink-0">
                                        <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-[#f5f5f7] text-[11px] font-semibold text-[#1d1d1f]">
                                            <i class="ri-star-fill text-amber-500 text-[11px]"></i>
                                            <span>4.9</span>
                                        </div>

                                        <form action="{{ route('marketplace.favorites.toggle', $service->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button 
                                                type="submit" 
                                                class="w-7 h-7 rounded-full flex items-center justify-center text-[#86868b] hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                                title="{{ app()->getLocale() === 'ar' ? 'حفظ في المفضلة' : 'Bookmark' }}"
                                            >
                                                <i class="{{ $isFavorited ? 'ri-bookmark-fill text-rose-600' : 'ri-bookmark-line' }} text-sm"></i>
                                            </button>
                                        </form>
                                    </div>

                                </div>

                                <!-- Middle: Prominent Service Title & Price -->
                                <div class="flex items-start justify-between gap-3 mb-3">
                                    <h3 class="font-semibold text-[15px] sm:text-[16px] text-[#1d1d1f] group-hover:text-[#0071e3] transition-colors leading-snug line-clamp-2 tracking-[-0.01em]">
                                        <a href="{{ $serviceUrl }}">
                                            {{ $service->title }}
                                        </a>
                                    </h3>
                                    <div class="text-end flex-shrink-0 whitespace-nowrap pt-0.5">
                                        <span class="text-[10px] text-[#86868b] block font-medium">
                                            {{ app()->getLocale() === 'ar' ? 'يبدأ من' : 'from' }}
                                        </span>
                                        <span class="text-[17px] sm:text-[19px] font-semibold text-[#0071e3] font-mono">
                                            {{ $currencyCode }}{{ number_format($minPrice, 0) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Metadata Badges Row (Delivery Model + Escrow) -->
                                <div class="flex items-center gap-2 flex-wrap mb-3.5">
                                    @if($deliveryDays <= 1)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200/80 text-[11px] font-medium">
                                            <i class="ri-flashlight-fill text-emerald-600"></i>
                                            <span>{{ app()->getLocale() === 'ar' ? 'تسليم فوري (< 24 ساعة)' : 'Instant Delivery (< 24h)' }}</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-[#f5f5f7] border border-black/5 text-[11px] font-medium text-[#1d1d1f]/80">
                                            <i class="ri-time-line text-[#86868b]"></i>
                                            <span>{{ $deliveryDays }} {{ app()->getLocale() === 'ar' ? 'أيام تسليم' : 'days delivery' }}</span>
                                        </span>
                                    @endif

                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-50 text-[#0066cc] text-[10px] font-medium border border-blue-200/60">
                                        <i class="ri-shield-check-line"></i>
                                        <span>{{ app()->getLocale() === 'ar' ? 'ضمان Escrow' : 'Escrow Protected' }}</span>
                                    </span>
                                </div>

                                <!-- Description Snippet -->
                                <p class="text-xs text-[#86868b] line-clamp-2 leading-relaxed mb-4">
                                    {{ $service->tagline ?? Str::limit(strip_tags($service->description ?? ''), 110) }}
                                </p>
                            </div>

                            <!-- Bottom Action Row -->
                            <div class="pt-3 border-t border-black/5 flex items-center justify-between">
                                <span class="text-[11px] text-[#86868b]">
                                    {{ app()->getLocale() === 'ar' ? 'دفع آمن بالمحفظة' : '1-Click Wallet Checkout' }}
                                </span>
                                <a 
                                    href="{{ $serviceUrl }}" 
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-[#0066cc] group-hover:underline transition-all"
                                >
                                    <span>{{ app()->getLocale() === 'ar' ? 'عرض المواصفات' : 'View Details' }}</span>
                                    <i class="ri-arrow-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}-s-line font-bold"></i>
                                </a>
                            </div>

                        </article>

                        <!-- Interspersed Apple Bento Promotional Card -->
                        @if($index === 2)
                            <article class="rounded-[18px] bg-[#1d1d1f] text-white p-7 flex flex-col justify-between relative overflow-hidden shadow-sm min-h-[220px]">
                                <div class="relative z-10">
                                    <span class="inline-block px-2.5 py-0.5 rounded-full bg-white/10 text-white text-[10px] font-medium tracking-wider uppercase mb-3">
                                        {{ app()->getLocale() === 'ar' ? 'انضم للمنظومة' : 'DEVELOPER NETWORK' }}
                                    </span>
                                    <h3 class="text-xl font-semibold tracking-[-0.02em] leading-snug mb-2 text-white">
                                        {{ app()->getLocale() === 'ar' ? 'اعرض خدماتك وأدواتك البرمجية للشركات' : 'Publish Your Custom Tools & Services' }}
                                    </h3>
                                    <p class="text-xs text-white/75 leading-relaxed mb-5">
                                        {{ app()->getLocale() === 'ar' ? 'اربط حلولك بقاعدة عملاء Musoftwares واستقبل الدفعات الفورية بأمان الضمان المالي.' : 'Monetize scripts, automation bots, and bespoke software directly with escrow guaranteed payouts.' }}
                                    </p>
                                </div>

                                <div class="relative z-10">
                                    <a 
                                        href="{{ route('marketplace.services.create') }}" 
                                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-white text-[#1d1d1f] font-medium text-xs hover:bg-[#f5f5f7] transition-all shadow-xs"
                                    >
                                        <span>{{ app()->getLocale() === 'ar' ? 'ابدأ كبائع معتمد' : 'Become a Seller' }}</span>
                                        <i class="ri-arrow-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}-line font-bold"></i>
                                    </a>
                                </div>
                            </article>
                        @endif

                    @endforeach

                </div>

                <!-- Pagination (Apple Minimal Style) -->
                <div class="mt-10">
                    {{ $services->links('pagination::tailwind') }}
                </div>

            @else
                <!-- Empty State (Apple Clean) -->
                <div class="text-center py-16 rounded-[22px] bg-[#f5f5f7] border border-black/5 p-8">
                    <div class="w-12 h-12 rounded-full bg-white text-[#86868b] flex items-center justify-center mx-auto text-xl mb-3 shadow-xs">
                        <i class="ri-inbox-line"></i>
                    </div>
                    <h3 class="text-base font-semibold text-[#1d1d1f] mb-1">
                        {{ app()->getLocale() === 'ar' ? 'لم يتم العثور على خدمات مطابقة' : 'No matching services found' }}
                    </h3>
                    <p class="text-xs text-[#86868b] max-w-sm mx-auto mb-5 leading-relaxed">
                        {{ app()->getLocale() === 'ar' ? 'جرب البحث بكلمات مختلفة أو إزالة الفلاتر المحددة لاستعراض كافة الحلول.' : 'Try adjusting your search criteria or resetting filters to browse all available software.' }}
                    </p>
                    <a href="{{ route('marketplace.services.index') }}" class="inline-flex items-center gap-1 px-4 py-2 rounded-full bg-[#0071e3] text-white text-xs font-medium hover:bg-[#0077ed] transition-all shadow-xs">
                        <span>{{ app()->getLocale() === 'ar' ? 'عرض جميع الخدمات' : 'View All Services' }}</span>
                    </a>
                </div>
            @endif

        </main>

    </div>

</div>

<!-- Mobile Filters Drawer (Apple Frosted Sheet) -->
<div id="mobileFiltersModal" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm hidden items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="w-full max-w-lg bg-white rounded-t-[28px] sm:rounded-[22px] p-6 max-h-[85vh] overflow-y-auto space-y-5 shadow-2xl border border-black/10">
        <div class="flex items-center justify-between pb-3 border-b border-black/5">
            <h3 class="text-base font-semibold text-[#1d1d1f]">
                {{ app()->getLocale() === 'ar' ? 'تصفية الخدمات' : 'Filter Services' }}
            </h3>
            <button type="button" onclick="toggleMobileFilters()" class="w-8 h-8 rounded-full bg-[#f5f5f7] text-[#1d1d1f] flex items-center justify-center text-base">
                <i class="ri-close-line"></i>
            </button>
        </div>

        <form action="{{ route('marketplace.services.index') }}" method="GET" class="space-y-4">
            @if(request('search'))
                <input type="hidden" name="search" value="{{ request('search') }}">
            @endif
            
            <div>
                <label class="block text-xs font-semibold text-[#86868b] uppercase mb-1">{{ app()->getLocale() === 'ar' ? 'القسم' : 'Category' }}</label>
                <select name="category" class="w-full h-11 px-3 rounded-xl bg-[#f5f5f7] border border-black/10 text-xs font-medium text-[#1d1d1f]">
                    <option value="">{{ app()->getLocale() === 'ar' ? 'جميع الأقسام' : 'All Categories' }}</option>
                    @if(isset($categories))
                        @foreach($categories as $cat)
                            <option value="{{ $cat->slug }}" {{ request('category') === $cat->slug ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-[#86868b] uppercase mb-1">{{ app()->getLocale() === 'ar' ? 'نوع التسليم' : 'Delivery Model' }}</label>
                <select name="availability" class="w-full h-11 px-3 rounded-xl bg-[#f5f5f7] border border-black/10 text-xs font-medium text-[#1d1d1f]">
                    <option value="">{{ app()->getLocale() === 'ar' ? 'جميع النماذج' : 'All Models' }}</option>
                    <option value="instant" {{ request('availability') === 'instant' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? '⚡ تسليم فوري (< 24 ساعة)' : '⚡ Instant (< 24h)' }}</option>
                    <option value="project" {{ request('availability') === 'project' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? '📅 عمل بالمشروع' : '📅 Custom Project' }}</option>
                </select>
            </div>

            <button type="submit" class="w-full h-11 rounded-full bg-[#0071e3] text-white text-xs font-medium hover:bg-[#0077ed] transition-all">
                {{ app()->getLocale() === 'ar' ? 'تطبيق الفلاتر' : 'Apply Filters' }}
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function toggleMobileFilters() {
        const modal = document.getElementById('mobileFiltersModal');
        if (modal.classList.contains('hidden')) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        } else {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }
    }

    const priceMin = 5;
    const priceMax = 5000;

    function updateSliderVisuals(minVal, maxVal) {
        const progress = document.getElementById('sliderProgress');
        const rangeText = document.getElementById('priceDisplayRange');
        if (!progress) return;

        const leftPercent = ((minVal - priceMin) / (priceMax - priceMin)) * 100;
        const rightPercent = ((maxVal - priceMin) / (priceMax - priceMin)) * 100;

        progress.style.left = leftPercent + '%';
        progress.style.width = (rightPercent - leftPercent) + '%';

        if (rangeText) {
            rangeText.innerText = '{{ $currencySymbol }}' + minVal + ' - {{ $currencySymbol }}' + maxVal;
        }
    }

    function onDualSliderInput(changedHandle) {
        let minVal = parseInt(document.getElementById('rangeMinInput').value);
        let maxVal = parseInt(document.getElementById('rangeMaxInput').value);

        if (minVal > maxVal) {
            if (changedHandle === 'min') {
                document.getElementById('rangeMinInput').value = maxVal;
                minVal = maxVal;
            } else {
                document.getElementById('rangeMaxInput').value = minVal;
                maxVal = minVal;
            }
        }

        document.getElementById('fromPriceInput').value = minVal;
        document.getElementById('toPriceInput').value = maxVal;
        updateSliderVisuals(minVal, maxVal);
    }

    function onBoxPriceInput(changedBox) {
        let minVal = parseInt(document.getElementById('fromPriceInput').value) || priceMin;
        let maxVal = parseInt(document.getElementById('toPriceInput').value) || priceMax;

        if (minVal < priceMin) minVal = priceMin;
        if (maxVal > priceMax) maxVal = priceMax;

        document.getElementById('rangeMinInput').value = minVal;
        document.getElementById('rangeMaxInput').value = maxVal;
        updateSliderVisuals(minVal, maxVal);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const minVal = parseInt(document.getElementById('rangeMinInput')?.value || 5);
        const maxVal = parseInt(document.getElementById('rangeMaxInput')?.value || 500);
        updateSliderVisuals(minVal, maxVal);
    });
</script>
@endpush
@endsection

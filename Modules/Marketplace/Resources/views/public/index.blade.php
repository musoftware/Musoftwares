@extends('marketplace::layouts.marketplace-master')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

    <!-- Top Section: Explore Header, Search Pill & Smooth Scroll Category Navigation -->
    <div class="mb-7">
        
        <!-- Header Row: Title & Search Pill -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
            
            <!-- Left: Menu Trigger & Explore Title -->
            <div class="flex items-center gap-3">
                <!-- Mobile Filters Drawer Toggle Button -->
                <button 
                    type="button" 
                    onclick="toggleMobileFilters()" 
                    class="lg:hidden w-9 h-9 rounded-2xl bg-white dark:bg-dark-800 border border-slate-200/80 dark:border-white/10 text-slate-800 dark:text-zinc-200 flex items-center justify-center shadow-sm hover:bg-slate-100 dark:hover:bg-dark-750 transition-colors"
                    title="{{ app()->getLocale() === 'ar' ? 'تصفية النتائج' : 'Open Filters' }}"
                >
                    <i class="ri-menu-2-line text-base font-bold"></i>
                </button>

                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-950 dark:text-white tracking-tight">
                    {{ app()->getLocale() === 'ar' ? 'استكشف' : 'Explore' }}
                </h1>
            </div>

            <!-- Right: Clean Search Pill -->
            <div class="w-full sm:w-72 lg:w-80">
                <form action="{{ route('marketplace.services.index') }}" method="GET" id="mainSearchForm" class="relative">
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

                    <div class="relative flex items-center">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ request('search') ?? request('q') }}" 
                            placeholder="{{ app()->getLocale() === 'ar' ? 'بحث...' : 'Search' }}"
                            class="w-full h-10 {{ app()->getLocale() === 'ar' ? 'pr-3.5 pl-10' : 'pl-3.5 pr-10' }} rounded-full bg-white dark:bg-dark-800 border border-slate-200/80 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 text-xs focus:outline-none focus:border-slate-400 dark:focus:border-white/30 transition-all shadow-sm"
                        >
                        <button type="submit" class="absolute {{ app()->getLocale() === 'ar' ? 'left-3' : 'right-3' }} text-slate-400 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white transition-colors" title="{{ app()->getLocale() === 'ar' ? 'بحث' : 'Search' }}">
                            <i class="ri-search-2-line text-base"></i>
                        </button>
                    </div>
                </form>
            </div>

        </div>

        <!-- Category Tabs Container with Left & Right Arrow Buttons & Drag Scroll -->
        <div class="relative group/tabs flex items-center">
            
            <!-- Left Scroll Arrow Button -->
            <button 
                type="button" 
                onclick="scrollCatTabs(-240)" 
                class="hidden sm:flex absolute -start-3 z-10 w-8 h-8 rounded-full bg-white dark:bg-dark-800 border border-slate-200 dark:border-white/10 text-slate-700 dark:text-zinc-200 items-center justify-center shadow-md hover:bg-slate-50 dark:hover:bg-dark-750 transition-all opacity-80 hover:opacity-100 hover:scale-105"
                title="Scroll Left"
                aria-label="Scroll Categories Left"
            >
                <i class="ri-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}-s-line text-base"></i>
            </button>

            <!-- Scrollable Category Row -->
            <div 
                id="catScrollContainer"
                class="flex items-center gap-6 sm:gap-7 overflow-x-auto pb-2 px-1 scrollbar-none scroll-smooth select-none cursor-grab active:cursor-grabbing w-full"
            >
                @php
                    $isAllActive = !request('category') && !request('category_id') && !request('category_slug') && empty($filters['category']);
                @endphp
                <a 
                    href="{{ route('marketplace.services.index', array_merge(request()->except(['category', 'category_id', 'category_slug', 'page']))) }}" 
                    class="whitespace-nowrap text-sm sm:text-base transition-colors flex-shrink-0 {{ $isAllActive ? 'font-extrabold text-slate-950 dark:text-white' : 'font-semibold text-slate-400 hover:text-slate-800 dark:text-zinc-500 dark:hover:text-zinc-300' }}"
                >
                    {{ app()->getLocale() === 'ar' ? 'الكل' : 'All' }}
                    <span class="text-xs font-normal text-slate-400 dark:text-zinc-500">({{ $services->total() }})</span>
                </a>

                @if(isset($categories) && $categories->count() > 0)
                    @foreach($categories as $category)
                        @php
                            $isActive = (request('category') === $category->slug || request('category') === (string)$category->id || (isset($filters['category']) && $filters['category'] === $category->slug));
                        @endphp
                        <a 
                            href="{{ route('marketplace.services.index', array_merge(request()->except(['page']), ['category' => $category->slug])) }}" 
                            class="whitespace-nowrap text-sm sm:text-base transition-colors flex-shrink-0 {{ $isActive ? 'font-extrabold text-slate-950 dark:text-white' : 'font-semibold text-slate-400 hover:text-slate-800 dark:text-zinc-500 dark:hover:text-zinc-300' }}"
                        >
                            {{ $category->name }}
                            @if(isset($category->services_count))
                                <span class="text-xs font-normal text-slate-400 dark:text-zinc-500">({{ $category->services_count }})</span>
                            @endif
                        </a>
                    @endforeach
                @endif
            </div>

            <!-- Right Scroll Arrow Button -->
            <button 
                type="button" 
                onclick="scrollCatTabs(240)" 
                class="hidden sm:flex absolute -end-3 z-10 w-8 h-8 rounded-full bg-white dark:bg-dark-800 border border-slate-200 dark:border-white/10 text-slate-700 dark:text-zinc-200 items-center justify-center shadow-md hover:bg-slate-50 dark:hover:bg-dark-750 transition-all opacity-80 hover:opacity-100 hover:scale-105"
                title="Scroll Right"
                aria-label="Scroll Categories Right"
            >
                <i class="ri-arrow-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}-s-line text-base"></i>
            </button>

        </div>

    </div>

    <!-- Main Content: Left Filters Sidebar & Right Cards Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-7 items-start">
        
        <!-- ========================================== -->
        <!-- LEFT COLUMN: FILTERS SIDEBAR               -->
        <!-- ========================================== -->
        <aside id="filtersSidebar" class="hidden lg:block lg:col-span-4 xl:col-span-3 sticky top-20">
            <div class="rounded-3xl bg-white dark:bg-dark-850 border border-slate-200/60 dark:border-white/5 p-4 sm:p-5 shadow-sm space-y-4">
                
                <!-- Sidebar Header with Quick Reset -->
                <div class="flex items-center justify-between pb-1 border-b border-slate-100 dark:border-white/5">
                    <h2 class="text-sm sm:text-base font-extrabold text-slate-950 dark:text-white tracking-tight">
                        {{ app()->getLocale() === 'ar' ? 'الفلاتر' : 'Filters' }}
                    </h2>
                    <a 
                        href="{{ route('marketplace.services.index') }}" 
                        class="text-[11px] font-bold text-slate-400 hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white flex items-center gap-1 transition-colors"
                        title="{{ app()->getLocale() === 'ar' ? 'إعادة ضبط الفلاتر' : 'Reset Filters' }}"
                    >
                        <i class="ri-refresh-line text-xs"></i>
                        <span>{{ app()->getLocale() === 'ar' ? 'إعادة ضبط' : 'Reset' }}</span>
                    </a>
                </div>

                <!-- Filters Form -->
                <form action="{{ route('marketplace.services.index') }}" method="GET" id="sidebarFilterForm" class="space-y-3.5">
                    @if(request('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif
                    @if(request('sort'))
                        <input type="hidden" name="sort" value="{{ request('sort') }}">
                    @endif

                    <!-- Filter 1: Category Dropdown (Auto Submits on Change) -->
                    <div class="rounded-2xl bg-[#f4f5f8] dark:bg-dark-800 px-3 py-2">
                        <label class="block text-[9px] font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-wider mb-0.5">
                            {{ app()->getLocale() === 'ar' ? 'القسم' : 'Category' }}
                        </label>
                        <div class="relative">
                            <select 
                                name="category" 
                                onchange="document.getElementById('sidebarFilterForm').submit()"
                                class="w-full bg-transparent text-xs font-bold text-slate-900 dark:text-zinc-100 focus:outline-none cursor-pointer appearance-none pe-5"
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
                            <i class="ri-arrow-down-s-line absolute {{ app()->getLocale() === 'ar' ? 'left-0' : 'right-0' }} top-0 text-slate-400 pointer-events-none text-sm"></i>
                        </div>
                    </div>

                    <!-- Filter 2: Availability Dropdown (Auto Submits on Change) -->
                    <div class="rounded-2xl bg-[#f4f5f8] dark:bg-dark-800 px-3 py-2">
                        <label class="block text-[9px] font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-wider mb-0.5">
                            {{ app()->getLocale() === 'ar' ? 'نوع الخدمة والعمل' : 'Availability' }}
                        </label>
                        <div class="relative">
                            <select 
                                name="availability" 
                                onchange="document.getElementById('sidebarFilterForm').submit()"
                                class="w-full bg-transparent text-xs font-bold text-slate-900 dark:text-zinc-100 focus:outline-none cursor-pointer appearance-none pe-5"
                            >
                                <option value="">{{ app()->getLocale() === 'ar' ? 'جميع أنواع العمل' : 'Full-time / Project work' }}</option>
                                <option value="instant" {{ request('availability') === 'instant' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? 'تسليم فوري (أقل من 24 ساعة)' : 'Instant delivery' }}</option>
                                <option value="project" {{ request('availability') === 'project' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? 'عمل بالمشروع (Project work)' : 'Project work' }}</option>
                                <option value="hourly" {{ request('availability') === 'hourly' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? 'دعم مستمر وعقود' : 'Retainer / Support' }}</option>
                            </select>
                            <i class="ri-arrow-down-s-line absolute {{ app()->getLocale() === 'ar' ? 'left-0' : 'right-0' }} top-0 text-slate-400 pointer-events-none text-sm"></i>
                        </div>
                    </div>

                    <!-- Filter 3: Price Range (Dual-Handle Slider) -->
                    @php
                        $currencySymbol = $viewerCurrency->symbol ?? $viewerCurrency->currency ?? '$';
                        $curMinPrice = (int)(request('min_price') ?? 10);
                        $curMaxPrice = (int)(request('max_price') ?? 500);
                        if ($curMinPrice < 5) $curMinPrice = 5;
                        if ($curMaxPrice > 5000) $curMaxPrice = 5000;
                        if ($curMinPrice > $curMaxPrice) $curMinPrice = $curMaxPrice;
                    @endphp
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-bold text-slate-900 dark:text-zinc-200">
                                {{ app()->getLocale() === 'ar' ? 'نطاق السعر' : 'Price Range' }}
                            </label>
                            <span class="text-[11px] font-bold text-slate-800 dark:text-zinc-300 font-mono" id="priceDisplayRange">
                                {{ $currencySymbol }} {{ $curMinPrice }} - {{ $currencySymbol }} {{ $curMaxPrice }}
                            </span>
                        </div>

                        <!-- Interactive Dual Range Slider Track -->
                        <div class="range-slider-wrapper mb-2 px-1">
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

                        <!-- From / To Direct Number Inputs -->
                        <div class="grid grid-cols-2 gap-2">
                            <div class="rounded-xl bg-[#f4f5f8] dark:bg-dark-800 px-2.5 py-1.5">
                                <span class="block text-[8px] text-slate-400 dark:text-zinc-500 font-semibold">{{ app()->getLocale() === 'ar' ? 'من ' . $currencySymbol : 'From, ' . $currencySymbol }}</span>
                                <input 
                                    type="number" 
                                    name="min_price" 
                                    id="fromPriceInput" 
                                    value="{{ $curMinPrice }}" 
                                    placeholder="5" 
                                    oninput="onBoxPriceInput('min')"
                                    onchange="document.getElementById('sidebarFilterForm').submit()"
                                    class="w-full bg-transparent text-xs font-bold text-slate-900 dark:text-white focus:outline-none p-0"
                                >
                            </div>
                            <div class="rounded-xl bg-[#f4f5f8] dark:bg-dark-800 px-2.5 py-1.5">
                                <span class="block text-[8px] text-slate-400 dark:text-zinc-500 font-semibold">{{ app()->getLocale() === 'ar' ? 'إلى ' . $currencySymbol : 'To, ' . $currencySymbol }}</span>
                                <input 
                                    type="number" 
                                    name="max_price" 
                                    id="toPriceInput" 
                                    value="{{ $curMaxPrice }}" 
                                    placeholder="500" 
                                    oninput="onBoxPriceInput('max')"
                                    onchange="document.getElementById('sidebarFilterForm').submit()"
                                    class="w-full bg-transparent text-xs font-bold text-slate-900 dark:text-white focus:outline-none p-0"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Filter 4: Delivery Time -->
                    <div>
                        <label class="block text-xs font-bold text-slate-900 dark:text-zinc-200 mb-1.5">
                            {{ app()->getLocale() === 'ar' ? 'مدة التسليم' : 'Delivery Time' }}
                        </label>
                        <div class="rounded-2xl bg-[#f4f5f8] dark:bg-dark-800 px-3 py-2">
                            <div class="relative">
                                <select 
                                    name="delivery_time" 
                                    onchange="document.getElementById('sidebarFilterForm').submit()"
                                    class="w-full bg-transparent text-xs font-bold text-slate-900 dark:text-zinc-100 focus:outline-none cursor-pointer appearance-none pe-5"
                                >
                                    <option value="">{{ app()->getLocale() === 'ar' ? 'أي مدة تسليم' : 'Any Delivery Time' }}</option>
                                    <option value="1" {{ request('delivery_time') == '1' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? 'تسليم خلال 24 ساعة' : 'Up to 24 hours' }}</option>
                                    <option value="3" {{ request('delivery_time') == '3' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? 'حتى 3 أيام' : 'Up to 3 days' }}</option>
                                    <option value="7" {{ request('delivery_time') == '7' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? 'حتى 7 أيام' : 'Up to 7 days' }}</option>
                                    <option value="14" {{ request('delivery_time') == '14' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? 'حتى 14 يوم' : 'Up to 14 days' }}</option>
                                    <option value="30" {{ request('delivery_time') == '30' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? 'حتى شهر' : 'Up to 30 days' }}</option>
                                </select>
                                <i class="ri-arrow-down-s-line absolute {{ app()->getLocale() === 'ar' ? 'left-0' : 'right-0' }} top-0 text-slate-400 pointer-events-none text-sm"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Filter 5: Skills / Tags (Pill Shape) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-900 dark:text-zinc-200 mb-1.5">
                            {{ app()->getLocale() === 'ar' ? 'المهارات والتقنيات' : 'Skills' }}
                        </label>

                        @php
                            $popularSkills = [
                                'Wireframes', 'Spline', 'Illustration', 'Figma',
                                'Adobe Photoshop', 'Laravel', 'React', 'WhatsApp Bot',
                                'Python', 'AI Automation', 'WordPress'
                            ];
                            $activeSkill = request('skill');
                        @endphp

                        <div class="flex flex-wrap gap-1.5">
                            @foreach($popularSkills as $skill)
                                @php
                                    $isSelected = ($activeSkill === $skill);
                                @endphp
                                <a 
                                    href="{{ $isSelected ? route('marketplace.services.index', request()->except(['skill', 'page'])) : route('marketplace.services.index', array_merge(request()->except(['page']), ['skill' => $skill])) }}"
                                    class="px-2.5 py-1 rounded-full text-[10px] font-bold transition-all {{ $isSelected ? 'bg-slate-950 text-white shadow-sm' : 'bg-white dark:bg-dark-800 text-slate-800 dark:text-zinc-200 border border-slate-300/80 dark:border-white/10 hover:border-slate-950 dark:hover:border-white' }}"
                                >
                                    {{ $skill }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <!-- Action Button: Show Results -->
                    <div class="pt-1">
                        <button 
                            type="submit" 
                            class="w-full h-10 rounded-full bg-slate-950 hover:bg-slate-900 text-white dark:bg-white dark:hover:bg-zinc-200 dark:text-slate-950 font-bold text-xs transition-all shadow-sm flex items-center justify-center gap-1.5"
                        >
                            <span>{{ app()->getLocale() === 'ar' ? 'عرض النتائج (' . $services->total() . ')' : 'Show ' . $services->total() . ' results' }}</span>
                        </button>
                    </div>

                </form>

            </div>
        </aside>

        <!-- ========================================== -->
        <!-- RIGHT COLUMN: SERVICES CARDS GRID (2 Cols) -->
        <!-- ========================================== -->
        <main class="lg:col-span-8 xl:col-span-9">
            
            @if($services->count() > 0)
                <!-- 2-Column Spacious Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
                    
                    @foreach($services as $index => $service)
                        @php
                            $serviceUrl = route('marketplace.services.show', ['id' => $service->id, 'slug' => $service->slug ?? 'service']);
                            $minPrice = $service->packages->min('price') ?? $service->price ?? 5;
                            $firstPackage = $service->packages->first();
                            $currencyCode = $firstPackage && $firstPackage->currency ? ($firstPackage->currency->symbol ?? $firstPackage->currency->code ?? '$') : '$';
                            $sellerName = $service->seller->name ?? 'Jason Holls';
                            $sellerInitials = strtoupper(substr($sellerName, 0, 2));
                            $deliveryDays = $firstPackage->delivery_days ?? 2;
                            $isFavorited = !empty($service->is_favorited);
                        @endphp

                        <!-- Clean Card Component -->
                        <article class="p-5 sm:p-6 rounded-3xl bg-white dark:bg-dark-850 border border-slate-200/80 dark:border-white/5 flex flex-col justify-between group hover:border-slate-300 dark:hover:border-white/20 transition-all duration-200 shadow-sm">
                            
                            <div>
                                <!-- Top Row: Creator Avatar + Name/Role + Rating Badge + Bookmark -->
                                <div class="flex items-center justify-between gap-3 mb-4">
                                    
                                    <!-- Creator Info -->
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-amber-300 to-rose-300 dark:from-zinc-700 dark:to-zinc-800 text-slate-900 dark:text-zinc-200 flex items-center justify-center font-bold text-xs flex-shrink-0 shadow-sm">
                                            {{ $sellerInitials }}
                                        </div>
                                        <div class="min-w-0">
                                            <h4 class="font-bold text-xs sm:text-sm text-slate-950 dark:text-white truncate">
                                                {{ $sellerName }}
                                            </h4>
                                            <p class="text-[11px] text-slate-400 dark:text-zinc-500 truncate font-medium">
                                                {{ $service->category->name ?? 'UX/UI Designer' }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Rating Pill & Bookmark -->
                                    <div class="flex items-center gap-1.5 flex-shrink-0">
                                        <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-[#f4f5f8] dark:bg-dark-800 text-[11px] font-bold text-slate-900 dark:text-zinc-200">
                                            <i class="ri-star-fill text-amber-500 text-[11px]"></i>
                                            <span>4.9</span>
                                            <span class="text-[10px] text-slate-400 dark:text-zinc-500 font-normal">({{ $service->reviews_count ?? ($index % 5 + 18) }})</span>
                                        </div>

                                        <form action="{{ route('marketplace.favorites.toggle', $service->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button 
                                                type="submit" 
                                                class="w-6 h-6 flex items-center justify-center text-slate-400 hover:text-slate-900 dark:text-zinc-500 dark:hover:text-white transition-colors"
                                                title="Bookmark"
                                            >
                                                <i class="{{ $isFavorited ? 'ri-bookmark-fill text-slate-950 dark:text-white' : 'ri-bookmark-line' }} text-sm"></i>
                                            </button>
                                        </form>
                                    </div>

                                </div>

                                <!-- Middle: Prominent Service Title & Price -->
                                <div class="flex items-start justify-between gap-3 mb-3.5">
                                    <h3 class="font-bold text-sm sm:text-base text-slate-950 dark:text-white group-hover:text-brand-600 dark:group-hover:text-brand-300 transition-colors leading-snug line-clamp-2">
                                        <a href="{{ $serviceUrl }}">
                                            {{ $service->title }}
                                        </a>
                                    </h3>
                                    <div class="text-end flex-shrink-0 whitespace-nowrap pt-0.5">
                                        <span class="text-[11px] text-slate-400 dark:text-zinc-500 font-medium me-0.5">
                                            {{ app()->getLocale() === 'ar' ? 'من' : 'from' }}
                                        </span>
                                        <span class="text-base sm:text-lg font-extrabold text-slate-950 dark:text-white">
                                            {{ $currencyCode }}{{ number_format($minPrice, 0) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Metadata Badges Row (Outlined Pills with Icons) -->
                                <div class="flex items-center gap-2 flex-wrap mb-3.5">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border border-slate-300/80 dark:border-white/10 text-[11px] font-semibold text-slate-700 dark:text-zinc-300">
                                        <i class="ri-briefcase-line text-[11px] text-slate-400"></i>
                                        <span>{{ $deliveryDays }} {{ app()->getLocale() === 'ar' ? 'أيام تسليم' : 'days delivery' }}</span>
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border border-slate-300/80 dark:border-white/10 text-[11px] font-semibold text-slate-700 dark:text-zinc-300">
                                        <i class="ri-time-line text-[11px] text-slate-400"></i>
                                        <span>{{ app()->getLocale() === 'ar' ? 'عمل بالمشروع' : 'Project work' }}</span>
                                    </span>
                                </div>

                                <!-- Description Snippet (2-3 clean lines) -->
                                <p class="text-xs text-slate-500 dark:text-zinc-400 line-clamp-3 leading-relaxed">
                                    {{ $service->tagline ?? Str::limit(strip_tags($service->description ?? ''), 120) }}
                                </p>
                            </div>

                        </article>

                        <!-- Interspersed Promotional Hero Card -->
                        @if($index === 2)
                            <article class="p-7 rounded-3xl bg-slate-950 text-white flex flex-col justify-between relative overflow-hidden shadow-md min-h-[230px]">
                                <div class="absolute -right-10 -bottom-10 w-36 h-36 bg-indigo-600/25 rounded-full blur-3xl pointer-events-none"></div>
                                <div class="relative z-10">
                                    <h3 class="text-xl sm:text-2xl font-black tracking-tight leading-snug mb-2.5 text-white">
                                        {{ app()->getLocale() === 'ar' ? 'ربطك بأفضل المطورين والفريلانسرز المعتمدين' : 'Connecting You with Trusted Freelancers & Developers' }}
                                    </h3>
                                    <p class="text-xs text-zinc-300 leading-relaxed mb-5">
                                        {{ app()->getLocale() === 'ar' ? 'خدمات برمجية مخصصة وحلول ذكاء اصطناعي مع ضمان حماية الدفع Escrow بنسبة 100%.' : 'Discover custom software development and AI tools with 100% escrow buyer protection.' }}
                                    </p>
                                </div>

                                <div class="relative z-10">
                                    <a 
                                        href="{{ route('marketplace.services.create') }}" 
                                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-white hover:bg-zinc-100 text-slate-950 font-bold text-xs transition-all shadow-sm"
                                    >
                                        <span>{{ app()->getLocale() === 'ar' ? 'انضم كبائع الآن' : 'Become a Seller' }}</span>
                                        <i class="ri-arrow-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}-line font-bold"></i>
                                    </a>
                                </div>
                            </article>
                        @endif

                    @endforeach

                </div>

                <!-- Pagination -->
                <div class="mt-10">
                    {{ $services->links('pagination::tailwind') }}
                </div>

            @else
                <!-- Empty State -->
                <div class="text-center py-20 rounded-3xl bg-white dark:bg-dark-850 border border-slate-200/60 dark:border-white/5 p-8 shadow-sm">
                    <div class="w-14 h-14 rounded-full bg-slate-100 dark:bg-dark-800 text-slate-400 dark:text-zinc-500 flex items-center justify-center mx-auto text-xl mb-3">
                        <i class="ri-inbox-line"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">
                        {{ app()->getLocale() === 'ar' ? 'لم يتم العثور على خدمات مطابقة' : 'No matching services found' }}
                    </h3>
                    <p class="text-xs text-slate-400 dark:text-zinc-500 max-w-md mx-auto mb-5 leading-relaxed">
                        {{ app()->getLocale() === 'ar' ? 'جرب البحث بكلمات مختلفة أو إزالة الفلاتر المحددة للوصول لكافة الحلول البرمجية.' : 'Try adjusting your search criteria or resetting filters to browse all available services.' }}
                    </p>
                    <a href="{{ route('marketplace.services.index') }}" class="inline-flex items-center gap-1.5 px-5 py-2 rounded-full bg-slate-950 text-white dark:bg-white dark:text-slate-950 text-xs font-bold transition-all shadow-sm">
                        <i class="ri-refresh-line"></i>
                        <span>{{ app()->getLocale() === 'ar' ? 'عرض جميع الخدمات' : 'Browse All Services' }}</span>
                    </a>
                </div>
            @endif

        </main>

    </div>

</div>

<!-- Mobile Filters Drawer / Sheet Modal -->
<div id="mobileFiltersModal" class="fixed inset-0 z-50 hidden lg:hidden">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" onclick="toggleMobileFilters()"></div>
    <div class="fixed inset-y-0 start-0 max-w-xs w-full bg-white dark:bg-dark-900 p-6 overflow-y-auto shadow-2xl z-50">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-white/5 mb-5">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">
                {{ app()->getLocale() === 'ar' ? 'تصفية الخدمات' : 'Filter Services' }}
            </h3>
            <button type="button" onclick="toggleMobileFilters()" class="w-7 h-7 rounded-full bg-slate-100 dark:bg-dark-800 text-slate-500 flex items-center justify-center">
                <i class="ri-close-line text-base font-bold"></i>
            </button>
        </div>
        
        <form action="{{ route('marketplace.services.index') }}" method="GET" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 dark:text-zinc-400 mb-1">
                    {{ app()->getLocale() === 'ar' ? 'القسم' : 'Category' }}
                </label>
                <select name="category" onchange="this.form.submit()" class="w-full h-10 px-3 rounded-xl bg-slate-50 dark:bg-dark-800 border border-slate-200 dark:border-white/10 text-xs font-bold text-slate-800 dark:text-zinc-200">
                    <option value="">{{ app()->getLocale() === 'ar' ? 'الكل' : 'All Categories' }}</option>
                    @if(isset($categories))
                        @foreach($categories as $cat)
                            <option value="{{ $cat->slug }}" {{ request('category') === $cat->slug ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 dark:text-zinc-400 mb-1">
                    {{ app()->getLocale() === 'ar' ? 'الحد الأقصى للسعر ($)' : 'Max Price ($)' }}
                </label>
                <input type="number" name="max_price" value="{{ request('max_price') ?? 500 }}" class="w-full h-10 px-3 rounded-xl bg-slate-50 dark:bg-dark-800 border border-slate-200 dark:border-white/10 text-xs font-bold">
            </div>

            <button type="submit" class="w-full h-11 rounded-full bg-slate-950 dark:bg-white text-white dark:text-slate-950 font-bold text-xs mt-3">
                {{ app()->getLocale() === 'ar' ? 'تطبيق الفلاتر' : 'Apply Filters' }}
            </button>
        </form>
    </div>
</div>

<script>
    const dynamicCurrencySymbol = @json($currencySymbol);

    // Dual Range Slider Logic
    function updateSliderProgress() {
        const minInput = document.getElementById('rangeMinInput');
        const maxInput = document.getElementById('rangeMaxInput');
        const progress = document.getElementById('sliderProgress');
        const display = document.getElementById('priceDisplayRange');
        if (!minInput || !maxInput || !progress) return;

        const minVal = parseInt(minInput.value);
        const maxVal = parseInt(maxInput.value);
        const minLimit = parseInt(minInput.min);
        const maxLimit = parseInt(minInput.max);

        const leftPercent = ((minVal - minLimit) / (maxLimit - minLimit)) * 100;
        const rightPercent = 100 - (((maxVal - minLimit) / (maxLimit - minLimit)) * 100);

        progress.style.left = leftPercent + '%';
        progress.style.right = rightPercent + '%';

        if (display) {
            display.innerText = dynamicCurrencySymbol + ' ' + minVal + ' - ' + dynamicCurrencySymbol + ' ' + maxVal;
        }
    }

    function onDualSliderInput(changed) {
        const minInput = document.getElementById('rangeMinInput');
        const maxInput = document.getElementById('rangeMaxInput');
        const fromBox = document.getElementById('fromPriceInput');
        const toBox = document.getElementById('toPriceInput');
        if (!minInput || !maxInput) return;

        let minVal = parseInt(minInput.value);
        let maxVal = parseInt(maxInput.value);

        if (maxVal - minVal < 10) {
            if (changed === 'min') {
                minInput.value = maxVal - 10;
                minVal = parseInt(minInput.value);
            } else {
                maxInput.value = minVal + 10;
                maxVal = parseInt(maxInput.value);
            }
        }

        if (fromBox) fromBox.value = minVal;
        if (toBox) toBox.value = maxVal;
        updateSliderProgress();
    }

    function onBoxPriceInput(changed) {
        const minInput = document.getElementById('rangeMinInput');
        const maxInput = document.getElementById('rangeMaxInput');
        const fromBox = document.getElementById('fromPriceInput');
        const toBox = document.getElementById('toPriceInput');
        if (!minInput || !maxInput || !fromBox || !toBox) return;

        let minVal = parseInt(fromBox.value) || 5;
        let maxVal = parseInt(toBox.value) || 5000;

        if (minVal < 5) minVal = 5;
        if (maxVal > 5000) maxVal = 5000;
        if (minVal > maxVal) minVal = maxVal - 10;

        minInput.value = minVal;
        maxInput.value = maxVal;
        updateSliderProgress();
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', () => {
        updateSliderProgress();
    });

    // Smooth Category Scroll using Arrow Buttons
    function scrollCatTabs(offset) {
        const container = document.getElementById('catScrollContainer');
        if (container) {
            const isRTL = document.documentElement.dir === 'rtl' || document.documentElement.lang === 'ar';
            container.scrollBy({
                left: isRTL ? -offset : offset,
                behavior: 'smooth'
            });
        }
    }

    // Mouse Drag-to-Scroll for Category Bar
    (function() {
        const slider = document.getElementById('catScrollContainer');
        if (!slider) return;

        let isDown = false;
        let startX;
        let scrollLeft;

        slider.addEventListener('mousedown', (e) => {
            isDown = true;
            slider.classList.add('cursor-grabbing');
            startX = e.pageX - slider.offsetLeft;
            scrollLeft = slider.scrollLeft;
        });

        slider.addEventListener('mouseleave', () => {
            isDown = false;
            slider.classList.remove('cursor-grabbing');
        });

        slider.addEventListener('mouseup', () => {
            isDown = false;
            slider.classList.remove('cursor-grabbing');
        });

        slider.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - slider.offsetLeft;
            const walk = (x - startX) * 1.5;
            slider.scrollLeft = scrollLeft - walk;
        });

        // Mouse Wheel Horizontal Scroll
        slider.addEventListener('wheel', (e) => {
            if (e.deltaY !== 0) {
                e.preventDefault();
                slider.scrollLeft += e.deltaY;
            }
        }, { passive: false });
    })();

    function toggleMobileFilters() {
        const modal = document.getElementById('mobileFiltersModal');
        if (modal) {
            modal.classList.toggle('hidden');
        }
    }
</script>
@endsection

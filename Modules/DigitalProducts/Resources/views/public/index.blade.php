@extends('digitalproducts::layouts.library-master')

@section('content')
@php
    $heroBook = $featuredProducts->first() ?? $products->first();
    $secondaryBooks = $featuredProducts->count() > 1 
        ? $featuredProducts->skip(1)->take(2)->values() 
        : $products->where('id', '!=', $heroBook?->id)->take(2)->values();
@endphp

<div class="max-w-[1340px] mx-auto px-3 sm:px-6 lg:px-8 py-6 sm:py-10 space-y-16">

    <!-- ══════════════════════════════════════════════════════════════════════════ -->
    <!-- 1. THE MAIN BOOKHOUSE SHOWCASE CANVAS FRAME (Exact Layout from Screenshot)  -->
    <!-- ══════════════════════════════════════════════════════════════════════════ -->
    <div class="bookhouse-canvas overflow-hidden transition-all duration-300">
        
        <!-- ── Internal Header Bar ─────────────────────────────────────────── -->
        <div class="px-6 sm:px-10 pt-6 sm:pt-8 pb-4 flex items-center justify-between gap-4 border-b border-slate-100 dark:border-zinc-800/60">
            
            <!-- Left Controls: Coral Hamburger Box + Links + Search Icon -->
            <div class="flex items-center gap-3 sm:gap-6">
                <!-- Coral Menu Button -->
                <button type="button" onclick="document.getElementById('catalogSection')?.scrollIntoView({behavior: 'smooth'})" class="w-11 h-11 rounded-xl bg-[#ff7a59] hover:bg-[#f06443] text-white flex items-center justify-center shadow-md shadow-[#ff7a59]/30 transition-transform hover:scale-105" title="Menu">
                    <i class="ri-menu-2-line text-xl font-bold"></i>
                </button>

                <!-- Navigation Links -->
                <div class="hidden md:flex items-center gap-6 text-xs font-black uppercase tracking-wider text-slate-700 dark:text-zinc-300">
                    <a href="#catalogSection" class="hover:text-[#ff7a59] transition-colors">
                        {{ app()->getLocale() === 'ar' ? 'المجموعات' : 'COLLECTION' }}
                    </a>
                    <a href="{{ route('library.index', ['sort' => 'popular']) }}" class="hover:text-[#ff7a59] transition-colors">
                        {{ app()->getLocale() === 'ar' ? 'الأكثر مبيعاً' : 'TOP SELLING' }}
                    </a>
                    <!-- Round Search Button -->
                    <button type="button" onclick="document.getElementById('catalogSearchInput')?.focus(); document.getElementById('catalogSection')?.scrollIntoView({behavior: 'smooth'})" class="w-8 h-8 rounded-full bg-[#fdeee7] dark:bg-zinc-800 text-[#ff7a59] hover:bg-[#ff7a59] hover:text-white flex items-center justify-center transition-all shadow-xs" title="Search">
                        <i class="ri-search-line text-sm"></i>
                    </button>
                </div>
            </div>

            <!-- Center: Brand Title (Musoftware) -->
            <div class="flex items-center justify-center">
                <a href="{{ route('library.index') }}" class="text-2xl sm:text-3xl font-black font-editorial tracking-tight text-[#ff7a59] hover:opacity-95 transition-opacity">
                    Mu<span class="text-[#2e1f1d] dark:text-white">software</span>
                </a>
            </div>

            <!-- Right Controls: Profile Avatar, Create Account/Library Pill, Cart with Badge, Theme Toggle -->
            <div class="flex items-center gap-2.5 sm:gap-4">
                
                <!-- Dark / Light Mode Toggle Button -->
                <button 
                    type="button" 
                    id="theme-toggle-btn"
                    onclick="toggleDarkMode()" 
                    class="w-9 h-9 rounded-full flex items-center justify-center text-slate-600 dark:text-zinc-300 bg-[#fdeee7] dark:bg-zinc-800 hover:bg-[#ff7a59] hover:text-white transition-all shadow-2xs"
                    title="Toggle Theme"
                >
                    <i id="theme-toggle-icon" class="ri-moon-line text-sm dark:hidden"></i>
                    <i id="theme-toggle-icon-dark" class="ri-sun-line text-sm hidden dark:inline-block text-amber-400"></i>
                </button>

                <!-- User Icon Circle -->
                <div class="w-9 h-9 rounded-full bg-[#fdeee7] dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 flex items-center justify-center text-sm font-semibold">
                    <i class="ri-user-3-line"></i>
                </div>

                @auth
                    <!-- My Library Pill -->
                    <a href="{{ route('library.my_library') }}" class="hidden sm:inline-flex px-4 py-2 rounded-full text-xs font-extrabold bg-[#fdeee7] text-[#ff7a59] dark:bg-zinc-800 dark:text-zinc-200 hover:bg-[#ff7a59] hover:text-white transition-all">
                        {{ app()->getLocale() === 'ar' ? 'مكتبتي' : 'My Library' }}
                    </a>
                @else
                    <!-- Create Account Pill -->
                    <a href="{{ route('register') }}" class="hidden sm:inline-flex px-4 py-2 rounded-full text-xs font-extrabold bg-[#fdeee7] text-slate-800 dark:bg-zinc-800 dark:text-zinc-200 hover:bg-[#ff7a59] hover:text-white transition-all">
                        {{ app()->getLocale() === 'ar' ? 'تسجيل حساب' : 'Create Account' }}
                    </a>
                @endauth

                <!-- Shopping Bag Icon with Dot Badge -->
                <a href="#catalogSection" class="relative w-9 h-9 rounded-full bg-[#fdeee7] dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 hover:text-[#ff7a59] flex items-center justify-center text-sm transition-colors">
                    <i class="ri-shopping-bag-3-line"></i>
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-[#ff7a59]"></span>
                </a>
            </div>

        </div>

        <!-- ── Top Hero Showcase Section (50% Left / 50% Right) ───────────── -->
        @if($heroBook)
            <div class="relative px-6 sm:px-12 lg:px-16 py-10 sm:py-16">
                
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                    
                    <!-- Left Side (50%): Organic Multi-Color Blobs + 3D Book Cover -->
                    <div class="lg:col-span-6 flex items-center justify-center relative py-8">
                        
                        <!-- Organic Fluid Graphic Art Layer (Matching exact screenshot) -->
                        <div class="relative w-72 sm:w-96 h-72 sm:h-96 flex items-center justify-center">
                            
                            <!-- 1. Turquoise / Teal fluid blob shape on left -->
                            <div class="absolute -left-4 sm:-left-8 top-10 w-44 sm:w-56 h-44 sm:h-56 bg-[#00dfc0] dark:bg-[#00dfc0]/40 rounded-[45%_55%_70%_30%/45%_45%_55%_55%] transform -rotate-12 pointer-events-none"></div>

                            <!-- 2. Coral Hand-drawn hatch / zebra textured blob on top-right -->
                            <div class="absolute -right-2 sm:-right-4 top-2 w-48 sm:w-60 h-48 sm:h-60 rounded-[60%_40%_30%_70%/60%_30%_70%_40%] bg-[#ff7a59] dark:bg-[#ff7a59]/40 opacity-90 pointer-events-none overflow-hidden flex items-center justify-center">
                                <svg class="w-full h-full text-white/30" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <line x1="-20" y1="20" x2="120" y2="20" transform="rotate(-30 50 50)" />
                                    <line x1="-20" y1="35" x2="120" y2="35" transform="rotate(-30 50 50)" />
                                    <line x1="-20" y1="50" x2="120" y2="50" transform="rotate(-30 50 50)" />
                                    <line x1="-20" y1="65" x2="120" y2="65" transform="rotate(-30 50 50)" />
                                    <line x1="-20" y1="80" x2="120" y2="80" transform="rotate(-30 50 50)" />
                                    <line x1="-20" y1="95" x2="120" y2="95" transform="rotate(-30 50 50)" />
                                </svg>
                            </div>

                            <!-- 3. Soft Amber background touch -->
                            <div class="absolute bottom-2 left-6 w-36 h-36 bg-[#fed7aa] dark:bg-[#fed7aa]/20 rounded-full blur-xl pointer-events-none"></div>

                            <!-- 4. The 3D Elevated Standing Book Cover (Centerpiece) -->
                            <a href="{{ route('library.show', $heroBook->slug) }}" class="relative z-20 block w-52 sm:w-64 aspect-[3/4] rounded-lg overflow-hidden book-shadow-3d bg-[#141418] border border-black/10 dark:border-white/10 group">
                                @if($heroBook->cover_image_path)
                                    <img src="{{ $heroBook->cover_url }}" alt="{{ $heroBook->title }}" class="w-full h-full object-cover rounded-lg">
                                @else
                                    <div class="w-full h-full bg-[#1a1a22] p-6 flex flex-col justify-between text-white">
                                        <div class="text-[10px] font-mono tracking-widest text-[#00dfc0] uppercase">
                                            {{ $heroBook->category?->name ?? 'PLAYBOOK' }}
                                        </div>
                                        <div class="my-auto">
                                            <span class="text-[11px] text-zinc-400 uppercase tracking-widest font-semibold block mb-1">THE BUSINESS OF</span>
                                            <h3 class="text-2xl font-black font-editorial leading-tight text-white">{{ $heroBook->title }}</h3>
                                        </div>
                                        <div class="flex items-center justify-between text-xs text-zinc-400 font-mono">
                                            <span>{{ $heroBook->author_name ?? 'Musoftware' }}</span>
                                            <i class="ri-book-read-line text-lg text-[#ff7a59]"></i>
                                        </div>
                                    </div>
                                @endif
                            </a>

                        </div>

                    </div>

                    <!-- Right Side (50%): Kicker, Display Title, 3-Col Meta, Rating, Price & CTA -->
                    <div class="lg:col-span-5 space-y-6 text-start">
                        
                        <!-- Tracked Kicker (T H E   B U S I N E S S   O F) -->
                        <span class="text-xs sm:text-sm font-extrabold uppercase tracking-[0.3em] text-[#b58376] dark:text-[#ff9f87] font-mono block">
                            {{ app()->getLocale() === 'ar' ? 'إصدار تطبيقي مميز' : 'T H E   B U S I N E S S   O F' }}
                        </span>

                        <!-- Huge Editorial Title (Design) -->
                        <h1 class="text-4xl sm:text-6xl lg:text-7xl font-black text-[#2e1f1d] dark:text-white font-editorial tracking-tight leading-[1.05]">
                            <a href="{{ route('library.show', $heroBook->slug) }}" class="hover:text-[#ff7a59] transition-colors">
                                {{ $heroBook->title }}
                            </a>
                        </h1>

                        <!-- 3-Column Specifications Grid -->
                        <div class="grid grid-cols-3 gap-3 sm:gap-6 pt-2 pb-2">
                            <div>
                                <span class="text-slate-400 dark:text-zinc-500 block text-[11px] font-semibold mb-0.5">
                                    {{ app()->getLocale() === 'ar' ? 'المؤلف' : 'Author' }}
                                </span>
                                <p class="font-extrabold text-[#2e1f1d] dark:text-zinc-100 text-xs sm:text-sm">
                                    {{ $heroBook->author_name ?? 'Keith Granet' }}
                                </p>
                            </div>
                            <div>
                                <span class="text-slate-400 dark:text-zinc-500 block text-[11px] font-semibold mb-0.5">
                                    {{ app()->getLocale() === 'ar' ? 'التصنيف' : 'Type' }}
                                </span>
                                <p class="font-extrabold text-[#2e1f1d] dark:text-zinc-100 text-xs sm:text-sm">
                                    {{ $heroBook->category?->name ?? 'Design thinking' }}
                                </p>
                            </div>
                            <div>
                                <span class="text-slate-400 dark:text-zinc-500 block text-[11px] font-semibold mb-0.5">
                                    {{ app()->getLocale() === 'ar' ? 'الصيغة' : 'Layout' }}
                                </span>
                                <p class="font-extrabold text-[#2e1f1d] dark:text-zinc-100 text-xs sm:text-sm">
                                    {{ $heroBook->page_count ? $heroBook->page_count . ' p. & PDF' : 'Printed & PDF' }}
                                </p>
                            </div>
                        </div>

                        <!-- 5 Star Rating Row -->
                        <div class="flex items-center gap-2 text-xs">
                            <div class="flex items-center text-[#ff7a59] text-sm gap-0.5">
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-half-fill"></i>
                            </div>
                            <span class="text-slate-500 dark:text-zinc-400 font-semibold">
                                4.1 / {{ max(150, ($heroBook->download_count ?? 15) * 12) }} {{ app()->getLocale() === 'ar' ? 'مراجعة' : 'Reviews' }}
                            </span>
                        </div>

                        <!-- Price & Curved Pill Button (ADD TO CART) -->
                        <div class="flex items-center gap-6 pt-3 flex-wrap">
                            <div class="text-3xl sm:text-4xl font-black font-editorial text-[#2e1f1d] dark:text-white">
                                @if($heroBook->is_free)
                                    <span class="text-[#00dfc0]">{{ app()->getLocale() === 'ar' ? 'مجاناً' : 'FREE' }}</span>
                                @else
                                    ${{ number_format($heroBook->price, 2) }}
                                @endif
                            </div>

                            <a href="{{ route('library.show', $heroBook->slug) }}" class="pill-btn-coral text-xs sm:text-sm uppercase tracking-wider">
                                <i class="ri-shopping-bag-3-fill text-base"></i>
                                <span>{{ $heroBook->is_free ? (app()->getLocale() === 'ar' ? 'تحميل مجاني' : 'GET BOOK') : (app()->getLocale() === 'ar' ? 'شراء الكتاب' : 'ADD TO CART') }}</span>
                            </a>
                        </div>

                    </div>

                    <!-- Far Right Edge (1 Col): Vertical Slider Indicator (Screenshot DNA) -->
                    <div class="hidden lg:flex lg:col-span-1 flex-col items-center justify-center gap-4 py-8">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-zinc-700"></span>
                        <div class="w-1.5 h-12 rounded-full bg-[#ff7a59] shadow-sm"></div>
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-zinc-700"></span>
                    </div>

                </div>

            </div>
        @endif

        <!-- ── Bottom Secondary Split Showcase (2 Cards Exactly like Screenshot) ── -->
        @if($secondaryBooks->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 border-t border-slate-200/80 dark:border-zinc-800/80 bg-[#fbf8f5] dark:bg-[#16161c]">
                @foreach($secondaryBooks as $index => $book)
                    @php
                        $isFirstCard = $index === 0;
                        $blobColor = $isFirstCard ? 'bg-[#ff7a59] dark:bg-[#ff7a59]/40' : 'bg-[#00dfc0] dark:bg-[#00dfc0]/40';
                    @endphp
                    <div class="p-6 sm:p-10 flex flex-col sm:flex-row items-center gap-6 {{ $isFirstCard ? 'border-b md:border-b-0 md:border-r border-slate-200/80 dark:border-zinc-800/80' : '' }}">
                        
                        <!-- Left: Organic Blob with 3D Standing Book -->
                        <div class="relative w-36 sm:w-44 h-48 sm:h-56 flex items-center justify-center flex-shrink-0">
                            <!-- Fluid Blob Shape -->
                            <div class="absolute w-32 sm:w-40 h-32 sm:h-40 {{ $blobColor }} rounded-[45%_55%_65%_35%/50%_50%_50%_50%] transform -rotate-12 opacity-85"></div>
                            
                            <!-- 3D Standing Book Cover -->
                            <a href="{{ route('library.show', $book->slug) }}" class="relative z-10 block w-24 sm:w-30 aspect-[3/4] rounded-md overflow-hidden book-shadow-3d bg-[#1a1a22] group">
                                @if($book->cover_image_path)
                                    <img src="{{ $book->cover_url }}" alt="{{ $book->title }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full bg-[#1e1e26] p-3 flex flex-col justify-between text-white text-[9px]">
                                        <span class="text-[#ff7a59] font-mono font-bold">{{ $book->category?->name ?? 'PDF' }}</span>
                                        <h4 class="font-bold line-clamp-2">{{ $book->title }}</h4>
                                        <span class="text-zinc-400">{{ $book->author_name ?? 'Musoftware' }}</span>
                                    </div>
                                @endif
                            </a>
                        </div>

                        <!-- Right: Title, Author, Price & Pill CTA -->
                        <div class="flex-1 text-center sm:text-start space-y-2">
                            <h3 class="text-lg sm:text-2xl font-black text-[#2e1f1d] dark:text-white font-editorial line-clamp-2 leading-tight">
                                <a href="{{ route('library.show', $book->slug) }}" class="hover:text-[#ff7a59] transition-colors">
                                    {{ $book->title }}
                                </a>
                            </h3>
                            
                            <p class="text-xs text-slate-500 dark:text-zinc-400 font-medium">
                                {{ app()->getLocale() === 'ar' ? 'بواسطة' : 'By' }} {{ $book->author_name ?? 'Author' }}
                            </p>

                            <!-- Price with Strikethrough -->
                            <div class="flex items-center justify-center sm:justify-start gap-2 pt-1 pb-2">
                                <span class="text-xl sm:text-2xl font-black font-editorial text-[#ff7a59]">
                                    @if($book->is_free)
                                        <span class="text-[#00dfc0]">{{ app()->getLocale() === 'ar' ? 'مجاناً' : 'FREE' }}</span>
                                    @else
                                        ${{ number_format($book->price, 2) }}
                                    @endif
                                </span>
                                @if(!$book->is_free)
                                    <span class="text-xs sm:text-sm text-slate-400 dark:text-zinc-500 line-through font-mono font-semibold">
                                        ${{ number_format($book->price * 1.3, 2) }}
                                    </span>
                                @endif
                            </div>

                            <!-- Coral Pill Button -->
                            <div>
                                <a href="{{ route('library.show', $book->slug) }}" class="pill-btn-coral text-xs uppercase tracking-wider py-2 px-5">
                                    <span>{{ $book->is_free ? (app()->getLocale() === 'ar' ? 'تحميل الكتاب' : 'GET BOOK') : (app()->getLocale() === 'ar' ? 'شراء الكتاب' : 'ADD TO CART') }}</span>
                                </a>
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>
        @endif

    </div>


    <!-- ══════════════════════════════════════════════════════════════════════════ -->
    <!-- 2. COMPLETE CATALOG EXPLORATION (Search, Categories & Books Grid)         -->
    <!-- ══════════════════════════════════════════════════════════════════════════ -->
    <section id="catalogSection" class="pt-8 space-y-8">
        
        <!-- Header & Search -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 pb-6 border-b border-slate-300/80 dark:border-zinc-800">
            <div>
                <span class="text-xs font-black uppercase tracking-widest text-[#ff7a59] block mb-1">
                    {{ app()->getLocale() === 'ar' ? 'المكتبة الرقمية الكاملة' : 'EXPLORE COLLECTION' }}
                </span>
                <h2 class="text-2xl sm:text-4xl font-black text-[#2e1f1d] dark:text-white font-editorial">
                    {{ app()->getLocale() === 'ar' ? 'جميع الكتب والإصدارات التطبيقية' : 'Complete Digital Library' }}
                </h2>
            </div>

            <!-- Search Bar Form -->
            <form action="{{ route('library.index') }}" method="GET" class="relative max-w-md w-full">
                @if(request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                @if(request('type'))
                    <input type="hidden" name="type" value="{{ request('type') }}">
                @endif
                <div class="relative flex items-center">
                    <input 
                        type="text" 
                        id="catalogSearchInput"
                        name="q" 
                        value="{{ request('q') }}" 
                        placeholder="{{ app()->getLocale() === 'ar' ? 'ابحث عن كتاب، مؤلف، أو موضوع...' : 'Search books, authors, playbooks...' }}" 
                        class="w-full h-12 ps-5 pe-24 rounded-full bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 text-xs focus:outline-none focus:border-[#ff7a59] shadow-sm"
                    >
                    <button type="submit" class="absolute {{ app()->getLocale() === 'ar' ? 'left-1.5' : 'right-1.5' }} px-5 py-2 rounded-full bg-[#ff7a59] hover:bg-[#f06443] text-white text-xs font-bold transition-all shadow-sm">
                        {{ app()->getLocale() === 'ar' ? 'بحث' : 'Search' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Filter Pills Bar -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            
            <!-- Category Pills -->
            <div class="flex items-center gap-2 overflow-x-auto w-full pb-2 sm:pb-0 scrollbar-none">
                <a href="{{ route('library.index', array_merge(request()->except(['category', 'page']))) }}" class="px-4 py-2 rounded-full text-xs font-extrabold whitespace-nowrap transition-all {{ !request('category') ? 'bg-[#2e1f1d] text-white dark:bg-white dark:text-[#2e1f1d] shadow-sm' : 'bg-white dark:bg-zinc-900 text-slate-700 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-zinc-800' }}">
                    {{ app()->getLocale() === 'ar' ? 'الكل' : 'All' }} ({{ $products->total() }})
                </a>
                @foreach($categories as $category)
                    <a href="{{ route('library.index', array_merge(request()->except(['page']), ['category' => $category->slug])) }}" class="px-4 py-2 rounded-full text-xs font-extrabold whitespace-nowrap transition-all {{ request('category') === $category->slug ? 'bg-[#ff7a59] text-white shadow-md' : 'bg-white dark:bg-zinc-900 text-slate-700 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-zinc-800' }}">
                        {{ $category->name }}
                        <span class="text-[10px] opacity-80">({{ $category->published_products_count }})</span>
                    </a>
                @endforeach
            </div>

            <!-- Free / Paid Toggle Pills -->
            <div class="inline-flex rounded-full bg-white dark:bg-zinc-900 p-1 border border-slate-200 dark:border-zinc-800 flex-shrink-0 shadow-xs">
                <a href="{{ route('library.index', array_merge(request()->except(['type', 'page']))) }}" class="px-3.5 py-1 rounded-full text-xs font-bold transition-all {{ !request('type') ? 'bg-[#ff7a59] text-white shadow-xs' : 'text-slate-600 dark:text-zinc-400' }}">
                    {{ app()->getLocale() === 'ar' ? 'الكل' : 'All' }}
                </a>
                <a href="{{ route('library.index', array_merge(request()->except(['page']), ['type' => 'free'])) }}" class="px-3.5 py-1 rounded-full text-xs font-bold transition-all {{ request('type') === 'free' ? 'bg-[#00dfc0] text-slate-900 font-extrabold shadow-xs' : 'text-slate-600 dark:text-zinc-400 hover:text-[#00dfc0]' }}">
                    {{ app()->getLocale() === 'ar' ? 'مجاني' : 'Free' }}
                </a>
                <a href="{{ route('library.index', array_merge(request()->except(['page']), ['type' => 'paid'])) }}" class="px-3.5 py-1 rounded-full text-xs font-bold transition-all {{ request('type') === 'paid' ? 'bg-[#ff7a59] text-white shadow-xs' : 'text-slate-600 dark:text-zinc-400 hover:text-[#ff7a59]' }}">
                    {{ app()->getLocale() === 'ar' ? 'مدفوع' : 'Paid' }}
                </a>
            </div>
        </div>

        <!-- 4-Column Responsive Catalog Grid -->
        @if($products->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                @foreach($products as $book)
                    <div class="group rounded-3xl bg-white dark:bg-[#121217] border border-slate-200/80 dark:border-zinc-800/80 p-5 flex flex-col justify-between hover:border-[#ff7a59] dark:hover:border-[#ff7a59] transition-all duration-300 shadow-sm hover:shadow-xl">
                        
                        <!-- 3D Standing Cover -->
                        <a href="{{ route('library.show', $book->slug) }}" class="relative aspect-[3/4] w-full rounded-2xl overflow-hidden bg-[#faf4ef] dark:bg-zinc-950 p-4 mb-4 flex items-center justify-center">
                            @if($book->cover_image_path)
                                <img src="{{ $book->cover_url }}" alt="{{ $book->title }}" class="w-full h-full object-cover rounded-xl book-shadow-3d group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="w-full h-full rounded-xl bg-[#1a1a22] p-4 flex flex-col justify-between text-white book-shadow-3d">
                                    <span class="text-[10px] text-[#00dfc0] font-mono uppercase">{{ $book->category?->name ?? 'EBOOK' }}</span>
                                    <h4 class="font-bold text-xs line-clamp-3">{{ $book->title }}</h4>
                                    <span class="text-[10px] text-zinc-400 font-mono">{{ $book->author_name ?? 'Musoftware' }}</span>
                                </div>
                            @endif

                            <!-- Top Price Badge -->
                            <div class="absolute top-3 right-3">
                                @if($book->is_free)
                                    <span class="px-2.5 py-0.5 rounded-full bg-[#00dfc0] text-slate-900 text-[10px] font-black shadow-md">
                                        FREE
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full bg-[#2e1f1d] text-white dark:bg-white dark:text-[#2e1f1d] text-[10px] font-black font-mono shadow-md">
                                        ${{ number_format($book->price, 2) }}
                                    </span>
                                @endif
                            </div>
                        </a>

                        <!-- Details -->
                        <div class="space-y-2 flex-1 flex flex-col justify-between">
                            <div>
                                <span class="text-[10px] font-black text-[#ff7a59] uppercase tracking-wider block">
                                    {{ $book->category?->name ?? 'Playbook' }}
                                </span>

                                <h3 class="text-sm font-black text-[#2e1f1d] dark:text-white font-editorial line-clamp-2 leading-snug group-hover:text-[#ff7a59] transition-colors">
                                    <a href="{{ route('library.show', $book->slug) }}">
                                        {{ $book->title }}
                                    </a>
                                </h3>

                                <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-1">
                                    {{ app()->getLocale() === 'ar' ? 'تأليف:' : 'By' }} {{ $book->author_name ?? 'Musoftwares' }}
                                </p>
                            </div>

                            <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-zinc-800/80 mt-auto">
                                <div class="text-xs font-mono font-black text-[#2e1f1d] dark:text-white">
                                    @if($book->is_free)
                                        <span class="text-[#00dfc0] font-bold">{{ app()->getLocale() === 'ar' ? 'مجاني' : 'FREE' }}</span>
                                    @else
                                        ${{ number_format($book->price, 2) }}
                                    @endif
                                </div>

                                <a href="{{ route('library.show', $book->slug) }}" class="px-4 py-1.5 rounded-full bg-[#ff7a59] hover:bg-[#f06443] text-white text-[11px] font-extrabold shadow-xs transition-transform hover:scale-105 flex items-center gap-1">
                                    <span>{{ $book->is_free ? (app()->getLocale() === 'ar' ? 'تحميل' : 'Get') : (app()->getLocale() === 'ar' ? 'شراء' : 'Add') }}</span>
                                    <i class="ri-arrow-right-line text-xs"></i>
                                </a>
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($products->hasPages())
                <div class="pt-6">
                    {{ $products->links() }}
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="text-center py-16 rounded-3xl bg-white dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 p-8 shadow-sm">
                <i class="ri-book-line text-4xl text-[#ff7a59] mx-auto mb-3 block"></i>
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">
                    {{ app()->getLocale() === 'ar' ? 'لم يتم العثور على كتب' : 'No books found' }}
                </h3>
                <a href="{{ route('library.index') }}" class="px-5 py-2 rounded-full bg-[#ff7a59] text-white text-xs font-bold shadow-md inline-block mt-3">
                    {{ app()->getLocale() === 'ar' ? 'عرض كل الكتب' : 'View All' }}
                </a>
            </div>
        @endif

    </section>

</div>
@endsection

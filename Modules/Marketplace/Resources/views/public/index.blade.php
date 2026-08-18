@extends('marketplace::layouts.marketplace-master')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Hero Header & Search Section -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-b from-brand-950/60 via-dark-850 to-dark-900 border border-brand-500/20 p-8 sm:p-14 mb-10 text-center shadow-2xl">
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-brand-600/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-indigo-500/15 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 max-w-3xl mx-auto">
            <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-brand-500/15 border border-brand-500/30 text-brand-300 text-xs font-semibold uppercase tracking-wider mb-4 shadow-sm">
                <i class="ri-sparkling-2-fill text-amber-400"></i>
                {{ app()->getLocale() === 'ar' ? 'سوق المطورين والخدمات البرمجية المعتمدة' : 'Verified Software Services & Solutions' }}
            </span>
            <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tight mb-4 leading-tight">
                {{ app()->getLocale() === 'ar' ? 'اعثر على أفضل الخدمات البرمجية والحلول الرقمية' : 'Find Top Software Services & Digital Solutions' }}
            </h1>
            <p class="text-sm sm:text-base text-zinc-300 mb-8 leading-relaxed max-w-2xl mx-auto">
                {{ app()->getLocale() === 'ar' ? 'اكتشف واشترِ خدمات برمجية متقدمة، بوتات واتساب، تطبيقات ذكية، ومواقع ويب مخصصة مع حماية الدفع بالضمان التام.' : 'Discover custom software development, WhatsApp automation bots, web applications, and AI integrations with 100% escrow buyer protection.' }}
            </p>

            <!-- Search Form -->
            <form action="{{ route('marketplace.services.index') }}" method="GET" class="relative max-w-2xl mx-auto">
                @if(request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                @if(request('sort'))
                    <input type="hidden" name="sort" value="{{ request('sort') }}">
                @endif
                <div class="relative flex items-center">
                    <i class="ri-search-2-line absolute {{ app()->getLocale() === 'ar' ? 'right-4' : 'left-4' }} text-zinc-400 text-lg"></i>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') ?? request('q') }}" 
                        placeholder="{{ app()->getLocale() === 'ar' ? 'ابحث عن خدمة، مطور، لغة برمجة، أو أداة...' : 'What service or solution are you looking for today?' }}" 
                        class="w-full h-14 {{ app()->getLocale() === 'ar' ? 'pr-12 pl-32' : 'pl-12 pr-32' }} rounded-2xl bg-zinc-900/90 border border-zinc-700/80 text-white placeholder-zinc-500 text-sm focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 transition-all shadow-inner"
                    >
                    <button type="submit" class="absolute {{ app()->getLocale() === 'ar' ? 'left-2' : 'right-2' }} px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold transition-all shadow-md shadow-brand-500/25 flex items-center gap-1.5">
                        <span>{{ app()->getLocale() === 'ar' ? 'بحث' : 'Search' }}</span>
                    </button>
                </div>
            </form>

            <!-- Popular Keywords -->
            <div class="mt-4 flex items-center justify-center gap-2 flex-wrap text-xs text-zinc-400">
                <span class="text-zinc-500">{{ app()->getLocale() === 'ar' ? 'شائع:' : 'Popular:' }}</span>
                <a href="{{ route('marketplace.services.index', ['search' => 'WhatsApp Bot']) }}" class="px-2.5 py-1 rounded-lg bg-zinc-800/80 hover:bg-zinc-700/80 text-zinc-300 transition-colors">WhatsApp Bot</a>
                <a href="{{ route('marketplace.services.index', ['search' => 'Laravel']) }}" class="px-2.5 py-1 rounded-lg bg-zinc-800/80 hover:bg-zinc-700/80 text-zinc-300 transition-colors">Laravel</a>
                <a href="{{ route('marketplace.services.index', ['search' => 'React']) }}" class="px-2.5 py-1 rounded-lg bg-zinc-800/80 hover:bg-zinc-700/80 text-zinc-300 transition-colors">React</a>
                <a href="{{ route('marketplace.services.index', ['search' => 'AI Automation']) }}" class="px-2.5 py-1 rounded-lg bg-zinc-800/80 hover:bg-zinc-700/80 text-zinc-300 transition-colors">AI Automation</a>
                <a href="{{ route('marketplace.services.index', ['search' => 'WordPress']) }}" class="px-2.5 py-1 rounded-lg bg-zinc-800/80 hover:bg-zinc-700/80 text-zinc-300 transition-colors">WordPress</a>
            </div>
        </div>
    </div>

    <!-- Category Navigation & Filters Bar -->
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 mb-8 pb-6 border-b border-zinc-800/80">
        
        <!-- Category Pills Bar -->
        <div class="flex items-center gap-2 overflow-x-auto w-full lg:w-auto pb-2 lg:pb-0 scrollbar-none">
            <a 
                href="{{ route('marketplace.services.index', array_merge(request()->except(['category', 'category_id', 'category_slug', 'page']))) }}" 
                class="px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all {{ !request('category') && !request('category_id') && !request('category_slug') && empty($filters['category']) ? 'bg-brand-600 text-white shadow-lg shadow-brand-500/20' : 'bg-zinc-800/70 text-zinc-400 hover:text-white hover:bg-zinc-800 border border-zinc-700/40' }}"
            >
                <i class="ri-apps-2-line me-1"></i>
                {{ app()->getLocale() === 'ar' ? 'جميع الخدمات' : 'All Services' }} ({{ $services->total() }})
            </a>

            @if(isset($categories) && $categories->count() > 0)
                @foreach($categories as $category)
                    @php
                        $isActive = (request('category') === $category->slug || request('category') === (string)$category->id || (isset($filters['category']) && $filters['category'] === $category->slug));
                    @endphp
                    <a 
                        href="{{ route('marketplace.services.index', array_merge(request()->except(['page']), ['category' => $category->slug])) }}" 
                        class="px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all {{ $isActive ? 'bg-brand-600 text-white shadow-lg shadow-brand-500/20' : 'bg-zinc-800/70 text-zinc-400 hover:text-white hover:bg-zinc-800 border border-zinc-700/40' }}"
                    >
                        {{ $category->name }}
                        @if(isset($category->services_count))
                            <span class="text-[10px] opacity-75 font-normal">({{ $category->services_count }})</span>
                        @endif
                    </a>
                @endforeach
            @endif
        </div>

        <!-- Sort & Active Filters -->
        <div class="flex items-center gap-3 w-full lg:w-auto justify-between lg:justify-end">
            
            @if(request('search') || request('category') || !empty($filters['tag']))
                <a href="{{ route('marketplace.services.index') }}" class="text-xs text-rose-400 hover:text-rose-300 flex items-center gap-1">
                    <i class="ri-close-circle-line"></i>
                    <span>{{ app()->getLocale() === 'ar' ? 'مسح الفلاتر' : 'Clear Filters' }}</span>
                </a>
            @endif

            <!-- Sort Dropdown -->
            <form action="{{ route('marketplace.services.index') }}" method="GET" id="sortForm">
                @foreach(request()->except(['sort', 'page']) as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <div class="relative flex items-center">
                    <select 
                        name="sort" 
                        onchange="document.getElementById('sortForm').submit()" 
                        class="h-9 ps-3 pe-8 rounded-xl bg-zinc-900 border border-zinc-800 text-xs text-zinc-300 focus:outline-none focus:border-zinc-700 cursor-pointer"
                    >
                        <option value="latest" {{ request('sort') == 'latest' || !request('sort') ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? 'الأحدث' : 'Latest' }}</option>
                        <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? 'السعر: الأقل للأعلى' : 'Price: Low to High' }}</option>
                        <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? 'السعر: الأعلى للأقل' : 'Price: High to Low' }}</option>
                        <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? 'الأكثر طلباً' : 'Most Popular' }}</option>
                    </select>
                </div>
            </form>
        </div>

    </div>

    <!-- Active Search Notice -->
    @if(request('search'))
        <div class="mb-6 p-4 rounded-2xl bg-brand-950/40 border border-brand-500/20 text-brand-200 text-xs flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="ri-search-line text-brand-400 text-base"></i>
                <span>{{ app()->getLocale() === 'ar' ? 'نتائج البحث عن:' : 'Search results for:' }} <strong class="text-white font-semibold">"{{ request('search') }}"</strong> ({{ $services->total() }} {{ app()->getLocale() === 'ar' ? 'خدمة' : 'services' }})</span>
            </div>
            <a href="{{ route('marketplace.services.index', request()->except(['search', 'q'])) }}" class="text-zinc-400 hover:text-white underline">
                {{ app()->getLocale() === 'ar' ? 'إلغاء البحث' : 'Clear search' }}
            </a>
        </div>
    @endif

    <!-- Services Grid -->
    @if($services->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($services as $service)
                @php
                    $serviceUrl = route('marketplace.services.show', ['id' => $service->id, 'slug' => $service->slug ?? 'service']);
                    $minPrice = $service->packages->min('price') ?? $service->price ?? 5;
                    $firstPackage = $service->packages->first();
                    $currencyCode = $firstPackage && $firstPackage->currency ? ($firstPackage->currency->symbol ?? $firstPackage->currency->code ?? '$') : '$';
                    $coverImage = $service->cover_image ? (Str::startsWith($service->cover_image, ['http://', 'https://', '/']) ? $service->cover_image : '/uploads/'.ltrim($service->cover_image, '/')) : null;
                @endphp

                <article class="group relative rounded-2xl card-surface overflow-hidden flex flex-col transition-all duration-300 hover:-translate-y-1">
                    
                    <!-- Cover Image Container -->
                    <a href="{{ $serviceUrl }}" class="block relative aspect-video w-full overflow-hidden bg-zinc-950">
                        @if($coverImage)
                            <img 
                                src="{{ $coverImage }}" 
                                alt="{{ $service->title }}" 
                                loading="lazy" 
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                            >
                        @else
                            <div class="w-full h-full bg-gradient-to-br from-brand-950 via-dark-800 to-dark-900 border-b border-zinc-800 p-6 flex flex-col justify-between">
                                <i class="ri-code-s-slash-line text-4xl text-brand-400/80"></i>
                                <span class="text-xs font-bold text-zinc-400 tracking-wider uppercase">{{ $service->category->name ?? 'Software Service' }}</span>
                            </div>
                        @endif

                        @if($service->category)
                            <span class="absolute top-3 {{ app()->getLocale() === 'ar' ? 'right-3' : 'left-3' }} px-2.5 py-1 rounded-lg bg-dark-900/80 backdrop-blur-md border border-white/10 text-[11px] font-medium text-zinc-200">
                                {{ $service->category->name }}
                            </span>
                        @endif

                        @if($service->is_featured)
                            <span class="absolute top-3 {{ app()->getLocale() === 'ar' ? 'left-3' : 'right-3' }} px-2 py-0.5 rounded-md bg-amber-500 text-dark-900 text-[10px] font-black uppercase tracking-wider flex items-center gap-1 shadow-md">
                                <i class="ri-vip-crown-fill"></i> Featured
                            </span>
                        @endif
                    </a>

                    <!-- Card Body -->
                    <div class="p-5 flex-1 flex flex-col justify-between">
                        
                        <div>
                            <!-- Seller Info -->
                            <div class="flex items-center gap-2.5 mb-3">
                                <div class="w-7 h-7 rounded-full bg-brand-600/30 border border-brand-500/30 text-brand-300 flex items-center justify-center font-bold text-[11px] flex-shrink-0">
                                    {{ strtoupper(substr($service->seller->name ?? 'S', 0, 2)) }}
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-xs font-medium text-zinc-300 truncate hover:text-white flex items-center gap-1">
                                        {{ $service->seller->name ?? 'Verified Seller' }}
                                        <i class="ri-verified-badge-fill text-brand-400 text-xs" title="Verified Seller"></i>
                                    </span>
                                </div>
                            </div>

                            <!-- Service Title -->
                            <h3 class="font-bold text-sm text-white group-hover:text-brand-300 transition-colors line-clamp-2 mb-2 leading-snug">
                                <a href="{{ $serviceUrl }}">
                                    {{ $service->title }}
                                </a>
                            </h3>

                            @if($service->tagline)
                                <p class="text-xs text-zinc-400 line-clamp-2 mb-4 leading-relaxed">
                                    {{ $service->tagline }}
                                </p>
                            @endif
                        </div>

                        <!-- Card Footer: Ratings & Starting Price -->
                        <div class="pt-4 border-t border-zinc-800/80 flex items-center justify-between mt-4">
                            
                            <!-- Delivery / Rating -->
                            <div class="flex items-center gap-2 text-xs">
                                <div class="flex items-center text-amber-400 font-semibold gap-1">
                                    <i class="ri-star-fill text-xs"></i>
                                    <span>5.0</span>
                                </div>
                                <span class="text-zinc-500">|</span>
                                <span class="text-zinc-400 text-[11px] flex items-center gap-1">
                                    <i class="ri-time-line text-zinc-500"></i>
                                    {{ $firstPackage->delivery_days ?? 3 }}d
                                </span>
                            </div>

                            <!-- Price -->
                            <div class="text-end">
                                <span class="text-[10px] text-zinc-400 uppercase tracking-wider block">
                                    {{ app()->getLocale() === 'ar' ? 'تبدأ من' : 'Starting at' }}
                                </span>
                                <span class="text-base font-black text-emerald-400">
                                    {{ $currencyCode }}{{ number_format($minPrice, 2) }}
                                </span>
                            </div>

                        </div>

                    </div>

                </article>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-12">
            {{ $services->links('pagination::tailwind') }}
        </div>
    @else
        <!-- Empty State -->
        <div class="text-center py-20 rounded-3xl bg-zinc-900/50 border border-zinc-800 p-8 my-8">
            <div class="w-16 h-16 rounded-2xl bg-zinc-800/80 text-zinc-400 flex items-center justify-center mx-auto text-3xl mb-4">
                <i class="ri-inbox-line"></i>
            </div>
            <h3 class="text-lg font-bold text-white mb-2">
                {{ app()->getLocale() === 'ar' ? 'لم يتم العثور على خدمات مطابقة' : 'No matching services found' }}
            </h3>
            <p class="text-sm text-zinc-400 max-w-md mx-auto mb-6">
                {{ app()->getLocale() === 'ar' ? 'جرب البحث بكلمات مختلفة أو تصفح الأقسام الأخرى من شريط الفلاتر.' : 'Try adjusting your search criteria or browse another category from the filter bar.' }}
            </p>
            <a href="{{ route('marketplace.services.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-semibold transition-all shadow-md">
                <i class="ri-refresh-line"></i>
                <span>{{ app()->getLocale() === 'ar' ? 'عرض جميع الخدمات' : 'Browse All Services' }}</span>
            </a>
        </div>
    @endif

    <!-- SEO Content & FAQ Section -->
    <section class="mt-20 pt-12 border-t border-zinc-800">
        <div class="max-w-4xl mx-auto">
            <h2 class="text-2xl font-bold text-white mb-4 text-center">
                {{ app()->getLocale() === 'ar' ? 'لماذا تختار سوق خدمات MuSoftwares؟' : 'Why Choose MuSoftwares Software Marketplace?' }}
            </h2>
            <p class="text-sm text-zinc-400 text-center mb-10 leading-relaxed">
                {{ app()->getLocale() === 'ar' ? 'نوفر لك بيئة عمل موثوقة تجمع أفضل المطورين وخبراء التكنولوجيا مع ضمان حماية الدفع Escrow لضمان تسليم مشاريعك بأعلى كفاءة.' : 'We provide a secure, escrow-backed marketplace connecting businesses with verified software engineers, automation specialists, and designers.' }}
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="p-6 rounded-2xl bg-zinc-900/60 border border-zinc-800">
                    <h3 class="text-base font-bold text-white mb-2 flex items-center gap-2">
                        <i class="ri-shield-keyhole-line text-emerald-400"></i>
                        {{ app()->getLocale() === 'ar' ? 'كيف يعمل نظام الدفع بالضمان (Escrow)؟' : 'How does the Escrow Payment system work?' }}
                    </h3>
                    <p class="text-xs text-zinc-400 leading-relaxed">
                        {{ app()->getLocale() === 'ar' ? 'عند طلب الخدمة، يتم حجز المبلغ في حساب وسيط آمن. لا يتسلم البائع مستحقاته إلا بعد أن تستلم الملفات وتراجع الأكواد البرمجية وتوافق على جودة التنفيذ.' : 'When ordering a service, your funds are safely held in an escrow intermediary account. The seller is only paid after you inspect and approve the completed deliverable.' }}
                    </p>
                </div>

                <div class="p-6 rounded-2xl bg-zinc-900/60 border border-zinc-800">
                    <h3 class="text-base font-bold text-white mb-2 flex items-center gap-2">
                        <i class="ri-loop-right-line text-brand-400"></i>
                        {{ app()->getLocale() === 'ar' ? 'هل تشمل الخدمات جولات تعديل ومراجعة؟' : 'Can I request revisions during the project?' }}
                    </h3>
                    <p class="text-xs text-zinc-400 leading-relaxed">
                        {{ app()->getLocale() === 'ar' ? 'نعم، تتضمن كل باقة عدد محدد من جولات التعديل لضمان مطابقة العمل للمواصفات المطلوبة بنسبة 100% مع إمكانية المحادثة المباشرة مع المطور.' : 'Yes, each package includes specified revision rounds. You can request changes and communicate directly with the developer inside your order room.' }}
                    </p>
                </div>

                <div class="p-6 rounded-2xl bg-zinc-900/60 border border-zinc-800">
                    <h3 class="text-base font-bold text-white mb-2 flex items-center gap-2">
                        <i class="ri-time-line text-amber-400"></i>
                        {{ app()->getLocale() === 'ar' ? 'ماذا يحدث إذا تأخر البائع عن موعد التسليم؟' : 'What happens if a deadline is missed?' }}
                    </h3>
                    <p class="text-xs text-zinc-400 leading-relaxed">
                        {{ app()->getLocale() === 'ar' ? 'في حال تجاوز البائع الموعد النهائي دون اتفاق مسبق، يمكنك إلغاء الطلب بضغطة زر واسترداد كامل الرصيد فوراً إلى محفظتك.' : 'If a seller fails to deliver within the agreed timeframe without mutual consent, you can cancel the order and receive an instant refund to your wallet balance.' }}
                    </p>
                </div>

                <div class="p-6 rounded-2xl bg-zinc-900/60 border border-zinc-800">
                    <h3 class="text-base font-bold text-white mb-2 flex items-center gap-2">
                        <i class="ri-user-add-line text-purple-400"></i>
                        {{ app()->getLocale() === 'ar' ? 'كيف يمكنني بيع خدماتي البرمجية؟' : 'How can I become a seller?' }}
                    </h3>
                    <p class="text-xs text-zinc-400 leading-relaxed">
                        {{ app()->getLocale() === 'ar' ? 'يمكنك نشر خدماتك البرمجية وأدواتك الرقمية مجاناً عبر الضغط على "أضف خدمتك" وإدخال تفاصيل الباقات والأسعار لتبدأ في استقبال الطلبات فور مراجعتها.' : 'You can publish your software and digital skills by clicking "Sell a Service", defining your package tiers and pricing to start receiving client orders.' }}
                    </p>
                </div>
            </div>
        </div>
    </section>

</div>
@endsection

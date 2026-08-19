@extends('marketplace::layouts.marketplace-master')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8" id="service-app">

    <!-- Breadcrumb Navigation -->
    <nav class="flex items-center gap-2 text-xs text-slate-400 dark:text-zinc-400 mb-6 flex-wrap" aria-label="Breadcrumb">
        <a href="{{ url('/') }}" class="hover:text-slate-900 dark:hover:text-white transition-colors flex items-center gap-1">
            <i class="ri-home-4-line"></i>
            <span>{{ app()->getLocale() === 'ar' ? 'الرئيسية' : 'Home' }}</span>
        </a>
        <i class="ri-arrow-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}-s-line text-slate-300 dark:text-zinc-600"></i>
        <a href="{{ route('marketplace.services.index') }}" class="hover:text-slate-900 dark:hover:text-white transition-colors">
            {{ app()->getLocale() === 'ar' ? 'سوق الخدمات' : 'Explore' }}
        </a>
        @if($service->category)
            <i class="ri-arrow-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}-s-line text-slate-300 dark:text-zinc-600"></i>
            <a href="{{ route('marketplace.services.index', ['category' => $service->category->slug]) }}" class="hover:text-slate-900 dark:hover:text-white transition-colors">
                {{ $service->category->name }}
            </a>
        @endif
        <i class="ri-arrow-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}-s-line text-slate-300 dark:text-zinc-600"></i>
        <span class="text-slate-800 dark:text-zinc-300 font-medium truncate max-w-xs">{{ $service->title }}</span>
    </nav>

    <!-- Service Header: Category Tag, Title & Seller Bar -->
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-3">
            @if($service->category)
                <a href="{{ route('marketplace.services.index', ['category' => $service->category->slug]) }}" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300 border border-brand-200 dark:border-brand-500/30 text-xs font-bold">
                    <i class="ri-folder-2-line"></i>
                    {{ $service->category->name }}
                </a>
            @endif

            @if($service->is_featured)
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-100 text-amber-900 dark:bg-amber-500/20 dark:text-amber-300 border border-amber-300 dark:border-amber-500/40 text-xs font-bold uppercase tracking-wider">
                    <i class="ri-vip-crown-fill text-amber-500"></i> Featured
                </span>
            @endif
        </div>

        <h1 class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight mb-4 leading-tight">
            {{ $service->title }}
        </h1>

        @if($service->tagline)
            <p class="text-sm sm:text-base text-slate-600 dark:text-zinc-400 mb-6 leading-relaxed">
                {{ $service->tagline }}
            </p>
        @endif

        <!-- Seller Overview Bar (Modern Card) -->
        <div class="flex flex-wrap items-center gap-4 sm:gap-6 py-3.5 px-5 rounded-2xl bg-white dark:bg-dark-850 border border-slate-200 dark:border-white/10 text-xs shadow-sm">
            <!-- Seller Profile -->
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-2xl bg-gradient-to-tr from-brand-600/20 to-indigo-500/20 dark:from-brand-500/30 dark:to-indigo-500/30 border border-brand-500/30 text-brand-600 dark:text-brand-300 flex items-center justify-center font-bold text-xs">
                    {{ strtoupper(substr($service->seller->name ?? 'S', 0, 2)) }}
                </div>
                <div>
                    <span class="font-bold text-slate-900 dark:text-white flex items-center gap-1">
                        {{ $service->seller->name ?? 'Verified Seller' }}
                        <i class="ri-verified-badge-fill text-brand-500 dark:text-brand-400 text-xs" title="Verified Expert"></i>
                    </span>
                    <span class="text-[10px] text-slate-400 dark:text-zinc-500 block">
                        {{ app()->getLocale() === 'ar' ? 'مطور معتمد' : 'Verified Specialist' }}
                    </span>
                </div>
            </div>

            <div class="h-6 w-px bg-slate-200 dark:bg-white/10 hidden sm:block"></div>

            <!-- Rating Summary -->
            <div class="flex items-center gap-1.5 text-amber-500 font-bold">
                <i class="ri-star-fill text-sm"></i>
                <span class="text-slate-900 dark:text-white">5.0</span>
                <span class="text-slate-400 dark:text-zinc-500 font-normal">({{ $service->reviews ? $service->reviews->count() : 18 }} {{ app()->getLocale() === 'ar' ? 'تقييم' : 'reviews' }})</span>
            </div>

            <div class="h-6 w-px bg-slate-200 dark:bg-white/10 hidden sm:block"></div>

            <!-- Orders count -->
            <div class="flex items-center gap-1.5 text-slate-600 dark:text-zinc-400">
                <i class="ri-shopping-bag-3-line text-brand-500"></i>
                <span>{{ $service->completed_orders_count ?? 12 }} {{ app()->getLocale() === 'ar' ? 'طلب مكتمل' : 'orders completed' }}</span>
            </div>

            <!-- Favorite & Share Button -->
            <div class="ms-auto flex items-center gap-2">
                <form action="{{ route('marketplace.favorites.toggle', $service->id) }}" method="POST" class="inline">
                    @csrf
                    <button 
                        type="submit" 
                        class="px-3.5 py-1.5 rounded-full bg-slate-100 dark:bg-dark-800 hover:bg-slate-200 dark:hover:bg-dark-750 text-slate-700 dark:text-zinc-300 border border-slate-200 dark:border-white/10 transition-all flex items-center gap-1.5 text-xs font-semibold"
                    >
                        <i class="{{ !empty($service->is_favorited) ? 'ri-bookmark-fill text-rose-500' : 'ri-bookmark-line' }}"></i>
                        <span>{{ !empty($service->is_favorited) ? (app()->getLocale() === 'ar' ? 'محفوظ' : 'Saved') : (app()->getLocale() === 'ar' ? 'حفظ' : 'Bookmark') }}</span>
                    </button>
                </form>

                <button 
                    type="button" 
                    onclick="copyShareUrl()" 
                    id="copyBtn"
                    class="px-3.5 py-1.5 rounded-full bg-slate-100 dark:bg-dark-800 hover:bg-slate-200 dark:hover:bg-dark-750 text-slate-700 dark:text-zinc-300 border border-slate-200 dark:border-white/10 transition-all flex items-center gap-1.5 text-xs font-semibold"
                >
                    <i class="ri-share-forward-line"></i>
                    <span id="copyBtnText">{{ app()->getLocale() === 'ar' ? 'مشاركة' : 'Share' }}</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Left Column: Media Gallery, Description, FAQs, Reviews (8 cols) -->
        <div class="lg:col-span-8 space-y-8">
            
            @php
                $mediaList = [];
                if ($service->cover_image) {
                    $mediaList[] = [
                        'type' => 'image',
                        'url' => Str::startsWith($service->cover_image, ['http://', 'https://', '/']) ? $service->cover_image : '/uploads/'.ltrim($service->cover_image, '/'),
                        'thumb' => Str::startsWith($service->cover_image, ['http://', 'https://', '/']) ? $service->cover_image : '/uploads/'.ltrim($service->cover_image, '/'),
                    ];
                }
                if (!empty($service->gallery) && is_array($service->gallery)) {
                    foreach ($service->gallery as $gItem) {
                        if ($gItem) {
                            $gUrl = Str::startsWith($gItem, ['http://', 'https://', '/']) ? $gItem : '/uploads/'.ltrim($gItem, '/');
                            $mediaList[] = [
                                'type' => 'image',
                                'url' => $gUrl,
                                'thumb' => $gUrl,
                            ];
                        }
                    }
                }
                if (!empty($service->video_url)) {
                    $vUrl = trim($service->video_url);
                    if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/|youtube\.com\/shorts\/)([a-zA-Z0-9_-]+)/', $vUrl, $matches)) {
                        $videoEmbedUrl = "https://www.youtube.com/embed/" . $matches[1];
                        array_unshift($mediaList, [
                            'type' => 'video',
                            'url' => $videoEmbedUrl,
                            'thumb' => "https://img.youtube.com/vi/{$matches[1]}/hqdefault.jpg",
                        ]);
                    }
                }
            @endphp

            <!-- Media Gallery Box -->
            <div class="rounded-3xl bg-white dark:bg-dark-850 overflow-hidden p-3 border border-slate-200 dark:border-white/10 shadow-sm">
                <!-- Main Active Media Stage -->
                <div class="relative aspect-video w-full bg-slate-900 rounded-2xl overflow-hidden flex items-center justify-center" id="mainMediaContainer">
                    @if(!empty($mediaList))
                        @if($mediaList[0]['type'] === 'video')
                            <iframe 
                                id="activeVideoFrame"
                                src="{{ $mediaList[0]['url'] }}" 
                                class="w-full h-full border-0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen
                            ></iframe>
                        @else
                            <img 
                                id="activeImage"
                                src="{{ $mediaList[0]['url'] }}" 
                                alt="{{ $service->title }}" 
                                class="w-full h-full object-contain cursor-zoom-in"
                                onclick="openFullscreenImage(this.src)"
                            >
                        @endif
                    @else
                        <div class="w-full h-full bg-gradient-to-br from-slate-950 via-zinc-900 to-brand-950 flex flex-col items-center justify-center text-zinc-500">
                            <i class="ri-code-s-slash-line text-6xl text-brand-400/50 mb-2"></i>
                            <span class="text-sm font-semibold text-zinc-300">{{ $service->title }}</span>
                        </div>
                    @endif
                </div>

                <!-- Thumbnail Carousel -->
                @if(count($mediaList) > 1)
                    <div class="flex items-center gap-3 overflow-x-auto p-3 scrollbar-none">
                        @foreach($mediaList as $index => $item)
                            <button 
                                type="button" 
                                onclick="switchMedia({{ $index }}, '{{ $item['type'] }}', '{{ $item['url'] }}')" 
                                class="thumb-btn relative flex-shrink-0 w-20 h-14 rounded-xl overflow-hidden border-2 {{ $index === 0 ? 'border-brand-500 shadow-md shadow-brand-500/30' : 'border-slate-200 dark:border-zinc-800 hover:border-brand-400' }} bg-slate-100 dark:bg-dark-800 transition-all"
                                id="thumb-{{ $index }}"
                            >
                                @if($item['type'] === 'video')
                                    <div class="w-full h-full flex items-center justify-center bg-zinc-900">
                                        <i class="ri-play-circle-fill text-2xl text-rose-500"></i>
                                    </div>
                                @else
                                    <img src="{{ $item['thumb'] }}" alt="thumb" class="w-full h-full object-cover">
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Service Description & Details -->
            <div class="rounded-3xl bg-white dark:bg-dark-850 p-6 sm:p-8 border border-slate-200 dark:border-white/10 shadow-sm">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                    <i class="ri-file-text-line text-brand-500"></i>
                    {{ app()->getLocale() === 'ar' ? 'تفاصيل ومواصفات الخدمة' : 'Service Description & Deliverables' }}
                </h2>

                <div class="prose dark:prose-invert max-w-none text-slate-700 dark:text-zinc-300 text-sm sm:text-base leading-relaxed space-y-4">
                    {!! nl2br(e($service->description)) !!}
                </div>

                <!-- Requirements & Deliverable Highlights -->
                @if(!empty($service->requirements))
                    <div class="mt-8 pt-6 border-t border-slate-100 dark:border-white/5">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                            <i class="ri-clipboard-line text-amber-500"></i>
                            {{ app()->getLocale() === 'ar' ? 'متطلبات البدء في المشروع:' : 'Buyer Requirements to Start:' }}
                        </h3>
                        @if(is_array($service->requirements))
                            <ul class="space-y-2 text-xs sm:text-sm text-slate-600 dark:text-zinc-400 bg-slate-50 dark:bg-dark-800 p-4 rounded-2xl border border-slate-200 dark:border-white/10">
                                @foreach($service->requirements as $req)
                                    <li class="flex items-start gap-2">
                                        <i class="ri-check-line text-emerald-500 mt-0.5 flex-shrink-0"></i>
                                        <span>{{ is_array($req) ? ($req['text'] ?? $req['title'] ?? json_encode($req)) : $req }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-xs sm:text-sm text-slate-600 dark:text-zinc-400 bg-slate-50 dark:bg-dark-800 p-4 rounded-2xl border border-slate-200 dark:border-white/10">
                                {{ $service->requirements }}
                            </p>
                        @endif
                    </div>
                @endif

                <!-- Tags / Technologies -->
                @if(!empty($service->tags) && is_array($service->tags))
                    <div class="mt-8 pt-6 border-t border-slate-100 dark:border-white/5 flex items-center gap-2 flex-wrap">
                        <span class="text-xs font-semibold text-slate-500 dark:text-zinc-400">{{ app()->getLocale() === 'ar' ? 'التقنيات المستخدمة:' : 'Technologies:' }}</span>
                        @foreach($service->tags as $tag)
                            <a href="{{ route('marketplace.services.index', ['skill' => $tag]) }}" class="px-3 py-1 rounded-full bg-slate-100 dark:bg-dark-800 text-xs font-semibold text-slate-700 dark:text-zinc-300 border border-slate-200 dark:border-white/10 hover:border-brand-500 hover:text-brand-500 transition-colors">
                                #{{ $tag }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Trust FAQ Accordion -->
            <div class="rounded-3xl bg-white dark:bg-dark-850 p-6 sm:p-8 border border-slate-200 dark:border-white/10 shadow-sm">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                    <i class="ri-questionnaire-line text-brand-500"></i>
                    {{ app()->getLocale() === 'ar' ? 'الأسئلة الشائعة حول الخدمة والضمان' : 'Frequently Asked Questions' }}
                </h2>

                <div class="space-y-3">
                    <div class="rounded-2xl bg-slate-50 dark:bg-dark-800 border border-slate-200 dark:border-white/10 p-4">
                        <button type="button" onclick="toggleFaq(1)" class="w-full flex items-center justify-between text-start text-xs sm:text-sm font-bold text-slate-900 dark:text-white focus:outline-none">
                            <span>{{ app()->getLocale() === 'ar' ? 'كيف يحميني نظام الضمان المالي (Escrow)؟' : 'How does Escrow Buyer Protection work?' }}</span>
                            <i id="faq-icon-1" class="ri-arrow-down-s-line text-slate-400 text-lg transition-transform"></i>
                        </button>
                        <div id="faq-body-1" class="mt-3 text-xs text-slate-600 dark:text-zinc-400 leading-relaxed hidden">
                            {{ app()->getLocale() === 'ar' ? 'عند طلبك لهذه الخدمة يتم حجز المبلغ في رصيد وسيط آمن. لا يتسلم المطور مستحقاته إلا بعد أن تستلم العمل بالكامل وتوافق على جودته.' : 'Your payment is held safely in escrow. The seller only receives funds after you inspect, test, and approve the delivered solution.' }}
                        </div>
                    </div>

                    <div class="rounded-2xl bg-slate-50 dark:bg-dark-800 border border-slate-200 dark:border-white/10 p-4">
                        <button type="button" onclick="toggleFaq(2)" class="w-full flex items-center justify-between text-start text-xs sm:text-sm font-bold text-slate-900 dark:text-white focus:outline-none">
                            <span>{{ app()->getLocale() === 'ar' ? 'هل تشمل الخدمة جولات مراجعة وتعديل؟' : 'Are revision rounds included?' }}</span>
                            <i id="faq-icon-2" class="ri-arrow-down-s-line text-slate-400 text-lg transition-transform"></i>
                        </button>
                        <div id="faq-body-2" class="mt-3 text-xs text-slate-600 dark:text-zinc-400 leading-relaxed hidden">
                            {{ app()->getLocale() === 'ar' ? 'نعم، تتضمن باقات الخدمة جولات تعديل ومراجعة يمكنك طلبها مباشرة من غرفة الطلب مع المطور.' : 'Yes, each service package includes defined revision rounds. You can request revisions directly through your dedicated order room.' }}
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Column: Package Pricing & Checkout Box (4 cols) -->
        <div class="lg:col-span-4 sticky top-24 space-y-6">
            
            @php
                $currencySymbol = $viewerCurrency->symbol ?? $viewerCurrency->currency ?? '$';
                $packages = $service->packages && $service->packages->count() > 0 
                    ? $service->packages 
                    : collect([
                        (object)[
                            'id' => 0,
                            'name' => 'Standard',
                            'description' => $service->description ?: 'Standard package deliverables.',
                            'price' => $service->is_free ? 0 : 5,
                            'delivery_days' => 3,
                            'revisions' => 2,
                            'currency' => $viewerCurrency ?? (object)['symbol' => '$', 'currency' => 'USD']
                        ]
                    ]);
                $firstPkg = $packages->first();
                $firstPkgSymbol = $firstPkg->currency->symbol ?? $firstPkg->currency->currency ?? $currencySymbol;
            @endphp

            <!-- Package Selection Card -->
            <div class="rounded-3xl bg-white dark:bg-dark-850 border border-slate-200/90 dark:border-white/10 p-5 sm:p-6 shadow-lg relative overflow-hidden">
                
                <!-- Package Tabs if multiple -->
                @if($packages->count() > 1)
                    <div class="flex items-center gap-1 rounded-2xl bg-[#f4f5f8] dark:bg-dark-800 p-1 border border-slate-200/70 dark:border-white/5 mb-5 overflow-x-auto scrollbar-none">
                        @foreach($packages as $pIdx => $pkg)
                            @php
                                $pkgSymbol = $pkg->currency->symbol ?? $pkg->currency->currency ?? $currencySymbol;
                            @endphp
                            <button 
                                type="button" 
                                onclick="selectPackage({{ $pkg->id }}, {{ $pkg->price }}, '{{ addslashes($pkg->name) }}', '{{ addslashes($pkg->description ?? '') }}', {{ $pkg->delivery_days ?? 3 }}, {{ $pkg->revisions ?? 1 }}, '{{ $pkgSymbol }}')" 
                                class="pkg-tab-btn flex-1 py-1.5 px-3 rounded-xl text-xs font-bold transition-all truncate text-center {{ $pIdx === 0 ? 'bg-slate-950 text-white dark:bg-white dark:text-slate-950 shadow-sm' : 'text-slate-500 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white' }}"
                                id="pkg-tab-{{ $pkg->id }}"
                                title="{{ $pkg->name }}"
                            >
                                {{ $pkg->name }}
                            </button>
                        @endforeach
                    </div>
                @endif

                <!-- Active Package Display -->
                <div class="mb-5">
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <h3 class="text-sm sm:text-base font-extrabold text-slate-950 dark:text-white leading-snug line-clamp-2" id="pkgDisplayTitle">
                            {{ $firstPkg->name }}
                        </h3>
                        <div class="text-lg sm:text-xl font-extrabold text-slate-950 dark:text-white whitespace-nowrap flex-shrink-0 text-end" id="pkgDisplayPrice">
                            <span class="text-xs text-slate-400 dark:text-zinc-500 font-semibold me-0.5">{{ $firstPkgSymbol }}</span>{{ number_format($firstPkg->price, 2) }}
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-zinc-400 leading-relaxed min-h-[32px]" id="pkgDisplayDesc">
                        {{ $firstPkg->description ?: 'Full package deliverables and source files.' }}
                    </p>
                </div>

                <!-- Package Highlights -->
                <div class="space-y-3 py-4 border-y border-slate-100 dark:border-white/5 mb-6 text-xs text-slate-700 dark:text-zinc-300">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 dark:text-zinc-400 flex items-center gap-1.5">
                            <i class="ri-time-line text-brand-500"></i>
                            {{ app()->getLocale() === 'ar' ? 'مدة التسليم' : 'Delivery Time' }}
                        </span>
                        <strong class="text-slate-900 dark:text-white" id="pkgDisplayDelivery">{{ $firstPkg->delivery_days ?? 3 }} {{ app()->getLocale() === 'ar' ? 'أيام' : 'Days' }}</strong>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 dark:text-zinc-400 flex items-center gap-1.5">
                            <i class="ri-loop-right-line text-emerald-500"></i>
                            {{ app()->getLocale() === 'ar' ? 'جولات التعديل' : 'Revisions' }}
                        </span>
                        <strong class="text-slate-900 dark:text-white" id="pkgDisplayRevisions">{{ $firstPkg->revisions ?? 2 }} {{ app()->getLocale() === 'ar' ? 'تعديلات' : 'Rounds' }}</strong>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 dark:text-zinc-400 flex items-center gap-1.5">
                            <i class="ri-shield-check-line text-amber-500"></i>
                            {{ app()->getLocale() === 'ar' ? 'الدفع بالضمان' : 'Escrow Protection' }}
                        </span>
                        <strong class="text-emerald-600 dark:text-emerald-400">100% Guaranteed</strong>
                    </div>
                </div>

                <!-- Order Now Form Submission -->
                <form action="{{ route('marketplace.orders.store') }}" method="POST" id="orderForm">
                    @csrf
                    <input type="hidden" name="package_id" id="selectedPackageIdInput" value="{{ $firstPkg->id }}">
                    <input type="hidden" name="service_id" value="{{ $service->id }}">

                    <button 
                        type="submit" 
                        class="w-full h-12 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white dark:bg-white dark:hover:bg-zinc-200 dark:text-slate-950 font-bold text-sm transition-all shadow-lg flex items-center justify-center gap-2"
                    >
                        <i class="ri-shopping-cart-2-line text-base font-bold"></i>
                        <span>{{ app()->getLocale() === 'ar' ? 'طلب الخدمة الآن' : 'Continue to Checkout' }}</span>
                    </button>
                </form>

                <p class="text-[11px] text-center text-slate-400 dark:text-zinc-500 mt-3 flex items-center justify-center gap-1">
                    <i class="ri-lock-line text-emerald-500"></i>
                    <span>{{ app()->getLocale() === 'ar' ? 'دفع آمن بالضمان التام' : 'Secure & Escrow-Protected Checkout' }}</span>
                </p>

            </div>

        </div>

    </div>

</div>

<!-- Fullscreen Image Modal (Simple Zoom) -->
<div id="imageModal" class="fixed inset-0 z-50 bg-black/90 hidden items-center justify-center p-4 backdrop-blur-md" onclick="closeFullscreenImage()">
    <div class="relative max-w-5xl max-h-[90vh] flex items-center justify-center">
        <img id="modalImg" src="" alt="preview" class="max-w-full max-h-[85vh] rounded-2xl object-contain shadow-2xl">
        <button type="button" class="absolute top-4 right-4 w-10 h-10 rounded-full bg-zinc-800 text-white flex items-center justify-center text-xl hover:bg-zinc-700">
            <i class="ri-close-line"></i>
        </button>
    </div>
</div>

@push('scripts')
<script>
    const defaultCurrencySymbol = @json($currencySymbol);

    function selectPackage(id, price, name, desc, delivery, revisions, pkgSymbol) {
        const symbol = pkgSymbol || defaultCurrencySymbol;
        document.getElementById('selectedPackageIdInput').value = id;
        document.getElementById('pkgDisplayTitle').innerText = name;
        document.getElementById('pkgDisplayPrice').innerHTML = '<span class="text-xs text-slate-400 dark:text-zinc-500 font-semibold me-0.5">' + symbol + '</span>' + Number(price).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('pkgDisplayDesc').innerText = desc || 'Full package deliverables and source files.';
        document.getElementById('pkgDisplayDelivery').innerText = delivery + ' ' + (document.documentElement.lang === 'ar' ? 'أيام' : 'Days');
        document.getElementById('pkgDisplayRevisions').innerText = revisions + ' ' + (document.documentElement.lang === 'ar' ? 'تعديلات' : 'Rounds');

        document.querySelectorAll('.pkg-tab-btn').forEach(btn => {
            btn.classList.remove('bg-slate-950', 'text-white', 'dark:bg-white', 'dark:text-slate-950', 'shadow-sm');
            btn.classList.add('text-slate-500', 'dark:text-zinc-400');
        });
        const activeBtn = document.getElementById('pkg-tab-' + id);
        if (activeBtn) {
            activeBtn.classList.add('bg-slate-950', 'text-white', 'dark:bg-white', 'dark:text-slate-950', 'shadow-sm');
            activeBtn.classList.remove('text-slate-500', 'dark:text-zinc-400');
        }
    }

    function switchMedia(index, type, url) {
        const container = document.getElementById('mainMediaContainer');
        if (type === 'video') {
            container.innerHTML = `<iframe src="${url}" class="w-full h-full border-0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
        } else {
            container.innerHTML = `<img id="activeImage" src="${url}" alt="Service Image" class="w-full h-full object-contain cursor-zoom-in" onclick="openFullscreenImage(this.src)">`;
        }

        document.querySelectorAll('.thumb-btn').forEach(btn => {
            btn.classList.remove('border-brand-500', 'shadow-md', 'shadow-brand-500/30');
            btn.classList.add('border-slate-200', 'dark:border-zinc-800');
        });
        const activeThumb = document.getElementById('thumb-' + index);
        if (activeThumb) {
            activeThumb.classList.add('border-brand-500', 'shadow-md', 'shadow-brand-500/30');
            activeThumb.classList.remove('border-slate-200', 'dark:border-zinc-800');
        }
    }

    function openFullscreenImage(src) {
        document.getElementById('modalImg').src = src;
        document.getElementById('imageModal').classList.remove('hidden');
        document.getElementById('imageModal').classList.add('flex');
    }

    function closeFullscreenImage() {
        document.getElementById('imageModal').classList.remove('flex');
        document.getElementById('imageModal').classList.add('hidden');
    }

    function toggleFaq(id) {
        const body = document.getElementById('faq-body-' + id);
        const icon = document.getElementById('faq-icon-' + id);
        if (body.classList.contains('hidden')) {
            body.classList.remove('hidden');
            icon.classList.add('rotate-180');
        } else {
            body.classList.add('hidden');
            icon.classList.remove('rotate-180');
        }
    }

    function copyShareUrl() {
        navigator.clipboard.writeText(window.location.href).then(() => {
            const btnText = document.getElementById('copyBtnText');
            const original = btnText.innerText;
            btnText.innerText = document.documentElement.lang === 'ar' ? 'تم النسخ!' : 'Copied!';
            setTimeout(() => {
                btnText.innerText = original;
            }, 2000);
        });
    }
</script>
@endpush
@endsection

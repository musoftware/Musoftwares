@extends('marketplace::layouts.marketplace-master')

@section('content')
<div class="max-w-[1280px] mx-auto px-6 sm:px-10 py-6 sm:py-10" id="service-app">

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- 1. BREADCRUMBS & TOP NAVIGATION                                         -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <nav class="flex items-center gap-2 text-xs text-[#86868b] mb-6 flex-wrap" aria-label="Breadcrumb">
        <a href="{{ url('/') }}" class="hover:text-[#1d1d1f] transition-colors flex items-center gap-1">
            <span>{{ app()->getLocale() === 'ar' ? 'الرئيسية' : 'Home' }}</span>
        </a>
        <span>›</span>
        <a href="{{ route('marketplace.services.index') }}" class="hover:text-[#1d1d1f] transition-colors">
            {{ app()->getLocale() === 'ar' ? 'سوق الخدمات' : 'Marketplace' }}
        </a>
        @if($service->category)
            <span>›</span>
            <a href="{{ route('marketplace.services.index', ['category' => $service->category->slug]) }}" class="hover:text-[#1d1d1f] transition-colors">
                {{ $service->category->name }}
            </a>
        @endif
        <span>›</span>
        <span class="text-[#1d1d1f] font-medium truncate max-w-xs">{{ $service->title }}</span>
    </nav>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- 2. SERVICE HEADER (Title, Category, Seller Bar)                         -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-3">
            @if($service->category)
                <a href="{{ route('marketplace.services.index', ['category' => $service->category->slug]) }}" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#f5f5f7] border border-black/5 text-[#0071e3] text-xs font-semibold hover:bg-[#e8e8ed] transition-colors">
                    <i class="ri-folder-2-line"></i>
                    <span>{{ $service->category->name }}</span>
                </a>
            @endif

            @if($service->is_featured)
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-50 text-amber-900 border border-amber-200/80 text-xs font-semibold uppercase tracking-wider">
                    <i class="ri-vip-crown-fill text-amber-500"></i> Featured
                </span>
            @endif
        </div>

        <h1 class="text-2xl sm:text-4xl font-semibold text-[#1d1d1f] tracking-[-0.025em] mb-3 leading-tight">
            {{ $service->title }}
        </h1>

        @if($service->tagline)
            <p class="text-sm sm:text-base text-[#86868b] mb-6 leading-relaxed max-w-3xl">
                {{ $service->tagline }}
            </p>
        @endif

        <!-- Seller Overview Bar (Apple Bento Card) -->
        <div class="flex flex-wrap items-center gap-4 sm:gap-6 py-3.5 px-5 rounded-[18px] bg-[#f5f5f7] border border-black/5 text-xs shadow-2xs">
            <!-- Seller Profile -->
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-[#1d1d1f] text-white flex items-center justify-center font-bold text-xs">
                    {{ strtoupper(substr($service->seller->name ?? 'S', 0, 2)) }}
                </div>
                <div>
                    <span class="font-semibold text-[#1d1d1f] flex items-center gap-1">
                        {{ $service->seller->name ?? 'Verified Seller' }}
                        <i class="ri-verified-badge-fill text-[#0071e3] text-xs" title="Verified Expert"></i>
                    </span>
                    <span class="text-[10px] text-[#86868b] block">
                        {{ app()->getLocale() === 'ar' ? 'مطور معتمد' : 'Verified Software Engineer' }}
                    </span>
                </div>
            </div>

            <div class="h-5 w-px bg-black/10 hidden sm:block"></div>

            <!-- Rating Summary -->
            <div class="flex items-center gap-1.5 font-semibold text-[#1d1d1f]">
                <i class="ri-star-fill text-amber-500 text-sm"></i>
                <span>5.0</span>
                <span class="text-[#86868b] font-normal">({{ $service->reviews ? $service->reviews->count() : 18 }} {{ app()->getLocale() === 'ar' ? 'تقييم' : 'reviews' }})</span>
            </div>

            <div class="h-5 w-px bg-black/10 hidden sm:block"></div>

            <!-- Orders count -->
            <div class="flex items-center gap-1.5 text-[#86868b]">
                <i class="ri-shopping-bag-3-line text-[#0071e3]"></i>
                <span class="text-[#1d1d1f] font-medium">{{ $service->completed_orders_count ?? 12 }}</span>
                <span>{{ app()->getLocale() === 'ar' ? 'طلب مكتمل' : 'orders completed' }}</span>
            </div>

            <!-- Favorite & Share Button -->
            <div class="ms-auto flex items-center gap-2">
                <form action="{{ route('marketplace.favorites.toggle', $service->id) }}" method="POST" class="inline">
                    @csrf
                    <button 
                        type="submit" 
                        class="px-3 py-1.5 rounded-full bg-white hover:bg-[#e8e8ed] text-[#1d1d1f] border border-black/10 transition-all flex items-center gap-1.5 text-xs font-medium shadow-2xs"
                    >
                        <i class="{{ !empty($service->is_favorited) ? 'ri-bookmark-fill text-rose-600' : 'ri-bookmark-line' }}"></i>
                        <span>{{ !empty($service->is_favorited) ? (app()->getLocale() === 'ar' ? 'محفوظ' : 'Saved') : (app()->getLocale() === 'ar' ? 'حفظ' : 'Bookmark') }}</span>
                    </button>
                </form>

                <button 
                    type="button" 
                    onclick="copyShareUrl()" 
                    id="copyBtn"
                    class="px-3 py-1.5 rounded-full bg-white hover:bg-[#e8e8ed] text-[#1d1d1f] border border-black/10 transition-all flex items-center gap-1.5 text-xs font-medium shadow-2xs"
                >
                    <i class="ri-share-forward-line"></i>
                    <span id="copyBtnText">{{ app()->getLocale() === 'ar' ? 'مشاركة' : 'Share' }}</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- 3. MAIN CONTENT GRID (Gallery + Details + Checkout Sidebar)             -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
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
            <div class="rounded-[18px] bg-[#f5f5f7] p-3 border border-black/5 shadow-2xs">
                <!-- Main Active Media Stage -->
                <div class="relative aspect-video w-full bg-black rounded-[14px] overflow-hidden flex items-center justify-center" id="mainMediaContainer">
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
                        <div class="w-full h-full bg-[#1d1d1f] flex flex-col items-center justify-center text-[#86868b]">
                            <i class="ri-code-s-slash-line text-5xl text-[#0071e3]/60 mb-2"></i>
                            <span class="text-sm font-semibold text-white">{{ $service->title }}</span>
                        </div>
                    @endif
                </div>

                <!-- Thumbnail Carousel -->
                @if(count($mediaList) > 1)
                    <div class="flex items-center gap-2.5 overflow-x-auto p-2 scrollbar-none">
                        @foreach($mediaList as $index => $item)
                            <button 
                                type="button" 
                                onclick="switchMedia({{ $index }}, '{{ $item['type'] }}', '{{ $item['url'] }}')" 
                                class="thumb-btn relative flex-shrink-0 w-20 h-14 rounded-xl overflow-hidden border-2 {{ $index === 0 ? 'border-[#0071e3] shadow-sm' : 'border-black/10 hover:border-[#0071e3]' }} bg-white transition-all"
                                id="thumb-{{ $index }}"
                            >
                                @if($item['type'] === 'video')
                                    <div class="w-full h-full flex items-center justify-center bg-black">
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
            <div class="rounded-[18px] bg-white p-6 sm:p-8 border border-black/5 shadow-2xs">
                <h2 class="text-lg font-semibold text-[#1d1d1f] mb-4 flex items-center gap-2">
                    <i class="ri-file-text-line text-[#0071e3]"></i>
                    <span>{{ app()->getLocale() === 'ar' ? 'تفاصيل ومواصفات الخدمة' : 'Service Description & Deliverables' }}</span>
                </h2>

                <div class="text-[#1d1d1f]/85 text-sm sm:text-base leading-relaxed space-y-4 font-normal">
                    {!! nl2br(e($service->description)) !!}
                </div>

                <!-- Requirements & Deliverable Highlights -->
                @if(!empty($service->requirements))
                    <div class="mt-8 pt-6 border-t border-black/5">
                        <h3 class="text-xs font-semibold text-[#86868b] uppercase tracking-wider mb-3 flex items-center gap-1.5">
                            <i class="ri-clipboard-line text-amber-500"></i>
                            <span>{{ app()->getLocale() === 'ar' ? 'متطلبات البدء في المشروع:' : 'Buyer Requirements to Start:' }}</span>
                        </h3>
                        @if(is_array($service->requirements))
                            <ul class="space-y-2 text-xs sm:text-sm text-[#1d1d1f] bg-[#f5f5f7] p-4 rounded-2xl border border-black/5">
                                @foreach($service->requirements as $req)
                                    <li class="flex items-start gap-2">
                                        <i class="ri-check-line text-emerald-600 mt-0.5 flex-shrink-0"></i>
                                        <span>{{ is_array($req) ? ($req['text'] ?? $req['title'] ?? json_encode($req)) : $req }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-xs sm:text-sm text-[#1d1d1f] bg-[#f5f5f7] p-4 rounded-2xl border border-black/5">
                                {{ $service->requirements }}
                            </p>
                        @endif
                    </div>
                @endif

                <!-- Tags / Technologies -->
                @if(!empty($service->tags) && is_array($service->tags))
                    <div class="mt-8 pt-6 border-t border-black/5 flex items-center gap-2 flex-wrap">
                        <span class="text-xs font-semibold text-[#86868b]">{{ app()->getLocale() === 'ar' ? 'التقنيات المستخدمة:' : 'Technologies:' }}</span>
                        @foreach($service->tags as $tag)
                            <a href="{{ route('marketplace.services.index', ['skill' => $tag]) }}" class="px-3 py-1 rounded-full bg-[#f5f5f7] text-xs font-medium text-[#1d1d1f] border border-black/5 hover:border-[#0071e3] hover:text-[#0071e3] transition-colors">
                                #{{ $tag }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Trust FAQ Accordion -->
            <div class="rounded-[18px] bg-white p-6 sm:p-8 border border-black/5 shadow-2xs">
                <h2 class="text-lg font-semibold text-[#1d1d1f] mb-4 flex items-center gap-2">
                    <i class="ri-questionnaire-line text-[#0071e3]"></i>
                    <span>{{ app()->getLocale() === 'ar' ? 'الأسئلة الشائعة حول الخدمة والضمان' : 'Frequently Asked Questions' }}</span>
                </h2>

                <div class="space-y-3">
                    <div class="rounded-2xl bg-[#f5f5f7] border border-black/5 p-4">
                        <button type="button" onclick="toggleFaq(1)" class="w-full flex items-center justify-between text-start text-xs sm:text-sm font-semibold text-[#1d1d1f] focus:outline-none">
                            <span>{{ app()->getLocale() === 'ar' ? 'كيف يحميني نظام الضمان المالي (Escrow)؟' : 'How does Escrow Buyer Protection work?' }}</span>
                            <i id="faq-icon-1" class="ri-arrow-down-s-line text-[#86868b] text-lg transition-transform"></i>
                        </button>
                        <div id="faq-body-1" class="mt-2.5 text-xs text-[#86868b] leading-relaxed hidden">
                            {{ app()->getLocale() === 'ar' ? 'عند طلبك لهذه الخدمة يتم حجز المبلغ في رصيد وسيط آمن. لا يتسلم المطور مستحقاته إلا بعد أن تستلم العمل بالكامل وتوافق على جودته.' : 'Your payment is held safely in escrow. The seller only receives funds after you inspect, test, and approve the delivered solution.' }}
                        </div>
                    </div>

                    <div class="rounded-2xl bg-[#f5f5f7] border border-black/5 p-4">
                        <button type="button" onclick="toggleFaq(2)" class="w-full flex items-center justify-between text-start text-xs sm:text-sm font-semibold text-[#1d1d1f] focus:outline-none">
                            <span>{{ app()->getLocale() === 'ar' ? 'هل تشمل الخدمة جولات مراجعة وتعديل؟' : 'Are revision rounds included?' }}</span>
                            <i id="faq-icon-2" class="ri-arrow-down-s-line text-[#86868b] text-lg transition-transform"></i>
                        </button>
                        <div id="faq-body-2" class="mt-2.5 text-xs text-[#86868b] leading-relaxed hidden">
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

            <!-- Package Selection Bento Card -->
            <div class="rounded-[18px] bg-white border border-black/10 p-6 shadow-sm relative overflow-hidden">
                
                <!-- Package Tabs if multiple -->
                @if($packages->count() > 1)
                    <div class="flex items-center gap-1 rounded-full bg-[#f5f5f7] p-1 border border-black/5 mb-5 overflow-x-auto scrollbar-none">
                        @foreach($packages as $pIdx => $pkg)
                            @php
                                $pkgSymbol = $pkg->currency->symbol ?? $pkg->currency->currency ?? $currencySymbol;
                            @endphp
                            <button 
                                type="button" 
                                onclick="selectPackage({{ $pkg->id }}, {{ $pkg->price }}, '{{ addslashes($pkg->name) }}', '{{ addslashes($pkg->description ?? '') }}', {{ $pkg->delivery_days ?? 3 }}, {{ $pkg->revisions ?? 1 }}, '{{ $pkgSymbol }}')" 
                                class="pkg-tab-btn flex-1 py-1.5 px-3 rounded-full text-xs font-semibold transition-all truncate text-center {{ $pIdx === 0 ? 'bg-[#1d1d1f] text-white shadow-xs' : 'text-[#86868b] hover:text-[#1d1d1f]' }}"
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
                        <h3 class="text-base font-semibold text-[#1d1d1f] leading-snug line-clamp-2" id="pkgDisplayTitle">
                            {{ $firstPkg->name }}
                        </h3>
                        <div class="text-xl font-bold text-[#0071e3] font-mono whitespace-nowrap flex-shrink-0 text-end" id="pkgDisplayPrice">
                            {{ $firstPkgSymbol }}{{ number_format($firstPkg->price, 2) }}
                        </div>
                    </div>
                    <p class="text-xs text-[#86868b] leading-relaxed min-h-[32px]" id="pkgDisplayDesc">
                        {{ $firstPkg->description ?: 'Full package deliverables and source files.' }}
                    </p>
                </div>

                <!-- Package Highlights -->
                <div class="space-y-3 py-4 border-y border-black/5 mb-6 text-xs text-[#1d1d1f]">
                    <div class="flex items-center justify-between">
                        <span class="text-[#86868b] flex items-center gap-1.5">
                            <i class="ri-time-line text-[#0071e3]"></i>
                            <span>{{ app()->getLocale() === 'ar' ? 'مدة التسليم' : 'Delivery Time' }}</span>
                        </span>
                        <strong class="text-[#1d1d1f]" id="pkgDisplayDelivery">{{ $firstPkg->delivery_days ?? 3 }} {{ app()->getLocale() === 'ar' ? 'أيام' : 'Days' }}</strong>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[#86868b] flex items-center gap-1.5">
                            <i class="ri-loop-right-line text-emerald-600"></i>
                            <span>{{ app()->getLocale() === 'ar' ? 'جولات التعديل' : 'Revisions' }}</span>
                        </span>
                        <strong class="text-[#1d1d1f]" id="pkgDisplayRevisions">{{ $firstPkg->revisions ?? 2 }} {{ app()->getLocale() === 'ar' ? 'تعديلات' : 'Rounds' }}</strong>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[#86868b] flex items-center gap-1.5">
                            <i class="ri-shield-check-line text-emerald-600"></i>
                            <span>{{ app()->getLocale() === 'ar' ? 'الدفع بالضمان' : 'Escrow Protection' }}</span>
                        </span>
                        <strong class="text-emerald-700 font-semibold">100% Guaranteed</strong>
                    </div>
                </div>

                <!-- Order Now Form Submission -->
                <form action="{{ route('marketplace.orders.store') }}" method="POST" id="orderForm">
                    @csrf
                    <input type="hidden" name="package_id" id="selectedPackageIdInput" value="{{ $firstPkg->id }}">
                    <input type="hidden" name="service_id" value="{{ $service->id }}">

                    @auth
                        @php
                            $userAvail = (float) auth()->user()->available_balance();
                        @endphp
                        <div class="mb-4 p-3 rounded-xl bg-[#f5f5f7] border border-black/5 flex items-center justify-between text-xs">
                            <span class="text-[#86868b] flex items-center gap-1.5 font-medium">
                                <i class="ri-wallet-3-line text-emerald-600"></i>
                                <span>{{ app()->getLocale() === 'ar' ? 'رصيد محفظتك المتاح:' : 'Available Balance:' }}</span>
                            </span>
                            <span class="font-bold text-[#1d1d1f] font-mono">${{ number_format($userAvail, 2) }}</span>
                        </div>

                        <button 
                            type="submit" 
                            class="w-full h-11 rounded-full bg-[#0071e3] hover:bg-[#0077ed] text-white font-medium text-xs transition-all shadow-xs flex items-center justify-center gap-1.5"
                        >
                            <i class="ri-flashlight-fill text-amber-300 text-sm"></i>
                            <span>{{ app()->getLocale() === 'ar' ? 'شراء وتفعيل فوري بالرصيد' : 'Instant 1-Click Purchase' }}</span>
                        </button>
                    @else
                        <a 
                            href="{{ route('login') }}" 
                            class="w-full h-11 rounded-full bg-[#0071e3] hover:bg-[#0077ed] text-white font-medium text-xs transition-all shadow-xs flex items-center justify-center gap-1.5"
                        >
                            <i class="ri-login-box-line text-sm"></i>
                            <span>{{ app()->getLocale() === 'ar' ? 'سجل دخول للمتابعة والشراء' : 'Sign In to Order' }}</span>
                        </a>
                    @endauth
                </form>

                <p class="text-[11px] text-center text-[#86868b] mt-3 flex items-center justify-center gap-1">
                    <i class="ri-lock-line text-emerald-600"></i>
                    <span>{{ app()->getLocale() === 'ar' ? 'دفع آمن بالضمان المالي الكامل (Escrow)' : '100% Escrow-Protected Transaction' }}</span>
                </p>

            </div>

        </div>

    </div>

</div>

<!-- Fullscreen Image Modal (Simple Zoom) -->
<div id="imageModal" class="fixed inset-0 z-50 bg-black/85 hidden items-center justify-center p-4 backdrop-blur-md" onclick="closeFullscreenImage()">
    <div class="relative max-w-5xl max-h-[90vh] flex items-center justify-center">
        <img id="modalImg" src="" alt="preview" class="max-w-full max-h-[85vh] rounded-2xl object-contain shadow-2xl">
        <button type="button" class="absolute top-4 right-4 w-9 h-9 rounded-full bg-white/20 text-white flex items-center justify-center text-xl hover:bg-white/30">
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
        document.getElementById('pkgDisplayPrice').innerHTML = symbol + Number(price).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('pkgDisplayDesc').innerText = desc || 'Full package deliverables and source files.';
        document.getElementById('pkgDisplayDelivery').innerText = delivery + ' ' + (document.documentElement.lang === 'ar' ? 'أيام' : 'Days');
        document.getElementById('pkgDisplayRevisions').innerText = revisions + ' ' + (document.documentElement.lang === 'ar' ? 'تعديلات' : 'Rounds');

        document.querySelectorAll('.pkg-tab-btn').forEach(btn => {
            btn.classList.remove('bg-[#1d1d1f]', 'text-white', 'shadow-xs');
            btn.classList.add('text-[#86868b]');
        });
        const activeBtn = document.getElementById('pkg-tab-' + id);
        if (activeBtn) {
            activeBtn.classList.add('bg-[#1d1d1f]', 'text-white', 'shadow-xs');
            activeBtn.classList.remove('text-[#86868b]');
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
            btn.classList.remove('border-[#0071e3]', 'shadow-sm');
            btn.classList.add('border-black/10');
        });
        const activeThumb = document.getElementById('thumb-' + index);
        if (activeThumb) {
            activeThumb.classList.add('border-[#0071e3]', 'shadow-sm');
            activeThumb.classList.remove('border-black/10');
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

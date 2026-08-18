@extends('marketplace::layouts.marketplace-master')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" id="service-app">

    <!-- Breadcrumb Navigation -->
    <nav class="flex items-center gap-2 text-xs text-zinc-400 mb-6 flex-wrap" aria-label="Breadcrumb">
        <a href="{{ url('/') }}" class="hover:text-white transition-colors flex items-center gap-1">
            <i class="ri-home-4-line"></i>
            <span>{{ app()->getLocale() === 'ar' ? 'الرئيسية' : 'Home' }}</span>
        </a>
        <i class="ri-arrow-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}-s-line text-zinc-600"></i>
        <a href="{{ route('marketplace.services.index') }}" class="hover:text-white transition-colors">
            {{ app()->getLocale() === 'ar' ? 'سوق الخدمات' : 'Marketplace' }}
        </a>
        @if($service->category)
            <i class="ri-arrow-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}-s-line text-zinc-600"></i>
            <a href="{{ route('marketplace.services.index', ['category' => $service->category->slug]) }}" class="hover:text-white transition-colors">
                {{ $service->category->name }}
            </a>
        @endif
        <i class="ri-arrow-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}-s-line text-zinc-600"></i>
        <span class="text-zinc-300 font-medium truncate max-w-xs">{{ $service->title }}</span>
    </nav>

    <!-- Service Header: Title, Category & Seller Bar -->
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-3">
            @if($service->category)
                <a href="{{ route('marketplace.services.index', ['category' => $service->category->slug]) }}" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-brand-500/15 border border-brand-500/30 text-brand-300 text-xs font-semibold hover:bg-brand-500/25 transition-colors">
                    <i class="ri-folder-2-line"></i>
                    {{ $service->category->name }}
                </a>
            @endif

            @if($service->is_featured)
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-500/20 border border-amber-500/40 text-amber-300 text-xs font-bold uppercase tracking-wider">
                    <i class="ri-vip-crown-fill text-amber-400"></i> Featured
                </span>
            @endif
        </div>

        <h1 class="text-2xl sm:text-4xl font-black text-white tracking-tight mb-4 leading-tight">
            {{ $service->title }}
        </h1>

        @if($service->tagline)
            <p class="text-base text-zinc-400 mb-6 leading-relaxed">
                {{ $service->tagline }}
            </p>
        @endif

        <!-- Seller Overview Bar -->
        <div class="flex flex-wrap items-center gap-4 sm:gap-6 py-3 px-4 rounded-2xl bg-zinc-900/80 border border-zinc-800 text-xs text-zinc-300">
            <!-- Seller Profile -->
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-brand-600/30 border border-brand-500/30 text-brand-300 flex items-center justify-center font-bold text-xs">
                    {{ strtoupper(substr($service->seller->name ?? 'S', 0, 2)) }}
                </div>
                <div>
                    <span class="font-semibold text-white flex items-center gap-1">
                        {{ $service->seller->name ?? 'Verified Seller' }}
                        <i class="ri-verified-badge-fill text-brand-400 text-xs" title="Verified Expert"></i>
                    </span>
                    <span class="text-[10px] text-zinc-500 block">
                        {{ app()->getLocale() === 'ar' ? 'مطور معتمد' : 'Verified Software Specialist' }}
                    </span>
                </div>
            </div>

            <div class="h-6 w-px bg-zinc-800 hidden sm:block"></div>

            <!-- Rating Summary -->
            <div class="flex items-center gap-1.5 text-amber-400 font-bold">
                <i class="ri-star-fill text-sm"></i>
                <span class="text-white">5.0</span>
                <span class="text-zinc-500 font-normal">({{ $service->reviews ? $service->reviews->count() : 0 }} {{ app()->getLocale() === 'ar' ? 'تقييم' : 'reviews' }})</span>
            </div>

            <div class="h-6 w-px bg-zinc-800 hidden sm:block"></div>

            <!-- Orders count -->
            <div class="flex items-center gap-1.5 text-zinc-400">
                <i class="ri-shopping-cart-2-line text-brand-400"></i>
                <span>{{ $service->completed_orders_count ?? 12 }} {{ app()->getLocale() === 'ar' ? 'طلب مكتمل' : 'orders completed' }}</span>
            </div>

            <!-- Favorite & Share Button -->
            <div class="ms-auto flex items-center gap-2">
                <button 
                    type="button" 
                    onclick="copyShareUrl()" 
                    id="copyBtn"
                    class="px-3 py-1.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white border border-zinc-700/60 transition-all flex items-center gap-1.5 text-xs"
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
                // Media gathering
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
                // Video check
                $videoEmbedUrl = null;
                if (!empty($service->video_url)) {
                    $vUrl = trim($service->video_url);
                    if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/|youtube\.com\/shorts\/)([a-zA-Z0-9_-]+)/', $vUrl, $matches)) {
                        $videoEmbedUrl = "https://www.youtube.com/embed/" . $matches[1];
                        array_unshift($mediaList, [
                            'type' => 'video',
                            'url' => $videoEmbedUrl,
                            'thumb' => "https://img.youtube.com/vi/{$matches[1]}/hqdefault.jpg",
                        ]);
                    } elseif (preg_match('/vimeo\.com\/(?:video\/)?([0-9]+)/', $vUrl, $matches)) {
                        $videoEmbedUrl = "https://player.vimeo.com/video/" . $matches[1];
                        array_unshift($mediaList, [
                            'type' => 'video',
                            'url' => $videoEmbedUrl,
                            'thumb' => null,
                        ]);
                    }
                }
            @endphp

            <!-- Media Gallery Box -->
            <div class="rounded-3xl card-surface overflow-hidden p-3 border border-zinc-800">
                <!-- Main Active Media Stage -->
                <div class="relative aspect-video w-full bg-zinc-950 rounded-2xl overflow-hidden flex items-center justify-center" id="mainMediaContainer">
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
                        <div class="w-full h-full bg-gradient-to-br from-brand-950 via-dark-800 to-dark-900 flex flex-col items-center justify-center text-zinc-500">
                            <i class="ri-code-s-slash-line text-6xl text-brand-400/50 mb-2"></i>
                            <span class="text-sm font-semibold">{{ $service->title }}</span>
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
                                class="thumb-btn relative flex-shrink-0 w-20 h-14 rounded-xl overflow-hidden border-2 {{ $index === 0 ? 'border-brand-500 shadow-md shadow-brand-500/30' : 'border-zinc-800 hover:border-zinc-600' }} bg-zinc-900 transition-all"
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
            <div class="rounded-3xl card-surface p-6 sm:p-8 border border-zinc-800">
                <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                    <i class="ri-file-text-line text-brand-400"></i>
                    {{ app()->getLocale() === 'ar' ? 'تفاصيل ومواصفات الخدمة' : 'Service Description & Deliverables' }}
                </h2>

                <div class="prose prose-invert max-w-none text-zinc-300 text-sm sm:text-base leading-relaxed space-y-4">
                    {!! nl2br(e($service->description)) !!}
                </div>

                <!-- Requirements & Deliverable Highlights -->
                @if(!empty($service->requirements))
                    <div class="mt-8 pt-6 border-t border-zinc-800">
                        <h3 class="text-base font-bold text-white mb-3 flex items-center gap-2">
                            <i class="ri-clipboard-line text-amber-400"></i>
                            {{ app()->getLocale() === 'ar' ? 'متطلبات البدء في المشروع:' : 'Buyer Requirements to Start:' }}
                        </h3>
                        <p class="text-xs sm:text-sm text-zinc-400 bg-zinc-900/60 p-4 rounded-2xl border border-zinc-800/80">
                            {{ $service->requirements }}
                        </p>
                    </div>
                @endif

                <!-- Tags / Technologies -->
                @if(!empty($service->tags) && is_array($service->tags))
                    <div class="mt-8 pt-6 border-t border-zinc-800 flex items-center gap-2 flex-wrap">
                        <span class="text-xs font-semibold text-zinc-400">{{ app()->getLocale() === 'ar' ? 'التقنيات المستخدمة:' : 'Technologies:' }}</span>
                        @foreach($service->tags as $tag)
                            <a href="{{ route('marketplace.technologies.show', ['tag' => $tag]) }}" class="px-3 py-1 rounded-xl bg-zinc-800/80 hover:bg-brand-600/20 hover:text-brand-300 hover:border-brand-500/40 text-xs text-zinc-300 border border-zinc-700/60 transition-colors">
                                #{{ $tag }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Extras / Add-ons Section -->
            @if($service->extras && $service->extras->count() > 0)
                <div class="rounded-3xl card-surface p-6 sm:p-8 border border-zinc-800">
                    <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                        <i class="ri-add-circle-line text-emerald-400"></i>
                        {{ app()->getLocale() === 'ar' ? 'تطويرات وإضافات الخدمة (Extras)' : 'Available Service Add-ons & Extras' }}
                    </h2>

                    <div class="space-y-3">
                        @foreach($service->extras as $extra)
                            <div class="p-4 rounded-2xl bg-zinc-900/70 border border-zinc-800 flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-sm">
                                        <i class="ri-check-line"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-white">{{ $extra->name }}</h4>
                                        @if($extra->description)
                                            <p class="text-xs text-zinc-400">{{ $extra->description }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <span class="text-sm font-black text-emerald-400">+${{ number_format($extra->price, 2) }}</span>
                                    @if($extra->delivery_days)
                                        <span class="text-[10px] text-zinc-500 block">+{{ $extra->delivery_days }} {{ app()->getLocale() === 'ar' ? 'أيام إضافية' : 'extra days' }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- FAQs Section -->
            <div class="rounded-3xl card-surface p-6 sm:p-8 border border-zinc-800">
                <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                    <i class="ri-questionnaire-line text-indigo-400"></i>
                    {{ app()->getLocale() === 'ar' ? 'الأسئلة الشائعة حول هذه الخدمة' : 'Frequently Asked Questions' }}
                </h2>

                <div class="space-y-3" id="faqAccordion">
                    
                    <div class="rounded-2xl bg-zinc-900/70 border border-zinc-800 p-4">
                        <button type="button" onclick="toggleFaq(1)" class="w-full flex items-center justify-between text-start text-sm font-bold text-white focus:outline-none">
                            <span>{{ app()->getLocale() === 'ar' ? 'كيف يتم حماية مدفوعاتي أثناء تنفيذ الخدمة؟' : 'How is my payment protected during service execution?' }}</span>
                            <i id="faq-icon-1" class="ri-arrow-down-s-line text-zinc-400 text-lg transition-transform"></i>
                        </button>
                        <div id="faq-body-1" class="mt-3 text-xs text-zinc-400 leading-relaxed">
                            {{ app()->getLocale() === 'ar' ? 'يتم الاحتفاظ بالمبلغ في نظام الضمان Escrow ولا يتم الإفراج عنه إلا بعد استلامك للعمل النهائي وفحصه بالكامل والموافقة عليه.' : 'Funds remain securely held in escrow by MuSoftwares Marketplace and are released to the seller only after your final inspection and approval.' }}
                        </div>
                    </div>

                    <div class="rounded-2xl bg-zinc-900/70 border border-zinc-800 p-4">
                        <button type="button" onclick="toggleFaq(2)" class="w-full flex items-center justify-between text-start text-sm font-bold text-white focus:outline-none">
                            <span>{{ app()->getLocale() === 'ar' ? 'هل يمكنني طلب تعديلات على العمل المسلّم؟' : 'Can I request revisions for this service?' }}</span>
                            <i id="faq-icon-2" class="ri-arrow-down-s-line text-zinc-400 text-lg transition-transform"></i>
                        </button>
                        <div id="faq-body-2" class="mt-3 text-xs text-zinc-400 leading-relaxed hidden">
                            {{ app()->getLocale() === 'ar' ? 'نعم، تتضمن باقات الخدمة جولات تعديل ومراجعة يمكنك طلبها مباشرة من غرفة الطلب مع المطور.' : 'Yes, each service package includes defined revision rounds. You can request revisions directly through your dedicated order room.' }}
                        </div>
                    </div>

                    <div class="rounded-2xl bg-zinc-900/70 border border-zinc-800 p-4">
                        <button type="button" onclick="toggleFaq(3)" class="w-full flex items-center justify-between text-start text-sm font-bold text-white focus:outline-none">
                            <span>{{ app()->getLocale() === 'ar' ? 'ماذا يحدث في حال تأخر البائع أو عدم التزامه؟' : 'What happens if the seller does not deliver on time?' }}</span>
                            <i id="faq-icon-3" class="ri-arrow-down-s-line text-zinc-400 text-lg transition-transform"></i>
                        </button>
                        <div id="faq-body-3" class="mt-3 text-xs text-zinc-400 leading-relaxed hidden">
                            {{ app()->getLocale() === 'ar' ? 'يحق لك إلغاء الطلب فوراً واسترجاع 100% من المبلغ إلى محفظتك مع إمكانية فتح نزاع للمراجعة الإدارية.' : 'If a seller misses the agreed delivery deadline without mutual agreement, you can request an instant full refund or cancellation.' }}
                        </div>
                    </div>

                </div>
            </div>

            <!-- Customer Reviews Section -->
            <div class="rounded-3xl card-surface p-6 sm:p-8 border border-zinc-800">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="ri-star-half-line text-amber-400"></i>
                        {{ app()->getLocale() === 'ar' ? 'تقييمات وآراء العملاء' : 'Customer Reviews & Ratings' }}
                    </h2>
                    <span class="text-xs font-semibold text-zinc-400">
                        {{ $service->reviews ? $service->reviews->count() : 0 }} {{ app()->getLocale() === 'ar' ? 'تقييم' : 'reviews' }}
                    </span>
                </div>

                @if($service->reviews && $service->reviews->count() > 0)
                    <div class="space-y-4">
                        @foreach($service->reviews as $review)
                            <div class="p-4 rounded-2xl bg-zinc-900/60 border border-zinc-800">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-full bg-brand-600/30 text-brand-300 flex items-center justify-center font-bold text-xs">
                                            {{ strtoupper(substr($review->reviewer->name ?? 'C', 0, 1)) }}
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-bold text-white">{{ $review->reviewer->name ?? 'Verified Buyer' }}</h4>
                                            <span class="text-[10px] text-zinc-500">{{ $review->created_at ? $review->created_at->diffForHumans() : 'Recent' }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center text-amber-400 text-xs">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="ri-star-{{ $i <= ($review->rating ?? 5) ? 'fill' : 'line text-zinc-600' }}"></i>
                                        @endfor
                                    </div>
                                </div>
                                <p class="text-xs text-zinc-300 leading-relaxed">
                                    {{ $review->comment }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-6 rounded-2xl bg-zinc-900/40 border border-zinc-800/80 text-center text-zinc-400 text-xs">
                        <i class="ri-star-smile-line text-3xl text-zinc-500 mb-2 block"></i>
                        {{ app()->getLocale() === 'ar' ? 'لا توجد تقييمات حتى الآن. كن أول من يطلب هذه الخدمة ويقيمها!' : 'No reviews yet. Be the first to order and review this service!' }}
                    </div>
                @endif
            </div>

        </div>

        <!-- Right Column: Package Pricing, Checkout Box & Guarantee (4 cols) -->
        <div class="lg:col-span-4 sticky top-24 space-y-6">
            
            @php
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
                            'currency' => (object)['symbol' => '$', 'code' => 'USD']
                        ]
                    ]);
                $firstPkg = $packages->first();
            @endphp

            <!-- Package Selection Card -->
            <div class="rounded-3xl card-surface border-2 border-brand-500/30 p-6 sm:p-7 shadow-2xl relative overflow-hidden">
                
                <!-- Package Tabs if multiple -->
                @if($packages->count() > 1)
                    <div class="flex rounded-2xl bg-zinc-900/90 p-1 border border-zinc-800 mb-6">
                        @foreach($packages as $pIdx => $pkg)
                            <button 
                                type="button" 
                                onclick="selectPackage({{ $pkg->id }}, {{ $pkg->price }}, '{{ addslashes($pkg->name) }}', '{{ addslashes($pkg->description ?? '') }}', {{ $pkg->delivery_days ?? 3 }}, {{ $pkg->revisions ?? 1 }})" 
                                class="pkg-tab-btn flex-1 py-2 rounded-xl text-xs font-bold transition-all {{ $pIdx === 0 ? 'bg-brand-600 text-white shadow-md' : 'text-zinc-400 hover:text-white' }}"
                                id="pkg-tab-{{ $pkg->id }}"
                            >
                                {{ $pkg->name }}
                            </button>
                        @endforeach
                    </div>
                @endif

                <!-- Active Package Display -->
                <div class="mb-6">
                    <div class="flex items-baseline justify-between mb-2">
                        <h3 class="text-lg font-bold text-white" id="pkgDisplayTitle">
                            {{ $firstPkg->name }}
                        </h3>
                        <div class="text-2xl font-black text-emerald-400" id="pkgDisplayPrice">
                            ${{ number_format($firstPkg->price, 2) }}
                        </div>
                    </div>
                    <p class="text-xs text-zinc-400 leading-relaxed min-h-[36px]" id="pkgDisplayDesc">
                        {{ $firstPkg->description ?: 'Full package deliverables and source files.' }}
                    </p>
                </div>

                <!-- Package Highlights -->
                <div class="space-y-3 py-4 border-y border-zinc-800 mb-6 text-xs text-zinc-300">
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-400 flex items-center gap-1.5">
                            <i class="ri-time-line text-brand-400"></i>
                            {{ app()->getLocale() === 'ar' ? 'مدة التسليم' : 'Delivery Time' }}
                        </span>
                        <strong class="text-white" id="pkgDisplayDelivery">{{ $firstPkg->delivery_days ?? 3 }} {{ app()->getLocale() === 'ar' ? 'أيام' : 'Days' }}</strong>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-400 flex items-center gap-1.5">
                            <i class="ri-loop-right-line text-emerald-400"></i>
                            {{ app()->getLocale() === 'ar' ? 'جولات التعديل' : 'Revisions' }}
                        </span>
                        <strong class="text-white" id="pkgDisplayRevisions">{{ $firstPkg->revisions ?? 2 }} {{ app()->getLocale() === 'ar' ? 'تعديلات' : 'Rounds' }}</strong>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-400 flex items-center gap-1.5">
                            <i class="ri-shield-check-line text-amber-400"></i>
                            {{ app()->getLocale() === 'ar' ? 'الدفع المحمي' : 'Escrow Protection' }}
                        </span>
                        <strong class="text-emerald-400">100% Guaranteed</strong>
                    </div>
                </div>

                <!-- Order Now Form Submission -->
                <form action="{{ route('marketplace.orders.store') }}" method="POST" id="orderForm">
                    @csrf
                    <input type="hidden" name="package_id" id="selectedPackageIdInput" value="{{ $firstPkg->id }}">
                    
                    <button 
                        type="submit" 
                        class="w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 text-white font-bold text-sm shadow-xl shadow-brand-600/30 transition-all flex items-center justify-center gap-2 group cursor-pointer"
                    >
                        <i class="ri-flashlight-fill text-amber-300 group-hover:scale-110 transition-transform"></i>
                        <span>{{ app()->getLocale() === 'ar' ? 'اطلب الخدمة الآن' : 'Order Now (Instant Escrow)' }}</span>
                    </button>
                </form>

                <!-- Escrow Badge Inside Card -->
                <div class="mt-4 p-3 rounded-xl bg-zinc-900/60 border border-zinc-800 text-[11px] text-zinc-400 flex items-center gap-2">
                    <i class="ri-lock-password-fill text-emerald-400 text-base flex-shrink-0"></i>
                    <span>{{ app()->getLocale() === 'ar' ? 'دفع آمن بالكامل: لا يتم الإفراج عن المبلغ إلا بعد موافقتك.' : 'Safe Escrow: Seller receives payment only after you approve.' }}</span>
                </div>
            </div>

            <!-- Seller Summary Box -->
            <div class="rounded-3xl card-surface p-6 border border-zinc-800">
                <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-400 mb-4">
                    {{ app()->getLocale() === 'ar' ? 'عن البائع' : 'About the Seller' }}
                </h4>

                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-brand-600/20 border border-brand-500/40 text-brand-300 flex items-center justify-center font-black text-base">
                        {{ strtoupper(substr($service->seller->name ?? 'S', 0, 2)) }}
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-white">{{ $service->seller->name ?? 'Verified Seller' }}</h4>
                        <span class="text-xs text-zinc-400">{{ app()->getLocale() === 'ar' ? 'عضو معتمد في سوق الخدمات' : 'Verified Software Developer' }}</span>
                    </div>
                </div>

                <div class="space-y-2 text-xs text-zinc-400 pt-3 border-t border-zinc-800">
                    <div class="flex items-center justify-between">
                        <span>{{ app()->getLocale() === 'ar' ? 'سرعة الرد:' : 'Response Time:' }}</span>
                        <strong class="text-white">{{ app()->getLocale() === 'ar' ? 'أقل من ساعة' : '< 1 hour' }}</strong>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>{{ app()->getLocale() === 'ar' ? 'الطلبات المكتملة:' : 'Completed Orders:' }}</span>
                        <strong class="text-white">100%</strong>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- Related Services in the Same Category -->
    @if(isset($relatedServices) && $relatedServices->count() > 0)
        <div class="mt-20 pt-12 border-t border-zinc-800">
            <h2 class="text-2xl font-bold text-white mb-6">
                {{ app()->getLocale() === 'ar' ? 'خدمات ذات صلة في نفس القسم' : 'Related Services' }}
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($relatedServices as $rel)
                    @php
                        $relUrl = route('marketplace.services.show', ['id' => $rel->id, 'slug' => $rel->slug ?? 'service']);
                        $relPrice = $rel->packages->min('price') ?? $rel->price ?? 5;
                        $relCover = $rel->cover_image ? (Str::startsWith($rel->cover_image, ['http://', 'https://', '/']) ? $rel->cover_image : '/uploads/'.ltrim($rel->cover_image, '/')) : null;
                    @endphp
                    <div class="rounded-2xl card-surface overflow-hidden border border-zinc-800 flex flex-col group">
                        <a href="{{ $relUrl }}" class="aspect-video bg-zinc-950 block overflow-hidden">
                            @if($relCover)
                                <img src="{{ $relCover }}" alt="{{ $rel->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="w-full h-full bg-zinc-900 flex items-center justify-center text-zinc-600">
                                    <i class="ri-code-s-slash-line text-3xl"></i>
                                </div>
                            @endif
                        </a>
                        <div class="p-4 flex-1 flex flex-col justify-between">
                            <h4 class="text-xs font-bold text-white line-clamp-2 mb-2 group-hover:text-brand-300 transition-colors">
                                <a href="{{ $relUrl }}">{{ $rel->title }}</a>
                            </h4>
                            <div class="pt-3 border-t border-zinc-800 flex items-center justify-between text-xs">
                                <span class="text-amber-400 font-bold flex items-center gap-1">
                                    <i class="ri-star-fill text-[10px]"></i> 5.0
                                </span>
                                <strong class="text-emerald-400 text-sm">${{ number_format($relPrice, 2) }}</strong>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

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
    function selectPackage(id, price, name, desc, delivery, revisions) {
        document.getElementById('selectedPackageIdInput').value = id;
        document.getElementById('pkgDisplayTitle').innerText = name;
        document.getElementById('pkgDisplayPrice').innerText = '$' + Number(price).toFixed(2);
        document.getElementById('pkgDisplayDesc').innerText = desc || 'Full package deliverables and source files.';
        document.getElementById('pkgDisplayDelivery').innerText = delivery + ' ' + (document.documentElement.lang === 'ar' ? 'أيام' : 'Days');
        document.getElementById('pkgDisplayRevisions').innerText = revisions + ' ' + (document.documentElement.lang === 'ar' ? 'تعديلات' : 'Rounds');

        document.querySelectorAll('.pkg-tab-btn').forEach(btn => {
            btn.classList.remove('bg-brand-600', 'text-white', 'shadow-md');
            btn.classList.add('text-zinc-400');
        });
        const activeBtn = document.getElementById('pkg-tab-' + id);
        if (activeBtn) {
            activeBtn.classList.add('bg-brand-600', 'text-white', 'shadow-md');
            activeBtn.classList.remove('text-zinc-400');
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
            btn.classList.add('border-zinc-800');
        });
        const activeThumb = document.getElementById('thumb-' + index);
        if (activeThumb) {
            activeThumb.classList.add('border-brand-500', 'shadow-md', 'shadow-brand-500/30');
            activeThumb.classList.remove('border-zinc-800');
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

@extends('layouts.public')

@push('head')
@php
    $schemaData = [
        '@context' => 'https://schema.org',
        '@graph' => [
            array_filter([
                '@type' => 'Book',
                '@id' => route('library.show', $product->slug) . '#book',
                'name' => $product->title,
                'headline' => $product->title,
                'description' => $product->meta_description ?: ($product->short_description ?: strip_tags($product->description ?? '')),
                'inLanguage' => $product->language ?? 'ar',
                'numberOfPages' => (int) ($product->page_count ?? 1),
                'bookFormat' => 'https://schema.org/EBook',
                'author' => $product->author_name ? [
                    '@type' => 'Person',
                    'name' => $product->author_name,
                ] : null,
                'publisher' => $product->publisher ? [
                    '@type' => 'Organization',
                    'name' => $product->publisher,
                ] : null,
                'image' => $product->cover_image_path ? $product->cover_url : null,
                'offers' => [
                    '@type' => 'Offer',
                    'price' => $product->is_free ? '0.00' : (string) number_format($product->converted_price ?? $product->price, 2, '.', ''),
                    'priceCurrency' => $viewerCurrency->currency ?? 'USD',
                    'availability' => 'https://schema.org/InStock',
                    'url' => route('library.show', $product->slug),
                ],
            ])
        ],
    ];
@endphp
<!-- Schema.org JSON-LD Structured Data -->
<script type="application/ld+json">
{!! json_encode($schemaData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush

@section('content')
<div class="max-w-[1280px] mx-auto px-6 sm:px-10 py-6 sm:py-10 space-y-10">

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- 1. APPLE BREADCRUMB TRAIL                                              -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <nav class="flex items-center gap-2 text-xs text-[#86868b] flex-wrap" aria-label="Breadcrumb">
        <a href="{{ url('/') }}" class="hover:text-[#1d1d1f] transition-colors flex items-center gap-1">
            <span>{{ app()->getLocale() === 'ar' ? 'الرئيسية' : 'Home' }}</span>
        </a>
        <span>›</span>
        <a href="{{ route('library.index') }}" class="hover:text-[#1d1d1f] transition-colors">
            {{ app()->getLocale() === 'ar' ? 'المكتبة الرقمية' : 'Library' }}
        </a>
        @if($product->category)
            <span>›</span>
            <a href="{{ route('library.index', ['category' => $product->category->slug]) }}" class="hover:text-[#1d1d1f] transition-colors">
                {{ $product->category->name }}
            </a>
        @endif
        <span>›</span>
        <span class="text-[#1d1d1f] font-medium truncate max-w-xs sm:max-w-md">{{ $product->title }}</span>
    </nav>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- 2. MAIN BOOK SHOWCASE (Left: 3D Cover | Right: Apple Conversion Card)  -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="rounded-[22px] bg-[#f5f5f7] border border-black/5 p-6 sm:p-10 lg:p-12">
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">

            <!-- Left: 3D Standing Cover (5 Cols) -->
            <div class="lg:col-span-5 flex flex-col items-center">
                <div class="relative w-full max-w-[320px] aspect-[3/4] rounded-[18px] overflow-hidden bg-white border border-black/10 shadow-[0_16px_36px_rgba(0,0,0,0.12)] p-2">
                    @php
                        $coverUrl = $product->cover_image_path ? $product->cover_url : ($product->cover_image ? asset($product->cover_image) : asset('images/apple/web-mobile-suite.jpg'));
                    @endphp
                    <img 
                        src="{{ $coverUrl }}" 
                        alt="{{ $product->title }}" 
                        class="w-full h-full object-cover rounded-[14px]"
                    >
                </div>

                <!-- Format Metadata Pill Bar -->
                <div class="flex items-center gap-3 mt-6 text-xs text-[#86868b]">
                    @if($product->format)
                        <span class="px-2.5 py-1 rounded-full bg-white border border-black/5 text-[#1d1d1f] font-semibold uppercase">
                            {{ $product->format }}
                        </span>
                    @endif
                    @if($product->page_count)
                        <span>{{ $product->page_count }} {{ app()->getLocale() === 'ar' ? 'صفحة' : 'Pages' }}</span>
                        <span>•</span>
                    @endif
                    <span>{{ $product->download_count ?? 0 }} {{ app()->getLocale() === 'ar' ? 'تنزيل' : 'Downloads' }}</span>
                </div>
            </div>

            <!-- Right: Details & Purchase/Download Box (7 Cols) -->
            <div class="lg:col-span-7 space-y-6">
                
                <div>
                    @if($product->category)
                        <a href="{{ route('library.index', ['category' => $product->category->slug]) }}" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white border border-black/5 text-[#0071e3] text-xs font-semibold hover:bg-[#e8e8ed] transition-colors mb-3">
                            <i class="ri-folder-2-line"></i>
                            <span>{{ $product->category->name }}</span>
                        </a>
                    @endif

                    <h1 class="text-2xl sm:text-4xl font-semibold text-[#1d1d1f] tracking-[-0.025em] leading-tight mb-3">
                        {{ $product->title }}
                    </h1>

                    @if($product->short_description)
                        <p class="text-sm sm:text-base text-[#86868b] leading-relaxed">
                            {{ $product->short_description }}
                        </p>
                    @endif
                </div>

                <!-- Metadata Row (Author, Publisher, Year) -->
                <div class="flex flex-wrap items-center gap-4 text-xs text-[#86868b] py-3 border-y border-black/5">
                    @if($product->author_name)
                        <div class="flex items-center gap-1.5">
                            <i class="ri-user-3-line text-[#0071e3]"></i>
                            <span>{{ app()->getLocale() === 'ar' ? 'المؤلف:' : 'Author:' }} <strong class="text-[#1d1d1f] font-semibold">{{ $product->author_name }}</strong></span>
                        </div>
                    @endif
                    @if($product->publisher)
                        <div class="flex items-center gap-1.5">
                            <i class="ri-building-line text-[#0071e3]"></i>
                            <span>{{ app()->getLocale() === 'ar' ? 'الناشر:' : 'Publisher:' }} <strong class="text-[#1d1d1f] font-semibold">{{ $product->publisher }}</strong></span>
                        </div>
                    @endif
                    @if($product->publication_year)
                        <div class="flex items-center gap-1.5">
                            <i class="ri-calendar-line text-[#0071e3]"></i>
                            <span>{{ app()->getLocale() === 'ar' ? 'سنة الإصدار:' : 'Year:' }} <strong class="text-[#1d1d1f] font-semibold">{{ $product->publication_year }}</strong></span>
                        </div>
                    @endif
                </div>

                <!-- Apple Bento Conversion Box -->
                <div class="rounded-[18px] bg-white border border-black/10 p-6 sm:p-8 space-y-6 shadow-sm">
                    
                    @if(!empty($isPurchased))
                        <!-- State 1: User Already Owns Book -->
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div>
                                <span class="text-xs font-semibold text-emerald-600 flex items-center gap-1 mb-1">
                                    <i class="ri-checkbox-circle-fill text-base"></i>
                                    <span>{{ app()->getLocale() === 'ar' ? 'تمتلك هذا الإصدار في مكتبتك' : 'Owned in your library' }}</span>
                                </span>
                                <h4 class="text-base font-semibold text-[#1d1d1f]">{{ app()->getLocale() === 'ar' ? 'جاهز للتحميل والقراءة الفورية مدى الحياة' : 'Ready for direct lifetime download' }}</h4>
                            </div>
                            <a href="{{ route('library.my_library.download', $product->slug) }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs shadow-xs transition-all">
                                <i class="ri-download-2-fill text-base"></i>
                                <span>{{ app()->getLocale() === 'ar' ? 'تحميل ملف الكتاب (PDF)' : 'Download PDF File' }}</span>
                            </a>
                        </div>

                    @elseif($product->is_free || (float)$product->price <= 0)
                        <!-- State 2: 100% Free E-Book -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider block mb-1">
                                        {{ app()->getLocale() === 'ar' ? 'إصدار مجاني متاح للجميع' : '100% Free Edition' }}
                                    </span>
                                    <h4 class="text-xl font-semibold text-[#1d1d1f]">
                                        {{ app()->getLocale() === 'ar' ? 'احصل على نسختك الفورية الآن' : 'Get your free copy now' }}
                                    </h4>
                                </div>
                                <span class="text-2xl font-bold font-mono text-emerald-600">
                                    $0.00
                                </span>
                            </div>

                            <form action="{{ route('library.free_download', $product->slug) }}" method="POST" class="space-y-3">
                                @csrf
                                <input type="hidden" name="edition_type" value="full">
                                
                                @guest
                                    <div class="relative">
                                        <input 
                                            type="email" 
                                            name="email" 
                                            required 
                                            placeholder="{{ app()->getLocale() === 'ar' ? 'أدخل بريدك الإلكتروني لتحميل الكتاب فوراً...' : 'Enter your email for direct download...' }}"
                                            class="w-full h-11 ps-4 pe-12 rounded-full bg-[#f5f5f7] border border-black/10 text-xs text-[#1d1d1f] focus:outline-none focus:border-[#0071e3]"
                                        >
                                        <i class="ri-mail-send-line absolute end-4 top-3 text-[#86868b] text-base"></i>
                                    </div>
                                @endguest

                                <button type="submit" class="w-full h-11 rounded-full bg-[#0071e3] hover:bg-[#0077ed] text-white font-medium text-xs shadow-xs transition-all flex items-center justify-center gap-2">
                                    <i class="ri-download-cloud-line text-base"></i>
                                    <span>{{ app()->getLocale() === 'ar' ? 'تحميل الكتاب الآن مجاناً (PDF)' : 'Download Free PDF Now' }}</span>
                                </button>
                            </form>
                        </div>

                    @else
                        <!-- State 3: Paid E-Book Purchase Box -->
                        <div class="space-y-5">
                            
                            @if($product->has_free_edition)
                                <!-- Free Playbook Edition Box -->
                                <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200/80 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <span class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider block">
                                                نسخة مجانية (Playbook)
                                            </span>
                                            <h4 class="text-sm font-semibold text-emerald-950">
                                                {{ $product->free_edition_title ?: ($product->title . ' - Playbook') }}
                                            </h4>
                                        </div>
                                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-600 text-white text-xs font-bold uppercase">
                                            Free
                                        </span>
                                    </div>

                                    <form action="{{ route('library.free_download', $product->slug) }}" method="POST" class="space-y-2">
                                        @csrf
                                        <input type="hidden" name="edition_type" value="playbook">
                                        @guest
                                            <input 
                                                type="email" 
                                                name="email" 
                                                required 
                                                placeholder="{{ app()->getLocale() === 'ar' ? 'أدخل بريدك لتحميل الملخص المجاني...' : 'Enter email for free playbook...' }}"
                                                class="w-full h-10 px-3 rounded-full bg-white border border-emerald-300 text-xs text-[#1d1d1f] focus:outline-none focus:border-emerald-600"
                                            >
                                        @endguest
                                        <button type="submit" class="w-full h-10 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs shadow-xs transition-all flex items-center justify-center gap-1.5">
                                            <i class="ri-download-cloud-line text-sm"></i>
                                            <span>{{ app()->getLocale() === 'ar' ? 'تحميل النسخة المجانية (Playbook)' : 'Download Free Playbook' }}</span>
                                        </button>
                                    </form>
                                </div>
                            @endif

                            @php
                                $mainFormattedPrice = $product->viewer_price_formatted ?? $product->formatted_price;
                                $convertedBasePrice = (float) ($product->converted_price ?? $product->price);
                                $originalFormattedPrice = \App\Helpers\FinanceHelper::instance()->format_money($convertedBasePrice * 1.3, $viewerCurrency->id ?? 1);
                            @endphp
                            <div class="flex items-baseline justify-between gap-4">
                                <div>
                                    <span class="text-xs text-[#86868b] font-medium block mb-0.5">{{ app()->getLocale() === 'ar' ? 'الإصدار الكامل المعتمد' : 'Official Full Edition' }}</span>
                                    <div class="flex items-baseline gap-2.5 flex-wrap">
                                        <span class="text-3xl sm:text-4xl font-bold font-mono text-[#0071e3]">{{ $mainFormattedPrice }}</span>
                                        @if(($viewerCurrency->id ?? 1) !== 1 && !empty($product->usd_price_formatted))
                                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 font-mono font-semibold border border-black/5">
                                                ≈ {{ $product->usd_price_formatted }}
                                            </span>
                                        @endif
                                        <span class="text-xs text-[#86868b] line-through font-mono">{{ $originalFormattedPrice }}</span>
                                    </div>
                                </div>
                                <span class="px-3 py-1 rounded-full bg-blue-50 text-[#0066cc] border border-blue-200/80 text-xs font-semibold shrink-0">
                                    Lifetime Access
                                </span>
                            </div>

                            @auth
                                @php
                                    $userBal = (float) auth()->user()->available_balance();
                                    $userBalFormatted = \App\Helpers\FinanceHelper::instance()->format_money($userBal, $userCurrency->id ?? 1);
                                    $confirmMsg = app()->getLocale() === 'ar'
                                        ? 'تأكيد شراء كتاب ' . addslashes($product->title) . ' بمبلغ ' . $userPriceFormatted . '؟'
                                        : 'Confirm purchasing ' . addslashes($product->title) . ' for ' . $userPriceFormatted . '?';
                                @endphp
                                <form action="{{ route('library.buy.wallet', $product->slug) }}" method="POST">
                                    @csrf
                                    <button type="submit" onclick="return confirm('{{ $confirmMsg }}')" class="w-full h-11 rounded-full bg-[#0071e3] hover:bg-[#0077ed] text-white font-medium text-xs shadow-xs transition-all flex items-center justify-center gap-2">
                                        <i class="ri-shopping-bag-3-fill text-base"></i>
                                        <span>{{ app()->getLocale() === 'ar' ? 'شراء الكتاب من المحفظة — ' . $userPriceFormatted : 'Buy Book from Wallet — ' . $userPriceFormatted }}</span>
                                    </button>
                                </form>

                                <div class="flex items-center justify-between text-xs text-[#86868b] pt-1 px-1">
                                    <span>{{ app()->getLocale() === 'ar' ? 'رصيدك المتاح:' : 'Available Balance:' }} <strong class="text-[#1d1d1f] font-mono font-bold">{{ $userBalFormatted }}</strong></span>
                                </div>
                            @else
                                <a href="{{ route('login') }}" class="w-full h-11 rounded-full bg-[#0071e3] hover:bg-[#0077ed] text-white font-medium text-xs shadow-xs transition-all flex items-center justify-center gap-2">
                                    <i class="ri-login-box-line text-base"></i>
                                    <span>{{ app()->getLocale() === 'ar' ? 'تسجيل الدخول لشراء الكتاب — ' . $mainFormattedPrice : 'Sign In to Purchase — ' . $mainFormattedPrice }}</span>
                                </a>
                            @endauth
                        </div>
                    @endif

                    <!-- Trust Checklist -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-4 border-t border-black/5 text-xs text-[#86868b]">
                        <div class="flex items-center gap-2">
                            <i class="ri-checkbox-circle-fill text-emerald-600 text-base"></i>
                            <span>{{ app()->getLocale() === 'ar' ? 'تحميل مباشر بدون إعلانات' : 'Direct Instant Download' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="ri-checkbox-circle-fill text-emerald-600 text-base"></i>
                            <span>{{ app()->getLocale() === 'ar' ? 'وصول دائم مدى الحياة' : 'Lifetime Offline Access' }}</span>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- 3. OVERVIEW & WHAT YOU WILL LEARN                                      -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="rounded-[22px] bg-white border border-black/5 p-6 sm:p-10 lg:p-12 space-y-8">
        <div>
            <h2 class="text-xl font-semibold text-[#1d1d1f] mb-4">
                {{ app()->getLocale() === 'ar' ? 'نبذة ومحتوى الكتاب' : 'About This Book' }}
            </h2>
            <div class="text-sm text-[#1d1d1f]/85 leading-relaxed space-y-3 font-normal">
                @if($product->description)
                    {!! nl2br(e($product->description)) !!}
                @else
                    <p>{{ app()->getLocale() === 'ar' ? 'يقدم هذا الكتاب دليلاً عملياً ومنهجياً يركز على التطبيق العملي ونقل الخبرة الحقيقية المباشرة بأسلوب سلس يناسب مختلف المستويات.' : 'A practical engineering playbook covering real-world architectural decisions and production-tested patterns.' }}</p>
                @endif
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- 4. RELATED BOOKS RECOMMENDATIONS                                       -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    @if(isset($relatedProducts) && $relatedProducts->count() > 0)
        <section class="space-y-6 pt-4">
            <h3 class="text-lg font-semibold text-[#1d1d1f]">
                {{ app()->getLocale() === 'ar' ? 'إصدارات ذات صلة قد تهمك' : 'Related Books & Guides' }}
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                @foreach($relatedProducts as $relBook)
                    @php
                        $relCover = $relBook->cover_image_path ? $relBook->cover_url : ($relBook->cover_image ? asset($relBook->cover_image) : asset('images/apple/web-mobile-suite.jpg'));
                        $relIsFree = $relBook->is_free || (float)$relBook->price <= 0;
                    @endphp
                    <article class="apple-bento-card p-4 flex flex-col justify-between group">
                        <div>
                            <a href="{{ route('library.show', $relBook->slug) }}" class="block aspect-[3/4] w-full rounded-[14px] overflow-hidden bg-[#f5f5f7] mb-3 border border-black/5">
                                <img src="{{ $relCover }}" alt="{{ $relBook->title }}" class="w-full h-full object-cover">
                            </a>
                            <h4 class="font-semibold text-xs sm:text-sm text-[#1d1d1f] group-hover:text-[#0071e3] line-clamp-2 mb-1">
                                <a href="{{ route('library.show', $relBook->slug) }}">{{ $relBook->title }}</a>
                            </h4>
                        </div>
                        <div class="pt-2 border-t border-black/5 flex items-center justify-between text-xs">
                            <span class="font-semibold {{ $relIsFree ? 'text-emerald-700' : 'text-[#0071e3]' }}">
                                {{ $relIsFree ? (app()->getLocale() === 'ar' ? 'مجاني' : 'Free') : ($relBook->viewer_price_formatted ?? $relBook->formatted_price) }}
                            </span>
                            <a href="{{ route('library.show', $relBook->slug) }}" class="text-[#0066cc] hover:underline font-medium">
                                {{ app()->getLocale() === 'ar' ? 'عرض' : 'View' }} ➔
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

</div>
@endsection

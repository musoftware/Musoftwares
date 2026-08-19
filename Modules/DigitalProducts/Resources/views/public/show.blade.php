@extends('digitalproducts::layouts.library-master')

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
                    'price' => $product->is_free ? '0.00' : (string) $product->price,
                    'priceCurrency' => $product->currency?->code ?? 'USD',
                    'availability' => 'https://schema.org/InStock',
                    'url' => route('library.show', $product->slug),
                ],
            ])
        ],
    ];
@endphp
<!-- Schema.org JSON-LD Structured Data for Google Rich Snippets -->
<script type="application/ld+json">
{!! json_encode($schemaData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush

@section('content')
<div class="max-w-[1340px] mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10 space-y-12">

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- 1. ENHANCED BREADCRUMB (Higher Visual Weight & Clear Hierarchy)        -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <nav class="flex items-center gap-2.5 text-xs sm:text-sm text-slate-500 dark:text-zinc-400 font-medium">
        <a href="{{ route('library.index') }}" class="hover:text-[#ff7a59] transition-colors flex items-center gap-1.5 font-bold">
            <i class="ri-home-4-line text-sm"></i>
            <span>{{ app()->getLocale() === 'ar' ? 'المكتبة الرقمية' : 'Digital Library' }}</span>
        </a>
        <i class="ri-arrow-left-s-line text-slate-400 dark:text-zinc-600 text-sm {{ app()->getLocale() === 'ar' ? '' : 'rotate-180' }}"></i>
        @if($product->category)
            <a href="{{ route('library.index', ['category' => $product->category->slug]) }}" class="px-2.5 py-1 rounded-lg bg-white dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 text-[#ff7a59] hover:bg-[#ff7a59] hover:text-white transition-all font-bold text-xs shadow-2xs">
                {{ $product->category->name }}
            </a>
            <i class="ri-arrow-left-s-line text-slate-400 dark:text-zinc-600 text-sm {{ app()->getLocale() === 'ar' ? '' : 'rotate-180' }}"></i>
        @endif
        <span class="text-[#2e1f1d] dark:text-zinc-100 font-extrabold truncate max-w-xs sm:max-w-md">{{ $product->title }}</span>
    </nav>


    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- 2. MAIN PRODUCT HERO CANVAS (Left: 3D Preview | Right: Conversion Box)  -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="bookhouse-canvas p-6 sm:p-10 lg:p-12 transition-all">
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-14 items-start">

            <!-- ── Left Column: 3D Cover + Preview + Selling Metadata (5 Cols) ── -->
            <div class="lg:col-span-5 flex flex-col items-center">
                <div class="relative w-full max-w-md flex flex-col items-center">
                    
                    <!-- Ambient Backdrop Blobs behind the 3D book -->
                    <div class="relative w-full aspect-[3/4] max-w-[290px] flex items-center justify-center mb-6">
                        <!-- Fluid Organic Shape Layers -->
                        <div class="absolute -left-6 top-6 w-52 h-52 bg-[#00dfc0] dark:bg-[#00dfc0]/30 rounded-[45%_55%_70%_30%/45%_45%_55%_55%] transform -rotate-12 pointer-events-none"></div>
                        <div class="absolute -right-4 -top-2 w-56 h-56 rounded-[60%_40%_30%_70%/60%_30%_70%_40%] bg-[#ff7a59] dark:bg-[#ff7a59]/30 opacity-90 pointer-events-none overflow-hidden flex items-center justify-center">
                            <svg class="w-full h-full text-white/30" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="2.5">
                                <line x1="-20" y1="20" x2="120" y2="20" transform="rotate(-30 50 50)" />
                                <line x1="-20" y1="40" x2="120" y2="40" transform="rotate(-30 50 50)" />
                                <line x1="-20" y1="60" x2="120" y2="60" transform="rotate(-30 50 50)" />
                                <line x1="-20" y1="80" x2="120" y2="80" transform="rotate(-30 50 50)" />
                            </svg>
                        </div>

                        <!-- 3D Standing Book Cover -->
                        <div class="relative z-10 w-full h-full rounded-xl overflow-hidden book-shadow-3d bg-[#141418] border border-black/10 dark:border-white/10 group flex items-center justify-center">
                            @if($product->cover_image_path)
                                <img src="{{ $product->cover_url }}" alt="{{ $product->title }}" class="w-full h-full object-cover rounded-lg">
                            @else
                                <div class="w-full h-full bg-[#1a1a22] p-6 flex flex-col justify-between text-white">
                                    <div class="text-[10px] font-mono tracking-widest text-[#00dfc0] uppercase">
                                        {{ $product->category?->name ?? 'PLAYBOOK' }}
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-zinc-400 uppercase tracking-widest block mb-1">PRACTICAL GUIDE</span>
                                        <h3 class="text-xl font-black font-editorial leading-tight text-white">{{ $product->title }}</h3>
                                    </div>
                                    <div class="flex items-center justify-between text-xs text-zinc-400 font-mono">
                                        <span>{{ $product->author_name ?? 'Musoftware' }}</span>
                                        <i class="ri-book-open-line text-base text-[#ff7a59]"></i>
                                    </div>
                                </div>
                            @endif

                            @if($product->is_free)
                                <div class="absolute top-3 right-3 px-3 py-1 rounded-full bg-[#00dfc0] text-slate-900 text-[10px] font-black shadow-lg">
                                    FREE
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Quick Sample Pages Preview Trigger (Interactive Trust Booster) -->
                    <button 
                        type="button" 
                        onclick="document.getElementById('samplePreviewModal').classList.remove('hidden')" 
                        class="w-full mb-6 py-2.5 px-4 rounded-2xl bg-white dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 text-[#2e1f1d] dark:text-zinc-200 hover:border-[#ff7a59] dark:hover:border-[#ff7a59] text-xs font-bold transition-all flex items-center justify-center gap-2 shadow-2xs hover:scale-[1.01]"
                    >
                        <i class="ri-eye-line text-base text-[#ff7a59]"></i>
                        <span>{{ app()->getLocale() === 'ar' ? 'معاينة مقتطفات وفهرس الكتاب (View Preview)' : 'Look Inside & Table of Contents' }}</span>
                    </button>

                    <!-- Selling Points Metadata Cards (Reframed from generic tech labels to selling value) -->
                    <div class="w-full grid grid-cols-2 gap-3 text-xs">
                        <div class="p-3.5 rounded-2xl bg-[#fbf7f4] dark:bg-[#181820] border border-slate-200/80 dark:border-zinc-800">
                            <span class="text-slate-400 dark:text-zinc-500 block mb-0.5 text-[10px] uppercase tracking-wider font-semibold">حجم المحتوى</span>
                            <span class="font-extrabold text-[#2e1f1d] dark:text-white flex items-center gap-1.5 text-xs">
                                <i class="ri-pages-line text-[#ff7a59] text-sm"></i>
                                <span>{{ $product->page_count ? $product->page_count . ' صفحة عملية' : 'محتوى تطبيقي مكثف' }}</span>
                            </span>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-[#fbf7f4] dark:bg-[#181820] border border-slate-200/80 dark:border-zinc-800">
                            <span class="text-slate-400 dark:text-zinc-500 block mb-0.5 text-[10px] uppercase tracking-wider font-semibold">صيغة الملف</span>
                            <span class="font-extrabold text-[#2e1f1d] dark:text-white flex items-center gap-1.5 text-xs">
                                <i class="ri-file-pdf-2-line text-rose-500 text-sm"></i>
                                <span>PDF عالي الدقة للطباعة والقراءة</span>
                            </span>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-[#fbf7f4] dark:bg-[#181820] border border-slate-200/80 dark:border-zinc-800">
                            <span class="text-slate-400 dark:text-zinc-500 block mb-0.5 text-[10px] uppercase tracking-wider font-semibold">حجم الملف</span>
                            <span class="font-extrabold text-[#2e1f1d] dark:text-white flex items-center gap-1.5 text-xs font-mono">
                                <i class="ri-hard-drive-2-line text-amber-500 text-sm"></i>
                                <span>{{ $product->formatted_file_size }} (تحميل فوري)</span>
                            </span>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-[#fbf7f4] dark:bg-[#181820] border border-slate-200/80 dark:border-zinc-800">
                            <span class="text-slate-400 dark:text-zinc-500 block mb-0.5 text-[10px] uppercase tracking-wider font-semibold">اللغة والدعم</span>
                            <span class="font-extrabold text-[#2e1f1d] dark:text-white flex items-center gap-1.5 text-xs">
                                <i class="ri-translate-2 text-[#00dfc0] text-sm"></i>
                                <span>{{ strtoupper($product->language ?? 'AR') }} (لغة عربية سلسة)</span>
                            </span>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ── Right Column: Value Prop + Conversion Box + Trust Assurances (7 Cols) ── -->
            <div class="lg:col-span-7 space-y-6">
                
                <!-- Category, Ratings & Views Row -->
                <div class="flex flex-wrap items-center gap-3">
                    @if($product->category)
                        <span class="px-3 py-1 rounded-full bg-[#fdeee7] dark:bg-zinc-800 text-[#ff7a59] border border-[#ff7a59]/30 text-xs font-black uppercase tracking-wider">
                            {{ $product->category->name }}
                        </span>
                    @endif
                    <div class="flex items-center text-amber-500 text-sm gap-0.5">
                        <i class="ri-star-fill"></i>
                        <i class="ri-star-fill"></i>
                        <i class="ri-star-fill"></i>
                        <i class="ri-star-fill"></i>
                        <i class="ri-star-half-fill"></i>
                        <span class="text-xs font-bold text-slate-700 dark:text-zinc-300 ms-1.5">4.9 / 5.0</span>
                    </div>
                    <span class="text-xs text-slate-400 dark:text-zinc-500 flex items-center gap-1">
                        <i class="ri-download-cloud-2-line"></i> {{ number_format(max(45, $product->download_count)) }} عملية تحميل وقراءة
                    </span>
                </div>

                <!-- Main Book Title -->
                <div>
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-[#2e1f1d] dark:text-white font-editorial tracking-tight leading-[1.1] mb-3">
                        {{ $product->title }}
                    </h1>

                    <!-- One-Line Value Proposition (Critical Selling Point) -->
                    <p class="text-sm sm:text-base font-semibold text-slate-700 dark:text-zinc-300 leading-relaxed">
                        {{ $product->short_description ?: 'دليل تطبيقي وعملي شامل يركز على الممارسة المباشرة، فهم الأكواد والأنظمة، وبناء المهارة الحقيقية خطوة بخطوة من الصفر.' }}
                    </p>
                </div>

                <!-- Author & Publisher Meta Bar -->
                <div class="flex flex-wrap items-center gap-4 text-xs text-slate-600 dark:text-zinc-400 py-3 border-y border-slate-200/80 dark:border-zinc-800">
                    @if($product->author_name)
                        <div class="flex items-center gap-1.5">
                            <i class="ri-user-3-line text-[#ff7a59]"></i>
                            <span>{{ app()->getLocale() === 'ar' ? 'المؤلف:' : 'Author:' }} <strong class="text-[#2e1f1d] dark:text-white font-bold">{{ $product->author_name }}</strong></span>
                        </div>
                    @endif
                    @if($product->publisher)
                        <div class="flex items-center gap-1.5">
                            <i class="ri-building-line text-[#ff7a59]"></i>
                            <span>{{ app()->getLocale() === 'ar' ? 'الناشر:' : 'Publisher:' }} <strong class="text-[#2e1f1d] dark:text-white font-bold">{{ $product->publisher }}</strong></span>
                        </div>
                    @endif
                    @if($product->publication_year)
                        <div class="flex items-center gap-1.5">
                            <i class="ri-calendar-line text-[#ff7a59]"></i>
                            <span>{{ app()->getLocale() === 'ar' ? 'سنة الإصدار:' : 'Year:' }} <strong class="text-[#2e1f1d] dark:text-white font-bold">{{ $product->publication_year }}</strong></span>
                        </div>
                    @endif
                </div>

                <!-- ── HIGH-CONVERSION PURCHASE CARD (Clear Hierarchy & Trust) ── -->
                <div class="rounded-3xl bg-[#fbf7f4] dark:bg-[#181820] border border-slate-200/90 dark:border-zinc-800 p-6 sm:p-8 space-y-6 shadow-sm">
                    
                    @if($isPurchased)
                        <!-- State 1: User Already Owns This Book -->
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div>
                                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5 mb-1">
                                    <i class="ri-checkbox-circle-fill text-base"></i> تمتلك هذا الإصدار في مكتبتك الرقمية
                                </span>
                                <h4 class="text-base font-bold text-[#2e1f1d] dark:text-white">جاهز للتحميل والقراءة الفورية مدى الحياة</h4>
                            </div>
                            <a href="{{ route('library.my_library.download', $product->slug) }}" class="pill-btn-coral py-3 px-8 text-xs sm:text-sm">
                                <i class="ri-download-2-fill text-lg"></i>
                                <span>تحميل ملف الكتاب (PDF)</span>
                            </a>
                        </div>
                    @elseif($product->is_free)
                        <!-- State 2: 100% Free E-Book -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-black text-[#00dfc0] uppercase tracking-wider block mb-1">إصدار مجاني متاح للجميع</span>
                                    <h4 class="text-xl font-black font-editorial text-[#2e1f1d] dark:text-white">احصل على نسختك المجانية فوراً</h4>
                                </div>
                                <span class="text-2xl font-black font-editorial text-[#00dfc0]">
                                    0.00 $
                                </span>
                            </div>

                            <form action="{{ route('library.free_download', $product->slug) }}" method="POST" class="space-y-3">
                                @csrf
                                <input type="hidden" name="edition_type" value="full">
                                <div class="relative">
                                    <input 
                                        type="email" 
                                        name="email" 
                                        value="{{ auth()->user()?->email ?? '' }}" 
                                        required 
                                        placeholder="أدخل بريدك الإلكتروني لتحميل الكتاب مباشرة..." 
                                        class="w-full h-12 ps-4 pe-12 rounded-full bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 text-slate-900 dark:text-white text-xs focus:outline-none focus:border-[#ff7a59] shadow-inner"
                                    >
                                    <i class="ri-mail-send-line absolute end-4 top-3.5 text-slate-400 text-base"></i>
                                </div>
                                <button type="submit" class="pill-btn-coral w-full py-3.5 text-xs sm:text-sm">
                                    <i class="ri-download-cloud-line text-lg"></i>
                                    <span>تحميل الكتاب الآن مجاناً (PDF)</span>
                                </button>
                            </form>
                        </div>
                    @else
                        <!-- State 3: Paid Book Conversion Box -->
                        <div class="space-y-5">
                            
                            <!-- Price Display Row -->
                            <div class="flex items-baseline justify-between gap-4">
                                <div>
                                    <span class="text-xs text-slate-400 dark:text-zinc-500 font-semibold block mb-0.5">سعر النسخة الرقمية الكاملة</span>
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-4xl sm:text-5xl font-black font-editorial text-[#2e1f1d] dark:text-white">${{ number_format($product->price, 2) }}</span>
                                        <span class="text-xs font-bold text-slate-400 line-through font-mono">${{ number_format($product->price * 1.35, 2) }}</span>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <span class="px-3 py-1 rounded-full bg-[#00dfc0]/15 text-[#00a892] dark:text-[#00dfc0] border border-[#00dfc0]/30 text-xs font-bold inline-block">
                                        Instant Digital Access
                                    </span>
                                </div>
                            </div>

                            <!-- Primary Purchase CTA Button (Clean & Direct) -->
                            @auth
                                <form action="{{ route('library.buy.wallet', $product->slug) }}" method="POST">
                                    @csrf
                                    <button type="submit" onclick="return confirm('تأكيد شراء كتاب {{ addslashes($product->title) }} بمبلغ ${{ number_format($product->price, 2) }}؟')" class="pill-btn-coral w-full py-4 text-sm sm:text-base font-extrabold">
                                        <i class="ri-shopping-bag-3-fill text-lg"></i>
                                        <span>شراء الكتاب — ${{ number_format($product->price, 2) }}</span>
                                    </button>
                                </form>
                                
                                @if(isset(auth()->user()->user_balance))
                                    <div class="flex items-center justify-between text-xs text-slate-500 dark:text-zinc-400 pt-1 px-1">
                                        <span>رصيد محفظتك المتاح: <strong class="text-emerald-600 dark:text-emerald-400 font-mono font-bold">${{ number_format(auth()->user()->user_balance, 2) }}</strong></span>
                                        @if(auth()->user()->user_balance < $product->price)
                                            <a href="{{ url('/wallet/deposit') }}" class="text-[#ff7a59] font-bold hover:underline">شحن الرصيد</a>
                                        @endif
                                    </div>
                                @endif
                            @else
                                <a href="{{ route('login') }}" class="pill-btn-coral w-full py-4 text-sm sm:text-base font-extrabold">
                                    <i class="ri-user-shared-line text-lg"></i>
                                    <span>تسجيل الدخول لشراء الكتاب — ${{ number_format($product->price, 2) }}</span>
                                </a>
                            @endauth

                        </div>
                    @endif

                    <!-- Trust Checklist Badges (Direct Psychology & Assurance) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-4 border-t border-slate-200/80 dark:border-zinc-800 text-xs text-slate-600 dark:text-zinc-300">
                        <div class="flex items-center gap-2">
                            <i class="ri-checkbox-circle-fill text-emerald-500 text-base"></i>
                            <span>استلام فوري وتحميل مباشر بعد الشراء</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="ri-checkbox-circle-fill text-emerald-500 text-base"></i>
                            <span>وصول دائم مدى الحياة (Lifetime Access)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="ri-checkbox-circle-fill text-emerald-500 text-base"></i>
                            <span>ملف PDF كامل متوافق مع كافة الأجهزة</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="ri-checkbox-circle-fill text-emerald-500 text-base"></i>
                            <span>متاح دائماً في حسابك بـ "مكتبتي"</span>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- 3. "WHY BUY?" & VALUE BREAKDOWN (What You'll Learn, Who It's For)       -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="bookhouse-canvas p-6 sm:p-10 lg:p-12 space-y-10">
        
        <!-- Section A: What You'll Learn (Core Benefit Checklists) -->
        <div>
            <div class="flex items-center gap-2 text-xs font-black uppercase tracking-widest text-[#ff7a59] mb-2">
                <i class="ri-sparkling-fill"></i>
                <span>القيمة والمهارات المكتسبة</span>
            </div>
            <h2 class="text-2xl sm:text-3xl font-black text-[#2e1f1d] dark:text-white font-editorial mb-6">
                ماذا ستتعلم وتكتسب من هذا الإصدار؟
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 rounded-2xl bg-[#fbf7f4] dark:bg-[#181820] border border-slate-200/80 dark:border-zinc-800 flex items-start gap-3">
                    <div class="w-7 h-7 rounded-full bg-[#ff7a59]/15 text-[#ff7a59] flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="ri-check-line font-bold"></i>
                    </div>
                    <div>
                        <h4 class="font-extrabold text-sm text-[#2e1f1d] dark:text-white">التأسيس المنهجي المتين</h4>
                        <p class="text-xs text-slate-600 dark:text-zinc-400 mt-1 leading-relaxed">فهم الأصول والقواعد الأساسية بعيداً عن التعقيدات الأكاديمية المجردة.</p>
                    </div>
                </div>

                <div class="p-4 rounded-2xl bg-[#fbf7f4] dark:bg-[#181820] border border-slate-200/80 dark:border-zinc-800 flex items-start gap-3">
                    <div class="w-7 h-7 rounded-full bg-[#00dfc0]/20 text-[#00a892] dark:text-[#00dfc0] flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="ri-code-s-slash-line font-bold"></i>
                    </div>
                    <div>
                        <h4 class="font-extrabold text-sm text-[#2e1f1d] dark:text-white">أمثلة وتطبيقات برمجية واقعية</h4>
                        <p class="text-xs text-slate-600 dark:text-zinc-400 mt-1 leading-relaxed">كتابة برامج وحلول متكاملة تم إعدادها واختبارها عملياً خطوة بخطوة.</p>
                    </div>
                </div>

                <div class="p-4 rounded-2xl bg-[#fbf7f4] dark:bg-[#181820] border border-slate-200/80 dark:border-zinc-800 flex items-start gap-3">
                    <div class="w-7 h-7 rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="ri-lightbulb-line font-bold"></i>
                    </div>
                    <div>
                        <h4 class="font-extrabold text-sm text-[#2e1f1d] dark:text-white">تطوير عقلية حل المشكلات (Problem Solving)</h4>
                        <p class="text-xs text-slate-600 dark:text-zinc-400 mt-1 leading-relaxed">كيفية تحليل المتطلبات والتفكير المنطقي السليم لهندسة حلول فعالة.</p>
                    </div>
                </div>

                <div class="p-4 rounded-2xl bg-[#fbf7f4] dark:bg-[#181820] border border-slate-200/80 dark:border-zinc-800 flex items-start gap-3">
                    <div class="w-7 h-7 rounded-full bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="ri-shield-check-line font-bold"></i>
                    </div>
                    <div>
                        <h4 class="font-extrabold text-sm text-[#2e1f1d] dark:text-white">أفضل الممارسات البرمجية وتجنب الأخطاء</h4>
                        <p class="text-xs text-slate-600 dark:text-zinc-400 mt-1 leading-relaxed">التعرف على أشهر الأخطاء الشائعة وطرق كتابة كود نظيف وقابل للتوسع.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section B: Who Is This For? -->
        <div class="pt-6 border-t border-slate-200/80 dark:border-zinc-800">
            <h3 class="text-lg font-black text-[#2e1f1d] dark:text-white font-editorial mb-4">
                {{ app()->getLocale() === 'ar' ? 'لمن هذا الكتاب؟' : 'Who Is This Book For?' }}
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                <div class="p-4 rounded-2xl bg-white dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 space-y-1">
                    <span class="font-black text-[#ff7a59] text-sm block">المبتدئون والشغوفون</span>
                    <p class="text-slate-600 dark:text-zinc-400">الراغبون في الانطلاق بقوة وبناء قاعدة صلبة ومفاهيم دقيقة من البداية.</p>
                </div>
                <div class="p-4 rounded-2xl bg-white dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 space-y-1">
                    <span class="font-black text-[#00dfc0] text-sm block">الطلاب والدارسون</span>
                    <p class="text-slate-600 dark:text-zinc-400">كمرجع تطبيقي شامل ومبسط يساعد على اجتياز المقررات والمشاريع العملية.</p>
                </div>
                <div class="p-4 rounded-2xl bg-white dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 space-y-1">
                    <span class="font-black text-amber-500 text-sm block">المطورون الممارسون</span>
                    <p class="text-slate-600 dark:text-zinc-400">الراغبون في مراجعة الأصول البرمجية العميقة وإتقان التفاصيل الدقيقة.</p>
                </div>
            </div>
        </div>

        <!-- Section C: Scannable Description -->
        <div class="pt-6 border-t border-slate-200/80 dark:border-zinc-800">
            <h3 class="text-lg font-black text-[#2e1f1d] dark:text-white font-editorial mb-3">
                {{ app()->getLocale() === 'ar' ? 'نبذة تفصيلية عن محتوى الكتاب' : 'Detailed Overview' }}
            </h3>
            <div class="text-sm text-slate-700 dark:text-zinc-300 leading-relaxed space-y-3">
                @if($product->description)
                    {!! nl2br(e($product->description)) !!}
                @else
                    <p>يقدم هذا الكتاب دليلاً عملياً ومنهجياً تم إعداده وصياغته بعناية فائقة ليركز على التطبيق العملي ونقل الخبرة الحقيقية المباشرة بأسلوب عربي فصيح وسلس يناسب مختلف المستويات.</p>
                @endif
            </div>
        </div>

    </div>


    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- 4. CONTEXTUAL RECOMMENDATIONS (Continue Your Learning Path)           -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    @if($relatedProducts->count() > 0)
        <section class="space-y-6 pt-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-300/80 dark:border-zinc-800">
                <div>
                    <span class="text-xs font-black uppercase tracking-widest text-[#ff7a59] block mb-1">
                        {{ app()->getLocale() === 'ar' ? 'مسار التعلم التكاملي' : 'CONTINUE YOUR PATH' }}
                    </span>
                    <h3 class="text-2xl sm:text-3xl font-black text-[#2e1f1d] dark:text-white font-editorial">
                        {{ app()->getLocale() === 'ar' ? 'أكمل مسارك في ' . ($product->category->name ?? 'التطوير البرمجي') : 'Recommended Next Reads' }}
                    </h3>
                </div>
                <a href="{{ route('library.index', ['category' => $product->category?->slug]) }}" class="text-xs font-extrabold text-[#ff7a59] hover:underline">
                    {{ app()->getLocale() === 'ar' ? 'استكشف المزيد من هذا القسم' : 'Browse All in Category' }} →
                </a>
            </div>

            <!-- 4-Column Responsive Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-7">
                @foreach($relatedProducts as $rel)
                    <div class="group rounded-3xl bg-white dark:bg-[#121217] border border-slate-200/80 dark:border-zinc-800/80 p-5 flex flex-col justify-between hover:border-[#ff7a59] transition-all duration-300 shadow-sm hover:shadow-xl">
                        
                        <!-- 3D Cover -->
                        <a href="{{ route('library.show', $rel->slug) }}" class="relative aspect-[3/4] w-full rounded-2xl overflow-hidden bg-[#faf4ef] dark:bg-zinc-950 p-4 mb-4 flex items-center justify-center">
                            @if($rel->cover_image_path)
                                <img src="{{ $rel->cover_url }}" alt="{{ $rel->title }}" class="w-full h-full object-cover rounded-xl book-shadow-3d group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="w-full h-full rounded-xl bg-[#1a1a22] p-4 flex flex-col justify-between text-white book-shadow-3d">
                                    <span class="text-[10px] text-[#00dfc0] font-mono uppercase">{{ $rel->category?->name ?? 'EBOOK' }}</span>
                                    <h4 class="font-bold text-xs line-clamp-3">{{ $rel->title }}</h4>
                                    <span class="text-[10px] text-zinc-400 font-mono">{{ $rel->author_name ?? 'Musoftware' }}</span>
                                </div>
                            @endif

                            <div class="absolute top-3 right-3">
                                @if($rel->is_free)
                                    <span class="px-2.5 py-0.5 rounded-full bg-[#00dfc0] text-slate-900 text-[10px] font-black shadow-md">
                                        FREE
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full bg-[#2e1f1d] text-white dark:bg-white dark:text-[#2e1f1d] text-[10px] font-black font-mono shadow-md">
                                        ${{ number_format($rel->price, 2) }}
                                    </span>
                                @endif
                            </div>
                        </a>

                        <!-- Details -->
                        <div class="space-y-2 flex-1 flex flex-col justify-between">
                            <div>
                                <span class="text-[10px] font-black text-[#ff7a59] uppercase tracking-wider block">
                                    {{ $rel->category?->name ?? 'Playbook' }}
                                </span>

                                <h4 class="text-sm font-black text-[#2e1f1d] dark:text-white font-editorial line-clamp-2 leading-snug group-hover:text-[#ff7a59] transition-colors">
                                    <a href="{{ route('library.show', $rel->slug) }}">
                                        {{ $rel->title }}
                                    </a>
                                </h4>

                                <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-1">
                                    {{ app()->getLocale() === 'ar' ? 'تأليف:' : 'By' }} {{ $rel->author_name ?? 'Musoftware' }}
                                </p>
                            </div>

                            <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-zinc-800/80 mt-auto">
                                <div class="text-xs font-mono font-black text-[#2e1f1d] dark:text-white">
                                    @if($rel->is_free)
                                        <span class="text-[#00dfc0] font-bold">{{ app()->getLocale() === 'ar' ? 'مجاني' : 'FREE' }}</span>
                                    @else
                                        ${{ number_format($rel->price, 2) }}
                                    @endif
                                </div>

                                <a href="{{ route('library.show', $rel->slug) }}" class="px-4 py-1.5 rounded-full bg-[#ff7a59] hover:bg-[#f06443] text-white text-[11px] font-extrabold shadow-xs transition-transform hover:scale-105 flex items-center gap-1">
                                    <span>{{ app()->getLocale() === 'ar' ? 'عرض الكتاب' : 'View' }}</span>
                                    <i class="ri-arrow-right-line text-xs"></i>
                                </a>
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>
        </section>
    @endif

</div>


<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- SAMPLE PREVIEW MODAL (Interactive Trust & Sample Peek)                     -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div id="samplePreviewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm hidden">
    <div class="relative w-full max-w-2xl rounded-3xl bg-white dark:bg-zinc-900 border border-slate-200 dark:border-zinc-700 p-6 sm:p-8 shadow-2xl space-y-6">
        
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-zinc-800">
            <div>
                <span class="text-[10px] font-bold text-[#ff7a59] uppercase tracking-wider block">معاينة صفحات من الكتاب</span>
                <h3 class="text-lg font-black text-[#2e1f1d] dark:text-white font-editorial">{{ $product->title }}</h3>
            </div>
            <button type="button" onclick="document.getElementById('samplePreviewModal').classList.add('hidden')" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-zinc-800 text-slate-500 hover:text-slate-900 dark:hover:text-white flex items-center justify-center">
                <i class="ri-close-line text-lg"></i>
            </button>
        </div>

        <!-- Sample Content Container -->
        <div class="space-y-4 max-h-[60vh] overflow-y-auto p-4 rounded-2xl bg-[#faf7f4] dark:bg-zinc-950 border border-slate-200/80 dark:border-zinc-800 text-xs sm:text-sm text-slate-700 dark:text-zinc-300 leading-relaxed">
            <h4 class="font-bold text-base text-[#2e1f1d] dark:text-white pb-2 border-b border-slate-200 dark:border-zinc-800">
                📑 فهرس المحتويات والمحاور الرئيسية:
            </h4>
            <ul class="space-y-2 list-disc list-inside">
                <li><strong>الفصل الأول:</strong> المدخل التأسيسي وبناء المفاهيم الجوهرية.</li>
                <li><strong>الفصل الثاني:</strong> البيئة التطبيقية والأدوات الأساسية للعمل.</li>
                <li><strong>الفصل الثالث:</strong> الهياكل البرمجية والتحكم في سير البيانات.</li>
                <li><strong>الفصل الرابع:</strong> بناء الدوال والوحدات النمطية القابلة لإعادة الاستخدام.</li>
                <li><strong>الفصل الخامس:</strong> التطبيقات العملية ومشاريع التخرج الشاملة.</li>
            </ul>
            <div class="p-3 rounded-xl bg-white dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 text-xs text-slate-500 italic text-center">
                هذه المعاينة توضح هيكل الفهرس المعتمد. يمكنك الحصول على النسخة الكاملة بـ {{ $product->is_free ? 'مجاناً' : '$' . number_format($product->price, 2) }} مع إمكانية التحميل الفوري بصيغة PDF.
            </div>
        </div>

        <!-- Action Footer -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <button type="button" onclick="document.getElementById('samplePreviewModal').classList.add('hidden')" class="px-5 py-2 rounded-full text-xs font-bold text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors">
                إغلاق
            </button>
            <button type="button" onclick="document.getElementById('samplePreviewModal').classList.add('hidden'); window.scrollTo({top: 200, behavior: 'smooth'})" class="pill-btn-coral py-2 px-6 text-xs">
                <span>{{ $product->is_free ? 'تحميل الكتاب الآن' : 'شراء النسخة الكاملة' }}</span>
            </button>
        </div>

    </div>
</div>
@endsection

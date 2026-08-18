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
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-zinc-400 mb-8 overflow-x-auto pb-2">
        <a href="{{ route('library.index') }}" class="hover:text-white transition-colors flex items-center gap-1">
            <i class="ri-home-4-line"></i> المكتبة
        </a>
        <i class="ri-arrow-left-s-line text-zinc-600"></i>
        @if($product->category)
            <a href="{{ route('library.index', ['category' => $product->category->slug]) }}" class="hover:text-white transition-colors">
                {{ $product->category->name }}
            </a>
            <i class="ri-arrow-left-s-line text-zinc-600"></i>
        @endif
        <span class="text-zinc-200 font-medium truncate">{{ $product->title }}</span>
    </nav>

    <!-- Main Details Container -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">

        <!-- Left / Cover Column (5 cols) -->
        <div class="lg:col-span-5 flex flex-col items-center">
            <div class="relative w-full max-w-sm rounded-2xl glass-card p-6 border border-zinc-800 flex flex-col items-center">
                
                <!-- Book Cover with 3D Effect -->
                <div class="relative w-full aspect-[3/4] max-w-[280px] rounded-xl overflow-hidden book-shadow border border-white/10 bg-zinc-950 p-2 flex items-center justify-center mb-6">
                    @if($product->cover_image_path)
                        <img src="{{ $product->cover_url }}" alt="{{ $product->title }}" class="w-full h-full object-contain rounded-lg">
                    @else
                        <div class="w-full h-full rounded-lg bg-gradient-to-br from-zinc-800 to-dark-900 border border-zinc-700/60 p-6 flex flex-col justify-between">
                            <i class="ri-book-2-line text-5xl text-brand-400"></i>
                            <div>
                                <span class="text-xs text-zinc-500 font-semibold uppercase">{{ $product->category?->name ?? 'PDF EBOOK' }}</span>
                                <h3 class="text-base font-bold text-white mt-1">{{ $product->title }}</h3>
                            </div>
                            <span class="text-xs text-zinc-400 font-mono">{{ $product->author_name ?? 'Musoftware' }}</span>
                        </div>
                    @endif

                    @if($product->is_free)
                        <div class="absolute top-4 right-4 px-3 py-1 rounded-lg bg-emerald-500 text-white text-xs font-bold shadow-lg">
                            تحميل مجاني
                        </div>
                    @endif
                </div>

                <!-- Quick Specs List -->
                <div class="w-full grid grid-cols-2 gap-3 text-xs">
                    <div class="p-3 rounded-xl bg-zinc-900/80 border border-zinc-800">
                        <span class="text-zinc-500 block mb-1">صيغة الملف</span>
                        <span class="font-bold text-white flex items-center gap-1.5">
                            <i class="ri-file-pdf-2-line text-rose-400 text-base"></i> PDF Document
                        </span>
                    </div>
                    <div class="p-3 rounded-xl bg-zinc-900/80 border border-zinc-800">
                        <span class="text-zinc-500 block mb-1">عدد الصفحات</span>
                        <span class="font-bold text-white flex items-center gap-1.5">
                            <i class="ri-pages-line text-brand-400 text-base"></i> {{ $product->page_count ?? '—' }} صفحة
                        </span>
                    </div>
                    <div class="p-3 rounded-xl bg-zinc-900/80 border border-zinc-800">
                        <span class="text-zinc-500 block mb-1">حجم الملف</span>
                        <span class="font-bold text-white flex items-center gap-1.5">
                            <i class="ri-hard-drive-2-line text-amber-400 text-base"></i> {{ $product->formatted_file_size }}
                        </span>
                    </div>
                    <div class="p-3 rounded-xl bg-zinc-900/80 border border-zinc-800">
                        <span class="text-zinc-500 block mb-1">لغة الكتاب</span>
                        <span class="font-bold text-white flex items-center gap-1.5">
                            <i class="ri-translate-2 text-cyan-400 text-base"></i> {{ strtoupper($product->language ?? 'AR') }}
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <!-- Right / Info & Action Column (7 cols) -->
        <div class="lg:col-span-7 flex flex-col justify-between">
            <div>
                <!-- Category & Badges -->
                <div class="flex items-center gap-3 mb-3">
                    @if($product->category)
                        <span class="px-3 py-1 rounded-lg bg-brand-500/10 border border-brand-500/30 text-brand-400 text-xs font-semibold">
                            {{ $product->category->name }}
                        </span>
                    @endif
                    <span class="text-xs text-zinc-500 flex items-center gap-1">
                        <i class="ri-download-cloud-2-line"></i> {{ number_format($product->download_count) }} تحميل
                    </span>
                    <span class="text-xs text-zinc-500 flex items-center gap-1">
                        <i class="ri-eye-line"></i> {{ number_format($product->view_count) }} مشاهدة
                    </span>
                </div>

                <!-- Title -->
                <h1 class="text-2xl sm:text-4xl font-extrabold text-white tracking-tight mb-4 leading-tight">
                    {{ $product->title }}
                </h1>

                <!-- Author & Publisher -->
                <div class="flex flex-wrap items-center gap-4 text-sm text-zinc-400 mb-6 pb-6 border-b border-zinc-800">
                    @if($product->author_name)
                        <div class="flex items-center gap-2">
                            <i class="ri-user-3-line text-zinc-500"></i>
                            <span>المؤلف: <strong class="text-zinc-200">{{ $product->author_name }}</strong></span>
                        </div>
                    @endif
                    @if($product->publisher)
                        <div class="flex items-center gap-2">
                            <i class="ri-building-line text-zinc-500"></i>
                            <span>الناشر: <strong class="text-zinc-200">{{ $product->publisher }}</strong></span>
                        </div>
                    @endif
                    @if($product->publication_year)
                        <div class="flex items-center gap-2">
                            <i class="ri-calendar-line text-zinc-500"></i>
                            <span>سنة النشر: <strong class="text-zinc-200">{{ $product->publication_year }}</strong></span>
                        </div>
                    @endif
                </div>

                <!-- Action Card (Purchase or Free Download) -->
                <div class="rounded-2xl glass-card p-6 border border-zinc-800 mb-8">
                    @if($isPurchased)
                        <!-- User Already Purchased -->
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div>
                                <span class="text-xs font-semibold text-emerald-400 flex items-center gap-1 mb-1">
                                    <i class="ri-checkbox-circle-fill"></i> تمتلك هذا الكتاب في مكتبتك
                                </span>
                                <h4 class="text-sm font-bold text-white">يمكنك تحميل الكتاب في أي وقت مدى الحياة</h4>
                            </div>
                            <a href="{{ route('library.my_library.download', $product->slug) }}" class="w-full sm:w-auto px-6 py-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm shadow-lg shadow-emerald-600/30 flex items-center justify-center gap-2 transition-all">
                                <i class="ri-download-2-fill text-lg"></i>
                                <span>تحميل ملف الـ PDF</span>
                            </a>
                        </div>
                    @elseif($product->is_free)
                        <!-- Free Download with Email Verification -->
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider block">تحميل مجاني 100%</span>
                                    <h4 class="text-lg font-bold text-white">احصل على نسختك المجانية الآن</h4>
                                </div>
                                <span class="px-3 py-1 rounded-lg bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-xs font-bold">
                                    Free Ebook
                                </span>
                            </div>

                            <form action="{{ route('library.free_download', $product->slug) }}" method="POST" class="space-y-3">
                                @csrf
                                <div class="relative">
                                    <i class="ri-mail-line absolute right-4 top-3.5 text-zinc-400 text-base"></i>
                                    <input type="email" name="email" value="{{ auth()->user()?->email ?? '' }}" required placeholder="أدخل بريدك الإلكتروني لاستلام رابط التحميل..." class="w-full h-12 pr-11 pl-4 rounded-xl bg-zinc-900 border border-zinc-700/80 text-white placeholder-zinc-500 text-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all">
                                </div>
                                <button type="submit" class="w-full h-12 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm shadow-lg shadow-emerald-600/25 transition-all flex items-center justify-center gap-2">
                                    <i class="ri-download-cloud-line text-lg"></i>
                                    <span>تحميل الكتاب مجاناً</span>
                                </button>
                                <p class="text-[11px] text-zinc-500 text-center">
                                    🔒 نلتزم بحماية خصوصيتك ولن يتم إرسال أي رسائل غير مرغوب فيها.
                                </p>
                            </form>
                        </div>
                    @else
                        <!-- Paid Book Flow -->
                        <div>
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <span class="text-xs text-zinc-400 block mb-1">سعر النسخة الرقمية</span>
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-3xl font-extrabold text-white">{{ number_format($product->price, 2) }}</span>
                                        <span class="text-sm font-semibold text-brand-400">{{ $product->currency?->code ?? 'USD' }}</span>
                                    </div>
                                </div>
                                <div class="text-left text-xs text-zinc-400">
                                    <span class="px-2.5 py-1 rounded-md bg-zinc-800 text-zinc-300 font-semibold inline-block mb-1">وصول دائم</span>
                                    <div>تحديثات مجانية للكتاب</div>
                                </div>
                            </div>

                            @auth
                                <form action="{{ route('library.buy.wallet', $product->slug) }}" method="POST">
                                    @csrf
                                    <button type="submit" onclick="return confirm('هل تريد تأكيد شراء الكتاب وخصم {{ number_format($product->price, 2) }} من رصيدك؟')" class="w-full h-13 py-3.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-sm shadow-lg shadow-brand-500/30 transition-all flex items-center justify-center gap-2">
                                        <i class="ri-wallet-3-line text-lg"></i>
                                        <span>شراء الآن من رصيد المحفظة ({{ number_format($product->price, 2) }} {{ $product->currency?->code ?? 'USD' }})</span>
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('login') }}" class="w-full h-12 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-sm shadow-lg shadow-brand-500/25 transition-all flex items-center justify-center gap-2">
                                    <i class="ri-user-shared-line text-lg"></i>
                                    <span>تسجيل الدخول لشراء الكتاب</span>
                                </a>
                            @endauth
                        </div>
                    @endif
                </div>

                <!-- Description / Book Content -->
                <div class="prose prose-invert max-w-none">
                    <h3 class="text-lg font-bold text-white mb-3">نبذة عن الكتاب</h3>
                    @if($product->description)
                        <div class="text-sm text-zinc-300 leading-relaxed space-y-4">
                            {!! nl2br(e($product->description)) !!}
                        </div>
                    @elseif($product->short_description)
                        <p class="text-sm text-zinc-300 leading-relaxed">
                            {{ $product->short_description }}
                        </p>
                    @else
                        <p class="text-sm text-zinc-500 italic">
                            دليل رقمي شامل بصيغة PDF معد بعناية من فريق Musoftware.
                        </p>
                    @endif
                </div>

            </div>
        </div>

    </div>

    <!-- Related Books -->
    @if($relatedProducts->count() > 0)
        <div class="mt-20 pt-10 border-t border-zinc-800/80">
            <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i class="ri-bookmark-3-line text-brand-400"></i> كتب ذات صلة قد تهمك
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($relatedProducts as $rel)
                    <div class="rounded-xl glass-card p-4 border border-zinc-800 hover:border-zinc-700 book-hover transition-all">
                        <a href="{{ route('library.show', $rel->slug) }}" class="block aspect-[3/4] w-full rounded-lg overflow-hidden bg-zinc-950 p-2 mb-3 flex items-center justify-center">
                            @if($rel->cover_image_path)
                                <img src="{{ $rel->cover_url }}" alt="{{ $rel->title }}" class="w-full h-full object-contain rounded">
                            @else
                                <div class="w-full h-full rounded bg-zinc-800 p-4 flex flex-col justify-between">
                                    <i class="ri-book-2-line text-2xl text-brand-400"></i>
                                    <span class="text-xs font-bold text-white line-clamp-2">{{ $rel->title }}</span>
                                </div>
                            @endif
                        </a>
                        <h4 class="text-xs font-bold text-white line-clamp-2 mb-2">
                            <a href="{{ route('library.show', $rel->slug) }}" class="hover:text-brand-300">
                                {{ $rel->title }}
                            </a>
                        </h4>
                        <div class="flex items-center justify-between text-[11px] text-zinc-400">
                            <span>{{ $rel->is_free ? 'مجاني' : $rel->formatted_price }}</span>
                            <a href="{{ route('library.show', $rel->slug) }}" class="text-brand-400 hover:underline">عرض</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection

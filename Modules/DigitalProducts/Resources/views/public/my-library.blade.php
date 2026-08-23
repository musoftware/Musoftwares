@extends('layouts.public')

@section('content')
<div class="max-w-[1280px] mx-auto px-6 sm:px-10 py-8 sm:py-12 space-y-8">

    <!-- Apple Page Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-6 border-b border-black/5">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#f5f5f7] border border-black/5 text-[#0071e3] text-xs font-semibold uppercase tracking-wider mb-2">
                <i class="ri-book-open-line"></i>
                <span>{{ app()->getLocale() === 'ar' ? 'الوصول الدائم مدى الحياة' : 'Lifetime Library Access' }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-semibold text-[#1d1d1f] tracking-[-0.02em]">
                {{ app()->getLocale() === 'ar' ? 'مكتبتي الرقمية' : 'My Digital Library' }}
            </h1>
            <p class="text-xs sm:text-sm text-[#86868b] mt-1">
                {{ app()->getLocale() === 'ar' ? 'جميع الكتب والأدلة الرقمية التي تمتلكها جاهزة للتحميل والقراءة في أي وقت.' : 'All your owned books and engineering guides ready for direct download.' }}
            </p>
        </div>
        <a href="{{ route('library.index') }}" class="px-4 py-2 rounded-full bg-[#f5f5f7] hover:bg-[#e8e8ed] text-[#1d1d1f] border border-black/5 text-xs font-medium transition-all flex items-center gap-1.5 shadow-2xs">
            <i class="ri-add-line"></i>
            <span>{{ app()->getLocale() === 'ar' ? 'تصفح المزيد من الكتب' : 'Browse Catalog' }}</span>
        </a>
    </div>

    <!-- User Books Shelf Grid -->
    @if($purchasedProducts->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach($purchasedProducts as $book)
                @php
                    $coverUrl = $book->cover_image_path ? $book->cover_url : ($book->cover_image ? asset($book->cover_image) : asset('images/apple/web-mobile-suite.jpg'));
                @endphp
                <article class="apple-bento-card p-4 flex flex-col justify-between group">
                    <div>
                        <!-- Cover -->
                        <div class="aspect-[3/4] w-full rounded-[14px] overflow-hidden bg-[#f5f5f7] mb-3.5 border border-black/5">
                            <img src="{{ $coverUrl }}" alt="{{ $book->title }}" class="w-full h-full object-cover">
                        </div>

                        <span class="text-[10px] font-semibold text-[#0071e3] uppercase tracking-wider block mb-1">
                            {{ $book->category?->name ?? 'E-Book' }}
                        </span>
                        <h3 class="text-xs sm:text-sm font-semibold text-[#1d1d1f] line-clamp-2 mb-1.5 leading-snug">
                            {{ $book->title }}
                        </h3>
                        <p class="text-[11px] text-[#86868b] mb-4 flex items-center gap-2">
                            <span>{{ $book->page_count ?? '—' }} {{ app()->getLocale() === 'ar' ? 'صفحة' : 'pages' }}</span>
                            <span>•</span>
                            <span>{{ $book->formatted_file_size }}</span>
                        </p>
                    </div>

                    <!-- Direct Download CTA -->
                    <a href="{{ route('library.my_library.download', $book->slug) }}" class="w-full h-10 rounded-full bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-medium transition-all flex items-center justify-center gap-1.5 shadow-xs">
                        <i class="ri-download-2-line text-sm"></i>
                        <span>{{ app()->getLocale() === 'ar' ? 'تحميل الكتاب PDF' : 'Download PDF' }}</span>
                    </a>
                </article>
            @endforeach
        </div>
    @else
        <div class="text-center py-16 rounded-[22px] bg-[#f5f5f7] border border-black/5 p-8">
            <div class="w-12 h-12 rounded-full bg-white text-[#86868b] flex items-center justify-center mx-auto text-xl mb-3 shadow-xs">
                <i class="ri-book-open-line"></i>
            </div>
            <h3 class="text-base font-semibold text-[#1d1d1f] mb-1">
                {{ app()->getLocale() === 'ar' ? 'مكتبتك فارغة حالياً' : 'Your library is empty' }}
            </h3>
            <p class="text-xs text-[#86868b] max-w-sm mx-auto mb-5 leading-relaxed">
                {{ app()->getLocale() === 'ar' ? 'لم تقم بتنزيل أو شراء أي كتب بعد. تصفح المعرض واختر من بين مئات الكتب المجانية والمدفوعة.' : 'You have not added any books yet. Browse the catalog to download free guides and playbooks.' }}
            </p>
            <a href="{{ route('library.index') }}" class="inline-flex items-center gap-1 px-4 py-2 rounded-full bg-[#0071e3] text-white text-xs font-medium hover:bg-[#0077ed] transition-all shadow-xs">
                <span>{{ app()->getLocale() === 'ar' ? 'استكشف معرض الكتب' : 'Browse Catalog' }}</span>
            </a>
        </div>
    @endif

</div>
@endsection

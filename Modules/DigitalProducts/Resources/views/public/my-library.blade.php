@extends('digitalproducts::layouts.library-master')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <!-- Page Header -->
    <div class="mb-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-6 border-b border-zinc-800">
        <div>
            <span class="text-xs font-semibold text-brand-400 uppercase tracking-wider block mb-1">الوصول الدائم مدى الحياة</span>
            <h1 class="text-2xl sm:text-4xl font-extrabold text-white">مكتبتي الرقمية</h1>
            <p class="text-sm text-zinc-400 mt-1">جميع الكتب والمنتجات الرقمية التي تمتلكها جاهزة للتحميل والقراءة في أي وقت.</p>
        </div>
        <a href="{{ route('library.index') }}" class="px-4 py-2.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-white text-xs font-semibold transition-colors flex items-center gap-2">
            <i class="ri-add-line"></i> تصفح المزيد من الكتب
        </a>
    </div>

    <!-- User Books Shelf Grid -->
    @if($purchasedProducts->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($purchasedProducts as $book)
                <div class="rounded-2xl glass-card border border-zinc-800 p-5 flex flex-col justify-between book-hover transition-all">
                    <div>
                        <!-- Cover -->
                        <div class="aspect-[3/4] w-full rounded-xl overflow-hidden bg-zinc-950 p-3 mb-4 flex items-center justify-center border border-zinc-800">
                            @if($book->cover_image_path)
                                <img src="{{ $book->cover_url }}" alt="{{ $book->title }}" class="w-full h-full object-contain rounded-lg">
                            @else
                                <div class="w-full h-full rounded-lg bg-zinc-800 p-4 flex flex-col justify-between">
                                    <i class="ri-book-2-line text-3xl text-brand-400"></i>
                                    <span class="text-xs font-bold text-white">{{ $book->title }}</span>
                                </div>
                            @endif
                        </div>

                        <span class="text-[10px] font-semibold text-brand-400 block mb-1">
                            {{ $book->category?->name ?? 'PDF Ebook' }}
                        </span>
                        <h3 class="text-sm font-bold text-white line-clamp-2 mb-2">
                            {{ $book->title }}
                        </h3>
                        <p class="text-xs text-zinc-400 mb-4 flex items-center gap-2">
                            <span><i class="ri-pages-line"></i> {{ $book->page_count ?? '—' }} صفحة</span>
                            <span>•</span>
                            <span>{{ $book->formatted_file_size }}</span>
                        </p>
                    </div>

                    <!-- Direct Download CTA -->
                    <a href="{{ route('library.my_library.download', $book->slug) }}" class="w-full py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition-colors flex items-center justify-center gap-2 shadow-md shadow-emerald-600/20">
                        <i class="ri-download-2-line"></i>
                        <span>تحميل الكتاب PDF</span>
                    </a>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-20 rounded-3xl glass-card border border-zinc-800 p-8">
            <div class="w-16 h-16 rounded-2xl bg-zinc-800 flex items-center justify-center text-zinc-500 mx-auto mb-4 text-3xl">
                <i class="ri-book-shelf-line"></i>
            </div>
            <h3 class="text-lg font-bold text-white mb-2">مكتبتك فارغة حالياً</h3>
            <p class="text-sm text-zinc-400 max-w-sm mx-auto mb-6">
                لم تقم بشراء أي كتب بعد. تصفح المعرض واختر من بين مئات الكتب المجانية والمدفوعة.
            </p>
            <a href="{{ route('library.index') }}" class="px-6 py-3 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold shadow-lg shadow-brand-500/25 transition-all inline-block">
                استكشف معرض الكتب
            </a>
        </div>
    @endif

</div>
@endsection

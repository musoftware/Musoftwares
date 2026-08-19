@extends('digitalproducts::layouts.library-master')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <!-- Page Header -->
    <div class="mb-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-6 border-b border-slate-200 dark:border-zinc-800 transition-colors">
        <div>
            <span class="text-xs font-semibold text-brand-600 dark:text-brand-400 uppercase tracking-wider block mb-1">الوصول الدائم مدى الحياة</span>
            <h1 class="text-2xl sm:text-4xl font-extrabold text-slate-900 dark:text-white">مكتبتي الرقمية</h1>
            <p class="text-sm text-slate-600 dark:text-zinc-400 mt-1">جميع الكتب والمنتجات الرقمية التي تمتلكها جاهزة للتحميل والقراءة في أي وقت.</p>
        </div>
        <a href="{{ route('library.index') }}" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-white dark:border-zinc-700/60 text-xs font-semibold transition-colors flex items-center gap-2 shadow-sm">
            <i class="ri-add-line"></i> تصفح المزيد من الكتب
        </a>
    </div>

    <!-- User Books Shelf Grid -->
    @if($purchasedProducts->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-7">
            @foreach($purchasedProducts as $book)
                <div class="rounded-[2rem] glass-card border border-slate-200/80 dark:border-zinc-800/80 p-6 flex flex-col justify-between hover:border-peach-400 dark:hover:border-peach-500 transition-all duration-300 shadow-sm hover:shadow-xl">
                    <div>
                        <!-- 3D Cover -->
                        <div class="aspect-[3/4] w-full rounded-2xl overflow-hidden bg-slate-100 dark:bg-zinc-950 p-4 mb-4 flex items-center justify-center">
                            @if($book->cover_image_path)
                                <img src="{{ $book->cover_url }}" alt="{{ $book->title }}" class="w-full h-full object-cover rounded-xl book-shadow-3d">
                            @else
                                <div class="w-full h-full rounded-xl bg-slate-900 p-4 flex flex-col justify-between text-white book-shadow-3d">
                                    <span class="text-[10px] text-peach-400 font-mono">{{ $book->category?->name ?? 'PDF' }}</span>
                                    <h4 class="font-bold text-xs line-clamp-3">{{ $book->title }}</h4>
                                    <span class="text-[10px] text-zinc-400">{{ $book->author_name ?? 'Musoftware' }}</span>
                                </div>
                            @endif
                        </div>

                        <span class="text-[10px] font-bold text-peach-600 dark:text-peach-400 uppercase tracking-wider block mb-1">
                            {{ $book->category?->name ?? 'PDF Ebook' }}
                        </span>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white font-display line-clamp-2 mb-2 leading-snug">
                            {{ $book->title }}
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-zinc-400 mb-4 flex items-center gap-2">
                            <span><i class="ri-pages-line text-peach-500"></i> {{ $book->page_count ?? '—' }} صفحة</span>
                            <span>•</span>
                            <span>{{ $book->formatted_file_size }}</span>
                        </p>
                    </div>

                    <!-- Direct Download CTA -->
                    <a href="{{ route('library.my_library.download', $book->slug) }}" class="w-full py-3 rounded-full bg-peach-500 hover:bg-peach-600 text-white text-xs font-bold transition-all flex items-center justify-center gap-2 shadow-md shadow-peach-500/20 hover:scale-[1.02]">
                        <i class="ri-download-2-line"></i>
                        <span>تحميل الكتاب PDF</span>
                    </a>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-20 rounded-3xl glass-card border border-slate-200 dark:border-zinc-800 p-8">
            <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-zinc-800 flex items-center justify-center text-slate-400 dark:text-zinc-500 mx-auto mb-4 text-3xl">
                <i class="ri-book-shelf-line"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">مكتبتك فارغة حالياً</h3>
            <p class="text-sm text-slate-600 dark:text-zinc-400 max-w-sm mx-auto mb-6">
                لم تقم بشراء أي كتب بعد. تصفح المعرض واختر من بين مئات الكتب المجانية والمدفوعة.
            </p>
            <a href="{{ route('library.index') }}" class="px-6 py-3 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold shadow-lg shadow-brand-500/25 transition-all inline-block">
                استكشف معرض الكتب
            </a>
        </div>
    @endif

</div>
@endsection

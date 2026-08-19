@extends('marketplace::layouts.marketplace-master')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <!-- Page Header -->
    <div class="mb-8 pb-6 border-b border-slate-200 dark:border-zinc-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-colors">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-brand-600 dark:text-brand-400 uppercase tracking-wider mb-1">
                <i class="ri-heart-fill text-rose-500"></i>
                <span>{{ app()->getLocale() === 'ar' ? 'قائمتي المحفوظة' : 'My Saved Wishlist' }}</span>
            </div>
            <h1 class="text-2xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                {{ app()->getLocale() === 'ar' ? 'الخدمات المفضلة' : 'Favorite Services' }}
            </h1>
            <p class="text-sm text-slate-600 dark:text-zinc-400 mt-1">
                {{ app()->getLocale() === 'ar' ? 'الخدمات البرمجية والحلول التي قمت بحفظها للرجوع إليها والطلب لاحقاً.' : 'Bookmarked services for easy access and later purchase.' }}
            </p>
        </div>

        <a href="{{ route('marketplace.services.index') }}" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-white dark:border-zinc-700/60 text-xs font-semibold transition-all flex items-center gap-2 shadow-sm">
            <i class="ri-search-2-line"></i>
            <span>{{ app()->getLocale() === 'ar' ? 'تصفح المزيد من الخدمات' : 'Browse Catalog' }}</span>
        </a>
    </div>

    <!-- Favorites Grid -->
    @if($favorites->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($favorites as $fav)
                @php
                    $service = $fav->favoritable;
                @endphp
                @if($service)
                    <div class="group rounded-2xl glass-card overflow-hidden border border-slate-200 dark:border-zinc-800 hover:border-brand-300 dark:hover:border-zinc-700 flex flex-col justify-between transition-all duration-300 shadow-sm">
                        <!-- Cover Image Container -->
                        <div class="relative aspect-video w-full overflow-hidden bg-slate-100 dark:bg-zinc-950">
                            @if($service->cover_image)
                                <img src="{{ $service->cover_image }}" alt="{{ $service->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-slate-200 dark:bg-zinc-800 text-slate-400 dark:text-zinc-500 text-3xl">
                                    <i class="ri-service-line"></i>
                                </div>
                            @endif

                            <!-- Remove from Favorites Button -->
                            <form action="{{ route('marketplace.favorites.toggle', $service->id) }}" method="POST" class="absolute top-3 right-3">
                                @csrf
                                <button type="submit" title="{{ app()->getLocale() === 'ar' ? 'إزالة من المفضلة' : 'Remove from saved' }}" class="w-8 h-8 rounded-full bg-white/90 dark:bg-zinc-900/90 text-rose-500 hover:bg-rose-500 hover:text-white transition-all flex items-center justify-center shadow-md">
                                    <i class="ri-delete-bin-line text-sm"></i>
                                </button>
                            </form>
                        </div>

                        <!-- Card Body -->
                        <div class="p-5 flex-1 flex flex-col justify-between">
                            <div>
                                <div class="text-xs text-slate-500 dark:text-zinc-400 mb-1.5 flex items-center justify-between">
                                    <span>{{ $service->category?->name ?? 'Service' }}</span>
                                    <span>{{ app()->getLocale() === 'ar' ? 'البائع:' : 'Seller:' }} {{ $service->seller?->name ?? 'Creator' }}</span>
                                </div>

                                <h3 class="text-base font-bold text-slate-900 dark:text-white group-hover:text-brand-600 dark:group-hover:text-brand-300 transition-colors line-clamp-2 leading-snug mb-3">
                                    <a href="{{ route('marketplace.services.show', ['id' => $service->id, 'slug' => $service->slug]) }}">
                                        {{ $service->title }}
                                    </a>
                                </h3>
                            </div>

                            <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-zinc-800/80 mt-auto">
                                <div class="flex items-center gap-1 text-xs text-amber-500 font-bold">
                                    <i class="ri-star-fill"></i>
                                    <span>{{ number_format($service->avg_rating ?? 5.0, 1) }}</span>
                                    <span class="text-slate-400 dark:text-zinc-500 font-normal">({{ $service->review_count ?? 0 }})</span>
                                </div>

                                <a href="{{ route('marketplace.services.show', ['id' => $service->id, 'slug' => $service->slug]) }}" class="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-brand-600 text-slate-800 hover:text-white dark:bg-zinc-800 dark:hover:bg-brand-600 dark:text-zinc-200 text-xs font-semibold transition-colors flex items-center gap-1">
                                    <span>{{ app()->getLocale() === 'ar' ? 'عرض التفاصيل' : 'View' }}</span>
                                    <i class="ri-arrow-left-s-line {{ app()->getLocale() === 'ar' ? '' : 'rotate-180' }}"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <!-- Pagination -->
        @if($favorites->hasPages())
            <div class="mt-10">
                {{ $favorites->links() }}
            </div>
        @endif
    @else
        <!-- Empty State -->
        <div class="text-center py-20 rounded-3xl glass-card border border-slate-200 dark:border-zinc-800 p-8 shadow-sm">
            <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-zinc-800 flex items-center justify-center text-rose-500 mx-auto mb-4 text-3xl">
                <i class="ri-heart-line"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">
                {{ app()->getLocale() === 'ar' ? 'قائمتك المفضلة فارغة' : 'Your wishlist is empty' }}
            </h3>
            <p class="text-sm text-slate-600 dark:text-zinc-400 max-w-sm mx-auto mb-6">
                {{ app()->getLocale() === 'ar' ? 'اضغط على أيقونة القلب في أي خدمة بالسوق لحفظها في هذه الصفحة والرجوع إليها بسهولة.' : 'Click the heart icon on any service listing to bookmark it here for later.' }}
            </p>
            <a href="{{ route('marketplace.services.index') }}" class="px-6 py-3 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold shadow-lg shadow-brand-500/25 transition-all inline-block">
                {{ app()->getLocale() === 'ar' ? 'تصفح خدمات السوق' : 'Browse Services' }}
            </a>
        </div>
    @endif

</div>
@endsection

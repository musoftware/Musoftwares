@extends('marketplace::layouts.marketplace-master')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Page Header -->
    <div class="mb-8 pb-6 border-b border-slate-200/80 dark:border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-brand-600 dark:text-brand-400 uppercase tracking-wider mb-1">
                <i class="ri-heart-fill text-rose-500"></i>
                <span>{{ app()->getLocale() === 'ar' ? 'قائمتي المحفوظة' : 'My Saved Wishlist' }}</span>
            </div>
            <h1 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">
                {{ app()->getLocale() === 'ar' ? 'الخدمات المفضلة' : 'Saved Services' }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-zinc-400 mt-1">
                {{ app()->getLocale() === 'ar' ? 'الخدمات البرمجية والحلول التي قمت بحفظها للرجوع إليها والطلب لاحقاً.' : 'Bookmarked services for easy access and later purchase.' }}
            </p>
        </div>

        <a href="{{ route('marketplace.services.index') }}" class="px-5 py-2.5 rounded-full bg-slate-900 hover:bg-slate-800 text-white dark:bg-white dark:hover:bg-zinc-200 dark:text-slate-950 text-xs font-bold transition-all flex items-center gap-2 shadow-sm self-start sm:self-auto">
            <i class="ri-search-2-line"></i>
            <span>{{ app()->getLocale() === 'ar' ? 'استكشف المزيد من الخدمات' : 'Explore Marketplace' }}</span>
        </a>
    </div>

    <!-- Favorites Grid -->
    @if($favorites->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            @foreach($favorites as $fav)
                @php
                    $service = $fav->favoritable;
                @endphp
                @if($service)
                    @php
                        $serviceUrl = route('marketplace.services.show', ['id' => $service->id, 'slug' => $service->slug ?? 'service']);
                        $minPrice = $service->packages->min('price') ?? $service->price ?? 5;
                        $firstPackage = $service->packages->first();
                        $currencyCode = $firstPackage && $firstPackage->currency ? ($firstPackage->currency->symbol ?? $firstPackage->currency->code ?? '$') : '$';
                        $coverImage = $service->cover_image ? (Str::startsWith($service->cover_image, ['http://', 'https://', '/']) ? $service->cover_image : '/uploads/'.ltrim($service->cover_image, '/')) : null;
                        $sellerName = $service->seller->name ?? 'Specialist';
                        $sellerInitials = strtoupper(substr($sellerName, 0, 2));
                        $deliveryDays = $firstPackage->delivery_days ?? 3;
                    @endphp

                    <article class="explore-card p-6 flex flex-col justify-between group relative">
                        
                        <div>
                            <!-- Top Row: Seller Avatar + Name/Role + Rating Badge + Remove Bookmark -->
                            <div class="flex items-start justify-between gap-3 mb-5">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-brand-600/20 to-indigo-500/20 dark:from-brand-500/30 dark:to-indigo-500/30 border border-brand-500/30 text-brand-600 dark:text-brand-300 flex items-center justify-center font-bold text-sm flex-shrink-0">
                                        {{ $sellerInitials }}
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="font-bold text-sm text-slate-900 dark:text-white truncate flex items-center gap-1 leading-tight">
                                            <span>{{ $sellerName }}</span>
                                            <i class="ri-verified-badge-fill text-brand-500 dark:text-brand-400 text-xs"></i>
                                        </h4>
                                        <p class="text-xs text-slate-500 dark:text-zinc-400 truncate mt-0.5">
                                            {{ $service->category->name ?? 'Software Engineer' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <form action="{{ route('marketplace.favorites.toggle', $service->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button 
                                            type="submit" 
                                            class="w-8 h-8 rounded-full bg-rose-50 dark:bg-rose-950/30 text-rose-500 flex items-center justify-center hover:bg-rose-500 hover:text-white transition-colors shadow-sm"
                                            title="{{ app()->getLocale() === 'ar' ? 'إزالة من المفضلة' : 'Remove' }}"
                                        >
                                            <i class="ri-delete-bin-line text-sm"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Service Title & Price -->
                            <div class="flex items-start justify-between gap-3 mb-4">
                                <h3 class="font-extrabold text-base sm:text-lg text-slate-900 dark:text-white group-hover:text-brand-600 dark:group-hover:text-brand-300 transition-colors leading-snug line-clamp-2">
                                    <a href="{{ $serviceUrl }}">
                                        {{ $service->title }}
                                    </a>
                                </h3>
                                <div class="text-end flex-shrink-0">
                                    <span class="text-[10px] font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-wider block">
                                        {{ app()->getLocale() === 'ar' ? 'من' : 'from' }}
                                    </span>
                                    <span class="text-base sm:text-lg font-black text-slate-900 dark:text-white">
                                        {{ $currencyCode }}{{ number_format($minPrice, 0) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Metadata Badges Row -->
                            <div class="flex items-center gap-2 flex-wrap mb-4">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border border-slate-200 dark:border-white/10 text-[11px] font-semibold text-slate-600 dark:text-zinc-300 bg-slate-50/50 dark:bg-dark-800/50">
                                    <i class="ri-briefcase-4-line text-xs text-brand-500"></i>
                                    <span>{{ $deliveryDays }} {{ app()->getLocale() === 'ar' ? 'أيام تسليم' : 'days delivery' }}</span>
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border border-slate-200 dark:border-white/10 text-[11px] font-semibold text-slate-600 dark:text-zinc-300 bg-slate-50/50 dark:bg-dark-800/50">
                                    <i class="ri-shield-check-line text-xs text-emerald-500"></i>
                                    <span>{{ app()->getLocale() === 'ar' ? 'ضمان Escrow' : 'Project work' }}</span>
                                </span>
                            </div>

                            <p class="text-xs text-slate-600 dark:text-zinc-400 line-clamp-2 leading-relaxed mb-4">
                                {{ $service->tagline ?? Str::limit(strip_tags($service->description ?? ''), 100) }}
                            </p>
                        </div>

                        <!-- Action Link -->
                        <div class="pt-4 border-t border-slate-100 dark:border-white/5 flex items-center justify-between mt-2">
                            <a href="{{ $serviceUrl }}" class="w-full py-2.5 rounded-xl bg-slate-100 hover:bg-slate-900 hover:text-white dark:bg-dark-800 dark:hover:bg-white dark:hover:text-slate-950 text-slate-800 dark:text-zinc-200 text-xs font-bold transition-all text-center">
                                <span>{{ app()->getLocale() === 'ar' ? 'عرض تفاصيل الخدمة والطلب' : 'View Service Details' }}</span>
                            </a>
                        </div>

                    </article>
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
        <div class="text-center py-20 rounded-3xl bg-white dark:bg-dark-850 border border-slate-200 dark:border-white/10 p-8 shadow-sm">
            <div class="w-16 h-16 rounded-2xl bg-rose-50 dark:bg-rose-950/40 text-rose-500 flex items-center justify-center mx-auto text-3xl mb-4">
                <i class="ri-heart-line"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">
                {{ app()->getLocale() === 'ar' ? 'لا توجد خدمات في المفضلة حالياً' : 'Your saved wishlist is empty' }}
            </h3>
            <p class="text-xs text-slate-500 dark:text-zinc-400 max-w-md mx-auto mb-6 leading-relaxed">
                {{ app()->getLocale() === 'ar' ? 'تصفح سوق الخدمات واضغط على علامة الحفظ لأي خدمة تعجبك لتصل إليها بسهولة لاحقاً.' : 'Browse the marketplace and bookmark software services you like for later review and purchase.' }}
            </p>
            <a href="{{ route('marketplace.services.index') }}" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-full bg-slate-900 hover:bg-slate-800 text-white dark:bg-white dark:hover:bg-zinc-200 dark:text-slate-950 text-xs font-bold transition-all shadow-md">
                <i class="ri-search-2-line"></i>
                <span>{{ app()->getLocale() === 'ar' ? 'تصفح خدمات السوق' : 'Explore Marketplace' }}</span>
            </a>
        </div>
    @endif

</div>
@endsection

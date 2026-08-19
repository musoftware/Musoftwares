@extends('marketplace::layouts.marketplace-master')

@section('content')
@php
    $currentMode = request('mode', 'client');
    $isBuyer = $currentMode === 'client';
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <!-- Top Dashboard Header & Perspective Switcher -->
    <div class="mb-8 rounded-3xl glass-card border border-slate-200 dark:border-zinc-800 p-6 sm:p-8 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider {{ $isBuyer ? 'bg-brand-100 text-brand-800 dark:bg-brand-950/60 dark:text-brand-300 border border-brand-300 dark:border-brand-800/60' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800/60' }}">
                        {{ $isBuyer ? (app()->getLocale() === 'ar' ? 'بوابة المشتري' : 'Buyer Workspace') : (app()->getLocale() === 'ar' ? 'بوابة البائع والفريلانسر' : 'Seller Workspace') }}
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    {{ app()->getLocale() === 'ar' ? 'لوحة تحكم سوق الخدمات' : 'Marketplace Command Center' }}
                </h1>
                <p class="text-sm text-slate-600 dark:text-zinc-400 mt-1">
                    {{ $isBuyer 
                        ? (app()->getLocale() === 'ar' ? 'إدارة مشترياتك، فحص تسليمات المطورين، ومتابعة رصيد الضمان المالي.' : 'Track your purchases, review deliverables, and manage escrow protected orders.')
                        : (app()->getLocale() === 'ar' ? 'إدارة خدماتك المعروضة، طابور طلبات العملاء، تسليم المشاريع، وصافي أرباحك.' : 'Manage your listed services, active client orders, deliver work, and track revenue.')
                    }}
                </p>
            </div>

            <!-- Mode Switcher Buttons -->
            <div class="inline-flex rounded-2xl bg-slate-100 dark:bg-zinc-800 p-1.5 border border-slate-200 dark:border-zinc-700 shadow-inner flex-shrink-0">
                <a 
                    href="{{ route('marketplace.home', ['mode' => 'client']) }}" 
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 {{ $isBuyer ? 'bg-white dark:bg-zinc-900 text-brand-600 dark:text-brand-400 shadow-sm' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white' }}"
                >
                    <i class="ri-shopping-bag-3-line"></i>
                    <span>{{ app()->getLocale() === 'ar' ? 'وضع المشتري' : 'Buyer Mode' }}</span>
                </a>
                <a 
                    href="{{ route('marketplace.home', ['mode' => 'seller']) }}" 
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 {{ !$isBuyer ? 'bg-white dark:bg-zinc-900 text-emerald-600 dark:text-emerald-400 shadow-sm' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white' }}"
                >
                    <i class="ri-store-2-line"></i>
                    <span>{{ app()->getLocale() === 'ar' ? 'وضع البائع' : 'Seller Mode' }}</span>
                </a>
            </div>
        </div>
    </div>

    @if($isBuyer)
        <!-- ─── BUYER PERSPECTIVE ─── -->

        <!-- 4 Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <div class="rounded-2xl glass-card border border-slate-200 dark:border-zinc-800 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider">{{ app()->getLocale() === 'ar' ? 'محفوظ بالضمان' : 'Locked in Escrow' }}</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-base">
                        <i class="ri-shield-check-line"></i>
                    </div>
                </div>
                <div class="text-2xl font-black text-slate-900 dark:text-white font-mono">
                    ${{ number_format($buyerStats['lockedEscrow'] ?? 0, 2) }}
                </div>
                <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-1">{{ app()->getLocale() === 'ar' ? 'مبالغ آمنة حتى توافق على التسليم' : 'Protected until you approve' }}</p>
            </div>

            <div class="rounded-2xl glass-card border border-slate-200 dark:border-zinc-800 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider">{{ app()->getLocale() === 'ar' ? 'الطلبات النشطة' : 'Active Orders' }}</span>
                    <div class="w-8 h-8 rounded-lg bg-brand-500/10 text-brand-600 dark:text-brand-400 flex items-center justify-center text-base">
                        <i class="ri-time-line"></i>
                    </div>
                </div>
                <div class="text-2xl font-black text-slate-900 dark:text-white font-mono">
                    {{ $buyerStats['activeOrders'] ?? 0 }}
                </div>
                <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-1">{{ app()->getLocale() === 'ar' ? 'طلبات قيد التنفيذ والمراجعة' : 'Currently in progress' }}</p>
            </div>

            <div class="rounded-2xl glass-card border border-slate-200 dark:border-zinc-800 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider">{{ app()->getLocale() === 'ar' ? 'إجمالي المشتريات' : 'Total Spent' }}</span>
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-base">
                        <i class="ri-wallet-3-line"></i>
                    </div>
                </div>
                <div class="text-2xl font-black text-slate-900 dark:text-white font-mono">
                    ${{ number_format($buyerStats['totalSpent'] ?? 0, 2) }}
                </div>
                <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-1">{{ app()->getLocale() === 'ar' ? 'على جميع الخدمات المكتملة' : 'On completed services' }}</p>
            </div>

            <div class="rounded-2xl glass-card border border-slate-200 dark:border-zinc-800 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider">{{ app()->getLocale() === 'ar' ? 'الطلبات المكتملة' : 'Completed' }}</span>
                    <div class="w-8 h-8 rounded-lg bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 flex items-center justify-center text-base">
                        <i class="ri-checkbox-circle-line"></i>
                    </div>
                </div>
                <div class="text-2xl font-black text-slate-900 dark:text-white font-mono">
                    {{ $buyerStats['completedCount'] ?? 0 }}
                </div>
                <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-1">{{ app()->getLocale() === 'ar' ? 'مشاريع تم تسليمها بنجاح' : 'Successfully received' }}</p>
            </div>
        </div>

        <!-- Needs Action Alerts (Purchases) -->
        @if(count($needsActionPurchases) > 0)
            <div class="mb-10 rounded-3xl bg-indigo-50/80 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-800 p-6 shadow-sm">
                <div class="flex items-center gap-2 mb-4 text-indigo-900 dark:text-indigo-300 font-bold text-sm">
                    <i class="ri-notification-3-fill text-indigo-600 dark:text-indigo-400 text-lg"></i>
                    <span>{{ app()->getLocale() === 'ar' ? 'تسليمات بانتظار مراجعتك وموافقتك:' : 'Deliverables Waiting for Your Review:' }}</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($needsActionPurchases as $actionOrder)
                        <div class="p-4 rounded-2xl bg-white dark:bg-zinc-900 border border-indigo-100 dark:border-zinc-800 flex items-center justify-between gap-4">
                            <div>
                                <h4 class="font-bold text-sm text-slate-900 dark:text-white line-clamp-1">{{ $actionOrder['title'] }}</h4>
                                <p class="text-xs text-slate-500 dark:text-zinc-400 mt-0.5">{{ $actionOrder['actionNeededText'] }} • {{ $actionOrder['sellerName'] }}</p>
                            </div>
                            <a href="{{ route('marketplace.orders.show', $actionOrder['id']) }}" class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold flex-shrink-0 transition-colors">
                                {{ app()->getLocale() === 'ar' ? 'فحص التسليم' : 'Review' }}
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Active Purchases Section -->
        <div class="rounded-3xl glass-card border border-slate-200 dark:border-zinc-800 p-6 sm:p-8 mb-10 shadow-sm">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-200 dark:border-zinc-800">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="ri-shopping-cart-2-line text-brand-600 dark:text-brand-400"></i>
                    <span>{{ app()->getLocale() === 'ar' ? 'مشترياتي النشطة قيد التنفيذ' : 'Active In-Progress Orders' }}</span>
                </h3>
                <a href="{{ route('marketplace.orders.index', ['tab' => 'purchases']) }}" class="text-xs font-bold text-brand-600 dark:text-brand-400 hover:underline">
                    {{ app()->getLocale() === 'ar' ? 'عرض الكل' : 'View All' }} →
                </a>
            </div>

            @if(count($activePurchases) > 0)
                <div class="space-y-3">
                    @foreach($activePurchases as $ord)
                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-zinc-900/60 border border-slate-200 dark:border-zinc-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h4 class="font-bold text-sm text-slate-900 dark:text-white">{{ $ord['title'] }}</h4>
                                <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-zinc-400 mt-1">
                                    <span>{{ app()->getLocale() === 'ar' ? 'البائع:' : 'Seller:' }} {{ $ord['sellerName'] }}</span>
                                    <span>•</span>
                                    <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">${{ number_format($ord['amount'], 2) }}</span>
                                    @if(!empty($ord['deliveryDate']))
                                        <span>•</span>
                                        <span>{{ app()->getLocale() === 'ar' ? 'التسليم المتوقع:' : 'Due:' }} {{ $ord['deliveryDate'] }}</span>
                                    @endif
                                </div>
                            </div>
                            <a href="{{ route('marketplace.orders.show', $ord['id']) }}" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-brand-600 text-slate-800 hover:text-white dark:bg-zinc-800 dark:hover:bg-brand-600 dark:text-zinc-200 text-xs font-semibold transition-colors text-center">
                                {{ app()->getLocale() === 'ar' ? 'متابعة الطلب' : 'Track Order' }}
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-10 text-slate-500 dark:text-zinc-400 text-sm">
                    <p class="mb-4">{{ app()->getLocale() === 'ar' ? 'لا توجد لديك طلبات نشطة حالياً.' : 'You have no active orders in progress.' }}</p>
                    <a href="{{ route('marketplace.services.index') }}" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold transition-all inline-block">
                        {{ app()->getLocale() === 'ar' ? 'تصفح خدمات السوق' : 'Explore Services' }}
                    </a>
                </div>
            @endif
        </div>

    @else
        <!-- ─── SELLER PERSPECTIVE ─── -->

        <!-- 4 Seller Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <div class="rounded-2xl glass-card border border-slate-200 dark:border-zinc-800 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider">{{ app()->getLocale() === 'ar' ? 'أرباح معلقة بالضمان' : 'Escrow Revenue' }}</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-base">
                        <i class="ri-lock-2-line"></i>
                    </div>
                </div>
                <div class="text-2xl font-black text-slate-900 dark:text-white font-mono">
                    ${{ number_format($sellerStats['lockedEscrow'] ?? 0, 2) }}
                </div>
                <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-1">{{ app()->getLocale() === 'ar' ? 'تُحول لرصيدك فور قبول التسليم' : 'Released upon client approval' }}</p>
            </div>

            <div class="rounded-2xl glass-card border border-slate-200 dark:border-zinc-800 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider">{{ app()->getLocale() === 'ar' ? 'طابور الطلبات النشطة' : 'Active Queue' }}</span>
                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center text-base">
                        <i class="ri-list-check-3"></i>
                    </div>
                </div>
                <div class="text-2xl font-black text-slate-900 dark:text-white font-mono">
                    {{ $sellerStats['activeOrders'] ?? 0 }}
                </div>
                <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-1">{{ app()->getLocale() === 'ar' ? 'مشاريع مطلوبة قيد التنفيذ' : 'Orders to deliver' }}</p>
            </div>

            <div class="rounded-2xl glass-card border border-slate-200 dark:border-zinc-800 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider">{{ app()->getLocale() === 'ar' ? 'إجمالي المبيعات' : 'Total Sales' }}</span>
                    <div class="w-8 h-8 rounded-lg bg-brand-500/10 text-brand-600 dark:text-brand-400 flex items-center justify-center text-base">
                        <i class="ri-money-dollar-circle-line"></i>
                    </div>
                </div>
                <div class="text-2xl font-black text-slate-900 dark:text-white font-mono">
                    ${{ number_format($sellerStats['totalSales'] ?? 0, 2) }}
                </div>
                <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-1">{{ app()->getLocale() === 'ar' ? 'أرباح المشاريع المسلمة' : 'Earned to date' }}</p>
            </div>

            <div class="rounded-2xl glass-card border border-slate-200 dark:border-zinc-800 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider">{{ app()->getLocale() === 'ar' ? 'نسبة الإنجاز' : 'Completion Rate' }}</span>
                    <div class="w-8 h-8 rounded-lg bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 flex items-center justify-center text-base">
                        <i class="ri-percent-line"></i>
                    </div>
                </div>
                <div class="text-2xl font-black text-slate-900 dark:text-white font-mono">
                    {{ $sellerStats['completionRate'] ?? 100 }}%
                </div>
                <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-1">{{ app()->getLocale() === 'ar' ? 'معدل رضا وتسليم الطلبات' : 'Order satisfaction score' }}</p>
            </div>
        </div>

        <!-- Listed Gigs / Services -->
        <div class="rounded-3xl glass-card border border-slate-200 dark:border-zinc-800 p-6 sm:p-8 mb-10 shadow-sm">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-200 dark:border-zinc-800">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="ri-store-2-line text-emerald-600 dark:text-emerald-400"></i>
                    <span>{{ app()->getLocale() === 'ar' ? 'خدماتي المعروضة بالسوق' : 'My Listed Services' }}</span>
                </h3>
                <a href="{{ route('marketplace.services.create') }}" class="px-3.5 py-1.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold transition-all shadow-md shadow-brand-600/20 flex items-center gap-1">
                    <i class="ri-add-line"></i>
                    <span>{{ app()->getLocale() === 'ar' ? 'إضافة خدمة جديدة' : 'New Service' }}</span>
                </a>
            </div>

            @if(count($listedGigs) > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($listedGigs as $gig)
                        <div class="p-5 rounded-2xl bg-slate-50 dark:bg-zinc-900/60 border border-slate-200 dark:border-zinc-800 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $gig['status'] === 'active' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-200 text-slate-700 dark:bg-zinc-800 dark:text-zinc-400' }}">
                                        {{ $gig['status'] }}
                                    </span>
                                    <span class="font-mono font-bold text-sm text-emerald-600 dark:text-emerald-400">${{ number_format($gig['price'], 2) }}</span>
                                </div>
                                <h4 class="font-bold text-sm text-slate-900 dark:text-white line-clamp-2 mb-3">{{ $gig['title'] }}</h4>
                            </div>

                            <div class="flex items-center justify-between pt-3 border-t border-slate-200 dark:border-zinc-800 text-xs">
                                <a href="{{ route('marketplace.services.show', $gig['id']) }}" class="text-brand-600 dark:text-brand-400 font-semibold hover:underline">
                                    {{ app()->getLocale() === 'ar' ? 'معاينة' : 'Preview' }}
                                </a>
                                <a href="{{ route('marketplace.services.edit', $gig['id']) }}" class="text-slate-600 dark:text-zinc-300 font-semibold hover:text-slate-900 dark:hover:text-white">
                                    {{ app()->getLocale() === 'ar' ? 'تعديل' : 'Edit' }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-10 text-slate-500 dark:text-zinc-400 text-sm">
                    <p class="mb-4">{{ app()->getLocale() === 'ar' ? 'لم تقم بنشر أي خدمات بعد.' : 'You have not published any services yet.' }}</p>
                    <a href="{{ route('marketplace.services.create') }}" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold transition-all inline-block shadow-md">
                        {{ app()->getLocale() === 'ar' ? 'ابدأ الآن وأضف خدمتك الأولى' : 'Publish Your First Service' }}
                    </a>
                </div>
            @endif
        </div>
    @endif

</div>
@endsection

@extends('marketplace::layouts.marketplace-master')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <!-- Page Header & Welcome -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-slate-200 dark:border-zinc-800 transition-colors">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider {{ ($tab ?? 'purchases') === 'sales' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800/60' : 'bg-brand-100 text-brand-800 dark:bg-brand-950/60 dark:text-brand-300 border border-brand-300 dark:border-brand-800/60' }}">
                    {{ ($tab ?? 'purchases') === 'sales' ? (app()->getLocale() === 'ar' ? 'وضع البائع' : 'Seller Mode') : (app()->getLocale() === 'ar' ? 'وضع المشتري' : 'Buyer Mode') }}
                </span>
                <span class="text-xs text-slate-500 dark:text-zinc-400 font-mono">
                    {{ $orders->total() }} {{ app()->getLocale() === 'ar' ? 'طلبات إجمالية' : 'total orders' }}
                </span>
            </div>
            <h1 class="text-2xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                {{ app()->getLocale() === 'ar' ? 'إدارة الطلبات والمشتريات' : 'Orders & Purchases' }}
            </h1>
            <p class="text-sm text-slate-600 dark:text-zinc-400 mt-1">
                {{ app()->getLocale() === 'ar' ? 'متابعة مسار تسليم الخدمات، مراحل العمل، والضمان المالي لكل مرحلة.' : 'Track service milestones, deliverables, escrow status, and direct communication.' }}
            </p>
        </div>

        <div class="flex items-center gap-3">
            @if(($tab ?? 'purchases') === 'sales')
                <a href="{{ route('marketplace.services.create') }}" class="px-4 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold transition-all shadow-md shadow-brand-600/20 flex items-center gap-2">
                    <i class="ri-add-line text-sm"></i>
                    <span>{{ app()->getLocale() === 'ar' ? 'إضافة خدمة جديدة' : 'Add New Service' }}</span>
                </a>
            @else
                <a href="{{ route('marketplace.services.index') }}" class="px-4 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold transition-all shadow-md shadow-brand-600/20 flex items-center gap-2">
                    <i class="ri-shopping-bag-3-line text-sm"></i>
                    <span>{{ app()->getLocale() === 'ar' ? 'تصفح خدمات السوق' : 'Browse Catalog' }}</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Mode Tabs Switcher -->
    <div class="flex items-center gap-4 mb-8 border-b border-slate-200 dark:border-zinc-800">
        <a 
            href="{{ route('marketplace.orders.index', ['tab' => 'purchases']) }}" 
            class="pb-3.5 px-2 text-sm font-bold flex items-center gap-2 transition-all border-b-2 {{ ($tab ?? 'purchases') !== 'sales' ? 'border-brand-600 text-brand-600 dark:text-brand-400' : 'border-transparent text-slate-500 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white' }}"
        >
            <i class="ri-shopping-cart-line text-base"></i>
            <span>{{ app()->getLocale() === 'ar' ? 'مشترياتي (كمشتري)' : 'Purchases (As Buyer)' }}</span>
        </a>
        <a 
            href="{{ route('marketplace.orders.index', ['tab' => 'sales']) }}" 
            class="pb-3.5 px-2 text-sm font-bold flex items-center gap-2 transition-all border-b-2 {{ ($tab ?? 'purchases') === 'sales' ? 'border-brand-600 text-brand-600 dark:text-brand-400' : 'border-transparent text-slate-500 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white' }}"
        >
            <i class="ri-store-2-line text-base"></i>
            <span>{{ app()->getLocale() === 'ar' ? 'مبيعاتي (كبائع)' : 'Sales (As Seller)' }}</span>
        </a>
    </div>

    <!-- Orders Table & Content -->
    @if($orders->count() > 0)
        <div class="rounded-2xl glass-card border border-slate-200 dark:border-zinc-800 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-start text-xs text-slate-700 dark:text-zinc-300">
                    <thead class="bg-slate-100/80 dark:bg-zinc-900/90 text-slate-600 dark:text-zinc-400 text-[11px] font-bold uppercase tracking-wider border-b border-slate-200 dark:border-zinc-800">
                        <tr>
                            <th class="py-3.5 px-4 text-start">{{ app()->getLocale() === 'ar' ? 'رقم الطلب والخدمة' : 'Order & Service' }}</th>
                            <th class="py-3.5 px-4 text-start">{{ ($tab ?? 'purchases') === 'sales' ? (app()->getLocale() === 'ar' ? 'المشتري' : 'Buyer') : (app()->getLocale() === 'ar' ? 'البائع' : 'Seller') }}</th>
                            <th class="py-3.5 px-4 text-start">{{ app()->getLocale() === 'ar' ? 'الباقة والمبلغ' : 'Package & Amount' }}</th>
                            <th class="py-3.5 px-4 text-start">{{ app()->getLocale() === 'ar' ? 'حالة الطلب' : 'Status' }}</th>
                            <th class="py-3.5 px-4 text-start">{{ app()->getLocale() === 'ar' ? 'موعد التسليم' : 'Delivery Due' }}</th>
                            <th class="py-3.5 px-4 text-end">{{ app()->getLocale() === 'ar' ? 'الإجراء' : 'Action' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/80">
                        @foreach($orders as $order)
                            @php
                                $service = $order->package?->service;
                                $statusVal = is_object($order->status) ? $order->status->value : (string)$order->status;
                                $otherParty = ($tab ?? 'purchases') === 'sales' ? $order->buyer : $order->seller;
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-zinc-800/40 transition-colors">
                                <!-- Order & Service -->
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-zinc-800 overflow-hidden flex-shrink-0 border border-slate-200 dark:border-zinc-700/60 flex items-center justify-center">
                                            @if($service && $service->cover_image)
                                                <img src="{{ $service->cover_image }}" alt="" class="w-full h-full object-cover">
                                            @else
                                                <i class="ri-service-line text-slate-400 dark:text-zinc-500 text-lg"></i>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <a href="{{ route('marketplace.orders.show', $order->id) }}" class="font-bold text-sm text-slate-900 dark:text-white hover:text-brand-600 dark:hover:text-brand-300 truncate block">
                                                {{ $order->snapshot['service_title'] ?? $service?->title ?? ('Order #'.$order->id) }}
                                            </a>
                                            <div class="flex items-center gap-2 mt-0.5 text-[11px] text-slate-500 dark:text-zinc-400">
                                                <span class="font-mono font-semibold text-brand-600 dark:text-brand-400">#{{ $order->id }}</span>
                                                <span>•</span>
                                                <span>{{ $order->created_at ? $order->created_at->setTimezone('Africa/Cairo')->format('Y-m-d H:i') : '' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Other Party -->
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-brand-500/10 border border-brand-500/20 text-brand-600 dark:text-brand-300 flex items-center justify-center font-bold text-xs">
                                            {{ strtoupper(substr($otherParty->name ?? 'U', 0, 2)) }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900 dark:text-white">{{ $otherParty->name ?? '—' }}</p>
                                            <p class="text-[10px] text-slate-500 dark:text-zinc-400">{{ $otherParty->email ?? '' }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Package & Amount -->
                                <td class="py-4 px-4">
                                    <p class="font-semibold text-slate-900 dark:text-white">
                                        {{ $order->snapshot['package_name'] ?? $order->package?->name ?? 'Standard Package' }}
                                    </p>
                                    @php
                                        $oCurrency = $order->currency->symbol ?? $order->currency->currency ?? '$';
                                    @endphp
                                    <div class="flex items-baseline gap-1 text-xs font-bold text-emerald-600 dark:text-emerald-400 font-mono mt-0.5">
                                        <span>{{ $oCurrency }} {{ number_format($order->amount, 2) }}</span>
                                        <span class="text-[10px] text-slate-500 dark:text-zinc-400 font-normal">({{ app()->getLocale() === 'ar' ? 'محفوظ بالضمان' : 'Escrow Protected' }})</span>
                                    </div>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-4 px-4">
                                    @if(in_array($statusVal, ['completed', 'auto_completed']))
                                        <span class="px-2.5 py-1 rounded-lg bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800/60 font-bold text-[11px] inline-flex items-center gap-1">
                                            <i class="ri-checkbox-circle-fill text-emerald-500"></i>
                                            {{ app()->getLocale() === 'ar' ? 'مكتمل' : 'Completed' }}
                                        </span>
                                    @elseif($statusVal === 'delivered')
                                        <span class="px-2.5 py-1 rounded-lg bg-indigo-100 dark:bg-indigo-950/60 text-indigo-800 dark:text-indigo-300 border border-indigo-300 dark:border-indigo-800/60 font-bold text-[11px] inline-flex items-center gap-1">
                                            <i class="ri-send-plane-fill text-indigo-500"></i>
                                            {{ app()->getLocale() === 'ar' ? 'تم التسليم' : 'Delivered' }}
                                        </span>
                                    @elseif($statusVal === 'revision')
                                        <span class="px-2.5 py-1 rounded-lg bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-800/60 font-bold text-[11px] inline-flex items-center gap-1">
                                            <i class="ri-refresh-line text-amber-500"></i>
                                            {{ app()->getLocale() === 'ar' ? 'طلب تعديل' : 'Revision' }}
                                        </span>
                                    @elseif(in_array($statusVal, ['processing', 'in_progress', 'active']))
                                        <span class="px-2.5 py-1 rounded-lg bg-cyan-100 dark:bg-cyan-950/60 text-cyan-800 dark:text-cyan-300 border border-cyan-300 dark:border-cyan-800/60 font-bold text-[11px] inline-flex items-center gap-1">
                                            <i class="ri-time-line text-cyan-500"></i>
                                            {{ app()->getLocale() === 'ar' ? 'قيد التنفيذ' : 'In Progress' }}
                                        </span>
                                    @elseif($statusVal === 'disputed')
                                        <span class="px-2.5 py-1 rounded-lg bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-300 dark:border-rose-800/60 font-bold text-[11px] inline-flex items-center gap-1">
                                            <i class="ri-error-warning-fill text-rose-500"></i>
                                            {{ app()->getLocale() === 'ar' ? 'نزاع مفتوح' : 'Disputed' }}
                                        </span>
                                    @elseif($statusVal === 'cancelled')
                                        <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 border border-slate-300 dark:border-zinc-700 font-bold text-[11px] inline-flex items-center gap-1">
                                            <i class="ri-close-circle-line"></i>
                                            {{ app()->getLocale() === 'ar' ? 'ملغي' : 'Cancelled' }}
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 border border-slate-300 dark:border-zinc-700 font-bold text-[11px] inline-flex items-center gap-1">
                                            <i class="ri-hourglass-line"></i>
                                            {{ ucfirst($statusVal) }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Delivery Due Date -->
                                <td class="py-4 px-4">
                                    @if($order->due_date)
                                        <p class="font-medium text-slate-800 dark:text-zinc-200">
                                            {{ $order->due_date->setTimezone('Africa/Cairo')->format('Y-m-d') }}
                                        </p>
                                        <span class="text-[10px] text-slate-500 dark:text-zinc-400">
                                            {{ $order->due_date->setTimezone('Africa/Cairo')->diffForHumans() }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 dark:text-zinc-500">—</span>
                                    @endif
                                </td>

                                <!-- Action -->
                                <td class="py-4 px-4 text-end">
                                    <a href="{{ route('marketplace.orders.show', $order->id) }}" class="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-brand-600 text-slate-800 hover:text-white dark:bg-zinc-800 dark:hover:bg-brand-600 dark:text-zinc-200 dark:hover:text-white text-xs font-semibold transition-colors inline-flex items-center gap-1 shadow-sm">
                                        <span>{{ app()->getLocale() === 'ar' ? 'عرض الطلب' : 'View Order' }}</span>
                                        <i class="ri-arrow-left-s-line {{ app()->getLocale() === 'ar' ? '' : 'rotate-180' }}"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($orders->hasPages())
                <div class="p-4 border-t border-slate-200 dark:border-zinc-800">
                    {{ $orders->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    @else
        <!-- Empty State -->
        <div class="text-center py-20 rounded-3xl glass-card border border-slate-200 dark:border-zinc-800 p-8 shadow-sm">
            <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-zinc-800 flex items-center justify-center text-slate-400 dark:text-zinc-500 mx-auto mb-4 text-3xl">
                <i class="ri-shopping-bag-3-line"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">
                {{ ($tab ?? 'purchases') === 'sales' ? (app()->getLocale() === 'ar' ? 'لا توجد طلبات بيع حالياً' : 'No sales orders yet') : (app()->getLocale() === 'ar' ? 'لا توجد طلبات شراء بعد' : 'No purchases found') }}
            </h3>
            <p class="text-sm text-slate-600 dark:text-zinc-400 max-w-sm mx-auto mb-6">
                {{ ($tab ?? 'purchases') === 'sales' ? (app()->getLocale() === 'ar' ? 'عندما يقوم العملاء بطلب خدماتك ستظهر جميع تفاصيل العمل والتسليمات هنا.' : 'When clients purchase your listed services, all order milestones and deliverables will appear here.') : (app()->getLocale() === 'ar' ? 'تصفح خدمات البرمجة، التصميم، والذكاء الاصطناعي واطلب خدمتك الأولى مع ضمان حماية الدفع الكامل.' : 'Browse verified developer services, scripts, and automation bots protected by escrow.') }}
            </p>
            @if(($tab ?? 'purchases') === 'sales')
                <a href="{{ route('marketplace.services.create') }}" class="px-6 py-3 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold shadow-lg shadow-brand-500/25 transition-all inline-block">
                    {{ app()->getLocale() === 'ar' ? 'أضف خدمتك الأولى' : 'List Your First Service' }}
                </a>
            @else
                <a href="{{ route('marketplace.services.index') }}" class="px-6 py-3 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold shadow-lg shadow-brand-500/25 transition-all inline-block">
                    {{ app()->getLocale() === 'ar' ? 'استكشف خدمات السوق' : 'Explore Marketplace Services' }}
                </a>
            @endif
        </div>
    @endif

</div>
@endsection

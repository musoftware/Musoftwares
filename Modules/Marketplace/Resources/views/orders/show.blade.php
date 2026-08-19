@extends('marketplace::layouts.marketplace-master')

@section('content')
@php
    $user = auth()->user();
    $isBuyer = $user->id === $order->buyer_id;
    $isSeller = $user->id === $order->seller_id;
    $service = $order->package?->service;
    $statusVal = is_object($order->status) ? $order->status->value : (string)$order->status;
    $otherParty = $isBuyer ? $order->seller : $order->buyer;

    $steps = ['pending', 'in_progress', 'delivered', 'completed'];
    $currentStepIndex = array_search($statusVal === 'active' ? 'in_progress' : ($statusVal === 'processing' ? 'in_progress' : ($statusVal === 'auto_completed' ? 'completed' : $statusVal)), $steps);
    if ($currentStepIndex === false) {
        $currentStepIndex = ($statusVal === 'disputed' || $statusVal === 'revision') ? 1 : 0;
    }
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-slate-500 dark:text-zinc-400 mb-6 overflow-x-auto pb-2">
        <a href="{{ route('marketplace.services.index') }}" class="hover:text-slate-900 dark:hover:text-white transition-colors flex items-center gap-1">
            <i class="ri-store-2-line"></i> {{ app()->getLocale() === 'ar' ? 'السوق' : 'Marketplace' }}
        </a>
        <i class="ri-arrow-left-s-line text-slate-400 dark:text-zinc-600 {{ app()->getLocale() === 'ar' ? '' : 'rotate-180' }}"></i>
        <a href="{{ route('marketplace.orders.index') }}" class="hover:text-slate-900 dark:hover:text-white transition-colors">
            {{ app()->getLocale() === 'ar' ? 'الطلبات' : 'Orders' }}
        </a>
        <i class="ri-arrow-left-s-line text-slate-400 dark:text-zinc-600 {{ app()->getLocale() === 'ar' ? '' : 'rotate-180' }}"></i>
        <span class="text-slate-800 dark:text-zinc-200 font-mono font-medium">#{{ $order->id }}</span>
    </nav>

    <!-- Top Status Bar & Stepper -->
    <div class="rounded-3xl glass-card p-6 sm:p-8 border border-slate-200 dark:border-zinc-800 mb-8 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-zinc-400 mb-1">
                    <span class="font-mono font-bold text-brand-600 dark:text-brand-400">Order #{{ $order->id }}</span>
                    <span>•</span>
                    <span>{{ $order->created_at ? $order->created_at->setTimezone('Africa/Cairo')->format('Y-m-d H:i') : '' }}</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white">
                    {{ $order->snapshot['service_title'] ?? $service?->title ?? 'Custom Service Order' }}
                </h1>
            </div>

            <!-- Status Badge -->
            <div>
                @if(in_array($statusVal, ['completed', 'auto_completed']))
                    <span class="px-3.5 py-1.5 rounded-xl bg-emerald-100 dark:bg-emerald-950/70 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 font-bold text-xs inline-flex items-center gap-1.5 shadow-sm">
                        <i class="ri-checkbox-circle-fill text-emerald-500 text-sm"></i>
                        {{ app()->getLocale() === 'ar' ? 'طلب مكتمل ومسلم' : 'Order Completed' }}
                    </span>
                @elseif($statusVal === 'delivered')
                    <span class="px-3.5 py-1.5 rounded-xl bg-indigo-100 dark:bg-indigo-950/70 text-indigo-800 dark:text-indigo-300 border border-indigo-300 dark:border-indigo-800 font-bold text-xs inline-flex items-center gap-1.5 shadow-sm">
                        <i class="ri-send-plane-fill text-indigo-500 text-sm"></i>
                        {{ app()->getLocale() === 'ar' ? 'تم تسليم العمل - بانتظار موافقتك' : 'Work Delivered - Review Pending' }}
                    </span>
                @elseif($statusVal === 'revision')
                    <span class="px-3.5 py-1.5 rounded-xl bg-amber-100 dark:bg-amber-950/70 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-800 font-bold text-xs inline-flex items-center gap-1.5 shadow-sm">
                        <i class="ri-refresh-line text-amber-500 text-sm"></i>
                        {{ app()->getLocale() === 'ar' ? 'قيد التعديل والمراجعة' : 'Under Revision' }}
                    </span>
                @elseif(in_array($statusVal, ['processing', 'in_progress', 'active', 'pending']))
                    <span class="px-3.5 py-1.5 rounded-xl bg-cyan-100 dark:bg-cyan-950/70 text-cyan-800 dark:text-cyan-300 border border-cyan-300 dark:border-cyan-800 font-bold text-xs inline-flex items-center gap-1.5 shadow-sm">
                        <i class="ri-time-line text-cyan-500 text-sm"></i>
                        {{ app()->getLocale() === 'ar' ? 'الطلب قيد التنفيذ' : 'In Progress' }}
                    </span>
                @elseif($statusVal === 'disputed')
                    <span class="px-3.5 py-1.5 rounded-xl bg-rose-100 dark:bg-rose-950/70 text-rose-800 dark:text-rose-300 border border-rose-300 dark:border-rose-800 font-bold text-xs inline-flex items-center gap-1.5 shadow-sm">
                        <i class="ri-error-warning-fill text-rose-500 text-sm"></i>
                        {{ app()->getLocale() === 'ar' ? 'نزاع مفتوح ومحال للإدارة' : 'Order in Dispute' }}
                    </span>
                @else
                    <span class="px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 border border-slate-300 dark:border-zinc-700 font-bold text-xs inline-flex items-center gap-1.5">
                        {{ ucfirst($statusVal) }}
                    </span>
                @endif
            </div>
        </div>

        <!-- Progress Stepper -->
        <div class="relative pt-2">
            <div class="h-2 w-full bg-slate-200 dark:bg-zinc-800 rounded-full overflow-hidden mb-3">
                <div 
                    class="h-full bg-gradient-to-r from-brand-600 to-indigo-500 transition-all duration-500 rounded-full" 
                    style="width: {{ max(15, min(100, (($currentStepIndex + 1) / count($steps)) * 100)) }}%;"
                ></div>
            </div>
            <div class="grid grid-cols-4 text-xs font-semibold text-slate-500 dark:text-zinc-400">
                <div class="{{ $currentStepIndex >= 0 ? 'text-brand-600 dark:text-brand-400 font-bold' : '' }}">
                    <i class="ri-checkbox-circle-fill me-1"></i> {{ app()->getLocale() === 'ar' ? 'تم إنشاء الطلب' : 'Placed' }}
                </div>
                <div class="text-center {{ $currentStepIndex >= 1 ? 'text-brand-600 dark:text-brand-400 font-bold' : '' }}">
                    <i class="ri-time-fill me-1"></i> {{ app()->getLocale() === 'ar' ? 'قيد التنفيذ' : 'In Progress' }}
                </div>
                <div class="text-center {{ $currentStepIndex >= 2 ? 'text-brand-600 dark:text-brand-400 font-bold' : '' }}">
                    <i class="ri-send-plane-2-fill me-1"></i> {{ app()->getLocale() === 'ar' ? 'تم التسليم' : 'Delivered' }}
                </div>
                <div class="text-end {{ $currentStepIndex >= 3 ? 'text-brand-600 dark:text-brand-400 font-bold' : '' }}">
                    <i class="ri-verified-badge-fill me-1"></i> {{ app()->getLocale() === 'ar' ? 'مكتمل' : 'Completed' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Main Workspace Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left / Workspace & Deliverables & Messages (8 cols) -->
        <div class="lg:col-span-8 space-y-8">
            
            <!-- Deliverables & Serial Key Section -->
            @if($order->delivery_payload)
                <div class="rounded-3xl glass-card border border-emerald-300 dark:border-emerald-800/80 p-6 sm:p-8 bg-emerald-50/40 dark:bg-emerald-950/20 shadow-lg">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center text-xl shadow-md">
                            <i class="ri-gift-line"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                {{ app()->getLocale() === 'ar' ? 'مخرجات العمل والتسليم' : 'Final Deliverables' }}
                            </h3>
                            <p class="text-xs text-slate-600 dark:text-zinc-400">
                                {{ app()->getLocale() === 'ar' ? 'تم تسليم العمل والمرفقات من قبل مقدم الخدمة.' : 'Deliverables provided by the seller.' }}
                            </p>
                        </div>
                    </div>

                    @if(isset($order->delivery_payload['serial_code']))
                        <div class="p-4 rounded-2xl bg-white dark:bg-zinc-900 border border-emerald-300 dark:border-emerald-800/60 mb-4">
                            <span class="text-xs font-bold text-slate-500 dark:text-zinc-400 block mb-1">
                                {{ app()->getLocale() === 'ar' ? 'مفتاح الترخيص / السيريال الرقمي المخصص لك:' : 'Your License / Digital Serial Key:' }}
                            </span>
                            <div class="flex items-center justify-between gap-3 bg-slate-100 dark:bg-zinc-800 p-3 rounded-xl font-mono text-sm font-bold text-emerald-600 dark:text-emerald-400">
                                <span id="serialKeyText">{{ $order->delivery_payload['serial_code'] }}</span>
                                <button type="button" onclick="navigator.clipboard.writeText('{{ $order->delivery_payload['serial_code'] }}'); alert('{{ app()->getLocale() === 'ar' ? 'تم نسخ المفتاح بنجاح' : 'Key copied to clipboard!' }}')" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold">
                                    <i class="ri-file-copy-line"></i> {{ app()->getLocale() === 'ar' ? 'نسخ' : 'Copy' }}
                                </button>
                            </div>
                        </div>
                    @endif

                    @if(!empty($order->delivery_payload['message']))
                        <div class="p-4 rounded-2xl bg-white dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 text-sm text-slate-700 dark:text-zinc-300 mb-4 whitespace-pre-line">
                            {{ $order->delivery_payload['message'] }}
                        </div>
                    @endif

                    @if(!empty($order->delivery_payload['links']))
                        <div class="p-4 rounded-2xl bg-white dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 text-sm text-slate-700 dark:text-zinc-300">
                            <span class="font-bold block mb-1">{{ app()->getLocale() === 'ar' ? 'روابط التسليم والمرفقات:' : 'Delivery Links & Attachments:' }}</span>
                            <div class="text-brand-600 dark:text-brand-400 underline break-all">
                                {!! nl2br(e($order->delivery_payload['links'])) !!}
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Seller Delivery Submission Box -->
            @if($isSeller && in_array($statusVal, ['pending', 'processing', 'in_progress', 'active', 'revision']))
                <div class="rounded-3xl glass-card border border-brand-300 dark:border-brand-500/30 p-6 sm:p-8 shadow-sm">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-brand-600 text-white flex items-center justify-center text-xl shadow-md">
                            <i class="ri-upload-cloud-2-line"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                {{ app()->getLocale() === 'ar' ? 'تسليم العمل النهائي للعميل' : 'Submit Final Work Deliverable' }}
                            </h3>
                            <p class="text-xs text-slate-600 dark:text-zinc-400">
                                {{ app()->getLocale() === 'ar' ? 'أرفق ملاحظات التسليم، روابط التحميل، أو الكود المكتمل.' : 'Provide final notes, download links, or deliverable repository.' }}
                            </p>
                        </div>
                    </div>

                    <form action="{{ route('marketplace.orders.deliver', $order->id) }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1">
                                {{ app()->getLocale() === 'ar' ? 'ملاحظات وتفاصيل التسليم' : 'Delivery Notes' }}
                            </label>
                            <textarea 
                                name="message" 
                                rows="4" 
                                required
                                placeholder="{{ app()->getLocale() === 'ar' ? 'اكتب تفاصيل ما تم تنفيذه وكيفية تشغيل أو استخدام الخدمة...' : 'Describe what was completed and instructions for the client...' }}"
                                class="w-full p-3.5 rounded-2xl bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 text-slate-900 dark:text-white text-xs focus:outline-none focus:border-brand-500"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1">
                                {{ app()->getLocale() === 'ar' ? 'روابط الملفات أو المستودع (GitHub, Drive, Dropbox...)' : 'File / Download Links' }}
                            </label>
                            <textarea 
                                name="links" 
                                rows="2" 
                                placeholder="{{ app()->getLocale() === 'ar' ? 'ضع روابط التسليم المباشرة هنا...' : 'https://github.com/... or https://drive.google.com/...' }}"
                                class="w-full p-3.5 rounded-2xl bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 text-slate-900 dark:text-white text-xs focus:outline-none focus:border-brand-500"
                            ></textarea>
                        </div>

                        <button type="submit" onclick="return confirm('{{ app()->getLocale() === 'ar' ? 'هل أنت متأكد من تسليم العمل النهائي للعميل؟' : 'Confirm submitting final work deliverable?' }}')" class="w-full py-3.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold shadow-lg shadow-brand-600/25 transition-all flex items-center justify-center gap-2">
                            <i class="ri-send-plane-2-fill text-base"></i>
                            <span>{{ app()->getLocale() === 'ar' ? 'إرسال التسليم ومطالبة العميل بالفحص' : 'Submit Deliverable & Notify Buyer' }}</span>
                        </button>
                    </form>
                </div>
            @endif

            <!-- Order Requirements / Details Card -->
            <div class="rounded-3xl glass-card border border-slate-200 dark:border-zinc-800 p-6 sm:p-8 shadow-sm">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="ri-file-list-3-line text-brand-600 dark:text-brand-400"></i>
                    <span>{{ app()->getLocale() === 'ar' ? 'تفاصيل باقة الخدمة المطلوبة' : 'Package Specifications' }}</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-zinc-900/80 border border-slate-200 dark:border-zinc-800">
                        <span class="text-slate-500 dark:text-zinc-400 block mb-1">{{ app()->getLocale() === 'ar' ? 'اسم الباقة' : 'Package' }}</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $order->snapshot['package_name'] ?? $order->package?->name ?? 'Standard' }}</span>
                    </div>
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-zinc-900/80 border border-slate-200 dark:border-zinc-800">
                        <span class="text-slate-500 dark:text-zinc-400 block mb-1">{{ app()->getLocale() === 'ar' ? 'مدة التسليم المتفق عليها' : 'Delivery Timeline' }}</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $order->snapshot['delivery_days'] ?? $order->package?->delivery_days ?? 3 }} {{ app()->getLocale() === 'ar' ? 'أيام' : 'days' }}</span>
                    </div>
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-zinc-900/80 border border-slate-200 dark:border-zinc-800">
                        <span class="text-slate-500 dark:text-zinc-400 block mb-1">{{ app()->getLocale() === 'ar' ? 'عدد جولات التعديل' : 'Revisions' }}</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $order->snapshot['revisions'] ?? $order->package?->revisions ?? 1 }} {{ app()->getLocale() === 'ar' ? 'تعديلات' : 'revisions' }}</span>
                    </div>
                </div>

                @if(!empty($order->snapshot['package_description']))
                    <div class="mt-4 p-4 rounded-2xl bg-slate-50 dark:bg-zinc-900/60 border border-slate-200 dark:border-zinc-800 text-xs text-slate-600 dark:text-zinc-300 leading-relaxed">
                        {{ $order->snapshot['package_description'] }}
                    </div>
                @endif
            </div>

        </div>

        <!-- Right / Financials & Action Sidebar (4 cols) -->
        <div class="lg:col-span-4 space-y-6">
            
            <!-- Financial Summary & Escrow Protection Box -->
            <div class="rounded-3xl glass-card border border-slate-200 dark:border-zinc-800 p-6 shadow-sm">
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-200 dark:border-zinc-800">
                    <span class="text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider">
                        {{ app()->getLocale() === 'ar' ? 'الملخص المالي' : 'Financial Summary' }}
                    </span>
                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 text-[10px] font-bold">
                        <i class="ri-shield-check-fill text-emerald-500"></i> Escrow Protected
                    </span>
                </div>

                @php
                    $orderCurrency = $order->currency->symbol ?? $order->currency->currency ?? '$';
                @endphp
                <div class="space-y-3 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600 dark:text-zinc-400">{{ app()->getLocale() === 'ar' ? 'مبلغ الطلب:' : 'Order Total:' }}</span>
                        <span class="text-base font-extrabold text-slate-900 dark:text-white font-mono">{{ $orderCurrency }} {{ number_format($order->amount, 2) }}</span>
                    </div>

                    @if($isSeller)
                        <div class="flex items-center justify-between text-slate-500 dark:text-zinc-400">
                            <span>{{ app()->getLocale() === 'ar' ? 'عمولة المنصة:' : 'Platform Fee:' }}</span>
                            <span class="font-mono">-{{ $orderCurrency }} {{ number_format($order->commission_amount, 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-zinc-800 font-bold text-emerald-600 dark:text-emerald-400">
                            <span>{{ app()->getLocale() === 'ar' ? 'صافي أرباحك عند الإكمال:' : 'Net Earnings:' }}</span>
                            <span class="text-base font-mono">{{ $orderCurrency }} {{ number_format($order->amount - $order->commission_amount, 2) }}</span>
                        </div>
                    @endif
                </div>

                <div class="mt-5 p-3 rounded-2xl bg-brand-500/10 border border-brand-500/20 text-[11px] text-brand-800 dark:text-brand-300 leading-relaxed">
                    <i class="ri-lock-2-line me-1"></i>
                    {{ app()->getLocale() === 'ar' ? 'الأموال محفوظة بأمان بنظام الضمان (Escrow) ولن تُحرر إلا بعد موافقة المشتري على التسليم النهائي.' : 'Funds are securely locked in escrow and only released upon buyer deliverable approval.' }}
                </div>
            </div>

            <!-- Buyer Action Controls (Accept / Revision / Dispute) -->
            @if($isBuyer && $statusVal !== 'completed' && $statusVal !== 'cancelled')
                <div class="rounded-3xl glass-card border border-slate-200 dark:border-zinc-800 p-6 space-y-3 shadow-sm">
                    <h4 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-2">
                        {{ app()->getLocale() === 'ar' ? 'إجراءات المشتري' : 'Buyer Actions' }}
                    </h4>

                    @if($statusVal === 'delivered')
                        <!-- Accept Deliverable -->
                        <form action="{{ route('marketplace.orders.complete', $order->id) }}" method="POST">
                            @csrf
                            <button type="submit" onclick="return confirm('{{ app()->getLocale() === 'ar' ? 'هل أنت متأكد من قبول العمل النهائي وتحرير الأرباح للبائع؟' : 'Accept deliverable and release funds to seller?' }}')" class="w-full py-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-lg shadow-emerald-600/25 transition-all flex items-center justify-center gap-2">
                                <i class="ri-checkbox-circle-fill text-base"></i>
                                <span>{{ app()->getLocale() === 'ar' ? 'قبول العمل وإكمال الطلب' : 'Accept Delivery & Release Escrow' }}</span>
                            </button>
                        </form>

                        <!-- Request Revision -->
                        <form action="{{ route('marketplace.orders.revision', $order->id) }}" method="POST">
                            @csrf
                            <button type="submit" onclick="return confirm('{{ app()->getLocale() === 'ar' ? 'هل تريد طلب تعديل على العمل المسلم؟' : 'Request revision for this deliverable?' }}')" class="w-full py-2.5 rounded-xl bg-amber-100 hover:bg-amber-200 text-amber-800 dark:bg-amber-950/60 dark:hover:bg-amber-900/60 dark:text-amber-300 border border-amber-300 dark:border-amber-800/60 text-xs font-bold transition-all flex items-center justify-center gap-1.5">
                                <i class="ri-refresh-line"></i>
                                <span>{{ app()->getLocale() === 'ar' ? 'طلب تعديل ومراجعة' : 'Request Revision' }}</span>
                            </button>
                        </form>
                    @endif

                    @if($statusVal !== 'disputed')
                        <!-- Dispute Order -->
                        <form action="{{ route('marketplace.orders.dispute', $order->id) }}" method="POST">
                            @csrf
                            <button type="submit" onclick="return confirm('{{ app()->getLocale() === 'ar' ? 'هل تريد فتح نزاع وإحالة الطلب للتحكيم من إدارة المنصة؟' : 'Open dispute and escalate to arbitration?' }}')" class="w-full py-2 rounded-xl text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/30 text-xs font-semibold transition-all flex items-center justify-center gap-1">
                                <i class="ri-error-warning-line"></i>
                                <span>{{ app()->getLocale() === 'ar' ? 'فتح نزاع / مشكلة بالطلب' : 'Dispute Order' }}</span>
                            </button>
                        </form>
                    @endif
                </div>
            @endif

            <!-- Other Party Contact Card -->
            <div class="rounded-3xl glass-card border border-slate-200 dark:border-zinc-800 p-6 shadow-sm">
                <span class="text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider block mb-3">
                    {{ $isBuyer ? (app()->getLocale() === 'ar' ? 'مقدم الخدمة (البائع)' : 'Service Seller') : (app()->getLocale() === 'ar' ? 'العميل (المشتري)' : 'Client (Buyer)') }}
                </span>

                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-brand-600/20 border border-brand-500/30 text-brand-700 dark:text-brand-300 flex items-center justify-center font-extrabold text-sm">
                        {{ strtoupper(substr($otherParty->name ?? 'U', 0, 2)) }}
                    </div>
                    <div>
                        <h4 class="font-bold text-sm text-slate-900 dark:text-white">{{ $otherParty->name ?? '—' }}</h4>
                        <p class="text-xs text-slate-500 dark:text-zinc-400">{{ $otherParty->email ?? '' }}</p>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection

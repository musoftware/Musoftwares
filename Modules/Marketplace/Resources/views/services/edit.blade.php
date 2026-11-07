@extends('marketplace::layouts.marketplace-master')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-slate-500 dark:text-zinc-400 mb-6 overflow-x-auto pb-2">
        <a href="{{ route('marketplace.services.index') }}" class="hover:text-slate-900 dark:hover:text-white transition-colors flex items-center gap-1">
            <i class="ri-store-2-line"></i> {{ app()->getLocale() === 'ar' ? 'السوق' : 'Marketplace' }}
        </a>
        <i class="ri-arrow-left-s-line text-slate-400 dark:text-zinc-600 {{ app()->getLocale() === 'ar' ? '' : 'rotate-180' }}"></i>
        <a href="{{ route('marketplace.home', ['mode' => 'seller']) }}" class="hover:text-slate-900 dark:hover:text-white transition-colors">
            {{ app()->getLocale() === 'ar' ? 'خدماتي' : 'My Services' }}
        </a>
        <i class="ri-arrow-left-s-line text-slate-400 dark:text-zinc-600 {{ app()->getLocale() === 'ar' ? '' : 'rotate-180' }}"></i>
        <span class="text-slate-800 dark:text-zinc-200 font-medium">{{ app()->getLocale() === 'ar' ? 'تعديل الخدمة' : 'Edit Service' }}</span>
    </nav>

    <!-- Page Header -->
    <div class="mb-8 pb-6 border-b border-slate-200 dark:border-zinc-800 transition-colors">
        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800/60 inline-block mb-2">
            {{ app()->getLocale() === 'ar' ? 'تعديل الخدمة' : 'Edit Service' }}
        </span>
        <h1 class="text-2xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">
            {{ $service->title }}
        </h1>
    </div>

    <!-- Full Page Form -->
    <form action="{{ route('marketplace.services.update', $service->id) }}" method="POST" enctype="multipart/form-data" class="space-y-8">
        @csrf
        @method('PUT')

        <!-- 1. Basic Information -->
        <div class="rounded-3xl glass-card border border-slate-200 dark:border-zinc-800 p-6 sm:p-8 space-y-6 shadow-sm">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-zinc-800">
                <i class="ri-information-line text-brand-600 dark:text-brand-400 text-lg"></i>
                <span>{{ app()->getLocale() === 'ar' ? '١. المعلومات الأساسية للخدمة' : '1. Basic Service Information' }}</span>
            </h3>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1.5">
                    {{ app()->getLocale() === 'ar' ? 'عنوان الخدمة' : 'Service Title' }} <span class="text-rose-500">*</span>
                </label>
                <input 
                    type="text" 
                    name="title" 
                    value="{{ old('title', $service->title) }}" 
                    required 
                    class="w-full h-12 px-4 rounded-xl bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 text-slate-900 dark:text-white text-sm focus:outline-none focus:border-brand-500"
                >
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1.5">
                        {{ app()->getLocale() === 'ar' ? 'قسم الخدمة' : 'Category' }} <span class="text-rose-500">*</span>
                    </label>
                    <select 
                        name="category_id" 
                        required 
                        class="w-full h-12 px-4 rounded-xl bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 text-slate-900 dark:text-white text-sm focus:outline-none focus:border-brand-500"
                    >
                        <option value="">{{ app()->getLocale() === 'ar' ? 'اختر القسم المناسب...' : 'Select Category...' }}</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $service->category_id) == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1.5">
                        {{ app()->getLocale() === 'ar' ? 'شعار / ملخص سريع (Tagline)' : 'Short Tagline' }}
                    </label>
                    <input 
                        type="text" 
                        name="tagline" 
                        value="{{ old('tagline', $service->tagline) }}" 
                        class="w-full h-12 px-4 rounded-xl bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 text-slate-900 dark:text-white text-sm focus:outline-none focus:border-brand-500"
                    >
                </div>
            </div>
        </div>

        <!-- 2. Pricing & Delivery Package -->
        @php
            $isAr = app()->getLocale() === 'ar';
            $packageRows = $service->packages->isEmpty() ? collect([null]) : $service->packages->values();
            $hasManyPackages = $packageRows->count() > 1;
        @endphp
        <div class="rounded-3xl glass-card border border-slate-200 dark:border-zinc-800 p-6 sm:p-8 space-y-6 shadow-sm">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-zinc-800">
                <i class="ri-price-tag-3-line text-emerald-600 dark:text-emerald-400 text-lg"></i>
                <span>{{ $isAr ? '٢. باقة التسعير ومدة التنفيذ' : '2. Package & Pricing' }}</span>
            </h3>

            @foreach ($packageRows as $index => $package)
                <div class="space-y-4 {{ $index > 0 ? 'pt-6 border-t border-slate-100 dark:border-zinc-800' : '' }}">
                    @if ($hasManyPackages)
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label for="package-{{ $index }}-name" class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1.5">
                                    {{ $isAr ? 'اسم الباقة' : 'Package name' }} {{ $index + 1 }} <span class="text-rose-500">*</span>
                                </label>
                                <input id="package-{{ $index }}-name" type="text" maxlength="80" name="packages[{{ $index }}][name]" value="{{ old("packages.$index.name", $package?->name) }}" required class="w-full h-12 px-4 rounded-xl bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 text-slate-900 dark:text-white  text-sm focus:outline-none focus:border-brand-500">
                            </div>
                            <div>
                                <label for="package-{{ $index }}-description" class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1.5">
                                    {{ $isAr ? 'وصف الباقة' : 'Package description' }}
                                </label>
                                <textarea id="package-{{ $index }}-description" rows="2" maxlength="500" name="packages[{{ $index }}][description]" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 text-slate-900 dark:text-white text-sm focus:outline-none focus:border-brand-500">{{ old("packages.$index.description", $package?->description) }}</textarea>
                            </div>
                        </div>
                    @else
                        <input type="hidden" name="packages[{{ $index }}][name]" value="{{ $package?->name ?? 'Standard Package' }}">
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label for="package-{{ $index }}-price" class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1.5">
                                {{ $isAr ? 'سعر الخدمة' : 'Price' }} ({{ $package?->currency?->currency ?? 'USD' }}) <span class="text-rose-500">*</span>
                            </label>
                            <input id="package-{{ $index }}-price" type="number" step="0.01" min="1" name="packages[{{ $index }}][price]" value="{{ old("packages.$index.price", $package?->price ?? 25) }}" required class="w-full h-12 px-4 rounded-xl bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 text-slate-900 dark:text-white font-mono text-sm focus:outline-none focus:border-brand-500">
                        </div>

                        <div>
                            <label for="package-{{ $index }}-days" class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1.5">
                                {{ $isAr ? 'مدة التسليم (بالأيام)' : 'Delivery Days' }} <span class="text-rose-500">*</span>
                            </label>
                            <input id="package-{{ $index }}-days" type="number" min="1" max="365" name="packages[{{ $index }}][delivery_days]" value="{{ old("packages.$index.delivery_days", $package?->delivery_days ?? 3) }}" required class="w-full h-12 px-4 rounded-xl bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 text-slate-900 dark:text-white font-mono text-sm focus:outline-none focus:border-brand-500">
                        </div>

                        <div>
                            <label for="package-{{ $index }}-revisions" class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1.5">
                                {{ $isAr ? 'عدد جولات التعديل' : 'Revisions Included' }}
                            </label>
                            <input id="package-{{ $index }}-revisions" type="number" min="-1" name="packages[{{ $index }}][revisions]" value="{{ old("packages.$index.revisions", $package?->revisions ?? 2) }}" class="w-full h-12 px-4 rounded-xl bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 text-slate-900 dark:text-white font-mono text-sm focus:outline-none focus:border-brand-500">
                        </div>
                    </div>

                    <input type="hidden" name="packages[{{ $index }}][id]" value="{{ $package?->id }}">
                    <input type="hidden" name="packages[{{ $index }}][tier]" value="standard">
                </div>
            @endforeach
        </div>

        <!-- 3. Detailed Description -->
        <div class="rounded-3xl glass-card border border-slate-200 dark:border-zinc-800 p-6 sm:p-8 space-y-6 shadow-sm">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-zinc-800">
                <i class="ri-file-text-line text-brand-600 dark:text-brand-400 text-lg"></i>
                <span>{{ app()->getLocale() === 'ar' ? '٣. الوصف التفصيلي للخدمة والمخرجات' : '3. Service Description' }}</span>
            </h3>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1.5">
                    {{ app()->getLocale() === 'ar' ? 'تفاصيل الخدمة' : 'Description' }} <span class="text-rose-500">*</span>
                </label>
                <textarea 
                    name="description" 
                    rows="6" 
                    required 
                    class="w-full p-4 rounded-xl bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 text-slate-900 dark:text-white text-sm focus:outline-none focus:border-brand-500 leading-relaxed"
                >{{ old('description', $service->description) }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1.5">
                    {{ app()->getLocale() === 'ar' ? 'تعليمات ومتطلبات من المشتري للبدء' : 'Buyer Requirements' }}
                </label>
                <textarea 
                    name="requirements" 
                    rows="3" 
                    class="w-full p-4 rounded-xl bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 text-slate-900 dark:text-white text-sm focus:outline-none focus:border-brand-500 leading-relaxed"
                >{{ implode("\n", (array) old('requirements', $service->requirements)) }}</textarea>
            </div>

            <div>
                <label for="auto_reply" class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1.5">
                    {{ app()->getLocale() === 'ar' ? 'رسالة الرد التلقائي (اختياري)' : 'Auto reply message (optional)' }}
                </label>
                <textarea
                    id="auto_reply"
                    name="auto_reply"
                    rows="3"
                    maxlength="5000"
                    placeholder="{{ app()->getLocale() === 'ar' ? 'بتتبعت للمشتري في محادثة الطلب أول ما يشتري.' : 'Sent to the buyer in the order chat right after purchase.' }}"
                    class="w-full p-4 rounded-xl bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 text-slate-900 dark:text-white text-sm"
                >{{ old('auto_reply', $service->auto_reply) }}</textarea>
            </div>
        </div>

        <!-- 4. Media & Gallery -->
        <div class="rounded-3xl glass-card border border-slate-200 dark:border-zinc-800 p-6 sm:p-8 space-y-6 shadow-sm">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-zinc-800">
                <i class="ri-image-add-line text-indigo-600 dark:text-indigo-400 text-lg"></i>
                <span>{{ app()->getLocale() === 'ar' ? '٤. صور ومعرض أعمال الخدمة' : '4. Service Gallery & Images' }}</span>
            </h3>

            @if($service->cover_image)
                <div class="mb-4">
                    <span class="text-xs text-slate-500 dark:text-zinc-400 block mb-2">{{ app()->getLocale() === 'ar' ? 'الغلاف الحالي:' : 'Current Cover:' }}</span>
                    <div class="w-32 h-20 rounded-xl overflow-hidden border border-slate-300 dark:border-zinc-700">
                        <img src="{{ $service->cover_image }}" alt="" class="w-full h-full object-cover">
                    </div>
                </div>
            @endif

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1.5">
                    {{ app()->getLocale() === 'ar' ? 'تحديث أو رفع صور جديدة' : 'Upload New Gallery Images' }}
                </label>
                <input 
                    type="file" 
                    name="gallery[]" 
                    multiple 
                    accept="image/*"
                    class="w-full p-3 rounded-xl bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 text-slate-700 dark:text-zinc-300 text-xs focus:outline-none file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100"
                >
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex items-center justify-end gap-4 pt-4">
            <a href="{{ route('marketplace.home', ['mode' => 'seller']) }}" class="px-6 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-300 text-xs font-bold transition-all">
                {{ app()->getLocale() === 'ar' ? 'إلغاء' : 'Cancel' }}
            </a>
            <button type="submit" class="px-8 py-3.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold shadow-lg shadow-brand-600/25 transition-all flex items-center gap-2">
                <i class="ri-check-line text-base"></i>
                <span>{{ app()->getLocale() === 'ar' ? 'حفظ التعديلات' : 'Save Changes' }}</span>
            </button>
        </div>
    </form>

</div>
@endsection

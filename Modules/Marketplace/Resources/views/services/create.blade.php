@extends('marketplace::layouts.marketplace-master')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-slate-500 dark:text-zinc-400 mb-6 overflow-x-auto pb-2">
        <a href="{{ route('marketplace.services.index') }}" class="hover:text-slate-900 dark:hover:text-white transition-colors flex items-center gap-1">
            <i class="ri-store-2-line"></i> {{ app()->getLocale() === 'ar' ? 'السوق' : 'Marketplace' }}
        </a>
        <i class="ri-arrow-left-s-line text-slate-400 dark:text-zinc-600 {{ app()->getLocale() === 'ar' ? '' : 'rotate-180' }}"></i>
        <span class="text-slate-800 dark:text-zinc-200 font-medium">{{ app()->getLocale() === 'ar' ? 'إضافة خدمة جديدة' : 'New Service' }}</span>
    </nav>

    <!-- Page Header -->
    <div class="mb-8 pb-6 border-b border-slate-200 dark:border-zinc-800 transition-colors">
        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-brand-100 text-brand-800 dark:bg-brand-950/60 dark:text-brand-300 border border-brand-300 dark:border-brand-800/60 inline-block mb-2">
            {{ app()->getLocale() === 'ar' ? 'بوابة البائع' : 'Seller Portal' }}
        </span>
        <h1 class="text-2xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">
            {{ app()->getLocale() === 'ar' ? 'نشر خدمة برمجية جديدة' : 'List a New Software Service' }}
        </h1>
        <p class="text-sm text-slate-600 dark:text-zinc-400 mt-1">
            {{ app()->getLocale() === 'ar' ? 'اعرض مهاراتك البرمجية، الأدوات، أو البوتات للآلاف من المشترين والشركات مع حماية الدفع الكاملة.' : 'Offer your custom software, bots, and digital solutions with 100% escrow protection.' }}
        </p>
    </div>

    <!-- Full Page Form -->
    <form action="{{ route('marketplace.services.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
        @csrf

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
                    value="{{ old('title') }}" 
                    required 
                    placeholder="{{ app()->getLocale() === 'ar' ? 'مثال: سأقوم ببرمجة بوت تيليجرام ذكي متصل بالذكاء الاصطناعي' : 'e.g. I will build an AI-powered Telegram Automation Bot' }}"
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
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
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
                        value="{{ old('tagline') }}" 
                        placeholder="{{ app()->getLocale() === 'ar' ? 'تطوير سريع، كود نظيف، دعم فني مستمر' : 'High quality code with full documentation' }}"
                        class="w-full h-12 px-4 rounded-xl bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 text-slate-900 dark:text-white text-sm focus:outline-none focus:border-brand-500"
                    >
                </div>
            </div>
        </div>

        <!-- 2. Pricing & Delivery Package -->
        <div class="rounded-3xl glass-card border border-slate-200 dark:border-zinc-800 p-6 sm:p-8 space-y-6 shadow-sm">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-zinc-800">
                <i class="ri-price-tag-3-line text-emerald-600 dark:text-emerald-400 text-lg"></i>
                <span>{{ app()->getLocale() === 'ar' ? '٢. باقة التسعير ومدة التنفيذ' : '2. Package & Pricing' }}</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1.5">
                        {{ app()->getLocale() === 'ar' ? 'سعر الخدمة ($ USD)' : 'Price ($ USD)' }} <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        step="0.01" 
                        min="5" 
                        name="packages[0][price]" 
                        value="{{ old('packages.0.price', 25) }}" 
                        required 
                        class="w-full h-12 px-4 rounded-xl bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 text-slate-900 dark:text-white font-mono text-sm focus:outline-none focus:border-brand-500"
                    >
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1.5">
                        {{ app()->getLocale() === 'ar' ? 'مدة التسليم (بالأيام)' : 'Delivery Days' }} <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        min="1" 
                        max="60" 
                        name="packages[0][delivery_days]" 
                        value="{{ old('packages.0.delivery_days', 3) }}" 
                        required 
                        class="w-full h-12 px-4 rounded-xl bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 text-slate-900 dark:text-white font-mono text-sm focus:outline-none focus:border-brand-500"
                    >
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1.5">
                        {{ app()->getLocale() === 'ar' ? 'عدد جولات التعديل' : 'Revisions Included' }}
                    </label>
                    <input 
                        type="number" 
                        min="0" 
                        max="10" 
                        name="packages[0][revisions]" 
                        value="{{ old('packages.0.revisions', 2) }}" 
                        class="w-full h-12 px-4 rounded-xl bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 text-slate-900 dark:text-white font-mono text-sm focus:outline-none focus:border-brand-500"
                    >
                </div>
            </div>

            <input type="hidden" name="packages[0][name]" value="Standard Package">
            <input type="hidden" name="packages[0][tier]" value="standard">
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
                    placeholder="{{ app()->getLocale() === 'ar' ? 'اشرح بالتفصيل ما سيحصل عليه المشتري، المميزات، التقنيات المستخدمة، وطريقة التنفيذ...' : 'Explain in detail what the client will receive, technologies used, and features...' }}"
                    class="w-full p-4 rounded-xl bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 text-slate-900 dark:text-white text-sm focus:outline-none focus:border-brand-500 leading-relaxed"
                >{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1.5">
                    {{ app()->getLocale() === 'ar' ? 'تعليمات ومتطلبات من المشتري للبدء' : 'Buyer Requirements' }}
                </label>
                <textarea 
                    name="requirements" 
                    rows="3" 
                    placeholder="{{ app()->getLocale() === 'ar' ? 'ما الذي تحتاجه من المشتري للبدء؟ (مثال: بيانات الـ API، متطلبات التصميم...)' : 'What do you need from the buyer before starting?' }}"
                    class="w-full p-4 rounded-xl bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 text-slate-900 dark:text-white text-sm focus:outline-none focus:border-brand-500 leading-relaxed"
                >{{ old('requirements') }}</textarea>
            </div>
        </div>

        <!-- 4. Media & Gallery -->
        <div class="rounded-3xl glass-card border border-slate-200 dark:border-zinc-800 p-6 sm:p-8 space-y-6 shadow-sm">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-zinc-800">
                <i class="ri-image-add-line text-indigo-600 dark:text-indigo-400 text-lg"></i>
                <span>{{ app()->getLocale() === 'ar' ? '٤. صور ومعرض أعمال الخدمة' : '4. Service Gallery & Images' }}</span>
            </h3>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1.5">
                    {{ app()->getLocale() === 'ar' ? 'رفع صور الخدمة (الصورة الأولى ستكون الغلاف الرئيسي)' : 'Upload Gallery Images' }}
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
            <a href="{{ route('marketplace.home') }}" class="px-6 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-300 text-xs font-bold transition-all">
                {{ app()->getLocale() === 'ar' ? 'إلغاء' : 'Cancel' }}
            </a>
            <button type="submit" class="px-8 py-3.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold shadow-lg shadow-brand-600/25 transition-all flex items-center gap-2">
                <i class="ri-check-line text-base"></i>
                <span>{{ app()->getLocale() === 'ar' ? 'نشر الخدمة في السوق' : 'Publish Service' }}</span>
            </button>
        </div>
    </form>

</div>
@endsection

@extends('digitalproducts::layouts.library-master')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <!-- Header -->
    <div class="mb-8 flex items-center justify-between pb-6 border-b border-zinc-800">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('admin.digitalproducts.index') }}" class="text-xs text-zinc-500 hover:text-white transition-colors">
                    <i class="ri-arrow-right-line"></i> العودة لقائمة الكتب
                </a>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white">إدارة تصنيفات الكتب الرقمية</h1>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        <!-- Add Category Form (5 cols) -->
        <div class="lg:col-span-5">
            <div class="rounded-3xl glass-card p-6 border border-zinc-800 sticky top-24">
                <h3 class="text-base font-bold text-white mb-4 flex items-center gap-2">
                    <i class="ri-add-circle-line text-brand-400"></i> إضافة تصنيف جديد
                </h3>

                <form action="{{ route('admin.digitalproducts.categories.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-zinc-300 mb-2">اسم التصنيف <span class="text-rose-400">*</span></label>
                        <input type="text" name="name" required placeholder="مثال: الذكاء الاصطناعي" class="w-full h-11 px-4 rounded-xl bg-zinc-900 border border-zinc-700 text-sm text-white focus:outline-none focus:border-brand-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-300 mb-2">الرابط اللطيف (Slug) — اختياري</label>
                        <input type="text" name="slug" placeholder="ai-models" class="w-full h-11 px-4 rounded-xl bg-zinc-900 border border-zinc-700 text-sm text-white font-mono focus:outline-none focus:border-brand-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-300 mb-2">ترتيب الظهور</label>
                        <input type="number" name="sort_order" value="0" class="w-full h-11 px-4 rounded-xl bg-zinc-900 border border-zinc-700 text-sm text-white focus:outline-none focus:border-brand-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-300 mb-2">وصف التصنيف</label>
                        <textarea name="description" rows="2" placeholder="وصف مقتضب عن نوعية الكتب في هذا القسم..." class="w-full p-3 rounded-xl bg-zinc-900 border border-zinc-700 text-sm text-white focus:outline-none focus:border-brand-500"></textarea>
                    </div>

                    <button type="submit" class="w-full h-12 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-sm shadow-lg shadow-brand-500/30 transition-all">
                        إضافة التصنيف
                    </button>
                </form>
            </div>
        </div>

        <!-- Categories List (7 cols) -->
        <div class="lg:col-span-7">
            <div class="rounded-3xl glass-card border border-zinc-800 overflow-hidden">
                <div class="p-4 border-b border-zinc-800 bg-zinc-900/60 font-bold text-sm text-white flex items-center justify-between">
                    <span>التصنيفات المتاحة ({{ $categories->count() }})</span>
                </div>
                
                <div class="divide-y divide-zinc-800/80">
                    @forelse($categories as $category)
                        <div class="p-4 flex items-center justify-between hover:bg-zinc-800/30 transition-colors">
                            <div>
                                <h4 class="text-sm font-bold text-white mb-0.5">{{ $category->name }}</h4>
                                <span class="text-xs text-zinc-400 font-mono">{{ $category->slug }}</span>
                                @if($category->description)
                                    <p class="text-xs text-zinc-500 mt-1">{{ $category->description }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-zinc-800 text-zinc-300 text-xs font-semibold">
                                    {{ $category->products_count }} كتاب
                                </span>
                                <form action="{{ route('admin.digitalproducts.categories.destroy', $category->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا التصنيف؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-8 h-8 rounded-lg bg-rose-950/40 hover:bg-rose-600 text-rose-400 hover:text-white flex items-center justify-center transition-colors">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-zinc-500 text-xs">
                            لا توجد تصنيفات مضافة حتى الآن.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>

</div>
@endsection

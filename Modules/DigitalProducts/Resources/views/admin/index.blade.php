@extends('digitalproducts::layouts.library-master')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <!-- Top Admin Bar -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8 pb-6 border-b border-zinc-800">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2.5 py-0.5 rounded-md bg-brand-500/20 text-brand-400 border border-brand-500/30 text-xs font-bold uppercase">لوحة الإدارة</span>
                <span class="text-xs text-zinc-500">إدارة الكتب والمنتجات الرقمية</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white">معرض الكتب والمطبوعات الرقمية</h1>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.digitalproducts.categories.index') }}" class="px-4 py-2.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-xs font-semibold transition-colors flex items-center gap-2 border border-zinc-700/60">
                <i class="ri-folder-settings-line"></i> إدارة التصنيفات
            </a>
            <a href="{{ route('admin.digitalproducts.create') }}" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold transition-all shadow-lg shadow-brand-500/30 flex items-center gap-2">
                <i class="ri-upload-cloud-2-line text-base"></i>
                <span>رفع كتاب جديد (PDF)</span>
            </a>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="rounded-2xl glass-card p-5 border border-zinc-800">
            <span class="text-xs text-zinc-400 block mb-1">إجمالي الكتب</span>
            <div class="text-2xl font-black text-white flex items-center justify-between">
                <span>{{ number_format($stats['total_books']) }}</span>
                <i class="ri-book-2-line text-brand-400 text-xl"></i>
            </div>
        </div>
        <div class="rounded-2xl glass-card p-5 border border-zinc-800">
            <span class="text-xs text-zinc-400 block mb-1">إجمالي التحميلات</span>
            <div class="text-2xl font-black text-emerald-400 flex items-center justify-between">
                <span>{{ number_format($stats['total_downloads']) }}</span>
                <i class="ri-download-cloud-2-line text-emerald-400 text-xl"></i>
            </div>
        </div>
        <div class="rounded-2xl glass-card p-5 border border-zinc-800">
            <span class="text-xs text-zinc-400 block mb-1">الكتب المجانية</span>
            <div class="text-2xl font-black text-cyan-400 flex items-center justify-between">
                <span>{{ number_format($stats['total_free']) }}</span>
                <i class="ri-gift-line text-cyan-400 text-xl"></i>
            </div>
        </div>
        <div class="rounded-2xl glass-card p-5 border border-zinc-800">
            <span class="text-xs text-zinc-400 block mb-1">الكتب المدفوعة</span>
            <div class="text-2xl font-black text-amber-400 flex items-center justify-between">
                <span>{{ number_format($stats['total_paid']) }}</span>
                <i class="ri-vip-crown-line text-amber-400 text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Filters & Search Form -->
    <div class="rounded-2xl glass-card p-4 border border-zinc-800 mb-6">
        <form action="{{ route('admin.digitalproducts.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            <div class="sm:col-span-5 relative">
                <i class="ri-search-line absolute right-3.5 top-3 text-zinc-500"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="بحث بالاسم أو المؤلف..." class="w-full h-10 pr-10 pl-3 rounded-xl bg-zinc-900 border border-zinc-800 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-brand-500">
            </div>
            <div class="sm:col-span-3">
                <select name="category_id" class="w-full h-10 px-3 rounded-xl bg-zinc-900 border border-zinc-800 text-xs text-zinc-300 focus:outline-none focus:border-brand-500">
                    <option value="">كل التصنيفات</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <select name="status" class="w-full h-10 px-3 rounded-xl bg-zinc-900 border border-zinc-800 text-xs text-zinc-300 focus:outline-none focus:border-brand-500">
                    <option value="">كل الحالات</option>
                    <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>منشور</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>مسودة</option>
                </select>
            </div>
            <div class="sm:col-span-2 flex items-center gap-2">
                <button type="submit" class="flex-1 h-10 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-white text-xs font-semibold transition-colors">
                    تطبيق الفلتر
                </button>
                @if(request()->anyFilled(['search', 'category_id', 'status']))
                    <a href="{{ route('admin.digitalproducts.index') }}" class="h-10 px-3 rounded-xl bg-zinc-800/60 hover:bg-zinc-800 text-zinc-400 hover:text-white text-xs flex items-center justify-center">
                        <i class="ri-refresh-line"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Books Table -->
    <div class="rounded-2xl glass-card border border-zinc-800 overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-zinc-900/90 text-zinc-400 border-b border-zinc-800 font-semibold">
                    <tr>
                        <th class="p-4">الكتاب / الغلاف</th>
                        <th class="p-4">التصنيف</th>
                        <th class="p-4">السعر</th>
                        <th class="p-4">الصفحات / الحجم</th>
                        <th class="p-4">التحميلات</th>
                        <th class="p-4">حالة النشر</th>
                        <th class="p-4 text-left">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800/60 text-zinc-300">
                    @forelse($products as $product)
                        <tr class="hover:bg-zinc-800/30 transition-colors">
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-16 rounded-lg bg-zinc-950 overflow-hidden border border-zinc-800 flex-shrink-0 flex items-center justify-center">
                                        @if($product->cover_image_path)
                                            <img src="{{ $product->cover_url }}" alt="" class="w-full h-full object-contain">
                                        @else
                                            <i class="ri-book-2-line text-zinc-600 text-xl"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-bold text-white mb-0.5 line-clamp-1">
                                            <a href="{{ route('library.show', $product->slug) }}" target="_blank" class="hover:text-brand-400 transition-colors">
                                                {{ $product->title }}
                                            </a>
                                        </h3>
                                        <span class="text-[11px] text-zinc-500">{{ $product->author_name ?? '—' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-md bg-zinc-800 text-zinc-300 text-[11px]">
                                    {{ $product->category?->name ?? 'غير مصنف' }}
                                </span>
                            </td>
                            <td class="p-4">
                                @if($product->is_free)
                                    <span class="px-2.5 py-1 rounded-md bg-emerald-950/60 border border-emerald-800/40 text-emerald-400 font-bold">مجاني</span>
                                @else
                                    <span class="font-bold text-white">{{ $product->formatted_price }}</span>
                                @endif
                            </td>
                            <td class="p-4">
                                <div class="text-[11px] text-zinc-400">
                                    <div>{{ $product->page_count ?? '—' }} صفحة</div>
                                    <div class="text-zinc-500 font-mono">{{ $product->formatted_file_size }}</div>
                                </div>
                            </td>
                            <td class="p-4 font-mono font-bold text-zinc-200">
                                {{ number_format($product->download_count) }}
                            </td>
                            <td class="p-4">
                                <button onclick="togglePublish({{ $product->id }}, this)" class="px-3 py-1 rounded-full text-[11px] font-semibold transition-all {{ $product->is_published ? 'bg-emerald-950/60 border border-emerald-800/40 text-emerald-400' : 'bg-zinc-800 text-zinc-500' }}">
                                    {{ $product->is_published ? '✓ منشور' : 'مخفي' }}
                                </button>
                            </td>
                            <td class="p-4 text-left">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('library.show', $product->slug) }}" target="_blank" class="w-8 h-8 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-300 flex items-center justify-center transition-colors" title="معاينة في المعرض">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                    <a href="{{ route('admin.digitalproducts.edit', $product->id) }}" class="w-8 h-8 rounded-lg bg-brand-600/20 hover:bg-brand-600 text-brand-300 hover:text-white flex items-center justify-center transition-colors" title="تعديل">
                                        <i class="ri-edit-line"></i>
                                    </a>
                                    <form action="{{ route('admin.digitalproducts.destroy', $product->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا الكتاب نهائياً؟');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-8 h-8 rounded-lg bg-rose-950/40 hover:bg-rose-600 text-rose-400 hover:text-white flex items-center justify-center transition-colors" title="حذف">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-12 text-center text-zinc-500">
                                <i class="ri-book-2-line text-4xl block mb-2 opacity-50"></i>
                                لا توجد كتب مضافة حتى الآن. اضغط على زر "رفع كتاب جديد" للبدء.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="p-4 border-t border-zinc-800">
                {{ $products->links() }}
            </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
function togglePublish(id, btn) {
    fetch(`/admin/digital-products/${id}/toggle-publish`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (data.is_published) {
                btn.className = 'px-3 py-1 rounded-full text-[11px] font-semibold transition-all bg-emerald-950/60 border border-emerald-800/40 text-emerald-400';
                btn.textContent = '✓ منشور';
            } else {
                btn.className = 'px-3 py-1 rounded-full text-[11px] font-semibold transition-all bg-zinc-800 text-zinc-500';
                btn.textContent = 'مخفي';
            }
        }
    });
}
</script>
@endpush
@endsection

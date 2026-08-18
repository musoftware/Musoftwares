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
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white">تعديل بيانات الكتاب: {{ $product->title }}</h1>
        </div>
        <a href="{{ route('library.show', $product->slug) }}" target="_blank" class="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-xs font-semibold flex items-center gap-1.5">
            <i class="ri-eye-line"></i> معاينة في المعرض
        </a>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.digitalproducts.update', $product->id) }}" method="POST" enctype="multipart/form-data" class="space-y-8">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left: Current Cover & Files -->
            <div class="space-y-6">
                <div class="rounded-3xl glass-card p-6 border border-zinc-800 text-center">
                    <h4 class="text-xs font-bold text-zinc-400 mb-4 uppercase">الغلاف الحالي</h4>
                    <div class="aspect-[3/4] w-full rounded-xl overflow-hidden bg-black p-2 border border-zinc-700 mb-4 flex items-center justify-center">
                        @if($product->cover_image_path)
                            <img src="{{ $product->cover_url }}" alt="" class="w-full h-full object-contain rounded">
                        @else
                            <i class="ri-book-2-line text-5xl text-zinc-600"></i>
                        @endif
                    </div>

                    <label class="block text-xs font-semibold text-zinc-300 mb-2">استبدال صورة الغلاف (اختياري)</label>
                    <input type="file" name="cover_image" accept="image/*" class="w-full text-xs text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-zinc-800 file:text-zinc-200 hover:file:bg-zinc-700">
                </div>

                <div class="rounded-3xl glass-card p-6 border border-zinc-800">
                    <h4 class="text-xs font-bold text-zinc-400 mb-3 uppercase">ملف الـ PDF</h4>
                    <p class="text-xs text-zinc-300 mb-2">الحجم الحالي: <strong class="text-white">{{ $product->formatted_file_size }}</strong></p>
                    
                    <label class="block text-xs font-semibold text-zinc-300 mb-2">استبدال ملف الـ PDF (اختياري)</label>
                    <input type="file" name="pdf_file" accept="application/pdf" class="w-full text-xs text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-zinc-800 file:text-zinc-200 hover:file:bg-zinc-700">
                </div>
            </div>

            <!-- Right: Details -->
            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-3xl glass-card p-6 sm:p-8 border border-zinc-800 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-zinc-300 mb-2">عنوان الكتاب <span class="text-rose-400">*</span></label>
                            <input type="text" name="title" value="{{ old('title', $product->title) }}" required class="w-full h-12 px-4 rounded-xl bg-zinc-900 border border-zinc-700 text-sm text-white focus:outline-none focus:border-brand-500">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-zinc-300 mb-2">رابط الكتاب (Slug)</label>
                            <input type="text" name="slug" value="{{ old('slug', $product->slug) }}" required class="w-full h-12 px-4 rounded-xl bg-zinc-900 border border-zinc-700 text-sm text-white font-mono focus:outline-none focus:border-brand-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-zinc-300 mb-2">التصنيف</label>
                            <select name="category_id" class="w-full h-12 px-4 rounded-xl bg-zinc-900 border border-zinc-700 text-sm text-white focus:outline-none focus:border-brand-500">
                                <option value="">-- بدون تصنيف --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ $product->category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-zinc-300 mb-2">السعر (USD) — 0 للتحميل المجاني</label>
                            <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $product->price) }}" class="w-full h-12 px-4 rounded-xl bg-zinc-900 border border-zinc-700 text-sm text-white focus:outline-none focus:border-brand-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-zinc-300 mb-2">عدد الصفحات</label>
                            <input type="number" name="page_count" value="{{ old('page_count', $product->page_count) }}" class="w-full h-12 px-4 rounded-xl bg-zinc-900 border border-zinc-700 text-sm text-white focus:outline-none focus:border-brand-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-zinc-300 mb-2">اسم المؤلف</label>
                            <input type="text" name="author_name" value="{{ old('author_name', $product->author_name) }}" class="w-full h-12 px-4 rounded-xl bg-zinc-900 border border-zinc-700 text-sm text-white focus:outline-none focus:border-brand-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-zinc-300 mb-2">الناشر</label>
                            <input type="text" name="publisher" value="{{ old('publisher', $product->publisher) }}" class="w-full h-12 px-4 rounded-xl bg-zinc-900 border border-zinc-700 text-sm text-white focus:outline-none focus:border-brand-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-zinc-300 mb-2">سنة النشر</label>
                            <input type="text" name="publication_year" value="{{ old('publication_year', $product->publication_year) }}" class="w-full h-12 px-4 rounded-xl bg-zinc-900 border border-zinc-700 text-sm text-white focus:outline-none focus:border-brand-500">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-zinc-300 mb-2">الوصف المختصر</label>
                            <textarea name="short_description" rows="2" class="w-full p-4 rounded-xl bg-zinc-900 border border-zinc-700 text-sm text-white focus:outline-none focus:border-brand-500">{{ old('short_description', $product->short_description) }}</textarea>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-zinc-300 mb-2">الوصف الكامل</label>
                            <textarea name="description" rows="6" class="w-full p-4 rounded-xl bg-zinc-900 border border-zinc-700 text-sm text-white focus:outline-none focus:border-brand-500">{{ old('description', $product->description) }}</textarea>
                        </div>

                    </div>
                </div>

                <div class="rounded-3xl glass-card p-6 border border-zinc-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-zinc-300">
                            <input type="checkbox" name="is_published" value="1" {{ $product->is_published ? 'checked' : '' }} class="w-4 h-4 rounded text-brand-600 bg-zinc-900 border-zinc-700">
                            <span>منشور في المعرض</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-zinc-300">
                            <input type="checkbox" name="is_featured" value="1" {{ $product->is_featured ? 'checked' : '' }} class="w-4 h-4 rounded text-brand-600 bg-zinc-900 border-zinc-700">
                            <span>إصدار مميز (Featured)</span>
                        </label>
                    </div>

                    <button type="submit" class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-sm shadow-xl shadow-brand-500/30 transition-all">
                        حفظ التعديلات
                    </button>
                </div>
            </div>

        </div>
    </form>

</div>
@endsection

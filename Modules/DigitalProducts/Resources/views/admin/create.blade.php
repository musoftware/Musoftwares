@extends('digitalproducts::layouts.library-master')

@push('head')
<!-- PDF.js CDN for instantaneous client-side Cover rendering & page counting -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
</script>
@endpush

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
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white">رفع كتاب جديد واستخراج البيانات تلقائياً</h1>
            <p class="text-xs text-zinc-400 mt-1">ارفع ملف الـ PDF وسيقوم النظام فورياً باستخراج الغلاف، عدد الصفحات، وحجم الملف.</p>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.digitalproducts.store') }}" method="POST" enctype="multipart/form-data" id="uploadForm" class="space-y-8">
        @csrf

        <!-- Hidden input for base64 extracted cover image -->
        <input type="hidden" name="cover_data" id="coverDataInput">

        <!-- 1. Smart PDF Dropzone -->
        <div class="rounded-3xl glass-card border-2 border-dashed border-zinc-700 hover:border-brand-500/80 p-8 text-center transition-all bg-dark-800/40 relative group" id="dropZone">
            <input type="file" name="pdf_file" id="pdfFileInput" accept="application/pdf" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" required>
            
            <div class="flex flex-col items-center justify-center pointer-events-none" id="dropZoneInitial">
                <div class="w-16 h-16 rounded-2xl bg-brand-500/10 text-brand-400 flex items-center justify-center text-3xl mb-4 group-hover:scale-110 group-hover:bg-brand-500/20 transition-all">
                    <i class="ri-file-pdf-2-line"></i>
                </div>
                <h3 class="text-base font-bold text-white mb-1">اسحب وأفلت ملف الـ PDF هنا أو اضغط للاختيار</h3>
                <p class="text-xs text-zinc-400 max-w-sm mb-3">يقبل ملفات PDF حتى حجم 150 ميجابايت. سيتم استخراج الغلاف والبيانات فورياً بمجرد اختيار الملف.</p>
                <span class="px-3.5 py-1.5 rounded-xl bg-zinc-800 text-zinc-300 text-xs font-semibold border border-zinc-700">
                    <i class="ri-magic-line text-brand-400 me-1"></i> استخراج تلقائي للغلاف والصفحات
                </span>
            </div>

            <!-- Loading State -->
            <div class="hidden flex flex-col items-center justify-center py-6" id="dropZoneLoading">
                <div class="w-10 h-10 border-4 border-brand-500/30 border-t-brand-500 rounded-full animate-spin mb-3"></div>
                <p class="text-sm font-semibold text-white">جارٍ قراءة ملف الـ PDF واستخراج الغلاف والصفحات...</p>
            </div>

            <!-- Success / Preview State -->
            <div class="hidden flex flex-col sm:flex-row items-center gap-6 p-4 rounded-2xl bg-zinc-900/90 border border-zinc-700 text-right" id="dropZoneSuccess">
                <div class="w-28 aspect-[3/4] rounded-xl overflow-hidden bg-black border border-white/10 p-1 flex-shrink-0 shadow-lg relative">
                    <img id="coverPreviewImg" src="" alt="غلاف الكتاب" class="w-full h-full object-contain rounded">
                    <span class="absolute bottom-1 right-1 left-1 bg-black/80 backdrop-blur-sm text-[9px] text-center text-emerald-400 font-bold py-0.5 rounded">
                        ✓ الغلاف المستخرج
                    </span>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-2 text-emerald-400 text-xs font-bold mb-1">
                        <i class="ri-checkbox-circle-fill text-base"></i>
                        <span>تمت معالجة ملف الـ PDF واستخراج الغلاف بنجاح!</span>
                    </div>
                    <h4 class="text-sm font-bold text-white mb-2" id="extractedFileName">filename.pdf</h4>
                    <div class="flex flex-wrap items-center gap-3 text-xs text-zinc-400">
                        <span class="px-2.5 py-1 rounded-lg bg-zinc-800 border border-zinc-700">
                            <i class="ri-pages-line text-brand-400 me-1"></i> <strong class="text-white" id="extractedPagesCount">0</strong> صفحة
                        </span>
                        <span class="px-2.5 py-1 rounded-lg bg-zinc-800 border border-zinc-700">
                            <i class="ri-hard-drive-2-line text-amber-400 me-1"></i> <strong class="text-white" id="extractedFileSize">0 MB</strong>
                        </span>
                        <span class="text-zinc-500 text-[11px]">اضغط على المربع لتغيير الملف</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hidden canvas for PDF.js page rendering -->
        <canvas id="pdfRenderCanvas" class="hidden"></canvas>

        <!-- 2. Book Info Fields -->
        <div class="rounded-3xl glass-card p-6 sm:p-8 border border-zinc-800 space-y-6">
            <h3 class="text-lg font-bold text-white flex items-center gap-2 pb-4 border-b border-zinc-800">
                <i class="ri-information-line text-brand-400"></i> بيانات وتفاصيل الكتاب
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- Title (Required) -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-zinc-300 mb-2">عنوان الكتاب <span class="text-rose-400">*</span></label>
                    <input type="text" name="title" id="bookTitleInput" required placeholder="مثال: دليل بناء نماذج الأعمال الذكية 2026" class="w-full h-12 px-4 rounded-xl bg-zinc-900 border border-zinc-700 text-sm text-white focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-xs font-bold text-zinc-300 mb-2">التصنيف</label>
                    <select name="category_id" class="w-full h-12 px-4 rounded-xl bg-zinc-900 border border-zinc-700 text-sm text-white focus:outline-none focus:border-brand-500">
                        <option value="">-- بدون تصنيف --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Pricing & Type -->
                <div>
                    <label class="block text-xs font-bold text-zinc-300 mb-2">السعر (USD) — اتركه 0 للتحميل المجاني</label>
                    <div class="relative">
                        <input type="number" step="0.01" min="0" name="price" id="bookPriceInput" value="0" placeholder="0.00" class="w-full h-12 pr-4 pl-14 rounded-xl bg-zinc-900 border border-zinc-700 text-sm text-white focus:outline-none focus:border-brand-500">
                        <span class="absolute left-4 top-3.5 text-xs text-zinc-500 font-mono font-bold">$ USD</span>
                    </div>
                </div>

                <!-- Page Count (Auto-filled) -->
                <div>
                    <label class="block text-xs font-bold text-zinc-300 mb-2">عدد الصفحات (يُملأ تلقائياً من الـ PDF)</label>
                    <input type="number" name="page_count" id="bookPageCountInput" min="1" placeholder="مثال: 120" class="w-full h-12 px-4 rounded-xl bg-zinc-900 border border-zinc-700 text-sm text-white focus:outline-none focus:border-brand-500">
                </div>

                <!-- Author Name -->
                <div>
                    <label class="block text-xs font-bold text-zinc-300 mb-2">اسم المؤلف / الكاتب</label>
                    <input type="text" name="author_name" value="Musoftware" placeholder="مثال: م/ محمود السيد" class="w-full h-12 px-4 rounded-xl bg-zinc-900 border border-zinc-700 text-sm text-white focus:outline-none focus:border-brand-500">
                </div>

                <!-- Publisher & Year -->
                <div>
                    <label class="block text-xs font-bold text-zinc-300 mb-2">الناشر</label>
                    <input type="text" name="publisher" value="Musoftware Publications" class="w-full h-12 px-4 rounded-xl bg-zinc-900 border border-zinc-700 text-sm text-white focus:outline-none focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-300 mb-2">سنة النشر</label>
                    <input type="text" name="publication_year" value="{{ date('Y') }}" class="w-full h-12 px-4 rounded-xl bg-zinc-900 border border-zinc-700 text-sm text-white focus:outline-none focus:border-brand-500">
                </div>

                <!-- Short Description -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-zinc-300 mb-2">وصف مختصر (يظهر في بطاقة الكتاب والسيو)</label>
                    <textarea name="short_description" rows="2" placeholder="نبذة سريعة في سطرين عن محتوى الكتاب..." class="w-full p-4 rounded-xl bg-zinc-900 border border-zinc-700 text-sm text-white focus:outline-none focus:border-brand-500"></textarea>
                </div>

                <!-- Full Description -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-zinc-300 mb-2">الوصف الكامل والفهرس</label>
                    <textarea name="description" rows="5" placeholder="تفاصيل الكتاب، المحاور الرئيسية، ما سيتعلمه القارئ..." class="w-full p-4 rounded-xl bg-zinc-900 border border-zinc-700 text-sm text-white focus:outline-none focus:border-brand-500"></textarea>
                </div>

            </div>
        </div>

        <!-- 3. Publishing Options & Submit -->
        <div class="rounded-3xl glass-card p-6 border border-zinc-800 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-6">
                <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-zinc-300">
                    <input type="checkbox" name="is_published" value="1" checked class="w-4 h-4 rounded text-brand-600 bg-zinc-900 border-zinc-700 focus:ring-brand-500">
                    <span>نشر الكتاب مباشرة في المعرض</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-zinc-300">
                    <input type="checkbox" name="is_featured" value="1" class="w-4 h-4 rounded text-brand-600 bg-zinc-900 border-zinc-700 focus:ring-brand-500">
                    <span>تمييز كإصدار مميز (Featured)</span>
                </label>
            </div>

            <button type="submit" id="submitBtn" class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-sm shadow-xl shadow-brand-500/30 transition-all flex items-center justify-center gap-2">
                <i class="ri-check-line text-lg"></i>
                <span>حفظ ونشر الكتاب</span>
            </button>
        </div>
    </form>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById('pdfFileInput');
    const dropZoneInitial = document.getElementById('dropZoneInitial');
    const dropZoneLoading = document.getElementById('dropZoneLoading');
    const dropZoneSuccess = document.getElementById('dropZoneSuccess');
    const coverPreviewImg = document.getElementById('coverPreviewImg');
    const coverDataInput = document.getElementById('coverDataInput');
    const titleInput = document.getElementById('bookTitleInput');
    const pageCountInput = document.getElementById('bookPageCountInput');
    const extractedFileName = document.getElementById('extractedFileName');
    const extractedPagesCount = document.getElementById('extractedPagesCount');
    const extractedFileSize = document.getElementById('extractedFileSize');
    const canvas = document.getElementById('pdfRenderCanvas');

    fileInput.addEventListener('change', async (e) => {
        const file = e.target.files[0];
        if (!file || file.type !== 'application/pdf') {
            return;
        }

        // Show loading state
        dropZoneInitial.classList.add('hidden');
        dropZoneSuccess.classList.add('hidden');
        dropZoneLoading.classList.remove('hidden');

        try {
            // Auto fill file name and size
            const sizeInMb = (file.size / (1024 * 1024)).toFixed(2);
            extractedFileName.textContent = file.name;
            extractedFileSize.textContent = `${sizeInMb} MB`;

            // Auto suggest title from filename if title is empty
            if (!titleInput.value.trim()) {
                const cleanName = file.name
                    .replace(/\.[^/.]+$/, '') // remove extension
                    .replace(/^\d+[\s_-]*/, '') // remove leading numbers like 01-
                    .replace(/[-_]+/g, ' ') // replace dashes/underscores with spaces
                    .trim();
                titleInput.value = cleanName;
            }

            // Read PDF with PDF.js
            const arrayBuffer = await file.arrayBuffer();
            const loadingTask = pdfjsLib.getDocument({ data: arrayBuffer });
            const pdf = await loadingTask.promise;

            // Extract total pages
            const numPages = pdf.numPages;
            pageCountInput.value = numPages;
            extractedPagesCount.textContent = numPages;

            // Render Page 1 for Cover
            const page = await pdf.getPage(1);
            const scale = 1.5; // High resolution scale
            const viewport = page.getViewport({ scale: scale });

            canvas.height = viewport.height;
            canvas.width = viewport.width;
            const ctx = canvas.getContext('2d');

            const renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };

            await page.render(renderContext).promise;

            // Convert canvas to WebP data URL
            const coverDataUrl = canvas.toDataURL('image/webp', 0.92);
            coverDataInput.value = coverDataUrl;
            coverPreviewImg.src = coverDataUrl;

            // Show Success State
            dropZoneLoading.classList.add('hidden');
            dropZoneSuccess.classList.remove('hidden');

        } catch (error) {
            console.error('Error processing PDF:', error);
            dropZoneLoading.classList.add('hidden');
            dropZoneInitial.classList.remove('hidden');
            alert('حدث خطأ أثناء معالجة ملف الـ PDF. يرجى التأكد من صحة الملف والمحاولة مجدداً.');
        }
    });
});
</script>
@endpush
@endsection

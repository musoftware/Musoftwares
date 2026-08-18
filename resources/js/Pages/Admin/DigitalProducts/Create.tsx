import React, { useState, useRef, useEffect } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';
import { Card, CardContent } from '@/Components/ui/card';
import { Switch } from '@/Components/ui/switch';
import { PremiumCombobox } from '@/Components/ui/PremiumCombobox';
import {
    ArrowLeft,
    BookOpen,
    CheckCircle2,
    FileText,
    Gift,
    HardDrive,
    Layers,
    Loader2,
    Plus,
    Sparkles,
    UploadCloud,
    X,
    FileCode,
} from 'lucide-react';
import { toast } from 'sonner';
import { __ } from '@/lib/i18n';

interface Category {
    id: number;
    name: string;
}

interface Props {
    categories: Category[];
}

export default function Create({ categories }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        slug: '',
        category_id: '',
        price: '0.00',
        is_free: true,
        author_name: '',
        publisher: 'Musoftware',
        publication_year: String(new Date().getFullYear()),
        language: 'ar',
        page_count: '',
        short_description: '',
        description: '',
        is_published: true,
        is_featured: false,
        meta_title: '',
        meta_description: '',

        // Main Files & Extracted Cover
        pdf_file: null as File | null,
        cover_data: '', // base64 data url from client-side PDF.js
        cover_image: null as File | null,

        // Dual Edition (Playbook)
        has_free_edition: false,
        free_edition_title: 'Playbook Edition (الملخص التطبيقي)',
        free_edition_pdf_file: null as File | null,
        free_edition_cover_data: '',
        free_edition_cover_image: null as File | null,
        free_edition_page_count: '',
    });

    // PDF.js State for Main PDF
    const [mainPdfLoading, setMainPdfLoading] = useState(false);
    const [mainPdfInfo, setMainPdfInfo] = useState<{
        fileName: string;
        fileSize: string;
        pages: number;
        coverPreview: string;
    } | null>(null);

    // PDF.js State for Playbook PDF
    const [playbookPdfLoading, setPlaybookPdfLoading] = useState(false);
    const [playbookPdfInfo, setPlaybookPdfInfo] = useState<{
        fileName: string;
        fileSize: string;
        pages: number;
        coverPreview: string;
    } | null>(null);

    const mainFileInputRef = useRef<HTMLInputElement>(null);
    const playbookFileInputRef = useRef<HTMLInputElement>(null);

    // Dynamically load PDF.js from CDN
    const getPdfJs = async () => {
        if ((window as any).pdfjsLib) {
            return (window as any).pdfjsLib;
        }
        return new Promise<any>((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';
            script.onload = () => {
                const lib = (window as any).pdfjsLib;
                lib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                resolve(lib);
            };
            script.onerror = reject;
            document.head.appendChild(script);
        });
    };

    // Extract Cover & Metadata from PDF
    const processPdfFile = async (file: File, isPlaybook = false) => {
        if (!file || file.type !== 'application/pdf') {
            toast.error(__('general.please_select_valid_pdf') || 'Please select a valid PDF file.');
            return;
        }

        if (isPlaybook) {
            setPlaybookPdfLoading(true);
            setData('free_edition_pdf_file', file);
        } else {
            setMainPdfLoading(true);
            setData('pdf_file', file);

            // Auto fill title if empty
            if (!data.title) {
                const cleanName = file.name
                    .replace(/\.[^/.]+$/, '')
                    .replace(/^\d+[\s_-]*/, '')
                    .replace(/[-_]+/g, ' ')
                    .trim();
                setData('title', cleanName);
            }
        }

        try {
            const pdfjsLib = await getPdfJs();
            const arrayBuffer = await file.arrayBuffer();
            const loadingTask = pdfjsLib.getDocument({ data: arrayBuffer });
            const pdf = await loadingTask.promise;

            const numPages = pdf.numPages;
            const fileSizeMb = (file.size / (1024 * 1024)).toFixed(2) + ' MB';

            // Render Page 1
            const page = await pdf.getPage(1);
            const scale = 1.5;
            const viewport = page.getViewport({ scale });

            const canvas = document.createElement('canvas');
            canvas.height = viewport.height;
            canvas.width = viewport.width;
            const ctx = canvas.getContext('2d');

            if (ctx) {
                await page.render({ canvasContext: ctx, viewport }).promise;
                const coverDataUrl = canvas.toDataURL('image/webp', 0.92);

                if (isPlaybook) {
                    setData((prev) => ({
                        ...prev,
                        free_edition_cover_data: coverDataUrl,
                        free_edition_page_count: String(numPages),
                    }));
                    setPlaybookPdfInfo({
                        fileName: file.name,
                        fileSize: fileSizeMb,
                        pages: numPages,
                        coverPreview: coverDataUrl,
                    });
                } else {
                    setData((prev) => ({
                        ...prev,
                        cover_data: coverDataUrl,
                        page_count: String(numPages),
                    }));
                    setMainPdfInfo({
                        fileName: file.name,
                        fileSize: fileSizeMb,
                        pages: numPages,
                        coverPreview: coverDataUrl,
                    });
                }
                toast.success(__('general.pdf_processed_successfully') || 'PDF processed and cover extracted!');
            }
        } catch (error) {
            console.error('PDF parsing error:', error);
            toast.error(__('general.failed_to_extract_pdf') || 'Could not extract PDF cover automatically, but file was attached.');
        } finally {
            if (isPlaybook) {
                setPlaybookPdfLoading(false);
            } else {
                setMainPdfLoading(false);
            }
        }
    };

    const handleMainFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            processPdfFile(file, false);
        }
    };

    const handlePlaybookFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            processPdfFile(file, true);
        }
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!data.pdf_file) {
            toast.error(__('general.pdf_file_is_required') || 'Please upload the main PDF book.');
            return;
        }

        post(route('admin.digitalproducts.store'), {
            forceFormData: true,
            preserveScroll: true,
            onError: (errs) => {
                const firstErr = Object.values(errs)[0];
                if (firstErr) toast.error(String(firstErr));
            },
        });
    };

    const categoryOptions = [
        { value: '', label: __('general.select_category') || 'Select Category' },
        ...categories.map((c) => ({
            value: String(c.id),
            label: c.name,
        })),
    ];

    return (
        <AdminSidebarLayout
            title={__('general.upload_new_book') || 'Upload New Digital Book (PDF)'}
            header={
                <div className="flex items-center gap-2">
                    <Link href={route('admin.digitalproducts.index')} className="text-slate-500 hover:text-slate-900 transition-colors">
                        <ArrowLeft className="h-4 w-4" />
                    </Link>
                    <BookOpen className="h-5 w-5 text-slate-700" />
                    <span>{__('general.upload_new_book') || 'Upload New Digital Book (PDF)'}</span>
                </div>
            }
        >
            <Head title={__('general.upload_new_book') || 'Upload Book'} />

            <div className="max-w-5xl mx-auto">
                <form onSubmit={submit} className="space-y-6">

                    {/* 1. Main PDF Dropzone Card */}
                    <Card className="border border-slate-200 shadow-sm bg-white overflow-hidden">
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between mb-3">
                                <Label className="text-sm font-bold text-slate-900 flex items-center gap-2">
                                    <FileText className="h-4 w-4 text-blue-600" />
                                    <span>{__('general.main_pdf_file') || 'Main Book PDF File'} <span className="text-red-500">*</span></span>
                                </Label>
                                <span className="text-xs text-slate-400 font-medium">Max 150MB</span>
                            </div>

                            <input
                                type="file"
                                ref={mainFileInputRef}
                                onChange={handleMainFileChange}
                                accept="application/pdf"
                                className="hidden"
                            />

                            {!mainPdfInfo && !mainPdfLoading && (
                                <div
                                    onClick={() => mainFileInputRef.current?.click()}
                                    className="border-2 border-dashed border-slate-300 hover:border-slate-800 rounded-2xl p-8 text-center cursor-pointer transition-all bg-slate-50/50 hover:bg-slate-50 group"
                                >
                                    <div className="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                                        <UploadCloud className="h-7 w-7" />
                                    </div>
                                    <h4 className="text-sm font-bold text-slate-900 mb-1">
                                        {__('general.drag_or_click_pdf') || 'Click to select or drag & drop PDF book'}
                                    </h4>
                                    <p className="text-xs text-slate-500 max-w-md mx-auto mb-3">
                                        {__('general.pdf_auto_extraction_desc') || 'The cover thumbnail, total page count, and file size will be extracted instantly.'}
                                    </p>
                                    <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white border border-slate-200 text-slate-700 shadow-xs">
                                        <Sparkles className="h-3.5 w-3.5 text-blue-600" />
                                        <span>Instant Auto Cover & Pages Extraction</span>
                                    </span>
                                </div>
                            )}

                            {mainPdfLoading && (
                                <div className="border border-slate-200 rounded-2xl p-8 text-center bg-slate-50 flex flex-col items-center justify-center">
                                    <Loader2 className="h-8 w-8 text-blue-600 animate-spin mb-3" />
                                    <p className="text-sm font-bold text-slate-900">
                                        {__('general.processing_pdf') || 'Analyzing PDF and extracting cover...'}
                                    </p>
                                    <p className="text-xs text-slate-500 mt-1">Please wait a moment</p>
                                </div>
                            )}

                            {mainPdfInfo && !mainPdfLoading && (
                                <div className="border border-slate-200 rounded-2xl p-4 bg-slate-50 flex flex-col sm:flex-row items-center gap-4">
                                    <div className="w-20 h-28 rounded-lg bg-slate-900 border border-slate-300 overflow-hidden flex-shrink-0 relative shadow-sm">
                                        <img
                                            src={mainPdfInfo.coverPreview}
                                            alt="Cover"
                                            className="w-full h-full object-cover"
                                        />
                                        <span className="absolute bottom-0 inset-x-0 bg-slate-900/80 text-[9px] text-center text-emerald-400 font-bold py-0.5">
                                            ✓ Extracted
                                        </span>
                                    </div>

                                    <div className="flex-1 min-w-0 text-center sm:text-start">
                                        <div className="flex items-center gap-1.5 text-emerald-600 text-xs font-bold mb-1 justify-center sm:justify-start">
                                            <CheckCircle2 className="h-4 w-4" />
                                            <span>{__('general.pdf_attached_ready') || 'PDF analyzed & ready'}</span>
                                        </div>
                                        <h4 className="text-sm font-bold text-slate-900 truncate mb-2">{mainPdfInfo.fileName}</h4>
                                        <div className="flex flex-wrap items-center gap-2 text-xs">
                                            <span className="px-2.5 py-0.5 rounded-md bg-white border border-slate-200 font-medium text-slate-700">
                                                {mainPdfInfo.pages} {__('general.pages') || 'Pages'}
                                            </span>
                                            <span className="px-2.5 py-0.5 rounded-md bg-white border border-slate-200 font-medium text-slate-700">
                                                {mainPdfInfo.fileSize}
                                            </span>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                className="h-7 text-xs text-blue-600 hover:text-blue-700 hover:bg-blue-50"
                                                onClick={() => mainFileInputRef.current?.click()}
                                            >
                                                {__('general.change_file') || 'Change File'}
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {errors.pdf_file && <p className="text-xs text-red-600 mt-2">{errors.pdf_file}</p>}
                        </CardContent>
                    </Card>

                    {/* 2. Dual Edition (Playbook Edition) Card */}
                    <Card className="border border-slate-200 shadow-sm bg-white">
                        <CardContent className="p-6 space-y-4">
                            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                                <div>
                                    <h3 className="text-sm font-bold text-slate-900 flex items-center gap-2">
                                        <Gift className="h-4 w-4 text-emerald-600" />
                                        <span>{__('general.dual_edition_playbook') || 'Dual Edition (Free Playbook / Summary Edition)'}</span>
                                    </h3>
                                    <p className="text-xs text-slate-500 mt-0.5">
                                        {__('general.dual_edition_desc') || 'Attach an optional free summary edition to attract visitors alongside the full book.'}
                                    </p>
                                </div>
                                <Switch
                                    checked={data.has_free_edition}
                                    onCheckedChange={(checked) => setData('has_free_edition', checked)}
                                />
                            </div>

                            {data.has_free_edition && (
                                <div className="space-y-4 pt-2">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div className="space-y-1.5">
                                            <Label className="text-xs font-semibold text-slate-700">
                                                {__('general.playbook_title') || 'Playbook Title'}
                                            </Label>
                                            <Input
                                                value={data.free_edition_title}
                                                onChange={(e) => setData('free_edition_title', e.target.value)}
                                                className="h-9 text-xs"
                                                placeholder="Playbook Edition (ملخص تطبيقي)"
                                            />
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label className="text-xs font-semibold text-slate-700">
                                                {__('general.playbook_page_count') || 'Playbook Pages'}
                                            </Label>
                                            <Input
                                                type="number"
                                                value={data.free_edition_page_count}
                                                onChange={(e) => setData('free_edition_page_count', e.target.value)}
                                                className="h-9 text-xs"
                                                placeholder="e.g. 20"
                                            />
                                        </div>
                                    </div>

                                    {/* Playbook Dropzone */}
                                    <div className="space-y-1.5">
                                        <Label className="text-xs font-semibold text-slate-700">
                                            {__('general.playbook_pdf_file') || 'Playbook PDF File'}
                                        </Label>
                                        <input
                                            type="file"
                                            ref={playbookFileInputRef}
                                            onChange={handlePlaybookFileChange}
                                            accept="application/pdf"
                                            className="hidden"
                                        />

                                        {!playbookPdfInfo && !playbookPdfLoading && (
                                            <div
                                                onClick={() => playbookFileInputRef.current?.click()}
                                                className="border-2 border-dashed border-slate-200 hover:border-emerald-500 rounded-xl p-5 text-center cursor-pointer transition-colors bg-slate-50/50 hover:bg-emerald-50/20"
                                            >
                                                <UploadCloud className="h-6 w-6 text-emerald-600 mx-auto mb-1" />
                                                <p className="text-xs font-bold text-slate-800">
                                                    {__('general.click_to_upload_playbook_pdf') || 'Click to select Playbook PDF'}
                                                </p>
                                                <p className="text-[11px] text-slate-400 mt-0.5">Cover thumbnail will be generated automatically</p>
                                            </div>
                                        )}

                                        {playbookPdfLoading && (
                                            <div className="border border-slate-200 rounded-xl p-5 text-center bg-slate-50 flex items-center justify-center gap-2">
                                                <Loader2 className="h-4 w-4 text-emerald-600 animate-spin" />
                                                <span className="text-xs font-semibold text-slate-700">Analyzing Playbook PDF...</span>
                                            </div>
                                        )}

                                        {playbookPdfInfo && !playbookPdfLoading && (
                                            <div className="border border-emerald-200 rounded-xl p-3 bg-emerald-50/40 flex items-center gap-3">
                                                <div className="w-12 h-16 rounded bg-slate-900 border border-emerald-300 overflow-hidden flex-shrink-0 shadow-xs">
                                                    <img src={playbookPdfInfo.coverPreview} alt="Playbook Cover" className="w-full h-full object-cover" />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <h5 className="text-xs font-bold text-slate-900 truncate">{playbookPdfInfo.fileName}</h5>
                                                    <p className="text-[11px] text-slate-500">{playbookPdfInfo.pages} pages • {playbookPdfInfo.fileSize}</p>
                                                </div>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    className="h-7 text-xs text-emerald-700 hover:bg-emerald-100"
                                                    onClick={() => playbookFileInputRef.current?.click()}
                                                >
                                                    {__('general.change') || 'Change'}
                                                </Button>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* 3. Core Book Details */}
                    <Card className="border border-slate-200 shadow-sm bg-white">
                        <CardContent className="p-6 space-y-5">
                            <h3 className="text-sm font-bold text-slate-900 flex items-center gap-2 pb-2 border-b border-slate-100">
                                <BookOpen className="h-4 w-4 text-slate-700" />
                                <span>{__('general.book_details') || 'Book Details'}</span>
                            </h3>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-1.5 md:col-span-2">
                                    <Label className="text-xs font-semibold text-slate-700">
                                        {__('general.book_title') || 'Book Title'} <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        value={data.title}
                                        onChange={(e) => setData('title', e.target.value)}
                                        className="h-10 text-xs font-semibold"
                                        placeholder="e.g. Master Clean Architecture in Laravel"
                                        required
                                    />
                                    {errors.title && <p className="text-xs text-red-600">{errors.title}</p>}
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold text-slate-700">
                                        {__('general.category') || 'Category'}
                                    </Label>
                                    <PremiumCombobox
                                        value={data.category_id}
                                        onChange={(val) => setData('category_id', String(val || ''))}
                                        options={categoryOptions}
                                        placeholder={__('general.select_category') || 'Select Category'}
                                    />
                                    {errors.category_id && <p className="text-xs text-red-600">{errors.category_id}</p>}
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold text-slate-700">
                                        {__('general.slug') || 'URL Slug (Optional)'}
                                    </Label>
                                    <Input
                                        value={data.slug}
                                        onChange={(e) => setData('slug', e.target.value)}
                                        className="h-10 text-xs font-mono"
                                        placeholder="leave blank for auto-generation"
                                    />
                                    {errors.slug && <p className="text-xs text-red-600">{errors.slug}</p>}
                                </div>

                                {/* Pricing */}
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold text-slate-700">
                                        {__('general.pricing') || 'Pricing'}
                                    </Label>
                                    <div className="flex items-center gap-3">
                                        <label className="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-800">
                                            <input
                                                type="checkbox"
                                                checked={data.is_free}
                                                onChange={(e) => {
                                                    const checked = e.target.checked;
                                                    setData((prev) => ({
                                                        ...prev,
                                                        is_free: checked,
                                                        price: checked ? '0.00' : prev.price || '9.99',
                                                    }));
                                                }}
                                                className="rounded border-slate-300 text-blue-600"
                                            />
                                            <span>{__('general.free_book') || 'Free Book'}</span>
                                        </label>

                                        {!data.is_free && (
                                            <div className="flex-1 relative">
                                                <Input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    value={data.price}
                                                    onChange={(e) => setData('price', e.target.value)}
                                                    className="h-10 text-xs font-bold pe-12"
                                                    placeholder="Price"
                                                />
                                                <span className="absolute end-3 top-2.5 text-xs text-slate-400 font-bold">$ USD</span>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold text-slate-700">
                                        {__('general.author_name') || 'Author Name'}
                                    </Label>
                                    <Input
                                        value={data.author_name}
                                        onChange={(e) => setData('author_name', e.target.value)}
                                        className="h-10 text-xs"
                                        placeholder="e.g. John Doe"
                                    />
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold text-slate-700">
                                        {__('general.publisher') || 'Publisher'}
                                    </Label>
                                    <Input
                                        value={data.publisher}
                                        onChange={(e) => setData('publisher', e.target.value)}
                                        className="h-10 text-xs"
                                    />
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold text-slate-700">
                                        {__('general.publication_year') || 'Year & Language'}
                                    </Label>
                                    <div className="grid grid-cols-2 gap-2">
                                        <Input
                                            value={data.publication_year}
                                            onChange={(e) => setData('publication_year', e.target.value)}
                                            className="h-10 text-xs"
                                            placeholder="Year"
                                        />
                                        <Input
                                            value={data.language}
                                            onChange={(e) => setData('language', e.target.value)}
                                            className="h-10 text-xs"
                                            placeholder="Lang (ar/en)"
                                        />
                                    </div>
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold text-slate-700">
                                        {__('general.page_count') || 'Page Count'}
                                    </Label>
                                    <Input
                                        type="number"
                                        value={data.page_count}
                                        onChange={(e) => setData('page_count', e.target.value)}
                                        className="h-10 text-xs"
                                        placeholder="Extracted automatically from PDF"
                                    />
                                </div>

                                <div className="space-y-1.5 md:col-span-2">
                                    <Label className="text-xs font-semibold text-slate-700">
                                        {__('general.short_description') || 'Short Summary / Teaser'}
                                    </Label>
                                    <Textarea
                                        value={data.short_description}
                                        onChange={(e) => setData('short_description', e.target.value)}
                                        rows={2}
                                        className="text-xs"
                                        placeholder="A brief punchy summary shown in cards and previews..."
                                    />
                                </div>

                                <div className="space-y-1.5 md:col-span-2">
                                    <Label className="text-xs font-semibold text-slate-700">
                                        {__('general.full_description') || 'Full Book Description'}
                                    </Label>
                                    <Textarea
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        rows={5}
                                        className="text-xs"
                                        placeholder="Comprehensive overview of chapters, key takeaways, who this book is for..."
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* 4. Publishing & SEO Settings */}
                    <Card className="border border-slate-200 shadow-sm bg-white">
                        <CardContent className="p-6 space-y-4">
                            <h3 className="text-sm font-bold text-slate-900 flex items-center gap-2 pb-2 border-b border-slate-100">
                                <Sparkles className="h-4 w-4 text-slate-700" />
                                <span>{__('general.publishing_and_seo') || 'Publishing & SEO'}</span>
                            </h3>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200">
                                    <div>
                                        <h4 className="text-xs font-bold text-slate-900">{__('general.publish_immediately') || 'Publish Immediately'}</h4>
                                        <p className="text-[11px] text-slate-500">Visible in public library gallery</p>
                                    </div>
                                    <Switch
                                        checked={data.is_published}
                                        onCheckedChange={(checked) => setData('is_published', checked)}
                                    />
                                </div>

                                <div className="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200">
                                    <div>
                                        <h4 className="text-xs font-bold text-slate-900">{__('general.featured_book') || 'Featured Book'}</h4>
                                        <p className="text-[11px] text-slate-500">Highlighted on top of library and home</p>
                                    </div>
                                    <Switch
                                        checked={data.is_featured}
                                        onCheckedChange={(checked) => setData('is_featured', checked)}
                                    />
                                </div>

                                <div className="space-y-1.5 md:col-span-2">
                                    <Label className="text-xs font-semibold text-slate-700">
                                        {__('general.meta_title') || 'Meta SEO Title'}
                                    </Label>
                                    <Input
                                        value={data.meta_title}
                                        onChange={(e) => setData('meta_title', e.target.value)}
                                        className="h-9 text-xs"
                                        placeholder="Defaults to book title"
                                    />
                                </div>

                                <div className="space-y-1.5 md:col-span-2">
                                    <Label className="text-xs font-semibold text-slate-700">
                                        {__('general.meta_description') || 'Meta SEO Description'}
                                    </Label>
                                    <Textarea
                                        value={data.meta_description}
                                        onChange={(e) => setData('meta_description', e.target.value)}
                                        rows={2}
                                        className="text-xs"
                                        placeholder="Defaults to short description"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Submit Bar */}
                    <div className="flex items-center justify-end gap-3 pt-2">
                        <Link href={route('admin.digitalproducts.index')}>
                            <Button type="button" variant="outline" className="h-10 px-5 text-xs">
                                {__('general.cancel') || 'Cancel'}
                            </Button>
                        </Link>

                        <Button
                            type="submit"
                            disabled={processing || mainPdfLoading || playbookPdfLoading}
                            className="h-10 px-6 text-xs font-bold bg-slate-900 hover:bg-slate-800 text-white shadow-sm gap-2"
                        >
                            {processing ? (
                                <>
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                    <span>{__('general.uploading_book') || 'Uploading Book...'}</span>
                                </>
                            ) : (
                                <>
                                    <Plus className="h-4 w-4" />
                                    <span>{__('general.save_and_publish_book') || 'Save & Upload Book'}</span>
                                </>
                            )}
                        </Button>
                    </div>
                </form>
            </div>
        </AdminSidebarLayout>
    );
}

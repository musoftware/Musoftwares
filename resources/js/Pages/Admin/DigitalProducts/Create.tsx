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
    Check,
    CheckCircle2,
    FileText,
    Gift,
    Loader2,
    Plus,
    Sparkles,
    UploadCloud,
} from 'lucide-react';
import { toast } from 'sonner';
import { __ } from '@/lib/i18n';

interface Category {
    id: number;
    name: string;
}

interface CurrencyItem {
    id: number;
    currency: string;
    symbol: string;
}

interface Props {
    categories: Category[];
    currencies?: CurrencyItem[];
}

export default function Create({ categories, currencies = [] }: Props) {
    const defaultCurrencyId = currencies.find(c => c.currency === 'USD')?.id || currencies[0]?.id || 1;

    const { data, setData, post, processing, errors } = useForm({
        title: '',
        slug: '',
        category_id: '',
        price: '0.00',
        currency_id: String(defaultCurrencyId),
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
        free_edition_title: __('admin.digital_products_default_playbook_title'),
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
            toast.error(__('general.please_select_valid_pdf'));
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
                toast.success(__('general.pdf_processed_successfully'));
            }
        } catch (error) {
            console.error('PDF parsing error:', error);
            toast.error(__('general.failed_to_extract_pdf'));
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
            toast.error(__('general.pdf_file_is_required'));
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
        { value: '', label: __('general.select_category') },
        ...categories.map((c) => ({
            value: String(c.id),
            label: c.name,
        })),
    ];

    return (
        <AdminSidebarLayout
            title={__('general.upload_new_book')}
            header={
                <div className="flex items-center gap-2">
                    <Link href={route('admin.digitalproducts.index')} className="text-slate-500 hover:text-slate-900 transition-colors" aria-label={__('general.back')}>
                        <ArrowLeft className="h-4 w-4 rtl:rotate-180" />
                    </Link>
                    <BookOpen className="h-5 w-5 text-slate-700" />
                    <span>{__('general.upload_new_book')}</span>
                </div>
            }
        >
            <Head title={__('general.upload_new_book')} />

            <div className="max-w-5xl mx-auto">
                <form onSubmit={submit} className="space-y-6">

                    {/* 1. Main PDF Dropzone Card */}
                    <Card className="border border-slate-200 shadow-sm bg-white overflow-hidden">
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between mb-3">
                                <Label className="text-sm font-bold text-slate-900 flex items-center gap-2">
                                    <FileText className="h-4 w-4 text-blue-600" />
                                    <span>{__('general.main_pdf_file')} <span className="text-red-500">*</span></span>
                                </Label>
                                <span className="text-xs text-slate-400 font-medium">{__('admin.digital_products_max_pdf_size')}</span>
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
                                        {__('general.drag_or_click_pdf')}
                                    </h4>
                                    <p className="text-xs text-slate-500 max-w-md mx-auto mb-3">
                                        {__('general.pdf_auto_extraction_desc')}
                                    </p>
                                    <span className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-700">
                                        <Sparkles className="h-3.5 w-3.5 text-blue-600" />
                                        <span>{__('admin.digital_products_auto_extraction_hint')}</span>
                                    </span>
                                </div>
                            )}

                            {mainPdfLoading && (
                                <div className="border border-slate-200 rounded-2xl p-8 text-center bg-slate-50 flex flex-col items-center justify-center">
                                    <Loader2 className="h-8 w-8 text-blue-600 animate-spin mb-3" />
                                    <p className="text-sm font-bold text-slate-900">
                                        {__('general.processing_pdf')}
                                    </p>
                                    <p className="text-xs text-slate-500 mt-1">{__('admin.digital_products_please_wait')}</p>
                                </div>
                            )}

                            {mainPdfInfo && !mainPdfLoading && (
                                <div className="border border-slate-200 rounded-2xl p-4 bg-slate-50 flex flex-col sm:flex-row items-center gap-4">
                                    <div className="w-20 h-28 rounded-lg bg-slate-900 border border-slate-300 overflow-hidden flex-shrink-0 relative shadow-sm">
                                        <img
                                            src={mainPdfInfo.coverPreview}
                                            alt={__('admin.digital_products_cover_alt')}
                                            className="w-full h-full object-cover"
                                        />
                                        <span className="absolute bottom-0 inset-x-0 bg-slate-900/80 text-[9px] text-emerald-400 font-bold py-0.5 flex items-center justify-center gap-0.5">
                                            <Check className="h-2.5 w-2.5" aria-hidden="true" />
                                            <span>{__('general.extracted')}</span>
                                        </span>
                                    </div>

                                    <div className="flex-1 min-w-0 text-center sm:text-start">
                                        <div className="flex items-center gap-1.5 text-emerald-600 text-xs font-bold mb-1 justify-center sm:justify-start">
                                            <CheckCircle2 className="h-4 w-4" />
                                            <span>{__('general.pdf_attached_ready')}</span>
                                        </div>
                                        <h4 className="text-sm font-bold text-slate-900 truncate mb-2">{mainPdfInfo.fileName}</h4>
                                        <div className="flex flex-wrap items-center gap-2 text-xs">
                                            <span className="px-2.5 py-0.5 rounded-md bg-white border border-slate-200 font-medium text-slate-700">
                                                {mainPdfInfo.pages} {__('general.pages')}
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
                                                {__('general.change_file')}
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
                                        <span>{__('general.dual_edition_playbook')}</span>
                                    </h3>
                                    <p className="text-xs text-slate-500 mt-0.5">
                                        {__('general.dual_edition_desc')}
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
                                                {__('general.playbook_title')}
                                            </Label>
                                            <Input
                                                value={data.free_edition_title}
                                                onChange={(e) => setData('free_edition_title', e.target.value)}
                                                className="h-9 text-xs"
                                                placeholder={__('admin.digital_products_default_playbook_title')}
                                            />
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label className="text-xs font-semibold text-slate-700">
                                                {__('general.playbook_page_count')}
                                            </Label>
                                            <Input
                                                type="number"
                                                value={data.free_edition_page_count}
                                                onChange={(e) => setData('free_edition_page_count', e.target.value)}
                                                className="h-9 text-xs"
                                                placeholder={__('admin.digital_products_playbook_pages_placeholder')}
                                            />
                                        </div>
                                    </div>

                                    {/* Playbook Dropzone */}
                                    <div className="space-y-1.5">
                                        <Label className="text-xs font-semibold text-slate-700">
                                            {__('general.playbook_pdf_file')}
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
                                                    {__('general.click_to_upload_playbook_pdf')}
                                                </p>
                                                <p className="text-[11px] text-slate-400 mt-0.5">{__('admin.digital_products_cover_auto_generated')}</p>
                                            </div>
                                        )}

                                        {playbookPdfLoading && (
                                            <div className="border border-slate-200 rounded-xl p-5 text-center bg-slate-50 flex items-center justify-center gap-2">
                                                <Loader2 className="h-4 w-4 text-emerald-600 animate-spin" />
                                                <span className="text-xs font-semibold text-slate-700">{__('admin.digital_products_analyzing_playbook')}</span>
                                            </div>
                                        )}

                                        {playbookPdfInfo && !playbookPdfLoading && (
                                            <div className="border border-emerald-200 rounded-xl p-3 bg-emerald-50/40 flex items-center gap-3">
                                                <div className="w-12 h-16 rounded bg-slate-900 border border-emerald-300 overflow-hidden flex-shrink-0 shadow-xs">
                                                    <img src={playbookPdfInfo.coverPreview} alt={__('admin.digital_products_playbook_cover_alt')} className="w-full h-full object-cover" />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <h5 className="text-xs font-bold text-slate-900 truncate">{playbookPdfInfo.fileName}</h5>
                                                    <p className="text-[11px] text-slate-500">{__('admin.digital_products_pages_and_size', { pages: playbookPdfInfo.pages, size: playbookPdfInfo.fileSize })}</p>
                                                </div>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    className="h-7 text-xs text-emerald-700 hover:bg-emerald-100"
                                                    onClick={() => playbookFileInputRef.current?.click()}
                                                >
                                                    {__('general.change')}
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
                                <span>{__('general.book_details')}</span>
                            </h3>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-1.5 md:col-span-2">
                                    <Label className="text-xs font-semibold text-slate-700">
                                        {__('general.book_title')} <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        value={data.title}
                                        onChange={(e) => setData('title', e.target.value)}
                                        className="h-10 text-xs font-semibold"
                                        placeholder={__('admin.digital_products_title_placeholder')}
                                        required
                                    />
                                    {errors.title && <p className="text-xs text-red-600">{errors.title}</p>}
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold text-slate-700">
                                        {__('general.category')}
                                    </Label>
                                    <PremiumCombobox
                                        value={data.category_id}
                                        onChange={(val) => setData('category_id', String(val || ''))}
                                        options={categoryOptions}
                                        placeholder={__('general.select_category')}
                                    />
                                    {errors.category_id && <p className="text-xs text-red-600">{errors.category_id}</p>}
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold text-slate-700">
                                        {__('general.slug')}
                                    </Label>
                                    <Input
                                        value={data.slug}
                                        onChange={(e) => setData('slug', e.target.value)}
                                        className="h-10 text-xs font-mono"
                                        placeholder={__('admin.digital_products_slug_placeholder')}
                                    />
                                    {errors.slug && <p className="text-xs text-red-600">{errors.slug}</p>}
                                </div>

                                {/* Pricing */}
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold text-slate-700">
                                        {__('general.pricing')}
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
                                            <span>{__('general.free_book')}</span>
                                        </label>

                                        {!data.is_free && (
                                            <div className="flex-1 flex items-center gap-2">
                                                <Input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    value={data.price}
                                                    onChange={(e) => setData('price', e.target.value)}
                                                    className="h-10 text-xs font-bold flex-1"
                                                    placeholder={__('general.price')}
                                                />
                                                {currencies.length > 0 ? (
                                                    <select
                                                        value={data.currency_id}
                                                        onChange={(e) => setData('currency_id', e.target.value)}
                                                        className="h-10 px-2.5 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                                    >
                                                        {currencies.map((c) => (
                                                            <option key={c.id} value={c.id}>
                                                                {c.currency} ({c.symbol})
                                                            </option>
                                                        ))}
                                                    </select>
                                                ) : (
                                                    <span className="text-xs text-slate-400 font-bold px-2">USD</span>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold text-slate-700">
                                        {__('general.author_name')}
                                    </Label>
                                    <Input
                                        value={data.author_name}
                                        onChange={(e) => setData('author_name', e.target.value)}
                                        className="h-10 text-xs"
                                        placeholder={__('admin.digital_products_author_placeholder')}
                                    />
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold text-slate-700">
                                        {__('general.publisher')}
                                    </Label>
                                    <Input
                                        value={data.publisher}
                                        onChange={(e) => setData('publisher', e.target.value)}
                                        className="h-10 text-xs"
                                    />
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold text-slate-700">
                                        {__('general.publication_year')}
                                    </Label>
                                    <div className="grid grid-cols-2 gap-2">
                                        <Input
                                            value={data.publication_year}
                                            onChange={(e) => setData('publication_year', e.target.value)}
                                            className="h-10 text-xs"
                                            placeholder={__('general.year')}
                                        />
                                        <Input
                                            value={data.language}
                                            onChange={(e) => setData('language', e.target.value)}
                                            className="h-10 text-xs"
                                            placeholder={__('admin.digital_products_language_placeholder')}
                                        />
                                    </div>
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold text-slate-700">
                                        {__('general.page_count')}
                                    </Label>
                                    <Input
                                        type="number"
                                        value={data.page_count}
                                        onChange={(e) => setData('page_count', e.target.value)}
                                        className="h-10 text-xs"
                                        placeholder={__('admin.digital_products_page_count_placeholder')}
                                    />
                                </div>

                                <div className="space-y-1.5 md:col-span-2">
                                    <Label className="text-xs font-semibold text-slate-700">
                                        {__('general.short_description')}
                                    </Label>
                                    <Textarea
                                        value={data.short_description}
                                        onChange={(e) => setData('short_description', e.target.value)}
                                        rows={2}
                                        className="text-xs"
                                        placeholder={__('admin.digital_products_short_description_placeholder')}
                                    />
                                </div>

                                <div className="space-y-1.5 md:col-span-2">
                                    <Label className="text-xs font-semibold text-slate-700">
                                        {__('general.full_description')}
                                    </Label>
                                    <Textarea
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        rows={5}
                                        className="text-xs"
                                        placeholder={__('admin.digital_products_description_placeholder')}
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
                                <span>{__('general.publishing_and_seo')}</span>
                            </h3>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200">
                                    <div>
                                        <h4 className="text-xs font-bold text-slate-900">{__('general.publish_immediately')}</h4>
                                        <p className="text-[11px] text-slate-500">{__('admin.digital_products_publish_hint')}</p>
                                    </div>
                                    <Switch
                                        checked={data.is_published}
                                        onCheckedChange={(checked) => setData('is_published', checked)}
                                    />
                                </div>

                                <div className="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200">
                                    <div>
                                        <h4 className="text-xs font-bold text-slate-900">{__('general.featured_book')}</h4>
                                        <p className="text-[11px] text-slate-500">{__('admin.digital_products_featured_hint')}</p>
                                    </div>
                                    <Switch
                                        checked={data.is_featured}
                                        onCheckedChange={(checked) => setData('is_featured', checked)}
                                    />
                                </div>

                                <div className="space-y-1.5 md:col-span-2">
                                    <Label className="text-xs font-semibold text-slate-700">
                                        {__('general.meta_title')}
                                    </Label>
                                    <Input
                                        value={data.meta_title}
                                        onChange={(e) => setData('meta_title', e.target.value)}
                                        className="h-9 text-xs"
                                        placeholder={__('admin.digital_products_meta_title_placeholder')}
                                    />
                                </div>

                                <div className="space-y-1.5 md:col-span-2">
                                    <Label className="text-xs font-semibold text-slate-700">
                                        {__('general.meta_description')}
                                    </Label>
                                    <Textarea
                                        value={data.meta_description}
                                        onChange={(e) => setData('meta_description', e.target.value)}
                                        rows={2}
                                        className="text-xs"
                                        placeholder={__('admin.digital_products_meta_description_placeholder')}
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Submit Bar */}
                    <div className="flex items-center justify-end gap-3 pt-2">
                        <Link href={route('admin.digitalproducts.index')}>
                            <Button type="button" variant="outline" className="h-10 px-5 text-xs">
                                {__('general.cancel')}
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
                                    <span>{__('general.uploading_book')}</span>
                                </>
                            ) : (
                                <>
                                    <Plus className="h-4 w-4" />
                                    <span>{__('general.save_and_publish_book')}</span>
                                </>
                            )}
                        </Button>
                    </div>
                </form>
            </div>
        </AdminSidebarLayout>
    );
}

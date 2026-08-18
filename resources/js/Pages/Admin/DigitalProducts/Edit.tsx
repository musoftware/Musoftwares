import React, { useState, useRef } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
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
    ExternalLink,
    FileText,
    Gift,
    HardDrive,
    ImageIcon,
    Layers,
    Loader2,
    Save,
    Sparkles,
    UploadCloud,
} from 'lucide-react';
import { toast } from 'sonner';
import { __ } from '@/lib/i18n';

interface Category {
    id: number;
    name: string;
}

interface Product {
    id: number;
    title: string;
    slug: string;
    category_id: number | null;
    price: number | string;
    is_free: boolean;
    has_free_edition: boolean;
    free_edition_title: string | null;
    free_edition_page_count: number | null;
    free_edition_file_path: string | null;
    free_edition_cover_path: string | null;
    free_edition_download_count: number;
    formatted_free_edition_file_size: string;
    free_edition_cover_url: string;
    file_path: string;
    cover_image_path: string | null;
    file_size: number | null;
    page_count: number | null;
    author_name: string | null;
    publisher: string | null;
    publication_year: string | null;
    language: string | null;
    short_description: string | null;
    description: string | null;
    is_published: boolean;
    is_featured: boolean;
    meta_title: string | null;
    meta_description: string | null;
    cover_url: string;
    formatted_price: string;
    formatted_file_size: string;
}

interface Props {
    product: Product;
    categories: Category[];
}

export default function Edit({ product, categories }: Props) {
    const [submitting, setSubmitting] = useState(false);

    const [form, setForm] = useState({
        title: product.title || '',
        slug: product.slug || '',
        category_id: product.category_id ? String(product.category_id) : '',
        price: product.price ? String(product.price) : '0.00',
        is_free: Boolean(product.is_free),
        author_name: product.author_name || '',
        publisher: product.publisher || 'Musoftware',
        publication_year: product.publication_year || String(new Date().getFullYear()),
        language: product.language || 'ar',
        page_count: product.page_count ? String(product.page_count) : '',
        short_description: product.short_description || '',
        description: product.description || '',
        is_published: Boolean(product.is_published),
        is_featured: Boolean(product.is_featured),
        meta_title: product.meta_title || '',
        meta_description: product.meta_description || '',

        // File replacements
        pdf_file: null as File | null,
        cover_image: null as File | null,

        // Dual Edition Playbook
        has_free_edition: Boolean(product.has_free_edition),
        free_edition_title: product.free_edition_title || 'Playbook Edition (الملخص التطبيقي)',
        free_edition_pdf_file: null as File | null,
        free_edition_cover_image: null as File | null,
        free_edition_page_count: product.free_edition_page_count ? String(product.free_edition_page_count) : '',
    });

    const [coverPreview, setCoverPreview] = useState<string | null>(null);
    const [playbookCoverPreview, setPlaybookCoverPreview] = useState<string | null>(null);

    const mainCoverInputRef = useRef<HTMLInputElement>(null);
    const mainPdfInputRef = useRef<HTMLInputElement>(null);
    const playbookPdfInputRef = useRef<HTMLInputElement>(null);
    const playbookCoverInputRef = useRef<HTMLInputElement>(null);

    const handleCoverChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setForm((prev) => ({ ...prev, cover_image: file }));
            setCoverPreview(URL.createObjectURL(file));
        }
    };

    const handlePlaybookCoverChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setForm((prev) => ({ ...prev, free_edition_cover_image: file }));
            setPlaybookCoverPreview(URL.createObjectURL(file));
        }
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);

        const formData = new FormData();
        formData.append('_method', 'PUT');
        formData.append('title', form.title);
        formData.append('slug', form.slug);
        if (form.category_id) formData.append('category_id', form.category_id);
        formData.append('price', form.is_free ? '0.00' : form.price);
        formData.append('is_free', form.is_free ? '1' : '0');
        if (form.author_name) formData.append('author_name', form.author_name);
        if (form.publisher) formData.append('publisher', form.publisher);
        if (form.publication_year) formData.append('publication_year', form.publication_year);
        if (form.language) formData.append('language', form.language);
        if (form.page_count) formData.append('page_count', form.page_count);
        if (form.short_description) formData.append('short_description', form.short_description);
        if (form.description) formData.append('description', form.description);
        formData.append('is_published', form.is_published ? '1' : '0');
        formData.append('is_featured', form.is_featured ? '1' : '0');
        if (form.meta_title) formData.append('meta_title', form.meta_title);
        if (form.meta_description) formData.append('meta_description', form.meta_description);

        if (form.pdf_file) formData.append('pdf_file', form.pdf_file);
        if (form.cover_image) formData.append('cover_image', form.cover_image);

        // Playbook
        formData.append('has_free_edition', form.has_free_edition ? '1' : '0');
        if (form.has_free_edition) {
            if (form.free_edition_title) formData.append('free_edition_title', form.free_edition_title);
            if (form.free_edition_page_count) formData.append('free_edition_page_count', form.free_edition_page_count);
            if (form.free_edition_pdf_file) formData.append('free_edition_pdf_file', form.free_edition_pdf_file);
            if (form.free_edition_cover_image) formData.append('free_edition_cover_image', form.free_edition_cover_image);
        }

        router.post(route('admin.digitalproducts.update', product.id), formData, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                toast.success(__('general.book_updated_successfully') || 'Book updated successfully!');
                setSubmitting(false);
            },
            onError: (errs) => {
                const first = Object.values(errs)[0];
                if (first) toast.error(String(first));
                setSubmitting(false);
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
            title={__('general.edit_book') || `Edit Book: ${product.title}`}
            header={
                <div className="flex items-center gap-2">
                    <Link href={route('admin.digitalproducts.index')} className="text-slate-500 hover:text-slate-900 transition-colors">
                        <ArrowLeft className="h-4 w-4" />
                    </Link>
                    <BookOpen className="h-5 w-5 text-slate-700" />
                    <span className="truncate max-w-lg">{product.title}</span>
                </div>
            }
            actions={
                <a
                    href={`/library/${product.slug}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 shadow-xs"
                >
                    <ExternalLink className="h-3.5 w-3.5" />
                    <span>{__('general.view_in_store') || 'View in Store'}</span>
                </a>
            }
        >
            <Head title={`Edit: ${product.title}`} />

            <div className="max-w-6xl mx-auto">
                <form onSubmit={submit} className="space-y-6">
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">

                        {/* Left Column: Cover & Main PDF */}
                        <div className="space-y-6">
                            {/* Main Cover */}
                            <Card className="border border-slate-200 shadow-sm bg-white text-center">
                                <CardContent className="p-5 space-y-4">
                                    <Label className="text-xs font-bold text-slate-700 block uppercase">
                                        {__('general.main_book_cover') || 'Main Book Cover'}
                                    </Label>

                                    <div className="w-40 aspect-[3/4] mx-auto rounded-xl overflow-hidden bg-slate-900 border border-slate-200 shadow-md relative flex items-center justify-center">
                                        <img
                                            src={coverPreview || product.cover_url}
                                            alt={product.title}
                                            className="w-full h-full object-cover"
                                        />
                                    </div>

                                    <input
                                        type="file"
                                        ref={mainCoverInputRef}
                                        onChange={handleCoverChange}
                                        accept="image/*"
                                        className="hidden"
                                    />

                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        className="w-full h-9 text-xs gap-1.5 border-slate-300"
                                        onClick={() => mainCoverInputRef.current?.click()}
                                    >
                                        <ImageIcon className="h-3.5 w-3.5 text-slate-600" />
                                        <span>{coverPreview ? __('general.change_new_cover') || 'Change Selected Cover' : __('general.replace_cover_image') || 'Replace Cover Image'}</span>
                                    </Button>
                                </CardContent>
                            </Card>

                            {/* Main PDF File */}
                            <Card className="border border-slate-200 shadow-sm bg-white">
                                <CardContent className="p-5 space-y-3">
                                    <Label className="text-xs font-bold text-slate-700 block uppercase">
                                        {__('general.main_pdf_file') || 'Main Book PDF'}
                                    </Label>

                                    <div className="p-3 rounded-xl bg-slate-50 border border-slate-200 space-y-1 text-xs">
                                        <div className="flex items-center justify-between text-slate-600">
                                            <span>Current Size:</span>
                                            <strong className="text-slate-900 font-mono">{product.formatted_file_size}</strong>
                                        </div>
                                        <div className="flex items-center justify-between text-slate-600">
                                            <span>Pages:</span>
                                            <strong className="text-slate-900">{product.page_count ?? '—'}</strong>
                                        </div>
                                    </div>

                                    <input
                                        type="file"
                                        ref={mainPdfInputRef}
                                        onChange={(e) => {
                                            const file = e.target.files?.[0];
                                            if (file) setForm((prev) => ({ ...prev, pdf_file: file }));
                                        }}
                                        accept="application/pdf"
                                        className="hidden"
                                    />

                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        className="w-full h-9 text-xs gap-1.5 border-slate-300"
                                        onClick={() => mainPdfInputRef.current?.click()}
                                    >
                                        <FileText className="h-3.5 w-3.5 text-slate-600" />
                                        <span>{form.pdf_file ? `Selected: ${form.pdf_file.name}` : __('general.replace_pdf_file') || 'Replace PDF File'}</span>
                                    </Button>
                                </CardContent>
                            </Card>
                        </div>

                        {/* Right Column: Details & Playbook */}
                        <div className="lg:col-span-2 space-y-6">

                            {/* Dual Edition Playbook */}
                            <Card className="border border-slate-200 shadow-sm bg-white">
                                <CardContent className="p-5 space-y-4">
                                    <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                                        <div>
                                            <h3 className="text-sm font-bold text-slate-900 flex items-center gap-2">
                                                <Gift className="h-4 w-4 text-emerald-600" />
                                                <span>{__('general.dual_edition_playbook') || 'Dual Edition (Free Playbook Summary)'}</span>
                                            </h3>
                                            <p className="text-xs text-slate-500 mt-0.5">
                                                {__('general.dual_edition_desc') || 'Optional free summary edition alongside the full book.'}
                                            </p>
                                        </div>
                                        <Switch
                                            checked={form.has_free_edition}
                                            onCheckedChange={(checked) => setForm((prev) => ({ ...prev, has_free_edition: checked }))}
                                        />
                                    </div>

                                    {form.has_free_edition && (
                                        <div className="space-y-4 pt-2">
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div className="space-y-1.5">
                                                    <Label className="text-xs font-semibold text-slate-700">
                                                        {__('general.playbook_title') || 'Playbook Title'}
                                                    </Label>
                                                    <Input
                                                        value={form.free_edition_title}
                                                        onChange={(e) => setForm((prev) => ({ ...prev, free_edition_title: e.target.value }))}
                                                        className="h-9 text-xs"
                                                    />
                                                </div>

                                                <div className="space-y-1.5">
                                                    <Label className="text-xs font-semibold text-slate-700">
                                                        {__('general.playbook_page_count') || 'Playbook Pages'}
                                                    </Label>
                                                    <Input
                                                        type="number"
                                                        value={form.free_edition_page_count}
                                                        onChange={(e) => setForm((prev) => ({ ...prev, free_edition_page_count: e.target.value }))}
                                                        className="h-9 text-xs"
                                                    />
                                                </div>
                                            </div>

                                            {product.free_edition_file_path && (
                                                <div className="p-2.5 rounded-lg bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 flex items-center gap-2">
                                                    <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                                                    <span>
                                                        Playbook active ({product.formatted_free_edition_file_size} • {product.free_edition_download_count} downloads)
                                                    </span>
                                                </div>
                                            )}

                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                <input
                                                    type="file"
                                                    ref={playbookPdfInputRef}
                                                    onChange={(e) => {
                                                        const file = e.target.files?.[0];
                                                        if (file) setForm((prev) => ({ ...prev, free_edition_pdf_file: file }));
                                                    }}
                                                    accept="application/pdf"
                                                    className="hidden"
                                                />
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    className="h-9 text-xs gap-1.5 border-slate-300"
                                                    onClick={() => playbookPdfInputRef.current?.click()}
                                                >
                                                    <UploadCloud className="h-3.5 w-3.5 text-emerald-600" />
                                                    <span>{form.free_edition_pdf_file ? `Selected: ${form.free_edition_pdf_file.name}` : 'Replace Playbook PDF'}</span>
                                                </Button>

                                                <input
                                                    type="file"
                                                    ref={playbookCoverInputRef}
                                                    onChange={handlePlaybookCoverChange}
                                                    accept="image/*"
                                                    className="hidden"
                                                />
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    className="h-9 text-xs gap-1.5 border-slate-300"
                                                    onClick={() => playbookCoverInputRef.current?.click()}
                                                >
                                                    <ImageIcon className="h-3.5 w-3.5 text-emerald-600" />
                                                    <span>{playbookCoverPreview ? 'Cover Selected' : 'Replace Playbook Cover'}</span>
                                                </Button>
                                            </div>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Core Details */}
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
                                                value={form.title}
                                                onChange={(e) => setForm((prev) => ({ ...prev, title: e.target.value }))}
                                                className="h-10 text-xs font-semibold"
                                                required
                                            />
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label className="text-xs font-semibold text-slate-700">
                                                {__('general.category') || 'Category'}
                                            </Label>
                                            <PremiumCombobox
                                                value={form.category_id}
                                                onChange={(val) => setForm((prev) => ({ ...prev, category_id: String(val || '') }))}
                                                options={categoryOptions}
                                                placeholder={__('general.select_category') || 'Select Category'}
                                            />
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label className="text-xs font-semibold text-slate-700">
                                                {__('general.slug') || 'URL Slug'} <span className="text-red-500">*</span>
                                            </Label>
                                            <Input
                                                value={form.slug}
                                                onChange={(e) => setForm((prev) => ({ ...prev, slug: e.target.value }))}
                                                className="h-10 text-xs font-mono"
                                                required
                                            />
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
                                                        checked={form.is_free}
                                                        onChange={(e) => {
                                                            const checked = e.target.checked;
                                                            setForm((prev) => ({
                                                                ...prev,
                                                                is_free: checked,
                                                                price: checked ? '0.00' : prev.price || '9.99',
                                                            }));
                                                        }}
                                                        className="rounded border-slate-300 text-blue-600"
                                                    />
                                                    <span>{__('general.free_book') || 'Free Book'}</span>
                                                </label>

                                                {!form.is_free && (
                                                    <div className="flex-1 relative">
                                                        <Input
                                                            type="number"
                                                            step="0.01"
                                                            min="0"
                                                            value={form.price}
                                                            onChange={(e) => setForm((prev) => ({ ...prev, price: e.target.value }))}
                                                            className="h-10 text-xs font-bold pe-12"
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
                                                value={form.author_name}
                                                onChange={(e) => setForm((prev) => ({ ...prev, author_name: e.target.value }))}
                                                className="h-10 text-xs"
                                            />
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label className="text-xs font-semibold text-slate-700">
                                                {__('general.publisher') || 'Publisher'}
                                            </Label>
                                            <Input
                                                value={form.publisher}
                                                onChange={(e) => setForm((prev) => ({ ...prev, publisher: e.target.value }))}
                                                className="h-10 text-xs"
                                            />
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label className="text-xs font-semibold text-slate-700">
                                                {__('general.publication_year') || 'Year & Language'}
                                            </Label>
                                            <div className="grid grid-cols-2 gap-2">
                                                <Input
                                                    value={form.publication_year}
                                                    onChange={(e) => setForm((prev) => ({ ...prev, publication_year: e.target.value }))}
                                                    className="h-10 text-xs"
                                                />
                                                <Input
                                                    value={form.language}
                                                    onChange={(e) => setForm((prev) => ({ ...prev, language: e.target.value }))}
                                                    className="h-10 text-xs"
                                                />
                                            </div>
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label className="text-xs font-semibold text-slate-700">
                                                {__('general.page_count') || 'Page Count'}
                                            </Label>
                                            <Input
                                                type="number"
                                                value={form.page_count}
                                                onChange={(e) => setForm((prev) => ({ ...prev, page_count: e.target.value }))}
                                                className="h-10 text-xs"
                                            />
                                        </div>

                                        <div className="space-y-1.5 md:col-span-2">
                                            <Label className="text-xs font-semibold text-slate-700">
                                                {__('general.short_description') || 'Short Summary / Teaser'}
                                            </Label>
                                            <Textarea
                                                value={form.short_description}
                                                onChange={(e) => setForm((prev) => ({ ...prev, short_description: e.target.value }))}
                                                rows={2}
                                                className="text-xs"
                                            />
                                        </div>

                                        <div className="space-y-1.5 md:col-span-2">
                                            <Label className="text-xs font-semibold text-slate-700">
                                                {__('general.full_description') || 'Full Book Description'}
                                            </Label>
                                            <Textarea
                                                value={form.description}
                                                onChange={(e) => setForm((prev) => ({ ...prev, description: e.target.value }))}
                                                rows={5}
                                                className="text-xs"
                                            />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Publishing & SEO */}
                            <Card className="border border-slate-200 shadow-sm bg-white">
                                <CardContent className="p-6 space-y-4">
                                    <h3 className="text-sm font-bold text-slate-900 flex items-center gap-2 pb-2 border-b border-slate-100">
                                        <Sparkles className="h-4 w-4 text-slate-700" />
                                        <span>{__('general.publishing_and_seo') || 'Publishing & SEO'}</span>
                                    </h3>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div className="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200">
                                            <div>
                                                <h4 className="text-xs font-bold text-slate-900">{__('general.publish_in_gallery') || 'Published in Store'}</h4>
                                                <p className="text-[11px] text-slate-500">Visible to all visitors</p>
                                            </div>
                                            <Switch
                                                checked={form.is_published}
                                                onCheckedChange={(checked) => setForm((prev) => ({ ...prev, is_published: checked }))}
                                            />
                                        </div>

                                        <div className="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200">
                                            <div>
                                                <h4 className="text-xs font-bold text-slate-900">{__('general.featured_book') || 'Featured Book'}</h4>
                                                <p className="text-[11px] text-slate-500">Featured badge</p>
                                            </div>
                                            <Switch
                                                checked={form.is_featured}
                                                onCheckedChange={(checked) => setForm((prev) => ({ ...prev, is_featured: checked }))}
                                            />
                                        </div>

                                        <div className="space-y-1.5 md:col-span-2">
                                            <Label className="text-xs font-semibold text-slate-700">
                                                {__('general.meta_title') || 'Meta SEO Title'}
                                            </Label>
                                            <Input
                                                value={form.meta_title}
                                                onChange={(e) => setForm((prev) => ({ ...prev, meta_title: e.target.value }))}
                                                className="h-9 text-xs"
                                            />
                                        </div>

                                        <div className="space-y-1.5 md:col-span-2">
                                            <Label className="text-xs font-semibold text-slate-700">
                                                {__('general.meta_description') || 'Meta SEO Description'}
                                            </Label>
                                            <Textarea
                                                value={form.meta_description}
                                                onChange={(e) => setForm((prev) => ({ ...prev, meta_description: e.target.value }))}
                                                rows={2}
                                                className="text-xs"
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
                                    disabled={submitting}
                                    className="h-10 px-6 text-xs font-bold bg-slate-900 hover:bg-slate-800 text-white shadow-sm gap-2"
                                >
                                    {submitting ? (
                                        <>
                                            <Loader2 className="h-4 w-4 animate-spin" />
                                            <span>{__('general.saving') || 'Saving...'}</span>
                                        </>
                                    ) : (
                                        <>
                                            <Save className="h-4 w-4" />
                                            <span>{__('general.save_changes') || 'Save Changes'}</span>
                                        </>
                                    )}
                                </Button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </AdminSidebarLayout>
    );
}

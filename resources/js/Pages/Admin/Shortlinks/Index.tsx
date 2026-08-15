import React, { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Plus, Copy, ExternalLink, Trash2, MoreHorizontal, Search, Link2, Sparkles, Image as ImageIcon, Globe, Share2, Eye } from 'lucide-react';
import { toast } from 'sonner';
import { toastSuccess, toastError } from '@/Components/ui/use-toast';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import axios from 'axios';

interface ShortLinkItem {
    id: number;
    short_code: string;
    short_url: string;
    destination_url: string;
    label: string | null;
    title: string | null;
    description: string | null;
    image_url: string | null;
    effective_title?: string;
    effective_description?: string;
    effective_image?: string;
    is_active: boolean;
    clicks: number;
    expires_at: string | null;
    created_at: string | null;
    source_type: string | null;
    creator: { id: number; name: string } | null;
}

interface PaginatedLinks {
    data: ShortLinkItem[];
    links: any[];
    current_page: number;
    last_page: number;
    from?: number;
    to?: number;
    total?: number;
}

interface Props {
    links: PaginatedLinks;
    filters: { q?: string };
    translations: Record<string, string>;
}

export default function Index({ links, filters, translations }: Props) {
    const [createOpen, setCreateOpen] = useState(false);
    const [previewModalItem, setPreviewModalItem] = useState<ShortLinkItem | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [fetchingMeta, setFetchingMeta] = useState(false);
    const [form, setForm] = useState({
        destination_url: '',
        label: '',
        title: '',
        description: '',
        image_url: '',
        expires_at: '',
    });
    const [pendingDelete, setPendingDelete] = useState<ShortLinkItem | null>(null);

    const t = (key: string, fallback = key) => translations[key] ?? fallback;

    const copyToClipboard = (text: string) => {
        navigator.clipboard.writeText(text).then(() => {
            toast.success(t('copied', 'Copied!'));
        });
    };

    const handleFetchMeta = async () => {
        if (!form.destination_url) {
            toast.error(t('enter_url_first', 'Please enter destination URL first.'));
            return;
        }

        try {
            setFetchingMeta(true);
            const response = await axios.post(route('admin.shortlinks.fetch_meta'), {
                url: form.destination_url,
            });

            if (response.data) {
                setForm((prev) => ({
                    ...prev,
                    title: response.data.title || prev.title,
                    description: response.data.description || prev.description,
                    image_url: response.data.image_url || prev.image_url,
                }));
                toast.success(t('meta_fetched_successfully', 'SEO metadata fetched successfully!'));
            }
        } catch (error) {
            toast.error(t('meta_fetch_failed', 'Could not auto-fetch metadata from this URL.'));
        } finally {
            setFetchingMeta(false);
        }
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);
        router.post(
            route('admin.shortlinks.store'),
            {
                destination_url: form.destination_url,
                label: form.label || null,
                title: form.title || null,
                description: form.description || null,
                image_url: form.image_url || null,
                expires_at: form.expires_at || null,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setForm({
                        destination_url: '',
                        label: '',
                        title: '',
                        description: '',
                        image_url: '',
                        expires_at: '',
                    });
                    setCreateOpen(false);
                    toastSuccess(t('created_successfully', 'Short link created with full SEO metadata'));
                },
                onError: () => {
                    toast.error(t('invalid_url', 'Please check the entered URL and fields.'));
                },
                onFinish: () => setSubmitting(false),
            },
        );
    };

    const toggleStatus = (item: ShortLinkItem) => {
        router.post(route('admin.shortlinks.toggle', item.id), {}, {
            preserveScroll: true,
            onSuccess: () => toastSuccess(item.is_active ? 'Link deactivated' : 'Link activated'),
            onError: () => toastError('Failed to update link status'),
        });
    };

    const confirmDelete = () => {
        if (!pendingDelete) return;
        const id = pendingDelete.id;
        setPendingDelete(null);
        router.delete(route('admin.shortlinks.destroy', id), {
            preserveScroll: true,
            onSuccess: () => toastSuccess('Link deleted'),
            onError: () => toastError('Failed to delete link'),
        });
    };

    const onSearch = (value: string) => {
        router.get(
            route('admin.shortlinks.index'),
            { q: value || undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const fmtDate = (iso: string | null) => {
        if (!iso) return t('never', 'Never');
        try {
            return new Date(iso).toLocaleString();
        } catch {
            return iso;
        }
    };

    // Live preview card computed values
    const previewTitle = form.title || form.label || 'Musoftware | Systems & Digital Solutions';
    const previewDesc = form.description || 'Discover powerful tools, automated workflows, and enterprise solutions.';
    const previewImg = form.image_url || '/images/default-meta.png';

    return (
        <AdminSidebarLayout title={t('title')} header={t('title')}>
            <Head title={t('title')} />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            <Link2 className="h-6 w-6 text-slate-500" />
                            {t('title')}
                        </h1>
                        <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{t('subtitle')}</p>
                    </div>
                    <Button onClick={() => setCreateOpen(true)} className="gap-2">
                        <Plus className="h-4 w-4" />
                        {t('create_new')}
                    </Button>
                </div>

                <div className="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <div className="flex items-center gap-2 border-b border-slate-100 p-4 dark:border-slate-800">
                        <div className="relative w-full max-w-sm">
                            <Search className="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <Input
                                defaultValue={filters.q ?? ''}
                                placeholder={t('search_placeholder')}
                                onChange={(e) => onSearch(e.target.value)}
                                className="pl-8"
                            />
                        </div>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t('short_url')}</TableHead>
                                <TableHead>{t('destination_url')}</TableHead>
                                <TableHead>{t('label')}</TableHead>
                                <TableHead>SEO / Preview</TableHead>
                                <TableHead className="text-center">{t('clicks')}</TableHead>
                                <TableHead className="text-center">{t('status')}</TableHead>
                                <TableHead>{t('expires_at')}</TableHead>
                                <TableHead className="text-end">{t('actions')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {links.data.length > 0 ? (
                                links.data.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell className="font-medium">
                                            <div className="flex items-center gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() => copyToClipboard(item.short_url)}
                                                    title={t('copy')}
                                                    className="inline-flex items-center gap-1.5 rounded-md bg-slate-100 px-2 py-1 font-mono text-xs text-slate-900 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                                >
                                                    /l/{item.short_code}
                                                    <Copy className="h-3 w-3 text-slate-400" />
                                                </button>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <a
                                                href={item.short_url}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="block max-w-[220px] truncate text-xs text-blue-600 hover:underline dark:text-blue-400"
                                                title={item.destination_url}
                                            >
                                                {item.destination_url}
                                            </a>
                                        </TableCell>
                                        <TableCell className="max-w-[140px] truncate text-xs text-slate-600 dark:text-slate-300" title={item.label ?? ''}>
                                            {item.label ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => setPreviewModalItem(item)}
                                                className="h-7 gap-1 px-2 text-xs text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white"
                                            >
                                                <Eye className="h-3.5 w-3.5" />
                                                <span>Social Card</span>
                                            </Button>
                                        </TableCell>
                                        <TableCell className="text-center font-mono text-xs">{item.clicks}</TableCell>
                                        <TableCell className="text-center">
                                            <div className="flex items-center justify-center">
                                                <Switch checked={item.is_active} onCheckedChange={() => toggleStatus(item)} />
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-xs text-slate-600 dark:text-slate-400">{fmtDate(item.expires_at)}</TableCell>
                                        <TableCell className="text-end">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" className="h-8 w-8 p-0">
                                                        <span className="sr-only">{t('actions')}</span>
                                                        <MoreHorizontal className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem asChild>
                                                        <a
                                                            href={item.short_url}
                                                            target="_blank"
                                                            rel="noreferrer"
                                                            className="flex cursor-pointer items-center"
                                                        >
                                                            <ExternalLink className="me-2 h-4 w-4" />
                                                            {t('open')}
                                                        </a>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        onClick={() => copyToClipboard(item.short_url)}
                                                        className="flex cursor-pointer items-center"
                                                    >
                                                        <Copy className="me-2 h-4 w-4" />
                                                        {t('copy')}
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        onClick={() => setPreviewModalItem(item)}
                                                        className="flex cursor-pointer items-center"
                                                    >
                                                        <Share2 className="me-2 h-4 w-4" />
                                                        Preview Social Card
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        onClick={() => setPendingDelete(item)}
                                                        className="flex cursor-pointer items-center text-red-600 focus:text-red-700"
                                                    >
                                                        <Trash2 className="me-2 h-4 w-4" />
                                                        {t('delete')}
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell colSpan={8} className="h-24 text-center text-slate-500">
                                        {t('no_links')}
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>

                    {links.last_page > 1 && (
                        <div className="flex items-center justify-center gap-1 border-t border-slate-100 p-4 dark:border-slate-800">
                            {links.links.map((link: any, i: number) => (
                                <Button
                                    key={i}
                                    variant={link.active ? 'default' : 'outline'}
                                    size="sm"
                                    disabled={!link.url}
                                    onClick={() => link.url && router.visit(link.url, { preserveScroll: true })}
                                >
                                    {link.label}
                                </Button>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            <ConfirmModal
                isOpen={pendingDelete !== null}
                title="Delete short link?"
                description={`This will permanently delete "/l/${pendingDelete?.short_code}".`}
                confirmLabel="Delete"
                cancelLabel="Cancel"
                variant="danger"
                onConfirm={confirmDelete}
                onCancel={() => setPendingDelete(null)}
            />

            {/* View Social Card Modal */}
            <Dialog open={previewModalItem !== null} onOpenChange={() => setPreviewModalItem(null)}>
                <DialogContent className="sm:max-w-[480px]">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Share2 className="h-5 w-5 text-emerald-500" />
                            WhatsApp / Social Preview
                        </DialogTitle>
                        <DialogDescription>
                            How this shortlink appears when shared on WhatsApp, Facebook, Twitter, and Telegram.
                        </DialogDescription>
                    </DialogHeader>
                    {previewModalItem && (
                        <div className="space-y-4 pt-2">
                            <div className="overflow-hidden rounded-xl border border-slate-200 bg-slate-50 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                                {previewModalItem.image_url || previewModalItem.effective_image ? (
                                    <div className="h-44 w-full overflow-hidden bg-slate-950">
                                        <img
                                            src={previewModalItem.image_url || previewModalItem.effective_image}
                                            alt={previewModalItem.effective_title || 'Preview'}
                                            className="h-full w-full object-cover"
                                            onError={(e) => {
                                                (e.target as HTMLImageElement).src = '/images/default-meta.png';
                                            }}
                                        />
                                    </div>
                                ) : null}
                                <div className="p-3.5 space-y-1">
                                    <div className="text-[11px] font-medium uppercase tracking-wider text-slate-400">
                                        musoftwares.com
                                    </div>
                                    <div className="text-sm font-semibold text-slate-900 dark:text-white line-clamp-1">
                                        {previewModalItem.effective_title || previewModalItem.title || previewModalItem.label || 'Musoftware'}
                                    </div>
                                    <div className="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
                                        {previewModalItem.effective_description || previewModalItem.description || 'Digital Systems & Automation'}
                                    </div>
                                    <div className="pt-2 text-xs font-mono text-emerald-600 dark:text-emerald-400 truncate">
                                        {previewModalItem.short_url}
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                    <DialogFooter>
                        <Button type="button" onClick={() => setPreviewModalItem(null)}>
                            Close
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Create Shortlink with Rich SEO Modal */}
            <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                <DialogContent className="sm:max-w-[620px] max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>{t('create_new')}</DialogTitle>
                        <DialogDescription>{t('subtitle')}</DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="space-y-1.5">
                            <div className="flex items-center justify-between">
                                <Label htmlFor="destination_url">{t('destination_url')} *</Label>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={handleFetchMeta}
                                    disabled={fetchingMeta || !form.destination_url}
                                    className="h-7 gap-1 px-2.5 text-xs text-indigo-600 border-indigo-200 hover:bg-indigo-50 dark:text-indigo-400 dark:border-indigo-800"
                                >
                                    <Sparkles className="h-3 w-3" />
                                    {fetchingMeta ? t('fetching_meta') : t('fetch_meta')}
                                </Button>
                            </div>
                            <Input
                                id="destination_url"
                                type="url"
                                required
                                autoFocus
                                placeholder="https://example.com/item or /proposals/..."
                                value={form.destination_url}
                                onChange={(e) => setForm((f) => ({ ...f, destination_url: e.target.value }))}
                            />
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="label">{t('label')}</Label>
                                <Input
                                    id="label"
                                    placeholder={t('label_placeholder')}
                                    value={form.label}
                                    onChange={(e) => setForm((f) => ({ ...f, label: e.target.value }))}
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="expires_at">{t('expires_at')}</Label>
                                <Input
                                    id="expires_at"
                                    type="datetime-local"
                                    placeholder={t('expires_at_placeholder')}
                                    value={form.expires_at}
                                    onChange={(e) => setForm((f) => ({ ...f, expires_at: e.target.value }))}
                                />
                            </div>
                        </div>

                        {/* SEO / OpenGraph Customization */}
                        <div className="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-3 dark:border-slate-800 dark:bg-slate-900/50">
                            <div className="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                <Globe className="h-4 w-4 text-emerald-500" />
                                {t('seo_preview_title')}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="seo_title" className="text-xs">{t('seo_title')}</Label>
                                <Input
                                    id="seo_title"
                                    placeholder="e.g. Exclusive Offer | Musoftware"
                                    value={form.title}
                                    onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))}
                                    className="h-8 text-xs"
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="seo_description" className="text-xs">{t('seo_description')}</Label>
                                <Textarea
                                    id="seo_description"
                                    placeholder="Brief description that appears when link is shared on WhatsApp..."
                                    rows={2}
                                    value={form.description}
                                    onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))}
                                    className="text-xs resize-none"
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="seo_image" className="text-xs">{t('seo_image')}</Label>
                                <Input
                                    id="seo_image"
                                    type="url"
                                    placeholder="https://.../preview.jpg"
                                    value={form.image_url}
                                    onChange={(e) => setForm((f) => ({ ...f, image_url: e.target.value }))}
                                    className="h-8 text-xs"
                                />
                            </div>

                            {/* Live Social Card Preview */}
                            <div className="pt-2">
                                <div className="text-[11px] font-medium text-slate-500 dark:text-slate-400 mb-2">
                                    {t('preview_card_heading')}
                                </div>
                                <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
                                    {form.image_url ? (
                                        <div className="h-32 w-full overflow-hidden bg-slate-900">
                                            <img
                                                src={form.image_url}
                                                alt="Preview"
                                                className="h-full w-full object-cover"
                                                onError={(e) => {
                                                    (e.target as HTMLImageElement).src = '/images/default-meta.png';
                                                }}
                                            />
                                        </div>
                                    ) : null}
                                    <div className="p-3 space-y-1">
                                        <div className="text-[10px] uppercase tracking-wider text-slate-400">musoftwares.com</div>
                                        <div className="text-xs font-semibold text-slate-900 dark:text-white line-clamp-1">
                                            {previewTitle}
                                        </div>
                                        <div className="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2">
                                            {previewDesc}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setCreateOpen(false)}>
                                {t('cancel')}
                            </Button>
                            <Button type="submit" disabled={submitting}>
                                {t('submit')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AdminSidebarLayout>
    );
}

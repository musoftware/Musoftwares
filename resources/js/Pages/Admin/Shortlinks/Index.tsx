import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
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
import { Plus, Copy, ExternalLink, Trash2, MoreHorizontal, Search, Link2 } from 'lucide-react';
import { toast } from 'sonner';

interface ShortLinkItem {
    id: number;
    short_code: string;
    short_url: string;
    destination_url: string;
    label: string | null;
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
    const [submitting, setSubmitting] = useState(false);
    const [form, setForm] = useState({
        destination_url: '',
        label: '',
        expires_at: '',
    });

    const t = (key: string, fallback = key) => translations[key] ?? fallback;

    const copyToClipboard = (text: string) => {
        navigator.clipboard.writeText(text).then(() => {
            toast.success(t('copied', 'Copied!'));
        });
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);
        router.post(
            route('admin.shortlinks.store'),
            {
                destination_url: form.destination_url,
                label: form.label || null,
                expires_at: form.expires_at || null,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setForm({ destination_url: '', label: '', expires_at: '' });
                    setCreateOpen(false);
                },
                onError: () => {
                    toast.error(t('invalid_url', 'Please check the entered URL.'));
                },
                onFinish: () => setSubmitting(false),
            },
        );
    };

    const toggleStatus = (item: ShortLinkItem) => {
        router.post(route('admin.shortlinks.toggle', item.id), {}, { preserveScroll: true });
    };

    const deleteLink = (item: ShortLinkItem) => {
        if (confirm(t('confirm_delete'))) {
            router.delete(route('admin.shortlinks.destroy', item.id), { preserveScroll: true });
        }
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

    return (
        <AdminSidebarLayout title={t('title')} header={t('title')}>
            <Head title={t('title')} />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-bold tracking-tight text-slate-900">
                            <Link2 className="h-6 w-6 text-slate-500" />
                            {t('title')}
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">{t('subtitle')}</p>
                    </div>
                    <Button onClick={() => setCreateOpen(true)} className="gap-2">
                        <Plus className="h-4 w-4" />
                        {t('create_new')}
                    </Button>
                </div>

                <div className="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center gap-2 border-b border-slate-100 p-4">
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
                                <TableHead className="text-center">{t('clicks')}</TableHead>
                                <TableHead className="text-center">{t('status')}</TableHead>
                                <TableHead>{t('expires_at')}</TableHead>
                                <TableHead>{t('created_by')}</TableHead>
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
                                                    className="inline-flex items-center gap-1.5 rounded-md bg-slate-50 px-2 py-1 font-mono text-xs text-slate-900 hover:bg-slate-100"
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
                                                className="block max-w-[260px] truncate text-xs text-blue-600 hover:underline"
                                                title={item.destination_url}
                                            >
                                                {item.destination_url}
                                            </a>
                                        </TableCell>
                                        <TableCell className="max-w-[160px] truncate text-xs text-slate-600" title={item.label ?? ''}>
                                            {item.label ?? '—'}
                                        </TableCell>
                                        <TableCell className="text-center font-mono text-xs">{item.clicks}</TableCell>
                                        <TableCell className="text-center">
                                            <div className="flex items-center justify-center">
                                                <Switch checked={item.is_active} onCheckedChange={() => toggleStatus(item)} />
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-xs text-slate-600">{fmtDate(item.expires_at)}</TableCell>
                                        <TableCell className="text-xs text-slate-600">{item.creator?.name ?? '—'}</TableCell>
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
                                                        onClick={() => deleteLink(item)}
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
                        <div className="flex items-center justify-center gap-1 border-t border-slate-100 p-4">
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

            <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                <DialogContent className="sm:max-w-[520px]">
                    <DialogHeader>
                        <DialogTitle>{t('create_new')}</DialogTitle>
                        <DialogDescription>{t('subtitle')}</DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="destination_url">{t('destination_url')}</Label>
                            <Input
                                id="destination_url"
                                type="url"
                                required
                                autoFocus
                                placeholder={t('destination_url_placeholder')}
                                value={form.destination_url}
                                onChange={(e) => setForm((f) => ({ ...f, destination_url: e.target.value }))}
                            />
                        </div>
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

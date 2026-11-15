import { FormEvent, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Download, ExternalLink, Search } from 'lucide-react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Card, CardContent } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import Pagination, { PaginationLink } from '@/Components/Pagination';
import { __ } from '@/lib/i18n';

interface Page<T> {
    data: T[];
    links: PaginationLink[];
    total: number;
}

interface DownloadRow {
    id: number;
    email: string;
    user: { id: number; name: string } | null;
    edition: string;
    times: number;
    requested_at: string | null;
    last_downloaded_at: string | null;
}

interface PurchaseRow {
    id: number;
    user: { id: number; name: string; email: string } | null;
    amount: number;
    currency: string | null;
    method: string;
    status: string;
    bought_at: string | null;
}

interface Props {
    product: { id: number; title: string; slug: string; is_free: boolean; has_free_edition: boolean; is_published: boolean };
    stats: Record<'views' | 'people' | 'requests' | 'downloads' | 'full_requests' | 'playbook_requests' | 'purchases' | 'paid_purchases', number>;
    downloads: Page<DownloadRow>;
    purchases: Page<PurchaseRow>;
    filters: { search: string };
}

const dateTime = (value: string | null) => (value ? new Date(value).toLocaleString() : '—');

function Stat({ label, value, hint }: { label: string; value: number; hint?: string }) {
    return (
        <Card className="border border-slate-200 shadow-sm">
            <CardContent className="p-4">
                <p className="text-xs font-semibold text-slate-500">{label}</p>
                <p className="text-2xl font-bold text-slate-900 mt-1">{value.toLocaleString()}</p>
                {hint && <p className="text-[11px] text-slate-500 mt-1">{hint}</p>}
            </CardContent>
        </Card>
    );
}

export default function Stats({ product, stats, downloads, purchases, filters }: Props) {
    const [search, setSearch] = useState(filters.search);

    const submitSearch = (e: FormEvent) => {
        e.preventDefault();
        router.get(route('admin.digitalproducts.stats', product.id), search ? { search } : {}, { preserveState: true, preserveScroll: true });
    };

    return (
        <AdminSidebarLayout
            title={__('admin.digital_products_stats_title', { title: product.title })}
            header={
                <div className="flex items-center gap-2 min-w-0">
                    <Link href={route('admin.digitalproducts.index')} className="text-slate-500 hover:text-slate-900" aria-label={__('general.back')}>
                        <ArrowLeft className="h-4 w-4 rtl:rotate-180" />
                    </Link>
                    <span className="truncate">{__('admin.digital_products_stats_title', { title: product.title })}</span>
                </div>
            }
        >
            <Head title={__('admin.digital_products_stats_title', { title: product.title })} />

            <div className="max-w-6xl mx-auto space-y-6 overflow-x-clip">
                <div className="flex flex-wrap items-center justify-end gap-2">
                    <Button asChild variant="outline" size="sm" className="text-xs">
                        <a href={route('library.show', product.slug)} target="_blank" rel="noopener noreferrer">
                            <ExternalLink className="h-3.5 w-3.5" />
                            <span>{__('general.view_in_store')}</span>
                        </a>
                    </Button>
                    <Button asChild size="sm" className="text-xs">
                        <a href={route('admin.digitalproducts.stats.export', product.id)}>
                            <Download className="h-3.5 w-3.5" />
                            <span>{__('admin.digital_products_stats_export')}</span>
                        </a>
                    </Button>
                </div>

                <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <Stat label={__('admin.digital_products_stats_views')} value={stats.views} />
                    <Stat label={__('admin.digital_products_stats_people')} value={stats.people} />
                    <Stat
                        label={__('admin.digital_products_stats_downloads')}
                        value={stats.downloads}
                        hint={product.has_free_edition ? __('admin.digital_products_stats_split', { full: stats.full_requests, playbook: stats.playbook_requests }) : undefined}
                    />
                    <Stat label={__('admin.digital_products_stats_purchases')} value={stats.purchases} hint={__('admin.digital_products_stats_paid', { count: stats.paid_purchases })} />
                </div>

                <Card className="border border-slate-200 shadow-sm">
                    <CardContent className="p-0">
                        <div className="flex flex-wrap items-center justify-between gap-3 p-4 border-b border-slate-100">
                            <h2 className="text-sm font-bold text-slate-900">{__('admin.digital_products_stats_downloads_table', { count: downloads.total })}</h2>
                            <form onSubmit={submitSearch} className="flex gap-2 w-full sm:w-auto">
                                <Input
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder={__('admin.digital_products_stats_search')}
                                    className="h-9 text-xs flex-1 sm:w-64"
                                    dir="ltr"
                                />
                                <Button type="submit" variant="outline" size="sm" className="h-9" aria-label={__('general.search')}>
                                    <Search className="h-4 w-4" />
                                </Button>
                            </form>
                        </div>

                        {downloads.data.length === 0 ? (
                            <p className="p-6 text-center text-xs text-slate-500">{__('admin.digital_products_stats_no_downloads')}</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="text-xs">{__('admin.digital_products_stats_email')}</TableHead>
                                            <TableHead className="text-xs">{__('admin.digital_products_stats_edition')}</TableHead>
                                            <TableHead className="text-xs">{__('admin.digital_products_stats_times')}</TableHead>
                                            <TableHead className="text-xs">{__('admin.digital_products_stats_requested')}</TableHead>
                                            <TableHead className="text-xs">{__('admin.digital_products_stats_last')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {downloads.data.map((row) => (
                                            <TableRow key={row.id}>
                                                <TableCell className="text-xs">
                                                    <a href={`mailto:${row.email}`} className="font-medium text-slate-900 hover:underline" dir="ltr">{row.email}</a>
                                                    {row.user && (
                                                        <Link href={route('admin.users.show', row.user.id)} className="block text-[11px] text-slate-500 hover:underline">{row.user.name}</Link>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-xs">
                                                    {row.edition === 'playbook' ? __('admin.digital_products_stats_playbook') : __('admin.digital_products_stats_full')}
                                                </TableCell>
                                                <TableCell className="text-xs">{row.times}</TableCell>
                                                <TableCell className="text-xs whitespace-nowrap">{dateTime(row.requested_at)}</TableCell>
                                                <TableCell className="text-xs whitespace-nowrap">{dateTime(row.last_downloaded_at)}</TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                        {downloads.links.length > 3 && <div className="p-4"><Pagination links={downloads.links} /></div>}
                    </CardContent>
                </Card>

                <Card className="border border-slate-200 shadow-sm">
                    <CardContent className="p-0">
                        <h2 className="text-sm font-bold text-slate-900 p-4 border-b border-slate-100">{__('admin.digital_products_stats_purchases_table', { count: purchases.total })}</h2>
                        {purchases.data.length === 0 ? (
                            <p className="p-6 text-center text-xs text-slate-500">{__('admin.digital_products_stats_no_purchases')}</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="text-xs">{__('admin.digital_products_stats_buyer')}</TableHead>
                                            <TableHead className="text-xs">{__('admin.digital_products_stats_amount')}</TableHead>
                                            <TableHead className="text-xs">{__('admin.digital_products_stats_method')}</TableHead>
                                            <TableHead className="text-xs">{__('admin.digital_products_stats_date')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {purchases.data.map((row) => (
                                            <TableRow key={row.id}>
                                                <TableCell className="text-xs">
                                                    {row.user ? (
                                                        <Link href={route('admin.users.show', row.user.id)} className="hover:underline">
                                                            <span className="font-medium text-slate-900">{row.user.name}</span>
                                                            <span className="block text-[11px] text-slate-500" dir="ltr">{row.user.email}</span>
                                                        </Link>
                                                    ) : '—'}
                                                </TableCell>
                                                <TableCell className="text-xs whitespace-nowrap">
                                                    {row.amount > 0 ? `${row.amount.toLocaleString()} ${row.currency ?? ''}` : __('admin.digital_products_stats_free')}
                                                </TableCell>
                                                <TableCell className="text-xs">{row.method}</TableCell>
                                                <TableCell className="text-xs whitespace-nowrap">{dateTime(row.bought_at)}</TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                        {purchases.links.length > 3 && <div className="p-4"><Pagination links={purchases.links} /></div>}
                    </CardContent>
                </Card>
            </div>
        </AdminSidebarLayout>
    );
}

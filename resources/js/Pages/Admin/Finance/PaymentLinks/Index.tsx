import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { MoreHorizontal, Plus, Link as LinkIcon, Copy, Trash } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Card, CardContent } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { __ } from '@/lib/i18n';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { CurrencySelect } from '@/Components/CurrencySelect';

export default function Index({ paymentLinks, currencies }: { paymentLinks: any, currencies: any }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        amount: '',
        currency_id: '',
    });

    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.payment-links.store'), {
            onSuccess: () => {
                setIsCreateModalOpen(false);
                reset();
            },
        });
    };

    const handleDelete = (id: number | string) => {
        if (confirm(__('admin.confirm_delete_payment_link', { default: 'Are you sure you want to delete this payment link?' }))) {
            router.delete(route('admin.payment-links.destroy', id));
        }
    };

    const copyToClipboard = (uuid: string) => {
        const url = route('guest.payment-links.show', uuid);
        navigator.clipboard.writeText(url);
        alert(__('admin.copied_to_clipboard', { default: 'Copied to clipboard' }));
    };

    const paginationLinks = paymentLinks.meta?.links || paymentLinks.links;

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'paid':
                return <StatusBadge status="paid" label={__('admin.paid')} className="bg-emerald-50 text-emerald-700 border-emerald-100" />;
            case 'pending':
            default:
                return <StatusBadge status="pending" label={__('admin.pending')} className="bg-amber-50 text-amber-700 border-amber-100" />;
        }
    };

    return (
        <AdminSidebarLayout title={__('admin.payment_links')} header={__('admin.payment_links')}>
            
            <Card className="mb-4 bg-white shadow-sm overflow-visible">
                <CardContent className="p-4 flex justify-between items-center">
                    <h2 className="text-lg font-semibold">{__('admin.payment_links')}</h2>
                    <Button onClick={() => setIsCreateModalOpen(true)}>
                        <Plus className="mr-2 h-4 w-4" />{__('admin.create_payment_link', { default: 'Create Link' })}
                    </Button>
                </CardContent>
            </Card>

            <div className="dashboard-container at-mobile-scroll-fix admin-table-mobile-cards">
                <Card className="bg-white shadow-sm border border-gray-100 overflow-hidden">
                    <div className="table-responsive">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-muted/50 hover:bg-muted/50 border-b">
                                    <TableHead className="uppercase text-xs">ID</TableHead>
                                    <TableHead className="uppercase text-xs">{__('general.title')}</TableHead>
                                    <TableHead className="uppercase text-xs">{__('general.amount')}</TableHead>
                                    <TableHead className="uppercase text-xs">{__('general.status')}</TableHead>
                                    <TableHead className="uppercase text-xs">{__('admin.created_by')}</TableHead>
                                    <TableHead className="uppercase text-xs">{__('general.date')}</TableHead>
                                    <TableHead className="text-right uppercase text-xs">{__('general.actions')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {(paymentLinks.data as any).map((link: any) => (
                                    <TableRow key={link.id}>
                                        <TableCell data-label="ID" className="font-medium">
                                            #{link.id}
                                        </TableCell>
                                        <TableCell data-label={__('general.title')}>
                                            {link.title}
                                        </TableCell>
                                        <TableCell data-label={__('general.amount')} className="font-semibold text-emerald-600">
                                            {formatCurrency(link.amount, link.currency?.currency || 'USD')}
                                        </TableCell>
                                        <TableCell data-label={__('general.status')}>
                                            {getStatusBadge(link.status)}
                                        </TableCell>
                                        <TableCell data-label={__('admin.created_by')}>
                                            {link.user?.name || '-'}
                                        </TableCell>
                                        <TableCell data-label={__('general.date')} className="text-muted-foreground text-sm">
                                            {new Date(link.created_at).toLocaleDateString()}
                                        </TableCell>
                                        <TableCell data-label={__('general.actions')} className="text-right">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" className="h-8 w-8 p-0">
                                                        <span className="sr-only">{__('general.open_menu')}</span>
                                                        <MoreHorizontal className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem onClick={() => copyToClipboard(link.uuid)}>
                                                        <Copy className="mr-2 h-4 w-4" />{__('admin.copy_link', { default: 'Copy Link' })}
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem asChild>
                                                        <a href={route('guest.payment-links.show', link.uuid)} target="_blank" rel="noopener noreferrer" className="flex items-center w-full">
                                                            <LinkIcon className="mr-2 h-4 w-4" />{__('admin.view_link', { default: 'View Link' })}
                                                        </a>
                                                    </DropdownMenuItem>
                                                    {link.status === 'pending' && (
                                                        <>
                                                            <DropdownMenuSeparator />
                                                            <DropdownMenuItem onClick={() => handleDelete(link.id)} className="text-red-600 focus:text-red-600">
                                                                <Trash className="mr-2 h-4 w-4" />{__('general.delete')}
                                                            </DropdownMenuItem>
                                                        </>
                                                    )}
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {(paymentLinks.data as any).length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={7} className="h-24 text-center text-muted-foreground">{__('admin.no_payment_links_found', { default: 'No payment links found.' })}</TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </Card>
            </div>

            {Array.isArray(paginationLinks) && paginationLinks.length > 3 && (
                <div className="mt-4 flex justify-center md:justify-end">
                    <div className="inline-flex -space-x-px rounded-md shadow-sm">
                        {paginationLinks.map((link, i) => (
                            <Link
                                key={i}
                                href={link.url || '#'}
                                className={`px-3 py-2 text-sm border ${
                                    link.active 
                                        ? 'z-10 bg-primary border-primary text-primary-foreground font-medium' 
                                        : 'bg-background border-input text-muted-foreground hover:bg-muted'
                                } ${i === 0 ? 'rounded-l-md' : ''} ${i === paginationLinks.length - 1 ? 'rounded-r-md' : ''}`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                </div>
            )}

            <Dialog open={isCreateModalOpen} onOpenChange={setIsCreateModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{__('admin.create_payment_link', { default: 'Create Payment Link' })}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleCreate}>
                        <div className="space-y-4 py-4">
                            <div>
                                <Label>{__('general.title')}</Label>
                                <Input 
                                    value={data.title} 
                                    onChange={(e) => setData('title', e.target.value)} 
                                    required 
                                    placeholder={__('admin.payment_link_title_placeholder', { default: 'e.g. Website Maintenance' })}
                                />
                                {errors.title && <span className="text-red-500 text-sm">{errors.title}</span>}
                            </div>
                            <div>
                                <Label>{__('general.amount')}</Label>
                                <Input 
                                    type="number" 
                                    step="0.01" 
                                    value={data.amount} 
                                    onChange={(e) => setData('amount', e.target.value)} 
                                    required 
                                />
                                {errors.amount && <span className="text-red-500 text-sm">{errors.amount}</span>}
                            </div>
                            <CurrencySelect 
                                label={__('general.currency')}
                                currencies={currencies} 
                                value={data.currency_id} 
                                onChange={(val) => setData('currency_id', val)}
                                error={errors.currency_id}
                            />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsCreateModalOpen(false)}>
                                {__('general.cancel')}
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {__('general.save')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

        </AdminSidebarLayout>
    );
}

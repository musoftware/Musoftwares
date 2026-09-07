import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { MoreHorizontal, FileText, Send, CheckCircle, XCircle, Trash2, Sparkles } from 'lucide-react';
import { __ } from '@/lib/i18n';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

export default function Index({ contracts, currentTab }) {

    const handleStatusUpdate = (id, status) => {
        router.post(route('isaas.contracts.update-status', id), { status });
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this contract?')) {
            router.delete(route('isaas.contracts.destroy', id));
        }
    };

    const getStatusBadge = (status) => {
        switch (status) {
            case 'signed':
                return <span className="inline-flex items-center rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-medium text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">{__('general.signed')}</span>;
            case 'sent':
                return <span className="inline-flex items-center rounded-full bg-sky-500/10 px-2.5 py-0.5 text-xs font-medium text-sky-600 dark:text-sky-400 border border-sky-500/20">{__('general.sent')}</span>;
            case 'cancelled':
                return <span className="inline-flex items-center rounded-full bg-destructive/10 px-2.5 py-0.5 text-xs font-medium text-destructive border border-destructive/20">{__('general.cancelled')}</span>;
            case 'draft':
            default:
                return <span className="inline-flex items-center rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium text-muted-foreground border border-border">{__('general.draft')}</span>;
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={__('general.contracts_manager')} />
            
            <div className="mx-auto w-full max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-2">
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-muted text-muted-foreground mb-3 border border-border">{__('general.freelance_tools')}</span>
                    <div className="flex items-baseline gap-3">
                        <h1 className="text-3xl font-bold tracking-tight text-foreground">{__('general.contracts_manager')}</h1>
                        <span className="text-muted-foreground font-medium">/ iSAAS</span>
                    </div>
                </div>

                <div className="mb-6 flex items-center justify-between">
                    <div className="flex space-x-2">
                        <Link
                            href={route('isaas.contracts.index', { status: 'all' })}
                            className={`rounded-md px-4 py-2 text-sm font-medium transition-colors ${currentTab === 'all' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-muted/80'}`}
                        >{__('general.all_contracts')}</Link>
                        <Link
                            href={route('isaas.contracts.index', { status: 'draft' })}
                            className={`rounded-md px-4 py-2 text-sm font-medium transition-colors ${currentTab === 'draft' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-muted/80'}`}
                        >
                            {__('general.drafts')}</Link>
                        <Link
                            href={route('isaas.contracts.index', { status: 'sent' })}
                            className={`rounded-md px-4 py-2 text-sm font-medium transition-colors ${currentTab === 'sent' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-muted/80'}`}
                        >
                            {__('general.sent')}</Link>
                        <Link
                            href={route('isaas.contracts.index', { status: 'signed' })}
                            className={`rounded-md px-4 py-2 text-sm font-medium transition-colors ${currentTab === 'signed' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-muted/80'}`}
                        >
                            {__('general.signed')}</Link>
                    </div>
                    <div className="flex items-center gap-3">
                        <Link href="/admin/contracts/quick-create">
                            <Button variant="outline" className="gap-2 border-amber-500/40 text-amber-600 dark:text-amber-400 bg-amber-500/10 hover:bg-amber-500/20 font-bold shadow-sm">
                                <Sparkles className="h-4 w-4 text-amber-500" />
                                {__('general.quick_pricing_and_contract', { default: 'التسعير والعقد السريع' })}
                            </Button>
                        </Link>
                        <Link href={route('isaas.contracts.create')}>
                            <Button>
                                + Create Contract
                            </Button>
                        </Link>
                    </div>
                </div>

                <div className="overflow-hidden rounded-lg bg-card text-card-foreground shadow border border-border">
                    <table className="w-full text-start text-sm">
                        <thead className="border-b border-border bg-muted/40">
                            <tr>
                                <th className="p-4 font-semibold text-muted-foreground">{__('general.reference')}</th>
                                <th className="p-4 font-semibold text-muted-foreground">{__('general.client_user')}</th>
                                <th className="p-4 font-semibold text-muted-foreground">{__('general.project')}</th>
                                <th className="p-4 font-semibold text-muted-foreground text-end">{__('general.amount')}</th>
                                <th className="p-4 font-semibold text-muted-foreground text-center">{__('general.status')}</th>
                                <th className="p-4 font-semibold text-muted-foreground text-center">{__('general.valid_until')}</th>
                                <th className="p-4 font-semibold text-muted-foreground text-end">{__('general.actions')}</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {(contracts.data as any).map((contract) => (
                                <tr key={contract.id} className="hover:bg-muted/30">
                                    <td className="p-4 font-medium text-foreground">{contract.reference || `CTR-${contract.id}`}</td>
                                    <td className="p-4">
                                        <div className="font-medium text-foreground">{contract.client_name || 'Unknown'}</div>
                                    </td>
                                    <td className="p-4 text-muted-foreground">
                                        <div className="font-medium text-foreground">{contract.project_name || 'N/A'}</div>
                                    </td>
                                    <td className="p-4 text-end font-medium text-foreground">
                                        {contract.total_amount ? `${parseFloat(contract.total_amount).toFixed(2)} ${contract.currency}` : '-'}
                                    </td>
                                    <td className="p-4 text-center">
                                        {getStatusBadge(contract.status)}
                                    </td>
                                    <td className="p-4 text-center text-muted-foreground">
                                        {contract.valid_until ? new Date(contract.valid_until).toLocaleDateString() : '-'}
                                    </td>
                                    <td className="p-4 text-end">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" className="h-8 w-8 p-0">
                                                    <span className="sr-only">{__('general.open_menu')}</span>
                                                    <MoreHorizontal className="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuLabel>{__('general.actions')}</DropdownMenuLabel>
                                                <DropdownMenuItem onClick={() => router.get(route('isaas.contracts.edit', contract.id))}>
                                                    <FileText className="me-2 h-4 w-4" />{__('general.view_edit')}</DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                {contract.status === 'draft' && (
                                                    <DropdownMenuItem onClick={() => handleStatusUpdate(contract.id, 'sent')}>
                                                        <Send className="me-2 h-4 w-4 text-blue-600" />{__('general.mark_as_sent')}</DropdownMenuItem>
                                                )}
                                                {contract.status === 'sent' && (
                                                    <DropdownMenuItem onClick={() => handleStatusUpdate(contract.id, 'signed')}>
                                                        <CheckCircle className="me-2 h-4 w-4 text-green-600" />{__('general.mark_as_signed')}</DropdownMenuItem>
                                                )}
                                                {contract.status !== 'cancelled' && (
                                                    <DropdownMenuItem onClick={() => handleStatusUpdate(contract.id, 'cancelled')} className="text-yellow-600">
                                                        <XCircle className="me-2 h-4 w-4" />{__('general.cancel_contract')}</DropdownMenuItem>
                                                )}
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem onClick={() => handleDelete(contract.id)} className="text-red-600">
                                                    <Trash2 className="me-2 h-4 w-4" />{__('general.delete_contract')}</DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </td>
                                </tr>
                            ))}
                            {(contracts.data as any).length === 0 && (
                                <tr>
                                    <td colSpan={7} className="p-8 text-center text-muted-foreground">{__('general.no_contracts_found')}</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination Controls */}
                {contracts.links && contracts.links.length > 3 && (
                    <div className="mt-4 flex justify-center">
                        <div className="inline-flex -space-x-px rounded-md shadow-sm">
                            {contracts.links.map((link, i) => (
                                <Link
                                    key={i}
                                    href={link.url || '#'}
                                    className={`px-4 py-2 text-sm font-medium border ${link.active ? 'z-10 bg-primary text-primary-foreground border-primary' : 'bg-card border-border text-muted-foreground hover:bg-muted'} ${i === 0 ? 'rounded-s-md' : ''} ${i === contracts.links.length - 1 ? 'rounded-e-md' : ''}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}

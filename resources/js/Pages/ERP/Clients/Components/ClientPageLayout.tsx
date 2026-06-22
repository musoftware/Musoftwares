import React from 'react';
import { Link } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { __ } from '@/lib/i18n';
import {
    ArrowLeft, FileText, ArrowDownLeft, Edit2, Wallet, Lock, DollarSign, FolderOpen, MessageSquare
} from 'lucide-react';

interface Client {
    id: number;
    name: string;
    email: string;
    phone?: string;
    company?: string;
    status: string;
    created_at: string;
    currency?: { id: number; currency: string; symbol: string };
    initials?: string;
    tenant_id?: number | string;
}

interface Props {
    client: Client;
    balance: number;
    lockedBalance: number;
    totalRevenue: number;
    unpaidRevenue: number;
    projectsCount: number;
    ticketsCount: number;
    hasTickets: boolean;
    activeTab: 'overview' | 'transactions' | 'files' | 'notes';
    children: React.ReactNode;
}

export default function ClientPageLayout({
    client,
    balance,
    lockedBalance,
    totalRevenue,
    unpaidRevenue,
    projectsCount,
    ticketsCount,
    hasTickets,
    activeTab,
    children
}: Props) {
    const currencyCode = client.currency?.currency;
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('clients', { tenantId: client.tenant_id?.toString() });

    const tabs = [
        { id: 'overview', label: __('general.overview'), href: route('erp.clients.show', client.id) },
        { id: 'transactions', label: __('general.transactions'), href: route('erp.clients.transactions', client.id) },
        { id: 'files', label: __('general.files'), href: route('erp.clients.files', client.id) },
        { id: 'notes', label: __('general.notes'), href: route('erp.clients.notes', client.id) },
    ];

    return (
        <ERPLayout title={`${__('general.client')} — ${client.name}`} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>
            <div className="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
                {/* Back + Header */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 pb-5">
                    <div className="flex items-center gap-4">
                        <Link href={route('erp.clients.index')} className="text-slate-400 hover:text-slate-900 transition-colors">
                            <ArrowLeft className="w-5 h-5" />
                        </Link>
                        <div className="flex-1">
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-bold text-slate-900">{client.name}</h1>
                                <StatusBadge status={client.status} />
                            </div>
                            <p className="text-slate-500 text-sm mt-0.5">{client.email}{client.company ? ` · ${client.company}` : ''}</p>
                        </div>
                    </div>
                    
                    {/* Action buttons */}
                    <div className="flex flex-wrap items-center gap-2">
                        <Link href={route('erp.invoices.create', { client_id: client.id })}>
                            <Button size="sm" className="gap-1.5 shadow-none bg-slate-900 hover:bg-slate-800 text-white">
                                <FileText className="w-3.5 h-3.5" /> {__('general.new_invoice')}
                            </Button>
                        </Link>
                        <Link href={route('erp.clients.wallet.adjust', client.id) + '?type=receive'}>
                            <Button size="sm" variant="outline" className="gap-1.5 shadow-none border-slate-200 text-slate-700 hover:bg-slate-50">
                                <ArrowDownLeft className="w-3.5 h-3.5 text-emerald-600" /> {__('general.receive_money')}
                            </Button>
                        </Link>
                        <Link href={route('erp.clients.edit', client.id)}>
                            <Button size="sm" variant="outline" className="gap-1.5 shadow-none border-slate-200 text-slate-700 hover:bg-slate-50">
                                <Edit2 className="w-3.5 h-3.5 text-slate-500" /> {__('general.edit_profile')}
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    {[
                        { label: __('general.available_balance'), value: <CurrencyDisplay amount={balance} currency={currencyCode} />, icon: Wallet, color: 'text-indigo-600' },
                        { label: __('general.locked_balance'), value: <CurrencyDisplay amount={lockedBalance} currency={currencyCode} />, icon: Lock, color: 'text-amber-500' },
                        { label: __('general.total_revenue'), value: <CurrencyDisplay amount={totalRevenue} currency={currencyCode} />, icon: DollarSign, color: 'text-emerald-600' },
                        { label: __('general.unpaid_invoices'), value: unpaidRevenue > 0 ? <span className="text-rose-600"><CurrencyDisplay amount={unpaidRevenue} currency={currencyCode} /></span> : '—', icon: FileText, color: 'text-rose-600' },
                        { label: __('general.projects'), value: projectsCount, icon: FolderOpen, color: 'text-primary' },
                        ...(hasTickets ? [{ label: __('general.tickets'), value: ticketsCount, icon: MessageSquare, color: 'text-amber-600' }] : []),
                    ].map(({ label, value, icon: Icon, color }) => (
                        <Card key={label} className="bg-white border border-slate-200 shadow-sm">
                            <CardContent className="p-4 flex items-center gap-3">
                                <div className="w-9 h-9 rounded-lg bg-slate-50 flex items-center justify-center shrink-0">
                                    <Icon className={`w-4 h-4 ${color}`} />
                                </div>
                                <div className="min-w-0">
                                    <p className="text-base font-bold text-slate-900 truncate" title={typeof value === 'string' || typeof value === 'number' ? String(value) : undefined}>{value}</p>
                                    <p className="text-[10px] uppercase font-semibold tracking-wider text-slate-400 truncate" title={label}>{label}</p>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Admin UX Tabs */}
                <div className="border-b border-slate-200 mb-6">
                    <nav className="-mb-px flex space-x-8" aria-label={__('general.tabs')}>
                        {tabs.map((tab) => {
                            const isActive = activeTab === tab.id;
                            return (
                                <Link
                                    key={tab.id}
                                    href={tab.href}
                                    className={`
                                        whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors
                                        ${isActive 
                                            ? 'border-primary text-primary' 
                                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                                        }
                                    `}
                                    aria-current={isActive ? 'page' : undefined}
                                >
                                    {tab.label}
                                </Link>
                            );
                        })}
                    </nav>
                </div>

                {/* Tab Content */}
                <div>
                    {children}
                </div>
            </div>
        </ERPLayout>
    );
}

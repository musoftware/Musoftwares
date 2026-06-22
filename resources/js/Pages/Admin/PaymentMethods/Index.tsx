import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { DataTable } from '@/Components/ui/DataTable';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { useToast } from '@/Components/ui/use-toast';
import { __ } from '@/lib/i18n';
import {
    MoreHorizontal,
    Eye,
    CheckCircle,
    XCircle,
    CreditCard,
    User as UserIcon,
    Clock,
    ShieldCheck,
    ShieldX,
    Layers,
} from 'lucide-react';

// ─── Types ───────────────────────────────────────────────────────────────────

interface PaymentMethodUser {
    id: number;
    name: string;
    email: string;
}

interface PaymentMethod {
    id: number;
    name: string | null;
    status: string;
    type: string | null;
    type_name: string | null;
    summary: string | null;
    details: string | null;
    user: PaymentMethodUser | null;
    created_at: string;
}

interface Stats {
    total: number;
    pending: number;
    active: number;
    declined: number;
}

interface Filters {
    status?: string;
    search?: string;
}

interface Props {
    methods: { data: PaymentMethod[]; [key: string]: any };
    filters: Filters;
    stats: Stats;
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

const statusStyles: Record<string, string> = {
    pending:  'bg-yellow-100 text-yellow-700',
    active:   'bg-emerald-100 text-emerald-700',
    declined: 'bg-red-100 text-red-700',
};

const statusLabel: Record<string, string> = {
    pending:  'Pending',
    active:   'Approved',
    declined: 'Declined',
};

// Map payment types to icon colors for visual variety
const typeColors: Record<string, string> = {
    bank:          'bg-blue-100 text-blue-600',
    paypal:        'bg-indigo-100 text-indigo-600',
    mobile_wallet: 'bg-purple-100 text-purple-600',
    wallet:        'bg-purple-100 text-purple-600',
    instapay:      'bg-rose-100 text-rose-600',
};

// ─── Component ───────────────────────────────────────────────────────────────

export default function Index({ methods, filters, stats }: Props) {
    const { toast } = useToast();

    const handleFilter = (status: string) => {
        router.get('/admin/payment-methods', { ...filters, status, page: 1 }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleSearch = (search: string) => {
        router.get('/admin/payment-methods', { ...filters, search, page: 1 }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleUpdate = (id: number, status: string) => {
        router.put(
            route('admin.payment-methods.update', id),
            { status },
            {
                preserveScroll: true,
                onSuccess: () => toast({ title: `Payment method marked as ${statusLabel[status] ?? status}.` }),
                onError:   () => toast({ title: 'Update failed.', variant: 'destructive' }),
            }
        );
    };

    // ── Table columns ─────────────────────────────────────────────────────────

    const columns = [
        {
            key: 'id',
            label: 'ID',
            className: 'w-[60px]',
            render: (m: PaymentMethod) => (
                <span className="text-slate-400 font-mono text-xs">#{m.id}</span>
            ),
        },
        {
            key: 'user',
            label: 'Employee',
            render: (m: PaymentMethod) =>
                m.user ? (
                    <Link href={route('admin.users.show', m.user.id)} className="flex items-center gap-2 group cursor-pointer">
                        <div className="h-7 w-7 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0 group-hover:bg-indigo-600 transition-colors">
                            <UserIcon className="h-3.5 w-3.5 text-indigo-600 group-hover:text-white transition-colors" />
                        </div>
                        <div>
                            <p className="text-sm font-medium text-slate-700 group-hover:text-indigo-600 transition-colors">{m.user.name}</p>
                            <p className="text-xs text-slate-400">{m.user.email}</p>
                        </div>
                    </Link>
                ) : (
                    <span className="text-slate-400 text-sm">— deleted user</span>
                ),
        },
        {
            key: 'type',
            label: 'Type',
            render: (m: PaymentMethod) => (
                <div className="flex items-center gap-2">
                    <span className={`inline-flex h-7 w-7 items-center justify-center rounded-md flex-shrink-0 ${typeColors[m.type ?? ''] ?? 'bg-slate-100 text-slate-500'}`}>
                        <CreditCard className="h-3.5 w-3.5" />
                    </span>
                    <span className="text-sm font-medium text-slate-700 capitalize">
                        {m.type_name ?? m.type ?? '—'}
                    </span>
                </div>
            ),
        },
        {
            key: 'summary',
            label: 'Method Details',
            render: (m: PaymentMethod) => (
                <span className="text-sm text-slate-500 max-w-[200px] truncate block">
                    {m.summary ?? m.name ?? '—'}
                </span>
            ),
        },
        {
            key: 'status',
            label: 'Status',
            render: (m: PaymentMethod) => (
                <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold whitespace-nowrap ${statusStyles[m.status] ?? 'bg-slate-100 text-slate-600'}`}>
                    {statusLabel[m.status] ?? m.status}
                </span>
            ),
        },
        {
            key: 'created_at',
            label: 'Submitted',
            render: (m: PaymentMethod) => (
                <span className="text-sm text-slate-500 whitespace-nowrap">
                    {new Date(m.created_at).toLocaleDateString()}
                </span>
            ),
        },
        {
            key: 'actions',
            label: '',
            className: 'w-[60px] text-end',
            render: (m: PaymentMethod) => (
                <DropdownMenu>
                    <DropdownMenuTrigger
                        className="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100 hover:text-slate-900 transition-colors"
                        aria-label={__('general.open_actions_menu')}
                    >
                        <MoreHorizontal className="h-4 w-4" />
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-44">
                        <DropdownMenuLabel>{__('general.actions')}</DropdownMenuLabel>

                        <DropdownMenuItem>
                            <Link
                                href={route('admin.payment-methods.show', m.id)}
                                className="flex items-center w-full"
                            >
                                <Eye className="me-2 h-4 w-4" />{__('general.view_details')}</Link>
                        </DropdownMenuItem>

                        {m.status !== 'active' && (
                            <DropdownMenuItem
                                className="text-emerald-600 focus:text-emerald-600"
                                onClick={() => handleUpdate(m.id, 'active')}
                            >
                                <CheckCircle className="me-2 h-4 w-4" /> {__('general.approve')}</DropdownMenuItem>
                        )}

                        {m.status !== 'declined' && (
                            <DropdownMenuItem
                                className="text-red-600 focus:text-red-600"
                                onClick={() => handleUpdate(m.id, 'declined')}
                            >
                                <XCircle className="me-2 h-4 w-4" /> {__('general.decline')}</DropdownMenuItem>
                        )}

                        {m.status !== 'pending' && (
                            <DropdownMenuItem
                                className="text-slate-600"
                                onClick={() => handleUpdate(m.id, 'pending')}
                            >
                                <Clock className="me-2 h-4 w-4" />{__('general.reset_to_pending')}</DropdownMenuItem>
                        )}
                    </DropdownMenuContent>
                </DropdownMenu>
            ),
        },
    ];

    // ── Filter tabs ───────────────────────────────────────────────────────────

    const filterBar = (
        <div className="flex items-center gap-1">
            {[
                { value: '',         label: 'Active & Pending' },
                { value: 'pending',  label: 'Pending' },
                { value: 'active',   label: 'Approved' },
                { value: 'declined', label: 'Declined' },
            ].map((opt) => (
                <button
                    key={opt.value}
                    onClick={() => handleFilter(opt.value)}
                    className={`px-3 py-1.5 rounded-md text-xs font-medium transition-colors whitespace-nowrap ${
                        (filters.status ?? '') === opt.value
                            ? 'bg-slate-900 text-white'
                            : 'text-slate-600 hover:bg-slate-100'
                    }`}
                >
                    {opt.label}
                    {opt.value === 'pending' && stats.pending > 0 && (
                        <span className="ms-1.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-yellow-500 px-1 text-[10px] font-bold text-white">
                            {stats.pending}
                        </span>
                    )}
                </button>
            ))}
        </div>
    );

    // ─── Render ───────────────────────────────────────────────────────────────

    return (
        <AdminSidebarLayout title={__('general.payment_methods')} header="Payment Methods">
            <Head title={__('general.payment_methods')} />

            {/* Stats */}
            <div className="mb-6 grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col items-center justify-center">
                    <div className="flex items-center gap-1.5 mb-1">
                        <Layers className="h-4 w-4 text-slate-400" />
                        <span className="text-2xl font-semibold text-slate-800">{stats.total}</span>
                    </div>
                    <span className="text-xs text-slate-500 font-medium uppercase tracking-wider">{__('general.total')}</span>
                </div>
                <div className="bg-white rounded-xl border border-yellow-200 p-4 shadow-sm flex flex-col items-center justify-center">
                    <div className="flex items-center gap-1.5 mb-1">
                        <Clock className="h-4 w-4 text-yellow-500" />
                        <span className="text-2xl font-semibold text-yellow-600">{stats.pending}</span>
                    </div>
                    <span className="text-xs text-slate-500 font-medium uppercase tracking-wider">{__('general.pending')}</span>
                </div>
                <div className="bg-white rounded-xl border border-emerald-200 p-4 shadow-sm flex flex-col items-center justify-center">
                    <div className="flex items-center gap-1.5 mb-1">
                        <ShieldCheck className="h-4 w-4 text-emerald-500" />
                        <span className="text-2xl font-semibold text-emerald-600">{stats.active}</span>
                    </div>
                    <span className="text-xs text-slate-500 font-medium uppercase tracking-wider">{__('general.approved')}</span>
                </div>
                <div className="bg-white rounded-xl border border-red-200 p-4 shadow-sm flex flex-col items-center justify-center">
                    <div className="flex items-center gap-1.5 mb-1">
                        <ShieldX className="h-4 w-4 text-red-500" />
                        <span className="text-2xl font-semibold text-red-600">{stats.declined}</span>
                    </div>
                    <span className="text-xs text-slate-500 font-medium uppercase tracking-wider">{__('general.declined')}</span>
                </div>
            </div>

            {/* Table */}
            <div className="mb-6">
                <DataTable
                    columns={columns}
                    data={methods.data}
                    pagination={methods}
                    filters={{ ...filters, extra: filterBar }}
                    onSearch={handleSearch}
                    emptyTitle="No payment methods found"
                    emptyDescription="No payout methods match the current filter."
                />
            </div>
        </AdminSidebarLayout>
    );
}

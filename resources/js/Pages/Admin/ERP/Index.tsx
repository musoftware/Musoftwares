import React, { useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { 
    Building2, 
    Users, 
    FileText, 
    UserCheck, 
    DollarSign, 
    Search, 
    ArrowRight, 
    UserMinus, 
    Eye,
    TrendingUp,
    ShieldAlert
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Card, CardContent } from '@/Components/ui/card';

interface Tenant {
    id: number;
    name: string;
    owner_name: string;
    owner_email: string;
    status: string;
    client_count: number;
    invoice_count: number;
    team_count: number;
    revenue: number;
    created_at: string;
    user_id: number;
}

interface IndexProps {
    tenants: {
        data: Tenant[];
        links: any[];
        current_page: number;
        last_page: number;
    };
    filters: {
        search?: string;
    };
    stats: {
        total_tenants: number;
        active_tenants: number;
        total_revenue: number;
        total_team_members: number;
    };
    auth: {
        user: any;
    };
}

export default function Index({ tenants, filters, stats, auth }: IndexProps) {
    const [search, setSearch] = useState(filters.search || '');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(route('admin.erp.index'), { search }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleImpersonate = (userId: number) => {
        router.get(route('admin.erp.impersonate', userId));
    };

    return (
        <AdminLayout user={auth.user}>
            <Head title="ERP Admin Oversight" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-slate-900">ERP Workspaces Oversight</h1>
                    <p className="text-sm text-slate-500 mt-1">
                        Monitor active business tenant databases, system-wide billing statistics, and workspace health.
                    </p>
                </div>

                {/* KPI Cards Grid */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card className="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                        <CardContent className="p-5 flex items-center justify-between">
                            <div className="space-y-1">
                                <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Workspaces</span>
                                <h3 className="text-2xl font-bold text-slate-900">{stats.total_tenants}</h3>
                            </div>
                            <div className="p-3 bg-indigo-50 text-indigo-600 rounded-lg">
                                <Building2 className="h-6 w-6" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                        <CardContent className="p-5 flex items-center justify-between">
                            <div className="space-y-1">
                                <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Active Status</span>
                                <h3 className="text-2xl font-bold text-emerald-600">{stats.active_tenants}</h3>
                            </div>
                            <div className="p-3 bg-emerald-50 text-emerald-600 rounded-lg">
                                <TrendingUp className="h-6 w-6" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                        <CardContent className="p-5 flex items-center justify-between">
                            <div className="space-y-1">
                                <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Overall Platform Revenue</span>
                                <h3 className="text-2xl font-bold text-slate-900">
                                    {new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(stats.total_revenue)}
                                </h3>
                            </div>
                            <div className="p-3 bg-indigo-50 text-indigo-600 rounded-lg">
                                <DollarSign className="h-6 w-6" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                        <CardContent className="p-5 flex items-center justify-between">
                            <div className="space-y-1">
                                <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Active Staff</span>
                                <h3 className="text-2xl font-bold text-slate-900">{stats.total_team_members}</h3>
                            </div>
                            <div className="p-3 bg-indigo-50 text-indigo-600 rounded-lg">
                                <UserCheck className="h-6 w-6" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filter and Table Card */}
                <Card className="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div className="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <h3 className="font-semibold text-slate-800 text-sm">Workspace Registry</h3>
                        <form onSubmit={handleSearch} className="flex items-center gap-2 max-w-sm w-full">
                            <div className="relative w-full">
                                <Search className="absolute left-3 top-3 h-4 w-4 text-slate-400" />
                                <Input 
                                    type="text" 
                                    placeholder="Search by name, owner, or email..." 
                                    value={search}
                                    onChange={e => setSearch(e.target.value)}
                                    className="pl-9 h-10 shadow-none border-slate-200 focus-visible:ring-indigo-500"
                                />
                            </div>
                            <Button type="submit" size="sm" className="h-10 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow-none border-0">
                                Search
                            </Button>
                        </form>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm border-collapse">
                            <thead>
                                <tr className="bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    <th className="px-6 py-3.5">Workspace Name</th>
                                    <th className="px-6 py-3.5">Owner / Contact</th>
                                    <th className="px-6 py-3.5 text-center">Clients</th>
                                    <th className="px-6 py-3.5 text-center">Invoices</th>
                                    <th className="px-6 py-3.5 text-center">Team Size</th>
                                    <th className="px-6 py-3.5 text-right">Revenue</th>
                                    <th className="px-6 py-3.5">Created At</th>
                                    <th className="px-6 py-3.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {tenants.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={8} className="p-0">
                                            <div className="flex flex-col items-center justify-center p-12 text-center">
                                                <ShieldAlert className="h-12 w-12 text-slate-300 mb-4" />
                                                <h3 className="font-semibold text-slate-800 text-sm">No Workspaces Found</h3>
                                                <p className="text-xs text-slate-500 max-w-xs mt-1">
                                                    No tenants match your search filter or no tenants exist.
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                ) : (
                                    tenants.data.map((tenant) => (
                                        <tr key={tenant.id} className="hover:bg-slate-50 transition text-[13px] text-slate-700">
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-2.5">
                                                    <div className="h-8 w-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs">
                                                        {tenant.name.charAt(0).toUpperCase()}
                                                    </div>
                                                    <div>
                                                        <span className="font-semibold text-slate-900 block">{tenant.name}</span>
                                                        <span className="text-slate-400 text-xs mt-0.5 block">ID: #{tenant.id}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className="font-semibold text-slate-800 block">{tenant.owner_name}</span>
                                                <span className="text-slate-400 font-mono text-[11px] block mt-0.5">{tenant.owner_email}</span>
                                            </td>
                                            <td className="px-6 py-4 text-center font-medium font-mono text-slate-600">
                                                {tenant.client_count}
                                            </td>
                                            <td className="px-6 py-4 text-center font-medium font-mono text-slate-600">
                                                {tenant.invoice_count}
                                            </td>
                                            <td className="px-6 py-4 text-center">
                                                <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold ${
                                                    tenant.team_count > 0 ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-500'
                                                }`}>
                                                    {tenant.team_count} staff
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-right font-bold text-slate-900 font-mono">
                                                {new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(tenant.revenue)}
                                            </td>
                                            <td className="px-6 py-4 text-slate-500 font-mono text-xs">
                                                {tenant.created_at}
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Link 
                                                        href={route('admin.erp.show', tenant.id)}
                                                        className={route('admin.erp.show', tenant.id) ? "inline-flex items-center gap-1 px-2.5 py-1 rounded bg-slate-100 text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 font-semibold text-xs transition-colors" : ""}
                                                    >
                                                        <Eye className="h-3 w-3" /> Drilldown
                                                    </Link>
                                                    <Button 
                                                        size="sm"
                                                        onClick={() => handleImpersonate(tenant.user_id)}
                                                        className="h-7 px-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow-none border-0"
                                                    >
                                                        Impersonate
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination Links */}
                    {tenants.last_page > 1 && (
                        <div className="px-6 py-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 font-medium">
                            <span>
                                Page {tenants.current_page} of {tenants.last_page}
                            </span>
                            <div className="flex items-center gap-1">
                                {tenants.links.map((link, idx) => {
                                    if (link.url === null) return null;
                                    return (
                                        <Link 
                                            key={idx}
                                            href={link.url}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                            className={`px-3 py-1.5 rounded transition ${
                                                link.active 
                                                    ? 'bg-indigo-600 text-white font-bold' 
                                                    : 'hover:bg-slate-100 text-slate-600'
                                            }`}
                                        />
                                    );
                                })}
                            </div>
                        </div>
                    )}
                </Card>
            </div>
        </AdminLayout>
    );
}

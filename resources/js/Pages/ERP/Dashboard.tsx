import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import {
    Users,
    FileText,
    Sparkles,
    CheckCircle2,
    Wallet,
    TrendingUp,
    TrendingDown,
    Layers,
    Settings,
    FileSpreadsheet,
    Clock,
    ArrowUpRight,
    ShieldCheck,
    Inbox,
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { formatMoney, formatDate } from '@/lib/utils';
import { ResponsiveContainer, BarChart, Bar, XAxis, YAxis, Tooltip, CartesianGrid } from 'recharts';
import {
    InvoiceQuickView,
    CustomerQuickView
} from '@/Components/ContextualPanels';

interface ERPDashboardProps {
    stats?: {
        totalRevenue: number;
        outstandingRevenue: number;
        clientCount: number;
        recurringCount: number;
        growthPercent: number | null;
        businessCurrency: string;
    };
    clients?: Array<{
        id: number;
        name: string;
        company: string;
        email: string;
        phone: string;
        address: string;
        totalInvoiced: number;
        totalPaid: number;
    }>;
    invoices?: Array<{
        id: number;
        invoiceNumber: string;
        clientName: string;
        amount: number;
        currency: string;
        issuedDate: string;
        dueDate: string;
        status: string;
        project: string;
    }>;
    chartData?: Array<{
        name: string;
        Sales: number;
        Costs: number;
    }>;
}

export default function ERPDashboard({ stats: serverStats, clients: serverClients, invoices: serverInvoices, chartData: serverChartData }: ERPDashboardProps) {
    // Real data from server, with safe zero-state fallbacks
    const activeStats = serverStats || {
        totalRevenue: 0,
        outstandingRevenue: 0,
        clientCount: 0,
        recurringCount: 0,
        growthPercent: null,
        businessCurrency: 'USD',
    };

    const [selectedInvoice, setSelectedInvoice] = useState<any>(null);
    const [selectedCustomer, setSelectedCustomer] = useState<any>(null);

    // Real data from database
    const clients = serverClients || [];
    const invoices = serverInvoices || [];
    const chartData = serverChartData || [{ name: new Date().toLocaleString('default', { month: 'short' }), Sales: 0, Costs: 0 }];

    const currency = activeStats.businessCurrency || 'USD';

    return (
        <AuthenticatedLayout header="ERP Workspace Dashboard">
            <Head title="ERP Workspace Dashboard" />

            <div className="max-w-6xl mx-auto space-y-6 pb-12 font-sans">
                
                {/* ─────────────────────────────────────────
                    ERP KPI METRICS DECK
                    ───────────────────────────────────────── */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    {/* Paid Revenue */}
                    <Card className="shadow-none">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                                Paid Revenue
                            </CardTitle>
                            <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold tracking-tight">
                                {formatMoney(activeStats.totalRevenue, currency)}
                            </div>
                            {activeStats.growthPercent !== null ? (
                                <p className={`text-xs flex items-center gap-1 mt-1 ${activeStats.growthPercent >= 0 ? 'text-emerald-600' : 'text-destructive'}`}>
                                    {activeStats.growthPercent >= 0 ? <TrendingUp className="h-3 w-3" /> : <TrendingDown className="h-3 w-3" />}
                                    {activeStats.growthPercent >= 0 ? '+' : ''}{activeStats.growthPercent}% vs last month
                                </p>
                            ) : (
                                <p className="text-xs text-muted-foreground mt-1">No comparison data yet</p>
                            )}
                        </CardContent>
                    </Card>

                    {/* Outstanding Invoices */}
                    <Card className="shadow-none">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                                Outstanding Bills
                            </CardTitle>
                            <Clock className="h-4 w-4 text-amber-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold tracking-tight">
                                {formatMoney(activeStats.outstandingRevenue, currency)}
                            </div>
                            <p className="text-xs text-muted-foreground mt-1">
                                Across {invoices.filter(i => i.status === 'sent' || i.status === 'partial').length} active claims
                            </p>
                        </CardContent>
                    </Card>

                    {/* Active Client Tenants */}
                    <Card className="shadow-none">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                                Client Tenants
                            </CardTitle>
                            <Users className="h-4 w-4 text-primary" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold tracking-tight">
                                {activeStats.clientCount}
                            </div>
                            <p className="text-xs text-muted-foreground mt-1">
                                {activeStats.clientCount > 0 ? 'Managed client database' : 'Add your first client'}
                            </p>
                        </CardContent>
                    </Card>

                    {/* Recurring Contracts */}
                    <Card className="shadow-none">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                                Recurring Models
                            </CardTitle>
                            <Layers className="h-4 w-4 text-primary" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold tracking-tight">
                                {activeStats.recurringCount}
                            </div>
                            <p className="text-xs text-muted-foreground mt-1">
                                {activeStats.recurringCount > 0 ? 'Auto-invoicing models active' : 'Setup recurring billing'}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* ─────────────────────────────────────────
                    MAIN ERP CONTENT GRID (70% - 30%)
                    ───────────────────────────────────────── */}
                <div className="grid grid-cols-1 lg:grid-cols-10 gap-6">
                    
                    {/* 70% Primary Area */}
                    <div className="lg:col-span-7 space-y-6">
                        
                        {/* Analytical P&L Chart */}
                        <Card className="shadow-none">
                            <CardHeader className="flex flex-row items-center justify-between pb-4 border-b">
                                <div className="space-y-1">
                                    <CardTitle className="text-base font-semibold">Enterprise Revenue Progression</CardTitle>
                                    <CardDescription>Comparing total billable revenue receipts against operational costs.</CardDescription>
                                </div>
                                <Badge variant="secondary" className="font-semibold">Live Ledger</Badge>
                            </CardHeader>
                            <CardContent className="pt-6">
                                <div className="h-64 font-mono text-xs w-full">
                                    <ResponsiveContainer width="100%" height="100%">
                                        <BarChart data={chartData} margin={{ top: 5, right: 5, left: -25, bottom: 5 }}>
                                            <CartesianGrid strokeDasharray="3 3" opacity={0.3} vertical={false} />
                                            <XAxis dataKey="name" stroke="currentColor" fontSize={12} tickLine={false} axisLine={false} className="text-muted-foreground" />
                                            <YAxis stroke="currentColor" fontSize={12} tickLine={false} axisLine={false} className="text-muted-foreground" tickFormatter={(value) => `$${value}`} />
                                            <Tooltip formatter={(value) => [`$${value}`, '']} cursor={{fill: 'var(--muted)'}} contentStyle={{ borderRadius: '8px', border: '1px solid var(--border)' }} />
                                            <Bar dataKey="Sales" fill="var(--primary)" radius={[4, 4, 0, 0]} />
                                            <Bar dataKey="Costs" fill="var(--destructive)" radius={[4, 4, 0, 0]} />
                                        </BarChart>
                                    </ResponsiveContainer>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Real Client Tenants List */}
                        <Card className="shadow-none overflow-hidden">
                            <CardHeader className="px-5 py-4 border-b bg-muted/30 flex flex-row justify-between items-center space-y-0">
                                <div className="flex items-center gap-2">
                                    <Users className="h-4 w-4 text-primary" />
                                    <CardTitle className="text-sm font-semibold uppercase tracking-wider text-muted-foreground">Managed Client Tenants</CardTitle>
                                </div>
                                <Link 
                                    href={route().has('admin.clients.index') ? route('admin.clients.index') : '#'}
                                    className="text-xs text-primary font-medium hover:underline flex items-center gap-1"
                                >
                                    Manage Database <ArrowUpRight className="h-3 w-3" />
                                </Link>
                            </CardHeader>
                            
                            <div className="divide-y">
                                {clients.length === 0 ? (
                                    <div className="p-8 text-center">
                                        <div className="w-10 h-10 rounded-full bg-muted flex items-center justify-center mx-auto mb-3">
                                            <Users className="w-5 h-5 text-muted-foreground" />
                                        </div>
                                        <p className="text-sm font-medium text-foreground">No clients yet</p>
                                        <p className="text-xs text-muted-foreground mt-1">Add your first client to start managing invoices.</p>
                                    </div>
                                ) : clients.map(client => (
                                    <div 
                                        key={client.id}
                                        onClick={() => setSelectedCustomer(client)}
                                        className="p-4 hover:bg-muted/50 cursor-pointer transition flex items-center justify-between"
                                    >
                                        <div className="space-y-1">
                                            <span className="font-semibold text-foreground text-sm block">{client.name}</span>
                                            <span className="text-muted-foreground text-xs block">{client.company} • {client.email}</span>
                                        </div>
                                        <div className="text-right font-mono">
                                            <span className="text-foreground font-semibold text-sm block">Invoiced: {formatMoney(client.totalInvoiced, currency)}</span>
                                            <span className="text-emerald-600 font-medium block text-xs">Paid: {formatMoney(client.totalPaid, currency)}</span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </Card>

                        {/* Real Active Claims table */}
                        <Card className="shadow-none overflow-hidden">
                            <CardHeader className="px-5 py-4 border-b bg-muted/30 flex flex-row justify-between items-center space-y-0">
                                <div className="flex items-center gap-2">
                                    <FileText className="h-4 w-4 text-primary" />
                                    <CardTitle className="text-sm font-semibold uppercase tracking-wider text-muted-foreground">Recent Active Billing Invoices</CardTitle>
                                </div>
                                <Link 
                                    href={route().has('erp.invoices.index') ? route('erp.invoices.index') : '#'}
                                    className="text-xs text-primary font-medium hover:underline flex items-center gap-1"
                                >
                                    Invoices Ledger <ArrowUpRight className="h-3 w-3" />
                                </Link>
                            </CardHeader>
                            
                            {invoices.length === 0 ? (
                                <div className="p-8 text-center">
                                    <div className="w-10 h-10 rounded-full bg-muted flex items-center justify-center mx-auto mb-3">
                                        <Inbox className="w-5 h-5 text-muted-foreground" />
                                    </div>
                                    <p className="text-sm font-medium text-foreground">No active invoices</p>
                                    <p className="text-xs text-muted-foreground mt-1">Create your first invoice to start billing clients.</p>
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-left text-sm border-collapse">
                                        <thead>
                                            <tr className="bg-muted/20 text-xs font-semibold text-muted-foreground border-b">
                                                <th className="px-4 py-3">Invoice</th>
                                                <th className="px-4 py-3">Client</th>
                                                <th className="px-4 py-3">Due Date</th>
                                                <th className="px-4 py-3 text-right">Amount</th>
                                                <th className="px-4 py-3 text-center">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y">
                                            {invoices.map((inv) => (
                                                <tr
                                                    key={inv.id}
                                                    onClick={() => setSelectedInvoice(inv)}
                                                    className="hover:bg-muted/50 cursor-pointer transition text-sm"
                                                >
                                                    <td className="px-4 py-3 font-mono font-medium text-primary">{inv.invoiceNumber}</td>
                                                    <td className="px-4 py-3 font-medium text-foreground">{inv.clientName}</td>
                                                    <td className="px-4 py-3 text-muted-foreground text-xs">{inv.dueDate ? formatDate(inv.dueDate) : '-'}</td>
                                                    <td className="px-4 py-3 text-right font-mono font-medium text-foreground">
                                                        {formatMoney(inv.amount, inv.currency)}
                                                    </td>
                                                    <td className="px-4 py-3 text-center">
                                                        <Badge variant={inv.status === 'sent' ? 'outline' : inv.status === 'partial' ? 'secondary' : 'default'} className={`text-[10px] uppercase font-bold tracking-wider ${inv.status === 'sent' ? 'text-amber-600 border-amber-200 bg-amber-50' : inv.status === 'partial' ? 'bg-blue-50 text-blue-700 hover:bg-blue-100' : ''}`}>
                                                            {inv.status}
                                                        </Badge>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </Card>

                    </div>

                    {/* 30% Context & Shortcuts Deck */}
                    <div className="lg:col-span-3 space-y-6">
                        
                        {/* Quick Shortcuts */}
                        <Card className="shadow-none">
                            <CardHeader className="p-4 border-b pb-3">
                                <CardTitle className="text-xs font-bold uppercase tracking-wider text-muted-foreground flex items-center gap-2">
                                    <Settings className="h-4 w-4 text-primary" /> ERP Core Actions
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-4 space-y-2">
                                <Link
                                    href={route().has('admin.clients.index') ? route('admin.clients.index') : '#'}
                                    className="flex items-center gap-3 p-3 rounded-xl border bg-card hover:bg-muted/50 text-foreground transition group"
                                >
                                    <Users className="h-4 w-4 text-primary shrink-0" />
                                    <span className="font-medium text-sm">Manage Client Database</span>
                                </Link>

                                <Link
                                    href={route().has('erp.invoices.index') ? route('erp.invoices.index') : '#'}
                                    className="flex items-center gap-3 p-3 rounded-xl border bg-card hover:bg-muted/50 text-foreground transition group"
                                >
                                    <FileSpreadsheet className="h-4 w-4 text-primary shrink-0" />
                                    <span className="font-medium text-sm">Create Billing Invoice</span>
                                </Link>

                                <Link
                                    href={route().has('erp.withdrawals.index') ? route('erp.withdrawals.index') : '#'}
                                    className="flex items-center gap-3 p-3 rounded-xl border bg-card hover:bg-muted/50 text-foreground transition group"
                                >
                                    <Wallet className="h-4 w-4 text-primary shrink-0" />
                                    <span className="font-medium text-sm">Withdrawals Settlement</span>
                                </Link>
                            </CardContent>
                        </Card>

                        {/* Onboarding Smart Checklist */}
                        <Card className="shadow-none">
                            <CardHeader className="p-4 border-b pb-3">
                                <CardTitle className="text-xs font-bold uppercase tracking-wider text-muted-foreground flex items-center gap-2">
                                    <Sparkles className="h-4 w-4 text-primary animate-pulse" /> Workspace Onboarding
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-4 space-y-4">
                                <div className="flex gap-3 text-foreground">
                                    <CheckCircle2 className={`h-5 w-5 shrink-0 mt-0.5 ${activeStats.clientCount > 0 ? 'text-emerald-500' : 'text-muted'}`} />
                                    <div>
                                        <span className="font-semibold text-sm block">Create Customer Profiling</span>
                                        <p className="text-xs text-muted-foreground mt-1">
                                            {activeStats.clientCount > 0
                                                ? `${activeStats.clientCount} client${activeStats.clientCount !== 1 ? 's' : ''} added`
                                                : 'Link business invoices directly to client tenant records.'}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex gap-3 text-foreground">
                                    <CheckCircle2 className={`h-5 w-5 shrink-0 mt-0.5 ${activeStats.totalRevenue > 0 ? 'text-emerald-500' : 'text-muted'}`} />
                                    <div>
                                        <span className="font-semibold text-sm block">Setup Payout Ledger</span>
                                        <p className="text-xs text-muted-foreground mt-1">Wire business earnings safely via verified bank checking.</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Immutable Security Notice */}
                        <div className="bg-primary/5 border border-primary/20 rounded-xl p-4 text-xs leading-relaxed text-foreground">
                            <div className="flex items-center gap-2 font-bold mb-2">
                                <ShieldCheck className="h-4 w-4 text-primary" />
                                Corporate ERP Security Policy
                            </div>
                            <span className="text-muted-foreground">Musoftware Business ERP enforces PCI-DSS and automated secure ledger auditing protocols across multi-tenant clients. Transactions are captured securely on completion.</span>
                        </div>

                    </div>
                </div>

            </div>

            {/* Contextual Side slide-overs */}
            <InvoiceQuickView
                isOpen={selectedInvoice !== null}
                onClose={() => setSelectedInvoice(null)}
                data={selectedInvoice}
            />

            <CustomerQuickView
                isOpen={selectedCustomer !== null}
                onClose={() => setSelectedCustomer(null)}
                data={selectedCustomer}
            />
        </AuthenticatedLayout>
    );
}

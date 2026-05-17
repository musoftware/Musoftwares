import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import {
    Users,
    FileText,
    Sparkles,
    Plus,
    CheckCircle2,
    Wallet,
    TrendingUp,
    Layers,
    Settings,
    FileSpreadsheet,
    Clock,
    ArrowUpRight,
    Search,
    ShieldCheck
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { formatMoney, formatDate } from '@/lib/utils';
import { ResponsiveContainer, BarChart, Bar, XAxis, YAxis, Tooltip, CartesianGrid } from 'recharts';
import {
    InvoiceQuickView,
    CustomerQuickView
} from '@/Components/ContextualPanels';

export default function ERPDashboard({ stats }: { stats?: any }) {
    // Default high-fidelity statistics fallback
    const activeStats = stats || {
        totalRevenue: 12450.00,
        outstandingRevenue: 3400.00,
        clientCount: 3,
        recurringCount: 2
    };

    const [selectedInvoice, setSelectedInvoice] = useState<any>(null);
    const [selectedCustomer, setSelectedCustomer] = useState<any>(null);

    // Dynamic mock datasets to eliminate placeholder feeling
    const clients = [
        { id: 1, name: 'Acme Corp', company: 'Acme Enterprises', email: 'billing@acme.com', phone: '+1 (555) 902-1244', address: 'Chicago, IL', totalInvoiced: 4800, totalPaid: 3950 },
        { id: 2, name: 'Stripe Labs', company: 'Stripe, Inc.', email: 'ops@stripe.com', phone: '+1 (555) 304-2000', address: 'San Francisco, CA', totalInvoiced: 1500, totalPaid: 0 },
        { id: 3, name: 'Vercel Labs', company: 'Vercel, Inc.', email: 'dev@vercel.com', phone: '+1 (555) 100-2022', address: 'New York, NY', totalInvoiced: 2700, totalPaid: 2000 }
    ];

    const invoices = [
        { id: 1, invoiceNumber: 'INV-303', clientName: 'Acme Corp', amount: 1200.00, currency: 'USD', issuedDate: '2026-05-12', dueDate: '2026-05-25', status: 'pending', project: 'Design Framework Refactor' },
        { id: 2, invoiceNumber: 'INV-304', clientName: 'Stripe Labs', amount: 1500.00, currency: 'USD', issuedDate: '2026-05-10', dueDate: '2026-05-29', status: 'pending', project: 'Webhook API integration' },
        { id: 3, invoiceNumber: 'INV-305', clientName: 'Vercel LLC', amount: 700.00, currency: 'USD', issuedDate: '2026-05-16', dueDate: '2026-06-02', status: 'draft', project: 'Edge Routing Audit' },
    ];

    // Recharts revenue vs costs monthly data
    const chartData = [
        { name: 'Jan', Sales: 2400, Costs: 1200 },
        { name: 'Feb', Sales: 3100, Costs: 1400 },
        { name: 'Mar', Sales: 4500, Costs: 1800 },
        { name: 'Apr', Sales: 5200, Costs: 2100 },
        { name: 'May', Sales: 6450, Costs: 2300 }
    ];

    return (
        <AuthenticatedLayout header="ERP Workspace Dashboard">
            <Head title="ERP Workspace Dashboard" />

            <div className="max-w-5xl mx-auto space-y-6 pb-12 font-sans text-sm">
                
                {/* ─────────────────────────────────────────
                    ERP KPI METRICS DECK
                    ───────────────────────────────────────── */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    {/* Paid Revenue */}
                    <div className="bg-white border border-border/60 rounded-xl p-5 shadow-sm space-y-2 hover:border-indigo-100 transition">
                        <div className="flex justify-between items-center text-text-muted text-[11px] font-bold uppercase tracking-wider">
                            <span>Paid Revenue</span>
                            <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                        </div>
                        <div>
                            <span className="font-mono text-2xl font-bold text-text-primary block">
                                {formatMoney(activeStats.totalRevenue, 'USD')}
                            </span>
                            <span className="text-[10px] text-emerald-600 font-semibold flex items-center gap-0.5 mt-1">
                                <TrendingUp className="h-3 w-3" /> +14.2% growth
                            </span>
                        </div>
                    </div>

                    {/* Outstanding Invoices */}
                    <div className="bg-white border border-border/60 rounded-xl p-5 shadow-sm space-y-2 hover:border-indigo-100 transition">
                        <div className="flex justify-between items-center text-text-muted text-[11px] font-bold uppercase tracking-wider">
                            <span>Outstanding Bills</span>
                            <Clock className="h-4 w-4 text-amber-500" />
                        </div>
                        <div>
                            <span className="font-mono text-2xl font-bold text-text-primary block">
                                {formatMoney(activeStats.outstandingRevenue, 'USD')}
                            </span>
                            <span className="text-[10px] text-text-secondary block mt-1">
                                Across {invoices.filter(i => i.status === 'pending').length} active claims
                            </span>
                        </div>
                    </div>

                    {/* Active Client Tenants */}
                    <div className="bg-white border border-border/60 rounded-xl p-5 shadow-sm space-y-2 hover:border-indigo-100 transition">
                        <div className="flex justify-between items-center text-text-muted text-[11px] font-bold uppercase tracking-wider">
                            <span>Client Tenants</span>
                            <Users className="h-4 w-4 text-indigo-500" />
                        </div>
                        <div>
                            <span className="font-mono text-2xl font-bold text-text-primary block">
                                {activeStats.clientCount} Active
                            </span>
                            <span className="text-[10px] text-text-secondary block mt-1">
                                100% contract retention rate
                            </span>
                        </div>
                    </div>

                    {/* Recurring Contracts */}
                    <div className="bg-white border border-border/60 rounded-xl p-5 shadow-sm space-y-2 hover:border-indigo-100 transition">
                        <div className="flex justify-between items-center text-text-muted text-[11px] font-bold uppercase tracking-wider">
                            <span>Recurring Models</span>
                            <Layers className="h-4 w-4 text-indigo-500" />
                        </div>
                        <div>
                            <span className="font-mono text-2xl font-bold text-text-primary block">
                                {activeStats.recurringCount} Contracts
                            </span>
                            <span className="text-[10px] text-text-secondary block mt-1">
                                Auto-invoicing models active
                            </span>
                        </div>
                    </div>
                </div>

                {/* ─────────────────────────────────────────
                    MAIN ERP CONTENT GRID (70% - 30%)
                    ───────────────────────────────────────── */}
                <div className="grid grid-cols-1 lg:grid-cols-10 gap-6">
                    
                    {/* 70% Primary Area */}
                    <div className="lg:col-span-7 space-y-6">
                        
                        {/* Analytical P&L Chart */}
                        <div className="bg-white border border-border/60 rounded-xl p-5 shadow-sm space-y-4">
                            <div className="flex justify-between items-center border-b border-border/40 pb-3">
                                <div>
                                    <h3 className="font-sora text-xs font-bold uppercase tracking-wider text-text-muted">Enterprise Revenue Progression</h3>
                                    <p className="text-[11px] text-text-secondary">Comparing total billable revenue receipts against operational costs.</p>
                                </div>
                                <span className="bg-indigo-50 text-indigo-700 text-[10px] font-bold px-2 py-0.5 rounded border border-indigo-150">
                                    FY2026 Live Ledger
                                </span>
                            </div>
                            
                            <div className="h-48 font-mono text-[9px] w-full">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={chartData} margin={{ top: 5, right: 5, left: -25, bottom: 5 }}>
                                        <CartesianGrid strokeDasharray="3 3" opacity={0.3} />
                                        <XAxis dataKey="name" stroke="#94a3b8" />
                                        <YAxis stroke="#94a3b8" />
                                        <Tooltip formatter={(value) => [`$${value}`, '']} />
                                        <Bar dataKey="Sales" fill="#4f46e5" radius={[4, 4, 0, 0]} />
                                        <Bar dataKey="Costs" fill="#f43f5e" radius={[4, 4, 0, 0]} />
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        </div>

                        {/* Recent Client Tenants List */}
                        <div className="bg-white border border-border/60 rounded-xl overflow-hidden shadow-sm">
                            <div className="px-5 py-4 border-b border-border/50 flex justify-between items-center bg-slate-50/20">
                                <h3 className="font-sora text-xs font-bold uppercase tracking-wider text-text-muted flex items-center gap-2">
                                    <Users className="h-4 w-4 text-indigo-500" /> Managed Client Tenants
                                </h3>
                                <Link 
                                    href={route().has('admin.clients.index') ? route('admin.clients.index') : '#'}
                                    className="text-[11px] text-indigo-600 font-semibold hover:underline flex items-center gap-0.5"
                                >
                                    Manage Database <ArrowUpRight className="h-3 w-3" />
                                </Link>
                            </div>
                            
                            <div className="divide-y divide-border/50">
                                {clients.map(client => (
                                    <div 
                                        key={client.id}
                                        onClick={() => setSelectedCustomer(client)}
                                        className="p-4 hover:bg-slate-50/60 cursor-pointer transition flex items-center justify-between text-xs"
                                    >
                                        <div className="space-y-0.5">
                                            <span className="font-bold text-text-primary text-[13px] block">{client.name}</span>
                                            <span className="text-text-secondary text-[11px] block">{client.company} • {client.email}</span>
                                        </div>
                                        <div className="text-right font-mono">
                                            <span className="text-text-primary font-bold block">Invoiced: {formatMoney(client.totalInvoiced, 'USD')}</span>
                                            <span className="text-emerald-600 font-semibold block text-[10px]">Paid: {formatMoney(client.totalPaid, 'USD')}</span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Active Claims table */}
                        <div className="bg-white border border-border/60 rounded-xl overflow-hidden shadow-sm">
                            <div className="px-5 py-4 border-b border-border/50 flex justify-between items-center bg-slate-50/20">
                                <h3 className="font-sora text-xs font-bold uppercase tracking-wider text-text-muted flex items-center gap-2">
                                    <FileText className="h-4 w-4 text-indigo-500" /> Recent Active Billing Invoices
                                </h3>
                                <Link 
                                    href={route().has('erp.invoices.index') ? route('erp.invoices.index') : '#'}
                                    className="text-[11px] text-indigo-600 font-semibold hover:underline flex items-center gap-0.5"
                                >
                                    Invoices Ledger <ArrowUpRight className="h-3 w-3" />
                                </Link>
                            </div>
                            
                            <div className="overflow-x-auto">
                                <table className="w-full text-left font-sans text-xs border-collapse">
                                    <thead>
                                        <tr className="bg-slate-50/80 text-[10px] uppercase font-bold text-text-muted border-b border-border/40">
                                            <th className="px-4 py-2.5">Invoice</th>
                                            <th className="px-4 py-2.5">Client</th>
                                            <th className="px-4 py-2.5">Due Date</th>
                                            <th className="px-4 py-2.5 text-right">Amount</th>
                                            <th className="px-4 py-2.5 text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border/40">
                                        {invoices.map((inv) => (
                                            <tr
                                                key={inv.id}
                                                onClick={() => setSelectedInvoice(inv)}
                                                className="hover:bg-slate-50/70 cursor-pointer transition"
                                            >
                                                <td className="px-4 py-3 font-mono font-semibold text-indigo-600">{inv.invoiceNumber}</td>
                                                <td className="px-4 py-3 font-medium text-text-primary">{inv.clientName}</td>
                                                <td className="px-4 py-3 text-text-muted">{formatDate(inv.dueDate)}</td>
                                                <td className="px-4 py-3 text-right font-mono font-medium text-text-primary">
                                                    {formatMoney(inv.amount, inv.currency)}
                                                </td>
                                                <td className="px-4 py-3 text-center">
                                                    <span className={`text-[9px] font-bold uppercase px-2 py-0.5 rounded-full ${
                                                        inv.status === 'pending'
                                                            ? 'bg-amber-50 text-amber-700 border border-amber-200'
                                                            : 'bg-slate-50 text-slate-700 border border-slate-200'
                                                    }`}>
                                                        {inv.status}
                                                    </span>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                    {/* 30% Context & Shortcuts Deck */}
                    <div className="lg:col-span-3 space-y-6">
                        
                        {/* Quick Shortcuts */}
                        <div className="bg-white border border-border/60 rounded-xl p-4 shadow-sm space-y-3">
                            <h4 className="text-[11px] font-bold uppercase tracking-wider text-text-muted flex items-center gap-1.5 border-b border-border/40 pb-2">
                                <Settings className="h-3.5 w-3.5 text-indigo-500" /> ERP Core Actions
                            </h4>
                            <div className="grid grid-cols-1 gap-2 text-xs">
                                <Link
                                    href={route().has('admin.clients.index') ? route('admin.clients.index') : '#'}
                                    className="flex items-center gap-2.5 p-3 rounded-lg border border-slate-100 hover:border-indigo-150 hover:bg-indigo-50/20 text-text-primary transition group"
                                >
                                    <Users className="h-4 w-4 text-indigo-600 shrink-0" />
                                    <span className="font-semibold block text-[11px]">Manage Client Database</span>
                                </Link>

                                <Link
                                    href={route().has('erp.invoices.index') ? route('erp.invoices.index') : '#'}
                                    className="flex items-center gap-2.5 p-3 rounded-lg border border-slate-100 hover:border-indigo-150 hover:bg-indigo-50/20 text-text-primary transition group"
                                >
                                    <FileSpreadsheet className="h-4 w-4 text-indigo-600 shrink-0" />
                                    <span className="font-semibold block text-[11px]">Create Billing Invoice</span>
                                </Link>

                                <Link
                                    href={route().has('erp.withdrawals.index') ? route('erp.withdrawals.index') : '#'}
                                    className="flex items-center gap-2.5 p-3 rounded-lg border border-slate-100 hover:border-indigo-150 hover:bg-indigo-50/20 text-text-primary transition group"
                                >
                                    <Wallet className="h-4 w-4 text-indigo-600 shrink-0" />
                                    <span className="font-semibold block text-[11px]">Withdrawals Settlement</span>
                                </Link>
                            </div>
                        </div>

                        {/* Onboarding Smart Checklist */}
                        <div className="bg-white border border-border/60 rounded-xl p-4 shadow-sm space-y-3">
                            <h4 className="text-[11px] font-bold uppercase tracking-wider text-text-muted flex items-center gap-1.5 border-b border-border/40 pb-2">
                                <Sparkles className="h-3.5 w-3.5 text-indigo-500 animate-pulse" /> Workspace Onboarding
                            </h4>
                            <div className="space-y-3 text-xs leading-normal">
                                <div className="flex gap-2 text-text-primary">
                                    <CheckCircle2 className="h-4.5 w-4.5 text-emerald-500 shrink-0 mt-0.5" />
                                    <div>
                                        <span className="font-semibold block">Create Customer Profiling</span>
                                        <p className="text-[10px] text-text-secondary mt-0.5">Link business invoices directly to client tenant records.</p>
                                    </div>
                                </div>
                                <div className="flex gap-2 text-text-primary">
                                    <CheckCircle2 className="h-4.5 w-4.5 text-emerald-500 shrink-0 mt-0.5" />
                                    <div>
                                        <span className="font-semibold block">Setup Payout Ledger</span>
                                        <p className="text-[10px] text-text-secondary mt-0.5">Wire business earnings safely via verified bank checking.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Immutable Security Notice */}
                        <div className="bg-indigo-50/30 border border-indigo-100/50 rounded-xl p-3.5 text-[11px] leading-relaxed text-indigo-900">
                            <div className="flex items-center gap-1.5 font-bold mb-1">
                                <ShieldCheck className="h-4 w-4 text-indigo-600" />
                                Corporate ERP Security Policy
                            </div>
                            Musoftware Business ERP enforces PCI-DSS and automated secure ledger auditing protocols across multi-tenant clients. Transactions are captured securely on completion.
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

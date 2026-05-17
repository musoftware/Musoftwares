import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    InvoiceQuickView,
    WalletQuickView,
    ContractQuickView,
    CustomerQuickView,
    ServiceQuickView
} from '@/Components/ContextualPanels';
import {
    Wallet,
    FileText,
    Briefcase,
    Gift,
    Clock,
    Plus,
    CheckCircle2,
    ArrowUpRight,
    Search,
    UserCheck,
    Bell,
    Lock,
    ShieldCheck
} from 'lucide-react';
import { formatMoney, formatDate } from '@/lib/utils';

export default function Dashboard() {
    const { auth } = usePage().props as any;
    const user = auth?.user;

    // Controls for contextual slide-over panels
    const [selectedInvoice, setSelectedInvoice] = useState<any>(null);
    const [selectedWallet, setSelectedWallet] = useState<any>(null);
    const [selectedContract, setSelectedContract] = useState<any>(null);
    const [selectedCustomer, setSelectedCustomer] = useState<any>(null);
    const [selectedService, setSelectedService] = useState<any>(null);

    // Dynamic mock datasets matching workspace models
    const invoices = [
        { id: 1, invoiceNumber: 'INV-303', clientName: 'Acme Corp', amount: 1200.00, currency: 'USD', issuedDate: '2026-05-12', dueDate: '2026-05-25', status: 'pending', project: 'Design Framework Refactor' },
        { id: 2, invoiceNumber: 'INV-304', clientName: 'Stripe Labs', amount: 1500.00, currency: 'USD', issuedDate: '2026-05-10', dueDate: '2026-05-29', status: 'pending', project: 'Webhook API integration' },
        { id: 3, invoiceNumber: 'INV-305', clientName: 'Vercel LLC', amount: 700.00, currency: 'USD', issuedDate: '2026-05-16', dueDate: '2026-06-02', status: 'draft', project: 'Edge Routing Audit' },
    ];

    const contracts = [
        { id: 1, title: 'Modular SaaS Dashboard Refactor', clientName: 'Musoftware Inc', value: 4500.00, progress: 75, status: 'in progress', startDate: '2026-05-01' },
        { id: 2, title: 'API Gateway Speed Optimization', clientName: 'Vercel Labs', value: 2000.00, progress: 40, status: 'in progress', startDate: '2026-05-10' },
    ];

    const transactions = [
        { desc: 'Invoice payment credit INV-302', amount: 850.00, date: '2026-05-16', type: 'credit', category: 'Invoicing' },
        { desc: 'Referral Bounty Credit', amount: 50.00, date: '2026-05-14', type: 'credit', category: 'Referrals' },
        { desc: 'Withdrawal to Wise Account', amount: -200.00, date: '2026-05-10', type: 'debit', category: 'Settlement' },
    ];

    return (
        <AuthenticatedLayout header="Workspace Operations">
            <Head title="Client Dashboard" />

            <div className="max-w-5xl mx-auto space-y-6 pb-12 font-sans text-sm">
                
                {/* ─────────────────────────────────────────
                    PART 3 — COMPACT WELCOME HEADER
                    ───────────────────────────────────────── */}
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-2">
                    <div>
                        <h2 className="text-base font-bold text-text-primary tracking-tight font-sora">
                            Welcome back, {user?.name || 'Administrator'}
                        </h2>
                        <p className="text-[11px] text-text-secondary">
                            Your operational workspace is fully synced. Review pending items and clear invoice actions below.
                        </p>
                    </div>
                    <div className="flex items-center gap-2 font-mono text-[10px] text-text-secondary bg-slate-50 border border-slate-100 px-2 py-0.5 rounded">
                        <span>Cleared: {formatDate(new Date(), 'MMM d, yyyy')}</span>
                    </div>
                </div>

                {/* ─────────────────────────────────────────
                    PART 6 — MINIMAL KPI ROW
                    ───────────────────────────────────────── */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    {/* Wallet Balance */}
                    <div 
                        onClick={() => setSelectedWallet({ balance: 1250.45 })}
                        className="bg-white border border-border/60 rounded-xl p-4 shadow-sm hover:border-indigo-150 transition cursor-pointer space-y-1.5"
                    >
                        <div className="flex justify-between items-center text-text-muted text-[10px] font-bold uppercase tracking-wider">
                            <span>Wallet Balance</span>
                            <Wallet className="h-3.5 w-3.5 text-indigo-500" />
                        </div>
                        <div>
                            <span className="font-mono text-lg font-bold text-text-primary block">
                                {formatMoney(1250.45, 'USD')}
                            </span>
                            <span className="text-[10px] text-emerald-600 font-semibold block mt-0.5">
                                +8% this month
                            </span>
                        </div>
                    </div>

                    {/* Pending Invoices */}
                    <div 
                        onClick={() => setSelectedInvoice(invoices[0])}
                        className="bg-white border border-border/60 rounded-xl p-4 shadow-sm hover:border-indigo-150 transition cursor-pointer space-y-1.5"
                    >
                        <div className="flex justify-between items-center text-text-muted text-[10px] font-bold uppercase tracking-wider">
                            <span>Pending Invoices</span>
                            <FileText className="h-3.5 w-3.5 text-indigo-500" />
                        </div>
                        <div>
                            <span className="font-mono text-lg font-bold text-text-primary block">
                                {invoices.filter(i => i.status === 'pending').length} Invoices
                            </span>
                            <span className="text-[10px] text-text-secondary block mt-0.5">
                                Aggregate: {formatMoney(2700.00, 'USD')}
                            </span>
                        </div>
                    </div>

                    {/* Active Contracts */}
                    <div 
                        onClick={() => setSelectedContract(contracts[0])}
                        className="bg-white border border-border/60 rounded-xl p-4 shadow-sm hover:border-indigo-150 transition cursor-pointer space-y-1.5"
                    >
                        <div className="flex justify-between items-center text-text-muted text-[10px] font-bold uppercase tracking-wider">
                            <span>Active Projects</span>
                            <Briefcase className="h-3.5 w-3.5 text-indigo-500" />
                        </div>
                        <div>
                            <span className="font-mono text-lg font-bold text-text-primary block">
                                {contracts.length} Contracts
                            </span>
                            <span className="text-[10px] text-text-secondary block mt-0.5">
                                Next milestone: May 25
                            </span>
                        </div>
                    </div>

                    {/* Recent Earnings */}
                    <div className="bg-white border border-border/60 rounded-xl p-4 shadow-sm space-y-1.5 hover:border-indigo-100 transition">
                        <div className="flex justify-between items-center text-text-muted text-[10px] font-bold uppercase tracking-wider">
                            <span>Recent Earnings</span>
                            <Gift className="h-3.5 w-3.5 text-emerald-500" />
                        </div>
                        <div>
                            <span className="font-mono text-lg font-bold text-text-primary block">
                                {formatMoney(180.00, 'USD')}
                            </span>
                            <span className="text-[10px] text-indigo-600 font-semibold block mt-0.5">
                                4 active invites
                            </span>
                        </div>
                    </div>
                </div>

                {/* ─────────────────────────────────────────
                    MAIN OPERATIONAL AREA (70% - 30%)
                    ───────────────────────────────────────── */}
                <div className="grid grid-cols-1 lg:grid-cols-10 gap-6">
                    
                    {/* 70% Left Section */}
                    <div className="lg:col-span-7 space-y-6">
                        
                        {/* PART 7 — ACTIONABLE WORKFLOW FEED */}
                        <div className="bg-white border border-border/60 rounded-xl overflow-hidden shadow-sm">
                            <div className="px-5 py-3.5 border-b border-border/40 flex justify-between items-center bg-slate-50/20">
                                <h3 className="font-sora text-xs font-bold uppercase tracking-wider text-text-muted flex items-center gap-1.5">
                                    <Clock className="h-4 w-4 text-indigo-500" /> High-Priority Actions Required
                                </h3>
                                <span className="bg-amber-50 text-amber-700 text-[10px] font-semibold px-2 py-0.5 rounded border border-amber-200">
                                    Needs Review
                                </span>
                            </div>
                            
                            <div className="divide-y divide-border/40 text-xs">
                                {/* Action Item 1 */}
                                <div 
                                    onClick={() => setSelectedInvoice(invoices[0])}
                                    className="p-4 hover:bg-slate-50/60 transition cursor-pointer flex items-center justify-between"
                                >
                                    <div className="space-y-0.5">
                                        <span className="font-semibold text-text-primary text-[13px] block">Invoice INV-303 Requires Payment</span>
                                        <span className="text-[11px] text-text-secondary block">Client: Acme Corp • Due in 8 days</span>
                                    </div>
                                    <div className="text-right">
                                        <span className="font-mono font-bold text-text-primary block">{formatMoney(1200.00, 'USD')}</span>
                                        <span className="text-[9px] uppercase font-bold text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.2 rounded mt-0.5 inline-block">
                                            Pay claim
                                        </span>
                                    </div>
                                </div>

                                {/* Action Item 2 */}
                                <div 
                                    onClick={() => setSelectedContract(contracts[0])}
                                    className="p-4 hover:bg-slate-50/60 transition cursor-pointer flex items-center justify-between"
                                >
                                    <div className="space-y-0.5">
                                        <span className="font-semibold text-text-primary text-[13px] block">SaaS Dashboard Refactor Milestone Approval</span>
                                        <span className="text-[11px] text-text-secondary block">Milestone 2: High fidelity charts integration (75% completed)</span>
                                    </div>
                                    <div className="text-right">
                                        <span className="font-mono font-bold text-text-primary block">{formatMoney(4500.00, 'USD')}</span>
                                        <span className="text-[9px] uppercase font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-1.5 py-0.2 rounded mt-0.5 inline-block">
                                            Approve release
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Recent Transactions Ledger */}
                        <div className="bg-white border border-border/60 rounded-xl overflow-hidden shadow-sm">
                            <div className="px-5 py-3.5 border-b border-border/40 flex justify-between items-center bg-slate-50/20">
                                <h3 className="font-sora text-xs font-bold uppercase tracking-wider text-text-muted flex items-center gap-1.5">
                                    <CheckCircle2 className="h-4 w-4 text-emerald-500" /> Recent Transactions Ledger
                                </h3>
                                <Link 
                                    href="/erp/invoices"
                                    className="text-[11px] text-indigo-600 font-semibold hover:underline flex items-center gap-0.5"
                                >
                                    All Ledger <ArrowUpRight className="h-3 w-3" />
                                </Link>
                            </div>
                            
                            <div className="overflow-x-auto">
                                <table className="w-full text-left font-sans text-xs border-collapse">
                                    <thead>
                                        <tr className="bg-slate-50/80 text-[10px] uppercase font-bold text-text-muted border-b border-border/40">
                                            <th className="px-4 py-2">Details</th>
                                            <th className="px-4 py-2">Category</th>
                                            <th className="px-4 py-2">Cleared Date</th>
                                            <th className="px-4 py-2 text-right">Settled Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border/40">
                                        {transactions.map((tx, idx) => (
                                            <tr key={idx} className="hover:bg-slate-50/40 transition">
                                                <td className="px-4 py-2.5 font-medium text-text-primary">{tx.desc}</td>
                                                <td className="px-4 py-2.5 text-text-secondary">{tx.category}</td>
                                                <td className="px-4 py-2.5 text-text-muted">{formatDate(tx.date)}</td>
                                                <td className={`px-4 py-2.5 text-right font-mono font-medium ${
                                                    tx.amount > 0 ? 'text-emerald-600' : 'text-text-primary'
                                                }`}>
                                                    {tx.amount > 0 ? '+' : ''}{formatMoney(tx.amount, 'USD')}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                    {/* 30% Right Section (Contextual Side Widgets) */}
                    <div className="lg:col-span-3 space-y-6">
                        
                        {/* PART 8 — QUICK ACTIONS */}
                        <div className="bg-white border border-border/60 rounded-xl p-4 shadow-sm space-y-3">
                            <h4 className="text-[10px] font-bold uppercase tracking-wider text-text-muted border-b border-border/40 pb-2">
                                Operations Actions
                            </h4>
                            <div className="grid grid-cols-1 gap-2 text-xs">
                                <Link
                                    href="/erp/invoices"
                                    className="flex items-center gap-2 p-2 rounded-lg border border-slate-100 hover:border-indigo-150 hover:bg-indigo-50/10 text-text-primary font-semibold transition"
                                >
                                    <Plus className="h-4 w-4 text-indigo-600 mr-2 shrink-0" />
                                    <span>Create Billing Invoice</span>
                                </Link>
                                <Link
                                    href="/erp/withdrawals"
                                    className="flex items-center gap-2 p-2 rounded-lg border border-slate-100 hover:border-indigo-150 hover:bg-indigo-50/10 text-text-primary font-semibold transition"
                                >
                                    <ArrowUpRight className="h-4 w-4 text-indigo-600 mr-2 shrink-0" />
                                    <span>Request Wallet Settlement</span>
                                </Link>
                                <Link
                                    href="/freelance/jobs/browse"
                                    className="flex items-center gap-2 p-2 rounded-lg border border-slate-100 hover:border-indigo-150 hover:bg-indigo-50/10 text-text-primary font-semibold transition"
                                >
                                    <Search className="h-4 w-4 text-indigo-600 mr-2 shrink-0" />
                                    <span>Browse Jobs Directory</span>
                                </Link>
                            </div>
                        </div>

                        {/* PART 12 — SMART ONBOARDING CHECKLIST */}
                        <div className="bg-white border border-border/60 rounded-xl p-4 shadow-sm space-y-3">
                            <h4 className="text-[10px] font-bold uppercase tracking-wider text-text-muted border-b border-border/40 pb-2">
                                Workspace Setup Checklist
                            </h4>
                            <div className="space-y-3 text-xs leading-normal">
                                <div className="flex gap-2">
                                    <CheckCircle2 className="h-4.5 w-4.5 text-emerald-500 shrink-0 mt-0.5" />
                                    <div>
                                        <span className="font-semibold block text-text-primary">Corporate Profile Setup</span>
                                        <p className="text-[10px] text-text-secondary mt-0.5 font-medium">Verify credentials and email details.</p>
                                    </div>
                                </div>
                                <div className="flex gap-2">
                                    <div className="h-4.5 w-4.5 rounded-full border border-slate-300 shrink-0 mt-0.5" />
                                    <div>
                                        <span className="font-semibold block text-text-primary">Link Wise Settlement</span>
                                        <p className="text-[10px] text-text-secondary mt-0.5">Wire business withdrawals instantly.</p>
                                        <Link 
                                            href="/profile" 
                                            className="text-[10px] text-indigo-600 font-bold hover:underline block mt-1"
                                        >
                                            Verify Wise Account →
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Standard Escrow Security Shield */}
                        <div className="bg-indigo-50/30 border border-indigo-100/50 rounded-xl p-3.5 text-[11px] leading-relaxed text-indigo-900 flex gap-2">
                            <Lock className="h-4.5 w-4.5 text-indigo-600 shrink-0 mt-0.5" />
                            <div>
                                <span className="font-bold text-indigo-950 block">Unified Escrow Safe Protection</span>
                                Funds are locked securely inside integrated customer accounts. Settlements are processed safely on deliverable approvals.
                            </div>
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

            <WalletQuickView
                isOpen={selectedWallet !== null}
                onClose={() => setSelectedWallet(null)}
                data={selectedWallet}
            />

            <ContractQuickView
                isOpen={selectedContract !== null}
                onClose={() => setSelectedContract(null)}
                data={selectedContract}
            />

            <CustomerQuickView
                isOpen={selectedCustomer !== null}
                onClose={() => setSelectedCustomer(null)}
                data={selectedCustomer}
            />

            <ServiceQuickView
                isOpen={selectedService !== null}
                onClose={() => setSelectedService(null)}
                data={selectedService}
            />
        </AuthenticatedLayout>
    );
}

import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { formatMoney, formatDate } from '@/lib/utils';
import { motion, AnimatePresence } from 'framer-motion';
import { 
    X, 
    FileText, 
    CheckCircle2, 
    Wallet, 
    History, 
    User, 
    Phone, 
    Mail, 
    MessageSquare, 
    MapPin, 
    CreditCard, 
    ArrowUpRight, 
    Calendar, 
    DollarSign, 
    Activity, 
    Briefcase,
    Clock,
    FileCheck2,
    Lock,
    ShoppingCart,
    Star
} from 'lucide-react';
import React from 'react';

interface QuickViewProps {
    isOpen: boolean;
    onClose: () => void;
    data: any;
}

export function SlideOver({ isOpen, onClose, title, icon: Icon, children }: { 
    isOpen: boolean; 
    onClose: () => void; 
    title: string; 
    icon: any; 
    children: React.ReactNode 
}) {
    return (
        <AnimatePresence>
            {isOpen && (
                <>
                    {/* Backdrop */}
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 0.4 }}
                        exit={{ opacity: 0 }}
                        onClick={onClose}
                        className="fixed inset-0 z-50 bg-black/60 backdrop-blur-[2px]"
                    />

                    {/* Panel */}
                    <motion.div
                        initial={{ x: '100%' }}
                        animate={{ x: 0 }}
                        exit={{ x: '100%' }}
                        transition={{ type: 'spring', damping: 25, stiffness: 220 }}
                        className="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col border-l border-border bg-white shadow-2xl"
                    >
                        {/* Header */}
                        <div className="flex items-center justify-between border-b border-border px-5 py-4">
                            <div className="flex items-center gap-3">
                                {Icon && (
                                    <div className="bg-primary/10 text-primary flex h-9 w-9 items-center justify-center rounded-lg">
                                        <Icon className="h-5 w-5" />
                                    </div>
                                )}
                                <h3 className="font-sora text-sm font-semibold text-text-primary">
                                    {title}
                                </h3>
                            </div>
                            <Button 
                                variant="ghost" 
                                size="icon" 
                                className="h-8 w-8 rounded-full text-text-muted hover:bg-gray-100 hover:text-text-primary"
                                onClick={onClose}
                            >
                                <X className="h-4 w-4" />
                            </Button>
                        </div>

                        {/* Content Scrollable */}
                        <div className="flex-1 overflow-y-auto px-6 py-5 text-sm">
                            {children}
                        </div>
                    </motion.div>
                </>
            )}
        </AnimatePresence>
    );
}

export function InvoiceQuickView({ isOpen, onClose, data }: QuickViewProps) {
    if (!data) return null;

    const statusColors: any = {
        paid: 'bg-emerald-50 text-emerald-700 border-emerald-200/60',
        pending: 'bg-amber-50 text-amber-700 border-amber-200/60',
        overdue: 'bg-rose-50 text-rose-700 border-rose-200/60',
        draft: 'bg-slate-50 text-slate-700 border-slate-200/60',
    };

    return (
        <SlideOver isOpen={isOpen} onClose={onClose} title={`Invoice Detail - ${data.invoiceNumber || 'INV-001'}`} icon={FileText}>
            <div className="space-y-6">
                {/* Status card */}
                <div className="flex items-center justify-between rounded-xl border border-border bg-gray-50/50 p-4">
                    <div>
                        <div className="text-text-muted text-[11px] font-semibold uppercase tracking-wider">Amount Due</div>
                        <div className="font-mono text-xl font-bold text-text-primary mt-0.5">
                            {formatMoney(data.amount, data.currency || 'USD')}
                        </div>
                    </div>
                    <Badge variant="outline" className={`rounded-full px-3 py-1 text-xs capitalize ${statusColors[data.status] || 'bg-gray-100'}`}>
                        {data.status}
                    </Badge>
                </div>

                {/* Details list */}
                <div className="space-y-4">
                    <h4 className="font-semibold text-text-primary">Overview Details</h4>
                    <div className="grid grid-cols-2 gap-4 border-t border-border/60 pt-3">
                        <div>
                            <span className="text-text-muted text-[11px] block">Client Name</span>
                            <span className="font-medium text-text-primary mt-0.5 block">{data.clientName}</span>
                        </div>
                        <div>
                            <span className="text-text-muted text-[11px] block">Issued Date</span>
                            <span className="font-medium text-text-primary mt-0.5 block">{formatDate(data.issuedDate)}</span>
                        </div>
                        <div className="mt-2">
                            <span className="text-text-muted text-[11px] block">Due Date</span>
                            <span className="font-medium text-text-primary mt-0.5 block">{formatDate(data.dueDate)}</span>
                        </div>
                        <div className="mt-2">
                            <span className="text-text-muted text-[11px] block">Project</span>
                            <span className="font-medium text-text-primary mt-0.5 block">{data.project || 'Development Services'}</span>
                        </div>
                    </div>
                </div>

                {/* Invoice items */}
                <div className="space-y-3">
                    <h4 className="font-semibold text-text-primary">Line Items</h4>
                    <div className="divide-y divide-border/60 rounded-xl border border-border bg-white px-4">
                        {(data.items || [
                            { description: 'SaaS Platform Development Architecture', qty: 1, rate: data.amount }
                        ]).map((item: any, i: number) => (
                            <div key={i} className="flex justify-between py-3">
                                <div>
                                    <div className="font-medium text-text-primary text-xs">{item.description}</div>
                                    <div className="text-text-muted text-[11px] mt-0.5">Qty: {item.qty} × {formatMoney(item.rate, data.currency || 'USD')}</div>
                                </div>
                                <span className="font-mono font-medium text-text-primary self-center text-xs">
                                    {formatMoney(item.qty * item.rate, data.currency || 'USD')}
                                </span>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Status Timeline */}
                <div className="space-y-4">
                    <h4 className="font-semibold text-text-primary">Invoice Timeline</h4>
                    <div className="relative border-l border-border pl-4 space-y-4 ml-2">
                        <div className="relative">
                            <div className="absolute -left-[21px] mt-0.5 bg-emerald-500 text-white rounded-full p-0.5 border-4 border-white">
                                <CheckCircle2 className="h-3 w-3" />
                            </div>
                            <span className="text-text-muted text-[11px] block">{formatDate(data.issuedDate)}</span>
                            <span className="font-medium text-text-primary text-xs block">Invoice drafted & sent to client</span>
                        </div>
                        {data.status === 'paid' ? (
                            <div className="relative">
                                <div className="absolute -left-[21px] mt-0.5 bg-emerald-500 text-white rounded-full p-0.5 border-4 border-white">
                                    <CheckCircle2 className="h-3 w-3" />
                                </div>
                                <span className="text-text-muted text-[11px] block">{formatDate(data.dueDate)}</span>
                                <span className="font-medium text-text-primary text-xs block">Payment received successfully via Wallet Credit</span>
                            </div>
                        ) : (
                            <div className="relative">
                                <div className="absolute -left-[21px] mt-0.5 bg-gray-300 text-white rounded-full p-0.5 border-4 border-white">
                                    <Clock className="h-3 w-3" />
                                </div>
                                <span className="text-text-muted text-[11px] block">Awaiting client payment</span>
                                <span className="font-medium text-text-muted text-xs block">Overdue notification scheduled for {formatDate(data.dueDate)}</span>
                            </div>
                        )}
                    </div>
                </div>

                {/* Quick actions */}
                <div className="flex gap-3 border-t border-border pt-4">
                    <Button className="flex-1 text-xs py-2 bg-primary hover:bg-primary-hover text-white">
                        Send Reminder
                    </Button>
                    <Button variant="outline" className="flex-1 text-xs py-2">
                        Download PDF
                    </Button>
                </div>
            </div>
        </SlideOver>
    );
}

export function WalletQuickView({ isOpen, onClose, data }: QuickViewProps) {
    if (!data) return null;

    return (
        <SlideOver isOpen={isOpen} onClose={onClose} title="SaaS Wallet Center" icon={Wallet}>
            <div className="space-y-6">
                {/* Total balance card */}
                <div className="rounded-2xl bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-950 p-6 text-white shadow-xl relative overflow-hidden">
                    <div className="absolute right-0 top-0 translate-x-4 -translate-y-4 opacity-10">
                        <Wallet className="h-36 w-36" />
                    </div>
                    <div className="text-[11px] font-semibold uppercase tracking-wider text-indigo-200">Unified Wallet Balance</div>
                    <div className="font-mono text-3xl font-bold mt-1 text-white">
                        {formatMoney(data.balance, 'USD')}
                    </div>
                    <div className="mt-4 flex items-center justify-between text-xs text-slate-300 border-t border-white/10 pt-4">
                        <span className="flex items-center gap-1.5">
                            <Lock className="h-3.5 w-3.5 text-indigo-300" /> Locked Holds: $0.00
                        </span>
                        <span className="bg-white/15 px-2 py-0.5 rounded text-[10px] text-white">Active</span>
                    </div>
                </div>

                {/* Account Details */}
                <div className="space-y-3">
                    <h4 className="font-semibold text-text-primary">Ledger Breakdown</h4>
                    <div className="space-y-2.5">
                        <div className="flex justify-between border-b border-border/40 pb-2.5">
                            <span className="text-text-muted">Available Funds</span>
                            <span className="font-mono font-medium text-text-primary">{formatMoney(data.balance * 0.9, 'USD')}</span>
                        </div>
                        <div className="flex justify-between border-b border-border/40 pb-2.5">
                            <span className="text-text-muted">Referral Bonus Credit</span>
                            <span className="font-mono font-medium text-text-primary">{formatMoney(data.balance * 0.1, 'USD')}</span>
                        </div>
                        <div className="flex justify-between border-b border-border/40 pb-2.5">
                            <span className="text-text-muted">Total Withdrawals Paid</span>
                            <span className="font-mono font-medium text-text-primary">$1,250.00</span>
                        </div>
                    </div>
                </div>

                {/* Recent transaction history log */}
                <div className="space-y-3">
                    <h4 className="font-semibold text-text-primary flex items-center gap-2">
                        <History className="h-4 w-4 text-text-muted" /> Wallet Activity
                    </h4>
                    <div className="divide-y divide-border/60 rounded-xl border border-border bg-slate-50/50 px-4">
                        {(data.transactions || [
                            { desc: 'Invoice payment credit INV-201', amount: 850.00, date: '2026-05-15', type: 'credit' },
                            { desc: 'Referral bounty earnings payout', amount: 50.00, date: '2026-05-14', type: 'credit' },
                            { desc: 'Wallet withdrawal via PayPal', amount: -200.00, date: '2026-05-10', type: 'debit' }
                        ]).map((tx: any, i: number) => (
                            <div key={i} className="flex justify-between py-3 text-xs">
                                <div>
                                    <div className="font-medium text-text-primary">{tx.desc}</div>
                                    <div className="text-[10px] text-text-muted mt-0.5">{formatDate(tx.date)}</div>
                                </div>
                                <span className={`font-mono font-bold self-center ${tx.type === 'credit' ? 'text-emerald-600' : 'text-rose-600'}`}>
                                    {tx.type === 'credit' ? '+' : ''}{formatMoney(tx.amount, 'USD')}
                                </span>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Transfer triggers */}
                <div className="flex gap-3 border-t border-border pt-4">
                    <Button className="flex-1 text-xs py-2 bg-slate-900 hover:bg-slate-800 text-white">
                        Withdraw Funds
                    </Button>
                    <Button variant="outline" className="flex-1 text-xs py-2">
                        Add Deposit
                    </Button>
                </div>
            </div>
        </SlideOver>
    );
}

export function ContractQuickView({ isOpen, onClose, data }: QuickViewProps) {
    if (!data) return null;

    const progressValue = data.progress || 60;

    return (
        <SlideOver isOpen={isOpen} onClose={onClose} title={`Contract - ${data.title}`} icon={Briefcase}>
            <div className="space-y-6">
                {/* Info Card */}
                <div className="rounded-xl border border-border bg-gray-50/50 p-4">
                    <div className="flex items-start justify-between">
                        <div>
                            <span className="text-text-muted text-[11px] block">Client Name</span>
                            <span className="font-semibold text-text-primary block mt-0.5">{data.clientName}</span>
                        </div>
                        <Badge className="bg-indigo-50 border-indigo-200 text-indigo-700 capitalize text-[10px] px-2.5 py-0.5 rounded-full">
                            {data.status || 'Active'}
                        </Badge>
                    </div>
                    
                    {/* Contract price */}
                    <div className="mt-4 grid grid-cols-2 gap-4 border-t border-border/60 pt-3 text-xs">
                        <div>
                            <span className="text-text-muted block">Contract Value</span>
                            <span className="font-mono font-bold text-text-primary text-sm mt-0.5 block">
                                {formatMoney(data.value || 3500, 'USD')}
                            </span>
                        </div>
                        <div>
                            <span className="text-text-muted block">Active Timer Log</span>
                            <span className="font-mono font-medium text-text-primary text-sm mt-0.5 flex items-center gap-1 block">
                                <Clock className="h-3.5 w-3.5 text-emerald-500 animate-pulse" /> 18h 45m
                            </span>
                        </div>
                    </div>
                </div>

                {/* Progress bar */}
                <div className="space-y-2">
                    <div className="flex justify-between text-xs">
                        <span className="text-text-secondary font-medium">Milestone Progress</span>
                        <span className="font-mono font-semibold text-text-primary">{progressValue}%</span>
                    </div>
                    <div className="h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                        <div 
                            className="h-full rounded-full bg-primary transition-all duration-500" 
                            style={{ width: `${progressValue}%` }}
                        />
                    </div>
                </div>

                {/* Milestones checklists */}
                <div className="space-y-3">
                    <h4 className="font-semibold text-text-primary">Project Milestones</h4>
                    <div className="space-y-2.5 text-xs">
                        <div className="flex items-center gap-3 p-2.5 rounded-lg border border-emerald-100 bg-emerald-50/20">
                            <CheckCircle2 className="h-4.5 w-4.5 text-emerald-500 shrink-0" />
                            <div className="flex-1">
                                <div className="font-medium text-text-primary line-through opacity-60">Phase 1: Brand UX Wireframes & Architecture</div>
                                <div className="text-[10px] text-emerald-700">Completed May 5, 2026</div>
                            </div>
                        </div>
                        <div className="flex items-center gap-3 p-2.5 rounded-lg border border-emerald-100 bg-emerald-50/20">
                            <CheckCircle2 className="h-4.5 w-4.5 text-emerald-500 shrink-0" />
                            <div className="flex-1">
                                <div className="font-medium text-text-primary line-through opacity-60">Phase 2: Database Schema & Modular Migrations</div>
                                <div className="text-[10px] text-emerald-700">Completed May 12, 2026</div>
                            </div>
                        </div>
                        <div className="flex items-center gap-3 p-2.5 rounded-lg border border-border bg-white">
                            <div className="h-4.5 w-4.5 rounded-full border-2 border-primary/50 flex items-center justify-center shrink-0">
                                <div className="h-2 w-2 rounded-full bg-primary" />
                            </div>
                            <div className="flex-1">
                                <div className="font-medium text-text-primary">Phase 3: Client Dashboard Shell Architecture Refactor</div>
                                <div className="text-[10px] text-primary font-semibold">Currently In Progress</div>
                            </div>
                        </div>
                        <div className="flex items-center gap-3 p-2.5 rounded-lg border border-border bg-white opacity-50">
                            <div className="h-4.5 w-4.5 rounded-full border border-border shrink-0" />
                            <div className="flex-1">
                                <div className="font-medium text-text-secondary">Phase 4: Launch Production Build & QA Integration</div>
                                <div className="text-[10px] text-text-muted">Target: May 25, 2026</div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Operations triggers */}
                <div className="flex gap-3 border-t border-border pt-4">
                    <Button className="flex-1 text-xs py-2 bg-primary hover:bg-primary-hover text-white">
                        Submit Deliverable
                    </Button>
                    <Button variant="outline" className="flex-1 text-xs py-2 text-rose-600 hover:text-rose-700 hover:bg-rose-50 border-rose-200">
                        Dispute Contract
                    </Button>
                </div>
            </div>
        </SlideOver>
    );
}

export function CustomerQuickView({ isOpen, onClose, data }: QuickViewProps) {
    if (!data) return null;

    return (
        <SlideOver isOpen={isOpen} onClose={onClose} title={`Client Card - ${data.name}`} icon={User}>
            <div className="space-y-6">
                {/* Profile Card header */}
                <div className="flex items-center gap-4 rounded-xl border border-border bg-slate-50/50 p-4">
                    <div className="bg-slate-200 text-slate-700 flex h-14 w-14 items-center justify-center rounded-full font-sora text-lg font-bold">
                        {data.name.split(' ').map((n: string) => n[0]).join('')}
                    </div>
                    <div>
                        <h4 className="font-sora text-sm font-bold text-text-primary">{data.name}</h4>
                        <span className="text-text-muted text-xs block mt-0.5">{data.company || 'Musoftware LLC'}</span>
                        <div className="flex items-center gap-1.5 mt-2">
                            <Badge className="bg-emerald-50 text-emerald-700 border-emerald-200 capitalize text-[9px] font-semibold py-0 rounded-full">
                                Verified Client
                            </Badge>
                        </div>
                    </div>
                </div>

                {/* Contact list details */}
                <div className="space-y-3.5">
                    <h4 className="font-semibold text-text-primary">Contact Coordinates</h4>
                    <div className="space-y-3 text-xs">
                        <div className="flex items-center gap-3 text-text-secondary">
                            <Mail className="h-4 w-4 text-text-muted" />
                            <span>{data.email || 'client@musoftware.com'}</span>
                        </div>
                        <div className="flex items-center gap-3 text-text-secondary">
                            <Phone className="h-4 w-4 text-text-muted" />
                            <span>{data.phone || '+1 (555) 124-5678'}</span>
                        </div>
                        <div className="flex items-center gap-3 text-text-secondary">
                            <MapPin className="h-4 w-4 text-text-muted" />
                            <span>{data.address || 'San Francisco, CA, USA'}</span>
                        </div>
                    </div>
                </div>

                {/* Core operational financial metrics with this client */}
                <div className="space-y-3">
                    <h4 className="font-semibold text-text-primary">Financial Relationship</h4>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="rounded-xl border border-border bg-white p-3.5 text-center">
                            <span className="text-text-muted text-[11px] block">Total Invoiced</span>
                            <span className="font-mono text-sm font-bold text-text-primary block mt-1">
                                {formatMoney(data.totalInvoiced || 4800, 'USD')}
                            </span>
                        </div>
                        <div className="rounded-xl border border-border bg-white p-3.5 text-center">
                            <span className="text-text-muted text-[11px] block">Paid Balance</span>
                            <span className="font-mono text-sm font-bold text-emerald-600 block mt-1">
                                {formatMoney(data.totalPaid || 3950, 'USD')}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Recent relationship events */}
                <div className="space-y-3">
                    <h4 className="font-semibold text-text-primary flex items-center gap-2">
                        <Activity className="h-4 w-4 text-text-muted" /> Recent Activities
                    </h4>
                    <div className="divide-y divide-border/60 rounded-xl border border-border bg-slate-50/50 px-4">
                        <div className="py-2.5 text-xs flex justify-between">
                            <span className="text-text-primary">Contract "SaaS Design Shell" launched</span>
                            <span className="text-[10px] text-text-muted self-center">May 12</span>
                        </div>
                        <div className="py-2.5 text-xs flex justify-between">
                            <span className="text-text-primary">Invoice INV-302 paid by client</span>
                            <span className="text-[10px] text-text-muted self-center">May 08</span>
                        </div>
                        <div className="py-2.5 text-xs flex justify-between">
                            <span className="text-text-primary">Support ticket reopened: Stripe hook lag</span>
                            <span className="text-[10px] text-text-muted self-center">May 01</span>
                        </div>
                    </div>
                </div>

                {/* Message / Chat triggers */}
                <div className="flex gap-3 border-t border-border pt-4">
                    <Button className="flex-1 text-xs py-2 bg-primary hover:bg-primary-hover text-white flex items-center justify-center gap-2">
                        <MessageSquare className="h-4 w-4" /> Message Client
                    </Button>
                    <Button variant="outline" className="flex-1 text-xs py-2">
                        View ERP File
                    </Button>
                </div>
            </div>
        </SlideOver>
    );
}

export function ServiceQuickView({ isOpen, onClose, data }: QuickViewProps) {
    if (!data) return null;

    return (
        <SlideOver isOpen={isOpen} onClose={onClose} title={`Service Info - ${data.title}`} icon={ShoppingCart}>
            <div className="space-y-6">
                {/* Title & Image banner */}
                <div className="rounded-xl border border-border overflow-hidden bg-slate-50">
                    <div className="p-4 space-y-1">
                        <div className="text-[10px] uppercase font-bold text-indigo-600 tracking-wider">Marketplace Gig</div>
                        <h4 className="font-sora text-sm font-bold text-text-primary leading-tight">{data.title}</h4>
                        <div className="flex items-center gap-1 mt-2 text-xs text-text-secondary">
                            <span className="font-semibold text-text-primary">{data.sellerName || 'Mahmoud'}</span>
                            <span>•</span>
                            <span className="flex items-center text-amber-500 font-semibold gap-0.5">
                                ★ 4.9 (48 reviews)
                            </span>
                        </div>
                    </div>
                </div>

                {/* Scope details */}
                <div className="space-y-3">
                    <h4 className="font-semibold text-text-primary">Service Capabilities</h4>
                    <p className="text-text-secondary text-xs leading-normal">
                        {data.description || 'Full stack design conversion and platform optimization. Fully coded in clean responsive React/TailwindCSS structures.'}
                    </p>
                    <div className="space-y-2 text-xs mt-3">
                        <div className="flex items-center gap-2 text-text-secondary">
                            <CheckCircle2 className="h-4 w-4 text-emerald-500 shrink-0" />
                            <span>100% Mobile & Desktop Responsive Layouts</span>
                        </div>
                        <div className="flex items-center gap-2 text-text-secondary">
                            <CheckCircle2 className="h-4 w-4 text-emerald-500 shrink-0" />
                            <span>Framer Motion Micro-animations included</span>
                        </div>
                        <div className="flex items-center gap-2 text-text-secondary">
                            <CheckCircle2 className="h-4 w-4 text-emerald-500 shrink-0" />
                            <span>Clean modular components & type definitions</span>
                        </div>
                    </div>
                </div>

                {/* Escrow Guarantee */}
                <div className="rounded-xl border border-indigo-100 bg-indigo-50/20 p-3.5 text-xs flex gap-3">
                    <Lock className="h-5 w-5 text-indigo-500 shrink-0 mt-0.5" />
                    <div>
                        <span className="font-bold text-indigo-900 block">Unified Escrow Safe Protection</span>
                        <p className="text-indigo-700/80 text-[10px] mt-0.5 leading-snug">
                            Funds are locked in escrow and only released to the expert when you review and approve the project deliverable.
                        </p>
                    </div>
                </div>

                {/* Pricing Packages Matrix */}
                <div className="space-y-3">
                    <h4 className="font-semibold text-text-primary">Pricing Tiers</h4>
                    <div className="divide-y divide-border/60 rounded-xl border border-border bg-white overflow-hidden text-xs">
                        <div className="flex justify-between p-3 bg-slate-50/50">
                            <div>
                                <span className="font-bold text-text-primary">Basic Conversion</span>
                                <p className="text-[10px] text-text-muted mt-0.5">3 screens, standard styling</p>
                            </div>
                            <span className="font-mono font-bold text-text-primary self-center">$450.00</span>
                        </div>
                        <div className="flex justify-between p-3 border-l-2 border-indigo-500 bg-indigo-50/5">
                            <div>
                                <span className="font-bold text-indigo-950 flex items-center gap-1">
                                    Standard Full Core <Badge className="bg-indigo-100 text-indigo-700 text-[8px] font-bold py-0">POPULAR</Badge>
                                </span>
                                <p className="text-[10px] text-text-muted mt-0.5">10 screens, advanced layouts, state controls</p>
                            </div>
                            <span className="font-mono font-bold text-indigo-700 self-center">$850.00</span>
                        </div>
                        <div className="flex justify-between p-3">
                            <div>
                                <span className="font-bold text-text-primary">Enterprise Refactor</span>
                                <p className="text-[10px] text-text-muted mt-0.5">Complete system architecture overhaul</p>
                            </div>
                            <span className="font-mono font-bold text-text-primary self-center">$1,500.00</span>
                        </div>
                    </div>
                </div>

                {/* Purchase quick CTA */}
                <div className="flex gap-3 border-t border-border pt-4">
                    <Button className="flex-1 text-xs py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">
                        Purchase Service
                    </Button>
                    <Button variant="outline" className="flex-1 text-xs py-2 text-text-primary">
                        Contact Seller
                    </Button>
                </div>
            </div>
        </SlideOver>
    );
}

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import {
    ShieldAlert,
    Sparkles,
    CheckCircle2,
    Lock,
    ArrowRight,
    TrendingUp,
    FileText,
    Calculator,
    PieChart,
    ChevronRight,
    Play
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { useToast } from '@/Components/ui/use-toast';

export default function UpgradePreview() {
    const [isUpgrading, setIsUpgrading] = useState(false);
    const { toast } = useToast();

    // Simulated upgrade pipeline
    const handleUpgradeSimulate = () => {
        setIsUpgrading(true);
        setTimeout(() => {
            // Write simulated activation into sessionStorage so AuthenticatedLayout and other pages reflect the active ERP state instantly!
            sessionStorage.setItem('is_subscribed_erp', 'true');
            setIsUpgrading(false);
            
            toast({
                title: "Premium ERP Workspace Unlocked!",
                description: "Your organization now has active estimates, expense tracking, and reports.",
            });

            // Redirect back to dashboard where ERP is now fully active
            router.visit(route('dashboard'));
        }, 1500);
    };

    return (
        <AuthenticatedLayout header="ERP Expansion Preview">
            <Head title="ERP Workspace Premium Upgrade" />

            <div className="max-w-5xl mx-auto space-y-8 pb-12 font-sans text-sm">
                
                {/* Visual upgrade hero card */}
                <div className="relative rounded-2xl bg-slate-900 text-white p-6 md:p-8 shadow-xl overflow-hidden">
                    <div className="absolute top-0 right-0 translate-x-12 -translate-y-12 opacity-10">
                        <Calculator className="h-64 w-64" />
                    </div>

                    <div className="max-w-2xl space-y-4 relative z-10">
                        <div className="inline-flex items-center gap-1.5 bg-indigo-500/20 text-indigo-300 px-3 py-1 rounded-full text-xs font-semibold">
                            <Sparkles className="h-3.5 w-3.5" />
                            Premium ERP Extension Module
                        </div>
                        <h1 className="font-sora text-xl md:text-2xl font-bold tracking-tight text-white leading-tight">
                            Consolidate Invoicing, Estimations, & Profit Ledger Into One Operational Workspace
                        </h1>
                        <p className="text-xs text-slate-300 leading-relaxed max-w-lg">
                            Upgrade your client account to unlock professional estimates, automated recurring business invoices, employee expense reporting, and active ledger profit distribution charts.
                        </p>
                        
                        <div className="pt-3">
                            <Button 
                                onClick={handleUpgradeSimulate}
                                disabled={isUpgrading}
                                className="bg-white hover:bg-slate-100 text-slate-950 font-bold px-6 py-2.5 rounded-lg text-xs flex items-center gap-2 group transition"
                            >
                                {isUpgrading ? "Clearing payment checkout..." : "Activate ERP Capability"}
                                <ArrowRight className="h-4 w-4 group-hover:translate-x-1 transition" />
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Capabilities grid with locked screen visual mockups */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {/* Capability 1: Advanced Estimations */}
                    <div className="bg-white border border-border/60 rounded-xl p-5 shadow-sm space-y-4 relative overflow-hidden group">
                        <div className="absolute top-3 right-3 text-text-muted opacity-30 group-hover:opacity-60 transition">
                            <Lock className="h-4.5 w-4.5" />
                        </div>
                        
                        <div className="space-y-1">
                            <h3 className="font-sora font-semibold text-text-primary text-sm flex items-center gap-2">
                                <Calculator className="h-4 w-4 text-indigo-500" /> Dynamic Estimations Engine
                            </h3>
                            <p className="text-text-secondary text-xs leading-normal">
                                Build interactive itemized estimates and automatically convert them into billing invoices upon client signature approval.
                            </p>
                        </div>

                        {/* Blurred Mockup Visual */}
                        <div className="rounded-lg border border-slate-100 bg-slate-50/50 p-3 select-none filter blur-[1.5px] opacity-70 pointer-events-none font-mono text-[10px] space-y-2">
                            <div className="flex justify-between border-b pb-1.5">
                                <span>Estimate #EST-021</span>
                                <span className="bg-amber-100 px-1 rounded">Draft</span>
                            </div>
                            <div className="space-y-1">
                                <div className="flex justify-between"><span>Core Refactoring</span><span>$1,200.00</span></div>
                                <div className="flex justify-between"><span>Database Migration</span><span>$650.00</span></div>
                            </div>
                            <div className="text-right font-bold pt-1.5 border-t">Total: $1,850.00</div>
                        </div>
                    </div>

                    {/* Capability 2: Ledger Expenses & Cash Flow Reports */}
                    <div className="bg-white border border-border/60 rounded-xl p-5 shadow-sm space-y-4 relative overflow-hidden group">
                        <div className="absolute top-3 right-3 text-text-muted opacity-30 group-hover:opacity-60 transition">
                            <Lock className="h-4.5 w-4.5" />
                        </div>

                        <div className="space-y-1">
                            <h3 className="font-sora font-semibold text-text-primary text-sm flex items-center gap-2">
                                <PieChart className="h-4 w-4 text-indigo-500" /> Automated Expense Tracker
                            </h3>
                            <p className="text-text-secondary text-xs leading-normal">
                                Log operational costs, vendor receipts, server costs, and compile automatic Profit & Loss charts for annual tax schedules.
                            </p>
                        </div>

                        {/* Blurred chart visual */}
                        <div className="rounded-lg border border-slate-100 bg-slate-50/50 p-3 select-none filter blur-[1.5px] opacity-70 pointer-events-none font-mono text-[10px] space-y-2">
                            <div className="flex justify-between items-center">
                                <span>Profit & Loss Ledger</span>
                                <span className="text-emerald-600 font-bold flex items-center"><TrendingUp className="h-3 w-3 mr-0.5" /> +14.5%</span>
                            </div>
                            <div className="flex justify-between text-text-secondary">
                                <span>Net Sales: $12,450.00</span>
                                <span>Expenses: $3,210.00</span>
                            </div>
                            <div className="h-2 w-full bg-slate-200 rounded overflow-hidden flex">
                                <div className="h-full bg-emerald-500 w-2/3" />
                                <div className="h-full bg-rose-400 w-1/3" />
                            </div>
                        </div>
                    </div>
                </div>

                {/* Feature Comparison Capability Table */}
                <div className="bg-white border border-border/60 rounded-xl overflow-hidden shadow-sm">
                    <div className="px-5 py-4 border-b border-border/50 bg-slate-50/50">
                        <h3 className="font-sora text-xs font-bold uppercase tracking-wider text-text-muted">
                            Capability Mapping comparison
                        </h3>
                    </div>
                    <div className="overflow-x-auto text-xs">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-border font-bold text-text-muted bg-slate-50/30">
                                    <th className="p-4">Feature</th>
                                    <th className="p-4 text-center">Standard Client Account</th>
                                    <th className="p-4 text-center text-indigo-700 bg-indigo-50/10">Premium ERP Workspace</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border/60">
                                <tr>
                                    <td className="p-4 font-semibold text-text-primary">Receive Invoices & Pay</td>
                                    <td className="p-4 text-center"><CheckCircle2 className="h-4.5 w-4.5 text-emerald-500 mx-auto" /></td>
                                    <td className="p-4 text-center bg-indigo-50/10"><CheckCircle2 className="h-4.5 w-4.5 text-emerald-500 mx-auto" /></td>
                                </tr>
                                <tr>
                                    <td className="p-4 font-semibold text-text-primary">Estimates Creation & Signatures</td>
                                    <td className="p-4 text-center text-text-muted">Locked</td>
                                    <td className="p-4 text-center bg-indigo-50/10"><CheckCircle2 className="h-4.5 w-4.5 text-emerald-500 mx-auto" /></td>
                                </tr>
                                <tr>
                                    <td className="p-4 font-semibold text-text-primary">Client Expense Ledger Tracking</td>
                                    <td className="p-4 text-center text-text-muted">Locked</td>
                                    <td className="p-4 text-center bg-indigo-50/10"><CheckCircle2 className="h-4.5 w-4.5 text-emerald-500 mx-auto" /></td>
                                </tr>
                                <tr>
                                    <td className="p-4 font-semibold text-text-primary">Annual Profit & Loss Reports</td>
                                    <td className="p-4 text-center text-text-muted">Locked</td>
                                    <td className="p-4 text-center bg-indigo-50/10"><CheckCircle2 className="h-4.5 w-4.5 text-emerald-500 mx-auto" /></td>
                                </tr>
                                <tr>
                                    <td className="p-4 font-semibold text-text-primary">Recurring Auto-Invoicing Automation</td>
                                    <td className="p-4 text-center text-text-muted">Locked</td>
                                    <td className="p-4 text-center bg-indigo-50/10"><CheckCircle2 className="h-4.5 w-4.5 text-emerald-500 mx-auto" /></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Bottom interactive Upgrade CTA banner */}
                <div className="rounded-xl border border-indigo-100 bg-indigo-50/20 p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="space-y-1">
                        <span className="font-bold text-indigo-950 block">Ready to unlock fully unified operations?</span>
                        <p className="text-[11px] text-indigo-800 leading-snug">
                            Activate your Premium ERP workspace license today. Simulates check clearance instantly in sandbox mode.
                        </p>
                    </div>
                    <Button 
                        onClick={handleUpgradeSimulate}
                        disabled={isUpgrading}
                        className="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-5 rounded-lg text-xs"
                    >
                        {isUpgrading ? "Checking..." : "Upgrade Now"}
                    </Button>
                </div>
            </div>
            
            {/* Simulated progress overlay */}
            <AnimatePresence>
                {isUpgrading && (
                    <motion.div 
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center"
                    >
                        <motion.div 
                            initial={{ scale: 0.95 }}
                            animate={{ scale: 1 }}
                            exit={{ scale: 0.95 }}
                            className="bg-white rounded-2xl p-6 max-w-sm w-full border border-border shadow-2xl text-center space-y-4"
                        >
                            <div className="h-10 w-10 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto animate-spin">
                                <Sparkles className="h-5 w-5" />
                            </div>
                            <h3 className="font-sora font-bold text-text-primary text-sm">Processing License Setup</h3>
                            <p className="text-text-secondary text-xs leading-normal">
                                Provisioning estimates tables, recurring models, and dashboard metrics trackers...
                            </p>
                        </motion.div>
                    </motion.div>
                )}
            </AnimatePresence>
        </AuthenticatedLayout>
    );
}

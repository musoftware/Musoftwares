import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import axios from 'axios';
import { 
    Sparkles, LayoutDashboard, FileText, Wallet, Boxes, 
    ArrowUpRight, Bell, CheckCircle2, ChevronRight, ChevronLeft, 
    X, ArrowRight, Play, RefreshCw, Compass
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Link } from '@inertiajs/react';

interface User {
    id: number;
    name: string;
    email: string;
    tour_completed?: boolean;
    tour_skipped?: boolean;
    current_tour_step?: number;
}

interface ProductTourProps {
    user: User | null;
    isOpen: boolean;
    onClose: () => void;
    currentStep: number;
    onStepChange: (step: number) => void;
}

export default function ProductTourModal({ user, isOpen, onClose, currentStep, onStepChange }: ProductTourProps) {
    const [loading, setLoading] = useState(false);

    if (!isOpen) return null;

    const steps = [
        {
            step: 1,
            title: "Welcome to Musoftware 👋",
            subtitle: "Unified Enterprise Workspace",
            description: "This workspace helps you manage invoices, wallet balances, services, and operational workflows from one unified platform.",
            icon: Sparkles,
            color: "text-amber-500 bg-amber-50 dark:bg-amber-500/10",
            highlightTarget: "none",
        },
        {
            step: 2,
            title: "Dashboard Overview",
            subtitle: "Financial & Action Summary",
            description: "Your central operations node displays live wallet balances, unpaid invoices, recent transactions, and quick action shortcuts.",
            icon: LayoutDashboard,
            color: "text-indigo-600 bg-indigo-50 dark:bg-indigo-500/10",
            highlightTarget: "dashboard",
        },
        {
            step: 3,
            title: "Invoices & Wallet",
            subtitle: "Frictionless Billing",
            description: "You can pay invoices directly using your internal wallet balance or external payment methods. Deposit funds anytime to keep workflows active.",
            icon: Wallet,
            color: "text-emerald-600 bg-emerald-50 dark:bg-emerald-500/10",
            highlightTarget: "invoices",
        },
        {
            step: 4,
            title: "Modules & iSAAS",
            subtitle: "Connected Ecosystem",
            description: "Each module (ERP, Freelance Hub, Marketing) provides a specialized workspace while staying securely connected to your shared wallet, identity, and ledger.",
            icon: Boxes,
            color: "text-blue-600 bg-blue-50 dark:bg-blue-500/10",
            highlightTarget: "isaas",
        },
        {
            step: 5,
            title: "Payouts & Withdrawals",
            subtitle: "Transparent Financial Dispatch",
            description: "Earn funds through Freelance or Marketplace orders, configure secure payout methods, and submit direct withdrawal requests instantly.",
            icon: ArrowUpRight,
            color: "text-purple-600 bg-purple-50 dark:bg-purple-500/10",
            highlightTarget: "more",
        },
        {
            step: 6,
            title: "Notifications & Activity",
            subtitle: "Realtime Operational Continuity",
            description: "Track system dispatches, invoice approvals, support ticket resolutions, and workflow updates without missing a beat.",
            icon: Bell,
            color: "text-rose-600 bg-rose-50 dark:bg-rose-500/10",
            highlightTarget: "notifications",
        },
        {
            step: 7,
            title: "Your workspace is ready 🚀",
            subtitle: "Setup Complete",
            description: "You are fully equipped to navigate Musoftware. Start by depositing wallet balance, reviewing outstanding invoices, or exploring ERP.",
            icon: CheckCircle2,
            color: "text-emerald-600 bg-emerald-50 dark:bg-emerald-500/10",
            highlightTarget: "none",
        },
    ];

    const currentStepData = steps[currentStep - 1] || steps[0];
    const IconComponent = currentStepData.icon;

    const saveTourStatus = async (params: { step?: number; skipped?: boolean; completed?: boolean }) => {
        try {
            await axios.post('/product-tour/status', params);
        } catch (e) {
            console.error("Failed to update tour status", e);
        }
    };

    const handleNext = () => {
        if (currentStep < 7) {
            const next = currentStep + 1;
            onStepChange(next);
            saveTourStatus({ step: next });
        }
    };

    const handlePrev = () => {
        if (currentStep > 1) {
            const prev = currentStep - 1;
            onStepChange(prev);
            saveTourStatus({ step: prev });
        }
    };

    const handleSkip = () => {
        onClose();
        saveTourStatus({ skipped: true });
    };

    const handleFinish = () => {
        onClose();
        saveTourStatus({ completed: true });
    };

    return (
        <div className="fixed bottom-6 right-6 z-50 w-[380px] sm:w-[420px] shadow-2xl rounded-2xl border border-slate-200/80 dark:border-slate-800/80 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl overflow-hidden font-sans text-slate-900 dark:text-slate-100 transition-all">
            {/* Header / Top bar */}
            <div className="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/50">
                <div className="flex items-center space-x-2">
                    <Compass className="w-4 h-4 text-indigo-600 dark:text-indigo-400 animate-spin-slow" />
                    <span className="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Interactive Product Tour
                    </span>
                </div>
                <div className="flex items-center space-x-2">
                    <span className="text-xs font-medium px-2 py-0.5 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-mono">
                        {currentStep} / 7
                    </span>
                    <button 
                        onClick={handleSkip} 
                        className="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        title="Close Tour"
                    >
                        <X className="w-4 h-4" />
                    </button>
                </div>
            </div>

            {/* Body */}
            <div className="p-6">
                <AnimatePresence mode="wait">
                    <motion.div
                        key={currentStepData.step}
                        initial={{ opacity: 0, y: 10 }}
                        animate={{ opacity: 1, y: 0 }}
                        exit={{ opacity: 0, y: -10 }}
                        transition={{ duration: 0.18 }}
                        className="space-y-4"
                    >
                        <div className="flex items-start space-x-4">
                            <div className={`w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 shadow-xs ${currentStepData.color}`}>
                                <IconComponent className="w-6 h-6" />
                            </div>
                            <div>
                                <span className="text-xs font-bold text-indigo-600 dark:text-indigo-400 tracking-wide uppercase block mb-0.5">
                                    {currentStepData.subtitle}
                                </span>
                                <h3 className="text-lg font-bold tracking-tight text-slate-900 dark:text-white leading-snug">
                                    {currentStepData.title}
                                </h3>
                            </div>
                        </div>

                        <p className="text-sm text-slate-600 dark:text-slate-300 leading-relaxed pt-1">
                            {currentStepData.description}
                        </p>

                        {/* Interactive UI highlight teaser / visual indicator */}
                        <div className="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-800 text-xs flex items-center justify-between">
                            <span className="text-slate-500 dark:text-slate-400">Targeting Module Node:</span>
                            <span className="font-semibold text-slate-700 dark:text-slate-200 flex items-center space-x-1">
                                <span className="w-2 h-2 rounded-full bg-indigo-500 animate-ping mr-1" />
                                <span>{currentStepData.highlightTarget === 'none' ? 'Workspace Core' : `Navigation: ${currentStepData.highlightTarget.toUpperCase()}`}</span>
                            </span>
                        </div>

                        {/* Step 7 Suggested Actions */}
                        {currentStep === 7 && (
                            <div className="grid grid-cols-2 gap-2 pt-2">
                                <Link 
                                    href="/erp/wallet" 
                                    onClick={handleFinish}
                                    className="p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 hover:border-indigo-500 text-xs font-medium flex items-center space-x-2 transition"
                                >
                                    <Wallet className="w-4 h-4 text-emerald-500" />
                                    <span>Add Balance</span>
                                </Link>
                                <Link 
                                    href="/erp/invoices" 
                                    onClick={handleFinish}
                                    className="p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 hover:border-indigo-500 text-xs font-medium flex items-center space-x-2 transition"
                                >
                                    <FileText className="w-4 h-4 text-rose-500" />
                                    <span>View Invoices</span>
                                </Link>
                            </div>
                        )}
                    </motion.div>
                </AnimatePresence>
            </div>

            {/* Footer / Navigation controls */}
            <div className="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/50 flex items-center justify-between">
                <div className="flex space-x-1.5">
                    {steps.map(s => (
                        <button
                            key={s.step}
                            onClick={() => {
                                onStepChange(s.step);
                                saveTourStatus({ step: s.step });
                            }}
                            className={`h-1.5 rounded-full transition-all ${
                                s.step === currentStep 
                                ? 'w-6 bg-indigo-600 dark:bg-indigo-400' 
                                : 'w-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300'
                            }`}
                            title={`Go to step ${s.step}`}
                        />
                    ))}
                </div>

                <div className="flex items-center space-x-2">
                    {currentStep > 1 && (
                        <Button 
                            variant="outline" 
                            size="sm" 
                            onClick={handlePrev}
                            className="h-8 rounded-lg text-xs font-medium border-slate-200 dark:border-slate-700"
                        >
                            <ChevronLeft className="w-3.5 h-3.5 mr-0.5" /> Back
                        </Button>
                    )}

                    {currentStep < 7 ? (
                        <Button 
                            size="sm" 
                            onClick={handleNext}
                            className="h-8 px-3.5 rounded-lg text-xs font-medium bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs"
                        >
                            Next <ChevronRight className="w-3.5 h-3.5 ml-0.5" />
                        </Button>
                    ) : (
                        <Button 
                            size="sm" 
                            onClick={handleFinish}
                            className="h-8 px-4 rounded-lg text-xs font-medium bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs"
                        >
                            Finish Tour
                        </Button>
                    )}
                </div>
            </div>
        </div>
    );
}

import React from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import axios from 'axios';
import {
    Sparkles, LayoutDashboard, Wallet, Boxes,
    ArrowUpRight, Bell, CheckCircle2, ChevronRight,
    FileText
} from 'lucide-react';
import { Link, router } from '@inertiajs/react';
import { __ } from '@/lib/i18n';

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

const steps = [
    {
        step: 1,
        eyebrow: "Welcome",
        title: "Your workspace,\nall in one place.",
        description: "Manage invoices, wallet balances, services, and operational workflows from a single unified platform.",
        icon: Sparkles,
        accent: "#6366f1",
    },
    {
        step: 2,
        eyebrow: "Dashboard",
        title: "Everything at\na glance.",
        description: "Live wallet balances, unpaid invoices, recent transactions, and quick actions — your central operations node.",
        icon: LayoutDashboard,
        accent: "#3b82f6",
    },
    {
        step: 3,
        eyebrow: "Billing",
        title: "Pay invoices\neffortlessly.",
        description: "Use your internal wallet or external payment methods to settle invoices. Deposit funds anytime to keep things moving.",
        icon: Wallet,
        accent: "#10b981",
    },
    {
        step: 4,
        eyebrow: "Modules",
        title: "A connected\necosystem.",
        description: "ERP, Freelance Hub, and Marketing workspaces — each specialized, all sharing one secure wallet, identity, and ledger.",
        icon: Boxes,
        accent: "#8b5cf6",
    },
    {
        step: 5,
        eyebrow: "Payouts",
        title: "Get paid\non your terms.",
        description: "Earn through Freelance or Marketplace orders, configure payout methods, and request withdrawals instantly.",
        icon: ArrowUpRight,
        accent: "#f59e0b",
    },
    {
        step: 6,
        eyebrow: "Notifications",
        title: "Never miss\na beat.",
        description: "Track invoice approvals, support resolutions, and workflow updates in real time — all in one feed.",
        icon: Bell,
        accent: "#ef4444",
    },
    {
        step: 7,
        eyebrow: "You're all set",
        title: "Ready to go.",
        description: "Start by exploring your dashboard, depositing wallet balance, or reviewing any outstanding invoices.",
        icon: CheckCircle2,
        accent: "#10b981",
    },
];

const TOTAL = steps.length;

export default function ProductTourModal({ user, isOpen, onClose, currentStep, onStepChange }: ProductTourProps) {
    if (!isOpen) return null;

    const stepData = steps[currentStep - 1] ?? steps[0];
    const Icon = stepData.icon;
    const isLast = currentStep === TOTAL;

    const saveTourStatus = async (params: { step?: number; skipped?: boolean; completed?: boolean }) => {
        try {
            await axios.post('/product-tour/status', params);
        } catch (e) {
            console.error('Failed to update tour status', e);
        }
    };

    const handleNext = () => {
        if (currentStep < TOTAL) {
            const next = currentStep + 1;
            onStepChange(next);
            saveTourStatus({ step: next });
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
        <div
            className="fixed inset-0 z-50 flex items-center justify-center"
            style={{ background: 'rgba(0,0,0,0.45)', backdropFilter: 'blur(12px)' }}
        >
            <AnimatePresence mode="wait">
                <motion.div
                    key={stepData.step}
                    initial={{ opacity: 0, scale: 0.97, y: 16 }}
                    animate={{ opacity: 1, scale: 1, y: 0 }}
                    exit={{ opacity: 0, scale: 0.97, y: -16 }}
                    transition={{ duration: 0.28, ease: [0.4, 0, 0.2, 1] }}
                    className="relative w-full max-w-md mx-4 rounded-3xl overflow-hidden"
                    style={{
                        background: 'rgba(255,255,255,0.97)',
                        boxShadow: '0 40px 80px rgba(0,0,0,0.25), 0 0 0 1px rgba(0,0,0,0.06)',
                    }}
                >
                    {/* Skip */}
                    <button
                        onClick={handleSkip}
                        className="absolute top-5 right-5 text-xs font-medium text-slate-400 hover:text-slate-700 transition-colors z-10"
                    >
                        Skip
                    </button>

                    {/* Content */}
                    <div className="px-10 pt-14 pb-8 text-center flex flex-col items-center">
                        {/* Icon */}
                        <div
                            className="w-16 h-16 rounded-2xl flex items-center justify-center mb-7 shadow-sm"
                            style={{ background: `${stepData.accent}18` }}
                        >
                            <Icon
                                className="w-8 h-8"
                                style={{ color: stepData.accent }}
                                strokeWidth={1.6}
                            />
                        </div>

                        {/* Eyebrow */}
                        <span
                            className="text-xs font-semibold uppercase tracking-widest mb-3 block"
                            style={{ color: stepData.accent }}
                        >
                            {stepData.eyebrow}
                        </span>

                        {/* Title */}
                        <h2
                            className="text-3xl font-bold tracking-tight text-slate-900 leading-tight mb-4 whitespace-pre-line"
                            style={{ fontFamily: "'Inter', -apple-system, BlinkMacSystemFont, sans-serif", letterSpacing: '-0.02em' }}
                        >
                            {stepData.title}
                        </h2>

                        {/* Description */}
                        <p className="text-sm text-slate-500 leading-relaxed max-w-xs">
                            {stepData.description}
                        </p>

                        {/* Step 7 CTA links */}
                        {isLast && (
                            <div className="flex gap-3 mt-6 w-full">
                                <Link
                                    href="/erp/wallet"
                                    onClick={handleFinish}
                                    className="flex-1 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:border-slate-400 transition-colors flex items-center justify-center gap-1.5"
                                >
                                    <Wallet className="w-3.5 h-3.5 text-emerald-500" />{__('general.add_balance')}</Link>
                                <Link
                                    href="/erp/invoices"
                                    onClick={handleFinish}
                                    className="flex-1 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:border-slate-400 transition-colors flex items-center justify-center gap-1.5"
                                >
                                    <FileText className="w-3.5 h-3.5 text-rose-500" />{__('general.view_invoices')}</Link>
                            </div>
                        )}
                    </div>

                    {/* Footer */}
                    <div className="px-10 pb-8 flex flex-col items-center gap-5">
                        {/* Dot indicators */}
                        <div className="flex items-center gap-1.5">
                            {steps.map(s => (
                                <button
                                    key={s.step}
                                    onClick={() => { onStepChange(s.step); saveTourStatus({ step: s.step }); }}
                                    className="rounded-full transition-all duration-300"
                                    style={{
                                        width: s.step === currentStep ? 20 : 6,
                                        height: 6,
                                        background: s.step === currentStep ? stepData.accent : '#d1d5db',
                                    }}
                                    aria-label={`Step ${s.step}`}
                                />
                            ))}
                        </div>

                        {/* Primary action */}
                        <button
                            onClick={isLast ? handleFinish : handleNext}
                            className="w-full py-3.5 rounded-2xl text-sm font-semibold text-white transition-opacity hover:opacity-90 flex items-center justify-center gap-1.5"
                            style={{ background: isLast ? '#10b981' : stepData.accent }}
                        >
                            {isLast ? 'Get Started' : (
                                <>Continue <ChevronRight className="w-4 h-4" /></>
                            )}
                        </button>
                    </div>
                </motion.div>
            </AnimatePresence>
        </div>
    );
}

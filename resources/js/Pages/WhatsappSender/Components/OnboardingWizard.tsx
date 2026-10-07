import { __ } from '@/lib/i18n';
import React from 'react';
import { CheckCircle2, Smartphone, FileText, Send, Sparkles, Zap } from 'lucide-react';

interface Props {
    hasConnectedAccount: boolean;
    hasTemplate: boolean;
    hasSentMessage: boolean;
    onConnectClick: () => void;
    onTemplateClick: () => void;
    onSendClick: () => void;
}

export default function OnboardingWizard({
    hasConnectedAccount,
    hasTemplate,
    hasSentMessage,
    onConnectClick,
    onTemplateClick,
    onSendClick,
}: Props) {
    const steps = [
        {
            number: 1,
            title: __('whatsapp.onboarding_step1_title'),
            subtitle: 'Meta Cloud API',
            isDone: hasConnectedAccount,
            onClick: onConnectClick,
            icon: Smartphone,
        },
        {
            number: 2,
            title: __('whatsapp.onboarding_step2_title'),
            subtitle: __('whatsapp.onboarding_step2_subtitle'),
            isDone: hasTemplate,
            onClick: onTemplateClick,
            icon: FileText,
        },
        {
            number: 3,
            title: __('whatsapp.onboarding_step3_title'),
            subtitle: __('whatsapp.onboarding_step3_subtitle'),
            isDone: hasSentMessage,
            onClick: onSendClick,
            icon: Send,
        },
        {
            number: 4,
            title: __('whatsapp.onboarding_step4_title'),
            subtitle: __('whatsapp.onboarding_step4_subtitle'),
            isDone: false,
            onClick: () => {},
            icon: Sparkles,
        },
    ];

    const completedCount = steps.filter(s => s.isDone).length;
    if (completedCount === 4) return null; // Hide wizard once setup is 100% complete

    return (
        <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-4 shadow-sm mb-6 text-zinc-900 dark:text-zinc-100">
            <div className="flex items-center justify-between mb-3">
                <div className="flex items-center gap-2">
                    <span className="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                    <h3 className="text-xs font-bold text-zinc-950 dark:text-zinc-50 uppercase tracking-wider">
                        <Zap className="inline w-3.5 h-3.5 me-1" aria-hidden="true" />
                        {__('whatsapp.onboarding_title', { done: completedCount, total: 4 })}
                    </h3>
                </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                {steps.map((step) => {
                    const Icon = step.icon;
                    return (
                        <div
                            key={step.number}
                            role="button"
                            tabIndex={0}
                            onClick={step.onClick}
                            onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); step.onClick(); } }}
                            className={`p-3 rounded-xl border flex items-center gap-3 cursor-pointer transition-all ${
                                step.isDone
                                    ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-300 dark:border-emerald-700/60 text-emerald-800 dark:text-emerald-300'
                                    : 'bg-zinc-50 dark:bg-zinc-950/60 hover:bg-zinc-100 dark:hover:bg-zinc-900 border-zinc-200 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300'
                            }`}
                        >
                            <div className={`w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs shrink-0 ${
                                step.isDone ? 'bg-emerald-600 text-white' : 'bg-zinc-200 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400'
                            }`}>
                                {step.isDone ? <CheckCircle2 className="w-4 h-4" /> : step.number}
                            </div>

                            <div className="flex-1 min-w-0">
                                <h4 className="text-xs font-bold truncate">{step.title}</h4>
                                <p className="text-[10px] text-zinc-500 dark:text-zinc-400 truncate">{step.subtitle}</p>
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

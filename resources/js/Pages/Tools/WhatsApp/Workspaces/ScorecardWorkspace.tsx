import React from 'react';
import { ShieldCheck, Sparkles } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function ScorecardWorkspace({ t, campaignResult }: any) {
    if (!campaignResult) return null;

    return (
        <div className="space-y-6 animate-in fade-in duration-300">
            {/* Overall campaign scorecard summary statistics */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-1.5">
                    <span className="text-[10px] font-black text-slate-400 uppercase tracking-wider">{t.scorecard.totalProcessed}</span>
                    <div className="text-3xl font-black text-slate-800">{campaignResult.total}</div>
                </div>
                <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-1.5">
                    <span className="text-[10px] font-black text-emerald-500 uppercase tracking-wider">{t.scorecard.sentSuccessfully}</span>
                    <div className="text-3xl font-black text-emerald-600">{campaignResult.sent}</div>
                </div>
                <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-1.5">
                    <span className="text-[10px] font-black text-amber-500 uppercase tracking-wider">{t.scorecard.failedOrSkipped}</span>
                    <div className="text-3xl font-black text-amber-600">{campaignResult.failed + campaignResult.skipped}</div>
                </div>
                <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-1.5">
                    <span className="text-[10px] font-black text-rose-500 uppercase tracking-wider">{t.scorecard.blocksReceived}</span>
                    <div className="text-3xl font-black text-rose-600">{campaignResult.blocked}</div>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Health grade & block rate diagnostic */}
                <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex flex-col justify-between space-y-6">
                    <div className="space-y-1">
                        <h3 className="font-bold text-slate-800 text-sm">{t.scorecard.title}</h3>
                        <p className="text-[10px] text-slate-400">{t.scorecard.description}</p>
                    </div>

                    <div className="flex flex-col items-center justify-center py-4 space-y-3">
                        {/* Health badge */}
                        <div className="w-24 h-24 rounded-full border-4 border-slate-850 flex items-center justify-center text-4xl font-black bg-gradient-to-tr from-slate-50 to-slate-100 text-slate-800 shadow-md">
                            {campaignResult.healthGrade || 'A+'}
                        </div>
                        <div className="text-center">
                            <span className="text-xs font-black text-slate-500 block">{t.scorecard.trustGrade}</span>
                            <span className="text-[10px] text-slate-400 font-medium mt-1 inline-block">{t.scorecard.scoreExplanation}</span>
                        </div>
                    </div>

                    <div className="space-y-3.5 border-t border-slate-100 pt-4">
                        <div className="flex justify-between text-xs font-semibold">
                            <span className="text-slate-450">{t.scorecard.blockRate}</span>
                            <span className="font-black text-rose-600">{campaignResult.blockRate || '0%'}</span>
                        </div>
                        <div className="flex justify-between text-xs font-semibold">
                            <span className="text-slate-450">{t.scorecard.banProbability}</span>
                            <span className="font-black text-amber-600">{campaignResult.banProbability || 'Low'}</span>
                        </div>
                    </div>
                </div>

                {/* Safe recommendation instruction panel */}
                <div className="lg:col-span-2 bg-gradient-to-tr from-slate-900 to-slate-950 border border-slate-800 rounded-2xl p-6 text-white shadow-lg flex flex-col justify-between relative overflow-hidden">
                    <div className="absolute top-0 end-0 w-48 h-48 bg-teal-500/10 rounded-full blur-3xl -z-10" />

                    <div className="space-y-4">
                        <div className="flex items-center gap-2 text-teal-400">
                            <ShieldCheck className="w-5 h-5" />
                            <h3 className="font-extrabold tracking-tight text-sm uppercase">{t.scorecard.warmingRecommendation}</h3>
                        </div>

                        <div className="space-y-3 leading-relaxed">
                            {/* Diagnosis output text block */}
                            <p className="text-xs text-slate-350 font-bold border-s-2 border-teal-500 ps-3">
                                {campaignResult.healthGrade === 'A+' ? t.scorecard.grades.excellent :
                                 campaignResult.healthGrade === 'B' ? t.scorecard.grades.good :
                                 campaignResult.healthGrade === 'C' ? t.scorecard.grades.warning :
                                 t.scorecard.grades.danger}
                            </p>
                            <p className="text-xs text-slate-400">
                                {campaignResult.recommendation || 'Anti-ban safety score diagnostic is fully optimized. We recommend keeping moderate pacing levels, proxy routing, and using AI message variations regularly for optimal delivery results.'}
                            </p>
                        </div>
                    </div>

                    <div className="border-t border-slate-800/80 pt-4 mt-6 flex justify-between items-center text-[10px] font-bold text-slate-500">
                        <span>{__('general.campaign_os_v3_safeguards')}</span>
                        <div className="flex items-center gap-1 bg-teal-500/10 text-teal-400 border border-teal-500/20 px-2 py-1 rounded-full uppercase">
                            <Sparkles className="w-3 h-3" />
                            <span>{__('general.ai_warming_active')}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

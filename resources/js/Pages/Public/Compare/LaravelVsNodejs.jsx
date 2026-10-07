import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { 
    CheckCircle2, 
    XCircle, 
    Zap, 
    Shield, 
    Database, 
    Server, 
    Cpu, 
    Layers, 
    ArrowRight, 
    MessageSquare,
    Calculator,
    Sparkles,
    Scale
} from 'lucide-react';
import { __ } from '@/lib/i18n';
import { openWhatsAppChat } from '@/lib/whatsapp';
import StudioHeader from '@/Components/Studio/StudioHeader';

export default function LaravelVsNodejs() {
    const comparisonMetrics = [
        {
            category: __('frontend.compare_lvn_m1_category'),
            icon: Database,
            laravel: {
                title: __('frontend.compare_lvn_m1_laravel_title'),
                desc: __('frontend.compare_lvn_m1_laravel_desc'),
                status: 'winner',
            },
            nodejs: {
                title: __('frontend.compare_lvn_m1_node_title'),
                desc: __('frontend.compare_lvn_m1_node_desc'),
                status: 'draw',
            },
        },
        {
            category: __('frontend.compare_lvn_m2_category'),
            icon: Zap,
            laravel: {
                title: __('frontend.compare_lvn_m2_laravel_title'),
                desc: __('frontend.compare_lvn_m2_laravel_desc'),
                status: 'winner',
            },
            nodejs: {
                title: __('frontend.compare_lvn_m2_node_title'),
                desc: __('frontend.compare_lvn_m2_node_desc'),
                status: 'winner',
            },
        },
        {
            category: __('frontend.compare_lvn_m3_category'),
            icon: Cpu,
            laravel: {
                title: __('frontend.compare_lvn_m3_laravel_title'),
                desc: __('frontend.compare_lvn_m3_laravel_desc'),
                status: 'winner',
            },
            nodejs: {
                title: __('frontend.compare_lvn_m3_node_title'),
                desc: __('frontend.compare_lvn_m3_node_desc'),
                status: 'draw',
            },
        },
        {
            category: __('frontend.compare_lvn_m4_category'),
            icon: Shield,
            laravel: {
                title: __('frontend.compare_lvn_m4_laravel_title'),
                desc: __('frontend.compare_lvn_m4_laravel_desc'),
                status: 'winner',
            },
            nodejs: {
                title: __('frontend.compare_lvn_m4_node_title'),
                desc: __('frontend.compare_lvn_m4_node_desc'),
                status: 'draw',
            },
        },
        {
            category: __('frontend.compare_lvn_m5_category'),
            icon: Scale,
            laravel: {
                title: __('frontend.compare_lvn_m5_laravel_title'),
                desc: __('frontend.compare_lvn_m5_laravel_desc'),
                status: 'winner',
            },
            nodejs: {
                title: __('frontend.compare_lvn_m5_node_title'),
                desc: __('frontend.compare_lvn_m5_node_desc'),
                status: 'draw',
            },
        },
    ];

    const verdictHighlights = [
        {
            title: __('frontend.compare_lvn_verdict_laravel_title'),
            points: [
                __('frontend.compare_lvn_verdict_laravel_p1'),
                __('frontend.compare_lvn_verdict_laravel_p2'),
                __('frontend.compare_lvn_verdict_laravel_p3'),
                __('frontend.compare_lvn_verdict_laravel_p4'),
            ],
            color: 'border-[#0071e3]/30 bg-[#0071e3]/5',
        },
        {
            title: __('frontend.compare_lvn_verdict_node_title'),
            points: [
                __('frontend.compare_lvn_verdict_node_p1'),
                __('frontend.compare_lvn_verdict_node_p2'),
                __('frontend.compare_lvn_verdict_node_p3'),
                __('frontend.compare_lvn_verdict_node_p4'),
            ],
            color: 'border-black/10 bg-[#f5f5f7]',
        },
    ];

    return (
        <PublicLayout>
            <Head>
                <title>{`${__('frontend.compare_lvn_meta_title')} | Musoftwares`}</title>
                <meta name="description" content={__('frontend.compare_lvn_meta_desc')} />
            </Head>

            <div className="w-full bg-[#ffffff] text-[#1d1d1f] font-sans selection:bg-[#0071e3]/20 selection:text-[#0071e3] pt-12 sm:pt-20 pb-24 sm:pb-36">
                
                {/* Hero Header */}
                <StudioHeader
                    badge={__('frontend.compare_lvn_badge')}
                    title={
                        <>
                            {__('frontend.compare_lvn_hero_title')} <br className="hidden sm:inline" />
                            <span className="text-[#0071e3]">{__('frontend.compare_lvn_hero_highlight')}</span>
                        </>
                    }
                    subtitle={__('frontend.compare_lvn_subtitle')}
                />

                {/* Quick Actions */}
                <div className="flex flex-col sm:flex-row gap-4 w-full sm:w-auto items-center justify-center text-xs mb-20 px-6">
                    <Link href="/estimator">
                        <button className="bg-[#0071e3] hover:bg-[#0077ed] text-white px-8 py-3 rounded-[980px] font-semibold tracking-wide transition-all shadow-md shadow-blue-500/20 cursor-pointer">
                            {__('frontend.compare_lvn_calc_scope')} ➔
                        </button>
                    </Link>
                    <button 
                        onClick={() => openWhatsAppChat(__('frontend.compare_lvn_consult_message'))}
                        className="border border-black/10 hover:border-black/30 bg-white text-[#1d1d1f] hover:bg-[#f5f5f7] px-8 py-3 rounded-[980px] font-semibold tracking-wide transition-all shadow-sm cursor-pointer flex items-center gap-2"
                    >
                        <MessageSquare className="w-4 h-4 text-[#0071e3]" />
                        <span>{__('frontend.compare_lvn_consult_architect')}</span>
                    </button>
                </div>

                <div className="max-w-[1400px] mx-auto px-6 sm:px-12 space-y-20">
                    
                    {/* Executive Summary Card */}
                    <div className="p-8 sm:p-12 bg-white border border-black/5 rounded-[24px] shadow-sm relative overflow-hidden">
                        <div className="absolute top-0 right-0 w-96 h-96 bg-[#0071e3]/5 rounded-full blur-3xl pointer-events-none" />
                        <div className="relative z-10 space-y-4 max-w-4xl">
                            <span className="text-xs uppercase tracking-wider text-[#0071e3] font-semibold">
                                {__('frontend.compare_lvn_exec_summary')}
                            </span>
                            <h2 className="text-2xl sm:text-3xl font-semibold text-[#1d1d1f] tracking-tight font-sans">
                                {__('frontend.compare_lvn_exec_title')}
                            </h2>
                            <p className="text-sm sm:text-base text-[#1d1d1f]/70 leading-relaxed font-sans">
                                {__('frontend.compare_lvn_exec_p1')} <strong className="text-[#1d1d1f]">Laravel 12 + Inertia.js + PostgreSQL</strong> {__('frontend.compare_lvn_exec_p2')} <strong className="text-[#0071e3]">{__('frontend.compare_lvn_exec_p2_highlight')}</strong> {__('frontend.compare_lvn_exec_p3')}
                            </p>
                        </div>
                    </div>

                    {/* Detailed Comparison Matrix */}
                    <div className="space-y-8">
                        <div className="text-center max-w-3xl mx-auto space-y-2">
                            <span className="text-xs uppercase tracking-wider text-[#0071e3] font-semibold">
                                {__('frontend.compare_lvn_showdown')}
                            </span>
                            <h3 className="text-2xl sm:text-4xl font-semibold text-[#1d1d1f] tracking-tight font-sans">
                                {__('frontend.compare_lvn_matrix_title')}
                            </h3>
                        </div>

                        <div className="grid grid-cols-1 gap-6">
                            {comparisonMetrics.map((metric, idx) => {
                                const IconComp = metric.icon;
                                return (
                                    <div key={idx} className="bg-white border border-black/5 rounded-[24px] p-6 sm:p-8 space-y-6 shadow-sm">
                                        <div className="flex items-center gap-3 border-b border-black/5 pb-4">
                                            <div className="p-2.5 rounded-xl bg-[#0071e3]/10 text-[#0071e3]">
                                                <IconComp className="h-5 w-5" />
                                            </div>
                                            <h4 className="font-semibold text-lg text-[#1d1d1f] font-sans">
                                                {metric.category}
                                            </h4>
                                        </div>

                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            {/* Laravel Column */}
                                            <div className="p-5 rounded-[20px] bg-[#0071e3]/5 border border-[#0071e3]/20 space-y-2 text-xs">
                                                <div className="flex items-center justify-between text-[#1d1d1f] font-semibold text-sm">
                                                    <span className="text-[#0071e3] flex items-center gap-1.5">
                                                        <CheckCircle2 className="h-4 w-4" />
                                                        {__('frontend.compare_lvn_laravel_column')}
                                                    </span>
                                                    <span className="text-[10px] uppercase px-2.5 py-0.5 rounded-full bg-[#0071e3]/10 text-[#0071e3] font-semibold">
                                                        {__('frontend.compare_lvn_recommended')}
                                                    </span>
                                                </div>
                                                <div className="font-semibold text-[#1d1d1f]">{metric.laravel.title}</div>
                                                <p className="text-[#1d1d1f]/70 font-sans text-xs leading-relaxed">
                                                    {metric.laravel.desc}
                                                </p>
                                            </div>

                                            {/* Node.js Column */}
                                            <div className="p-5 rounded-[20px] bg-[#f5f5f7] border border-black/5 space-y-2 text-xs">
                                                <div className="flex items-center justify-between text-[#1d1d1f] font-semibold text-sm">
                                                    <span className="text-[#1d1d1f]/70 flex items-center gap-1.5">
                                                        <Server className="h-4 w-4" />
                                                        {__('frontend.compare_lvn_node_column')}
                                                    </span>
                                                    <span className="text-[10px] uppercase px-2.5 py-0.5 rounded-full bg-black/5 text-[#1d1d1f]/60 font-semibold">
                                                        {__('frontend.compare_lvn_specialized')}
                                                    </span>
                                                </div>
                                                <div className="font-semibold text-[#1d1d1f]/80">{metric.nodejs.title}</div>
                                                <p className="text-[#1d1d1f]/60 font-sans text-xs leading-relaxed">
                                                    {metric.nodejs.desc}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    {/* Verdict Columns */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                        {verdictHighlights.map((verdict, i) => (
                            <div key={i} className={`p-8 rounded-[24px] border ${verdict.color} space-y-6 shadow-sm`}>
                                <h4 className="text-xl font-semibold text-[#1d1d1f] font-sans flex items-center gap-2">
                                    <Sparkles className="w-5 h-5 text-[#0071e3]" />
                                    {verdict.title}
                                </h4>
                                <ul className="space-y-3 font-sans text-sm text-[#1d1d1f]/80">
                                    {verdict.points.map((pt, idx) => (
                                        <li key={idx} className="flex items-start gap-2.5">
                                            <CheckCircle2 className="w-4 h-4 text-[#0071e3] shrink-0 mt-0.5" />
                                            <span>{pt}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ))}
                    </div>

                    {/* Studio Call To Action */}
                    <div className="bg-[#f5f5f7] p-8 sm:p-12 border border-black/5 rounded-[28px] text-center space-y-6">
                        <span className="text-xs uppercase tracking-wider text-[#0071e3] font-semibold block">
                            {__('frontend.compare_lvn_studio_advantage')}
                        </span>
                        <h3 className="text-2xl sm:text-4xl font-semibold text-[#1d1d1f] tracking-tight font-sans">
                            {__('frontend.compare_lvn_studio_title')}
                        </h3>
                        <p className="text-sm text-[#1d1d1f]/60 max-w-2xl mx-auto font-sans leading-relaxed">
                            {__('frontend.compare_lvn_studio_desc')}
                        </p>
                        <div className="pt-2 flex items-center justify-center gap-4 flex-wrap text-xs">
                            <Link href="/estimator">
                                <button className="px-8 py-3.5 rounded-[980px] bg-[#0071e3] hover:bg-[#0077ed] text-white font-semibold tracking-wide transition-colors cursor-pointer shadow-md shadow-blue-500/20">
                                    {__('frontend.compare_lvn_launch_estimator')}
                                </button>
                            </Link>
                            <Link href="/portfolio">
                                <button className="px-8 py-3.5 rounded-[980px] bg-white hover:bg-[#e5e5ea] text-[#1d1d1f] border border-black/10 font-semibold tracking-wide transition-colors cursor-pointer shadow-sm">
                                    {__('frontend.compare_lvn_case_studies')}
                                </button>
                            </Link>
                        </div>
                    </div>

                </div>

            </div>
        </PublicLayout>
    );
}

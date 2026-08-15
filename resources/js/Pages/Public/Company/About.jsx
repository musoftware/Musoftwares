import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Sparkles, ShieldCheck, Lock, Cpu, ArrowRight, MessageSquare, Compass, CheckCircle2 } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function About({ canLogin, canRegister }) {
    const phoneNumber = "201015218548";

    const openWhatsApp = (msg) => {
        const encodedMessage = encodeURIComponent(msg);
        window.open(`https://wa.me/${phoneNumber}?text=${encodedMessage}`, '_blank');
    };

    const pillars = [
        {
            icon: Cpu,
            title: __('landing_company.about_engineering_first_title') || 'Engineering-First Philosophy',
            desc: __('landing_company.about_engineering_first_body') || 'We do not build minimum disposable prototypes. We engineer scalable systems that run continuous business operations with zero unexpected downtime.',
        },
        {
            icon: ShieldCheck,
            title: __('landing_company.about_long_term_title') || 'Long-Term Durability & Craftsmanship',
            desc: __('landing_company.about_long_term_body') || 'From clean database relational schemas to precise financial BC-math operations, every line of code is structured for multi-year stability.',
        },
        {
            icon: Lock,
            title: __('landing_company.about_data_sovereignty_title') || 'Complete Data Sovereignty & Isolation',
            desc: __('landing_company.about_data_sovereignty_body') || 'Your operational records and financial ledgers belong to you. We implement strict multi-tenant isolation and data protection by design.',
        },
    ];

    return (
        <PublicLayout auth={{ user: null }}>
            <Head title={`${__('landing_company.about_meta_title') || 'About Us'} | Musoftware`} />

            <div className="w-full bg-white text-[#1d1d1f] font-sans selection:bg-[#1d1d1f] selection:text-white pt-12 sm:pt-20 pb-20 sm:pb-32">
                
                {/* Hero Section */}
                <section className="px-6 max-w-5xl mx-auto flex flex-col items-center text-center mb-20 sm:mb-28">
                    <p className="text-base sm:text-xl text-[#86868b] font-medium mb-3 sm:mb-4 tracking-tight">
                        The Software Studio
                    </p>

                    <h1 className="text-4xl sm:text-6xl md:text-7xl lg:text-[86px] leading-[1.05] font-bold text-[#1d1d1f] max-w-5xl mb-6 tracking-tight">
                        {__('landing_company.about_title') || 'We engineer software'} <br className="hidden sm:inline" />
                        <span className="bg-gradient-to-r from-[#0066cc] to-[#3399ff] bg-clip-text text-transparent">
                            with pure simplicity.
                        </span>
                    </h1>

                    <p className="text-lg sm:text-2xl md:text-[26px] text-[#86868b] max-w-3xl mb-10 sm:mb-12 font-medium leading-snug tracking-tight">
                        {__('landing_company.about_subtitle') || 'One dedicated engineering studio, 10+ years of operational architecture, and 30+ production platforms shipped worldwide.'}
                    </p>

                    <div className="flex flex-col sm:flex-row gap-4 sm:gap-6 w-full sm:w-auto items-center justify-center">
                        <Link href="/company/careers" className="w-full sm:w-auto">
                            <button className="bg-[#1d1d1f] hover:bg-[#333336] text-white px-8 py-3.5 rounded-full text-[17px] font-semibold w-full sm:w-auto transition-all cursor-pointer shadow-sm hover:scale-[1.02] active:scale-95">
                                {__('landing_company.about_join_team') || 'Join the Team'}
                            </button>
                        </Link>
                        <button
                            onClick={() => openWhatsApp("Hello Mahmoud, I'd like to learn more about Musoftware.")}
                            className="text-[#0066cc] hover:text-[#0077ed] text-[17px] font-semibold flex items-center gap-1 group transition-colors cursor-pointer"
                        >
                            <span>Talk with Architect</span>
                            <ChevronRight className="w-4 h-4 group-hover:translate-x-1 transition-transform rtl:rotate-180" />
                        </button>
                    </div>
                </section>

                {/* 3 Core Studio Pillars */}
                <section className="px-6 max-w-6xl mx-auto mb-20 sm:mb-28">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        {pillars.map((p, idx) => {
                            const IconComponent = p.icon;
                            return (
                                <div key={idx} className="bg-[#f5f5f7] rounded-[28px] p-8 sm:p-10 flex flex-col justify-between h-full border border-[#d2d2d7]/50 shadow-xs">
                                    <div>
                                        <div className="w-12 h-12 rounded-2xl bg-white border border-[#d2d2d7]/60 flex items-center justify-center mb-6 text-[#1d1d1f]">
                                            <IconComponent className="w-6 h-6" />
                                        </div>
                                        <h3 className="text-xl font-bold text-[#1d1d1f] tracking-tight mb-3">
                                            {p.title}
                                        </h3>
                                        <p className="text-[15px] text-[#86868b] leading-relaxed font-medium">
                                            {p.desc}
                                        </p>
                                    </div>
                                    <div className="pt-6 mt-6 border-t border-[#d2d2d7]/40 flex items-center gap-1.5 text-xs font-bold text-zinc-900">
                                        <CheckCircle2 className="w-4 h-4 text-emerald-600" />
                                        <span>Engineered in Production</span>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </section>

            </div>
        </PublicLayout>
    );
}

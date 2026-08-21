import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { ShieldCheck, Lock, Cpu, ArrowRight } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { openWhatsAppChat } from '@/lib/whatsapp';
import StudioHeader from '@/Components/Studio/StudioHeader';

export default function About({ canLogin, canRegister }) {
    const pillars = [
        {
            icon: Cpu,
            title: __('landing_company.about_engineering_first_title') || 'Engineering-First Philosophy',
            desc: __('landing_company.about_engineering_first_body') || 'We do not build disposable prototypes. We engineer scalable, high-throughput systems that run continuous business operations with zero unexpected downtime.',
        },
        {
            icon: ShieldCheck,
            title: __('landing_company.about_long_term_title') || 'Long-Term Durability & Craftsmanship',
            desc: __('landing_company.about_long_term_body') || 'From clean database relational schemas to precise financial BC-math ledgers, every line of code is structured for multi-year stability.',
        },
        {
            icon: Lock,
            title: __('landing_company.about_data_sovereignty_title') || 'Complete Data Sovereignty & Isolation',
            desc: __('landing_company.about_data_sovereignty_body') || 'Your operational records and financial ledgers belong to you. We implement strict multi-tenant isolation and zero-leak data protection by design.',
        },
    ];

    return (
        <PublicLayout>
            <Head title={`${__('landing_company.about_meta_title') || 'About Studio'} | Musoftwares`} />

            <div className="w-full bg-[#ffffff] text-[#1d1d1f] font-sans selection:bg-[#0071e3]/20 selection:text-[#0071e3] pt-12 sm:pt-20 pb-24 sm:pb-36">
                
                {/* Reusable Hero Header */}
                <StudioHeader
                    badge="The Software Studio"
                    title={
                        <>
                            Engineering with Scale. <br className="hidden sm:inline" />
                            <span className="text-[#0071e3]">Pure Architectural Clarity.</span>
                        </>
                    }
                    subtitle={__('landing_company.about_subtitle') || 'One dedicated engineering studio, 10+ years of operational architecture, and 30+ production platforms shipped worldwide.'}
                />

                <div className="flex flex-col sm:flex-row gap-4 w-full sm:w-auto items-center justify-center text-xs mb-20">
                    <Link href="/company/contact">
                        <button className="bg-[#0071e3] hover:bg-[#0077ed] text-white px-8 py-3 rounded-[980px] font-semibold tracking-wide transition-all shadow-md shadow-blue-500/20 cursor-pointer">
                            {__('general.initiate_brief') || 'INITIATE BRIEF'} ➔
                        </button>
                    </Link>
                    <button 
                        onClick={() => openWhatsAppChat("Hello Mahmoud, I want to learn more about the studio.")}
                        className="border border-black/10 hover:border-black/30 bg-white text-[#1d1d1f] hover:bg-[#f5f5f7] px-8 py-3 rounded-[980px] font-semibold tracking-wide transition-all shadow-sm cursor-pointer"
                    >
                        {__('general.talk_with_architect') || 'TALK WITH ARCHITECT'}
                    </button>
                </div>

                {/* Editorial Pillars Section */}
                <section className="px-6 max-w-[1400px] mx-auto mb-24 sm:mb-32">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        {pillars.map((pillar, idx) => {
                            const Icon = pillar.icon;
                            return (
                                <div 
                                    key={idx} 
                                    className="bg-white border border-black/5 rounded-[24px] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#0071e3]/40 hover:shadow-md transition-all shadow-sm"
                                >
                                    <div className="space-y-4">
                                        <div className="w-12 h-12 rounded-2xl bg-[#0071e3]/10 flex items-center justify-center text-[#0071e3]">
                                            <Icon className="w-6 h-6" />
                                        </div>
                                        <h3 className="text-lg font-semibold text-[#1d1d1f] tracking-tight font-sans">
                                            {pillar.title}
                                        </h3>
                                        <p className="text-sm text-[#1d1d1f]/60 leading-relaxed font-sans">
                                            {pillar.desc}
                                        </p>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </section>

                {/* Chief Architect Profile Strip */}
                <section className="px-6 max-w-[1400px] mx-auto border-t border-black/5 pt-16">
                    <div className="bg-white border border-black/5 rounded-[24px] p-8 sm:p-12 flex flex-col lg:flex-row items-center justify-between gap-10 shadow-sm">
                        <div className="space-y-3 max-w-2xl">
                            <span className="text-xs uppercase tracking-wider text-[#0071e3] font-semibold">
                                Leadership
                            </span>
                            <h2 className="text-2xl sm:text-3xl font-semibold text-[#1d1d1f] font-sans tracking-tight">
                                Mahmoud Amin — Chief Software Architect
                            </h2>
                            <p className="text-sm text-[#1d1d1f]/60 font-sans leading-relaxed">
                                Full-lifecycle software engineer specializing in Enterprise ERP backbones, high-frequency transactional data models, and verified Meta API automations.
                            </p>
                        </div>
                        <Link href="/about/mahmoud-amin">
                            <button className="px-8 py-3 rounded-[980px] border border-black/10 bg-white text-[#1d1d1f] hover:bg-[#f5f5f7] text-xs font-semibold tracking-wide uppercase transition-all whitespace-nowrap shadow-sm cursor-pointer">
                                READ LEADERSHIP BIO ➔
                            </button>
                        </Link>
                    </div>
                </section>

            </div>
        </PublicLayout>
    );
}

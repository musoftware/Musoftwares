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

            <div className="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white pt-16 sm:pt-24 pb-24 sm:pb-36">
                
                {/* Reusable Hero Header */}
                <StudioHeader
                    badge="The Software Studio"
                    title={
                        <>
                            Engineering with Scale. <br className="hidden sm:inline" />
                            <span className="text-[#748660]">Pure Architectural Clarity.</span>
                        </>
                    }
                    subtitle={__('landing_company.about_subtitle') || 'One dedicated engineering studio, 10+ years of operational architecture, and 30+ production platforms shipped worldwide.'}
                />

                <div className="flex flex-col sm:flex-row gap-4 w-full sm:w-auto items-center justify-center font-mono text-xs mb-20">
                    <Link href="/company/contact">
                        <button className="bg-white text-black hover:bg-zinc-200 px-8 py-3.5 font-bold uppercase tracking-widest rtl:tracking-normal transition-all">
                            {__('general.initiate_brief') || 'INITIATE BRIEF'} ➔
                        </button>
                    </Link>
                    <button 
                        onClick={() => openWhatsAppChat("Hello Mahmoud, I want to learn more about the studio.")}
                        className="border border-[#333333] hover:border-white text-zinc-300 hover:text-white px-8 py-3.5 font-bold uppercase tracking-widest rtl:tracking-normal transition-all"
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
                                    className="bg-[#161616] border border-[#262626] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#748660] transition-colors"
                                >
                                    <div className="space-y-4">
                                        <div className="w-12 h-12 bg-black border border-[#2B2B2B] flex items-center justify-center text-[#748660]">
                                            <Icon className="w-6 h-6" />
                                        </div>
                                        <h3 className="text-lg font-bold text-white tracking-tight font-sans">
                                            {pillar.title}
                                        </h3>
                                        <p className="text-sm text-zinc-400 leading-relaxed font-sans">
                                            {pillar.desc}
                                        </p>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </section>

                {/* Chief Architect Profile Strip */}
                <section className="px-6 max-w-[1400px] mx-auto border-t border-[#222222] pt-20">
                    <div className="bg-[#161616] border border-[#262626] p-8 sm:p-12 flex flex-col lg:flex-row items-center justify-between gap-10">
                        <div className="space-y-3 max-w-2xl">
                            <span className="text-xs font-mono uppercase tracking-widest rtl:tracking-normal text-[#748660] font-bold">
                                Leadership
                            </span>
                            <h2 className="text-2xl sm:text-3xl font-bold text-white font-sans">
                                Mahmoud Amin — Chief Software Architect
                            </h2>
                            <p className="text-sm text-zinc-400 font-sans leading-relaxed">
                                Full-lifecycle software engineer specializing in Enterprise ERP backbones, high-frequency transactional data models, and verified Meta API automations.
                            </p>
                        </div>
                        <Link href="/about/mahmoud-amin">
                            <button className="px-8 py-3.5 border border-white text-white hover:bg-white hover:text-black text-xs font-bold font-mono tracking-widest rtl:tracking-normal uppercase transition-all whitespace-nowrap">
                                READ LEADERSHIP BIO ➔
                            </button>
                        </Link>
                    </div>
                </section>

            </div>
        </PublicLayout>
    );
}

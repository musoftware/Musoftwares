import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { Mail, MessageSquare, Globe, ArrowUpRight } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { openWhatsAppChat } from '@/lib/whatsapp';
import StudioHeader from '@/Components/Studio/StudioHeader';

export default function Contact({ canLogin, canRegister }) {
    return (
        <PublicLayout>
            <Head title={`${__('landing_company.contact_meta_title') || 'Contact Studio'} | Musoftwares`} />

            <div className="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white pt-16 sm:pt-24 pb-24 sm:pb-36">

                {/* Reusable Hero Header */}
                <StudioHeader
                    badge="Direct Technical Communication"
                    title={
                        <>
                            Talk Directly with <br className="hidden sm:inline" />
                            <span className="text-[#748660]">The Software Architect.</span>
                        </>
                    }
                    subtitle={__('landing_company.contact_subtitle') || 'No layers of middle management. Direct technical communication, rapid responses, and transparent scoping.'}
                />

                <div className="flex flex-col sm:flex-row gap-4 w-full sm:w-auto items-center justify-center font-mono text-xs mb-20">
                    <button
                        onClick={() => openWhatsAppChat("Hello Mahmoud, I'd like to discuss a project with Musoftware.")}
                        className="bg-white text-black hover:bg-zinc-200 px-8 py-3.5 font-bold uppercase tracking-widest rtl:tracking-normal transition-all"
                    >
                        {__('general.whatsapp_direct') || 'WHATSAPP DIRECT CHAT'} ➔
                    </button>
                    <a
                        href="mailto:admin@musoftwares.com"
                        className="border border-[#333333] hover:border-white text-zinc-300 hover:text-white px-8 py-3.5 font-bold uppercase tracking-widest rtl:tracking-normal transition-all"
                    >
                        admin@musoftwares.com
                    </a>
                </div>

                {/* 3 Contact Channels Cards */}
                <section className="px-6 max-w-[1400px] mx-auto">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">

                        {/* Channel 1: WhatsApp */}
                        <div className="bg-[#161616] border border-[#262626] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#748660] transition-colors">
                            <div className="space-y-4">
                                <div className="w-12 h-12 bg-black border border-[#2B2B2B] flex items-center justify-center text-[#748660]">
                                    <MessageSquare className="w-6 h-6" />
                                </div>
                                <h3 className="text-lg font-bold text-white tracking-tight font-sans">
                                    Instant Direct WhatsApp
                                </h3>
                                <p className="text-sm text-zinc-400 font-sans leading-relaxed">
                                    Fastest way to get in touch. Technical consultations, urgent scope reviews, and architecture briefs.
                                </p>
                            </div>
                            <button
                                onClick={() => openWhatsAppChat("Hello Mahmoud, I want to initiate a scope.")}
                                className="mt-8 text-xs font-mono font-bold text-white hover:text-[#748660] flex items-center gap-1 rtl:gap-reverse"
                            >
                                <span>OPEN WHATSAPP CHAT</span>
                                <ArrowUpRight className="w-4 h-4 rtl:rotate-[-90deg]" />
                            </button>
                        </div>

                        {/* Channel 2: Email Studio */}
                        <div className="bg-[#161616] border border-[#262626] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#748660] transition-colors">
                            <div className="space-y-4">
                                <div className="w-12 h-12 bg-black border border-[#2B2B2B] flex items-center justify-center text-[#748660]">
                                    <Mail className="w-6 h-6" />
                                </div>
                                <h3 className="text-lg font-bold text-white tracking-tight font-sans">
                                    Formal Proposal Inbox
                                </h3>
                                <p className="text-sm text-zinc-400 font-sans leading-relaxed">
                                    Send RFP documents, NDA agreements, and multi-platform specification files for detailed review.
                                </p>
                            </div>
                            <a
                                href="mailto:admin@musoftwares.com"
                                className="mt-8 text-xs font-mono font-bold text-white hover:text-[#748660] flex items-center gap-1 rtl:gap-reverse"
                            >
                                <span>SEND EMAIL BRIEF</span>
                                <ArrowUpRight className="w-4 h-4 rtl:rotate-[-90deg]" />
                            </a>
                        </div>

                        {/* Channel 3: Global Delivery */}
                        <div className="bg-[#161616] border border-[#262626] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#748660] transition-colors">
                            <div className="space-y-4">
                                <div className="w-12 h-12 bg-black border border-[#2B2B2B] flex items-center justify-center text-[#748660]">
                                    <Globe className="w-6 h-6" />
                                </div>
                                <h3 className="text-lg font-bold text-white tracking-tight font-sans">
                                    Worldwide Delivery
                                </h3>
                                <p className="text-sm text-zinc-400 font-sans leading-relaxed">
                                    Headquartered in Suez, Egypt. Deploying mission-critical platforms to clients worldwide.
                                </p>
                            </div>
                            <div className="mt-8 text-xs font-mono text-zinc-400">
                                Suez, Egypt (Cairo Timezone UTC+2 / UTC+3)
                            </div>
                        </div>

                    </div>
                </section>

            </div>
        </PublicLayout>
    );
}

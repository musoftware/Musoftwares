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

            <div className="w-full bg-[#ffffff] text-[#1d1d1f] font-sans selection:bg-[#0071e3]/20 selection:text-[#0071e3] pt-12 sm:pt-20 pb-24 sm:pb-36">

                {/* Reusable Hero Header */}
                <StudioHeader
                    badge="Direct Technical Communication"
                    title={
                        <>
                            Talk Directly with <br className="hidden sm:inline" />
                            <span className="text-[#0071e3]">The Software Architect.</span>
                        </>
                    }
                    subtitle={__('landing_company.contact_subtitle') || 'No layers of middle management. Direct technical communication, rapid responses, and transparent scoping.'}
                />

                <div className="flex flex-col sm:flex-row gap-4 w-full sm:w-auto items-center justify-center text-xs mb-20">
                    <button
                        onClick={() => openWhatsAppChat("Hello Mahmoud, I'd like to discuss a project with Musoftware.")}
                        className="bg-[#0071e3] hover:bg-[#0077ed] text-white px-8 py-3 rounded-[980px] font-semibold tracking-wide transition-all shadow-md shadow-blue-500/20 cursor-pointer"
                    >
                        {__('general.whatsapp_direct') || 'WHATSAPP DIRECT CHAT'} ➔
                    </button>
                    <a
                        href="mailto:admin@musoftwares.com"
                        className="border border-black/10 hover:border-black/30 bg-white text-[#1d1d1f] hover:bg-[#f5f5f7] px-8 py-3 rounded-[980px] font-semibold tracking-wide transition-all shadow-sm"
                    >
                        admin@musoftwares.com
                    </a>
                </div>

                {/* 3 Contact Channels Cards */}
                <section className="px-6 max-w-[1400px] mx-auto">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">

                        {/* Channel 1: WhatsApp */}
                        <div className="bg-white border border-black/5 rounded-[24px] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#0071e3]/40 hover:shadow-md transition-all shadow-sm">
                            <div className="space-y-4">
                                <div className="w-12 h-12 rounded-2xl bg-[#0071e3]/10 flex items-center justify-center text-[#0071e3]">
                                    <MessageSquare className="w-6 h-6" />
                                </div>
                                <h3 className="text-lg font-semibold text-[#1d1d1f] tracking-tight font-sans">
                                    Instant Direct WhatsApp
                                </h3>
                                <p className="text-sm text-[#1d1d1f]/60 font-sans leading-relaxed">
                                    Fastest way to get in touch. Technical consultations, urgent scope reviews, and architecture briefs.
                                </p>
                            </div>
                            <button
                                onClick={() => openWhatsAppChat("Hello Mahmoud, I want to initiate a scope.")}
                                className="mt-8 text-xs font-semibold text-[#0071e3] hover:text-[#0077ed] flex items-center gap-1 rtl:gap-reverse cursor-pointer"
                            >
                                <span>OPEN WHATSAPP CHAT</span>
                                <ArrowUpRight className="w-4 h-4 rtl:rotate-[-90deg]" />
                            </button>
                        </div>

                        {/* Channel 2: Email Studio */}
                        <div className="bg-white border border-black/5 rounded-[24px] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#0071e3]/40 hover:shadow-md transition-all shadow-sm">
                            <div className="space-y-4">
                                <div className="w-12 h-12 rounded-2xl bg-[#0071e3]/10 flex items-center justify-center text-[#0071e3]">
                                    <Mail className="w-6 h-6" />
                                </div>
                                <h3 className="text-lg font-semibold text-[#1d1d1f] tracking-tight font-sans">
                                    Formal Proposal Inbox
                                </h3>
                                <p className="text-sm text-[#1d1d1f]/60 font-sans leading-relaxed">
                                    Send RFP documents, NDA agreements, and multi-platform specification files for detailed review.
                                </p>
                            </div>
                            <a
                                href="mailto:admin@musoftwares.com"
                                className="mt-8 text-xs font-semibold text-[#0071e3] hover:text-[#0077ed] flex items-center gap-1 rtl:gap-reverse"
                            >
                                <span>SEND EMAIL BRIEF</span>
                                <ArrowUpRight className="w-4 h-4 rtl:rotate-[-90deg]" />
                            </a>
                        </div>

                        {/* Channel 3: Global Delivery */}
                        <div className="bg-white border border-black/5 rounded-[24px] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#0071e3]/40 hover:shadow-md transition-all shadow-sm">
                            <div className="space-y-4">
                                <div className="w-12 h-12 rounded-2xl bg-[#0071e3]/10 flex items-center justify-center text-[#0071e3]">
                                    <Globe className="w-6 h-6" />
                                </div>
                                <h3 className="text-lg font-semibold text-[#1d1d1f] tracking-tight font-sans">
                                    Worldwide Delivery
                                </h3>
                                <p className="text-sm text-[#1d1d1f]/60 font-sans leading-relaxed">
                                    Headquartered in Suez, Egypt. Deploying mission-critical platforms to clients worldwide.
                                </p>
                            </div>
                            <div className="mt-8 text-xs text-[#1d1d1f]/60 font-medium">
                                Suez, Egypt (Cairo Timezone UTC+2 / UTC+3)
                            </div>
                        </div>

                    </div>
                </section>

            </div>
        </PublicLayout>
    );
}

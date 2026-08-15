import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { MapPin, Mail, Phone, MessageSquare, Sparkles, Clock, Globe, ChevronRight } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function Contact({ canLogin, canRegister }) {
    const phoneNumber = "201015218548";

    const openWhatsApp = (msg) => {
        const encodedMessage = encodeURIComponent(msg);
        window.open(`https://wa.me/${phoneNumber}?text=${encodedMessage}`, '_blank');
    };

    return (
        <PublicLayout auth={{ user: null }}>
            <Head title={`${__('landing_company.contact_meta_title') || 'Contact Us'} | Musoftware`} />

            <div className="w-full bg-white text-[#1d1d1f] font-sans selection:bg-[#1d1d1f] selection:text-white pt-12 sm:pt-20 pb-20 sm:pb-32">
                
                {/* Hero Section */}
                <section className="px-6 max-w-5xl mx-auto flex flex-col items-center text-center mb-20 sm:mb-28">
                    <p className="text-base sm:text-xl text-[#86868b] font-medium mb-3 sm:mb-4 tracking-tight">
                        Direct Technical Communication
                    </p>

                    <h1 className="text-4xl sm:text-6xl md:text-7xl lg:text-[86px] leading-[1.05] font-bold text-[#1d1d1f] max-w-5xl mb-6 tracking-tight">
                        {__('landing_company.contact_title') || 'Talk directly with'} <br className="hidden sm:inline" />
                        <span className="bg-gradient-to-r from-[#0066cc] to-[#3399ff] bg-clip-text text-transparent">
                            the software architect.
                        </span>
                    </h1>

                    <p className="text-lg sm:text-2xl md:text-[26px] text-[#86868b] max-w-3xl mb-10 sm:mb-12 font-medium leading-snug tracking-tight">
                        {__('landing_company.contact_subtitle') || 'No layers of middle management. Direct technical communication, rapid responses, and transparent scoping.'}
                    </p>

                    <div className="flex flex-col sm:flex-row gap-4 sm:gap-6 w-full sm:w-auto items-center justify-center">
                        <button
                            onClick={() => openWhatsApp("Hello Mahmoud, I'd like to discuss a project with Musoftware.")}
                            className="bg-[#1d1d1f] hover:bg-[#333336] text-white px-8 py-3.5 rounded-full text-[17px] font-semibold w-full sm:w-auto transition-all cursor-pointer shadow-sm hover:scale-[1.02] active:scale-95"
                        >
                            {__('landing_company.contact_support_whatsapp') || 'WhatsApp Direct Chat'}
                        </button>
                        <a
                            href="mailto:admin@musoftwares.com"
                            className="text-[#0066cc] hover:text-[#0077ed] text-[17px] font-semibold flex items-center gap-1 group transition-colors cursor-pointer"
                        >
                            <span>admin@musoftwares.com</span>
                            <ChevronRight className="w-4 h-4 group-hover:translate-x-1 transition-transform rtl:rotate-180" />
                        </a>
                    </div>
                </section>

                {/* 3 Contact Channels Cards */}
                <section className="px-6 max-w-6xl mx-auto">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        
                        {/* Channel 1: WhatsApp & Technical Leadership */}
                        <div className="bg-[#f5f5f7] rounded-[28px] p-8 sm:p-10 flex flex-col justify-between h-full border border-[#0066cc]/20 shadow-xs">
                            <div>
                                <div className="w-12 h-12 rounded-2xl bg-white border border-[#d2d2d7]/60 flex items-center justify-center mb-6 text-emerald-600">
                                    <MessageSquare className="w-6 h-6" />
                                </div>
                                <h3 className="text-xl font-bold text-[#1d1d1f] tracking-tight mb-2">
                                    {__('landing_company.contact_support_title') || 'Instant WhatsApp Channel'}
                                </h3>
                                <p className="text-[15px] text-[#86868b] leading-relaxed font-medium mb-6">
                                    {__('landing_company.contact_support_desc') || 'Fastest response for project scoping, technical consultations, and urgent production support.'}
                                </p>
                            </div>
                            <div className="space-y-3 pt-6 border-t border-[#d2d2d7]/40">
                                <button
                                    onClick={() => openWhatsApp("Hello Mahmoud, I'm reaching out from the website.")}
                                    className="w-full h-11 rounded-full bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-black text-xs uppercase tracking-wider cursor-pointer flex items-center justify-center gap-2 transition"
                                >
                                    <span>+20 101 521 8548</span>
                                </button>
                            </div>
                        </div>

                        {/* Channel 2: Sales & Formal Inquiries */}
                        <div className="bg-[#f5f5f7] rounded-[28px] p-8 sm:p-10 flex flex-col justify-between h-full border border-[#d2d2d7]/50 shadow-xs">
                            <div>
                                <div className="w-12 h-12 rounded-2xl bg-white border border-[#d2d2d7]/60 flex items-center justify-center mb-6 text-[#1d1d1f]">
                                    <Mail className="w-6 h-6" />
                                </div>
                                <h3 className="text-xl font-bold text-[#1d1d1f] tracking-tight mb-2">
                                    {__('landing_company.contact_sales_title') || 'Official Email'}
                                </h3>
                                <p className="text-[15px] text-[#86868b] leading-relaxed font-medium mb-6">
                                    {__('landing_company.contact_sales_desc') || 'Send RFPs, enterprise scopes, legal inquiries, and custom architecture documentation.'}
                                </p>
                            </div>
                            <div className="pt-6 border-t border-[#d2d2d7]/40">
                                <a
                                    href="mailto:admin@musoftwares.com"
                                    className="w-full h-11 rounded-full bg-white hover:bg-zinc-100 border border-[#d2d2d7] text-zinc-900 font-bold text-xs uppercase tracking-wider cursor-pointer flex items-center justify-center transition"
                                >
                                    <span>admin@musoftwares.com</span>
                                </a>
                            </div>
                        </div>

                        {/* Channel 3: Headquarters & Operations */}
                        <div className="bg-[#f5f5f7] rounded-[28px] p-8 sm:p-10 flex flex-col justify-between h-full border border-[#d2d2d7]/50 shadow-xs">
                            <div>
                                <div className="w-12 h-12 rounded-2xl bg-white border border-[#d2d2d7]/60 flex items-center justify-center mb-6 text-[#1d1d1f]">
                                    <MapPin className="w-6 h-6" />
                                </div>
                                <h3 className="text-xl font-bold text-[#1d1d1f] tracking-tight mb-2">
                                    {__('landing_company.contact_hq_title') || 'Headquarters'}
                                </h3>
                                <p className="text-[15px] text-[#86868b] leading-relaxed font-medium mb-6">
                                    {__('landing_company.contact_hq_desc') || 'Operating from Suez, Egypt with enterprise clients and software deployments across the globe.'}
                                </p>
                            </div>
                            <div className="pt-6 border-t border-[#d2d2d7]/40 flex items-center justify-center text-xs font-semibold text-zinc-700 gap-1.5">
                                <Globe className="w-4 h-4 text-[#86868b]" />
                                <span>{__('landing_company.contact_hq_address') || 'Suez, Egypt — Serving Worldwide'}</span>
                            </div>
                        </div>

                    </div>
                </section>

            </div>
        </PublicLayout>
    );
}

import { useRef } from 'react';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Check } from 'lucide-react';
import PricingBuilder from '@/Components/PricingBuilder';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';
import { __ } from '@/lib/i18n';
import StudioHeader from '@/Components/Studio/StudioHeader';
import { openWhatsAppChat } from '@/lib/whatsapp';

gsap.registerPlugin(ScrollTrigger);

export default function Pricing({ currency = 'USD', serviceItems = [], targetModule = null, targetTool = null, targetPlan = null }) {
    const mainRef = useRef(null);

    useGSAP(() => {
        const sections = gsap.utils.toArray('.reveal-section');
        sections.forEach((section) => {
            const elements = gsap.utils.toArray(section.querySelectorAll('.gsap-fade-up'));
            if (elements && elements.length > 0) {
                gsap.fromTo(elements, 
                    { opacity: 0, y: 20 },
                    {
                        opacity: 1, 
                        y: 0, 
                        duration: 0.6,
                        stagger: 0.08,
                        ease: "power2.out",
                        scrollTrigger: {
                            trigger: section,
                            start: "top 85%",
                            toggleActions: "play none none reverse"
                        }
                    }
                );
            }
        });
    }, { scope: mainRef });

    const openWhatsApp = (msg) => {
        const encodedMessage = encodeURIComponent(msg);
        window.open(`https://wa.me/${phoneNumber}?text=${encodedMessage}`, '_blank');
    };

    return (
        <PublicLayout>
            <Head title={`${__('general.pricing') || 'Pricing'} | Musoftwares Studio`}>
                <meta name="description" content="Transparent pricing for SaaS subscriptions and dedicated engineering blocks." />
            </Head>

            <div ref={mainRef} className="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white pt-16 sm:pt-24 pb-24 sm:pb-36">
                
                {/* Reusable Studio Hero Section */}
                <div className="reveal-section">
                    <StudioHeader
                        badge={__('general.pricing') || 'Transparent Pricing'}
                        title={
                            <>
                                Transparent Investment. <br className="hidden sm:inline" />
                                <span className="text-[#748660]">Zero Hidden Surprises.</span>
                            </>
                        }
                        subtitle={__('general.no_hidden_fees_choose_a_prebuilt_saas_su') || 'Choose from our cloud-ready platforms or book dedicated engineering blocks for bespoke development.'}
                    />

                    <div className="flex flex-col sm:flex-row gap-4 w-full sm:w-auto items-center justify-center -mt-8 mb-24">
                        <button
                            onClick={() => openWhatsAppChat("Hello Mahmoud, I'd like to discuss pricing options.")}
                            className="bg-white text-black hover:bg-zinc-200 px-8 py-3.5 text-xs font-bold font-mono tracking-widest rtl:tracking-normal uppercase transition-all"
                        >
                            {__('general.talk_with_architect') || 'TALK WITH ARCHITECT'}
                        </button>
                        <Link
                            href="/estimator"
                            className="border border-[#333333] hover:border-white text-zinc-300 hover:text-white px-8 py-3.5 text-xs font-bold font-mono tracking-widest rtl:tracking-normal uppercase transition-all"
                        >
                            {__('general.calculate_estimate') || 'CALCULATE ESTIMATE'} ➔
                        </Link>
                    </div>
                </div>

                {/* 1. ENGINEERING BLOCKS & HOURLY MODELS */}
                <section className="px-6 max-w-[1400px] mx-auto mb-24 sm:mb-32 reveal-section">
                    <div className="text-center mb-16">
                        <h2 className="text-2xl sm:text-4xl font-bold text-white mb-3 tracking-tight font-sans">
                            {__('general.engineering_blocks') || 'Engineering Sprints & Retainers'}
                        </h2>
                        <p className="text-sm font-sans text-zinc-400 max-w-2xl mx-auto leading-relaxed">
                            {__('general.for_businesses_that_need_custom_developm') || 'Direct engineering hours with dedicated architecture, code reviews, and rapid delivery.'}
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        {/* Fix & Improve */}
                        <div className="bg-[#161616] border border-[#262626] p-8 sm:p-10 flex flex-col justify-between h-full">
                            <div>
                                <div className="text-xs font-mono font-bold text-zinc-400 mb-6 uppercase tracking-widest rtl:tracking-normal">
                                    {__('general.maintenance_retainer') || 'Fix & Improve'}
                                </div>
                                <div className="mb-4 flex items-baseline gap-2">
                                    <span className="text-4xl sm:text-5xl font-bold font-mono text-white">$45</span>
                                    <span className="text-xs font-mono text-zinc-400">/ 5h block</span>
                                </div>
                                <p className="text-sm text-zinc-300 mb-8 leading-relaxed font-sans">
                                    {__('general.ideal_for_existing_projects_that_need_bu') || 'Ideal for urgent bug fixes, server maintenance, or minor system updates.'}
                                </p>
                                <ul className="space-y-3.5 mb-10 text-xs font-sans">
                                    <li className="flex items-start gap-3 text-zinc-300">
                                        <Check className="w-4 h-4 text-[#748660] shrink-0 mt-0.5" />
                                        <span>{__('general.bug_fixes_and_patches') || 'Codebase debugging & patches'}</span>
                                    </li>
                                    <li className="flex items-start gap-3 text-zinc-300">
                                        <Check className="w-4 h-4 text-[#748660] shrink-0 mt-0.5" />
                                        <span>{__('general.server_monitoring') || 'Server monitoring & health check'}</span>
                                    </li>
                                    <li className="flex items-start gap-3 text-zinc-300">
                                        <Check className="w-4 h-4 text-[#748660] shrink-0 mt-0.5" />
                                        <span>{__('general.security_updates') || 'Security & package updates'}</span>
                                    </li>
                                </ul>
                            </div>
                            <button
                                onClick={() => openWhatsAppChat("Hello Mahmoud, I want to book a 5h Fix & Improve block.")}
                                className="w-full py-3 border border-white text-white hover:bg-white hover:text-black text-xs font-mono font-bold tracking-widest rtl:tracking-normal uppercase transition-colors text-center"
                            >
                                BOOK 5H BLOCK ➔
                            </button>
                        </div>

                        {/* Build New (Signature Sage Green Block) */}
                        <div className="bg-[#1A2215] border-2 border-[#748660] p-8 sm:p-10 flex flex-col justify-between h-full relative">
                            <div className="absolute top-6 end-6 text-[10px] font-mono font-bold uppercase tracking-widest rtl:tracking-normal bg-[#748660] text-black px-2.5 py-0.5">
                                {__('general.most_popular') || 'Recommended'}
                            </div>
                            <div>
                                <div className="text-xs font-mono font-bold text-[#748660] mb-6 uppercase tracking-widest rtl:tracking-normal">
                                    {__('general.custom_build') || 'Feature Sprint'}
                                </div>
                                <div className="mb-4 flex items-baseline gap-2">
                                    <span className="text-4xl sm:text-5xl font-bold font-mono text-white">$110</span>
                                    <span className="text-xs font-mono text-zinc-400">/ 12h sprint</span>
                                </div>
                                <p className="text-sm text-zinc-300 mb-8 leading-relaxed font-sans">
                                    {__('general.for_building_new_mvps_saas_platforms_or') || 'Perfect for developing specific new modules or integrating third-party APIs.'}
                                </p>
                                <ul className="space-y-3.5 mb-10 text-xs font-sans">
                                    <li className="flex items-start gap-3 text-zinc-200">
                                        <Check className="w-4 h-4 text-[#748660] shrink-0 mt-0.5" />
                                        <span>{__('general.fullstack_development') || 'Full-stack feature engineering'}</span>
                                    </li>
                                    <li className="flex items-start gap-3 text-zinc-200">
                                        <Check className="w-4 h-4 text-[#748660] shrink-0 mt-0.5" />
                                        <span>{__('general.database_architecture') || 'Database & API integrations'}</span>
                                    </li>
                                    <li className="flex items-start gap-3 text-zinc-200">
                                        <Check className="w-4 h-4 text-[#748660] shrink-0 mt-0.5" />
                                        <span>Priority architect queue</span>
                                    </li>
                                </ul>
                            </div>
                            <button
                                onClick={() => openWhatsAppChat("Hello Mahmoud, I want to book a 12h Build New sprint.")}
                                className="w-full py-3 bg-[#748660] text-black hover:bg-[#869970] text-xs font-mono font-bold tracking-widest rtl:tracking-normal uppercase transition-colors text-center"
                            >
                                BOOK 12H SPRINT ➔
                            </button>
                        </div>

                        {/* Dedicated Retainer */}
                        <div className="bg-[#161616] border border-[#262626] p-8 sm:p-10 flex flex-col justify-between h-full">
                            <div>
                                <div className="text-xs font-mono font-bold text-zinc-400 mb-6 uppercase tracking-widest rtl:tracking-normal">
                                    Full Architecture Retainer
                                </div>
                                <div className="mb-4 flex items-baseline gap-2">
                                    <span className="text-4xl sm:text-5xl font-bold font-mono text-white">$450</span>
                                    <span className="text-xs font-mono text-zinc-400">/ 50h retainer</span>
                                </div>
                                <p className="text-sm text-zinc-300 mb-8 leading-relaxed font-sans">
                                    Full-cycle dedicated engineering team supporting ongoing scale, features, and security.
                                </p>
                                <ul className="space-y-3.5 mb-10 text-xs font-sans">
                                    <li className="flex items-start gap-3 text-zinc-300">
                                        <Check className="w-4 h-4 text-[#748660] shrink-0 mt-0.5" />
                                        <span>Continuous deployment & CI/CD</span>
                                    </li>
                                    <li className="flex items-start gap-3 text-zinc-300">
                                        <Check className="w-4 h-4 text-[#748660] shrink-0 mt-0.5" />
                                        <span>Direct Slack / WhatsApp channel</span>
                                    </li>
                                    <li className="flex items-start gap-3 text-zinc-300">
                                        <Check className="w-4 h-4 text-[#748660] shrink-0 mt-0.5" />
                                        <span>24/7 emergency incident coverage</span>
                                    </li>
                                </ul>
                            </div>
                            <button
                                onClick={() => openWhatsAppChat("Hello Mahmoud, I want to book a 50h Dedicated Retainer.")}
                                className="w-full py-3 border border-white text-white hover:bg-white hover:text-black text-xs font-mono font-bold tracking-widest rtl:tracking-normal uppercase transition-colors text-center"
                            >
                                BOOK 50H RETAINER ➔
                            </button>
                        </div>
                    </div>
                </section>

                {/* 2. DYNAMIC PRICING CALCULATOR */}
                <section className="px-6 max-w-[1400px] mx-auto reveal-section border-t border-[#222222] pt-20">
                    <PricingBuilder 
                        currency={currency} 
                        serviceItems={serviceItems} 
                        targetModule={targetModule} 
                        targetTool={targetTool} 
                        targetPlan={targetPlan} 
                    />
                </section>

            </div>
        </PublicLayout>
    );
}

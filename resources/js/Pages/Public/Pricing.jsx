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

            <div ref={mainRef} className="w-full bg-[#ffffff] text-[#1d1d1f] font-sans selection:bg-[#0071e3]/20 selection:text-[#0071e3] pt-12 sm:pt-20 pb-24 sm:pb-36">
                
                {/* Reusable Studio Hero Section */}
                <div className="reveal-section">
                    <StudioHeader
                        badge={__('general.pricing') || 'Transparent Pricing'}
                        title={
                            <>
                                Transparent Investment. <br className="hidden sm:inline" />
                                <span className="text-[#0071e3]">Zero Hidden Surprises.</span>
                            </>
                        }
                        subtitle={__('general.no_hidden_fees_choose_a_prebuilt_saas_su') || 'Choose from our cloud-ready platforms or book dedicated engineering blocks for bespoke development.'}
                    />

                    <div className="flex flex-col sm:flex-row gap-4 w-full sm:w-auto items-center justify-center -mt-8 mb-20">
                        <button
                            onClick={() => openWhatsAppChat("Hello Mahmoud, I'd like to discuss pricing options.")}
                            className="bg-[#0071e3] hover:bg-[#0077ed] text-white px-8 py-3 rounded-[980px] text-xs font-semibold tracking-wide transition-all shadow-md shadow-blue-500/20 cursor-pointer"
                        >
                            {__('general.talk_with_architect') || 'TALK WITH ARCHITECT'}
                        </button>
                        <Link
                            href="/estimator"
                            className="border border-black/10 hover:border-black/30 bg-white text-[#1d1d1f] hover:bg-[#f5f5f7] px-8 py-3 rounded-[980px] text-xs font-semibold tracking-wide transition-all shadow-sm"
                        >
                            {__('general.calculate_estimate') || 'CALCULATE ESTIMATE'} ➔
                        </Link>
                    </div>
                </div>

                {/* 1. ENGINEERING BLOCKS & HOURLY MODELS */}
                <section className="px-6 max-w-[1400px] mx-auto mb-20 sm:mb-28 reveal-section">
                    <div className="text-center mb-14">
                        <h2 className="text-2xl sm:text-4xl font-semibold text-[#1d1d1f] mb-3 tracking-tight font-sans">
                            {__('general.engineering_blocks') || 'Engineering Sprints & Retainers'}
                        </h2>
                        <p className="text-sm font-sans text-[#1d1d1f]/60 max-w-2xl mx-auto leading-relaxed">
                            {__('general.for_businesses_that_need_custom_developm') || 'Direct engineering hours with dedicated architecture, code reviews, and rapid delivery.'}
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        {/* Fix & Improve */}
                        <div className="bg-white border border-black/5 rounded-[24px] p-8 sm:p-10 flex flex-col justify-between h-full shadow-sm hover:shadow-md transition-all">
                            <div>
                                <div className="text-xs font-semibold text-[#1d1d1f]/60 mb-6 uppercase tracking-wider">
                                    {__('general.maintenance_retainer') || 'Fix & Improve'}
                                </div>
                                <div className="mb-4 flex items-baseline gap-2">
                                    <span className="text-4xl sm:text-5xl font-semibold text-[#1d1d1f]">$45</span>
                                    <span className="text-xs text-[#1d1d1f]/50">/ 5h block</span>
                                </div>
                                <p className="text-sm text-[#1d1d1f]/70 mb-8 leading-relaxed font-sans">
                                    {__('general.ideal_for_existing_projects_that_need_bu') || 'Ideal for urgent bug fixes, server maintenance, or minor system updates.'}
                                </p>
                                <ul className="space-y-3.5 mb-10 text-xs font-sans">
                                    <li className="flex items-start gap-3 text-[#1d1d1f]/80">
                                        <Check className="w-4 h-4 text-[#0071e3] shrink-0 mt-0.5" />
                                        <span>{__('general.bug_fixes_and_patches') || 'Codebase debugging & patches'}</span>
                                    </li>
                                    <li className="flex items-start gap-3 text-[#1d1d1f]/80">
                                        <Check className="w-4 h-4 text-[#0071e3] shrink-0 mt-0.5" />
                                        <span>{__('general.server_monitoring') || 'Server monitoring & health check'}</span>
                                    </li>
                                    <li className="flex items-start gap-3 text-[#1d1d1f]/80">
                                        <Check className="w-4 h-4 text-[#0071e3] shrink-0 mt-0.5" />
                                        <span>{__('general.security_updates') || 'Security & package updates'}</span>
                                    </li>
                                </ul>
                            </div>
                            <button
                                onClick={() => openWhatsAppChat("Hello Mahmoud, I want to book a 5h Fix & Improve block.")}
                                className="w-full py-3 rounded-[980px] border border-black/10 text-[#1d1d1f] hover:bg-[#f5f5f7] text-xs font-semibold tracking-wide uppercase transition-colors text-center cursor-pointer shadow-sm"
                            >
                                BOOK 5H BLOCK ➔
                            </button>
                        </div>

                        {/* Build New (Signature Apple Blue Block) */}
                        <div className="bg-[#0071e3]/5 border-2 border-[#0071e3] rounded-[24px] p-8 sm:p-10 flex flex-col justify-between h-full relative shadow-[0_12px_30px_rgba(0,113,227,0.12)]">
                            <div className="absolute top-6 end-6 text-[10px] font-semibold uppercase tracking-wider bg-[#0071e3] text-white px-3 py-1 rounded-full shadow-xs">
                                {__('general.most_popular') || 'Recommended'}
                            </div>
                            <div>
                                <div className="text-xs font-semibold text-[#0071e3] mb-6 uppercase tracking-wider">
                                    {__('general.custom_build') || 'Feature Sprint'}
                                </div>
                                <div className="mb-4 flex items-baseline gap-2">
                                    <span className="text-4xl sm:text-5xl font-semibold text-[#1d1d1f]">$110</span>
                                    <span className="text-xs text-[#1d1d1f]/50">/ 12h sprint</span>
                                </div>
                                <p className="text-sm text-[#1d1d1f]/70 mb-8 leading-relaxed font-sans">
                                    {__('general.for_building_new_mvps_saas_platforms_or') || 'Perfect for developing specific new modules or integrating third-party APIs.'}
                                </p>
                                <ul className="space-y-3.5 mb-10 text-xs font-sans">
                                    <li className="flex items-start gap-3 text-[#1d1d1f]/80">
                                        <Check className="w-4 h-4 text-[#0071e3] shrink-0 mt-0.5" />
                                        <span>{__('general.fullstack_development') || 'Full-stack feature engineering'}</span>
                                    </li>
                                    <li className="flex items-start gap-3 text-[#1d1d1f]/80">
                                        <Check className="w-4 h-4 text-[#0071e3] shrink-0 mt-0.5" />
                                        <span>{__('general.database_architecture') || 'Database & API integrations'}</span>
                                    </li>
                                    <li className="flex items-start gap-3 text-[#1d1d1f]/80">
                                        <Check className="w-4 h-4 text-[#0071e3] shrink-0 mt-0.5" />
                                        <span>Priority architect queue</span>
                                    </li>
                                </ul>
                            </div>
                            <button
                                onClick={() => openWhatsAppChat("Hello Mahmoud, I want to book a 12h Build New sprint.")}
                                className="w-full py-3 rounded-[980px] bg-[#0071e3] text-white hover:bg-[#0077ed] text-xs font-semibold tracking-wide uppercase transition-colors text-center cursor-pointer shadow-md shadow-blue-500/20"
                            >
                                BOOK 12H SPRINT ➔
                            </button>
                        </div>

                        {/* Dedicated Retainer */}
                        <div className="bg-white border border-black/5 rounded-[24px] p-8 sm:p-10 flex flex-col justify-between h-full shadow-sm hover:shadow-md transition-all">
                            <div>
                                <div className="text-xs font-semibold text-[#1d1d1f]/60 mb-6 uppercase tracking-wider">
                                    Full Architecture Retainer
                                </div>
                                <div className="mb-4 flex items-baseline gap-2">
                                    <span className="text-4xl sm:text-5xl font-semibold text-[#1d1d1f]">$450</span>
                                    <span className="text-xs text-[#1d1d1f]/50">/ 50h retainer</span>
                                </div>
                                <p className="text-sm text-[#1d1d1f]/70 mb-8 leading-relaxed font-sans">
                                    Full-cycle dedicated engineering team supporting ongoing scale, features, and security.
                                </p>
                                <ul className="space-y-3.5 mb-10 text-xs font-sans">
                                    <li className="flex items-start gap-3 text-[#1d1d1f]/80">
                                        <Check className="w-4 h-4 text-[#0071e3] shrink-0 mt-0.5" />
                                        <span>Continuous deployment & CI/CD</span>
                                    </li>
                                    <li className="flex items-start gap-3 text-[#1d1d1f]/80">
                                        <Check className="w-4 h-4 text-[#0071e3] shrink-0 mt-0.5" />
                                        <span>Direct Slack / WhatsApp channel</span>
                                    </li>
                                    <li className="flex items-start gap-3 text-[#1d1d1f]/80">
                                        <Check className="w-4 h-4 text-[#0071e3] shrink-0 mt-0.5" />
                                        <span>24/7 emergency incident coverage</span>
                                    </li>
                                </ul>
                            </div>
                            <button
                                onClick={() => openWhatsAppChat("Hello Mahmoud, I want to book a 50h Dedicated Retainer.")}
                                className="w-full py-3 rounded-[980px] border border-black/10 text-[#1d1d1f] hover:bg-[#f5f5f7] text-xs font-semibold tracking-wide uppercase transition-colors text-center cursor-pointer shadow-sm"
                            >
                                BOOK 50H RETAINER ➔
                            </button>
                        </div>
                    </div>
                </section>

                {/* 2. DYNAMIC PRICING CALCULATOR */}
                <section className="px-6 max-w-[1400px] mx-auto reveal-section border-t border-black/5 pt-16">
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

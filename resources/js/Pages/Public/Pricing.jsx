import { useRef } from 'react';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Check, ChevronRight, MessageSquare, Sparkles, Calculator, ShieldCheck } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import PricingBuilder from '@/Components/PricingBuilder';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';
import { __ } from '@/lib/i18n';

gsap.registerPlugin(ScrollTrigger);

export default function Pricing({ currency = 'USD', serviceItems = [], targetModule = null, targetTool = null, targetPlan = null }) {
    const mainRef = useRef(null);
    const phoneNumber = "201015218548";

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
            <Head title={`${__('general.pricing') || 'Pricing'} | Musoftware`}>
                <meta name="description" content="Transparent pricing for SaaS subscriptions and dedicated engineering blocks." />
            </Head>

            <div ref={mainRef} className="w-full bg-white text-[#1d1d1f] font-sans selection:bg-[#1d1d1f] selection:text-white pt-12 sm:pt-20 pb-20 sm:pb-32">
                
                {/* Hero Section */}
                <section className="px-6 max-w-5xl mx-auto flex flex-col items-center text-center mb-20 sm:mb-28 reveal-section">
                    <p className="text-base sm:text-xl text-[#86868b] font-medium mb-3 sm:mb-4 tracking-tight">
                        {__('general.pricing') || 'Transparent Pricing'}
                    </p>

                    <h1 className="text-4xl sm:text-6xl md:text-7xl lg:text-[86px] leading-[1.05] font-bold text-[#1d1d1f] max-w-5xl mb-6 tracking-tight">
                        {__('general.transparent_engineering_costs') || 'Transparent Investment.'} <br className="hidden sm:inline" />
                        <span className="bg-gradient-to-r from-[#0066cc] to-[#3399ff] bg-clip-text text-transparent">
                            Zero Hidden Costs.
                        </span>
                    </h1>

                    <p className="text-lg sm:text-2xl md:text-[26px] text-[#86868b] max-w-3xl mb-10 sm:mb-12 font-medium leading-snug tracking-tight">
                        {__('general.no_hidden_fees_choose_a_prebuilt_saas_su') || 'Choose from our cloud-ready platforms or book dedicated engineering blocks for custom development.'}
                    </p>

                    <div className="flex flex-col sm:flex-row gap-4 sm:gap-6 w-full sm:w-auto items-center justify-center">
                        <button
                            onClick={() => openWhatsApp("Hello Mahmoud, I'd like to discuss pricing options.")}
                            className="bg-[#1d1d1f] hover:bg-[#333336] text-white px-8 py-3.5 rounded-full text-[17px] font-semibold w-full sm:w-auto transition-all cursor-pointer shadow-sm hover:scale-[1.02] active:scale-95"
                        >
                            Talk with Architect
                        </button>
                        <Link
                            href="/estimator"
                            className="text-[#0066cc] hover:text-[#0077ed] text-[17px] font-semibold flex items-center gap-1 group transition-colors cursor-pointer"
                        >
                            <span>Calculate Your Estimate</span>
                            <ChevronRight className="w-4 h-4 group-hover:translate-x-1 transition-transform rtl:rotate-180" />
                        </Link>
                    </div>
                </section>

                {/* 1. ENGINEERING BLOCKS & HOURLY MODELS (Apple Card Grid) */}
                <section className="px-6 max-w-7xl mx-auto mb-24 sm:mb-32 reveal-section">
                    <div className="text-center mb-16 sm:mb-20">
                        <h2 className="text-3xl sm:text-5xl font-bold text-[#1d1d1f] mb-4 tracking-tight leading-tight">
                            {__('general.engineering_blocks') || 'Engineering Sprints & Blocks'}
                        </h2>
                        <p className="text-base sm:text-xl text-[#86868b] max-w-2xl mx-auto font-medium tracking-tight">
                            {__('general.for_businesses_that_need_custom_developm') || 'Direct engineering hours with dedicated architecture, code reviews, and rapid delivery.'}
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        {/* Fix & Improve */}
                        <div className="bg-[#f5f5f7] rounded-[28px] p-8 sm:p-10 flex flex-col justify-between h-full">
                            <div>
                                <div className="text-[13px] font-semibold text-[#86868b] mb-6 uppercase tracking-wider">
                                    {__('general.maintenance_retainer') || 'Fix & Improve'}
                                </div>
                                <div className="mb-4 flex items-baseline gap-2">
                                    <span className="text-[44px] sm:text-[48px] font-bold text-[#1d1d1f] tracking-tight">$45</span>
                                    <span className="text-[17px] text-[#86868b] font-medium">/ 5h block</span>
                                </div>
                                <p className="text-[15px] text-[#1d1d1f] mb-8 leading-relaxed font-medium">
                                    {__('general.ideal_for_existing_projects_that_need_bu') || 'Ideal for urgent bug fixes, server maintenance, or minor system updates.'}
                                </p>
                                <ul className="space-y-3.5 mb-10">
                                    <li className="flex items-start gap-3">
                                        <Check className="w-4 h-4 text-[#86868b] shrink-0 mt-1" strokeWidth={2.5} />
                                        <span className="text-[15px] text-[#86868b] font-medium">{__('general.bug_fixes_and_patches') || 'Codebase debugging & patches'}</span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Check className="w-4 h-4 text-[#86868b] shrink-0 mt-1" strokeWidth={2.5} />
                                        <span className="text-[15px] text-[#86868b] font-medium">{__('general.server_monitoring') || 'Server monitoring & health check'}</span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Check className="w-4 h-4 text-[#86868b] shrink-0 mt-1" strokeWidth={2.5} />
                                        <span className="text-[15px] text-[#86868b] font-medium">{__('general.security_updates') || 'Security & package updates'}</span>
                                    </li>
                                </ul>
                            </div>
                            <button
                                onClick={() => openWhatsApp("Hello Mahmoud, I want to book a 5h Fix & Improve block.")}
                                className="text-[#0066cc] hover:text-[#0077ed] text-[17px] font-semibold flex items-center gap-1 group transition-colors cursor-pointer justify-center"
                            >
                                <span>Book 5h Block</span>
                                <ChevronRight className="w-4 h-4 group-hover:translate-x-1 transition-transform rtl:rotate-180" strokeWidth={2.5} />
                            </button>
                        </div>

                        {/* Build New (Most Popular) */}
                        <div className="bg-[#f5f5f7] rounded-[28px] p-8 sm:p-10 flex flex-col justify-between h-full relative border border-[#0066cc]/20 shadow-xs">
                            <div className="absolute top-8 end-8 text-[11px] sm:text-[12px] font-bold text-[#0066cc] uppercase tracking-wider bg-blue-50 px-2.5 py-1 rounded-full border border-blue-200/60">
                                {__('general.most_popular') || 'Most Popular'}
                            </div>
                            <div>
                                <div className="text-[13px] font-semibold text-[#1d1d1f] mb-6 uppercase tracking-wider">
                                    {__('general.custom_build') || 'Build New Feature'}
                                </div>
                                <div className="mb-4 flex items-baseline gap-2">
                                    <span className="text-[44px] sm:text-[48px] font-bold text-[#1d1d1f] tracking-tight">$110</span>
                                    <span className="text-[17px] text-[#86868b] font-medium">/ 12h block</span>
                                </div>
                                <p className="text-[15px] text-[#1d1d1f] mb-8 leading-relaxed font-medium">
                                    {__('general.for_building_new_mvps_saas_platforms_or') || 'Perfect for developing specific new modules or integrating third-party APIs.'}
                                </p>
                                <ul className="space-y-3.5 mb-10">
                                    <li className="flex items-start gap-3">
                                        <Check className="w-4 h-4 text-[#86868b] shrink-0 mt-1" strokeWidth={2.5} />
                                        <span className="text-[15px] text-[#86868b] font-medium">{__('general.fullstack_development') || 'Full-stack feature engineering'}</span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Check className="w-4 h-4 text-[#86868b] shrink-0 mt-1" strokeWidth={2.5} />
                                        <span className="text-[15px] text-[#86868b] font-medium">{__('general.database_architecture') || 'Database & API integrations'}</span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Check className="w-4 h-4 text-[#86868b] shrink-0 mt-1" strokeWidth={2.5} />
                                        <span className="text-[15px] text-[#86868b] font-medium">Priority engineering queue</span>
                                    </li>
                                </ul>
                            </div>
                            <button
                                onClick={() => openWhatsApp("Hello Mahmoud, I want to book a 12h Build New sprint.")}
                                className="text-[#0066cc] hover:text-[#0077ed] text-[17px] font-semibold flex items-center gap-1 group transition-colors cursor-pointer justify-center"
                            >
                                <span>Book 12h Sprint</span>
                                <ChevronRight className="w-4 h-4 group-hover:translate-x-1 transition-transform rtl:rotate-180" strokeWidth={2.5} />
                            </button>
                        </div>

                        {/* Growth Partner / Custom Scope */}
                        <div className="bg-[#f5f5f7] rounded-[28px] p-8 sm:p-10 flex flex-col justify-between h-full">
                            <div>
                                <div className="text-[13px] font-semibold text-[#86868b] mb-6 uppercase tracking-wider">
                                    {__('general.growth_partner') || 'Full Platform'}
                                </div>
                                <div className="mb-4 h-[56px] flex items-end">
                                    <span className="text-[28px] sm:text-[32px] font-bold text-[#1d1d1f] tracking-tight leading-none pb-1">Custom Scope</span>
                                </div>
                                <p className="text-[15px] text-[#1d1d1f] mb-8 leading-relaxed font-medium">
                                    {__('general.for_scaling_companies_that_need_an_exter') || 'Complete architecture, end-to-end MVP execution, or dedicated team partnership.'}
                                </p>
                                <ul className="space-y-3.5 mb-10">
                                    <li className="flex items-start gap-3">
                                        <Check className="w-4 h-4 text-[#86868b] shrink-0 mt-1" strokeWidth={2.5} />
                                        <span className="text-[15px] text-[#86868b] font-medium">{__('general.technical_leadership') || 'Direct software architect leadership'}</span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Check className="w-4 h-4 text-[#86868b] shrink-0 mt-1" strokeWidth={2.5} />
                                        <span className="text-[15px] text-[#86868b] font-medium">{__('general.code_reviews_audits') || 'Full architecture & system design'}</span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Check className="w-4 h-4 text-[#86868b] shrink-0 mt-1" strokeWidth={2.5} />
                                        <span className="text-[15px] text-[#86868b] font-medium">Long-term SLA and deployment support</span>
                                    </li>
                                </ul>
                            </div>
                            <Link
                                href="/estimator"
                                className="text-[#0066cc] hover:text-[#0077ed] text-[17px] font-semibold flex items-center gap-1 group transition-colors cursor-pointer justify-center"
                            >
                                <span>Get Custom Quote</span>
                                <ChevronRight className="w-4 h-4 group-hover:translate-x-1 transition-transform rtl:rotate-180" strokeWidth={2.5} />
                            </Link>
                        </div>
                    </div>
                </section>

                {/* 2. SAAS SUBSCRIPTIONS & PLATFORMS BUILDER */}
                <section className="px-6 max-w-7xl mx-auto pt-16 border-t border-[#d2d2d7]/50 reveal-section">
                    <div className="text-center max-w-3xl mx-auto mb-16">
                        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#f5f5f7] border border-[#d2d2d7]/60 text-[#86868b] text-xs font-semibold mb-3 uppercase tracking-wider">
                            <span>SaaS &amp; Cloud Modules</span>
                        </div>
                        <h2 className="text-3xl sm:text-5xl font-bold text-[#1d1d1f] mb-4 tracking-tight leading-tight">
                            Ready Cloud Subscriptions
                        </h2>
                        <p className="text-[#86868b] text-base sm:text-lg">
                            {__('general.readytouse_platforms_built_by_us_subscri') || 'Deploy and run your business operations with our dedicated cloud-ready platforms.'}
                        </p>
                    </div>

                    <div className="max-w-7xl mx-auto">
                        <PricingBuilder 
                            serviceItems={serviceItems} 
                            currency={currency} 
                            isNewSystem={true} 
                            targetModule={targetModule}
                            targetTool={targetTool}
                            targetPlan={targetPlan}
                        />
                    </div>
                </section>

            </div>
        </PublicLayout>
    );
}

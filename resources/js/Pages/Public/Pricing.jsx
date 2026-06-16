import { useRef } from 'react';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Check, Info, ArrowRight } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { __ } from '@/lib/i18n';
import PricingBuilder from '@/Components/PricingBuilder';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';

gsap.registerPlugin(ScrollTrigger);

export default function Pricing({ currency = 'USD', serviceItems = [] }) {
    const mainRef = useRef(null);

    useGSAP(() => {
        const sections = gsap.utils.toArray('.reveal-section');
        sections.forEach((section) => {
            const elements = section.querySelectorAll('.gsap-fade-up');
            gsap.fromTo(elements, 
                { opacity: 0, y: 20 },
                {
                    opacity: 1, 
                    y: 0, 
                    duration: 0.7,
                    stagger: 0.08,
                    ease: "power2.out",
                    scrollTrigger: {
                        trigger: section,
                        start: "top 85%",
                        toggleActions: "play none none reverse"
                    }
                }
            );
        });

        document.documentElement.style.scrollSnapType = 'y proximity';
        document.documentElement.style.overflowY = 'scroll';
        
        return () => {
            document.documentElement.style.scrollSnapType = '';
            document.documentElement.style.overflowY = '';
        };
    }, { scope: mainRef });

    return (
        <PublicLayout>
            <Head>
                <title>{__('frontend.pricing.meta_title')}</title>
                <meta name="description" content={__('frontend.pricing.meta_description')} />
            </Head>

            <style>{`
                .snap-section { scroll-snap-align: start; }
                html { scroll-behavior: smooth; }
            `}</style>

            <div ref={mainRef} className="w-full bg-[#fafafa] text-[#111111] font-sans selection:bg-[#111111] selection:text-white overflow-x-hidden">
                
                {/* Hero Section */}
                <section className="snap-section pt-32 pb-16 lg:pt-48 lg:pb-24 bg-[#fafafa] border-b border-[#e5e5e5] reveal-section">
                    <div className="max-w-[80rem] mx-auto px-6 lg:px-8">
                        <div className="text-center max-w-3xl mx-auto mb-20">
                            <div className="gsap-fade-up inline-flex items-center gap-2 px-3 py-1 border border-[#e5e5e5] text-xs font-semibold text-[#666666] tracking-widest uppercase mb-8 bg-white mx-auto">
                                <span className="flex h-1.5 w-1.5 rounded-full bg-[#111111]"></span>
                                Pricing
                            </div>
                            <h1 className="gsap-fade-up text-5xl lg:text-7xl font-bold text-[#111111] tracking-tight leading-[1.05] mb-6">
                                {__('frontend.pricing.title')}
                            </h1>
                            <p className="gsap-fade-up text-xl text-[#666666] font-normal leading-relaxed">
                                {__('frontend.pricing.subtitle')}
                            </p>
                        </div>

                        {/* SaaS Subscriptions Section */}
                        <div className="mb-12 gsap-fade-up">
                            <h2 className="text-2xl font-bold text-[#111111] mb-4 text-center">{__('frontend.pricing.saas_plans')}</h2>
                            <p className="text-[#666666] text-center mb-12">{__('frontend.pricing.saas_subtitle')}</p>
                            
                            <div className="max-w-7xl mx-auto">
                                {/* Note: PricingBuilder is an external component, its internal styling may need updates separately to perfectly match. We wrap it for now. */}
                                <PricingBuilder 
                                    serviceItems={serviceItems} 
                                    currency={currency} 
                                    isNewSystem={true} 
                                />
                            </div>
                        </div>
                    </div>
                </section>

                <section className="snap-section py-24 lg:py-32 bg-white reveal-section border-b border-[#e5e5e5]">
                    <div className="max-w-[80rem] mx-auto px-6 lg:px-8">
                        <div className="text-center max-w-3xl mx-auto mb-16">
                            <h2 className="gsap-fade-up text-3xl font-bold text-[#111111] mb-4">{__('frontend.pricing.engineering_blocks')}</h2>
                            <p className="gsap-fade-up text-[#666666]">{__('frontend.pricing.blocks_subtitle')}</p>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-px bg-[#e5e5e5] border border-[#e5e5e5] max-w-6xl mx-auto">
                            
                            {/* Maintenance Block */}
                            <div className="gsap-fade-up flex flex-col p-10 bg-[#fafafa] transition-colors hover:bg-white relative">
                                <div className="mb-8">
                                    <h3 className="text-xl font-bold text-[#111111] mb-2">{__('frontend.pricing.maintenance.title')}</h3>
                                    <p className="text-[#666666] text-sm leading-relaxed mb-6">{__('frontend.pricing.maintenance.desc')}</p>
                                    <div className="flex items-baseline gap-2">
                                        <span className="text-4xl font-bold text-[#111111]">{__('frontend.pricing.maintenance.hrs')}</span>
                                    </div>
                                </div>
                                <div className="flex-1">
                                    <ul className="space-y-4 mb-8">
                                        <li className="flex gap-3 text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full shrink-0 mt-1.5"></div> {__('frontend.pricing.maintenance.f1')}</li>
                                        <li className="flex gap-3 text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full shrink-0 mt-1.5"></div> {__('frontend.pricing.maintenance.f2')}</li>
                                        <li className="flex gap-3 text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full shrink-0 mt-1.5"></div> {__('frontend.pricing.maintenance.f3')}</li>
                                    </ul>
                                </div>
                                <button className="w-full bg-white hover:bg-[#111111] text-[#111111] hover:text-white border border-[#111111] h-12 text-sm font-bold uppercase tracking-widest transition-colors">
                                    {__('frontend.pricing.maintenance.cta')}
                                </button>
                            </div>

                            {/* Implementation Block (Most Popular) */}
                            <div className="gsap-fade-up flex flex-col p-10 bg-[#111111] text-white relative outline outline-1 outline-[#111111] z-10 scale-[1.02]">
                                <div className="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2">
                                    <span className="bg-white text-[#111111] text-[10px] font-bold uppercase tracking-widest py-1 px-3 border border-[#111111]">
                                        {__('frontend.pricing.implementation.badge')}
                                    </span>
                                </div>
                                <div className="mb-8 mt-2">
                                    <h3 className="text-xl font-bold text-white mb-2">{__('frontend.pricing.implementation.title')}</h3>
                                    <p className="text-[#a0a0a0] text-sm leading-relaxed mb-6">{__('frontend.pricing.implementation.desc')}</p>
                                    <div className="flex items-baseline gap-2">
                                        <span className="text-4xl font-bold text-white">{__('frontend.pricing.implementation.hrs')}</span>
                                    </div>
                                </div>
                                <div className="flex-1">
                                    <ul className="space-y-4 mb-8">
                                        <li className="flex gap-3 text-white text-sm font-semibold"><div className="w-1.5 h-1.5 bg-white rounded-full shrink-0 mt-1.5"></div> {__('frontend.pricing.implementation.f1')}</li>
                                        <li className="flex gap-3 text-white text-sm font-semibold"><div className="w-1.5 h-1.5 bg-white rounded-full shrink-0 mt-1.5"></div> {__('frontend.pricing.implementation.f2')}</li>
                                        <li className="flex gap-3 text-white text-sm font-semibold"><div className="w-1.5 h-1.5 bg-white rounded-full shrink-0 mt-1.5"></div> {__('frontend.pricing.implementation.f3')}</li>
                                    </ul>
                                </div>
                                <button className="w-full bg-white hover:bg-[#e5e5e5] text-[#111111] h-12 text-sm font-bold uppercase tracking-widest transition-colors">
                                    {__('frontend.pricing.implementation.cta')}
                                </button>
                            </div>

                            {/* Growth Partner */}
                            <div className="gsap-fade-up flex flex-col p-10 bg-[#fafafa] transition-colors hover:bg-white relative">
                                <div className="mb-8">
                                    <h3 className="text-xl font-bold text-[#111111] mb-2">{__('frontend.pricing.growth.title')}</h3>
                                    <p className="text-[#666666] text-sm leading-relaxed mb-6">{__('frontend.pricing.growth.desc')}</p>
                                    <div className="flex items-baseline gap-2">
                                        <span className="text-4xl font-bold text-[#111111]">{__('frontend.pricing.growth.hrs')}</span>
                                    </div>
                                </div>
                                <div className="flex-1">
                                    <ul className="space-y-4 mb-8">
                                        <li className="flex gap-3 text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full shrink-0 mt-1.5"></div> {__('frontend.pricing.growth.f1')}</li>
                                        <li className="flex gap-3 text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full shrink-0 mt-1.5"></div> {__('frontend.pricing.growth.f2')}</li>
                                        <li className="flex gap-3 text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full shrink-0 mt-1.5"></div> {__('frontend.pricing.growth.f3')}</li>
                                    </ul>
                                </div>
                                <button className="w-full bg-white hover:bg-[#111111] text-[#111111] hover:text-white border border-[#111111] h-12 text-sm font-bold uppercase tracking-widest transition-colors">
                                    {__('frontend.pricing.growth.cta')}
                                </button>
                            </div>

                        </div>

                        {/* Disclaimer */}
                        <div className="gsap-fade-up mt-16 mb-24 max-w-2xl mx-auto flex items-start sm:items-center gap-4 p-6 bg-[#fafafa] border border-[#e5e5e5]">
                            <Info className="w-5 h-5 text-[#111111] shrink-0 mt-0.5 sm:mt-0" />
                            <p className="text-sm text-[#666666] leading-relaxed" dangerouslySetInnerHTML={{ __html: __('frontend.pricing.disclaimer') }}></p>
                        </div>

                        {/* FAQ Section */}
                        <div className="max-w-4xl mx-auto pt-10">
                            <h2 className="gsap-fade-up text-xs font-bold text-[#888888] tracking-widest uppercase mb-12 text-center">{__('frontend.pricing.faq.title')}</h2>
                            
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-x-16 gap-y-12">
                                <div className="gsap-fade-up">
                                    <h4 className="text-sm font-bold text-[#111111] mb-3">{__('frontend.pricing.faq.q1')}</h4>
                                    <p className="text-[#666666] text-sm leading-relaxed">{__('frontend.pricing.faq.a1')}</p>
                                </div>
                                
                                <div className="gsap-fade-up">
                                    <h4 className="text-sm font-bold text-[#111111] mb-3">{__('frontend.pricing.faq.q2')}</h4>
                                    <p className="text-[#666666] text-sm leading-relaxed">{__('frontend.pricing.faq.a2')}</p>
                                </div>
                                
                                <div className="gsap-fade-up">
                                    <h4 className="text-sm font-bold text-[#111111] mb-3">{__('frontend.pricing.faq.q3')}</h4>
                                    <p className="text-[#666666] text-sm leading-relaxed">{__('frontend.pricing.faq.a3')}</p>
                                </div>
                                
                                <div className="gsap-fade-up">
                                    <h4 className="text-sm font-bold text-[#111111] mb-3">{__('frontend.pricing.faq.q4')}</h4>
                                    <p className="text-[#666666] text-sm leading-relaxed">{__('frontend.pricing.faq.a4')}</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}

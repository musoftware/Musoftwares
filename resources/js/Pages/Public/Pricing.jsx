import { useRef } from 'react';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Check, Info, ArrowRight } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import PricingBuilder from '@/Components/PricingBuilder';
import FloatingWhatsAppButton from '@/Components/FloatingWhatsAppButton';
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
            const elements = section.querySelectorAll('.gsap-fade-up');
            gsap.fromTo(elements, 
                { opacity: 0, y: 30 },
                {
                    opacity: 1, 
                    y: 0, 
                    duration: 0.8,
                    stagger: 0.1,
                    ease: "power2.out",
                    scrollTrigger: {
                        trigger: section,
                        start: "top 85%",
                        toggleActions: "play none none reverse"
                    }
                }
            );
        });
    }, { scope: mainRef });

    const openWhatsApp = (msg) => {
        const encodedMessage = encodeURIComponent(msg);
        window.open(`https://wa.me/${phoneNumber}?text=${encodedMessage}`, '_blank');
    };

    return (
        <PublicLayout>
            <Head>
                <title>Pricing | Musoftware</title>
                <meta name="description" content="Transparent pricing for SaaS subscriptions and dedicated engineering blocks." />
            </Head>

            <FloatingWhatsAppButton phoneNumber={phoneNumber} defaultMessage="Hello Mahmoud, I have a question about pricing." />

            <div ref={mainRef} className="w-full bg-[#fcfcfc] text-[#111111] font-sans selection:bg-[#111111] selection:text-white overflow-x-hidden">
                
                {/* Hero Section */}
                <section className="pt-32 pb-16 lg:pt-48 lg:pb-24 px-6 lg:px-8 max-w-7xl mx-auto reveal-section border-b border-[#e5e5e5]">
                    <div className="text-center max-w-4xl mx-auto mb-20">
                        <div className="gsap-fade-up inline-flex items-center gap-2 px-3 py-1 border border-[#e5e5e5] text-xs font-semibold text-[#666666] tracking-widest uppercase mb-8 bg-white mx-auto">
                            <span className="flex h-1.5 w-1.5 rounded-full bg-[#111111]"></span>
                            {__('general.pricing')}</div>
                        <h1 className="gsap-fade-up text-5xl lg:text-7xl font-extrabold text-[#111111] tracking-tight leading-[1.05] mb-6">
                            {__('general.transparent_engineering_costs')}</h1>
                        <p className="gsap-fade-up text-xl text-[#666666] font-normal leading-relaxed">
                            {__('general.no_hidden_fees_choose_a_prebuilt_saas_su')}</p>
                    </div>

                    {/* SaaS Subscriptions Section */}
                    <div className="mb-12 gsap-fade-up">
                        <h2 className="text-3xl font-extrabold text-[#111111] mb-4 text-center">Software Subscriptions (SaaS)</h2>
                        <p className="text-[#666666] text-lg text-center mb-16 max-w-2xl mx-auto">
                            {__('general.readytouse_platforms_built_by_us_subscri')}</p>
                        
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
                    </div>
                </section>

                <section className="py-24 lg:py-32 px-6 lg:px-8 bg-white reveal-section border-b border-[#e5e5e5]">
                    <div className="text-center max-w-3xl mx-auto mb-16">
                        <h2 className="gsap-fade-up text-xs font-bold text-[#888888] tracking-widest uppercase mb-4">{__('general.custom_development')}</h2>
                        <h3 className="gsap-fade-up text-4xl font-extrabold text-[#111111] mb-4">{__('general.engineering_blocks')}</h3>
                        <p className="gsap-fade-up text-[#666666] text-lg">
                            {__('general.for_businesses_that_need_custom_developm')}</p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-6xl mx-auto">
                        
                        {/* Maintenance Block */}
                        <div className="gsap-fade-up flex flex-col p-10 bg-[#fafafa] border border-[#e5e5e5] rounded-2xl transition-all hover:border-[#111111] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)]">
                            <div className="mb-8">
                                <h3 className="text-2xl font-bold text-[#111111] mb-3">{__('general.maintenance_retainer')}</h3>
                                <p className="text-[#666666] text-sm leading-relaxed mb-6">{__('general.ideal_for_existing_projects_that_need_bu')}</p>
                                <div className="flex items-baseline gap-2">
                                    <span className="text-4xl font-extrabold text-[#111111]">10</span>
                                    <span className="text-[#666666] font-medium">Hours / Month</span>
                                </div>
                            </div>
                            <div className="flex-1">
                                <ul className="space-y-4 mb-8">
                                    <li className="flex gap-3 text-[#444444] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full shrink-0 mt-1.5"></div> {__('general.bug_fixes_and_patches')}</li>
                                    <li className="flex gap-3 text-[#444444] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full shrink-0 mt-1.5"></div> {__('general.server_monitoring')}</li>
                                    <li className="flex gap-3 text-[#444444] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full shrink-0 mt-1.5"></div> {__('general.security_updates')}</li>
                                </ul>
                            </div>
                            <Button onClick={() => openWhatsApp("I'm interested in the Maintenance Retainer block.")} variant="outline" className="w-full h-14 text-sm font-bold uppercase tracking-widest transition-colors rounded-xl border-[#e5e5e5]">
                                {__('general.request_pricing')}</Button>
                        </div>

                        {/* Implementation Block (Most Popular) */}
                        <div className="gsap-fade-up flex flex-col p-10 bg-[#111111] text-white rounded-2xl relative shadow-[0_8px_30px_-10px_rgba(0,0,0,0.2)] scale-[1.02] z-10 border border-[#111111]">
                            <div className="absolute -top-4 start-1/2 -translate-x-1/2">
                                <span className="bg-white text-[#111111] text-[11px] font-bold uppercase tracking-widest py-1.5 px-4 rounded-full shadow-sm">
                                    {__('general.most_popular')}</span>
                            </div>
                            <div className="mb-8 mt-4">
                                <h3 className="text-2xl font-bold text-white mb-3">{__('general.custom_build')}</h3>
                                <p className="text-[#a3a3a3] text-sm leading-relaxed mb-6">{__('general.for_building_new_mvps_saas_platforms_or')}</p>
                                <div className="flex items-baseline gap-2">
                                    <span className="text-4xl font-extrabold text-white">40+</span>
                                    <span className="text-[#a3a3a3] font-medium">Hours / Sprint</span>
                                </div>
                            </div>
                            <div className="flex-1">
                                <ul className="space-y-4 mb-8">
                                    <li className="flex gap-3 text-white text-sm font-semibold"><div className="w-1.5 h-1.5 bg-white rounded-full shrink-0 mt-1.5"></div> {__('general.fullstack_development')}</li>
                                    <li className="flex gap-3 text-white text-sm font-semibold"><div className="w-1.5 h-1.5 bg-white rounded-full shrink-0 mt-1.5"></div> {__('general.database_architecture')}</li>
                                    <li className="flex gap-3 text-white text-sm font-semibold"><div className="w-1.5 h-1.5 bg-white rounded-full shrink-0 mt-1.5"></div> {__('general.dedicated_slack_channel')}</li>
                                </ul>
                            </div>
                            <Button onClick={() => openWhatsApp("I want to discuss a Custom Build project.")} className="w-full h-14 bg-white hover:bg-[#e5e5e5] text-[#111111] text-sm font-bold uppercase tracking-widest transition-colors rounded-xl">
                                {__('general.request_pricing')}</Button>
                        </div>

                        {/* Growth Partner */}
                        <div className="gsap-fade-up flex flex-col p-10 bg-[#fafafa] border border-[#e5e5e5] rounded-2xl transition-all hover:border-[#111111] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)]">
                            <div className="mb-8">
                                <h3 className="text-2xl font-bold text-[#111111] mb-3">{__('general.growth_partner')}</h3>
                                <p className="text-[#666666] text-sm leading-relaxed mb-6">{__('general.for_scaling_companies_that_need_an_exter')}</p>
                                <div className="flex items-baseline gap-2">
                                    <span className="text-4xl font-extrabold text-[#111111]">80+</span>
                                    <span className="text-[#666666] font-medium">Hours / Month</span>
                                </div>
                            </div>
                            <div className="flex-1">
                                <ul className="space-y-4 mb-8">
                                    <li className="flex gap-3 text-[#444444] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full shrink-0 mt-1.5"></div> {__('general.technical_leadership')}</li>
                                    <li className="flex gap-3 text-[#444444] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full shrink-0 mt-1.5"></div> {__('general.code_reviews_audits')}</li>
                                    <li className="flex gap-3 text-[#444444] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full shrink-0 mt-1.5"></div> {__('general.performance_optimization')}</li>
                                </ul>
                            </div>
                            <Button onClick={() => openWhatsApp("I'm looking for a Growth Partner technical retainer.")} variant="outline" className="w-full h-14 text-sm font-bold uppercase tracking-widest transition-colors rounded-xl border-[#e5e5e5]">
                                {__('general.request_pricing')}</Button>
                        </div>

                    </div>

                    {/* Disclaimer */}
                    <div className="gsap-fade-up mt-16 max-w-3xl mx-auto flex items-start sm:items-center gap-4 p-6 bg-[#f4f4f5] rounded-xl border border-[#e5e5e5]">
                        <Info className="w-6 h-6 text-[#111111] shrink-0 mt-0.5 sm:mt-0" />
                        <p className="text-sm text-[#444444] leading-relaxed">
                            <strong>Note:</strong> {__('general.we_do_not_compete_on_price_we_compete_on')}</p>
                    </div>
                </section>

                {/* FAQ Section */}
                <section className="py-24 bg-[#fafafa] px-6 lg:px-8 reveal-section border-b border-[#e5e5e5]">
                    <div className="max-w-4xl mx-auto">
                        <div className="text-center mb-16">
                            <h2 className="gsap-fade-up text-xs font-bold text-[#888888] tracking-widest uppercase mb-4">FAQ</h2>
                            <h3 className="gsap-fade-up text-4xl font-extrabold text-[#111111]">{__('general.common_questions')}</h3>
                        </div>
                        
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-10">
                            <div className="gsap-fade-up">
                                <h4 className="text-lg font-bold text-[#111111] mb-3">{__('general.do_you_provide_fixedprice_quotes')}</h4>
                                <p className="text-[#666666] leading-relaxed">{__('general.for_clearly_defined_mvp_projects_yes_how')}</p>
                            </div>
                            
                            <div className="gsap-fade-up">
                                <h4 className="text-lg font-bold text-[#111111] mb-3">{__('general.what_happens_if_we_dont_use_all_our_hour')}</h4>
                                <p className="text-[#666666] leading-relaxed">{__('general.for_our_monthly_retainers_hours_do_not_r')}</p>
                            </div>
                            
                            <div className="gsap-fade-up">
                                <h4 className="text-lg font-bold text-[#111111] mb-3">{__('general.do_you_work_with_equity_instead_of_cash')}</h4>
                                <p className="text-[#666666] leading-relaxed">{__('general.no_we_operate_strictly_as_an_engineering')}</p>
                            </div>
                            
                            <div className="gsap-fade-up">
                                <h4 className="text-lg font-bold text-[#111111] mb-3">{__('general.how_do_we_communicate_during_a_sprint')}</h4>
                                <p className="text-[#666666] leading-relaxed">{__('general.we_set_up_a_shared_slack_or_whatsapp_cha')}</p>
                            </div>
                        </div>
                    </div>
                </section>

                {/* CTA Section */}
                <section className="py-32 bg-[#111111] text-white text-center reveal-section px-6">
                    <div className="max-w-3xl mx-auto">
                        <h2 className="gsap-fade-up text-4xl md:text-5xl font-extrabold mb-6">
                            {__('general.ready_to_hire_expert_engineers')}</h2>
                        <p className="gsap-fade-up text-xl text-[#a3a3a3] mb-12 leading-relaxed">
                            {__('general.contact_us_to_discuss_your_requirements')}</p>
                        <Button 
                            onClick={() => openWhatsApp("Hello Mahmoud, I want to discuss hiring Musoftware for a custom project.")}
                            className="gsap-fade-up bg-white text-[#111111] hover:bg-[#e5e5e5] rounded-xl px-10 py-7 text-sm font-bold tracking-wide uppercase transition-all flex items-center justify-center gap-3 mx-auto"
                        >
                            {__('general.get_a_custom_quote')}<ArrowRight className="w-4 h-4" />
                        </Button>
                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}

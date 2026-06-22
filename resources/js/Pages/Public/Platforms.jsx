import { useRef } from 'react';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { LayoutDashboard, Workflow, Globe, Monitor, ArrowRight } from 'lucide-react';
import FloatingWhatsAppButton from '@/Components/FloatingWhatsAppButton';
import { Button } from '@/Components/ui/button';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';
import { __ } from '@/lib/i18n';

gsap.registerPlugin(ScrollTrigger);

export default function Platforms() {
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
                <title>Platforms | Musoftware</title>
                <meta name="description" content="Explore the platforms we build, from internal dashboards to high-performance enterprise systems." />
            </Head>

            <FloatingWhatsAppButton phoneNumber={phoneNumber} defaultMessage="Hello Mahmoud, I want to discuss building a platform." />

            <div ref={mainRef} className="w-full bg-[#fcfcfc] text-[#111111] font-sans selection:bg-[#111111] selection:text-white overflow-x-hidden">
                
                <section className="pt-32 pb-24 lg:pt-48 lg:pb-32 px-6 lg:px-8 max-w-7xl mx-auto reveal-section border-b border-[#e5e5e5]">
                    <div className="max-w-4xl mb-20">
                        <div className="gsap-fade-up inline-flex items-center gap-2 px-3 py-1 border border-[#e5e5e5] text-xs font-semibold text-[#666666] tracking-widest uppercase mb-8 bg-white">
                            <span className="flex h-1.5 w-1.5 rounded-full bg-[#111111]"></span>
                            {__('general.platforms')}</div>
                        <h1 className="gsap-fade-up text-5xl lg:text-7xl font-extrabold text-[#111111] tracking-tight leading-[1.05] mb-6">
                            {__('general.scalable_digital_platforms')}</h1>
                        <p className="gsap-fade-up text-xl text-[#666666] font-normal leading-relaxed max-w-2xl">
                            We don't just write code; we build robust platforms that serve as the operational backbone of your business. Whether you need an internal dashboard or a public-facing SaaS, we have the architecture ready.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div className="gsap-fade-up p-12 bg-white border border-[#e5e5e5] rounded-2xl shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] transition-colors group hover:border-[#111111]">
                            <LayoutDashboard className="w-10 h-10 text-[#111111] mb-8" strokeWidth={1.5} />
                            <h3 className="text-2xl font-bold text-[#111111] mb-4">{__('general.internal_admin_dashboards')}</h3>
                            <p className="text-[#666666] leading-relaxed mb-8">
                                {__('general.give_your_team_a_central_hub_to_manage_d')}</p>
                            <ul className="space-y-4 mb-10">
                                <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full me-4"></div>{__('general.data_visualizations_charts')}</li>
                                <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full me-4"></div>{__('general.rolebased_permissions')}</li>
                                <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full me-4"></div>{__('general.realtime_data_updates')}</li>
                            </ul>
                            <button onClick={() => openWhatsApp("I'm interested in an Internal Dashboard.")} className="inline-flex items-center gap-2 text-xs font-bold text-[#111111] uppercase tracking-widest hover:text-[#666666] transition-colors">
                                {__('general.discuss_this_platform')}<ArrowRight className="w-4 h-4" />
                            </button>
                        </div>
                        
                        <div className="gsap-fade-up p-12 bg-white border border-[#e5e5e5] rounded-2xl shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] transition-colors group hover:border-[#111111]">
                            <Workflow className="w-10 h-10 text-[#111111] mb-8" strokeWidth={1.5} />
                            <h3 className="text-2xl font-bold text-[#111111] mb-4">{__('general.workflow_automation')}</h3>
                            <p className="text-[#666666] leading-relaxed mb-8">
                                {__('general.replace_manual_data_entry_with_automated')}</p>
                            <ul className="space-y-4 mb-10">
                                <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full me-4"></div>{__('general.thirdparty_api_integrations')}</li>
                                <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full me-4"></div>{__('general.scheduled_background_tasks')}</li>
                                <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full me-4"></div>{__('general.automated_sms_email_triggers')}</li>
                            </ul>
                            <button onClick={() => openWhatsApp("I'm interested in Workflow Automation.")} className="inline-flex items-center gap-2 text-xs font-bold text-[#111111] uppercase tracking-widest hover:text-[#666666] transition-colors">
                                {__('general.discuss_this_platform')}<ArrowRight className="w-4 h-4" />
                            </button>
                        </div>

                        <div className="gsap-fade-up p-12 bg-white border border-[#e5e5e5] rounded-2xl shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] transition-colors group hover:border-[#111111]">
                            <Globe className="w-10 h-10 text-[#111111] mb-8" strokeWidth={1.5} />
                            <h3 className="text-2xl font-bold text-[#111111] mb-4">{__('general.saas_applications')}</h3>
                            <p className="text-[#666666] leading-relaxed mb-8">
                                {__('general.launch_your_own_subscriptionbased_softwa')}</p>
                            <ul className="space-y-4 mb-10">
                                <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full me-4"></div>{__('general.subscription_billing_logic')}</li>
                                <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full me-4"></div>{__('general.tenant_data_isolation')}</li>
                                <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full me-4"></div>{__('general.custom_user_portals')}</li>
                            </ul>
                            <button onClick={() => openWhatsApp("I'm interested in building a SaaS Application.")} className="inline-flex items-center gap-2 text-xs font-bold text-[#111111] uppercase tracking-widest hover:text-[#666666] transition-colors">
                                {__('general.discuss_this_platform')}<ArrowRight className="w-4 h-4" />
                            </button>
                        </div>

                        <div className="gsap-fade-up p-12 bg-white border border-[#e5e5e5] rounded-2xl shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] transition-colors group hover:border-[#111111]">
                            <Monitor className="w-10 h-10 text-[#111111] mb-8" strokeWidth={1.5} />
                            <h3 className="text-2xl font-bold text-[#111111] mb-4">{__('general.customer_portals')}</h3>
                            <p className="text-[#666666] leading-relaxed mb-8">
                                {__('general.give_your_clients_a_professional_interfa')}</p>
                            <ul className="space-y-4 mb-10">
                                <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full me-4"></div>{__('general.secure_client_authentication')}</li>
                                <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full me-4"></div>{__('general.invoice_document_sharing')}</li>
                                <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full me-4"></div>{__('general.support_ticket_systems')}</li>
                            </ul>
                            <button onClick={() => openWhatsApp("I'm interested in building a Customer Portal.")} className="inline-flex items-center gap-2 text-xs font-bold text-[#111111] uppercase tracking-widest hover:text-[#666666] transition-colors">
                                {__('general.discuss_this_platform')}<ArrowRight className="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </section>
                
                {/* CTA Section */}
                <section className="py-32 bg-[#111111] text-white text-center reveal-section px-6">
                    <div className="max-w-3xl mx-auto">
                        <h2 className="gsap-fade-up text-4xl md:text-5xl font-extrabold mb-6">
                            {__('general.ready_to_build_your_platform')}</h2>
                        <p className="gsap-fade-up text-xl text-[#a3a3a3] mb-12 leading-relaxed">
                            {__('general.stop_using_fragmented_tools_lets_build_a')}</p>
                        <Button 
                            onClick={() => openWhatsApp("Hello Mahmoud, I want to discuss building a platform for my business.")}
                            className="gsap-fade-up bg-white text-[#111111] hover:bg-[#e5e5e5] rounded-xl px-10 py-7 text-sm font-bold tracking-wide uppercase transition-all flex items-center justify-center gap-3 mx-auto"
                        >
                            {__('general.start_your_project')}<ArrowRight className="w-4 h-4" />
                        </Button>
                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}

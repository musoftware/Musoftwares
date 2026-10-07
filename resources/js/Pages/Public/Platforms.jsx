import { useRef } from 'react';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { LayoutDashboard, Workflow, Globe, Monitor, ArrowUpRight } from 'lucide-react';
import FloatingWhatsAppButton from '@/Components/FloatingWhatsAppButton';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';
import { __ } from '@/lib/i18n';
import StudioHeader from '@/Components/Studio/StudioHeader';
import { openWhatsAppChat, STUDIO_PHONE } from '@/lib/whatsapp';

gsap.registerPlugin(ScrollTrigger);

export default function Platforms() {
    const mainRef = useRef(null);

    useGSAP(() => {
        const sections = gsap.utils.toArray('.reveal-section');
        sections.forEach((section) => {
            const elements = section.querySelectorAll('.gsap-fade-up');
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

    return (
        <PublicLayout>
            <Head>
                <title>{__('general.platforms')} | Musoftwares</title>
                <meta name="description" content={__('frontend.platforms_meta_desc')} />
            </Head>

            <FloatingWhatsAppButton phoneNumber={STUDIO_PHONE} defaultMessage={__('frontend.platforms_whatsapp_message')} />

            <div ref={mainRef} className="w-full bg-[#ffffff] text-[#1d1d1f] font-sans selection:bg-[#0071e3]/20 selection:text-[#0071e3] overflow-x-hidden pt-12 sm:pt-20 pb-24 sm:pb-36">
                
                {/* Hero Header */}
                <div className="reveal-section">
                    <StudioHeader
                        badge={__('general.platforms')}
                        title={
                            <>
                                {__('frontend.platforms_hero_title')} <br className="hidden sm:inline" />
                                <span className="text-[#0071e3]">{__('frontend.platforms_hero_highlight')}</span>
                            </>
                        }
                        subtitle={__('general.we_dont_just_write_code')}
                    />

                    <div className="flex flex-col sm:flex-row gap-4 w-full sm:w-auto items-center justify-center text-xs mb-20 -mt-8">
                        <button
                            onClick={() => openWhatsAppChat(__('frontend.platforms_cta_message'))}
                            className="bg-[#0071e3] hover:bg-[#0077ed] text-white px-8 py-3 rounded-[980px] font-semibold tracking-wide transition-all shadow-md shadow-blue-500/20 cursor-pointer"
                        >
                            {__('frontend.platforms_cta')} ➔
                        </button>
                        <Link
                            href="/estimator"
                            className="border border-black/10 hover:border-black/30 bg-white text-[#1d1d1f] hover:bg-[#f5f5f7] px-8 py-3 rounded-[980px] font-semibold tracking-wide transition-all shadow-sm"
                        >
                            {__('general.calculate_estimate')}
                        </Link>
                    </div>
                </div>

                {/* Platforms Grid */}
                <section className="px-6 max-w-[1400px] mx-auto reveal-section mb-24">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                        
                        {/* 1. Internal Dashboards */}
                        <div className="gsap-fade-up bg-white border border-black/5 rounded-[24px] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#0071e3]/40 hover:shadow-md transition-all shadow-sm">
                            <div className="space-y-4">
                                <div className="w-12 h-12 rounded-2xl bg-[#0071e3]/10 flex items-center justify-center text-[#0071e3]">
                                    <LayoutDashboard className="w-6 h-6" />
                                </div>
                                <h3 className="text-xl font-semibold text-[#1d1d1f] font-sans tracking-tight">
                                    {__('general.internal_admin_dashboards')}
                                </h3>
                                <p className="text-sm text-[#1d1d1f]/60 leading-relaxed font-sans">
                                    {__('general.give_your_team_a_central_hub_to_manage_d')}
                                </p>
                                <ul className="space-y-2.5 pt-2 text-xs font-sans text-[#1d1d1f]/80">
                                    <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 rounded-full bg-[#0071e3] shrink-0"></span>{__('general.data_visualizations_charts')}</li>
                                    <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 rounded-full bg-[#0071e3] shrink-0"></span>{__('general.rolebased_permissions')}</li>
                                    <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 rounded-full bg-[#0071e3] shrink-0"></span>{__('general.realtime_data_updates')}</li>
                                </ul>
                            </div>
                            <button
                                onClick={() => openWhatsAppChat(__('frontend.platforms_dashboard_message'))}
                                className="mt-8 text-xs font-semibold text-[#0071e3] hover:text-[#0077ed] flex items-center gap-1 rtl:gap-reverse cursor-pointer"
                            >
                                <span>{__('frontend.platforms_discuss_this_platform')}</span>
                                <ArrowUpRight className="w-4 h-4 rtl:rotate-[-90deg]" />
                            </button>
                        </div>

                        {/* 2. Workflow Automation */}
                        <div className="gsap-fade-up bg-white border border-black/5 rounded-[24px] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#0071e3]/40 hover:shadow-md transition-all shadow-sm">
                            <div className="space-y-4">
                                <div className="w-12 h-12 rounded-2xl bg-[#0071e3]/10 flex items-center justify-center text-[#0071e3]">
                                    <Workflow className="w-6 h-6" />
                                </div>
                                <h3 className="text-xl font-semibold text-[#1d1d1f] font-sans tracking-tight">
                                    {__('general.workflow_automation')}
                                </h3>
                                <p className="text-sm text-[#1d1d1f]/60 leading-relaxed font-sans">
                                    {__('general.replace_manual_data_entry_with_automated')}
                                </p>
                                <ul className="space-y-2.5 pt-2 text-xs font-sans text-[#1d1d1f]/80">
                                    <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 rounded-full bg-[#0071e3] shrink-0"></span>{__('general.thirdparty_api_integrations')}</li>
                                    <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 rounded-full bg-[#0071e3] shrink-0"></span>{__('general.scheduled_background_tasks')}</li>
                                    <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 rounded-full bg-[#0071e3] shrink-0"></span>{__('general.automated_sms_email_triggers')}</li>
                                </ul>
                            </div>
                            <button
                                onClick={() => openWhatsAppChat(__('frontend.platforms_workflow_message'))}
                                className="mt-8 text-xs font-semibold text-[#0071e3] hover:text-[#0077ed] flex items-center gap-1 rtl:gap-reverse cursor-pointer"
                            >
                                <span>{__('frontend.platforms_discuss_this_platform')}</span>
                                <ArrowUpRight className="w-4 h-4 rtl:rotate-[-90deg]" />
                            </button>
                        </div>

                        {/* 3. SaaS Platforms */}
                        <div className="gsap-fade-up bg-white border border-black/5 rounded-[24px] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#0071e3]/40 hover:shadow-md transition-all shadow-sm">
                            <div className="space-y-4">
                                <div className="w-12 h-12 rounded-2xl bg-[#0071e3]/10 flex items-center justify-center text-[#0071e3]">
                                    <Globe className="w-6 h-6" />
                                </div>
                                <h3 className="text-xl font-semibold text-[#1d1d1f] font-sans tracking-tight">
                                    {__('general.saas_applications')}
                                </h3>
                                <p className="text-sm text-[#1d1d1f]/60 leading-relaxed font-sans">
                                    {__('general.launch_your_own_subscriptionbased_softwa')}
                                </p>
                                <ul className="space-y-2.5 pt-2 text-xs font-sans text-[#1d1d1f]/80">
                                    <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 rounded-full bg-[#0071e3] shrink-0"></span>{__('general.subscription_billing_logic')}</li>
                                    <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 rounded-full bg-[#0071e3] shrink-0"></span>{__('general.tenant_data_isolation')}</li>
                                    <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 rounded-full bg-[#0071e3] shrink-0"></span>{__('general.custom_user_portals')}</li>
                                </ul>
                            </div>
                            <button
                                onClick={() => openWhatsAppChat(__('frontend.platforms_saas_message'))}
                                className="mt-8 text-xs font-semibold text-[#0071e3] hover:text-[#0077ed] flex items-center gap-1 rtl:gap-reverse cursor-pointer"
                            >
                                <span>{__('frontend.platforms_discuss_this_platform')}</span>
                                <ArrowUpRight className="w-4 h-4 rtl:rotate-[-90deg]" />
                            </button>
                        </div>

                        {/* 4. Customer Portals */}
                        <div className="gsap-fade-up bg-white border border-black/5 rounded-[24px] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#0071e3]/40 hover:shadow-md transition-all shadow-sm">
                            <div className="space-y-4">
                                <div className="w-12 h-12 rounded-2xl bg-[#0071e3]/10 flex items-center justify-center text-[#0071e3]">
                                    <Monitor className="w-6 h-6" />
                                </div>
                                <h3 className="text-xl font-semibold text-[#1d1d1f] font-sans tracking-tight">
                                    {__('general.customer_portals')}
                                </h3>
                                <p className="text-sm text-[#1d1d1f]/60 leading-relaxed font-sans">
                                    {__('general.give_your_clients_a_professional_interfa')}
                                </p>
                                <ul className="space-y-2.5 pt-2 text-xs font-sans text-[#1d1d1f]/80">
                                    <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 rounded-full bg-[#0071e3] shrink-0"></span>{__('general.secure_client_authentication')}</li>
                                    <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 rounded-full bg-[#0071e3] shrink-0"></span>{__('general.invoice_document_sharing')}</li>
                                    <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 rounded-full bg-[#0071e3] shrink-0"></span>{__('general.support_ticket_systems')}</li>
                                </ul>
                            </div>
                            <button
                                onClick={() => openWhatsAppChat(__('frontend.platforms_portal_message'))}
                                className="mt-8 text-xs font-semibold text-[#0071e3] hover:text-[#0077ed] flex items-center gap-1 rtl:gap-reverse cursor-pointer"
                            >
                                <span>{__('frontend.platforms_discuss_this_platform')}</span>
                                <ArrowUpRight className="w-4 h-4 rtl:rotate-[-90deg]" />
                            </button>
                        </div>

                    </div>
                </section>

            </div>
        </PublicLayout>
    );
}

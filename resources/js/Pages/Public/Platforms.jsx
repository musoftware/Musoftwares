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
                <title>{__('general.platforms') || 'Platforms'} | Musoftwares</title>
                <meta name="description" content="Explore the platforms we build, from internal dashboards to high-performance enterprise systems." />
            </Head>

            <FloatingWhatsAppButton phoneNumber={STUDIO_PHONE} defaultMessage="Hello Mahmoud, I want to discuss building a platform." />

            <div ref={mainRef} className="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white overflow-x-hidden pt-16 sm:pt-24 pb-24 sm:pb-36">
                
                {/* Hero Header */}
                <div className="reveal-section">
                    <StudioHeader
                        badge={__('general.platforms') || 'Digital Architecture'}
                        title={
                            <>
                                Scalable Digital Platforms. <br className="hidden sm:inline" />
                                <span className="text-[#748660]">Engineered For Longevity.</span>
                            </>
                        }
                        subtitle={__('general.we_dont_just_write_code') || "We don't just write code; we build robust platforms that serve as the operational backbone of your business."}
                    />

                    <div className="flex flex-col sm:flex-row gap-4 w-full sm:w-auto items-center justify-center font-mono text-xs mb-20 -mt-8">
                        <button
                            onClick={() => openWhatsAppChat("Hello Mahmoud, I want to discuss a custom platform architecture.")}
                            className="bg-white text-black hover:bg-zinc-200 px-8 py-3.5 font-bold uppercase tracking-widest rtl:tracking-normal transition-all"
                        >
                            DISCUSS PLATFORM ➔
                        </button>
                        <Link
                            href="/estimator"
                            className="border border-[#333333] hover:border-white text-zinc-300 hover:text-white px-8 py-3.5 font-bold uppercase tracking-widest rtl:tracking-normal transition-all"
                        >
                            {__('general.calculate_estimate') || 'ESTIMATE SCOPE'}
                        </Link>
                    </div>
                </div>

                {/* Platforms Grid */}
                <section className="px-6 max-w-[1400px] mx-auto reveal-section mb-24">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                        
                        {/* 1. Internal Dashboards */}
                        <div className="gsap-fade-up bg-[#161616] border border-[#262626] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#748660] transition-colors">
                            <div className="space-y-4">
                                <div className="w-12 h-12 bg-black border border-[#2B2B2B] flex items-center justify-center text-[#748660]">
                                    <LayoutDashboard className="w-6 h-6" />
                                </div>
                                <h3 className="text-xl font-bold text-white font-sans">
                                    {__('general.internal_admin_dashboards') || 'Internal Admin Dashboards'}
                                </h3>
                                <p className="text-sm text-zinc-400 leading-relaxed font-sans">
                                    {__('general.give_your_team_a_central_hub_to_manage_d') || 'Give your team a central hub to manage daily workflows, financial ledgers, and analytics.'}
                                </p>
                                <ul className="space-y-2.5 pt-2 text-xs font-sans text-zinc-300">
                                    <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 bg-[#748660] shrink-0"></span>{__('general.data_visualizations_charts') || 'Realtime Data Visualizations & Metrics'}</li>
                                    <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 bg-[#748660] shrink-0"></span>{__('general.rolebased_permissions') || 'Granular Role-Based Permissions'}</li>
                                    <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 bg-[#748660] shrink-0"></span>{__('general.realtime_data_updates') || 'Zero-Lag Operational Feeds'}</li>
                                </ul>
                            </div>
                            <button
                                onClick={() => openWhatsAppChat("I'm interested in an Internal Dashboard.")}
                                className="mt-8 text-xs font-mono font-bold text-white hover:text-[#748660] flex items-center gap-1 rtl:gap-reverse"
                            >
                                <span>DISCUSS THIS PLATFORM</span>
                                <ArrowUpRight className="w-4 h-4 rtl:rotate-[-90deg]" />
                            </button>
                        </div>

                        {/* 2. Workflow Automation */}
                        <div className="gsap-fade-up bg-[#161616] border border-[#262626] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#748660] transition-colors">
                            <div className="space-y-4">
                                <div className="w-12 h-12 bg-black border border-[#2B2B2B] flex items-center justify-center text-[#748660]">
                                    <Workflow className="w-6 h-6" />
                                </div>
                                <h3 className="text-xl font-bold text-white font-sans">
                                    {__('general.workflow_automation') || 'Workflow Automation'}
                                </h3>
                                <p className="text-sm text-zinc-400 leading-relaxed font-sans">
                                    {__('general.replace_manual_data_entry_with_automated') || 'Replace manual repetitive data entry with automated background pipelines.'}
                                </p>
                                <ul className="space-y-2.5 pt-2 text-xs font-sans text-zinc-300">
                                    <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 bg-[#748660] shrink-0"></span>{__('general.thirdparty_api_integrations') || 'Third-party API & Graph Integrations'}</li>
                                    <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 bg-[#748660] shrink-0"></span>{__('general.scheduled_background_tasks') || 'Scheduled Daemon Queue Workers'}</li>
                                    <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 bg-[#748660] shrink-0"></span>{__('general.automated_sms_email_triggers') || 'Automated Webhook & WhatsApp Triggers'}</li>
                                </ul>
                            </div>
                            <button
                                onClick={() => openWhatsAppChat("I'm interested in Workflow Automation.")}
                                className="mt-8 text-xs font-mono font-bold text-white hover:text-[#748660] flex items-center gap-1 rtl:gap-reverse"
                            >
                                <span>DISCUSS THIS PLATFORM</span>
                                <ArrowUpRight className="w-4 h-4 rtl:rotate-[-90deg]" />
                            </button>
                        </div>

                        {/* 3. SaaS Platforms */}
                        <div className="gsap-fade-up bg-[#161616] border border-[#262626] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#748660] transition-colors">
                            <div className="space-y-4">
                                <div className="w-12 h-12 bg-black border border-[#2B2B2B] flex items-center justify-center text-[#748660]">
                                    <Globe className="w-6 h-6" />
                                </div>
                                <h3 className="text-xl font-bold text-white font-sans">
                                    {__('general.saas_applications') || 'SaaS Applications'}
                                </h3>
                                <p className="text-sm text-zinc-400 leading-relaxed font-sans">
                                    {__('general.launch_your_own_subscriptionbased_softwa') || 'Launch your own subscription-based software with automated multi-tenant isolation.'}
                                </p>
                                <ul className="space-y-2.5 pt-2 text-xs font-sans text-zinc-300">
                                    <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 bg-[#748660] shrink-0"></span>{__('general.subscription_billing_logic') || 'Billing & Automated Wallet Deductions'}</li>
                                    <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 bg-[#748660] shrink-0"></span>{__('general.tenant_data_isolation') || 'Zero-Leak Schema Tenant Isolation'}</li>
                                    <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 bg-[#748660] shrink-0"></span>{__('general.custom_user_portals') || 'White-Label Customer Workspaces'}</li>
                                </ul>
                            </div>
                            <button
                                onClick={() => openWhatsAppChat("I'm interested in building a SaaS Application.")}
                                className="mt-8 text-xs font-mono font-bold text-white hover:text-[#748660] flex items-center gap-1 rtl:gap-reverse"
                            >
                                <span>DISCUSS THIS PLATFORM</span>
                                <ArrowUpRight className="w-4 h-4 rtl:rotate-[-90deg]" />
                            </button>
                        </div>

                        {/* 4. Customer Portals */}
                        <div className="gsap-fade-up bg-[#161616] border border-[#262626] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#748660] transition-colors">
                            <div className="space-y-4">
                                <div className="w-12 h-12 bg-black border border-[#2B2B2B] flex items-center justify-center text-[#748660]">
                                    <Monitor className="w-6 h-6" />
                                </div>
                                <h3 className="text-xl font-bold text-white font-sans">
                                    {__('general.customer_portals') || 'Client Workspaces & Portals'}
                                </h3>
                                <p className="text-sm text-zinc-400 leading-relaxed font-sans">
                                    {__('general.give_your_clients_a_professional_interfa') || 'Give your clients a high-fidelity interface to track invoices, deliverables, and projects.'}
                                </p>
                                <ul className="space-y-2.5 pt-2 text-xs font-sans text-zinc-300">
                                    <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 bg-[#748660] shrink-0"></span>{__('general.secure_client_authentication') || 'Secure Single-Sign-On Auth'}</li>
                                    <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 bg-[#748660] shrink-0"></span>{__('general.invoice_document_sharing') || 'PDF Quotations & Instant Invoicing'}</li>
                                    <li className="flex items-center gap-2"><span className="w-1.5 h-1.5 bg-[#748660] shrink-0"></span>{__('general.support_ticket_systems') || 'Direct Real-Time Chat & Tickets'}</li>
                                </ul>
                            </div>
                            <button
                                onClick={() => openWhatsAppChat("I'm interested in building a Customer Portal.")}
                                className="mt-8 text-xs font-mono font-bold text-white hover:text-[#748660] flex items-center gap-1 rtl:gap-reverse"
                            >
                                <span>DISCUSS THIS PLATFORM</span>
                                <ArrowUpRight className="w-4 h-4 rtl:rotate-[-90deg]" />
                            </button>
                        </div>

                    </div>
                </section>

            </div>
        </PublicLayout>
    );
}

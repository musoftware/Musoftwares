import { useRef } from 'react';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Users, MessageSquare, Target, ArrowUpRight } from 'lucide-react';
import FloatingWhatsAppButton from '@/Components/FloatingWhatsAppButton';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';
import StudioHeader from '@/Components/Studio/StudioHeader';
import { openWhatsAppChat, STUDIO_PHONE } from '@/lib/whatsapp';

gsap.registerPlugin(ScrollTrigger);

export default function Crm({ auth }) {
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

    const features = [
        {
            title: "Automated Lead Pipeline & Stages",
            icon: Target,
            desc: "Capture inquiries instantly from landing pages, Facebook Ads, and WhatsApp into structured conversion stages.",
            bullets: ["Custom sales funnel stages", "Lead scoring & assignment rules", "Interaction timeline tracking"]
        },
        {
            title: "Omnichannel Communications & WhatsApp",
            icon: MessageSquare,
            desc: "Unified messaging hub integrating Official Meta WhatsApp Cloud API with multi-agent inbox support.",
            bullets: ["Official WhatsApp Business API", "Multi-agent concurrent chat", "Automated message templates"]
        },
        {
            title: "Client 360 & Lifetime Value",
            icon: Users,
            desc: "Comprehensive customer intelligence with historical purchase orders, support tickets, and total lifetime spend.",
            bullets: ["Customer transaction ledger", "Lifetime value (LTV) analytics", "Contract & file repository"]
        }
    ];

    return (
        <PublicLayout>
            <Head>
                <title>Custom CRM Platform Architecture | Musoftwares</title>
                <meta name="description" content="Custom Customer Relationship Management systems built for seamless lead conversion and communication." />
            </Head>

            <FloatingWhatsAppButton phoneNumber={STUDIO_PHONE} defaultMessage="Hello Mahmoud, I want to discuss building a custom CRM system." />

            <div ref={mainRef} className="w-full bg-[#ffffff] text-[#1d1d1f] font-sans selection:bg-[#0071e3]/20 selection:text-[#0071e3] overflow-x-hidden pt-12 sm:pt-20 pb-24 sm:pb-36">
                
                {/* Hero Header */}
                <div className="reveal-section">
                    <StudioHeader
                        badge="Pipeline & Conversions"
                        title={
                            <>
                                Bespoke CRM Architecture. <br className="hidden sm:inline" />
                                <span className="text-[#0071e3]">Full Lead-to-Cash Visibility.</span>
                            </>
                        }
                        subtitle="Never lose a qualified lead. We build unified CRM systems with deep WhatsApp integration and real-time interaction continuity."
                    />

                    <div className="flex flex-col sm:flex-row gap-4 w-full sm:w-auto items-center justify-center text-xs mb-20 -mt-8">
                        <button
                            onClick={() => openWhatsAppChat("Hello Mahmoud, I want to discuss a custom CRM system.")}
                            className="bg-[#0071e3] hover:bg-[#0077ed] text-white px-8 py-3 rounded-[980px] font-semibold tracking-wide transition-all shadow-md shadow-blue-500/20 cursor-pointer"
                        >
                            DISCUSS CRM SCOPE ➔
                        </button>
                        <Link
                            href="/estimator"
                            className="border border-black/10 hover:border-black/30 bg-white text-[#1d1d1f] hover:bg-[#f5f5f7] px-8 py-3 rounded-[980px] font-semibold tracking-wide transition-all shadow-sm"
                        >
                            {__('general.calculate_estimate') || 'CALCULATE ESTIMATE'}
                        </Link>
                    </div>
                </div>

                {/* CRM Pillars Grid */}
                <section className="px-6 max-w-[1400px] mx-auto reveal-section mb-24">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        {features.map((item, idx) => {
                            const Icon = item.icon;
                            return (
                                <div
                                    key={idx}
                                    className="gsap-fade-up bg-white border border-black/5 rounded-[24px] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#0071e3]/40 hover:shadow-md transition-all shadow-sm"
                                >
                                    <div className="space-y-4">
                                        <div className="w-12 h-12 rounded-2xl bg-[#0071e3]/10 flex items-center justify-center text-[#0071e3]">
                                            <Icon className="w-6 h-6" />
                                        </div>
                                        <h3 className="text-xl font-semibold text-[#1d1d1f] font-sans tracking-tight">
                                            {item.title}
                                        </h3>
                                        <p className="text-sm text-[#1d1d1f]/60 leading-relaxed font-sans">
                                            {item.desc}
                                        </p>
                                        <ul className="space-y-2.5 pt-2 text-xs font-sans text-[#1d1d1f]/80">
                                            {item.bullets.map((b, bIdx) => (
                                                <li key={bIdx} className="flex items-center gap-2">
                                                    <span className="w-1.5 h-1.5 rounded-full bg-[#0071e3] shrink-0"></span>
                                                    <span>{b}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                    <button
                                        onClick={() => openWhatsAppChat(`Hello Mahmoud, I want to discuss ${item.title}.`)}
                                        className="mt-8 text-xs font-semibold text-[#0071e3] hover:text-[#0077ed] flex items-center gap-1 rtl:gap-reverse cursor-pointer"
                                    >
                                        <span>INITIATE MODULE BRIEF</span>
                                        <ArrowUpRight className="w-4 h-4 rtl:rotate-[-90deg]" />
                                    </button>
                                </div>
                            );
                        })}
                    </div>
                </section>

            </div>
        </PublicLayout>
    );
}

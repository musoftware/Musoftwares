import { useRef } from 'react';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Briefcase, Building2, GraduationCap, Code2, ArrowUpRight } from 'lucide-react';
import FloatingWhatsAppButton from '@/Components/FloatingWhatsAppButton';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';
import { __ } from '@/lib/i18n';
import StudioHeader from '@/Components/Studio/StudioHeader';
import { openWhatsAppChat, STUDIO_PHONE } from '@/lib/whatsapp';

gsap.registerPlugin(ScrollTrigger);

export default function Solutions() {
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

    const solutions = [
        {
            title: "ERP & Business Backbones",
            icon: Building2,
            desc: "Custom Enterprise Resource Planning systems tailored to your unique operational workflows, double-entry finance, inventory, and multi-branch sales.",
            features: ["Custom business workflows", "Strict role-based access", "Real-time ledger analytics"]
        },
        {
            title: "SaaS & Subscription Platforms",
            icon: Briefcase,
            desc: "Turn your software idea into a scalable business with tenant data isolation, recurring billing engines, and unified client workspaces.",
            features: ["Zero-leak multi-tenant schema", "Instant payment integrations", "White-label customer dashboards"]
        },
        {
            title: "E-Learning & Academy Systems",
            icon: GraduationCap,
            desc: "Custom platforms for video streaming protection, student grade management, and interactive exam pipelines.",
            features: ["DRM video protection", "Automated student grading", "Certificates & quiz engines"]
        },
        {
            title: "Bespoke APIs & Background Microservices",
            icon: Code2,
            desc: "High-throughput webhook consumers, message queues, and API gateways that process millions of events reliably.",
            features: ["High-speed Redis pipelines", "Guaranteed webhook deliveries", "Sub-100ms response targets"]
        }
    ];

    return (
        <PublicLayout>
            <Head>
                <title>{__('general.solutions') || 'Solutions'} | Musoftwares</title>
                <meta name="description" content="Custom software engineering solutions tailored to solve specific business problems." />
            </Head>

            <FloatingWhatsAppButton phoneNumber={STUDIO_PHONE} defaultMessage="Hello Mahmoud, I'd like to discuss a software solution." />

            <div ref={mainRef} className="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white overflow-x-hidden pt-16 sm:pt-24 pb-24 sm:pb-36">
                
                {/* Hero Header */}
                <div className="reveal-section">
                    <StudioHeader
                        badge={__('general.solutions') || 'Engineered Solutions'}
                        title={
                            <>
                                Tailored Software Solutions. <br className="hidden sm:inline" />
                                <span className="text-[#748660]">Built for Your Exact Workflow.</span>
                            </>
                        }
                        subtitle="Every industry has unique operational challenges. We build specialized software engines designed around your exact business requirements."
                    />

                    <div className="flex flex-col sm:flex-row gap-4 w-full sm:w-auto items-center justify-center font-mono text-xs mb-20 -mt-8">
                        <button
                            onClick={() => openWhatsAppChat("Hello Mahmoud, I want to discuss a tailored solution for my business.")}
                            className="bg-white text-black hover:bg-zinc-200 px-8 py-3.5 font-bold uppercase tracking-widest rtl:tracking-normal transition-all"
                        >
                            DISCUSS SOLUTION ➔
                        </button>
                        <Link
                            href="/estimator"
                            className="border border-[#333333] hover:border-white text-zinc-300 hover:text-white px-8 py-3.5 font-bold uppercase tracking-widest rtl:tracking-normal transition-all"
                        >
                            {__('general.calculate_estimate') || 'CALCULATE ESTIMATE'}
                        </Link>
                    </div>
                </div>

                {/* Solutions Grid */}
                <section className="px-6 max-w-[1400px] mx-auto reveal-section mb-24">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                        {solutions.map((item, idx) => {
                            const Icon = item.icon;
                            return (
                                <div
                                    key={idx}
                                    className="gsap-fade-up bg-[#161616] border border-[#262626] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#748660] transition-colors"
                                >
                                    <div className="space-y-4">
                                        <div className="w-12 h-12 bg-black border border-[#2B2B2B] flex items-center justify-center text-[#748660]">
                                            <Icon className="w-6 h-6" />
                                        </div>
                                        <h3 className="text-xl font-bold text-white font-sans">
                                            {item.title}
                                        </h3>
                                        <p className="text-sm text-zinc-400 leading-relaxed font-sans">
                                            {item.desc}
                                        </p>
                                        <ul className="space-y-2.5 pt-2 text-xs font-sans text-zinc-300">
                                            {item.features.map((feat, fIdx) => (
                                                <li key={fIdx} className="flex items-center gap-2">
                                                    <span className="w-1.5 h-1.5 bg-[#748660] shrink-0"></span>
                                                    <span>{feat}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                    <button
                                        onClick={() => openWhatsAppChat(`Hello Mahmoud, I want to discuss ${item.title}.`)}
                                        className="mt-8 text-xs font-mono font-bold text-white hover:text-[#748660] flex items-center gap-1 rtl:gap-reverse"
                                    >
                                        <span>INITIATE ARCHITECTURE BRIEF</span>
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

import { useRef } from 'react';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Cloud as CloudIcon, Shield, Server, ArrowUpRight } from 'lucide-react';
import FloatingWhatsAppButton from '@/Components/FloatingWhatsAppButton';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';
import StudioHeader from '@/Components/Studio/StudioHeader';
import { openWhatsAppChat, STUDIO_PHONE } from '@/lib/whatsapp';

gsap.registerPlugin(ScrollTrigger);

export default function Cloud({ auth }) {
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
            title: "Automated Deployments & CI/CD",
            icon: CloudIcon,
            desc: "Zero-downtime rolling deployments, automated database migrations, and Git-driven production pipelines.",
            bullets: ["Zero-downtime deployments", "Automated migration safeguards", "Isolated staging environments"]
        },
        {
            title: "Zero-Data-Leak Server Hardening",
            icon: Shield,
            desc: "Hardened Linux VPS configurations with firewall rules, rate limiting, and SSL/TLS certificate auto-renewals.",
            bullets: ["DDoS & brute-force mitigation", "Automated daily off-site backups", "Strict firewall isolation"]
        },
        {
            title: "High-Throughput Redis & Queue Daemons",
            icon: Server,
            desc: "Dedicated Supervisor worker pools ensuring instant background execution of high-volume tasks.",
            bullets: ["Asynchronous worker daemons", "Sub-millisecond memory caching", "Auto-recovering job supervisors"]
        }
    ];

    return (
        <PublicLayout>
            <Head>
                <title>Cloud Infrastructure &amp; DevOps | Musoftwares</title>
                <meta name="description" content="Mission-critical cloud hosting, automated deployments, and server architecture engineered for high availability." />
            </Head>

            <FloatingWhatsAppButton phoneNumber={STUDIO_PHONE} defaultMessage="Hello Mahmoud, I want to discuss cloud hosting and infrastructure." />

            <div ref={mainRef} className="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white overflow-x-hidden pt-16 sm:pt-24 pb-24 sm:pb-36">
                
                {/* Hero Header */}
                <div className="reveal-section">
                    <StudioHeader
                        badge="DevOps & Infrastructure"
                        title={
                            <>
                                Mission-Critical Cloud. <br className="hidden sm:inline" />
                                <span className="text-[#748660]">Zero Downtime Architecture.</span>
                            </>
                        }
                        subtitle="We engineer dedicated cloud servers, auto-recovering background queues, and bulletproof deployment pipelines for enterprise workloads."
                    />

                    <div className="flex flex-col sm:flex-row gap-4 w-full sm:w-auto items-center justify-center font-mono text-xs mb-20 -mt-8">
                        <button
                            onClick={() => openWhatsAppChat("Hello Mahmoud, I want to discuss cloud infrastructure and DevOps.")}
                            className="bg-white text-black hover:bg-zinc-200 px-8 py-3.5 font-bold uppercase tracking-widest rtl:tracking-normal transition-all"
                        >
                            DISCUSS INFRASTRUCTURE ➔
                        </button>
                        <Link
                            href="/estimator"
                            className="border border-[#333333] hover:border-white text-zinc-300 hover:text-white px-8 py-3.5 font-bold uppercase tracking-widest rtl:tracking-normal transition-all"
                        >
                            {__('general.calculate_estimate') || 'CALCULATE ESTIMATE'}
                        </Link>
                    </div>
                </div>

                {/* Cloud Pillars Grid */}
                <section className="px-6 max-w-[1400px] mx-auto reveal-section mb-24">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        {features.map((item, idx) => {
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
                                            {item.bullets.map((bullet, bIdx) => (
                                                <li key={bIdx} className="flex items-center gap-2">
                                                    <span className="w-1.5 h-1.5 bg-[#748660] shrink-0"></span>
                                                    <span>{bullet}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                    <button
                                        onClick={() => openWhatsAppChat(`Hello Mahmoud, I want to discuss ${item.title}.`)}
                                        className="mt-8 text-xs font-mono font-bold text-white hover:text-[#748660] flex items-center gap-1 rtl:gap-reverse"
                                    >
                                        <span>CONSULT ARCHITECT</span>
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

import { useRef } from 'react';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import FloatingWhatsAppButton from '@/Components/FloatingWhatsAppButton';
import { ArrowUpRight, Cpu, LineChart, ShieldCheck } from 'lucide-react';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';
import { __ } from '@/lib/i18n';
import StudioHeader from '@/Components/Studio/StudioHeader';
import { openWhatsAppChat, STUDIO_PHONE } from '@/lib/whatsapp';

gsap.registerPlugin(ScrollTrigger);

export default function Company() {
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
                <title>Studio &amp; Engineering Firm | Musoftwares</title>
                <meta name="description" content="Learn about our boutique software engineering firm and our direct approach to solving complex technical challenges." />
            </Head>

            <FloatingWhatsAppButton phoneNumber={STUDIO_PHONE} defaultMessage="Hello Mahmoud, I'd like to learn more about your engineering studio." />

            <div ref={mainRef} className="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white overflow-x-hidden pt-16 sm:pt-24 pb-24 sm:pb-36">
                
                {/* Hero Section */}
                <div className="reveal-section">
                    <StudioHeader
                        badge="The Software Studio"
                        title={
                            <>
                                Architectural Precision. <br className="hidden sm:inline" />
                                <span className="text-[#748660]">Direct Craftsmanship.</span>
                            </>
                        }
                        subtitle="We are a specialized engineering firm focused on high-throughput ERP backbones, Meta Graph automations, and scalable cloud platforms."
                    />

                    <div className="flex flex-col sm:flex-row gap-4 w-full sm:w-auto items-center justify-center font-mono text-xs mb-20 -mt-8">
                        <Link href="/company/contact">
                            <button className="bg-white text-black hover:bg-zinc-200 px-8 py-3.5 font-bold uppercase tracking-widest transition-all">
                                INITIATE BRIEF ➔
                            </button>
                        </Link>
                        <button
                            onClick={() => openWhatsAppChat("Hello Mahmoud, I want to learn more about your studio.")}
                            className="border border-[#333333] hover:border-white text-zinc-300 hover:text-white px-8 py-3.5 font-bold uppercase tracking-widest transition-all"
                        >
                            TALK WITH ARCHITECT
                        </button>
                    </div>
                </div>

                {/* Core Values Grid */}
                <section className="px-6 max-w-[1400px] mx-auto reveal-section mb-24">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        
                        <div className="gsap-fade-up bg-[#161616] border border-[#262626] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#748660] transition-colors font-mono">
                            <div className="space-y-4">
                                <div className="w-12 h-12 bg-black border border-[#2B2B2B] flex items-center justify-center text-[#748660]">
                                    <Cpu className="w-6 h-6" />
                                </div>
                                <h3 className="text-xl font-bold text-white font-sans">
                                    Direct Architect Access
                                </h3>
                                <p className="text-xs sm:text-sm text-zinc-400 leading-relaxed font-mono">
                                    No non-technical account managers or communication silos. You communicate directly with the senior software architect.
                                </p>
                            </div>
                        </div>

                        <div className="gsap-fade-up bg-[#161616] border border-[#262626] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#748660] transition-colors font-mono">
                            <div className="space-y-4">
                                <div className="w-12 h-12 bg-black border border-[#2B2B2B] flex items-center justify-center text-[#748660]">
                                    <ShieldCheck className="w-6 h-6" />
                                </div>
                                <h3 className="text-xl font-bold text-white font-sans">
                                    Zero Technical Debt
                                </h3>
                                <p className="text-xs sm:text-sm text-zinc-400 leading-relaxed font-mono">
                                    We write clean, strictly typed, self-documenting code with comprehensive migrations, queues, and test coverage.
                                </p>
                            </div>
                        </div>

                        <div className="gsap-fade-up bg-[#161616] border border-[#262626] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#748660] transition-colors font-mono">
                            <div className="space-y-4">
                                <div className="w-12 h-12 bg-black border border-[#2B2B2B] flex items-center justify-center text-[#748660]">
                                    <LineChart className="w-6 h-6" />
                                </div>
                                <h3 className="text-xl font-bold text-white font-sans">
                                    Long-Term Continuity
                                </h3>
                                <p className="text-xs sm:text-sm text-zinc-400 leading-relaxed font-mono">
                                    From system launch to multi-year enterprise expansion, our architecture adapts smoothly to higher transaction volume.
                                </p>
                            </div>
                        </div>

                    </div>
                </section>

            </div>
        </PublicLayout>
    );
}

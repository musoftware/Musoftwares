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

            <div ref={mainRef} className="w-full bg-[#ffffff] text-[#1d1d1f] font-sans selection:bg-[#0071e3]/20 selection:text-[#0071e3] overflow-x-hidden pt-12 sm:pt-20 pb-24 sm:pb-36">
                
                {/* Hero Section */}
                <div className="reveal-section">
                    <StudioHeader
                        badge="The Software Studio"
                        title={
                            <>
                                Architectural Precision. <br className="hidden sm:inline" />
                                <span className="text-[#0071e3]">Direct Craftsmanship.</span>
                            </>
                        }
                        subtitle="We are a specialized engineering firm focused on high-throughput ERP backbones, Meta Graph automations, and scalable cloud platforms."
                    />

                    <div className="flex flex-col sm:flex-row gap-4 w-full sm:w-auto items-center justify-center text-xs mb-20 -mt-8">
                        <Link href="/company/contact">
                            <button className="bg-[#0071e3] hover:bg-[#0077ed] text-white px-8 py-3 rounded-[980px] font-semibold tracking-wide transition-all shadow-md shadow-blue-500/20 cursor-pointer">
                                INITIATE BRIEF ➔
                            </button>
                        </Link>
                        <button
                            onClick={() => openWhatsAppChat("Hello Mahmoud, I want to learn more about your studio.")}
                            className="border border-black/10 hover:border-black/30 bg-white text-[#1d1d1f] hover:bg-[#f5f5f7] px-8 py-3 rounded-[980px] font-semibold tracking-wide transition-all shadow-sm cursor-pointer"
                        >
                            TALK WITH ARCHITECT
                        </button>
                    </div>
                </div>

                {/* Core Values Grid */}
                <section className="px-6 max-w-[1400px] mx-auto reveal-section mb-24">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        
                        <div className="gsap-fade-up bg-white border border-black/5 rounded-[24px] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#0071e3]/40 hover:shadow-md transition-all shadow-sm">
                            <div className="space-y-4">
                                <div className="w-12 h-12 rounded-2xl bg-[#0071e3]/10 flex items-center justify-center text-[#0071e3]">
                                    <Cpu className="w-6 h-6" />
                                </div>
                                <h3 className="text-xl font-semibold text-[#1d1d1f] font-sans tracking-tight">
                                    Direct Architect Access
                                </h3>
                                <p className="text-sm text-[#1d1d1f]/60 leading-relaxed font-sans">
                                    No non-technical account managers or communication silos. You communicate directly with the senior software architect.
                                </p>
                            </div>
                        </div>

                        <div className="gsap-fade-up bg-white border border-black/5 rounded-[24px] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#0071e3]/40 hover:shadow-md transition-all shadow-sm">
                            <div className="space-y-4">
                                <div className="w-12 h-12 rounded-2xl bg-[#0071e3]/10 flex items-center justify-center text-[#0071e3]">
                                    <ShieldCheck className="w-6 h-6" />
                                </div>
                                <h3 className="text-xl font-semibold text-[#1d1d1f] font-sans tracking-tight">
                                    Zero Technical Debt
                                </h3>
                                <p className="text-sm text-[#1d1d1f]/60 leading-relaxed font-sans">
                                    We write clean, strictly typed, self-documenting code with comprehensive migrations, queues, and test coverage.
                                </p>
                            </div>
                        </div>

                        <div className="gsap-fade-up bg-white border border-black/5 rounded-[24px] p-8 sm:p-10 flex flex-col justify-between group hover:border-[#0071e3]/40 hover:shadow-md transition-all shadow-sm">
                            <div className="space-y-4">
                                <div className="w-12 h-12 rounded-2xl bg-[#0071e3]/10 flex items-center justify-center text-[#0071e3]">
                                    <LineChart className="w-6 h-6" />
                                </div>
                                <h3 className="text-xl font-semibold text-[#1d1d1f] font-sans tracking-tight">
                                    Long-Term Continuity
                                </h3>
                                <p className="text-sm text-[#1d1d1f]/60 leading-relaxed font-sans">
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

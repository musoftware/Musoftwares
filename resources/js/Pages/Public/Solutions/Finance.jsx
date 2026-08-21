import { useRef } from 'react';
import { Head } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Wallet, PieChart, Lock, ArrowRight, CheckCircle2 } from 'lucide-react';
import FloatingWhatsAppButton from '@/Components/FloatingWhatsAppButton';
import { Button } from '@/Components/ui/button';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';

gsap.registerPlugin(ScrollTrigger);

export default function Finance({ auth }) {
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

    const features = [
        {
            title: "Secure Transactions",
            icon: Lock,
            desc: "Bank-grade encryption for all financial operations, ensuring total security for you and your clients.",
            bullets: ["End-to-end encryption", "PCI-DSS compliance", "Fraud prevention"]
        },
        {
            title: "Ledger Management",
            icon: Wallet,
            desc: "Immutable ledger architectures that guarantee accurate tracking of every cent entering or leaving the system.",
            bullets: ["Double-entry accounting", "Wallet systems", "Automated reconciliation"]
        },
        {
            title: "Financial Analytics",
            icon: PieChart,
            desc: "Complex data visualization translating raw financial data into actionable business intelligence.",
            bullets: ["Real-time dashboards", "Predictive modeling", "Custom reporting"]
        }
    ];

    return (
        <PublicLayout auth={auth}>
            <Head>
                <title>FinTech Solutions | Musoftware</title>
                <meta name="description" content="Build secure, compliant fintech applications. We handle complex encryption and ledger management." />
            </Head>

            <FloatingWhatsAppButton phoneNumber={phoneNumber} defaultMessage="Hello Mahmoud, I want to discuss a FinTech Solution." />

            <div ref={mainRef} className="w-full bg-[#fcfcfc] text-[#111111] font-sans selection:bg-[#111111] selection:text-white overflow-x-hidden">
                
                {/* Hero Section */}
                <section className="pt-32 pb-24 lg:pt-48 lg:pb-32 px-6 lg:px-8 max-w-7xl mx-auto reveal-section border-b border-[#e5e5e5]">
                    <div className="max-w-4xl">
                        <div className="gsap-fade-up inline-flex items-center gap-2 px-3 py-1 border border-[#e5e5e5] text-xs font-semibold text-[#666666] tracking-widest uppercase mb-8 bg-white">
                            <span className="flex h-1.5 w-1.5 rounded-full bg-[#111111]"></span>
                            Solution
                        </div>
                        <h1 className="gsap-fade-up text-5xl lg:text-7xl font-extrabold text-[#111111] tracking-tight leading-[1.05] mb-6">
                            Financial Technology.
                        </h1>
                        <p className="gsap-fade-up text-xl text-[#666666] font-normal leading-relaxed max-w-2xl mb-10">
                            Build secure, compliant fintech applications. We handle the complex encryption, ledger management, and regulatory compliance required for financial software.
                        </p>
                        <Button 
                            onClick={() => openWhatsApp("Hello Mahmoud, I want to discuss a FinTech Solution.")}
                            className="gsap-fade-up bg-[#0071e3] text-white hover:bg-[#0077ed] rounded-[980px] px-8 py-4 text-sm font-semibold tracking-wide transition-all shadow-md shadow-blue-500/20 cursor-pointer"
                        >
                            Discuss Your Needs
                        </Button>
                    </div>
                </section>

                {/* Features Grid */}
                <section className="py-24 px-6 lg:px-8 max-w-7xl mx-auto reveal-section">
                    <div className="text-center mb-16">
                        <h2 className="gsap-fade-up text-4xl font-extrabold mb-4">FinTech Core</h2>
                        <p className="gsap-fade-up text-lg text-[#666666]">Engineered for trust and precision.</p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        {features.map((feature, idx) => (
                            <div key={idx} className="gsap-fade-up bg-white p-8 lg:p-10 border border-black/5 rounded-[24px] shadow-sm hover:shadow-md hover:border-[#0071e3]/40 transition-all flex flex-col h-full group">
                                <div className="w-14 h-14 bg-[#0071e3]/10 text-[#0071e3] transition-colors rounded-2xl flex items-center justify-center mb-8">
                                    <feature.icon className="w-6 h-6" />
                                </div>
                                <h3 className="text-2xl font-bold mb-4">{feature.title}</h3>
                                <p className="text-[#666666] leading-relaxed text-[15px] mb-8 flex-grow">
                                    {feature.desc}
                                </p>
                                <ul className="space-y-3 pt-6 border-t border-[#f4f4f5]">
                                    {feature.bullets.map((bullet, i) => (
                                        <li key={i} className="flex items-center gap-3 text-[#444444]">
                                            <CheckCircle2 className="w-4 h-4 text-[#0071e3]" />
                                            <span className="text-sm font-medium">{bullet}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ))}
                    </div>
                </section>

                {/* CTA Section */}
                <section className="py-24 bg-[#f5f5f7] text-[#1d1d1f] border-t border-black/5 text-center reveal-section px-6">
                    <div className="max-w-3xl mx-auto">
                        <h2 className="gsap-fade-up text-3xl md:text-5xl font-bold tracking-tight mb-6">
                            Ready to innovate in finance?
                        </h2>
                        <p className="gsap-fade-up text-lg text-[#1d1d1f]/70 mb-10 leading-relaxed">
                            Don't risk your reputation on poor architecture. Let's build a rock-solid financial platform.
                        </p>
                        <Button 
                            onClick={() => openWhatsApp("Hello Mahmoud, I need to build a FinTech application.")}
                            className="gsap-fade-up bg-[#0071e3] text-white hover:bg-[#0077ed] rounded-[980px] px-10 py-4 text-sm font-semibold tracking-wide transition-all shadow-md shadow-blue-500/20 flex items-center justify-center gap-3 mx-auto cursor-pointer"
                        >
                            Book a Consultation <ArrowRight className="w-4 h-4" />
                        </Button>
                    </div>
                </section>

            </div>
        </PublicLayout>
    );
}

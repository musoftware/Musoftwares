import { useRef } from 'react';
import { Head } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Server, Shield, Activity, ArrowRight, CheckCircle2 } from 'lucide-react';
import FloatingWhatsAppButton from '@/Components/FloatingWhatsAppButton';
import { Button } from '@/Components/ui/button';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';

gsap.registerPlugin(ScrollTrigger);

export default function Cloud({ auth }) {
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
            title: "High Availability",
            icon: Activity,
            desc: "Load-balanced servers ensuring 99.9% uptime for mission-critical software. Traffic is seamlessly routed to healthy nodes.",
            bullets: ["Auto-scaling groups", "Failover routing", "Global edge network"]
        },
        {
            title: "Automated Backups",
            icon: Server,
            desc: "Secure, off-site daily snapshots protect your data against catastrophic failure. Instantly restore databases to any point in time.",
            bullets: ["Encrypted snapshots", "Point-in-time recovery", "Geographic redundancy"]
        },
        {
            title: "Isolated Environments",
            icon: Shield,
            desc: "Dedicated resources and private networks for maximum security compliance. Your data never shares space with public instances.",
            bullets: ["Virtual Private Cloud", "Strict firewall rules", "DDoS mitigation"]
        }
    ];

    return (
        <PublicLayout auth={auth}>
            <Head>
                <title>Cloud Infrastructure | Musoftware</title>
                <meta name="description" content="Scalable, secure, and fully managed cloud hosting." />
            </Head>

            <FloatingWhatsAppButton phoneNumber={phoneNumber} defaultMessage="Hello Mahmoud, I want to discuss Cloud Infrastructure." />

            <div ref={mainRef} className="w-full bg-[#fcfcfc] text-[#111111] font-sans selection:bg-[#111111] selection:text-white overflow-x-hidden">
                
                {/* Hero Section */}
                <section className="pt-32 pb-24 lg:pt-48 lg:pb-32 px-6 lg:px-8 max-w-7xl mx-auto reveal-section border-b border-[#e5e5e5]">
                    <div className="max-w-4xl">
                        <div className="gsap-fade-up inline-flex items-center gap-2 px-3 py-1 border border-[#e5e5e5] text-xs font-semibold text-[#666666] tracking-widest uppercase mb-8 bg-white">
                            <span className="flex h-1.5 w-1.5 rounded-full bg-[#111111]"></span>
                            Platform
                        </div>
                        <h1 className="gsap-fade-up text-5xl lg:text-7xl font-extrabold text-[#111111] tracking-tight leading-[1.05] mb-6">
                            Cloud Infrastructure.
                        </h1>
                        <p className="gsap-fade-up text-xl text-[#666666] font-normal leading-relaxed max-w-2xl mb-10">
                            Scalable, secure, and fully managed cloud hosting. We provide the robust architecture required to run enterprise applications without downtime.
                        </p>
                        <Button 
                            onClick={() => openWhatsApp("Hello Mahmoud, I want to discuss Cloud Infrastructure.")}
                            className="gsap-fade-up bg-[#111111] text-white hover:bg-[#333333] rounded-xl px-8 py-6 text-sm font-bold uppercase tracking-wide transition-all"
                        >
                            Discuss Your Needs
                        </Button>
                    </div>
                </section>

                {/* Features Grid */}
                <section className="py-24 px-6 lg:px-8 max-w-7xl mx-auto reveal-section">
                    <div className="text-center mb-16">
                        <h2 className="gsap-fade-up text-4xl font-extrabold mb-4">Core Infrastructure</h2>
                        <p className="gsap-fade-up text-lg text-[#666666]">Engineered for reliability and scale.</p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        {features.map((feature, idx) => (
                            <div key={idx} className="gsap-fade-up bg-white p-8 lg:p-10 border border-[#e5e5e5] rounded-2xl shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] hover:border-[#111111] transition-all flex flex-col h-full group">
                                <div className="w-14 h-14 bg-[#f4f4f5] group-hover:bg-[#111111] transition-colors rounded-xl flex items-center justify-center mb-8">
                                    <feature.icon className="w-6 h-6 text-[#111111] group-hover:text-white transition-colors" />
                                </div>
                                <h3 className="text-2xl font-bold mb-4">{feature.title}</h3>
                                <p className="text-[#666666] leading-relaxed text-[15px] mb-8 flex-grow">
                                    {feature.desc}
                                </p>
                                <ul className="space-y-3 pt-6 border-t border-[#f4f4f5]">
                                    {feature.bullets.map((bullet, i) => (
                                        <li key={i} className="flex items-center gap-3 text-[#444444]">
                                            <CheckCircle2 className="w-4 h-4 text-[#111111]" />
                                            <span className="text-sm font-medium">{bullet}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ))}
                    </div>
                </section>

                {/* CTA Section */}
                <section className="py-32 bg-[#111111] text-white text-center reveal-section px-6">
                    <div className="max-w-3xl mx-auto">
                        <h2 className="gsap-fade-up text-4xl md:text-5xl font-extrabold mb-6">
                            Ready to scale your architecture?
                        </h2>
                        <p className="gsap-fade-up text-xl text-[#a3a3a3] mb-12 leading-relaxed">
                            Stop worrying about server maintenance. Let us manage your cloud infrastructure.
                        </p>
                        <Button 
                            onClick={() => openWhatsApp("Hello Mahmoud, I need to scale my cloud infrastructure.")}
                            className="gsap-fade-up bg-white text-[#111111] hover:bg-[#e5e5e5] rounded-xl px-10 py-7 text-sm font-bold tracking-wide uppercase transition-all flex items-center justify-center gap-3 mx-auto"
                        >
                            Book a Consultation <ArrowRight className="w-4 h-4" />
                        </Button>
                    </div>
                </section>

            </div>
        </PublicLayout>
    );
}

import { useRef } from 'react';
import { Head } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Briefcase, Building2, Server, GraduationCap, Code2, ShieldCheck, ArrowRight, CheckCircle2, MessageSquare } from 'lucide-react';
import FloatingWhatsAppButton from '@/Components/FloatingWhatsAppButton';
import { Button } from '@/Components/ui/button';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';

gsap.registerPlugin(ScrollTrigger);

export default function Solutions() {
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

    const solutions = [
        {
            title: "ERP & Business Systems",
            icon: Building2,
            desc: "Stop relying on chaotic spreadsheets. We build custom Enterprise Resource Planning systems tailored to your unique workflows, managing inventory, sales, and HR.",
            features: ["Custom workflows", "Role-based access", "Automated reporting"]
        },
        {
            title: "SaaS & Subscription Platforms",
            icon: Briefcase,
            desc: "Turn your idea into a scalable business. We architect SaaS applications that handle multi-tenant data isolation, recurring billing, and user management.",
            features: ["Multi-tenant architecture", "Stripe/Paymob integration", "User dashboards"]
        },
        {
            title: "E-Learning Academies",
            icon: GraduationCap,
            desc: "Launch your own digital academy. We create secure, video-centric platforms for selling courses with progress tracking and interactive exams.",
            features: ["Video protection", "Interactive quizzes", "Certificates generation"]
        },
        {
            title: "Process Automation",
            icon: Code2,
            desc: "Automate the boring stuff. We develop custom scripts and background workers to connect different APIs, sync data, and run scheduled tasks.",
            features: ["API integrations", "Scheduled cron jobs", "Data syncing"]
        },
        {
            title: "E-Commerce Engines",
            icon: Server,
            desc: "Sell products seamlessly. High-performance, SEO-optimized e-commerce platforms designed to handle massive traffic and complex product variations.",
            features: ["High concurrency", "Cart management", "Payment gateways"]
        },
        {
            title: "Security & Audits",
            icon: ShieldCheck,
            desc: "Protect your data and reputation. We review existing codebases, patch vulnerabilities, and restructure applications to withstand heavy attacks.",
            features: ["Code reviews", "Vulnerability patching", "Performance optimization"]
        }
    ];

    return (
        <PublicLayout>
            <Head>
                <title>Solutions | Musoftware</title>
                <meta name="description" content="Custom software solutions, ERP systems, SaaS platforms, and business automation engineered for scale." />
            </Head>

            <FloatingWhatsAppButton phoneNumber={phoneNumber} defaultMessage="Hello Mahmoud, I want to discuss a software solution." />

            <div ref={mainRef} className="w-full bg-[#fcfcfc] text-[#111111] font-sans selection:bg-[#111111] selection:text-white">
                
                {/* Hero Section */}
                <section className="pt-32 pb-24 px-6 lg:px-8 max-w-7xl mx-auto reveal-section border-b border-[#e5e5e5]">
                    <div className="max-w-4xl">
                        <div className="gsap-fade-up inline-flex items-center gap-2 px-3 py-1 border border-[#e5e5e5] text-xs font-semibold text-[#666666] tracking-widest uppercase mb-8 bg-white">
                            <span className="flex h-1.5 w-1.5 rounded-full bg-[#111111]"></span>
                            Custom Solutions
                        </div>
                        <h1 className="gsap-fade-up text-5xl lg:text-7xl font-extrabold text-[#111111] tracking-tight leading-[1.05] mb-6">
                            Software Built for Your Business.
                        </h1>
                        <p className="gsap-fade-up text-xl text-[#666666] font-normal leading-relaxed max-w-2xl mb-10">
                            We don't sell templates. We engineer robust, scalable systems that solve complex operational problems and drive revenue.
                        </p>
                        <Button 
                            onClick={() => openWhatsApp("Hello Mahmoud, I need a custom software solution.")}
                            className="gsap-fade-up bg-[#111111] text-white hover:bg-[#333333] rounded-xl px-8 py-6 text-sm font-bold uppercase tracking-wide transition-all"
                        >
                            Discuss Your Project
                        </Button>
                    </div>
                </section>

                {/* Solutions Grid */}
                <section className="py-24 px-6 lg:px-8 max-w-7xl mx-auto reveal-section">
                    <div className="text-center mb-16">
                        <h2 className="gsap-fade-up text-4xl font-extrabold mb-4">Core Competencies</h2>
                        <p className="gsap-fade-up text-lg text-[#666666]">What we excel at building.</p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        {solutions.map((sol, idx) => (
                            <div key={idx} className="gsap-fade-up bg-white p-8 lg:p-10 border border-[#e5e5e5] rounded-2xl shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] hover:border-[#111111] transition-all flex flex-col h-full group">
                                <div className="w-14 h-14 bg-[#f4f4f5] group-hover:bg-[#111111] transition-colors rounded-xl flex items-center justify-center mb-8">
                                    <sol.icon className="w-6 h-6 text-[#111111] group-hover:text-white transition-colors" />
                                </div>
                                <h3 className="text-2xl font-bold mb-4">{sol.title}</h3>
                                <p className="text-[#666666] leading-relaxed text-[15px] mb-8 flex-grow">
                                    {sol.desc}
                                </p>
                                <ul className="space-y-3 pt-6 border-t border-[#f4f4f5]">
                                    {sol.features.map((feature, i) => (
                                        <li key={i} className="flex items-center gap-3 text-[#444444]">
                                            <CheckCircle2 className="w-4 h-4 text-[#111111]" />
                                            <span className="text-sm font-medium">{feature}</span>
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
                            Have a complex problem?
                        </h2>
                        <p className="gsap-fade-up text-xl text-[#a3a3a3] mb-12 leading-relaxed">
                            Let's map out the architecture. The consultation is free, and we speak plain English.
                        </p>
                        <Button 
                            onClick={() => openWhatsApp("Hello Mahmoud, I have a complex problem and need a technical consultation.")}
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

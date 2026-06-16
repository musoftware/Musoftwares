import { useRef } from 'react';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { LayoutDashboard, Workflow, Globe, Monitor, ArrowRight } from 'lucide-react';
import FloatingWhatsAppButton from '@/Components/FloatingWhatsAppButton';
import { Button } from '@/Components/ui/button';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';

gsap.registerPlugin(ScrollTrigger);

export default function Platforms() {
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

    return (
        <PublicLayout>
            <Head>
                <title>Platforms | Musoftware</title>
                <meta name="description" content="Explore the platforms we build, from internal dashboards to high-performance enterprise systems." />
            </Head>

            <FloatingWhatsAppButton phoneNumber={phoneNumber} defaultMessage="Hello Mahmoud, I want to discuss building a platform." />

            <div ref={mainRef} className="w-full bg-[#fcfcfc] text-[#111111] font-sans selection:bg-[#111111] selection:text-white overflow-x-hidden">
                
                <section className="pt-32 pb-24 lg:pt-48 lg:pb-32 px-6 lg:px-8 max-w-7xl mx-auto reveal-section border-b border-[#e5e5e5]">
                    <div className="max-w-4xl mb-20">
                        <div className="gsap-fade-up inline-flex items-center gap-2 px-3 py-1 border border-[#e5e5e5] text-xs font-semibold text-[#666666] tracking-widest uppercase mb-8 bg-white">
                            <span className="flex h-1.5 w-1.5 rounded-full bg-[#111111]"></span>
                            Platforms
                        </div>
                        <h1 className="gsap-fade-up text-5xl lg:text-7xl font-extrabold text-[#111111] tracking-tight leading-[1.05] mb-6">
                            Scalable Digital Platforms.
                        </h1>
                        <p className="gsap-fade-up text-xl text-[#666666] font-normal leading-relaxed max-w-2xl">
                            We don't just write code; we build robust platforms that serve as the operational backbone of your business. Whether you need an internal dashboard or a public-facing SaaS, we have the architecture ready.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div className="gsap-fade-up p-12 bg-white border border-[#e5e5e5] rounded-2xl shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] transition-colors group hover:border-[#111111]">
                            <LayoutDashboard className="w-10 h-10 text-[#111111] mb-8" strokeWidth={1.5} />
                            <h3 className="text-2xl font-bold text-[#111111] mb-4">Internal Admin Dashboards</h3>
                            <p className="text-[#666666] leading-relaxed mb-8">
                                Give your team a central hub to manage data, view analytics, and control operations without juggling multiple tools.
                            </p>
                            <ul className="space-y-4 mb-10">
                                <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full mr-4"></div>Data visualizations & charts</li>
                                <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full mr-4"></div>Role-based permissions</li>
                                <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full mr-4"></div>Real-time data updates</li>
                            </ul>
                            <button onClick={() => openWhatsApp("I'm interested in an Internal Dashboard.")} className="inline-flex items-center gap-2 text-xs font-bold text-[#111111] uppercase tracking-widest hover:text-[#666666] transition-colors">
                                Discuss this platform <ArrowRight className="w-4 h-4" />
                            </button>
                        </div>
                        
                        <div className="gsap-fade-up p-12 bg-white border border-[#e5e5e5] rounded-2xl shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] transition-colors group hover:border-[#111111]">
                            <Workflow className="w-10 h-10 text-[#111111] mb-8" strokeWidth={1.5} />
                            <h3 className="text-2xl font-bold text-[#111111] mb-4">Workflow Automation</h3>
                            <p className="text-[#666666] leading-relaxed mb-8">
                                Replace manual data entry with automated background workers that sync APIs, process queues, and send notifications.
                            </p>
                            <ul className="space-y-4 mb-10">
                                <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full mr-4"></div>Third-party API integrations</li>
                                <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full mr-4"></div>Scheduled background tasks</li>
                                <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full mr-4"></div>Automated SMS & Email triggers</li>
                            </ul>
                            <button onClick={() => openWhatsApp("I'm interested in Workflow Automation.")} className="inline-flex items-center gap-2 text-xs font-bold text-[#111111] uppercase tracking-widest hover:text-[#666666] transition-colors">
                                Discuss this platform <ArrowRight className="w-4 h-4" />
                            </button>
                        </div>

                        <div className="gsap-fade-up p-12 bg-white border border-[#e5e5e5] rounded-2xl shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] transition-colors group hover:border-[#111111]">
                            <Globe className="w-10 h-10 text-[#111111] mb-8" strokeWidth={1.5} />
                            <h3 className="text-2xl font-bold text-[#111111] mb-4">SaaS Applications</h3>
                            <p className="text-[#666666] leading-relaxed mb-8">
                                Launch your own subscription-based software. We handle the complex multi-tenant architecture and billing engines.
                            </p>
                            <ul className="space-y-4 mb-10">
                                <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full mr-4"></div>Subscription & billing logic</li>
                                <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full mr-4"></div>Tenant data isolation</li>
                                <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full mr-4"></div>Custom user portals</li>
                            </ul>
                            <button onClick={() => openWhatsApp("I'm interested in building a SaaS Application.")} className="inline-flex items-center gap-2 text-xs font-bold text-[#111111] uppercase tracking-widest hover:text-[#666666] transition-colors">
                                Discuss this platform <ArrowRight className="w-4 h-4" />
                            </button>
                        </div>

                        <div className="gsap-fade-up p-12 bg-white border border-[#e5e5e5] rounded-2xl shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] transition-colors group hover:border-[#111111]">
                            <Monitor className="w-10 h-10 text-[#111111] mb-8" strokeWidth={1.5} />
                            <h3 className="text-2xl font-bold text-[#111111] mb-4">Customer Portals</h3>
                            <p className="text-[#666666] leading-relaxed mb-8">
                                Give your clients a professional interface to track their orders, download invoices, and submit support tickets.
                            </p>
                            <ul className="space-y-4 mb-10">
                                <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full mr-4"></div>Secure client authentication</li>
                                <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full mr-4"></div>Invoice & document sharing</li>
                                <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full mr-4"></div>Support ticket systems</li>
                            </ul>
                            <button onClick={() => openWhatsApp("I'm interested in building a Customer Portal.")} className="inline-flex items-center gap-2 text-xs font-bold text-[#111111] uppercase tracking-widest hover:text-[#666666] transition-colors">
                                Discuss this platform <ArrowRight className="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </section>
                
                {/* CTA Section */}
                <section className="py-32 bg-[#111111] text-white text-center reveal-section px-6">
                    <div className="max-w-3xl mx-auto">
                        <h2 className="gsap-fade-up text-4xl md:text-5xl font-extrabold mb-6">
                            Ready to build your platform?
                        </h2>
                        <p className="gsap-fade-up text-xl text-[#a3a3a3] mb-12 leading-relaxed">
                            Stop using fragmented tools. Let's build a unified platform that scales with your business.
                        </p>
                        <Button 
                            onClick={() => openWhatsApp("Hello Mahmoud, I want to discuss building a platform for my business.")}
                            className="gsap-fade-up bg-white text-[#111111] hover:bg-[#e5e5e5] rounded-xl px-10 py-7 text-sm font-bold tracking-wide uppercase transition-all flex items-center justify-center gap-3 mx-auto"
                        >
                            Start Your Project <ArrowRight className="w-4 h-4" />
                        </Button>
                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}

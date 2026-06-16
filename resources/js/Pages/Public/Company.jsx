import { useRef } from 'react';
import { Head } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import FloatingWhatsAppButton from '@/Components/FloatingWhatsAppButton';
import { Button } from '@/Components/ui/button';
import { ArrowRight, Code2, Cpu, LineChart, MessageSquare } from 'lucide-react';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';

gsap.registerPlugin(ScrollTrigger);

export default function Company() {
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
                <title>Company | Musoftware</title>
                <meta name="description" content="Learn about our boutique software engineering firm and our direct approach to solving complex technical challenges." />
            </Head>

            <FloatingWhatsAppButton phoneNumber={phoneNumber} defaultMessage="Hello Mahmoud, I'd like to learn more about your services." />

            <div ref={mainRef} className="w-full bg-[#fcfcfc] text-[#111111] font-sans selection:bg-[#111111] selection:text-white overflow-x-hidden">
                
                {/* Hero Section */}
                <section className="pt-32 pb-24 lg:pt-48 lg:pb-32 px-6 lg:px-8 max-w-7xl mx-auto reveal-section border-b border-[#e5e5e5]">
                    <div className="max-w-4xl">
                        <div className="gsap-fade-up inline-flex items-center gap-2 px-3 py-1 border border-[#e5e5e5] text-xs font-semibold text-[#666666] tracking-widest uppercase mb-8 bg-white">
                            <span className="flex h-1.5 w-1.5 rounded-full bg-[#111111]"></span>
                            The Company
                        </div>
                        <h1 className="gsap-fade-up text-5xl lg:text-7xl font-extrabold text-[#111111] tracking-tight leading-[1.05] mb-6">
                            Boutique Engineering. Direct Communication.
                        </h1>
                        <p className="gsap-fade-up text-xl text-[#666666] font-normal leading-relaxed max-w-2xl mb-10">
                            We are not a massive agency where you get lost in the shuffle. Led by Eng. Mahmoud, we provide dedicated technical expertise tailored specifically to ambitious businesses.
                        </p>
                        <Button 
                            onClick={() => openWhatsApp("Hello Mahmoud, I'm interested in working with you.")}
                            className="gsap-fade-up bg-[#111111] text-white hover:bg-[#333333] rounded-xl px-8 py-6 text-sm font-bold uppercase tracking-wide transition-all"
                        >
                            Talk to the Lead Engineer
                        </Button>
                    </div>
                </section>

                {/* Our Philosophy Section */}
                <section className="py-24 lg:py-32 px-6 lg:px-8 bg-white border-b border-[#e5e5e5] reveal-section">
                    <div className="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-24">
                        
                        {/* Left Column */}
                        <div className="lg:col-span-5 pt-12 lg:pt-0">
                            <h2 className="gsap-fade-up text-3xl font-extrabold text-[#111111] mb-8">Why work with us?</h2>
                            <p className="gsap-fade-up text-lg text-[#666666] leading-relaxed mb-6">
                                Most software agencies focus on churning out projects quickly using pre-made templates. We do things differently.
                            </p>
                            <p className="gsap-fade-up text-lg text-[#666666] leading-relaxed mb-8">
                                We believe in deep, structural engineering. We write clean code, build scalable architectures, and ensure that what we build today won't break tomorrow. When you hire us, you're not just getting code—you're getting technical partnership.
                            </p>

                            <div className="gsap-fade-up p-8 mt-12 bg-[#fafafa] border border-[#e5e5e5] rounded-2xl">
                                <blockquote className="text-xl font-medium text-[#111111] leading-relaxed mb-6 italic">
                                    "Good architecture is about making complex systems simple, not making simple systems complex."
                                </blockquote>
                                <p className="text-xs font-bold text-[#888888] uppercase tracking-widest">— Eng. Mahmoud</p>
                            </div>
                        </div>

                        {/* Right Column: Values */}
                        <div className="lg:col-span-7 border-t lg:border-t-0 lg:border-l border-[#e5e5e5] pt-12 lg:pt-0 lg:pl-16">
                            <h2 className="gsap-fade-up text-xs font-bold text-[#888888] tracking-widest uppercase mb-12">Our Core Values</h2>
                            
                            <div className="space-y-12">
                                <div className="gsap-fade-up flex gap-6">
                                    <div className="w-12 h-12 bg-[#f4f4f5] rounded-xl flex items-center justify-center shrink-0">
                                        <Code2 className="w-5 h-5 text-[#111111]" />
                                    </div>
                                    <div>
                                        <h3 className="text-xl font-bold text-[#111111] mb-2">Technical Excellence</h3>
                                        <p className="text-[#666666] leading-relaxed">No shortcuts. We use modern, enterprise-grade technology stacks to ensure your software is fast, secure, and easily maintainable.</p>
                                    </div>
                                </div>
                                <div className="gsap-fade-up flex gap-6">
                                    <div className="w-12 h-12 bg-[#f4f4f5] rounded-xl flex items-center justify-center shrink-0">
                                        <MessageSquare className="w-5 h-5 text-[#111111]" />
                                    </div>
                                    <div>
                                        <h3 className="text-xl font-bold text-[#111111] mb-2">Direct Communication</h3>
                                        <p className="text-[#666666] leading-relaxed">You speak directly with the people building your software. No account managers playing telephone with your requirements.</p>
                                    </div>
                                </div>
                                <div className="gsap-fade-up flex gap-6">
                                    <div className="w-12 h-12 bg-[#f4f4f5] rounded-xl flex items-center justify-center shrink-0">
                                        <LineChart className="w-5 h-5 text-[#111111]" />
                                    </div>
                                    <div>
                                        <h3 className="text-xl font-bold text-[#111111] mb-2">Business-Driven Solutions</h3>
                                        <p className="text-[#666666] leading-relaxed">We don't build features just for the sake of it. Everything we engineer is designed to solve a specific operational problem or generate ROI.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </section>

                {/* Process Section */}
                <section className="py-24 lg:py-32 px-6 lg:px-8 bg-[#fafafa] reveal-section">
                    <div className="max-w-7xl mx-auto">
                        <div className="max-w-3xl mb-16">
                            <h2 className="gsap-fade-up text-xs font-bold text-[#888888] tracking-widest uppercase mb-4">How we work</h2>
                            <h3 className="gsap-fade-up text-4xl lg:text-5xl font-extrabold text-[#111111] tracking-tight">A proven delivery process.</h3>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div className="gsap-fade-up relative p-8 bg-white border border-[#e5e5e5] rounded-2xl transition-colors hover:border-[#111111]">
                                <span className="text-5xl font-bold text-[#f0f0f0] absolute top-6 right-6 font-mono select-none">1</span>
                                <h4 className="text-xl font-bold text-[#111111] mb-4 mt-8 relative z-10">Discovery</h4>
                                <p className="text-[#666666] text-sm leading-relaxed relative z-10">
                                    We start with a deep dive into your business logic, identifying the bottlenecks and defining the technical requirements.
                                </p>
                            </div>
                            
                            <div className="gsap-fade-up relative p-8 bg-white border border-[#e5e5e5] rounded-2xl transition-colors hover:border-[#111111]">
                                <span className="text-5xl font-bold text-[#f0f0f0] absolute top-6 right-6 font-mono select-none">2</span>
                                <h4 className="text-xl font-bold text-[#111111] mb-4 mt-8 relative z-10">Architecture</h4>
                                <p className="text-[#666666] text-sm leading-relaxed relative z-10">
                                    We design the database schema, system flow, and user interface, ensuring everything is scalable from day one.
                                </p>
                            </div>
                            
                            <div className="gsap-fade-up relative p-8 bg-white border border-[#e5e5e5] rounded-2xl transition-colors hover:border-[#111111]">
                                <span className="text-5xl font-bold text-[#f0f0f0] absolute top-6 right-6 font-mono select-none">3</span>
                                <h4 className="text-xl font-bold text-[#111111] mb-4 mt-8 relative z-10">Engineering</h4>
                                <p className="text-[#666666] text-sm leading-relaxed relative z-10">
                                    We write clean, documented code using modern frameworks. You get regular updates and transparency throughout the build.
                                </p>
                            </div>
                            
                            <div className="gsap-fade-up relative p-8 bg-white border border-[#e5e5e5] rounded-2xl transition-colors hover:border-[#111111]">
                                <span className="text-5xl font-bold text-[#f0f0f0] absolute top-6 right-6 font-mono select-none">4</span>
                                <h4 className="text-xl font-bold text-[#111111] mb-4 mt-8 relative z-10">Deployment</h4>
                                <p className="text-[#666666] text-sm leading-relaxed relative z-10">
                                    We securely deploy your application to production servers, configure the domains, and hand over the keys.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {/* CTA Section */}
                <section className="py-32 bg-[#111111] text-white text-center reveal-section px-6">
                    <div className="max-w-3xl mx-auto">
                        <h2 className="gsap-fade-up text-4xl md:text-5xl font-extrabold mb-6">
                            Let's build something great.
                        </h2>
                        <p className="gsap-fade-up text-xl text-[#a3a3a3] mb-12 leading-relaxed">
                            Reach out today to discuss your vision. We're ready to architect your next big move.
                        </p>
                        <Button 
                            onClick={() => openWhatsApp("Hello Mahmoud, I'd like to start a project with Musoftware.")}
                            className="gsap-fade-up bg-white text-[#111111] hover:bg-[#e5e5e5] rounded-xl px-10 py-7 text-sm font-bold tracking-wide uppercase transition-all flex items-center justify-center gap-3 mx-auto"
                        >
                            Contact Us on WhatsApp <ArrowRight className="w-4 h-4" />
                        </Button>
                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}

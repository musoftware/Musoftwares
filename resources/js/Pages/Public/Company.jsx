import { useRef } from 'react';
import { Head } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { __ } from '@/lib/i18n';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';

gsap.registerPlugin(ScrollTrigger);

export default function Company() {
    const mainRef = useRef(null);

    useGSAP(() => {
        const sections = gsap.utils.toArray('.reveal-section');
        sections.forEach((section) => {
            const elements = section.querySelectorAll('.gsap-fade-up');
            gsap.fromTo(elements, 
                { opacity: 0, y: 20 },
                {
                    opacity: 1, 
                    y: 0, 
                    duration: 0.7,
                    stagger: 0.08,
                    ease: "power2.out",
                    scrollTrigger: {
                        trigger: section,
                        start: "top 85%",
                        toggleActions: "play none none reverse"
                    }
                }
            );
        });

        document.documentElement.style.scrollSnapType = 'y proximity';
        document.documentElement.style.overflowY = 'scroll';
        
        return () => {
            document.documentElement.style.scrollSnapType = '';
            document.documentElement.style.overflowY = '';
        };
    }, { scope: mainRef });

    return (
        <PublicLayout>
            <Head>
                <title>{__('frontend.company.meta_title')}</title>
                <meta name="description" content={__('frontend.company.meta_description')} />
            </Head>

            <style>{`
                .snap-section { scroll-snap-align: start; }
                html { scroll-behavior: smooth; }
            `}</style>

            <div ref={mainRef} className="w-full bg-[#fafafa] text-[#111111] font-sans selection:bg-[#111111] selection:text-white overflow-x-hidden">
                
                {/* Hero Section */}
                <section className="snap-section pt-32 pb-24 lg:pt-48 lg:pb-32 bg-[#fafafa] reveal-section border-b border-[#e5e5e5]">
                    <div className="max-w-[80rem] mx-auto px-6 lg:px-8">
                        <div className="max-w-4xl">
                            <div className="gsap-fade-up inline-flex items-center gap-2 px-3 py-1 border border-[#e5e5e5] text-xs font-semibold text-[#666666] tracking-widest uppercase mb-8 bg-white">
                                <span className="flex h-1.5 w-1.5 rounded-full bg-[#111111]"></span>
                                Company
                            </div>
                            <h1 className="gsap-fade-up text-5xl lg:text-7xl font-bold text-[#111111] tracking-tight leading-[1.05] mb-6">
                                {__('frontend.company.title')}
                            </h1>
                            <p className="gsap-fade-up text-xl text-[#666666] font-normal leading-relaxed max-w-2xl">
                                {__('frontend.company.subtitle')}
                            </p>
                        </div>
                    </div>
                </section>

                {/* History Section */}
                <section className="snap-section py-24 lg:py-32 bg-white border-b border-[#e5e5e5] reveal-section">
                    <div className="max-w-[80rem] mx-auto px-6 lg:px-8">
                        <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-24">
                            
                            {/* Left Column: Timeline / Stats */}
                            <div className="lg:col-span-4 border-t lg:border-t-0 lg:border-r border-[#e5e5e5] pt-12 lg:pt-0 lg:pr-12">
                                <h2 className="gsap-fade-up text-xs font-bold text-[#888888] tracking-widest uppercase mb-12">{__('frontend.company.timeline_title')}</h2>
                                
                                <div className="space-y-12">
                                    <div className="gsap-fade-up">
                                        <h3 className="text-2xl font-bold text-[#111111] mb-2">{__('frontend.company.y2013')}</h3>
                                        <p className="text-[#666666] text-sm leading-relaxed">{__('frontend.company.y2013_desc')}</p>
                                    </div>
                                    <div className="gsap-fade-up">
                                        <h3 className="text-2xl font-bold text-[#111111] mb-2">{__('frontend.company.y2014')}</h3>
                                        <p className="text-[#666666] text-sm leading-relaxed">{__('frontend.company.y2014_desc')}</p>
                                    </div>
                                    <div className="gsap-fade-up">
                                        <h3 className="text-2xl font-bold text-[#111111] mb-2">{__('frontend.company.today')}</h3>
                                        <p className="text-[#666666] text-sm leading-relaxed">{__('frontend.company.today_desc')}</p>
                                    </div>
                                </div>
                            </div>

                            {/* Right Column: Narrative */}
                            <div className="lg:col-span-8 pt-12 lg:pt-0">
                                <h2 className="gsap-fade-up text-3xl font-bold text-[#111111] mb-8">{__('frontend.company.background_title')}</h2>
                                
                                <div className="prose prose-lg text-[#666666] font-normal max-w-none">
                                    <p className="gsap-fade-up mb-6 leading-relaxed">{__('frontend.company.p1')}</p>
                                    <p className="gsap-fade-up mb-6 leading-relaxed">{__('frontend.company.p2')}</p>
                                    
                                    <ul className="gsap-fade-up list-none pl-0 mb-8 space-y-4">
                                        <li className="flex items-start gap-3"><div className="w-1.5 h-1.5 rounded-full bg-[#111111] mt-2 flex-shrink-0"></div><span>{__('frontend.company.l1')}</span></li>
                                        <li className="flex items-start gap-3"><div className="w-1.5 h-1.5 rounded-full bg-[#111111] mt-2 flex-shrink-0"></div><span>{__('frontend.company.l2')}</span></li>
                                        <li className="flex items-start gap-3"><div className="w-1.5 h-1.5 rounded-full bg-[#111111] mt-2 flex-shrink-0"></div><span>{__('frontend.company.l3')}</span></li>
                                        <li className="flex items-start gap-3"><div className="w-1.5 h-1.5 rounded-full bg-[#111111] mt-2 flex-shrink-0"></div><span>{__('frontend.company.l4')}</span></li>
                                        <li className="flex items-start gap-3"><div className="w-1.5 h-1.5 rounded-full bg-[#111111] mt-2 flex-shrink-0"></div><span>{__('frontend.company.l5')}</span></li>
                                    </ul>

                                    <p className="gsap-fade-up mb-6 leading-relaxed">{__('frontend.company.p3')}</p>

                                    <div className="gsap-fade-up p-10 mt-16 bg-[#fafafa] border border-[#e5e5e5]">
                                        <blockquote className="text-xl font-normal text-[#111111] leading-relaxed mb-6 italic">
                                            "{__('frontend.company.quote')}"
                                        </blockquote>
                                        <p className="text-xs font-bold text-[#888888] uppercase tracking-widest">{__('frontend.company.ceo')}</p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </section>

                {/* Process Section */}
                <section className="snap-section py-24 lg:py-32 bg-[#fafafa] reveal-section">
                    <div className="max-w-[80rem] mx-auto px-6 lg:px-8">
                        <div className="max-w-3xl mb-16">
                            <h2 className="gsap-fade-up text-xs font-bold text-[#888888] tracking-widest uppercase mb-4">{__('frontend.company.how_we_work')}</h2>
                            <h3 className="gsap-fade-up text-4xl lg:text-5xl font-bold text-[#111111] tracking-tight">{__('frontend.company.process_title')}</h3>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-px bg-[#e5e5e5] border border-[#e5e5e5]">
                            <div className="gsap-fade-up relative p-10 bg-white transition-colors hover:bg-[#fafafa]">
                                <span className="text-4xl font-bold text-[#f0f0f0] absolute top-8 right-8 font-mono">0</span>
                                <h4 className="text-lg font-bold text-[#111111] mb-4 mt-8">{__('frontend.company.s0')}</h4>
                                <p className="text-[#666666] text-sm leading-relaxed">
                                    {__('frontend.company.s0_desc')}
                                </p>
                            </div>
                            
                            <div className="gsap-fade-up relative p-10 bg-white transition-colors hover:bg-[#fafafa]">
                                <span className="text-4xl font-bold text-[#f0f0f0] absolute top-8 right-8 font-mono">1</span>
                                <h4 className="text-lg font-bold text-[#111111] mb-4 mt-8">{__('frontend.company.s1')}</h4>
                                <p className="text-[#666666] text-sm leading-relaxed">
                                    {__('frontend.company.s1_desc')}
                                </p>
                            </div>
                            
                            <div className="gsap-fade-up relative p-10 bg-white transition-colors hover:bg-[#fafafa]">
                                <span className="text-4xl font-bold text-[#f0f0f0] absolute top-8 right-8 font-mono">2</span>
                                <h4 className="text-lg font-bold text-[#111111] mb-4 mt-8">{__('frontend.company.s2')}</h4>
                                <p className="text-[#666666] text-sm leading-relaxed">
                                    {__('frontend.company.s2_desc')}
                                </p>
                            </div>
                            
                            <div className="gsap-fade-up relative p-10 bg-white transition-colors hover:bg-[#fafafa]">
                                <span className="text-4xl font-bold text-[#f0f0f0] absolute top-8 right-8 font-mono">3</span>
                                <h4 className="text-lg font-bold text-[#111111] mb-4 mt-8">{__('frontend.company.s3')}</h4>
                                <p className="text-[#666666] text-sm leading-relaxed">
                                    {__('frontend.company.s3_desc')}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

            </div>
        </PublicLayout>
    );
}

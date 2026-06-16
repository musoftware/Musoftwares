import { useRef } from 'react';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { LayoutDashboard, Workflow, Globe, Monitor, ArrowRight } from 'lucide-react';
import { __ } from '@/lib/i18n';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';

gsap.registerPlugin(ScrollTrigger);

export default function Platforms() {
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
                <title>{__('frontend.platforms.meta_title')}</title>
                <meta name="description" content={__('frontend.platforms.meta_description')} />
            </Head>

            <style>{`
                .snap-section { scroll-snap-align: start; }
                html { scroll-behavior: smooth; }
            `}</style>

            <div ref={mainRef} className="w-full bg-[#fafafa] text-[#111111] font-sans selection:bg-[#111111] selection:text-white overflow-x-hidden">
                
                <section className="snap-section pt-32 pb-24 lg:pt-48 lg:pb-32 bg-[#fafafa] border-b border-[#e5e5e5] reveal-section">
                    <div className="max-w-[80rem] mx-auto px-6 lg:px-8">
                        <div className="max-w-3xl mb-20">
                            <div className="gsap-fade-up inline-flex items-center gap-2 px-3 py-1 border border-[#e5e5e5] text-xs font-semibold text-[#666666] tracking-widest uppercase mb-8 bg-white">
                                <span className="flex h-1.5 w-1.5 rounded-full bg-[#111111]"></span>
                                Platforms
                            </div>
                            <h1 className="gsap-fade-up text-5xl lg:text-7xl font-bold text-[#111111] tracking-tight leading-[1.05] mb-6">
                                {__('frontend.platforms.title')}
                            </h1>
                            <p className="gsap-fade-up text-xl text-[#666666] font-normal leading-relaxed max-w-2xl">
                                {__('frontend.platforms.subtitle')}
                            </p>
                        </div>

                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-px bg-[#e5e5e5] border border-[#e5e5e5]">
                            <div className="gsap-fade-up p-12 bg-white transition-colors group hover:bg-[#fafafa]">
                                <LayoutDashboard className="w-8 h-8 text-[#111111] mb-8" strokeWidth={1.5} />
                                <h3 className="text-2xl font-bold text-[#111111] mb-4">{__('frontend.platforms.p1_title')}</h3>
                                <p className="text-[#666666] leading-relaxed mb-8">
                                    {__('frontend.platforms.p1_desc')}
                                </p>
                                <ul className="space-y-4 mb-10">
                                    <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full mr-4"></div>{__('frontend.platforms.p1_l1')}</li>
                                    <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full mr-4"></div>{__('frontend.platforms.p1_l2')}</li>
                                    <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full mr-4"></div>{__('frontend.platforms.p1_l3')}</li>
                                </ul>
                                <Link href="/pricing" className="inline-flex items-center gap-2 text-xs font-bold text-[#111111] uppercase tracking-widest hover:text-[#666666] transition-colors">
                                    {__('frontend.platforms.view_pricing')} <ArrowRight className="w-4 h-4" />
                                </Link>
                            </div>
                            
                            <div className="gsap-fade-up p-12 bg-white transition-colors group hover:bg-[#fafafa]">
                                <Workflow className="w-8 h-8 text-[#111111] mb-8" strokeWidth={1.5} />
                                <h3 className="text-2xl font-bold text-[#111111] mb-4">{__('frontend.platforms.p2_title')}</h3>
                                <p className="text-[#666666] leading-relaxed mb-8">
                                    {__('frontend.platforms.p2_desc')}
                                </p>
                                <ul className="space-y-4 mb-10">
                                    <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full mr-4"></div>{__('frontend.platforms.p2_l1')}</li>
                                    <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full mr-4"></div>{__('frontend.platforms.p2_l2')}</li>
                                    <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full mr-4"></div>{__('frontend.platforms.p2_l3')}</li>
                                </ul>
                                <Link href="/pricing" className="inline-flex items-center gap-2 text-xs font-bold text-[#111111] uppercase tracking-widest hover:text-[#666666] transition-colors">
                                    {__('frontend.platforms.view_pricing')} <ArrowRight className="w-4 h-4" />
                                </Link>
                            </div>

                            <div className="gsap-fade-up p-12 bg-white transition-colors group hover:bg-[#fafafa]">
                                <Globe className="w-8 h-8 text-[#111111] mb-8" strokeWidth={1.5} />
                                <h3 className="text-2xl font-bold text-[#111111] mb-4">{__('frontend.platforms.p3_title')}</h3>
                                <p className="text-[#666666] leading-relaxed mb-8">
                                    {__('frontend.platforms.p3_desc')}
                                </p>
                                <ul className="space-y-4 mb-10">
                                    <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full mr-4"></div>{__('frontend.platforms.p3_l1')}</li>
                                    <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full mr-4"></div>{__('frontend.platforms.p3_l2')}</li>
                                    <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full mr-4"></div>{__('frontend.platforms.p3_l3')}</li>
                                </ul>
                                <Link href="/pricing" className="inline-flex items-center gap-2 text-xs font-bold text-[#111111] uppercase tracking-widest hover:text-[#666666] transition-colors">
                                    {__('frontend.platforms.view_pricing')} <ArrowRight className="w-4 h-4" />
                                </Link>
                            </div>

                            <div className="gsap-fade-up p-12 bg-white transition-colors group hover:bg-[#fafafa]">
                                <Monitor className="w-8 h-8 text-[#111111] mb-8" strokeWidth={1.5} />
                                <h3 className="text-2xl font-bold text-[#111111] mb-4">{__('frontend.platforms.p4_title')}</h3>
                                <p className="text-[#666666] leading-relaxed mb-8">
                                    {__('frontend.platforms.p4_desc')}
                                </p>
                                <ul className="space-y-4 mb-10">
                                    <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full mr-4"></div>{__('frontend.platforms.p4_l1')}</li>
                                    <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full mr-4"></div>{__('frontend.platforms.p4_l2')}</li>
                                    <li className="flex items-center text-[#111111] text-sm font-semibold"><div className="w-1.5 h-1.5 bg-[#111111] rounded-full mr-4"></div>{__('frontend.platforms.p4_l3')}</li>
                                </ul>
                                <Link href="/pricing" className="inline-flex items-center gap-2 text-xs font-bold text-[#111111] uppercase tracking-widest hover:text-[#666666] transition-colors">
                                    {__('frontend.platforms.view_pricing')} <ArrowRight className="w-4 h-4" />
                                </Link>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}

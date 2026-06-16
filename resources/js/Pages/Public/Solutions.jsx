import { useRef } from 'react';
import { Head } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Briefcase, Building2, Server, GraduationCap, Code2, ShieldCheck } from 'lucide-react';
import { __ } from '@/lib/i18n';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';

gsap.registerPlugin(ScrollTrigger);

export default function Solutions() {
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
                <title>{__('frontend.solutions.meta_title')}</title>
                <meta name="description" content={__('frontend.solutions.meta_description')} />
            </Head>

            <style>{`
                .snap-section { scroll-snap-align: start; }
                html { scroll-behavior: smooth; }
            `}</style>

            <div ref={mainRef} className="w-full bg-[#fafafa] text-[#111111] font-sans selection:bg-[#111111] selection:text-white overflow-x-hidden">
                
                {/* Hero Section */}
                <section className="snap-section pt-32 pb-24 lg:pt-48 lg:pb-32 bg-[#fafafa] border-b border-[#e5e5e5] reveal-section">
                    <div className="max-w-[80rem] mx-auto px-6 lg:px-8">
                        <div className="max-w-3xl mb-20">
                            <div className="gsap-fade-up inline-flex items-center gap-2 px-3 py-1 border border-[#e5e5e5] text-xs font-semibold text-[#666666] tracking-widest uppercase mb-8 bg-white">
                                <span className="flex h-1.5 w-1.5 rounded-full bg-[#111111]"></span>
                                Solutions
                            </div>
                            <h1 className="gsap-fade-up text-5xl lg:text-7xl font-bold text-[#111111] tracking-tight leading-[1.05] mb-6">
                                {__('frontend.solutions.title')}
                            </h1>
                            <p className="gsap-fade-up text-xl text-[#666666] font-normal leading-relaxed max-w-2xl">
                                {__('frontend.solutions.subtitle')}
                            </p>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-px bg-[#e5e5e5] border border-[#e5e5e5]">
                            {[
                                { title: __('frontend.solutions.i1_title'), icon: <Building2 className="w-5 h-5" strokeWidth={1.5} />, text: __('frontend.solutions.i1_desc') },
                                { title: __('frontend.solutions.i2_title'), icon: <Briefcase className="w-5 h-5" strokeWidth={1.5} />, text: __('frontend.solutions.i2_desc') },
                                { title: __('frontend.solutions.i3_title'), icon: <Server className="w-5 h-5" strokeWidth={1.5} />, text: __('frontend.solutions.i3_desc') },
                                { title: __('frontend.solutions.i4_title'), icon: <GraduationCap className="w-5 h-5" strokeWidth={1.5} />, text: __('frontend.solutions.i4_desc') },
                                { title: __('frontend.solutions.i5_title'), icon: <Code2 className="w-5 h-5" strokeWidth={1.5} />, text: __('frontend.solutions.i5_desc') },
                                { title: __('frontend.solutions.i6_title'), icon: <ShieldCheck className="w-5 h-5" strokeWidth={1.5} />, text: __('frontend.solutions.i6_desc') }
                            ].map((ind, i) => (
                                <div key={i} className="gsap-fade-up p-10 bg-white transition-colors group hover:bg-[#fafafa]">
                                    <div className="text-[#111111] mb-6">{ind.icon}</div>
                                    <h4 className="text-lg font-bold text-[#111111] mb-3">{ind.title}</h4>
                                    <p className="text-[#666666] text-sm leading-relaxed">{ind.text}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Portfolio / Case Studies Section */}
                <section className="snap-section py-24 lg:py-32 bg-white reveal-section">
                    <div className="max-w-[80rem] mx-auto px-6 lg:px-8">
                        <div className="mb-16 max-w-3xl">
                            <h2 className="gsap-fade-up text-4xl lg:text-5xl font-bold text-[#111111] tracking-tight mb-4">{__('frontend.solutions.portfolio_title')}</h2>
                            <p className="gsap-fade-up text-lg text-[#666666] font-normal leading-relaxed">
                                {__('frontend.solutions.portfolio_subtitle')}
                            </p>
                        </div>

                        <div className="space-y-8 border-t border-[#e5e5e5] pt-12">
                            {/* Case Study 1 */}
                            <div className="gsap-fade-up grid grid-cols-1 lg:grid-cols-4 gap-8 p-0 lg:p-0 transition-all">
                                <div className="lg:col-span-1 border-b lg:border-b-0 lg:border-r border-[#e5e5e5] pb-6 lg:pb-0 lg:pr-8">
                                    <span className="text-xs font-bold uppercase tracking-widest text-[#888888] mb-2 block">{__('frontend.solutions.cs1_cat')}</span>
                                    <h3 className="text-2xl font-bold text-[#111111]">{__('frontend.solutions.cs1_title')}</h3>
                                </div>
                                <div className="lg:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-8">
                                    <div>
                                        <h4 className="text-xs font-bold text-[#111111] mb-2 uppercase tracking-widest">{__('frontend.solutions.cs1_h1')}</h4>
                                        <p className="text-sm text-[#666666] leading-relaxed">{__('frontend.solutions.cs1_d1')}</p>
                                    </div>
                                    <div>
                                        <h4 className="text-xs font-bold text-[#111111] mb-2 uppercase tracking-widest">{__('frontend.solutions.cs1_h2')}</h4>
                                        <p className="text-sm text-[#666666] leading-relaxed">{__('frontend.solutions.cs1_d2')}</p>
                                    </div>
                                    <div>
                                        <h4 className="text-xs font-bold text-[#111111] mb-2 uppercase tracking-widest">{__('frontend.solutions.cs1_h3')}</h4>
                                        <p className="text-sm text-[#666666] leading-relaxed">{__('frontend.solutions.cs1_d3')}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <hr className="border-[#e5e5e5] my-8" />

                            {/* Case Study 2 */}
                            <div className="gsap-fade-up grid grid-cols-1 lg:grid-cols-4 gap-8 p-0 lg:p-0 transition-all">
                                <div className="lg:col-span-1 border-b lg:border-b-0 lg:border-r border-[#e5e5e5] pb-6 lg:pb-0 lg:pr-8">
                                    <span className="text-xs font-bold uppercase tracking-widest text-[#888888] mb-2 block">{__('frontend.solutions.cs2_cat')}</span>
                                    <h3 className="text-2xl font-bold text-[#111111]">{__('frontend.solutions.cs2_title')}</h3>
                                </div>
                                <div className="lg:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-8">
                                    <div>
                                        <h4 className="text-xs font-bold text-[#111111] mb-2 uppercase tracking-widest">{__('frontend.solutions.cs2_h1')}</h4>
                                        <p className="text-sm text-[#666666] leading-relaxed">{__('frontend.solutions.cs2_d1')}</p>
                                    </div>
                                    <div>
                                        <h4 className="text-xs font-bold text-[#111111] mb-2 uppercase tracking-widest">{__('frontend.solutions.cs2_h2')}</h4>
                                        <p className="text-sm text-[#666666] leading-relaxed">{__('frontend.solutions.cs2_d2')}</p>
                                    </div>
                                    <div>
                                        <h4 className="text-xs font-bold text-[#111111] mb-2 uppercase tracking-widest">{__('frontend.solutions.cs2_h3')}</h4>
                                        <p className="text-sm text-[#666666] leading-relaxed">{__('frontend.solutions.cs2_d3')}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <hr className="border-[#e5e5e5] my-8" />

                            {/* Case Study 3 */}
                            <div className="gsap-fade-up grid grid-cols-1 lg:grid-cols-4 gap-8 p-0 lg:p-0 transition-all">
                                <div className="lg:col-span-1 border-b lg:border-b-0 lg:border-r border-[#e5e5e5] pb-6 lg:pb-0 lg:pr-8">
                                    <span className="text-xs font-bold uppercase tracking-widest text-[#888888] mb-2 block">{__('frontend.solutions.cs3_cat')}</span>
                                    <h3 className="text-2xl font-bold text-[#111111]">{__('frontend.solutions.cs3_title')}</h3>
                                </div>
                                <div className="lg:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-8">
                                    <div>
                                        <h4 className="text-xs font-bold text-[#111111] mb-2 uppercase tracking-widest">{__('frontend.solutions.cs3_h1')}</h4>
                                        <p className="text-sm text-[#666666] leading-relaxed">{__('frontend.solutions.cs3_d1')}</p>
                                    </div>
                                    <div>
                                        <h4 className="text-xs font-bold text-[#111111] mb-2 uppercase tracking-widest">{__('frontend.solutions.cs3_h2')}</h4>
                                        <p className="text-sm text-[#666666] leading-relaxed">{__('frontend.solutions.cs3_d2')}</p>
                                    </div>
                                    <div>
                                        <h4 className="text-xs font-bold text-[#111111] mb-2 uppercase tracking-widest">{__('frontend.solutions.cs3_h3')}</h4>
                                        <p className="text-sm text-[#666666] leading-relaxed">{__('frontend.solutions.cs3_d3')}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}

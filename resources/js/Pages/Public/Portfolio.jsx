import { useRef } from 'react';
import { __ } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { portfolioItems } from '@/lib/portfolioData';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';

gsap.registerPlugin(ScrollTrigger);

export default function Portfolio() {
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
                <title>{`${__('general.landing_portfolio_title')} | ${__('general.musoftware_unified_workspace') || 'Musoftware'}`}</title>
                <meta name="description" content={__('general.landing_portfolio_desc')} />
            </Head>

            <style>{`
                .snap-section { scroll-snap-align: start; }
                html { scroll-behavior: smooth; }
            `}</style>

            <div ref={mainRef} className="w-full bg-[#fafafa] text-[#111111] font-sans selection:bg-[#111111] selection:text-white overflow-x-hidden">
                {/* HERO SECTION */}
                <section className="snap-section pt-32 pb-24 lg:pt-48 lg:pb-32 bg-[#fafafa] border-b border-[#e5e5e5] reveal-section">
                    <div className="max-w-[80rem] mx-auto px-6 lg:px-8 text-center">
                        <div className="max-w-4xl mx-auto">
                            <div className="gsap-fade-up inline-flex items-center gap-2 px-3 py-1 border border-[#e5e5e5] text-xs font-semibold text-[#666666] tracking-widest uppercase mb-8 bg-white mx-auto">
                                <span className="flex h-1.5 w-1.5 rounded-full bg-[#111111]"></span>
                                {__('general.landing_portfolio_badge') || 'Our Work'}
                            </div>
                            <h1 className="gsap-fade-up text-5xl lg:text-7xl font-bold text-[#111111] tracking-tight leading-[1.05] mb-6">
                                {__('general.landing_portfolio_title')}
                            </h1>
                            <p className="gsap-fade-up text-xl text-[#666666] font-normal leading-relaxed max-w-2xl mx-auto">
                                {__('general.landing_portfolio_desc')}
                            </p>
                        </div>
                    </div>
                </section>

                {/* PORTFOLIO GRID */}
                <section className="snap-section py-24 lg:py-32 bg-white reveal-section">
                    <div className="max-w-[80rem] mx-auto px-6 lg:px-8">
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-px bg-[#e5e5e5] border border-[#e5e5e5]">
                            {portfolioItems.map((item, index) => (
                                <Link 
                                    href={route('portfolio.show', item.slug)} 
                                    key={index} 
                                    className="gsap-fade-up group relative bg-white flex flex-col h-[400px] overflow-hidden"
                                >
                                    <div className="h-2/3 w-full overflow-hidden relative">
                                        <div className="absolute inset-0 bg-[#111111]/10 group-hover:bg-transparent transition-colors duration-500 z-10 mix-blend-multiply"></div>
                                        <img 
                                            src={item.img} 
                                            alt={`Portfolio ${index + 1}`} 
                                            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 ease-out grayscale group-hover:grayscale-0"
                                        />
                                    </div>
                                    <div className="h-1/3 p-6 flex flex-col justify-center border-t border-[#e5e5e5] bg-white transition-colors group-hover:bg-[#fafafa]">
                                        <span className="text-[10px] font-bold tracking-widest uppercase text-[#888888] mb-2">
                                            {item.cat}
                                        </span>
                                        <h3 className="text-lg font-bold text-[#111111] mb-2 line-clamp-1">
                                            {__(`general.${item.titleKey}`)}
                                        </h3>
                                        <p className="text-[#666666] text-xs leading-relaxed line-clamp-2">
                                            {__(`general.${item.descKey}`)}
                                        </p>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}

import { useMemo, useRef } from 'react';
import { Head, Link } from '@inertiajs/react';
import { __ } from '@/lib/i18n';
import PublicLayout from '@/Layouts/PublicLayout';
import { ArrowLeft } from 'lucide-react';
import { portfolioItems } from '@/lib/portfolioData';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';

gsap.registerPlugin(ScrollTrigger);

export default function PortfolioShow({ slug }) {
    const mainRef = useRef(null);
    const item = useMemo(() => portfolioItems.find((p) => p.slug === slug), [slug]);

    useGSAP(() => {
        if (!item) return;
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
    }, { scope: mainRef, dependencies: [item] });

    if (!item) {
        return (
            <PublicLayout>
                <div className="min-h-[60vh] flex items-center justify-center bg-[#fafafa]">
                    <div className="text-center">
                        <h2 className="text-3xl font-bold text-[#111111] mb-4">{__('general.not_found') || 'Not Found'}</h2>
                        <Link href={route('portfolio')} className="text-[#666666] font-semibold hover:text-[#111111] underline transition-colors">
                            {__('general.back_to_portfolio') || 'Back to Portfolio'}
                        </Link>
                    </div>
                </div>
            </PublicLayout>
        );
    }

    const content = __(`general.${item.contentKey}`);

    return (
        <PublicLayout>
            <Head>
                <title>{`${__(`general.${item.titleKey}`)} | ${__('general.musoftware_unified_workspace') || 'Musoftware'}`}</title>
                <meta name="description" content={__(`general.${item.descKey}`)} />
            </Head>

            <div ref={mainRef} className="w-full bg-[#fafafa] text-[#111111] font-sans selection:bg-[#111111] selection:text-white overflow-x-hidden">
                {/* HERO SECTION */}
                <section className="pt-32 pb-16 lg:pt-40 lg:pb-24 bg-[#fafafa] border-b border-[#e5e5e5] reveal-section">
                    <div className="max-w-[80rem] mx-auto px-6 lg:px-8">
                        <Link 
                            href={route('portfolio')} 
                            className="gsap-fade-up inline-flex items-center text-xs font-bold uppercase tracking-widest text-[#888888] hover:text-[#111111] transition-colors mb-12"
                        >
                            <ArrowLeft className="w-4 h-4 mr-2 rtl:ml-2 rtl:mr-0 rtl:rotate-180" />
                            {__('general.back_to_portfolio') || 'Back to Portfolio'}
                        </Link>

                        <div className="grid lg:grid-cols-2 gap-12 lg:gap-24 items-center">
                            <div>
                                <div className="gsap-fade-up inline-flex items-center gap-2 px-3 py-1 border border-[#e5e5e5] text-xs font-semibold text-[#666666] tracking-widest uppercase mb-6 bg-white">
                                    <span className="flex h-1.5 w-1.5 rounded-full bg-[#111111]"></span>
                                    {item.cat}
                                </div>
                                <h1 className="gsap-fade-up text-4xl lg:text-6xl font-bold tracking-tight text-[#111111] leading-[1.05] mb-6">
                                    {__(`general.${item.titleKey}`)}
                                </h1>
                                <p className="gsap-fade-up text-xl text-[#666666] leading-relaxed font-normal">
                                    {__(`general.${item.descKey}`)}
                                </p>
                            </div>
                            <div className="gsap-fade-up bg-white p-4 border border-[#e5e5e5]">
                                <img 
                                    src={item.img} 
                                    alt={__(`general.${item.titleKey}`)} 
                                    className="w-full h-auto object-cover border border-[#e5e5e5] max-h-[600px] grayscale hover:grayscale-0 transition-all duration-700" 
                                />
                            </div>
                        </div>
                    </div>
                </section>

                {/* DETAILED CONTENT SECTION */}
                <section className="py-24 lg:py-32 bg-white reveal-section">
                    <div className="max-w-4xl mx-auto px-6 lg:px-8">
                        <div 
                            className="gsap-fade-up prose prose-lg max-w-none text-[#666666] prose-headings:text-[#111111] prose-headings:font-bold prose-headings:tracking-tight prose-a:text-[#111111] prose-a:font-semibold hover:prose-a:text-[#666666] prose-img:border prose-img:border-[#e5e5e5] prose-img:rounded-none rtl:prose-p:text-right rtl:prose-headings:text-right rtl:prose-ul:text-right rtl:prose-li:text-right rtl:prose-blockquote:text-right rtl:prose-blockquote:border-r-4 rtl:prose-blockquote:border-l-0 rtl:prose-blockquote:pr-4 rtl:prose-blockquote:pl-0"
                            dangerouslySetInnerHTML={{ __html: content }}
                        />
                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}

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

export default function PortfolioShow({ slug, dbProject = null }) {
    const mainRef = useRef(null);
    
    const item = useMemo(() => {
        if (dbProject) return dbProject;
        const staticItem = portfolioItems.find((p) => p.slug === slug);
        if (staticItem) {
            const rawTitle = __(`general.${staticItem.titleKey}`);
            const rawDesc = __(`general.${staticItem.descKey}`);
            const rawContent = __(`general.${staticItem.contentKey}`);

            return {
                slug: staticItem.slug,
                img: staticItem.img,
                img_original: staticItem.img, // fallback to standard
                title: (rawTitle && !rawTitle.startsWith('general.')) ? rawTitle : staticItem.slug,
                desc: (rawDesc && !rawDesc.startsWith('general.')) ? rawDesc : '',
                content: (rawContent && !rawContent.startsWith('general.')) ? rawContent : `<p>${rawDesc || ''}</p>`,
                cat: staticItem.cat,
                is_db: false,
                live_url: null,
                github_url: null,
                techs: []
            };
        }
        return null;
    }, [slug, dbProject]);

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

    return (
        <PublicLayout>
            <Head title={`${item.title} | ${__('general.musoftware_unified_workspace') || 'Musoftware'}`}>
                <meta name="description" content={item.desc} />
            </Head>

            <div ref={mainRef} className="w-full bg-[#fafafa] text-[#111111] font-sans selection:bg-[#111111] selection:text-white overflow-x-hidden">
                {/* HERO SECTION */}
                <section className="pt-32 pb-16 lg:pt-40 lg:pb-24 bg-[#fafafa] border-b border-[#e5e5e5] reveal-section">
                    <div className="max-w-[80rem] mx-auto px-6 lg:px-8">
                        <Link 
                            href={route('portfolio')} 
                            className="gsap-fade-up inline-flex items-center text-xs font-bold uppercase tracking-widest text-[#888888] hover:text-[#111111] transition-colors mb-12"
                        >
                            <ArrowLeft className="w-4 h-4 me-2 rtl:ms-2 rtl:me-0 rtl:rotate-180" />
                            {__('general.back_to_portfolio') || 'Back to Portfolio'}
                        </Link>

                        <div className="grid lg:grid-cols-2 gap-12 lg:gap-24 items-center">
                            <div>
                                <div className="gsap-fade-up inline-flex items-center gap-2 px-3 py-1 border border-[#e5e5e5] text-xs font-semibold text-[#666666] tracking-widest uppercase mb-6 bg-white">
                                    <span className="flex h-1.5 w-1.5 rounded-full bg-[#111111]"></span>
                                    {item.cat}
                                </div>
                                <h1 className="gsap-fade-up text-4xl lg:text-6xl font-bold tracking-tight text-[#111111] leading-[1.05] mb-6">
                                    {item.title}
                                </h1>
                                <p className="gsap-fade-up text-xl text-[#666666] leading-relaxed font-normal mb-8">
                                    {item.desc}
                                </p>
                                
                                {item.live_url && (
                                    <a 
                                        href={item.live_url} 
                                        target="_blank" 
                                        rel="noopener noreferrer" 
                                        className="gsap-fade-up inline-flex items-center gap-2 px-6 py-3.5 bg-[#111111] hover:bg-[#333333] text-white rounded-lg text-sm font-bold uppercase tracking-wider transition-colors duration-300 shadow-sm"
                                    >
                                        {__('general.visit_live_website') || 'Visit Live Website'}
                                    </a>
                                )}
                            </div>
                            
                            <div className="gsap-fade-up bg-white border border-[#e5e5e5] rounded-xl overflow-hidden shadow-lg flex flex-col h-[500px]">
                                {/* Browser Mockup Header */}
                                <div className="bg-[#f4f4f5] border-b border-[#e5e5e5] px-4 py-3 flex items-center gap-2 shrink-0">
                                    <div className="flex gap-1.5">
                                        <span className="w-3 h-3 rounded-full bg-[#ef4444]"></span>
                                        <span className="w-3 h-3 rounded-full bg-[#eab308]"></span>
                                        <span className="w-3 h-3 rounded-full bg-[#22c55e]"></span>
                                    </div>
                                    <div className="flex-1 bg-white border border-[#e5e5e5] rounded px-3 py-0.5 text-xs text-[#888888] truncate select-all text-center font-mono">
                                        {item.live_url || 'https://www.musoftwares.com'}
                                    </div>
                                </div>
                                {/* Scrollable Image Container */}
                                <div className="flex-1 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300">
                                    {item.img_original || item.img ? (
                                        <img 
                                            src={item.img_original || item.img} 
                                            alt={item.title} 
                                            className="w-full h-auto object-contain transition-all duration-500" 
                                        />
                                    ) : (
                                        <div className="w-full h-full bg-slate-100 flex items-center justify-center text-slate-400 font-medium">
                                            No Image
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* DETAILED CONTENT SECTION */}
                <section className="py-24 lg:py-32 bg-white reveal-section">
                    <div className="max-w-4xl mx-auto px-6 lg:px-8">
                        <div 
                            className="gsap-fade-up prose prose-lg max-w-none text-[#666666] prose-headings:text-[#111111] prose-headings:font-bold prose-headings:tracking-tight prose-a:text-[#111111] prose-a:font-semibold hover:prose-a:text-[#666666] prose-img:border prose-img:border-[#e5e5e5] prose-img:rounded-none rtl:prose-p:text-end rtl:prose-headings:text-end rtl:prose-ul:text-end rtl:prose-li:text-end rtl:prose-blockquote:text-end rtl:prose-blockquote:border-e-4 rtl:prose-blockquote:border-s-0 rtl:prose-blockquote:pe-4 rtl:prose-blockquote:ps-0"
                            dangerouslySetInnerHTML={{ __html: item.content }}
                        />
                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}

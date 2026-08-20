import { useMemo, useRef } from 'react';
import { Head, Link } from '@inertiajs/react';
import { __ } from '@/lib/i18n';
import PublicLayout from '@/Layouts/PublicLayout';
import { ArrowLeft, ArrowUpRight, Globe, Layers } from 'lucide-react';
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
                img_original: staticItem.img,
                title: (rawTitle && !rawTitle.startsWith('general.')) ? rawTitle : staticItem.slug,
                desc: (rawDesc && !rawDesc.startsWith('general.')) ? rawDesc : '',
                content: (rawContent && !rawContent.startsWith('general.')) ? rawContent : `<p>${rawDesc || ''}</p>`,
                cat: staticItem.cat,
                is_db: false,
                live_url: staticItem.live_url || null,
                github_url: staticItem.github_url || null,
                techs: staticItem.techs || []
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
                <div className="min-h-[60vh] flex items-center justify-center bg-[#111111] text-white font-mono">
                    <div className="text-center space-y-4">
                        <h2 className="text-2xl font-bold text-white uppercase tracking-widest">{__('general.not_found') || 'Case Study Not Found'}</h2>
                        <Link href="/portfolio" className="text-xs text-[#748660] hover:underline uppercase tracking-wider block">
                            ➔ {__('general.back_to_portfolio') || 'Back to Studio Archive'}
                        </Link>
                    </div>
                </div>
            </PublicLayout>
        );
    }

    return (
        <PublicLayout>
            <Head title={`${item.title} | ${__('general.musoftware_unified_workspace') || 'Musoftwares'}`}>
                <meta name="description" content={item.desc} />
            </Head>

            <div ref={mainRef} className="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white overflow-x-hidden pt-12 pb-28">
                
                {/* HERO SECTION */}
                <section className="max-w-[1400px] mx-auto px-6 lg:px-8 mb-20 reveal-section">
                    <Link 
                        href="/portfolio" 
                        className="gsap-fade-up inline-flex items-center text-xs font-mono font-bold uppercase tracking-widest text-zinc-400 hover:text-white transition-colors mb-10"
                    >
                        <ArrowLeft className="w-3.5 h-3.5 me-2 rtl:ms-2 rtl:me-0 rtl:rotate-180" />
                        {__('general.back_to_portfolio') || 'STUDIO ARCHIVE'}
                    </Link>

                    <div className="grid lg:grid-cols-12 gap-12 lg:gap-16 items-start">
                        
                        {/* Meta & Project Brief (5 cols) */}
                        <div className="lg:col-span-5 space-y-6 font-mono">
                            <span className="inline-block text-[11px] font-bold uppercase tracking-[0.2em] text-[#748660] bg-[#1E2619] border border-[#748660]/40 px-3 py-1">
                                {item.cat}
                            </span>
                            <h1 className="gsap-fade-up text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight text-white leading-tight font-sans">
                                {item.title}
                            </h1>
                            <p className="gsap-fade-up text-xs sm:text-sm text-zinc-400 leading-relaxed font-mono">
                                {item.desc}
                            </p>
                            
                            {item.techs && item.techs.length > 0 && (
                                <div className="pt-4 border-t border-[#262626] space-y-2">
                                    <span className="text-[10px] font-mono uppercase tracking-widest text-zinc-500">
                                        Engineered With:
                                    </span>
                                    <div className="flex flex-wrap gap-1.5">
                                        {item.techs.map((tech, idx) => (
                                            <span key={idx} className="text-[11px] font-mono bg-black border border-[#2B2B2B] text-zinc-300 px-2.5 py-1">
                                                {tech}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {item.live_url && (
                                <div className="pt-4">
                                    <a 
                                        href={item.live_url} 
                                        target="_blank" 
                                        rel="noopener noreferrer" 
                                        className="gsap-fade-up inline-flex items-center gap-2 px-8 py-3.5 bg-white text-black hover:bg-zinc-200 text-xs font-bold font-mono uppercase tracking-widest transition-colors shadow-sm"
                                    >
                                        <Globe className="w-4 h-4" />
                                        <span>{__('general.visit_live_website') || 'LAUNCH PLATFORM'} ➔</span>
                                    </a>
                                </div>
                            )}
                        </div>
                        
                        {/* Browser Showcase Screen (7 cols) */}
                        <div className="lg:col-span-7 gsap-fade-up bg-[#161616] border border-[#2B2B2B] overflow-hidden flex flex-col h-[520px]">
                            {/* Browser Mockup Bar */}
                            <div className="bg-black border-b border-[#2B2B2B] px-4 py-3 flex items-center gap-2 shrink-0">
                                <div className="flex gap-1.5">
                                    <span className="w-2.5 h-2.5 rounded-full bg-zinc-700"></span>
                                    <span className="w-2.5 h-2.5 rounded-full bg-zinc-700"></span>
                                    <span className="w-2.5 h-2.5 rounded-full bg-zinc-700"></span>
                                </div>
                                <div className="flex-1 bg-[#141414] border border-[#262626] px-3 py-1 text-[11px] text-zinc-400 truncate text-center font-mono">
                                    {item.live_url || 'https://www.musoftwares.com'}
                                </div>
                            </div>
                            {/* Image Showcase */}
                            <div className="flex-1 overflow-y-auto bg-black flex items-center justify-center p-4">
                                {item.img_original || item.img ? (
                                    <img 
                                        src={item.img_original || item.img} 
                                        alt={item.title} 
                                        className="w-full h-auto object-contain transition-all" 
                                    />
                                ) : (
                                    <div className="w-full h-full flex items-center justify-center text-zinc-600 font-mono text-xs">
                                        [PREVIEW UNAVAILABLE]
                                    </div>
                                )}
                            </div>
                        </div>

                    </div>
                </section>

                {/* DETAILED CONTENT SECTION */}
                <section className="py-16 border-t border-[#222222] reveal-section">
                    <div className="max-w-4xl mx-auto px-6 lg:px-8">
                        <div 
                            className="gsap-fade-up prose prose-invert prose-p:text-zinc-400 prose-p:font-mono prose-p:text-xs sm:prose-p:text-sm prose-headings:text-white prose-headings:font-sans prose-a:text-[#748660] max-w-none rtl:prose-p:text-end rtl:prose-headings:text-end rtl:prose-ul:text-end"
                            dangerouslySetInnerHTML={{ __html: item.content }}
                        />
                    </div>
                </section>

            </div>
        </PublicLayout>
    );
}

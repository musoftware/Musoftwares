import { useRef, useMemo } from 'react';
import { __ } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { portfolioItems } from '@/lib/portfolioData';
import { Sparkles, ArrowRight, ChevronRight, ExternalLink } from 'lucide-react';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';

gsap.registerPlugin(ScrollTrigger);

export default function Portfolio({ dbProjects = [] }) {
    const mainRef = useRef(null);

    // Merge database projects with static items, avoiding duplicates
    const allItems = useMemo(() => {
        const combined = [...dbProjects];
        const dbSlugs = new Set(dbProjects.map(item => item.slug));
        
        portfolioItems.forEach(item => {
            if (!dbSlugs.has(item.slug)) {
                const rawTitle = __(`general.${item.titleKey}`);
                const rawDesc = __(`general.${item.descKey}`);
                combined.push({
                    slug: item.slug,
                    img: item.img,
                    title: (rawTitle && !rawTitle.startsWith('general.')) ? rawTitle : item.slug,
                    desc: (rawDesc && !rawDesc.startsWith('general.')) ? rawDesc : '',
                    cat: item.cat,
                    is_db: false
                });
            }
        });
        return combined;
    }, [dbProjects]);

    useGSAP(() => {
        const sections = gsap.utils.toArray('.reveal-section');
        sections.forEach((section) => {
            const elements = gsap.utils.toArray(section.querySelectorAll('.gsap-fade-up'));
            if (elements && elements.length > 0) {
                gsap.fromTo(elements, 
                    { opacity: 0, y: 20 },
                    {
                        opacity: 1, 
                        y: 0, 
                        duration: 0.6,
                        stagger: 0.05,
                        ease: "power2.out",
                        scrollTrigger: {
                            trigger: section,
                            start: "top 85%",
                            toggleActions: "play none none reverse"
                        }
                    }
                );
            }
        });
    }, { scope: mainRef, dependencies: [allItems] });

    return (
        <PublicLayout>
            <Head title={`${__('general.landing_portfolio_title') || 'Work'} | ${__('general.musoftware_unified_workspace') || 'Musoftware'}`}>
                <meta name="description" content={__('general.landing_portfolio_desc') || "Explore our engineered platforms, enterprise SaaS, and desktop utilities."} />
            </Head>

            <div ref={mainRef} className="w-full bg-white text-[#1d1d1f] font-sans selection:bg-[#1d1d1f] selection:text-white pt-12 sm:pt-20 pb-20 sm:pb-32">
                
                {/* HERO SECTION */}
                <section className="px-6 max-w-5xl mx-auto flex flex-col items-center text-center mb-16 sm:mb-24 reveal-section">
                    <p className="text-base sm:text-xl text-[#86868b] font-medium mb-3 sm:mb-4 tracking-tight">
                        {__('general.landing_portfolio_badge') || 'Proven in Production'}
                    </p>

                    <h1 className="text-4xl sm:text-6xl md:text-7xl lg:text-[86px] leading-[1.05] font-bold text-[#1d1d1f] max-w-5xl mb-6 tracking-tight">
                        {__('general.landing_portfolio_title') || 'Engineered products.'} <br className="hidden sm:inline" />
                        <span className="bg-gradient-to-r from-[#0066cc] to-[#3399ff] bg-clip-text text-transparent">
                            Shipped in the real world.
                        </span>
                    </h1>

                    <p className="text-lg sm:text-2xl md:text-[26px] text-[#86868b] max-w-3xl mb-4 font-medium leading-snug tracking-tight">
                        {__('general.landing_portfolio_desc') || 'See how we have helped businesses transform their operations through custom software.'}
                    </p>
                </section>

                {/* PORTFOLIO PRODUCT GRID */}
                <section className="px-6 max-w-7xl mx-auto reveal-section">
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                        {allItems.map((item, index) => (
                            <Link 
                                key={index} 
                                href={route('portfolio.show', item.slug)}
                                className="group bg-[#f5f5f7] rounded-[28px] p-6 sm:p-7 flex flex-col justify-between border border-[#d2d2d7]/50 hover:border-[#0066cc]/40 hover:shadow-md transition-all duration-300"
                            >
                                <div>
                                    {/* Image Preview Container */}
                                    <div className="h-44 sm:h-48 rounded-2xl overflow-hidden bg-white border border-[#d2d2d7]/50 mb-5 relative">
                                        <img 
                                            src={item.img} 
                                            alt={item.title} 
                                            className="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500" 
                                            loading="lazy"
                                        />
                                        <div className="absolute top-3 end-3">
                                            <span className="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-white/95 text-zinc-800 shadow-xs border border-zinc-200 backdrop-blur-xs">
                                                {item.cat}
                                            </span>
                                        </div>
                                    </div>

                                    {/* Titles and descriptions */}
                                    <h3 className="text-lg sm:text-xl font-bold text-[#1d1d1f] tracking-tight mb-2 group-hover:text-[#0066cc] transition-colors">
                                        {item.title}
                                    </h3>
                                    <p className="text-xs sm:text-sm text-[#86868b] leading-relaxed line-clamp-2 mb-4 font-medium">
                                        {item.desc}
                                    </p>
                                </div>

                                <div className="pt-4 border-t border-[#d2d2d7]/40 flex items-center justify-between text-xs font-bold text-[#1d1d1f] group-hover:text-[#0066cc] transition-colors">
                                    <span>Explore Case Study</span>
                                    <ChevronRight className="w-4 h-4 group-hover:translate-x-1 transition-transform rtl:rotate-180" />
                                </div>
                            </Link>
                        ))}
                    </div>
                </section>

            </div>
        </PublicLayout>
    );
}

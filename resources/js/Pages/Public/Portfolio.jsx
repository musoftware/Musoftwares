import { useRef, useMemo } from 'react';
import { __ } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { portfolioItems } from '@/lib/portfolioData';
import { ChevronRight, ArrowUpRight } from 'lucide-react';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';
import StudioHeader from '@/Components/Studio/StudioHeader';

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
            <Head title={`${__('general.landing_portfolio_title') || 'Work'} | ${__('general.musoftware_unified_workspace') || 'Musoftwares Studio'}`}>
                <meta name="description" content={__('general.landing_portfolio_desc') || "Explore our engineered platforms, enterprise SaaS, and desktop utilities."} />
            </Head>

            <div ref={mainRef} className="w-full bg-[#ffffff] text-[#1d1d1f] font-sans selection:bg-[#0071e3]/20 selection:text-[#0071e3] pt-12 sm:pt-20 pb-24 sm:pb-36">
                
                {/* Reusable Studio Hero Section */}
                <div className="reveal-section">
                    <StudioHeader
                        badge={__('general.landing_portfolio_badge') || 'Proven in Production'}
                        title={
                            <>
                                Engineered for Scale. <br className="hidden sm:inline" />
                                <span className="text-[#0071e3]">Shipped to the World.</span>
                            </>
                        }
                        subtitle={__('general.landing_portfolio_desc') || 'A curated archive of bespoke ERP architectures, high-throughput Meta Graph pipelines, and custom enterprise platforms.'}
                    />
                </div>

                {/* PORTFOLIO ASYMMETRIC BENTO GRID */}
                <section className="px-6 max-w-[1400px] mx-auto reveal-section">
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                        {allItems.map((item, index) => (
                            <Link 
                                key={index} 
                                href={route('portfolio.show', item.slug)}
                                className="group bg-white border border-black/5 rounded-[24px] overflow-hidden flex flex-col justify-between hover:border-[#0071e3]/40 hover:shadow-xl transition-all duration-300 shadow-sm"
                            >
                                <div>
                                    {/* Image Preview Container */}
                                    <div className="h-56 sm:h-64 overflow-hidden bg-[#f5f5f7] relative border-b border-black/5">
                                        <img 
                                            src={item.img} 
                                            alt={item.title} 
                                            className="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500" 
                                            loading="lazy"
                                        />
                                        <div className="absolute top-3 end-3">
                                            <span className="text-[10px] font-semibold uppercase tracking-wider px-3 py-1 bg-white/90 text-[#1d1d1f] border border-black/5 rounded-full backdrop-blur-md shadow-xs">
                                                {item.cat}
                                            </span>
                                        </div>
                                    </div>

                                    {/* Titles and descriptions */}
                                    <div className="p-6">
                                        <h3 className="text-base sm:text-lg font-semibold text-[#1d1d1f] tracking-tight mb-2 group-hover:text-[#0071e3] transition-colors font-sans">
                                            {item.title}
                                        </h3>
                                        <p className="text-sm text-[#1d1d1f]/60 font-sans leading-relaxed line-clamp-2">
                                            {item.desc}
                                        </p>
                                    </div>
                                </div>

                                <div className="p-6 pt-0 flex items-center justify-between text-xs font-semibold text-[#0071e3] group-hover:text-[#0077ed] transition-colors">
                                    <span className="tracking-wide">EXPLORE CASE STUDY</span>
                                    <ArrowUpRight className="w-4 h-4 rtl:rotate-[-90deg] group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" />
                                </div>
                            </Link>
                        ))}
                    </div>
                </section>

            </div>
        </PublicLayout>
    );
}

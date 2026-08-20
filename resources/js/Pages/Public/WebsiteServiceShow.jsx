import { useMemo, useRef } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { __ } from '@/lib/i18n';
import PublicLayout from '@/Layouts/PublicLayout';
import { ArrowLeft, Box, ArrowUpRight } from 'lucide-react';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';
import { openWhatsAppChat } from '@/lib/whatsapp';

gsap.registerPlugin(ScrollTrigger);

export default function WebsiteServiceShow({ service }) {
    const { locale } = usePage().props;
    const mainRef = useRef(null);

    useGSAP(() => {
        if (!service) return;
        const sections = gsap.utils.toArray('.reveal-section');
        sections.forEach((section) => {
            const elements = section.querySelectorAll('.gsap-fade-up');
            if (elements && elements.length > 0) {
                gsap.fromTo(elements, 
                    { opacity: 0, y: 20 },
                    {
                        opacity: 1, 
                        y: 0, 
                        duration: 0.6,
                        stagger: 0.08,
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
    }, { scope: mainRef, dependencies: [service] });

    if (!service) {
        return (
            <PublicLayout>
                <div className="min-h-[60vh] flex items-center justify-center bg-[#111111] text-white font-mono">
                    <div className="text-center space-y-4">
                        <h2 className="text-2xl font-bold text-white uppercase tracking-widest">{__('general.not_found') || 'Service Not Found'}</h2>
                        <Link href="/" className="text-xs text-[#748660] hover:underline uppercase tracking-wider block">
                            ➔ {__('general.back_to_home') || 'Back to Studio Home'}
                        </Link>
                    </div>
                </div>
            </PublicLayout>
        );
    }

    const title = locale === 'ar' ? service.title_ar : service.title_en;
    const subtitle = locale === 'ar' ? service.subtitle_ar : service.subtitle_en;
    const description = locale === 'ar' ? service.description_ar : service.description_en;

    const finalTitle = title || __('general.service');
    const finalDescription = subtitle || description || '';

    const imageUrl = locale === 'ar' ? service.primary_image_ar : service.primary_image_en;

    return (
        <PublicLayout>
            <Head>
                <title>{`${finalTitle} | ${__('general.musoftware_unified_workspace') || 'Musoftwares'}`}</title>
                <meta name="description" content={finalDescription} />
            </Head>

            <div ref={mainRef} className="w-full bg-[#111111] text-[#E5E5E5] font-sans selection:bg-[#748660] selection:text-white overflow-x-hidden pt-12 pb-28">
                
                {/* HERO SECTION */}
                <section className="max-w-[1400px] mx-auto px-6 lg:px-8 mb-20 reveal-section">
                    <Link 
                        href="/" 
                        className="gsap-fade-up inline-flex items-center text-xs font-mono font-bold uppercase tracking-widest text-zinc-400 hover:text-white transition-colors mb-10"
                    >
                        <ArrowLeft className="w-3.5 h-3.5 me-2 rtl:ms-2 rtl:me-0 rtl:rotate-180" />
                        {__('general.back_to_home') || 'STUDIO HOME'}
                    </Link>

                    <div className="grid lg:grid-cols-12 gap-12 lg:gap-16 items-start">
                        
                        {/* Meta & Info (6 cols) */}
                        <div className="lg:col-span-6 space-y-6 font-mono">
                            <span className="inline-block text-[11px] font-bold uppercase tracking-[0.2em] text-[#748660] bg-[#1E2619] border border-[#748660]/40 px-3 py-1">
                                {__('general.services') || 'Engineering Service'}
                            </span>
                            <h1 className="gsap-fade-up text-3xl sm:text-5xl font-bold tracking-tight text-white leading-tight font-sans">
                                {finalTitle}
                            </h1>
                            <p className="gsap-fade-up text-sm sm:text-base text-zinc-400 leading-relaxed font-mono">
                                {finalDescription}
                            </p>
                            
                            <div className="pt-4 flex flex-col sm:flex-row gap-4">
                                <button 
                                    onClick={() => openWhatsAppChat(`Hello Mahmoud, I'm inquiring about ${finalTitle}`)}
                                    className="gsap-fade-up px-8 py-3.5 bg-white text-black hover:bg-zinc-200 text-xs font-bold font-mono uppercase tracking-widest transition-colors shadow-sm text-center"
                                >
                                    <span>INITIATE SERVICE SCOPE ➔</span>
                                </button>
                                <Link
                                    href="/estimator"
                                    className="border border-[#333333] hover:border-white text-zinc-300 hover:text-white px-8 py-3.5 text-xs font-bold font-mono tracking-widest uppercase transition-all text-center"
                                >
                                    ESTIMATE COST
                                </Link>
                            </div>
                        </div>
                        
                        {/* Image Showcase (6 cols) */}
                        <div className="lg:col-span-6 gsap-fade-up bg-[#161616] border border-[#2B2B2B] overflow-hidden flex items-center justify-center p-6 min-h-[380px]">
                            {imageUrl ? (
                                <img 
                                    src={`/${imageUrl}`} 
                                    alt={finalTitle} 
                                    className="w-full h-auto object-contain max-h-[500px]" 
                                />
                            ) : (
                                <div className="text-zinc-600 font-mono text-xs flex flex-col items-center gap-3">
                                    <Box className="w-12 h-12 text-zinc-700" strokeWidth={1} />
                                    <span>[ENGINEERING SPECIFICATION DIAGRAM]</span>
                                </div>
                            )}
                        </div>

                    </div>
                </section>

                {/* DETAILED CONTENT SECTION */}
                {description && (
                    <section className="py-16 border-t border-[#222222] reveal-section">
                        <div className="max-w-4xl mx-auto px-6 lg:px-8">
                            <h2 className="text-xs font-mono font-bold text-[#748660] uppercase tracking-widest mb-6">
                                Technical Specifications &amp; Architecture
                            </h2>
                            <div 
                                className="gsap-fade-up prose prose-invert prose-p:text-zinc-400 prose-p:font-mono prose-p:text-xs sm:prose-p:text-sm prose-headings:text-white prose-headings:font-sans prose-a:text-[#748660] max-w-none rtl:prose-p:text-end rtl:prose-headings:text-end rtl:prose-ul:text-end"
                                dangerouslySetInnerHTML={{ __html: description }}
                            />
                        </div>
                    </section>
                )}

            </div>
        </PublicLayout>
    );
}

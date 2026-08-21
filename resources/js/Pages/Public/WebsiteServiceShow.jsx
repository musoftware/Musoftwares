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
                <div className="min-h-[60vh] flex items-center justify-center bg-white text-[#1d1d1f] font-sans">
                    <div className="text-center space-y-4">
                        <h2 className="text-2xl font-semibold text-[#1d1d1f]">{__('general.not_found') || 'Service Not Found'}</h2>
                        <Link href="/" className="text-xs font-semibold text-[#0071e3] hover:underline block">
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

            <div ref={mainRef} className="w-full bg-[#ffffff] text-[#1d1d1f] font-sans selection:bg-[#0071e3]/20 selection:text-[#0071e3] overflow-x-hidden pt-8 pb-28">
                
                {/* HERO SECTION */}
                <section className="max-w-[1400px] mx-auto px-6 lg:px-8 mb-16 reveal-section">
                    <Link 
                        href="/" 
                        className="gsap-fade-up inline-flex items-center text-xs font-semibold uppercase tracking-wider text-[#1d1d1f]/60 hover:text-[#0071e3] transition-colors mb-8"
                    >
                        <ArrowLeft className="w-3.5 h-3.5 me-2 rtl:ms-2 rtl:me-0 rtl:rotate-180" />
                        {__('general.back_to_home') || 'STUDIO HOME'}
                    </Link>

                    <div className="grid lg:grid-cols-12 gap-12 lg:gap-16 items-start">
                        
                        {/* Meta & Info (6 cols) */}
                        <div className="lg:col-span-6 space-y-6">
                            <span className="inline-block text-[11px] font-semibold uppercase tracking-wider text-[#0071e3] bg-[#0071e3]/10 border border-[#0071e3]/20 px-3.5 py-1 rounded-full shadow-2xs">
                                {__('general.services') || 'Engineering Service'}
                            </span>
                            <h1 className="gsap-fade-up text-3xl sm:text-5xl font-semibold tracking-tight text-[#1d1d1f] leading-tight font-sans">
                                {finalTitle}
                            </h1>
                            <p className="gsap-fade-up text-sm sm:text-base text-[#1d1d1f]/70 leading-relaxed font-sans">
                                {finalDescription}
                            </p>
                            
                            <div className="pt-4 flex flex-col sm:flex-row gap-4">
                                <button 
                                    onClick={() => openWhatsAppChat(`Hello Mahmoud, I'm inquiring about ${finalTitle}`)}
                                    className="gsap-fade-up px-8 py-3 bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-semibold rounded-[980px] transition-colors shadow-md shadow-blue-500/20 text-center cursor-pointer"
                                >
                                    <span>INITIATE SERVICE SCOPE ➔</span>
                                </button>
                                <Link
                                    href="/estimator"
                                    className="border border-black/10 hover:border-black/30 bg-white text-[#1d1d1f] hover:bg-[#f5f5f7] px-8 py-3 text-xs font-semibold rounded-[980px] tracking-wide transition-all text-center shadow-sm"
                                >
                                    ESTIMATE COST
                                </Link>
                            </div>
                        </div>
                        
                        {/* Image Showcase (6 cols) */}
                        <div className="lg:col-span-6 gsap-fade-up bg-white border border-black/5 rounded-[24px] shadow-xl overflow-hidden flex items-center justify-center p-6 min-h-[380px]">
                            {imageUrl ? (
                                <img 
                                    src={`/${imageUrl}`} 
                                    alt={finalTitle} 
                                    className="w-full h-auto object-contain max-h-[500px] rounded-lg" 
                                />
                            ) : (
                                <div className="text-[#1d1d1f]/40 text-xs flex flex-col items-center gap-3">
                                    <Box className="w-12 h-12 text-[#1d1d1f]/30" strokeWidth={1} />
                                    <span>[ENGINEERING SPECIFICATION DIAGRAM]</span>
                                </div>
                            )}
                        </div>

                    </div>
                </section>

                {/* DETAILED CONTENT SECTION */}
                {description && (
                    <section className="py-16 border-t border-black/5 reveal-section">
                        <div className="max-w-4xl mx-auto px-6 lg:px-8">
                            <h2 className="text-xs font-semibold text-[#0071e3] uppercase tracking-wider mb-6">
                                Technical Specifications &amp; Architecture
                            </h2>
                            <div 
                                className="gsap-fade-up prose prose-neutral prose-p:text-[#1d1d1f]/70 prose-headings:text-[#1d1d1f] prose-a:text-[#0071e3] max-w-none rtl:prose-p:text-end rtl:prose-headings:text-end rtl:prose-ul:text-end"
                                dangerouslySetInnerHTML={{ __html: description }}
                            />
                        </div>
                    </section>
                )}

            </div>
        </PublicLayout>
    );
}

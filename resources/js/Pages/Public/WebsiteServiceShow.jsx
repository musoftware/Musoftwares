import { useMemo, useRef } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { __ } from '@/lib/i18n';
import PublicLayout from '@/Layouts/PublicLayout';
import { ArrowLeft, Box } from 'lucide-react';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';

gsap.registerPlugin(ScrollTrigger);

export default function WebsiteServiceShow({ service }) {
    const { locale } = usePage().props;
    const mainRef = useRef(null);

    useGSAP(() => {
        if (!service) return;
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
    }, { scope: mainRef, dependencies: [service] });

    if (!service) {
        return (
            <PublicLayout>
                <div className="min-h-[60vh] flex items-center justify-center bg-[#fafafa]">
                    <div className="text-center">
                        <h2 className="text-3xl font-bold text-[#111111] mb-4">{__('general.not_found') || 'Not Found'}</h2>
                        <Link href={route('home')} className="text-[#666666] font-semibold hover:text-[#111111] underline transition-colors">
                            {__('general.back_to_home') || 'Back to Home'}
                        </Link>
                    </div>
                </div>
            </PublicLayout>
        );
    }

    const title = locale === 'ar' ? service.title_ar : service.title_en;
    const subtitle = locale === 'ar' ? service.subtitle_ar : service.subtitle_en;
    const description = locale === 'ar' ? service.description_ar : service.description_en;

    const seoTitle = locale === 'ar' ? service.seo_title_ar : service.seo_title_en;
    const seoDescription = locale === 'ar' ? service.seo_description_ar : service.seo_description_en;
    const seoKeywords = locale === 'ar' ? service.seo_keywords_ar : service.seo_keywords_en;

    const finalTitle = seoTitle || title || '';
    const finalDescription = seoDescription || subtitle || description || '';
    const finalKeywords = seoKeywords || '';

    const appUrl = typeof window !== 'undefined' ? window.location.origin : '';
    const currentUrl = typeof window !== 'undefined' ? window.location.href : '';
    const imageUrl = locale === 'ar' ? service.primary_image_ar : service.primary_image_en;
    const fullImageUrl = imageUrl && appUrl ? `${appUrl}/${imageUrl}` : '';

    const schemaData = {
        "@context": "https://schema.org",
        "@type": "Service",
        "name": finalTitle,
        "description": finalDescription,
        "provider": {
            "@type": "Organization",
            "name": "Musoftware",
            "url": appUrl
        },
        "url": currentUrl,
        "image": fullImageUrl || undefined
    };

    return (
        <PublicLayout>
            <Head>
                <title>{`${finalTitle} | ${__('general.musoftware_unified_workspace') || 'Musoftware'}`}</title>
                <meta name="description" content={finalDescription} />
                {finalKeywords && <meta name="keywords" content={finalKeywords} />}
                
                <meta property="og:title" content={finalTitle} />
                <meta property="og:description" content={finalDescription} />
                {fullImageUrl && <meta property="og:image" content={fullImageUrl} />}
                <meta property="og:url" content={currentUrl} />
                <meta property="og:type" content="website" />
                
                <meta name="twitter:card" content="summary_large_image" />
                <meta name="twitter:title" content={finalTitle} />
                <meta name="twitter:description" content={finalDescription} />
                {fullImageUrl && <meta name="twitter:image" content={fullImageUrl} />}
                
                {currentUrl && <link rel="canonical" href={currentUrl} />}
                <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(schemaData) }} />
            </Head>

            <div ref={mainRef} className="w-full bg-[#fafafa] text-[#111111] font-sans selection:bg-[#111111] selection:text-white overflow-x-hidden">
                
                {/* HERO SECTION */}
                <section className="pt-32 pb-16 lg:pt-40 lg:pb-24 bg-[#fafafa] border-b border-[#e5e5e5] reveal-section">
                    <div className="max-w-[80rem] mx-auto px-6 lg:px-8">
                        <Link 
                            href={route('home')} 
                            className="gsap-fade-up inline-flex items-center text-xs font-bold uppercase tracking-widest text-[#888888] hover:text-[#111111] transition-colors mb-12"
                        >
                            <ArrowLeft className="w-4 h-4 me-2 rtl:ms-2 rtl:me-0 rtl:rotate-180" />
                            {__('general.back_to_home') || 'Back to Home'}
                        </Link>

                        <div className="grid lg:grid-cols-2 gap-12 lg:gap-24 items-center">
                            <div>
                                <div className="gsap-fade-up inline-flex items-center gap-2 px-3 py-1 border border-[#e5e5e5] text-xs font-semibold text-[#666666] tracking-widest uppercase mb-6 bg-white">
                                    <span className="flex h-1.5 w-1.5 rounded-full bg-[#111111]"></span>
                                    {__('general.services') || 'Service'}
                                </div>
                                <h1 className="gsap-fade-up text-4xl lg:text-6xl font-bold tracking-tight text-[#111111] leading-[1.05] mb-6">
                                    {title}
                                </h1>
                                <p className="gsap-fade-up text-xl text-[#666666] leading-relaxed font-normal mb-10">
                                    {subtitle}
                                </p>
                                <button 
                                    onClick={() => window.dispatchEvent(new Event('open-guest-ticket'))}
                                    className="gsap-fade-up bg-[#111111] hover:bg-[#333333] text-white px-8 h-12 text-sm font-bold tracking-widest uppercase transition-colors"
                                >
                                    {__('general.submit_guest_ticket') || 'Request Service'}
                                </button>
                            </div>
                            <div className="gsap-fade-up bg-white p-4 border border-[#e5e5e5] min-h-[300px] flex items-center justify-center relative">
                                {imageUrl ? (
                                    <>
                                        <img 
                                            src={`/${imageUrl}`} 
                                            alt={title} 
                                            className="w-full h-auto object-cover border border-[#e5e5e5] max-h-[600px] grayscale hover:grayscale-0 transition-all duration-700" 
                                        />
                                        <div className="absolute top-8 end-8 bg-white text-[#111111] text-[10px] font-bold px-3 py-1 uppercase tracking-widest border border-[#e5e5e5]">
                                            {__('English')}
                                        </div>
                                    </>
                                ) : (
                                    <Box className="w-16 h-16 text-[#e5e5e5]" strokeWidth={1} />
                                )}
                            </div>
                        </div>
                    </div>
                </section>

                {/* DETAILED CONTENT SECTION */}
                <section className="py-24 lg:py-32 bg-white reveal-section">
                    <div className="max-w-[80rem] mx-auto px-6 lg:px-8">
                        <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-24">
                            <div className="lg:col-span-4 border-b lg:border-b-0 lg:border-e border-[#e5e5e5] pb-8 lg:pb-0 lg:pe-12">
                                <h2 className="gsap-fade-up text-xs font-bold text-[#888888] tracking-widest uppercase mb-4">
                                    {__('general.service_description') || 'Service Description'}
                                </h2>
                                <h3 className="gsap-fade-up text-2xl font-bold text-[#111111] tracking-tight">
                                    {__('general.deep_dive')}</h3>
                            </div>
                            <div className="lg:col-span-8">
                                <div 
                                    className="gsap-fade-up prose prose-lg max-w-none text-[#666666] prose-headings:text-[#111111] prose-headings:font-bold prose-headings:tracking-tight prose-a:text-[#111111] prose-a:font-semibold hover:prose-a:text-[#666666] prose-img:border prose-img:border-[#e5e5e5] prose-img:rounded-none rtl:prose-p:text-end rtl:prose-headings:text-end rtl:prose-ul:text-end rtl:prose-li:text-end rtl:prose-blockquote:text-end rtl:prose-blockquote:border-e-4 rtl:prose-blockquote:border-s-0 rtl:prose-blockquote:pe-4 rtl:prose-blockquote:ps-0 whitespace-pre-wrap"
                                    dangerouslySetInnerHTML={{ __html: description }}
                                />
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}

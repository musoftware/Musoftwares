import { useMemo } from 'react';
import { Head, Link } from '@inertiajs/react';
import { __ } from '@/lib/i18n';
import PublicLayout from '@/Layouts/PublicLayout';
import { motion } from 'framer-motion';
import { ArrowLeft } from 'lucide-react';
import { portfolioItems } from '@/lib/portfolioData';

export default function PortfolioShow({ slug }) {
    const item = useMemo(() => portfolioItems.find((p) => p.slug === slug), [slug]);

    if (!item) {
        return (
            <PublicLayout>
                <div className="min-h-[60vh] flex items-center justify-center">
                    <div className="text-center">
                        <h2 className="text-3xl font-bold text-slate-800 mb-4">{__('general.not_found') || 'Not Found'}</h2>
                        <Link href={route('portfolio')} className="text-emerald-600 hover:underline">
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

            {/* HERO SECTION */}
            <section className="relative pt-32 pb-16 lg:pt-40 lg:pb-20 overflow-hidden bg-slate-900 text-white">
                <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-slate-800 via-slate-900 to-slate-950 opacity-90"></div>
                
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8 relative z-10">
                    <Link 
                        href={route('portfolio')} 
                        className="inline-flex items-center text-slate-400 hover:text-emerald-400 transition-colors mb-8 font-medium"
                    >
                        <ArrowLeft className="w-5 h-5 mr-2 rtl:ml-2 rtl:mr-0 rtl:rotate-180" />
                        {__('general.back_to_portfolio') || 'Back to Portfolio'}
                    </Link>

                    <div className="grid lg:grid-cols-2 gap-12 items-center">
                        <div>
                            <p className="text-sm font-semibold text-emerald-400 uppercase tracking-widest mb-4">
                                {item.cat}
                            </p>
                            <h1 className="text-4xl lg:text-6xl font-extrabold tracking-tight text-white leading-[1.1] mb-6">
                                {__(`general.${item.titleKey}`)}
                            </h1>
                            <p className="text-xl text-slate-300 leading-relaxed font-light">
                                {__(`general.${item.descKey}`)}
                            </p>
                        </div>
                        <motion.div 
                            initial={{ opacity: 0, x: 20 }}
                            animate={{ opacity: 1, x: 0 }}
                            transition={{ duration: 0.6 }}
                            className="relative rounded-2xl overflow-hidden shadow-2xl border border-slate-700 bg-slate-800/50"
                        >
                            <img 
                                src={item.img} 
                                alt={__(`general.${item.titleKey}`)} 
                                className="w-full h-auto object-cover max-h-[500px]" 
                            />
                        </motion.div>
                    </div>
                </div>
            </section>

            {/* DETAILED CONTENT SECTION */}
            <section className="py-16 lg:py-24 bg-white relative">
                <div className="max-w-4xl mx-auto px-6 lg:px-8">
                    <motion.div 
                        initial={{ opacity: 0, y: 20 }}
                        whileInView={{ opacity: 1, y: 0 }}
                        viewport={{ once: true }}
                        transition={{ duration: 0.6 }}
                        className="prose prose-lg prose-slate max-w-none prose-a:text-emerald-600 hover:prose-a:text-emerald-500 prose-img:rounded-xl prose-img:shadow-lg rtl:prose-p:text-right rtl:prose-headings:text-right rtl:prose-ul:text-right rtl:prose-li:text-right rtl:prose-blockquote:text-right rtl:prose-blockquote:border-r-4 rtl:prose-blockquote:border-l-0 rtl:prose-blockquote:pr-4 rtl:prose-blockquote:pl-0"
                        dangerouslySetInnerHTML={{ __html: content }}
                    />
                </div>
            </section>
        </PublicLayout>
    );
}

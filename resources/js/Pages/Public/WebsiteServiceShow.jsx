import { useMemo } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { __ } from '@/lib/i18n';
import PublicLayout from '@/Layouts/PublicLayout';
import { motion } from 'framer-motion';
import { ArrowLeft, Box, CheckCircle } from 'lucide-react';
import { Button } from '@/Components/ui/button';

export default function WebsiteServiceShow({ service }) {
    const { locale } = usePage().props;

    if (!service) {
        return (
            <PublicLayout>
                <div className="min-h-[60vh] flex items-center justify-center">
                    <div className="text-center">
                        <h2 className="text-3xl font-bold text-slate-800 mb-4">{__('general.not_found') || 'Not Found'}</h2>
                        <Link href={route('home')} className="text-emerald-600 hover:underline">
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

    return (
        <PublicLayout>
            <Head>
                <title>{`${title} | ${__('general.musoftware_unified_workspace') || 'Musoftware'}`}</title>
                <meta name="description" content={subtitle || description} />
            </Head>

            {/* HERO SECTION */}
            <section className="relative pt-32 pb-16 lg:pt-40 lg:pb-20 overflow-hidden bg-slate-900 text-white">
                <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-slate-800 via-slate-900 to-slate-950 opacity-90"></div>
                
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8 relative z-10">
                    <Link 
                        href={route('home')} 
                        className="inline-flex items-center text-slate-400 hover:text-emerald-400 transition-colors mb-8 font-medium"
                    >
                        <ArrowLeft className="w-5 h-5 mr-2 rtl:ml-2 rtl:mr-0 rtl:rotate-180" />
                        {__('general.back_to_home') || 'Back to Home'}
                    </Link>

                    <div className="grid lg:grid-cols-2 gap-12 items-center">
                        <div>
                            <p className="text-sm font-semibold text-emerald-400 uppercase tracking-widest mb-4">
                                {__('general.services') || 'Service'}
                            </p>
                            <h1 className="text-4xl lg:text-6xl font-extrabold tracking-tight text-white leading-[1.1] mb-6">
                                {title}
                            </h1>
                            <p className="text-xl text-slate-300 leading-relaxed font-light mb-8">
                                {subtitle}
                            </p>
                            <Button 
                                onClick={() => window.dispatchEvent(new Event('open-guest-ticket'))}
                                size="lg" 
                                className="bg-emerald-500 hover:bg-emerald-400 text-slate-900 rounded-full px-10 h-14 text-base font-semibold shadow-xl hover:shadow-2xl transition-all"
                            >
                                {__('general.submit_guest_ticket') || 'Request Service'}
                            </Button>
                        </div>
                        <motion.div 
                            initial={{ opacity: 0, x: 20 }}
                            animate={{ opacity: 1, x: 0 }}
                            transition={{ duration: 0.6 }}
                            className="relative rounded-2xl overflow-hidden shadow-2xl border border-slate-700 bg-slate-800/50 flex items-center justify-center min-h-[300px]"
                        >
                            {service.image_path ? (
                                <img 
                                    src={`/${service.image_path}`} 
                                    alt={title} 
                                    className="w-full h-auto object-cover max-h-[500px]" 
                                />
                            ) : (
                                <Box className="w-24 h-24 text-slate-600" />
                            )}
                        </motion.div>
                    </div>
                </div>
            </section>

            {/* DETAILED CONTENT SECTION */}
            <section className="py-16 lg:py-24 bg-slate-50 relative">
                <div className="max-w-4xl mx-auto px-6 lg:px-8">
                    <div className="bg-white rounded-3xl p-8 md:p-12 shadow-sm border border-slate-200">
                        <h2 className="text-2xl font-bold text-slate-900 mb-6 border-b border-slate-100 pb-4">
                            {__('general.service_description') || 'Service Description'}
                        </h2>
                        <motion.div 
                            initial={{ opacity: 0, y: 20 }}
                            whileInView={{ opacity: 1, y: 0 }}
                            viewport={{ once: true }}
                            transition={{ duration: 0.6 }}
                            className="prose prose-lg prose-slate max-w-none prose-a:text-emerald-600 hover:prose-a:text-emerald-500 prose-img:rounded-xl prose-img:shadow-lg rtl:prose-p:text-right rtl:prose-headings:text-right rtl:prose-ul:text-right rtl:prose-li:text-right rtl:prose-blockquote:text-right rtl:prose-blockquote:border-r-4 rtl:prose-blockquote:border-l-0 rtl:prose-blockquote:pr-4 rtl:prose-blockquote:pl-0 whitespace-pre-wrap"
                        >
                            {description}
                        </motion.div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

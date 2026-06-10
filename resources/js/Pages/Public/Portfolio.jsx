import { __ } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { motion } from 'framer-motion';
import { portfolioItems } from '@/lib/portfolioData';

export default function Portfolio() {

    return (
        <PublicLayout>
            <Head>
                <title>{`${__('general.landing_portfolio_title')} | ${__('general.musoftware_unified_workspace') || 'Musoftware'}`}</title>
                <meta name="description" content={__('general.landing_portfolio_desc')} />
            </Head>

            {/* HERO SECTION */}
            <section className="relative pt-32 pb-16 lg:pt-48 lg:pb-24 overflow-hidden bg-slate-900 text-white">
                <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-slate-800 via-slate-900 to-slate-950 opacity-90"></div>
                
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8 relative z-10 text-center">
                    <div className="max-w-4xl mx-auto">
                        <p className="text-sm font-semibold text-emerald-400 uppercase tracking-widest mb-4">
                            {__('general.landing_portfolio_badge') || 'Our Work'}
                        </p>
                        <h1 className="text-5xl lg:text-7xl font-extrabold tracking-tight text-white leading-[1.1] mb-8">
                            {__('general.landing_portfolio_title')}
                        </h1>
                        <p className="text-xl lg:text-2xl text-slate-400 leading-relaxed font-light mb-12 max-w-2xl mx-auto">
                            {__('general.landing_portfolio_desc')}
                        </p>
                    </div>
                </div>
            </section>

            {/* PORTFOLIO GRID */}
            <section className="py-16 lg:py-24 bg-slate-50 relative border-t border-slate-100">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8">
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                        {portfolioItems.map((item, index) => (
                            <motion.div 
                                key={index} 
                                initial={{ opacity: 0, y: 20 }}
                                whileInView={{ opacity: 1, y: 0 }}
                                viewport={{ once: true, margin: "-50px" }}
                                transition={{ duration: 0.5, delay: index % 4 * 0.1 }}
                                className="h-[250px] rounded-3xl overflow-hidden relative group cursor-pointer border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300"
                                onClick={() => {}}
                            >
                                <img src={item.img} alt={`Portfolio ${index + 1}`} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 pointer-events-none" />
                                <Link href={route('portfolio.show', item.slug)} className="absolute inset-0 bg-slate-900/70 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-6 text-left z-10">
                                    <span className="text-emerald-400 text-xs font-bold tracking-wider uppercase mb-1 translate-y-4 group-hover:translate-y-0 transition-transform duration-300">
                                        {item.cat}
                                    </span>
                                    <h3 className="text-lg font-bold text-white mb-2 translate-y-4 group-hover:translate-y-0 transition-transform duration-300 delay-75 line-clamp-1">
                                        {__(`general.${item.titleKey}`)}
                                    </h3>
                                    <p className="text-slate-300 text-xs line-clamp-2 translate-y-4 group-hover:translate-y-0 transition-transform duration-300 delay-100">
                                        {__(`general.${item.descKey}`)}
                                    </p>
                                </Link>
                            </motion.div>
                        ))}
                    </div>
                </div>
            </section>



        </PublicLayout>
    );
}

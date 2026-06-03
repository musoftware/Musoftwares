import { useState } from 'react';
import { __ } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Button } from '@/Components/ui/button';
import { motion } from 'framer-motion';
import { 
    Monitor, Smartphone, Server, CheckCircle, 
    ArrowRight, LayoutDashboard, Ticket, FolderKanban
} from 'lucide-react';

const fadeUp = {
    hidden: { opacity: 0, y: 30 },
    visible: { opacity: 1, y: 0, transition: { duration: 0.6, ease: [0.16, 1, 0.3, 1] } }
};

const staggerContainer = {
    hidden: { opacity: 0 },
    visible: {
        opacity: 1,
        transition: { staggerChildren: 0.1 }
    }
};

export default function Home({ serviceItems = [], currency = 'USD' }) {
    return (
        <PublicLayout>
            <Head>
                <title>{__('general.musoftware_unified_workspace') || 'Musoftware'}</title>
                <meta name="description" content={__('general.landing_hero_subtitle')} />
            </Head>

            {/* HERO SECTION */}
            <section className="relative pt-32 pb-24 lg:pt-48 lg:pb-32 overflow-hidden bg-white">
                <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-slate-50 via-white to-white opacity-90"></div>
                
                <div className="absolute top-20 right-0 -mr-48 opacity-10 pointer-events-none">
                    <svg width="800" height="800" viewBox="0 0 800 800" xmlns="http://www.w3.org/2000/svg">
                        <g stroke="#000" strokeWidth="1" fill="none" fillRule="evenodd">
                            <path d="M400 0v800M0 400h800M200 0v800M600 0v800M0 200h800M0 600h800" opacity="0.2"/>
                            <circle cx="400" cy="400" r="200" strokeDasharray="5,5" />
                            <circle cx="400" cy="400" r="300" strokeDasharray="5,5" />
                            <path d="M200 200l400 400M200 600L600 200" />
                        </g>
                    </svg>
                </div>

                <div className="max-w-[90rem] mx-auto px-6 lg:px-8 relative z-10 text-center">
                    <div className="max-w-4xl mx-auto">
                        <motion.div 
                            initial="hidden"
                            animate="visible"
                            variants={staggerContainer}
                        >
                            <motion.h1 variants={fadeUp} className="text-5xl lg:text-7xl font-extrabold tracking-tight text-slate-900 leading-[1.1] mb-8">
                                {__('general.landing_hero_title')}
                            </motion.h1>
                            <motion.p variants={fadeUp} className="text-xl lg:text-2xl text-slate-500 leading-relaxed font-light mb-12 max-w-2xl mx-auto">
                                {__('general.landing_hero_subtitle')}
                            </motion.p>
                            <motion.div variants={fadeUp} className="flex flex-col sm:flex-row gap-4 justify-center">
                                <a href="mailto:admin@musoftwares.com">
                                    <Button size="lg" className="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white rounded-full px-10 h-14 text-base font-semibold transition-all">
                                        {__('general.landing_hero_cta')}
                                    </Button>
                                </a>
                                <Link href="/platforms">
                                    <Button size="lg" variant="outline" className="w-full sm:w-auto hover:bg-slate-50 text-slate-900 border-slate-200 rounded-full px-10 h-14 text-base font-semibold transition-all">
                                        {__('general.landing_hero_secondary_cta')}
                                    </Button>
                                </Link>
                            </motion.div>
                        </motion.div>
                    </div>
                </div>
            </section>

            {/* SERVICES SECTION */}
            <section id="services" className="py-24 lg:py-32 bg-slate-50 relative border-y border-slate-100">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8">
                    <div className="mb-20 text-center">
                        <p className="text-sm font-semibold text-slate-500 uppercase tracking-widest mb-4">
                            {__('general.landing_services_badge')}
                        </p>
                        <h2 className="text-4xl font-extrabold text-slate-900 tracking-tight mb-4">
                            {__('general.landing_services_title')}
                        </h2>
                        <p className="text-xl text-slate-500 font-light max-w-2xl mx-auto">
                            {__('general.landing_services_desc')}
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        {/* Service 1: Web Apps */}
                        <div className="group bg-white rounded-3xl p-10 border border-slate-100 shadow-sm hover:shadow-md transition-all duration-300">
                            <div className="w-14 h-14 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mb-8 text-slate-700 group-hover:bg-slate-900 group-hover:text-white transition-all duration-300">
                                <Monitor className="w-7 h-7" />
                            </div>
                            <h3 className="text-2xl font-bold text-slate-900 mb-4">{__('general.landing_services_web')}</h3>
                            <p className="text-lg text-slate-500 font-light leading-relaxed">
                                {__('general.landing_services_web_desc')}
                            </p>
                        </div>

                        {/* Service 2: ERPs */}
                        <div className="group bg-white rounded-3xl p-10 border border-slate-100 shadow-sm hover:shadow-md transition-all duration-300">
                            <div className="w-14 h-14 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mb-8 text-slate-700 group-hover:bg-slate-900 group-hover:text-white transition-all duration-300">
                                <Server className="w-7 h-7" />
                            </div>
                            <h3 className="text-2xl font-bold text-slate-900 mb-4">{__('general.landing_services_erp')}</h3>
                            <p className="text-lg text-slate-500 font-light leading-relaxed">
                                {__('general.landing_services_erp_desc')}
                            </p>
                        </div>

                        {/* Service 3: Mobile Apps */}
                        <div className="group bg-white rounded-3xl p-10 border border-slate-100 shadow-sm hover:shadow-md transition-all duration-300">
                            <div className="w-14 h-14 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mb-8 text-slate-700 group-hover:bg-slate-900 group-hover:text-white transition-all duration-300">
                                <Smartphone className="w-7 h-7" />
                            </div>
                            <h3 className="text-2xl font-bold text-slate-900 mb-4">{__('general.landing_services_mobile')}</h3>
                            <p className="text-lg text-slate-500 font-light leading-relaxed">
                                {__('general.landing_services_mobile_desc')}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            {/* SAAS SECTION */}
            <section className="py-24 lg:py-32 bg-white relative">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8">
                    <div className="flex flex-col lg:flex-row gap-16 items-center">
                        <div className="lg:w-1/2">
                            <p className="text-sm font-semibold text-slate-500 uppercase tracking-widest mb-4">
                                {__('general.landing_saas_badge')}
                            </p>
                            <h2 className="text-4xl lg:text-5xl font-extrabold text-slate-900 tracking-tight mb-6">
                                {__('general.landing_saas_title')}
                            </h2>
                            <p className="text-xl text-slate-500 font-light mb-10 leading-relaxed">
                                {__('general.landing_saas_desc')}
                            </p>

                            <ul className="space-y-6">
                                <li className="flex items-center gap-4">
                                    <div className="flex-shrink-0 w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-900">
                                        <FolderKanban className="w-5 h-5" />
                                    </div>
                                    <span className="text-lg font-medium text-slate-700">{__('general.landing_saas_feature_1')}</span>
                                </li>
                                <li className="flex items-center gap-4">
                                    <div className="flex-shrink-0 w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-900">
                                        <CheckCircle className="w-5 h-5" />
                                    </div>
                                    <span className="text-lg font-medium text-slate-700">{__('general.landing_saas_feature_2')}</span>
                                </li>
                                <li className="flex items-center gap-4">
                                    <div className="flex-shrink-0 w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-900">
                                        <Ticket className="w-5 h-5" />
                                    </div>
                                    <span className="text-lg font-medium text-slate-700">{__('general.landing_saas_feature_3')}</span>
                                </li>
                            </ul>
                        </div>
                        <div className="lg:w-1/2 w-full">
                            {/* Dashboard Mockup (Apple-style Glassmorphism) */}
                            <div className="relative rounded-2xl bg-slate-100 p-2 shadow-2xl border border-slate-200">
                                <div className="absolute inset-0 bg-gradient-to-tr from-slate-200 to-white opacity-50 rounded-2xl"></div>
                                <div className="relative bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden aspect-[4/3] flex flex-col">
                                    {/* Mock Header */}
                                    <div className="h-12 border-b border-slate-100 flex items-center px-4 gap-2 bg-slate-50/50">
                                        <div className="w-3 h-3 rounded-full bg-red-400"></div>
                                        <div className="w-3 h-3 rounded-full bg-amber-400"></div>
                                        <div className="w-3 h-3 rounded-full bg-emerald-400"></div>
                                    </div>
                                    {/* Mock Content */}
                                    <div className="flex-1 p-6 flex flex-col gap-4">
                                        <div className="flex gap-4 mb-4">
                                            <div className="w-1/3 h-24 bg-slate-50 rounded-xl border border-slate-100"></div>
                                            <div className="w-1/3 h-24 bg-slate-50 rounded-xl border border-slate-100"></div>
                                            <div className="w-1/3 h-24 bg-slate-50 rounded-xl border border-slate-100"></div>
                                        </div>
                                        <div className="w-full h-8 bg-slate-100 rounded-md w-1/3 mb-2"></div>
                                        <div className="w-full h-12 bg-slate-50 rounded-lg border border-slate-100"></div>
                                        <div className="w-full h-12 bg-slate-50 rounded-lg border border-slate-100"></div>
                                        <div className="w-full h-12 bg-slate-50 rounded-lg border border-slate-100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* PORTFOLIO SECTION */}
            <section className="py-24 lg:py-32 bg-slate-900 text-white relative border-y border-slate-800">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8 text-center">
                    <p className="text-sm font-semibold text-slate-400 uppercase tracking-widest mb-4">
                        {__('general.landing_portfolio_badge')}
                    </p>
                    <h2 className="text-4xl font-extrabold tracking-tight mb-4 text-white">
                        {__('general.landing_portfolio_title')}
                    </h2>
                    <p className="text-xl text-slate-400 font-light max-w-2xl mx-auto mb-16">
                        {__('general.landing_portfolio_desc')}
                    </p>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
                        {/* Project 1 */}
                        <div className="group rounded-3xl bg-slate-800 border border-slate-700 overflow-hidden aspect-[4/3] relative flex items-center justify-center text-slate-500 hover:text-white transition-all cursor-pointer">
                            <span className="text-lg font-medium">Project Preview 1</span>
                            {/* Insert actual image here later: <img src="..." className="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition-opacity" /> */}
                        </div>
                        {/* Project 2 */}
                        <div className="group rounded-3xl bg-slate-800 border border-slate-700 overflow-hidden aspect-[4/3] relative flex items-center justify-center text-slate-500 hover:text-white transition-all cursor-pointer">
                            <span className="text-lg font-medium">Project Preview 2</span>
                        </div>
                    </div>

                    <Button variant="outline" className="border-slate-700 text-slate-300 hover:bg-slate-800 hover:text-white rounded-full px-8 h-12">
                        {__('general.landing_portfolio_view_all')} <ArrowRight className="ml-2 w-4 h-4" />
                    </Button>
                </div>
            </section>

            {/* FINAL CTA / CONTACT */}
            <section className="py-24 lg:py-32 bg-white text-center">
                <div className="max-w-3xl mx-auto px-6">
                    <h2 className="text-4xl lg:text-5xl font-extrabold text-slate-900 tracking-tight mb-8">
                        {__('general.landing_contact_title')}
                    </h2>
                    <p className="text-xl text-slate-500 font-light mb-10 max-w-2xl mx-auto">
                        {__('general.landing_contact_desc')}
                    </p>
                    <a href="mailto:admin@musoftwares.com">
                        <Button size="lg" className="bg-slate-900 hover:bg-slate-800 text-white rounded-full px-12 h-16 text-lg font-bold shadow-xl hover:shadow-2xl transition-all">
                            {__('general.landing_contact_cta')}
                        </Button>
                    </a>
                </div>
            </section>

        </PublicLayout>
    );
}

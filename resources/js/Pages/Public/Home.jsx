import { useState, useRef, useEffect } from 'react';
import { __ } from '@/lib/i18n';
import { Head, Link, usePage } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Button } from '@/Components/ui/button';
import { motion, AnimatePresence } from 'framer-motion';
import { 
    Monitor, Smartphone, Server, CheckCircle, 
    ArrowRight, LayoutDashboard, Ticket, FolderKanban, X,
    Users, MessageSquare, TrendingUp, Calendar, Store, Wrench,
    Download, MessageCircle, Search, Box, Star, Quote, Zap, Shield, Rocket, ArrowUpRight, BarChart
} from 'lucide-react';

const testimonialsData = [
    { name: 'Ahmed Hassan', company: 'TechVision', text: 'MuSoftwares completely transformed how we handle our internal operations. The ERP system is intuitive and powerful.', rating: 5 },
    { name: 'Sara El-Sayed', company: 'RetailPro', text: 'The custom POS and inventory system they built for us saved us hundreds of hours every month. Highly recommended.', rating: 5 },
    { name: 'Mohamed Ali', company: 'Global Logistics', text: 'Excellent team and unparalleled support. Their SaaS solutions are reliable and scale perfectly with our business growth.', rating: 5 },
    { name: 'Omar Youssef', company: 'StartUp Inc', text: 'They delivered our platform ahead of schedule with exceptional quality. Their attention to detail is truly impressive.', rating: 5 },
];

const statisticsData = [
    { value: '500+', label: 'Projects Delivered' },
    { value: '99%', label: 'Client Satisfaction' },
    { value: '24/7', label: 'Support Available' },
    { value: '50+', label: 'Enterprise Clients' }
];

const clientsData = [
    { name: "AMC Academy", logo: "/images/clients/amcacademy.png" },
    { name: "Aswan", logo: "/images/clients/aswan.png" },
    { name: "Egy Servers", logo: "/images/clients/egy-servers.png" },
    { name: "Jad Technology", logo: "/images/clients/jad-technology.png" },
    { name: "Mini Fatora", logo: "/images/clients/mini-fatora.png" },
    { name: "MIT", logo: "/images/clients/mit.png" },
    { name: "My Line", logo: "/images/clients/my-line.png" },
    { name: "OBD Ultra", logo: "/images/clients/obdultra.png" },
    { name: "Technosoft", logo: "/images/clients/technosoft.png" },
    { name: "Topline", logo: "/images/clients/topline.png" },
];

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

const portfolioItems = [
    { img: '/images/portfolio/kbdny.png', titleKey: 'portfolio_kbdny_title', descKey: 'portfolio_kbdny_desc', cat: 'Platform' , challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']},
    { img: '/images/portfolio/stockmanager.png', titleKey: 'portfolio_stock_manager_title', descKey: 'portfolio_stock_manager_desc', cat: 'ERP' , challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']},
    { img: '/images/portfolio/minifatora.png', titleKey: 'portfolio_mini_fatora_title', descKey: 'portfolio_mini_fatora_desc', cat: 'SaaS' , challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']},
    { img: '/images/portfolio/vodafone-crm.jpg', titleKey: 'portfolio_vodafone_crm_title', descKey: 'portfolio_vodafone_crm_desc', cat: 'CRM' , challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']},
    { img: '/images/portfolio/amcacademy.jpg', titleKey: 'portfolio_amc_academy', descKey: 'portfolio_amc_academy_desc', cat: 'E-Learning' , challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']},
    { img: '/images/portfolio/projectmanager.png', titleKey: 'portfolio_project_manager', descKey: 'portfolio_project_manager_desc', cat: 'Management' , challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']},
    { img: '/images/portfolio/telecom-system.png', titleKey: 'portfolio_telecom_system_title', descKey: 'portfolio_telecom_system_desc', cat: 'Platform' , challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']},
    { img: '/images/portfolio/altayaraa.png', titleKey: 'portfolio_altayaraa_title', descKey: 'portfolio_altayaraa_desc', cat: 'E-Commerce' , challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']},
    { img: '/images/portfolio/forex-app.png', titleKey: 'portfolio_forex_app_title', descKey: 'portfolio_forex_app_desc', cat: 'Mobile App' , challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']},
    { img: '/images/portfolio/amcsocial.png', titleKey: 'portfolio_amc_social', descKey: 'portfolio_amc_social_desc', cat: 'Platform' , challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']},
    { img: '/images/portfolio/nokhpa.png', titleKey: 'portfolio_nokhpa_title', descKey: 'portfolio_nokhpa_desc', cat: 'E-Commerce' , challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']},
    { img: '/images/portfolio/duplicate-finder.jpg', titleKey: 'portfolio_duplicate_finder_title', descKey: 'portfolio_duplicate_finder_desc', cat: 'Desktop App' , challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']},
    { img: '/images/portfolio/map-extractor.jpg', titleKey: 'portfolio_map_extractor_title', descKey: 'portfolio_map_extractor_desc', cat: 'Desktop App' , challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']},
    { img: '/images/portfolio/instagram-manager.png', titleKey: 'portfolio_instagram_manager_title', descKey: 'portfolio_instagram_manager_desc', cat: 'Desktop App' , challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']},
    { img: '/images/portfolio/whatsapp-sender.png', titleKey: 'portfolio_whatsapp_sender_title', descKey: 'portfolio_whatsapp_sender_desc', cat: 'Desktop App' , challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']},
    { img: '/images/portfolio/telegram-sender.png', titleKey: 'portfolio_telegram_sender_title', descKey: 'portfolio_telegram_sender_desc', cat: 'Desktop App' , challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']},
    { img: '/images/portfolio/inbox-sender.png', titleKey: 'portfolio_inbox_sender_title', descKey: 'portfolio_inbox_sender_desc', cat: 'Desktop App' , challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']},
    { img: '/images/portfolio/heic-converter.png', titleKey: 'portfolio_heic_converter_title', descKey: 'portfolio_heic_converter_desc', cat: 'Desktop App' , challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']},
    { img: '/images/portfolio/text-studio.jpg', titleKey: 'portfolio_text_studio_title', descKey: 'portfolio_text_studio_desc', cat: 'Desktop App' , challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']},
    { img: '/images/portfolio/amctasks-downloader.png', titleKey: 'portfolio_amc_tasks_downloader', descKey: 'portfolio_amc_tasks_downloader_desc', cat: 'Desktop App' , challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']},
    { img: '/images/portfolio/stocktalk.png', titleKey: 'portfolio_stocktalk_ai_title', descKey: 'portfolio_stocktalk_ai_desc', cat: 'AI & Bot' , challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']},
    { img: '/images/portfolio/forex.png', titleKey: 'portfolio_forex_bot_title', descKey: 'portfolio_forex_bot_desc', cat: 'AI & Bot' , challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']},
    { img: '/images/portfolio/revFlow.png', titleKey: 'portfolio_revflow_title', descKey: 'portfolio_revflow_desc', cat: 'SaaS' , challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']},
    { img: '/images/portfolio/chartcash.png', titleKey: 'portfolio_chartcash_title', descKey: 'portfolio_chartcash_desc', cat: 'Dashboard' , challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']},
    { img: '/images/portfolio/chrome-ext-fb-id.png', titleKey: 'portfolio_fb_id_extractor_title', descKey: 'portfolio_fb_id_extractor_desc', cat: 'Extension' , challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']},
    { img: '/images/portfolio/khamsat-notifier.jpg', titleKey: 'portfolio_khamsat_notifier_title', descKey: 'portfolio_khamsat_notifier_desc', cat: 'Extension' , challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']},
    { img: '/images/portfolio/amemailcontrols.png', titleKey: 'portfolio_am_email_controls', descKey: 'portfolio_am_email_controls_desc', cat: 'Extension' , challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']},
    { img: '/images/portfolio/amctasks.jpg', titleKey: 'portfolio_amc_tasks', descKey: 'portfolio_amc_tasks_desc', cat: 'ERP' , challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']},
];

export default function Home({ serviceItems = [], currency = 'USD' }) {
    const [selectedItem, setSelectedItem] = useState(null);
    const carouselRef = useRef(null);
    const testimonialsRef = useRef(null);
    const [width, setWidth] = useState(0);
    const [testiWidth, setTestiWidth] = useState(0);
    const { website_services, locale } = usePage().props;

    useEffect(() => {
        if (carouselRef.current) {
            setWidth(carouselRef.current.scrollWidth - carouselRef.current.offsetWidth);
        }
        if (testimonialsRef.current) {
            setTestiWidth(testimonialsRef.current.scrollWidth - testimonialsRef.current.offsetWidth);
        }
    }, [carouselRef.current, testimonialsRef.current]);

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
                                <Button 
                                    onClick={() => window.dispatchEvent(new Event('open-guest-ticket'))}
                                    size="lg" 
                                    className="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white rounded-full px-10 h-14 text-base font-semibold transition-all shadow-lg hover:shadow-xl"
                                >
                                    {__('general.submit_guest_ticket') || 'Submit Guest Ticket'} <ArrowRight className="ml-2 w-5 h-5" />
                                </Button>
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

            {/* CLIENT LOGOS SECTION */}
            <section className="py-12 bg-white border-t border-slate-100 overflow-hidden">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8">
                    <p className="text-center text-sm font-semibold text-slate-400 uppercase tracking-widest mb-8">
                        {__('general.landing_trusted_by')}
                    </p>
                    
                    {/* Marquee Container */}
                    <div className="relative flex overflow-x-hidden group">
                        <motion.div
                            className="flex items-center gap-12 sm:gap-24 pr-12 sm:pr-24"
                            animate={{ x: ["0%", "-50%"] }}
                            transition={{ repeat: Infinity, ease: "linear", duration: 25 }}
                        >
                            {/* We render the array twice to create a seamless infinite loop */}
                            {[...clientsData, ...clientsData].map((client, index) => (
                                <img 
                                    key={`client-${index}`} 
                                    src={client.logo} 
                                    alt={client.name} 
                                    className="h-10 md:h-12 w-auto object-contain shrink-0 opacity-50 grayscale hover:grayscale-0 transition-all duration-300" 
                                />
                            ))}
                        </motion.div>
                    </div>
                </div>
            </section>

            {/* PROBLEM SECTION */}
            <section className="py-24 lg:py-32 bg-slate-50 relative border-t border-slate-100">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8">
                    <div className="mb-16 text-center max-w-3xl mx-auto">
                        <p className="text-sm font-semibold text-rose-500 uppercase tracking-widest mb-4">
                            {__('general.landing_problem_badge')}
                        </p>
                        <h2 className="text-4xl font-extrabold text-slate-900 tracking-tight mb-6">
                            {__('general.landing_problem_title')}
                        </h2>
                        <p className="text-xl text-slate-500 font-light leading-relaxed">
                            {__('general.landing_problem_desc')}
                        </p>
                    </div>
                    
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div className="bg-white rounded-3xl p-8 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-rose-200 hover:shadow-md transition-all duration-300">
                            <div className="w-12 h-12 bg-rose-50 rounded-xl flex items-center justify-center mb-6">
                                <Server className="w-6 h-6 text-rose-500" />
                            </div>
                            <h3 className="text-xl font-bold text-slate-900 mb-3">Fragmented Tools</h3>
                            <p className="text-slate-500 leading-relaxed">Using different platforms for CRM, accounting, and task management creates data silos.</p>
                        </div>
                        <div className="bg-white rounded-3xl p-8 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-amber-200 hover:shadow-md transition-all duration-300">
                            <div className="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center mb-6">
                                <Monitor className="w-6 h-6 text-amber-500" />
                            </div>
                            <h3 className="text-xl font-bold text-slate-900 mb-3">Manual Processes</h3>
                            <p className="text-slate-500 leading-relaxed">Wasting hundreds of hours on manual data entry and repetitive administrative tasks.</p>
                        </div>
                        <div className="bg-white rounded-3xl p-8 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-blue-200 hover:shadow-md transition-all duration-300">
                            <div className="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mb-6">
                                <BarChart className="w-6 h-6 text-blue-500" />
                            </div>
                            <h3 className="text-xl font-bold text-slate-900 mb-3">Lack of Visibility</h3>
                            <p className="text-slate-500 leading-relaxed">Unable to make quick decisions due to scattered metrics and delayed reporting.</p>
                        </div>
                    </div>
                </div>
            </section>

            {/* SERVICES SECTION */}
            <section id="services" className="py-24 lg:py-32 bg-white relative border-t border-slate-100">
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
                        {website_services && website_services.length > 0 ? (
                            website_services.map((service) => (
                                <Link href={route('website-services.show', service.slug)} key={service.id} className="group bg-white rounded-3xl p-10 border border-slate-100 shadow-sm hover:shadow-md transition-all duration-300 block text-left">
                                    <div className="w-14 h-14 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mb-8 text-slate-700 group-hover:bg-slate-900 transition-all duration-300 overflow-hidden">
                                        {(locale === 'ar' ? service.primary_image_ar : service.primary_image_en) ? (
                                            <img src={`/${locale === 'ar' ? service.primary_image_ar : service.primary_image_en}`} alt={locale === 'ar' ? service.title_ar : service.title_en} className="w-full h-full object-cover group-hover:opacity-90" />
                                        ) : (
                                            <Box className="w-7 h-7 group-hover:text-white" />
                                        )}
                                    </div>
                                    <h3 className="text-2xl font-bold text-slate-900 mb-4">{locale === 'ar' ? service.title_ar : service.title_en}</h3>
                                    <p className="text-lg text-slate-500 font-light leading-relaxed">
                                        {locale === 'ar' ? (service.description_ar || service.subtitle_ar || '') : (service.description_en || service.subtitle_en || '')}
                                    </p>
                                </Link>
                            ))
                        ) : (
                            <p className="col-span-1 md:col-span-3 text-center text-slate-500 py-12">No services available.</p>
                        )}
                    </div>
                </div>
            </section>

            {/* SAAS SECTION */}
            <section className="py-24 lg:py-32 bg-slate-50 relative border-t border-slate-200">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8">
                    <div className="text-center max-w-3xl mx-auto mb-16">
                        <p className="text-sm font-semibold text-emerald-600 uppercase tracking-widest mb-4">
                            {__('general.landing_saas_badge')}
                        </p>
                        <h2 className="text-4xl lg:text-5xl font-extrabold text-slate-900 tracking-tight mb-6">
                            {__('general.landing_saas_title')}
                        </h2>
                        <p className="text-xl text-slate-600 font-light leading-relaxed">
                            {__('general.landing_saas_desc')}
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        {/* 1. ERP - Spans 2 columns & 2 rows on large screens */}
                        <div className="group bg-white rounded-3xl p-10 border border-slate-200 hover:border-emerald-500/50 hover:shadow-2xl hover:shadow-emerald-900/5 transition-all duration-500 md:col-span-2 md:row-span-2 relative overflow-hidden">
                            <div className="absolute -right-20 -top-20 w-64 h-64 bg-emerald-500/5 rounded-full blur-3xl group-hover:bg-emerald-500/10 transition-all duration-500"></div>
                            <div className="w-16 h-16 bg-emerald-50 rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform duration-500">
                                <Server className="w-8 h-8 text-emerald-600" />
                            </div>
                            <h3 className="text-3xl font-bold text-slate-900 mb-4">{__('general.module_erp_title')}</h3>
                            <p className="text-slate-600 leading-relaxed text-lg max-w-md">
                                {__('general.module_erp_desc')}
                            </p>
                            <div className="mt-8 pt-8 border-t border-slate-100 grid grid-cols-2 gap-4">
                                <div>
                                    <p className="font-semibold text-slate-900 mb-1">Invoicing & Billing</p>
                                    <p className="text-sm text-slate-500">Multi-currency financial tracking</p>
                                </div>
                                <div>
                                    <p className="font-semibold text-slate-900 mb-1">Project Management</p>
                                    <p className="text-sm text-slate-500">Timers, tasks, and team collaboration</p>
                                </div>
                            </div>
                        </div>

                        {/* 2. CRM */}
                        <div className="group bg-white rounded-3xl p-8 border border-slate-200 hover:border-blue-500/50 hover:shadow-2xl hover:shadow-blue-900/5 transition-all duration-500 relative overflow-hidden">
                            <div className="absolute -right-10 -bottom-10 w-40 h-40 bg-blue-500/5 rounded-full blur-2xl group-hover:bg-blue-500/10 transition-all duration-500"></div>
                            <div className="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500">
                                <Users className="w-7 h-7 text-blue-600" />
                            </div>
                            <h3 className="text-xl font-bold text-slate-900 mb-3">{__('general.module_crm_title')}</h3>
                            <p className="text-slate-600 leading-relaxed text-sm relative z-10">
                                {__('general.module_crm_desc')}
                            </p>
                        </div>

                        {/* 3. SMS Gateway */}
                        <div className="group bg-white rounded-3xl p-8 border border-slate-200 hover:border-indigo-500/50 hover:shadow-2xl hover:shadow-indigo-900/5 transition-all duration-500 relative overflow-hidden">
                            <div className="absolute -left-10 -bottom-10 w-40 h-40 bg-indigo-500/5 rounded-full blur-2xl group-hover:bg-indigo-500/10 transition-all duration-500"></div>
                            <div className="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500">
                                <MessageSquare className="w-7 h-7 text-indigo-600" />
                            </div>
                            <h3 className="text-xl font-bold text-slate-900 mb-3">{__('general.module_sms_title')}</h3>
                            <p className="text-slate-600 leading-relaxed text-sm relative z-10">
                                {__('general.module_sms_desc')}
                            </p>
                        </div>

                        {/* 4. Gold Saver */}
                        <div className="group bg-white rounded-3xl p-8 border border-slate-200 hover:border-amber-500/50 hover:shadow-2xl hover:shadow-amber-900/5 transition-all duration-500 relative overflow-hidden xl:row-span-2">
                            <div className="absolute top-0 right-0 w-full h-32 bg-gradient-to-b from-amber-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                            <div className="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500 relative z-10">
                                <TrendingUp className="w-7 h-7 text-amber-600" />
                            </div>
                            <h3 className="text-xl font-bold text-slate-900 mb-3 relative z-10">{__('general.module_gold_title')}</h3>
                            <p className="text-slate-600 leading-relaxed text-sm relative z-10">
                                {__('general.module_gold_desc')}
                            </p>
                        </div>

                        {/* 5. Booking */}
                        <div className="group bg-white rounded-3xl p-8 border border-slate-200 hover:border-rose-500/50 hover:shadow-2xl hover:shadow-rose-900/5 transition-all duration-500 relative overflow-hidden">
                            <div className="w-14 h-14 bg-rose-50 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500">
                                <Calendar className="w-7 h-7 text-rose-600" />
                            </div>
                            <h3 className="text-xl font-bold text-slate-900 mb-3">{__('general.module_booking_title')}</h3>
                            <p className="text-slate-600 leading-relaxed text-sm">
                                {__('general.module_booking_desc')}
                            </p>
                        </div>

                        {/* 6. POS */}
                        <div className="group bg-white rounded-3xl p-8 border border-slate-200 hover:border-teal-500/50 hover:shadow-2xl hover:shadow-teal-900/5 transition-all duration-500 relative overflow-hidden">
                            <div className="w-14 h-14 bg-teal-50 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500">
                                <Store className="w-7 h-7 text-teal-600" />
                            </div>
                            <h3 className="text-xl font-bold text-slate-900 mb-3">{__('general.module_pos_title')}</h3>
                            <p className="text-slate-600 leading-relaxed text-sm">
                                {__('general.module_pos_desc')}
                            </p>
                        </div>

                        {/* 7. Tools & Addons */}
                        <div className="group bg-white rounded-3xl p-8 border border-slate-200 hover:border-violet-500/50 hover:shadow-2xl hover:shadow-violet-900/5 transition-all duration-500 md:col-span-2 xl:col-span-3 relative overflow-hidden flex flex-col md:flex-row items-start md:items-center gap-8">
                            <div className="absolute -right-20 -bottom-20 w-64 h-64 bg-violet-500/5 rounded-full blur-3xl group-hover:bg-violet-500/10 transition-all duration-500"></div>
                            <div className="flex-1 relative z-10">
                                <div className="w-14 h-14 bg-violet-50 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500">
                                    <Wrench className="w-7 h-7 text-violet-600" />
                                </div>
                                <h3 className="text-xl font-bold text-slate-900 mb-3">{__('general.module_tools_title')}</h3>
                                <p className="text-slate-600 leading-relaxed text-sm">
                                    {__('general.module_tools_desc')}
                                </p>
                            </div>
                            <div className="flex gap-4 opacity-50 group-hover:opacity-100 transition-opacity duration-500 relative z-10">
                                <div className="w-12 h-12 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-center shadow-sm"><Download className="w-5 h-5 text-slate-500" /></div>
                                <div className="w-12 h-12 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-center shadow-sm"><MessageCircle className="w-5 h-5 text-slate-500" /></div>
                                <div className="w-12 h-12 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-center shadow-sm"><Search className="w-5 h-5 text-slate-500" /></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* BENEFITS SECTION */}
            <section className="py-24 lg:py-32 bg-white relative border-t border-slate-100">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8">
                    <div className="mb-16 text-center max-w-3xl mx-auto">
                        <p className="text-sm font-semibold text-emerald-600 uppercase tracking-widest mb-4">
                            {__('general.landing_benefits_badge')}
                        </p>
                        <h2 className="text-4xl font-extrabold text-slate-900 tracking-tight mb-6">
                            {__('general.landing_benefits_title')}
                        </h2>
                        <p className="text-xl text-slate-500 font-light leading-relaxed">
                            {__('general.landing_benefits_desc')}
                        </p>
                    </div>
                    
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                        <div className="space-y-8">
                            <div className="flex gap-4">
                                <div className="w-12 h-12 rounded-xl bg-slate-50 flex items-center justify-center flex-shrink-0 border border-slate-100">
                                    <Zap className="w-6 h-6 text-emerald-500" />
                                </div>
                                <div>
                                    <h3 className="text-xl font-bold text-slate-900 mb-2">Automated Workflows</h3>
                                    <p className="text-slate-500 leading-relaxed">Eliminate repetitive tasks with intelligent automation that connects all your departments seamlessly.</p>
                                </div>
                            </div>
                            <div className="flex gap-4">
                                <div className="w-12 h-12 rounded-xl bg-slate-50 flex items-center justify-center flex-shrink-0 border border-slate-100">
                                    <Shield className="w-6 h-6 text-blue-500" />
                                </div>
                                <div>
                                    <h3 className="text-xl font-bold text-slate-900 mb-2">Enterprise Security</h3>
                                    <p className="text-slate-500 leading-relaxed">Bank-grade encryption, role-based access control, and continuous security monitoring for your data.</p>
                                </div>
                            </div>
                            <div className="flex gap-4">
                                <div className="w-12 h-12 rounded-xl bg-slate-50 flex items-center justify-center flex-shrink-0 border border-slate-100">
                                    <Rocket className="w-6 h-6 text-violet-500" />
                                </div>
                                <div>
                                    <h3 className="text-xl font-bold text-slate-900 mb-2">Scalable Architecture</h3>
                                    <p className="text-slate-500 leading-relaxed">Built on a robust infrastructure that scales effortlessly from small teams to large enterprises.</p>
                                </div>
                            </div>
                        </div>
                        <div className="relative">
                            <div className="absolute inset-0 bg-gradient-to-tr from-emerald-500/10 to-blue-500/10 rounded-3xl blur-3xl"></div>
                            <div className="relative bg-slate-900 rounded-3xl p-8 border border-slate-800 shadow-2xl">
                                <div className="flex items-center gap-3 mb-6 border-b border-slate-800 pb-4">
                                    <div className="w-3 h-3 rounded-full bg-rose-500"></div>
                                    <div className="w-3 h-3 rounded-full bg-amber-500"></div>
                                    <div className="w-3 h-3 rounded-full bg-emerald-500"></div>
                                </div>
                                <div className="space-y-4">
                                    <div className="h-4 bg-slate-800 rounded-md w-3/4"></div>
                                    <div className="h-4 bg-slate-800 rounded-md w-full"></div>
                                    <div className="h-4 bg-slate-800 rounded-md w-5/6"></div>
                                    <div className="grid grid-cols-2 gap-4 mt-8">
                                        <div className="h-24 bg-slate-800/50 rounded-xl border border-slate-800"></div>
                                        <div className="h-24 bg-slate-800/50 rounded-xl border border-slate-800"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* STATISTICS SECTION */}
            <section className="py-20 bg-slate-900 relative border-t border-slate-800">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8">
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-8 divide-x divide-slate-800/50">
                        {statisticsData.map((stat, i) => (
                            <div key={i} className="text-center px-4">
                                <div className="text-4xl md:text-5xl font-extrabold text-white mb-2 tracking-tight">
                                    {stat.value}
                                </div>
                                <div className="text-sm md:text-base font-medium text-slate-400 uppercase tracking-wider">
                                    {stat.label}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* PORTFOLIO SECTION */}
            <section className="py-24 lg:py-32 bg-slate-900 text-white relative border-y border-slate-800 overflow-hidden">
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

                    <div className="mb-12 cursor-grab active:cursor-grabbing">
                        <motion.div ref={carouselRef} className="overflow-hidden">
                            <motion.div 
                                drag="x" 
                                dragConstraints={{ right: 0, left: -width }} 
                                className="flex gap-8"
                            >
                                {portfolioItems.map((item, index) => (
                                    <motion.div 
                                        key={index} 
                                        className="min-w-[300px] md:min-w-[400px] h-[250px] md:h-[300px] rounded-3xl overflow-hidden relative group cursor-pointer border border-slate-700 shadow-xl"
                                        onClick={() => setSelectedItem(item)}
                                    >
                                        <img src={item.img} alt={`Portfolio ${index + 1}`} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 pointer-events-none" />
                                        <div className="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none flex flex-col justify-end p-6 text-left">
                                            <span className="text-emerald-400 text-sm font-semibold tracking-wider uppercase mb-1 translate-y-4 group-hover:translate-y-0 transition-transform duration-300">
                                                {item.cat}
                                            </span>
                                            <h3 className="text-xl font-bold text-white mb-2 translate-y-4 group-hover:translate-y-0 transition-transform duration-300 delay-75">
                                                {__(`general.${item.titleKey}`)}
                                            </h3>
                                            <p className="text-slate-300 text-sm line-clamp-2 translate-y-4 group-hover:translate-y-0 transition-transform duration-300 delay-100">
                                                {__(`general.${item.descKey}`)}
                                            </p>
                                        </div>
                                    </motion.div>
                                ))}
                            </motion.div>
                        </motion.div>
                    </div>

                    <Link href={route('portfolio')}>
                        <Button variant="outline" className="border-slate-700 text-slate-300 hover:bg-slate-800 hover:text-white rounded-full px-8 h-12">
                            {__('general.landing_portfolio_view_all')} <ArrowRight className="ml-2 w-4 h-4" />
                        </Button>
                    </Link>
                </div>
            </section>

            {/* TESTIMONIALS SECTION */}
            <section className="py-24 lg:py-32 bg-slate-50 relative border-y border-slate-200 overflow-hidden">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8 text-center mb-16">
                    <p className="text-sm font-semibold text-blue-600 uppercase tracking-widest mb-4">
                        {__('general.landing_testimonials_badge')}
                    </p>
                    <h2 className="text-4xl font-extrabold tracking-tight mb-4 text-slate-900">
                        {__('general.landing_testimonials_title')}
                    </h2>
                    <p className="text-xl text-slate-600 font-light max-w-2xl mx-auto">
                        {__('general.landing_testimonials_desc')}
                    </p>
                </div>

                <div className="max-w-[90rem] mx-auto px-6 lg:px-8 cursor-grab active:cursor-grabbing">
                    <motion.div ref={testimonialsRef} className="overflow-hidden">
                        <motion.div 
                            drag="x" 
                            dragConstraints={{ right: 0, left: -testiWidth }} 
                            className="flex gap-6"
                        >
                            {testimonialsData.map((testi, index) => (
                                <motion.div 
                                    key={index} 
                                    className="min-w-[320px] md:min-w-[400px] bg-white rounded-3xl p-8 border border-slate-200 shadow-sm flex flex-col hover:border-blue-200 transition-colors"
                                >
                                    <div className="flex gap-1 mb-6">
                                        {[...Array(testi.rating)].map((_, i) => (
                                            <Star key={i} className="w-5 h-5 text-amber-400 fill-amber-400" />
                                        ))}
                                    </div>
                                    <Quote className="w-10 h-10 text-slate-200 mb-4" />
                                    <p className="text-slate-600 text-lg italic leading-relaxed mb-8 flex-1">
                                        "{testi.text}"
                                    </p>
                                    <div className="flex items-center gap-4 mt-auto">
                                        <div className="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-bold text-lg border border-slate-200">
                                            {testi.name.charAt(0)}
                                        </div>
                                        <div>
                                            <h4 className="font-bold text-slate-900">{testi.name}</h4>
                                            <p className="text-sm text-slate-500">{testi.company}</p>
                                        </div>
                                    </div>
                                </motion.div>
                            ))}
                        </motion.div>
                    </motion.div>
                </div>
            </section>

            {/* FINAL CTA / CONTACT */}
            <section className="py-24 lg:py-32 bg-white text-center">
                <div className="max-w-3xl mx-auto px-6">
                    <h2 className="text-4xl lg:text-5xl font-extrabold text-slate-900 tracking-tight mb-8">
                        {__('general.landing_footer_cta_title')}
                    </h2>
                    <p className="text-xl text-slate-500 font-light mb-10 max-w-2xl mx-auto">
                        {__('general.landing_footer_cta_desc')}
                    </p>
                    <Button 
                        onClick={() => window.dispatchEvent(new Event('open-guest-ticket'))}
                        size="lg" 
                        className="bg-slate-900 hover:bg-slate-800 text-white rounded-full px-12 h-16 text-lg font-bold shadow-xl hover:shadow-2xl transition-all"
                    >
                        {__('general.submit_guest_ticket') || 'Submit Guest Ticket'}
                    </Button>
                </div>
            </section>

            <AnimatePresence>
                {selectedItem && (
                    <motion.div 
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        className="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4 md:p-8 backdrop-blur-sm"
                        onClick={() => setSelectedItem(null)}
                    >
                        <button 
                            className="absolute top-6 right-6 text-white/70 hover:text-white bg-white/10 hover:bg-white/20 rounded-full p-2 transition-colors z-[60]"
                            onClick={(e) => { e.stopPropagation(); setSelectedItem(null); }}
                        >
                            <X className="w-8 h-8" />
                        </button>
                        <motion.div
                            initial={{ scale: 0.9, opacity: 0, y: 20 }}
                            animate={{ scale: 1, opacity: 1, y: 0 }}
                            exit={{ scale: 0.9, opacity: 0, y: 20 }}
                            transition={{ type: "spring", damping: 25, stiffness: 300 }}
                            className="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden max-w-5xl w-full max-h-full flex flex-col shadow-2xl"
                            onClick={(e) => e.stopPropagation()}
                        >
                            <div className="flex-1 overflow-auto min-h-0 bg-slate-950 p-2 md:p-6 flex items-center justify-center relative">
                                <img 
                                    src={selectedItem.img} 
                                    alt="Fullscreen Portfolio" 
                                    className="max-w-full max-h-[70vh] object-contain rounded-lg shadow-xl" 
                                />
                            </div>
                            <div className="p-6 md:p-8 bg-slate-900 border-t border-slate-800 text-left overflow-y-auto max-h-[50vh]">
                                <span className="text-emerald-400 text-sm font-semibold tracking-wider uppercase mb-2 block">
                                    {selectedItem.cat}
                                </span>
                                <h3 className="text-2xl md:text-3xl font-bold text-white mb-3">
                                    {__(`general.${selectedItem.titleKey}`)}
                                </h3>
                                <p className="text-slate-400 text-base md:text-lg max-w-3xl leading-relaxed mb-8">
                                    {__(`general.${selectedItem.descKey}`)}
                                </p>
                                
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 border-t border-slate-800 pt-8">
                                    <div>
                                        <h4 className="text-lg font-bold text-white mb-3 flex items-center gap-2">
                                            <Box className="w-5 h-5 text-rose-400" /> {__('general.landing_portfolio_challenge')}
                                        </h4>
                                        <p className="text-slate-400 leading-relaxed text-sm">
                                            {selectedItem.challenge}
                                        </p>
                                    </div>
                                    <div>
                                        <h4 className="text-lg font-bold text-white mb-3 flex items-center gap-2">
                                            <Zap className="w-5 h-5 text-emerald-400" /> {__('general.landing_portfolio_solution')}
                                        </h4>
                                        <p className="text-slate-400 leading-relaxed text-sm">
                                            {selectedItem.solution}
                                        </p>
                                    </div>
                                </div>
                                
                                <div className="border-t border-slate-800 pt-6">
                                    <h4 className="text-sm font-bold text-slate-300 mb-4 uppercase tracking-wider">
                                        {__('general.landing_portfolio_technologies')}
                                    </h4>
                                    <div className="flex flex-wrap gap-2">
                                        {selectedItem.techs && selectedItem.techs.map((tech, i) => (
                                            <span key={i} className="px-3 py-1 bg-slate-800 border border-slate-700 text-slate-300 text-xs font-semibold rounded-full">
                                                {tech}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </motion.div>
                    </motion.div>
                )}
            </AnimatePresence>

        </PublicLayout>
    );
}

import { useState } from 'react';
import { __ } from '@/lib/i18n';
import { Head } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { motion, AnimatePresence } from 'framer-motion';
import { X } from 'lucide-react';

const portfolioItems = [
    { img: '/images/portfolio/kbdny.png', titleKey: 'portfolio_kbdny_title', descKey: 'portfolio_kbdny_desc', cat: 'Platform' },
    { img: '/images/portfolio/stockmanager.png', titleKey: 'portfolio_stock_manager_title', descKey: 'portfolio_stock_manager_desc', cat: 'ERP' },
    { img: '/images/portfolio/minifatora.png', titleKey: 'portfolio_mini_fatora_title', descKey: 'portfolio_mini_fatora_desc', cat: 'SaaS' },
    { img: '/images/portfolio/vodafone-crm.jpg', titleKey: 'portfolio_vodafone_crm_title', descKey: 'portfolio_vodafone_crm_desc', cat: 'CRM' },
    { img: '/images/portfolio/amcacademy.jpg', titleKey: 'portfolio_amc_academy', descKey: 'portfolio_amc_academy_desc', cat: 'E-Learning' },
    { img: '/images/portfolio/projectmanager.png', titleKey: 'portfolio_project_manager', descKey: 'portfolio_project_manager_desc', cat: 'Management' },
    { img: '/images/portfolio/telecom-system.png', titleKey: 'portfolio_telecom_system_title', descKey: 'portfolio_telecom_system_desc', cat: 'Platform' },
    { img: '/images/portfolio/altayaraa.png', titleKey: 'portfolio_altayaraa_title', descKey: 'portfolio_altayaraa_desc', cat: 'E-Commerce' },
    { img: '/images/portfolio/forex-app.png', titleKey: 'portfolio_forex_app_title', descKey: 'portfolio_forex_app_desc', cat: 'Mobile App' },
    { img: '/images/portfolio/amcsocial.png', titleKey: 'portfolio_amc_social', descKey: 'portfolio_amc_social_desc', cat: 'Platform' },
    { img: '/images/portfolio/nokhpa.png', titleKey: 'portfolio_nokhpa_title', descKey: 'portfolio_nokhpa_desc', cat: 'E-Commerce' },
    { img: '/images/portfolio/duplicate-finder.jpg', titleKey: 'portfolio_duplicate_finder_title', descKey: 'portfolio_duplicate_finder_desc', cat: 'Desktop App' },
    { img: '/images/portfolio/map-extractor.jpg', titleKey: 'portfolio_map_extractor_title', descKey: 'portfolio_map_extractor_desc', cat: 'Desktop App' },
    { img: '/images/portfolio/instagram-manager.png', titleKey: 'portfolio_instagram_manager_title', descKey: 'portfolio_instagram_manager_desc', cat: 'Desktop App' },
    { img: '/images/portfolio/whatsapp-sender.png', titleKey: 'portfolio_whatsapp_sender_title', descKey: 'portfolio_whatsapp_sender_desc', cat: 'Desktop App' },
    { img: '/images/portfolio/telegram-sender.png', titleKey: 'portfolio_telegram_sender_title', descKey: 'portfolio_telegram_sender_desc', cat: 'Desktop App' },
    { img: '/images/portfolio/inbox-sender.png', titleKey: 'portfolio_inbox_sender_title', descKey: 'portfolio_inbox_sender_desc', cat: 'Desktop App' },
    { img: '/images/portfolio/heic-converter.png', titleKey: 'portfolio_heic_converter_title', descKey: 'portfolio_heic_converter_desc', cat: 'Desktop App' },
    { img: '/images/portfolio/text-studio.jpg', titleKey: 'portfolio_text_studio_title', descKey: 'portfolio_text_studio_desc', cat: 'Desktop App' },
    { img: '/images/portfolio/amctasks-downloader.png', titleKey: 'portfolio_amc_tasks_downloader', descKey: 'portfolio_amc_tasks_downloader_desc', cat: 'Desktop App' },
    { img: '/images/portfolio/stocktalk.png', titleKey: 'portfolio_stocktalk_ai_title', descKey: 'portfolio_stocktalk_ai_desc', cat: 'AI & Bot' },
    { img: '/images/portfolio/forex.png', titleKey: 'portfolio_forex_bot_title', descKey: 'portfolio_forex_bot_desc', cat: 'AI & Bot' },
    { img: '/images/portfolio/revFlow.png', titleKey: 'portfolio_revflow_title', descKey: 'portfolio_revflow_desc', cat: 'SaaS' },
    { img: '/images/portfolio/chartcash.png', titleKey: 'portfolio_chartcash_title', descKey: 'portfolio_chartcash_desc', cat: 'Dashboard' },
    { img: '/images/portfolio/chrome-ext-fb-id.png', titleKey: 'portfolio_fb_id_extractor_title', descKey: 'portfolio_fb_id_extractor_desc', cat: 'Extension' },
    { img: '/images/portfolio/khamsat-notifier.jpg', titleKey: 'portfolio_khamsat_notifier_title', descKey: 'portfolio_khamsat_notifier_desc', cat: 'Extension' },
    { img: '/images/portfolio/amemailcontrols.png', titleKey: 'portfolio_am_email_controls', descKey: 'portfolio_am_email_controls_desc', cat: 'Extension' },
    { img: '/images/portfolio/amctasks.jpg', titleKey: 'portfolio_amc_tasks', descKey: 'portfolio_amc_tasks_desc', cat: 'ERP' },
];

export default function Portfolio() {
    const [selectedItem, setSelectedItem] = useState(null);

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
                                onClick={() => setSelectedItem(item)}
                            >
                                <img src={item.img} alt={`Portfolio ${index + 1}`} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 pointer-events-none" />
                                <div className="absolute inset-0 bg-slate-900/70 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none flex flex-col justify-end p-6 text-left">
                                    <span className="text-emerald-400 text-xs font-bold tracking-wider uppercase mb-1 translate-y-4 group-hover:translate-y-0 transition-transform duration-300">
                                        {item.cat}
                                    </span>
                                    <h3 className="text-lg font-bold text-white mb-2 translate-y-4 group-hover:translate-y-0 transition-transform duration-300 delay-75 line-clamp-1">
                                        {__(`general.${item.titleKey}`)}
                                    </h3>
                                    <p className="text-slate-300 text-xs line-clamp-2 translate-y-4 group-hover:translate-y-0 transition-transform duration-300 delay-100">
                                        {__(`general.${item.descKey}`)}
                                    </p>
                                </div>
                            </motion.div>
                        ))}
                    </div>
                </div>
            </section>

            {/* LIGHTBOX */}
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
                            <div className="p-6 md:p-8 bg-slate-900 border-t border-slate-800 text-left">
                                <span className="text-emerald-400 text-sm font-semibold tracking-wider uppercase mb-2 block">
                                    {selectedItem.cat}
                                </span>
                                <h3 className="text-2xl md:text-3xl font-bold text-white mb-3">
                                    {__(`general.${selectedItem.titleKey}`)}
                                </h3>
                                <p className="text-slate-400 text-base md:text-lg max-w-3xl leading-relaxed">
                                    {__(`general.${selectedItem.descKey}`)}
                                </p>
                            </div>
                        </motion.div>
                    </motion.div>
                )}
            </AnimatePresence>

        </PublicLayout>
    );
}

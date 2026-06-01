import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import PricingBuilder from '@/Components/PricingBuilder';
import { Button } from '@/Components/ui/button';
import { motion } from 'framer-motion';
import { 
    Wallet, UserCheck, Layers, ChevronDown, Check,
    Lock, CheckCircle2, ArrowRight, Activity, FileText,
    Building2, MessageSquare, Zap, Sparkles, Store, Crown
} from 'lucide-react';

const ICON_MAP = {
    Building2, MessageSquare, Zap, Sparkles, Check, Store, Layers
};

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
    const [faqOpen, setFaqOpen] = useState(0);

    const toggleFaq = (idx) => {
        setFaqOpen(faqOpen === idx ? null : idx);
    };

    return (
        <PublicLayout>
            <Head>
                <title>{__('general.musoftware_unified_workspace')}</title>
                <meta name="description" content="Manage clients, billing, and operations from one workspace." />
            </Head>

            {/* HERO SECTION */}
            <section id="hero" className="relative pt-32 pb-24 lg:pt-48 lg:pb-32 overflow-hidden bg-white">
                <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-indigo-50 via-white to-white opacity-70"></div>
                <div className="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                        {/* LEFT: Copy & CTA */}
                        <motion.div 
                            initial="hidden"
                            animate="visible"
                            variants={staggerContainer}
                            className="max-w-2xl"
                        >
                            <motion.h1 variants={fadeUp} className="text-5xl lg:text-7xl font-semibold tracking-tight text-slate-900 leading-[1.05] mb-8">{__('general.unified_operations_for_modern_businesses')}</motion.h1>
                            <motion.p variants={fadeUp} className="text-xl text-slate-500 leading-relaxed font-light mb-10 max-w-lg">{__('general.select_exactly_what_you_need_from_our_marketplace_from_billing_to_crm_engineered_for_calm_clarity_and_high_performance')}</motion.p>
                            <motion.div variants={fadeUp} className="flex flex-col sm:flex-row gap-4">
                                <Link href="/register?trial=true">
                                    <Button size="lg" className="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white rounded-full px-8 h-14 text-base font-medium shadow-md hover:shadow-xl transition-all">{__('general.start_free_trial')}</Button>
                                </Link>
                                <a href="#features">
                                    <Button size="lg" variant="ghost" className="w-full sm:w-auto hover:bg-slate-50 text-slate-900 rounded-full px-8 h-14 text-base font-medium transition-all">{__('general.explore_features')}</Button>
                                </a>
                            </motion.div>
                            <motion.p variants={fadeUp} className="text-sm text-slate-500 mt-4">{__('general.no_credit_card_required_for_14_day_trial_on_modules_tools_excluded')}</motion.p>
                        </motion.div>

                        {/* RIGHT: REAL Modern Corporate Memphis SVG */}
                        <motion.div 
                            initial={{ opacity: 0, x: 20 }}
                            animate={{ opacity: 1, x: 0 }}
                            transition={{ duration: 0.8, delay: 0.2 }}
                            className="relative w-full flex items-center justify-center lg:justify-end"
                        >
                            <div className="absolute inset-0 bg-gradient-to-tr from-indigo-100 to-purple-50 rounded-[3rem] blur-3xl opacity-50"></div>
                            <img src="/illustrations/workspace-illustration.svg" alt="Workspace Overview" className="w-full max-w-lg object-contain drop-shadow-2xl relative z-10" />
                        </motion.div>
                    </div>
                </div>
            </section>

            {/* FEATURES SECTION */}
            <section id="features" className="py-24 lg:py-32 bg-white relative">
                <div className="max-w-7xl mx-auto px-6 lg:px-8">
                    <div className="text-center max-w-2xl mx-auto mb-20">
                        <h2 className="text-3xl lg:text-4xl font-semibold text-slate-900 tracking-tight mb-4">{__('general.everything_you_need_nothing_you_don_t')}</h2>
                        <p className="text-lg text-slate-500 font-light">{__('general.our_modular_approach_means_you_only_see_and_pay_for_the_tools_that_fit_your_workflow')}</p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        {[
                            { title: "Unified Workspace", desc: "Manage all your modules—CRM, Invoicing, Projects—from a single, beautiful dashboard.", icon: <Layers className="w-6 h-6 text-indigo-600" /> },
                            { title: "Multi-Currency Wallet", desc: "Built-in ledger supporting cross-border transactions without messy conversions.", icon: <Wallet className="w-6 h-6 text-indigo-600" /> },
                            { title: "Client Portals", desc: "Give your clients a premium login to view invoices, manage tasks, and pay online.", icon: <UserCheck className="w-6 h-6 text-indigo-600" /> }
                        ].map((feat, idx) => (
                            <motion.div 
                                key={idx}
                                initial={{ opacity: 0, y: 20 }}
                                whileInView={{ opacity: 1, y: 0 }}
                                viewport={{ once: true }}
                                transition={{ delay: idx * 0.1, duration: 0.5 }}
                                className="p-8 rounded-3xl bg-slate-50 border border-slate-100 hover:shadow-lg hover:border-indigo-100 transition-all group"
                            >
                                <div className="w-12 h-12 rounded-2xl bg-white shadow-sm flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                                    {feat.icon}
                                </div>
                                <h3 className="text-xl font-semibold text-slate-900 mb-3">{feat.title}</h3>
                                <p className="text-slate-500 leading-relaxed font-light">{feat.desc}</p>
                            </motion.div>
                        ))}
                    </div>
                </div>
            </section>

            {/* PRICING */}
            <section id="pricing" className="py-24 lg:py-32 bg-slate-50">
                <div className="max-w-7xl mx-auto px-6 lg:px-8">
                    <div className="text-center max-w-2xl mx-auto mb-16">
                        <h2 className="text-3xl lg:text-4xl font-semibold text-slate-900 tracking-tight mb-4">{__('general.build_your_perfect_plan')}</h2>
                        <p className="text-lg text-slate-500 font-light">{__('general.select_exactly_what_you_need_no_more_no_less')}</p>
                    </div>

                    <PricingBuilder serviceItems={serviceItems} currency={currency} />
                </div>
            </section>

            {/* FAQ */}
            <section id="faq" className="py-24 lg:py-32 bg-white">
                <div className="max-w-3xl mx-auto px-6 lg:px-8">
                    <h2 className="text-3xl font-semibold text-slate-900 tracking-tight mb-12 text-center">{__('general.common_questions')}</h2>
                    <div className="space-y-4">
                        {[
                            { q: "Is there a setup fee?", a: "No. You can start using the platform immediately without any initial setup fees." },
                            { q: "Can I use my own domain for client portals?", a: "Yes, custom domain mapping with auto-SSL is included in the Professional plan." },
                            { q: "How are escrows secured?", a: "Escrows are held in an encrypted vault and are only released upon mutually agreed milestone completions." },
                            { q: "Do you support multiple currencies?", a: "Yes, our wallet infrastructure natively supports multi-currency balances and conversions." }
                        ].map((faq, i) => (
                            <div key={i} className="border border-slate-100/60 rounded-2xl overflow-hidden bg-white hover:shadow-md transition-shadow">
                                <button 
                                    onClick={() => toggleFaq(i)}
                                    className="w-full px-6 py-5 flex justify-between items-center text-left"
                                >
                                    <span className="font-medium text-slate-900">{faq.q}</span>
                                    <ChevronDown className={`w-5 h-5 text-slate-400 transition-transform ${faqOpen === i ? 'rotate-180' : ''}`} />
                                </button>
                                {faqOpen === i && (
                                    <div className="px-6 pb-5 pt-0 text-slate-600 font-light">
                                        {faq.a}
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* FINAL CTA */}
            <section className="relative py-24 lg:py-32 bg-slate-900 text-white text-center overflow-hidden">
                <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_bottom,_var(--tw-gradient-stops))] from-indigo-900/40 via-slate-900 to-slate-900"></div>
                <div className="max-w-3xl mx-auto px-6 lg:px-8 relative z-10">
                    <h2 className="text-4xl lg:text-5xl font-semibold tracking-tight mb-8">{__('general.ready_for_operational_clarity')}</h2>
                    <p className="text-xl text-slate-400 font-light mb-10 max-w-2xl mx-auto">{__('general.join_the_platform_that_unifies_your_business_from_the_ground_up_experience_the_calm')}</p>
                    <Link href="/register?trial=true">
                        <Button size="lg" className="bg-white hover:bg-indigo-50 text-slate-900 rounded-full px-10 h-14 text-lg font-medium shadow-xl hover:scale-105 transition-all">{__('general.start_free_trial')}</Button>
                    </Link>
                    <p className="text-sm text-slate-400 mt-4">{__('general.no_credit_card_required_for_14_day_trial_on_modules_tools_excluded')}</p>
                </div>
            </section>

        </PublicLayout>
    );
}

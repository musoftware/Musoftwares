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
                <title>musoftware — Unified Workspace</title>
                <meta name="description" content="Manage clients, billing, and operations from one workspace." />
            </Head>

            {/* HERO SECTION */}
            <section id="hero" className="relative pt-32 pb-24 lg:pt-48 lg:pb-32 overflow-hidden bg-white">
                <div className="max-w-7xl mx-auto px-6 lg:px-8">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                        {/* LEFT: Copy & CTA */}
                        <motion.div 
                            initial="hidden"
                            animate="visible"
                            variants={staggerContainer}
                            className="max-w-2xl"
                        >
                            <motion.h1 variants={fadeUp} className="text-5xl lg:text-7xl font-semibold tracking-tight text-slate-900 leading-[1.05] mb-8">
                                Choose the services you need. We handle the rest.
                            </motion.h1>
                            <motion.p variants={fadeUp} className="text-xl text-slate-500 leading-relaxed font-light mb-10 max-w-lg">
                                Select exactly what you need from our marketplace. No more, no less. Engineered for calm clarity.
                            </motion.p>
                            <motion.div variants={fadeUp} className="flex flex-col sm:flex-row gap-4">
                                <Link href="/register?trial=true">
                                    <Button size="lg" className="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white rounded-full px-8 h-14 text-base font-medium shadow-md">
                                        Start Free Trial
                                    </Button>
                                </Link>
                                <a href="#overview">
                                    <Button size="lg" variant="ghost" className="w-full sm:w-auto hover:bg-slate-50 text-slate-900 rounded-full px-8 h-14 text-base font-medium">
                                        Explore Workspace
                                    </Button>
                                </a>
                            </motion.div>
                        </motion.div>

                        {/* RIGHT: REAL Modern Corporate Memphis SVG */}
                        <motion.div 
                            initial={{ opacity: 0, x: 20 }}
                            animate={{ opacity: 1, x: 0 }}
                            transition={{ duration: 0.8, delay: 0.2 }}
                            className="relative w-full flex items-center justify-center lg:justify-end"
                        >
                            <img src="/illustrations/workspace-illustration.svg" alt="Workspace Overview" className="w-full max-w-lg object-contain drop-shadow-xl" />
                        </motion.div>
                    </div>
                </div>
            </section>










            {/* PRICING */}
            <section id="pricing" className="py-24 lg:py-32 bg-slate-50">
                <div className="max-w-7xl mx-auto px-6 lg:px-8">
                    <div className="text-center max-w-2xl mx-auto mb-16">
                        <div className="inline-flex items-center gap-2 bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-semibold px-3 py-1 rounded-full mb-3">
                            <Crown className="h-3.5 w-3.5" /> Fully Genius System
                        </div>
                        <h2 className="text-3xl lg:text-4xl font-semibold text-slate-900 tracking-tight mb-4">
                            Build Your Perfect Plan
                        </h2>
                        <p className="text-lg text-slate-500 font-light">
                            Select exactly what you need. No more, no less.
                        </p>
                    </div>

                    <PricingBuilder serviceItems={serviceItems} currency={currency} />
                </div>
            </section>

            {/* FAQ */}
            <section id="faq" className="py-24 lg:py-32 bg-white">
                <div className="max-w-3xl mx-auto px-6 lg:px-8">
                    <h2 className="text-3xl font-semibold text-slate-900 tracking-tight mb-12 text-center">
                        Common questions
                    </h2>
                    <div className="space-y-4">
                        {[
                            { q: "Is there a setup fee?", a: "No. You can start using the platform immediately without any initial setup fees." },
                            { q: "Can I use my own domain for client portals?", a: "Yes, custom domain mapping with auto-SSL is included in the Professional plan." },
                            { q: "How are escrows secured?", a: "Escrows are held in an encrypted vault and are only released upon mutually agreed milestone completions." },
                            { q: "Do you support multiple currencies?", a: "Yes, our wallet infrastructure natively supports multi-currency balances and conversions." }
                        ].map((faq, i) => (
                            <div key={i} className="border border-slate-100 rounded-2xl overflow-hidden">
                                <button 
                                    onClick={() => toggleFaq(i)}
                                    className="w-full px-6 py-5 flex justify-between items-center bg-slate-50/50 hover:bg-slate-50 transition-colors text-left"
                                >
                                    <span className="font-medium text-slate-900">{faq.q}</span>
                                    <ChevronDown className={`w-5 h-5 text-slate-400 transition-transform ${faqOpen === i ? 'rotate-180' : ''}`} />
                                </button>
                                {faqOpen === i && (
                                    <div className="px-6 pb-5 pt-2 bg-slate-50/50 text-slate-600 font-light">
                                        {faq.a}
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* FINAL CTA */}
            <section className="py-24 lg:py-32 bg-slate-900 text-white text-center">
                <div className="max-w-3xl mx-auto px-6 lg:px-8">
                    <h2 className="text-4xl lg:text-5xl font-semibold tracking-tight mb-8">
                        Ready for operational clarity?
                    </h2>
                    <p className="text-xl text-slate-400 font-light mb-10 max-w-2xl mx-auto">
                        Join the platform that unifies your business from the ground up.
                    </p>
                    <Link href="/register?trial=true">
                        <Button size="lg" className="bg-white hover:bg-slate-50 text-slate-900 rounded-full px-10 h-14 text-lg font-medium shadow-lg">
                            Start Free Trial
                        </Button>
                    </Link>
                </div>
            </section>

        </PublicLayout>
    );
}

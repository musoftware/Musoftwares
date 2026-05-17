import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Button } from '@/Components/ui/button';
import { motion } from 'framer-motion';
import { 
    Wallet, UserCheck, Layers, ChevronDown, Check,
    Lock, CheckCircle2, ArrowRight, Activity, FileText
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

export default function Home() {
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
                                Manage clients, billing, and operations from one workspace.
                            </motion.h1>
                            <motion.p variants={fadeUp} className="text-xl text-slate-500 leading-relaxed font-light mb-10 max-w-lg">
                                A single platform for invoices, wallets, services, and customer workflows. Engineered for calm clarity.
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

                        {/* RIGHT: UI Composition */}
                        <motion.div 
                            initial={{ opacity: 0, x: 20 }}
                            animate={{ opacity: 1, x: 0 }}
                            transition={{ duration: 0.8, delay: 0.2 }}
                            className="relative h-[500px] w-full flex items-center justify-center lg:justify-end"
                        >
                            {/* Soft geometric background representing Corporate Memphis style */}
                            <div className="absolute w-[400px] h-[400px] bg-slate-100 rounded-full blur-3xl opacity-60 right-0 top-10 pointer-events-none" />
                            
                            {/* Layered UI Cards */}
                            <div className="relative w-full max-w-md h-full">
                                {/* Base Dashboard Card */}
                                <div className="absolute top-10 right-0 w-full bg-white rounded-2xl shadow-2xl border border-slate-100 p-6 z-10 overflow-hidden">
                                    <div className="flex justify-between items-center mb-6">
                                        <div className="flex gap-2">
                                            <div className="w-3 h-3 rounded-full bg-slate-200" />
                                            <div className="w-3 h-3 rounded-full bg-slate-200" />
                                        </div>
                                        <div className="w-20 h-2 bg-slate-100 rounded-full" />
                                    </div>
                                    <div className="space-y-4">
                                        <div className="h-24 bg-slate-50 rounded-xl border border-slate-100 flex items-center p-4">
                                            <div className="w-12 h-12 rounded-full bg-indigo-50 flex items-center justify-center mr-4">
                                                <Wallet className="w-5 h-5 text-indigo-500" />
                                            </div>
                                            <div>
                                                <div className="text-sm text-slate-500 mb-1">Company Wallet</div>
                                                <div className="text-2xl font-semibold text-slate-900">$124,500.00</div>
                                            </div>
                                        </div>
                                        <div className="flex gap-4">
                                            <div className="flex-1 h-32 bg-slate-50 rounded-xl border border-slate-100 p-4">
                                                <div className="text-xs text-slate-400 mb-2">Escrow Locked</div>
                                                <div className="text-lg font-medium text-slate-800 mb-4">$42,000</div>
                                                <div className="w-full h-1 bg-slate-200 rounded-full overflow-hidden">
                                                    <div className="w-2/3 h-full bg-emerald-400" />
                                                </div>
                                            </div>
                                            <div className="flex-1 h-32 bg-slate-50 rounded-xl border border-slate-100 p-4">
                                                <div className="text-xs text-slate-400 mb-2">Active Invoices</div>
                                                <div className="text-lg font-medium text-slate-800 mb-4">12 Pending</div>
                                                <div className="flex -space-x-2">
                                                    {[1,2,3].map(i => (
                                                        <div key={i} className="w-6 h-6 rounded-full bg-white border border-slate-200 shadow-sm" />
                                                    ))}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Floating Notification Card */}
                                <motion.div 
                                    animate={{ y: [0, -10, 0] }}
                                    transition={{ duration: 4, repeat: Infinity, ease: "easeInOut" }}
                                    className="absolute -bottom-10 -left-10 w-72 bg-slate-900 text-white rounded-2xl shadow-xl p-5 z-20 border border-slate-800"
                                >
                                    <div className="flex items-center gap-4">
                                        <div className="w-10 h-10 rounded-full bg-emerald-500/20 flex items-center justify-center">
                                            <CheckCircle2 className="w-5 h-5 text-emerald-400" />
                                        </div>
                                        <div>
                                            <div className="text-sm font-medium">Invoice Paid</div>
                                            <div className="text-xs text-slate-400 mt-1">Acme Corp sent $12,500</div>
                                        </div>
                                    </div>
                                </motion.div>
                            </div>
                        </motion.div>
                    </div>
                </div>
            </section>

            {/* PLATFORM OVERVIEW */}
            <section id="overview" className="py-24 lg:py-32 bg-slate-50">
                <div className="max-w-7xl mx-auto px-6 lg:px-8">
                    <div className="text-center max-w-2xl mx-auto mb-20">
                        <h2 className="text-3xl lg:text-4xl font-semibold text-slate-900 tracking-tight mb-4">
                            Everything you need. Nothing you don't.
                        </h2>
                        <p className="text-lg text-slate-500 font-light">
                            A unified architecture replacing fragmented tools with calm, focused capability.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        {[
                            {
                                icon: <Wallet className="w-6 h-6 text-slate-700" />,
                                title: "Financial Hub",
                                desc: "Wallets, invoices, and escrows natively connected to your ledger."
                            },
                            {
                                icon: <UserCheck className="w-6 h-6 text-slate-700" />,
                                title: "Client Portals",
                                desc: "Give your clients a premium, white-labeled space to interact."
                            },
                            {
                                icon: <Layers className="w-6 h-6 text-slate-700" />,
                                title: "Marketplace",
                                desc: "Productized services and custom RFP bidding built directly in."
                            }
                        ].map((feature, idx) => (
                            <div key={idx} className="bg-white rounded-2xl p-8 shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
                                <div className="w-12 h-12 bg-slate-50 rounded-xl flex items-center justify-center mb-6">
                                    {feature.icon}
                                </div>
                                <h3 className="text-xl font-medium text-slate-900 mb-3">{feature.title}</h3>
                                <p className="text-slate-500 font-light leading-relaxed">{feature.desc}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* FINANCIAL OPERATIONS */}
            <section id="financials" className="py-24 lg:py-32 bg-white overflow-hidden">
                <div className="max-w-7xl mx-auto px-6 lg:px-8">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-20 items-center">
                        <div className="order-2 lg:order-1 relative">
                            {/* Minimal UI Card */}
                            <div className="w-full max-w-lg mx-auto bg-slate-50 rounded-3xl p-8 border border-slate-100">
                                <div className="space-y-6">
                                    <div className="flex justify-between items-center border-b border-slate-200 pb-4">
                                        <span className="text-sm font-medium text-slate-500">Ledger Balance</span>
                                        <span className="text-sm font-medium text-slate-900">$124,500.00</span>
                                    </div>
                                    {[
                                        { label: "Stripe Settlement", amount: "+$14,250.00", type: "pos" },
                                        { label: "Partner Tax Reserve", amount: "-$320.00", type: "neg" },
                                        { label: "Escrow Release", amount: "+$2,500.00", type: "pos" },
                                    ].map((item, i) => (
                                        <div key={i} className="flex justify-between items-center bg-white p-4 rounded-xl border border-slate-100 shadow-sm">
                                            <div className="flex items-center gap-3">
                                                <div className={`w-8 h-8 rounded-full flex items-center justify-center ${item.type === 'pos' ? 'bg-emerald-50' : 'bg-slate-100'}`}>
                                                    <ArrowRight className={`w-4 h-4 ${item.type === 'pos' ? 'text-emerald-500' : 'text-slate-400'}`} />
                                                </div>
                                                <span className="text-sm font-medium text-slate-700">{item.label}</span>
                                            </div>
                                            <span className={`text-sm font-medium ${item.type === 'pos' ? 'text-emerald-600' : 'text-slate-600'}`}>
                                                {item.amount}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                        <div className="order-1 lg:order-2 max-w-xl">
                            <h2 className="text-3xl lg:text-4xl font-semibold text-slate-900 tracking-tight mb-6">
                                Deep financial control. <br />Simplified.
                            </h2>
                            <p className="text-lg text-slate-500 font-light leading-relaxed mb-8">
                                Automate client billing, double-entry ledger balancing, multi-currency wallets, and automatic tax withholdings. Stop exposing your accounting to manual input errors.
                            </p>
                            <ul className="space-y-4">
                                {['Double-entry ledger tracks every cent', 'Encrypted escrow vault locks funds', 'Instant settlement via card and wire'].map((item, i) => (
                                    <li key={i} className="flex items-center gap-3 text-slate-700">
                                        <Check className="w-5 h-5 text-slate-900" />
                                        <span className="font-light">{item}</span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            {/* MARKETPLACE & FREELANCE */}
            <section id="marketplace" className="py-24 lg:py-32 bg-slate-50">
                <div className="max-w-7xl mx-auto px-6 lg:px-8">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-20 items-center">
                        <div className="max-w-xl">
                            <h2 className="text-3xl lg:text-4xl font-semibold text-slate-900 tracking-tight mb-6">
                                A connected marketplace. <br />Built-in.
                            </h2>
                            <p className="text-lg text-slate-500 font-light leading-relaxed mb-8">
                                Launch fixed-price services or pitch detailed bids on custom RFPs. Secure contracts automatically using our native escrow vault.
                            </p>
                            <ul className="space-y-4">
                                {['Productized services structure', 'Flexible RFP bidding engine', 'Automated milestone tracking'].map((item, i) => (
                                    <li key={i} className="flex items-center gap-3 text-slate-700">
                                        <Check className="w-5 h-5 text-slate-900" />
                                        <span className="font-light">{item}</span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                        <div className="relative">
                            {/* Minimal UI Card */}
                            <div className="w-full max-w-lg mx-auto bg-white rounded-3xl p-8 border border-slate-100 shadow-sm">
                                <div className="space-y-6">
                                    <div className="flex justify-between items-center border-b border-slate-100 pb-4">
                                        <span className="text-sm font-medium text-slate-500">Active Proposals</span>
                                        <span className="px-2.5 py-1 bg-slate-100 rounded-full text-xs font-medium text-slate-700">2 Pending</span>
                                    </div>
                                    <div className="space-y-4">
                                        {[
                                            { title: "E-Commerce Portal Sync", bid: "$8,500", status: "In Review" },
                                            { title: "API Webhook Handler", bid: "$4,500", status: "Escrow Locked", highlight: true },
                                        ].map((item, i) => (
                                            <div key={i} className={`p-4 rounded-xl border ${item.highlight ? 'border-indigo-100 bg-indigo-50/30' : 'border-slate-100 bg-slate-50'}`}>
                                                <div className="flex justify-between items-start mb-2">
                                                    <span className="font-medium text-slate-900">{item.title}</span>
                                                    <span className="text-sm font-medium text-slate-700">{item.bid}</span>
                                                </div>
                                                <span className={`text-xs font-medium px-2 py-1 rounded-md ${item.highlight ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-200 text-slate-600'}`}>
                                                    {item.status}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* UNIFIED WORKSPACE */}
            <section id="workspace" className="py-24 lg:py-32 bg-slate-900 text-white">
                <div className="max-w-7xl mx-auto px-6 lg:px-8">
                    <div className="text-center max-w-3xl mx-auto mb-20">
                        <h2 className="text-3xl lg:text-4xl font-semibold tracking-tight mb-6">
                            One profile. Every interaction.
                        </h2>
                        <p className="text-lg text-slate-400 font-light">
                            Unify client relations, financial balances, active support logs, and private internal notes under a single customer profile directory.
                        </p>
                    </div>

                    <div className="bg-slate-800 rounded-3xl border border-slate-700 p-8 lg:p-12 max-w-5xl mx-auto overflow-hidden relative">
                        {/* Minimalist UI representation */}
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-8 relative z-10">
                            <div className="space-y-6">
                                <div className="w-16 h-16 rounded-2xl bg-indigo-500/20 flex items-center justify-center">
                                    <span className="text-2xl font-medium text-indigo-300">AC</span>
                                </div>
                                <div>
                                    <h3 className="text-xl font-medium mb-1">Acme Corp</h3>
                                    <p className="text-slate-400 text-sm">Enterprise Client</p>
                                </div>
                                <div className="space-y-4 pt-4 border-t border-slate-700">
                                    <div>
                                        <p className="text-xs text-slate-500 uppercase tracking-wider mb-1">Status</p>
                                        <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-emerald-500/10 text-emerald-400 text-xs font-medium">
                                            <div className="w-1.5 h-1.5 rounded-full bg-emerald-400" />
                                            Active
                                        </span>
                                    </div>
                                    <div>
                                        <p className="text-xs text-slate-500 uppercase tracking-wider mb-1">Total Volume</p>
                                        <p className="text-lg font-medium">$128,400</p>
                                    </div>
                                </div>
                            </div>
                            <div className="md:col-span-2 bg-slate-900/50 rounded-2xl p-6 border border-slate-700/50">
                                <h4 className="text-sm font-medium text-slate-300 mb-6">Recent Activity</h4>
                                <div className="space-y-4">
                                    {[
                                        { action: "Invoice #1042 Paid", time: "2 hours ago", icon: FileText },
                                        { action: "Escrow Locked for API Integration", time: "1 day ago", icon: Lock },
                                        { action: "Timer logged: 4h 30m", time: "2 days ago", icon: Activity },
                                    ].map((item, i) => (
                                        <div key={i} className="flex items-center gap-4">
                                            <div className="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center">
                                                <item.icon className="w-4 h-4 text-slate-400" />
                                            </div>
                                            <div className="flex-1">
                                                <p className="text-sm font-medium text-slate-200">{item.action}</p>
                                            </div>
                                            <span className="text-xs text-slate-500">{item.time}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* REALTIME COMMUNICATION */}
            <section id="communication" className="py-24 lg:py-32 bg-white">
                <div className="max-w-7xl mx-auto px-6 lg:px-8 text-center">
                    <div className="max-w-3xl mx-auto mb-16">
                        <h2 className="text-3xl lg:text-4xl font-semibold text-slate-900 tracking-tight mb-6">
                            Time is money. Track it accurately.
                        </h2>
                        <p className="text-lg text-slate-500 font-light leading-relaxed">
                            Track timed client operations directly from your toolbar. Paused logs immediately compile into ledger invoice drafts, removing administrative lag.
                        </p>
                    </div>

                    <div className="flex justify-center">
                        <div className="inline-flex items-center gap-6 bg-slate-900 text-white rounded-full px-8 py-4 shadow-xl border border-slate-800">
                            <div className="flex items-center gap-3">
                                <Activity className="w-5 h-5 text-indigo-400" />
                                <span className="text-sm font-medium tracking-wide">API INTEGRATION</span>
                            </div>
                            <div className="w-px h-6 bg-slate-700" />
                            <div className="font-mono text-xl font-medium tracking-wider text-emerald-400">
                                02:45:12
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* PRICING */}
            <section id="pricing" className="py-24 lg:py-32 bg-slate-50">
                <div className="max-w-7xl mx-auto px-6 lg:px-8">
                    <div className="text-center max-w-2xl mx-auto mb-16">
                        <h2 className="text-3xl lg:text-4xl font-semibold text-slate-900 tracking-tight mb-4">
                            Simple, transparent pricing.
                        </h2>
                        <p className="text-lg text-slate-500 font-light">
                            Start for free. Upgrade when your operational volume demands it.
                        </p>
                    </div>

                    <div className="max-w-md mx-auto bg-white rounded-3xl p-8 lg:p-10 shadow-sm border border-slate-100">
                        <div className="text-center mb-8">
                            <h3 className="text-xl font-medium text-slate-900 mb-2">Professional</h3>
                            <div className="flex items-baseline justify-center gap-1">
                                <span className="text-5xl font-semibold text-slate-900">$49</span>
                                <span className="text-slate-500">/mo</span>
                            </div>
                        </div>
                        <ul className="space-y-4 mb-8">
                            {['Unlimited Clients', 'Full ERP & Wallet Access', 'Marketplace Creation', 'Escrow Management', 'Priority Support'].map((feat, i) => (
                                <li key={i} className="flex items-center gap-3 text-slate-600">
                                    <CheckCircle2 className="w-5 h-5 text-slate-900 shrink-0" />
                                    <span className="font-light">{feat}</span>
                                </li>
                            ))}
                        </ul>
                        <Link href="/register?trial=true" className="block w-full">
                            <Button className="w-full bg-slate-900 hover:bg-slate-800 text-white rounded-xl h-12 text-base font-medium shadow-sm">
                                Start 14-Day Free Trial
                            </Button>
                        </Link>
                    </div>
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

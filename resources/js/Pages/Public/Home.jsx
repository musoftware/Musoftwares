import { useState } from 'react';
import { __ } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Button } from '@/Components/ui/button';
import { motion } from 'framer-motion';
import { 
    Server, Activity, Box, Monitor, Building2,
    Briefcase, GraduationCap, ShoppingCart, 
    Landmark, HeartPulse, ShieldCheck, ArrowRight,
    LineChart, Network, Layers
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
                <title>{__('general.musoftware_unified_workspace') || 'Software Infrastructure'}</title>
                <meta name="description" content="Software infrastructure and systems for growing businesses." />
            </Head>

            {/* HERO SECTION - Positioning, Not Selling */}
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

                <div className="max-w-[90rem] mx-auto px-6 lg:px-8 relative z-10">
                    <div className="max-w-4xl">
                        <motion.div 
                            initial="hidden"
                            animate="visible"
                            variants={staggerContainer}
                        >
                            <motion.h1 variants={fadeUp} className="text-5xl lg:text-7xl font-extrabold tracking-tight text-slate-900 leading-[1.1] mb-8">
                                Software Infrastructure <br /> for Business.
                            </motion.h1>
                            <motion.p variants={fadeUp} className="text-xl lg:text-2xl text-slate-600 leading-relaxed font-light mb-12 max-w-2xl">
                                We build and deploy systems that scale with your operations.
                            </motion.p>
                            <motion.div variants={fadeUp} className="flex flex-col sm:flex-row gap-4">
                                <a href="#solutions">
                                    <Button size="lg" className="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white rounded-full px-10 h-14 text-base font-semibold transition-all">
                                        Explore Solutions
                                    </Button>
                                </a>
                                <Link href="/platforms">
                                    <Button size="lg" variant="outline" className="w-full sm:w-auto hover:bg-slate-50 text-slate-900 border-slate-200 rounded-full px-10 h-14 text-base font-semibold transition-all">
                                        Discover Platforms
                                    </Button>
                                </Link>
                            </motion.div>
                        </motion.div>
                    </div>
                </div>
            </section>

            {/* LAYER 1: Company Identity & Scale */}
            <section className="py-16 border-y border-slate-100 bg-slate-50">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8">
                    <p className="text-center text-sm font-semibold text-slate-500 uppercase tracking-widest mb-10">Built for Reliability</p>
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-8 opacity-60 grayscale text-center">
                        <div className="flex flex-col items-center justify-center">
                            <ShieldCheck className="w-10 h-10 mb-2 text-slate-700" />
                            <span className="text-lg font-bold text-slate-700">Secure Infrastructure</span>
                        </div>
                        <div className="flex flex-col items-center justify-center">
                            <Activity className="w-10 h-10 mb-2 text-slate-700" />
                            <span className="text-lg font-bold text-slate-700">High Availability</span>
                        </div>
                        <div className="flex flex-col items-center justify-center">
                            <Network className="w-10 h-10 mb-2 text-slate-700" />
                            <span className="text-lg font-bold text-slate-700">Distributed Systems</span>
                        </div>
                        <div className="flex flex-col items-center justify-center">
                            <LineChart className="w-10 h-10 mb-2 text-slate-700" />
                            <span className="text-lg font-bold text-slate-700">Scalable Architecture</span>
                        </div>
                    </div>
                </div>
            </section>

            {/* LAYER 2: Solutions / Capabilities */}
            <section id="solutions" className="py-24 lg:py-32 bg-white relative">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8">
                    <div className="mb-20">
                        <h2 className="text-4xl font-extrabold text-slate-900 tracking-tight mb-4">What We Build</h2>
                        <p className="text-xl text-slate-500 font-light max-w-2xl">
                            Core capabilities designed to digitize and manage your operations.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-12 lg:gap-16">
                        {[
                            { title: "Dashboards & ERPs", desc: "Live dashboards and full enterprise resource planning systems with role-based access.", icon: <Activity className="w-8 h-8" /> },
                            { title: "Automation & Integrations", desc: "Connect systems, eliminate manual work, and deploy scheduled automated workflows.", icon: <Server className="w-8 h-8" /> },
                            { title: "Custom Web Apps", desc: "Scalable web applications built to your exact process with mobile-responsive architecture.", icon: <Monitor className="w-8 h-8" /> },
                            { title: "Desktop Apps & Tools", desc: "Offline-capable encrypted desktop utilities for internal business operations.", icon: <Box className="w-8 h-8" /> }
                        ].map((sol, idx) => (
                            <div key={idx} className="group">
                                <div className="w-16 h-16 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mb-6 text-slate-700 group-hover:bg-slate-900 group-hover:text-white transition-all duration-300">
                                    {sol.icon}
                                </div>
                                <h3 className="text-2xl font-bold text-slate-900 mb-3">{sol.title}</h3>
                                <p className="text-lg text-slate-500 font-light leading-relaxed">{sol.desc}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>



            {/* FINAL CTA */}
            <section className="py-24 bg-white text-center border-t border-slate-100">
                <div className="max-w-3xl mx-auto px-6">
                    <h2 className="text-4xl lg:text-5xl font-extrabold text-slate-900 tracking-tight mb-8">Ready to Upgrade Your Systems?</h2>
                    <p className="text-xl text-slate-500 font-light mb-10 max-w-2xl mx-auto">
                        Contact our team to discuss your software requirements.
                    </p>
                    <a href="mailto:admin@musoftwares.com">
                        <Button size="lg" className="bg-slate-900 hover:bg-slate-800 text-white rounded-full px-12 h-16 text-lg font-bold shadow-xl transition-all">
                            Contact Solutions Team
                        </Button>
                    </a>
                </div>
            </section>

        </PublicLayout>
    );
}

import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { motion } from 'framer-motion';

export default function Crm({ canLogin, canRegister }) {
    return (
        <PublicLayout auth={{ user: null }}>
            <Head title="MU CRM - Customer Operations Platform" />

            <section className="relative pt-32 pb-24 overflow-hidden bg-white">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="max-w-4xl">
                        <h1 className="text-5xl sm:text-7xl font-extrabold text-slate-900 tracking-tight mb-8">
                            Customer Operations Platform
                        </h1>
                        <p className="text-xl text-slate-600 font-light mb-12 max-w-2xl leading-relaxed">
                            A centralized infrastructure for managing customer data, tracking interactions, and accelerating sales cycles. Built for scale and operational clarity.
                        </p>
                        <div className="flex flex-col sm:flex-row gap-4">
                            <a href="mailto:sales@musoftwares.com">
                                <Button size="lg" className="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white rounded-full px-10 h-14 text-base font-semibold transition-all">
                                    Contact Sales
                                </Button>
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <section className="py-24 bg-slate-50 border-t border-slate-100">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-12">
                        <div>
                            <h3 className="text-xl font-bold text-slate-900 mb-4">Centralized Data</h3>
                            <p className="text-slate-600 font-light">Single source of truth for all client interactions, eliminating data silos.</p>
                        </div>
                        <div>
                            <h3 className="text-xl font-bold text-slate-900 mb-4">Pipeline Management</h3>
                            <p className="text-slate-600 font-light">Clear visibility into sales cycles, conversion rates, and operational bottlenecks.</p>
                        </div>
                        <div>
                            <h3 className="text-xl font-bold text-slate-900 mb-4">Automated Workflows</h3>
                            <p className="text-slate-600 font-light">Reduce manual entry with automated lead assignment and follow-up triggers.</p>
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

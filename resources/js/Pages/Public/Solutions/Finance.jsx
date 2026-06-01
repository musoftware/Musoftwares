import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';

export default function Finance({ canLogin, canRegister }) {
    return (
        <PublicLayout auth={{ user: null }}>
            <Head title="Financial Platforms - musoftware" />

            <section className="relative pt-32 pb-24 overflow-hidden bg-white">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="max-w-4xl">
                        <h1 className="text-5xl sm:text-7xl font-extrabold text-slate-900 tracking-tight mb-8">
                            Compliant Financial Platforms
                        </h1>
                        <p className="text-xl text-slate-600 font-light mb-12 max-w-2xl leading-relaxed">
                            Bank-grade ledger accuracy and security. We build platforms that handle high-frequency transactions with perfect consistency and regulatory compliance.
                        </p>
                        <div className="flex flex-col sm:flex-row gap-4">
                            <a href="mailto:sales@musoftwares.com">
                                <Button size="lg" className="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white rounded-full px-10 h-14 text-base font-semibold transition-all">
                                    Discuss Financial Needs
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
                            <h3 className="text-xl font-bold text-slate-900 mb-4">Ledger Immutability</h3>
                            <p className="text-slate-600 font-light">Double-entry accounting architecture ensuring perfect balancing of all financial records.</p>
                        </div>
                        <div>
                            <h3 className="text-xl font-bold text-slate-900 mb-4">Regulatory Compliance</h3>
                            <p className="text-slate-600 font-light">Built-in reporting tools designed to meet local and international tax and auditing standards.</p>
                        </div>
                        <div>
                            <h3 className="text-xl font-bold text-slate-900 mb-4">Fraud Detection</h3>
                            <p className="text-slate-600 font-light">Automated anomaly detection across massive volumes of transaction data.</p>
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

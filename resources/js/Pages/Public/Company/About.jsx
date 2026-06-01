import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';

export default function About({ canLogin, canRegister }) {
    return (
        <PublicLayout auth={{ user: null }}>
            <Head title="About Us - musoftware" />

            <section className="relative pt-32 pb-24 overflow-hidden bg-white">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="max-w-4xl">
                        <h1 className="text-5xl sm:text-7xl font-extrabold text-slate-900 tracking-tight mb-8">
                            Our Enterprise Vision
                        </h1>
                        <p className="text-xl text-slate-600 font-light mb-12 max-w-2xl leading-relaxed">
                            musoftware was founded with a single premise: complex businesses require simple, scalable, and secure infrastructure. We are an engineering-first software company.
                        </p>
                        <div className="flex flex-col sm:flex-row gap-4">
                            <Link href="/company/careers">
                                <Button size="lg" className="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white rounded-full px-10 h-14 text-base font-semibold transition-all">
                                    Join Our Team
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <section className="py-24 bg-slate-50 border-t border-slate-100">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-12">
                        <div>
                            <h3 className="text-xl font-bold text-slate-900 mb-4">Engineering First</h3>
                            <p className="text-slate-600 font-light">We prioritize clean architecture, robust testing, and scalable performance over marketing buzzwords.</p>
                        </div>
                        <div>
                            <h3 className="text-xl font-bold text-slate-900 mb-4">Long-Term Partnerships</h3>
                            <p className="text-slate-600 font-light">We do not just build software; we maintain, scale, and secure it for the lifetime of your business.</p>
                        </div>
                        <div>
                            <h3 className="text-xl font-bold text-slate-900 mb-4">Data Sovereignty</h3>
                            <p className="text-slate-600 font-light">You own your data. We provide the tools to export, manage, and secure it at all times.</p>
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

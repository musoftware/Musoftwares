import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';

export default function Careers({ canLogin, canRegister }) {
    return (
        <PublicLayout auth={{ user: null }}>
            <Head title="Careers - musoftware" />

            <section className="relative pt-32 pb-24 overflow-hidden bg-white">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="max-w-4xl">
                        <h1 className="text-5xl sm:text-7xl font-extrabold text-slate-900 tracking-tight mb-8">
                            Join Our Engineering Team
                        </h1>
                        <p className="text-xl text-slate-600 font-light mb-12 max-w-2xl leading-relaxed">
                            We are always looking for rigorous engineers, security researchers, and systems architects to help us build resilient platforms.
                        </p>
                        <div className="flex flex-col sm:flex-row gap-4">
                            <a href="mailto:careers@musoftwares.com">
                                <Button size="lg" className="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white rounded-full px-10 h-14 text-base font-semibold transition-all">
                                    Send Your Resume
                                </Button>
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <section className="py-24 bg-slate-50 border-t border-slate-100">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-12 max-w-5xl">
                        <div className="bg-white p-8 rounded-2xl border border-slate-100 shadow-sm">
                            <h3 className="text-xl font-bold text-slate-900 mb-2">Senior Backend Engineer</h3>
                            <p className="text-slate-500 text-sm mb-6">Full-Time &middot; Remote</p>
                            <p className="text-slate-600 font-light mb-6">Architect and scale our Laravel and Node.js microservices. Focus on database optimization and API performance.</p>
                            <a href="mailto:careers@musoftwares.com?subject=Senior Backend Engineer">
                                <Button variant="outline" className="w-full sm:w-auto rounded-full">Apply Now</Button>
                            </a>
                        </div>
                        <div className="bg-white p-8 rounded-2xl border border-slate-100 shadow-sm">
                            <h3 className="text-xl font-bold text-slate-900 mb-2">Frontend Architect</h3>
                            <p className="text-slate-500 text-sm mb-6">Full-Time &middot; Remote</p>
                            <p className="text-slate-600 font-light mb-6">Lead our React and Inertia.js frontend. Build reusable components and ensure perfect accessibility and performance.</p>
                            <a href="mailto:careers@musoftwares.com?subject=Frontend Architect">
                                <Button variant="outline" className="w-full sm:w-auto rounded-full">Apply Now</Button>
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

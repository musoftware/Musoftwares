import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';

export default function Healthcare({ canLogin, canRegister }) {
    return (
        <PublicLayout auth={{ user: null }}>
            <Head title="Healthcare IT Solutions - musoftware" />

            <section className="relative pt-32 pb-24 overflow-hidden bg-white">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="max-w-4xl">
                        <h1 className="text-5xl sm:text-7xl font-extrabold text-slate-900 tracking-tight mb-8">
                            Healthcare Software Infrastructure
                        </h1>
                        <p className="text-xl text-slate-600 font-light mb-12 max-w-2xl leading-relaxed">
                            Secure, compliant, and reliable systems for clinics and hospitals. Manage patient records, appointments, and billing with uncompromising data security.
                        </p>
                        <div className="flex flex-col sm:flex-row gap-4">
                            <a href="mailto:admin@musoftwares.com">
                                <Button size="lg" className="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white rounded-full px-10 h-14 text-base font-semibold transition-all">
                                    Discuss Healthcare Needs
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
                            <h3 className="text-xl font-bold text-slate-900 mb-4">HIPAA Compliance</h3>
                            <p className="text-slate-600 font-light">Architecture designed to meet strict regulatory standards for patient data privacy.</p>
                        </div>
                        <div>
                            <h3 className="text-xl font-bold text-slate-900 mb-4">Patient Portals</h3>
                            <p className="text-slate-600 font-light">Secure interfaces for patients to view test results, book appointments, and manage billing.</p>
                        </div>
                        <div>
                            <h3 className="text-xl font-bold text-slate-900 mb-4">Clinical Operations</h3>
                            <p className="text-slate-600 font-light">Streamline scheduling, resource allocation, and electronic health records (EHR).</p>
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

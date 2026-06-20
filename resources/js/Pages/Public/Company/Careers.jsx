import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { __ } from '@/lib/i18n';

export default function Careers({ canLogin, canRegister }) {
    return (
        <PublicLayout auth={{ user: null }}>
            <Head title={__('landing_company.careers_meta_title')} />

            <section className="relative pt-32 pb-24 overflow-hidden bg-white">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="max-w-4xl">
                        <h1 className="text-5xl sm:text-7xl font-extrabold text-slate-900 tracking-tight mb-8">
                            {__('landing_company.careers_title')}
                        </h1>
                        <p className="text-xl text-slate-600 font-light mb-12 max-w-2xl leading-relaxed">
                            {__('landing_company.careers_subtitle')}
                        </p>
                        <div className="flex flex-col sm:flex-row gap-4">
                            <a href="mailto:admin@musoftwares.com">
                                <Button size="lg" className="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white rounded-full px-10 h-14 text-base font-semibold transition-all">
                                    {__('landing_company.careers_send_resume')}
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
                            <h3 className="text-xl font-bold text-slate-900 mb-2">{__('landing_company.careers_job_1_title')}</h3>
                            <p className="text-slate-500 text-sm mb-6">{__('landing_company.careers_job_1_type')}</p>
                            <p className="text-slate-600 font-light mb-6">{__('landing_company.careers_job_1_desc')}</p>
                            <a href="mailto:admin@musoftwares.com?subject=Senior Backend Engineer">
                                <Button variant="outline" className="w-full sm:w-auto rounded-full">{__('landing_company.careers_job_1_apply')}</Button>
                            </a>
                        </div>
                        <div className="bg-white p-8 rounded-2xl border border-slate-100 shadow-sm">
                            <h3 className="text-xl font-bold text-slate-900 mb-2">{__('landing_company.careers_job_2_title')}</h3>
                            <p className="text-slate-500 text-sm mb-6">{__('landing_company.careers_job_2_type')}</p>
                            <p className="text-slate-600 font-light mb-6">{__('landing_company.careers_job_2_desc')}</p>
                            <a href="mailto:admin@musoftwares.com?subject=Frontend Architect">
                                <Button variant="outline" className="w-full sm:w-auto rounded-full">{__('landing_company.careers_job_2_apply')}</Button>
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

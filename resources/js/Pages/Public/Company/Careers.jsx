import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { __ } from '@/lib/i18n';

export default function Careers({ canLogin, canRegister }) {
    return (
        <PublicLayout auth={{ user: null }}>
            <Head title={__('general.careers_musoftware')} />

            <section className="relative pt-32 pb-24 overflow-hidden bg-white">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="max-w-4xl">
                        <h1 className="text-5xl sm:text-7xl font-extrabold text-slate-900 tracking-tight mb-8">
                            {__('general.join_our_engineering_team')}</h1>
                        <p className="text-xl text-slate-600 font-light mb-12 max-w-2xl leading-relaxed">
                            {__('general.we_are_always_looking_for_rigorous_engin')}</p>
                        <div className="flex flex-col sm:flex-row gap-4">
                            <a href="mailto:admin@musoftwares.com">
                                <Button size="lg" className="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white rounded-full px-10 h-14 text-base font-semibold transition-all">
                                    {__('general.send_your_resume')}</Button>
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <section className="py-24 bg-slate-50 border-t border-slate-100">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-12 max-w-5xl">
                        <div className="bg-white p-8 rounded-2xl border border-slate-100 shadow-sm">
                            <h3 className="text-xl font-bold text-slate-900 mb-2">{__('general.senior_backend_engineer')}</h3>
                            <p className="text-slate-500 text-sm mb-6">Full-Time &middot; Remote</p>
                            <p className="text-slate-600 font-light mb-6">{__('general.architect_and_scale_our_laravel_and_node')}</p>
                            <a href="mailto:admin@musoftwares.com?subject=Senior Backend Engineer">
                                <Button variant="outline" className="w-full sm:w-auto rounded-full">{__('general.apply_now')}</Button>
                            </a>
                        </div>
                        <div className="bg-white p-8 rounded-2xl border border-slate-100 shadow-sm">
                            <h3 className="text-xl font-bold text-slate-900 mb-2">{__('general.frontend_architect')}</h3>
                            <p className="text-slate-500 text-sm mb-6">Full-Time &middot; Remote</p>
                            <p className="text-slate-600 font-light mb-6">{__('general.lead_our_react_and_inertiajs_frontend_bu')}</p>
                            <a href="mailto:admin@musoftwares.com?subject=Frontend Architect">
                                <Button variant="outline" className="w-full sm:w-auto rounded-full">{__('general.apply_now')}</Button>
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

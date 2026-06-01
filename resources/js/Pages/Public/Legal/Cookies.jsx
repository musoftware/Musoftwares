import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head } from '@inertiajs/react';
import { __ } from '@/lib/i18n';

export default function Cookies({ canLogin, canRegister }) {
    return (
        <PublicLayout auth={{ user: null }}>
            <Head title={__('landing_legal.cookie_meta_title')} />

            <section className="relative pt-32 pb-24 overflow-hidden bg-white border-b border-slate-100">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="max-w-4xl">
                        <span className="inline-block py-1 px-3 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold uppercase tracking-wider mb-6">
                            {__('landing_legal.legal_center')}
                        </span>
                        <h1 className="text-4xl sm:text-6xl font-extrabold text-slate-900 tracking-tight mb-6">
                            {__('landing_legal.cookie_title')}
                        </h1>
                        <p className="text-xl text-slate-600 font-light mb-6 max-w-2xl leading-relaxed">
                            {__('landing_legal.cookie_subtitle')}
                        </p>
                        <p className="text-sm text-slate-400 font-medium">
                            {__('landing_legal.last_updated')}
                        </p>
                    </div>
                </div>
            </section>

            <section className="py-24 bg-slate-50">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <div className="bg-white p-8 md:p-12 rounded-2xl border border-slate-100 shadow-sm space-y-12">
                        
                        <div>
                            <h2 className="text-2xl font-bold text-slate-900 mb-4">{__('landing_legal.cookie_intro_title')}</h2>
                            <p className="text-slate-600 leading-relaxed">{__('landing_legal.cookie_intro_body')}</p>
                        </div>

                        <div>
                            <h2 className="text-2xl font-bold text-slate-900 mb-4">{__('landing_legal.cookie_use_title')}</h2>
                            <p className="text-slate-600 leading-relaxed">{__('landing_legal.cookie_use_body')}</p>
                        </div>

                        <div>
                            <h2 className="text-2xl font-bold text-slate-900 mb-4">{__('landing_legal.cookie_types_title')}</h2>
                            <p className="text-slate-600 leading-relaxed">{__('landing_legal.cookie_types_body')}</p>
                        </div>

                        <div>
                            <h2 className="text-2xl font-bold text-slate-900 mb-4">{__('landing_legal.cookie_third_party_title')}</h2>
                            <p className="text-slate-600 leading-relaxed">{__('landing_legal.cookie_third_party_body')}</p>
                        </div>

                        <div>
                            <h2 className="text-2xl font-bold text-slate-900 mb-4">{__('landing_legal.cookie_control_title')}</h2>
                            <p className="text-slate-600 leading-relaxed">{__('landing_legal.cookie_control_body')}</p>
                        </div>

                        <div>
                            <h2 className="text-2xl font-bold text-slate-900 mb-4">{__('landing_legal.cookie_updates_title')}</h2>
                            <p className="text-slate-600 leading-relaxed">{__('landing_legal.cookie_updates_body')}</p>
                        </div>

                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head } from '@inertiajs/react';
import { __ } from '@/lib/i18n';

export default function Privacy({ canLogin, canRegister }) {
    return (
        <PublicLayout auth={{ user: null }}>
            <Head title={__('landing_legal.privacy_meta_title')} />

            <section className="relative pt-32 pb-24 overflow-hidden bg-white border-b border-slate-100">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="max-w-4xl">
                        <span className="inline-block py-1 px-3 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold uppercase tracking-wider mb-6">
                            {__('landing_legal.legal_center')}
                        </span>
                        <h1 className="text-4xl sm:text-6xl font-extrabold text-slate-900 tracking-tight mb-6">
                            {__('landing_legal.privacy_title')}
                        </h1>
                        <p className="text-xl text-slate-600 font-light mb-6 max-w-2xl leading-relaxed">
                            {__('landing_legal.privacy_subtitle')}
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
                            <h2 className="text-2xl font-bold text-slate-900 mb-4">{__('landing_legal.privacy_intro_title')}</h2>
                            <p className="text-slate-600 leading-relaxed">{__('landing_legal.privacy_intro_body')}</p>
                        </div>

                        <div>
                            <h2 className="text-2xl font-bold text-slate-900 mb-4">{__('landing_legal.privacy_data_collect_title')}</h2>
                            <p className="text-slate-600 leading-relaxed">{__('landing_legal.privacy_data_collect_body')}</p>
                        </div>

                        <div>
                            <h2 className="text-2xl font-bold text-slate-900 mb-4">{__('landing_legal.privacy_data_use_title')}</h2>
                            <p className="text-slate-600 leading-relaxed">{__('landing_legal.privacy_data_use_body')}</p>
                        </div>

                        <div>
                            <h2 className="text-2xl font-bold text-slate-900 mb-4">{__('landing_legal.privacy_data_sharing_title')}</h2>
                            <p className="text-slate-600 leading-relaxed">{__('landing_legal.privacy_data_sharing_body')}</p>
                        </div>

                        <div>
                            <h2 className="text-2xl font-bold text-slate-900 mb-4">{__('landing_legal.privacy_data_security_title')}</h2>
                            <p className="text-slate-600 leading-relaxed">{__('landing_legal.privacy_data_security_body')}</p>
                        </div>

                        <div>
                            <h2 className="text-2xl font-bold text-slate-900 mb-4">{__('landing_legal.privacy_user_rights_title')}</h2>
                            <p className="text-slate-600 leading-relaxed">{__('landing_legal.privacy_user_rights_body')}</p>
                        </div>

                        <div>
                            <h2 className="text-2xl font-bold text-slate-900 mb-4">{__('landing_legal.privacy_intl_transfers_title')}</h2>
                            <p className="text-slate-600 leading-relaxed">{__('landing_legal.privacy_intl_transfers_body')}</p>
                        </div>

                        <div>
                            <h2 className="text-2xl font-bold text-slate-900 mb-4">{__('landing_legal.privacy_contact_title')}</h2>
                            <p className="text-slate-600 leading-relaxed mb-6">{__('landing_legal.privacy_contact_body')}</p>
                            <a href="mailto:legal@musoftwares.com" className="inline-flex items-center justify-center h-10 px-6 rounded-lg bg-slate-900 text-white font-medium hover:bg-slate-800 transition-colors">
                                {__('landing_legal.contact_support')}
                            </a>
                        </div>

                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { MapPin, Mail, Phone } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function Contact({ canLogin, canRegister }) {
    return (
        <PublicLayout auth={{ user: null }}>
            <Head title={__('landing_company.contact_meta_title')} />

            <section className="relative pt-32 pb-24 overflow-hidden bg-white">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="max-w-4xl">
                        <h1 className="text-5xl sm:text-7xl font-extrabold text-slate-900 tracking-tight mb-8">
                            {__('landing_company.contact_title')}
                        </h1>
                        <p className="text-xl text-slate-600 font-light mb-12 max-w-2xl leading-relaxed">
                            {__('landing_company.contact_subtitle')}
                        </p>
                    </div>
                </div>
            </section>

            <section className="py-24 bg-slate-50 border-t border-slate-100">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-12">
                        <div className="bg-white p-8 rounded-2xl border border-slate-100 shadow-sm flex flex-col items-start text-left">
                            <div className="h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center mb-6">
                                <Mail className="h-6 w-6 text-slate-900" />
                            </div>
                            <h3 className="text-lg font-bold text-slate-900 mb-2">{__('landing_company.contact_sales_title')}</h3>
                            <p className="text-slate-600 font-light mb-6 flex-1">{__('landing_company.contact_sales_desc')}</p>
                            <a href="mailto:admin@musoftwares.com" className="text-slate-900 font-semibold hover:underline">admin@musoftwares.com</a>
                        </div>

                        <div className="bg-white p-8 rounded-2xl border border-slate-100 shadow-sm flex flex-col items-start text-left">
                            <div className="h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center mb-6">
                                <Phone className="h-6 w-6 text-slate-900" />
                            </div>
                            <h3 className="text-lg font-bold text-slate-900 mb-2">{__('landing_company.contact_support_title')}</h3>
                            <p className="text-slate-600 font-light mb-6 flex-1">{__('landing_company.contact_support_desc')}</p>
                            <div className="flex flex-col space-y-2 mt-auto">
                                <a href="tel:+201015218548" className="text-slate-900 font-semibold hover:underline">{__('landing_company.contact_support_call')}</a>
                                <a href="https://wa.me/201015218548" target="_blank" rel="noreferrer" className="text-slate-900 font-semibold hover:underline">{__('landing_company.contact_support_whatsapp')}</a>
                            </div>
                        </div>

                        <div className="bg-white p-8 rounded-2xl border border-slate-100 shadow-sm flex flex-col items-start text-left">
                            <div className="h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center mb-6">
                                <MapPin className="h-6 w-6 text-slate-900" />
                            </div>
                            <h3 className="text-lg font-bold text-slate-900 mb-2">{__('landing_company.contact_hq_title')}</h3>
                            <p className="text-slate-600 font-light mb-6 flex-1">{__('landing_company.contact_hq_desc')}</p>
                            <span className="text-slate-900 font-semibold">{__('landing_company.contact_hq_address')}</span>
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { MapPin, Mail, Phone } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function Contact({ canLogin, canRegister }) {
    return (
        <PublicLayout auth={{ user: null }}>
            <Head title={__('general.contact_sales_musoftware')} />

            <section className="relative pt-32 pb-24 overflow-hidden bg-white">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="max-w-4xl">
                        <h1 className="text-5xl sm:text-7xl font-extrabold text-slate-900 tracking-tight mb-8">
                            {__('general.get_in_touch')}</h1>
                        <p className="text-xl text-slate-600 font-light mb-12 max-w-2xl leading-relaxed">
                            {__('general.speak_directly_with_our_engineering_and')}</p>
                    </div>
                </div>
            </section>

            <section className="py-24 bg-slate-50 border-t border-slate-100">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-12">
                        <div className="bg-white p-8 rounded-2xl border border-slate-100 shadow-sm flex flex-col items-start text-start">
                            <div className="h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center mb-6">
                                <Mail className="h-6 w-6 text-slate-900" />
                            </div>
                            <h3 className="text-lg font-bold text-slate-900 mb-2">{__('general.sales_inquiries')}</h3>
                            <p className="text-slate-600 font-light mb-6 flex-1">{__('general.email_our_solutions_team_for_pricing_arc')}</p>
                            <a href="mailto:admin@musoftwares.com" className="text-slate-900 font-semibold hover:underline">admin@musoftwares.com</a>
                        </div>

                        <div className="bg-white p-8 rounded-2xl border border-slate-100 shadow-sm flex flex-col items-start text-start">
                            <div className="h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center mb-6">
                                <Phone className="h-6 w-6 text-slate-900" />
                            </div>
                            <h3 className="text-lg font-bold text-slate-900 mb-2">{__('general.technical_support')}</h3>
                            <p className="text-slate-600 font-light mb-6 flex-1">{__('general.for_existing_enterprise_clients_requirin')}</p>
                            <div className="flex flex-col space-y-2 mt-auto">
                                <a href="tel:+201015218548" className="text-slate-900 font-semibold hover:underline">Call: +201015218548</a>
                                <a href="https://wa.me/201015218548" target="_blank" rel="noreferrer" className="text-slate-900 font-semibold hover:underline">WhatsApp: +201015218548</a>
                            </div>
                        </div>

                        <div className="bg-white p-8 rounded-2xl border border-slate-100 shadow-sm flex flex-col items-start text-start">
                            <div className="h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center mb-6">
                                <MapPin className="h-6 w-6 text-slate-900" />
                            </div>
                            <h3 className="text-lg font-bold text-slate-900 mb-2">{__('general.headquarters')}</h3>
                            <p className="text-slate-600 font-light mb-6 flex-1">{__('general.visit_our_main_engineering_office_for_sc')}</p>
                            <span className="text-slate-900 font-semibold">{__('general.suez_egypt')}</span>
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

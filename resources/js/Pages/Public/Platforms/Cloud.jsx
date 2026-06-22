import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { __ } from '@/lib/i18n';

export default function Cloud({ canLogin, canRegister }) {
    return (
        <PublicLayout auth={{ user: null }}>
            <Head title={__('general.mu_cloud_dedicated_infrastructure')} />

            <section className="relative pt-32 pb-24 overflow-hidden bg-white">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="max-w-4xl">
                        <h1 className="text-5xl sm:text-7xl font-extrabold text-slate-900 tracking-tight mb-8">
                            {__('general.cloud_infrastructure')}</h1>
                        <p className="text-xl text-slate-600 font-light mb-12 max-w-2xl leading-relaxed">
                            {__('general.scalable_secure_and_fully_managed_cloud')}</p>
                        <div className="flex flex-col sm:flex-row gap-4">
                            <a href="mailto:admin@musoftwares.com">
                                <Button size="lg" className="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white rounded-full px-10 h-14 text-base font-semibold transition-all">
                                    {__('general.contact_sales')}</Button>
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <section className="py-24 bg-slate-50 border-t border-slate-100">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-12">
                        <div>
                            <h3 className="text-xl font-bold text-slate-900 mb-4">{__('general.high_availability')}</h3>
                            <p className="text-slate-600 font-light">Load-balanced servers ensuring 99.9% uptime for mission-critical software.</p>
                        </div>
                        <div>
                            <h3 className="text-xl font-bold text-slate-900 mb-4">{__('general.automated_backups')}</h3>
                            <p className="text-slate-600 font-light">{__('general.secure_offsite_daily_snapshots_protect_y')}</p>
                        </div>
                        <div>
                            <h3 className="text-xl font-bold text-slate-900 mb-4">{__('general.isolated_environments')}</h3>
                            <p className="text-slate-600 font-light">{__('general.dedicated_resources_and_private_networks')}</p>
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

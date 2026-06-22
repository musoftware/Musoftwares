import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { __ } from '@/lib/i18n';

export default function RealEstate({ canLogin, canRegister }) {
    return (
        <PublicLayout auth={{ user: null }}>
            <Head title={__('general.real_estate_software_musoftware')} />

            <section className="relative pt-32 pb-24 overflow-hidden bg-white">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="max-w-4xl">
                        <h1 className="text-5xl sm:text-7xl font-extrabold text-slate-900 tracking-tight mb-8">
                            {__('general.property_management_infrastructure')}</h1>
                        <p className="text-xl text-slate-600 font-light mb-12 max-w-2xl leading-relaxed">
                            {__('general.centralized_systems_for_real_estate_agen')}</p>
                        <div className="flex flex-col sm:flex-row gap-4">
                            <a href="mailto:admin@musoftwares.com">
                                <Button size="lg" className="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white rounded-full px-10 h-14 text-base font-semibold transition-all">
                                    {__('general.discuss_real_estate_needs')}</Button>
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <section className="py-24 bg-slate-50 border-t border-slate-100">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-12">
                        <div>
                            <h3 className="text-xl font-bold text-slate-900 mb-4">{__('general.portfolio_tracking')}</h3>
                            <p className="text-slate-600 font-light">{__('general.monitor_property_values_maintenance_sche')}</p>
                        </div>
                        <div>
                            <h3 className="text-xl font-bold text-slate-900 mb-4">{__('general.tenant_portals')}</h3>
                            <p className="text-slate-600 font-light">{__('general.provide_residents_with_secure_access_for')}</p>
                        </div>
                        <div>
                            <h3 className="text-xl font-bold text-slate-900 mb-4">{__('general.leasing_automation')}</h3>
                            <p className="text-slate-600 font-light">{__('general.generate_contracts_process_background_ch')}</p>
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

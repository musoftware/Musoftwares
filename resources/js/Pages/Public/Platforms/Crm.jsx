import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { motion } from 'framer-motion';
import { __ } from '@/lib/i18n';

export default function Crm({ canLogin, canRegister }) {
    return (
        <PublicLayout auth={{ user: null }}>
            <Head title={__('general.mu_crm_customer_operations_platform')} />

            <section className="relative pt-32 pb-24 overflow-hidden bg-white">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="max-w-4xl">
                        <h1 className="text-5xl sm:text-7xl font-extrabold text-slate-900 tracking-tight mb-8">
                            {__('general.customer_operations_platform')}</h1>
                        <p className="text-xl text-slate-600 font-light mb-12 max-w-2xl leading-relaxed">
                            {__('general.a_centralized_infrastructure_for_managin')}</p>
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
                            <h3 className="text-xl font-bold text-slate-900 mb-4">{__('general.centralized_data')}</h3>
                            <p className="text-slate-600 font-light">{__('general.single_source_of_truth_for_all_client_in')}</p>
                        </div>
                        <div>
                            <h3 className="text-xl font-bold text-slate-900 mb-4">{__('general.pipeline_management')}</h3>
                            <p className="text-slate-600 font-light">{__('general.clear_visibility_into_sales_cycles_conve')}</p>
                        </div>
                        <div>
                            <h3 className="text-xl font-bold text-slate-900 mb-4">{__('general.automated_workflows')}</h3>
                            <p className="text-slate-600 font-light">{__('general.reduce_manual_entry_with_automated_lead')}</p>
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

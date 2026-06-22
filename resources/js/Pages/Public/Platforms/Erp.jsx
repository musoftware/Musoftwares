import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { __ } from '@/lib/i18n';

export default function Erp({ canLogin, canRegister }) {
    return (
        <PublicLayout auth={{ user: null }}>
            <Head title={__('general.mu_erp_enterprise_resource_planning')} />

            <section className="relative pt-32 pb-24 overflow-hidden bg-white">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="max-w-4xl">
                        <h1 className="text-5xl sm:text-7xl font-extrabold text-slate-900 tracking-tight mb-8">
                            {__('general.enterprise_resource_planning')}</h1>
                        <p className="text-xl text-slate-600 font-light mb-12 max-w-2xl leading-relaxed">
                            {__('general.a_complete_suite_to_manage_financials_op')}</p>
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
                            <h3 className="text-xl font-bold text-slate-900 mb-4">{__('general.financial_ledger')}</h3>
                            <p className="text-slate-600 font-light">{__('general.accurate_realtime_tracking_of_income_exp')}</p>
                        </div>
                        <div>
                            <h3 className="text-xl font-bold text-slate-900 mb-4">{__('general.inventory_control')}</h3>
                            <p className="text-slate-600 font-light">{__('general.monitor_stock_levels_predict_shortages_a')}</p>
                        </div>
                        <div>
                            <h3 className="text-xl font-bold text-slate-900 mb-4">{__('general.hr_payroll')}</h3>
                            <p className="text-slate-600 font-light">{__('general.manage_employee_records_time_tracking_an')}</p>
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

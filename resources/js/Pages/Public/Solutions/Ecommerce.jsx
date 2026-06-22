import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { __ } from '@/lib/i18n';

export default function Ecommerce({ canLogin, canRegister }) {
    return (
        <PublicLayout auth={{ user: null }}>
            <Head title={__('general.ecommerce_solutions_musoftware')} />

            <section className="relative pt-32 pb-24 overflow-hidden bg-white">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="max-w-4xl">
                        <h1 className="text-5xl sm:text-7xl font-extrabold text-slate-900 tracking-tight mb-8">
                            {__('general.highvolume_retail_systems')}</h1>
                        <p className="text-xl text-slate-600 font-light mb-12 max-w-2xl leading-relaxed">
                            {__('general.robust_ecommerce_infrastructure_designed')}</p>
                        <div className="flex flex-col sm:flex-row gap-4">
                            <a href="mailto:admin@musoftwares.com">
                                <Button size="lg" className="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white rounded-full px-10 h-14 text-base font-semibold transition-all">
                                    {__('general.discuss_ecommerce_needs')}</Button>
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <section className="py-24 bg-slate-50 border-t border-slate-100">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-12">
                        <div>
                            <h3 className="text-xl font-bold text-slate-900 mb-4">{__('general.inventory_synchronization')}</h3>
                            <p className="text-slate-600 font-light">{__('general.realtime_stock_updates_across_multiple_w')}</p>
                        </div>
                        <div>
                            <h3 className="text-xl font-bold text-slate-900 mb-4">{__('general.payment_processing')}</h3>
                            <p className="text-slate-600 font-light">{__('general.secure_multicurrency_payment_gateways_wi')}</p>
                        </div>
                        <div>
                            <h3 className="text-xl font-bold text-slate-900 mb-4">{__('general.order_fulfillment')}</h3>
                            <p className="text-slate-600 font-light">{__('general.automate_shipping_logic_tracking_generat')}</p>
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

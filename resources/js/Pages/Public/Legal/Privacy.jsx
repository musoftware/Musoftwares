import React from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, usePage } from '@inertiajs/react';
import { __ } from '@/lib/i18n';

export default function Privacy({ legalInfo: propLegal }) {
    const { privacyCompliance } = usePage().props;
    const legal = propLegal || privacyCompliance?.legalInfo || {};
    const rows = [1, 2, 3, 4, 5];

    return (
        <PublicLayout>
            <Head title={__('landing_legal.privacy_meta_title')} />

            <section className="relative pt-24 pb-16 overflow-x-clip bg-white border-b border-slate-100">
                <div className="mx-auto max-w-4xl px-4 sm:px-6">
                    <p className="text-xs font-semibold uppercase tracking-wider text-[#0071e3] mb-3">
                        {__('landing_legal.legal_center')}
                    </p>
                    <h1 className="text-3xl sm:text-5xl font-bold text-slate-900 tracking-tight mb-4">
                        {__('landing_legal.privacy_title')}
                    </h1>
                    <p className="text-base sm:text-lg text-slate-600 mb-4 leading-relaxed">
                        {__('landing_legal.privacy_subtitle')}
                    </p>
                    <p className="text-xs text-slate-500 font-medium">
                        {__('landing_legal.last_updated')} {legal.privacy_policy_version ? `(${legal.privacy_policy_version})` : ''}
                    </p>
                </div>
            </section>

            <section className="py-16 bg-slate-50 overflow-x-clip">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 space-y-8">
                    <div id="controller" className="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 space-y-4">
                        <h2 className="text-xl font-bold text-slate-900">{__('privacy_compliance.controller_section_title')}</h2>
                        <p className="text-sm text-slate-600 leading-relaxed">{__('landing_legal.privacy_intro_body')}</p>
                        <dl className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-slate-100 text-sm">
                            <div>
                                <dt className="font-semibold text-slate-900">{__('privacy_compliance.controller_legal_name_label')}</dt>
                                <dd className="text-slate-600 mt-1">{legal.legal_company_name}</dd>
                            </div>
                            <div>
                                <dt className="font-semibold text-slate-900">{__('privacy_compliance.controller_headquarters_label')}</dt>
                                <dd className="text-slate-600 mt-1">{legal.legal_headquarters_address}</dd>
                            </div>
                            <div>
                                <dt className="font-semibold text-slate-900">{__('privacy_compliance.controller_registry_label')}</dt>
                                <dd className="text-slate-600 mt-1">{legal.legal_commercial_registry}</dd>
                            </div>
                            <div>
                                <dt className="font-semibold text-slate-900">{__('privacy_compliance.controller_policy_version_label')}</dt>
                                <dd className="text-slate-600 mt-1 font-mono">{legal.privacy_policy_version}</dd>
                            </div>
                        </dl>
                    </div>

                    <div id="data-table" className="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 space-y-4">
                        <h2 className="text-xl font-bold text-slate-900">{__('privacy_compliance.inventory_table_title')}</h2>
                        <p className="text-sm text-slate-600 leading-relaxed">{__('landing_legal.privacy_data_collect_body')}</p>
                        <div className="overflow-x-auto border border-slate-200 rounded-xl">
                            <table className="w-full text-start border-collapse text-xs sm:text-sm">
                                <thead>
                                    <tr className="bg-slate-50 border-b border-slate-200 text-slate-900">
                                        <th className="p-3 sm:p-4 font-semibold text-start">{__('privacy_compliance.inventory_col_data')}</th>
                                        <th className="p-3 sm:p-4 font-semibold text-start">{__('privacy_compliance.inventory_col_purpose')}</th>
                                        <th className="p-3 sm:p-4 font-semibold text-start">{__('privacy_compliance.inventory_col_retention')}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {rows.map((row) => (
                                        <tr key={row}>
                                            <td className="p-3 sm:p-4 font-medium text-slate-900 align-top">{__(`privacy_compliance.inventory_row_${row}_data`)}</td>
                                            <td className="p-3 sm:p-4 text-slate-600 align-top">{__(`privacy_compliance.inventory_row_${row}_purpose`)}</td>
                                            <td className="p-3 sm:p-4 text-slate-600 align-top">{__(`privacy_compliance.inventory_row_${row}_retention`)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="processors" className="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 space-y-4">
                        <h2 className="text-xl font-bold text-slate-900">{__('privacy_compliance.processors_section_title')}</h2>
                        <p className="text-sm text-slate-600 leading-relaxed">{__('landing_legal.privacy_data_sharing_body')}</p>
                        <ul className="list-disc ps-5 space-y-2 text-sm text-slate-700">
                            <li>{__('privacy_compliance.processors_payment')}</li>
                            <li>{__('privacy_compliance.processors_hosting', { regions: legal.hosting_regions_disclosure || '' })}</li>
                            <li>{__('privacy_compliance.processors_comms')}</li>
                        </ul>
                        <p className="text-sm text-slate-600 pt-3 border-t border-slate-100 leading-relaxed">
                            {__('landing_legal.privacy_data_security_body')}
                        </p>
                    </div>

                    <div id="rights" className="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 space-y-4">
                        <h2 className="text-xl font-bold text-slate-900">{__('privacy_compliance.rights_sla_section_title')}</h2>
                        <p className="text-sm text-slate-600 leading-relaxed">{__('privacy_compliance.rights_sla_intro')}</p>
                        <ol className="list-decimal ps-5 space-y-2 text-sm text-slate-700">
                            <li>{__('privacy_compliance.rights_step_1')}</li>
                            <li>{__('privacy_compliance.rights_step_2')}</li>
                            <li>{__('privacy_compliance.rights_step_3')}</li>
                        </ol>
                    </div>

                    <div id="dpo" className="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 space-y-4">
                        <h2 className="text-xl font-bold text-slate-900">{__('privacy_compliance.dpo_section_title')}</h2>
                        <p className="text-sm text-slate-600 leading-relaxed">{__('privacy_compliance.dpo_section_body')}</p>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-slate-100 text-sm">
                            <div>
                                <span className="block font-semibold text-slate-900">{__('privacy_compliance.dpo_email_label')}</span>
                                <a href={`mailto:${legal.dpo_email || 'admin@musoftwares.com'}`} className="text-[#0071e3] hover:underline font-medium break-all">
                                    {legal.dpo_email || 'admin@musoftwares.com'}
                                </a>
                            </div>
                            <div>
                                <span className="block font-semibold text-slate-900">{__('privacy_compliance.dpo_phone_label')}</span>
                                <a href={`tel:${(legal.dpo_phone || '+201015218548').replace(/\s+/g, '')}`} dir="ltr" className="text-[#0071e3] hover:underline font-medium inline-block">
                                    {legal.dpo_phone || '+20 101 521 8548'}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

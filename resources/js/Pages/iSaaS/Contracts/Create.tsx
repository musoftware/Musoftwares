import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ArrowLeft } from 'lucide-react';
import ContractForm from './Components/ContractForm';
import { __ } from '@/lib/i18n';

interface CreateProps {
    currencies: Array<{ id: number; name: string; symbol: string }>;
}

export default function Create({ currencies }: CreateProps) {
    return (
        <AuthenticatedLayout>
            <Head title={__('general.create_contract')} />
            
            <div className="mx-auto w-full max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-2 flex items-center justify-between">
                    <div>
                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-600 mb-3 border border-slate-200">{__('general.freelance_tools')}</span>
                        <div className="flex items-baseline gap-3">
                            <h1 className="text-3xl font-bold tracking-tight text-slate-900">{__('general.create_contract')}</h1>
                            <span className="text-slate-500 font-medium">/ iSAAS</span>
                        </div>
                    </div>
                </div>

                <div className="mb-6 flex items-center justify-between">
                    <Link href={route('isaas.contracts.index')} className="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700">
                        <ArrowLeft className="me-2 h-4 w-4" />{__('general.back_to_contracts')}</Link>
                </div>

                <ContractForm currencies={currencies} />
            </div>
        </AuthenticatedLayout>
    );
}

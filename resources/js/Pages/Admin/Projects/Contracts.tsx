import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { __ } from '@/lib/i18n';
import { formatMoney } from '@/lib/utils';
import { FileText, Plus, Sparkles, Copy, History, ArrowLeft } from 'lucide-react';
import { toast } from 'sonner';
import ContractModal from './Contracts/ContractModal';

export default function Contracts({ project, contracts, currencies }) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingContract, setEditingContract] = useState(null);

    const handleCreate = () => {
        setEditingContract(null);
        setIsModalOpen(true);
    };

    const handleEdit = (contract) => {
        setEditingContract(contract);
        setIsModalOpen(true);
    };

    const handleCopyLink = (uuid) => {
        const link = `${window.location.origin}/c/${uuid}`;
        navigator.clipboard.writeText(link)
            .then(() => toast.success(__('general.link_copied')))
            .catch(() => toast.error(__('admin.contracts_copy_failed')));
    };

    return (
        <AdminSidebarLayout
            title={__('admin.contracts_page_title', { project: project.project_name })}
            header={__('admin.contracts_page_header', { project: project.project_name })}
        >
            <div className="mb-6 flex justify-between items-center">
                <Link href="/admin/projects?status=active" className="inline-flex items-center gap-1 text-slate-900 hover:underline">
                    <ArrowLeft className="w-4 h-4 rtl:rotate-180" />
                    {__('general.back_to_projects')}
                </Link>
                <div className="flex gap-2">
                    <Button onClick={handleCreate} className="gap-2">
                        <Plus className="w-4 h-4" />
                        {__('general.create_contract')}</Button>
                </div>
            </div>

            {contracts.length === 0 ? (
                <Card className="text-center p-12 bg-slate-50 border-dashed">
                    <div className="flex flex-col items-center gap-4">
                        <div className="h-12 w-12 rounded-full bg-slate-50 flex items-center justify-center">
                            <FileText className="h-6 w-6 text-slate-900" />
                        </div>
                        <div>
                            <h3 className="text-lg font-semibold text-slate-900">{__('general.no_contracts_yet')}</h3>
                            <p className="text-slate-500 max-w-sm mt-1">
                                {__('general.create_a_contract_or_proposal_for_this_p')}</p>
                        </div>
                        <Button onClick={handleCreate} className="mt-2 gap-2">
                            <Sparkles className="w-4 h-4" />
                            {__('general.generate_with_ai')}</Button>
                    </div>
                </Card>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {contracts.map(contract => (
                        <Card key={contract.id} className="flex flex-col">
                            <CardHeader className="pb-3 border-b">
                                <div className="flex justify-between items-start">
                                    <div className="flex items-center gap-2">
                                        <FileText className="h-5 w-5 text-slate-900" />
                                        <CardTitle className="text-base truncate">{contract.project_name}</CardTitle>
                                    </div>
                                    <span className={`px-2.5 py-0.5 rounded-full text-xs font-semibold
                                        ${contract.status === 'signed' ? 'bg-green-100 text-slate-900' : ''}
                                        ${contract.status === 'draft' ? 'bg-slate-100 text-slate-700' : ''}
                                        ${contract.status === 'sent' ? 'bg-slate-50 text-slate-900' : ''}
                                    `}>
                                        {__(`admin.contracts_status_${contract.status}`)}
                                    </span>
                                </div>
                                <CardDescription className="line-clamp-2 mt-2 text-xs">
                                    {contract.description || __('general.no_description_provided')}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="pt-4 flex-1 flex flex-col justify-between gap-4">
                                <div>
                                    <div className="flex justify-between text-sm mb-2">
                                        <span className="text-slate-500">{__('admin.contracts_amount_label')}</span>
                                        <span className="font-medium text-slate-900">
                                            {formatMoney(contract.total_amount, contract.currency_id)}
                                        </span>
                                    </div>
                                    <div className="flex justify-between text-sm mb-2">
                                        <span className="text-slate-500">{__('admin.contracts_versions_label')}</span>
                                        <span className="font-medium flex items-center gap-1">
                                            <History className="w-3 h-3" />
                                            {contract.versions?.length || 1}
                                        </span>
                                    </div>
                                    {contract.status === 'signed' && (
                                        <div className="flex justify-between text-sm text-slate-900 bg-green-50 p-2 rounded">
                                            <span>{__('admin.contracts_signed_by_label')}</span>
                                            <span className="font-semibold truncate max-w-[120px]" title={contract.client_name}>
                                                {contract.client_name}
                                            </span>
                                        </div>
                                    )}
                                </div>
                                <div className="flex gap-2 mt-auto">
                                    <Button variant="outline" size="sm" className="flex-1" onClick={() => handleEdit(contract)}>
                                        {__('admin.contracts_edit_view')}
                                    </Button>
                                    <Button variant="outline" size="sm" className="px-3 text-slate-500 hover:text-slate-900" onClick={() => handleCopyLink(contract.uuid)} title={__('general.copy_public_link')} aria-label={__('general.copy_public_link')}>
                                        <Copy className="w-4 h-4" />
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}

            <ContractModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                project={project}
                contract={editingContract}
                currencies={currencies}
            />
        </AdminSidebarLayout>
    );
}

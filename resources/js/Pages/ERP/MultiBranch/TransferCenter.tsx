import React, { useState } from 'react';
import ERPLayout from '@/Layouts/ERPLayout';
import { Head } from '@inertiajs/react';
import { ArrowRightLeft, PackageCheck, FileText, Check, X, Clock } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { useERPMenu } from '@/hooks/useERPMenu';
import { EmptyState } from '@/Components/ui/EmptyState';
import { Badge } from '@/Components/ui/badge';
import { format } from 'date-fns';
import { __ } from '@/lib/i18n';

interface Branch {
    id: number;
    name: string;
    code: string;
}

interface Transfer {
    id: number;
    from_branch_id: number;
    to_branch_id: number;
    type: string;
    status: string;
    created_at: string;
}

interface Props {
    transfers: Transfer[];
    branches: Branch[];
}

export default function TransferCenter({ transfers = [], branches = [] }: Props) {
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('branches');
    const [isNewModalOpen, setIsNewModalOpen] = useState(false);

    const pendingCount = transfers.filter(t => t.status === 'pending').length;
    const completedToday = transfers.filter(t => 
        t.status === 'completed' && 
        new Date(t.created_at).toDateString() === new Date().toDateString()
    ).length;

    const getBranchName = (id: number) => {
        return branches.find(b => b.id === id)?.name || id;
    };

    const getStatusBadge = (status: string) => {
        switch(status?.toLowerCase()) {
            case 'pending': return <Badge variant="outline" className="text-amber-600 bg-amber-50 border-amber-200"><Clock className="w-3 h-3 mr-1"/> {__('erp.status_pending')}</Badge>;
            case 'completed': return <Badge variant="outline" className="text-emerald-600 bg-emerald-50 border-emerald-200"><Check className="w-3 h-3 mr-1"/> {__('erp.status_completed')}</Badge>;
            case 'rejected': return <Badge variant="outline" className="text-red-600 bg-red-50 border-red-200"><X className="w-3 h-3 mr-1"/> {__('erp.status_rejected')}</Badge>;
            default: return <Badge variant="outline">{status}</Badge>;
        }
    };

    return (
        <ERPLayout 
            title={__('erp.transfer_center')}
            menuItems={menuItems}
            lockedAddons={lockedAddons}
            workspaceName={workspaceName}
            tenantId={tenantId}
        >
            <Head title={__('erp.transfer_center_title')} />

            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                <div>
                    <h1 className="text-2xl font-semibold text-slate-900 tracking-tight">{__('erp.transfer_center')}</h1>
                    <p className="text-sm text-slate-500 mt-1">{__('erp.transfer_center_desc')}</p>
                </div>

                <Button size="sm" onClick={() => setIsNewModalOpen(true)}>
                    <ArrowRightLeft className="w-4 h-4 mr-2" />
                    {__('erp.new_transfer')}
                </Button>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div className="bg-white border rounded-xl p-6 shadow-sm">
                    <div className="flex items-center gap-3 mb-2">
                        <div className="p-2 bg-blue-50 text-blue-600 rounded-lg">
                            <ArrowRightLeft className="w-5 h-5" />
                        </div>
                        <h3 className="font-medium text-slate-900">{__('erp.pending_approvals')}</h3>
                    </div>
                    <p className="text-2xl font-bold mt-2 text-slate-900">{pendingCount}</p>
                </div>
                
                <div className="bg-white border rounded-xl p-6 shadow-sm">
                    <div className="flex items-center gap-3 mb-2">
                        <div className="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
                            <PackageCheck className="w-5 h-5" />
                        </div>
                        <h3 className="font-medium text-slate-900">{__('erp.completed_today')}</h3>
                    </div>
                    <p className="text-2xl font-bold mt-2 text-slate-900">{completedToday}</p>
                </div>
            </div>

            <div className="bg-white border rounded-xl shadow-sm overflow-hidden">
                <div className="p-6 border-b">
                    <h3 className="font-medium text-lg">{__('erp.recent_transfer_logs')}</h3>
                </div>
                
                {transfers.length === 0 ? (
                    <EmptyState
                        icon={<FileText className="w-12 h-12 text-slate-300" />}
                        title={__('erp.no_transfers_found')}
                        description={__('erp.no_transfers_desc')}
                    />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm border-collapse">
                            <thead>
                                <tr className="bg-slate-50 border-b">
                                    <th className="px-6 py-3.5 font-medium text-slate-500">{__('erp.date')}</th>
                                    <th className="px-6 py-3.5 font-medium text-slate-500">{__('erp.from_branch')}</th>
                                    <th className="px-6 py-3.5 font-medium text-slate-500">{__('erp.to_branch')}</th>
                                    <th className="px-6 py-3.5 font-medium text-slate-500">{__('erp.type')}</th>
                                    <th className="px-6 py-3.5 font-medium text-slate-500 text-center">{__('erp.status')}</th>
                                    <th className="px-6 py-3.5 font-medium text-slate-500 text-right">{__('erp.actions')}</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {transfers.map((transfer) => (
                                    <tr key={transfer.id} className="hover:bg-slate-50/50 transition-colors">
                                        <td className="px-6 py-4">
                                            {format(new Date(transfer.created_at), 'MMM d, yyyy HH:mm')}
                                        </td>
                                        <td className="px-6 py-4 font-medium">{getBranchName(transfer.from_branch_id)}</td>
                                        <td className="px-6 py-4 font-medium">{getBranchName(transfer.to_branch_id)}</td>
                                        <td className="px-6 py-4 capitalize">{transfer.type?.replace('_', ' ')}</td>
                                        <td className="px-6 py-4 text-center">
                                            {getStatusBadge(transfer.status)}
                                        </td>
                                        <td className="px-6 py-4 text-right">
                                            <Button variant="ghost" size="sm" className="text-blue-600">
                                                {__('erp.view_details')}
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </ERPLayout>
    );
}


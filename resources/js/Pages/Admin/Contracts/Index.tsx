import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import {
    Copy,
    Edit,
    FileText,
    History,
    MoreHorizontal,
    Plus,
    Sparkles,
    Trash2,
} from 'lucide-react';
import { EmptyState } from '@/Components/ui/EmptyState';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { formatMoney } from '@/lib/utils';
import { toast } from 'sonner';
import { __ } from '@/lib/i18n';

function getStatusClass(status: string) {
    switch (status) {
        case 'signed': return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'draft': return 'bg-slate-100 text-slate-700 border-slate-200';
        case 'sent': return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'active': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'completed': return 'bg-purple-100 text-purple-700 border-purple-200';
        default: return 'bg-slate-100 text-slate-700 border-slate-200';
    }
}

export default function Index({ contracts }) {
    const [pendingDelete, setPendingDelete] = useState<any | null>(null);

    const handleCopyLink = (uuid: string) => {
        const link = `${window.location.origin}/c/${uuid}`;
        navigator.clipboard.writeText(link);
        toast.success(__('general.link_copied') || 'Link copied to clipboard');
    };

    const handleDelete = () => {
        if (!pendingDelete) return;
        router.delete(`/admin/contracts/${pendingDelete.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(__('general.deleted') || 'Deleted');
                setPendingDelete(null);
            },
            onError: () => {
                toast.error(__('general.error_occurred') || 'Something went wrong');
                setPendingDelete(null);
            },
        });
    };

    const data = contracts?.data ?? [];

    return (
        <AdminSidebarLayout title={__('general.contracts')} header={__('general.all_contracts')}>
            <div className="mb-6 flex items-center justify-end gap-3">
                <Link href="/admin/contracts/quick-create">
                    <Button variant="outline" className="gap-2 border-amber-500 text-amber-900 bg-amber-50 hover:bg-amber-100 font-extrabold shadow-sm">
                        <Sparkles className="h-4 w-4 text-amber-600" />
                        التسعير والعقد السريع
                    </Button>
                </Link>
                <Link href="/admin/contracts/create">
                    <Button className="gap-2">
                        <Plus className="h-4 w-4" />
                        {__('general.create_contract')}
                    </Button>
                </Link>
            </div>

            {data.length === 0 ? (
                <EmptyState
                    icon={FileText}
                    title={__('general.no_contracts_yet')}
                    description={__('general.create_a_contract_or_proposal_for_this_p')}
                    action="/admin/contracts/create"
                    actionLabel={__('general.create_contract')}
                    actionIcon={Plus}
                />
            ) : (
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {data.map((contract: any) => (
                        <Card key={contract.id} className="flex flex-col">
                            <CardHeader className="border-b pb-3">
                                <div className="flex items-start justify-between gap-4">
                                    <div className="flex min-w-0 flex-1 items-start gap-2">
                                        <FileText className="mt-0.5 h-5 w-5 shrink-0 text-slate-900" />
                                        <Link href={`/admin/contracts/${contract.id}/edit`} className="hover:text-indigo-600 transition-colors">
                                            <CardTitle className="line-clamp-2 text-base">
                                                {contract.project_name}
                                            </CardTitle>
                                        </Link>
                                    </div>
                                    <div className="flex shrink-0 items-center gap-2">
                                        <span className={`rounded-full border px-2.5 py-0.5 text-xs font-semibold ${getStatusClass(contract.status)}`}>
                                            {contract.status}
                                        </span>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" className="h-8 w-8 p-0">
                                                    <MoreHorizontal className="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem asChild>
                                                    <Link href={`/admin/contracts/${contract.id}/edit`} className="flex w-full cursor-pointer items-center">
                                                        <Edit className="mr-2 h-4 w-4" />
                                                        {__('general.edit')}
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem onClick={() => handleCopyLink(contract.uuid)} className="cursor-pointer">
                                                    <Copy className="mr-2 h-4 w-4" />
                                                    {__('general.copy_public_link')}
                                                </DropdownMenuItem>
                                                <DropdownMenuItem
                                                    onClick={() => setPendingDelete(contract)}
                                                    className="cursor-pointer text-red-600 focus:text-red-600"
                                                >
                                                    <Trash2 className="mr-2 h-4 w-4" />
                                                    {__('general.delete')}
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>
                                <CardDescription className="mt-2 line-clamp-2 text-xs">
                                    {contract.description || __('general.no_description_provided')}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="flex flex-1 flex-col justify-between gap-4 pt-4">
                                <div className="space-y-2 text-sm">
                                    <div className="flex justify-between">
                                        <span className="text-slate-500">{__('general.amount')}:</span>
                                        <span className="font-medium text-slate-900">
                                            {formatMoney(contract.total_amount, contract.currency_id)}
                                        </span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-slate-500">{__('general.versions')}:</span>
                                        <span className="font-medium flex items-center gap-1">
                                            <History className="w-3 h-3" />
                                            {contract.versions?.length || 1}
                                        </span>
                                    </div>
                                    {contract.status === 'signed' && (
                                        <div className="flex justify-between bg-emerald-50 p-2 rounded text-slate-900">
                                            <span>{__('general.signed_by')}:</span>
                                            <span className="font-semibold truncate max-w-[120px]" title={contract.client_name}>
                                                {contract.client_name}
                                            </span>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}

            <ConfirmModal
                isOpen={pendingDelete !== null}
                title={__('general.confirm_delete_contract') || 'Delete contract?'}
                description={__('general.confirm_delete_contract_desc') || 'This action cannot be undone. The contract will be permanently deleted.'}
                confirmLabel={__('general.delete')}
                cancelLabel={__('general.cancel')}
                variant="danger"
                onConfirm={handleDelete}
                onCancel={() => setPendingDelete(null)}
            />
        </AdminSidebarLayout>
    );
}
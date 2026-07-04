import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Copy, Edit, History } from 'lucide-react';
import { formatMoney } from '@/lib/utils';
import { toast } from 'sonner';
import { __ } from '@/lib/i18n';

export default function Show({ contract }) {
    const handleCopyLink = () => {
        const link = `${window.location.origin}/c/${contract.uuid}`;
        navigator.clipboard.writeText(link);
        toast.success(__('general.public_link_copied') || 'Public link copied to clipboard');
    };

    return (
        <AdminSidebarLayout
            title={`${__('general.contract')}: ${contract.project_name}`}
            header={contract.project_name}
        >
            <div className="mb-6 flex justify-between items-center">
                <Link href="/admin/contracts" className="text-sm text-slate-500 hover:text-slate-900 flex items-center gap-1">
                    &larr; {__('general.back_to_contracts')}
                </Link>
                <div className="flex gap-2">
                    <Button variant="outline" onClick={handleCopyLink}>
                        <Copy className="w-4 h-4 me-2" />{__('general.copy_public_link')}
                    </Button>
                    <Link href={`/admin/contracts/${contract.id}/edit`}>
                        <Button>
                            <Edit className="w-4 h-4 me-2" />{__('general.edit_contract')}
                        </Button>
                    </Link>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>{__('general.details')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <h4 className="text-sm font-semibold text-slate-500">{__('general.description')}</h4>
                                <p className="mt-1 text-slate-900">{contract.description || __('general.no_description_provided')}</p>
                            </div>
                            <div className="grid grid-cols-2 gap-4 pt-4 border-t">
                                <div>
                                    <h4 className="text-sm font-semibold text-slate-500">{__('general.total_amount')}</h4>
                                    <p className="mt-1 font-medium font-mono text-slate-900">
                                        {formatMoney(contract.total_amount, contract.currency_id)}
                                    </p>
                                </div>
                                <div>
                                    <h4 className="text-sm font-semibold text-slate-500">{__('general.status')}</h4>
                                    <p className="mt-1 capitalize text-slate-900">{contract.status}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>{__('general.pricing_items')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {contract.content?.pricing_items?.length > 0 ? (
                                <div className="space-y-3">
                                    {contract.content.pricing_items.map((item: any, idx: number) => (
                                        <div key={idx} className="flex justify-between items-center p-3 border rounded-md">
                                            <div className="min-w-0">
                                                <h5 className="font-semibold text-slate-900 truncate">{item.item}</h5>
                                                {item.description && <p className="text-sm text-slate-500">{item.description}</p>}
                                            </div>
                                            <div className="font-medium font-mono text-slate-900">
                                                {formatMoney(item.price, contract.currency_id)}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-slate-500">{__('general.no_pricing_items')}</p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>{__('general.client_info')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {contract.status === 'signed' ? (
                                <div className="space-y-2 text-sm">
                                    <p><span className="font-semibold text-slate-500">{__('general.signed_by')}:</span> {contract.client_name}</p>
                                    <p><span className="font-semibold text-slate-500">{__('general.signed_at')}:</span> {new Date(contract.signed_at).toLocaleString()}</p>
                                </div>
                            ) : (
                                <p className="text-slate-500">{__('general.not_signed_yet')}</p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <History className="h-4 w-4" />{__('general.version_history')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {contract.versions?.length > 0 ? (
                                <div className="space-y-3">
                                    {contract.versions.map((version: any, index: number) => (
                                        <div key={version.id} className="p-3 border rounded-md bg-slate-50">
                                            <div className="flex justify-between items-center mb-1">
                                                <span className="font-semibold text-sm">{__('general.version')} {contract.versions.length - index}</span>
                                                <span className="text-xs text-slate-500">{new Date(version.created_at).toLocaleDateString()}</span>
                                            </div>
                                            <div className="text-xs text-slate-600">
                                                {__('general.total')}: <span className="font-mono font-medium">{formatMoney(version.total_amount, contract.currency_id)}</span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-slate-500">{__('general.no_versions_yet')}</p>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { formatMoney } from '@/lib/utils';
import { __ } from '@/lib/i18n';

export default function Show({ contract }) {
    const handleCopyLink = () => {
        const link = `${window.location.origin}/c/${contract.uuid}`;
        navigator.clipboard.writeText(link);
        alert("Public link copied to clipboard");
    };

    return (
        <AdminSidebarLayout 
            title={`Contract: ${contract.project_name}`} 
            header={contract.project_name}
        >
            <div className="mb-6 flex justify-between items-center">
                <Link href="/admin/contracts" className="text-slate-900 hover:underline">
                    &larr; Back to Contracts
                </Link>
                <div className="flex gap-2">
                    <Button variant="outline" onClick={handleCopyLink}>Copy Public Link</Button>
                    <Link href={`/admin/contracts/${contract.id}/edit`}>
                        <Button>Edit Contract</Button>
                    </Link>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <h4 className="text-sm font-semibold text-slate-500">Description</h4>
                                <p className="mt-1">{contract.description || 'No description provided.'}</p>
                            </div>
                            <div className="grid grid-cols-2 gap-4 pt-4 border-t">
                                <div>
                                    <h4 className="text-sm font-semibold text-slate-500">Total Amount</h4>
                                    <p className="mt-1 font-medium">{formatMoney(contract.total_amount, contract.currency_id)}</p>
                                </div>
                                <div>
                                    <h4 className="text-sm font-semibold text-slate-500">Status</h4>
                                    <p className="mt-1 capitalize">{contract.status}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Pricing Items</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {contract.content?.pricing_items?.length > 0 ? (
                                <div className="space-y-4">
                                    {contract.content.pricing_items.map((item, idx) => (
                                        <div key={idx} className="flex justify-between items-center p-3 border rounded-md">
                                            <div>
                                                <h5 className="font-semibold">{item.item}</h5>
                                                {item.description && <p className="text-sm text-slate-500">{item.description}</p>}
                                            </div>
                                            <div className="font-medium">
                                                {formatMoney(item.price, contract.currency_id)}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-slate-500">No pricing items.</p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Client Info</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {contract.status === 'signed' ? (
                                <div className="space-y-2">
                                    <p><span className="font-semibold text-slate-500">Signed By:</span> {contract.client_name}</p>
                                    <p><span className="font-semibold text-slate-500">Signed At:</span> {new Date(contract.signed_at).toLocaleString()}</p>
                                </div>
                            ) : (
                                <p className="text-slate-500">Not signed yet.</p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Version History</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {contract.versions?.map((version, index) => (
                                    <div key={version.id} className="p-3 border rounded-md bg-slate-50">
                                        <div className="flex justify-between items-center mb-2">
                                            <span className="font-semibold text-sm">Version {contract.versions.length - index}</span>
                                            <span className="text-xs text-slate-500">{new Date(version.created_at).toLocaleDateString()}</span>
                                        </div>
                                        <div className="text-xs">
                                            Total: {formatMoney(version.total_amount, contract.currency_id)}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}

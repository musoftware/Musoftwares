import React from 'react';
import ERPLayout from '@/Layouts/ERPLayout';
import { Head } from '@inertiajs/react';
import { ArrowRightLeft, PackageCheck } from 'lucide-react';
import { Button } from '@/Components/ui/button';

interface Props {
    transfers: Array<any>;
}

export default function TransferCenter({ transfers }: Props) {
    return (
        <ERPLayout title="Transfer Center">
            <Head title="Transfer Center - Cross-Branch Operations" />

            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                <div>
                    <h1 className="text-2xl font-semibold text-slate-900 tracking-tight">Transfer Center</h1>
                    <p className="text-sm text-slate-500 mt-1">Cross-branch operations, stock balancing, and resource movement.</p>
                </div>

                <Button size="sm">
                    <ArrowRightLeft className="w-4 h-4 mr-2" />
                    New Transfer
                </Button>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div className="bg-white border rounded-xl p-6 shadow-sm">
                    <div className="flex items-center gap-3 mb-2">
                        <div className="p-2 bg-blue-50 text-blue-600 rounded-lg">
                            <ArrowRightLeft className="w-5 h-5" />
                        </div>
                        <h3 className="font-medium text-slate-900">Pending Approvals</h3>
                    </div>
                    <p className="text-2xl font-bold mt-2 text-slate-900">0</p>
                </div>
                
                <div className="bg-white border rounded-xl p-6 shadow-sm">
                    <div className="flex items-center gap-3 mb-2">
                        <div className="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
                            <PackageCheck className="w-5 h-5" />
                        </div>
                        <h3 className="font-medium text-slate-900">Completed Today</h3>
                    </div>
                    <p className="text-2xl font-bold mt-2 text-slate-900">0</p>
                </div>
            </div>

            <div className="bg-white border rounded-xl p-6 shadow-sm">
                <h3 className="font-medium text-lg mb-4">Recent Transfer Logs</h3>
                <div className="text-sm text-slate-500 text-center py-8">
                    No transfers found.
                </div>
            </div>
        </ERPLayout>
    );
}

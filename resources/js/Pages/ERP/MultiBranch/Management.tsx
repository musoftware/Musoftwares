import React from 'react';
import ERPLayout from '@/Layouts/ERPLayout';
import { Head } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Plus } from 'lucide-react';
import { useERPMenu } from '@/hooks/useERPMenu';

interface Props {
    branches: Array<any>;
}

export default function BranchManagement({ branches }: Props) {
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('branches');

    return (
        <ERPLayout 
            title="Branch Management"
            menuItems={menuItems}
            lockedAddons={lockedAddons}
            workspaceName={workspaceName}
            tenantId={tenantId}
        >
            <Head title="Branch Management - ERP" />

            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                <div>
                    <h1 className="text-2xl font-semibold text-slate-900 tracking-tight">Branch Management</h1>
                    <p className="text-sm text-slate-500 mt-1">Configure locations, operating hours, and assign managers.</p>
                </div>

                <Button size="sm">
                    <Plus className="w-4 h-4 mr-2" />
                    Add Branch
                </Button>
            </div>

            <div className="bg-white border rounded-xl overflow-hidden shadow-sm">
                <table className="w-full text-sm text-left">
                    <thead className="bg-slate-50 border-b">
                        <tr>
                            <th className="px-6 py-3 font-medium text-slate-500">Branch Name</th>
                            <th className="px-6 py-3 font-medium text-slate-500">Type</th>
                            <th className="px-6 py-3 font-medium text-slate-500">Timezone</th>
                            <th className="px-6 py-3 font-medium text-slate-500">Status</th>
                            <th className="px-6 py-3 font-medium text-slate-500 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {branches.length === 0 ? (
                            <tr>
                                <td colSpan={5} className="px-6 py-8 text-center text-slate-500">
                                    No branches configured yet.
                                </td>
                            </tr>
                        ) : (
                            branches.map((branch) => (
                                <tr key={branch.id} className="hover:bg-slate-50">
                                    <td className="px-6 py-4 font-medium text-slate-900">{branch.name}</td>
                                    <td className="px-6 py-4 text-slate-500 capitalize">{branch.type}</td>
                                    <td className="px-6 py-4 text-slate-500">{branch.timezone}</td>
                                    <td className="px-6 py-4">
                                        <span className="px-2.5 py-1 text-xs font-medium rounded-full bg-emerald-100 text-emerald-800">
                                            {branch.status}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-right">
                                        <button className="text-blue-600 hover:text-blue-800 font-medium text-sm">Edit Settings</button>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>
        </ERPLayout>
    );
}

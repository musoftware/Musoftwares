import React from 'react';
import ERPLayout from '@/Layouts/ERPLayout';
import { Head } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Plus } from 'lucide-react';
import { useERPMenu } from '@/hooks/useERPMenu';
import { __ } from '@/lib/i18n';

interface Props {
    branches: Array<any>;
}

export default function BranchManagement({ branches }: Props) {
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('branches');

    return (
        <ERPLayout 
            title={__('general.branch_management')}
            menuItems={menuItems}
            lockedAddons={lockedAddons}
            workspaceName={workspaceName}
            tenantId={tenantId}
        >
            <Head title={__('general.branch_management_erp')} />

            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                <div>
                    <h1 className="text-2xl font-semibold text-slate-900 tracking-tight">{__('general.branch_management')}</h1>
                    <p className="text-sm text-slate-500 mt-1">{__('general.configure_locations_operating_hours_and_assign_managers')}</p>
                </div>

                <Button size="sm">
                    <Plus className="w-4 h-4 me-2" />{__('general.add_branch')}</Button>
            </div>

            <div className="bg-white border rounded-xl overflow-hidden shadow-sm">
                <table className="w-full text-sm text-start">
                    <thead className="bg-slate-50 border-b">
                        <tr>
                            <th className="px-6 py-3 font-medium text-slate-500">{__('general.branch_name')}</th>
                            <th className="px-6 py-3 font-medium text-slate-500">{__('general.type')}</th>
                            <th className="px-6 py-3 font-medium text-slate-500">{__('general.timezone')}</th>
                            <th className="px-6 py-3 font-medium text-slate-500">{__('general.status')}</th>
                            <th className="px-6 py-3 font-medium text-slate-500 text-end">{__('general.actions')}</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {branches.length === 0 ? (
                            <tr>
                                <td colSpan={5} className="px-6 py-8 text-center text-slate-500">{__('general.no_branches_configured_yet')}</td>
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
                                    <td className="px-6 py-4 text-end">
                                        <button className="text-blue-600 hover:text-blue-800 font-medium text-sm">{__('general.edit_settings')}</button>
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

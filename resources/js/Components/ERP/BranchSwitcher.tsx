import React from 'react';
import Dropdown from '@/Components/Dropdown';
import { Store, Building2, Warehouse } from 'lucide-react';
import { usePage, router } from '@inertiajs/react';

interface Branch {
    id: number;
    name: string;
    type: string;
}

interface BranchSwitcherProps {
    branches: Branch[];
    activeBranchId?: number | null;
}

export function BranchSwitcher({ branches, activeBranchId }: BranchSwitcherProps) {
    const activeBranch = branches.find((b) => b.id === activeBranchId);

    const handleSwitch = (branchId: number | null) => {
        router.post(route('erp.branches.switch'), { branch_id: branchId }, {
            preserveState: false,
            preserveScroll: true
        });
    };

    const getIcon = (type: string) => {
        switch (type) {
            case 'clinic':
                return <Building2 className="w-4 h-4 mr-2" />;
            case 'warehouse':
                return <Warehouse className="w-4 h-4 mr-2" />;
            default:
                return <Store className="w-4 h-4 mr-2" />;
        }
    };

    return (
        <Dropdown>
            <Dropdown.Trigger>
                <button className="flex items-center space-x-2 px-3 py-2 text-sm font-medium border rounded-md hover:bg-slate-50 transition-colors">
                    {activeBranch ? (
                        <>
                            {getIcon(activeBranch.type)}
                            <span>{activeBranch.name}</span>
                        </>
                    ) : (
                        <>
                            <Building2 className="w-4 h-4 mr-2 text-slate-500" />
                            <span>All Branches (Global)</span>
                        </>
                    )}
                </button>
            </Dropdown.Trigger>

            <Dropdown.Content align="left" width="48">
                <button className="block w-full px-4 py-2 text-left text-sm leading-5 text-slate-700 hover:bg-slate-100 focus:outline-none" onClick={() => handleSwitch(null)}>
                    <div className="flex items-center">
                        <Building2 className="w-4 h-4 mr-2" />
                        All Branches (Global)
                    </div>
                </button>
                
                <div className="border-t border-slate-100 my-1"></div>

                {branches.map((branch) => (
                    <button key={branch.id} className="block w-full px-4 py-2 text-left text-sm leading-5 text-slate-700 hover:bg-slate-100 focus:outline-none" onClick={() => handleSwitch(branch.id)}>
                        <div className="flex items-center">
                            {getIcon(branch.type)}
                            <span className={activeBranchId === branch.id ? 'font-bold' : ''}>
                                {branch.name}
                            </span>
                        </div>
                    </button>
                ))}
            </Dropdown.Content>
        </Dropdown>
    );
}

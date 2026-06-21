import React from 'react';
import CrmLayout from '@/Layouts/CrmLayout';
import { PhoneCall, Users, Database, ArrowRight, Briefcase } from 'lucide-react';
import { Link, usePage } from '@inertiajs/react';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { UpgradeOverlay } from '@/Components/ui/UpgradeOverlay';
import { __ } from '@/lib/i18n';

interface ActionCenter {
    id: string;
    title: string;
    description: string;
    icon: React.ElementType;
    href: string;
    colorClass: string;
    bgClass: string;
}

export default function WorkspaceIndex({ availableCenters }: { availableCenters: string[] }) {
    const { auth } = usePage().props;
    const hasAdvancedOps = (auth as any)?.crm_features?.includes('crm-advanced-operations') ?? false;
    
    const allCenters: Record<string, ActionCenter> = {
        telesales: {
            id: 'telesales',
            title: __('general.telesales_workspace'),
            description: __('general.your_daily_action_center_make_calls_manage_your_pipeline_and_handle_follow_ups'),
            icon: PhoneCall,
            href: route('crm.workspaces.telesales'),
            colorClass: 'text-indigo-600',
            bgClass: 'bg-indigo-100',
        },
        manager: {
            id: 'manager',
            title: __('general.manager_workspace'),
            description: __('general.oversee_branch_performance_monitor_slas_and_track_agent_conversion_metrics'),
            icon: Users,
            href: route('crm.workspaces.manager'),
            colorClass: 'text-rose-600',
            bgClass: 'bg-rose-100',
        },
        collector: {
            id: 'collector',
            title: __('general.collector_workspace'),
            description: __('general.manage_incoming_data_import_bulk_lead_lists_and_handle_duplicate_prevention'),
            icon: Database,
            href: route('crm.workspaces.collector'),
            colorClass: 'text-emerald-600',
            bgClass: 'bg-emerald-100',
        }
    };

    if (!hasAdvancedOps) {
        return (
            <CrmLayout title={__('general.workspaces_hub')} activeMenu="workspaces">
                <ModulePageHeader 
                    title={__('general.workspaces')}
                    description={__('general.dedicated_hubs_for_telesales_managers_and_data_collectors')}
                    icon={Briefcase}
                    
                />
                <div className="px-8 pb-8">
                    <UpgradeOverlay 
                        title={__('general.advanced_operations_required')}
                        description={__('general.to_use_dedicated_role_based_workspaces_manager_telesales_collector_hubs_you_need_the_advanced_operations_add_on')}
                        icon={Briefcase}
                        module="crm-advanced-operations"
                        priceText={__('general.subscribe_to_advanced_operations')}
                    />
                </div>
            </CrmLayout>
        );
    }

    // Filter based on what the backend provided
    const visibleCenters = availableCenters ? availableCenters.map(key => allCenters[key]).filter(Boolean) : [];

    return (
        <CrmLayout title={__('general.workspaces_hub')} activeMenu="workspaces">
            <ModulePageHeader 
                title={__('general.select_your_workspace')}
                description={__('general.you_have_access_to_multiple_action_centers_choose_the_workspace_that_matches_your_task_for_today')}
                icon={Briefcase}
                
            />
            <div className="flex flex-col h-full max-w-5xl mx-auto w-full px-8 pb-8">


                {/* Hub Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-4">
                    {visibleCenters.map((center) => (
                        <Link 
                            key={center.id} 
                            href={center.href}
                            className="group flex flex-col bg-white border border-slate-200 rounded-2xl p-6 hover:shadow-lg hover:border-indigo-200 transition-all duration-300"
                        >
                            <div className={`w-12 h-12 rounded-xl flex items-center justify-center mb-6 ${center.bgClass} ${center.colorClass} group-hover:scale-110 transition-transform duration-300`}>
                                <center.icon size={24} strokeWidth={2} />
                            </div>
                            
                            <h2 className="text-xl font-bold text-slate-900 mb-2">{center.title}</h2>
                            <p className="text-sm text-slate-500 leading-relaxed flex-1 mb-6">
                                {center.description}
                            </p>

                            <div className="flex items-center text-sm font-semibold text-indigo-600 group-hover:text-indigo-700 transition-colors">
                                {__('general.enter_workspace')}
                                <ArrowRight size={16} className="ms-1 opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300" />
                            </div>
                        </Link>
                    ))}
                </div>

                {visibleCenters.length === 0 && (
                    <div className="flex flex-col items-center justify-center p-12 bg-slate-50 border border-dashed border-slate-200 rounded-2xl text-center">
                        <div className="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mb-4">
                            <Users size={32} />
                        </div>
                        <h3 className="text-lg font-semibold text-slate-800">{__('general.no_workspaces_available')}</h3>
                        <p className="text-sm text-slate-500 mt-1 max-w-md">
                            {__('general.you_currently_do_not_have_access_to_any_specialized_workspaces_please_contact_your_administrator_to_assign_a_role_to_your_account')}
                        </p>
                    </div>
                )}

            </div>
        </CrmLayout>
    );
}

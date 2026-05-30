import React from 'react';
import CrmLayout from '@/Layouts/CrmLayout';
import { PhoneCall, Users, Database, ArrowRight } from 'lucide-react';
import { Link } from '@inertiajs/react';
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
    
    const allCenters: Record<string, ActionCenter> = {
        telesales: {
            id: 'telesales',
            title: __('Telesales Workspace'),
            description: __('Your daily action center. Make calls, manage your pipeline, and handle follow-ups.'),
            icon: PhoneCall,
            href: route('crm.workspaces.telesales'),
            colorClass: 'text-indigo-600',
            bgClass: 'bg-indigo-100',
        },
        manager: {
            id: 'manager',
            title: __('Manager Workspace'),
            description: __('Oversee branch performance, monitor SLAs, and track agent conversion metrics.'),
            icon: Users,
            href: route('crm.workspaces.manager'),
            colorClass: 'text-rose-600',
            bgClass: 'bg-rose-100',
        },
        collector: {
            id: 'collector',
            title: __('Collector Workspace'),
            description: __('Manage incoming data, import bulk lead lists, and handle duplicate prevention.'),
            icon: Database,
            href: route('crm.workspaces.collector'),
            colorClass: 'text-emerald-600',
            bgClass: 'bg-emerald-100',
        }
    };

    // Filter based on what the backend provided
    const visibleCenters = availableCenters.map(key => allCenters[key]).filter(Boolean);

    return (
        <CrmLayout title={__('Workspaces Hub')} activeMenu="workspaces">
            <div className="flex flex-col h-full max-w-5xl mx-auto w-full gap-8 p-8 pt-12">
                
                {/* Header */}
                <div className="text-center max-w-2xl mx-auto">
                    <h1 className="text-3xl font-bold text-slate-900 tracking-tight">{__('Select Your Workspace')}</h1>
                    <p className="text-base text-slate-500 mt-2">
                        {__('You have access to multiple action centers. Choose the workspace that matches your task for today.')}
                    </p>
                </div>

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
                                {__('Enter Workspace')}
                                <ArrowRight size={16} className="ml-1 opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300" />
                            </div>
                        </Link>
                    ))}
                </div>

                {visibleCenters.length === 0 && (
                    <div className="flex flex-col items-center justify-center p-12 bg-slate-50 border border-dashed border-slate-200 rounded-2xl text-center">
                        <div className="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mb-4">
                            <Users size={32} />
                        </div>
                        <h3 className="text-lg font-semibold text-slate-800">{__('No Workspaces Available')}</h3>
                        <p className="text-sm text-slate-500 mt-1 max-w-md">
                            {__('You currently do not have access to any specialized workspaces. Please contact your administrator to assign a role to your account.')}
                        </p>
                    </div>
                )}

            </div>
        </CrmLayout>
    );
}

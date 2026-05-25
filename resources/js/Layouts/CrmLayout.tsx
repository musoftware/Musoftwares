import React, { PropsWithChildren } from 'react';
import { usePage } from '@inertiajs/react';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { LayoutDashboard, Users, Mail, PlayCircle, Settings } from 'lucide-react';
import { CrmCommandPalette } from '@/Components/CRM/CrmCommandPalette';

interface CrmLayoutProps extends PropsWithChildren {
    title: string;
    activeMenu: string;
}

export default function CrmLayout({ title, activeMenu, children }: CrmLayoutProps) {
    const { crm_features } = usePage().props as any;

    const hasFeature = (featureName: string) => {
        // If the features array includes the feature name or if the key is true
        return crm_features?.includes(featureName) || crm_features?.[featureName] === true;
    };

    const menuItems = [
        {
            id: 'dashboard',
            label: 'Dashboard',
            icon: LayoutDashboard,
            href: route('crm.dashboard'),
            isActive: activeMenu === 'dashboard',
        },
        {
            id: 'leads',
            label: 'Leads',
            icon: Users,
            href: route('crm.leads.index'),
            isActive: activeMenu === 'leads',
        },
    ];

    if (hasFeature('crm.campaigns.whatsapp') || hasFeature('crm.campaigns.email')) {
        menuItems.push({
            id: 'campaigns',
            label: 'Campaigns',
            icon: Mail,
            href: route('crm.campaigns.index'),
            isActive: activeMenu === 'campaigns',
        });
    }

    if (hasFeature('crm.automations')) {
        menuItems.push({
            id: 'sequences',
            label: 'Sequences',
            icon: PlayCircle,
            href: route('crm.sequences.index'),
            isActive: activeMenu === 'sequences',
        });
    }

    menuItems.push({
        id: 'settings',
        label: 'Settings',
        icon: Settings,
        href: '#', // TODO: CRM Settings route
        isActive: activeMenu === 'settings',
    });

    const [cmdOpen, setCmdOpen] = React.useState(false);

    return (
        <WorkspaceLayout
            title={title}
            workspaceName="My CRM Workspace"
            menuItems={menuItems}
        >
            {children}
            <CrmCommandPalette open={cmdOpen} setOpen={setCmdOpen} onOpenLead={() => {}} />
        </WorkspaceLayout>
    );
}

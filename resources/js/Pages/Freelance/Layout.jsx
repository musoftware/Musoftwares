import React, { useEffect } from 'react';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { useFreelanceMode } from '@/Components/Freelance/FreelanceModeContext';
import { usePage, router } from '@inertiajs/react';
import { 
    Briefcase,
    Clock,
    Search,
    Plus,
    Coins
} from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function FreelanceLayout({ children, clean = false }) {
    const freelanceModeContext = useFreelanceMode();
    const mode = freelanceModeContext?.mode || 'client';
    const isClient = mode === 'client';
    
    // Get the current path to match active item
    const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';

    // Automated role-based redirects
    useEffect(() => {
        if (typeof window !== 'undefined') {
            const clientOnlyPrefixes = [
                '/freelance/jobs/create',
                '/freelance/jobs/my-jobs'
            ];
            const freelancerOnlyPrefixes = [
                '/freelance/jobs/browse',
                '/freelance/proposals',
                '/points'
            ];

            const isClientOnlyPath = 
                clientOnlyPrefixes.some(path => currentPath.startsWith(path)) ||
                (currentPath.startsWith('/freelance/jobs/') && currentPath.includes('/edit'));

            const isFreelancerOnlyPath = 
                freelancerOnlyPrefixes.some(path => currentPath.startsWith(path));

            if (!isClient && isClientOnlyPath) {
                router.visit('/freelance/dashboard');
            } else if (isClient && isFreelancerOnlyPath) {
                router.visit('/freelance/dashboard');
            }
        }
    }, [isClient, currentPath]);

    const freelancerMenuItems = [
        { id: 'dashboard',  label: __('Dashboard'),    icon: Briefcase, href: '/freelance/dashboard',              isActive: currentPath === '/freelance/dashboard' || currentPath === '/freelance/dashboard/' },
        { id: 'jobs',       label: __('Find Work'),     icon: Search,    href: '/freelance/jobs/browse',  isActive: currentPath.startsWith('/freelance/jobs/browse') || currentPath.startsWith('/freelance/jobs/') && !currentPath.includes('/my-jobs') && !currentPath.includes('/create') },
        { id: 'proposals',  label: __('My Proposals'),  icon: Clock,     href: '/freelance/proposals',    isActive: currentPath.startsWith('/freelance/proposals') },
        { id: 'contracts',  label: __('My Contracts'),  icon: Clock,     href: '/freelance/contracts',    isActive: currentPath.startsWith('/freelance/contracts') },
        { id: 'points',     label: __('Buy Points'),  icon: Coins,     href: '/points',       isActive: currentPath.startsWith('/points') },
    ];

    const clientMenuItems = [
        { id: 'dashboard',  label: __('Dashboard'),       icon: Briefcase, href: '/freelance/dashboard',              isActive: currentPath === '/freelance/dashboard' || currentPath === '/freelance/dashboard/' },
        { id: 'post-job',   label: __('Post a Job'),      icon: Plus,      href: '/freelance/jobs/create',  isActive: currentPath.startsWith('/freelance/jobs/create') },
        { id: 'my-jobs',    label: __('My Posted Jobs'),  icon: Briefcase, href: '/freelance/jobs/my-jobs', isActive: currentPath.startsWith('/freelance/jobs/my-jobs') },
        { id: 'contracts',  label: __('My Contracts'),    icon: Clock,     href: '/freelance/contracts',    isActive: currentPath.startsWith('/freelance/contracts') },
    ];

    const menuItems = isClient ? clientMenuItems : freelancerMenuItems;

    return (
        <WorkspaceLayout 
            title={__('Freelance Hub')}
            workspaceName={__('Freelance Hub')}
            tenantId="FR-DRAFT"
            menuItems={menuItems}
        >
            {children}
        </WorkspaceLayout>
    );
}

import { usePage } from '@inertiajs/react';
import { PageProps } from '@/types';

export function usePermissions() {
    const { auth } = usePage<PageProps>().props;

    const hasRole = (role: string | string[]) => {
        const userRoles = auth?.user?.roles;
        if (!userRoles || !Array.isArray(userRoles)) return false;
        
        const roleNames = userRoles.map((r: any) => 
            typeof r === 'object' && r !== null ? (r.name || '').toLowerCase() : String(r).toLowerCase()
        );

        if (Array.isArray(role)) {
            return role.some(r => roleNames.includes(r.toLowerCase()));
        }
        
        return roleNames.includes(role.toLowerCase());
    };

    const can = (permission: string | string[]) => {
        const userPermissions = auth?.user?.permissions;
        if (!userPermissions) return false;

        // Super admins can do everything
        if (hasRole('super_admin') || hasRole('admin')) {
            return true;
        }
        
        if (Array.isArray(permission)) {
            return permission.some(p => userPermissions.includes(p.toLowerCase()));
        }
        
        return userPermissions.includes(permission.toLowerCase());
    };

    return { hasRole, can };
}

import { usePage } from '@inertiajs/react';
import { PageProps } from '@/types';

export function usePermissions() {
    const { auth } = usePage<PageProps>().props;

    const hasRole = (role: string | string[]) => {
        if (!auth?.user?.roles) return false;
        
        if (Array.isArray(role)) {
            return role.some(r => auth.user.roles.includes(r.toLowerCase()));
        }
        
        return auth.user.roles.includes(role.toLowerCase());
    };

    const can = (permission: string | string[]) => {
        if (!auth?.user?.permissions) return false;

        // Super admins can do everything
        if (hasRole('super_admin') || hasRole('admin')) {
            return true;
        }
        
        if (Array.isArray(permission)) {
            return permission.some(p => auth.user.permissions.includes(p.toLowerCase()));
        }
        
        return auth.user.permissions.includes(permission.toLowerCase());
    };

    return { hasRole, can };
}

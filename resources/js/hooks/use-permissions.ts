import { hasPermission, hasRole } from '../lib/permissions';
import { useAuth } from './use-auth';

export function usePermissions() {
    const { user } = useAuth();
    const permissions = user?.permissions || [];
    const roles = user?.roles || [];

    return {
        can: (permission: string) => hasPermission(permissions, permission),
        is: (role: string) => hasRole(roles, role),
    };
}

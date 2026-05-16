import { usePage } from '@inertiajs/react';

export function useAuth() {
    const { auth } = usePage().props as unknown as {
        auth: {
            user: { name: string; permissions: string[]; roles: string[] };
        };
    };
    return {
        user: auth?.user,
        isAuthenticated: !!auth?.user,
    };
}

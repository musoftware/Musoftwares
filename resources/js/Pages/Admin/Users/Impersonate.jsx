import React, { useEffect } from 'react';
import { Head, router } from '@inertiajs/react';

export default function Impersonate({ user, token }) {
    useEffect(() => {
        // Here you would typically store the token in local storage or a cookie
        // localStorage.setItem('impersonation_token', token);
        
        // Then redirect to the frontend or dashboard
        // window.location.href = '/dashboard';
    }, [token]);

    return (
        <div className="flex min-h-screen items-center justify-center bg-gray-50">
            <Head title="Impersonating User" />
            <div className="text-center">
                <h1 className="text-2xl font-bold text-gray-900 mb-4">
                    Impersonating {user.name}
                </h1>
                <p className="text-gray-500 mb-6">
                    Setting up impersonation session...
                </p>
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 mx-auto"></div>
            </div>
        </div>
    );
}

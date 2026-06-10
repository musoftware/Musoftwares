import React, { PropsWithChildren } from 'react';
import { usePage } from '@inertiajs/react';
import PublicNavbar from '@/Components/Freelance/PublicNavbar';
import PublicFooter from '@/Components/Freelance/PublicFooter';

export default function PublicLayout({ children }: PropsWithChildren) {
    const { auth } = usePage().props as any;
    const user = auth?.user;

    return (
        <div className="min-h-screen bg-slate-50 flex flex-col font-sans text-slate-900">
            <PublicNavbar canLogin={!user} canRegister={!user} />
            <main className="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 lg:py-32">
                {children}
            </main>
            <PublicFooter />
        </div>
    );
}

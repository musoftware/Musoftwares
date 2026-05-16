import React from 'react';
import AppLayout from './app-layout';

export default function ClientLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    return <AppLayout>{children}</AppLayout>;
}

import React from 'react';
import AppLayout from './app-layout';

export default function AdminLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    // Admin specific wrappers or contexts can go here
    return <AppLayout>{children}</AppLayout>;
}

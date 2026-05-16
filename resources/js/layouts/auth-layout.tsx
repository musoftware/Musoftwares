import React from 'react';
import { ThemeProvider } from '../components/shared/ThemeProvider';

export default function AuthLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    return (
        <ThemeProvider attribute="class" defaultTheme="system" enableSystem>
            <div className="flex min-h-screen items-center justify-center bg-muted/30 p-4">
                <div className="w-full max-w-md rounded-xl border border-border bg-card p-8 shadow-lg">
                    {children}
                </div>
            </div>
        </ThemeProvider>
    );
}

import React from 'react';
import { ThemeProvider } from '../components/shared/ThemeProvider';

export default function PublicLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    return (
        <ThemeProvider attribute="class" defaultTheme="system" enableSystem>
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                {/* Placeholder for public header */}
                <main className="mx-auto w-full max-w-7xl flex-1 p-4 md:p-6 lg:p-8">
                    {children}
                </main>
                {/* Placeholder for public footer */}
            </div>
        </ThemeProvider>
    );
}

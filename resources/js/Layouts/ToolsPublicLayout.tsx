import React, { PropsWithChildren } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { Button } from '@/Components/ui/button';
import { Download, Star, Activity, ShoppingBag, LogIn, UserPlus } from 'lucide-react';

interface ToolsPublicLayoutProps extends PropsWithChildren {
    title: string;
    activeNav?: 'explore' | 'downloads' | 'licenses' | 'billing';
}

export default function ToolsPublicLayout({
    children,
    title,
    activeNav = 'explore',
}: ToolsPublicLayoutProps) {
    const { auth } = usePage().props as any;
    const isAuthed = !!auth?.user;

    const navItems = [
        { id: 'explore',   label: 'Browse Tools',  icon: ShoppingBag, href: route('tools.explore'),    public: true },
        { id: 'downloads', label: 'Downloads',      icon: Download,    href: route('tools.downloads'),  public: false },
        { id: 'licenses',  label: 'My Licenses',    icon: Star,        href: route('tools.my-licenses'),public: false },
        { id: 'billing',   label: 'Billing',        icon: Activity,    href: route('tools.billing'),    public: false },
    ];

    const visibleItems = navItems.filter(item => item.public || isAuthed);

    return (
        <div className="min-h-screen bg-slate-50 font-sans text-slate-900 flex flex-col">
            {/* Topbar */}
            <header className="sticky top-0 z-40 w-full bg-white border-b border-slate-200">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex h-14 items-center justify-between gap-4">
                        {/* Logo */}
                        <Link href="/" className="flex items-center gap-2 shrink-0">
                            <div className="w-8 h-8 rounded-lg bg-slate-900 flex items-center justify-center">
                                <ApplicationLogo className="w-5 h-5 text-white fill-current" />
                            </div>
                            <span className="font-semibold text-base tracking-tight hidden sm:block">musoftware</span>
                        </Link>

                        {/* Sub-navigation tabs */}
                        <nav className="flex items-center gap-0.5 overflow-x-auto hide-scrollbar flex-1 max-w-xl">
                            {visibleItems.map(item => {
                                const Icon = item.icon;
                                const isActive = activeNav === item.id;
                                return (
                                    <Link
                                        key={item.id}
                                        href={item.href}
                                        className={`flex items-center gap-1.5 px-3 py-2 rounded-md text-sm font-medium whitespace-nowrap transition-colors ${
                                            isActive
                                                ? 'bg-slate-100 text-slate-900'
                                                : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'
                                        }`}
                                    >
                                        <Icon className="h-4 w-4 shrink-0" />
                                        {item.label}
                                    </Link>
                                );
                            })}
                        </nav>

                        {/* Auth CTAs */}
                        <div className="flex items-center gap-2 shrink-0">
                            {isAuthed ? (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => router.visit(route('dashboard'))}
                                    className="text-slate-600 text-sm"
                                >
                                    Dashboard
                                </Button>
                            ) : (
                                <>
                                    <Link href={route('login')}>
                                        <Button variant="ghost" size="sm" className="gap-1.5 text-slate-600">
                                            <LogIn className="h-4 w-4" />
                                            <span className="hidden sm:inline">Log in</span>
                                        </Button>
                                    </Link>
                                    <Link href={route('register')}>
                                        <Button size="sm" className="gap-1.5 bg-slate-900 hover:bg-slate-800 text-white">
                                            <UserPlus className="h-4 w-4" />
                                            <span className="hidden sm:inline">Register</span>
                                        </Button>
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </header>

            {/* Banner for guests */}
            {!isAuthed && (
                <div className="bg-gradient-to-r from-fuchsia-600 to-indigo-600 text-white text-center py-2 px-4 text-sm font-medium">
                    🎉 Browse freely — <Link href={route('register')} className="underline underline-offset-2 hover:opacity-90">create a free account</Link> to subscribe and download tools.
                </div>
            )}

            {/* Main content */}
            <main className="flex-1 w-full">
                {children}
            </main>

            {/* Simple footer */}
            <footer className="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-400">
                © {new Date().getFullYear()} musoftware — Tools & Plugins Marketplace
            </footer>
        </div>
    );
}

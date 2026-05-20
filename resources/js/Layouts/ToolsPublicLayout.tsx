import React, { PropsWithChildren } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { Button } from '@/Components/ui/button';
import { RuntimeStatusBanner } from '@/Components/Tools/RuntimeStatusBanner';
import { Download, CreditCard, LayoutGrid, LogIn, UserPlus, ArrowUpRight } from 'lucide-react';

interface ToolsPublicLayoutProps extends PropsWithChildren {
    title: string;
    activeNav?: 'explore' | 'downloads' | 'billing';
    /** Tool slug for plugin install tracking */
    toolSlug?: string;
}

export default function ToolsPublicLayout({
    children,
    title,
    activeNav = 'explore',
    toolSlug,
}: ToolsPublicLayoutProps) {
    const { auth } = usePage().props as any;
    const isAuthed = !!auth?.user;

    const navItems = [
        { id: 'explore',   label: 'Browse',      icon: LayoutGrid,   href: route('tools.explore'),   public: true  },
        { id: 'downloads', label: 'Downloads',    icon: Download,     href: route('tools.downloads'), public: false },
        { id: 'billing',   label: 'Billing',      icon: CreditCard,   href: route('tools.billing'),   public: false },
    ];

    const visibleItems = navItems.filter(item => item.public || isAuthed);

    return (
        <div className="min-h-screen bg-[#FAFAFA] font-['Inter',sans-serif] text-slate-900 flex flex-col">
            <Head title={`${title} — musoftware Tools`} />

            {/* Runtime status banner (authenticated users only) */}
            {isAuthed && (
                <RuntimeStatusBanner toolSlug={toolSlug} />
            )}

            {/* Announcement bar (guests only) */}
            {!isAuthed && (
                <div className="bg-slate-900 text-slate-300 text-center py-2 px-4 text-xs font-medium flex items-center justify-center gap-2">
                    <span className="text-slate-400">🚀</span>
                    <span>Professional desktop tools for power users.</span>
                    <Link
                        href={route('register')}
                        className="inline-flex items-center gap-0.5 text-white font-semibold hover:opacity-80 transition-opacity ml-1"
                    >
                        Get started free <ArrowUpRight className="h-3 w-3" />
                    </Link>
                </div>
            )}

            {/* Header */}
            <header className="sticky top-0 z-40 w-full bg-white/95 backdrop-blur-sm border-b border-slate-200/80">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex h-13 items-center gap-6">
                        {/* Logo */}
                        <Link href="/" className="flex items-center gap-2.5 shrink-0 group">
                            <div className="w-7 h-7 rounded-lg bg-slate-900 flex items-center justify-center group-hover:bg-slate-700 transition-colors">
                                <ApplicationLogo className="w-4 h-4 text-white fill-current" />
                            </div>
                            <span className="font-semibold text-[13px] tracking-tight text-slate-800 hidden sm:block">
                                musoftware
                            </span>
                            <span className="hidden sm:block text-slate-300 text-xs font-light">/</span>
                            <span className="hidden sm:block text-[13px] font-medium text-slate-500">Tools</span>
                        </Link>

                        {/* Nav tabs */}
                        <nav className="flex items-center gap-0.5 flex-1 overflow-x-auto hide-scrollbar">
                            {visibleItems.map(item => {
                                const Icon = item.icon;
                                const isActive = activeNav === item.id;
                                return (
                                    <Link
                                        key={item.id}
                                        href={item.href}
                                        className={`flex items-center gap-1.5 px-3 py-2 rounded-md text-[13px] font-medium whitespace-nowrap transition-colors ${
                                            isActive
                                                ? 'bg-slate-900 text-white'
                                                : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'
                                        }`}
                                    >
                                        <Icon className="h-3.5 w-3.5 shrink-0" />
                                        {item.label}
                                    </Link>
                                );
                            })}
                        </nav>

                        {/* Auth actions */}
                        <div className="flex items-center gap-2 shrink-0">
                            {isAuthed ? (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => router.visit(route('dashboard'))}
                                    className="text-[13px] text-slate-500 hover:text-slate-800 h-8"
                                >
                                    Dashboard
                                </Button>
                            ) : (
                                <>
                                    <Link href={route('login')}>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="gap-1.5 text-[13px] text-slate-500 h-8 hover:text-slate-800"
                                        >
                                            <LogIn className="h-3.5 w-3.5" />
                                            <span className="hidden sm:inline">Sign in</span>
                                        </Button>
                                    </Link>
                                    <Link href={route('register')}>
                                        <Button
                                            size="sm"
                                            className="gap-1.5 text-[13px] bg-slate-900 hover:bg-slate-800 text-white h-8 px-3.5"
                                        >
                                            <UserPlus className="h-3.5 w-3.5" />
                                            <span className="hidden sm:inline">Register</span>
                                        </Button>
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </header>

            {/* Main */}
            <main className="flex-1 w-full">
                {children}
            </main>

            {/* Footer */}
            <footer className="border-t border-slate-200 bg-white mt-16">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div className="flex items-center gap-2">
                            <div className="w-5 h-5 rounded-md bg-slate-900 flex items-center justify-center">
                                <ApplicationLogo className="w-3 h-3 text-white fill-current" />
                            </div>
                            <span className="text-sm font-medium text-slate-700">musoftware Tools</span>
                        </div>
                        <div className="flex items-center gap-6 text-xs text-slate-400">
                            <span>© {new Date().getFullYear()} musoftware</span>
                            <Link href="/" className="hover:text-slate-600 transition-colors">Home</Link>
                            <Link href={route('tools.explore')} className="hover:text-slate-600 transition-colors">Marketplace</Link>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    );
}

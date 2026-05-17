import ApplicationLogo from '@/Components/ApplicationLogo';
import CommandPalette from '@/Components/CommandPalette';
import { Toaster } from '@/Components/ui/toaster';
import { useToast } from '@/Components/ui/use-toast';
import { Link, usePage } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Sheet, SheetContent, SheetTrigger } from '@/Components/ui/sheet';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import {
    LayoutDashboard,
    Users,
    UserCheck,
    Contact,
    Bookmark,
    Coins,
    FileText,
    Wallet,
    History,
    ArrowUpRight,
    Gift,
    ShoppingCart,
    Globe,
    ShoppingBag,
    Briefcase,
    Heart,
    Search,
    FileSpreadsheet,
    FileSignature,
    MessageSquare,
    Mail,
    LifeBuoy,
    Bell,
    Calendar,
    Activity,
    Settings,
    User,
    Building2,
    Clock,
    CreditCard,
    Lock,
    Sliders,
    ChevronLeft,
    ChevronRight,
    Menu,
    LogOut,
    Plus,
    Keyboard,
    ShieldCheck,
    Moon,
    Sparkles,
    CheckCircle2
} from 'lucide-react';
import { PropsWithChildren, ReactNode, useEffect, useState } from 'react';

export default function Authenticated({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const { auth, notifications, flash } = usePage().props as any;
    const user = auth.user;
    const isImpersonating = auth.is_impersonating;
    const { toast } = useToast();

    const [isCollapsed, setIsCollapsed] = useState(false);
    const [isMobileOpen, setIsMobileOpen] = useState(false);
    const [isErpSubscribed, setIsErpSubscribed] = useState(false);

    useEffect(() => {
        const saved = sessionStorage.getItem('is_subscribed_erp');
        if (saved === 'true') {
            setIsErpSubscribed(true);
        } else if (user?.is_subscribed_erp) {
            setIsErpSubscribed(true);
        }
    }, [user]);

    const toggleErpSubscriptionSimulate = () => {
        const nextState = !isErpSubscribed;
        sessionStorage.setItem('is_subscribed_erp', String(nextState));
        setIsErpSubscribed(nextState);
        toast({
            title: nextState ? "ERP Capability Active!" : "ERP Workspace Locked",
            description: nextState 
                ? "Simulated billing verified. All ERP sidebar routes are now unlocked." 
                : "Simulated subscription terminated. Gated routes show lock features.",
        });
    };

    useEffect(() => {
        if (flash?.message) {
            toast({
                description: flash.message,
            });
        }
    }, [flash]);

    // Safety checks for route existence
    const safeRoute = (name: string, params?: any) => {
        try {
            // @ts-ignore
            if (typeof route !== 'undefined' && route().has(name)) {
                // @ts-ignore
                return route(name, params);
            }
        } catch (e) {
            console.warn(`Route ${name} not found, falling back to #`);
        }
        return '#';
    };

    const isRouteActive = (name: string) => {
        try {
            // @ts-ignore
            if (typeof route !== 'undefined') {
                // @ts-ignore
                return route().current(name) || route().current(`${name}.*`);
            }
        } catch (e) {}
        return false;
    };

    // Keyboard shortcut to trigger CommandPalette click
    const triggerSearch = () => {
        const event = new KeyboardEvent('keydown', {
            key: 'k',
            metaKey: true,
            bubbles: true
        });
        document.dispatchEvent(event);
    };

    interface NavigationItem {
        name: string;
        icon: any;
        path: string;
        active: boolean;
        badge?: string;
        isLocked?: boolean;
    }

    interface NavigationGroup {
        label: string;
        items: NavigationItem[];
    }

    // Sidebar navigation groups exactly matching requested structures
    const navigationGroups: NavigationGroup[] = [
        {
            label: 'Operational Home',
            items: [
                { name: 'Dashboard', icon: LayoutDashboard, path: safeRoute('dashboard'), active: isRouteActive('dashboard') }
            ]
        },
        {
            label: 'Financial',
            items: [
                { name: 'Invoices', icon: FileText, path: safeRoute('erp.invoices.index'), active: isRouteActive('erp.invoices.index'), badge: '3' },
                { name: 'Wallet', icon: Wallet, path: safeRoute('erp.wallet.show', user?.id || 1), active: isRouteActive('erp.wallet.show') },
                { name: 'Withdrawals', icon: ArrowUpRight, path: safeRoute('erp.withdrawals.index'), active: isRouteActive('erp.withdrawals.index') }
            ]
        },
        {
            label: 'Business OS (ERP)',
            items: [
                { name: 'My Clients', icon: Users, path: isErpSubscribed ? safeRoute('erp.clients.index') : '/erp/preview', active: isRouteActive('erp.clients.index'), isLocked: !isErpSubscribed },
                { name: 'Time Tracking', icon: Clock, path: isErpSubscribed ? safeRoute('erp.timer.index') : '/erp/preview', active: isRouteActive('erp.timer.index'), isLocked: !isErpSubscribed }
            ]
        },
        {
            label: 'Marketplace',
            items: [
                { name: 'Orders', icon: ShoppingBag, path: safeRoute('marketplace.orders.index'), active: isRouteActive('marketplace.orders.index'), badge: '2' },
                { name: 'Services', icon: Briefcase, path: safeRoute('marketplace.dashboard'), active: isRouteActive('marketplace.dashboard') }
            ]
        },
        {
            label: 'Freelance',
            items: [
                { name: 'Jobs', icon: Search, path: safeRoute('freelance.jobs.browse'), active: isRouteActive('freelance.jobs.browse') },
                { name: 'Contracts', icon: FileSignature, path: safeRoute('freelance.dashboard'), active: isRouteActive('freelance.dashboard'), badge: '2' }
            ]
        },
        {
            label: 'Communication',
            items: [
                { name: 'Messages', icon: Mail, path: safeRoute('notifications.index'), active: false, badge: 'New' },
                { name: 'Support', icon: LifeBuoy, path: safeRoute('notifications.index'), active: false }
            ]
        },
        {
            label: 'Settings',
            items: [
                { name: 'Profile Settings', icon: User, path: safeRoute('profile.edit'), active: isRouteActive('profile.edit') }
            ]
        }
    ];

    const renderNavItems = (mobile = false) => (
        <TooltipProvider delayDuration={0}>
            <div className="space-y-6">
                {navigationGroups.map((group, index) => (
                    <div key={index} className="space-y-1">
                        {!isCollapsed || mobile ? (
                            <h4 className="px-3 text-[10px] font-bold uppercase tracking-wider text-text-muted/70">
                                {group.label}
                            </h4>
                        ) : (
                            <div className="mx-auto h-[1px] w-6 bg-border/40 my-3" />
                        )}
                        <div className="space-y-0.5">
                            {group.items.map((item) => {
                                const ItemContent = (
                                    <Link
                                        href={item.path}
                                        onClick={() => mobile && setIsMobileOpen(false)}
                                        className={`flex items-center rounded-lg py-2 text-[13px] font-medium transition-all duration-150 relative ${
                                            item.active
                                                ? 'bg-slate-900 text-white shadow-sm font-semibold'
                                                : 'text-text-secondary hover:bg-slate-100 hover:text-text-primary'
                                        } ${isCollapsed && !mobile ? 'justify-center px-0' : 'px-3'}`}
                                    >
                                        <item.icon className={`h-4.5 w-4.5 shrink-0 ${isCollapsed && !mobile ? '' : 'mr-3'} ${item.isLocked ? 'text-text-muted/65' : ''}`} />
                                        {(!isCollapsed || mobile) && (
                                            <span className={`whitespace-nowrap flex-1 truncate ${item.isLocked ? 'text-text-muted/70 font-normal' : ''}`}>{item.name}</span>
                                        )}
                                        {(!isCollapsed || mobile) && item.isLocked && (
                                            <Lock className="h-3 w-3 text-text-muted/60 ml-auto shrink-0 animate-pulse" />
                                        )}
                                        {(!isCollapsed || mobile) && item.badge && !item.isLocked && (
                                            <span className={`text-[10px] font-bold px-1.5 py-0.5 rounded-full ${
                                                item.active 
                                                    ? 'bg-indigo-500 text-white' 
                                                    : item.badge === 'New' 
                                                        ? 'bg-indigo-50 text-indigo-600 animate-pulse'
                                                        : 'bg-slate-200 text-slate-700'
                                            }`}>
                                                {item.badge}
                                            </span>
                                        )}
                                        {/* Active bar */}
                                        {item.active && isCollapsed && !mobile && (
                                            <div className="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 bg-indigo-600 rounded-r-md" />
                                        )}
                                    </Link>
                                );

                                if (isCollapsed && !mobile) {
                                    return (
                                        <Tooltip key={item.name}>
                                            <TooltipTrigger asChild>
                                                {ItemContent}
                                            </TooltipTrigger>
                                            <TooltipContent side="right" className="font-sans font-medium text-xs">
                                                {item.name} {item.badge ? `(${item.badge})` : ''}
                                            </TooltipContent>
                                        </Tooltip>
                                    );
                                }

                                return <div key={item.name}>{ItemContent}</div>;
                            })}
                        </div>
                    </div>
                ))}
            </div>
        </TooltipProvider>
    );

    return (
        <div className="min-h-screen bg-[#f8f9fc] text-text-primary flex font-sans">
            {/* Impersonating Alert Banner */}
            {isImpersonating && (
                <div className="fixed top-0 left-0 right-0 z-50 bg-amber-500 px-4 py-2 text-center text-xs font-semibold text-white flex items-center justify-center gap-2">
                    <Sparkles className="h-4.5 w-4.5 animate-pulse" />
                    <span>Viewing workspace as <strong>{user.name}</strong></span>
                    <Link
                        href={safeRoute('stop-impersonating')}
                        method="post"
                        as="button"
                        className="ml-3 rounded bg-amber-600 px-2 py-0.5 text-[10px] font-bold hover:bg-amber-700 transition"
                    >
                        Return to Admin
                    </Link>
                </div>
            )}

            {/* Desktop Sidebar */}
            <aside
                className={`fixed top-0 left-0 z-40 hidden h-screen flex-col border-r border-border/60 bg-white shadow-sm transition-all duration-200 md:flex ${
                    isCollapsed ? 'w-16' : 'w-[260px]'
                } ${isImpersonating ? 'pt-8' : ''}`}
            >
                {/* Brand Header */}
                <div className="flex h-15 flex-shrink-0 items-center justify-between border-b border-border/50 px-4">
                    <Link href="/" className="flex items-center gap-2.5 overflow-hidden">
                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-900 text-white shrink-0">
                            <ApplicationLogo className="h-5 w-5 fill-current text-white" />
                        </div>
                        {!isCollapsed && (
                            <span className="font-sora text-sm font-bold tracking-tight text-slate-900 whitespace-nowrap">
                                unified.saas
                            </span>
                        )}
                    </Link>
                    {!isCollapsed && (
                        <div className="flex items-center gap-1">
                            <span className="bg-emerald-50 text-emerald-700 border-emerald-200 text-[10px] font-bold px-2 py-0.5 border rounded-full flex items-center gap-1">
                                <CheckCircle2 className="h-3 w-3 text-emerald-600" />
                                LIVE
                            </span>
                        </div>
                    )}
                </div>

                {/* Navigation scrollable */}
                <nav className="flex-1 overflow-x-hidden overflow-y-auto px-2 py-5 scrollbar-thin scrollbar-thumb-gray-200">
                    {renderNavItems()}
                </nav>

                {/* Sidebar Collapse Toggle footer */}
                <div className="border-t border-border/50 p-2 flex justify-end">
                    <Button
                        variant="ghost"
                        size="icon"
                        className="h-8 w-8 text-text-muted hover:bg-slate-100 rounded-lg"
                        onClick={() => setIsCollapsed(!isCollapsed)}
                    >
                        {isCollapsed ? (
                            <ChevronRight className="h-4.5 w-4.5" />
                        ) : (
                            <ChevronLeft className="h-4.5 w-4.5" />
                        )}
                    </Button>
                </div>
            </aside>

            {/* Main Content shell */}
            <div
                className={`flex-1 flex flex-col min-w-0 transition-all duration-200 ${
                    isCollapsed ? 'md:ml-16' : 'md:ml-[260px]'
                } ${isImpersonating ? 'pt-8' : ''}`}
            >
                {/* Sticky Topbar */}
                <header className="sticky top-0 z-30 flex h-15 items-center justify-between border-b border-border/60 bg-white/80 px-4 md:px-6 backdrop-blur-md">
                    {/* Left side breadcrumbs & Drawer button */}
                    <div className="flex items-center gap-3">
                        <Sheet open={isMobileOpen} onOpenChange={setIsMobileOpen}>
                            <SheetTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-8 w-8 text-text-secondary md:hidden"
                                >
                                    <Menu className="h-5 w-5" />
                                </Button>
                            </SheetTrigger>
                            <SheetContent side="left" className="w-[260px] p-0 flex flex-col h-full bg-white">
                                <div className="flex h-15 items-center border-b border-border/50 px-6">
                                    <div className="flex items-center gap-2">
                                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-900 text-white shrink-0">
                                            <ApplicationLogo className="h-5 w-5 fill-current text-white" />
                                        </div>
                                        <span className="font-sora text-sm font-bold text-slate-900">
                                            unified.saas
                                        </span>
                                    </div>
                                </div>
                                <nav className="flex-1 overflow-y-auto px-4 py-5">
                                    {renderNavItems(true)}
                                </nav>
                            </SheetContent>
                        </Sheet>

                        {/* Breadcrumbs / View Titles */}
                        <div className="hidden items-center gap-1.5 text-xs text-text-muted sm:flex">
                            <span className="hover:text-text-secondary cursor-pointer">Workspace</span>
                            <span>/</span>
                            <span className="font-semibold text-text-primary capitalize">
                                {header || 'Operational Home'}
                            </span>
                        </div>
                    </div>

                    {/* Center Search Input Trigger */}
                    <div className="flex-1 max-w-md mx-4 hidden sm:block">
                        <button
                            onClick={triggerSearch}
                            className="w-full flex items-center justify-between border border-border/80 bg-slate-50 hover:bg-slate-100 text-text-muted hover:text-text-secondary rounded-lg px-3 py-1.5 text-left text-xs transition duration-150"
                        >
                            <span className="flex items-center gap-2">
                                <Search className="h-3.5 w-3.5" />
                                Search clients, invoices, contracts...
                            </span>
                            <kbd className="pointer-events-none inline-flex h-5 select-none items-center gap-0.5 rounded border border-border/80 bg-white px-1.5 font-mono text-[10px] font-bold text-text-muted">
                                <span>⌘</span>K
                            </kbd>
                        </button>
                    </div>

                    {/* Right side widgets deck */}
                    <div className="flex items-center gap-3">
                        {/* Wallet quick balance */}
                        {/* Interactive ERP Gating Toggle */}
                        <button
                            onClick={toggleErpSubscriptionSimulate}
                            className={`hidden items-center rounded-full border px-3 py-1 text-xs font-semibold sm:flex transition cursor-pointer gap-1.5 ${
                                isErpSubscribed 
                                    ? 'bg-indigo-50 border-indigo-200 text-indigo-700 hover:bg-indigo-100' 
                                    : 'bg-slate-100 border-slate-200 text-slate-600 hover:bg-slate-200'
                            }`}
                        >
                            <Sparkles className="h-3.5 w-3.5 text-indigo-600 animate-pulse" />
                            {isErpSubscribed ? 'ERP Active' : 'ERP Locked (Sandbox)'}
                        </button>

                        <Link
                            href={safeRoute('erp.wallet.show', user?.id || 1)}
                            className="hidden items-center rounded-full border border-indigo-100 bg-indigo-50/50 hover:bg-indigo-50 px-3 py-1 text-xs font-mono font-bold text-indigo-700 sm:flex transition cursor-pointer"
                        >
                            <Wallet className="mr-1.5 h-3.5 w-3.5" />
                            $1,250.45
                        </Link>

                        {/* Search mobile icon trigger */}
                        <Button
                            variant="ghost"
                            size="icon"
                            className="h-8.5 w-8.5 rounded-lg text-text-secondary sm:hidden"
                            onClick={triggerSearch}
                        >
                            <Search className="h-4.5 w-4.5" />
                        </Button>

                        <DropdownMenu>
                            <DropdownMenuTrigger className="h-8.5 w-8.5 rounded-lg text-text-secondary relative hover:bg-slate-100 flex items-center justify-center">
                                <Bell className="h-4.5 w-4.5" />
                                {notifications?.unread_count > 0 && (
                                    <span className="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-indigo-600 animate-ping"></span>
                                )}
                            </DropdownMenuTrigger>
                            <DropdownMenuContent className="w-64 font-sans text-xs" align="end">
                                <DropdownMenuLabel className="font-semibold text-text-primary px-3 py-2 border-b border-border/50">
                                    Recent Alerts
                                </DropdownMenuLabel>
                                <div className="max-h-48 overflow-y-auto">
                                    {notifications?.recent?.length > 0 ? (
                                        notifications.recent.map((n: any) => (
                                            <DropdownMenuItem key={n.id} className="p-3 border-b border-border/40 last:border-0 hover:bg-slate-50">
                                                <Link
                                                    href={safeRoute('notifications.mark-read', { id: n.id })}
                                                    method="post"
                                                    as="button"
                                                    className="w-full text-left"
                                                >
                                                    <p className="font-medium text-text-primary text-[11px] leading-tight">{n.data?.message || 'New Alert'}</p>
                                                    <span className="text-[10px] text-text-muted mt-1 block">2 hours ago</span>
                                                </Link>
                                            </DropdownMenuItem>
                                        ))
                                    ) : (
                                        <div className="px-4 py-6 text-center text-text-muted">
                                            No unread notifications
                                        </div>
                                    )}
                                </div>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem className="text-center font-semibold text-primary hover:bg-indigo-50">
                                    <Link href={safeRoute('notifications.index')} className="w-full block py-1.5">
                                        View All Alerts
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        {/* Profile menu */}
                        <DropdownMenu>
                            <DropdownMenuTrigger className="relative h-8 w-8 rounded-full border-2 border-slate-200 overflow-hidden hover:border-slate-300 transition">
                                <Avatar className="h-full w-full">
                                    <AvatarFallback className="bg-slate-900 text-white font-sora font-semibold text-[11px]">
                                        {user?.name?.substring(0, 2).toUpperCase() || 'US'}
                                    </AvatarFallback>
                                </Avatar>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent className="w-56 font-sans text-xs" align="end">
                                <DropdownMenuLabel className="font-normal p-3 border-b border-border/50">
                                    <div className="flex flex-col space-y-1">
                                        <p className="text-xs leading-none font-bold text-text-primary">
                                            {user?.name || 'SaaS User'}
                                        </p>
                                        <p className="text-text-muted text-[10px] leading-none truncate mt-0.5">
                                            {user?.email || 'user@example.com'}
                                        </p>
                                    </div>
                                </DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem>
                                    <Link href={safeRoute('profile.edit')} className="w-full block py-0.5">
                                        Profile Settings
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem>
                                    <Link href={safeRoute('erp.wallet.show', user?.id || 1)} className="w-full block py-0.5">
                                        Finances Wallet
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem className="text-rose-600 hover:bg-rose-50 hover:text-rose-700">
                                    <Link
                                        href={safeRoute('logout')}
                                        method="post"
                                        as="button"
                                        className="w-full text-left py-0.5 flex items-center gap-2 font-medium"
                                    >
                                        <LogOut className="h-3.5 w-3.5" />
                                        Log Out
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </header>

                {/* Main Content Area scrollable */}
                <main className="flex-1 overflow-y-auto bg-[#f8f9fc] p-5 md:p-8 lg:p-10 relative">
                    {children}
                </main>
            </div>

            {/* Global keyboard command searchpalette */}
            <CommandPalette />
            <Toaster />
        </div>
    );
}

import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Sheet, SheetContent, SheetTrigger } from '@/Components/ui/sheet';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/Components/ui/tooltip';
import { User } from '@/types';
import { Link } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import {
    Bell,
    Briefcase,
    Building2,
    ChevronLeft,
    ChevronRight,
    LayoutDashboard,
    Menu,
    Moon,
    ShoppingCart,
    Wallet,
} from 'lucide-react';
import { PropsWithChildren, useState } from 'react';

interface ClientLayoutProps extends PropsWithChildren {
    user: User;
    hasErpSubscription?: boolean;
    walletBalance?: number;
}

export default function ClientLayout({
    user,
    hasErpSubscription = false,
    walletBalance = 0,
    children,
}: ClientLayoutProps) {
    const [isCollapsed, setIsCollapsed] = useState(false);
    const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

    const defaultModules = [
        { name: 'Core Dashboard', icon: LayoutDashboard, route: 'dashboard' },
        { name: 'Freelance', icon: Briefcase, route: 'freelance.dashboard' },
        {
            name: 'Marketplace',
            icon: ShoppingCart,
            route: 'marketplace.dashboard',
        },
    ];

    const erpModule = {
        name: 'ERP System',
        icon: Building2,
        route: 'erp.dashboard',
    };

    const modules = hasErpSubscription
        ? [defaultModules[0], erpModule, ...defaultModules.slice(1)]
        : defaultModules;

    return (
        <div className="min-h-screen bg-gray-50">
            {/* Sidebar */}
            {/* Desktop Sidebar */}
            <aside
                className={`fixed top-0 left-0 z-40 hidden h-screen flex-col border-r border-gray-200 bg-white transition-all duration-300 md:flex ${isCollapsed ? 'w-16' : 'w-[260px]'}`}
            >
                <div className="flex h-16 flex-shrink-0 items-center border-b border-gray-200 px-4">
                    {!isCollapsed ? (
                        <Link href="/" className="flex items-center gap-2.5 overflow-hidden">
                            <ApplicationLogo className="h-6 w-6 flex-shrink-0 text-indigo-600 fill-current" />
                            <span className="text-xl font-bold whitespace-nowrap text-gray-900 tracking-tight">
                                Client Portal
                            </span>
                        </Link>
                    ) : (
                        <Link href="/" className="mx-auto flex items-center justify-center">
                            <ApplicationLogo className="h-6 w-6 text-indigo-600 fill-current" />
                        </Link>
                    )}
                </div>
                <nav className="flex-1 space-y-2 overflow-x-hidden overflow-y-auto px-2 py-6">
                    <TooltipProvider delayDuration={0}>
                        {modules.map((item) => (
                            <Tooltip key={item.name}>
                                <TooltipTrigger asChild>
                                    <Link
                                        href={route(item.route)}
                                        className={`flex items-center rounded-md bg-gray-100 py-2 text-gray-700 hover:bg-gray-200 hover:text-gray-900 ${isCollapsed ? 'justify-center px-0' : 'px-4'}`}
                                    >
                                        <item.icon
                                            className={`h-5 w-5 flex-shrink-0 ${isCollapsed ? '' : 'mr-3'}`}
                                        />
                                        {!isCollapsed && (
                                            <span className="whitespace-nowrap">
                                                {item.name}
                                            </span>
                                        )}
                                    </Link>
                                </TooltipTrigger>
                                {isCollapsed && (
                                    <TooltipContent side="right">
                                        {item.name}
                                    </TooltipContent>
                                )}
                            </Tooltip>
                        ))}
                    </TooltipProvider>
                </nav>
                <div className="border-t border-gray-200 p-2">
                    <Button
                        variant="ghost"
                        size="icon"
                        className="flex w-full justify-center"
                        onClick={() => setIsCollapsed(!isCollapsed)}
                    >
                        {isCollapsed ? (
                            <ChevronRight className="h-5 w-5" />
                        ) : (
                            <ChevronLeft className="h-5 w-5" />
                        )}
                    </Button>
                </div>
            </aside>

            {/* Main Content */}
            <main
                className={`flex min-h-screen flex-col transition-all duration-300 ${isCollapsed ? 'md:ml-16' : 'md:ml-[260px]'}`}
            >
                {/* Top Bar */}
                <header className="flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 md:px-6">
                    <div className="flex items-center">
                        <Sheet
                            open={isMobileMenuOpen}
                            onOpenChange={setIsMobileMenuOpen}
                        >
                            <SheetTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="mr-2 md:hidden"
                                >
                                    <Menu className="h-5 w-5" />
                                </Button>
                            </SheetTrigger>
                            <SheetContent side="left" className="w-[260px] p-0">
                                <div className="flex h-16 items-center border-b border-gray-200 px-6">
                                    <Link href="/" className="flex items-center gap-2.5">
                                        <ApplicationLogo className="h-6 w-6 text-indigo-600 fill-current" />
                                        <span className="text-xl font-bold text-gray-900 tracking-tight">
                                            Client Portal
                                        </span>
                                    </Link>
                                </div>
                                <nav className="flex-1 space-y-2 px-4 py-6">
                                    {modules.map((item) => (
                                        <Link
                                            key={item.name}
                                            href={route(item.route)}
                                            onClick={() =>
                                                setIsMobileMenuOpen(false)
                                            }
                                            className="flex items-center rounded-md bg-gray-100 px-4 py-2 text-gray-700 hover:bg-gray-200 hover:text-gray-900"
                                        >
                                            <item.icon className="mr-3 h-5 w-5" />
                                            {item.name}
                                        </Link>
                                    ))}
                                </nav>
                            </SheetContent>
                        </Sheet>
                        <span className="text-lg font-semibold text-gray-800">
                            Dashboard
                        </span>
                    </div>

                    <div className="flex items-center space-x-4">
                        <div className="hidden items-center rounded-full border border-green-200 bg-green-50 px-3 py-1.5 text-sm font-medium text-green-700 sm:flex">
                            <Wallet className="mr-2 h-4 w-4" />$
                            {walletBalance.toFixed(2)}
                        </div>

                        <Button variant="ghost" size="icon">
                            <Moon className="h-5 w-5 text-gray-600" />
                            <span className="sr-only">Toggle dark mode</span>
                        </Button>

                        <Button
                            variant="ghost"
                            size="icon"
                            className="relative"
                        >
                            <Bell className="h-5 w-5 text-gray-600" />
                            <span className="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-red-500"></span>
                            <span className="sr-only">View notifications</span>
                        </Button>

                        <DropdownMenu>
                            <DropdownMenuTrigger className="relative h-8 w-8 rounded-full">
                                <Avatar className="h-8 w-8">
                                    <AvatarImage
                                        src="/avatars/01.png"
                                        alt="@client"
                                    />
                                    <AvatarFallback>CL</AvatarFallback>
                                </Avatar>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent className="w-56" align="end">
                                <DropdownMenuLabel className="font-normal">
                                    <div className="flex flex-col space-y-1">
                                        <p className="text-sm leading-none font-medium">
                                            {user?.name || 'Client User'}
                                        </p>
                                        <p className="text-muted-foreground text-xs leading-none">
                                            {user?.email ||
                                                'client@example.com'}
                                        </p>
                                    </div>
                                </DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    render={
                                        <Link href={route('profile.edit')} />
                                    }
                                >
                                    Profile
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    render={
                                        <Link
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                            className="w-full"
                                        />
                                    }
                                >
                                    Log out
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </header>

                {/* Page Content */}
                <div className="flex-1 overflow-auto bg-gray-50 p-6">
                    {children}
                </div>
            </main>
        </div>
    );
}

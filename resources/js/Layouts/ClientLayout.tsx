import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { User } from '@/types';
import { Link } from '@inertiajs/react';
import {
    Bell,
    Briefcase,
    Building2,
    LayoutDashboard,
    Moon,
    ShoppingCart,
    Wallet,
} from 'lucide-react';
import { PropsWithChildren } from 'react';

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
        <div className="flex min-h-screen bg-gray-50">
            {/* Sidebar */}
            <aside className="flex hidden w-64 flex-col border-r border-gray-200 bg-white md:flex">
                <div className="flex h-16 items-center border-b border-gray-200 px-6">
                    <span className="text-xl font-bold text-gray-900">
                        Client Portal
                    </span>
                </div>
                <nav className="flex-1 space-y-2 px-4 py-6">
                    {modules.map((item) => (
                        <Link
                            key={item.name}
                            href={route(item.route)}
                            className="flex items-center rounded-md bg-gray-100 px-4 py-2 text-gray-700 hover:bg-gray-200 hover:text-gray-900"
                        >
                            <item.icon className="mr-3 h-5 w-5" />
                            {item.name}
                        </Link>
                    ))}
                </nav>
            </aside>

            {/* Main Content */}
            <main className="flex min-w-0 flex-1 flex-col overflow-hidden">
                {/* Top Bar */}
                <header className="flex h-16 items-center justify-between border-b border-gray-200 bg-white px-6">
                    <div className="flex items-center">
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
                            <span className="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-red-500"></span>
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
                                        <p className="text-sm font-medium leading-none">
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

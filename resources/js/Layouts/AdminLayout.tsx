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
import { User } from '@/types';
import { Link } from '@inertiajs/react';
import {
    Bell,
    Briefcase,
    Building2,
    LayoutDashboard,
    LogIn,
    Moon,
    ShoppingCart,
} from 'lucide-react';
import { PropsWithChildren } from 'react';

interface AdminLayoutProps extends PropsWithChildren {
    user: User;
}

export default function AdminLayout({ user, children }: AdminLayoutProps) {
    const modules = [
        { name: 'Core', icon: LayoutDashboard, route: 'dashboard' },
        { name: 'ERP', icon: Building2, route: 'erp.dashboard' },
        { name: 'Freelance', icon: Briefcase, route: 'freelance.dashboard' },
        {
            name: 'Marketplace',
            icon: ShoppingCart,
            route: 'marketplace.dashboard',
        },
    ];

    return (
        <div className="flex min-h-screen bg-gray-50">
            {/* Sidebar */}
            <aside className="flex hidden w-64 flex-col border-r border-gray-200 bg-white md:flex">
                <div className="flex h-16 items-center border-b border-gray-200 px-6">
                    <span className="text-xl font-bold text-gray-900">
                        Admin Panel
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
                            Admin Dashboard
                        </span>
                    </div>

                    <div className="flex items-center space-x-4">
                        <Button
                            variant="outline"
                            size="sm"
                            className="hidden items-center sm:flex"
                        >
                            <LogIn className="mr-2 h-4 w-4" />
                            Login as Client
                        </Button>

                        <Button variant="ghost" size="icon">
                            <Moon className="h-5 w-5 text-gray-600" />
                            <span className="sr-only">Toggle dark mode</span>
                        </Button>

                        <Button variant="ghost" size="icon">
                            <Bell className="h-5 w-5 text-gray-600" />
                            <span className="sr-only">View notifications</span>
                        </Button>

                        <DropdownMenu>
                            <DropdownMenuTrigger className="relative h-8 w-8 rounded-full">
                                <Avatar className="h-8 w-8">
                                    <AvatarImage
                                        src="/avatars/01.png"
                                        alt="@admin"
                                    />
                                    <AvatarFallback>AD</AvatarFallback>
                                </Avatar>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent className="w-56" align="end">
                                <DropdownMenuLabel className="font-normal">
                                    <div className="flex flex-col space-y-1">
                                        <p className="text-sm leading-none font-medium">
                                            {user?.name || 'Admin User'}
                                        </p>
                                        <p className="text-muted-foreground text-xs leading-none">
                                            {user?.email || 'admin@example.com'}
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

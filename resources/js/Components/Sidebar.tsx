import { Link, usePage } from '@inertiajs/react';
import { 
    LayoutDashboard, 
    Briefcase, 
    ShoppingCart, 
    Settings, 
    Users, 
    FileText, 
    Wallet,
    ChevronRight,
    Home
} from 'lucide-react';
import { cn } from '@/lib/utils';

export default function Sidebar() {
    console.log("Sidebar rendering...");
    const { auth } = usePage().props as any;
    const user = auth.user;
    const currentRoute = route().current();

    const navigation = [
        { name: 'Dashboard', href: route('dashboard'), icon: LayoutDashboard, current: currentRoute === 'dashboard' },
        { 
            name: 'ERP Workspace', 
            href: route('erp.dashboard'), 
            icon: Home, 
            current: currentRoute?.startsWith('erp.'),
            badge: 'Business'
        },
        { 
            name: 'Freelance Portal', 
            href: route('freelance.dashboard'), 
            icon: Briefcase, 
            current: currentRoute?.startsWith('freelance.'),
            badge: 'Jobs'
        },
        { 
            name: 'Marketplace', 
            href: route('marketplace.dashboard'), 
            icon: ShoppingCart, 
            current: currentRoute?.startsWith('marketplace.'),
            badge: 'Store'
        },
    ];

    const adminNavigation = [
        { name: 'System Admin', href: route('admin.dashboard'), icon: Settings, current: currentRoute?.startsWith('admin.') },
        { name: 'Reports', href: route('admin.reports.pnl'), icon: FileText, current: currentRoute === 'admin.reports.pnl' },
    ];

    return (
        <div className="flex h-full w-64 flex-col bg-white border-r border-gray-200">
            <div className="flex flex-col flex-grow pt-5 pb-4 overflow-y-auto">
                <div className="flex items-center flex-shrink-0 px-4 mb-5">
                    <span className="text-2xl font-bold text-indigo-600">MuSoftwares</span>
                </div>
                <nav className="flex-1 px-2 space-y-1 bg-white" aria-label="Sidebar">
                    <div className="space-y-1">
                        <p className="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                            Main Menu
                        </p>
                        {navigation.map((item) => (
                            <Link
                                key={item.name}
                                href={item.href}
                                className={cn(
                                    item.current
                                        ? 'bg-indigo-50 text-indigo-600'
                                        : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                    'group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors'
                                )}
                            >
                                <item.icon
                                    className={cn(
                                        item.current ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500',
                                        'mr-3 flex-shrink-0 h-5 w-5'
                                    )}
                                    aria-hidden="true"
                                />
                                <span className="flex-1">{item.name}</span>
                                {item.badge && (
                                    <span className={cn(
                                        item.current ? 'bg-indigo-200 text-indigo-800' : 'bg-gray-100 text-gray-600',
                                        'ml-3 inline-block py-0.5 px-2 text-xs font-medium rounded-full'
                                    )}>
                                        {item.badge}
                                    </span>
                                )}
                            </Link>
                        ))}
                    </div>

                    {user.role === 'admin' && (
                        <div className="mt-8 space-y-1">
                            <p className="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                                Administration
                            </p>
                            {adminNavigation.map((item) => (
                                <Link
                                    key={item.name}
                                    href={item.href}
                                    className={cn(
                                        item.current
                                            ? 'bg-red-50 text-red-600'
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                        'group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors'
                                    )}
                                >
                                    <item.icon
                                        className={cn(
                                            item.current ? 'text-red-600' : 'text-gray-400 group-hover:text-gray-500',
                                            'mr-3 flex-shrink-0 h-5 w-5'
                                        )}
                                        aria-hidden="true"
                                    />
                                    <span className="flex-1">{item.name}</span>
                                </Link>
                            ))}
                        </div>
                    )}
                </nav>
            </div>
            <div className="flex-shrink-0 flex border-t border-gray-200 p-4">
                <div className="flex-shrink-0 w-full group block">
                    <div className="flex items-center">
                        <div>
                            <div className="h-9 w-9 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold">
                                {user.name.charAt(0)}
                            </div>
                        </div>
                        <div className="ml-3">
                            <p className="text-sm font-medium text-gray-700 group-hover:text-gray-900">
                                {user.name}
                            </p>
                            <p className="text-xs font-medium text-gray-500 group-hover:text-gray-700 uppercase">
                                {user.role}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

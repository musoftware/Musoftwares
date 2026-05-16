import { AnimatePresence, motion } from 'framer-motion';
import {
    Briefcase,
    FileText,
    LayoutDashboard,
    Menu,
    Settings,
    ShoppingBag,
    Users,
    X,
} from 'lucide-react';
import { useSidebar } from '../../../hooks/use-sidebar';
import { cn } from '../../../lib/utils';
import { SidebarItem } from './SidebarItem';

const ADMIN_NAVIGATION = [
    { name: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard },
    { name: 'Clients', href: '/admin/clients', icon: Users },
    { name: 'ERP Invoices', href: '/erp/invoices', icon: FileText },
    { name: 'Freelance', href: '/freelance/jobs', icon: Briefcase },
    { name: 'Marketplace', href: '/marketplace/services', icon: ShoppingBag },
    { name: 'Settings', href: '/profile', icon: Settings },
];

export function Sidebar() {
    const { isOpen, toggle } = useSidebar();

    return (
        <motion.aside
            initial={false}
            animate={{ width: isOpen ? 256 : 80 }}
            className={cn(
                'relative z-20 flex h-screen shrink-0 flex-col border-r border-border bg-card transition-all duration-300',
                !isOpen && 'items-center',
            )}
        >
            <div className="flex h-16 items-center justify-between border-b border-border px-4">
                <AnimatePresence mode="wait">
                    {isOpen && (
                        <motion.div
                            initial={{ opacity: 0, x: -10 }}
                            animate={{ opacity: 1, x: 0 }}
                            exit={{ opacity: 0, x: -10 }}
                            className="truncate text-lg font-bold"
                        >
                            SaaS Platform
                        </motion.div>
                    )}
                </AnimatePresence>
                <button
                    onClick={toggle}
                    className="rounded-md p-2 text-muted-foreground transition-colors hover:bg-muted"
                >
                    {isOpen ? <X size={20} /> : <Menu size={20} />}
                </button>
            </div>

            <nav className="flex-1 space-y-1 overflow-y-auto px-2 py-4">
                {ADMIN_NAVIGATION.map((item) => (
                    <SidebarItem
                        key={item.name}
                        href={item.href}
                        icon={item.icon}
                        name={item.name}
                        isOpen={isOpen}
                        isActive={window.location.pathname.startsWith(
                            item.href,
                        )}
                    />
                ))}
            </nav>

            {/* Workspace / Tenant Switcher Placeholder */}
            <div
                className={cn(
                    'border-t border-border p-4',
                    !isOpen && 'flex justify-center',
                )}
            >
                {isOpen ? (
                    <div className="flex items-center space-x-3 rounded-md border border-border bg-muted/50 p-2">
                        <div className="flex h-8 w-8 items-center justify-center rounded bg-primary font-bold text-primary-foreground">
                            AC
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-sm font-medium text-foreground">
                                Acme Corp
                            </p>
                            <p className="truncate text-xs text-muted-foreground">
                                Free Plan
                            </p>
                        </div>
                    </div>
                ) : (
                    <div className="flex h-8 w-8 items-center justify-center rounded bg-primary font-bold text-primary-foreground">
                        AC
                    </div>
                )}
            </div>
        </motion.aside>
    );
}

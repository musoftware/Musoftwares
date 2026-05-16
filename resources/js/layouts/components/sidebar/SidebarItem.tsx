import { Link } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
import { LucideIcon } from 'lucide-react';
import { cn } from '../../../lib/utils';

interface SidebarItemProps {
    href: string;
    icon: LucideIcon;
    name: string;
    isOpen: boolean;
    isActive?: boolean;
}

export function SidebarItem({
    href,
    icon: Icon,
    name,
    isOpen,
    isActive,
}: SidebarItemProps) {
    return (
        <Link
            href={href}
            className={cn(
                'group relative flex items-center space-x-3 rounded-md px-3 py-2.5 transition-colors',
                isActive
                    ? 'bg-primary/10 font-medium text-primary'
                    : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                !isOpen && 'justify-center px-0',
            )}
            title={!isOpen ? name : undefined}
        >
            <Icon
                size={20}
                className={cn(
                    'shrink-0',
                    isActive
                        ? 'text-primary'
                        : 'text-muted-foreground group-hover:text-foreground',
                )}
            />

            <AnimatePresence mode="wait">
                {isOpen && (
                    <motion.span
                        initial={{ opacity: 0, width: 0 }}
                        animate={{ opacity: 1, width: 'auto' }}
                        exit={{ opacity: 0, width: 0 }}
                        className="truncate whitespace-nowrap"
                    >
                        {name}
                    </motion.span>
                )}
            </AnimatePresence>
        </Link>
    );
}

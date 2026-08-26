import React from 'react';
import { useAppStore } from '@/store/useAppStore';
import { Sun, Moon, Laptop, Check } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Button } from '@/Components/ui/button';
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';

interface ThemeToggleProps {
    className?: string;
    variant?: 'ghost' | 'outline' | 'default';
    size?: 'default' | 'sm' | 'lg' | 'icon';
    showLabel?: boolean;
}

export default function ThemeToggle({
    className,
    variant = 'ghost',
    size = 'icon',
    showLabel = false,
}: ThemeToggleProps) {
    const theme = useAppStore((state) => state.theme);
    const setTheme = useAppStore((state) => state.setTheme);

    const getIcon = () => {
        switch (theme) {
            case 'light':
                return <Sun className="h-4 w-4 text-amber-500 transition-transform duration-300 hover:rotate-45" />;
            case 'dark':
                return <Moon className="h-4 w-4 text-indigo-400 transition-transform duration-300 hover:-rotate-12" />;
            default:
                return <Laptop className="h-4 w-4 text-zinc-500 dark:text-zinc-400" />;
        }
    };

    const getLabel = () => {
        switch (theme) {
            case 'light':
                return __('general.light') || 'Light';
            case 'dark':
                return __('general.dark') || 'Dark';
            default:
                return __('general.system_auto') || 'Auto (System)';
        }
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant={variant}
                    size={size}
                    aria-label="Select theme mode"
                    className={cn(
                        "rounded-full border border-black/10 dark:border-white/10 hover:bg-black/5 dark:hover:bg-white/10 transition-all focus-visible:ring-1 focus-visible:ring-indigo-500",
                        showLabel && "px-3 gap-2 w-auto",
                        className
                    )}
                >
                    {getIcon()}
                    {showLabel && <span className="text-xs font-medium">{getLabel()}</span>}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-44 rounded-2xl p-1.5 border border-black/10 dark:border-white/10 bg-white/95 dark:bg-zinc-900/95 backdrop-blur-xl shadow-xl z-50">
                <DropdownMenuItem
                    onClick={() => setTheme('light')}
                    className={cn(
                        "flex items-center justify-between rounded-xl px-2.5 py-2 text-xs font-medium cursor-pointer transition-colors",
                        theme === 'light'
                            ? "bg-amber-500/10 text-amber-600 dark:text-amber-400 font-semibold"
                            : "hover:bg-black/5 dark:hover:bg-white/5 text-zinc-700 dark:text-zinc-300"
                    )}
                >
                    <div className="flex items-center gap-2">
                        <Sun className="h-3.5 w-3.5 text-amber-500" />
                        <span>{__('general.light') || 'Light'}</span>
                    </div>
                    {theme === 'light' && <Check className="h-3.5 w-3.5 text-amber-500" />}
                </DropdownMenuItem>

                <DropdownMenuItem
                    onClick={() => setTheme('dark')}
                    className={cn(
                        "flex items-center justify-between rounded-xl px-2.5 py-2 text-xs font-medium cursor-pointer transition-colors",
                        theme === 'dark'
                            ? "bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 font-semibold"
                            : "hover:bg-black/5 dark:hover:bg-white/5 text-zinc-700 dark:text-zinc-300"
                    )}
                >
                    <div className="flex items-center gap-2">
                        <Moon className="h-3.5 w-3.5 text-indigo-400" />
                        <span>{__('general.dark') || 'Dark'}</span>
                    </div>
                    {theme === 'dark' && <Check className="h-3.5 w-3.5 text-indigo-400" />}
                </DropdownMenuItem>

                <DropdownMenuItem
                    onClick={() => setTheme('system')}
                    className={cn(
                        "flex items-center justify-between rounded-xl px-2.5 py-2 text-xs font-medium cursor-pointer transition-colors",
                        theme === 'system'
                            ? "bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-semibold"
                            : "hover:bg-black/5 dark:hover:bg-white/5 text-zinc-700 dark:text-zinc-300"
                    )}
                >
                    <div className="flex items-center gap-2">
                        <Laptop className="h-3.5 w-3.5 text-zinc-500 dark:text-zinc-400" />
                        <span>{__('general.system_auto') || 'Auto (System)'}</span>
                    </div>
                    {theme === 'system' && <Check className="h-3.5 w-3.5 text-emerald-500" />}
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

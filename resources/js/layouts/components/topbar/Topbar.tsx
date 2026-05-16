import { Link } from '@inertiajs/react';
import { Bell, Moon, Search, Sun } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '../../../components/ui/dropdown-menu';
import { useAuth } from '../../../hooks/use-auth';
import { useTheme } from '../../../hooks/use-theme';

export function Topbar() {
    const { theme, setTheme } = useTheme();
    const { user } = useAuth();

    return (
        <header className="z-10 flex h-16 shrink-0 items-center justify-between border-b border-border bg-card px-4 lg:px-8">
            <div className="flex flex-1 items-center">
                {/* Search Command Palette Trigger Placeholder */}
                <button className="flex w-full max-w-sm items-center space-x-2 rounded-md border border-border bg-muted/50 px-3 py-1.5 text-muted-foreground transition-colors hover:text-foreground">
                    <Search size={18} />
                    <span className="text-sm">Search...</span>
                    <kbd className="pointer-events-none ml-auto inline-flex h-5 select-none items-center gap-1 rounded border bg-muted px-1.5 font-mono text-[10px] font-medium text-muted-foreground opacity-100">
                        <span className="text-xs">⌘</span>K
                    </kbd>
                </button>
            </div>

            <div className="flex items-center space-x-4">
                {/* Dark Mode Toggle */}
                <button
                    onClick={() =>
                        setTheme(theme === 'dark' ? 'light' : 'dark')
                    }
                    className="rounded-full p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                >
                    {theme === 'dark' ? <Sun size={20} /> : <Moon size={20} />}
                </button>

                {/* Notifications */}
                <button className="relative rounded-full p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground">
                    <Bell size={20} />
                    <span className="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-destructive"></span>
                </button>

                {/* Profile Dropdown */}
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <button className="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 font-medium text-primary outline-none transition-all hover:ring-2 hover:ring-primary/20">
                            {user?.name?.charAt(0) || 'U'}
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-56">
                        <DropdownMenuLabel>My Account</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild>
                            <Link
                                href="/profile"
                                className="w-full cursor-pointer"
                            >
                                Profile Settings
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                            <Link
                                href="/logout"
                                method="post"
                                as="button"
                                className="w-full cursor-pointer"
                            >
                                Log out
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </header>
    );
}

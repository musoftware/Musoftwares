import React, { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Search, Menu, Globe, Heart } from 'lucide-react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import MarketplaceModeToggle from '../MarketplaceModeToggle';
import { __ } from '@/lib/i18n';

export default function MarketplaceHeader() {
    const { auth, locale } = usePage().props as any;
    const user = auth?.user;
    const currentLocale = locale || (typeof document !== 'undefined' ? document.documentElement.lang : 'en');
    const [searchQuery, setSearchQuery] = useState('');

    const handleLanguageChange = (newLang: string) => {
        const url = new URL(window.location.href);
        url.searchParams.set('lang', newLang);
        window.location.href = url.toString();
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        // Redirect to search/browse page with query
        if (searchQuery.trim()) {
            window.location.href = `/marketplace/services?q=${encodeURIComponent(searchQuery)}`;
        }
    };

    return (
        <header className="sticky top-0 z-50 w-full border-b border-gray-200 bg-white">
            <div className="mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                {/* Left side: Logo and Search */}
                <div className="flex items-center gap-6 md:gap-8 flex-1">
                    <div className="flex items-center gap-2">
                        <Button variant="ghost" size="icon" className="md:hidden">
                            <Menu className="h-5 w-5" />
                        </Button>
                        <Link href="/marketplace" className="flex items-center gap-2">
                            <ApplicationLogo className="h-8 w-auto fill-current text-gray-900" />
                            <span className="text-2xl font-black tracking-tighter text-gray-900 hidden sm:block">
                                musoftware
                            </span>
                        </Link>
                    </div>

                    <div className="hidden md:flex flex-1 max-w-2xl">
                        <form onSubmit={handleSearch} className="w-full relative flex items-center">
                            <Input
                                type="search"
                                placeholder={__('general.what_service_are_you_looking_for_today')}
                                className="w-full rounded-e-none border-e-0 focus-visible:ring-0 focus-visible:ring-offset-0"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                            />
                            <Button type="submit" className="rounded-s-none bg-gray-900 hover:bg-gray-800 px-6">
                                <Search className="h-4 w-4 text-white" />
                            </Button>
                        </form>
                    </div>
                </div>

                {/* Right side: Navigation & Auth */}
                <div className="flex items-center gap-2 sm:gap-4 ms-auto ps-4">
                    <div className="hidden lg:flex items-center gap-4 text-sm font-medium text-gray-600">
                        <Link href="/marketplace/services" className="hover:text-indigo-600 transition-colors">
                            {__('general.explore')}
                        </Link>
                        <Link
                            href="/marketplace/favorites"
                            className="text-gray-600 hover:text-indigo-600 transition-colors p-2 rounded-full hover:bg-gray-100 flex items-center justify-center"
                            title={__('general.saved_favorites') || 'Saved Favorites'}
                            aria-label={__('general.saved_favorites') || 'Saved Favorites'}
                        >
                            <Heart className="h-5 w-5" />
                        </Link>

                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" size="sm" className="flex items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-indigo-600 px-2 py-1.5 h-auto focus-visible:ring-0">
                                    <Globe className="h-4 w-4" />
                                    <span>{currentLocale === 'ar' ? 'العربية' : 'English'}</span>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-36">
                                <DropdownMenuItem 
                                    onClick={() => handleLanguageChange('en')}
                                    className={`cursor-pointer ${currentLocale === 'en' ? 'font-bold text-indigo-600 bg-indigo-50' : ''}`}
                                >
                                    English
                                </DropdownMenuItem>
                                <DropdownMenuItem 
                                    onClick={() => handleLanguageChange('ar')}
                                    className={`cursor-pointer ${currentLocale === 'ar' ? 'font-bold text-indigo-600 bg-indigo-50' : ''}`}
                                >
                                    العربية
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        {!user && (
                            <Link href="/marketplace/services/create" className="hover:text-indigo-600 transition-colors">
                                {__('general.become_a_seller')}
                            </Link>
                        )}
                    </div>

                    <div className="h-6 w-px bg-gray-200 hidden lg:block mx-2"></div>

                    {user ? (
                        <div className="flex items-center gap-3">
                            <div className="hidden sm:block">
                                <MarketplaceModeToggle />
                            </div>
                            
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="ghost" className="relative h-9 w-9 rounded-full">
                                        <Avatar className="h-9 w-9">
                                            <AvatarImage src={user.profile_photo_url || ''} alt={user.name} />
                                            <AvatarFallback className="bg-indigo-100 text-indigo-700 font-bold">
                                                {user?.name ? user.name.charAt(0).toUpperCase() : 'U'}
                                            </AvatarFallback>
                                        </Avatar>
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent className="w-56" align="end">
                                    <DropdownMenuLabel className="font-normal">
                                        <div className="flex flex-col space-y-1">
                                            <p className="text-sm font-medium leading-none">{user.name}</p>
                                            <p className="text-xs leading-none text-muted-foreground">
                                                {user.email}
                                            </p>
                                        </div>
                                    </DropdownMenuLabel>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem asChild>
                                        <Link href="/marketplace" className="cursor-pointer w-full">{__('general.dashboard')}</Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem asChild>
                                        <Link href="/marketplace/orders" className="cursor-pointer w-full">{__('general.orders')}</Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem asChild>
                                        <Link href="/marketplace/favorites" className="cursor-pointer w-full flex items-center gap-2">
                                            <Heart className="h-4 w-4" />
                                            <span>{__('general.saved_favorites') || 'Wishlist'}</span>
                                        </Link>
                                    </DropdownMenuItem>

                                    <DropdownMenuItem asChild>
                                        <Link href="/profile" className="cursor-pointer w-full">{__('general.profile')}</Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem asChild>
                                        <Link href={route('logout')} method="post" as="button" className="cursor-pointer w-full">
                                            {__('general.log_out')}
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    ) : (
                        <div className="flex items-center gap-3">
                            <Link
                                href={route('login')}
                                className="text-sm font-medium text-gray-600 hover:text-indigo-600 transition-colors hidden sm:block"
                            >
                                {__('general.log_in')}
                            </Link>
                            <Button asChild className="bg-indigo-600 hover:bg-indigo-700 text-white font-medium">
                                <Link href={route('register')}>{__('general.join')}</Link>
                            </Button>
                        </div>
                    )}
                </div>
            </div>
        </header>
    );
}

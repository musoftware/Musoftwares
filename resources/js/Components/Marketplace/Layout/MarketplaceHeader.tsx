import React, { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Search, Menu, Globe, Heart, Plus, LayoutGrid, ExternalLink, Store, Sparkles, User, LogOut, ChevronDown } from 'lucide-react';
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
        if (searchQuery.trim()) {
            window.location.href = `/marketplace/services?q=${encodeURIComponent(searchQuery)}`;
        }
    };

    return (
        <header className="sticky top-0 z-50 w-full border-b border-slate-200/80 bg-white/95 backdrop-blur-md shadow-xs">
            <div className="mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                {/* Left side: Standalone Marketplace Brand & Search */}
                <div className="flex items-center gap-6 md:gap-8 flex-1">
                    <div className="flex items-center gap-3">
                        <Button variant="ghost" size="icon" className="md:hidden">
                            <Menu className="h-5 w-5 text-slate-700" />
                        </Button>

                        {/* Standalone Marketplace Logo & Badge */}
                        <Link href="/marketplace" className="flex items-center gap-2.5 group">
                            <div className="p-1.5 rounded-xl bg-indigo-50 border border-indigo-100 group-hover:bg-indigo-100 transition-colors">
                                <ApplicationLogo className="h-7 w-auto fill-current text-indigo-600 transition-transform group-hover:scale-105" />
                            </div>
                            <div className="flex flex-col">
                                <div className="flex items-center gap-1.5">
                                    <span className="text-xl font-black tracking-tighter text-slate-900">
                                        musoftware
                                    </span>
                                    <span className="inline-flex items-center rounded-full bg-indigo-600/10 px-2 py-0.5 text-[10px] font-bold tracking-wider text-indigo-700 uppercase">
                                        Marketplace
                                    </span>
                                </div>
                                <span className="text-[10px] font-medium text-slate-400 hidden sm:block">
                                    Digital Services & Software Store
                                </span>
                            </div>
                        </Link>
                    </div>

                    <div className="hidden md:flex flex-1 max-w-xl">
                        <form onSubmit={handleSearch} className="w-full relative flex items-center">
                            <div className="relative w-full">
                                <Input
                                    type="search"
                                    placeholder={__('general.what_service_are_you_looking_for_today') || 'Search services, digital tools & serials...'}
                                    className="w-full rounded-l-xl rounded-r-none border-r-0 border-slate-300 bg-slate-50/50 focus:bg-white focus-visible:ring-2 focus-visible:ring-indigo-500 text-sm h-10 ps-4"
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                />
                            </div>
                            <Button type="submit" className="rounded-l-none rounded-r-xl bg-indigo-600 hover:bg-indigo-700 h-10 px-5 text-white font-medium shadow-xs">
                                <Search className="h-4 w-4" />
                            </Button>
                        </form>
                    </div>
                </div>

                {/* Right side: Marketplace Navigation & User Profile */}
                <div className="flex items-center gap-2 sm:gap-3 ms-auto ps-4">
                    <div className="hidden lg:flex items-center gap-3 text-sm font-medium text-slate-600">
                        <Link href="/marketplace/services" className="hover:text-indigo-600 transition-colors px-2 py-1">
                            {__('general.explore') || 'Explore'}
                        </Link>
                        
                        <Link
                            href="/marketplace/favorites"
                            className="text-slate-600 hover:text-indigo-600 transition-colors p-2 rounded-full hover:bg-slate-100 flex items-center justify-center"
                            title={__('general.saved_favorites') || 'Saved Favorites'}
                            aria-label={__('general.saved_favorites') || 'Saved Favorites'}
                        >
                            <Heart className="h-4.5 w-4.5" />
                        </Link>

                        {/* Language Selector */}
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" size="sm" className="flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-indigo-600 px-2 py-1.5 h-8 focus-visible:ring-0">
                                    <Globe className="h-4 w-4 text-slate-500" />
                                    <span>{currentLocale === 'ar' ? 'العربية' : 'English'}</span>
                                    <ChevronDown className="h-3 w-3 opacity-50" />
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

                        {!user ? (
                            <Link href="/marketplace/services/create" className="hover:text-indigo-600 transition-colors px-2 py-1">
                                {__('general.become_a_seller') || 'Become a Seller'}
                            </Link>
                        ) : (
                            <Link
                                href="/marketplace/services/create"
                                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition-colors shadow-sm"
                            >
                                <Plus className="h-3.5 w-3.5" />
                                <span>{__('general.add_marketplace_item') || __('general.publish_service') || 'Add Item'}</span>
                            </Link>
                        )}
                    </div>

                    <div className="h-5 w-px bg-slate-200 hidden lg:block mx-1"></div>

                    {user ? (
                        <div className="flex items-center gap-2 sm:gap-3">
                            <div className="hidden sm:block">
                                <MarketplaceModeToggle />
                            </div>
                            
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="ghost" className="relative h-9 w-9 rounded-full ring-2 ring-indigo-500/20 hover:ring-indigo-500/40 transition-all p-0">
                                        <Avatar className="h-9 w-9">
                                            <AvatarImage src={user.profile_photo_url || ''} alt={user.name} />
                                            <AvatarFallback className="bg-indigo-600 text-white font-bold text-sm">
                                                {user?.name ? user.name.charAt(0).toUpperCase() : 'U'}
                                            </AvatarFallback>
                                        </Avatar>
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent className="w-60" align="end">
                                    <DropdownMenuLabel className="font-normal p-3 bg-slate-50 rounded-t-lg border-b border-slate-100">
                                        <div className="flex flex-col space-y-1">
                                            <p className="text-sm font-semibold text-slate-900 leading-none">{user.name}</p>
                                            <p className="text-xs text-slate-500 truncate leading-none mt-1">
                                                {user.email}
                                            </p>
                                        </div>
                                    </DropdownMenuLabel>
                                    
                                    <div className="p-1">
                                        <DropdownMenuItem asChild>
                                            <Link href="/marketplace/services/create" className="cursor-pointer w-full flex items-center gap-2.5 text-indigo-600 font-semibold bg-indigo-50/70 hover:bg-indigo-100/70 my-1 rounded-md p-2">
                                                <Plus className="h-4 w-4 text-indigo-600" />
                                                <span>{__('general.add_marketplace_item') || 'Add Marketplace Item'}</span>
                                            </Link>
                                        </DropdownMenuItem>
                                        
                                        <DropdownMenuItem asChild>
                                            <Link href="/marketplace" className="cursor-pointer w-full flex items-center gap-2.5 p-2 text-slate-700">
                                                <Store className="h-4 w-4 text-slate-400" />
                                                <span>{__('general.marketplace_dashboard') || 'Marketplace Dashboard'}</span>
                                            </Link>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem asChild>
                                            <Link href="/marketplace/orders" className="cursor-pointer w-full flex items-center gap-2.5 p-2 text-slate-700">
                                                <Sparkles className="h-4 w-4 text-slate-400" />
                                                <span>{__('general.orders') || 'Orders & Workspace'}</span>
                                            </Link>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem asChild>
                                            <Link href="/marketplace/favorites" className="cursor-pointer w-full flex items-center gap-2.5 p-2 text-slate-700">
                                                <Heart className="h-4 w-4 text-slate-400" />
                                                <span>{__('general.saved_favorites') || 'Wishlist'}</span>
                                            </Link>
                                        </DropdownMenuItem>

                                        <DropdownMenuSeparator className="my-1" />

                                        {/* Switch back to Main SaaS App */}
                                        <DropdownMenuItem asChild>
                                            <Link href="/dashboard" className="cursor-pointer w-full flex items-center gap-2.5 p-2 text-slate-700 font-medium">
                                                <LayoutGrid className="h-4 w-4 text-indigo-500" />
                                                <span>{__('general.return_to_main_app') || 'Main Business App'}</span>
                                            </Link>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem asChild>
                                            <Link href="/profile" className="cursor-pointer w-full flex items-center gap-2.5 p-2 text-slate-700">
                                                <User className="h-4 w-4 text-slate-400" />
                                                <span>{__('general.profile') || 'Account Profile'}</span>
                                            </Link>
                                        </DropdownMenuItem>

                                        <DropdownMenuSeparator className="my-1" />

                                        <DropdownMenuItem asChild>
                                            <Link href={route('logout')} method="post" as="button" className="cursor-pointer w-full flex items-center gap-2.5 p-2 text-red-600 font-medium">
                                                <LogOut className="h-4 w-4 text-red-500" />
                                                <span>{__('general.log_out') || 'Log out'}</span>
                                            </Link>
                                        </DropdownMenuItem>
                                    </div>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    ) : (
                        <div className="flex items-center gap-3">
                            <Link
                                href={route('login')}
                                className="text-sm font-medium text-slate-600 hover:text-indigo-600 transition-colors hidden sm:block"
                            >
                                {__('general.log_in') || 'Log In'}
                            </Link>
                            <Button asChild className="bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg">
                                <Link href={route('register')}>{__('general.join') || 'Join Marketplace'}</Link>
                            </Button>
                        </div>
                    )}
                </div>
            </div>
        </header>
    );
}

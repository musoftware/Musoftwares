import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode, useEffect, useState } from 'react';
import { Bell, Menu, X } from 'lucide-react';
import CommandPalette from '@/Components/CommandPalette';
import { Toaster } from '@/Components/ui/toaster';
import { useToast } from '@/Components/ui/use-toast';
import Sidebar from '@/Components/Sidebar';

export default function Authenticated({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const { auth, notifications, flash } = usePage().props as any;
    const user = auth.user;
    const isImpersonating = auth.is_impersonating;
    const { toast } = useToast();

    const [isSidebarOpen, setIsSidebarOpen] = useState(true);

    useEffect(() => {
        if (flash?.message) {
            toast({
                description: flash.message,
            });
        }
    }, [flash]);

    return (
        <div className="min-h-screen bg-gray-50 flex">
            {/* Desktop Sidebar */}
            <div className="hidden md:flex md:flex-shrink-0">
                <Sidebar />
            </div>

            <div className="flex flex-col flex-1">
                {isImpersonating && (
                    <div className="bg-amber-500 text-white px-4 py-2 text-center text-sm font-medium z-50">
                        Viewing as {user.name} —
                        <Link
                            href={route('stop-impersonating')}
                            method="post"
                            as="button"
                            className="ml-2 underline hover:text-amber-100"
                        >
                            [Stop]
                        </Link>
                    </div>
                )}

                <header className="relative z-10 flex-shrink-0 flex h-16 bg-white border-b border-gray-200">
                    <button
                        className="px-4 border-r border-gray-200 text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 md:hidden"
                        onClick={() => setIsSidebarOpen(true)}
                    >
                        <span className="sr-only">Open sidebar</span>
                        <Menu className="h-6 w-6" aria-hidden="true" />
                    </button>
                    
                    <div className="flex-1 px-4 flex justify-between">
                        <div className="flex-1 flex items-center">
                            {header && (
                                <div className="text-xl font-semibold text-gray-800">
                                    {header}
                                </div>
                            )}
                        </div>
                        <div className="ml-4 flex items-center md:ml-6 space-x-4">
                            {/* Notifications Dropdown */}
                            <div className="relative">
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <button className="relative p-2 text-gray-400 hover:text-gray-500 focus:outline-none transition duration-150 ease-in-out">
                                            <Bell className="w-6 h-6" />
                                            {notifications?.unread_count > 0 && (
                                                <span className="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 transform translate-x-1/4 -translate-y-1/4 bg-red-600 rounded-full">
                                                    {notifications.unread_count}
                                                </span>
                                            )}
                                        </button>
                                    </Dropdown.Trigger>

                                    <Dropdown.Content align="right" width="48">
                                        <div className="px-4 py-2 text-xs text-gray-500 border-b">
                                            Recent Notifications
                                        </div>
                                        {notifications?.recent?.length > 0 ? (
                                            notifications.recent.map((notification: any) => (
                                                <Dropdown.Link
                                                    key={notification.id}
                                                    href={route('notifications.mark-read', { id: notification.id })}
                                                    method="post"
                                                    as="button"
                                                    className="w-full text-left"
                                                >
                                                    {notification.data?.message || 'New notification'}
                                                </Dropdown.Link>
                                            ))
                                        ) : (
                                            <div className="px-4 py-2 text-sm text-gray-500">No new notifications</div>
                                        )}
                                        <div className="border-t border-gray-100"></div>
                                        <Dropdown.Link href={route('notifications.index')} className="text-center text-sm text-indigo-600 font-medium w-full block">
                                            View All
                                        </Dropdown.Link>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>

                            {/* Profile Dropdown */}
                            <div className="relative">
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <button className="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none transition duration-150 ease-in-out">
                                            <div className="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold mr-2">
                                                {user.name.charAt(0)}
                                            </div>
                                            <span className="hidden md:inline">{user.name}</span>
                                            <svg className="ml-1 h-4 w-4 fill-current" viewBox="0 0 20 20">
                                                <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
                                            </svg>
                                        </button>
                                    </Dropdown.Trigger>

                                    <Dropdown.Content>
                                        <Dropdown.Link href={route('profile.edit')}>Profile</Dropdown.Link>
                                        <Dropdown.Link href={route('logout')} method="post" as="button">Log Out</Dropdown.Link>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>
                        </div>
                    </div>
                </header>

                <main className="flex-1 relative overflow-y-auto focus:outline-none">
                    <div className="py-6">
                        <div className="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                            {children}
                        </div>
                    </div>
                </main>
            </div>

            {/* Mobile Sidebar Overlay */}
            {isSidebarOpen && (
                <div className="fixed inset-0 flex z-40 md:hidden">
                    <div className="fixed inset-0 bg-gray-600 bg-opacity-75" onClick={() => setIsSidebarOpen(false)}></div>
                    <div className="relative flex-1 flex flex-col max-w-xs w-full bg-white">
                        <div className="absolute top-0 right-0 -mr-12 pt-2">
                            <button
                                className="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
                                onClick={() => setIsSidebarOpen(false)}
                            >
                                <X className="h-6 w-6 text-white" />
                            </button>
                        </div>
                        <Sidebar />
                    </div>
                </div>
            )}

            <CommandPalette />
            <Toaster />
        </div>
    );
}

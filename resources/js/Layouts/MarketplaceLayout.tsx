import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode } from 'react';

export default function MarketplaceLayout({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const { auth } = usePage().props as any;
    const user = auth?.user;

    return (
        <div className="min-h-screen bg-gray-100">
            <nav className="border-b border-gray-100 bg-white">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between items-center">
                        <div className="flex items-center">
                            <Link href="/">
                                <ApplicationLogo className="block h-9 w-auto fill-current text-gray-800" />
                            </Link>
                            <span className="ml-4 text-xl font-bold text-gray-800">Marketplace</span>
                        </div>

                        <div className="flex items-center space-x-4">
                            <Link href={route('marketplace.services.index')} className="text-gray-600 hover:text-gray-900">
                                Browse
                            </Link>
                            {user ? (
                                <>
                                    <Link href={route('marketplace.orders.index')} className="text-gray-600 hover:text-gray-900">
                                        Orders
                                    </Link>
                                    <Link href={route('dashboard')} className="text-gray-600 hover:text-gray-900">
                                        Dashboard
                                    </Link>
                                </>
                            ) : (
                                <>
                                    <Link href={route('login')} className="text-gray-600 hover:text-gray-900">
                                        Log in
                                    </Link>
                                    <Link href={route('register')} className="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                                        Register
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </nav>

            {header && (
                <header className="bg-white shadow">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main>{children}</main>
        </div>
    );
}

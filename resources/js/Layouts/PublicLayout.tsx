import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

interface PublicLayoutProps extends PropsWithChildren {}

export default function PublicLayout({ children }: PublicLayoutProps) {
    return (
        <div className="flex min-h-screen flex-col bg-white">
            {/* Navbar */}
            <header className="sticky top-0 z-50 h-16 border-b border-gray-200 bg-white">
                <div className="mx-auto flex h-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center">
                        <Link
                            href="/"
                            className="text-2xl font-bold tracking-tight text-gray-900"
                        >
                            SaaS Platform
                        </Link>
                    </div>
                    <nav className="flex items-center space-x-4">
                        <Link href={route('login')}>
                            <Button variant="ghost">Log in</Button>
                        </Link>
                        <Link href={route('register')}>
                            <Button>Register</Button>
                        </Link>
                    </nav>
                </div>
            </header>

            {/* Main Content */}
            <main className="flex flex-1 flex-col">{children}</main>

            {/* Footer */}
            <footer className="border-t border-gray-200 bg-gray-50 py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="text-center text-sm text-gray-500">
                        &copy; {new Date().getFullYear()} SaaS Platform. All
                        rights reserved.
                    </div>
                </div>
            </footer>
        </div>
    );
}

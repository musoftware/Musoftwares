import { Button } from '@/Components/ui/button';
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
            <footer className="border-t border-gray-800 bg-gray-900 py-12 text-gray-300">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 gap-8 md:grid-cols-3">
                        {/* Column 1: Logo + Tagline + Social */}
                        <div className="space-y-4">
                            <Link href="/" className="text-2xl font-bold tracking-tight text-white">
                                SaaS Platform
                            </Link>
                            <p className="text-sm text-gray-400">
                                ERP, Freelancing marketplace, and services platform — all in one place.
                            </p>
                            <div className="flex space-x-4 pt-2">
                                {/* Social Icons Placeholders */}
                                <a href="#" className="text-gray-400 hover:text-white">
                                    <span className="sr-only">Twitter</span>
                                    <svg className="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
                                    </svg>
                                </a>
                                <a href="#" className="text-gray-400 hover:text-white">
                                    <span className="sr-only">GitHub</span>
                                    <svg className="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path fillRule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clipRule="evenodd" />
                                    </svg>
                                </a>
                            </div>
                        </div>

                        {/* Column 2: Quick Links */}
                        <div>
                            <h3 className="text-sm font-semibold uppercase tracking-wider text-white">
                                Quick Links
                            </h3>
                            <ul className="mt-4 space-y-2">
                                <li>
                                    <Link href="#" className="text-sm hover:text-white">Home</Link>
                                </li>
                                <li>
                                    <Link href="#" className="text-sm hover:text-white">Services</Link>
                                </li>
                                <li>
                                    <Link href="#" className="text-sm hover:text-white">Freelancers</Link>
                                </li>
                                <li>
                                    <Link href="#" className="text-sm hover:text-white">Pricing</Link>
                                </li>
                            </ul>
                        </div>

                        {/* Column 3: Contact Info */}
                        <div>
                            <h3 className="text-sm font-semibold uppercase tracking-wider text-white">
                                Contact Us
                            </h3>
                            <ul className="mt-4 space-y-2">
                                <li className="text-sm">
                                    <span className="block text-gray-400">Email:</span>
                                    <a href="mailto:hello@example.com" className="hover:text-white">hello@example.com</a>
                                </li>
                                <li className="text-sm">
                                    <span className="block text-gray-400">Phone:</span>
                                    <a href="tel:+1234567890" className="hover:text-white">+1 (234) 567-890</a>
                                </li>
                                <li className="text-sm">
                                    <span className="block text-gray-400">Address:</span>
                                    123 Tech Avenue, Suite 400<br />San Francisco, CA 94105
                                </li>
                            </ul>
                        </div>
                    </div>

                    {/* Bottom: Copyright */}
                    <div className="mt-12 border-t border-gray-800 pt-8 text-center md:flex md:items-center md:justify-between">
                        <div className="flex justify-center space-x-6 md:order-2">
                            <Link href="#" className="text-sm text-gray-400 hover:text-white">Privacy Policy</Link>
                            <Link href="#" className="text-sm text-gray-400 hover:text-white">Terms of Service</Link>
                        </div>
                        <p className="mt-8 text-sm text-gray-400 md:order-1 md:mt-0">
                            &copy; {new Date().getFullYear()} SaaS Platform. All rights reserved.
                        </p>
                    </div>
                </div>
            </footer>
        </div>
    );
}

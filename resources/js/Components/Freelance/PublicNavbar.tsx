import React, { useState } from 'react';
import { Link } from '@inertiajs/react';
import { __ } from '@/lib/i18n';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { Button } from '@/Components/ui/button';
import { Menu, X } from 'lucide-react';

interface PublicNavbarProps {
    canLogin?: boolean;
    canRegister?: boolean;
}

export default function PublicNavbar({ canLogin = true, canRegister = true }: PublicNavbarProps) {
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    const navigation = [
        { name: __('freelance.nav.browse_jobs', undefined, 'Browse Jobs'), href: route('freelance.jobs.browse') },
        { name: __('freelance.nav.browse_freelancers', undefined, 'Find Talent'), href: route('freelance.freelancers.browse') },
        { name: __('freelance.nav.how_it_works', undefined, 'How it Works'), href: route('freelance.how-it-works') },
        { name: __('freelance.nav.about_us', undefined, 'About Us'), href: route('freelance.about-us') },
    ];

    return (
        <header className="absolute inset-x-0 top-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-100">
            <nav className="mx-auto flex max-w-7xl items-center justify-between p-6 lg:px-8" aria-label="Global">
                <div className="flex lg:flex-1">
                    <Link href="/" className="-m-1.5 p-1.5 flex items-center gap-2">
                        <ApplicationLogo className="h-8 w-auto text-emerald-600" />
                        <span className="font-bold text-xl tracking-tight text-slate-900">ArabiJobs</span>
                    </Link>
                </div>
                
                <div className="flex lg:hidden">
                    <button
                        type="button"
                        className="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-gray-700"
                        onClick={() => setMobileMenuOpen(true)}
                    >
                        <span className="sr-only">Open main menu</span>
                        <Menu className="h-6 w-6" aria-hidden="true" />
                    </button>
                </div>

                <div className="hidden lg:flex lg:gap-x-8">
                    {navigation.map((item) => (
                        <Link key={item.name} href={item.href} className="text-sm font-semibold leading-6 text-gray-900 hover:text-emerald-600 transition-colors">
                            {item.name}
                        </Link>
                    ))}
                </div>

                <div className="hidden lg:flex lg:flex-1 lg:justify-end lg:items-center lg:gap-4">
                    {(!canLogin && !canRegister) ? (
                        <Link href={route('freelance.dashboard')}>
                            <Button className="bg-emerald-600 hover:bg-emerald-700 text-white rounded-full">
                                {__('general.dashboard')}
                            </Button>
                        </Link>
                    ) : (
                        <>
                            {canLogin && (
                                <Link href={route('login')} className="text-sm font-semibold leading-6 text-gray-900 hover:text-emerald-600">
                                    {__('general.login')}
                                </Link>
                            )}
                            {canRegister && (
                                <Link href={route('register')}>
                                    <Button className="bg-emerald-600 hover:bg-emerald-700 text-white rounded-full">
                                        {__('general.register')}
                                    </Button>
                                </Link>
                            )}
                        </>
                    )}
                </div>
            </nav>

            {/* Mobile menu, show/hide based on menu open state. */}
            {mobileMenuOpen && (
                <div className="lg:hidden" role="dialog" aria-modal="true">
                    <div className="fixed inset-0 z-50 bg-black/20" onClick={() => setMobileMenuOpen(false)}></div>
                    <div className="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-white px-6 py-6 sm:max-w-sm sm:ring-1 sm:ring-gray-900/10">
                        <div className="flex items-center justify-between">
                            <Link href="/" className="-m-1.5 p-1.5 flex items-center gap-2">
                                <ApplicationLogo className="h-8 w-auto text-emerald-600" />
                                <span className="font-bold text-xl tracking-tight text-slate-900">ArabiJobs</span>
                            </Link>
                            <button
                                type="button"
                                className="-m-2.5 rounded-md p-2.5 text-gray-700"
                                onClick={() => setMobileMenuOpen(false)}
                            >
                                <span className="sr-only">Close menu</span>
                                <X className="h-6 w-6" aria-hidden="true" />
                            </button>
                        </div>
                        <div className="mt-6 flow-root">
                            <div className="-my-6 divide-y divide-gray-500/10">
                                <div className="space-y-2 py-6">
                                    {navigation.map((item) => (
                                        <Link
                                            key={item.name}
                                            href={item.href}
                                            className="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50"
                                            onClick={() => setMobileMenuOpen(false)}
                                        >
                                            {item.name}
                                        </Link>
                                    ))}
                                </div>
                                <div className="py-6 flex flex-col gap-3">
                                    {canLogin && (
                                        <Link
                                            href={route('login')}
                                            className="-mx-3 block rounded-lg px-3 py-2.5 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50"
                                            onClick={() => setMobileMenuOpen(false)}
                                        >
                                            {__('general.login')}
                                        </Link>
                                    )}
                                    {canRegister && (
                                        <Link href={route('register')} onClick={() => setMobileMenuOpen(false)}>
                                            <Button className="w-full bg-emerald-600 hover:bg-emerald-700 text-white rounded-full">
                                                {__('general.register')}
                                            </Button>
                                        </Link>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </header>
    );
}

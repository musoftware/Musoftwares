import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { __ } from '@/lib/i18n';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { Button } from '@/Components/ui/button';

export default function Landing({ canLogin, canRegister }: { canLogin: boolean, canRegister: boolean }) {
    return (
        <div className="min-h-screen bg-white">
            <Head title="ArabiJobs Freelance" />
            
            <header className="absolute inset-x-0 top-0 z-50">
                <nav className="flex items-center justify-between p-6 lg:px-8" aria-label="Global">
                    <div className="flex lg:flex-1">
                        <Link href="/" className="-m-1.5 p-1.5 flex items-center gap-2">
                            <ApplicationLogo className="h-8 w-auto text-emerald-600" />
                            <span className="font-bold text-xl tracking-tight text-slate-900">ArabiJobs</span>
                        </Link>
                    </div>
                    <div className="flex flex-1 justify-end items-center gap-4">
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
                    </div>
                </nav>
            </header>

            <div className="relative isolate px-6 pt-14 lg:px-8">
                <div className="mx-auto max-w-2xl py-32 sm:py-48 lg:py-56">
                    <div className="text-center">
                        <h1 className="text-4xl font-bold tracking-tight text-gray-900 sm:text-6xl">
                            The Premier Freelance Marketplace
                        </h1>
                        <p className="mt-6 text-lg leading-8 text-gray-600">
                            Find top talent or great jobs. ArabiJobs provides a dedicated platform to connect freelancers with businesses instantly.
                        </p>
                        <div className="mt-10 flex items-center justify-center gap-x-6">
                            <Link href={route('register')}>
                                <Button size="lg" className="bg-emerald-600 hover:bg-emerald-700 text-white rounded-full px-8">
                                    Get Started
                                </Button>
                            </Link>
                            <Link href={route('login')} className="text-sm font-semibold leading-6 text-gray-900 hover:text-emerald-600">
                                Learn more <span aria-hidden="true">→</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

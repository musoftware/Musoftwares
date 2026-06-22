import { PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { __ } from '@/lib/i18n';
import { Button } from '@/components/ui/button';
import { motion } from 'framer-motion';

export default function Welcome({
    auth,
    laravelVersion,
    phpVersion,
}: PageProps<{ laravelVersion: string; phpVersion: string }>) {
    return (
        <>
            <Head title={__('general.welcome') || 'Welcome to Musoftwares'} />
            <div className="min-h-screen bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 selection:bg-zinc-900 selection:text-white dark:selection:bg-zinc-100 dark:selection:text-zinc-900 font-sans flex flex-col relative overflow-hidden">
                
                {/* Background Decoration */}
                <div className="absolute inset-0 pointer-events-none overflow-hidden flex justify-center items-center">
                    <div className="absolute w-[800px] h-[800px] bg-zinc-200/50 dark:bg-zinc-800/20 rounded-full blur-3xl -top-1/4 -right-1/4 opacity-50 mix-blend-multiply dark:mix-blend-screen"></div>
                    <div className="absolute w-[600px] h-[600px] bg-zinc-300/30 dark:bg-zinc-800/30 rounded-full blur-3xl -bottom-1/4 -left-1/4 opacity-50 mix-blend-multiply dark:mix-blend-screen"></div>
                </div>

                <div className="relative z-10 flex flex-col min-h-screen">
                    <header className="container mx-auto px-6 py-8 flex items-center justify-between">
                        <div className="flex items-center space-x-2">
                            <div className="w-8 h-8 bg-zinc-900 dark:bg-zinc-100 rounded-lg flex items-center justify-center">
                                <span className="text-white dark:text-zinc-900 font-bold text-xl">M</span>
                            </div>
                            <span className="font-bold text-xl tracking-tight">Musoftwares</span>
                        </div>
                        
                        <nav className="flex items-center space-x-4">
                            {auth.user ? (
                                <Link href={route('dashboard')}>
                                    <Button variant="outline" className="rounded-full px-6 font-medium border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                        {__('general.dashboard') || 'Dashboard'}
                                    </Button>
                                </Link>
                            ) : (
                                <>
                                    <Link href={route('login')} className="text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100 transition-colors">
                                        {__('general.log_in') || 'Log in'}
                                    </Link>
                                    <Link href={route('register')}>
                                        <Button className="rounded-full px-6 bg-zinc-900 text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200 font-medium">
                                            {__('general.register') || 'Get Started'}
                                        </Button>
                                    </Link>
                                </>
                            )}
                        </nav>
                    </header>

                    <main className="flex-1 flex items-center justify-center container mx-auto px-6 py-12 lg:py-24">
                        <div className="max-w-4xl text-center space-y-10">
                            <motion.div 
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.5 }}
                                className="space-y-6"
                            >
                                <h1 className="text-5xl sm:text-6xl md:text-7xl font-extrabold tracking-tight leading-tight">
                                    The Comprehensive <br className="hidden sm:block" />
                                    <span className="text-transparent bg-clip-text bg-gradient-to-r from-zinc-500 to-zinc-900 dark:from-zinc-400 dark:to-zinc-100">
                                        Business Platform
                                    </span>
                                </h1>
                                <p className="text-lg sm:text-xl text-zinc-500 dark:text-zinc-400 max-w-2xl mx-auto leading-relaxed">
                                    A modular, multi-tenant ecosystem combining ERP, CRM, Billing, and Marketplace features into one unified, intelligent workspace.
                                </p>
                            </motion.div>

                            <motion.div 
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.5, delay: 0.2 }}
                                className="flex flex-col sm:flex-row items-center justify-center gap-4"
                            >
                                {auth.user ? (
                                    <Link href={route('dashboard')}>
                                        <Button size="lg" className="h-12 px-8 rounded-full bg-zinc-900 text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200 text-base font-medium w-full sm:w-auto shadow-sm">
                                            Go to Dashboard
                                        </Button>
                                    </Link>
                                ) : (
                                    <>
                                        <Link href={route('register')}>
                                            <Button size="lg" className="h-12 px-8 rounded-full bg-zinc-900 text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200 text-base font-medium w-full sm:w-auto shadow-sm">
                                                Start your free trial
                                            </Button>
                                        </Link>
                                        <Link href={route('login')}>
                                            <Button size="lg" variant="outline" className="h-12 px-8 rounded-full border-zinc-200 dark:border-zinc-800 text-zinc-900 dark:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-base font-medium w-full sm:w-auto">
                                                Sign in to workspace
                                            </Button>
                                        </Link>
                                    </>
                                )}
                            </motion.div>

                            <motion.div 
                                initial={{ opacity: 0 }}
                                animate={{ opacity: 1 }}
                                transition={{ duration: 0.5, delay: 0.4 }}
                                className="pt-16 grid grid-cols-2 md:grid-cols-4 gap-6 sm:gap-8 text-center text-sm font-medium text-zinc-500 dark:text-zinc-400"
                            >
                                <div className="flex flex-col items-center gap-2">
                                    <div className="w-12 h-12 rounded-2xl bg-zinc-100 dark:bg-zinc-900 flex items-center justify-center mb-2 shadow-sm border border-zinc-200/50 dark:border-zinc-800/50">
                                        <svg className="w-6 h-6 text-zinc-700 dark:text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                        </svg>
                                    </div>
                                    <span>Smart Billing</span>
                                </div>
                                <div className="flex flex-col items-center gap-2">
                                    <div className="w-12 h-12 rounded-2xl bg-zinc-100 dark:bg-zinc-900 flex items-center justify-center mb-2 shadow-sm border border-zinc-200/50 dark:border-zinc-800/50">
                                        <svg className="w-6 h-6 text-zinc-700 dark:text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <span>Enterprise ERP</span>
                                </div>
                                <div className="flex flex-col items-center gap-2">
                                    <div className="w-12 h-12 rounded-2xl bg-zinc-100 dark:bg-zinc-900 flex items-center justify-center mb-2 shadow-sm border border-zinc-200/50 dark:border-zinc-800/50">
                                        <svg className="w-6 h-6 text-zinc-700 dark:text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                    <span>Modern CRM</span>
                                </div>
                                <div className="flex flex-col items-center gap-2">
                                    <div className="w-12 h-12 rounded-2xl bg-zinc-100 dark:bg-zinc-900 flex items-center justify-center mb-2 shadow-sm border border-zinc-200/50 dark:border-zinc-800/50">
                                        <svg className="w-6 h-6 text-zinc-700 dark:text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                        </svg>
                                    </div>
                                    <span>Marketplace</span>
                                </div>
                            </motion.div>
                        </div>
                    </main>

                    <footer className="container mx-auto px-6 py-8 text-center text-xs text-zinc-400 dark:text-zinc-600">
                        &copy; {new Date().getFullYear()} Musoftwares. All rights reserved. Laravel v{laravelVersion} (PHP v{phpVersion})
                    </footer>
                </div>
            </div>
        </>
    );
}

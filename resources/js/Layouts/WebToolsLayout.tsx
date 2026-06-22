import React, { PropsWithChildren } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { Button } from '@/Components/ui/button';
import { __ } from '@/lib/i18n';
import {
    Calculator, 
    Coins, 
    CreditCard, 
    DollarSign, 
    Lock, 
    MapPin, 
    Code, 
    Clock, 
    LogOut,
    LogIn,
    UserPlus,
    LayoutGrid,
    ChevronDown,
    ArrowUpRight
} from 'lucide-react';

interface WebToolsLayoutProps extends PropsWithChildren {
    title: string;
    activeNav?: string;
}

export default function WebToolsLayout({ children, title, activeNav = '' }: WebToolsLayoutProps) {
    const { auth } = usePage().props as any;
    const isAuthed = !!auth?.user;

    const financialTools = [
        { label: 'InstaPay Calculator', href: route('tools.withdraw-instapay'), icon: Calculator },
        { label: 'Gold Indicator', href: route('tools.gold-indicator'), icon: Coins },
        { label: 'Gold Saver', href: route('tools.gold-saver'), icon: Coins },
        { label: 'Smart Pricing', href: route('tools.smart-pricing-calculator'), icon: DollarSign },
        { label: 'Pay Guest', href: route('tools.pay-guest'), icon: CreditCard },
        { label: 'General Calculator', href: route('tools.calculator'), icon: Calculator },
        { label: 'Payout USD', href: route('tools.payout-usd'), icon: DollarSign },
    ];

    const utilityTools = [
        { label: 'Cipher Identifier', href: route('tools.cipher-identifier'), icon: Lock },
        { label: 'Coordinates Converter', href: route('tools.coordinates-converter'), icon: MapPin },
        { label: 'JS Obfuscator', href: route('tools.js-obfuscator'), icon: Code },
        { label: 'Countdown Timer', href: route('tools.multiple-countdown-timer'), icon: Clock },
    ];

    return (
        <div className="min-h-screen bg-slate-50 font-sans text-slate-900 flex flex-col">
            <Head title={`${title} — musoftware Web Tools`} />

            {/* Header */}
            <header className="sticky top-0 z-40 w-full bg-white/95 backdrop-blur-sm border-b border-slate-200">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 items-center justify-between">
                        {/* Logo */}
                        <div className="flex items-center gap-8">
                            <Link href="/" className="flex items-center gap-2 group">
                                <div className="w-8 h-8 rounded-lg bg-slate-900 flex items-center justify-center group-hover:bg-slate-700 transition-colors">
                                    <ApplicationLogo className="w-4 h-4 text-white fill-current" />
                                </div>
                                <span className="font-bold text-lg tracking-tight text-slate-900">
                                    musoftware <span className="font-light text-slate-500">{__('general.tools')}</span>
                                </span>
                            </Link>

                            {/* Nav dropdowns (Desktop) */}
                            <nav className="hidden md:flex items-center gap-6">
                                <div className="relative group">
                                    <button className="flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-slate-900 py-2">
                                        {__('general.financial')}<ChevronDown className="w-4 h-4 transition-transform group-hover:rotate-180" />
                                    </button>
                                    <div className="absolute top-full start-0 w-64 bg-white border border-slate-200 rounded-xl shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all p-2 flex flex-col gap-1 z-50">
                                        {financialTools.map((tool, idx) => {
                                            const Icon = tool.icon;
                                            return (
                                                <Link key={idx} href={tool.href} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-50 text-sm text-slate-700 hover:text-slate-900 transition-colors">
                                                    <Icon className="w-4 h-4 text-slate-400" />
                                                    {tool.label}
                                                </Link>
                                            );
                                        })}
                                    </div>
                                </div>

                                <div className="relative group">
                                    <button className="flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-slate-900 py-2">
                                        {__('general.utilities')}<ChevronDown className="w-4 h-4 transition-transform group-hover:rotate-180" />
                                    </button>
                                    <div className="absolute top-full start-0 w-64 bg-white border border-slate-200 rounded-xl shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all p-2 flex flex-col gap-1 z-50">
                                        {utilityTools.map((tool, idx) => {
                                            const Icon = tool.icon;
                                            return (
                                                <Link key={idx} href={tool.href} className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-50 text-sm text-slate-700 hover:text-slate-900 transition-colors">
                                                    <Icon className="w-4 h-4 text-slate-400" />
                                                    {tool.label}
                                                </Link>
                                            );
                                        })}
                                    </div>
                                </div>
                            </nav>
                        </div>

                        {/* Auth actions */}
                        <div className="flex items-center gap-3">
                            {isAuthed ? (
                                <Link href="/dashboard">
                                    <Button variant="outline" className="rounded-full text-sm font-medium">
                                        {__('general.dashboard')}</Button>
                                </Link>
                            ) : (
                                <>
                                    <Link href={route('login')} className="hidden sm:block text-sm font-medium text-slate-600 hover:text-slate-900">
                                        {__('general.sign_in')}</Link>
                                    <Link href={route('register')}>
                                        <Button className="rounded-full bg-slate-900 text-white hover:bg-slate-800 text-sm">
                                            {__('general.register_free')}</Button>
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </header>

            {/* Main */}
            <main className="flex-1 w-full">
                {children}
            </main>

            {/* Footer */}
            <footer className="border-t border-slate-200 bg-white mt-auto">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div className="flex items-center gap-2">
                            <div className="w-6 h-6 rounded-md bg-slate-900 flex items-center justify-center">
                                <ApplicationLogo className="w-3 h-3 text-white fill-current" />
                            </div>
                            <span className="text-sm font-bold text-slate-900">musoftware</span>
                        </div>
                        <div className="flex items-center gap-6 text-sm text-slate-500">
                            <span>© {new Date().getFullYear()} musoftware. All rights reserved.</span>
                            <Link href="/" className="hover:text-slate-900 transition-colors">{__('general.main_site')}</Link>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    );
}


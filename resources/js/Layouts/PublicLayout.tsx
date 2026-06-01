import { Button } from '@/Components/ui/button';
import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, useEffect, useState } from 'react';
import { Menu, X, ArrowRight, Sparkles, CheckCircle2 } from 'lucide-react';
import ApplicationLogo from '@/Components/ApplicationLogo';

interface PublicLayoutProps extends PropsWithChildren {
    auth?: {
        user: any;
    };
}

export default function PublicLayout({ children, auth: propAuth }: PublicLayoutProps) {
    const { auth: pageAuth } = usePage().props as any;
    const auth = propAuth || pageAuth;
    const [scrolled, setScrolled] = useState(false);
    const [activeSection, setActiveSection] = useState('hero');
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    useEffect(() => {
        const handleScroll = () => {
            setScrolled(window.scrollY > 20);

            // Scroll spy for active section highlight
            const sections = ['hero', 'overview', 'financials', 'marketplace', 'workspace', 'communication', 'pricing', 'faq'];
            const scrollPosition = window.scrollY + 120;

            for (const section of sections) {
                const el = document.getElementById(section);
                if (el) {
                    const top = el.offsetTop;
                    const height = el.offsetHeight;
                    if (scrollPosition >= top && scrollPosition < top + height) {
                        setActiveSection(section);
                        break;
                    }
                }
            }
        };

        window.addEventListener('scroll', handleScroll);
        return () => window.removeEventListener('scroll', handleScroll);
    }, []);

    const navItems = [
        { label: 'Features', href: '#overview', id: 'overview' },
        { label: 'Pricing', href: '#pricing', id: 'pricing' },
    ];

    const handleNavClick = (e: React.MouseEvent<HTMLAnchorElement>, href: string) => {
        e.preventDefault();
        setMobileMenuOpen(false);
        const targetId = href.replace('#', '');
        const targetEl = document.getElementById(targetId);
        if (targetEl) {
            const offset = 80;
            const bodyRect = document.body.getBoundingClientRect().top;
            const elementRect = targetEl.getBoundingClientRect().top;
            const elementPosition = elementRect - bodyRect;
            const offsetPosition = elementPosition - offset;

            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });
        }
    };

    return (
        <div className="flex min-h-screen flex-col bg-slate-50/50 text-slate-800 selection:bg-indigo-500 selection:text-white antialiased font-sans">
            {/* Global soft grid background pattern overlay */}
            <div className="absolute inset-0 bg-[linear-gradient(to_right,#e2e8f080_1px,transparent_1px),linear-gradient(to_bottom,#e2e8f080_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,#000_70%,transparent_100%)] pointer-events-none z-0" />

            {/* Premium SaaS Sticky Navbar */}
            <header 
                className={`sticky top-0 z-50 w-full transition-all duration-300 ${
                    scrolled 
                        ? 'border-b border-slate-200/80 bg-white/95 backdrop-blur-xl py-3 shadow-sm shadow-slate-100' 
                        : 'border-b border-transparent bg-transparent py-5'
                }`}
            >
                <div className="mx-auto flex max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                    {/* Left Side: Logo & Product Name */}
                    <div className="flex items-center gap-8">
                        <Link
                            href="/"
                            className="flex items-center gap-2.5 group focus:outline-none"
                        >
                            <div className="relative flex h-9.5 w-9.5 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-500 via-indigo-650 to-purple-600 shadow-md shadow-indigo-500/10 group-hover:scale-105 transition-transform duration-300">
                                <ApplicationLogo className="h-5 w-5 text-white fill-current" />
                                <div className="absolute inset-0 rounded-lg bg-white/10 opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                            <span className="text-xl font-extrabold tracking-tight text-slate-900">
                                musoftware
                            </span>
                        </Link>

                        {/* Desktop Navigation Links */}
                        <nav className="hidden md:flex items-center gap-6">
                            {navItems.map((item) => (
                                <a
                                    key={item.href}
                                    href={item.href}
                                    onClick={(e) => handleNavClick(e, item.href)}
                                    className={`text-sm font-medium transition-all hover:text-slate-950 relative py-1 focus:outline-none ${
                                        activeSection === item.id 
                                            ? 'text-slate-900 font-semibold' 
                                            : 'text-slate-500'
                                    }`}
                                >
                                    {item.label}
                                    {activeSection === item.id && (
                                        <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full" />
                                    )}
                                </a>
                            ))}
                            <a 
                                href="https://laravel.com/docs" 
                                target="_blank" 
                                rel="noreferrer" 
                                className="text-sm font-medium text-slate-500 transition-all hover:text-slate-950 focus:outline-none"
                            >
                                Docs
                            </a>
                        </nav>
                    </div>

                    {/* Right-Side Authentication CTAs */}
                    <div className="hidden md:flex items-center gap-4">
                        {auth?.user ? (
                            <Link href="/dashboard">
                                <Button 
                                    className="bg-slate-900 hover:bg-slate-800 text-white rounded-lg shadow-sm font-semibold h-9 px-4 cursor-pointer"
                                >
                                    Dashboard
                                </Button>
                            </Link>
                        ) : (
                            <>
                                <Link 
                                    href="/login"
                                    className="text-sm font-medium text-slate-650 hover:text-slate-955 px-3 py-2 transition-all focus:outline-none"
                                >
                                    Login
                                </Link>
                                <Link href="/register">
                                    <Button 
                                        variant="outline"
                                        size="sm" 
                                        className="bg-white hover:bg-slate-50 text-slate-700 border-slate-200 rounded-lg shadow-sm font-semibold h-9 px-4 cursor-pointer"
                                    >
                                        Register
                                    </Button>
                                </Link>
                                <Link href="/register?trial=true">
                                    <Button 
                                        size="sm"
                                        className="bg-gradient-to-r from-indigo-500 via-indigo-650 to-purple-600 hover:opacity-95 text-white rounded-lg shadow-md shadow-indigo-500/10 border-0 flex items-center gap-1 group font-semibold h-9 px-4 cursor-pointer"
                                    >{__('general.start_free_trial')}<ArrowRight className="h-3.5 w-3.5 group-hover:translate-x-1 transition-transform" />
                                    </Button>
                                </Link>
                            </>
                        )}
                    </div>

                    {/* Mobile Menu Button Trigger */}
                    <button
                        onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                        className="flex md:hidden p-2 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 border border-transparent hover:border-slate-200 transition-all focus:outline-none cursor-pointer"
                        aria-label={__('general.toggle_menu')}
                    >
                        {mobileMenuOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
                    </button>
                </div>
            </header>

            {/* Custom Mobile Menu Drawer Overlay */}
            {mobileMenuOpen && (
                <div className="fixed inset-0 z-40 bg-white/98 backdrop-blur-xl flex flex-col md:hidden animate-fade-in text-slate-900">
                    {/* Header space alignment spacing */}
                    <div className="h-16 flex items-center justify-between px-4 sm:px-6 border-b border-slate-100">
                        <div className="flex items-center gap-2">
                            <div className="h-8.5 w-8.5 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                                <ApplicationLogo className="h-4 w-4 text-white fill-current" />
                            </div>
                            <span className="text-lg font-bold text-slate-900">musoftware</span>
                        </div>
                        <button
                            onClick={() => setMobileMenuOpen(false)}
                            className="p-2 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 border border-slate-200"
                        >
                            <X className="h-6 w-6" />
                        </button>
                    </div>

                    {/* Navigation Items */}
                    <div className="flex-1 flex flex-col justify-center px-6 space-y-6">
                        {navItems.map((item, idx) => (
                            <a
                                key={item.href}
                                href={item.href}
                                onClick={(e) => handleNavClick(e, item.href)}
                                className="text-2xl font-bold text-slate-650 hover:text-slate-950 transition-colors"
                                style={{ animationDelay: `${idx * 75}ms` }}
                            >
                                {item.label}
                            </a>
                        ))}
                        <a 
                            href="https://laravel.com/docs" 
                            target="_blank" 
                            rel="noreferrer" 
                            className="text-2xl font-bold text-slate-500 hover:text-slate-900 transition-colors"
                        >
                            Docs
                        </a>
                    </div>

                    {/* Mobile CTAs */}
                    <div className="p-6 border-t border-slate-100 bg-slate-50/50 space-y-4">
                        {auth?.user ? (
                            <Link href="/dashboard" className="block w-full">
                                <Button className="w-full bg-slate-900 hover:bg-slate-800 text-white cursor-pointer">{__('general.go_to_dashboard')}</Button>
                            </Link>
                        ) : (
                            <>
                                <Link href="/login" className="block w-full">
                                    <Button variant="ghost" className="w-full text-slate-650 hover:text-slate-950 cursor-pointer">
                                        Login
                                    </Button>
                                </Link>
                                <Link href="/register" className="block w-full">
                                    <Button className="w-full bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 cursor-pointer">
                                        Register
                                    </Button>
                                </Link>
                                <Link href="/register?trial=true" className="block w-full">
                                    <Button className="w-full bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg cursor-pointer">{__('general.start_free_trial')}</Button>
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            )}

            {/* Page Main Content Area */}
            <main className="flex flex-1 flex-col relative z-10">{children}</main>

            {/* Structured 5-Column Footer */}
            <footer className="relative border-t border-slate-200 bg-white py-16 text-slate-500 z-10">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="grid grid-cols-2 gap-8 md:grid-cols-6 lg:gap-12">
                        {/* Column 1: Brand Info (Spans 2 columns) */}
                        <div className="col-span-2 space-y-6">
                            <Link href="/" className="flex items-center gap-2 group">
                                <div className="h-8 w-8 rounded-lg bg-slate-900 flex items-center justify-center">
                                    <ApplicationLogo className="h-4 w-4 text-white fill-current" />
                                </div>
                                <span className="text-lg font-medium text-slate-900 transition-colors">
                                    musoftware
                                </span>
                            </Link>
                            <p className="text-sm leading-relaxed text-slate-500 max-w-xs font-light">{__('general.one_workspace_for_your_clients_billing_services_and_operations')}</p>
                            <div className="inline-flex items-center gap-2 px-3 py-1 text-xs text-slate-500 font-light border border-slate-200 rounded-full">
                                <div className="w-1.5 h-1.5 rounded-full bg-emerald-400" />{__('general.all_systems_operational')}</div>
                        </div>

                        {/* Column 2: Product */}
                        <div>
                            <h3 className="text-xs font-semibold uppercase tracking-wider text-slate-900">
                                Product
                            </h3>
                            <ul className="mt-4 space-y-3">
                                <li>
                                    <a href="#overview" onClick={(e) => handleNavClick(e, '#overview')} className="text-sm hover:text-slate-950 transition-colors">{__('general.platform_features')}</a>
                                </li>
                                <li>
                                    <a href="#marketplace" onClick={(e) => handleNavClick(e, '#marketplace')} className="text-sm hover:text-slate-950 transition-colors">Marketplace</a>
                                </li>
                                <li>
                                    <a href="#financials" onClick={(e) => handleNavClick(e, '#financials')} className="text-sm hover:text-slate-950 transition-colors">{__('general.financial_hub')}</a>
                                </li>
                                <li>
                                    <a href="#pricing" onClick={(e) => handleNavClick(e, '#pricing')} className="text-sm hover:text-slate-950 transition-colors">{__('general.pricing_plans')}</a>
                                </li>
                            </ul>
                        </div>

                        {/* Column 3: Features */}
                        <div>
                            <h3 className="text-xs font-semibold uppercase tracking-wider text-slate-900">
                                Features
                            </h3>
                            <ul className="mt-4 space-y-3">
                                <li>
                                    <a href="#financials" onClick={(e) => handleNavClick(e, '#financials')} className="text-sm hover:text-slate-950 transition-colors">{__('general.invoices_billing')}</a>
                                </li>
                                <li>
                                    <a href="#overview" onClick={(e) => handleNavClick(e, '#overview')} className="text-sm hover:text-slate-950 transition-colors">{__('general.client_portal')}</a>
                                </li>
                                <li>
                                    <a href="#workspace" onClick={(e) => handleNavClick(e, '#workspace')} className="text-sm hover:text-slate-950 transition-colors">{__('general.unified_workspace')}</a>
                                </li>
                                <li>
                                    <a href="#communication" onClick={(e) => handleNavClick(e, '#communication')} className="text-sm hover:text-slate-950 transition-colors">{__('general.timers_comm')}</a>
                                </li>
                            </ul>
                        </div>

                        {/* Column 4: Resources */}
                        <div>
                            <h3 className="text-xs font-semibold uppercase tracking-wider text-slate-900">
                                Resources
                            </h3>
                            <ul className="mt-4 space-y-3">
                                <li>
                                    <a href="https://laravel.com/docs" target="_blank" rel="noreferrer" className="text-sm hover:text-slate-950 transition-colors">Documentation</a>
                                </li>
                                <li>
                                    <a href="#integrations" onClick={(e) => handleNavClick(e, '#integrations')} className="text-sm hover:text-slate-950 transition-colors">{__('general.api_reference')}</a>
                                </li>
                                <li>
                                    <a href="#faq" onClick={(e) => handleNavClick(e, '#faq')} className="text-sm hover:text-slate-950 transition-colors">Changelog</a>
                                </li>
                                <li>
                                    <a href="mailto:hello@musoftwares.com" className="text-sm hover:text-slate-950 transition-colors">{__('general.contact_support')}</a>
                                </li>
                            </ul>
                        </div>

                        {/* Column 5: Legal */}
                        <div>
                            <h3 className="text-xs font-semibold uppercase tracking-wider text-slate-900">
                                Legal
                            </h3>
                            <ul className="mt-4 space-y-3">
                                <li>
                                    <Link href="/legal/privacy" className="text-sm hover:text-slate-950 transition-colors">{__('general.privacy_policy')}</Link>
                                </li>
                                <li>
                                    <Link href="/legal/terms" className="text-sm hover:text-slate-950 transition-colors">{__('general.terms_of_service')}</Link>
                                </li>
                                <li>
                                    <Link href="/legal/escrow" className="text-sm hover:text-slate-950 transition-colors">{__('general.escrow_protection')}</Link>
                                </li>
                                <li>
                                    <Link href="/legal/security" className="text-sm hover:text-slate-950 transition-colors">{__('general.security_audit')}</Link>
                                </li>
                            </ul>
                        </div>
                    </div>

                    {/* Bottom Divider & Footnotes */}
                    <div className="mt-16 border-t border-slate-100 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
                        <p className="text-sm font-light text-slate-400">
                            &copy; {new Date().getFullYear()} musoftware. All rights reserved.
                        </p>
                        <div className="flex gap-4 text-sm font-light text-slate-400">
                            <span>{__('general.platform_secured')}</span>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    );
}

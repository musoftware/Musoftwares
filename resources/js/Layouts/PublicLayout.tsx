import { Button } from '@/Components/ui/button';
import { Link } from '@inertiajs/react';
import { PropsWithChildren, useEffect, useState } from 'react';
import { Menu, X, ArrowRight, Sparkles, CheckCircle2 } from 'lucide-react';

interface PublicLayoutProps extends PropsWithChildren {
    auth?: {
        user: any;
    };
}

export default function PublicLayout({ children, auth }: PublicLayoutProps) {
    const [scrolled, setScrolled] = useState(false);
    const [activeSection, setActiveSection] = useState('hero');
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    useEffect(() => {
        const handleScroll = () => {
            setScrolled(window.scrollY > 20);

            // Scroll spy for active section highlight
            const sections = ['hero', 'overview', 'financials', 'marketplace', 'workspace', 'pricing', 'faq'];
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
        { label: 'Overview', href: '#overview', id: 'overview' },
        { label: 'ERP & Billing', href: '#financials', id: 'financials' },
        { label: 'Marketplace', href: '#marketplace', id: 'marketplace' },
        { label: 'Workspace', href: '#workspace', id: 'workspace' },
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
        <div className="flex min-h-screen flex-col bg-zinc-950 text-zinc-100 selection:bg-indigo-500 selection:text-white antialiased font-sans">
            {/* Global grid background pattern overlay */}
            <div className="absolute inset-0 bg-[linear-gradient(to_right,#1f293710_1px,transparent_1px),linear-gradient(to_bottom,#1f293710_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,#000_70%,transparent_100%)] pointer-events-none z-0" />

            {/* Premium SaaS Sticky Navbar */}
            <header 
                className={`sticky top-0 z-50 w-full transition-all duration-300 ${
                    scrolled 
                        ? 'border-b border-zinc-800/80 bg-zinc-950/80 backdrop-blur-xl py-3 shadow-lg shadow-zinc-950/40' 
                        : 'border-b border-transparent bg-transparent py-5'
                }`}
            >
                <div className="mx-auto flex max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                    {/* Logo & Product Name */}
                    <div className="flex items-center gap-8">
                        <Link
                            href="/"
                            className="flex items-center gap-2 group focus:outline-none"
                        >
                            <div className="relative flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition-transform duration-300">
                                <Sparkles className="h-4.5 w-4.5 text-white" />
                                <div className="absolute inset-0 rounded-lg bg-white/10 opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                            <span className="text-xl font-bold tracking-tight bg-gradient-to-r from-white via-zinc-100 to-zinc-400 bg-clip-text text-transparent">
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
                                    className={`text-sm font-medium transition-colors hover:text-zinc-100 relative py-1 focus:outline-none ${
                                        activeSection === item.id 
                                            ? 'text-zinc-100 font-semibold' 
                                            : 'text-zinc-400'
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
                                className="text-sm font-medium text-zinc-400 transition-colors hover:text-zinc-100 focus:outline-none"
                            >
                                Docs
                            </a>
                        </nav>
                    </div>

                    {/* Right-Side Authentication CTAs */}
                    <div className="hidden md:flex items-center gap-4">
                        <Link 
                            href="/login"
                            className="text-sm font-medium text-zinc-300 hover:text-white px-3 py-2 transition-colors focus:outline-none"
                        >
                            Login
                        </Link>
                        <Link href="/register">
                            <Button 
                                size="sm" 
                                className="bg-zinc-800 hover:bg-zinc-700 text-zinc-100 border border-zinc-700/80 rounded-lg shadow-sm"
                            >
                                Register
                            </Button>
                        </Link>
                        <Link href="/register?trial=true">
                            <Button 
                                size="sm"
                                className="bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white rounded-lg shadow-md shadow-indigo-500/10 border-0 flex items-center gap-1 group"
                            >
                                Start Free Trial
                                <ArrowRight className="h-3.5 w-3.5 group-hover:translate-x-1 transition-transform" />
                            </Button>
                        </Link>
                    </div>

                    {/* Mobile Menu Button Trigger */}
                    <button
                        onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                        className="flex md:hidden p-2 rounded-lg text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900 border border-transparent hover:border-zinc-800 transition-all focus:outline-none"
                        aria-label="Toggle Menu"
                    >
                        {mobileMenuOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
                    </button>
                </div>
            </header>

            {/* Custom Mobile Menu Drawer Overlay */}
            {mobileMenuOpen && (
                <div className="fixed inset-0 z-40 bg-zinc-950/98 backdrop-blur-xl flex flex-col md:hidden animate-fade-in">
                    {/* Header space alignment spacing */}
                    <div className="h-16 flex items-center justify-between px-4 sm:px-6">
                        <div className="flex items-center gap-2">
                            <div className="h-8 w-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                                <Sparkles className="h-4 w-4 text-white" />
                            </div>
                            <span className="text-lg font-bold text-white">musoftware</span>
                        </div>
                        <button
                            onClick={() => setMobileMenuOpen(false)}
                            className="p-2 rounded-lg text-zinc-400 hover:text-white hover:bg-zinc-900 border border-zinc-800"
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
                                className="text-2xl font-bold text-zinc-300 hover:text-white transition-colors"
                                style={{ animationDelay: `${idx * 75}ms` }}
                            >
                                {item.label}
                            </a>
                        ))}
                        <a 
                            href="https://laravel.com/docs" 
                            target="_blank" 
                            rel="noreferrer" 
                            className="text-2xl font-bold text-zinc-400 hover:text-white transition-colors"
                        >
                            Docs
                        </a>
                    </div>

                    {/* Mobile CTAs */}
                    <div className="p-6 border-t border-zinc-900 bg-zinc-900/30 space-y-4">
                        <Link href="/login" className="block w-full">
                            <Button variant="ghost" className="w-full text-zinc-300 hover:text-white">
                                Login
                            </Button>
                        </Link>
                        <Link href="/register" className="block w-full">
                            <Button className="w-full bg-zinc-800 hover:bg-zinc-700 text-white">
                                Register
                            </Button>
                        </Link>
                        <Link href="/register?trial=true" className="block w-full">
                            <Button className="w-full bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg">
                                Start Free Trial
                            </Button>
                        </Link>
                    </div>
                </div>
            )}

            {/* Page Main Content Area */}
            <main className="flex flex-1 flex-col relative z-10">{children}</main>

            {/* Enterprise Structured 5-Column Footer */}
            <footer className="relative border-t border-zinc-900 bg-zinc-950 py-16 text-zinc-400 z-10">
                <div className="absolute inset-0 bg-gradient-to-b from-transparent to-zinc-950 pointer-events-none" />
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="grid grid-cols-2 gap-8 md:grid-cols-5 lg:gap-12">
                        {/* Column 1: Brand Info */}
                        <div className="col-span-2 space-y-6">
                            <Link href="/" className="flex items-center gap-2 group">
                                <div className="h-8 w-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-md shadow-indigo-500/10">
                                    <Sparkles className="h-4 w-4 text-white" />
                                </div>
                                <span className="text-lg font-bold text-white group-hover:text-zinc-200 transition-colors">
                                    musoftware
                                </span>
                            </Link>
                            <p className="text-sm leading-relaxed text-zinc-400 max-w-xs">
                                The all-in-one business engine. Unifying deep ERP, freelance boards, contract billing, wallet automation, and service marketplaces into a single operational workspace.
                            </p>
                            {/* Real-time Status Indicator Badge */}
                            <div className="inline-flex items-center gap-2 bg-emerald-950/40 border border-emerald-900/60 rounded-full px-3 py-1 text-xs text-emerald-400 font-medium">
                                <span className="relative flex h-2 w-2">
                                    <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                                All systems operational
                            </div>
                        </div>

                        {/* Column 2: Product */}
                        <div>
                            <h3 className="text-xs font-semibold uppercase tracking-wider text-white">
                                Product
                            </h3>
                            <ul className="mt-4 space-y-3">
                                <li>
                                    <a href="#overview" onClick={(e) => handleNavClick(e, '#overview')} className="text-sm hover:text-white transition-colors">Features</a>
                                </li>
                                <li>
                                    <a href="#financials" onClick={(e) => handleNavClick(e, '#financials')} className="text-sm hover:text-white transition-colors">Financial Flow</a>
                                </li>
                                <li>
                                    <a href="#marketplace" onClick={(e) => handleNavClick(e, '#marketplace')} className="text-sm hover:text-white transition-colors">Marketplace</a>
                                </li>
                                <li>
                                    <a href="#workspace" onClick={(e) => handleNavClick(e, '#workspace')} className="text-sm hover:text-white transition-colors">Workspace</a>
                                </li>
                                <li>
                                    <a href="#pricing" onClick={(e) => handleNavClick(e, '#pricing')} className="text-sm hover:text-white transition-colors">Pricing Options</a>
                                </li>
                            </ul>
                        </div>

                        {/* Column 3: Resources */}
                        <div>
                            <h3 className="text-xs font-semibold uppercase tracking-wider text-white">
                                Resources
                            </h3>
                            <ul className="mt-4 space-y-3">
                                <li>
                                    <a href="https://laravel.com/docs" target="_blank" rel="noreferrer" className="text-sm hover:text-white transition-colors">Documentation</a>
                                </li>
                                <li>
                                    <a href="#" className="text-sm hover:text-white transition-colors">API Reference</a>
                                </li>
                                <li>
                                    <a href="#" className="text-sm hover:text-white transition-colors">Changelog</a>
                                </li>
                                <li>
                                    <a href="#" className="text-sm hover:text-white transition-colors">Community Forum</a>
                                </li>
                                <li>
                                    <a href="#" className="text-sm hover:text-white transition-colors">Support Desk</a>
                                </li>
                            </ul>
                        </div>

                        {/* Column 4: Legal & Safety */}
                        <div>
                            <h3 className="text-xs font-semibold uppercase tracking-wider text-white">
                                Company & Legal
                            </h3>
                            <ul className="mt-4 space-y-3">
                                <li>
                                    <a href="#" className="text-sm hover:text-white transition-colors">About Us</a>
                                </li>
                                <li>
                                    <a href="#" className="text-sm hover:text-white transition-colors">Privacy Policy</a>
                                </li>
                                <li>
                                    <a href="#" className="text-sm hover:text-white transition-colors">Terms of Service</a>
                                </li>
                                <li>
                                    <a href="#" className="text-sm hover:text-white transition-colors">Escrow Protection</a>
                                </li>
                                <li>
                                    <a href="#" className="text-sm hover:text-white transition-colors">Contact Relations</a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    {/* Bottom: Divider & Copyright */}
                    <div className="mt-12 border-t border-zinc-900 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
                        <p className="text-xs text-zinc-500">
                            &copy; {new Date().getFullYear()} musoftware. Engineered for hyper-growth enterprises, modern freelancers, and client networks. All rights reserved.
                        </p>
                        <div className="flex gap-4 text-xs text-zinc-500">
                            <span>Vite React Inertia Laravel v11</span>
                            <span>•</span>
                            <span>TLS Escrow Secured</span>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    );
}

import { __ } from '@/lib/i18n';
import { Button } from '@/Components/ui/button';
import { Link, usePage, useForm } from '@inertiajs/react';
import { PropsWithChildren, useEffect, useState } from 'react';
import { Menu, X, ArrowRight, ChevronDown, Monitor, Box, Server, Activity, Phone, MessageCircle, Globe, MapPin, Send } from 'lucide-react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter, DialogTrigger } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';

interface PublicLayoutProps extends PropsWithChildren {
    auth?: {
        user: any;
    };
}

export default function PublicLayout({ children, auth: propAuth }: PublicLayoutProps) {
    const { auth: pageAuth } = usePage().props as any;
    const auth = propAuth || pageAuth;
    const [scrolled, setScrolled] = useState(false);
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const [activeDropdown, setActiveDropdown] = useState<string | null>(null);
    const [isGuestTicketOpen, setIsGuestTicketOpen] = useState(false);

    const { data, setData, post, processing, reset } = useForm({
        name: '',
        email: '',
        mobile: '',
        body: '',
    });

    const submitGuestTicket = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('guest-tickets.submit'), {
            onSuccess: () => {
                reset();
                setIsGuestTicketOpen(false);
            }
        });
    };

    useEffect(() => {
        const handleScroll = () => {
            setScrolled(window.scrollY > 20);
        };
        const handleOpenTicket = () => setIsGuestTicketOpen(true);
        
        window.addEventListener('scroll', handleScroll);
        window.addEventListener('open-guest-ticket', handleOpenTicket);
        return () => {
            window.removeEventListener('scroll', handleScroll);
            window.removeEventListener('open-guest-ticket', handleOpenTicket);
        };
    }, []);

    type NavItem = {
        id: string;
        label: string;
        href: string;
        items: {
            title: string;
            desc: string;
            href: string;
            icon?: React.ReactNode;
        }[];
    };

    const { website_services } = usePage().props as any;

    const servicesItems = (website_services || []).map((s: any) => ({
        title: s.title,
        desc: s.subtitle || '',
        href: '#',
        icon: s.image_path ? <img src={`/storage/${s.image_path}`} alt={s.title} className="w-full h-full object-cover rounded-lg" /> : <Monitor className="w-5 h-5 text-slate-400" />
    }));

    const navItems: NavItem[] = [
        {
            id: 'services',
            label: 'Services',
            href: '#',
            items: servicesItems
        },
        {
            id: 'solutions',
            label: 'Solutions',
            href: '/solutions',
            items: [
                { title: 'Healthcare', desc: 'Secure medical operations', href: '/solutions/healthcare' },
                { title: 'Education', desc: 'Digital campus platforms', href: '/solutions/education' },
                { title: 'E-commerce', desc: 'High-volume retail systems', href: '/solutions/ecommerce' },
                { title: 'Real Estate', desc: 'Property management infrastructure', href: '/solutions/real-estate' },
                { title: 'Finance', desc: 'Compliant financial platforms', href: '/solutions/finance' },
            ]
        },
        {
            id: 'pricing',
            label: 'Pricing',
            href: '/pricing',
            items: []
        },
        {
            id: 'company',
            label: 'Company',
            href: '/company',
            items: [
                { title: 'About Us', desc: 'Our enterprise vision', href: '/company/about' },
                { title: 'Careers', desc: 'Join our engineering team', href: '/company/careers' },
                { title: 'Contact', desc: 'Get in touch with sales', href: '/company/contact' },
            ]
        }
    ];

    return (
        <div className="flex min-h-screen flex-col bg-white text-slate-900 antialiased font-sans">
            {/* Enterprise Header */}
            <header
                className={`sticky top-0 w-full transition-all duration-300 ${mobileMenuOpen ? 'z-40' : 'z-50'
                    } ${scrolled
                        ? 'border-b border-slate-200 bg-white/95 backdrop-blur-xl py-3 shadow-sm'
                        : 'border-b border-transparent bg-transparent py-5'
                    }`}
                onMouseLeave={() => setActiveDropdown(null)}
            >
                <div className="mx-auto flex max-w-[90rem] items-center justify-between px-4 sm:px-6 lg:px-8">
                    {/* Left Side: Logo */}
                    <div className="flex items-center gap-10">
                        <Link href="/" className="flex items-center gap-2.5 group focus:outline-none">
                            <div className="relative flex h-10 w-10 items-center justify-center rounded-lg bg-slate-900 shadow-md group-hover:scale-105 transition-transform duration-300">
                                <ApplicationLogo className="h-5 w-5 text-white fill-current" />
                            </div>
                            <span className="text-xl font-extrabold tracking-tight text-slate-900">
                                musoftware
                            </span>
                        </Link>

                        {/* Desktop Navigation (Mega Menu triggers) */}
                        <nav className="hidden lg:flex items-center gap-1">
                            {navItems.map((item) => (
                                <div key={item.id} className="relative">
                                    <Link
                                        href={item.href}
                                        onMouseEnter={() => item.items.length > 0 && setActiveDropdown(item.id)}
                                        className="flex items-center gap-1 px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-50 rounded-full transition-all"
                                    >
                                        {item.label}
                                        {item.items.length > 0 && (
                                            <ChevronDown className={`w-4 h-4 transition-transform ${activeDropdown === item.id ? 'rotate-180' : ''}`} />
                                        )}
                                    </Link>
                                </div>
                            ))}
                        </nav>
                    </div>

                    {/* Right-Side Authentication CTAs */}
                    <div className="hidden lg:flex items-center gap-4">
                        {auth?.user ? (
                            <Link href="/dashboard">
                                <Button className="bg-slate-900 hover:bg-slate-800 text-white rounded-full font-semibold h-10 px-6">
                                    Dashboard
                                </Button>
                            </Link>
                        ) : (
                            <>
                                <Link href="/login" className="text-sm font-semibold text-slate-600 hover:text-slate-900 px-3 py-2 transition-all">
                                    Client Login
                                </Link>
                                <Button onClick={() => setIsGuestTicketOpen(true)} className="bg-slate-900 hover:bg-slate-800 text-white rounded-full font-semibold h-10 px-6 flex items-center gap-2">
                                    {__('general.submit_guest_ticket') || 'Submit Guest Ticket'} <Send className="h-4 w-4 ml-1" />
                                </Button>
                            </>
                        )}
                    </div>

                    {/* Mobile Menu Button Trigger */}
                    <button
                        onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                        className="flex lg:hidden p-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100"
                    >
                        {mobileMenuOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
                    </button>
                </div>

                {/* Desktop Mega Menu Dropdowns */}
                {activeDropdown && (
                    <div
                        className="hidden lg:block absolute top-full left-0 w-full bg-white border-b border-slate-200 shadow-xl shadow-slate-200/20 animate-in fade-in slide-in-from-top-2 duration-200"
                        onMouseEnter={() => setActiveDropdown(activeDropdown)}
                        onMouseLeave={() => setActiveDropdown(null)}
                    >
                        <div className="max-w-[90rem] mx-auto px-4 sm:px-6 lg:px-8 py-10">
                            <div className="grid grid-cols-4 gap-8">
                                {navItems.find(n => n.id === activeDropdown)?.items.map((sub, idx) => (
                                    <Link key={idx} href={sub.href || '#'} className="flex gap-4 p-4 rounded-xl hover:bg-slate-50 transition-colors group">
                                        {sub.icon && (
                                            <div className="w-10 h-10 rounded-lg bg-white shadow-sm border border-slate-100 flex items-center justify-center shrink-0">
                                                {sub.icon}
                                            </div>
                                        )}
                                        <div>
                                            <h4 className="text-sm font-bold text-slate-900 mb-1 group-hover:text-slate-700 transition-colors">{sub.title}</h4>
                                            <p className="text-sm text-slate-500 font-light">{sub.desc}</p>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        </div>
                    </div>
                )}
            </header>

            {/* Custom Mobile Menu Drawer Overlay */}
            {mobileMenuOpen && (
                <div className="fixed inset-0 z-50 bg-white flex flex-col lg:hidden animate-in fade-in text-slate-900 overflow-y-auto">
                    <div className="h-20 flex items-center justify-between px-4 sm:px-6 border-b border-slate-100">
                        <div className="flex items-center gap-2">
                            <div className="h-9 w-9 rounded-lg bg-slate-900 flex items-center justify-center">
                                <ApplicationLogo className="h-4 w-4 text-white fill-current" />
                            </div>
                            <span className="text-lg font-extrabold text-slate-900">musoftware</span>
                        </div>
                        <button onClick={() => setMobileMenuOpen(false)} className="p-2 text-slate-500">
                            <X className="h-6 w-6" />
                        </button>
                    </div>

                    <div className="flex-1 px-6 py-8 space-y-8">
                        {navItems.map((item) => (
                            <div key={item.id} className="space-y-4">
                                <Link href={item.href} onClick={() => setMobileMenuOpen(false)}>
                                    <h3 className="text-sm font-bold uppercase tracking-wider text-slate-900 hover:text-slate-600 transition-colors">{item.label}</h3>
                                </Link>
                                {item.items.length > 0 && (
                                    <div className="grid grid-cols-1 gap-4 mt-2">
                                        {item.items.map((sub, i) => (
                                            <Link key={i} href={sub.href || item.href} className="text-base font-semibold text-slate-600 hover:text-slate-900">
                                                {sub.title}
                                            </Link>
                                        ))}
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>

                    <div className="p-6 border-t border-slate-100 bg-slate-50 space-y-4">
                        {auth?.user ? (
                            <Link href="/dashboard" className="block w-full">
                                <Button className="w-full bg-slate-900 text-white rounded-full h-12">Dashboard</Button>
                            </Link>
                        ) : (
                            <>
                                <Link href="/login" className="block w-full">
                                    <Button variant="outline" className="w-full rounded-full h-12">Client Login</Button>
                                </Link>
                                <Button onClick={() => setIsGuestTicketOpen(true)} className="w-full bg-slate-900 text-white rounded-full h-12 flex items-center justify-center gap-2">
                                    {__('general.submit_guest_ticket') || 'Submit Guest Ticket'} <Send className="h-4 w-4 ml-1" />
                                </Button>
                            </>
                        )}
                    </div>
                </div>
            )}

            {/* Page Main Content Area */}
            <main className="flex flex-1 flex-col relative z-10">{children}</main>

            {/* Structured Enterprise Footer */}
            <footer className="border-t border-slate-200 bg-slate-50 pt-20 pb-12 text-slate-600">
                <div className="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-2 md:grid-cols-6 lg:grid-cols-7 gap-8 lg:gap-12 mb-16">
                        {/* Brand Column */}
                        <div className="col-span-2 lg:col-span-2 space-y-6">
                            <Link href="/" className="flex items-center gap-2 group">
                                <div className="h-8 w-8 rounded-lg bg-slate-900 flex items-center justify-center">
                                    <ApplicationLogo className="h-4 w-4 text-white fill-current" />
                                </div>
                                <span className="text-xl font-extrabold text-slate-900">musoftware</span>
                            </Link>
                            <p className="text-sm leading-relaxed text-slate-500 font-light pr-4">
                                We build software infrastructure and systems for growing businesses.
                            </p>

                            <div className="space-y-3 pt-4 border-t border-slate-200/60">
                                <a href="tel:201015218548" className="flex items-center gap-3 text-sm text-slate-600 hover:text-slate-900 transition-colors group">
                                    <div className="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center group-hover:bg-slate-200 transition-colors shrink-0">
                                        <Phone className="h-4 w-4" />
                                    </div>
                                    <div className="flex flex-col">
                                        <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider">{__('general.contact_mobile')}</span>
                                        <span className="font-medium" dir="ltr">+20 101 521 8548</span>
                                    </div>
                                </a>

                                <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" className="flex items-center gap-3 text-sm text-slate-600 hover:text-slate-900 transition-colors group">
                                    <div className="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center group-hover:bg-[#25D366]/20 group-hover:text-[#25D366] transition-colors shrink-0">
                                        <MessageCircle className="h-4 w-4" />
                                    </div>
                                    <div className="flex flex-col">
                                        <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider">{__('general.contact_whatsapp')}</span>
                                        <span className="font-medium" dir="ltr">+201015218548</span>
                                    </div>
                                </a>

                                <a href="https://www.facebook.com/musoftwares.com.page/" target="_blank" rel="noopener noreferrer" className="flex items-center gap-3 text-sm text-slate-600 hover:text-slate-900 transition-colors group">
                                    <div className="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center group-hover:bg-[#1877F2]/20 group-hover:text-[#1877F2] transition-colors shrink-0">
                                        <Globe className="h-4 w-4" />
                                    </div>
                                    <div className="flex flex-col">
                                        <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider">{__('general.contact_facebook')}</span>
                                        <span className="font-medium">musoftware</span>
                                    </div>
                                </a>

                                <div className="flex items-center gap-3 text-sm text-slate-600">
                                    <div className="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center shrink-0">
                                        <MapPin className="h-4 w-4" />
                                    </div>
                                    <div className="flex flex-col">
                                        <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider">{__('general.contact_location')}</span>
                                        <span className="font-medium">Suez, Egypt</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Solutions */}
                        <div>
                            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-900 mb-6">Solutions</h3>
                            <ul className="space-y-4">
                                <li><Link href="/solutions" className="text-sm hover:text-slate-900 transition-colors">Healthcare</Link></li>
                                <li><Link href="/solutions" className="text-sm hover:text-slate-900 transition-colors">Real Estate</Link></li>
                                <li><Link href="/solutions" className="text-sm hover:text-slate-900 transition-colors">Education</Link></li>
                                <li><Link href="/solutions" className="text-sm hover:text-slate-900 transition-colors">E-commerce</Link></li>
                                <li><Link href="/solutions" className="text-sm hover:text-slate-900 transition-colors">Finance</Link></li>
                            </ul>
                        </div>

                        {/* Platforms */}
                        <div>
                            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-900 mb-6">Platforms</h3>
                            <ul className="space-y-4">
                                <li><Link href="/platforms" className="text-sm hover:text-slate-900 transition-colors">MU CRM</Link></li>
                                <li><Link href="/platforms" className="text-sm hover:text-slate-900 transition-colors">MU ERP</Link></li>
                                <li><Link href="/platforms" className="text-sm hover:text-slate-900 transition-colors">MU Cloud</Link></li>
                            </ul>
                        </div>

                        {/* Company */}
                        <div>
                            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-900 mb-6">Company</h3>
                            <ul className="space-y-4">
                                <li><Link href="/company" className="text-sm hover:text-slate-900 transition-colors">About Us</Link></li>
                                <li><Link href="/pricing" className="text-sm hover:text-slate-900 transition-colors">Pricing</Link></li>
                                <li><Link href="/company" className="text-sm hover:text-slate-900 transition-colors">Contact</Link></li>
                            </ul>
                        </div>

                        {/* Resources */}
                        <div>
                            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-900 mb-6">Resources</h3>
                            <ul className="space-y-4">
                                <li><Link href="/solutions" className="text-sm hover:text-slate-900 transition-colors">Case Studies</Link></li>
                                <li><a href="mailto:admin@musoftwares.com" className="text-sm hover:text-slate-900 transition-colors">Documentation</a></li>
                                <li><a href="mailto:admin@musoftwares.com" className="text-sm hover:text-slate-900 transition-colors">API Reference</a></li>
                                <li><Link href="/company" className="text-sm hover:text-slate-900 transition-colors">Security</Link></li>
                            </ul>
                        </div>
                    </div>

                    <div className="border-t border-slate-200 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
                        <p className="text-sm font-light text-slate-500">
                            &copy; {new Date().getFullYear()} musoftware. All rights reserved.
                        </p>
                        <div className="flex gap-6 text-sm font-light text-slate-500">
                            <Link href="/privacy-policy" className="hover:text-slate-900">Privacy Policy</Link>
                            <Link href="/terms-of-service" className="hover:text-slate-900">Terms of Service</Link>
                            <Link href="/cookie-policy" className="hover:text-slate-900">Cookie Policy</Link>
                        </div>
                    </div>
                </div>
            </footer>

            {/* Guest Ticket Dialog */}
            <Dialog open={isGuestTicketOpen} onOpenChange={setIsGuestTicketOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{__('general.submit_guest_ticket') || 'Submit Guest Ticket'}</DialogTitle>
                        <DialogDescription>
                            Please fill out the form below and we will get back to you shortly.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submitGuestTicket} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="name">{__('general.name') || 'Name'}</Label>
                            <Input id="name" required value={data.name} onChange={e => setData('name', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="email">{__('general.email') || 'Email'}</Label>
                            <Input id="email" type="email" required value={data.email} onChange={e => setData('email', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="mobile" className="flex items-center gap-2">
                                {__('general.mobile') || 'Mobile'} <MessageCircle className="h-4 w-4 text-[#25D366]" />
                            </Label>
                            <Input id="mobile" required value={data.mobile} onChange={e => setData('mobile', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="body">{__('general.message') || 'Message'}</Label>
                            <Textarea id="body" required value={data.body} onChange={e => setData('body', e.target.value)} rows={4} />
                        </div>
                        <DialogFooter className="pt-4">
                            <Button type="button" variant="outline" onClick={() => setIsGuestTicketOpen(false)}>
                                {__('general.cancel') || 'Cancel'}
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {__('general.submit') || 'Submit'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
}

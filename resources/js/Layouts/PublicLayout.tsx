import { __ } from '@/lib/i18n';
import { Button } from '@/Components/ui/button';
import { Link, usePage, useForm, Head } from '@inertiajs/react';
import SafeLink from '@/Components/SafeLink';
import { PropsWithChildren, useEffect, useState } from 'react';
import { Menu, X, ArrowRight, ChevronDown, Monitor, Box, Server, Activity, Phone, MessageCircle, Globe, MapPin, Send, Briefcase, Mail, Sparkles, Loader2 } from 'lucide-react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter, DialogTrigger } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';
import { useToast } from '@/Components/ui/use-toast';
import { Toaster } from '@/Components/ui/toaster';
import FloatingWhatsAppButton from '@/Components/FloatingWhatsAppButton';
import MobileBottomActionBar from '@/Components/Public/MobileBottomActionBar';

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
    const [newsletterEmail, setNewsletterEmail] = useState('');
    const [newsletterLoading, setNewsletterLoading] = useState(false);
    const { toast } = useToast();

    const handleNewsletterSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!newsletterEmail || !newsletterEmail.includes('@')) {
            toast({
                title: 'Invalid Email',
                description: 'Please enter a valid email address.',
                variant: 'destructive',
            });
            return;
        }

        setNewsletterLoading(true);
        try {
            const token = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
            const res = await fetch(route('newsletter.subscribe'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ email: newsletterEmail }),
            });

            const json = await res.json();
            if (res.ok && json.success) {
                setNewsletterEmail('');
                toast({
                    title: 'Subscribed Successfully!',
                    description: json.message || 'Thank you for subscribing to our studio insights.',
                });
            } else {
                toast({
                    title: 'Subscription Failed',
                    description: json.message || 'Could not subscribe. Please try again.',
                    variant: 'destructive',
                });
            }
        } catch (error) {
            toast({
                title: 'Error',
                description: 'An unexpected error occurred. Please try again later.',
                variant: 'destructive',
            });
        } finally {
            setNewsletterLoading(false);
        }
    };

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
                toast({
                    title: __('general.success') || 'Success',
                    description: __('general.ticket_submitted_success') || 'Your brief has been submitted successfully. An architect will reach out within 24 hours.',
                });
            }
        });
    };

    useEffect(() => {
        if (window.self !== window.top) {
            const targetUrl = (window.location.pathname && window.location.pathname !== 'blank')
                ? window.location.pathname
                : '/dashboard';
            const isSandboxed = !window.location.href || window.location.href.startsWith('about:') || window.origin === 'null';
            if (isSandboxed) {
                try {
                    window.parent.postMessage({ type: 'FORCE_TOP_REDIRECT', url: targetUrl }, '*');
                } catch { /* empty */ }
            } else {
                try {
                    if (window.top) window.top.location.href = targetUrl;
                } catch {
                    try {
                        window.parent.postMessage({ type: 'FORCE_TOP_REDIRECT', url: targetUrl }, '*');
                    } catch { /* empty */ }
                }
            }
        }

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

    const navItems: NavItem[] = [
        {
            id: 'about',
            label: __('general.about') || 'ABOUT',
            href: '/about/mahmoud-amin',
            items: []
        },
        {
            id: 'work',
            label: __('general.portfolio') || 'WORK',
            href: '/portfolio',
            items: []
        },
        {
            id: 'expertise',
            label: __('general.solutions') || 'EXPERTISE',
            href: '/platforms/erp',
            items: [
                { title: 'Enterprise ERP Systems', desc: 'Modular double-entry ledger & operations', href: '/platforms/erp' },
                { title: 'WhatsApp Cloud API', desc: 'Verified Meta Graph automated pipeline', href: '/platforms/crm' },
                { title: 'Meta API Interceptors', desc: 'Omnichannel CRM synchronization', href: '/platforms/cloud' },
            ]
        },
        {
            id: 'insights',
            label: __('general.documentation') || 'INSIGHTS',
            href: '/compare/laravel-vs-nodejs',
            items: []
        },
        {
            id: 'pricing',
            label: __('general.pricing') || 'PRICING',
            href: '/pricing',
            items: []
        }
    ];

    return (
        <div className="flex min-h-screen flex-col bg-[#111111] text-[#E5E5E5] antialiased font-sans selection:bg-[#748660] selection:text-white">

            {/* Top Navigation Header (Matching Exact Reference Header) */}
            <header
                className={`sticky top-0 w-full transition-all duration-200 border-b border-[#222222] bg-[#111111]/95 backdrop-blur-md ${scrolled ? 'py-3.5 shadow-xl' : 'py-5'
                    } z-50`}
                onMouseLeave={() => setActiveDropdown(null)}
            >
                <div className="mx-auto flex max-w-[1400px] items-center justify-between px-6 sm:px-10">

                    {/* Left: Stark Brand Monogram */}
                    <div className="flex items-center gap-8">
                        <SafeLink href="/" className="flex items-center space-x-3 group focus:outline-none">
                            <span className="text-2xl sm:text-3xl font-black tracking-tighter text-white font-sans">
                                MUSOFT
                            </span>
                            <span className="h-4 w-[1px] bg-zinc-700 hidden sm:inline-block"></span>
                            <span className="text-[11px] font-mono tracking-widest rtl:tracking-normal uppercase text-zinc-400 hidden sm:inline-block">
                                STUDIO
                            </span>
                        </SafeLink>

                        {/* Desktop Navigation (Uppercase tracking-[0.2em]) */}
                        <nav className="hidden lg:flex items-center space-x-8 text-[11px] font-bold tracking-[0.2em] rtl:tracking-normal rtl:space-x-reverse uppercase text-zinc-300">
                            {navItems.map((item) => (
                                <div key={item.id} className="relative">
                                    <SafeLink
                                        href={item.href}
                                        onMouseEnter={() => item.items.length > 0 && setActiveDropdown(item.id)}
                                        className="flex items-center gap-1 hover:text-white transition-colors"
                                    >
                                        {item.label}
                                        {item.items.length > 0 && <ChevronDown className="h-3 w-3 opacity-60" />}
                                    </SafeLink>

                                    {/* Dropdown Menu */}
                                    {item.items.length > 0 && activeDropdown === item.id && (
                                        <div
                                            onMouseEnter={() => setActiveDropdown(item.id)}
                                            onMouseLeave={() => setActiveDropdown(null)}
                                            className="absolute left-0 rtl:left-auto rtl:right-0 top-full mt-3 w-80 bg-[#161616] border border-[#2B2B2B] p-2 shadow-2xl animate-in fade-in slide-in-from-top-2 duration-150 z-50"
                                        >
                                            {item.items.map((subItem) => (
                                                <SafeLink
                                                    key={subItem.href}
                                                    href={subItem.href}
                                                    className="flex flex-col p-3 hover:bg-[#222222] transition-colors group"
                                                >
                                                    <span className="text-xs font-bold text-white group-hover:text-white">
                                                        {subItem.title}
                                                    </span>
                                                    <span className="text-[11px] text-zinc-400 mt-0.5 font-sans">
                                                        {subItem.desc}
                                                    </span>
                                                </SafeLink>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            ))}
                        </nav>
                    </div>

                    {/* Right: Auth / Action Buttons */}
                    <div className="flex items-center gap-4">
                        {auth?.user ? (
                            <SafeLink href={route('dashboard')}>
                                <button className="px-5 py-2 rounded-none border border-white text-white hover:bg-white hover:text-black font-mono text-xs tracking-wider rtl:tracking-normal uppercase transition-all">
                                    {__('general.console') || 'CONSOLE'} ➔
                                </button>
                            </SafeLink>
                        ) : (
                            <div className="flex items-center space-x-4 rtl:space-x-reverse">
                                <SafeLink
                                    href={route('login')}
                                    className="text-[11px] font-bold tracking-widest rtl:tracking-normal uppercase text-zinc-400 hover:text-white transition-colors"
                                >
                                    {__('general.sign_in') || 'SIGN IN'}
                                </SafeLink>
                                <SafeLink href="/start-project">
                                    <button className="px-5 py-2 rounded-none bg-white text-black hover:bg-zinc-200 font-bold text-[11px] tracking-widest rtl:tracking-normal uppercase transition-all">
                                        {__('general.start_a_project') || 'START A PROJECT'} ➔
                                    </button>
                                </SafeLink>
                            </div>
                        )}

                        {/* Mobile Menu Toggle */}
                        <button
                            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                            className="lg:hidden p-2 text-zinc-400 hover:text-white transition-colors"
                        >
                            {mobileMenuOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
                        </button>
                    </div>
                </div>

                {/* Mobile Drawer */}
                {mobileMenuOpen && (
                    <div className="lg:hidden border-b border-[#222222] bg-[#111111] px-6 py-6 space-y-4">
                        <div className="flex flex-col space-y-3 font-mono text-xs uppercase tracking-wider rtl:tracking-normal text-zinc-300">
                            {navItems.map((item) => (
                                <SafeLink
                                    key={item.id}
                                    href={item.href}
                                    onClick={() => setMobileMenuOpen(false)}
                                    className="py-2 hover:text-white border-b border-[#1E1E1E]"
                                >
                                    {item.label}
                                </SafeLink>
                            ))}
                            <SafeLink
                                href="/start-project"
                                onClick={() => setMobileMenuOpen(false)}
                                className="py-2 text-[#748660] font-bold border-b border-[#1E1E1E]"
                            >
                                START SYSTEM WIZARD ➔
                            </SafeLink>
                        </div>
                    </div>
                )}
            </header>

            {/* Page Main Content */}
            <main className="flex-1 w-full">
                {children}
            </main>

            {/* Structured Minimalist 4-Column Footer */}
            <footer className="w-full bg-[#0A0A0A] border-t border-[#222222] py-20 px-6 sm:px-12 text-xs text-zinc-400">
                <div className="max-w-[1400px] mx-auto flex flex-col lg:flex-row justify-between gap-16">

                    {/* 4 Columns */}
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-10 lg:gap-16 flex-1">
                        <div className="space-y-4">
                            <div className="text-white font-bold tracking-wider rtl:tracking-normal font-sans">{__('general.contact_us') || 'Contact Us'}</div>
                            <ul className="space-y-2.5 font-sans text-xs">
                                <li><a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" className="hover:text-white transition-colors">{__('general.whatsapp_direct') || 'WhatsApp Direct'}</a></li>
                                <li><a href="mailto:admin@musoftwares.com" className="hover:text-white transition-colors">{__('general.email_studio') || 'Email Studio'}</a></li>
                                <li><SafeLink href="/start-project" className="hover:text-white transition-colors text-[#748660] font-bold">{__('general.start_project_wizard') || 'System Scoping Wizard ➔'}</SafeLink></li>
                            </ul>
                        </div>

                        <div className="space-y-4">
                            <div className="text-white font-bold tracking-wider rtl:tracking-normal font-sans">{__('general.solutions') || 'Solutions'}</div>
                            <ul className="space-y-2.5 font-sans text-xs">
                                <li><SafeLink href="/platforms/erp" className="hover:text-white transition-colors">Enterprise ERP</SafeLink></li>
                                <li><SafeLink href="/platforms/crm" className="hover:text-white transition-colors">WhatsApp Cloud API</SafeLink></li>
                                <li><SafeLink href="/platforms/cloud" className="hover:text-white transition-colors">Meta Graph Suite</SafeLink></li>
                                <li><SafeLink href="/start-project" className="hover:text-white transition-colors">System Architecture Wizard</SafeLink></li>
                            </ul>
                        </div>

                        <div className="space-y-4">
                            <div className="text-white font-bold tracking-wider rtl:tracking-normal font-sans">{__('general.press_center') || 'Press Center'}</div>
                            <ul className="space-y-2.5 font-sans text-xs">
                                <li><SafeLink href="/estimator" className="hover:text-white transition-colors">{__('general.estimator') || 'Architecture Estimator'}</SafeLink></li>
                                <li><SafeLink href="/portfolio" className="hover:text-white transition-colors">{__('general.portfolio') || 'Case Studies Archive'}</SafeLink></li>
                                <li><SafeLink href="/about/mahmoud-amin" className="hover:text-white transition-colors">{__('general.leadership_bio') || 'Leadership Bio (Mahmoud Amin)'}</SafeLink></li>
                                <li><SafeLink href="/compare/laravel-vs-nodejs" className="hover:text-white transition-colors">Tech Benchmarks (Laravel vs Node.js)</SafeLink></li>
                            </ul>
                        </div>

                        <div className="space-y-4">
                            <div className="text-white font-bold tracking-wider rtl:tracking-normal font-sans">{__('general.legal') || 'Legal & Privacy'}</div>
                            <ul className="space-y-2.5 font-sans text-xs">
                                <li><SafeLink href="/privacy-policy" className="hover:text-white transition-colors">{__('general.privacy_policy') || 'Privacy Policy'}</SafeLink></li>
                                <li><SafeLink href="/terms-of-service" className="hover:text-white transition-colors">{__('general.terms_of_service') || 'Terms & SLA'}</SafeLink></li>
                                <li><span className="text-zinc-500">Security Architecture</span></li>
                                <li><span className="text-zinc-500">GDPR Compliance</span></li>
                            </ul>
                        </div>
                    </div>

                    {/* Right Side */}
                    <div className="flex flex-col justify-between items-start lg:items-end space-y-8">
                        <div className="flex items-center space-x-6 rtl:space-x-reverse text-zinc-400 font-sans">
                            <a href="https://www.linkedin.com/in/musoftwareuno/?locale=ar" target="_blank" rel="noopener noreferrer" className="hover:text-white transition-colors">LinkedIn</a>
                            <a href="https://github.com/musoftware" target="_blank" rel="noopener noreferrer" className="hover:text-white transition-colors">GitHub</a>
                            <a href="https://x.com/MusoftwareUno" target="_blank" rel="noopener noreferrer" className="hover:text-white transition-colors">X</a>
                            <a href="https://www.facebook.com/musoftwares.com.page/" target="_blank" rel="noopener noreferrer" className="hover:text-white transition-colors">Facebook</a>
                        </div>

                        <div className="flex items-center space-x-3 rtl:space-x-reverse text-zinc-500 font-mono text-[11px]">
                            <span>MUSOFTWARES STUDIO</span>
                            <div className="w-6 h-6 border-2 border-zinc-400 rotate-45 flex items-center justify-center">
                                <div className="w-2 h-2 bg-zinc-400" />
                            </div>
                        </div>
                    </div>
                </div>

                <div className="max-w-[1400px] mx-auto mt-16 pt-8 border-t border-[#1C1C1C] flex flex-col sm:flex-row items-center justify-between text-zinc-400 font-mono text-[11px]">
                    <div>&copy; {new Date().getFullYear()} Musoftwares Inc. {__('general.all_rights_reserved') || 'All rights reserved.'}</div>
                    <div className="mt-2 sm:mt-0">Suez, Egypt • {__('general.worldwide_delivery') || 'Worldwide Delivery'}</div>
                </div>
            </footer>

            {/* Guest Ticket Dialog */}
            <Dialog open={isGuestTicketOpen} onOpenChange={setIsGuestTicketOpen}>
                <DialogContent className="sm:max-w-md bg-[#161616] border-[#2B2B2B] text-white">
                    <DialogHeader>
                        <DialogTitle className="text-white font-mono uppercase tracking-wider">{__('general.submit_guest_ticket') || 'Connect with Engineering Studio'}</DialogTitle>
                        <DialogDescription className="text-zinc-400 text-xs font-mono">
                            {__('general.please_fill_out_the_form_below_and_we_wi') || 'Describe your system requirements and our architects will reply within 24 hours.'}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submitGuestTicket} className="space-y-4 font-mono text-xs">
                        <div className="space-y-1.5">
                            <Label htmlFor="name" className="text-zinc-300">{__('general.name') || 'Name'}</Label>
                            <Input id="name" required value={data.name} onChange={e => setData('name', e.target.value)} className="bg-black border-[#2B2B2B] text-white" />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="email" className="text-zinc-300">{__('general.email') || 'Email'}</Label>
                            <Input id="email" type="email" required value={data.email} onChange={e => setData('email', e.target.value)} className="bg-black border-[#2B2B2B] text-white" />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="mobile" className="text-zinc-300 flex items-center gap-2">
                                {__('general.mobile') || 'Mobile / WhatsApp'} <MessageCircle className="h-3.5 w-3.5 text-[#25D366]" />
                            </Label>
                            <Input id="mobile" required value={data.mobile} onChange={e => setData('mobile', e.target.value)} className="bg-black border-[#2B2B2B] text-white" />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="body" className="text-zinc-300">{__('general.message') || 'Scope Brief'}</Label>
                            <Textarea id="body" required value={data.body} onChange={e => setData('body', e.target.value)} rows={4} className="bg-black border-[#2B2B2B] text-white" />
                        </div>
                        <DialogFooter className="pt-4">
                            <button type="button" onClick={() => setIsGuestTicketOpen(false)} className="px-4 py-2 border border-[#2B2B2B] bg-black text-zinc-300 hover:text-white text-xs font-mono">
                                {__('general.cancel') || 'Cancel'}
                            </button>
                            <button type="submit" disabled={processing} className="px-5 py-2 bg-white text-black hover:bg-zinc-200 text-xs font-mono uppercase font-bold">
                                {__('general.submit') || 'Send Brief'}
                            </button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
            <Toaster />
            <FloatingWhatsAppButton className="hidden lg:flex" />
            <MobileBottomActionBar onOpenTicket={() => setIsGuestTicketOpen(true)} />
        </div>
    );
}

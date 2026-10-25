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
import ThemeToggle from '@/Components/ThemeToggle';
import SiteHeader from '@/Components/Public/SiteHeader';
import SiteFooter from '@/Components/Public/SiteFooter';

interface PublicLayoutProps extends PropsWithChildren {
    auth?: {
        user: any;
    };
}

export default function PublicLayout({ children, auth: propAuth }: PublicLayoutProps) {
    const { auth: pageAuth } = usePage().props as any;
    const auth = propAuth || pageAuth;
    const [scrolled, setScrolled] = useState(false);
    const [isGuestTicketOpen, setIsGuestTicketOpen] = useState(false);
    const [newsletterEmail, setNewsletterEmail] = useState('');
    const [newsletterLoading, setNewsletterLoading] = useState(false);
    const { toast } = useToast();

    const handleNewsletterSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!newsletterEmail || !newsletterEmail.includes('@')) {
            toast({
                title: __('frontend.public_layout_invalid_email'),
                description: __('frontend.public_layout_invalid_email_desc'),
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
                    title: __('frontend.public_layout_subscribed'),
                    description: json.message || __('frontend.public_layout_subscribed_desc'),
                });
            } else {
                toast({
                    title: __('frontend.public_layout_subscribe_failed'),
                    description: json.message || __('frontend.public_layout_subscribe_failed_desc'),
                    variant: 'destructive',
                });
            }
        } catch (error) {
            toast({
                title: __('general.error'),
                description: __('frontend.public_layout_unexpected_error'),
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
                    title: __('general.success'),
                    description: __('general.ticket_submitted_success'),
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

        // Public pages strictly enforce Apple Light theme
        document.documentElement.classList.remove('dark');
        document.documentElement.classList.add('light');
        document.documentElement.setAttribute('data-theme', 'light');
        document.documentElement.setAttribute('data-theme-fixed', 'true');
        document.documentElement.style.colorScheme = 'light';
        const metaThemeColor = document.querySelector('meta[name="theme-color"]');
        if (metaThemeColor) {
            metaThemeColor.setAttribute('content', '#ffffff');
        }

        window.addEventListener('scroll', handleScroll);
        window.addEventListener('open-guest-ticket', handleOpenTicket);
        return () => {
            window.removeEventListener('scroll', handleScroll);
            window.removeEventListener('open-guest-ticket', handleOpenTicket);
        };
    }, []);

    return (
        <div data-theme="light" className="light-surface flex min-h-screen flex-col bg-[#ffffff] text-[#1d1d1f] antialiased font-sans selection:bg-[#0071e3]/20 selection:text-[#0071e3]">

            <SiteHeader />

            {/* Page Main Content */}
            <main className="flex-1 w-full bg-[#ffffff] dark:bg-[#000000]">
                {children}
            </main>

            {/* Same footer as the Blade pages (content from App\Support\SiteNav::footer()). */}
            <SiteFooter />
            {/* Keeps the last footer line visible above the phone action bar (same as Blade). */}
            <div className="h-20 lg:hidden" aria-hidden="true" />

            {/* Guest Ticket Dialog */}
            <Dialog open={isGuestTicketOpen} onOpenChange={setIsGuestTicketOpen}>
                <DialogContent className="sm:max-w-md bg-white dark:bg-[#1d1d1f] border-black/10 dark:border-white/10 text-[#1d1d1f] dark:text-[#f5f5f7] rounded-[24px] shadow-2xl p-6">
                    <DialogHeader>
                        <DialogTitle className="text-[#1d1d1f] dark:text-[#f5f5f7] font-semibold text-lg">{__('general.submit_guest_ticket')}</DialogTitle>
                        <DialogDescription className="text-[#1d1d1f]/60 dark:text-[#f5f5f7]/60 text-xs">
                            {__('general.please_fill_out_the_form_below_and_we_wi')}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submitGuestTicket} className="space-y-4 text-xs">
                        <div className="space-y-1.5">
                            <Label htmlFor="name" className="text-[#1d1d1f]/80 dark:text-[#f5f5f7]/80 font-medium">{__('general.name')}</Label>
                            <Input id="name" required value={data.name} onChange={e => setData('name', e.target.value)} className="bg-[#f5f5f7] dark:bg-black/40 border-black/10 dark:border-white/10 text-[#1d1d1f] dark:text-[#f5f5f7] rounded-xl focus:bg-white dark:focus:bg-black/60" />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="email" className="text-[#1d1d1f]/80 dark:text-[#f5f5f7]/80 font-medium">{__('general.email')}</Label>
                            <Input id="email" type="email" required value={data.email} onChange={e => setData('email', e.target.value)} className="bg-[#f5f5f7] dark:bg-black/40 border-black/10 dark:border-white/10 text-[#1d1d1f] dark:text-[#f5f5f7] rounded-xl focus:bg-white dark:focus:bg-black/60" />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="mobile" className="text-[#1d1d1f]/80 dark:text-[#f5f5f7]/80 font-medium flex items-center gap-2">
                                {__('general.mobile')} <MessageCircle className="h-3.5 w-3.5 text-[#25D366]" />
                            </Label>
                            <Input id="mobile" required value={data.mobile} onChange={e => setData('mobile', e.target.value)} className="bg-[#f5f5f7] dark:bg-black/40 border-black/10 dark:border-white/10 text-[#1d1d1f] dark:text-[#f5f5f7] rounded-xl focus:bg-white dark:focus:bg-black/60" />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="body" className="text-[#1d1d1f]/80 dark:text-[#f5f5f7]/80 font-medium">{__('general.message')}</Label>
                            <Textarea id="body" required value={data.body} onChange={e => setData('body', e.target.value)} rows={4} className="bg-[#f5f5f7] dark:bg-black/40 border-black/10 dark:border-white/10 text-[#1d1d1f] dark:text-[#f5f5f7] rounded-xl focus:bg-white dark:focus:bg-black/60" />
                        </div>
                        <DialogFooter className="pt-2">
                            <button type="button" onClick={() => setIsGuestTicketOpen(false)} className="px-4 py-2 rounded-full border border-black/10 dark:border-white/10 bg-white dark:bg-black/30 text-[#1d1d1f] dark:text-[#f5f5f7] hover:bg-[#f5f5f7] dark:hover:bg-white/10 text-xs font-medium">
                                {__('general.cancel')}
                            </button>
                            <button type="submit" disabled={processing} className="px-5 py-2 rounded-full bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-medium">
                                {__('general.submit')}
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

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

    const guestNavItems: NavItem[] = [
        {
            id: 'web',
            label: __('general.web_apps') || 'Web Apps',
            href: '/#web',
            items: []
        },
        {
            id: 'mobile',
            label: __('general.mobile_apps') || 'Mobile Apps',
            href: '/#mobile',
            items: []
        },
        {
            id: 'desktop',
            label: __('general.desktop_apps') || 'Desktop Apps',
            href: '/#desktop',
            items: []
        },
        {
            id: 'platforms',
            label: __('general.platforms') || 'Platforms',
            href: '/platforms',
            items: []
        },
        {
            id: 'solutions',
            label: __('general.solutions') || 'Solutions',
            href: '/solutions',
            items: []
        },
        {
            id: 'portfolio',
            label: __('general.portfolio') || 'Portfolio',
            href: '/portfolio',
            items: []
        },
        {
            id: 'estimator',
            label: __('general.estimator') || 'Estimator',
            href: '/estimator',
            items: []
        },
        {
            id: 'founder',
            label: __('general.founder') || 'Founder',
            href: '/about/mahmoud-amin',
            items: []
        },
        {
            id: 'contact',
            label: __('general.contact') || 'Contact',
            href: '/company/contact',
            items: []
        },
    ];

    const authNavItems: NavItem[] = [
        {
            id: 'platforms',
            label: __('general.platforms') || 'Platforms',
            href: '/platforms',
            items: [
                { title: 'Enterprise ERP Systems', desc: 'Modular double-entry ledger & operations', href: '/platforms/erp' },
                { title: 'WhatsApp Cloud API', desc: 'Verified Meta Graph automated pipeline', href: '/platforms/crm' },
                { title: 'Meta API Interceptors', desc: 'Omnichannel CRM synchronization', href: '/platforms/cloud' },
            ]
        },
        {
            id: 'solutions',
            label: __('general.solutions') || 'Solutions',
            href: '/solutions',
            items: [
                { title: 'E-Commerce Platforms', desc: 'Realtime store & omnichannel POS', href: '/solutions/ecommerce' },
                { title: 'Fintech & Wallets', desc: 'Automated ledgers & payment gateways', href: '/solutions/finance' },
                { title: 'Education & LMS', desc: 'Interactive portals & course telemetry', href: '/solutions/education' },
            ]
        },
        {
            id: 'portfolio',
            label: __('general.portfolio') || 'Portfolio',
            href: '/portfolio',
            items: []
        },
        {
            id: 'estimator',
            label: __('general.estimator') || 'Estimator',
            href: '/estimator',
            items: []
        },
        {
            id: 'pricing',
            label: __('general.pricing') || 'Pricing',
            href: '/pricing',
            items: []
        },
        {
            id: 'insights',
            label: __('general.documentation') || 'Insights',
            href: '/compare/laravel-vs-nodejs',
            items: []
        }
    ];

    const navItems = auth?.user ? authNavItems : guestNavItems;

    return (
        <div className="flex min-h-screen flex-col bg-[#ffffff] dark:bg-[#000000] text-[#1d1d1f] dark:text-[#f5f5f7] antialiased font-sans selection:bg-[#0071e3]/20 selection:text-[#0071e3]">

            {/* Top Navigation Header (Apple Glassmorphism Light / Dark) */}
            <header
                className={`sticky top-0 w-full transition-all duration-200 border-b border-black/5 dark:border-white/10 bg-white/80 dark:bg-black/80 backdrop-blur-md z-50`}
                onMouseLeave={() => setActiveDropdown(null)}
            >
                <div className="mx-auto flex max-w-[1280px] items-center justify-between px-6 sm:px-10 min-h-[56px] py-3">

                    {/* Left: Brand Monogram & Name */}
                    <div className="flex items-center gap-8">
                        <SafeLink href="/" className="flex items-center space-x-2.5 rtl:space-x-reverse group focus:outline-none shrink-0" title="Musoftwares Studio">
                            <svg className="w-5 h-5 fill-[#1d1d1f] dark:fill-white group-hover:text-[#000000] transition-colors" viewBox="0 0 307 307" xmlns="http://www.w3.org/2000/svg">
                                <path d="M 48 54 L 48 223 L 51 226 L 52 226 L 54 228 L 55 228 L 57 230 L 58 230 L 60 232 L 61 232 L 63 234 L 64 234 L 66 236 L 67 236 L 69 238 L 70 238 L 72 240 L 73 240 L 75 242 L 76 242 L 78 244 L 79 244 L 81 246 L 82 246 L 84 248 L 91 252 L 94 255 L 97 256 L 99 258 L 100 258 L 102 260 L 103 260 L 105 262 L 106 262 L 108 264 L 109 264 L 132 280 L 135 281 L 141 286 L 144 287 L 146 289 L 153 293 L 155 291 L 158 290 L 161 287 L 162 287 L 164 285 L 165 285 L 167 283 L 168 283 L 170 281 L 171 281 L 173 279 L 174 279 L 176 277 L 177 277 L 179 275 L 180 275 L 182 273 L 183 273 L 185 271 L 186 271 L 188 269 L 189 269 L 191 267 L 192 267 L 194 265 L 195 265 L 197 263 L 198 263 L 200 261 L 201 261 L 203 259 L 204 259 L 206 257 L 207 257 L 209 255 L 210 255 L 212 253 L 213 253 L 215 251 L 216 251 L 218 249 L 219 249 L 221 247 L 222 247 L 224 245 L 225 245 L 227 243 L 228 243 L 230 241 L 231 241 L 233 239 L 234 239 L 236 237 L 237 237 L 239 235 L 240 235 L 242 233 L 243 233 L 245 231 L 246 231 L 256 224 L 256 220 L 257 219 L 257 216 L 256 215 L 256 54 L 254 56 L 250 58 L 247 61 L 246 61 L 243 64 L 236 68 L 226 76 L 225 76 L 223 78 L 219 80 L 216 83 L 215 83 L 213 85 L 206 89 L 203 92 L 196 96 L 193 99 L 186 103 L 183 106 L 182 106 L 180 108 L 173 112 L 170 115 L 169 115 L 164 119 L 164 120 L 166 122 L 167 122 L 174 128 L 176 128 L 180 125 L 181 125 L 184 122 L 188 120 L 191 117 L 198 113 L 201 110 L 202 110 L 204 108 L 211 104 L 214 101 L 215 101 L 217 99 L 224 95 L 227 92 L 231 90 L 237 85 L 239 84 L 241 85 L 241 216 L 238 219 L 237 219 L 232 223 L 229 224 L 227 226 L 223 228 L 220 231 L 217 232 L 215 234 L 211 236 L 208 239 L 202 242 L 200 244 L 199 244 L 197 246 L 196 246 L 194 248 L 193 248 L 191 250 L 190 250 L 188 252 L 187 252 L 185 254 L 184 254 L 182 256 L 181 256 L 179 258 L 178 258 L 176 260 L 175 260 L 173 262 L 172 262 L 170 264 L 163 268 L 160 271 L 159 271 L 154 275 L 151 275 L 149 273 L 148 273 L 146 271 L 145 271 L 143 269 L 142 269 L 140 267 L 139 267 L 137 265 L 136 265 L 134 263 L 133 263 L 131 261 L 130 261 L 128 259 L 127 259 L 125 257 L 124 257 L 122 255 L 121 255 L 119 253 L 118 253 L 116 251 L 115 251 L 92 235 L 86 232 L 80 227 L 77 226 L 75 224 L 68 220 L 64 216 L 64 85 L 66 84 L 68 86 L 69 86 L 72 89 L 73 89 L 75 91 L 82 95 L 85 98 L 86 98 L 92 103 L 93 103 L 95 105 L 102 109 L 105 112 L 106 112 L 112 117 L 113 117 L 115 119 L 122 123 L 125 126 L 126 126 L 128 128 L 129 128 L 131 130 L 138 134 L 145 140 L 152 144 L 159 150 L 163 152 L 166 155 L 167 155 L 175 161 L 177 161 L 180 158 L 181 158 L 183 156 L 184 156 L 186 154 L 187 154 L 189 152 L 190 152 L 192 150 L 199 146 L 202 143 L 203 143 L 210 138 L 211 139 L 211 204 L 201 211 L 200 211 L 198 213 L 197 213 L 195 215 L 194 215 L 192 217 L 191 217 L 189 219 L 188 219 L 186 221 L 185 221 L 183 223 L 182 223 L 180 225 L 179 225 L 177 227 L 176 227 L 174 229 L 173 229 L 171 231 L 170 231 L 168 233 L 167 233 L 165 235 L 164 235 L 162 237 L 161 237 L 159 239 L 158 239 L 156 241 L 155 241 L 153 243 L 152 243 L 150 241 L 149 241 L 147 239 L 146 239 L 144 237 L 143 237 L 141 235 L 140 235 L 138 233 L 137 233 L 135 231 L 134 231 L 132 229 L 131 229 L 108 213 L 105 212 L 102 209 L 99 208 L 94 204 L 94 141 L 93 140 L 94 139 L 98 140 L 104 145 L 105 145 L 128 161 L 130 161 L 140 153 L 141 153 L 137 149 L 134 148 L 131 145 L 130 145 L 127 142 L 120 138 L 117 135 L 116 135 L 114 133 L 113 133 L 111 131 L 110 131 L 108 129 L 101 125 L 98 122 L 97 122 L 95 120 L 88 116 L 85 113 L 78 109 L 78 211 L 88 218 L 89 218 L 91 220 L 92 220 L 94 222 L 95 222 L 97 224 L 98 224 L 100 226 L 101 226 L 103 228 L 104 228 L 106 230 L 107 230 L 109 232 L 110 232 L 112 234 L 113 234 L 115 236 L 116 236 L 118 238 L 119 238 L 121 240 L 122 240 L 124 242 L 125 242 L 127 244 L 128 244 L 130 246 L 131 246 L 133 248 L 134 248 L 136 250 L 137 250 L 139 252 L 140 252 L 142 254 L 143 254 L 145 256 L 146 256 L 148 258 L 149 258 L 151 260 L 155 259 L 157 257 L 158 257 L 160 255 L 161 255 L 163 253 L 164 253 L 166 251 L 167 251 L 169 249 L 170 249 L 172 247 L 173 247 L 175 245 L 176 245 L 178 243 L 179 243 L 181 241 L 182 241 L 184 239 L 185 239 L 187 237 L 188 237 L 211 221 L 214 220 L 217 217 L 223 214 L 227 210 L 227 109 L 224 110 L 222 112 L 218 114 L 215 117 L 214 117 L 212 119 L 208 121 L 205 124 L 204 124 L 202 126 L 201 126 L 199 128 L 192 132 L 189 135 L 186 136 L 183 139 L 182 139 L 177 143 L 174 143 L 168 138 L 167 138 L 161 133 L 160 133 L 157 130 L 153 128 L 150 125 L 143 121 L 133 113 L 132 113 L 130 111 L 126 109 L 123 106 L 122 106 L 120 104 L 113 100 L 110 97 L 109 97 L 107 95 L 100 91 L 97 88 L 90 84 L 87 81 L 83 79 L 80 76 L 73 72 L 70 69 L 63 65 L 56 59 L 55 59 L 49 54 Z" />
                            </svg>
                            <span className="text-[19px] sm:text-[21px] font-semibold tracking-[-0.03em] text-[#1d1d1f] dark:text-white">
                                Musoftware
                            </span>
                            <span className="text-[10px] font-medium px-2 py-0.5 rounded-full bg-[#f5f5f7] dark:bg-[#1d1d1f] border border-black/5 dark:border-white/10 text-[#1d1d1f]/70 dark:text-[#f5f5f7]/70">
                                STUDIO
                            </span>
                        </SafeLink>

                        {/* Desktop Navigation */}
                        <nav className="hidden lg:flex items-center space-x-6 rtl:space-x-reverse text-[13px] font-normal text-[#1d1d1f]/75 dark:text-[#f5f5f7]/75">
                            {navItems.map((item) => (
                                <div key={item.id} className="relative">
                                    <SafeLink
                                        href={item.href}
                                        onMouseEnter={() => item.items.length > 0 && setActiveDropdown(item.id)}
                                        className="flex items-center gap-1 hover:text-[#1d1d1f] dark:hover:text-white transition-colors"
                                    >
                                        {item.label}
                                        {item.items.length > 0 && <ChevronDown className="h-3 w-3 opacity-50" />}
                                    </SafeLink>

                                    {/* Dropdown Menu (for auth user items) */}
                                    {item.items.length > 0 && activeDropdown === item.id && (
                                        <div
                                            onMouseEnter={() => setActiveDropdown(item.id)}
                                            onMouseLeave={() => setActiveDropdown(null)}
                                            className="absolute left-0 rtl:left-auto rtl:right-0 top-full mt-2 w-80 bg-white/95 dark:bg-[#1d1d1f]/95 backdrop-blur-xl border border-black/10 dark:border-white/10 rounded-2xl p-2 shadow-[0_20px_50px_rgba(0,0,0,0.12)] animate-in fade-in slide-in-from-top-2 duration-150 z-50"
                                        >
                                            {item.items.map((subItem) => (
                                                <SafeLink
                                                    key={subItem.href}
                                                    href={subItem.href}
                                                    className="flex flex-col p-3 rounded-xl hover:bg-[#f5f5f7] dark:hover:bg-[#2d2d2f] transition-colors group"
                                                >
                                                    <span className="text-xs font-semibold text-[#1d1d1f] dark:text-white">
                                                        {subItem.title}
                                                    </span>
                                                    <span className="text-[11px] text-[#1d1d1f]/60 dark:text-[#f5f5f7]/60 mt-0.5 font-sans">
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

                    {/* Right: Utilities & Action Buttons */}
                    <div className="flex items-center space-x-3 sm:space-x-4 rtl:space-x-reverse shrink-0">
                        {/* WhatsApp Direct Action */}
                        <a
                            href="https://wa.me/201015218548"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="hidden sm:inline-flex p-2 text-[#1d1d1f]/70 dark:text-[#f5f5f7]/70 hover:text-[#25D366] transition-colors"
                            title={__('general.whatsapp_direct') || 'Direct Consultation'}
                        >
                            <svg className="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                        </a>

                        <ThemeToggle className="h-8 w-8" />

                        {auth?.user ? (
                            <SafeLink href={route('dashboard')}>
                                <button className="px-3.5 py-1.5 rounded-[980px] border border-black/10 dark:border-white/10 bg-white dark:bg-[#1d1d1f] text-[#1d1d1f] dark:text-white hover:bg-[#f5f5f7] dark:hover:bg-[#2d2d2f] text-[12px] font-medium transition-all shadow-sm">
                                    {__('general.console') || 'Dashboard'} ➔
                                </button>
                            </SafeLink>
                        ) : (
                            <div className="flex items-center space-x-3 rtl:space-x-reverse">
                                <SafeLink
                                    href={route('login')}
                                    className="text-[12px] font-medium text-[#1d1d1f]/75 dark:text-[#f5f5f7]/75 hover:text-[#1d1d1f] dark:hover:text-white transition-colors px-2"
                                >
                                    {__('general.sign_in') || 'Sign in'}
                                </SafeLink>
                                <SafeLink href="/start-project">
                                    <button className="inline-flex items-center justify-center rounded-[980px] bg-[#0071e3] hover:bg-[#0077ed] text-white text-[12px] font-medium px-3.5 py-1.5 transition-all shadow-sm">
                                        {__('general.start_a_project') || 'Start a Project'}
                                    </button>
                                </SafeLink>
                            </div>
                        )}

                        {/* Mobile Menu Toggle */}
                        <button
                            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                            className="lg:hidden p-2 text-[#1d1d1f]/70 dark:text-[#f5f5f7]/70 hover:text-[#1d1d1f] dark:hover:text-white transition-colors"
                            aria-label="Toggle navigation"
                        >
                            {mobileMenuOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
                        </button>
                    </div>
                </div>

                {/* Mobile Drawer */}
                {mobileMenuOpen && (
                    <div className="lg:hidden border-b border-black/5 dark:border-white/10 bg-white/95 dark:bg-[#1d1d1f]/95 backdrop-blur-xl px-6 py-5 space-y-3">
                        <div className="flex flex-col space-y-2 text-sm text-[#1d1d1f] dark:text-[#f5f5f7]">
                            {navItems.map((item) => (
                                <SafeLink
                                    key={item.id}
                                    href={item.href}
                                    onClick={() => setMobileMenuOpen(false)}
                                    className="py-2 hover:text-[#0071e3] border-b border-black/5 dark:border-white/10"
                                >
                                    {item.label}
                                </SafeLink>
                            ))}
                            {!auth?.user && (
                                <div className="pt-2 flex items-center gap-3">
                                    <SafeLink
                                        href="/login"
                                        onClick={() => setMobileMenuOpen(false)}
                                        className="flex-1 text-center py-2 rounded-xl border border-black/10 dark:border-white/10 text-sm font-medium hover:bg-black/5 dark:hover:bg-white/5"
                                    >
                                        {__('general.sign_in') || 'Sign in'}
                                    </SafeLink>
                                    <SafeLink
                                        href="/start-project"
                                        onClick={() => setMobileMenuOpen(false)}
                                        className="flex-1 text-center py-2 rounded-xl bg-[#0071e3] text-white text-sm font-medium hover:bg-[#0077ed]"
                                    >
                                        {__('general.start_a_project') || 'Start Project'}
                                    </SafeLink>
                                </div>
                            )}
                            {auth?.user && (
                                <SafeLink
                                    href="/dashboard"
                                    onClick={() => setMobileMenuOpen(false)}
                                    className="py-2 text-[#0071e3] font-semibold"
                                >
                                    {__('general.console') || 'Dashboard ➔'}
                                </SafeLink>
                            )}
                        </div>
                    </div>
                )}
            </header>

            {/* Page Main Content */}
            <main className="flex-1 w-full bg-[#ffffff] dark:bg-[#000000]">
                {children}
            </main>

            {/* Clean Apple Minimalist Footer */}
            <footer className="w-full bg-[#f5f5f7] dark:bg-[#161617] border-t border-black/5 dark:border-white/10 py-16 px-6 sm:px-12 text-[12px] text-[#1d1d1f]/70 dark:text-[#f5f5f7]/70">
                <div className="max-w-[1280px] mx-auto flex flex-col lg:flex-row justify-between gap-12">

                    {/* 4 Columns */}
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-8 lg:gap-12 flex-1">
                        <div className="space-y-3">
                            <div className="text-[#1d1d1f] dark:text-[#f5f5f7] font-semibold tracking-tight">{__('general.contact_us') || 'Contact Us'}</div>
                            <ul className="space-y-2 text-[12px]">
                                <li><a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" className="hover:text-[#1d1d1f] dark:hover:text-white transition-colors">{__('general.whatsapp_direct') || 'WhatsApp Direct'}</a></li>
                                <li><a href="mailto:admin@musoftwares.com" className="hover:text-[#1d1d1f] dark:hover:text-white transition-colors">{__('general.email_studio') || 'Email Studio'}</a></li>
                                <li><SafeLink href="/start-project" className="hover:text-[#0071e3] transition-colors text-[#0071e3] font-medium">{__('general.start_project_wizard') || 'System Scoping Wizard ➔'}</SafeLink></li>
                            </ul>
                        </div>

                        <div className="space-y-3">
                            <div className="text-[#1d1d1f] dark:text-[#f5f5f7] font-semibold tracking-tight">{__('general.solutions') || 'Solutions'}</div>
                            <ul className="space-y-2 text-[12px]">
                                <li><SafeLink href="/platforms/erp" className="hover:text-[#1d1d1f] dark:hover:text-white transition-colors">Enterprise ERP</SafeLink></li>
                                <li><SafeLink href="/platforms/crm" className="hover:text-[#1d1d1f] dark:hover:text-white transition-colors">WhatsApp Cloud API</SafeLink></li>
                                <li><SafeLink href="/platforms/cloud" className="hover:text-[#1d1d1f] dark:hover:text-white transition-colors">Meta Graph Suite</SafeLink></li>
                                <li><SafeLink href="/start-project" className="hover:text-[#1d1d1f] dark:hover:text-white transition-colors">System Architecture Wizard</SafeLink></li>
                            </ul>
                        </div>

                        <div className="space-y-3">
                            <div className="text-[#1d1d1f] dark:text-[#f5f5f7] font-semibold tracking-tight">{__('general.press_center') || 'Press Center'}</div>
                            <ul className="space-y-2 text-[12px]">
                                <li><SafeLink href="/estimator" className="hover:text-[#1d1d1f] dark:hover:text-white transition-colors">{__('general.estimator') || 'Architecture Estimator'}</SafeLink></li>
                                <li><SafeLink href="/portfolio" className="hover:text-[#1d1d1f] dark:hover:text-white transition-colors">{__('general.portfolio') || 'Case Studies Archive'}</SafeLink></li>
                                <li><SafeLink href="/about/mahmoud-amin" className="hover:text-[#1d1d1f] dark:hover:text-white transition-colors">{__('general.leadership_bio') || 'Leadership Bio (Mahmoud Amin)'}</SafeLink></li>
                                <li><SafeLink href="/compare/laravel-vs-nodejs" className="hover:text-[#1d1d1f] dark:hover:text-white transition-colors">Tech Benchmarks (Laravel vs Node.js)</SafeLink></li>
                            </ul>
                        </div>

                        <div className="space-y-3">
                            <div className="text-[#1d1d1f] dark:text-[#f5f5f7] font-semibold tracking-tight">{__('general.legal') || 'Legal & Privacy'}</div>
                            <ul className="space-y-2 text-[12px]">
                                <li><SafeLink href="/privacy-policy" className="hover:text-[#1d1d1f] dark:hover:text-white transition-colors">{__('general.privacy_policy') || 'Privacy Policy'}</SafeLink></li>
                                <li><SafeLink href="/terms-of-service" className="hover:text-[#1d1d1f] dark:hover:text-white transition-colors">{__('general.terms_of_service') || 'Terms & SLA'}</SafeLink></li>
                                <li><span className="text-[#1d1d1f]/40 dark:text-[#f5f5f7]/40">Security Architecture</span></li>
                                <li><span className="text-[#1d1d1f]/40 dark:text-[#f5f5f7]/40">GDPR Compliance</span></li>
                            </ul>
                        </div>
                    </div>

                    {/* Right Side */}
                    <div className="flex flex-col justify-between items-start lg:items-end space-y-6">
                        <div className="flex items-center space-x-5 rtl:space-x-reverse text-[#1d1d1f]/70 dark:text-[#f5f5f7]/70">
                            <a href="https://www.linkedin.com/in/musoftwareuno/?locale=ar" target="_blank" rel="noopener noreferrer" className="hover:text-[#1d1d1f] dark:hover:text-white transition-colors">LinkedIn</a>
                            <a href="https://github.com/musoftware" target="_blank" rel="noopener noreferrer" className="hover:text-[#1d1d1f] dark:hover:text-white transition-colors">GitHub</a>
                            <a href="https://x.com/MusoftwareUno" target="_blank" rel="noopener noreferrer" className="hover:text-[#1d1d1f] dark:hover:text-white transition-colors">X</a>
                            <a href="https://www.facebook.com/musoftwares.com.page/" target="_blank" rel="noopener noreferrer" className="hover:text-[#1d1d1f] dark:hover:text-white transition-colors">Facebook</a>
                        </div>

                        <div className="flex items-center space-x-2 rtl:space-x-reverse text-[#1d1d1f]/60 dark:text-[#f5f5f7]/60 text-[11px]">
                            <span>MUSOFTWARES STUDIO</span>
                            <span className="w-1.5 h-1.5 rounded-full bg-[#30d158]"></span>
                        </div>
                    </div>
                </div>

                <div className="max-w-[1280px] mx-auto mt-12 pt-6 border-t border-black/5 dark:border-white/10 flex flex-col sm:flex-row items-center justify-between text-[#1d1d1f]/50 dark:text-[#f5f5f7]/50 text-[11px]">
                    <div>&copy; {new Date().getFullYear()} Musoftwares Inc. {__('general.all_rights_reserved') || 'All rights reserved.'}</div>
                    <div className="mt-1 sm:mt-0">Suez, Egypt • {__('general.worldwide_delivery') || 'Worldwide Delivery'}</div>
                </div>
            </footer>

            {/* Guest Ticket Dialog */}
            <Dialog open={isGuestTicketOpen} onOpenChange={setIsGuestTicketOpen}>
                <DialogContent className="sm:max-w-md bg-white dark:bg-[#1d1d1f] border-black/10 dark:border-white/10 text-[#1d1d1f] dark:text-[#f5f5f7] rounded-[24px] shadow-2xl p-6">
                    <DialogHeader>
                        <DialogTitle className="text-[#1d1d1f] dark:text-[#f5f5f7] font-semibold text-lg">{__('general.submit_guest_ticket') || 'Connect with Engineering Studio'}</DialogTitle>
                        <DialogDescription className="text-[#1d1d1f]/60 dark:text-[#f5f5f7]/60 text-xs">
                            {__('general.please_fill_out_the_form_below_and_we_wi') || 'Describe your system requirements and our architects will reply within 24 hours.'}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submitGuestTicket} className="space-y-4 text-xs">
                        <div className="space-y-1.5">
                            <Label htmlFor="name" className="text-[#1d1d1f]/80 dark:text-[#f5f5f7]/80 font-medium">{__('general.name') || 'Name'}</Label>
                            <Input id="name" required value={data.name} onChange={e => setData('name', e.target.value)} className="bg-[#f5f5f7] dark:bg-black/40 border-black/10 dark:border-white/10 text-[#1d1d1f] dark:text-[#f5f5f7] rounded-xl focus:bg-white dark:focus:bg-black/60" />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="email" className="text-[#1d1d1f]/80 dark:text-[#f5f5f7]/80 font-medium">{__('general.email') || 'Email'}</Label>
                            <Input id="email" type="email" required value={data.email} onChange={e => setData('email', e.target.value)} className="bg-[#f5f5f7] dark:bg-black/40 border-black/10 dark:border-white/10 text-[#1d1d1f] dark:text-[#f5f5f7] rounded-xl focus:bg-white dark:focus:bg-black/60" />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="mobile" className="text-[#1d1d1f]/80 dark:text-[#f5f5f7]/80 font-medium flex items-center gap-2">
                                {__('general.mobile') || 'Mobile / WhatsApp'} <MessageCircle className="h-3.5 w-3.5 text-[#25D366]" />
                            </Label>
                            <Input id="mobile" required value={data.mobile} onChange={e => setData('mobile', e.target.value)} className="bg-[#f5f5f7] dark:bg-black/40 border-black/10 dark:border-white/10 text-[#1d1d1f] dark:text-[#f5f5f7] rounded-xl focus:bg-white dark:focus:bg-black/60" />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="body" className="text-[#1d1d1f]/80 dark:text-[#f5f5f7]/80 font-medium">{__('general.message') || 'Scope Brief'}</Label>
                            <Textarea id="body" required value={data.body} onChange={e => setData('body', e.target.value)} rows={4} className="bg-[#f5f5f7] dark:bg-black/40 border-black/10 dark:border-white/10 text-[#1d1d1f] dark:text-[#f5f5f7] rounded-xl focus:bg-white dark:focus:bg-black/60" />
                        </div>
                        <DialogFooter className="pt-2">
                            <button type="button" onClick={() => setIsGuestTicketOpen(false)} className="px-4 py-2 rounded-full border border-black/10 dark:border-white/10 bg-white dark:bg-black/30 text-[#1d1d1f] dark:text-[#f5f5f7] hover:bg-[#f5f5f7] dark:hover:bg-white/10 text-xs font-medium">
                                {__('general.cancel') || 'Cancel'}
                            </button>
                            <button type="submit" disabled={processing} className="px-5 py-2 rounded-full bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-medium">
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

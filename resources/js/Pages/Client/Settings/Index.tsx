import { Head, usePage } from '@inertiajs/react';
import {
    Bell, Check, ChevronRight, FileText, History, Languages, LifeBuoy, LogOut, MessageCircle,
    RefreshCw, Shield, ShieldCheck, UserRound, Wallet,
} from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import SafeLink from '@/Components/SafeLink';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

type Row = { href: string; icon: typeof UserRound; label: string; hint?: string; external?: boolean };

/** All client settings on one screen, grouped like a phone settings app. */
export default function SettingsIndex({ currentLang, whatsappUrl }: { currentLang: 'ar' | 'en'; whatsappUrl: string }) {
    const { auth } = usePage().props as any;
    const user = auth?.user;

    const groups: { title: string; rows: Row[] }[] = [
        {
            title: __('client_home.settings.account'),
            rows: [
                { href: '/profile', icon: UserRound, label: __('client_home.settings.profile'), hint: __('client_home.settings.profile_hint') },
                { href: '/profile#security', icon: Shield, label: __('client_home.settings.security') },
                { href: '/kyc', icon: ShieldCheck, label: __('client_home.settings.kyc') },
            ],
        },
        {
            title: __('client_home.settings.billing'),
            rows: [
                { href: '/billing/invoices', icon: FileText, label: __('client_home.settings.invoices') },
                { href: '/subscriptions/manage', icon: RefreshCw, label: __('client_home.settings.subscriptions') },
                { href: '/financial/transactions', icon: History, label: __('client_home.settings.transactions') },
                { href: '/financial/payout-methods', icon: Wallet, label: __('client_home.settings.payout_methods') },
            ],
        },
        {
            title: __('client_home.settings.help'),
            rows: [
                { href: '/notifications', icon: Bell, label: __('client_home.settings.notifications') },
                { href: '/tickets', icon: LifeBuoy, label: __('client_home.settings.support') },
                { href: whatsappUrl, icon: MessageCircle, label: __('client_home.settings.whatsapp'), external: true },
            ],
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title={__('client_home.settings.title')} />
            <div className="mx-auto w-full max-w-[680px] px-4 py-6 sm:py-10">
                <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">{__('client_home.settings.title')}</h1>
                {user && (
                    <div className="mt-5 flex items-center gap-3 rounded-[20px] bg-white p-4 shadow-sm dark:bg-zinc-900">
                        <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-black text-sm font-bold text-white dark:bg-zinc-700">
                            {String(user.name ?? '').substring(0, 2).toUpperCase()}
                        </span>
                        <div className="min-w-0">
                            <p className="truncate font-semibold">{user.name}</p>
                            <p className="truncate text-sm text-[#1d1d1f]/60 dark:text-zinc-400" dir="ltr">{user.email}</p>
                        </div>
                    </div>
                )}

                <section className="mt-6">
                    <h2 className="mb-2 flex items-center gap-2 px-1 text-sm font-semibold text-[#1d1d1f]/60 dark:text-zinc-400">
                        <Languages className="h-4 w-4" />{__('client_home.settings.language')}
                    </h2>
                    <div className="overflow-hidden rounded-[20px] bg-white shadow-sm dark:bg-zinc-900">
                        {(['ar', 'en'] as const).map((lang) => (
                            // full page load: the whole interface and its direction switch, and SetLocale saves the choice
                            <a
                                key={lang}
                                href={`/settings?lang=${lang}`}
                                lang={lang}
                                aria-current={currentLang === lang ? 'true' : undefined}
                                className="flex items-center justify-between border-b border-black/5 px-4 py-3.5 text-[15px] last:border-0 hover:bg-[#f5f5f7] dark:border-white/10 dark:hover:bg-zinc-800"
                            >
                                <span className={cn(currentLang === lang && 'font-semibold')}>{__(`client_home.settings.${lang === 'ar' ? 'arabic' : 'english'}`)}</span>
                                {currentLang === lang && <Check className="h-5 w-5 text-[#0071e3]" />}
                            </a>
                        ))}
                    </div>
                    <p className="mt-2 px-1 text-xs text-[#1d1d1f]/50 dark:text-zinc-500">{__('client_home.settings.language_hint')}</p>
                </section>

                {groups.map((group) => (
                    <section key={group.title} className="mt-6">
                        <h2 className="mb-2 px-1 text-sm font-semibold text-[#1d1d1f]/60 dark:text-zinc-400">{group.title}</h2>
                        <div className="overflow-hidden rounded-[20px] bg-white shadow-sm dark:bg-zinc-900">
                            {group.rows.map(({ href, icon: Icon, label, hint, external }) => {
                                const content = (
                                    <>
                                        <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#0071e3]/10 text-[#0071e3]"><Icon className="h-[18px] w-[18px]" /></span>
                                        <span className="min-w-0 flex-1">
                                            <span className="block text-[15px]">{label}</span>
                                            {hint && <span className="block text-xs text-[#1d1d1f]/50 dark:text-zinc-500">{hint}</span>}
                                        </span>
                                        <ChevronRight className="h-4 w-4 shrink-0 text-[#1d1d1f]/30 rtl:rotate-180" />
                                    </>
                                );
                                const className = 'flex items-center gap-3 border-b border-black/5 px-4 py-3 last:border-0 hover:bg-[#f5f5f7] dark:border-white/10 dark:hover:bg-zinc-800';
                                return external ? (
                                    <a key={href} href={href} target="_blank" rel="noopener noreferrer" className={className}>{content}</a>
                                ) : (
                                    <SafeLink key={href} href={href} className={className}>{content}</SafeLink>
                                );
                            })}
                        </div>
                    </section>
                ))}

                <SafeLink
                    href="/logout"
                    method="post"
                    as="button"
                    className="mt-8 flex w-full items-center justify-center gap-2 rounded-[20px] bg-white py-3.5 text-[15px] font-semibold text-rose-600 shadow-sm hover:bg-rose-50 dark:bg-zinc-900 dark:hover:bg-rose-950/30"
                >
                    <LogOut className="h-4 w-4" />{__('client_home.settings.logout')}
                </SafeLink>
            </div>
        </AuthenticatedLayout>
    );
}

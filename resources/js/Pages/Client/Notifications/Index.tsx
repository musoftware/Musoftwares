import { Link, useForm, usePage } from '@inertiajs/react';
import { Bell, CheckCheck } from 'lucide-react';
import ClientPage, { EmptyNote } from '@/Components/Client/ClientPage';
import Pagination from '@/Components/Pagination';
import { PageProps } from '@/types';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

/** Notifications as one plain list: unread first-glance via a blue dot, tap to open what it is about. */
export default function NotificationsIndex({ notifications }: PageProps<{ notifications: any }>) {
    const { locale } = usePage().props as any;
    const { post, processing } = useForm();
    const notifList = (notifications?.data as any[]) || [];
    const hasUnread = notifList.some((n) => !n.read_at);
    const when = (date: string) =>
        new Date(date).toLocaleString(locale === 'ar' ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit' });

    return (
        <ClientPage
            title={__('client_account.notifications.title')}
            hint={__('client_account.notifications.hint')}
            width="sm"
            action={hasUnread ? (
                <button
                    type="button"
                    onClick={() => post(route('notifications.mark-all-read'))}
                    disabled={processing}
                    className="inline-flex items-center gap-1.5 text-sm font-semibold text-[#0071e3] hover:underline disabled:opacity-50"
                >
                    <CheckCheck className="h-4 w-4" />{__('client_account.notifications.mark_all')}
                </button>
            ) : undefined}
        >
            {notifList.length === 0 ? (
                <EmptyNote icon={<Bell className="h-5 w-5" />}>{__('client_account.notifications.empty')}</EmptyNote>
            ) : (
                <ul className="divide-y divide-black/5 overflow-hidden rounded-[20px] bg-white shadow-sm dark:divide-white/10 dark:bg-zinc-900">
                    {notifList.map((n: any) => {
                        const unread = !n.read_at;
                        const title = n.data?.title || n.data?.message || __('client_account.notifications.new');
                        const body = n.data?.body || (n.data?.title && n.data?.message ? n.data.message : null);
                        return (
                            <li key={n.id} className={cn('flex items-start gap-3 px-4 py-3.5', unread && 'bg-[#0071e3]/[0.04]')}>
                                <span className={cn('mt-2 h-2.5 w-2.5 shrink-0 rounded-full', unread ? 'bg-[#0071e3]' : 'bg-transparent')} aria-hidden="true" />
                                <Link
                                    href={route('notifications.mark-read', { id: n.id })}
                                    method="post"
                                    as="button"
                                    className="min-w-0 flex-1 text-start"
                                >
                                    <p className={cn('text-[15px]', unread ? 'font-semibold' : 'font-medium text-[#1d1d1f]/80 dark:text-zinc-300')}>{title}</p>
                                    {body && <p className="mt-0.5 line-clamp-2 text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{body}</p>}
                                    <p className="mt-1 text-xs text-[#1d1d1f]/40 dark:text-zinc-500">{when(n.created_at)}</p>
                                </Link>
                                {unread && (
                                    <Link
                                        href={route('notifications.mark-read', { id: n.id, no_redirect: 1 })}
                                        method="post"
                                        as="button"
                                        className="shrink-0 text-xs font-semibold text-[#0071e3] hover:underline"
                                    >
                                        {__('client_account.notifications.mark_read')}
                                    </Link>
                                )}
                            </li>
                        );
                    })}
                </ul>
            )}
            <Pagination links={notifications?.links} />
        </ClientPage>
    );
}

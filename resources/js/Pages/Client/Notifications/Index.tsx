import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Bell, CheckCheck, ArrowLeft, Clock } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function NotificationsIndex({
    auth,
    notifications,
}: PageProps<{ notifications: any }>) {
    const { post, processing } = useForm();

    const markAllRead = () => {
        post(route('notifications.mark-all-read'));
    };

    const notifList = (notifications?.data as any[]) || [];

    return (
        <AuthenticatedLayout>
            <Head title={`${__('general.notifications')} — Musoftwares Studio`} />

            <div className="w-full bg-[#f5f5f7] text-[#1d1d1f] min-h-[calc(100vh-68px)] font-sans antialiased selection:bg-[#0071e3]/20 selection:text-[#0071e3]">
                
                {/* Hero Header */}
                <div className="w-full bg-white border-b border-black/5 py-8 px-6 sm:px-10">
                    <div className="max-w-[1400px] mx-auto flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div className="space-y-1.5">
                            <Link
                                href="/dashboard"
                                className="inline-flex items-center text-xs font-semibold text-[#0071e3] hover:text-[#0077ed] transition-colors mb-1"
                            >
                                <ArrowLeft className="me-1.5 h-3.5 w-3.5" />
                                {__('general.back_to_dashboard')}
                            </Link>
                            <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-[#1d1d1f] font-sans">
                                {__('general.notifications')}
                            </h1>
                            <p className="text-xs sm:text-sm text-[#1d1d1f]/60 font-sans">
                                Real-time system updates, project activities, and transaction logs.
                            </p>
                        </div>

                        {notifList.length > 0 && (
                            <button
                                onClick={markAllRead}
                                disabled={processing}
                                className="px-5 py-2.5 bg-white hover:bg-[#f5f5f7] text-[#1d1d1f] border border-black/10 text-xs font-semibold rounded-[980px] shadow-sm transition-all flex items-center gap-2 cursor-pointer shrink-0"
                            >
                                <CheckCheck className="w-4 h-4 text-[#0071e3]" />
                                <span>{__('general.mark_all_as_read')}</span>
                            </button>
                        )}
                    </div>
                </div>

                {/* Main Content Area */}
                <div className="max-w-[1000px] mx-auto px-6 sm:px-10 py-8 space-y-4">
                    
                    {notifList.length === 0 ? (
                        <div className="bg-white border border-black/5 rounded-[24px] p-12 text-center shadow-sm">
                            <div className="w-14 h-14 rounded-2xl bg-[#0071e3]/10 flex items-center justify-center text-[#0071e3] mx-auto mb-4">
                                <Bell className="w-7 h-7" />
                            </div>
                            <h3 className="text-base font-bold text-[#1d1d1f] font-sans">
                                {__('general.no_notifications_found')}
                            </h3>
                            <p className="text-xs text-[#1d1d1f]/60 max-w-md mx-auto mt-1 leading-relaxed">
                                You're completely up to date. New updates and alerts will appear here.
                            </p>
                        </div>
                    ) : (
                        <div className="bg-white border border-black/5 rounded-[24px] shadow-sm overflow-hidden divide-y divide-black/5">
                            {notifList.map((notification: any) => {
                                const isUnread = !notification.read_at;
                                return (
                                    <div
                                        key={notification.id}
                                        className={`p-5 transition-colors flex items-start justify-between gap-4 ${
                                            isUnread ? 'bg-[#0071e3]/3' : 'hover:bg-[#f5f5f7]/60'
                                        }`}
                                    >
                                        <div className="flex items-start gap-3.5 flex-1 min-w-0">
                                            <div className={`w-10 h-10 rounded-xl flex items-center justify-center shrink-0 mt-0.5 ${
                                                isUnread
                                                    ? 'bg-[#0071e3]/10 text-[#0071e3]'
                                                    : 'bg-[#f5f5f7] text-[#1d1d1f]/40'
                                            }`}>
                                                <Bell className="w-5 h-5" />
                                            </div>

                                            <Link
                                                href={route('notifications.mark-read', { id: notification.id })}
                                                method="post"
                                                as="button"
                                                className="flex-1 text-start outline-none"
                                            >
                                                <div className="flex items-center gap-2">
                                                    <p className={`text-xs sm:text-sm font-semibold text-[#1d1d1f] ${
                                                        isUnread ? 'font-bold' : ''
                                                    }`}>
                                                        {notification.data?.title || notification.data?.message || __('general.new_notification')}
                                                    </p>
                                                    {isUnread && (
                                                        <span className="w-2 h-2 rounded-full bg-[#0071e3] shrink-0" />
                                                    )}
                                                </div>

                                                {(notification.data?.body || (notification.data?.title && notification.data?.message)) && (
                                                    <p className="text-xs text-[#1d1d1f]/60 mt-1 line-clamp-2 font-sans">
                                                        {notification.data?.body || notification.data?.message}
                                                    </p>
                                                )}

                                                <div className="flex items-center gap-1.5 text-[11px] text-[#1d1d1f]/40 mt-1.5 font-sans">
                                                    <Clock className="w-3 h-3" />
                                                    <span>{new Date(notification.created_at).toLocaleString()}</span>
                                                </div>
                                            </Link>
                                        </div>

                                        {isUnread && (
                                            <Link
                                                href={route('notifications.mark-read', { id: notification.id, no_redirect: 1 })}
                                                method="post"
                                                as="button"
                                                className="px-3 py-1.5 rounded-full bg-[#f5f5f7] hover:bg-[#0071e3] hover:text-white text-[11px] font-semibold text-[#1d1d1f]/70 transition-all shrink-0 cursor-pointer"
                                            >
                                                {__('general.mark_read_1')}
                                            </Link>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    )}

                    {/* Pagination */}
                    {notifications?.links && notifications.links.length > 3 && (
                        <div className="flex justify-center gap-1 pt-4">
                            {notifications.links.map((link: any, key: number) => (
                                <Link
                                    key={key}
                                    href={link.url || '#'}
                                    className={`px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all ${
                                        link.active
                                            ? 'bg-[#1d1d1f] text-white shadow-xs'
                                            : link.url
                                                ? 'bg-white border border-black/10 text-[#1d1d1f] hover:bg-[#f5f5f7]'
                                                : 'cursor-not-allowed opacity-40 pointer-events-none bg-white text-[#1d1d1f]/40'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    )}

                </div>

            </div>
        </AuthenticatedLayout>
    );
}

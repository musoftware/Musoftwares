import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { __ } from '@/lib/i18n';

export default function Index({
    auth,
    notifications,
}: PageProps<{ notifications: any }>) {
    const { post, processing } = useForm();

    const markAllRead = () => {
        post(route('notifications.mark-all-read'));
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl leading-tight font-semibold text-gray-800">
                        Notifications
                    </h2>
                    <button
                        onClick={markAllRead}
                        disabled={processing}
                        className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                    >{__('general.mark_all_as_read')}</button>
                </div>
            }
        >
            <Head title="Notifications" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            {notifications.data.length > 0 ? (
                                <ul className="divide-y divide-gray-200">
                                    {notifications.data.map(
                                        (notification: any) => (
                                            <li
                                                key={notification.id}
                                                className={`py-4 ${!notification.read_at ? 'bg-gray-50' : ''}`}
                                            >
                                                <div className="flex items-center space-x-4 px-4">
                                                    <div className="min-w-0 flex-1">
                                                        <p className="truncate text-sm font-medium text-gray-900">
                                                            {notification.data
                                                                ?.message ||
                                                                'Notification'}
                                                        </p>
                                                        <p className="truncate text-sm text-gray-500">
                                                            {new Date(
                                                                notification.created_at,
                                                            ).toLocaleString()}
                                                        </p>
                                                    </div>
                                                    <div>
                                                        {!notification.read_at && (
                                                            <Link
                                                                href={route(
                                                                    'notifications.mark-read',
                                                                    {
                                                                        id: notification.id,
                                                                    },
                                                                )}
                                                                method="post"
                                                                as="button"
                                                                className="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-900"
                                                            >{__('general.mark_read_1')}</Link>
                                                        )}
                                                    </div>
                                                </div>
                                            </li>
                                        ),
                                    )}
                                </ul>
                            ) : (
                                <div className="py-8 text-center text-gray-500">{__('general.no_notifications_found')}</div>
                            )}

                            {/* Pagination would go here */}
                            {notifications.links &&
                                notifications.links.length > 3 && (
                                    <div className="mt-6 flex justify-center">
                                        <div className="flex space-x-1">
                                            {notifications.links.map(
                                                (link: any, key: number) => (
                                                    <Link
                                                        key={key}
                                                        href={link.url || '#'}
                                                        className={`rounded-md border px-4 py-2 text-sm ${
                                                            link.active
                                                                ? 'bg-indigo-600 text-white'
                                                                : 'bg-white text-gray-700 hover:bg-gray-50'
                                                        } ${!link.url ? 'cursor-not-allowed opacity-50' : ''}`}
                                                        dangerouslySetInnerHTML={{
                                                            __html: link.label,
                                                        }}
                                                    />
                                                ),
                                            )}
                                        </div>
                                    </div>
                                )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

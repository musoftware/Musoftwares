import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Pending({ services }: any) {
    const [rejectingServiceId, setRejectingServiceId] = useState<number | null>(
        null,
    );
    const [rejectionNote, setRejectionNote] = useState('');

    const handleApprove = (id: number) => {
        if (
            confirm(
                'Are you sure you want to approve this service? It will become publicly available immediately.',
            )
        ) {
            router.post(route('admin.marketplace.services.approve', id));
        }
    };

    const confirmReject = (id: number) => {
        setRejectingServiceId(id);
        setRejectionNote('');
    };

    const submitReject = (e: React.FormEvent) => {
        e.preventDefault();
        if (rejectingServiceId) {
            router.post(
                route('admin.marketplace.services.reject', rejectingServiceId),
                { note: rejectionNote },
                {
                    onSuccess: () => setRejectingServiceId(null),
                },
            );
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-2xl leading-tight font-bold text-gray-800">
                    Pending Services Review
                </h2>
            }
        >
            <Head title="Pending Services Review" />

            <div className="min-h-screen bg-gray-50 py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="mb-6 flex items-center justify-between">
                        <p className="text-sm text-gray-600">
                            Review newly submitted services to ensure they meet
                            platform quality guidelines.
                        </p>
                        <span className="rounded-full bg-indigo-100 px-3 py-1 text-xs font-bold text-indigo-800">
                            {services.total || 0} Pending
                        </span>
                    </div>

                    {services.data && services.data.length > 0 ? (
                        <div className="flex flex-col gap-6">
                            {services.data.map((service: any) => (
                                <div
                                    key={service.id}
                                    className="flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md md:flex-row"
                                >
                                    {/* Left: Image/Preview */}
                                    <div className="relative flex-shrink-0 bg-gray-100 md:w-64">
                                        {service.cover_image ? (
                                            <img
                                                src={service.cover_image}
                                                alt={service.title}
                                                className="h-48 w-full object-cover md:h-full"
                                            />
                                        ) : (
                                            <div className="flex h-48 w-full items-center justify-center text-gray-400 md:h-full">
                                                <svg
                                                    className="h-12 w-12"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                        strokeWidth="2"
                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                    ></path>
                                                </svg>
                                            </div>
                                        )}
                                        <div className="absolute top-3 left-3 rounded bg-white/90 px-2 py-1 text-xs font-bold text-gray-700 shadow-sm">
                                            ID: {service.id}
                                        </div>
                                    </div>

                                    {/* Middle: Details */}
                                    <div className="flex flex-1 flex-col justify-between p-6">
                                        <div>
                                            <div className="mb-2 flex items-center gap-3">
                                                <span className="rounded-full border border-indigo-100 bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700">
                                                    {service.category?.name ||
                                                        'Uncategorized'}
                                                </span>
                                                <span className="text-xs text-gray-500">
                                                    Submitted:{' '}
                                                    {new Date(
                                                        service.created_at,
                                                    ).toLocaleDateString()}
                                                </span>
                                            </div>
                                            <h3 className="mb-2 text-xl font-bold text-gray-900">
                                                {service.title}
                                            </h3>

                                            <div className="mb-4 flex items-center gap-2">
                                                <div className="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">
                                                    {service.seller?.name?.charAt(
                                                        0,
                                                    ) || '?'}
                                                </div>
                                                <span className="text-sm font-medium text-gray-700">
                                                    Seller:{' '}
                                                    <span className="text-gray-900">
                                                        {service.seller?.name}
                                                    </span>
                                                </span>
                                            </div>

                                            <div className="mb-4 rounded-lg border border-gray-100 bg-gray-50 p-4">
                                                <h4 className="mb-2 text-xs font-bold tracking-wider text-gray-500 uppercase">
                                                    Description Preview
                                                </h4>
                                                <p className="line-clamp-3 text-sm text-gray-700">
                                                    {service.description}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Right: Actions */}
                                    <div className="flex flex-col justify-center gap-3 border-t border-gray-100 bg-gray-50 p-6 md:w-56 md:border-t-0 md:border-l">
                                        <button
                                            onClick={() =>
                                                handleApprove(service.id)
                                            }
                                            className="flex w-full items-center justify-center gap-2 rounded-lg bg-green-600 px-4 py-2.5 font-bold text-white transition hover:bg-green-700"
                                        >
                                            <svg
                                                className="h-5 w-5"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth="2"
                                                    d="M5 13l4 4L19 7"
                                                ></path>
                                            </svg>
                                            Approve
                                        </button>
                                        <button
                                            onClick={() =>
                                                confirmReject(service.id)
                                            }
                                            className="flex w-full items-center justify-center gap-2 rounded-lg border border-red-200 bg-white px-4 py-2.5 font-bold text-red-600 transition hover:border-red-300 hover:bg-red-50"
                                        >
                                            <svg
                                                className="h-5 w-5"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth="2"
                                                    d="M6 18L18 6M6 6l12 12"
                                                ></path>
                                            </svg>
                                            Reject
                                        </button>

                                        <a
                                            href={route(
                                                'marketplace.services.show',
                                                service.id,
                                            )}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="mt-2 text-center text-sm font-medium text-indigo-600 hover:text-indigo-800"
                                        >
                                            View Full Service &rarr;
                                        </a>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="rounded-xl border border-gray-200 bg-white p-12 text-center shadow-sm">
                            <svg
                                className="mx-auto mb-4 h-16 w-16 text-gray-300"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
                                ></path>
                            </svg>
                            <h3 className="mb-1 text-lg font-medium text-gray-900">
                                All Caught Up!
                            </h3>
                            <p className="text-gray-500">
                                There are no pending services requiring review
                                at this time.
                            </p>
                        </div>
                    )}

                    {/* Pagination */}
                    {services.links && services.links.length > 3 && (
                        <div className="mt-8 flex justify-center gap-2">
                            {services.links.map((link: any, idx: number) => (
                                <button
                                    key={idx}
                                    onClick={() => {
                                        if (link.url) router.get(link.url);
                                    }}
                                    disabled={!link.url}
                                    className={`rounded-md border px-4 py-2 text-sm font-medium transition ${link.active ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'} ${!link.url ? 'cursor-not-allowed opacity-50' : ''}`}
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>

            {/* Rejection Modal */}
            <Modal
                show={rejectingServiceId !== null}
                onClose={() => setRejectingServiceId(null)}
            >
                <form onSubmit={submitReject} className="p-6">
                    <h2 className="mb-4 flex items-center gap-2 text-lg font-medium text-gray-900">
                        <svg
                            className="h-6 w-6 text-red-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            ></path>
                        </svg>
                        Reject Service
                    </h2>

                    <p className="mt-1 mb-4 text-sm text-gray-600">
                        Please provide a reason for rejecting this service. This
                        note will be visible to the seller so they can correct
                        the issues and resubmit.
                    </p>

                    <div className="mt-4">
                        <label htmlFor="rejectionNote" className="sr-only">
                            Rejection Note
                        </label>
                        <textarea
                            id="rejectionNote"
                            name="rejectionNote"
                            value={rejectionNote}
                            onChange={(e) => setRejectionNote(e.target.value)}
                            className="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            rows={4}
                            placeholder="e.g., Description is too vague, cover image violates guidelines..."
                            required
                        />
                    </div>

                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton
                            onClick={() => setRejectingServiceId(null)}
                        >
                            Cancel
                        </SecondaryButton>
                        <DangerButton type="submit">
                            Confirm Rejection
                        </DangerButton>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}

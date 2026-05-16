import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';

export default function Pending({ services }: any) {
    const [rejectingServiceId, setRejectingServiceId] = useState<number | null>(null);
    const [rejectionNote, setRejectionNote] = useState('');

    const handleApprove = (id: number) => {
        if(confirm('Are you sure you want to approve this service? It will become publicly available immediately.')) {
            router.post(route('admin.marketplace.services.approve', id));
        }
    };

    const confirmReject = (id: number) => {
        setRejectingServiceId(id);
        setRejectionNote('');
    };

    const submitReject = (e: React.FormEvent) => {
        e.preventDefault();
        if(rejectingServiceId) {
            router.post(route('admin.marketplace.services.reject', rejectingServiceId), { note: rejectionNote }, {
                onSuccess: () => setRejectingServiceId(null)
            });
        }
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-2xl font-bold leading-tight text-gray-800">Pending Services Review</h2>}>
            <Head title="Pending Services Review" />

            <div className="py-12 bg-gray-50 min-h-screen">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">

                    <div className="mb-6 flex justify-between items-center">
                        <p className="text-gray-600 text-sm">Review newly submitted services to ensure they meet platform quality guidelines.</p>
                        <span className="bg-indigo-100 text-indigo-800 text-xs font-bold px-3 py-1 rounded-full">
                            {services.total || 0} Pending
                        </span>
                    </div>

                    {services.data && services.data.length > 0 ? (
                        <div className="flex flex-col gap-6">
                            {services.data.map((service: any) => (
                                <div key={service.id} className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col md:flex-row hover:shadow-md transition">
                                    {/* Left: Image/Preview */}
                                    <div className="md:w-64 bg-gray-100 flex-shrink-0 relative">
                                        {service.cover_image ? (
                                            <img src={service.cover_image} alt={service.title} className="w-full h-48 md:h-full object-cover" />
                                        ) : (
                                            <div className="w-full h-48 md:h-full flex items-center justify-center text-gray-400">
                                                <svg className="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            </div>
                                        )}
                                        <div className="absolute top-3 left-3 bg-white/90 px-2 py-1 text-xs font-bold text-gray-700 rounded shadow-sm">
                                            ID: {service.id}
                                        </div>
                                    </div>

                                    {/* Middle: Details */}
                                    <div className="p-6 flex-1 flex flex-col justify-between">
                                        <div>
                                            <div className="flex items-center gap-3 mb-2">
                                                <span className="bg-indigo-50 text-indigo-700 px-2.5 py-0.5 rounded-full text-xs font-medium border border-indigo-100">
                                                    {service.category?.name || 'Uncategorized'}
                                                </span>
                                                <span className="text-xs text-gray-500">
                                                    Submitted: {new Date(service.created_at).toLocaleDateString()}
                                                </span>
                                            </div>
                                            <h3 className="text-xl font-bold text-gray-900 mb-2">{service.title}</h3>

                                            <div className="flex items-center gap-2 mb-4">
                                                <div className="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center text-xs font-bold text-indigo-700">
                                                    {service.seller?.name?.charAt(0) || '?'}
                                                </div>
                                                <span className="text-sm font-medium text-gray-700">Seller: <span className="text-gray-900">{service.seller?.name}</span></span>
                                            </div>

                                            <div className="bg-gray-50 p-4 rounded-lg border border-gray-100 mb-4">
                                                <h4 className="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Description Preview</h4>
                                                <p className="text-sm text-gray-700 line-clamp-3">
                                                    {service.description}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Right: Actions */}
                                    <div className="p-6 md:w-56 bg-gray-50 md:border-l border-t md:border-t-0 border-gray-100 flex flex-col justify-center gap-3">
                                        <button
                                            onClick={() => handleApprove(service.id)}
                                            className="w-full bg-green-600 text-white font-bold py-2.5 px-4 rounded-lg hover:bg-green-700 transition flex items-center justify-center gap-2"
                                        >
                                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path></svg>
                                            Approve
                                        </button>
                                        <button
                                            onClick={() => confirmReject(service.id)}
                                            className="w-full bg-white border border-red-200 text-red-600 font-bold py-2.5 px-4 rounded-lg hover:bg-red-50 hover:border-red-300 transition flex items-center justify-center gap-2"
                                        >
                                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            Reject
                                        </button>

                                        <a href={route('marketplace.services.show', service.id)} target="_blank" rel="noreferrer" className="mt-2 text-center text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                            View Full Service &rarr;
                                        </a>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                            <svg className="mx-auto h-16 w-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                            <h3 className="text-lg font-medium text-gray-900 mb-1">All Caught Up!</h3>
                            <p className="text-gray-500">There are no pending services requiring review at this time.</p>
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
                                    className={`px-4 py-2 border rounded-md text-sm font-medium transition ${link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'} ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>

            {/* Rejection Modal */}
            <Modal show={rejectingServiceId !== null} onClose={() => setRejectingServiceId(null)}>
                <form onSubmit={submitReject} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900 mb-4 flex items-center gap-2">
                        <svg className="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        Reject Service
                    </h2>

                    <p className="mt-1 text-sm text-gray-600 mb-4">
                        Please provide a reason for rejecting this service. This note will be visible to the seller so they can correct the issues and resubmit.
                    </p>

                    <div className="mt-4">
                        <label htmlFor="rejectionNote" className="sr-only">Rejection Note</label>
                        <textarea
                            id="rejectionNote"
                            name="rejectionNote"
                            value={rejectionNote}
                            onChange={(e) => setRejectionNote(e.target.value)}
                            className="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            rows={4}
                            placeholder="e.g., Description is too vague, cover image violates guidelines..."
                            required
                        />
                    </div>

                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setRejectingServiceId(null)}>Cancel</SecondaryButton>
                        <DangerButton type="submit">Confirm Rejection</DangerButton>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}

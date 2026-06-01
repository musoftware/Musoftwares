import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import Modal from '@/Components/Modal';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Search, FilterX, Check, X, Eye, FileWarning } from 'lucide-react';
import { DateDisplay } from '@/Components/ui/DateDisplay';
import { Badge } from '@/Components/ui/badge';

export default function Pending({ auth, services, filters }: any) {
    const [search, setSearch] = useState(filters?.search || '');
    const [rejectingServiceId, setRejectingServiceId] = useState<number | null>(null);
    const [rejectionNote, setRejectionNote] = useState('');

    const applySearch = () => {
        const query = { search };
        if (!query.search) delete query.search;
        router.get(route('admin.marketplace.services.pending'), query, {
            preserveState: true,
            preserveScroll: true,
            replace: true
        });
    };

    const handleApprove = (id: number) => {
        if (confirm('Are you sure you want to approve this service? It will become publicly available immediately.')) {
            router.post(route('admin.marketplace.services.approve', id), {}, { preserveScroll: true });
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
                    preserveScroll: true,
                    onSuccess: () => setRejectingServiceId(null),
                },
            );
        }
    };

    const clearSearch = () => {
        setSearch('');
        router.get(route('admin.marketplace.services.pending'));
    };

    return (
        <AdminSidebarLayout user={auth?.user} title={__('general.pending_services')} header="Pending Services">
            <div className="py-8 bg-slate-50 min-h-screen">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                    
                    <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div className="flex-1 max-w-md w-full">
                            <div className="relative flex items-center">
                                <Search className="absolute left-3 h-4 w-4 text-slate-400" />
                                <Input
                                    placeholder={__('general.search_by_title_or_seller')}
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && applySearch()}
                                    className="pl-9 bg-white"
                                />
                                {search && (
                                    <Button variant="ghost" size="icon" onClick={clearSearch} className="absolute right-1 h-7 w-7 text-slate-400 hover:text-black">
                                        <X className="h-4 w-4" />
                                    </Button>
                                )}
                            </div>
                        </div>
                        <div className="shrink-0">
                            <Badge variant="secondary" className="bg-slate-200 text-slate-800 text-sm px-3 py-1">
                                {services.total || 0} Pending
                            </Badge>
                        </div>
                    </div>

                    {services.data && services.data.length > 0 ? (
                        <div className="grid gap-6">
                            {services.data.map((service: any) => (
                                <div
                                    key={service.id}
                                    className="flex flex-col md:flex-row overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition-all hover:shadow-md"
                                >
                                    {/* Left: Image/Preview */}
                                    <div className="relative flex-shrink-0 bg-slate-100 md:w-64 border-b md:border-b-0 md:border-r border-slate-200">
                                        {service.gallery && service.gallery.length > 0 ? (
                                            <img
                                                src={`/storage/${service.gallery[0]}`}
                                                alt={service.title}
                                                className="h-48 w-full object-cover md:h-full"
                                            />
                                        ) : (
                                            <div className="flex h-48 w-full items-center justify-center text-slate-300 md:h-full bg-slate-50">
                                                <FileWarning className="h-12 w-12" />
                                            </div>
                                        )}
                                        <div className="absolute top-3 left-3 rounded bg-white/90 px-2 py-1 text-xs font-bold text-slate-700 shadow-sm border border-slate-200">
                                            ID: {service.id}
                                        </div>
                                    </div>

                                    {/* Middle: Details */}
                                    <div className="flex flex-1 flex-col justify-between p-6">
                                        <div>
                                            <div className="mb-2 flex items-center gap-3">
                                                <Badge variant="outline" className="bg-slate-50 text-slate-700 border-slate-200">
                                                    {service.category?.name || 'Uncategorized'}
                                                </Badge>
                                                <span className="text-xs text-slate-500 flex items-center gap-1">
                                                    Submitted: <DateDisplay date={service.created_at} format="MMM D, YYYY" />
                                                </span>
                                            </div>
                                            
                                            <h3 className="mb-2 text-xl font-bold text-slate-900 truncate max-w-xl" title={service.title}>
                                                {service.title}
                                            </h3>

                                            <div className="mb-4 flex items-center gap-2">
                                                <div className="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-700 border border-slate-200">
                                                    {service.seller?.name?.charAt(0) || '?'}
                                                </div>
                                                <span className="text-sm text-slate-500">
                                                    Seller:{' '}
                                                    <span className="font-medium text-slate-900">
                                                        {service.seller?.name || <span className="italic text-slate-400">Unknown</span>}
                                                    </span>
                                                </span>
                                            </div>

                                            <div className="mb-4 rounded-lg border border-slate-100 bg-slate-50/50 p-4">
                                                <h4 className="mb-2 text-xs font-bold tracking-wider text-slate-500 uppercase">{__('general.description_preview')}</h4>
                                                <p className="line-clamp-3 text-sm text-slate-700">
                                                    {service.description}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Right: Actions */}
                                    <div className="flex flex-col justify-center gap-3 bg-slate-50 p-6 md:w-56 border-t md:border-t-0 md:border-l border-slate-100">
                                        <Button
                                            onClick={() => handleApprove(service.id)}
                                            className="w-full bg-green-600 hover:bg-green-700 text-white font-bold gap-2 shadow-sm"
                                        >
                                            <Check className="h-4 w-4" />
                                            Approve
                                        </Button>
                                        
                                        <Button
                                            variant="outline"
                                            onClick={() => confirmReject(service.id)}
                                            className="w-full border-red-200 text-red-600 hover:bg-red-50 hover:text-red-700 font-bold gap-2 bg-white"
                                        >
                                            <X className="h-4 w-4" />
                                            Reject
                                        </Button>

                                        <a
                                            href={route('marketplace.services.show', service.id)}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="mt-2 flex items-center justify-center gap-1 text-sm font-medium text-slate-600 hover:text-black transition-colors"
                                        >
                                            <Eye className="h-4 w-4" />{__('general.view_full_service')}</a>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="rounded-xl border border-slate-200 bg-white p-12 text-center shadow-sm">
                            <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-50">
                                {search ? (
                                    <Search className="h-8 w-8 text-slate-400" />
                                ) : (
                                    <Check className="h-8 w-8 text-green-500" />
                                )}
                            </div>
                            <h3 className="mb-1 text-lg font-medium text-slate-900">
                                {search ? 'No matches found' : 'All Caught Up!'}
                            </h3>
                            <p className="text-slate-500">
                                {search ? 'Try adjusting your search query.' : 'There are no pending services requiring review at this time.'}
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
                                        if (link.url) router.get(link.url, {}, { preserveScroll: true });
                                    }}
                                    disabled={!link.url}
                                    className={`rounded-md px-4 py-2 text-sm font-medium transition ${
                                        link.active 
                                            ? 'bg-slate-900 text-white shadow-sm' 
                                            : !link.url 
                                                ? 'cursor-not-allowed text-slate-300' 
                                                : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:text-slate-900'
                                    }`}
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
                    <h2 className="mb-4 flex items-center gap-2 text-lg font-medium text-slate-900">
                        <X className="h-6 w-6 text-red-500" />{__('general.reject_service')}</h2>

                    <p className="mt-1 mb-4 text-sm text-slate-600">{__('general.please_provide_a_reason_for_rejecting_this_service_this_note_will_be_visible_to_the_seller_so_they_can_correct_the_issues_and_resubmit')}</p>

                    <div className="mt-4">
                        <label htmlFor="rejectionNote" className="sr-only">{__('general.rejection_note')}</label>
                        <textarea
                            id="rejectionNote"
                            name="rejectionNote"
                            value={rejectionNote}
                            onChange={(e) => setRejectionNote(e.target.value)}
                            className="w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 text-sm"
                            rows={4}
                            placeholder={__('general.e_g_description_is_too_vague_cover_image_violates_guidelines')}
                            required
                        />
                    </div>

                    <div className="mt-6 flex justify-end gap-3">
                        <Button type="button" variant="outline" onClick={() => setRejectingServiceId(null)}>
                            Cancel
                        </Button>
                        <Button type="submit" className="bg-red-600 hover:bg-red-700 text-white">{__('general.confirm_rejection')}</Button>
                    </div>
                </form>
            </Modal>
        </AdminSidebarLayout>
    );
}

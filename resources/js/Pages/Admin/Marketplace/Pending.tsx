import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import Modal from '@/Components/Modal';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Search, FilterX, Check, X, Eye, FileWarning, Pencil, Inbox } from 'lucide-react';
import { DateDisplay } from '@/Components/ui/DateDisplay';
import { Badge } from '@/Components/ui/badge';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { EmptyState } from '@/Components/ui/EmptyState';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';
import { toast } from 'sonner';
import { __ } from '@/lib/i18n';

export default function Pending({ auth, services, filters }: any) {
    const [search, setSearch] = useState(filters?.search || '');
    const [rejectingServiceId, setRejectingServiceId] = useState<number | null>(null);
    const [pendingApprove, setPendingApprove] = useState<number | null>(null);
    const [rejectionNote, setRejectionNote] = useState('');

    const applySearch = () => {
        const query = { search };
        if (!query.search) delete query.search;
        router.get(route('admin.marketplace.services.pending'), query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const confirmApprove = (id: number) => setPendingApprove(id);

    const handleApprove = () => {
        if (!pendingApprove) return;
        router.post(route('admin.marketplace.services.approve', pendingApprove), {}, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(__('general.approved') || 'Approved');
                setPendingApprove(null);
            },
            onError: () => {
                toast.error(__('general.error_occurred') || 'Something went wrong');
                setPendingApprove(null);
            },
        });
    };

    const confirmReject = (id: number) => {
        setRejectingServiceId(id);
        setRejectionNote('');
    };

    const submitReject = (e: React.FormEvent) => {
        e.preventDefault();
        if (!rejectingServiceId || !rejectionNote.trim()) return;
        router.post(
            route('admin.marketplace.services.reject', rejectingServiceId),
            { note: rejectionNote },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success(__('general.rejected') || 'Rejected');
                    setRejectingServiceId(null);
                    setRejectionNote('');
                },
                onError: () => toast.error(__('general.error_occurred') || 'Something went wrong'),
            },
        );
    };

    const clearSearch = () => {
        setSearch('');
        router.get(route('admin.marketplace.services.pending'));
    };

    return (
        <AdminSidebarLayout user={auth?.user} title={__('general.pending_services')} header="Pending Services">
            <Head title={__('general.pending_services')} />
            <div className="py-8 bg-slate-50 min-h-screen">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                    <div className="flex flex-col sm:flex-row items-center justify-end gap-4">
                        <div className="flex-1 max-w-md w-full">
                            <div className="relative flex items-center">
                                <Search className="absolute start-3 h-4 w-4 text-slate-400" />
                                <Input
                                    placeholder={__('general.search_by_title_or_seller')}
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && applySearch()}
                                    className="ps-9 bg-white"
                                />
                                {search && (
                                    <Button variant="ghost" size="icon" onClick={clearSearch} className="absolute end-1 h-7 w-7 text-slate-400 hover:text-black">
                                        <X className="h-4 w-4" />
                                    </Button>
                                )}
                            </div>
                        </div>
                        <Badge variant="secondary" className="bg-slate-200 text-slate-800 text-sm px-3 py-1 shrink-0">
                            {services.total || 0} {__('general.pending')}
                        </Badge>
                    </div>

                    {services.data && (services.data as any).length > 0 ? (
                        <div className="grid gap-6">
                            {(services.data as any).map((service: any) => (
                                <div
                                    key={service.id}
                                    className="flex flex-col md:flex-row overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition-all hover:shadow-md"
                                >
                                    <div className="relative shrink-0 bg-slate-100 md:w-64 border-b md:border-b-0 md:border-e border-slate-200">
                                        {service.gallery && service.gallery.length > 0 ? (
                                            <img
                                                src={service.gallery[0].startsWith('http') ? service.gallery[0] : (service.gallery[0].startsWith('/') ? service.gallery[0] : (service.gallery[0].startsWith('services/') ? `/uploads/${service.gallery[0]}` : `/${service.gallery[0]}`))}
                                                alt={service.title}
                                                className="h-48 w-full object-cover md:h-full"
                                            />
                                        ) : (
                                            <div className="flex h-48 w-full items-center justify-center text-slate-300 md:h-full bg-slate-50">
                                                <FileWarning className="h-12 w-12" />
                                            </div>
                                        )}
                                        <div className="absolute top-3 start-3 rounded bg-white/90 px-2 py-1 text-xs font-bold text-slate-700 shadow-sm border border-slate-200">
                                            ID: {service.id}
                                        </div>
                                    </div>

                                    <div className="flex flex-1 flex-col justify-between p-6">
                                        <div>
                                            <div className="mb-2 flex items-center gap-3">
                                                <Badge variant="outline" className="bg-slate-50 text-slate-700 border-slate-200">
                                                    {service.category?.name || __('general.uncategorized')}
                                                </Badge>
                                                <span className="text-xs text-slate-500 flex items-center gap-1">
                                                    {__('general.submitted')}: <DateDisplay date={service.created_at} />
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
                                                    {__('general.seller')}:{' '}
                                                    <span className="font-medium text-slate-900">
                                                        {service.seller?.name || <span className="italic text-slate-400">{__('general.unknown')}</span>}
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

                                    <div className="flex flex-col justify-center gap-3 bg-slate-50 p-6 md:w-56 border-t md:border-t-0 md:border-s border-slate-100">
                                        <Button
                                            onClick={() => confirmApprove(service.id)}
                                            className="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold gap-2 shadow-sm"
                                        >
                                            <Check className="h-4 w-4" />
                                            {__('general.approve')}
                                        </Button>

                                        <Button
                                            variant="outline"
                                            onClick={() => confirmReject(service.id)}
                                            className="w-full border-red-200 text-red-600 hover:bg-red-50 hover:text-red-700 font-bold gap-2 bg-white"
                                        >
                                            <X className="h-4 w-4" />
                                            {__('general.reject')}
                                        </Button>

                                        <Button
                                            variant="outline"
                                            onClick={() => router.get(route('admin.marketplace.services.edit', service.id))}
                                            className="w-full font-bold gap-2 bg-white"
                                        >
                                            <Pencil className="h-4 w-4" />
                                            {__('general.edit')}
                                        </Button>

                                        <a
                                            href={route('marketplace.services.show', service.id)}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="mt-2 flex items-center justify-center gap-1 text-sm font-medium text-slate-600 hover:text-black transition-colors"
                                        >
                                            <Eye className="h-4 w-4" />{__('general.view_full_service')}
                                        </a>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <EmptyState
                            icon={search ? Search : Inbox}
                            title={search ? __('general.no_matches_found') || 'No matches found' : __('general.all_caught_up') || 'All caught up!'}
                            description={search ? __('general.try_adjusting_search') || 'Try adjusting your search query.' : __('general.no_pending_services') || 'There are no pending services requiring review.'}
                        />
                    )}

                    {services.links && services.links.length > 3 && (
                        <div className="mt-8 flex justify-center gap-2">
                            {services.links.map((link: any, idx: number) => (
                                <button
                                    key={idx}
                                    onClick={() => { if (link.url) router.get(link.url, {}, { preserveScroll: true }); }}
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

            <ConfirmModal
                isOpen={pendingApprove !== null}
                title={__('general.approve_service') || 'Approve service?'}
                description={__('general.approve_service_desc_detailed') || 'This service will become publicly available immediately.'}
                confirmLabel={__('general.approve')}
                cancelLabel={__('general.cancel')}
                onConfirm={handleApprove}
                onCancel={() => setPendingApprove(null)}
            />

            <Modal show={rejectingServiceId !== null} onClose={() => setRejectingServiceId(null)}>
                <form onSubmit={submitReject} className="p-6">
                    <h2 className="mb-4 flex items-center gap-2 text-lg font-medium text-slate-900">
                        <X className="h-6 w-6 text-red-500" />{__('general.reject_service')}
                    </h2>

                    <p className="mt-1 mb-4 text-sm text-slate-600">
                        {__('general.please_provide_a_reason_for_rejecting_this_service_this_note_will_be_visible_to_the_seller_so_they_can_correct_the_issues_and_resubmit')}
                    </p>

                    <div className="mt-4">
                        <Label htmlFor="rejectionNote">{__('general.rejection_note')}</Label>
                        <Textarea
                            id="rejectionNote"
                            value={rejectionNote}
                            onChange={(e) => setRejectionNote(e.target.value)}
                            className="mt-1"
                            rows={4}
                            placeholder={__('general.e_g_description_is_too_vague_cover_image_violates_guidelines')}
                            required
                        />
                    </div>

                    <div className="mt-6 flex justify-end gap-3">
                        <Button type="button" variant="outline" onClick={() => setRejectingServiceId(null)}>
                            {__('general.cancel')}
                        </Button>
                        <Button type="submit" className="bg-red-600 hover:bg-red-700 text-white">
                            {__('general.confirm_rejection')}
                        </Button>
                    </div>
                </form>
            </Modal>
        </AdminSidebarLayout>
    );
}
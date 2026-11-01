import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Textarea } from '@/Components/ui/textarea';
import { __ } from '@/lib/i18n';

interface AdminDsrRow {
    id: number;
    user_id: number | null;
    user_name: string;
    user_email: string;
    user_deleted: boolean;
    request_type: string;
    status: string;
    details: string | null;
    admin_notes: string | null;
    requested_at: string | null;
    due_at: string | null;
    completed_at: string | null;
    processed_by_name: string | null;
}

interface PaginatedRequests {
    data: AdminDsrRow[];
    current_page: number;
    last_page: number;
    total: number;
}

const DSR_TYPE_LABELS: Record<string, string> = {
    access_data: 'privacy_compliance.dsr_type_access_data',
    update_data: 'privacy_compliance.dsr_type_update_data',
    delete_account: 'privacy_compliance.dsr_type_delete_account',
    restrict_processing: 'privacy_compliance.dsr_type_restrict_processing',
};

const DSR_STATUS_LABELS: Record<string, string> = {
    pending: 'privacy_compliance.dsr_status_pending',
    in_progress: 'privacy_compliance.dsr_status_in_progress',
    completed: 'privacy_compliance.dsr_status_completed',
    rejected_with_reason: 'privacy_compliance.dsr_status_rejected_with_reason',
};

export default function AdminPrivacyRequests({
    requests,
}: {
    requests: PaginatedRequests;
    currentStatus?: string;
}) {
    const [notesById, setNotesById] = useState<Record<number, string>>({});
    const [busyId, setBusyId] = useState<number | null>(null);

    const resolveRow = (row: AdminDsrRow, nextStatus: string) => {
        setBusyId(row.id);
        const adminNotes = notesById[row.id] ?? row.admin_notes ?? '';

        router.patch(
            route('admin.privacy-requests.update', { dataSubjectRequest: row.id }),
            { status: nextStatus, admin_notes: adminNotes },
            {
                preserveScroll: true,
                onFinish: () => setBusyId(null),
            },
        );
    };

    return (
        <AdminSidebarLayout>
            <Head title={__('privacy_compliance.admin_dsr_page_title')} />

            <div className="space-y-6 p-4 sm:p-6 overflow-x-clip">
                <header className="flex items-start gap-3">
                    <div className="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-border bg-muted text-foreground">
                        <ShieldCheck className="h-5 w-5" aria-hidden="true" />
                    </div>
                    <div className="min-w-0 flex-1">
                        <h1 className="text-xl sm:text-2xl font-bold tracking-tight">
                            {__('privacy_compliance.admin_dsr_page_title')}
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {__('privacy_compliance.admin_dsr_page_subtitle')}
                        </p>
                    </div>
                </header>

                <div className="overflow-x-auto rounded-xl border border-border bg-card">
                    <table className="w-full min-w-[880px] text-start text-xs sm:text-sm">
                        <thead className="border-b border-border bg-muted/50 text-muted-foreground">
                            <tr>
                                <th className="px-4 py-3 text-start font-semibold">#</th>
                                <th className="px-4 py-3 text-start font-semibold">{__('privacy_compliance.admin_dsr_col_user')}</th>
                                <th className="px-4 py-3 text-start font-semibold">{__('privacy_compliance.dsr_col_type')}</th>
                                <th className="px-4 py-3 text-start font-semibold">{__('privacy_compliance.dsr_col_status')}</th>
                                <th className="px-4 py-3 text-start font-semibold">{__('privacy_compliance.dsr_col_requested_at')}</th>
                                <th className="px-4 py-3 text-start font-semibold">{__('privacy_compliance.dsr_col_due_at')}</th>
                                <th className="px-4 py-3 text-start font-semibold">{__('privacy_compliance.admin_dsr_col_completed_at')}</th>
                                <th className="px-4 py-3 text-start font-semibold">{__('privacy_compliance.admin_dsr_col_actions')}</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {requests.data.length === 0 ? (
                                <tr>
                                    <td colSpan={8} className="px-4 py-8 text-center text-muted-foreground">
                                        {__('privacy_compliance.admin_dsr_empty')}
                                    </td>
                                </tr>
                            ) : (
                                requests.data.map((row) => {
                                    const isTerminal = row.status === 'completed' || row.status === 'rejected_with_reason';
                                    const isBusy = busyId === row.id;

                                    return (
                                        <tr key={row.id} className="align-top">
                                            <td className="px-4 py-3 font-mono text-xs">#{row.id}</td>
                                            <td className="px-4 py-3">
                                                <div className="font-semibold">{row.user_name}</div>
                                                <div className="text-xs text-muted-foreground" dir="ltr">{row.user_email}</div>
                                                {row.details && (
                                                    <p className="mt-1 text-xs text-muted-foreground">
                                                        {__('privacy_compliance.admin_dsr_col_details')}: {row.details}
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 font-medium">
                                                {__(DSR_TYPE_LABELS[row.request_type] ?? row.request_type)}
                                            </td>
                                            <td className="px-4 py-3">
                                                {__(DSR_STATUS_LABELS[row.status] ?? row.status)}
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-xs" dir="ltr">
                                                {row.requested_at ?? '-'}
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-xs font-medium" dir="ltr">
                                                {row.due_at ?? '-'}
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-xs" dir="ltr">
                                                {row.completed_at ?? '-'}
                                            </td>
                                            <td className="px-4 py-3 min-w-[260px] space-y-2">
                                                <Textarea
                                                    rows={2}
                                                    disabled={isTerminal || isBusy}
                                                    value={notesById[row.id] ?? row.admin_notes ?? ''}
                                                    onChange={(e) =>
                                                        setNotesById((prev) => ({ ...prev, [row.id]: e.target.value }))
                                                    }
                                                    placeholder={__('privacy_compliance.admin_dsr_reason_placeholder')}
                                                    className="text-xs"
                                                />
                                                {!isTerminal && (
                                                    <div className="flex flex-wrap gap-1.5">
                                                        {row.status === 'pending' && (
                                                            <Button
                                                                type="button"
                                                                size="sm"
                                                                variant="outline"
                                                                disabled={isBusy}
                                                                onClick={() => resolveRow(row, 'in_progress')}
                                                                className="text-xs"
                                                            >
                                                                {__('privacy_compliance.admin_dsr_mark_in_progress')}
                                                            </Button>
                                                        )}
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            disabled={isBusy}
                                                            onClick={() => resolveRow(row, 'completed')}
                                                            className="text-xs"
                                                        >
                                                            {__('privacy_compliance.admin_dsr_approve_complete')}
                                                        </Button>
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="destructive"
                                                            disabled={isBusy}
                                                            onClick={() => resolveRow(row, 'rejected_with_reason')}
                                                            className="text-xs"
                                                        >
                                                            {__('privacy_compliance.admin_dsr_reject_with_reason')}
                                                        </Button>
                                                    </div>
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}

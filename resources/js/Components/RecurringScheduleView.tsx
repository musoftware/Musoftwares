import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { ArrowLeft, Calendar, Clock, User, List, History, AlertCircle, Edit, Trash2, X, Play } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import { formatMoney } from '@/lib/utils';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { toast } from 'sonner';
import { __ } from '@/lib/i18n';

export interface RecurringViewProps {
    kind: 'salary' | 'invoice';
    item: any;
    records?: any[];
    transactions?: any[];
    upcomingSchedule?: any[];
    total_stat: any;
    backHref: string;
    backLabelKey: string;
    editRoute: string;
    deleteRoute: string;
    deleteRecordRoute: string;
    headerTitle: string;
    userLabelFallback: string;
    showReasonColumn?: boolean;
}

export function RecurringScheduleView({
    kind,
    item,
    records,
    transactions,
    upcomingSchedule,
    total_stat,
    backHref,
    backLabelKey,
    editRoute,
    deleteRoute,
    deleteRecordRoute,
    headerTitle,
    userLabelFallback,
    showReasonColumn = false,
}: RecurringViewProps) {
    const historyItems: any[] = records ?? transactions ?? [];
    const scheduleItems: any[] = Array.isArray(upcomingSchedule) ? upcomingSchedule : [];
    const [pendingDelete, setPendingDelete] = useState<number | null>(null);
    const [generating, setGenerating] = useState(false);

    const handleGenerateMissing = () => {
        const genRoute = kind === 'salary' ? 'admin.recurring_salaries.generate_missing' : 'admin.recurring_invoices.generate_missing';
        if (confirm(__('general.confirm_generate_missing') || 'Are you sure you want to generate all missing past transactions up to today for this schedule?')) {
            setGenerating(true);
            router.post(route(genRoute, item.id), {}, {
                preserveScroll: true,
                onFinish: () => setGenerating(false),
            });
        }
    };

    const handleDelete = () => {
        router.delete(route(deleteRoute, item.id), {
            onSuccess: () => toast.success(__('general.deleted') || 'Deleted'),
            onError: () => toast.error(__('general.error_occurred') || 'Something went wrong'),
        });
    };

    const handleRemoveRecord = (recordId: number) => {
        setPendingDelete(recordId);
    };

    const confirmRemoveRecord = () => {
        if (!pendingDelete) return;
        router.delete(
            route(deleteRecordRoute, { invoice: item.id, record: pendingDelete }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success(__('general.removed') || 'Removed');
                    setPendingDelete(null);
                },
                onError: () => {
                    toast.error(__('general.error_occurred') || 'Something went wrong');
                    setPendingDelete(null);
                },
            },
        );
    };

    const userName = item.user?.name || userLabelFallback;

    return (
        <AdminSidebarLayout
            title={`${userName} - ${headerTitle}`}
            header="Business Operations"
        >
            <Head title={`View Recurring - ${userName}`} />

            <div className="mb-4 flex justify-between items-center">
                <Link href={backHref} className="text-sm text-gray-500 hover:text-black flex items-center gap-1">
                    <ArrowLeft className="w-4 h-4" />{__(backLabelKey)}
                </Link>
                <div className="flex items-center gap-2">
                    <Button variant="default" size="sm" className="bg-slate-900 hover:bg-slate-800 text-white flex items-center gap-1.5 shadow-sm" onClick={handleGenerateMissing} disabled={generating}>
                        <Play className="w-3.5 h-3.5 fill-current" />
                        {generating ? (__('general.loading') || 'Generating...') : (__('general.generate_missing_transactions') || 'Generate Missing')}
                    </Button>
                    <Link href={route(editRoute, item.id)}>
                        <Button variant="outline" size="sm" className="flex items-center gap-1.5">
                            <Edit className="w-4 h-4" /> {__('general.edit')}
                        </Button>
                    </Link>
                    <Button variant="destructive" size="sm" className="flex items-center gap-1.5" onClick={handleDelete}>
                        <Trash2 className="w-4 h-4" />{__('general.delete_schedule')}
                    </Button>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div className="bg-white p-6 rounded-xl border shadow-sm md:col-span-2">
                    <h3 className="text-lg font-bold text-slate-900 mb-4 pb-2 border-b">{__('general.recurrence_overview')}</h3>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">{__('general.user')}</span>
                            <div className="flex items-center mt-1">
                                <div className="bg-slate-100 p-1.5 rounded-full me-2 text-slate-650 border">
                                    <User className="w-4 h-4" />
                                </div>
                                <div>
                                    <span className="text-sm font-semibold text-slate-800 block">{userName}</span>
                                    <span className="text-xs text-gray-500 block">{item.user?.email || 'N/A'}</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">{__('general.salary_rate')}</span>
                            <span className="text-sm font-bold text-slate-900 block mt-1 break-words">
                                {formatMoney(item.amount, item.currency)}
                            </span>
                        </div>
                        <div className="mt-2">
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">{__('general.title_description')}</span>
                            <span className="text-sm font-medium text-slate-800">{item.title}</span>
                        </div>
                        <div className="mt-2">
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">{__('general.recurrence_pattern')}</span>
                            <span className="text-sm font-medium text-slate-800 capitalize">{item.details}</span>
                        </div>
                        <div className="mt-2">
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">{__('general.note_reason')}</span>
                            <span className="text-sm font-medium text-slate-700 italic block mt-0.5">{item.reason || __('general.none')}</span>
                        </div>
                        <div className="mt-2">
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">{__('general.start_date')}</span>
                            <span className="text-sm font-medium text-slate-800 block mt-0.5">{new Date(item.start_date).toLocaleDateString()}</span>
                        </div>
                        <div className="mt-2">
                            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">{__('general.next_execution_date')}</span>
                            <span className="text-sm font-medium text-slate-800 flex items-center gap-1 mt-1">
                                <Clock className="w-4 h-4 text-slate-500" /> {item.next_date ? new Date(item.next_date).toLocaleDateString() : (item.current_date ? new Date(item.current_date).toLocaleDateString() : '—')}
                            </span>
                        </div>
                    </div>
                </div>

                <div className="bg-white p-6 rounded-xl border shadow-sm flex flex-col justify-between">
                    <div>
                        <h3 className="text-lg font-bold text-slate-900 mb-4 pb-2 border-b">{__('general.payroll_stats')}</h3>
                        <div className="space-y-4">
                            <div className="flex justify-between items-center">
                                <span className="text-sm text-gray-500">{__('general.payruns_executed')}</span>
                                <span className="text-sm font-bold text-slate-900">
                                    {total_stat?.entries_count ?? 0} {__('general.times')}
                                </span>
                            </div>
                            <div className="flex justify-between items-center">
                                <span className="text-sm text-gray-500">{__('general.cumulative_paid')}</span>
                                <span className="text-sm font-bold text-rose-700 bg-rose-50 px-2 py-0.5 border border-rose-100 rounded break-words">
                                    {total_stat?.total_cost ?? total_stat?.cumulative_paid ?? '—'}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div className="mt-4 pt-4 border-t text-xs text-gray-400 flex items-start gap-1">
                        <AlertCircle className="w-4 h-4 text-gray-400 shrink-0" />
                        <span>{kind === 'salary'
                            ? __('general.cumulative_paid_represents_the_total_amount_generated_in_transaction_records_for_this_employee')
                            : __('general.cumulative_paid_represents_the_total_amount_generated_in_transaction_records_for_this_user')}
                        </span>
                    </div>
                </div>
            </div>

            <div className="bg-white border rounded-xl shadow-sm overflow-hidden">
                <Tabs defaultValue="history">
                    <TabsList className="bg-slate-50 border-b w-full justify-start rounded-none">
                        <TabsTrigger value="history" className="gap-2">
                            <History className="w-4 h-4" /> {__('general.payroll_history')} ({historyItems.length})
                        </TabsTrigger>
                        <TabsTrigger value="schedule" className="gap-2">
                            <Calendar className="w-4 h-4" />{__('general.next_15_scheduled_runs')}
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="history" className="m-0 p-0">
                        {historyItems.length === 0 ? (
                            <div className="text-center py-8 text-gray-500">
                                <List className="w-8 h-8 mx-auto text-gray-300 mb-2" />
                                <p className="text-sm">{__('general.no_payroll_transactions_have_been_recorded_yet_for_this_salary_schedule')}</p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader className="bg-slate-50">
                                        <TableRow>
                                            <TableHead className="px-4 py-2 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.date_recorded')}</TableHead>
                                            <TableHead className="px-4 py-2 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.transaction_id')}</TableHead>
                                            {showReasonColumn && (
                                                <TableHead className="px-4 py-2 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.reason')}</TableHead>
                                            )}
                                            <TableHead className="px-4 py-2 text-end text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.amount')}</TableHead>
                                            <TableHead className="px-4 py-2 text-end text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.actions')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {historyItems.map((tx: any) => (
                                            <TableRow key={tx.id} className="hover:bg-slate-50">
                                                <TableCell className="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                                    {new Date(tx.created_at).toLocaleString()}
                                                </TableCell>
                                                <TableCell className="px-4 py-3 whitespace-nowrap text-sm font-mono text-slate-500">
                                                    #{tx.id}
                                                </TableCell>
                                                {showReasonColumn && (
                                                    <TableCell className="px-4 py-3 text-sm text-gray-900">
                                                        {tx.reason}
                                                    </TableCell>
                                                )}
                                                <TableCell className="px-4 py-3 whitespace-nowrap text-sm font-bold text-rose-600 text-end">
                                                    -{formatMoney(tx.amount, tx.currency)}
                                                </TableCell>
                                                <TableCell className="px-4 py-3 whitespace-nowrap text-end">
                                                    {kind === 'invoice' && (
                                                        <button
                                                            type="button"
                                                            onClick={() => handleRemoveRecord(tx.id)}
                                                            className="inline-flex items-center justify-center h-8 w-8 rounded-md text-red-500 hover:bg-red-50 hover:text-red-700 transition-colors"
                                                            aria-label={__('general.remove_transaction')}
                                                            title={__('general.remove_transaction')}
                                                        >
                                                            <X className="w-4 h-4" />
                                                        </button>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </TabsContent>

                    <TabsContent value="schedule" className="m-0 p-0">
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader className="bg-slate-50">
                                    <TableRow>
                                        <TableHead className="px-4 py-2 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.projected_date')}</TableHead>
                                        <TableHead className="px-4 py-2 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.status')}</TableHead>
                                        <TableHead className="px-4 py-2 text-end text-xs font-semibold text-gray-500 uppercase tracking-wider">{__('general.amount')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {scheduleItems.map((run: any, idx: number) => (
                                        <TableRow key={idx} className="hover:bg-slate-50">
                                            <TableCell className="px-4 py-3 whitespace-nowrap text-sm font-medium text-slate-800">
                                                {new Date(run.date).toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}
                                            </TableCell>
                                            <TableCell className="px-4 py-3 whitespace-nowrap text-sm">
                                                <span className={`text-xs px-2 py-0.5 rounded font-medium ${
                                                    run.recorded
                                                        ? 'bg-slate-100 text-slate-700 border border-slate-200'
                                                        : 'bg-blue-50 text-blue-700 border border-blue-200'
                                                }`}>
                                                    {run.recorded ? __('general.recorded_historical') : __('general.pending_execution')}
                                                </span>
                                            </TableCell>
                                            <TableCell className="px-4 py-3 whitespace-nowrap text-end">
                                                <span className={`text-sm font-bold ${run.recorded ? 'text-slate-900' : 'text-slate-400'}`}>
                                                    {run.amount_str}
                                                </span>
                                                <span className="block text-xs mt-0.5 text-gray-400">
                                                    {run.is_actual ? __('general.actual_amount') : __('general.estimated_amount')}
                                                </span>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    </TabsContent>
                </Tabs>
            </div>

            <ConfirmModal
                isOpen={pendingDelete !== null}
                title={__('general.confirm_remove_transaction') || 'Remove transaction?'}
                description={__('general.confirm_remove_transaction_desc') || 'This transaction will be removed from the schedule history.'}
                confirmLabel={__('general.delete')}
                cancelLabel={__('general.cancel')}
                variant="danger"
                onConfirm={confirmRemoveRecord}
                onCancel={() => setPendingDelete(null)}
            />
        </AdminSidebarLayout>
    );
}

export default RecurringScheduleView;
import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Switch } from '@/Components/ui/switch';
import { Trash2, Edit, Plus, Clock, Calendar, ArrowLeft, Bell, CheckCircle2 } from 'lucide-react';
import { __ } from '@/lib/i18n';

type NoticeType = 'info' | 'success' | 'warning' | 'danger';

interface NoticeRow {
    id: number;
    title: string;
    message: string | null;
    type: NoticeType;
    start_date: string;
    recurring: string;
    recurring_times: number;
    is_active: boolean;
    is_due_today: boolean;
    schedule_label: string;
}

const TYPE_BADGE: Record<NoticeType, string> = {
    info: 'bg-blue-100 text-blue-700 ring-blue-200',
    success: 'bg-green-100 text-green-700 ring-green-200',
    warning: 'bg-amber-100 text-amber-700 ring-amber-200',
    danger: 'bg-red-100 text-red-700 ring-red-200',
};

export default function Index({ notices, stats }: { notices: any; stats: { total_active: number; due_today: number } }) {
    const rows = (notices.data as NoticeRow[]) ?? [];

    const handleDelete = (id: number) => {
        if (confirm(__('general.are_you_sure_delete_this_item', {}, 'Are you sure you want to delete this notice?'))) {
            router.delete(route('admin.recurring_notices.delete', id));
        }
    };

    const handleToggleActive = (id: number) => {
        router.post(route('admin.recurring_notices.toggle', id), {}, { preserveScroll: true });
    };

    return (
        <AdminSidebarLayout title={__('general.admin_recurring_notices')} header="Business Operations">
            <Head title={__('general.admin_recurring_notices')} />

            <div className="mb-4">
                <Link href={route('admin.finance.index')} className="text-sm text-gray-500 hover:text-black flex items-center gap-1">
                    <ArrowLeft className="w-4 h-4" />{__('general.back_to_financial_ledger')}
                </Link>
            </div>

            {/* Stats */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div className="bg-white p-6 rounded-xl border shadow-sm flex items-center">
                    <div className="bg-slate-100 p-4 rounded-full me-4 text-slate-800 border">
                        <Bell className="w-6 h-6" />
                    </div>
                    <div>
                        <p className="text-sm text-gray-500 font-medium uppercase tracking-wider">{__('general.active')}</p>
                        <h3 className="text-2xl font-bold text-slate-900">{stats.total_active}</h3>
                    </div>
                </div>
                <div className="bg-white p-6 rounded-xl border shadow-sm flex items-center">
                    <div className="bg-amber-100 p-4 rounded-full me-4 text-amber-800 border">
                        <CheckCircle2 className="w-6 h-6" />
                    </div>
                    <div>
                        <p className="text-sm text-gray-500 font-medium uppercase tracking-wider">{__('general.due_today')}</p>
                        <h3 className="text-2xl font-bold text-slate-900">{stats.due_today}</h3>
                    </div>
                </div>
            </div>

            {/* Title & Actions */}
            <div className="flex justify-end gap-4 items-center mb-6">
                <div>
                    <h2 className="text-xl font-bold text-slate-900">{__('general.recurring_notices')}</h2>
                    <p className="text-sm text-gray-500 mt-1">{__('general.manage_recurring_notices_hint', {}, 'Schedule notices that surface on the board when due.')}</p>
                </div>
                <Link href={route('admin.recurring_notices.create')}>
                    <Button className="bg-black hover:bg-slate-800 text-white h-9">
                        <Plus className="w-4 h-4 me-2" />{__('general.add_recurring_notice')}
                    </Button>
                </Link>
            </div>

            {/* Table */}
            <div className="bg-white border rounded-xl shadow-sm overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">{__('general.title_schedule')}</th>
                            <th className="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">{__('general.start_date')}</th>
                            <th className="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">{__('general.notice_type')}</th>
                            <th className="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">{__('general.due_today')}</th>
                            <th className="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">{__('general.active')}</th>
                            <th className="px-6 py-3 text-end text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">{__('general.actions')}</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {rows.map((notice) => (
                            <tr key={notice.id} className="hover:bg-gray-50">
                                <td className="px-6 py-4">
                                    <div className="text-sm font-semibold text-gray-900">{notice.title}</div>
                                    <div className="text-xs text-gray-500 mt-1 flex items-center gap-1">
                                        <Clock className="w-3 h-3" /> {notice.schedule_label}
                                    </div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <div className="text-sm text-gray-900">{notice.start_date ? new Date(notice.start_date).toLocaleDateString() : '-'}</div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <span className={`inline-flex rounded px-2 py-0.5 text-xs font-medium uppercase ring-1 ${TYPE_BADGE[notice.type]}`}>
                                        {notice.type}
                                    </span>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-center">
                                    {notice.is_due_today ? (
                                        <span className="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200">
                                            <CheckCircle2 className="w-3 h-3" /> {__('general.due_today')}
                                        </span>
                                    ) : (
                                        <span className="text-xs text-gray-400">—</span>
                                    )}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-center">
                                    <Switch
                                        checked={notice.is_active}
                                        onCheckedChange={() => handleToggleActive(notice.id)}
                                        aria-label={__('general.toggle_active_status')}
                                    />
                                    <div className="text-[10px] text-gray-500 mt-1">
                                        {notice.is_active ? __('general.active') : __('general.inactive')}
                                    </div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-end text-sm font-medium">
                                    <Link href={route('admin.recurring_notices.edit', notice.id)}>
                                        <Button variant="ghost" size="sm" className="text-slate-700 hover:text-black me-1" title={__('general.edit')}>
                                            <Edit className="w-4 h-4" />
                                        </Button>
                                    </Link>
                                    <Button variant="ghost" size="sm" className="text-red-600 hover:text-red-900" onClick={() => handleDelete(notice.id)} title={__('general.delete')}>
                                        <Trash2 className="w-4 h-4" />
                                    </Button>
                                </td>
                            </tr>
                        ))}
                        {rows.length === 0 && (
                            <tr>
                                <td colSpan={6} className="px-6 py-12 text-center text-gray-500">
                                    <Calendar className="w-12 h-12 mx-auto text-gray-300 mb-3" />
                                    <h3 className="text-lg font-medium text-gray-900">{__('general.no_recurring_notices_found')}</h3>
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </AdminSidebarLayout>
    );
}

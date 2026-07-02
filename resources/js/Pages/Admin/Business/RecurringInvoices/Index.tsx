import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Switch } from '@/Components/ui/switch';
import { Trash2, Edit, Plus, User, Clock, Calendar, ArrowLeft, Eye } from 'lucide-react';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { __ } from '@/lib/i18n';

export default function Index({ invoices }) {

  const handleDelete = (id) => {
    if (confirm('Are you sure you want to delete this Recurring Invoice?')) {
      router.delete(route('admin.recurring_invoices.delete', id));
    }
  };

  const handleToggleActive = (id) => {
    router.post(route('admin.recurring_invoices.toggle', id), {}, {
      preserveScroll: true
    });
  };

  const formatSchedule = (invoice) => {
    let scheduleStr = `Every ${invoice.recurring_times} ${invoice.recurring}(s)`;
    if (invoice.recurring === 'week' && invoice.recurring_times_week) {
      scheduleStr += ` on [${invoice.recurring_times_week}]`;
    } else if (invoice.recurring === 'month' && invoice.recurring_times_month) {
      scheduleStr += ` on day [${invoice.recurring_times_month}]`;
    } else if (invoice.recurring === 'year' && invoice.recurring_times_year) {
      scheduleStr += ` on [${invoice.recurring_times_year}]`;
    }
    return scheduleStr;
  };

  return (
    <AdminSidebarLayout title={__('general.recurring_invoices')} header="Business Operations">
            <Head title={__('general.admin_recurring_invoices')} />

            <div className="mb-4">
                <Link href={route('admin.finance.index')} className="text-sm text-gray-500 hover:text-black flex items-center gap-1">
                    <ArrowLeft className="w-4 h-4" />{__('general.back_to_financial_ledger')}</Link>
            </div>

            {/* Title & Actions Bar */}
            <div className="flex justify-end gap-4 items-center mb-6">
                <div className="me-auto">
                    <h2 className="text-xl font-bold text-slate-900">{__('general.active_recurring_invoices')}</h2>
                    <p className="text-sm text-gray-500 mt-1">{__('general.manage_repeated_automated_salary_schedules_for_users')}</p>
                </div>

                <Link href={route('admin.recurring_invoices.create')}>
                    <Button className="bg-black hover:bg-slate-800 text-white h-9">
                        <Plus className="w-4 h-4 me-2" />{__('general.add_recurring_invoice')}</Button>
                </Link>
            </div>

            {/* Data Table */}
            <div className="bg-white border rounded-xl shadow-sm overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">{__('general.user_user')}</th>
                            <th className="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">{__('general.title_schedule')}</th>
                            <th className="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">{__('general.start_date')}</th>
                            <th className="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">
                                {__('general.amount')}</th>
                            <th className="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">
                                {__('general.active')}</th>
                            <th className="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">
                                {__('general.note')}</th>
                            <th className="px-6 py-3 text-end text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">{__('general.actions')}</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {(invoices.data as any).map((invoice) =>
            <tr key={invoice.id} className="hover:bg-gray-50">
                                <td className="px-6 py-4">
                                    <div className="flex items-center">
                                        <div className="bg-slate-100 border p-2 rounded-full me-3 text-slate-650">
                                            <User className="w-4 h-4" />
                                        </div>
                                        <div>
                                            <div className="text-sm font-semibold text-gray-900">{invoice.user?.name || 'Unknown user'}</div>
                                            <div className="text-xs text-gray-500">{invoice.user?.email || 'N/A'}</div>
                                        </div>
                                    </div>
                                </td>
                                <td className="px-6 py-4">
                                    <div className="text-sm font-semibold text-gray-900">{invoice.title}</div>
                                    <div className="text-xs text-gray-500 mt-1 flex items-center gap-1"><Clock className="w-3 h-3" /> {formatSchedule(invoice)}</div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <div className="text-sm text-gray-900">{new Date(invoice.start_date).toLocaleDateString()}</div>
                                    <div className="text-xs text-gray-500 mt-0.5">Next: {invoice.current_date}</div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-center">
                                    <span className="text-sm font-bold text-red-600 bg-red-50 border border-red-200 px-2 py-1 rounded">
                                        {formatCurrency(invoice.amount, invoice.currency)}
                                    </span>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-center">
                                    <Switch
                  checked={invoice.is_active}
                  onCheckedChange={() => handleToggleActive(invoice.id)}
                  aria-label={__('general.toggle_active_status')} />
                
                                    <div className="text-[10px] text-gray-500 mt-1">{invoice.is_active ? __('general.active') : __('general.inactive')}</div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-center">
                                    <span className="text-xs text-gray-600 italic">
                                        {invoice.reason || 'No notes'}
                                    </span>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-end text-sm font-medium">
                                    <Link href={route('admin.recurring_invoices.view', invoice.id)}>
                                        <Button variant="ghost" size="sm" className="text-slate-700 hover:text-black me-1" title={__('general.view_details')}>
                                            <Eye className="w-4 h-4" />
                                        </Button>
                                    </Link>
                                    <Link href={route('admin.recurring_invoices.edit', invoice.id)}>
                                        <Button variant="ghost" size="sm" className="text-slate-700 hover:text-black me-1" title={__('general.edit')}>
                                            <Edit className="w-4 h-4" />
                                        </Button>
                                    </Link>
                                    <Button variant="ghost" size="sm" className="text-red-650 hover:text-red-900" onClick={() => handleDelete(invoice.id)} title={__('general.delete_schedule')}>
                                        <Trash2 className="w-4 h-4" />
                                    </Button>
                                </td>
                            </tr>
            )}
                        {(invoices.data as any).length === 0 &&
            <tr>
                                <td colSpan={6} className="px-6 py-12 text-center text-gray-500">
                                    <Calendar className="w-12 h-12 mx-auto text-gray-300 mb-3" />
                                    <h3 className="text-lg font-medium text-gray-900">{__('general.no_recurring_invoices_found')}</h3>
                                    <p className="mt-1">{__('general.add_a_new_schedule_to_start_managing_automated_user_payroll')}</p>
                                </td>
                            </tr>
            }
                    </tbody>
                </table>
            </div>

            {/* Pagination */}
            {invoices.links && invoices.links.length > 3 &&
      <div className="flex justify-end gap-4 items-center mt-6">
                    <div className="me-auto text-sm text-gray-500">
                        Showing {invoices.from} to {invoices.to} of {invoices.total} entries
                    </div>
                    <div className="flex space-x-1">
                        {invoices.links.map((link, idx) =>
          <Link
            key={idx}
            href={link.url || '#'}
            className={`px-3 py-2 border rounded text-sm ${link.active ? 'bg-black text-white border-black font-semibold' : 'bg-white text-gray-700 hover:bg-gray-50'} ${!link.url && 'opacity-50 cursor-not-allowed'}`}
            dangerouslySetInnerHTML={{ __html: link.label }} />

          )}
                    </div>
                </div>
      }
        </AdminSidebarLayout>);

}

import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Switch } from '@/Components/ui/switch';
import { Trash2, Edit, Plus, User, Clock, Calendar, ArrowLeft, Eye, Power } from 'lucide-react';
import { formatMoney as formatCurrency } from '@/lib/utils';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger } from
"@/Components/ui/dialog";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { __ } from '@/lib/i18n';

export default function Index({ salaries, currencies, users }) {
  const { errors } = usePage().props;
  const currenciesList = Array.isArray(currencies) ? currencies : currencies ? Object.values(currencies) : [];
  const usersList = Array.isArray(users) ? users : users ? Object.values(users) : [];

  // Dialog State
  const [isCreateOpen, setIsCreateOpen] = useState(false);

  const defaultCurrencyId = currenciesList[0]?.id || '';

  const [newSalary, setNewSalary] = useState({
    user_id: usersList[0]?.id || '',
    title: 'Monthly Salary',
    amount: '',
    currency: defaultCurrencyId,
    reason: '',
    start_date: new Date().toISOString().slice(0, 10),
    recurring: 'month',
    recurring_times: 1,
    recurring_times_week: [] as string[],
    recurring_times_month: [] as string[],
    recurring_times_year: [] as string[]
  });

  const handleCreate = (e) => {
    e.preventDefault();
    router.post(route('admin.recurring_salaries.store'), {
      ...newSalary,
      user_id: parseInt(newSalary.user_id as string) || newSalary.user_id,
      currency: parseInt(newSalary.currency as string) || newSalary.currency
    }, {
      onSuccess: () => {
        setIsCreateOpen(false);
        setNewSalary({
          user_id: usersList[0]?.id || '',
          title: 'Monthly Salary',
          amount: '',
          currency: defaultCurrencyId,
          reason: '',
          start_date: new Date().toISOString().slice(0, 10),
          recurring: 'month',
          recurring_times: 1,
          recurring_times_week: [],
          recurring_times_month: [],
          recurring_times_year: []
        });
      }
    });
  };

  const handleDelete = (id) => {
    if (confirm('Are you sure you want to delete this recurring salary?')) {
      // In the routes we registered 'recurring_salaries.delete'
      router.delete(route('admin.recurring_salaries.delete', id));
    }
  };

  const handleToggleActive = (id) => {
    router.post(route('admin.recurring_salaries.toggle', id), {}, {
      preserveScroll: true
    });
  };

  const formatSchedule = (salary) => {
    let scheduleStr = `Every ${salary.recurring_times} ${salary.recurring}(s)`;
    if (salary.recurring === 'week' && salary.recurring_times_week) {
      scheduleStr += ` on [${salary.recurring_times_week}]`;
    } else if (salary.recurring === 'month' && salary.recurring_times_month) {
      scheduleStr += ` on day [${salary.recurring_times_month}]`;
    } else if (salary.recurring === 'year' && salary.recurring_times_year) {
      scheduleStr += ` on [${salary.recurring_times_year}]`;
    }
    return scheduleStr;
  };

  // Week days helper list
  const weekDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

  // Month days helper list (1-31)
  const monthDays = Array.from({ length: 31 }, (_, i) => i + 1);

  // Month name helper
  const monthNames = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December'];


  // Days in month helper for year selection
  const getYearDaysList = () => {
    const list: {val: string;label: string;}[] = [];
    monthNames.forEach((month, mIdx) => {
      const daysInMonth = new Date(2024, mIdx + 1, 0).getDate(); // Leap year 2024 to support Feb 29
      for (let d = 1; d <= daysInMonth; d++) {
        list.push({
          val: `${d}-${mIdx + 1}`,
          label: `${d.toString().padStart(2, '0')} - ${month}`
        });
      }
    });
    return list;
  };

  const yearDaysList = getYearDaysList();

  return (
    <AdminSidebarLayout title={__('general.recurring_salaries')} header="Business Operations">
            <Head title={__('general.admin_recurring_salaries')} />

            <div className="mb-4">
                <Link href={route('admin.finance.index')} className="text-sm text-gray-500 hover:text-black flex items-center gap-1">
                    <ArrowLeft className="w-4 h-4" />{__('general.back_to_financial_ledger')}</Link>
            </div>

            {/* Title & Actions Bar */}
            <div className="flex justify-end gap-4 items-center mb-6">
                <div className="me-auto">
                    <h2 className="text-xl font-bold text-slate-900">{__('general.active_recurring_salaries')}</h2>
                    <p className="text-sm text-gray-500 mt-1">{__('general.manage_repeated_automated_salary_schedules_for_employees')}</p>
                </div>

                <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                    <DialogTrigger asChild>
                        <Button className="bg-black hover:bg-slate-800 text-white h-9">
                            <Plus className="w-4 h-4 me-2" />{__('general.add_recurring_salary')}</Button>
                    </DialogTrigger>
                    <DialogContent className="sm:max-w-[450px] max-h-[85vh] overflow-y-auto">
                        <form onSubmit={handleCreate}>
                            <DialogHeader>
                                <DialogTitle>{__('general.add_recurring_salary')}</DialogTitle>
                                <DialogDescription>{__('general.create_a_repeated_salary_payment_schedule_for_a_team_member')}</DialogDescription>
                            </DialogHeader>
                            <div className="space-y-4 py-4">
                                <div className="space-y-2">
                                    <Label htmlFor="user_id">{__('general.employee_user')}</Label>
                                    <select id="user_id" required className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={newSalary.user_id} onChange={(e) => setNewSalary({ ...newSalary, user_id: e.target.value })}>
                                        <option value="">{__('general.select_employee')}</option>
                                        {usersList.map((u) => <option key={u.id} value={u.id}>{u.name} ({u.email})</option>)}
                                    </select>
                                    {errors.user_id && <span className="text-red-600 text-xs block">{errors.user_id}</span>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="title">{__('general.title_description')}</Label>
                                    <Input id="title" required value={newSalary.title} onChange={(e) => setNewSalary({ ...newSalary, title: e.target.value })} placeholder={__('general.e_g_monthly_salary')} />
                                    {errors.title && <span className="text-red-600 text-xs block">{errors.title}</span>}
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="amount">{__('general.amount')}</Label>
                                        <Input id="amount" type="number" step="any" required value={newSalary.amount} onChange={(e) => setNewSalary({ ...newSalary, amount: e.target.value })} placeholder="0.00" />
                                        {errors.amount && <span className="text-red-600 text-xs block">{errors.amount}</span>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="currency">{__('general.currency')}</Label>
                                        <select id="currency" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={newSalary.currency} onChange={(e) => setNewSalary({ ...newSalary, currency: e.target.value })}>
                                            {currenciesList.map((c) => <option key={c.id} value={c.id}>{c.currency} ({c.symbol})</option>)}
                                        </select>
                                        {errors.currency && <span className="text-red-600 text-xs block">{errors.currency}</span>}
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="reason">Note / Custom Reason (Optional)</Label>
                                    <Input id="reason" value={newSalary.reason} onChange={(e) => setNewSalary({ ...newSalary, reason: e.target.value })} placeholder={__('general.e_g_senior_backend_dev_rate')} />
                                    {errors.reason && <span className="text-red-600 text-xs block">{errors.reason}</span>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="start_date">{__('general.start_date')}</Label>
                                    <Input id="start_date" type="date" required value={newSalary.start_date} onChange={(e) => setNewSalary({ ...newSalary, start_date: e.target.value })} />
                                    {errors.start_date && <span className="text-red-600 text-xs block">{errors.start_date}</span>}
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="frequency">{__('general.frequency')}</Label>
                                        <select id="frequency" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={newSalary.recurring} onChange={(e) => setNewSalary({ ...newSalary, recurring: e.target.value })}>
                                            <option value="day">{__('general.daily')}</option>
                                            <option value="week">{__('general.weekly')}</option>
                                            <option value="month">{__('general.monthly')}</option>
                                            <option value="year">{__('general.annually')}</option>
                                        </select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="interval">Interval (Every N)</Label>
                                        <select id="interval" className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-10" value={newSalary.recurring_times} onChange={(e) => setNewSalary({ ...newSalary, recurring_times: parseInt(e.target.value) || 1 })}>
                                            {Array.from({ length: 30 }, (_, i) => i + 1).map((num) =>
                      <option key={num} value={num}>{num}</option>
                      )}
                                        </select>
                                    </div>
                                </div>

                                {newSalary.recurring === 'week' &&
                <div className="space-y-2">
                                        <Label htmlFor="week-days">{__('general.specific_week_days')}</Label>
                                        <select
                    id="week-days"
                    multiple
                    className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-24"
                    value={newSalary.recurring_times_week}
                    onChange={(e) => {
                      const vals = Array.from(e.target.selectedOptions, (option) => option.value);
                      setNewSalary({ ...newSalary, recurring_times_week: vals });
                    }}>
                    
                                            {weekDays.map((wd) => <option key={wd} value={wd}>{wd}</option>)}
                                        </select>
                                        <span className="text-xs text-gray-400">{__('general.hold_ctrl_cmd_to_select_multiple_days')}</span>
                                    </div>
                }

                                {newSalary.recurring === 'month' &&
                <div className="space-y-2">
                                        <Label htmlFor="month-days">{__('general.specific_month_days')}</Label>
                                        <select
                    id="month-days"
                    multiple
                    className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-32"
                    value={newSalary.recurring_times_month}
                    onChange={(e) => {
                      const vals = Array.from(e.target.selectedOptions, (option) => option.value);
                      setNewSalary({ ...newSalary, recurring_times_month: vals });
                    }}>
                    
                                            {monthDays.map((d) => <option key={d} value={d.toString()}>{d.toString().padStart(2, '0')}</option>)}
                                        </select>
                                        <span className="text-xs text-gray-400">{__('general.hold_ctrl_cmd_to_select_multiple_days')}</span>
                                    </div>
                }

                                {newSalary.recurring === 'year' &&
                <div className="space-y-2">
                                        <Label htmlFor="year-days">{__('general.specific_year_dates')}</Label>
                                        <select
                    id="year-days"
                    multiple
                    className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white h-40"
                    value={newSalary.recurring_times_year}
                    onChange={(e) => {
                      const vals = Array.from(e.target.selectedOptions, (option) => option.value);
                      setNewSalary({ ...newSalary, recurring_times_year: vals });
                    }}>
                    
                                            {yearDaysList.map((yd) => <option key={yd.val} value={yd.val}>{yd.label}</option>)}
                                        </select>
                                        <span className="text-xs text-gray-400">{__('general.hold_ctrl_cmd_to_select_multiple_dates')}</span>
                                    </div>
                }
                            </div>
                            <DialogFooter>
                                <Button type="submit" className="bg-black hover:bg-slate-800 text-white w-full">{__('general.create_recurring_salary')}</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            {/* Data Table */}
            <div className="bg-white border rounded-xl shadow-sm overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">{__('general.employee_user')}</th>
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
                        {(salaries.data as any).map((salary) =>
            <tr key={salary.id} className="hover:bg-gray-50">
                                <td className="px-6 py-4">
                                    <div className="flex items-center">
                                        <div className="bg-slate-100 border p-2 rounded-full me-3 text-slate-650">
                                            <User className="w-4 h-4" />
                                        </div>
                                        <div>
                                            <div className="text-sm font-semibold text-gray-900">{salary.user?.name || 'Unknown Employee'}</div>
                                            <div className="text-xs text-gray-500">{salary.user?.email || 'N/A'}</div>
                                        </div>
                                    </div>
                                </td>
                                <td className="px-6 py-4">
                                    <div className="text-sm font-semibold text-gray-900">{salary.title}</div>
                                    <div className="text-xs text-gray-500 mt-1 flex items-center gap-1"><Clock className="w-3 h-3" /> {formatSchedule(salary)}</div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <div className="text-sm text-gray-900">{new Date(salary.start_date).toLocaleDateString()}</div>
                                    <div className="text-xs text-gray-500 mt-0.5">Next: {salary.current_date}</div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-center">
                                    <span className="text-sm font-bold text-red-600 bg-red-50 border border-red-200 px-2 py-1 rounded">
                                        {formatCurrency(salary.amount, salary.currency)}
                                    </span>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-center">
                                    <Switch
                  checked={salary.is_active}
                  onCheckedChange={() => handleToggleActive(salary.id)}
                  aria-label={__('general.toggle_active_status')} />
                
                                    <div className="text-[10px] text-gray-500 mt-1">{salary.is_active ? __('general.active') : __('general.inactive')}</div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-center">
                                    <span className="text-xs text-gray-600 italic">
                                        {salary.reason || 'No notes'}
                                    </span>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-end text-sm font-medium">
                                    <Link href={route('admin.recurring_salaries.view', salary.id)}>
                                        <Button variant="ghost" size="sm" className="text-slate-700 hover:text-black me-1" title={__('general.view_details')}>
                                            <Eye className="w-4 h-4" />
                                        </Button>
                                    </Link>
                                    <Link href={route('admin.recurring_salaries.edit', salary.id)}>
                                        <Button variant="ghost" size="sm" className="text-slate-700 hover:text-black me-1" title={__('general.edit')}>
                                            <Edit className="w-4 h-4" />
                                        </Button>
                                    </Link>
                                    <Button variant="ghost" size="sm" className="text-red-650 hover:text-red-900" onClick={() => handleDelete(salary.id)} title={__('general.delete_schedule')}>
                                        <Trash2 className="w-4 h-4" />
                                    </Button>
                                </td>
                            </tr>
            )}
                        {(salaries.data as any).length === 0 &&
            <tr>
                                <td colSpan={6} className="px-6 py-12 text-center text-gray-500">
                                    <Calendar className="w-12 h-12 mx-auto text-gray-300 mb-3" />
                                    <h3 className="text-lg font-medium text-gray-900">{__('general.no_recurring_salaries_found')}</h3>
                                    <p className="mt-1">{__('general.add_a_new_schedule_to_start_managing_automated_employee_payroll')}</p>
                                </td>
                            </tr>
            }
                    </tbody>
                </table>
            </div>

            {/* Pagination */}
            {salaries.links && salaries.links.length > 3 &&
      <div className="flex justify-end gap-4 items-center mt-6">
                    <div className="me-auto text-sm text-gray-500">
                        Showing {salaries.from} to {salaries.to} of {salaries.total} entries
                    </div>
                    <div className="flex space-x-1">
                        {salaries.links.map((link, idx) =>
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
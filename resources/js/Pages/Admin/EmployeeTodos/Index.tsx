import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { DataTable } from '@/Components/ui/DataTable';
import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/Components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { useToast } from '@/Components/ui/use-toast';
import { PremiumCombobox } from '@/Components/ui/PremiumCombobox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { __ } from '@/lib/i18n';
import {
    MoreHorizontal,
    Plus,
    Pencil,
    Trash2,
    RefreshCw,
    Calendar,
    User as UserIcon,
    AlertTriangle,
} from 'lucide-react';

// ─── Types ───────────────────────────────────────────────────────────────────

interface TodoUser {
    id: number;
    name: string;
    email: string;
}

interface EmployeeTodo {
    id: number;
    title: string;
    description: string | null;
    priority: 'high' | 'medium' | 'low';
    recurring: 'day' | 'week' | 'month' | 'year';
    recurring_times: number;
    recurring_times_week: string | null;
    recurring_times_month: string | null;
    recurring_times_year: string | null;
    current_date: string;
    transactions_count: number;
    created_at: string;
    user: TodoUser | null;
}

interface Stats {
    total: number;
    daily: number;
    weekly: number;
    monthly: number;
    yearly: number;
}

interface Filters {
    user_id?: string;
    recurring?: string;
    priority?: string;
    search?: string;
}

interface Props {
    todos: { data: EmployeeTodo[]; [key: string]: any };
    filters: Filters;
    stats: Stats;
    users: TodoUser[];
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

const priorityColors: Record<string, string> = {
    high:   'bg-red-100 text-red-700',
    medium: 'bg-yellow-100 text-yellow-700',
    low:    'bg-emerald-100 text-slate-900',
};

const recurringColors: Record<string, string> = {
    day:   'bg-blue-100 text-slate-900',
    week:  'bg-indigo-100 text-slate-900',
    month: 'bg-purple-100 text-slate-900',
    year:  'bg-slate-100 text-slate-700',
};

const recurringLabel: Record<string, string> = {
    day:   'Daily',
    week:  'Weekly',
    month: 'Monthly',
    year:  'Yearly',
};

// ─── Default form values ──────────────────────────────────────────────────────

const defaultForm = {
    user_id: '',
    title: '',
    description: '',
    priority: 'medium',
    recurring: 'day',
    recurring_times: 1,
    recurring_times_week: '',
    recurring_times_month: '',
    recurring_times_year: '',
    current_date: new Date().toISOString().slice(0, 10),
};

// ─── Component ───────────────────────────────────────────────────────────────

export default function Index({ todos, filters, stats, users }: Props) {
    const { toast } = useToast();

    const [showModal, setShowModal]   = useState(false);
    const [editingId, setEditingId]   = useState<number | null>(null);
    const [deleteId, setDeleteId]     = useState<number | null>(null);

    const { data, setData, post, put, processing, errors, reset } = useForm({ ...defaultForm });

    // ── Filters ───────────────────────────────────────────────────────────────

    const handleFilter = (key: string, value: string) => {
        router.get('/admin/employee-todos', { ...filters, [key]: value, page: 1 }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleSearch = (search: string) => {
        router.get('/admin/employee-todos', { ...filters, search, page: 1 }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleSort = (key: string) => {
        router.get('/admin/employee-todos', {
            ...filters,
            sort: key,
            direction: filters['sort' as keyof Filters] === key && filters['direction' as keyof Filters] === 'asc' ? 'desc' : 'asc',
        }, { preserveState: true, replace: true });
    };

    // ── Create / Edit ─────────────────────────────────────────────────────────

    const openCreate = () => {
        setEditingId(null);
        setData({ ...defaultForm });
        setShowModal(true);
    };

    const openEdit = (todo: EmployeeTodo) => {
        setEditingId(todo.id);
        setData({
            user_id:               String(todo.user?.id ?? ''),
            title:                 todo.title,
            description:           todo.description ?? '',
            priority:              todo.priority,
            recurring:             todo.recurring,
            recurring_times:       todo.recurring_times,
            recurring_times_week:  todo.recurring_times_week ?? '',
            recurring_times_month: todo.recurring_times_month ?? '',
            recurring_times_year:  todo.recurring_times_year ?? '',
            current_date:          todo.current_date,
        });
        setShowModal(true);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (editingId) {
            put(route('admin.employee-todos.update', editingId), {
                onSuccess: () => {
                    setShowModal(false);
                    toast({ title: 'Todo updated successfully.' });
                },
                onError: () => toast({ title: 'Failed to update todo.', variant: 'destructive' }),
            });
        } else {
            post(route('admin.employee-todos.store'), {
                onSuccess: () => {
                    setShowModal(false);
                    toast({ title: 'Todo created successfully.' });
                },
                onError: () => toast({ title: 'Failed to create todo.', variant: 'destructive' }),
            });
        }
    };

    // ── Delete ────────────────────────────────────────────────────────────────

    const handleDelete = () => {
        if (!deleteId) return;
        router.delete(route('admin.employee-todos.destroy', deleteId), {
            onSuccess: () => {
                setDeleteId(null);
                toast({ title: 'Todo deleted.' });
            },
            onError: () => toast({ title: 'Failed to delete.', variant: 'destructive' }),
        });
    };

    // ── Table columns ─────────────────────────────────────────────────────────

    const columns = [
        {
            key: 'id',
            label: 'ID',
            className: 'w-[60px]',
            render: (t: EmployeeTodo) => (
                <span className="text-slate-400 font-mono text-xs">#{t.id}</span>
            ),
        },
        {
            key: 'title',
            label: 'Title',
            sortable: true,
            render: (t: EmployeeTodo) => (
                <div>
                    <p className="font-medium text-slate-800">{t.title}</p>
                    {t.description && (
                        <p className="text-xs text-slate-400 mt-0.5 line-clamp-1">{t.description}</p>
                    )}
                </div>
            ),
        },
        {
            key: 'user',
            label: 'Employee',
            render: (t: EmployeeTodo) =>
                t.user ? (
                    <div className="flex items-center gap-2">
                        <div className="h-7 w-7 rounded-full bg-indigo-100 flex items-center justify-center">
                            <UserIcon className="h-3.5 w-3.5 text-slate-900" />
                        </div>
                        <div>
                            <p className="text-sm font-medium text-slate-700">{t.user.name}</p>
                            <p className="text-xs text-slate-400">{t.user.email}</p>
                        </div>
                    </div>
                ) : (
                    <span className="text-slate-400 text-sm">—</span>
                ),
        },
        {
            key: 'priority',
            label: 'Priority',
            render: (t: EmployeeTodo) => (
                <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold capitalize ${priorityColors[t.priority] ?? 'bg-slate-100 text-slate-700'}`}>
                    {t.priority}
                </span>
            ),
        },
        {
            key: 'recurring',
            label: 'Recurs',
            render: (t: EmployeeTodo) => (
                <div className="flex items-center gap-1.5">
                    <RefreshCw className="h-3.5 w-3.5 text-slate-400" />
                    <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${recurringColors[t.recurring] ?? 'bg-slate-100 text-slate-700'}`}>
                        {recurringLabel[t.recurring]}
                        {t.recurring_times > 1 ? ` ×${t.recurring_times}` : ''}
                    </span>
                </div>
            ),
        },
        {
            key: 'transactions_count',
            label: 'Applied',
            render: (t: EmployeeTodo) => (
                <span className="inline-flex items-center gap-1 text-sm text-slate-600">
                    <Calendar className="h-3.5 w-3.5 text-slate-400" />
                    {t.transactions_count}x
                </span>
            ),
        },
        {
            key: 'current_date',
            label: 'Start Date',
            sortable: true,
            render: (t: EmployeeTodo) => (
                <span className="text-sm text-slate-500 whitespace-nowrap">{t.current_date}</span>
            ),
        },
        {
            key: 'actions',
            label: '',
            className: 'w-[70px] text-end',
            render: (t: EmployeeTodo) => (
                <DropdownMenu>
                    <DropdownMenuTrigger
                        className="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100 hover:text-slate-900 transition-colors"
                        aria-label={__('general.open_actions_menu')}
                    >
                        <MoreHorizontal className="h-4 w-4" />
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-40">
                        <DropdownMenuLabel>{__('general.actions')}</DropdownMenuLabel>
                        <DropdownMenuItem onClick={() => openEdit(t)}>
                            <Pencil className="me-2 h-4 w-4" /> {__('general.edit')}</DropdownMenuItem>
                        <DropdownMenuItem
                            className="text-red-600 focus:text-red-600"
                            onClick={() => setDeleteId(t.id)}
                        >
                            <Trash2 className="me-2 h-4 w-4" /> {__('general.delete')}</DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            ),
        },
    ];

    // ── Filter bar ────────────────────────────────────────────────────────────

    const advancedFilters = (
        <div className="flex items-center gap-2 flex-wrap">
            <Select value={filters.recurring || 'all'} onValueChange={(val) => handleFilter('recurring', val === 'all' ? '' : ((val as string) || ''))}>
                <SelectTrigger className="w-[150px] bg-white h-11 rounded-xl">
                    <SelectValue placeholder={__('general.all_frequencies')} />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">{__('general.all_frequencies')}</SelectItem>
                    <SelectItem value="day">{__('general.daily')}</SelectItem>
                    <SelectItem value="week">{__('general.weekly')}</SelectItem>
                    <SelectItem value="month">{__('general.monthly')}</SelectItem>
                    <SelectItem value="year">{__('general.yearly')}</SelectItem>
                </SelectContent>
            </Select>

            <Select value={filters.priority || 'all'} onValueChange={(val) => handleFilter('priority', val === 'all' ? '' : ((val as string) || ''))}>
                <SelectTrigger className="w-[140px] bg-white h-11 rounded-xl">
                    <SelectValue placeholder={__('general.all_priorities')} />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">{__('general.all_priorities')}</SelectItem>
                    <SelectItem value="high">{__('general.high')}</SelectItem>
                    <SelectItem value="medium">{__('general.medium')}</SelectItem>
                    <SelectItem value="low">Low</SelectItem>
                </SelectContent>
            </Select>

            <div className="w-[200px]">
                <PremiumCombobox
                    value={filters.user_id || ''}
                    onChange={(val) => handleFilter('user_id', val ? String(val) : '')}
                    options={users.map(u => ({ value: String(u.id), label: u.name }))}
                    placeholder={__('general.all_employees')}
                    icon={<UserIcon className="w-4 h-4" />}
                />
            </div>

            <Button onClick={openCreate} className="flex items-center gap-1.5 ms-auto h-11 rounded-xl">
                <Plus className="h-4 w-4" />{__('general.new_todo')}</Button>
        </div>
    );

    // ─── Render ───────────────────────────────────────────────────────────────

    return (
        <AdminSidebarLayout title={__('general.employee_todos')} header="Employee Recurring Todos">
            <Head title={__('general.employee_todos')} />

            {/* Stats */}
            <div className="mb-6 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                {[
                    { label: 'Total',   value: stats.total,   color: 'text-slate-800' },
                    { label: 'Daily',   value: stats.daily,   color: 'text-slate-900' },
                    { label: 'Weekly',  value: stats.weekly,  color: 'text-slate-900' },
                    { label: 'Monthly', value: stats.monthly, color: 'text-slate-900' },
                    { label: 'Yearly',  value: stats.yearly,  color: 'text-slate-600' },
                ].map((s) => (
                    <div
                        key={s.label}
                        className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col items-center justify-center"
                    >
                        <span className={`text-2xl font-semibold ${s.color}`}>{s.value}</span>
                        <span className="text-xs text-slate-500 font-medium uppercase tracking-wider mt-1">{s.label}</span>
                    </div>
                ))}
            </div>

            {/* Table */}
            <div className="mb-6">
                <DataTable
                    columns={columns}
                    data={todos.data}
                    pagination={todos}
                    filters={{ ...filters, extra: advancedFilters }}
                    onSearch={handleSearch}
                    onSort={handleSort}
                    emptyTitle="No recurring todos found"
                    emptyDescription="Create a recurring todo to automatically assign tasks to employees on a schedule."
                />
            </div>

            {/* Create / Edit Modal */}
            <Dialog open={showModal} onOpenChange={setShowModal}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>{editingId ? 'Edit Recurring Todo' : 'New Recurring Todo'}</DialogTitle>
                    </DialogHeader>

                    <form onSubmit={handleSubmit} className="space-y-4 mt-2">
                        {/* Employee */}
                        <div className="space-y-1">
                            <Label htmlFor="et-user_id">{__('general.employee')}</Label>
                            <PremiumCombobox
                                value={data.user_id}
                                onChange={(val) => setData('user_id', val ? String(val) : '')}
                                options={users.map((u) => ({ value: String(u.id), label: `${u.name} — ${u.email}` }))}
                                placeholder={__('general.select_employee_1')}
                                icon={<UserIcon className="w-4 h-4" />}
                            />
                            {errors.user_id && <p className="text-xs text-red-500">{errors.user_id}</p>}
                        </div>

                        {/* Title */}
                        <div className="space-y-1">
                            <Label htmlFor="et-title">{__('general.title')}</Label>
                            <Input
                                id="et-title"
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value || '')}
                                placeholder={__('general.todo_title')}
                                className="h-11 rounded-xl"
                                required
                            />
                            {errors.title && <p className="text-xs text-red-500">{errors.title}</p>}
                        </div>

                        {/* Description */}
                        <div className="space-y-1">
                            <Label htmlFor="et-description">{__('general.description')}<span className="text-slate-400 font-normal">(optional)</span></Label>
                            <Textarea
                                id="et-description"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value || '')}
                                placeholder={__('general.additional_details')}
                                className="rounded-xl"
                                rows={3}
                            />
                        </div>

                        {/* Priority + Recurring side-by-side */}
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-1">
                                <Label htmlFor="et-priority">{__('general.priority')}</Label>
                                <Select value={data.priority} onValueChange={(v) => setData('priority', (v as string) || '')}>
                                    <SelectTrigger className="w-full bg-white h-11 rounded-xl shadow-sm">
                                        <SelectValue placeholder={__('general.select_priority')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="low">Low</SelectItem>
                                        <SelectItem value="medium">{__('general.medium')}</SelectItem>
                                        <SelectItem value="high">{__('general.high')}</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.priority && <p className="text-xs text-red-500">{errors.priority}</p>}
                            </div>

                            <div className="space-y-1">
                                <Label htmlFor="et-recurring">{__('general.recurs_every')}</Label>
                                <Select value={data.recurring} onValueChange={(v) => setData('recurring', (v as string) || '')}>
                                    <SelectTrigger className="w-full bg-white h-11 rounded-xl shadow-sm">
                                        <SelectValue placeholder={__('general.select_frequency')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="day">Day</SelectItem>
                                        <SelectItem value="week">{__('general.week')}</SelectItem>
                                        <SelectItem value="month">{__('general.month')}</SelectItem>
                                        <SelectItem value="year">{__('general.year')}</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.recurring && <p className="text-xs text-red-500">{errors.recurring}</p>}
                            </div>
                        </div>

                        {/* Interval + Start Date */}
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-1">
                                <Label htmlFor="et-recurring_times">Every N {data.recurring}(s)</Label>
                                <Input
                                    id="et-recurring_times"
                                    type="number"
                                    min={1}
                                    value={data.recurring_times}
                                    onChange={(e) => setData('recurring_times', parseInt(e.target.value) || 1)}
                                    className="h-11 rounded-xl"
                                    required
                                />
                                {errors.recurring_times && <p className="text-xs text-red-500">{errors.recurring_times}</p>}
                            </div>

                            <div className="space-y-1">
                                <Label htmlFor="et-current_date">{__('general.start_date')}</Label>
                                <Input
                                    id="et-current_date"
                                    type="date"
                                    value={data.current_date}
                                    onChange={(e) => setData('current_date', e.target.value || '')}
                                    className="h-11 rounded-xl"
                                    required
                                />
                                {errors.current_date && <p className="text-xs text-red-500">{errors.current_date}</p>}
                            </div>
                        </div>

                        {/* Conditional fields */}
                        {data.recurring === 'week' && (
                            <div className="space-y-1">
                                <Label htmlFor="et-week">{__('general.days_of_week')}<span className="text-slate-400 font-normal">(comma-separated, e.g. Monday,Wednesday)</span></Label>
                                <Input
                                    id="et-week"
                                    value={data.recurring_times_week}
                                    onChange={(e) => setData('recurring_times_week', e.target.value || '')}
                                    placeholder={__('general.monday_wednesday_friday')}
                                    className="h-11 rounded-xl"
                                />
                            </div>
                        )}

                        {data.recurring === 'month' && (
                            <div className="space-y-1">
                                <Label htmlFor="et-month">{__('general.days_of_month')}<span className="text-slate-400 font-normal">(comma-separated, e.g. 1,15)</span></Label>
                                <Input
                                    id="et-month"
                                    value={data.recurring_times_month}
                                    onChange={(e) => setData('recurring_times_month', e.target.value || '')}
                                    placeholder="1,15"
                                    className="h-11 rounded-xl"
                                />
                            </div>
                        )}

                        {data.recurring === 'year' && (
                            <div className="space-y-1">
                                <Label htmlFor="et-year">{__('general.day_month_pairs')}<span className="text-slate-400 font-normal">(comma-separated, e.g. 1-1,25-12)</span></Label>
                                <Input
                                    id="et-year"
                                    value={data.recurring_times_year}
                                    onChange={(e) => setData('recurring_times_year', e.target.value || '')}
                                    placeholder="1-1,25-12"
                                    className="h-11 rounded-xl"
                                />
                            </div>
                        )}

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setShowModal(false)}>
                                {__('general.cancel')}</Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Saving…' : editingId ? 'Save Changes' : 'Create Todo'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Delete Confirmation Modal */}
            <Dialog open={!!deleteId} onOpenChange={() => setDeleteId(null)}>
                <DialogContent className="max-w-sm">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-red-600">
                            <AlertTriangle className="h-5 w-5" />{__('general.delete_recurring_todo')}</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-slate-600 mt-2">{__('general.this_will_permanently_delete_the_recurring_todo')}<strong>{__('general.and_all_its_transaction_history')}</strong>. This action cannot be undone.
                    </p>
                    <DialogFooter className="mt-4">
                        <Button variant="outline" onClick={() => setDeleteId(null)}>{__('general.cancel')}</Button>
                        <Button variant="destructive" onClick={handleDelete}>{__('general.delete')}</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminSidebarLayout>
    );
}

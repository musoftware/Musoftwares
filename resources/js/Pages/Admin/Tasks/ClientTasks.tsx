import React, { useState } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, Link, router } from '@inertiajs/react';
import {
    Search,
    ArrowLeft,
    Activity,
    User,
    Wallet,
    CheckCircle2,
    DollarSign,
    AlertCircle,
    Calendar,
    CalendarCheck,
    ListTodo,
    ChevronRight,
    RotateCcw,
    ShieldCheck,
    Trash2,
    PlusCircle,
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Card, CardContent } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { toast } from 'sonner';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { __ } from '@/lib/i18n';

interface Client {
    id: number;
    name: string;
    email: string;
    initials: string;
    total_tasks: number;
    completed_tasks: number;
}

interface TodoItem {
    id: number;
    task_id: number;
    task_name: string;
    title: string;
    description: string | null;
    completed: boolean;
    paused: boolean;
    is_paid: boolean;
    cost: number;
    cost_in_client_currency: number;
    client_currency: string;
    start_at: string | null;
    end_at: string | null;
    refunded: boolean;
    refund_amount: number;
    show_refund: boolean;
}

interface SelectedClient {
    id: number;
    name: string;
    email: string;
    balance: number;
    currency: string;
    hourly_rate?: number;
    total_tasks: number;
    completed_tasks: number;
    latest_task_id: number | null;
}

interface Props {
    clients: Client[];
    selectedClient: SelectedClient | null;
    todos: TodoItem[];
    filters: { client_id?: string };
}

export default function ClientTasks({ clients, selectedClient, todos, filters }: Props) {
    const [search, setSearch] = useState('');
    const [refundingId, setRefundingId] = useState<number | null>(null);
    const [newTodoTitle, setNewTodoTitle] = useState('');

    const [scheduleData, setScheduleData] = useState({
        title: '',
        date: new Date().toISOString().split('T')[0],
        start_time: '',
        end_time: '',
    });
    const [submittingFocus, setSubmittingFocus] = useState(false);
    const [scheduleErrors, setScheduleErrors] = useState<any>({});
    const [estimatedCost, setEstimatedCost] = useState(0);

    React.useEffect(() => {
        if (scheduleData.start_time && scheduleData.end_time && selectedClient?.hourly_rate) {
            const start = new Date(`${scheduleData.date}T${scheduleData.start_time}`);
            const end = new Date(`${scheduleData.date}T${scheduleData.end_time}`);
            if (end > start) {
                const diffMs = end.getTime() - start.getTime();
                const diffHrs = diffMs / 1000 / 60 / 60;
                setEstimatedCost(diffHrs * selectedClient.hourly_rate);
            } else {
                setEstimatedCost(0);
            }
        } else {
            setEstimatedCost(0);
        }
    }, [scheduleData.date, scheduleData.start_time, scheduleData.end_time, selectedClient?.hourly_rate]);

    const submitFocusTask = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedClient) return;
        setSubmittingFocus(true);
        setScheduleErrors({});

        const start_at = `${scheduleData.date} ${scheduleData.start_time}:00`;
        const end_at = `${scheduleData.date} ${scheduleData.end_time}:00`;

        router.post(safeRoute('admin.tasks.client-tasks.store', selectedClient.id, `/admin/tasks/client-tasks/${selectedClient.id}/todos`), {
            title: scheduleData.title,
            start_at,
            end_at
        }, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Scheduled task booked successfully!');
                setScheduleData({ ...scheduleData, title: '', start_time: '', end_time: '' });
                setSubmittingFocus(false);
            },
            onError: (errs: any) => {
                setScheduleErrors(errs);
                if (errs.error) toast.error(errs.error);
                else if (errs.start_at) toast.error(errs.start_at);
                else toast.error('Please fix the validation errors.');
                setSubmittingFocus(false);
            }
        });
    };

    const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setSearch(e.target.value);
    };

    const handleSelectClient = (clientId: number) => {
        router.get(route('admin.tasks.client-tasks'), { client_id: clientId });
    };

    const handleBackToSelection = () => {
        router.get(route('admin.tasks.client-tasks'));
    };

    const handleRefund = (todoId: number) => {
        if (confirm('Are you sure you want to process a refund for the remaining time of this item?')) {
            setRefundingId(todoId);
            router.post(route('admin.tasks.todos.refund', todoId), {}, {
                onSuccess: () => {
                    toast.success('Refund processed successfully!');
                    setRefundingId(null);
                },
                onError: (errors: any) => {
                    const errMsg = errors.error || 'Failed to process refund.';
                    toast.error(errMsg);
                    setRefundingId(null);
                }
            });
        }
    };

    const handleDeleteTodo = (todoId: number) => {
        if (confirm('Are you sure you want to remove this item from the queue?')) {
            router.delete(route('admin.tasks.todos.destroy', todoId), {
                onSuccess: () => toast.success('Task removed from queue.'),
                onError: (errors: any) => toast.error(errors.error || 'Failed to delete task.')
            });
        }
    };

    const handlePayTodo = (todoId: number) => {
        if (confirm('Are you sure you want to confirm and schedule this task? The amount will be deducted from the client balance.')) {
            router.post(route('admin.tasks.todos.pay-schedule', todoId), {}, {
                onSuccess: () => toast.success('Task scheduled and billed successfully!'),
                onError: (errors: any) => toast.error(errors.error || 'Failed to process task payment.')
            });
        }
    };

    const handleToggleComplete = (todoId: number, currentStatus: boolean) => {
        router.post(route('admin.tasks.todos.complete', todoId), { completed: !currentStatus }, {
            preserveScroll: true,
            onSuccess: () => toast.success('Task status updated.'),
            onError: () => toast.error('Failed to update task status.')
        });
    };

    const handleAddUnpaidTodo = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedClient || !newTodoTitle.trim()) return;

        router.post(route('admin.tasks.client-tasks.store-unpaid', selectedClient.id), { title: newTodoTitle }, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Task added to the queue.');
                setNewTodoTitle('');
            },
            onError: (errors: any) => toast.error(errors.error || 'Failed to add task.')
        });
    };

    const filteredClients = clients.filter(c => 
        (c.name?.toLowerCase() || '').includes(search.toLowerCase()) ||
        (c.email?.toLowerCase() || '').includes(search.toLowerCase()) ||
        c.id.toString() === search.trim()
    );

    return (
        <AdminSidebarLayout title={__('general.client_tasks')} header="Client Tasks">
            <Head title={selectedClient ? `Tasks for ${selectedClient.name}` : "Select Client — Admin"} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                            <ListTodo className="h-6 w-6 text-slate-900" />
                            {selectedClient ? `Tasks for ${selectedClient.name}` : "Select Client"}
                        </h1>
                        <p className="text-sm text-slate-500 mt-1">
                            {selectedClient 
                                ? `Manage checklist items, scheduling, and process refunds for ${selectedClient.name}.`
                                : "Choose a client to view and manage their checklist items and process refunds."}
                        </p>
                    </div>
                    {selectedClient && (
                        <Button 
                            onClick={handleBackToSelection}
                            variant="outline" 
                            size="sm"
                            className="flex items-center gap-2 text-slate-600 hover:text-slate-900 border-slate-200"
                        >
                            <ArrowLeft className="h-4 w-4" />{__('general.back_to_selection')}</Button>
                    )}
                </div>

                {!selectedClient ? (
                    /* Client Selection Grid */
                    <div className="space-y-4">
                        <Card className="rounded-xl border border-slate-200 bg-white shadow-sm max-w-md">
                            <CardContent className="p-4">
                                <div className="relative">
                                    <Search className="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                                    <Input
                                        type="text"
                                        placeholder={__('general.search_client_by_name_or_email')}
                                        value={search}
                                        onChange={handleSearchChange}
                                        className="ps-9 h-10 border-slate-200 focus-visible:ring-slate-900"
                                        autoComplete="off"
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        {filteredClients.length === 0 ? (
                            <Card className="rounded-xl border border-dashed border-slate-300 bg-white shadow-sm">
                                <CardContent className="flex flex-col items-center justify-center p-16 text-center">
                                    <User className="h-12 w-12 text-slate-300 mb-4" />
                                    <h3 className="font-semibold text-slate-700 text-sm">
                                        {search ? 'No Clients Match Your Search' : 'No Clients Found'}
                                    </h3>
                                    <p className="text-xs text-slate-400 max-w-xs mt-1">
                                        {search
                                            ? `No client records match "${search}". Try a different name or email.`
                                            : 'No platform clients are registered yet.'}
                                    </p>
                                    {search && (
                                        <button
                                            onClick={() => setSearch('')}
                                            className="mt-3 text-xs font-semibold text-slate-900 hover:text-slate-900 underline"
                                        >{__('general.clear_search')}</button>
                                    )}
                                </CardContent>
                            </Card>
                        ) : (
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                {filteredClients.map((client) => (
                                    <Card key={client.id} className="rounded-xl border border-slate-200 bg-white hover:shadow-md transition-shadow duration-200 overflow-hidden">
                                        <CardContent className="p-6 space-y-4">
                                            <div className="flex items-center gap-4">
                                                <div className="h-12 w-12 rounded-full bg-slate-50 text-slate-900 flex items-center justify-center font-bold text-base">
                                                    {client.initials}
                                                </div>
                                                <div className="min-w-0">
                                                    <h3 className="font-bold text-slate-900 truncate" title={client.name}>{client.name}</h3>
                                                    <p className="text-xs text-slate-500 truncate" title={client.email}>{client.email}</p>
                                                </div>
                                            </div>

                                            <div className="flex items-center justify-end gap-4 text-xs pt-2 border-t border-slate-100">
                                                <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-semibold bg-slate-50 text-slate-900">
                                                    <ListTodo className="h-3 w-3" /> {client.total_tasks} Tasks
                                                </span>
                                                <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-semibold bg-green-50 text-slate-900">
                                                    <CheckCircle2 className="h-3 w-3" /> {client.completed_tasks} Completed
                                                </span>
                                            </div>

                                            <Button 
                                                onClick={() => handleSelectClient(client.id)}
                                                className="w-full bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs h-9 rounded-lg"
                                            >{__('general.view_focus_board')}<ChevronRight className="h-3.5 w-3.5 ms-1" />
                                            </Button>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        )}
                    </div>
                ) : (
                    /* Selected Client Queue and Details */
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                        {/* Queue list (2 cols) */}
                        <div className="lg:col-span-2 space-y-6">
                            {/* Schedule Focus Time Form */}
                            <Card className="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                                <div className="bg-slate-50 px-5 py-3 border-b border-slate-50 flex items-center justify-between">
                                    <h3 className="font-bold text-slate-900 flex items-center gap-2">
                                        <CalendarCheck className="h-4 w-4 text-slate-900" />{__('general.schedule_focus_time')}</h3>
                                    <span className="text-xs font-semibold bg-slate-50 text-slate-900 px-2 py-0.5 rounded-full">
                                        Hourly Rate: {formatCurrency(selectedClient.hourly_rate || 0, selectedClient.currency)}/hr
                                    </span>
                                </div>
                                <CardContent className="p-5">
                                    <form onSubmit={submitFocusTask} className="space-y-4">
                                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div className="space-y-1.5">
                                                <label className="text-xs font-semibold text-slate-700 uppercase">{__('general.target_date')}</label>
                                                <Input 
                                                    type="date" 
                                                    value={scheduleData.date}
                                                    onChange={e => setScheduleData({...scheduleData, date: e.target.value})}
                                                    className="border-slate-200 h-9"
                                                    required
                                                />
                                            </div>
                                            <div className="space-y-1.5">
                                                <label className="text-xs font-semibold text-slate-700 uppercase">{__('general.start_time')}</label>
                                                <Input 
                                                    type="time" 
                                                    value={scheduleData.start_time}
                                                    onChange={e => setScheduleData({...scheduleData, start_time: e.target.value})}
                                                    className="border-slate-200 h-9"
                                                    required
                                                />
                                            </div>
                                            <div className="space-y-1.5">
                                                <label className="text-xs font-semibold text-slate-700 uppercase">{__('general.end_time')}</label>
                                                <Input 
                                                    type="time" 
                                                    value={scheduleData.end_time}
                                                    onChange={e => setScheduleData({...scheduleData, end_time: e.target.value})}
                                                    className="border-slate-200 h-9"
                                                    required
                                                />
                                            </div>
                                        </div>

                                        {estimatedCost > 0 && (
                                            <div className="bg-slate-50 p-3 rounded-lg border border-slate-100 flex items-center justify-between">
                                                <span className="text-sm text-slate-600 font-semibold">Estimated Investment:</span>
                                                <div className="flex items-center gap-3">
                                                    <span className="text-lg font-bold text-slate-900">
                                                        {formatCurrency(estimatedCost, selectedClient.currency)}
                                                    </span>
                                                    <span className={`text-xs font-bold px-2 py-1 rounded ${selectedClient.balance >= estimatedCost ? 'bg-green-100 text-slate-900' : 'bg-red-100 text-slate-900'}`}>
                                                        Available: {formatCurrency(selectedClient.balance, selectedClient.currency)}
                                                    </span>
                                                </div>
                                            </div>
                                        )}

                                        <div className="flex gap-2">
                                            <div className="flex-grow space-y-1.5">
                                                <Input 
                                                    type="text" 
                                                    value={scheduleData.title}
                                                    onChange={e => setScheduleData({...scheduleData, title: e.target.value})}
                                                    placeholder={__('general.describe_the_specific_engineering_outcome')}
                                                    className="border-slate-200 h-10"
                                                    required
                                                />
                                                {scheduleErrors?.title && <p className="text-xs text-slate-900">{scheduleErrors.title}</p>}
                                            </div>
                                            <Button 
                                                type="submit" 
                                                disabled={submittingFocus || (estimatedCost > 0 && selectedClient.balance < estimatedCost)}
                                                className="bg-slate-900 hover:bg-slate-900 text-white font-semibold h-10 px-6 shrink-0"
                                            >
                                                {submittingFocus ? 'Booking...' : 'Book Time'}
                                            </Button>
                                        </div>
                                    </form>
                                </CardContent>
                            </Card>

                            {/* Sprint items */}
                            <div className="space-y-4">
                                <div className="flex items-center justify-between mb-2">
                                    <h2 className="text-lg font-bold text-slate-900 flex items-center gap-2">
                                        <Activity className="h-5 w-5 text-slate-700" />{__('general.active_sprint_items')}</h2>
                                    <Badge variant="outline" className="bg-slate-900 text-white font-semibold px-2 py-0.5 rounded text-xs">
                                        {todos.length}
                                    </Badge>
                                </div>

                                {/* Add Unpaid Todo Form */}
                                <form onSubmit={handleAddUnpaidTodo} className="flex gap-2 mb-4">
                                    <Input
                                        type="text"
                                        value={newTodoTitle}
                                        onChange={e => setNewTodoTitle(e.target.value)}
                                        placeholder={__('general.add_an_item_to_the_queue')}
                                        className="flex-grow rounded-lg shadow-sm bg-white"
                                    />
                                    <Button type="submit" disabled={!newTodoTitle.trim()} className="bg-slate-800 hover:bg-slate-900 text-white rounded-lg px-4 shadow-sm flex-shrink-0">
                                        <PlusCircle className="h-4 w-4 me-2" />{__('general.add_to_queue')}</Button>
                                </form>

                                {todos.length === 0 ? (
                                    <Card className="rounded-xl border border-dashed border-slate-300 bg-white shadow-sm">
                                        <CardContent className="flex flex-col items-center justify-center p-16 text-center">
                                            <CheckCircle2 className="h-12 w-12 text-slate-300 mb-4" />
                                            <h3 className="font-semibold text-slate-700 text-sm">{__('general.the_queue_is_empty')}</h3>
                                            <p className="text-xs text-slate-400 max-w-xs mt-1 mb-4">{__('general.create_the_first_task_for_this_client_to_get_started')}</p>
                                            <Link 
                                                href={`/admin/projects?client_id=${selectedClient.id}`}
                                                className="inline-flex items-center justify-center px-4 h-9 rounded-lg bg-slate-900 hover:bg-slate-900 text-white text-xs font-semibold"
                                            >
                                                <ListTodo className="h-4 w-4 me-2" />{__('general.create_first_task')}</Link>
                                        </CardContent>
                                    </Card>
                                ) : (
                                    <div className="space-y-4">
                                        {todos.map((todo) => (
                                            <Card key={todo.id} className="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden hover:border-slate-300 transition-colors">
                                                <CardContent className="p-5 flex flex-col md:flex-row md:items-start justify-between gap-4">
                                                    <div className="space-y-2 flex-grow min-w-0">
                                                        <div className="flex flex-wrap items-center gap-2">
                                                            {!todo.is_paid ? (
                                                                <>
                                                                    <Badge className="bg-slate-900 hover:bg-slate-900 text-white text-[10px] font-bold px-2 py-1 rounded-none uppercase">{__('general.awaiting_scheduling')}</Badge>
                                                                    <span className="text-xs font-bold text-slate-500">
                                                                        {todo.start_at ? new Date(todo.start_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '—'}
                                                                    </span>
                                                                </>
                                                            ) : (
                                                                <>
                                                                    <Badge variant="outline" className="bg-slate-100 text-slate-600 border-slate-200 text-[10px] font-bold px-1.5 py-0.5">
                                                                        📌 {todo.task_name}
                                                                    </Badge>
                                                                    <Badge className="bg-green-100 text-slate-900 border border-green-200 text-[10px] font-bold px-1.5 py-0.5 shadow-none hover:bg-green-100">
                                                                        {__('general.paid')}</Badge>
                                                                    {todo.start_at ? (
                                                                        <Badge className="bg-slate-50 text-slate-900 border border-slate-200 text-[10px] font-bold px-1.5 py-0.5 shadow-none hover:bg-slate-50 flex items-center gap-0.5">
                                                                            <CalendarCheck className="h-2.5 w-2.5" /> {__('general.scheduled')}</Badge>
                                                                    ) : (
                                                                        <Badge variant="outline" className="bg-yellow-50 text-yellow-600 border-yellow-200 text-[10px] font-bold px-1.5 py-0.5">{__('general.awaiting_scheduling_1')}</Badge>
                                                                    )}
                                                                </>
                                                            )}
                                                            {todo.refunded && (
                                                                <Badge className="bg-slate-900 text-white text-[10px] font-bold px-2 py-1 shadow-none rounded-none uppercase">
                                                                    {__('general.refunded')}</Badge>
                                                            )}
                                                            <div className="ms-auto md:hidden">
                                                                <span className="font-bold text-slate-900">
                                                                    {formatCurrency(todo.cost_in_client_currency, todo.client_currency)}
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div className="flex justify-between items-center">
                                                            <h3 className="font-bold text-slate-900 text-sm">{todo.title}</h3>
                                                            <span className="font-bold text-slate-900 hidden md:inline-block">
                                                                {formatCurrency(todo.cost_in_client_currency, todo.client_currency)}
                                                            </span>
                                                        </div>
                                                        
                                                        {todo.description && (
                                                            <p className="text-xs text-slate-500 leading-relaxed max-w-xl">
                                                                {todo.description}
                                                            </p>
                                                        )}

                                                        <div className="flex flex-wrap items-center gap-3 pt-2 text-[11px] text-slate-400 font-medium">
                                                            {todo.is_paid && (todo.start_at || todo.end_at) && (
                                                                <span className="flex items-center gap-1">
                                                                    <Calendar className="h-3.5 w-3.5" />
                                                                    {todo.start_at ? new Date(todo.start_at).toLocaleString() : '—'}
                                                                    {' → '}
                                                                    {todo.end_at ? new Date(todo.end_at).toLocaleString() : '—'}
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>

                                                    {/* Actions */}
                                                    <div className="flex-shrink-0 self-end md:self-start flex flex-col gap-2 items-end">
                                                        <Link
                                                            href={safeRoute('admin.projects.index', { project: todo.task_id }, '/admin/projects')}
                                                            className="inline-flex items-center justify-center bg-slate-900 hover:bg-slate-900 text-white font-semibold text-xs h-8 px-3 rounded-lg shadow-sm"
                                                        >
                                                            <Search className="h-3.5 w-3.5 me-1" />{__('general.view_task')}</Link>
                                                        {todo.is_paid && !todo.refunded && (
                                                            <Button
                                                                onClick={() => handleToggleComplete(todo.id, todo.completed)}
                                                                className={`font-semibold text-xs h-8 px-3 rounded-lg flex items-center gap-1.5 border shadow-sm mt-1 ${todo.completed ? 'bg-green-50 text-slate-900 hover:bg-green-100 border-green-200' : 'bg-white text-slate-700 hover:bg-slate-50 border-slate-200'}`}
                                                            >
                                                                <CheckCircle2 className="h-3.5 w-3.5" />
                                                                {todo.completed ? 'Completed' : 'Mark Complete'}
                                                            </Button>
                                                        )}
                                                        {todo.is_paid && !todo.refunded && todo.show_refund && (
                                                            <Button
                                                                onClick={() => handleRefund(todo.id)}
                                                                disabled={refundingId === todo.id}
                                                                className="bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs h-8 px-3 rounded-lg flex items-center gap-1.5 border shadow-sm mt-1"
                                                            >
                                                                <RotateCcw className="h-3.5 w-3.5" />
                                                                {refundingId === todo.id ? 'Refunding...' : 'Refund'}
                                                            </Button>
                                                        )}
                                                        {!todo.is_paid && (
                                                            <div className="flex flex-col gap-2 mt-1">
                                                                <Button
                                                                    onClick={() => handlePayTodo(todo.id)}
                                                                    className="bg-slate-900 hover:bg-slate-900 text-white font-semibold text-xs h-8 px-3 rounded-lg flex items-center gap-1.5 shadow-sm"
                                                                >
                                                                    <Wallet className="h-3.5 w-3.5" />{__('general.confirm_schedule')}</Button>
                                                                <Button
                                                                    onClick={() => handleDeleteTodo(todo.id)}
                                                                    variant="destructive"
                                                                    className="font-semibold text-xs h-8 px-3 rounded-lg flex items-center gap-1.5 shadow-sm"
                                                                >
                                                                    <Trash2 className="h-3.5 w-3.5" />{__('general.delete_item')}</Button>
                                                            </div>
                                                        )}
                                                    </div>
                                                </CardContent>
                                            </Card>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Admin Info details card (1 col) */}
                        <div className="space-y-6">
                            <Card className="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden mt-0">
                                <div className="bg-white px-5 py-3 flex items-center gap-3 border-b border-slate-100">
                                    <ShieldCheck className="h-5 w-5 text-slate-700" />
                                    <div>
                                        <h3 className="font-bold text-[10px] uppercase tracking-wider text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full inline-block mb-1">{__('general.admin_view')}</h3>
                                        <h2 className="font-bold text-sm text-slate-800 leading-none">Managing Tasks for: {selectedClient.name}</h2>
                                    </div>
                                </div>
                                <CardContent className="p-5 space-y-4">
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="space-y-1">
                                            <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{__('general.email')}</span>
                                            <p className="text-sm font-bold text-slate-800 break-all">{selectedClient.email}</p>
                                        </div>
                                        <div className="space-y-1">
                                            <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{__('general.available_balance')}</span>
                                            <p className={`text-sm font-bold ${selectedClient.balance <= 0 ? 'text-slate-900' : 'text-slate-900'}`}>
                                                {formatCurrency(selectedClient.balance, selectedClient.currency)}
                                            </p>
                                        </div>
                                        <div className="space-y-1">
                                            <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{__('general.total_tasks')}</span>
                                            <p className="text-sm font-bold text-slate-800">{selectedClient.total_tasks}</p>
                                        </div>
                                        <div className="space-y-1">
                                            <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{__('general.completed_tasks')}</span>
                                            <p className="text-sm font-bold text-slate-800">{selectedClient.completed_tasks}</p>
                                        </div>
                                    </div>

                                    <div className="pt-4 flex flex-col gap-2">
                                        <Link 
                                            href={`/admin/projects?client_id=${selectedClient.id}`}
                                            className="inline-flex items-center justify-center w-full h-9 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 transition-colors text-xs font-semibold text-slate-700 uppercase"
                                        >
                                            <ListTodo className="h-4 w-4 me-2" />{__('general.all_client_tasks')}</Link>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                )}
            </div>
        </AdminSidebarLayout>
    );
}

// Helpers
const safeRoute = (name: string, params?: any, fallbackUrl?: string) => {
    try {
        if (typeof route !== 'undefined' && route().has(name)) {
            return route(name, params);
        }
    } catch (e) { /* empty */ }
    return fallbackUrl || '#';
};

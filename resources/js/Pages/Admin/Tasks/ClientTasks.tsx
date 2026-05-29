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
    Clock,
    Loader2,
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Card, CardContent } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { toast } from 'sonner';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';

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
    const [schedulingId, setSchedulingId] = useState<number | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [scheduleForm, setScheduleForm] = useState({ start_at: '', end_at: '' });

    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [createForm, setCreateForm] = useState({ title: '', description: '', start_at: '', end_at: '' });
    const [isSubmitting, setIsSubmitting] = useState(false);

    const formatForInput = (dateStr: string | null) => {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
    };

    const openScheduleForm = (todo: TodoItem) => {
        setSchedulingId(todo.id);
        setSubmitting(false);
        setScheduleForm({
            start_at: formatForInput(todo.start_at),
            end_at: formatForInput(todo.end_at),
        });
    };

    const handleScheduleSubmit = (todoId: number) => {
        if (!scheduleForm.start_at || !scheduleForm.end_at) {
            toast.error('Both start and end times are required.');
            return;
        }
        setSubmitting(true);
        router.post(route('admin.tasks.todos.schedule', todoId), scheduleForm, {
            onSuccess: () => {
                toast.success('Time scheduled successfully!');
                setSchedulingId(null);
                setSubmitting(false);
            },
            onError: (errors: any) => {
                toast.error(errors.start_at || errors.end_at || 'Failed to schedule time.');
                setSubmitting(false);
            }
        });
    };

    const handleCreateSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!createForm.title || !createForm.start_at || !createForm.end_at) {
            toast.error('Title, start time, and end time are required.');
            return;
        }
        setIsSubmitting(true);
        router.post(route('admin.tasks.client-tasks.store', selectedClient?.id), createForm, {
            onSuccess: () => {
                toast.success('Scheduled task created and billed successfully!');
                setIsCreateModalOpen(false);
                setCreateForm({ title: '', description: '', start_at: '', end_at: '' });
                setIsSubmitting(false);
            },
            onError: (errors: any) => {
                setIsSubmitting(false);
                const errMsg = errors.error || errors.end_at || errors.start_at || errors.title || 'Failed to create task.';
                toast.error(errMsg);
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

    const filteredClients = clients.filter(c => 
        (c.name?.toLowerCase() || '').includes(search.toLowerCase()) ||
        (c.email?.toLowerCase() || '').includes(search.toLowerCase()) ||
        c.id.toString() === search.trim()
    );

    return (
        <AdminSidebarLayout title="Client Tasks" header="Client Tasks">
            <Head title={selectedClient ? `Tasks for ${selectedClient.name}` : "Select Client — Admin"} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                            <ListTodo className="h-6 w-6 text-indigo-600" />
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
                            <ArrowLeft className="h-4 w-4" /> Back to Selection
                        </Button>
                    )}
                </div>

                {!selectedClient ? (
                    /* Client Selection Grid */
                    <div className="space-y-4">
                        <Card className="rounded-xl border border-slate-200 bg-white shadow-sm max-w-md">
                            <CardContent className="p-4">
                                <div className="relative">
                                    <Search className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                                    <Input
                                        type="text"
                                        placeholder="Search client by name or email..."
                                        value={search}
                                        onChange={handleSearchChange}
                                        className="pl-9 h-10 border-slate-200 focus-visible:ring-indigo-500"
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
                                            className="mt-3 text-xs font-semibold text-indigo-600 hover:text-indigo-800 underline"
                                        >
                                            Clear search
                                        </button>
                                    )}
                                </CardContent>
                            </Card>
                        ) : (
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                {filteredClients.map((client) => (
                                    <Card key={client.id} className="rounded-xl border border-slate-200 bg-white hover:shadow-md transition-shadow duration-200 overflow-hidden">
                                        <CardContent className="p-6 space-y-4">
                                            <div className="flex items-center gap-4">
                                                <div className="h-12 w-12 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-base">
                                                    {client.initials}
                                                </div>
                                                <div className="min-w-0">
                                                    <h3 className="font-bold text-slate-900 truncate" title={client.name}>{client.name}</h3>
                                                    <p className="text-xs text-slate-500 truncate" title={client.email}>{client.email}</p>
                                                </div>
                                            </div>

                                            <div className="flex items-center justify-between text-xs pt-2 border-t border-slate-100">
                                                <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-semibold bg-indigo-50 text-indigo-700">
                                                    <ListTodo className="h-3 w-3" /> {client.total_tasks} Tasks
                                                </span>
                                                <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-semibold bg-emerald-50 text-emerald-700">
                                                    <CheckCircle2 className="h-3 w-3" /> {client.completed_tasks} Completed
                                                </span>
                                            </div>

                                            <Button 
                                                onClick={() => handleSelectClient(client.id)}
                                                className="w-full bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs h-9 rounded-lg"
                                            >
                                                View Focus Board <ChevronRight className="h-3.5 w-3.5 ml-1" />
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
                            {/* Create New Task card */}
                            <Card className="rounded-xl border border-indigo-100 bg-indigo-50/20 shadow-none">
                                <CardContent className="p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div>
                                        <h3 className="font-bold text-indigo-900 text-base">Create Task for {selectedClient.name}</h3>
                                        <p className="text-xs text-indigo-700 mt-1">Jump to the task board builder to create a new milestone queue, or schedule an immediate focus time.</p>
                                    </div>
                                    <div className="flex items-center gap-2 shrink-0 flex-wrap">
                                        <Button 
                                            onClick={() => setIsCreateModalOpen(true)}
                                            className="px-4 h-9 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold"
                                        >
                                            <Clock className="h-4 w-4 me-2" /> Schedule Focus Time
                                        </Button>
                                        <Link 
                                            href={safeRoute('admin.projects.index', {}, '/admin/projects')}
                                            className="inline-flex items-center justify-center px-4 h-9 rounded-lg border border-indigo-200 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold"
                                        >
                                            Task Board
                                        </Link>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Sprint items */}
                            <div className="space-y-4">
                                <div className="flex items-center justify-between">
                                    <h2 className="text-lg font-bold text-slate-900 flex items-center gap-2">
                                        <Activity className="h-5 w-5 text-slate-700" />
                                        Active Sprint Queue
                                    </h2>
                                    <Badge variant="outline" className="bg-slate-50 border-slate-200 font-semibold px-2 py-0.5 rounded text-xs">
                                        {todos.length} Active Items
                                    </Badge>
                                </div>

                                {todos.length === 0 ? (
                                    <Card className="rounded-xl border border-dashed border-slate-300 bg-white shadow-sm">
                                        <CardContent className="flex flex-col items-center justify-center p-16 text-center">
                                            <CheckCircle2 className="h-12 w-12 text-slate-300 mb-4" />
                                            <h3 className="font-semibold text-slate-700 text-sm">Queue is Empty</h3>
                                            <p className="text-xs text-slate-400 max-w-xs mt-1">
                                                All items are completed or no tasks have been created for this client.
                                            </p>
                                        </CardContent>
                                    </Card>
                                ) : (
                                    <div className="space-y-4">
                                        {todos.map((todo) => (
                                            <Card key={todo.id} className="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden hover:border-slate-300 transition-colors">
                                                <CardContent className="p-5 flex flex-col md:flex-row md:items-start justify-between gap-4">
                                                    <div className="space-y-2 flex-grow min-w-0">
                                                        <div className="flex flex-wrap items-center gap-2">
                                                            <Badge variant="outline" className="bg-slate-100 text-slate-600 border-slate-200 text-[10px] font-bold px-1.5 py-0.5">
                                                                📌 {todo.task_name}
                                                            </Badge>
                                                            {todo.is_paid ? (
                                                                <Badge className="bg-emerald-100 text-emerald-700 border border-emerald-200 text-[10px] font-bold px-1.5 py-0.5 shadow-none hover:bg-emerald-100">
                                                                    Paid
                                                                </Badge>
                                                            ) : (
                                                                <Badge variant="outline" className="bg-slate-100 text-slate-500 border-slate-200 text-[10px] font-bold px-1.5 py-0.5">
                                                                    Unpaid
                                                                </Badge>
                                                            )}
                                                            {todo.start_at ? (
                                                                <Badge className="bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10px] font-bold px-1.5 py-0.5 shadow-none hover:bg-indigo-50 flex items-center gap-0.5">
                                                                    <CalendarCheck className="h-2.5 w-2.5" /> Scheduled
                                                                </Badge>
                                                            ) : (
                                                                <Badge variant="outline" className="bg-amber-50 text-amber-600 border-amber-200 text-[10px] font-bold px-1.5 py-0.5">
                                                                    Awaiting Scheduling
                                                                </Badge>
                                                            )}
                                                            {todo.refunded && (
                                                                <Badge className="bg-rose-100 text-rose-700 border border-rose-200 text-[10px] font-bold px-1.5 py-0.5 shadow-none hover:bg-rose-100">
                                                                    Refunded
                                                                </Badge>
                                                            )}
                                                        </div>

                                                        <h3 className="font-bold text-slate-900 text-sm">{todo.title}</h3>
                                                        
                                                        {todo.description && (
                                                            <p className="text-xs text-slate-500 leading-relaxed max-w-xl">
                                                                {todo.description}
                                                            </p>
                                                        )}

                                                        <div className="flex flex-wrap items-center gap-3 pt-2 text-[11px] text-slate-400 font-medium">
                                                            {todo.cost > 0 && (
                                                                <span className="flex items-center gap-0.5 text-emerald-600 font-semibold">
                                                                    <DollarSign className="h-3.5 w-3.5" />
                                                                    {formatCurrency(todo.cost_in_client_currency, todo.client_currency)}
                                                                </span>
                                                            )}
                                                            {(todo.start_at || todo.end_at) && (
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
                                                        {schedulingId === todo.id ? (
                                                            <div className="flex flex-col gap-2 p-3 bg-slate-50 border border-slate-200 rounded-lg min-w-[240px]">
                                                                <div className="space-y-1">
                                                                    <label className="text-[10px] font-bold text-slate-500 uppercase">Start Time</label>
                                                                    <Input 
                                                                        type="datetime-local" 
                                                                        value={scheduleForm.start_at}
                                                                        onChange={(e) => setScheduleForm({...scheduleForm, start_at: e.target.value})}
                                                                        className="h-8 text-xs"
                                                                    />
                                                                </div>
                                                                <div className="space-y-1">
                                                                    <label className="text-[10px] font-bold text-slate-500 uppercase">End Time</label>
                                                                    <Input 
                                                                        type="datetime-local" 
                                                                        value={scheduleForm.end_at}
                                                                        onChange={(e) => setScheduleForm({...scheduleForm, end_at: e.target.value})}
                                                                        className="h-8 text-xs"
                                                                    />
                                                                </div>
                                                                <div className="flex justify-end gap-2 mt-1">
                                                                    <Button 
                                                                        variant="ghost" 
                                                                        size="sm" 
                                                                        onClick={() => setSchedulingId(null)}
                                                                        disabled={submitting}
                                                                        className="h-7 px-2 text-xs text-slate-500"
                                                                    >
                                                                        Cancel
                                                                    </Button>
                                                                    <Button 
                                                                        size="sm" 
                                                                        onClick={() => handleScheduleSubmit(todo.id)}
                                                                        disabled={submitting}
                                                                        className="h-7 px-3 text-xs bg-indigo-600 hover:bg-indigo-700 text-white flex items-center gap-1.5"
                                                                    >
                                                                        {submitting ? (
                                                                            <><Loader2 className="h-3 w-3 animate-spin" /> Saving...</>
                                                                        ) : 'Save Schedule'}
                                                                    </Button>
                                                                </div>
                                                            </div>
                                                        ) : (
                                                            <>
                                                                <Button
                                                                    onClick={() => openScheduleForm(todo)}
                                                                    variant="outline"
                                                                    className="font-semibold text-xs h-8 px-3 rounded-lg flex items-center gap-1.5 border-slate-200 shadow-sm hover:bg-slate-50"
                                                                >
                                                                    <Clock className="h-3.5 w-3.5" />
                                                                    Schedule Time
                                                                </Button>
                                                                {todo.is_paid && !todo.refunded && todo.show_refund && (
                                                                    <Button
                                                                        onClick={() => handleRefund(todo.id)}
                                                                        disabled={refundingId === todo.id}
                                                                        className="bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs h-8 px-3 rounded-lg flex items-center gap-1.5 border-0 shadow-sm"
                                                                    >
                                                                        <RotateCcw className="h-3.5 w-3.5" />
                                                                        {refundingId === todo.id ? 'Refunding...' : 'Refund Remaining'}
                                                                    </Button>
                                                                )}
                                                            </>
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
                            <Card className="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                                <div className="bg-slate-900 px-6 py-4 flex items-center gap-3 text-white border-b border-slate-800">
                                    <ShieldCheck className="h-5 w-5 text-indigo-400" />
                                    <div>
                                        <h3 className="font-bold text-xs uppercase tracking-wider text-slate-400">Admin Privileges</h3>
                                        <h2 className="font-bold text-sm text-white mt-0.5">Control Panel</h2>
                                    </div>
                                </div>
                                <CardContent className="p-6 space-y-5">
                                    <div className="space-y-1">
                                        <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Client Email</span>
                                        <p className="text-sm font-bold text-slate-800 break-all">{selectedClient.email}</p>
                                    </div>

                                    <div className="space-y-1">
                                        <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Available Balance</span>
                                        <div className="flex items-baseline gap-1.5">
                                            <Wallet className="h-4 w-4 text-emerald-600 self-center" />
                                            <p className="text-lg font-bold text-emerald-600">
                                                {formatCurrency(selectedClient.balance, selectedClient.currency)}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-4 pt-4 border-t border-slate-100">
                                        <div className="space-y-0.5">
                                            <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Tasks</span>
                                            <p className="text-base font-bold text-slate-800">{selectedClient.total_tasks}</p>
                                        </div>
                                        <div className="space-y-0.5">
                                            <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Completed Tasks</span>
                                            <p className="text-base font-bold text-slate-800">{selectedClient.completed_tasks}</p>
                                        </div>
                                    </div>

                                    <div className="pt-4 border-t border-slate-100 flex flex-col gap-2">
                                        <Link 
                                            href={`/admin/projects?client_id=${selectedClient.id}`}
                                            className="inline-flex items-center justify-center w-full h-9 rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors text-xs font-semibold text-slate-700"
                                        >
                                            All Client Projects <ChevronRight className="h-3.5 w-3.5 ml-1 text-slate-400" />
                                        </Link>
                                        <Link 
                                            href={`/admin/users/reports/${selectedClient.id}`}
                                            className="inline-flex items-center justify-center w-full h-9 rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors text-xs font-semibold text-slate-700"
                                        >
                                            All Client Tasks & Reports <ChevronRight className="h-3.5 w-3.5 ml-1 text-slate-400" />
                                        </Link>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Policy Banner */}
                            <Card className="rounded-xl border border-slate-200 bg-amber-50/20 shadow-none">
                                <CardContent className="p-5 space-y-3">
                                    <div className="flex items-center gap-2 text-amber-800">
                                        <AlertCircle className="h-5 w-5 shrink-0" />
                                        <h3 className="font-bold text-xs uppercase tracking-wider">Refund Policy Rules</h3>
                                    </div>
                                    <ul className="list-disc pl-5 space-y-1.5 text-xs text-slate-600">
                                        <li>Full refund is granted if the checklist item is paid but has no scheduling window booked.</li>
                                        <li>Full refund is granted if the scheduled end time is in the future.</li>
                                        <li>Partial refund is calculated dynamically based on remaining minutes if currently in progress.</li>
                                        <li>No refund is possible if the scheduled duration has already fully elapsed.</li>
                                    </ul>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                )}
            </div>

            {/* Create Scheduled Task Modal */}
            <Dialog open={isCreateModalOpen} onOpenChange={setIsCreateModalOpen}>
                <DialogContent className="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>Schedule Focus Time</DialogTitle>
                        <DialogDescription>
                            Create a new focus task for {selectedClient?.name}. The cost will be automatically calculated and deducted from their balance.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleCreateSubmit} className="space-y-4 pt-4">
                        <div className="space-y-2">
                            <Label htmlFor="title">Task Title</Label>
                            <Input 
                                id="title" 
                                value={createForm.title}
                                onChange={e => setCreateForm({...createForm, title: e.target.value})}
                                placeholder="e.g. Server Setup & Configuration"
                                required
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="description">Description (Optional)</Label>
                            <Textarea 
                                id="description" 
                                value={createForm.description}
                                onChange={e => setCreateForm({...createForm, description: e.target.value})}
                                placeholder="Details about what will be done..."
                                rows={3}
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="start_at">Start Time</Label>
                                <Input 
                                    id="start_at" 
                                    type="datetime-local" 
                                    value={createForm.start_at}
                                    onChange={e => setCreateForm({...createForm, start_at: e.target.value})}
                                    required
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="end_at">End Time</Label>
                                <Input 
                                    id="end_at" 
                                    type="datetime-local" 
                                    value={createForm.end_at}
                                    onChange={e => setCreateForm({...createForm, end_at: e.target.value})}
                                    required
                                />
                            </div>
                        </div>
                        <DialogFooter className="pt-4">
                            <Button type="button" variant="outline" onClick={() => setIsCreateModalOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={isSubmitting} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                                {isSubmitting ? 'Creating...' : 'Create & Bill Client'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AdminSidebarLayout>
    );
}

// Helpers
const safeRoute = (name: string, params?: any, fallbackUrl?: string) => {
    try {
        // @ts-ignore
        if (typeof route !== 'undefined' && route().has(name)) {
            // @ts-ignore
            return route(name, params);
        }
    } catch (e) {}
    return fallbackUrl || '#';
};

import React, { useState } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, router, useForm } from '@inertiajs/react';
import {
    Calendar as CalendarIcon,
    ChevronLeft,
    ChevronRight,
    ListTodo,
    CheckCircle2,
    Circle,
    Clock,
    AlertCircle,
    User,
    Plus,
    Trash2,
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { ClientAutocomplete } from '@/Components/ClientAutocomplete';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { format, startOfMonth, endOfMonth, eachDayOfInterval, isSameMonth, isToday, addMonths, subMonths, startOfWeek, endOfWeek } from 'date-fns';
import { __ } from '@/lib/i18n';

interface TaskEvent {
    id: number;
    title: string;
    priority: string;
    completed: boolean;
    client: string | null;
}

interface TodoEvent {
    id: number;
    title: string;
    priority: string;
    priority_color: string | null;
    completed: boolean;
    task_id: number;
    client: string | null;
    start_time: string;
}

interface BusyTimeEvent {
    id: string;
    title: string;
    is_full_day: boolean;
    start_time: string | null;
    end_time: string | null;
}

interface EventsMap {
    [date: string]: {
        tasks: TaskEvent[];
        todos: TodoEvent[];
        busy_times: BusyTimeEvent[];
    };
}

interface DropdownClient {
    id: number;
    name: string;
}

interface Props {
    events: EventsMap;
    year: number;
    month: number;
    clients: DropdownClient[];
    filters: { client_id?: string; tenant_id?: string };
}

export default function TaskCalendar({ events, year, month, clients, filters }: Props) {
    const initialClientId = filters.client_id || filters.tenant_id || '';
    const [clientFilter, setClient] = useState(initialClientId);
    const currentDate = new Date(year, month - 1, 1);

    const [isModalOpen, setIsModalOpen] = useState(false);
    
    const { data, setData, post, processing, errors, reset } = useForm({
        client_id: '',
        title: '',
        date: '',
        start_time: '',
        end_time: '',
        checklist_items: [] as { title: string }[],
    });

    const handleDayDoubleClick = (day: Date) => {
        reset();
        setData({
            client_id: clientFilter || '',
            title: '',
            date: format(day, 'yyyy-MM-dd'),
            start_time: '09:00',
            end_time: '10:00',
            checklist_items: [],
        });
        setIsModalOpen(true);
    };

    const submitTask = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.tasks.calendar.store-and-bill'), {
            onSuccess: () => {
                setIsModalOpen(false);
                reset();
            }
        });
    };

    const handleClientChange = (val: string) => {
        setClient(val);
        router.get(route('admin.tasks.calendar'), { month, year, client_id: val || undefined }, {
            preserveState: true,
            replace: true,
        });
    };

    const handlePrevMonth = () => {
        const prev = subMonths(currentDate, 1);
        router.get(route('admin.tasks.calendar'), {
            year: prev.getFullYear(),
            month: prev.getMonth() + 1,
            client_id: clientFilter || undefined
        }, { preserveState: true });
    };

    const handleNextMonth = () => {
        const next = addMonths(currentDate, 1);
        router.get(route('admin.tasks.calendar'), {
            year: next.getFullYear(),
            month: next.getMonth() + 1,
            client_id: clientFilter || undefined
        }, { preserveState: true });
    };

    const handleGoToToday = () => {
        const today = new Date();
        router.get(route('admin.tasks.calendar'), {
            year: today.getFullYear(),
            month: today.getMonth() + 1,
            client_id: clientFilter || undefined
        }, { preserveState: true });
    };

    const startDate = startOfWeek(startOfMonth(currentDate), { weekStartsOn: 1 });
    const endDate = endOfWeek(endOfMonth(currentDate), { weekStartsOn: 1 });
    const calendarDays = eachDayOfInterval({ start: startDate, end: endDate });

    return (
        <AdminSidebarLayout title={__('general.task_calendar')} header="Task Calendar">
            <Head title={__('general.task_calendar_admin')} />

            <div className="space-y-6">
                {/* Page Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                            <CalendarIcon className="h-6 w-6 text-indigo-600" />{__('general.task_calendar')}</h1>
                        <p className="text-sm text-slate-500 mt-1">{__('general.detailed_overview_of_all_tasks_todos_and_busy_times_by_date_across_platform_clients')}</p>
                    </div>

                    <div className="flex items-center gap-3 w-[250px]">
                        <ClientAutocomplete
                            value={clientFilter}
                            onChange={handleClientChange}
                            searchEndpoint={route('admin.projects.search-clients')}
                            placeholder={__('general.all_clients')}
                        />
                    </div>
                </div>

                <Card className="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    {/* Calendar Header */}
                    <div className="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <Button variant="outline" size="sm" onClick={handlePrevMonth}>
                                <ChevronLeft className="h-4 w-4" />
                            </Button>
                            <Button variant="outline" size="sm" onClick={handleGoToToday}>
                                {__('general.today')}</Button>
                            <Button variant="outline" size="sm" onClick={handleNextMonth}>
                                <ChevronRight className="h-4 w-4" />
                            </Button>
                        </div>
                        <h2 className="text-lg font-bold text-slate-800">
                            {format(currentDate, 'MMMM yyyy')}
                        </h2>
                    </div>

                    {/* Calendar Grid */}
                    <div className="grid grid-cols-7 border-b border-slate-200 bg-slate-100">
                        {['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].map(day => (
                            <div key={day} className="py-2 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {day}
                            </div>
                        ))}
                    </div>

                    <div className="grid grid-cols-7 bg-white">
                        {calendarDays.map((day, idx) => {
                            const dateStr = format(day, 'yyyy-MM-dd');
                            const dayEvents = events[dateStr] || { tasks: [], todos: [], busy_times: [] };
                            const isCurrentMonth = isSameMonth(day, currentDate);
                            const isCurrentDay = isToday(day);

                            return (
                                <div
                                    key={idx}
                                    onDoubleClick={() => handleDayDoubleClick(day)}
                                    className={`min-h-[140px] border-e border-b border-slate-100 p-2 flex flex-col transition-colors cursor-pointer ${
                                        !isCurrentMonth ? 'bg-slate-50/50 opacity-60' : ''
                                    } ${isCurrentDay ? 'bg-indigo-50/30' : 'hover:bg-slate-50/30'}`}
                                >
                                    <div className="flex justify-between items-start mb-2">
                                        <span
                                            className={`text-sm font-semibold flex items-center justify-center h-7 w-7 rounded-full ${
                                                isCurrentDay
                                                    ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200'
                                                    : 'text-slate-700'
                                            }`}
                                        >
                                            {format(day, 'd')}
                                        </span>
                                    </div>

                                    <div className="flex-1 space-y-1.5 overflow-y-auto max-h-[160px] pe-1 styled-scrollbar">
                                        {/* Busy Times */}
                                        {dayEvents.busy_times.map((bt) => (
                                            <div key={bt.id} className="text-[10px] px-1.5 py-1 rounded bg-rose-50 text-rose-700 border border-rose-100 flex flex-col gap-0.5 shadow-sm">
                                                <div className="font-semibold flex items-center gap-1">
                                                    <AlertCircle className="h-3 w-3" />
                                                    <span className="truncate">{bt.title}</span>
                                                </div>
                                                {!bt.is_full_day && (
                                                    <div className="flex items-center gap-1 opacity-80 ps-4">
                                                        <Clock className="h-2.5 w-2.5" />
                                                        {bt.start_time} - {bt.end_time}
                                                    </div>
                                                )}
                                            </div>
                                        ))}

                                        {/* Tasks */}
                                        {dayEvents.tasks.map((task) => (
                                            <div key={`task-${task.id}`} className="text-[10px] px-1.5 py-1 rounded bg-indigo-50 text-indigo-700 border border-indigo-100 flex flex-col gap-0.5 shadow-sm">
                                                <div className="font-semibold flex items-start gap-1">
                                                    <ListTodo className="h-3 w-3 mt-0.5 shrink-0" />
                                                    <span className={`truncate ${task.completed ? 'line-through opacity-70' : ''}`}>{task.title}</span>
                                                </div>
                                                {task.client && (
                                                    <div className="flex items-center gap-1 opacity-80 ps-4">
                                                        <User className="h-2.5 w-2.5 shrink-0 text-indigo-600" />
                                                        <span className="truncate">{task.client}</span>
                                                    </div>
                                                )}
                                            </div>
                                        ))}

                                        {/* Todos */}
                                        {dayEvents.todos.map((todo) => (
                                            <div key={`todo-${todo.id}`} className="text-[10px] px-1.5 py-1 rounded bg-emerald-50 text-emerald-700 border border-emerald-100 flex flex-col gap-0.5 shadow-sm">
                                                <div className="font-semibold flex items-start gap-1">
                                                    {todo.completed ? (
                                                        <CheckCircle2 className="h-3 w-3 mt-0.5 shrink-0" />
                                                    ) : (
                                                        <Circle className="h-3 w-3 mt-0.5 shrink-0" />
                                                    )}
                                                    <span className={`truncate ${todo.completed ? 'line-through opacity-70' : ''}`}>
                                                        {todo.title}
                                                    </span>
                                                </div>
                                                <div className="flex flex-col gap-0.5 ps-4 opacity-80">
                                                    <div className="flex items-center gap-1">
                                                        <Clock className="h-2.5 w-2.5 shrink-0 text-emerald-600" />
                                                        <span>{todo.start_time}</span>
                                                    </div>
                                                    {todo.client && (
                                                        <div className="flex items-center gap-1">
                                                            <User className="h-2.5 w-2.5 shrink-0 text-emerald-600" />
                                                            <span className="truncate">{todo.client}</span>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </Card>
            </div>

            {/* Create & Bill Task Modal */}
            <Dialog open={isModalOpen} onOpenChange={setIsModalOpen}>
                <DialogContent className="sm:max-w-xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>{__('general.create_and_bill_focus_task')}</DialogTitle>
                    </DialogHeader>
                    
                    <form onSubmit={submitTask} className="space-y-4 py-4">
                        <div className="space-y-2">
                            <Label htmlFor="client_id">{__('general.client')} *</Label>
                            <ClientAutocomplete
                                value={data.client_id}
                                onChange={val => setData('client_id', val)}
                                searchEndpoint={route('admin.projects.search-clients')}
                                placeholder={__('general.select_client')}
                            />
                            {errors.client_id && <p className="text-xs text-red-500">{errors.client_id}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="title">{__('general.title')} *</Label>
                            <Input
                                id="title"
                                value={data.title}
                                onChange={e => setData('title', e.target.value)}
                                placeholder="e.g. Server Maintenance"
                                required
                            />
                            {errors.title && <p className="text-xs text-red-500">{errors.title}</p>}
                        </div>

                        <div className="grid grid-cols-3 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="date">{__('general.date')} *</Label>
                                <Input
                                    id="date"
                                    type="date"
                                    value={data.date}
                                    onChange={e => setData('date', e.target.value)}
                                    required
                                />
                                {errors.date && <p className="text-xs text-red-500">{errors.date}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="start_time">{__('general.start_time')} *</Label>
                                <Input
                                    id="start_time"
                                    type="time"
                                    value={data.start_time}
                                    onChange={e => setData('start_time', e.target.value)}
                                    required
                                />
                                {errors.start_time && <p className="text-xs text-red-500">{errors.start_time}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="end_time">{__('general.end_time')} *</Label>
                                <Input
                                    id="end_time"
                                    type="time"
                                    value={data.end_time}
                                    onChange={e => setData('end_time', e.target.value)}
                                    required
                                />
                                {errors.end_time && <p className="text-xs text-red-500">{errors.end_time}</p>}
                            </div>
                        </div>

                        <div className="space-y-2 pt-2 border-t border-slate-100">
                            <div className="flex items-center justify-between">
                                <Label>{__('general.sub_todos')}</Label>
                                <Button 
                                    type="button" 
                                    variant="outline" 
                                    size="sm" 
                                    onClick={() => setData('checklist_items', [...data.checklist_items, { title: '' }])}
                                    className="h-7 px-2 text-xs"
                                >
                                    <Plus className="h-3 w-3 me-1" /> {__('general.add_item')}</Button>
                            </div>
                            
                            <div className="space-y-2">
                                {data.checklist_items.map((item, index) => (
                                    <div key={index} className="flex items-center gap-2">
                                        <Input
                                            value={item.title}
                                            onChange={e => {
                                                const newItems = [...data.checklist_items];
                                                newItems[index].title = e.target.value;
                                                setData('checklist_items', newItems);
                                            }}
                                            placeholder={__('general.checklist_item_description')}
                                            className="h-8 text-sm"
                                            required
                                        />
                                        <Button 
                                            type="button" 
                                            variant="ghost" 
                                            size="sm" 
                                            className="h-8 w-8 p-0 text-red-500 hover:text-red-700 hover:bg-red-50"
                                            onClick={() => {
                                                const newItems = data.checklist_items.filter((_, i) => i !== index);
                                                setData('checklist_items', newItems);
                                            }}
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </div>
                                ))}
                                {data.checklist_items.length === 0 && (
                                    <p className="text-xs text-slate-400 italic">{__('general.no_checklist_items_added')}</p>
                                )}
                            </div>
                        </div>

                        <DialogFooter className="pt-4 border-t border-slate-100">
                            <Button type="button" variant="outline" onClick={() => setIsModalOpen(false)}>
                                {__('general.cancel')}
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? __('general.processing') : __('general.create_and_bill')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
            
            <style>{`
                .styled-scrollbar::-webkit-scrollbar {
                    width: 4px;
                }
                .styled-scrollbar::-webkit-scrollbar-track {
                    background: transparent;
                }
                .styled-scrollbar::-webkit-scrollbar-thumb {
                    background-color: #cbd5e1;
                    border-radius: 4px;
                }
            `}</style>
        </AdminSidebarLayout>
    );
}

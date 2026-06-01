import React, { useState } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, router } from '@inertiajs/react';
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
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
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

                    <div className="flex items-center gap-3">
                        <select
                            value={clientFilter}
                            onChange={e => handleClientChange(e.target.value)}
                            className="h-9 rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">{__('general.all_clients')}</option>
                            {clients.map(c => (
                                <option key={c.id} value={c.id}>{c.name}</option>
                            ))}
                        </select>
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
                                Today
                            </Button>
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
                                    className={`min-h-[140px] border-r border-b border-slate-100 p-2 flex flex-col transition-colors ${
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

                                    <div className="flex-1 space-y-1.5 overflow-y-auto max-h-[160px] pr-1 styled-scrollbar">
                                        {/* Busy Times */}
                                        {dayEvents.busy_times.map((bt) => (
                                            <div key={bt.id} className="text-[10px] px-1.5 py-1 rounded bg-rose-50 text-rose-700 border border-rose-100 flex flex-col gap-0.5 shadow-sm">
                                                <div className="font-semibold flex items-center gap-1">
                                                    <AlertCircle className="h-3 w-3" />
                                                    <span className="truncate">{bt.title}</span>
                                                </div>
                                                {!bt.is_full_day && (
                                                    <div className="flex items-center gap-1 opacity-80 pl-4">
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
                                                    <div className="flex items-center gap-1 opacity-80 pl-4">
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
                                                <div className="flex flex-col gap-0.5 pl-4 opacity-80">
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

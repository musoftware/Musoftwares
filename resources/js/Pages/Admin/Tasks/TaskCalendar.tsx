import React, { useState, useEffect, useMemo, useCallback } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
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
    X,
    Info,
    Briefcase,
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { ClientAutocomplete } from '@/Components/ClientAutocomplete';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { format, startOfMonth, endOfMonth, eachDayOfInterval, isSameMonth, isToday, addMonths, subMonths, startOfWeek, endOfWeek, parseISO } from 'date-fns';
import { __ } from '@/lib/i18n';

interface TaskEvent {
    id: number;
    title: string;
    priority: string;
    completed: boolean;
    client_id: number | null;
    client: string | null;
}

interface TodoEvent {
    id: number;
    title: string;
    priority: string;
    priority_color: string | null;
    completed: boolean;
    task_id: number | null;
    client_id: number | null;
    client: string | null;
    start_time: string;
    end_time: string | null;
}

interface BusyTimeEvent {
    id: string;
    title: string;
    is_full_day: boolean;
    start_time: string | null;
    end_time: string | null;
}

interface DayEvents {
    tasks: TaskEvent[];
    todos: TodoEvent[];
    busy_times: BusyTimeEvent[];
}

interface EventsMap {
    [date: string]: DayEvents;
}

interface DropdownClient {
    id: number;
    name: string;
}

interface CalendarStats {
    todos_this_month: number;
    tasks_this_month: number;
    busy_days: number;
}

interface Props {
    events: EventsMap;
    year: number;
    month: number;
    tz: string;
    clients: DropdownClient[];
    stats: CalendarStats;
    filters: { client_id?: string | null; event_type?: string };
}

const ALL = '__all__';
const MAX_VISIBLE_PER_DAY = 3;

const WEEKDAYS = [
    'general.cal_mon',
    'general.cal_tue',
    'general.cal_wed',
    'general.cal_thu',
    'general.cal_fri',
    'general.cal_sat',
    'general.cal_sun',
];

const EVENT_TYPE_OPTIONS = [
    { value: 'all',   labelKey: 'general.all_events' },
    { value: 'tasks', labelKey: 'general.tasks_only' },
    { value: 'todos', labelKey: 'general.todos_only' },
    { value: 'busy',  labelKey: 'general.busy_only' },
];

export default function TaskCalendar({ events, year, month, tz, clients, stats, filters }: Props) {
    const initialClientId = filters.client_id ? String(filters.client_id) : '';
    const [clientFilter, setClient] = useState(initialClientId);
    const [eventType, setEventType] = useState(filters.event_type || 'all');
    const [selectedDay, setSelectedDay] = useState<string | null>(null);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [createDate, setCreateDate] = useState<string | null>(null);
    const currentDate = new Date(year, month - 1, 1);

    const pageProps = usePage().props as any;
    const flashSuccess = pageProps?.flash?.success ?? null;
    const flashError = pageProps?.flash?.error ?? null;
    const validationErrors: Record<string, string> = pageProps?.errors ?? {};

    const { data, setData, post, processing, errors, reset } = useForm({
        client_id: '',
        title: '',
        date: '',
        start_time: '',
        end_time: '',
        checklist_items: [] as { title: string }[],
    });

    useEffect(() => {
        const onKey = (e: KeyboardEvent) => {
            const target = e.target as HTMLElement | null;
            const tag = target?.tagName?.toLowerCase();
            if (tag === 'input' || tag === 'textarea' || target?.isContentEditable) return;
            if (e.metaKey || e.ctrlKey || e.altKey) return;
            if (e.key === 'ArrowLeft')  { e.preventDefault(); handlePrevMonth(); }
            if (e.key === 'ArrowRight') { e.preventDefault(); handleNextMonth(); }
            if (e.key === 't' || e.key === 'T') { e.preventDefault(); handleGoToToday(); }
            if (e.key === 'c' || e.key === 'C') { e.preventDefault(); openCreateForToday(); }
            if (e.key === 'Escape')     { setSelectedDay(null); }
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    });

    const openCreateForToday = () => {
        const today = format(new Date(), 'yyyy-MM-dd');
        setCreateDate(today);
        reset();
        setData({
            client_id: clientFilter || '',
            title: '',
            date: today,
            start_time: '09:00',
            end_time: '10:00',
            checklist_items: [],
        });
        setIsModalOpen(true);
    };

    const handleDayClick = (day: Date) => {
        setSelectedDay(format(day, 'yyyy-MM-dd'));
    };

    const handleDayCreate = (day: Date) => {
        const ds = format(day, 'yyyy-MM-dd');
        setCreateDate(ds);
        reset();
        setData({
            client_id: clientFilter || '',
            title: '',
            date: ds,
            start_time: '09:00',
            end_time: '10:00',
            checklist_items: [],
        });
        setIsModalOpen(true);
    };

    const submitTask = (e: React.FormEvent) => {
        e.preventDefault();
        if (data.end_time <= data.start_time) {
            // client-side guard, server still validates
        }
        post(route('admin.tasks.calendar.store-and-bill'), {
            onSuccess: () => {
                setIsModalOpen(false);
                reset();
            },
        });
    };

    const handleClientChange = (val: string) => {
        setClient(val);
        router.get(route('admin.tasks.calendar'), {
            month, year,
            client_id: val || undefined,
            event_type: eventType,
        }, { preserveState: true, replace: true });
    };

    const handleEventTypeChange = (val: string | null) => {
        if (val === null) return;
        setEventType(val);
        router.get(route('admin.tasks.calendar'), {
            month, year,
            client_id: clientFilter || undefined,
            event_type: val === 'all' ? undefined : val,
        }, { preserveState: true, replace: true });
    };

    const handlePrevMonth = () => {
        const prev = subMonths(currentDate, 1);
        router.get(route('admin.tasks.calendar'), {
            year: prev.getFullYear(), month: prev.getMonth() + 1,
            client_id: clientFilter || undefined,
            event_type: eventType === 'all' ? undefined : eventType,
        }, { preserveState: true });
    };

    const handleNextMonth = () => {
        const next = addMonths(currentDate, 1);
        router.get(route('admin.tasks.calendar'), {
            year: next.getFullYear(), month: next.getMonth() + 1,
            client_id: clientFilter || undefined,
            event_type: eventType === 'all' ? undefined : eventType,
        }, { preserveState: true });
    };

    const handleGoToToday = () => {
        const today = new Date();
        router.get(route('admin.tasks.calendar'), {
            year: today.getFullYear(), month: today.getMonth() + 1,
            client_id: clientFilter || undefined,
            event_type: eventType === 'all' ? undefined : eventType,
        }, { preserveState: true });
    };

    const startDate = startOfWeek(startOfMonth(currentDate), { weekStartsOn: 1 });
    const endDate   = endOfWeek(endOfMonth(currentDate),   { weekStartsOn: 1 });
    const calendarDays = eachDayOfInterval({ start: startDate, end: endDate });

    const isCurrentMonth = isSameMonth(currentDate, new Date());
    const todayStr = format(new Date(), 'yyyy-MM-dd');

    const selectedDayEvents: DayEvents | null = selectedDay
        ? (events[selectedDay] ?? { tasks: [], todos: [], busy_times: [] })
        : null;

    return (
        <AdminSidebarLayout title={__('general.task_calendar')} header={__('general.task_calendar')}>
            <Head title={__('general.task_calendar_admin')} />

            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                            <CalendarIcon className="h-6 w-6 text-slate-900" />
                            {__('general.task_calendar')}
                        </h1>
                        <p className="text-sm text-slate-500 mt-1">
                            {__('general.detailed_overview_of_all_tasks_todos_and_busy_times_by_date_across_platform_clients')}
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <div className="w-[220px]">
                            <ClientAutocomplete
                                value={clientFilter}
                                onChange={handleClientChange}
                                searchEndpoint={route('admin.projects.search-clients')}
                                placeholder={__('general.all_clients')}
                            />
                        </div>
                        <div className="w-[150px]">
                            <Select value={eventType} onValueChange={handleEventTypeChange}>
                                <SelectTrigger className="h-9 text-xs">
                                    <SelectValue placeholder={__('general.event_type_filter')} />
                                </SelectTrigger>
                                <SelectContent>
                                    {EVENT_TYPE_OPTIONS.map((o) => (
                                        <SelectItem key={o.value} value={o.value}>{__(o.labelKey)}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <Button onClick={openCreateForToday} size="sm" className="h-9 text-xs">
                            <Plus className="h-3.5 w-3.5 me-1" />
                            {__('general.create_event')}
                        </Button>
                    </div>
                </div>

                {(flashSuccess || flashError || Object.keys(validationErrors).length > 0) && (
                    <div className={`rounded-md border px-4 py-2 text-xs ${
                        flashError || Object.keys(validationErrors).length > 0
                            ? 'border-red-200 bg-red-50 text-red-700'
                            : 'border-green-200 bg-green-50 text-green-700'
                    }`}>
                        {flashSuccess || flashError || Object.values(validationErrors)[0]}
                    </div>
                )}

                <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    {[
                        { label: __('general.todos_this_month'), value: stats.todos_this_month, color: 'text-green-700', bg: 'bg-green-50', icon: ListTodo },
                        { label: __('general.tasks_this_month'), value: stats.tasks_this_month, color: 'text-blue-700',  bg: 'bg-blue-50',  icon: Briefcase },
                        { label: __('general.busy_days'),        value: stats.busy_days,         color: 'text-red-700',   bg: 'bg-red-50',   icon: AlertCircle },
                    ].map(({ label, value, color, bg, icon: Icon }) => (
                        <Card key={label} className="rounded-xl border border-slate-200 bg-white shadow-sm">
                            <CardContent className="p-4 flex items-center justify-between">
                                <div className="space-y-1">
                                    <span className="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">{label}</span>
                                    <h3 className={`text-xl font-bold ${color}`}>{value}</h3>
                                </div>
                                <div className={`p-2 ${bg} ${color} rounded-lg`}>
                                    <Icon className="h-4 w-4" />
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Card className="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div className="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between gap-2 flex-wrap">
                        <div className="flex items-center gap-2">
                            <Button variant="outline" size="sm" onClick={handlePrevMonth} aria-label={__('general.prev_month')}>
                                <ChevronLeft className="h-4 w-4" />
                            </Button>
                            <Button variant="outline" size="sm" onClick={handleGoToToday} aria-label={__('general.jump_to_today')}>
                                {__('general.today')}
                            </Button>
                            <Button variant="outline" size="sm" onClick={handleNextMonth} aria-label={__('general.next_month')}>
                                <ChevronRight className="h-4 w-4" />
                            </Button>
                            <span className="text-[10px] text-slate-400 hidden md:inline">
                                ← → T C
                            </span>
                        </div>
                        <h2 className="text-lg font-bold text-slate-800 flex items-center gap-2">
                            {format(currentDate, 'MMMM yyyy')}
                            {!isCurrentMonth && (
                                <span className="text-[10px] font-semibold uppercase tracking-wider text-amber-700 bg-amber-50 border border-amber-200 rounded px-1.5 py-0.5">
                                    {__('general.today')}
                                </span>
                            )}
                        </h2>
                        <div className="text-xs text-slate-400 flex items-center gap-1">
                            <Info className="h-3.5 w-3.5" />
                            {tz}
                        </div>
                    </div>

                    <Legend />

                    {/* Desktop month grid */}
                    <div className="hidden md:block">
                        <div className="grid grid-cols-7 border-b border-slate-200 bg-slate-100">
                            {WEEKDAYS.map((wk) => (
                                <div key={wk} className="py-2 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    {__(wk)}
                                </div>
                            ))}
                        </div>

                        <div className="grid grid-cols-7 bg-white">
                            {calendarDays.map((day) => {
                                const dateStr = format(day, 'yyyy-MM-dd');
                                const dayEvents = events[dateStr] ?? { tasks: [], todos: [], busy_times: [] };
                                const visible = collectVisible(dayEvents, eventType, MAX_VISIBLE_PER_DAY);
                                const hiddenCount = totalCount(dayEvents, eventType) - visible.length;
                                const inMonth = isSameMonth(day, currentDate);
                                const isTodayCell = isToday(day);
                                const isSelected = dateStr === selectedDay;

                                return (
                                    <DayCell
                                        key={dateStr}
                                        day={day}
                                        inMonth={inMonth}
                                        isToday={isTodayCell}
                                        isSelected={isSelected}
                                        visible={visible}
                                        hiddenCount={hiddenCount}
                                        totalCount={totalCount(dayEvents, eventType)}
                                        onSelect={() => handleDayClick(day)}
                                        onCreate={() => handleDayCreate(day)}
                                    />
                                );
                            })}
                        </div>
                    </div>

                    {/* Mobile agenda list */}
                    <MobileAgenda
                        days={calendarDays.filter((d) => isSameMonth(d, currentDate))}
                        events={events}
                        eventType={eventType}
                        onSelectDay={handleDayClick}
                    />
                </Card>
            </div>

            <DayDetailDrawer
                date={selectedDay}
                events={selectedDayEvents}
                onClose={() => setSelectedDay(null)}
                onCreate={() => {
                    if (selectedDay) {
                        setCreateDate(selectedDay);
                        reset();
                        setData({
                            client_id: clientFilter || '',
                            title: '',
                            date: selectedDay,
                            start_time: '09:00',
                            end_time: '10:00',
                            checklist_items: [],
                        });
                        setIsModalOpen(true);
                    }
                }}
            />

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
                            {(errors.client_id) && <p className="text-xs text-red-500">{errors.client_id}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="title">{__('general.title')} *</Label>
                            <Input
                                id="title"
                                value={data.title}
                                onChange={e => setData('title', e.target.value)}
                                placeholder={__('general.task_title_placeholder')}
                                required
                            />
                            {errors.title && <p className="text-xs text-red-500">{errors.title}</p>}
                        </div>

                        <div className="grid grid-cols-3 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="date">{__('general.date')} *</Label>
                                <Input id="date" type="date" value={data.date} onChange={e => setData('date', e.target.value)} required />
                                {errors.date && <p className="text-xs text-red-500">{errors.date}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="start_time">{__('general.start_time')} *</Label>
                                <Input id="start_time" type="time" value={data.start_time} onChange={e => setData('start_time', e.target.value)} required />
                                {errors.start_time && <p className="text-xs text-red-500">{errors.start_time}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="end_time">{__('general.end_time')} *</Label>
                                <Input id="end_time" type="time" value={data.end_time} onChange={e => setData('end_time', e.target.value)} required />
                                {errors.end_time && <p className="text-xs text-red-500">{errors.end_time}</p>}
                            </div>
                        </div>

                        {data.start_time && data.end_time && data.end_time <= data.start_time && (
                            <p className="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded px-2 py-1">
                                {__('general.overlap_detected')}
                            </p>
                        )}

                        <div className="space-y-2 pt-2 border-t border-slate-100">
                            <div className="flex items-center justify-end gap-4">
                                <Label>{__('general.sub_todos')}</Label>
                                <Button type="button" variant="outline" size="sm"
                                    onClick={() => setData('checklist_items', [...data.checklist_items, { title: '' }])}
                                    className="h-7 px-2 text-xs">
                                    <Plus className="h-3 w-3 me-1" /> {__('general.add_item')}
                                </Button>
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
                                        />
                                        <Button type="button" variant="ghost" size="sm"
                                            className="h-8 w-8 p-0 text-red-500 hover:text-red-700 hover:bg-red-50"
                                            onClick={() => setData('checklist_items', data.checklist_items.filter((_, i) => i !== index))}>
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
                .styled-scrollbar::-webkit-scrollbar { width: 4px; }
                .styled-scrollbar::-webkit-scrollbar-track { background: transparent; }
                .styled-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
                @media print {
                    .no-print { display: none !important; }
                }
            `}</style>
        </AdminSidebarLayout>
    );
}

function totalCount(d: DayEvents, eventType: string): number {
    if (eventType === 'tasks') return d.tasks.length;
    if (eventType === 'todos') return d.todos.length;
    if (eventType === 'busy')  return d.busy_times.length;
    return d.tasks.length + d.todos.length + d.busy_times.length;
}

interface VisibleItem {
    kind: 'task' | 'todo' | 'busy';
    title: string;
    color: string;
    icon: React.ComponentType<{ className?: string }>;
    meta?: string;
    payload: TaskEvent | TodoEvent | BusyTimeEvent;
}

function collectVisible(d: DayEvents, eventType: string, limit: number): VisibleItem[] {
    const items: VisibleItem[] = [];
    if (eventType === 'all' || eventType === 'busy') {
        for (const bt of d.busy_times) items.push({
            kind: 'busy', title: bt.title, color: 'bg-red-50 text-slate-900 border border-red-100',
            icon: AlertCircle, payload: bt,
        });
    }
    if (eventType === 'all' || eventType === 'tasks') {
        for (const t of d.tasks) items.push({
            kind: 'task', title: t.title, color: 'bg-slate-50 text-slate-900 border border-slate-200',
            icon: Briefcase, meta: t.client ?? undefined, payload: t,
        });
    }
    if (eventType === 'all' || eventType === 'todos') {
        for (const td of d.todos) items.push({
            kind: 'todo', title: td.title,
            color: td.completed ? 'bg-green-50/60 text-slate-500 border border-green-100' : 'bg-green-50 text-slate-900 border border-green-100',
            icon: td.completed ? CheckCircle2 : Circle,
            meta: `${td.start_time}${td.client ? ' · ' + td.client : ''}`,
            payload: td,
        });
    }
    return items.slice(0, limit);
}

function DayCell({
    day, inMonth, isToday, isSelected, visible, hiddenCount, totalCount, onSelect, onCreate,
}: {
    day: Date;
    inMonth: boolean;
    isToday: boolean;
    isSelected: boolean;
    visible: VisibleItem[];
    hiddenCount: number;
    totalCount: number;
    onSelect: () => void;
    onCreate: () => void;
}) {
    const dayStr = format(day, 'yyyy-MM-dd');
    return (
        <div
            role="button"
            tabIndex={0}
            onClick={onSelect}
            onKeyDown={(e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    onSelect();
                }
            }}
            onDoubleClick={onCreate}
            aria-label={__('general.day_detail') + ' ' + dayStr}
            className={`group min-h-[140px] border-e border-b border-slate-100 p-2 flex flex-col transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-slate-900 ${
                !inMonth ? 'bg-slate-50/50 opacity-60' : ''
            } ${isSelected ? 'ring-2 ring-slate-900' : ''} ${isToday ? 'bg-amber-50/30' : 'hover:bg-slate-50/30'}`}
        >
            <div className="flex justify-between items-start mb-2">
                <span
                    className={`text-sm font-semibold flex items-center justify-center h-7 w-7 rounded-full ${
                        isToday
                            ? 'bg-slate-900 text-white shadow-md shadow-slate-900/20'
                            : 'text-slate-700'
                    }`}
                >
                    {format(day, 'd')}
                </span>
                {totalCount > 0 && (
                    <span className="text-[10px] font-semibold text-slate-500 bg-slate-100 rounded-full px-1.5 py-0.5">
                        {totalCount}
                    </span>
                )}
            </div>

            <div className="flex-1 space-y-1 overflow-hidden styled-scrollbar">
                {visible.map((it, i) => {
                    const Icon = it.icon;
                    return (
                        <div
                            key={i}
                            className={`text-[10px] px-1.5 py-1 rounded ${it.color} flex flex-col gap-0.5 shadow-sm`}
                            title={it.title}
                        >
                            <div className="font-semibold flex items-start gap-1">
                                <Icon className="h-3 w-3 mt-0.5 shrink-0" />
                                <span className="truncate">{it.title}</span>
                            </div>
                            {it.meta && <div className="opacity-80 ps-4 truncate">{it.meta}</div>}
                        </div>
                    );
                })}
                {hiddenCount > 0 && (
                    <button
                        type="button"
                        onClick={(e) => { e.stopPropagation(); onSelect(); }}
                        className="text-[10px] text-slate-500 hover:text-slate-900 underline w-full text-center"
                    >
                        +{hiddenCount} {__('general.more')}
                    </button>
                )}
            </div>
        </div>
    );
}

function Legend() {
    return (
        <div className="px-4 py-2 border-b border-slate-100 bg-white flex flex-wrap items-center gap-3 text-[10px] text-slate-500">
            <span className="font-semibold uppercase tracking-wider">{__('general.legend')}:</span>
            <span className="flex items-center gap-1"><span className="w-3 h-3 rounded bg-green-100 border border-green-200" />{__('general.todos') ?? 'Todos'}</span>
            <span className="flex items-center gap-1"><span className="w-3 h-3 rounded bg-slate-100 border border-slate-200" />{__('general.tasks') ?? 'Tasks'}</span>
            <span className="flex items-center gap-1"><span className="w-3 h-3 rounded bg-red-100 border border-red-200" />{__('general.busy')}</span>
        </div>
    );
}

function MobileAgenda({
    days, events, eventType, onSelectDay,
}: {
    days: Date[];
    events: EventsMap;
    eventType: string;
    onSelectDay: (d: Date) => void;
}) {
    return (
        <div className="md:hidden divide-y divide-slate-100">
            {days.map((d) => {
                const ds = format(d, 'yyyy-MM-dd');
                const dayEvents = events[ds] ?? { tasks: [], todos: [], busy_times: [] };
                const total = totalCount(dayEvents, eventType);
                if (total === 0) return null;
                const visible = collectVisible(dayEvents, eventType, 50);
                return (
                    <button
                        key={ds}
                        onClick={() => onSelectDay(d)}
                        className="w-full text-left p-3 hover:bg-slate-50/60 focus:outline-none focus:bg-slate-50"
                    >
                        <div className="flex items-center justify-between mb-1.5">
                            <span className="text-sm font-bold text-slate-800">
                                {format(d, 'EEE, MMM d')}
                            </span>
                            <span className="text-[10px] font-semibold text-slate-500 bg-slate-100 rounded-full px-1.5 py-0.5">
                                {total}
                            </span>
                        </div>
                        <div className="space-y-1">
                            {visible.map((it, i) => {
                                const Icon = it.icon;
                                return (
                                    <div key={i} className={`text-xs px-2 py-1 rounded ${it.color} flex items-center gap-1`}>
                                        <Icon className="h-3 w-3 shrink-0" />
                                        <span className="truncate">{it.title}</span>
                                        {it.meta && <span className="text-[10px] opacity-70 ms-1">{it.meta}</span>}
                                    </div>
                                );
                            })}
                        </div>
                    </button>
                );
            })}
        </div>
    );
}

function DayDetailDrawer({
    date, events, onClose, onCreate,
}: {
    date: string | null;
    events: DayEvents | null;
    onClose: () => void;
    onCreate: () => void;
}) {
    if (!date || !events) return null;
    const total = events.tasks.length + events.todos.length + events.busy_times.length;
    const dateObj = parseISO(date);

    return (
        <Dialog open={!!date} onOpenChange={(o) => !o && onClose()}>
            <DialogContent className="sm:max-w-lg max-h-[85vh] overflow-y-auto">
                <DialogHeader>
                    <div className="flex items-center justify-between gap-2">
                        <DialogTitle>
                            {format(dateObj, 'EEEE, MMMM d, yyyy')}
                        </DialogTitle>
                        <Button variant="ghost" size="sm" onClick={onClose} className="h-7 w-7 p-0">
                            <X className="h-4 w-4" />
                        </Button>
                    </div>
                </DialogHeader>

                <div className="space-y-4 py-2">
                    {total === 0 ? (
                        <p className="text-sm text-slate-500 text-center py-6">
                            {__('general.no_more_events')}
                        </p>
                    ) : (
                        <>
                            {events.busy_times.length > 0 && (
                                <Section title={__('general.busy')}>
                                    {events.busy_times.map((bt) => (
                                        <EventRow key={bt.id} kind="busy" title={bt.title} meta={!bt.is_full_day ? `${bt.start_time} – ${bt.end_time}` : null} />
                                    ))}
                                </Section>
                            )}
                            {events.tasks.length > 0 && (
                                <Section title={__('general.tasks') ?? 'Tasks'}>
                                    {events.tasks.map((t) => (
                                        <EventRow key={`task-${t.id}`} kind="task" title={t.title} meta={t.client ?? null} link={t.client_id ? route('admin.tasks.client-tasks', { client_id: t.client_id }) : null} completed={t.completed} />
                                    ))}
                                </Section>
                            )}
                            {events.todos.length > 0 && (
                                <Section title={__('general.todos') ?? 'Todos'}>
                                    {events.todos.map((td) => (
                                        <EventRow key={`todo-${td.id}`} kind="todo" title={td.title} meta={`${td.start_time}${td.end_time ? ' – ' + td.end_time : ''}${td.client ? ' · ' + td.client : ''}`} completed={td.completed} />
                                    ))}
                                </Section>
                            )}
                        </>
                    )}
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={onClose}>{__('general.cancel')}</Button>
                    <Button onClick={onCreate}>
                        <Plus className="h-3.5 w-3.5 me-1" />
                        {__('general.create_event')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function Section({ title, children }: { title: string; children: React.ReactNode }) {
    return (
        <div>
            <h4 className="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">{title}</h4>
            <div className="space-y-1.5">{children}</div>
        </div>
    );
}

function EventRow({
    kind, title, meta, link, completed,
}: {
    kind: 'task' | 'todo' | 'busy';
    title: string;
    meta: string | null;
    link?: string | null;
    completed?: boolean;
}) {
    const cls = {
        task: 'bg-slate-50 border-slate-200',
        todo: completed ? 'bg-green-50/40 border-green-100' : 'bg-green-50 border-green-100',
        busy: 'bg-red-50 border-red-100',
    }[kind];
    const Icon = kind === 'busy' ? AlertCircle : kind === 'task' ? Briefcase : (completed ? CheckCircle2 : Circle);

    const content = (
        <div className={`flex items-start gap-2 px-3 py-2 rounded border ${cls} ${link ? 'hover:bg-slate-50 cursor-pointer' : ''}`}>
            <Icon className="h-4 w-4 mt-0.5 shrink-0" />
            <div className="flex-1 min-w-0">
                <div className={`text-sm font-medium ${completed ? 'line-through text-slate-500' : 'text-slate-800'}`}>
                    {title}
                </div>
                {meta && <div className="text-[11px] text-slate-500 mt-0.5">{meta}</div>}
            </div>
        </div>
    );

    return link ? <a href={link}>{content}</a> : content;
}

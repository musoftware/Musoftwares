import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Card } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Calendar as CalendarIcon, ChevronLeft, ChevronRight, Circle } from 'lucide-react';
import { format, startOfMonth, endOfMonth, eachDayOfInterval, isSameMonth, isToday, addMonths, subMonths, startOfWeek, endOfWeek } from 'date-fns';
import { __ } from '@/lib/i18n';

interface CalendarEvent {
    id: string;
    title: string;
    start: string;
    end: string;
    url?: string;
    backgroundColor: string;
    borderColor: string;
}

interface Props {
    events: CalendarEvent[];
}

export default function CalendarIndex({ events }: Props) {
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('calendar');
    const [currentDate, setCurrentDate] = useState(new Date());

    const handlePrevMonth = () => setCurrentDate(subMonths(currentDate, 1));
    const handleNextMonth = () => setCurrentDate(addMonths(currentDate, 1));
    const handleGoToToday = () => setCurrentDate(new Date());

    const startDate = startOfWeek(startOfMonth(currentDate), { weekStartsOn: 1 });
    const endDate = endOfWeek(endOfMonth(currentDate), { weekStartsOn: 1 });
    const calendarDays = eachDayOfInterval({ start: startDate, end: endDate });

    // Group events by date string (YYYY-MM-DD)
    const eventsByDate = events.reduce((acc, event) => {
        if (!acc[event.start]) acc[event.start] = [];
        acc[event.start].push(event);
        return acc;
    }, {} as Record<string, CalendarEvent[]>);

    return (
        <ERPLayout title={__('general.workspace_calendar')} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>
            <Head title={__('general.workspace_calendar')} />
            
            <div className="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                            <CalendarIcon className="h-6 w-6 text-indigo-600" />
                            {__('general.workspace_calendar')}
                        </h1>
                        <p className="text-sm text-slate-500 mt-1">
                            {__('general.view_all_deadlines_tasks_and_invoices_in_one_place')}
                        </p>
                    </div>
                </div>

                <Card className="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    {/* Calendar Header */}
                    <div className="p-4 border-b border-slate-200 bg-slate-50 flex items-center justify-end gap-4">
                        <div className="flex items-center gap-2">
                            <Button variant="outline" size="sm" onClick={handlePrevMonth}>
                                <ChevronLeft className="h-4 w-4" />
                            </Button>
                            <Button variant="outline" size="sm" onClick={handleGoToToday}>
                                {__('general.today')}
                            </Button>
                            <Button variant="outline" size="sm" onClick={handleNextMonth}>
                                <ChevronRight className="h-4 w-4" />
                            </Button>
                        </div>
                        <h2 className="text-lg font-bold text-slate-800">
                            {format(currentDate, 'MMMM yyyy')}
                        </h2>
                    </div>

                    {/* Calendar Grid Header */}
                    <div className="grid grid-cols-7 border-b border-slate-200 bg-slate-100">
                        {['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].map(day => (
                            <div key={day} className="py-2 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {__(day)}
                            </div>
                        ))}
                    </div>

                    {/* Calendar Grid */}
                    <div className="grid grid-cols-7 bg-white">
                        {calendarDays.map((day, idx) => {
                            const dateStr = format(day, 'yyyy-MM-dd');
                            const dayEvents = eventsByDate[dateStr] || [];
                            const isCurrentMonth = isSameMonth(day, currentDate);
                            const isCurrentDay = isToday(day);

                            return (
                                <div
                                    key={idx}
                                    className={`min-h-[140px] border-e border-b border-slate-100 p-2 flex flex-col transition-colors ${
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
                                        {dayEvents.map((evt) => (
                                            <a 
                                                key={evt.id} 
                                                href={evt.url || '#'}
                                                className="text-[10px] px-1.5 py-1 rounded flex items-start gap-1 shadow-sm transition-opacity hover:opacity-80"
                                                style={{ backgroundColor: evt.backgroundColor, color: '#fff', borderColor: evt.borderColor }}
                                            >
                                                <Circle className="h-3 w-3 mt-0.5 shrink-0" fill="currentColor" />
                                                <span className="truncate font-medium">{evt.title}</span>
                                            </a>
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
        </ERPLayout>
    );
}

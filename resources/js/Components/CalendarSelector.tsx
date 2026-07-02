import React, { useState } from 'react';
import { ChevronLeft, ChevronRight, Calendar as CalendarIcon } from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import {
    format,
    addMonths,
    subMonths,
    startOfMonth,
    endOfMonth,
    eachDayOfInterval,
    getDay,
    isSameDay,
    parseISO,
} from 'date-fns';

interface CalendarSelectorProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    activeDates: string[]; // ['YYYY-MM-DD', ...]
    selectedDate: string;  // 'YYYY-MM-DD'
    onSelectDate: (date: string) => void;
}

export default function CalendarSelector({
    open,
    onOpenChange,
    activeDates = [],
    selectedDate,
    onSelectDate,
}: CalendarSelectorProps) {
    const [currentMonth, setCurrentMonth] = useState<Date>(
        selectedDate ? parseISO(selectedDate) : new Date()
    );

    const handlePrevMonth = () => setCurrentMonth(subMonths(currentMonth, 1));
    const handleNextMonth = () => setCurrentMonth(addMonths(currentMonth, 1));

    const startOfCurrentMonth = startOfMonth(currentMonth);
    const endOfCurrentMonth = endOfMonth(currentMonth);

    // Days in current month
    const days = eachDayOfInterval({
        start: startOfCurrentMonth,
        end: endOfCurrentMonth,
    });

    // Start day offset (0: Sunday, 1: Monday, ...)
    const startDayOffset = getDay(startOfCurrentMonth);

    const weekDays = [
        __('general.cal_sun') || 'Su',
        __('general.cal_mon') || 'Mo',
        __('general.cal_tue') || 'Tu',
        __('general.cal_wed') || 'We',
        __('general.cal_thu') || 'Th',
        __('general.cal_fri') || 'Fr',
        __('general.cal_sat') || 'Sa',
    ];

    const handleDayClick = (date: Date) => {
        const formatted = format(date, 'yyyy-MM-dd');
        onSelectDate(formatted);
        onOpenChange(false);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md bg-white rounded-3xl p-5 shadow-2xl">
                <DialogHeader className="pb-2 border-b border-slate-100 flex flex-row items-center justify-between">
                    <DialogTitle className="text-sm font-black uppercase tracking-wider text-slate-500 flex items-center gap-2">
                        <CalendarIcon className="h-4.5 w-4.5 text-indigo-500" />
                        {__('general.board_calendar') || 'Board Calendar'}
                    </DialogTitle>
                </DialogHeader>

                <div className="py-4 space-y-4">
                    {/* Month / Year header navigation */}
                    <div className="flex items-center justify-between">
                        <h3 className="text-sm font-extrabold text-slate-800">
                            {format(currentMonth, 'MMMM yyyy')}
                        </h3>
                        <div className="flex items-center gap-1">
                            <button
                                onClick={handlePrevMonth}
                                className="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 transition-colors"
                            >
                                <ChevronLeft className="h-4 w-4" />
                            </button>
                            <button
                                onClick={handleNextMonth}
                                className="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 transition-colors"
                            >
                                <ChevronRight className="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    {/* Calendar grid */}
                    <div className="grid grid-cols-7 gap-1.5 text-center text-xs">
                        {/* Weekday titles */}
                        {weekDays.map((wd, i) => (
                            <div key={i} className="font-bold text-slate-400 py-1">
                                {wd}
                            </div>
                        ))}

                        {/* Month offset slots */}
                        {Array.from({ length: startDayOffset }).map((_, i) => (
                            <div key={`offset-${i}`} className="py-2" />
                        ))}

                        {/* Month day slots */}
                        {days.map((dateObj) => {
                            const dateStr = format(dateObj, 'yyyy-MM-dd');
                            const isSelected = dateStr === selectedDate;
                            const isToday = isSameDay(dateObj, new Date());
                            const hasWork = activeDates.includes(dateStr);

                            return (
                                <button
                                    key={dateStr}
                                    type="button"
                                    onClick={() => handleDayClick(dateObj)}
                                    className={cn(
                                        'group relative flex flex-col items-center justify-center py-2.5 rounded-xl transition-all duration-200 select-none active:scale-95',
                                        isSelected 
                                            ? 'bg-slate-900 text-white font-extrabold shadow shadow-slate-900/30' 
                                            : 'hover:bg-slate-100/80 text-slate-700 font-semibold',
                                        isToday && !isSelected && 'ring-2 ring-indigo-500/30 text-indigo-700 font-bold'
                                    )}
                                >
                                    <span>{format(dateObj, 'd')}</span>
                                    
                                    {/* Work dot indicator */}
                                    {hasWork && (
                                        <span className={cn(
                                            'absolute bottom-1 h-1 w-1 rounded-full',
                                            isSelected ? 'bg-white' : 'bg-emerald-500 group-hover:scale-125'
                                        )} />
                                    )}
                                </button>
                            );
                        })}
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}

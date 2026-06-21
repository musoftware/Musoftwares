import React, { useState } from 'react';
import { format, startOfMonth, endOfMonth, startOfWeek, endOfWeek, eachDayOfInterval, isSameMonth, isSameDay, addMonths, subMonths } from 'date-fns';
import { ChevronLeft, ChevronRight, Clock } from 'lucide-react';
import { Button } from '@/Components/ui/button';

export function SimpleCalendar({ bookings, onDateClick, onBookingClick }: any) {
    const [currentDate, setCurrentDate] = useState(new Date());

    const startDate = startOfWeek(startOfMonth(currentDate));
    const endDate = endOfWeek(endOfMonth(currentDate));

    const days = eachDayOfInterval({ start: startDate, end: endDate });

    const getBookingsForDay = (date: Date) => {
        return bookings.filter((b: any) => isSameDay(new Date(b.starts_at), date));
    };

    return (
        <div className="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
            {/* Calendar Header */}
            <div className="flex items-center justify-between p-4 border-b border-slate-100">
                <h2 className="text-lg font-semibold text-slate-800">
                    {format(currentDate, 'MMMM yyyy')}
                </h2>
                <div className="flex gap-2">
                    <Button variant="outline" size="sm" onClick={() => setCurrentDate(subMonths(currentDate, 1))}>
                        <ChevronLeft className="w-4 h-4" />
                    </Button>
                    <Button variant="outline" size="sm" onClick={() => setCurrentDate(new Date())}>
                        Today
                    </Button>
                    <Button variant="outline" size="sm" onClick={() => setCurrentDate(addMonths(currentDate, 1))}>
                        <ChevronRight className="w-4 h-4" />
                    </Button>
                </div>
            </div>

            {/* Days of Week Header */}
            <div className="grid grid-cols-7 bg-slate-50 border-b border-slate-100">
                {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map(day => (
                    <div key={day} className="py-2 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        {day}
                    </div>
                ))}
            </div>

            {/* Calendar Grid */}
            <div className="grid grid-cols-7 bg-slate-100 gap-px">
                {days.map((day, idx) => {
                    const dayBookings = getBookingsForDay(day);
                    const isCurrentMonth = isSameMonth(day, currentDate);
                    const isToday = isSameDay(day, new Date());

                    return (
                        <div 
                            key={idx} 
                            onClick={() => onDateClick?.(day)}
                            className={`min-h-32 bg-white p-2 flex flex-col transition-colors cursor-pointer hover:bg-slate-50 ${!isCurrentMonth ? 'opacity-50' : ''}`}
                        >
                            <div className="flex justify-between items-start mb-2">
                                <span className={`w-7 h-7 flex items-center justify-center rounded-full text-sm font-medium ${isToday ? 'bg-indigo-600 text-white' : 'text-slate-700'}`}>
                                    {format(day, 'd')}
                                </span>
                                {dayBookings.length > 0 && (
                                    <span className="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-medium">
                                        {dayBookings.length} {dayBookings.length === 1 ? 'Booking' : 'Bookings'}
                                    </span>
                                )}
                            </div>

                            <div className="flex-1 space-y-1 overflow-y-auto max-h-24 no-scrollbar">
                                {dayBookings.slice(0, 3).map((booking: any) => (
                                    <div 
                                        key={booking.id}
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            onBookingClick?.(booking);
                                        }}
                                        className="text-xs truncate bg-indigo-50 text-indigo-700 rounded px-1.5 py-1 hover:bg-indigo-100 cursor-pointer flex items-center"
                                    >
                                        <Clock className="w-3 h-3 me-1 shrink-0" />
                                        {format(new Date(booking.starts_at), 'HH:mm')} - {booking.guest_name}
                                    </div>
                                ))}
                                {dayBookings.length > 3 && (
                                    <div className="text-[10px] text-slate-500 font-medium ps-1">
                                        + {dayBookings.length - 3} more...
                                    </div>
                                )}
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

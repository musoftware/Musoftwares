import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { Clock, CalendarCheck, BarChart2 } from 'lucide-react';
import axios from 'axios';
import { __ } from '@/lib/i18n';

export default function HoursCalendar({ years, auth }: any) {
    const [selectedYear, setSelectedYear] = useState<number>(years[0] || new Date().getFullYear());
    const [isLoading, setIsLoading] = useState<boolean>(true);
    const [data, setData] = useState<any[]>([]);
    
    // Stats
    const [totalHours, setTotalHours] = useState<number>(0);
    const [activeDays, setActiveDays] = useState<number>(0);
    const [averageHours, setAverageHours] = useState<number>(0);

    // Grid data
    const [calendarDays, setCalendarDays] = useState<any[]>([]);
    const [weeksCount, setWeeksCount] = useState<number>(53);
    const [monthLabels, setMonthLabels] = useState<any[]>([]);

    const [tooltip, setTooltip] = useState<{show: boolean, x: number, y: number, date: string, hours: string}>({
        show: false, x: 0, y: 0, date: '', hours: ''
    });

    useEffect(() => {
        loadData(selectedYear);
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [selectedYear]);

    const loadData = async (year: number) => {
        setIsLoading(true);
        try {
            const response = await axios.post('/admin/hours-calendar/data', { year });
            const responseData = response.data;
            setData(responseData);
            generateCalendar(year, responseData);
        } catch (error: any) {
            console.error('Failed to load hours calendar data', error);
        } finally {
            setIsLoading(false);
        }
    };

    const generateCalendar = (year: number, dateYearsData: any[]) => {
        const startDate = new Date(year, 0, 1);
        const endDate = new Date(year, 11, 31);
        
        const days: any[] = [];
        const dataMap: {[key: string]: number} = {};
        
        if (Array.isArray(dateYearsData)) {
            dateYearsData.forEach(item => {
                dataMap[item.date] = item.count;
            });
        }

        const dayOfWeek = startDate.getDay();
        
        for (let i = 0; i < dayOfWeek; i++) {
            days.push({ date: 'empty-' + i, count: 0, visible: false } as any);
        }
        
        const currentDate = new Date(startDate);
        while (currentDate <= endDate) {
            const yearStr = currentDate.getFullYear();
            const monthStr = String(currentDate.getMonth() + 1).padStart(2, '0');
            const dayStr = String(currentDate.getDate()).padStart(2, '0');
            const dateString = `${yearStr}-${monthStr}-${dayStr}`;
            days.push({
                date: dateString,
                count: dataMap[dateString] || 0,
                visible: true
            } as any);
            currentDate.setDate(currentDate.getDate() + 1);
        }
        
        setCalendarDays(days);
        setWeeksCount(Math.ceil(days.length / 7));
        generateMonthLabels(year, startDate.getDay());

        // Calculate Stats
        let total = 0;
        let active = 0;
        days.forEach(day => {
            if ((day as any).visible && (day as any).count > 0) {
                total += (day as any).count;
                active++;
            }
        });
        
        setTotalHours(total);
        setActiveDays(active);
        setAverageHours(active > 0 ? total / active : 0);
    };

    const generateMonthLabels = (year: number, startDayOfWeek: number) => {
        const months: any[] = [];
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        const startDate = new Date(year, 0, 1);
        
        for (let month = 0; month < 12; month++) {
            const firstDay = new Date(year, month, 1);
            const firstDayIndex = Math.round((firstDay.getTime() - startDate.getTime()) / (1000 * 60 * 60 * 24));
            const firstGridIndex = firstDayIndex + startDayOfWeek;
            const weekStart = Math.floor(firstGridIndex / 7) + 1;
            
            months.push({
                name: monthNames[month],
                weekStart: weekStart
            } as any);
        }
        
        setMonthLabels(months);
    };

    const handleMouseEnter = (e: React.MouseEvent, day: any) => {
        if (!day.visible) return;
        
        const rect = (e.target as HTMLElement).getBoundingClientRect();
        const date = new Date(day.date);
        const formattedDate = date.toLocaleDateString('en-US', { 
            weekday: 'short', 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
        
        setTooltip({
            show: true,
            x: rect.left + rect.width / 2,
            y: rect.top - 10,
            date: formattedDate,
            hours: day.count > 0 ? `${day.count.toFixed(1)} hours` : 'No activity'
        });
    };

    const getColorClass = (count: number) => {
        if (!count || count === 0) return 'bg-slate-100 hover:bg-slate-200 border-slate-200';
        if (count < 2) return 'bg-green-200 hover:bg-green-300 border-green-300';
        if (count < 5) return 'bg-green-400 hover:bg-green-500 border-green-500';
        if (count < 8) return 'bg-green-600 hover:bg-green-700 border-green-700';
        return 'bg-green-800 hover:bg-green-900 border-green-900';
    };

    return (
        <AdminSidebarLayout 
            title={__('general.work_hours_calendar')}
            header="Work Hours Calendar"
            user={auth?.user}
        >
            <div className="space-y-6 pb-10">
                <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <div>
                        <div className="flex items-center gap-2 mb-1">
                            <span className="bg-slate-100 text-slate-600 text-xs font-bold px-2 py-1 rounded uppercase tracking-wider">Productivity</span>
                        </div>
                        <h1 className="text-2xl font-bold text-slate-900">{__('general.work_hours_calendar')}</h1>
                        <p className="text-sm text-slate-500 mt-1">{__('general.track_your_productivity_throughout_the_year')}</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                        <select 
                            className="bg-white border border-slate-300 text-slate-700 text-sm rounded-lg focus:ring-slate-500 focus:border-slate-500 block w-full p-2.5"
                            value={selectedYear}
                            onChange={(e) => setSelectedYear(parseInt(e.target.value))}
                        >
                            {years.map((year: number) => (
                                <option key={year} value={year}>{year}</option>
                            ))}
                        </select>
                    </div>
                </div>

                {totalHours > 0 && (
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
                            <div className="bg-slate-100 text-slate-700 p-3 rounded-lg"><Clock className="w-6 h-6"/></div>
                            <div>
                                <p className="text-sm font-medium text-slate-500 uppercase">{__('general.total_hours')}</p>
                                <p className="text-2xl font-bold text-slate-900">{totalHours.toFixed(1)}</p>
                            </div>
                        </div>
                        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
                            <div className="bg-slate-100 text-slate-700 p-3 rounded-lg"><CalendarCheck className="w-6 h-6"/></div>
                            <div>
                                <p className="text-sm font-medium text-slate-500 uppercase">{__('general.active_days')}</p>
                                <p className="text-2xl font-bold text-slate-900">{activeDays}</p>
                            </div>
                        </div>
                        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
                            <div className="bg-slate-100 text-slate-700 p-3 rounded-lg"><BarChart2 className="w-6 h-6"/></div>
                            <div>
                                <p className="text-sm font-medium text-slate-500 uppercase">{__('general.average_day')}</p>
                                <p className="text-2xl font-bold text-slate-900">{averageHours.toFixed(1)}</p>
                            </div>
                        </div>
                    </div>
                )}

                <OperationalCard title={`Heatmap (${selectedYear})`} className="relative">
                    {isLoading && (
                        <div className="absolute inset-0 bg-white/70 z-10 flex flex-col items-center justify-center rounded-xl">
                            <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-slate-900 mb-3"></div>
                            <span className="text-slate-600 font-medium">{__('general.loading_data')}</span>
                        </div>
                    )}

                    <div className="overflow-x-auto pb-4">
                        <div className="min-w-[800px]">
                            <div 
                                className="grid text-xs text-slate-500 mb-2 ps-[40px]" 
                                style={{ gridTemplateColumns: `repeat(${weeksCount}, minmax(12px, 1fr))` }}
                            >
                                {monthLabels.map((month, idx) => (
                                    <div key={idx} style={{ gridColumn: month.weekStart }}>
                                        {month.name}
                                    </div>
                                ))}
                            </div>

                            <div className="flex">
                                <div className="flex flex-col justify-between text-xs text-slate-400 pe-2 pb-[2px] pt-[2px]" style={{ height: '110px' }}>
                                    <div className="invisible">Sun</div>
                                    <div>Mon</div>
                                    <div className="invisible">Tue</div>
                                    <div>Wed</div>
                                    <div className="invisible">Thu</div>
                                    <div>Fri</div>
                                    <div className="invisible">Sat</div>
                                </div>
                                
                                <div 
                                    className="grid grid-flow-col gap-[3px] flex-1" 
                                    style={{ 
                                        gridTemplateColumns: `repeat(${weeksCount}, minmax(12px, 1fr))`,
                                        gridTemplateRows: 'repeat(7, 1fr)'
                                    }}
                                >
                                    {calendarDays.map((day, idx) => (
                                        <div 
                                            key={idx}
                                            className={`w-[12px] h-[12px] md:w-[14px] md:h-[14px] rounded-[2px] border ${day.visible ? getColorClass(day.count) : 'invisible'}`}
                                            onMouseEnter={(e) => handleMouseEnter(e, day)}
                                            onMouseLeave={() => setTooltip({...tooltip, show: false})}
                                        ></div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center gap-2 mt-6 text-xs text-slate-500 justify-end w-full">
                        <span>Less</span>
                        <div className="w-[12px] h-[12px] md:w-[14px] md:h-[14px] rounded-[2px] border bg-slate-100 border-slate-200"></div>
                        <div className="w-[12px] h-[12px] md:w-[14px] md:h-[14px] rounded-[2px] border bg-green-200 border-green-300"></div>
                        <div className="w-[12px] h-[12px] md:w-[14px] md:h-[14px] rounded-[2px] border bg-green-400 border-green-500"></div>
                        <div className="w-[12px] h-[12px] md:w-[14px] md:h-[14px] rounded-[2px] border bg-green-600 border-green-700"></div>
                        <div className="w-[12px] h-[12px] md:w-[14px] md:h-[14px] rounded-[2px] border bg-green-800 border-green-900"></div>
                        <span>More</span>
                    </div>

                    {/* Tooltip */}
                    {tooltip.show && (
                        <div 
                            className="fixed z-50 bg-slate-900 text-white text-xs py-1.5 px-3 rounded shadow-lg pointer-events-none transform -translate-x-1/2 -translate-y-full mt-[-8px]"
                            style={{ left: tooltip.x, top: tooltip.y }}
                        >
                            <div className="font-semibold">{tooltip.date}</div>
                            <div className="text-slate-300">{tooltip.hours}</div>
                            <div className="absolute w-2 h-2 bg-slate-900 rotate-45 -bottom-1 start-1/2 transform -translate-x-1/2"></div>
                        </div>
                    )}
                </OperationalCard>
            </div>
        </AdminSidebarLayout>
    );
}

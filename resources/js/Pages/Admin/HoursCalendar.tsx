import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { 
    Clock, 
    CalendarCheck, 
    BarChart2, 
    DollarSign, 
    TrendingUp, 
    Download, 
    Activity, 
    FileText,
    ArrowUpRight
} from 'lucide-react';
import axios from 'axios';
import { __ } from '@/lib/i18n';
import { IsoCurrencyAmount } from '@/lib/currencyDisplay';
import { 
    ResponsiveContainer, 
    AreaChart, 
    Area, 
    XAxis, 
    YAxis, 
    CartesianGrid, 
    Tooltip as RechartsTooltip, 
    Legend,
    ComposedChart,
    Line
} from 'recharts';

export default function HoursCalendar({ years, auth }: any) {
    const [selectedYear, setSelectedYear] = useState<number>(years[0] || new Date().getFullYear());
    const [isLoading, setIsLoading] = useState<boolean>(true);
    const [data, setData] = useState<any[]>([]);
    
    // Stats
    const [totalHours, setTotalHours] = useState<number>(0);
    const [activeDays, setActiveDays] = useState<number>(0);
    const [averageHours, setAverageHours] = useState<number>(0);

    // 30 Days stats and details
    const [chartData, setChartData] = useState<any[]>([]);
    const [marketHourlyRate, setMarketHourlyRate] = useState<number>(0);
    const [recommendedHourlyRate, setRecommendedHourlyRate] = useState<number>(0);
    const [businessCurrency, setBusinessCurrency] = useState<string>('USD');
    const [last30DaysTimers, setLast30DaysTimers] = useState<any[]>([]);
    const [searchQuery, setSearchQuery] = useState<string>('');

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
            if (responseData && !Array.isArray(responseData)) {
                setData(responseData.heatmap || []);
                generateCalendar(year, responseData.heatmap || []);
                setChartData(responseData.chart_30_days || []);
                setMarketHourlyRate(responseData.market_hourly_rate || 0);
                setRecommendedHourlyRate(responseData.recommended_hourly_rate || 0);
                setBusinessCurrency(responseData.business_currency || 'USD');
                setLast30DaysTimers(responseData.last_30_days_timers || []);
            } else {
                setData(responseData);
                generateCalendar(year, responseData);
            }
        } catch (error: any) {
            console.error('Failed to load hours calendar data', error);
        } finally {
            setIsLoading(false);
        }
    };

    const exportToCSV = () => {
        if (!last30DaysTimers.length) return;
        const headers = ['Date', 'Client', 'Project', 'Duration', 'Client Amount', 'Business Amount', 'Actual Yield'];
        const rows = last30DaysTimers.map(t => [
            t.date,
            t.client_name,
            t.project_name,
            t.duration_str,
            `${t.amount} ${t.amount_str.replace(/[^a-zA-Z\s]/g, '')}`,
            t.business_amount_str.replace(/[^\d.,]/g, '') + ' ' + businessCurrency,
            t.business_rate_str.replace(/[^\d.,]/g, '') + ' ' + businessCurrency
        ]);
        const csvContent = "\uFEFF" + [headers.join(','), ...rows.map(e => e.map(val => `"${val}"`).join(','))].join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute("download", `work_hours_export_${new Date().toISOString().split('T')[0]}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
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

    // 30 Days Computed Stats
    const totalHours30 = last30DaysTimers.reduce((sum, t) => sum + Number(t.hours), 0);
    const totalBilled30 = last30DaysTimers.reduce((sum, t) => sum + Number(t.business_amount), 0);
    const avgYield30 = totalHours30 > 0 ? totalBilled30 / totalHours30 : 0;
    const marketSavings30 = last30DaysTimers.reduce((sum, t) => {
        const marketValue = Number(t.hours) * marketHourlyRate;
        const diff = Math.max(0, marketValue - Number(t.business_amount));
        return sum + diff;
    }, 0);

    const filteredTimers = last30DaysTimers.filter(t => {
        const matchSearch = (t.project_name || '').toLowerCase().includes(searchQuery.toLowerCase()) ||
                            (t.client_name || '').toLowerCase().includes(searchQuery.toLowerCase()) ||
                            (t.date || '').toLowerCase().includes(searchQuery.toLowerCase());
        return matchSearch;
    });

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
                            <span className="bg-slate-100 text-slate-600 text-xs font-bold px-2 py-1 rounded uppercase tracking-wider">{__('general.productivity')}</span>
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
                        <span>{__('general.less')}</span>
                        <div className="w-[12px] h-[12px] md:w-[14px] md:h-[14px] rounded-[2px] border bg-slate-100 border-slate-200"></div>
                        <div className="w-[12px] h-[12px] md:w-[14px] md:h-[14px] rounded-[2px] border bg-green-200 border-green-300"></div>
                        <div className="w-[12px] h-[12px] md:w-[14px] md:h-[14px] rounded-[2px] border bg-green-400 border-green-500"></div>
                        <div className="w-[12px] h-[12px] md:w-[14px] md:h-[14px] rounded-[2px] border bg-green-600 border-green-700"></div>
                        <div className="w-[12px] h-[12px] md:w-[14px] md:h-[14px] rounded-[2px] border bg-green-800 border-green-900"></div>
                        <span>{__('general.more')}</span>
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

                {/* Last 30 Days Dashboard Section */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Left: KPI Stats Cards */}
                    <div className="space-y-4">
                        <h2 className="text-lg font-bold text-slate-900 px-1 flex items-center gap-2">
                            <Activity className="w-5 h-5 text-indigo-600" />
                            {__('general.productivity')} (آخر 30 يوم)
                        </h2>
                        
                        {/* KPI 1: Market Hourly Rate */}
                        <div className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm transition-all hover:shadow-md">
                            <div className="flex items-center justify-between mb-2">
                                <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">{__('general.market_hourly_rate')}</span>
                                <span className="p-1.5 bg-rose-50 text-rose-600 rounded-lg"><DollarSign className="w-4 h-4" /></span>
                            </div>
                            <div className="flex items-baseline gap-2">
                                <IsoCurrencyAmount amount={marketHourlyRate} currency={{ currency: businessCurrency }} size="lg" />
                                <span className="text-xs text-slate-400">/ {__('general.per_hour')}</span>
                            </div>
                            <p className="text-xs text-slate-400 mt-2">سعر الساعة التقديري في السوق حالياً</p>
                        </div>

                        {/* KPI 2: Average Hourly Yield */}
                        <div className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm transition-all hover:shadow-md">
                            <div className="flex items-center justify-between mb-2">
                                <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">{__('general.avg_billed_rate')}</span>
                                <span className="p-1.5 bg-indigo-50 text-indigo-600 rounded-lg"><TrendingUp className="w-4 h-4" /></span>
                            </div>
                            <div className="flex items-baseline gap-2">
                                <IsoCurrencyAmount amount={avgYield30} currency={{ currency: businessCurrency }} size="lg" />
                                <span className="text-xs text-slate-400">/ {__('general.per_hour')}</span>
                            </div>
                            <p className="text-xs text-slate-400 mt-2">متوسط العائد الفعلي المحقق لكل ساعة عمل</p>
                        </div>

                        {/* KPI 3: Market Savings */}
                        <div className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm transition-all hover:shadow-md">
                            <div className="flex items-center justify-between mb-2">
                                <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">{__('general.market_discount_savings')}</span>
                                <span className="p-1.5 bg-emerald-50 text-emerald-600 rounded-lg"><ArrowUpRight className="w-4 h-4" /></span>
                            </div>
                            <div className="flex items-baseline gap-2">
                                <IsoCurrencyAmount amount={marketSavings30} currency={{ currency: businessCurrency }} size="lg" />
                            </div>
                            <p className="text-xs text-slate-400 mt-2">الوفر والخصومات المقدمة للعملاء مقارنة بالسوق</p>
                        </div>
                    </div>

                    {/* Right: Chart Comparison */}
                    <div className="lg:col-span-2">
                        <OperationalCard title="مقارنة سعر الساعة الفعلي وسعر السوق (آخر 30 يوم)" className="h-full">
                            <div className="h-[280px] w-full mt-4">
                                <ResponsiveContainer width="100%" height="100%">
                                    <ComposedChart data={chartData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                                        <defs>
                                            <linearGradient id="colorActual" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="5%" stopColor="#6366f1" stopOpacity={0.2}/>
                                                <stop offset="95%" stopColor="#6366f1" stopOpacity={0}/>
                                            </linearGradient>
                                        </defs>
                                        <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                                        <XAxis 
                                            dataKey="date" 
                                            tickLine={false} 
                                            axisLine={false} 
                                            tickFormatter={(str) => {
                                                try {
                                                    const d = new Date(str);
                                                    return d.toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
                                                } catch (e) {
                                                    return str;
                                                }
                                            }}
                                            tick={{ fill: '#64748b', fontSize: 10 }} 
                                        />
                                        <YAxis 
                                            tickLine={false} 
                                            axisLine={false} 
                                            tick={{ fill: '#64748b', fontSize: 10 }}
                                            unit={` ${businessCurrency}`}
                                        />
                                        <RechartsTooltip 
                                            contentStyle={{ backgroundColor: '#0f172a', borderRadius: '8px', border: 'none', color: '#fff', fontSize: '12px' }}
                                            labelStyle={{ fontWeight: 'bold', color: '#94a3b8' }}
                                        />
                                        <Legend verticalAlign="top" height={36} iconType="circle" wrapperStyle={{ fontSize: '11px', color: '#64748b' }} />
                                        
                                        {/* actual hourly rate filled area */}
                                        <Area 
                                            type="monotone" 
                                            dataKey="actual_rate" 
                                            stroke="#4f46e5" 
                                            strokeWidth={2} 
                                            fillOpacity={1} 
                                            fill="url(#colorActual)" 
                                            name="العائد الفعلي لساعتك" 
                                        />
                                        
                                        {/* market hourly rate baseline */}
                                        <Line 
                                            type="monotone" 
                                            dataKey="market_rate" 
                                            stroke="#ef4444" 
                                            strokeWidth={1.5} 
                                            strokeDasharray="4 4" 
                                            dot={false} 
                                            name="سعر ساعة السوق" 
                                        />

                                        {/* recommended hourly rate baseline */}
                                        <Line 
                                            type="monotone" 
                                            dataKey="recommended_rate" 
                                            stroke="#f59e0b" 
                                            strokeWidth={1.5} 
                                            strokeDasharray="3 3" 
                                            dot={false} 
                                            name="السعر الموصى به" 
                                        />
                                    </ComposedChart>
                                </ResponsiveContainer>
                            </div>
                        </OperationalCard>
                    </div>
                </div>

                {/* Detailed Session Activity Log Table */}
                <OperationalCard 
                    title={__('general.view_detailed_time_sessions')} 
                    action={
                        <div className="flex items-center gap-3">
                            <input 
                                type="text"
                                placeholder="بحث عن عميل أو مشروع..."
                                className="px-3 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-500 w-48 md:w-64"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                            />
                            <button
                                onClick={exportToCSV}
                                disabled={!last30DaysTimers.length}
                                className="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 focus:outline-none cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <Download className="w-3.5 h-3.5" />
                                تصدير CSV
                            </button>
                        </div>
                    }
                >
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm text-left rtl:text-right text-slate-500">
                            <thead className="text-xs text-slate-700 bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th className="px-6 py-3 font-semibold text-center">{__('general.date')}</th>
                                    <th className="px-6 py-3 font-semibold text-center">العميل</th>
                                    <th className="px-6 py-3 font-semibold text-center">المشروع</th>
                                    <th className="px-6 py-3 font-semibold text-center">المدة</th>
                                    <th className="px-6 py-3 font-semibold text-center">معدل الساعة الفعلي</th>
                                    <th className="px-6 py-3 font-semibold text-center">قيمة الجلسة</th>
                                    <th className="px-6 py-3 font-semibold text-center">معدل الساعة بالـ Business</th>
                                    <th className="px-6 py-3 font-semibold text-center">الفاتورة</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {filteredTimers.length > 0 ? (
                                    filteredTimers.map((timer) => (
                                        <tr key={timer.id} className="hover:bg-slate-50/80 transition-colors">
                                            <td className="px-6 py-4 text-center font-medium text-slate-900 whitespace-nowrap">{timer.date}</td>
                                            <td className="px-6 py-4 text-center">{timer.client_name}</td>
                                            <td className="px-6 py-4 text-center">{timer.project_name}</td>
                                            <td className="px-6 py-4 text-center font-semibold text-slate-700 whitespace-nowrap">{timer.duration_str} ({timer.hours}h)</td>
                                            <td className="px-6 py-4 text-center text-slate-700 font-medium whitespace-nowrap">{timer.actual_rate_str} / ساعة</td>
                                            <td className="px-6 py-4 text-center font-semibold text-slate-900 whitespace-nowrap">{timer.amount_str}</td>
                                            <td className="px-6 py-4 text-center text-indigo-600 font-semibold whitespace-nowrap">{timer.business_rate_str} / ساعة</td>
                                            <td className="px-6 py-4 text-center whitespace-nowrap">
                                                {timer.invoice_id ? (
                                                    <Link 
                                                        href={`/admin/invoices/${timer.invoice_id}`}
                                                        className="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-900 hover:underline"
                                                    >
                                                        <FileText className="w-3.5 h-3.5" />
                                                        #{timer.invoice_number}
                                                    </Link>
                                                ) : (
                                                    <span className="text-slate-400 text-xs">—</span>
                                                )}
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={8} className="px-6 py-10 text-center text-slate-400">
                                            لا توجد جلسات عمل مسجلة في الـ 30 يومًا الماضية تطابق البحث.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </OperationalCard>
            </div>
        </AdminSidebarLayout>
    );
}

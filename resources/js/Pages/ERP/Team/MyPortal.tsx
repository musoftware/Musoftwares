import React, { useState } from 'react';
import ERPLayout from '@/Layouts/ERPLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import { 
    Clock, 
    CalendarDays, 
    FileText, 
    Play, 
    Square, 
    Plus,
    History,
    CheckCircle2,
    XCircle,
    Clock4,
    LayoutDashboard
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { __ } from '@/lib/i18n';

interface PortalProps {
    todayLog: any;
    attendanceHistory: any[];
    leaveRequests: any[];
    payslips: any[];
    member: any;
}

export default function MyPortal({ todayLog, attendanceHistory, leaveRequests, payslips, member }: PortalProps) {
    const clockInForm = useForm({});
    const clockOutForm = useForm({});
    const leaveForm = useForm({
        type: 'vacation',
        start_date: '',
        end_date: '',
        reason: ''
    });

    const [activeTab, setActiveTab] = useState<'time' | 'leave' | 'payroll'>('time');

    const menuItems = [
        {
            id: 'portal',
            label: __('erp.my_portal'),
            icon: LayoutDashboard,
            href: route('erp.team.portal.index'),
            isActive: true,
        }
    ];

    const handleClockIn = (e: React.FormEvent) => {
        e.preventDefault();
        clockInForm.post(route('erp.team.portal.clock-in'));
    };

    const handleClockOut = (e: React.FormEvent) => {
        e.preventDefault();
        clockOutForm.post(route('erp.team.portal.clock-out'));
    };

    const handleLeaveRequest = (e: React.FormEvent) => {
        e.preventDefault();
        leaveForm.post(route('erp.team.portal.leave-request'), {
            onSuccess: () => leaveForm.reset()
        });
    };

    const formatTime = (dateString: string | null) => {
        if (!dateString) return '--:--';
        return new Date(dateString).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    };

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'approved': return <CheckCircle2 className="w-4 h-4 text-emerald-500" />;
            case 'rejected': return <XCircle className="w-4 h-4 text-red-500" />;
            default: return <Clock4 className="w-4 h-4 text-amber-500" />;
        }
    };

    return (
        <ERPLayout title={__('erp.my_portal')} workspaceName={member.name} menuItems={menuItems}>
            <Head title={__('erp.my_portal')} />

            <div className="flex justify-between items-center mb-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">{__('erp.my_portal')}</h1>
                    <p className="text-sm text-neutral-500">{__('erp.manage_time_and_payroll')}</p>
                </div>
            </div>

            <div className="flex space-x-2 mb-6 border-b border-neutral-200">
                <button 
                    onClick={() => setActiveTab('time')}
                    className={`px-4 py-2 text-sm font-medium border-b-2 transition-colors ${activeTab === 'time' ? 'border-black text-black' : 'border-transparent text-neutral-500 hover:text-black hover:border-neutral-300'}`}
                >
                    <div className="flex items-center gap-2">
                        <Clock className="w-4 h-4" />
                        {__('erp.time_tracking')}
                    </div>
                </button>
                <button 
                    onClick={() => setActiveTab('leave')}
                    className={`px-4 py-2 text-sm font-medium border-b-2 transition-colors ${activeTab === 'leave' ? 'border-black text-black' : 'border-transparent text-neutral-500 hover:text-black hover:border-neutral-300'}`}
                >
                    <div className="flex items-center gap-2">
                        <CalendarDays className="w-4 h-4" />
                        {__('erp.leave_requests')}
                    </div>
                </button>
                <button 
                    onClick={() => setActiveTab('payroll')}
                    className={`px-4 py-2 text-sm font-medium border-b-2 transition-colors ${activeTab === 'payroll' ? 'border-black text-black' : 'border-transparent text-neutral-500 hover:text-black hover:border-neutral-300'}`}
                >
                    <div className="flex items-center gap-2">
                        <FileText className="w-4 h-4" />
                        {__('erp.payroll_slips')}
                    </div>
                </button>
            </div>

            {activeTab === 'time' && (
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div className="lg:col-span-1 space-y-6">
                        <OperationalCard title={__('erp.today_attendance')} icon={<Clock className="w-5 h-5 text-neutral-500" />}>
                            <div className="space-y-6">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="p-4 rounded-xl bg-neutral-50 border border-neutral-100 flex flex-col items-center justify-center">
                                        <span className="text-xs font-medium text-neutral-500 uppercase tracking-wider mb-1">{__('erp.clock_in')}</span>
                                        <span className="text-xl font-bold tracking-tight">
                                            {formatTime(todayLog?.clock_in_at)}
                                        </span>
                                    </div>
                                    <div className="p-4 rounded-xl bg-neutral-50 border border-neutral-100 flex flex-col items-center justify-center">
                                        <span className="text-xs font-medium text-neutral-500 uppercase tracking-wider mb-1">{__('erp.clock_out')}</span>
                                        <span className="text-xl font-bold tracking-tight">
                                            {formatTime(todayLog?.clock_out_at)}
                                        </span>
                                    </div>
                                </div>
                                
                                <div className="flex flex-col gap-3">
                                    {!todayLog?.clock_in_at ? (
                                        <form onSubmit={handleClockIn}>
                                            <Button type="submit" disabled={clockInForm.processing} className="w-full h-12 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-xl shadow-sm transition-all flex items-center justify-center gap-2">
                                                <Play className="w-5 h-5" />
                                                {__('erp.clock_in')}
                                            </Button>
                                        </form>
                                    ) : !todayLog?.clock_out_at ? (
                                        <form onSubmit={handleClockOut}>
                                            <Button type="submit" disabled={clockOutForm.processing} className="w-full h-12 bg-rose-600 hover:bg-rose-700 text-white font-medium rounded-xl shadow-sm transition-all flex items-center justify-center gap-2">
                                                <Square className="w-5 h-5 fill-current" />
                                                {__('erp.clock_out')}
                                            </Button>
                                        </form>
                                    ) : (
                                        <div className="p-4 rounded-xl bg-neutral-50 border border-neutral-100 text-center">
                                            <p className="text-sm font-medium text-neutral-700">{__('erp.shift_completed')}</p>
                                            <p className="text-xs text-neutral-500 mt-1">{__('erp.total_minutes')}: {todayLog?.total_minutes}</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </OperationalCard>
                    </div>

                    <div className="lg:col-span-2">
                        <OperationalCard title={__('erp.recent_attendance')} icon={<History className="w-5 h-5 text-neutral-500" />}>
                            {attendanceHistory.length > 0 ? (
                                <div className="overflow-x-auto rounded-xl border border-neutral-200">
                                    <table className="w-full text-sm text-left">
                                        <thead className="bg-neutral-50 border-b border-neutral-200 text-neutral-500">
                                            <tr>
                                                <th className="px-4 py-3 font-medium">{__('erp.date')}</th>
                                                <th className="px-4 py-3 font-medium">{__('erp.clock_in')}</th>
                                                <th className="px-4 py-3 font-medium">{__('erp.clock_out')}</th>
                                                <th className="px-4 py-3 font-medium text-right">{__('erp.duration')}</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-neutral-100">
                                            {attendanceHistory.map((log: any) => (
                                                <tr key={log.id} className="hover:bg-neutral-50 transition-colors">
                                                    <td className="px-4 py-3 font-medium">{log.date}</td>
                                                    <td className="px-4 py-3">{formatTime(log.clock_in_at)}</td>
                                                    <td className="px-4 py-3">{formatTime(log.clock_out_at)}</td>
                                                    <td className="px-4 py-3 text-right text-neutral-500">{log.total_minutes} min</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="py-8 text-center text-neutral-500 text-sm">
                                    {__('erp.no_attendance_records')}
                                </div>
                            )}
                        </OperationalCard>
                    </div>
                </div>
            )}

            {activeTab === 'leave' && (
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div className="lg:col-span-1">
                        <OperationalCard title={__('erp.request_leave')} icon={<Plus className="w-5 h-5 text-neutral-500" />}>
                            <form onSubmit={handleLeaveRequest} className="space-y-4">
                                <div className="space-y-2">
                                    <Label>{__('erp.leave_type')}</Label>
                                    <Select 
                                        value={leaveForm.data.type} 
                                        onValueChange={v => leaveForm.setData('type', v)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder={__('erp.select_leave_type')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="vacation">{__('erp.vacation')}</SelectItem>
                                            <SelectItem value="sick">{__('erp.sick_leave')}</SelectItem>
                                            <SelectItem value="unpaid">{__('erp.unpaid_leave')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {leaveForm.errors.type && <p className="text-sm text-red-500">{leaveForm.errors.type}</p>}
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label>{__('erp.start_date')}</Label>
                                        <Input 
                                            type="date" 
                                            value={leaveForm.data.start_date}
                                            onChange={e => leaveForm.setData('start_date', e.target.value)}
                                        />
                                        {leaveForm.errors.start_date && <p className="text-sm text-red-500">{leaveForm.errors.start_date}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label>{__('erp.end_date')}</Label>
                                        <Input 
                                            type="date" 
                                            value={leaveForm.data.end_date}
                                            onChange={e => leaveForm.setData('end_date', e.target.value)}
                                        />
                                        {leaveForm.errors.end_date && <p className="text-sm text-red-500">{leaveForm.errors.end_date}</p>}
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label>{__('erp.reason')}</Label>
                                    <Textarea 
                                        value={leaveForm.data.reason}
                                        onChange={e => leaveForm.setData('reason', e.target.value)}
                                        className="resize-none h-24"
                                    />
                                    {leaveForm.errors.reason && <p className="text-sm text-red-500">{leaveForm.errors.reason}</p>}
                                </div>

                                <Button type="submit" disabled={leaveForm.processing} className="w-full">
                                    {__('erp.submit_request')}
                                </Button>
                            </form>
                        </OperationalCard>
                    </div>

                    <div className="lg:col-span-2">
                        <OperationalCard title={__('erp.my_leave_requests')} icon={<CalendarDays className="w-5 h-5 text-neutral-500" />}>
                            {leaveRequests.length > 0 ? (
                                <div className="space-y-4">
                                    {leaveRequests.map((request: any) => (
                                        <div key={request.id} className="p-4 rounded-xl bg-white border border-neutral-200 shadow-sm flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
                                            <div>
                                                <div className="flex items-center gap-2 mb-1">
                                                    <span className="font-semibold text-neutral-900 capitalize">
                                                        {request.type === 'vacation' ? __('erp.vacation') : 
                                                         request.type === 'sick' ? __('erp.sick_leave') : 
                                                         __('erp.unpaid_leave')}
                                                    </span>
                                                    <div className={`px-2 py-0.5 rounded-full text-xs font-medium flex items-center gap-1 ${
                                                        request.status === 'approved' ? 'bg-emerald-100 text-emerald-700' :
                                                        request.status === 'rejected' ? 'bg-rose-100 text-rose-700' :
                                                        'bg-amber-100 text-amber-700'
                                                    }`}>
                                                        {getStatusIcon(request.status)}
                                                        <span className="capitalize">{request.status}</span>
                                                    </div>
                                                </div>
                                                <div className="text-sm text-neutral-500 flex items-center gap-2">
                                                    <CalendarDays className="w-3 h-3" />
                                                    {request.start_date} &rarr; {request.end_date}
                                                </div>
                                                {request.reason && (
                                                    <p className="text-sm text-neutral-600 mt-2 bg-neutral-50 p-2 rounded-lg">{request.reason}</p>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="py-8 text-center text-neutral-500 text-sm">
                                    {__('erp.no_leave_requests')}
                                </div>
                            )}
                        </OperationalCard>
                    </div>
                </div>
            )}

            {activeTab === 'payroll' && (
                <OperationalCard title={__('erp.my_payroll_slips')} icon={<FileText className="w-5 h-5 text-neutral-500" />}>
                    {payslips.length > 0 ? (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {payslips.map((slip: any) => (
                                <div key={slip.id} className="p-5 rounded-2xl border border-neutral-200 bg-white shadow-sm hover:shadow-md transition-all">
                                    <div className="flex justify-between items-start mb-4">
                                        <div>
                                            <h3 className="font-bold text-lg">{slip.month}/{slip.year}</h3>
                                            <p className="text-sm text-neutral-500 capitalize">{slip.status}</p>
                                        </div>
                                        <div className={`px-2.5 py-1 rounded-full text-xs font-semibold ${
                                            slip.status === 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-neutral-100 text-neutral-700'
                                        }`}>
                                            {slip.currency?.symbol} {Number(slip.net_amount).toLocaleString()}
                                        </div>
                                    </div>
                                    <div className="space-y-2 text-sm">
                                        <div className="flex justify-between text-neutral-600">
                                            <span>{__('erp.base_amount')}:</span>
                                            <span>{slip.currency?.symbol} {Number(slip.base_amount).toLocaleString()}</span>
                                        </div>
                                        <div className="flex justify-between text-neutral-600">
                                            <span>{__('erp.worked_days')}:</span>
                                            <span>{slip.worked_days}</span>
                                        </div>
                                        {slip.items?.map((item: any) => (
                                            <div key={item.id} className={`flex justify-between ${item.type === 'bonus' ? 'text-emerald-600' : 'text-rose-600'}`}>
                                                <span>{item.description}:</span>
                                                <span>{item.type === 'bonus' ? '+' : '-'}{slip.currency?.symbol} {Number(item.amount).toLocaleString()}</span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="py-12 text-center text-neutral-500">
                            <FileText className="w-8 h-8 mx-auto text-neutral-300 mb-3" />
                            <p className="text-sm font-medium">{__('erp.no_payslips_found')}</p>
                        </div>
                    )}
                </OperationalCard>
            )}
        </ERPLayout>
    );
}

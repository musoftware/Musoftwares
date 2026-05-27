import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';

import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Calendar, Clock, Users, ArrowUpRight, CheckCircle2, XCircle, CalendarOff } from 'lucide-react';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';

interface DashboardProps {
    stats: {
        total_bookings: number;
        today_appointments: number;
        cancelled_bookings: number;
        total_revenue: number;
    };
    upcoming_bookings: any[];
}

export default function Dashboard({ stats, upcoming_bookings }: DashboardProps) {
    const { wallet, settings } = usePage<any>().props;
    const currency = wallet?.currency || settings?.base_currency || 'USD';

    return (
        <WorkspaceLayout
            title="Booking Dashboard"
            workspaceName="Booking Settings"
            tenantId="SYS-BOOKING"
            menuItems={[
                { id: 'dashboard', label: 'Dashboard', icon: Calendar, href: '/booking', isActive: true },
                { id: 'appointments', label: 'Appointments', icon: Clock, href: '/booking/appointments', isActive: false },
                { id: 'events', label: 'Event Types', icon: Calendar, href: '/booking/events', isActive: false },
                { id: 'providers', label: 'Providers', icon: Users, href: '/booking/providers', isActive: false },
                { id: 'exceptions', label: 'Exceptions', icon: CalendarOff, href: '/booking/exceptions', isActive: false },
            ]}
        >
            <Head title="Booking Dashboard" />
            
            <div className="space-y-8">
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Dashboard</h1>
                        <p className="text-muted-foreground">Overview of your booking statistics and upcoming appointments.</p>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Today's Appointments</CardTitle>
                            <Clock className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.today_appointments.toString()}</div>
                            <p className="text-xs text-muted-foreground">Bookings for today</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Bookings</CardTitle>
                            <Calendar className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_bookings.toString()}</div>
                            <p className="text-xs text-muted-foreground">All time appointments</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Revenue</CardTitle>
                            <ArrowUpRight className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold"><CurrencyDisplay amount={stats.total_revenue} currency={currency} hideSymbol={false} /></div>
                            <p className="text-xs text-muted-foreground">Paid appointments</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Cancellations</CardTitle>
                            <XCircle className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.cancelled_bookings.toString()}</div>
                            <p className="text-xs text-muted-foreground">Total cancelled</p>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <Card className="col-span-2">
                        <CardHeader>
                            <CardTitle>Upcoming Appointments</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {upcoming_bookings.length === 0 ? (
                                <div className="text-center py-8 text-slate-500 text-sm">
                                    No upcoming appointments found.
                                </div>
                            ) : (
                                <div className="space-y-4">
                                    {upcoming_bookings.map((booking: any) => (
                                        <div key={booking.id} className="flex items-center justify-between p-4 bg-slate-50 rounded-lg border border-slate-100">
                                            <div className="flex items-start gap-4">
                                                <div className="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center shrink-0 font-medium text-slate-600">
                                                    {booking.guest_name ? booking.guest_name.charAt(0).toUpperCase() : 'G'}
                                                </div>
                                                <div>
                                                    <p className="font-medium text-slate-900">{booking.guest_name || 'Guest'}</p>
                                                    <p className="text-xs text-slate-500 flex items-center gap-1 mt-0.5">
                                                        <Calendar className="w-3 h-3" />
                                                        {new Date(booking.starts_at).toLocaleDateString()} at {new Date(booking.starts_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                                    </p>
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                <p className="text-sm font-medium text-slate-900">{booking.event_type?.title}</p>
                                                <p className="text-xs text-slate-500 mt-0.5">with {booking.provider?.name}</p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </WorkspaceLayout>
    );
}

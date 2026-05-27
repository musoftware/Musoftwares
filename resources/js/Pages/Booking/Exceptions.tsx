import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';

import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Calendar, Clock, Users, CalendarOff, Plus, Trash2 } from 'lucide-react';

interface ExceptionsProps {
    providers: any[];
    exceptions: any[];
}

export default function Exceptions({ providers, exceptions }: ExceptionsProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        booking_provider_id: '',
        starts_at: '',
        ends_at: '',
        reason: '',
        is_recurring: false,
        recurring_pattern: '',
    });

    const [isAdding, setIsAdding] = useState(false);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('booking.exceptions.store'), {
            onSuccess: () => {
                reset();
                setIsAdding(false);
            },
        });
    };

    const deleteException = (id: number) => {
        if (confirm('Are you sure you want to remove this exception?')) {
            router.delete(route('booking.exceptions.destroy', id));
        }
    };

    return (
        <WorkspaceLayout
            title="Booking Exceptions"
            workspaceName="Booking Settings"
            tenantId="SYS-BOOKING"
            menuItems={[
                { id: 'dashboard', label: 'Dashboard', icon: Calendar, href: '/booking', isActive: false },
                { id: 'appointments', label: 'Appointments', icon: Clock, href: '/booking/appointments', isActive: false },
                { id: 'events', label: 'Event Types', icon: Calendar, href: '/booking/events', isActive: false },
                { id: 'providers', label: 'Providers', icon: Users, href: '/booking/providers', isActive: false },
                { id: 'exceptions', label: 'Exceptions', icon: CalendarOff, href: '/booking/exceptions', isActive: true },
            ]}
        >
            <Head title="Exceptions & Days Off" />
            
            <div className="space-y-8">
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Exceptions & Days Off</h1>
                        <p className="text-muted-foreground">Manage holidays, vacations, and custom overrides for your providers.</p>
                    </div>
                    <div className="mt-4 sm:mt-0">
                        <Button onClick={() => setIsAdding(!isAdding)} className="bg-slate-900 text-white hover:bg-slate-800">
                            <Plus className="w-4 h-4 mr-2" />
                            Add Exception
                        </Button>
                    </div>
                </div>

                {isAdding && (
                    <Card className="border-slate-200 shadow-sm">
                        <CardHeader>
                            <CardTitle className="text-lg">Add New Exception</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label>Provider</Label>
                                        <Select onValueChange={(value) => setData('booking_provider_id', value)} value={data.booking_provider_id}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select Provider" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {providers.map((p) => (
                                                    <SelectItem key={p.id} value={p.id.toString()}>{p.name}</SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.booking_provider_id && <p className="text-sm text-red-500">{errors.booking_provider_id}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Reason (Optional)</Label>
                                        <Input value={data.reason} onChange={e => setData('reason', e.target.value)} placeholder="e.g. Vacation, Sick Leave" />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Start Date & Time</Label>
                                        <Input type="datetime-local" value={data.starts_at} onChange={e => setData('starts_at', e.target.value)} />
                                        {errors.starts_at && <p className="text-sm text-red-500">{errors.starts_at}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label>End Date & Time</Label>
                                        <Input type="datetime-local" value={data.ends_at} onChange={e => setData('ends_at', e.target.value)} />
                                        {errors.ends_at && <p className="text-sm text-red-500">{errors.ends_at}</p>}
                                    </div>
                                </div>
                                <div className="flex justify-end gap-2 pt-4">
                                    <Button type="button" variant="outline" onClick={() => setIsAdding(false)}>Cancel</Button>
                                    <Button type="submit" disabled={processing} className="bg-slate-900 hover:bg-slate-800 text-white">Save Exception</Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Upcoming Blocked Dates</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {exceptions.length === 0 ? (
                            <div className="text-center py-8 text-slate-500 text-sm">
                                No exceptions found. Providers will use their standard weekly schedule.
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {exceptions.map((ex) => (
                                    <div key={ex.id} className="flex items-center justify-between p-4 bg-slate-50 rounded-lg border border-slate-100">
                                        <div className="flex items-start gap-4">
                                            <div className="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center shrink-0">
                                                <CalendarOff className="w-5 h-5 text-slate-500" />
                                            </div>
                                            <div>
                                                <p className="font-medium text-slate-900">{ex.reason || 'Blocked Time'}</p>
                                                <p className="text-sm text-slate-600 mt-0.5">
                                                    Provider: <span className="font-medium">{ex.provider?.name}</span>
                                                </p>
                                                <p className="text-xs text-slate-500 mt-1">
                                                    From: {new Date(ex.starts_at).toLocaleString()} <br/>
                                                    To: {new Date(ex.ends_at).toLocaleString()}
                                                </p>
                                            </div>
                                        </div>
                                        <Button variant="ghost" size="icon" onClick={() => deleteException(ex.id)} className="text-red-500 hover:text-red-700 hover:bg-red-50">
                                            <Trash2 className="w-4 h-4" />
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </WorkspaceLayout>
    );
}

import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';
import { ArrowLeft, Monitor, User } from 'lucide-react';
import { toastSuccess, toastError } from '@/Components/ui/use-toast';
import { __ } from '@/lib/i18n';

interface AvailableDevice {
    device_id: string;
    machine_name: string;
    user_name: string;
    software?: { id: number; name: string };
}

interface User_ { id: number; name: string; email: string; }

interface Props {
    users: User_[];
    availableDevices: AvailableDevice[];
}

export default function SerialUserDevicesAssign({ users, availableDevices }: Props) {
    const { auth } = usePage().props as any;
    const [form, setForm] = useState({ user_id: '', device_id: '', status: 'active', notes: '' });
    const [errors, setErrors] = useState<Record<string, string>>({});

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        setErrors({});
        router.post(route('admin.serial-user-devices.store'), form, {
            onSuccess: () => {
                toastSuccess(__('general.assignment_created') || 'Device assigned successfully');
                setForm({ user_id: '', device_id: '', status: 'active', notes: '' });
            },
            onError: (errs: any) => {
                setErrors(errs);
                toastError(errs.error || errs.message || __('general.error_occurred') || 'Something went wrong');
            },
        });
    };

    const selectedDevice = availableDevices.find(d => d.device_id === form.device_id);

    return (
        <AdminSidebarLayout title={__('general.assign_device')} header="Assign Device">
            <Head title={__('general.assign_device')} />
            <div className="max-w-3xl mx-auto space-y-6">
                <div className="flex items-center gap-3">
                    <Link href={route('admin.serial-user-devices.index')} className="text-slate-500 hover:text-slate-900 transition-colors">
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">{__('general.assign_device_to_user')}</h1>
                        <p className="text-slate-500 text-sm">{availableDevices.length} unassigned devices available</p>
                    </div>
                </div>

                <Card>
                    <CardContent className="p-6">
                        <form onSubmit={submit} className="space-y-5">
                            <div className="space-y-2">
                                <Label>{__('general.device')}</Label>
                                <Select value={form.device_id} onValueChange={v => setForm(f => ({ ...f, device_id: v || '' }))}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={__('general.select_a_device')} />
                                    </SelectTrigger>
                                    <SelectContent className="max-h-60">
                                        {availableDevices.map(d => (
                                            <SelectItem key={d.device_id} value={d.device_id}>
                                                <span className="font-mono text-xs">{d.device_id}</span>
                                                <span className="text-slate-500 ms-2">· {d.machine_name}</span>
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.device_id && <p className="text-sm text-destructive">{errors.device_id}</p>}
                            </div>

                            {selectedDevice && (
                                <div className="bg-slate-50 rounded-lg p-3 flex items-center gap-3 text-sm border">
                                    <Monitor className="w-4 h-4 text-slate-500 shrink-0" />
                                    <div>
                                        <p className="text-slate-700">{selectedDevice.machine_name} <span className="text-slate-500">({selectedDevice.user_name})</span></p>
                                        {selectedDevice.software && <p className="text-slate-500 text-xs">{selectedDevice.software.name}</p>}
                                    </div>
                                </div>
                            )}

                            <div className="space-y-2">
                                <Label>{__('general.assign_to_user')}</Label>
                                <Select value={form.user_id} onValueChange={v => setForm(f => ({ ...f, user_id: v || '' }))}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={__('general.select_a_user')} />
                                    </SelectTrigger>
                                    <SelectContent className="max-h-60">
                                        {users.map(u => (
                                            <SelectItem key={u.id} value={String(u.id)}>
                                                <span>{u.name}</span>
                                                <span className="text-slate-500 ms-2 text-xs">{u.email}</span>
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.user_id && <p className="text-sm text-destructive">{errors.user_id}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label>{__('general.initial_status')}</Label>
                                <Select value={form.status} onValueChange={v => setForm(f => ({ ...f, status: v || 'active' }))}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="active">{__('general.active')}</SelectItem>
                                        <SelectItem value="inactive">{__('general.inactive')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label>Notes (optional)</Label>
                                <Textarea
                                    placeholder={__('general.internal_notes_about_this_assignment')}
                                    rows={3}
                                    value={form.notes}
                                    onChange={e => setForm(f => ({ ...f, notes: e.target.value }))}
                                />
                            </div>

                            <div className="flex gap-3 pt-2">
                                <Button type="submit">{__('general.assign_device')}</Button>
                                <Link href={route('admin.serial-user-devices.index')}>
                                    <Button type="button" variant="outline">{__('general.cancel')}</Button>
                                </Link>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AdminSidebarLayout>
    );
}

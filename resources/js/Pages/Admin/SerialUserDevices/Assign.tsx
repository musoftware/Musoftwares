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
import { PremiumCombobox } from '@/Components/ui/PremiumCombobox';
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
    const [form, setForm] = useState({ user_id: '', device_id: '', status: 'active', expires_at: '', notes: '' });
    const [errors, setErrors] = useState<Record<string, string>>({});

    const setPresetDays = (days: number | null) => {
        if (days === null) {
            setForm(f => ({ ...f, expires_at: '' }));
            return;
        }
        const d = new Date();
        d.setDate(d.getDate() + days);
        setForm(f => ({ ...f, expires_at: d.toISOString().slice(0, 10) }));
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        setErrors({});
        router.post(route('admin.serial-user-devices.store'), {
            ...form,
            expires_at: form.expires_at || null,
        }, {
            onSuccess: () => {
                toastSuccess(__('general.assignment_created') || 'Device assigned successfully');
                setForm({ user_id: '', device_id: '', status: 'active', expires_at: '', notes: '' });
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
                                <PremiumCombobox
                                    value={form.user_id}
                                    onChange={(v) => setForm(f => ({ ...f, user_id: v ? String(v) : '' }))}
                                    options={users.map(u => ({ value: String(u.id), label: `${u.name} (${u.email})` }))}
                                    placeholder={__('general.select_a_user')}
                                    searchPlaceholder={__('general.search_users')}
                                />
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
                                <div className="flex items-center justify-between">
                                    <Label>{__('general.license_expiration', {}, 'License Expiration (Optional)')}</Label>
                                    <span className="text-xs text-muted-foreground">{__('general.leave_blank_for_lifetime', {}, 'Leave blank for lifetime')}</span>
                                </div>
                                <div className="flex flex-wrap gap-1.5 pb-1">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        className="h-7 text-xs"
                                        onClick={() => setPresetDays(30)}
                                    >
                                        +30 {__('general.days', {}, 'Days')}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        className="h-7 text-xs"
                                        onClick={() => setPresetDays(90)}
                                    >
                                        +90 {__('general.days', {}, 'Days')}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        className="h-7 text-xs"
                                        onClick={() => setPresetDays(180)}
                                    >
                                        +6 {__('general.months', {}, 'Months')}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        className="h-7 text-xs"
                                        onClick={() => setPresetDays(365)}
                                    >
                                        +1 {__('general.year', {}, 'Year')}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        size="sm"
                                        className="h-7 text-xs"
                                        onClick={() => setPresetDays(null)}
                                    >
                                        {__('general.lifetime', {}, 'Lifetime (Clear)')}
                                    </Button>
                                </div>
                                <Input
                                    type="date"
                                    value={form.expires_at}
                                    onChange={e => setForm(f => ({ ...f, expires_at: e.target.value }))}
                                    className="h-9 text-xs"
                                />
                                {errors.expires_at && <p className="text-sm text-destructive">{errors.expires_at}</p>}
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

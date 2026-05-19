import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';
import { ArrowLeft, Monitor, User } from 'lucide-react';

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
    const [form, setForm] = useState({ user_id: '', device_id: '', status: 'active', notes: '' });
    const [errors, setErrors] = useState<Record<string, string>>({});

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(route('admin.serial-user-devices.store'), form, {
            onError: errs => setErrors(errs),
        });
    };

    const selectedDevice = availableDevices.find(d => d.device_id === form.device_id);

    return (
        <AdminLayout>
            <Head title="Assign Device" />
            <div className="max-w-2xl mx-auto px-4 py-8 space-y-6">
                <div className="flex items-center gap-3">
                    <Link href={route('admin.serial-user-devices.index')} className="text-zinc-400 hover:text-white transition-colors">
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="text-xl font-bold text-white">Assign Device to User</h1>
                        <p className="text-zinc-400 text-sm">{availableDevices.length} unassigned devices available</p>
                    </div>
                </div>

                <Card className="bg-zinc-900 border-zinc-800">
                    <CardContent className="p-6">
                        <form onSubmit={submit} className="space-y-5">
                            {/* Device Selection */}
                            <div className="space-y-2">
                                <Label className="text-zinc-300 font-medium">Device</Label>
                                <Select value={form.device_id} onValueChange={v => setForm(f => ({ ...f, device_id: v }))}>
                                    <SelectTrigger className="bg-zinc-800 border-zinc-700 text-white">
                                        <SelectValue placeholder="Select a device..." />
                                    </SelectTrigger>
                                    <SelectContent className="bg-zinc-900 border-zinc-700 max-h-60">
                                        {availableDevices.map(d => (
                                            <SelectItem key={d.device_id} value={d.device_id}>
                                                <span className="font-mono text-xs text-violet-300">{d.device_id}</span>
                                                <span className="text-zinc-400 ml-2">· {d.machine_name}</span>
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.device_id && <p className="text-red-400 text-xs">{errors.device_id}</p>}
                            </div>

                            {/* Device Preview */}
                            {selectedDevice && (
                                <div className="bg-zinc-800 rounded-lg p-3 flex items-center gap-3 text-sm">
                                    <Monitor className="w-4 h-4 text-zinc-400 shrink-0" />
                                    <div>
                                        <p className="text-zinc-300">{selectedDevice.machine_name} <span className="text-zinc-500">({selectedDevice.user_name})</span></p>
                                        {selectedDevice.software && <p className="text-zinc-500 text-xs">{selectedDevice.software.name}</p>}
                                    </div>
                                </div>
                            )}

                            {/* User Selection */}
                            <div className="space-y-2">
                                <Label className="text-zinc-300 font-medium">Assign To User</Label>
                                <Select value={form.user_id} onValueChange={v => setForm(f => ({ ...f, user_id: v }))}>
                                    <SelectTrigger className="bg-zinc-800 border-zinc-700 text-white">
                                        <SelectValue placeholder="Select a user..." />
                                    </SelectTrigger>
                                    <SelectContent className="bg-zinc-900 border-zinc-700 max-h-60">
                                        {users.map(u => (
                                            <SelectItem key={u.id} value={String(u.id)}>
                                                <span className="text-white">{u.name}</span>
                                                <span className="text-zinc-500 ml-2 text-xs">{u.email}</span>
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.user_id && <p className="text-red-400 text-xs">{errors.user_id}</p>}
                            </div>

                            {/* Status */}
                            <div className="space-y-2">
                                <Label className="text-zinc-300 font-medium">Initial Status</Label>
                                <Select value={form.status} onValueChange={v => setForm(f => ({ ...f, status: v }))}>
                                    <SelectTrigger className="bg-zinc-800 border-zinc-700 text-white">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent className="bg-zinc-900 border-zinc-700">
                                        <SelectItem value="active">Active</SelectItem>
                                        <SelectItem value="inactive">Inactive</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Notes */}
                            <div className="space-y-2">
                                <Label className="text-zinc-300 font-medium">Notes (optional)</Label>
                                <Textarea
                                    className="bg-zinc-800 border-zinc-700 text-white placeholder:text-zinc-500 resize-none"
                                    placeholder="Internal notes about this assignment..."
                                    rows={3}
                                    value={form.notes}
                                    onChange={e => setForm(f => ({ ...f, notes: e.target.value }))}
                                />
                            </div>

                            <div className="flex gap-3 pt-2">
                                <Button type="submit" className="bg-violet-600 hover:bg-violet-500 text-white">
                                    Assign Device
                                </Button>
                                <Link href={route('admin.serial-user-devices.index')}>
                                    <Button type="button" variant="ghost" className="text-zinc-400 hover:text-white">Cancel</Button>
                                </Link>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}

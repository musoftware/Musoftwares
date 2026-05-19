import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Layers, Plus, Trash2, ToggleLeft, ToggleRight } from 'lucide-react';

interface Software {
    id: number;
    name: string;
    default_status: string;
    total_licenses: number;
    active_count: number;
    inactive_count: number;
    created_at: string;
}

interface Props {
    softwares: { data: Software[]; links: any[]; meta: any };
    filters: Record<string, any>;
}

export default function SerialSoftwaresIndex({ softwares, filters }: Props) {
    const [form, setForm] = useState({ name: '', default_status: 'active' });

    const store = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(route('admin.serial-softwares.store'), form, { onSuccess: () => setForm({ name: '', default_status: 'active' }) });
    };

    const updateStatus = (sw: Software, status: string) => {
        router.patch(route('admin.serial-softwares.status', sw.id), { status });
    };

    const destroy = (sw: Software) => {
        if (!confirm(`Delete software "${sw.name}"? This will also delete all associated device records.`)) return;
        router.delete(route('admin.serial-softwares.destroy', sw.id));
    };

    return (
        <AdminLayout>
            <Head title="Serial Softwares" />
            <div className="min-h-screen bg-zinc-950 p-6 space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-white tracking-tight">Serial Softwares</h1>
                    <p className="text-zinc-400 text-sm mt-1">Software registry — auto-created on first API check-in</p>
                </div>

                {/* Add Software */}
                <Card className="bg-zinc-900 border-zinc-800">
                    <CardContent className="p-4">
                        <p className="text-sm font-semibold text-white mb-3">Add Software Manually</p>
                        <form onSubmit={store} className="flex flex-wrap gap-3 items-end">
                            <div className="flex-1 min-w-48">
                                <Label className="text-zinc-400 text-xs mb-1 block">Software Name</Label>
                                <Input
                                    className="bg-zinc-800 border-zinc-700 text-white placeholder:text-zinc-500"
                                    placeholder="e.g. MyApp.exe"
                                    value={form.name}
                                    onChange={e => setForm(f => ({ ...f, name: e.target.value }))}
                                    required
                                />
                            </div>
                            <div className="w-40">
                                <Label className="text-zinc-400 text-xs mb-1 block">Default Status</Label>
                                <Select value={form.default_status} onValueChange={v => setForm(f => ({ ...f, default_status: v }))}>
                                    <SelectTrigger className="bg-zinc-800 border-zinc-700 text-white">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent className="bg-zinc-900 border-zinc-700">
                                        <SelectItem value="active">Active</SelectItem>
                                        <SelectItem value="inactive">Inactive</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <Button type="submit" className="bg-violet-600 hover:bg-violet-500 text-white gap-2">
                                <Plus className="w-4 h-4" /> Add
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                {/* Table */}
                <Card className="bg-zinc-900 border-zinc-800">
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-zinc-800">
                                        <th className="text-left px-4 py-3 text-zinc-400 font-medium">Software</th>
                                        <th className="text-center px-4 py-3 text-zinc-400 font-medium">Total Devices</th>
                                        <th className="text-center px-4 py-3 text-zinc-400 font-medium">Active</th>
                                        <th className="text-center px-4 py-3 text-zinc-400 font-medium">Inactive</th>
                                        <th className="text-left px-4 py-3 text-zinc-400 font-medium">Default Status</th>
                                        <th className="text-left px-4 py-3 text-zinc-400 font-medium">Registered</th>
                                        <th className="text-right px-4 py-3 text-zinc-400 font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {softwares.data.length === 0 && (
                                        <tr><td colSpan={7} className="text-center py-12 text-zinc-500">No software registered yet.</td></tr>
                                    )}
                                    {softwares.data.map(sw => (
                                        <tr key={sw.id} className="border-b border-zinc-800/50 hover:bg-zinc-800/30 transition-colors">
                                            <td className="px-4 py-3 flex items-center gap-3">
                                                <div className="w-8 h-8 rounded-lg bg-zinc-800 flex items-center justify-center">
                                                    <Layers className="w-4 h-4 text-violet-400" />
                                                </div>
                                                <span className="text-white font-medium">{sw.name}</span>
                                            </td>
                                            <td className="px-4 py-3 text-center text-zinc-300">{sw.total_licenses}</td>
                                            <td className="px-4 py-3 text-center text-emerald-400">{sw.active_count}</td>
                                            <td className="px-4 py-3 text-center text-zinc-500">{sw.inactive_count}</td>
                                            <td className="px-4 py-3">
                                                <Select value={sw.default_status} onValueChange={v => updateStatus(sw, v)}>
                                                    <SelectTrigger className="w-28 h-7 text-xs bg-zinc-800 border-zinc-700 text-white">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent className="bg-zinc-900 border-zinc-700">
                                                        <SelectItem value="active">Active</SelectItem>
                                                        <SelectItem value="inactive">Inactive</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </td>
                                            <td className="px-4 py-3 text-zinc-500 text-xs">{sw.created_at}</td>
                                            <td className="px-4 py-3 text-right">
                                                <Button size="sm" variant="ghost"
                                                    className="text-red-400 hover:text-red-300 hover:bg-red-500/10 w-8 h-8 p-0"
                                                    onClick={() => destroy(sw)}>
                                                    <Trash2 className="w-3.5 h-3.5" />
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}

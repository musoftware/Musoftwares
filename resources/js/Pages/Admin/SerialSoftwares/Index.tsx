import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Separator } from '@/Components/ui/separator';
import { Layers, Plus, Trash2 } from 'lucide-react';

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
        router.post(route('admin.serial-softwares.store'), form, {
            onSuccess: () => setForm({ name: '', default_status: 'active' }),
        });
    };

    const updateStatus = (sw: Software, status: string) => {
        router.patch(route('admin.serial-softwares.status', sw.id), { status });
    };

    const destroy = (sw: Software) => {
        if (!confirm(`Delete software "${sw.name}"? This will also delete all associated device records.`)) return;
        router.delete(route('admin.serial-softwares.destroy', sw.id));
    };

    return (
        <AdminSidebarLayout title="Serial Softwares" header="Serial Softwares">
            <Head title="Serial Softwares" />

            <div className="p-6 space-y-6">

                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Serial Softwares</h1>
                    <p className="text-muted-foreground text-sm mt-1">
                        Software registry — auto-created on first API check-in
                    </p>
                </div>

                <Separator />

                {/* Add Software */}
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-sm font-semibold">Add Software Manually</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={store} className="flex flex-wrap gap-3 items-end">
                            <div className="flex-1 min-w-48">
                                <Label className="text-xs mb-1 block">Software Name</Label>
                                <Input
                                    placeholder="e.g. MyApp.exe"
                                    value={form.name}
                                    onChange={e => setForm(f => ({ ...f, name: e.target.value }))}
                                    required
                                />
                            </div>
                            <div className="w-40">
                                <Label className="text-xs mb-1 block">Default Status</Label>
                                <Select
                                    value={form.default_status}
                                    onValueChange={v => setForm(f => ({ ...f, default_status: v || 'active' }))}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="active">Active</SelectItem>
                                        <SelectItem value="inactive">Inactive</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <Button type="submit" className="gap-2">
                                <Plus className="w-4 h-4" /> Add
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                {/* Table */}
                <Card>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Software</TableHead>
                                    <TableHead className="text-center">Total Devices</TableHead>
                                    <TableHead className="text-center">Active</TableHead>
                                    <TableHead className="text-center">Inactive</TableHead>
                                    <TableHead>Default Status</TableHead>
                                    <TableHead>Registered</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {softwares.data.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={7} className="text-center py-12 text-muted-foreground">
                                            No software registered yet.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {softwares.data.map(sw => (
                                    <TableRow key={sw.id}>
                                        <TableCell>
                                            <div className="flex items-center gap-3">
                                                <div className="w-8 h-8 rounded-lg border flex items-center justify-center bg-muted">
                                                    <Layers className="w-4 h-4 text-muted-foreground" />
                                                </div>
                                                <span className="font-medium">{sw.name}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-center">{sw.total_licenses}</TableCell>
                                        <TableCell className="text-center">
                                            <span className="text-green-600 font-medium">{sw.active_count}</span>
                                        </TableCell>
                                        <TableCell className="text-center text-muted-foreground">
                                            {sw.inactive_count}
                                        </TableCell>
                                        <TableCell>
                                            <Select
                                                value={sw.default_status}
                                                onValueChange={v => updateStatus(sw, v || 'active')}
                                            >
                                                <SelectTrigger className="w-28 h-7 text-xs">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="active">Active</SelectItem>
                                                    <SelectItem value="inactive">Inactive</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </TableCell>
                                        <TableCell className="text-muted-foreground text-xs">
                                            {sw.created_at}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                className="text-red-600 hover:text-red-700 hover:bg-red-50 w-8 h-8 p-0"
                                                onClick={() => destroy(sw)}
                                            >
                                                <Trash2 className="w-3.5 h-3.5" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

            </div>
        </AdminSidebarLayout>
    );
}

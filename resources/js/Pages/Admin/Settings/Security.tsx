import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { ShieldAlert, Activity, Trash2, ShieldCheck } from 'lucide-react';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { toastSuccess, toastError } from '@/Components/ui/use-toast';
import { __ } from '@/lib/i18n';

export default function Security({ blockedIps, rateLimits }: any) {
    const [form, setForm] = useState({
        module: '',
        tenant_id: '',
        ip_address: '',
        max_requests: 60,
        decay_minutes: 1,
        is_active: true
    });

    const [deleteModal, setDeleteModal] = useState<{ open: boolean; type: 'ip' | 'rateLimit'; id: number | null }>({
        open: false,
        type: 'ip',
        id: null
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(route('admin.settings.security.rate-limits.store'), form as any, {
            onSuccess: () => {
                setForm({
                    module: '',
                    tenant_id: '',
                    ip_address: '',
                    max_requests: 60,
                    decay_minutes: 1,
                    is_active: true
                });
                toastSuccess(__('general.created') || 'Rate limit created');
            },
            onError: () => toastError(__('general.error_occurred') || 'Something went wrong'),
        });
    };

    const confirmDelete = () => {
        if (!deleteModal.id) return;
        const id = deleteModal.id;
        const isIp = deleteModal.type === 'ip';
        setDeleteModal({ open: false, type: 'ip', id: null });
        const routeName = isIp ? 'admin.settings.security.unblock-ip' : 'admin.settings.security.rate-limits.destroy';
        router.delete(route(routeName, id), {
            onSuccess: () => toastSuccess(isIp
                ? (__('general.ip_unblocked') || 'IP unblocked')
                : (__('general.deleted') || 'Rate limit deleted')),
            onError: () => toastError(__('general.error_occurred') || 'Something went wrong'),
        });
    };

    return (
        <AdminSidebarLayout title={__('general.security_rate_limits')} header="Security Settings">
            <Head title={__('general.security_settings')} />

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div className="space-y-6">
                    <div className="rounded-xl bg-white border border-gray-200 shadow-sm overflow-hidden">
                        <div className="flex items-center justify-between px-5 py-4 border-b border-gray-100 bg-gray-50">
                            <div className="flex items-center gap-2">
                                <Activity className="h-4 w-4 text-gray-500" />
                                <h2 className="text-sm font-semibold text-gray-800 uppercase tracking-wide">{__('general.add_rate_limit')}</h2>
                            </div>
                        </div>
                        <div className="p-5">
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div>
                                    <Label>Module / Route Prefix (e.g. 'api', 'erp')</Label>
                                    <Input
                                        value={form.module}
                                        onChange={e => setForm({ ...form, module: e.target.value })}
                                        required
                                        placeholder="api"
                                    />
                                </div>
                                <div>
                                    <Label>Tenant ID (Optional)</Label>
                                    <Input
                                        type="number"
                                        value={form.tenant_id}
                                        onChange={e => setForm({ ...form, tenant_id: e.target.value })}
                                        placeholder={__('general.leave_blank_for_all')}
                                    />
                                </div>
                                <div>
                                    <Label>IP Address (Optional)</Label>
                                    <Input
                                        value={form.ip_address}
                                        onChange={e => setForm({ ...form, ip_address: e.target.value })}
                                        placeholder={__('general.leave_blank_for_all')}
                                    />
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <Label>{__('general.max_requests')}</Label>
                                        <Input
                                            type="number"
                                            value={form.max_requests}
                                            onChange={e => setForm({ ...form, max_requests: Number(e.target.value) })}
                                            required
                                        />
                                    </div>
                                    <div>
                                        <Label>Decay (Minutes)</Label>
                                        <Input
                                            type="number"
                                            value={form.decay_minutes}
                                            onChange={e => setForm({ ...form, decay_minutes: Number(e.target.value) })}
                                            required
                                        />
                                    </div>
                                </div>
                                <Button type="submit" className="w-full">{__('general.create_rate_limit')}</Button>
                            </form>
                        </div>
                    </div>

                    <div className="rounded-xl bg-white border border-gray-200 shadow-sm overflow-hidden">
                        <div className="flex items-center gap-2 px-5 py-4 border-b border-gray-100 bg-gray-50">
                            <Activity className="h-4 w-4 text-gray-500" />
                            <h2 className="text-sm font-semibold text-gray-800 uppercase tracking-wide">{__('general.active_rate_limits')}</h2>
                        </div>
                        <div className="divide-y divide-gray-100">
                            {rateLimits.length === 0 ? (
                                <div className="p-5 text-sm text-gray-500 text-center">{__('general.no_custom_rate_limits_configured')}</div>
                            ) : (
                                rateLimits.map((limit: any) => (
                                    <div key={limit.id} className="p-4 flex items-center justify-end gap-4">
                                        <div>
                                            <p className="font-medium text-sm text-gray-900">{limit.module}</p>
                                            <p className="text-xs text-gray-500 mt-1">
                                                {limit.max_requests} reqs / {limit.decay_minutes} min
                                                {limit.tenant_id && ` | Tenant: ${limit.tenant_id}`}
                                                {limit.ip_address && ` | IP: ${limit.ip_address}`}
                                            </p>
                                        </div>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="text-red-500 hover:text-red-700 hover:bg-red-50"
                                            onClick={() => setDeleteModal({ open: true, type: 'rateLimit', id: limit.id })}
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                </div>

                <div>
                    <div className="rounded-xl bg-white border border-gray-200 shadow-sm overflow-hidden">
                        <div className="flex items-center gap-2 px-5 py-4 border-b border-gray-100 bg-gray-50">
                            <ShieldAlert className="h-4 w-4 text-red-500" />
                            <h2 className="text-sm font-semibold text-gray-800 uppercase tracking-wide">{__('general.blocked_ips')}</h2>
                        </div>
                        <div className="divide-y divide-gray-100">
                            {blockedIps.length === 0 ? (
                                <div className="p-5 flex flex-col items-center justify-center text-center">
                                    <ShieldCheck className="h-8 w-8 text-green-500 mb-2" />
                                    <p className="text-sm text-gray-500">{__('general.no_ips_are_currently_blocked_ecosystem_i')}</p>
                                </div>
                            ) : (
                                blockedIps.map((ip: any) => (
                                    <div key={ip.id} className="p-4 flex items-center justify-end gap-4">
                                        <div>
                                            <p className="font-medium text-sm text-gray-900 font-mono">{ip.ip_address}</p>
                                            <p className="text-xs text-gray-500 mt-1">{ip.reason}</p>
                                            <p className="text-xs text-gray-400 mt-0.5">
                                                Blocked until: {ip.blocked_until ? new Date(ip.blocked_until).toLocaleString() : 'Forever'}
                                            </p>
                                        </div>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            className="text-green-600 border-green-200 hover:bg-green-50"
                                            onClick={() => setDeleteModal({ open: true, type: 'ip', id: ip.id })}
                                        >
                                            {__('general.unblock')}</Button>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                </div>
            </div>

            <ConfirmModal
                isOpen={deleteModal.open}
                title={deleteModal.type === 'ip' ? 'Unblock IP' : 'Delete Rate Limit'}
                description={deleteModal.type === 'ip' ? 'Are you sure you want to unblock this IP address? It will have access to the system immediately.' : 'Are you sure you want to remove this rate limit?'}
                confirmLabel={deleteModal.type === 'ip' ? 'Yes, Unblock' : 'Yes, Delete'}
                onConfirm={confirmDelete}
                onCancel={() => setDeleteModal({ open: false, type: 'ip', id: null })}
            />
        </AdminSidebarLayout>
    );
}

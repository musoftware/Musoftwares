import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageShell } from '@/Components/ui/PageShell';
import { Button } from '@/Components/ui/button';
import { 
    Key, Laptop, CheckCircle2, AlertCircle, ArrowLeft, 
    Monitor, ShieldCheck, Clock, Calendar, HelpCircle 
} from 'lucide-react';

interface SoftwareLicense {
    id: number;
    software_name: string;
    status: string;
    expires_at: string | null;
    max_devices: number;
    created_at: string | null;
}

interface LinkedDevice {
    id: number;
    device_id: string;
    status: string;
    expires_at: string | null;
    machine_name?: string | null;
    os_version?: string | null;
    last_check_date?: string | null;
    software_name?: string | null;
}

interface MyLicensesProps {
    licenses: SoftwareLicense[];
    linkedDevices: LinkedDevice[];
}

export default function MyLicenses({ licenses = [], linkedDevices = [] }: MyLicensesProps) {
    return (
        <AuthenticatedLayout>
            <Head title="My Software Licenses & Devices | تراخيصي وأجهزتي" />

            <PageShell>
                <div className="space-y-8">
                    {/* Header */}
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-black/5 dark:border-white/10 pb-6">
                        <div>
                            <div className="flex items-center gap-2 mb-2">
                                <Link
                                    href={route('store.tools.index')}
                                    className="inline-flex items-center gap-1.5 text-xs text-[#0071e3] hover:underline font-semibold"
                                >
                                    <ArrowLeft className="w-3.5 h-3.5" />
                                    Back to Tools Store
                                </Link>
                            </div>
                            <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-[#1d1d1f] dark:text-[#f8fafc]">
                                My Software Licenses & Devices
                            </h1>
                            <p className="text-sm text-[#1d1d1f]/60 dark:text-[#f8fafc]/60 mt-1 max-w-2xl font-sans">
                                Active desktop software licenses registered to your email, along with all authenticated computers and devices.
                            </p>
                        </div>

                        <div>
                            <Link
                                href={route('store.tools.index')}
                                className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#0071e3] text-white text-xs sm:text-sm font-semibold hover:bg-[#0077ed] transition-all shadow-xs"
                            >
                                Browse More Tools
                            </Link>
                        </div>
                    </div>

                    {/* How Activation Works Guide */}
                    <div className="bg-blue-50/60 dark:bg-blue-950/20 border border-blue-200/60 dark:border-blue-800/40 rounded-2xl p-5">
                        <div className="flex items-start gap-3.5">
                            <div className="w-9 h-9 rounded-xl bg-blue-500/10 dark:bg-blue-500/20 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0 mt-0.5">
                                <HelpCircle className="w-5 h-5" />
                            </div>
                            <div className="space-y-1 text-xs text-[#1d1d1f]/80 dark:text-zinc-300 font-sans">
                                <h4 className="font-bold text-sm text-[#1d1d1f] dark:text-white">
                                    How Automatic Device Activation Works
                                </h4>
                                <ol className="list-decimal list-inside space-y-1 pt-1 text-xs text-[#1d1d1f]/70 dark:text-zinc-400">
                                    <li>Launch your software tool on your Windows PC (e.g. WAContactsExtract).</li>
                                    <li>When prompted by the activation dialog, enter your registered account email.</li>
                                    <li>The software connects securely to the server, validates your active license, and unlocks your computer immediately.</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    {/* Licenses Section */}
                    <div className="space-y-4">
                        <h2 className="text-lg font-bold text-[#1d1d1f] dark:text-white flex items-center gap-2">
                            <Key className="w-5 h-5 text-[#0071e3]" />
                            Active Software Licenses ({licenses.length})
                        </h2>

                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                            {licenses.map((lic) => {
                                const isActive = lic.status === 'active';
                                return (
                                    <div
                                        key={lic.id}
                                        className="bg-white dark:bg-zinc-900/90 border border-black/5 dark:border-white/10 rounded-2xl p-5 shadow-xs space-y-4"
                                    >
                                        <div className="flex items-start justify-between gap-2">
                                            <div>
                                                <h3 className="font-bold text-base text-[#1d1d1f] dark:text-white">
                                                    {lic.software_name}
                                                </h3>
                                                <p className="text-xs text-[#1d1d1f]/50 dark:text-zinc-400 font-mono mt-0.5">
                                                    License #{lic.id}
                                                </p>
                                            </div>

                                            <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold ${
                                                isActive
                                                    ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60'
                                                    : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200/60 dark:border-rose-800/60'
                                            }`}>
                                                {isActive ? <CheckCircle2 className="w-3 h-3 text-emerald-600" /> : <AlertCircle className="w-3 h-3 text-rose-600" />}
                                                {isActive ? 'Active' : 'Inactive'}
                                            </span>
                                        </div>

                                        <div className="space-y-2 pt-2 border-t border-black/5 dark:border-white/10 text-xs font-sans">
                                            <div className="flex items-center justify-between text-[#1d1d1f]/70 dark:text-zinc-400">
                                                <span>Allowed Devices:</span>
                                                <span className="font-semibold text-[#1d1d1f] dark:text-zinc-200">{lic.max_devices} Computers</span>
                                            </div>
                                            <div className="flex items-center justify-between text-[#1d1d1f]/70 dark:text-zinc-400">
                                                <span>Expires On:</span>
                                                <span className="font-semibold text-emerald-600 dark:text-emerald-400">
                                                    {lic.expires_at ? new Date(lic.expires_at).toLocaleDateString() : 'Lifetime'}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>

                        {licenses.length === 0 && (
                            <div className="text-center py-10 bg-white dark:bg-zinc-900/60 rounded-2xl border border-black/5 dark:border-white/10">
                                <Key className="w-10 h-10 text-[#1d1d1f]/20 dark:text-zinc-600 mx-auto mb-2" />
                                <h4 className="text-sm font-semibold text-[#1d1d1f] dark:text-white">No active software licenses yet</h4>
                                <p className="text-xs text-[#1d1d1f]/50 dark:text-zinc-400 mt-1">Visit the Tools Store to get your first software license.</p>
                            </div>
                        )}
                    </div>

                    {/* Linked Hardware Devices */}
                    <div className="space-y-4 pt-4">
                        <h2 className="text-lg font-bold text-[#1d1d1f] dark:text-white flex items-center gap-2">
                            <Laptop className="w-5 h-5 text-emerald-600" />
                            Connected Devices & Machines ({linkedDevices.length})
                        </h2>

                        <div className="bg-white dark:bg-zinc-900/90 border border-black/5 dark:border-white/10 rounded-2xl overflow-hidden shadow-xs">
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-xs">
                                    <thead className="bg-[#f5f5f7] dark:bg-zinc-800/60 border-b border-black/5 dark:border-white/10 font-semibold text-[#1d1d1f]/70 dark:text-zinc-300">
                                        <tr>
                                            <th className="px-4 py-3">Device / Machine</th>
                                            <th className="px-4 py-3">Hardware ID</th>
                                            <th className="px-4 py-3">Status</th>
                                            <th className="px-4 py-3">OS Version</th>
                                            <th className="px-4 py-3">Expiration Date</th>
                                            <th className="px-4 py-3">Last Check-In</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-black/5 dark:divide-white/10">
                                        {linkedDevices.map((dev) => {
                                            const isActive = dev.status === 'active';
                                            return (
                                                <tr key={dev.id} className="hover:bg-black/[0.02] dark:hover:bg-white/[0.02]">
                                                    <td className="px-4 py-3 font-semibold text-[#1d1d1f] dark:text-zinc-100">
                                                        <div className="flex items-center gap-2">
                                                            <Monitor className="w-4 h-4 text-[#0071e3]" />
                                                            <span>{dev.machine_name || 'Windows PC'}</span>
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-3 font-mono text-[#1d1d1f]/70 dark:text-zinc-400">
                                                        {dev.device_id}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold ${
                                                            isActive
                                                                ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60'
                                                                : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200/60 dark:border-rose-800/60'
                                                        }`}>
                                                            {isActive ? 'Active' : 'Inactive'}
                                                        </span>
                                                    </td>
                                                    <td className="px-4 py-3 text-[#1d1d1f]/60 dark:text-zinc-400 font-sans">
                                                        {dev.os_version || 'Windows'}
                                                    </td>
                                                    <td className="px-4 py-3 font-mono text-emerald-600 dark:text-emerald-400">
                                                        {dev.expires_at ? new Date(dev.expires_at).toLocaleDateString() : 'Permanent'}
                                                    </td>
                                                    <td className="px-4 py-3 text-[#1d1d1f]/50 dark:text-zinc-400 font-sans">
                                                        {dev.last_check_date ? new Date(dev.last_check_date).toLocaleString() : 'Recently'}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>

                            {linkedDevices.length === 0 && (
                                <div className="text-center py-10">
                                    <Monitor className="w-8 h-8 text-[#1d1d1f]/20 dark:text-zinc-600 mx-auto mb-2" />
                                    <p className="text-xs text-[#1d1d1f]/50 dark:text-zinc-400">
                                        No devices linked yet. Launch any software tool and type your email to automatically register your computer.
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </PageShell>
        </AuthenticatedLayout>
    );
}

import React, { useState } from 'react';
import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { Head, useForm, router } from '@inertiajs/react';
import {
    Key,
    Plus,
    Upload,
    CheckCircle2,
    Clock,
    Search,
    Copy,
    Check,
    Eye,
    EyeOff
} from 'lucide-react';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { MetricCard } from '@/Components/ui/MetricCard';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { SellerNav } from '@/Components/Marketplace/Seller/SellerNav';
import { __ } from '@/lib/i18n';

interface ServiceSerial {
    id: number;
    service_id: number;
    serial_code: string;
    is_used: boolean;
    used_at?: string;
    created_at: string;
    service?: {
        id: number;
        title: string;
    };
    used_by?: {
        id: number;
        name: string;
        email: string;
    };
}

interface ServiceOption {
    id: number;
    title: string;
    generate_serials: boolean;
}

interface SerialsPageProps {
    serials: {
        data: ServiceSerial[];
        links: any[];
        total: number;
    };
    services: ServiceOption[];
}

export default function SellerSerials({ serials, services }: SerialsPageProps) {
    const [showSingleModal, setShowSingleModal] = useState(false);
    const [showBulkModal, setShowBulkModal] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [revealedIds, setRevealedIds] = useState<Record<number, boolean>>({});
    const [copiedId, setCopiedId] = useState<number | null>(null);

    const singleForm = useForm({
        service_id: services.length > 0 ? services[0].id : '',
        serial_code: '',
    });

    const bulkForm = useForm({
        service_id: services.length > 0 ? services[0].id : '',
        serial_codes_text: '',
    });

    const totalStock = serials.total || serials.data.length;
    const availableCount = serials.data.filter((s) => !s.is_used).length;
    const claimedCount = serials.data.filter((s) => s.is_used).length;

    const handleSingleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        singleForm.post('/seller/serials', {
            onSuccess: () => {
                singleForm.reset('serial_code');
                setShowSingleModal(false);
            },
        });
    };

    const handleBulkSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const codesArray = bulkForm.data.serial_codes_text
            .split('\n')
            .map((c) => c.trim())
            .filter((c) => c.length > 0);

        router.post(
            '/seller/serials/bulk',
            {
                service_id: bulkForm.data.service_id,
                serial_codes: codesArray,
            },
            {
                onSuccess: () => {
                    bulkForm.reset('serial_codes_text');
                    setShowBulkModal(false);
                },
            }
        );
    };

    const toggleReveal = (id: number) => {
        setRevealedIds((prev) => ({ ...prev, [id]: !prev[id] }));
    };

    const handleCopy = (id: number, code: string) => {
        navigator.clipboard.writeText(code);
        setCopiedId(id);
        setTimeout(() => setCopiedId(null), 2000);
    };

    const filteredSerials = serials.data.filter((s) => {
        const matchesSearch =
            s.serial_code.toLowerCase().includes(searchTerm.toLowerCase()) ||
            (s.service?.title && s.service.title.toLowerCase().includes(searchTerm.toLowerCase()));
        return matchesSearch;
    });

    return (
        <MarketplaceLayout>
            <Head title={__('general.digital_keys') || 'Digital Key Inventory'} />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <ModulePageHeader
                        title={__('general.digital_keys') || 'Digital Key Inventory'}
                        description={__('general.manage_digital_serials_description') || 'Manage instant-delivery license keys and digital vouchers for your service packages.'}
                    />

                    <div className="flex items-center gap-3">
                        <button
                            onClick={() => setShowSingleModal(true)}
                            className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800 transition shadow-sm"
                        >
                            <Plus className="w-4 h-4" />
                            {__('general.add_single_key') || 'Add Single Key'}
                        </button>
                        <button
                            onClick={() => setShowBulkModal(true)}
                            className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition shadow-sm"
                        >
                            <Upload className="w-4 h-4" />
                            {__('general.bulk_import') || 'Bulk Import'}
                        </button>
                    </div>
                </div>

                <SellerNav />

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <MetricCard
                        label={__('general.total_keys') || 'Total Inventory Keys'}
                        value={totalStock}
                        icon={Key}
                    />
                    <MetricCard
                        label={__('general.available_stock') || 'Available for Claim'}
                        value={availableCount}
                        icon={CheckCircle2}
                    />
                    <MetricCard
                        label={__('general.claimed_keys') || 'Sold / Delivered Keys'}
                        value={claimedCount}
                        icon={Clock}
                    />
                </div>

                <OperationalCard
                    title={__('general.key_inventory_list') || 'Serial Key Records'}
                    description={__('general.key_inventory_sub') || 'Real-time stock of digital codes assigned to your active services.'}
                >
                    <div className="p-4 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div className="relative w-full sm:w-80">
                            <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                            <input
                                type="text"
                                placeholder={__('general.search_serials') || 'Search key code or service...'}
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white"
                            />
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-slate-50 border-b border-slate-100 text-slate-500 uppercase tracking-wider text-xs font-semibold">
                                <tr>
                                    <th className="px-6 py-3">{__('general.serial_code') || 'Serial Key / Code'}</th>
                                    <th className="px-6 py-3">{__('general.service') || 'Associated Service'}</th>
                                    <th className="px-6 py-3">{__('general.status') || 'Status'}</th>
                                    <th className="px-6 py-3">{__('general.claimed_by') || 'Claimed By / Buyer'}</th>
                                    <th className="px-6 py-3 text-right">{__('general.actions') || 'Actions'}</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {filteredSerials.length === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="px-6 py-12 text-center text-slate-400">
                                            <div className="flex flex-col items-center justify-center space-y-2">
                                                <Key className="w-8 h-8 text-slate-300 stroke-[1.5]" />
                                                <p className="text-sm">{__('general.no_serials_found') || 'No digital serial keys found in inventory.'}</p>
                                            </div>
                                        </td>
                                    </tr>
                                ) : (
                                    filteredSerials.map((serial) => {
                                        const isRevealed = !!revealedIds[serial.id];
                                        const maskedCode = serial.serial_code.replace(/.(?=.{4})/g, '•');

                                        return (
                                            <tr key={serial.id} className="hover:bg-slate-50/50 transition">
                                                <td className="px-6 py-4 font-mono font-medium text-slate-900">
                                                    <div className="flex items-center gap-2">
                                                        <span>{isRevealed ? serial.serial_code : maskedCode}</span>
                                                        <button
                                                            onClick={() => toggleReveal(serial.id)}
                                                            className="text-slate-400 hover:text-slate-600 p-1"
                                                            title="Toggle Visibility"
                                                        >
                                                            {isRevealed ? <EyeOff className="w-3.5 h-3.5" /> : <Eye className="w-3.5 h-3.5" />}
                                                        </button>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 text-slate-700 font-medium">
                                                    {serial.service?.title || 'General Service'}
                                                </td>
                                                <td className="px-6 py-4">
                                                    {serial.is_used ? (
                                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                                            {__('general.claimed') || 'Claimed / Used'}
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                            {__('general.available') || 'Available Stock'}
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 text-slate-600">
                                                    {serial.used_by ? (
                                                        <div>
                                                            <div className="font-medium text-slate-900">{serial.used_by.name}</div>
                                                            <div className="text-xs text-slate-400">{serial.used_by.email}</div>
                                                        </div>
                                                    ) : (
                                                        <span className="text-slate-400 text-xs">—</span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 text-right">
                                                    <button
                                                        onClick={() => handleCopy(serial.id, serial.serial_code)}
                                                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-medium transition"
                                                    >
                                                        {copiedId === serial.id ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5 text-slate-400" />}
                                                        {copiedId === serial.id ? __('general.copied') || 'Copied' : __('general.copy') || 'Copy'}
                                                    </button>
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>
                </OperationalCard>

                {/* Single Add Modal */}
                {showSingleModal && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
                        <div className="bg-white rounded-2xl max-w-md w-full p-6 space-y-6 shadow-2xl">
                            <div className="flex items-center justify-between">
                                <h3 className="text-lg font-semibold text-slate-900">{__('general.add_single_key') || 'Add Single Serial Key'}</h3>
                                <button onClick={() => setShowSingleModal(false)} className="text-slate-400 hover:text-slate-600">✕</button>
                            </div>

                            <form onSubmit={handleSingleSubmit} className="space-y-4">
                                <div>
                                    <label className="block text-xs font-semibold uppercase text-slate-500 mb-1">{__('general.service') || 'Service'}</label>
                                    <select
                                        value={singleForm.data.service_id}
                                        onChange={(e) => singleForm.setData('service_id', Number(e.target.value))}
                                        className="w-full rounded-xl border border-slate-200 py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                        required
                                    >
                                        {services.map((srv) => (
                                            <option key={srv.id} value={srv.id}>{srv.title}</option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold uppercase text-slate-500 mb-1">{__('general.serial_code') || 'Serial Code'}</label>
                                    <input
                                        type="text"
                                        placeholder="e.g. MSFT-2026-KEY-9981"
                                        value={singleForm.data.serial_code}
                                        onChange={(e) => singleForm.setData('serial_code', e.target.value)}
                                        className="w-full rounded-xl border border-slate-200 py-2.5 px-3 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                        required
                                    />
                                </div>

                                <div className="flex items-center justify-end gap-3 pt-2">
                                    <button
                                        type="button"
                                        onClick={() => setShowSingleModal(false)}
                                        className="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800"
                                    >
                                        {__('general.cancel') || 'Cancel'}
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={singleForm.processing}
                                        className="px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition"
                                    >
                                        {__('general.save_key') || 'Save Key'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}

                {/* Bulk Import Modal */}
                {showBulkModal && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
                        <div className="bg-white rounded-2xl max-w-lg w-full p-6 space-y-6 shadow-2xl">
                            <div className="flex items-center justify-between">
                                <h3 className="text-lg font-semibold text-slate-900">{__('general.bulk_import_keys') || 'Bulk Import Digital Serials'}</h3>
                                <button onClick={() => setShowBulkModal(false)} className="text-slate-400 hover:text-slate-600">✕</button>
                            </div>

                            <form onSubmit={handleBulkSubmit} className="space-y-4">
                                <div>
                                    <label className="block text-xs font-semibold uppercase text-slate-500 mb-1">{__('general.service') || 'Service'}</label>
                                    <select
                                        value={bulkForm.data.service_id}
                                        onChange={(e) => bulkForm.setData('service_id', Number(e.target.value))}
                                        className="w-full rounded-xl border border-slate-200 py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                        required
                                    >
                                        {services.map((srv) => (
                                            <option key={srv.id} value={srv.id}>{srv.title}</option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold uppercase text-slate-500 mb-1">{__('general.serial_codes_list') || 'Serial Codes (One Code Per Line)'}</label>
                                    <textarea
                                        rows={6}
                                        placeholder={"KEY-AAAA-1111\nKEY-BBBB-2222\nKEY-CCCC-3333"}
                                        value={bulkForm.data.serial_codes_text}
                                        onChange={(e) => bulkForm.setData('serial_codes_text', e.target.value)}
                                        className="w-full rounded-xl border border-slate-200 p-3 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                        required
                                    />
                                    <p className="text-xs text-slate-400 mt-1">Paste multiple keys separated by new lines.</p>
                                </div>

                                <div className="flex items-center justify-end gap-3 pt-2">
                                    <button
                                        type="button"
                                        onClick={() => setShowBulkModal(false)}
                                        className="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800"
                                    >
                                        {__('general.cancel') || 'Cancel'}
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={bulkForm.processing}
                                        className="px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition"
                                    >
                                        {__('general.import_keys') || 'Import Keys'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}
            </div>
        </MarketplaceLayout>
    );
}

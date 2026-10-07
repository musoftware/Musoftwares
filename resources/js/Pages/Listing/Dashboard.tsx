import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Briefcase, MapPin, Calendar, Edit2, Trash2, Shield, Eye, DollarSign } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { useConfirm } from '@/hooks/useConfirm';

interface ListingItem {
    id: number;
    title: string;
    description: string;
    price: number;
    currency: string;
    city: string;
    phone: string;
    email: string;
    images: string[];
    status: string;
    created_at: string;
}

interface DashboardProps {
    listings: ListingItem[];
}

export default function Dashboard({ listings }: DashboardProps) {
    const { confirm, confirmDialog } = useConfirm();

    const handleDelete = async (id: number, title: string) => {
        const accepted = await confirm({
            title: __('listing.dashboard_delete_title'),
            description: __('listing.dashboard_delete_confirm', { title }),
            variant: 'danger',
        });
        if (!accepted) return;
        router.delete(`/listing/${id}`);
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="font-semibold text-xl text-slate-100 leading-tight text-right font-sans">
                    {__('listing.dashboard_header')}
                </h2>
            }
        >
            <Head title={__('listing.dashboard_page_title')} />

            <div className="py-12 bg-slate-950 min-h-screen text-slate-100 px-4 sm:px-6 lg:px-8">
                <div className="max-w-7xl mx-auto space-y-8">
                    
                    {/* Welcome Banner */}
                    <div className="bg-gradient-to-r from-slate-900 to-indigo-950 border border-slate-800 p-6 rounded-2xl shadow-xl flex flex-col md:flex-row items-center justify-between gap-6">
                        <div className="space-y-2 text-right">
                            <h3 className="text-2xl font-bold text-slate-100">
                                {__('listing.dashboard_welcome')}
                            </h3>
                            <p className="text-slate-400 text-sm max-w-2xl leading-relaxed">
                                {__('listing.dashboard_welcome_desc')}
                            </p>
                        </div>
                        <div className="shrink-0 flex items-center gap-2 bg-slate-950/60 border border-slate-800/80 px-4 py-2 rounded-xl text-xs text-indigo-400 font-semibold">
                            <Shield className="h-4 w-4" />
                            {__('listing.dashboard_verified_account')}
                        </div>
                    </div>

                    {/* Listings Table / Cards */}
                    <div className="bg-slate-900/40 border border-slate-800/80 rounded-2xl overflow-hidden shadow-2xl backdrop-blur-xl">
                        <div className="p-6 border-b border-slate-800/60 flex items-center justify-between">
                            <h4 className="text-lg font-bold text-slate-200">
                                {__('listing.dashboard_list_title', { count: listings.length })}
                            </h4>
                        </div>

                        {listings.length === 0 ? (
                            <div className="text-center py-20">
                                <Briefcase className="h-16 w-16 mx-auto text-slate-700 mb-4" />
                                <h5 className="text-lg font-bold text-slate-350">{__('listing.dashboard_empty_title')}</h5>
                                <p className="text-slate-500 text-sm mt-1">{__('listing.dashboard_empty_hint')}</p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-right border-collapse">
                                    <thead>
                                        <tr className="border-b border-slate-800 text-xs text-slate-400 font-bold uppercase bg-slate-900/20">
                                            <th className="px-6 py-4">{__('listing.col_listing')}</th>
                                            <th className="px-6 py-4">{__('listing.col_city')}</th>
                                            <th className="px-6 py-4">{__('listing.col_budget')}</th>
                                            <th className="px-6 py-4">{__('listing.col_status')}</th>
                                            <th className="px-6 py-4">{__('listing.col_added')}</th>
                                            <th className="px-6 py-4 text-center">{__('listing.col_actions')}</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-850">
                                        {listings.map((item) => (
                                            <tr key={item.id} className="hover:bg-slate-900/30 transition-colors">
                                                <td className="px-6 py-4">
                                                    <div className="flex items-center gap-4">
                                                        <div className="w-12 h-12 bg-slate-950 border border-slate-850 rounded-lg flex items-center justify-center overflow-hidden shrink-0">
                                                            {item.images && item.images.length > 0 ? (
                                                                <img src={item.images[0]} alt="" className="w-full h-full object-cover" />
                                                            ) : (
                                                                <Briefcase className="h-6 w-6 text-indigo-400" />
                                                            )}
                                                        </div>
                                                        <div>
                                                            <div className="font-bold text-slate-200">{item.title}</div>
                                                            <div className="text-xs text-slate-500 line-clamp-1 mt-0.5">{item.description}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 text-slate-300">
                                                    <span className="flex items-center gap-1 text-sm">
                                                        <MapPin className="h-4 w-4 text-slate-500" />
                                                        {item.city || __('listing.not_specified')}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 font-semibold text-slate-300">
                                                    <span className="flex items-center gap-0.5 text-sm">
                                                        <DollarSign className="h-4 w-4 text-slate-500" />
                                                        {item.price > 0 ? `${item.price} ${item.currency}` : __('listing.negotiable')}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className={`inline-flex px-2.5 py-1 text-xs font-semibold rounded-full border ${
                                                        item.status === 'active'
                                                            ? 'bg-emerald-950/30 border-emerald-900/50 text-emerald-400'
                                                            : item.status === 'draft'
                                                            ? 'bg-slate-950/40 border-slate-800 text-slate-400'
                                                            : 'bg-rose-950/30 border-rose-900/50 text-rose-400'
                                                    }`}>
                                                        {item.status === 'active' ? __('listing.status_active') : item.status === 'draft' ? __('listing.status_draft') : __('listing.status_archived')}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 text-slate-400 text-sm">
                                                    <span className="flex items-center gap-1">
                                                        <Calendar className="h-4 w-4 text-slate-500" />
                                                        {item.created_at}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="flex items-center justify-center gap-3">
                                                        <Link
                                                            href={`/listing/${item.id}`}
                                                            className="p-2 hover:bg-slate-800 rounded-lg text-slate-400 hover:text-slate-100 transition-colors"
                                                            title={__('listing.preview')}
                                                            aria-label={__('listing.preview')}
                                                        >
                                                            <Eye className="h-4 w-4" />
                                                        </Link>
                                                        <Link
                                                            href={`/listing/${item.id}/edit`}
                                                            className="p-2 hover:bg-slate-800 rounded-lg text-blue-400 hover:text-blue-300 transition-colors"
                                                            title={__('listing.edit')}
                                                            aria-label={__('listing.edit')}
                                                        >
                                                            <Edit2 className="h-4 w-4" />
                                                        </Link>
                                                        <button
                                                            onClick={() => handleDelete(item.id, item.title)}
                                                            className="p-2 hover:bg-slate-800 rounded-lg text-rose-500 hover:text-rose-400 transition-colors"
                                                            title={__('listing.delete')}
                                                            aria-label={__('listing.delete')}
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </div>
            </div>
            {confirmDialog}
        </AuthenticatedLayout>
    );
}

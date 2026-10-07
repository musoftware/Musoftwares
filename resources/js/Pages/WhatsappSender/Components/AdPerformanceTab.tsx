import { __ } from '@/lib/i18n';
import React, { useState, useEffect } from 'react';
import axios from 'axios';
import {
    Sparkles,
    TrendingUp,
    MessageSquare,
    DollarSign,
    RefreshCw,
    BarChart3,
    Users
} from 'lucide-react';

interface AdPerformanceItem {
    source_id: string;
    headline: string;
    body: string | null;
    total_chats: number;
    unique_leads_count: number;
    last_lead_at: string;
}

interface Props {
    businessId: number;
    perMessageFee: string;
}

export default function AdPerformanceTab({ businessId, perMessageFee }: Props) {
    const [loading, setLoading] = useState(false);
    const [totalLeads, setTotalLeads] = useState(0);
    const [activeAdsCount, setActiveAdsCount] = useState(0);
    const [ads, setAds] = useState<AdPerformanceItem[]>([]);

    const fetchAdPerformance = async () => {
        setLoading(true);
        try {
            const res = await axios.get(`/whatsapp-sender/businesses/${businessId}/ad-performance`);
            if (res.data) {
                setTotalLeads(res.data.total_ctwa_leads || 0);
                setActiveAdsCount(res.data.active_ads_count || 0);
                setAds(res.data.ads || []);
            }
        } catch (err) {
            console.error('Failed to fetch CTWA ad performance:', err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchAdPerformance();
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [businessId]);

    const formatTimestamp = (dateStr: string) => {
        try {
            return new Date(dateStr).toLocaleString([], { dateStyle: 'short', timeStyle: 'short' });
        } catch {
            return dateStr;
        }
    };

    const estimatedSavings = (totalLeads * 0.0010).toFixed(4);

    return (
        <div className="space-y-6 text-zinc-900 dark:text-zinc-100">
            {/* Header Banner */}
            <div className="bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/40 rounded-2xl p-6 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div className="space-y-2">
                    <div className="flex items-center gap-2">
                        <span className="px-3 py-1 rounded-full bg-amber-200 dark:bg-amber-900/60 text-amber-900 dark:text-amber-200 border border-amber-300 dark:border-amber-800 text-xs font-bold flex items-center gap-1.5">
                            <Sparkles className="w-4 h-4 text-amber-600 dark:text-amber-400" />
                            {__('whatsapp.adperf_badge')}
                        </span>
                    </div>
                    <h2 className="text-2xl font-black text-amber-950 dark:text-amber-100 tracking-tight">{__('whatsapp.adperf_title')}</h2>
                    <p className="text-xs text-amber-900/80 dark:text-amber-200/80 max-w-2xl leading-relaxed">
                        {__('whatsapp.adperf_desc')}
                    </p>
                </div>

                <button
                    onClick={fetchAdPerformance}
                    className="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs rounded-xl flex items-center gap-2 transition-all shadow-sm shrink-0"
                >
                    <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
                    {__('whatsapp.adperf_refresh')}
                </button>
            </div>

            {/* KPI Cards */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 space-y-2 shadow-sm">
                    <div className="flex items-center justify-between text-zinc-500 text-xs font-semibold">
                        <span>{__('whatsapp.adperf_total_leads')}</span>
                        <Users className="w-5 h-5 text-amber-500" />
                    </div>
                    <h3 className="text-3xl font-black text-zinc-950 dark:text-zinc-50 font-mono">{totalLeads}</h3>
                    <p className="text-[11px] text-amber-600 dark:text-amber-400 font-medium">{__('whatsapp.adperf_inbound')}</p>
                </div>

                <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 space-y-2 shadow-sm">
                    <div className="flex items-center justify-between text-zinc-500 text-xs font-semibold">
                        <span>{__('whatsapp.adperf_active_campaigns')}</span>
                        <BarChart3 className="w-5 h-5 text-emerald-500" />
                    </div>
                    <h3 className="text-3xl font-black text-emerald-600 dark:text-emerald-400 font-mono">{activeAdsCount}</h3>
                    <p className="text-[11px] text-zinc-500">{__('whatsapp.adperf_unique_ids')}</p>
                </div>

                <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 space-y-2 shadow-sm">
                    <div className="flex items-center justify-between text-zinc-500 text-xs font-semibold">
                        <span>{__('whatsapp.adperf_savings')}</span>
                        <DollarSign className="w-5 h-5 text-sky-500" />
                    </div>
                    <h3 className="text-3xl font-black text-sky-600 dark:text-sky-400 font-mono">${estimatedSavings} USD</h3>
                    <p className="text-[11px] text-zinc-500">{__('whatsapp.adperf_savings_desc')}</p>
                </div>
            </div>

            {/* Ad Performance Table */}
            <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 space-y-4 shadow-sm">
                <h3 className="text-sm font-bold text-zinc-950 dark:text-zinc-50 flex items-center gap-2">
                    <TrendingUp className="w-4 h-4 text-amber-500" />
                    {__('whatsapp.adperf_breakdown')}
                </h3>

                <div className="overflow-x-auto">
                    <table className="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr className="border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 font-semibold uppercase tracking-wider text-[10px]">
                                <th className="py-3 px-4">{__('whatsapp.adperf_col_headline')}</th>
                                <th className="py-3 px-4">{__('whatsapp.adperf_col_ad_id')}</th>
                                <th className="py-3 px-4 text-center">{__('whatsapp.adperf_col_inquiries')}</th>
                                <th className="py-3 px-4 text-center">{__('whatsapp.adperf_col_unique')}</th>
                                <th className="py-3 px-4 text-right">{__('whatsapp.adperf_col_last')}</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-zinc-200 dark:divide-zinc-800/60">
                            {ads.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="py-12 text-center text-zinc-500">
                                        {__('whatsapp.adperf_empty')}
                                    </td>
                                </tr>
                            ) : (
                                ads.map(ad => (
                                    <tr key={ad.source_id} className="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
                                        <td className="py-3.5 px-4 font-semibold text-zinc-900 dark:text-zinc-100">
                                            <div className="flex items-center gap-2">
                                                <span className="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                                <div>
                                                    <div className="text-zinc-900 dark:text-zinc-200">{ad.headline}</div>
                                                    {ad.body && <div className="text-[11px] font-normal text-zinc-500 dark:text-zinc-400 line-clamp-1">{ad.body}</div>}
                                                </div>
                                            </div>
                                        </td>
                                        <td className="py-3.5 px-4 font-mono text-amber-600 dark:text-amber-400">
                                            {ad.source_id}
                                        </td>
                                        <td className="py-3.5 px-4 text-center font-bold text-zinc-900 dark:text-zinc-100 font-mono">
                                            {ad.total_chats}
                                        </td>
                                        <td className="py-3.5 px-4 text-center font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                                            {ad.unique_leads_count}
                                        </td>
                                        <td className="py-3.5 px-4 text-right text-zinc-500 font-mono">
                                            {formatTimestamp(ad.last_lead_at)}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}

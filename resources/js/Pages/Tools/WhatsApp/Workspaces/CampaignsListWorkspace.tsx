import React, { useState, useEffect } from 'react';
import { LayoutDashboard, Play, CheckCircle2, XCircle, RefreshCw, Eye } from 'lucide-react';

export default function CampaignsListWorkspace({ t, callRPC, onViewReport }: any) {
    const [campaigns, setCampaigns] = useState<any[]>([]);
    const [loading, setLoading] = useState(false);

    const fetchCampaigns = async () => {
        setLoading(true);
        try {
            const res: any = await callRPC('getCampaigns');
            setCampaigns(res.campaigns || []);
        } catch (err: any) {
            console.error('Failed to fetch campaigns', err);
        }
        setLoading(false);
    };

    useEffect(() => {
        fetchCampaigns();
    }, []);

    return (
        <div className="space-y-6 animate-in fade-in duration-300">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-xl font-bold tracking-tight text-slate-850">Campaigns History</h2>
                    <p className="text-xs text-slate-400 mt-1">Review the performance and status of your past campaigns.</p>
                </div>
                <button onClick={fetchCampaigns} disabled={loading} className="p-2 hover:bg-slate-100 border border-slate-200 text-slate-500 rounded-xl transition-all">
                    <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
                </button>
            </div>

            <div className="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm whitespace-nowrap">
                        <thead className="bg-slate-50 border-b border-slate-100 text-slate-500 font-semibold uppercase text-[10px] tracking-wider">
                            <tr>
                                <th className="px-6 py-4">Campaign Name</th>
                                <th className="px-6 py-4">Status</th>
                                <th className="px-6 py-4">Progress</th>
                                <th className="px-6 py-4">Sent</th>
                                <th className="px-6 py-4">Failed</th>
                                <th className="px-6 py-4">Date</th>
                                <th className="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {campaigns.map(c => {
                                const percent = c.total_contacts ? Math.round(((c.sent_count + c.failed_count) / c.total_contacts) * 100) : 0;
                                return (
                                    <tr key={c.id} className="hover:bg-slate-50 transition-colors">
                                        <td className="px-6 py-4 font-bold text-slate-800">{c.name}</td>
                                        <td className="px-6 py-4">
                                            <span className={`px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider ${
                                                c.status === 'completed' ? 'bg-emerald-50 text-emerald-600' :
                                                c.status === 'running' ? 'bg-blue-50 text-blue-600 animate-pulse' :
                                                'bg-slate-100 text-slate-500'
                                            }`}>
                                                {c.status}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-2">
                                                <div className="w-24 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                                    <div className="h-full bg-teal-500 transition-all" style={{ width: `${percent}%` }} />
                                                </div>
                                                <span className="text-[10px] font-bold text-slate-500">{percent}%</span>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-emerald-600 font-bold flex items-center gap-1.5">
                                            <CheckCircle2 className="w-3.5 h-3.5" />
                                            {c.sent_count}
                                        </td>
                                        <td className="px-6 py-4 text-rose-500 font-bold">
                                            <div className="flex items-center gap-1.5">
                                                <XCircle className="w-3.5 h-3.5" />
                                                {c.failed_count}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-slate-400 text-xs">
                                            {new Date(c.created_at).toLocaleString()}
                                        </td>
                                        <td className="px-6 py-4 text-right">
                                            <button 
                                                onClick={() => onViewReport(c.id, c.name)}
                                                className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-900 text-white rounded-lg text-xs font-bold hover:bg-slate-800 transition-all active:scale-95 shadow-sm"
                                            >
                                                <Eye className="w-3.5 h-3.5" />
                                                Report
                                            </button>
                                        </td>
                                    </tr>
                                );
                            })}
                            {campaigns.length === 0 && !loading && (
                                <tr>
                                    <td colSpan={7} className="px-6 py-12 text-center text-slate-400 text-sm">
                                        No campaigns found. Start a new campaign to see it here.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}

import React, { useState, useEffect } from 'react';
import { ArrowLeft, CheckCircle2, XCircle, FileText, Download } from 'lucide-react';

export default function CampaignReportWorkspace({ t, callRPC, campaignId, campaignName, onBack }: any) {
    const [logs, setLogs] = useState<any[]>([]);
    const [loading, setLoading] = useState(false);

    const fetchLogs = async () => {
        setLoading(true);
        try {
            const res: any = await callRPC('getCampaignLogs', { campaignId });
            setLogs(res.logs || []);
        } catch (err: any) {
            console.error('Failed to fetch logs', err);
        }
        setLoading(false);
    };

    useEffect(() => {
        if (campaignId) fetchLogs();
    }, [campaignId]);

    const sentCount = logs.filter(l => l.status === 'sent').length;
    const failedCount = logs.filter(l => l.status === 'failed').length;

    return (
        <div className="space-y-6 animate-in fade-in duration-300">
            <div className="flex items-center gap-4">
                <button onClick={onBack} className="p-2 hover:bg-slate-100 border border-slate-200 text-slate-500 rounded-xl transition-all">
                    <ArrowLeft className="w-4 h-4" />
                </button>
                <div>
                    <h2 className="text-xl font-bold tracking-tight text-slate-850">Campaign Report</h2>
                    <p className="text-xs text-slate-400 mt-1">{campaignName} ({campaignId})</p>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div className="bg-white/60 backdrop-blur-xl border border-white/60 rounded-3xl p-7 shadow-[0_8px_30px_rgb(0,0,0,0.04)] flex items-center gap-5 hover:-translate-y-1 transition-transform">
                    <div className="w-12 h-12 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400">
                        <FileText className="w-6 h-6" />
                    </div>
                    <div>
                        <p className="text-xs font-bold uppercase tracking-wider text-slate-400">Total Processed</p>
                        <p className="text-2xl font-black text-slate-800">{logs.length}</p>
                    </div>
                </div>
                <div className="bg-white/60 backdrop-blur-xl border border-white/60 rounded-3xl p-7 shadow-[0_8px_30px_rgb(0,0,0,0.04)] flex items-center gap-5 hover:-translate-y-1 transition-transform">
                    <div className="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-500">
                        <CheckCircle2 className="w-6 h-6" />
                    </div>
                    <div>
                        <p className="text-xs font-bold uppercase tracking-wider text-slate-400">Successfully Sent</p>
                        <p className="text-2xl font-black text-emerald-600">{sentCount}</p>
                    </div>
                </div>
                <div className="bg-white/60 backdrop-blur-xl border border-white/60 rounded-3xl p-7 shadow-[0_8px_30px_rgb(0,0,0,0.04)] flex items-center gap-5 hover:-translate-y-1 transition-transform">
                    <div className="w-12 h-12 rounded-xl bg-rose-50 flex items-center justify-center text-rose-500">
                        <XCircle className="w-6 h-6" />
                    </div>
                    <div>
                        <p className="text-xs font-bold uppercase tracking-wider text-slate-400">Failed / Blocked</p>
                        <p className="text-2xl font-black text-rose-600">{failedCount}</p>
                    </div>
                </div>
            </div>

            <div className="bg-white/60 backdrop-blur-xl border border-white/60 rounded-3xl p-7 shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden transition-all hover:bg-white/80">
                <div className="pb-4 border-b border-slate-100 flex items-center justify-between mb-4">
                    <h3 className="font-bold text-slate-800 text-sm">Detailed Delivery Logs</h3>
                    <button className="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-teal-500 to-emerald-600 hover:from-teal-400 hover:to-emerald-500 text-white rounded-xl text-xs font-bold transition-all shadow-[0_4px_10px_rgb(20,184,166,0.3)] hover:shadow-[0_6px_15px_rgb(20,184,166,0.4)] active:scale-95">
                        <Download className="w-3.5 h-3.5" /> Export CSV
                    </button>
                </div>
                <div className="overflow-x-auto max-h-[500px] overflow-y-auto">
                    <table className="w-full text-left text-sm whitespace-nowrap">
                        <thead className="bg-slate-50 border-b border-slate-100 text-slate-500 font-semibold uppercase text-[10px] tracking-wider sticky top-0">
                            <tr>
                                <th className="px-6 py-4">Phone Number</th>
                                <th className="px-6 py-4">Status</th>
                                <th className="px-6 py-4">Error / Reason</th>
                                <th className="px-6 py-4">Time</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {logs.map(l => (
                                <tr key={l.id} className="hover:bg-slate-50 transition-colors">
                                    <td className="px-6 py-3 font-mono text-slate-600 text-xs">{l.phone}</td>
                                    <td className="px-6 py-3">
                                        <span className={`px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider ${
                                            l.status === 'sent' ? 'bg-emerald-50 text-emerald-600' :
                                            'bg-rose-50 text-rose-600'
                                        }`}>
                                            {l.status}
                                        </span>
                                    </td>
                                    <td className="px-6 py-3 text-slate-500 text-xs truncate max-w-xs" title={l.error_message}>
                                        {l.error_message || '-'}
                                    </td>
                                    <td className="px-6 py-3 text-slate-400 text-xs">
                                        {new Date(l.sent_at).toLocaleString()}
                                    </td>
                                </tr>
                            ))}
                            {logs.length === 0 && !loading && (
                                <tr>
                                    <td colSpan={4} className="px-6 py-12 text-center text-slate-400 text-sm">
                                        No logs recorded for this campaign yet.
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

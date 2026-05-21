import React from 'react';
import { Square } from 'lucide-react';
import { Button } from '@/Components/ui/button';

export default function DeliverabilityWorkspace({
    t, isCampaignRunning, handleStopCampaign, campaignProgress, deliverabilityGrid
}: any) {
    return (
        <div className="space-y-6 animate-in fade-in duration-300">
            {/* Overall campaign progress details card */}
            <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-5">
                <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <div>
                        <h3 className="font-bold text-slate-800 text-base">{t.deliverability.campaignProgress}</h3>
                        <p className="text-xs text-slate-400 mt-1">{t.deliverability.description}</p>
                    </div>

                    {isCampaignRunning && (
                        <Button 
                            variant="outline"
                            onClick={handleStopCampaign}
                            className="h-9 gap-1.5 px-4 bg-rose-50 border-rose-200 text-rose-600 hover:bg-rose-100 hover:text-rose-700 text-xs font-bold shrink-0"
                        >
                            <Square className="w-3.5 h-3.5" />
                            {t.deliverability.stopCampaign}
                        </Button>
                    )}
                </div>

                {/* Dynamic progress bar stats */}
                <div className="space-y-2">
                    <div className="flex items-center justify-between text-xs font-bold text-slate-500">
                        <span>{campaignProgress.percent}% Completed</span>
                        <span>{campaignProgress.sent} / {campaignProgress.total} dispatched</span>
                    </div>
                    <div className="w-full h-3 bg-slate-100 rounded-full overflow-hidden border border-slate-200">
                        <div 
                            className="h-full bg-gradient-to-r from-emerald-500 to-teal-500 transition-all duration-500 ease-out rounded-full"
                            style={{ width: `${campaignProgress.percent}%` }}
                        />
                    </div>
                </div>

                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 pt-2">
                    <div className="bg-slate-50 border border-slate-100 rounded-xl p-3 flex flex-col justify-center">
                        <span className="text-[10px] font-bold uppercase tracking-wider text-slate-450">Processed</span>
                        <span className="text-xl font-black text-slate-700 mt-1">{campaignProgress.sent + campaignProgress.failed + campaignProgress.skipped}</span>
                    </div>
                    <div className="bg-emerald-50/50 border border-emerald-100 rounded-xl p-3 flex flex-col justify-center">
                        <span className="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Sent</span>
                        <span className="text-xl font-black text-emerald-700 mt-1">{campaignProgress.sent}</span>
                    </div>
                    <div className="bg-rose-50/50 border border-rose-100 rounded-xl p-3 flex flex-col justify-center">
                        <span className="text-[10px] font-bold uppercase tracking-wider text-rose-600">Blocked</span>
                        <span className="text-xl font-black text-rose-700 mt-1">{campaignProgress.blocked}</span>
                    </div>
                    <div className="bg-amber-50/50 border border-amber-100 rounded-xl p-3 flex flex-col justify-center">
                        <span className="text-[10px] font-bold uppercase tracking-wider text-amber-600">Skipped/Failed</span>
                        <span className="text-xl font-black text-amber-700 mt-1">{campaignProgress.failed + campaignProgress.skipped}</span>
                    </div>
                </div>
            </div>

            {/* Deliverability monitor contacts grid list */}
            <div className="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                <div className="overflow-x-auto">
                    <table className="w-full border-collapse text-xs">
                        <thead>
                            <tr className="bg-slate-50 border-b border-slate-200 text-slate-450 uppercase font-black tracking-wider">
                                <th className="px-5 py-3 text-left">{t.deliverability.colPhone}</th>
                                <th className="px-5 py-3 text-left">{t.deliverability.colName}</th>
                                <th className="px-5 py-3 text-left">{t.deliverability.colCompany}</th>
                                <th className="px-5 py-3 text-left">{t.deliverability.colStatus}</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {deliverabilityGrid.length === 0 ? (
                                <tr>
                                    <td colSpan={4} className="py-12 text-center text-slate-400 font-bold">
                                        No messages sent yet. Create a campaign to start tracking delivery.
                                    </td>
                                </tr>
                            ) : (
                                deliverabilityGrid.map((row: any, idx: number) => (
                                    <tr key={idx} className="hover:bg-slate-50/50 font-medium">
                                        <td className="px-5 py-3 font-mono text-slate-700">{row.phone}</td>
                                        <td className="px-5 py-3 text-slate-650">{row.name || '—'}</td>
                                        <td className="px-5 py-3 text-slate-400">{row.company || '—'}</td>
                                        <td className="px-5 py-3">
                                            <span className={`px-2.5 py-1 rounded-full text-[9px] font-extrabold uppercase border ${
                                                row.status === 'read' ? 'bg-emerald-50 border-emerald-250 text-emerald-700' :
                                                row.status === 'delivered' ? 'bg-cyan-50 border-cyan-250 text-cyan-700' :
                                                row.status === 'sent' ? 'bg-blue-50 border-blue-250 text-blue-700' :
                                                row.status === 'replied' ? 'bg-indigo-50 border-indigo-250 text-indigo-700' :
                                                row.status === 'blocked' ? 'bg-rose-50 border-rose-250 text-rose-700' :
                                                row.status === 'failed' ? 'bg-rose-100 border-rose-300 text-rose-800' :
                                                'bg-slate-50 border-slate-200 text-slate-400'
                                            }`}>
                                                {row.status === 'read' ? t.deliverability.statusRead :
                                                 row.status === 'delivered' ? t.deliverability.statusDelivered :
                                                 row.status === 'sent' ? t.deliverability.statusSent :
                                                 row.status === 'replied' ? t.deliverability.statusReplied :
                                                 row.status === 'blocked' ? t.deliverability.statusBlocked :
                                                 row.status === 'failed' ? t.deliverability.statusFailed :
                                                 t.deliverability.statusPending}
                                            </span>
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

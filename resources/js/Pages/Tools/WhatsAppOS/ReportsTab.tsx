import React from 'react';
import { Activity, ShieldCheck, CheckCircle2, AlertCircle } from 'lucide-react';

export default function ReportsTab() {
    return (
        <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
            <div>
                <h2 className="text-2xl font-black text-slate-900 tracking-tight">Campaign Reports</h2>
                <p className="text-sm font-semibold text-slate-500 mt-1">Live operational feed and delivery health.</p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div className="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm">
                    <div className="text-[10px] text-slate-500 font-bold uppercase tracking-wider mb-2 flex items-center gap-1.5"><ShieldCheck className="w-4 h-4 text-emerald-500" /> Delivery Rate</div>
                    <div className="text-3xl font-black text-slate-900">98.5%</div>
                </div>
                <div className="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm">
                    <div className="text-[10px] text-slate-500 font-bold uppercase tracking-wider mb-2 flex items-center gap-1.5"><CheckCircle2 className="w-4 h-4 text-emerald-500" /> Total Sent</div>
                    <div className="text-3xl font-black text-slate-900">14,250</div>
                </div>
                <div className="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm">
                    <div className="text-[10px] text-slate-500 font-bold uppercase tracking-wider mb-2 flex items-center gap-1.5"><AlertCircle className="w-4 h-4 text-amber-500" /> Action Required</div>
                    <div className="text-3xl font-black text-slate-900">12</div>
                </div>
                <div className="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm">
                    <div className="text-[10px] text-slate-500 font-bold uppercase tracking-wider mb-2 flex items-center gap-1.5"><Activity className="w-4 h-4 text-blue-500" /> Response Rate</div>
                    <div className="text-3xl font-black text-slate-900">15.2%</div>
                </div>
            </div>

            <div className="bg-slate-950 rounded-3xl border border-slate-800 shadow-xl overflow-hidden mt-8">
                <div className="p-4 border-b border-slate-800 bg-slate-900 flex items-center gap-2">
                    <div className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
                    <span className="text-xs font-black text-slate-300 uppercase tracking-wider">Live Operational Feed</span>
                </div>
                <div className="p-6 space-y-4 font-mono text-[11px] text-emerald-400">
                    <div className="flex gap-4">
                        <span className="text-slate-500 shrink-0">14:02:11</span>
                        <span>[Router] Dispatched batch 42 to node: Main Support</span>
                    </div>
                    <div className="flex gap-4">
                        <span className="text-slate-500 shrink-0">14:02:05</span>
                        <span className="text-amber-400">[Engine] Paused routing for node: Sales EU (Cooldown triggered)</span>
                    </div>
                    <div className="flex gap-4">
                        <span className="text-slate-500 shrink-0">14:01:50</span>
                        <span>[Campaign] Summer Promo 2026 progress updated 15%</span>
                    </div>
                </div>
            </div>
        </div>
    );
}

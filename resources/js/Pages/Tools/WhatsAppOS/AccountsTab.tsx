import React, { useState, useEffect } from 'react';
import { Smartphone, RefreshCw, Pause, Trash2, CheckCircle2, AlertCircle, Activity, Play } from 'lucide-react';

export default function AccountsTab({ runtimePort }: { runtimePort: number }) {
    const [accounts, setAccounts] = useState([
        { id: '1', label: 'Main Support', status: 'connected', health: 'A+', daily: '1,200', lastActive: '2m ago', phone: '+1234567890' },
        { id: '2', label: 'Sales EU', status: 'offline', health: 'B', daily: '500', lastActive: '1h ago', phone: '+0987654321' }
    ]);

    return (
        <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-2xl font-black text-slate-900 tracking-tight">WhatsApp Accounts</h2>
                    <p className="text-sm font-semibold text-slate-500 mt-1">Manage, connect, and monitor your routing nodes.</p>
                </div>
                <button className="bg-slate-900 text-white hover:bg-slate-800 text-sm font-bold px-6 py-2.5 rounded-full shadow-lg transition-transform active:scale-95 flex items-center gap-2">
                    <Smartphone className="w-4 h-4" />
                    Connect Account
                </button>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {accounts.map(acc => (
                    <div key={acc.id} className="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm hover:shadow-xl hover:border-emerald-200 transition-all group relative overflow-hidden">
                        <div className="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-400 to-teal-400 opacity-0 group-hover:opacity-100 transition-opacity" />
                        
                        <div className="flex justify-between items-start mb-6">
                            <div className="flex items-center gap-4">
                                <div className="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center border border-slate-200">
                                    <Smartphone className="w-6 h-6 text-slate-700" />
                                </div>
                                <div>
                                    <h3 className="font-bold text-slate-900">{acc.label}</h3>
                                    <p className="text-xs font-semibold text-slate-500 font-mono">{acc.phone}</p>
                                </div>
                            </div>
                            {acc.status === 'connected' ? (
                                <span className="bg-emerald-50 text-emerald-600 border border-emerald-200 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider flex items-center gap-1.5">
                                    <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
                                    Active
                                </span>
                            ) : (
                                <span className="bg-slate-100 text-slate-600 border border-slate-200 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider flex items-center gap-1.5">
                                    Offline
                                </span>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-4 mb-6">
                            <div className="bg-slate-50 rounded-2xl p-3 border border-slate-100">
                                <div className="text-[10px] text-slate-500 font-bold uppercase tracking-wider mb-1 flex items-center gap-1">
                                    <Activity className="w-3 h-3" /> Health
                                </div>
                                <div className="text-lg font-black text-slate-900">{acc.health}</div>
                            </div>
                            <div className="bg-slate-50 rounded-2xl p-3 border border-slate-100">
                                <div className="text-[10px] text-slate-500 font-bold uppercase tracking-wider mb-1 flex items-center gap-1">
                                    <CheckCircle2 className="w-3 h-3" /> Capacity
                                </div>
                                <div className="text-lg font-black text-slate-900">{acc.daily}</div>
                            </div>
                        </div>

                        <div className="flex items-center gap-2 pt-4 border-t border-slate-100">
                            {acc.status === 'connected' ? (
                                <button className="flex-1 bg-amber-50 hover:bg-amber-100 text-amber-700 text-xs font-bold py-2.5 rounded-xl transition-colors flex justify-center items-center gap-1.5">
                                    <Pause className="w-3.5 h-3.5" /> Pause
                                </button>
                            ) : (
                                <button className="flex-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-bold py-2.5 rounded-xl transition-colors flex justify-center items-center gap-1.5">
                                    <RefreshCw className="w-3.5 h-3.5" /> Reconnect
                                </button>
                            )}
                            <button className="px-4 bg-slate-50 hover:bg-red-50 text-slate-400 hover:text-red-600 text-xs font-bold py-2.5 rounded-xl transition-colors flex justify-center items-center">
                                <Trash2 className="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

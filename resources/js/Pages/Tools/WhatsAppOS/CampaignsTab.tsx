import React, { useState } from 'react';
import { Plus, Play, Pause, MoreVertical, Search, MessageSquare, Users } from 'lucide-react';
import CampaignWizard from './CampaignWizard';

export default function CampaignsTab({ runtimePort }: { runtimePort: number }) {
    const [isWizardOpen, setIsWizardOpen] = useState(false);
    
    // Mock data for UI prototype
    const [campaigns] = useState([
        { id: 1, name: 'Summer Promo 2026', status: 'running', audience: 12500, sent: 3400, mode: 'Balanced', created: '2 hours ago' },
        { id: 2, name: 'Inactive Leads Reactivation', status: 'paused', audience: 800, sent: 400, mode: 'Safe', created: 'Yesterday' },
        { id: 3, name: 'B2B Outreach Sequence', status: 'draft', audience: 50, sent: 0, mode: 'Fast', created: '3 days ago' },
    ]);

    if (isWizardOpen) {
        return <CampaignWizard onClose={() => setIsWizardOpen(false)} />;
    }

    return (
        <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-2xl font-black text-slate-900 tracking-tight">Campaign Operations</h2>
                    <p className="text-sm font-semibold text-slate-500 mt-1">Manage active dispatches and create new campaign entities.</p>
                </div>
                <button 
                    onClick={() => setIsWizardOpen(true)}
                    className="bg-emerald-500 text-white hover:bg-emerald-600 text-sm font-bold px-6 py-2.5 rounded-full shadow-lg shadow-emerald-200 transition-transform active:scale-95 flex items-center gap-2"
                >
                    <Plus className="w-4 h-4" />
                    New Campaign
                </button>
            </div>

            <div className="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">
                <div className="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div className="relative w-64">
                        <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input 
                            type="text" 
                            placeholder="Search campaigns..." 
                            className="w-full pl-9 pr-4 py-2 bg-white border-slate-200 rounded-xl text-xs font-bold focus:ring-emerald-500 focus:border-emerald-500"
                        />
                    </div>
                </div>
                
                <table className="w-full text-left border-collapse">
                    <thead>
                        <tr className="bg-slate-50 text-[10px] font-black text-slate-500 uppercase tracking-wider">
                            <th className="p-4 font-bold border-b border-slate-100">Campaign Entity</th>
                            <th className="p-4 font-bold border-b border-slate-100">Status</th>
                            <th className="p-4 font-bold border-b border-slate-100">Progress</th>
                            <th className="p-4 font-bold border-b border-slate-100">Delivery Profile</th>
                            <th className="p-4 font-bold border-b border-slate-100 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="text-sm font-semibold text-slate-700 divide-y divide-slate-100">
                        {campaigns.map(camp => (
                            <tr key={camp.id} className="hover:bg-slate-50/50 transition-colors group">
                                <td className="p-4">
                                    <div className="font-bold text-slate-900">{camp.name}</div>
                                    <div className="text-[10px] text-slate-400 mt-0.5">{camp.created}</div>
                                </td>
                                <td className="p-4">
                                    {camp.status === 'running' && <span className="text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider flex items-center w-max gap-1.5"><div className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"/> Active</span>}
                                    {camp.status === 'paused' && <span className="text-amber-600 bg-amber-50 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider w-max">Paused</span>}
                                    {camp.status === 'draft' && <span className="text-slate-500 bg-slate-100 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider w-max">Draft</span>}
                                </td>
                                <td className="p-4">
                                    <div className="flex items-center gap-4">
                                        <div className="flex-1 max-w-[120px]">
                                            <div className="flex justify-between text-[10px] font-bold mb-1.5">
                                                <span className="text-slate-900">{camp.sent} sent</span>
                                                <span className="text-slate-400">{camp.audience}</span>
                                            </div>
                                            <div className="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                                <div className="h-full bg-emerald-400 rounded-full" style={{ width: `${(camp.sent/camp.audience)*100}%` }} />
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td className="p-4">
                                    <span className="font-mono text-xs text-slate-500">{camp.mode}</span>
                                </td>
                                <td className="p-4 text-right">
                                    <div className="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        {camp.status === 'running' ? (
                                            <button className="p-2 text-amber-600 hover:bg-amber-50 rounded-xl transition-colors"><Pause className="w-4 h-4" /></button>
                                        ) : (
                                            <button className="p-2 text-emerald-600 hover:bg-emerald-50 rounded-xl transition-colors"><Play className="w-4 h-4" /></button>
                                        )}
                                        <button className="p-2 text-slate-400 hover:bg-slate-100 rounded-xl transition-colors"><MoreVertical className="w-4 h-4" /></button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

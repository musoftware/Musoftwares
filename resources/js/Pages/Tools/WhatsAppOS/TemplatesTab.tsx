import React from 'react';
import { Plus, FileText, Trash2, Edit } from 'lucide-react';

export default function TemplatesTab() {
    return (
        <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-2xl font-black text-slate-900 tracking-tight">Template Library</h2>
                    <p className="text-sm font-semibold text-slate-500 mt-1">Manage reusable message components and media assets.</p>
                </div>
                <button className="bg-slate-900 text-white hover:bg-slate-800 text-sm font-bold px-6 py-2.5 rounded-full shadow-lg transition-transform active:scale-95 flex items-center gap-2">
                    <Plus className="w-4 h-4" />
                    New Template
                </button>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm hover:shadow-lg transition-shadow group">
                    <div className="flex items-center justify-between mb-4">
                        <div className="flex items-center gap-3">
                            <div className="bg-slate-100 p-2.5 rounded-xl"><FileText className="w-5 h-5 text-slate-600" /></div>
                            <h3 className="font-bold text-slate-900">Welcome Series 1</h3>
                        </div>
                        <div className="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button className="p-2 text-slate-400 hover:text-emerald-600 transition-colors"><Edit className="w-4 h-4" /></button>
                            <button className="p-2 text-slate-400 hover:text-red-600 transition-colors"><Trash2 className="w-4 h-4" /></button>
                        </div>
                    </div>
                    <div className="bg-slate-50 border border-slate-100 rounded-2xl p-4 text-xs font-semibold text-slate-600 line-clamp-3">
                        Hi {'{{first_name}}'}, welcome to {'{{company}}'}! We are thrilled to have you onboard. Here is your quick start guide: https://link.com
                    </div>
                </div>

                <div className="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm hover:shadow-lg transition-shadow group">
                    <div className="flex items-center justify-between mb-4">
                        <div className="flex items-center gap-3">
                            <div className="bg-slate-100 p-2.5 rounded-xl"><FileText className="w-5 h-5 text-slate-600" /></div>
                            <h3 className="font-bold text-slate-900">Invoice Reminder</h3>
                        </div>
                        <div className="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button className="p-2 text-slate-400 hover:text-emerald-600 transition-colors"><Edit className="w-4 h-4" /></button>
                            <button className="p-2 text-slate-400 hover:text-red-600 transition-colors"><Trash2 className="w-4 h-4" /></button>
                        </div>
                    </div>
                    <div className="bg-slate-50 border border-slate-100 rounded-2xl p-4 text-xs font-semibold text-slate-600 line-clamp-3">
                        Hello {'{{first_name}}'}, this is a gentle reminder regarding invoice #{'{{invoice_id}}'}. Please let us know if you need assistance.
                    </div>
                </div>
            </div>
        </div>
    );
}

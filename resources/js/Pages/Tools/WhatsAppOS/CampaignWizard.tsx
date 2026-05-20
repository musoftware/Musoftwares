import React, { useState } from 'react';
import { ArrowLeft, ArrowRight, Play, Upload, MessageSquare, CheckCircle2, User, Users } from 'lucide-react';

export default function CampaignWizard({ onClose }: { onClose: () => void }) {
    const [step, setStep] = useState(1);

    return (
        <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500 max-w-4xl mx-auto">
            <div className="flex items-center gap-4">
                <button onClick={onClose} className="p-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-2xl transition-colors">
                    <ArrowLeft className="w-5 h-5" />
                </button>
                <div>
                    <h2 className="text-2xl font-black text-slate-900 tracking-tight">New Campaign</h2>
                    <p className="text-sm font-semibold text-slate-500 mt-1">Follow the steps to configure and launch your campaign.</p>
                </div>
            </div>

            <div className="flex items-center justify-between px-4 mb-8">
                {[1, 2, 3, 4].map(s => (
                    <div key={s} className="flex items-center">
                        <div className={`w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-colors ${step >= s ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-200' : 'bg-slate-100 text-slate-400'}`}>
                            {s}
                        </div>
                        {s < 4 && <div className={`w-16 md:w-32 h-1 mx-2 rounded-full ${step > s ? 'bg-emerald-500' : 'bg-slate-100'}`} />}
                    </div>
                ))}
            </div>

            <div className="bg-white border border-slate-200 rounded-3xl p-8 shadow-sm min-h-[400px]">
                {step === 1 && (
                    <div className="space-y-6 animate-in fade-in slide-in-from-right-4">
                        <h3 className="text-lg font-black text-slate-900">Select Dispatch Accounts</h3>
                        <p className="text-xs font-semibold text-slate-500">Choose the active nodes that will deliver this campaign.</p>
                        
                        <div className="grid grid-cols-2 gap-4">
                            {['Main Support', 'Sales EU', 'Marketing USA'].map((acc, i) => (
                                <label key={i} className="flex items-center gap-4 p-4 border border-slate-200 rounded-2xl cursor-pointer hover:border-emerald-500 transition-colors bg-slate-50">
                                    <input type="checkbox" className="w-5 h-5 text-emerald-500 focus:ring-emerald-500 border-slate-300 rounded-lg" />
                                    <div>
                                        <div className="font-bold text-slate-900">{acc}</div>
                                        <div className="text-[10px] text-emerald-600 font-bold uppercase tracking-wider">Active</div>
                                    </div>
                                </label>
                            ))}
                        </div>
                    </div>
                )}

                {step === 2 && (
                    <div className="space-y-6 animate-in fade-in slide-in-from-right-4">
                        <h3 className="text-lg font-black text-slate-900">Import Audience</h3>
                        <p className="text-xs font-semibold text-slate-500">Upload your CSV or paste numbers. Data is processed locally and securely.</p>
                        
                        <div className="border-2 border-dashed border-slate-200 rounded-3xl p-12 text-center hover:border-emerald-400 hover:bg-emerald-50/50 transition-colors cursor-pointer group">
                            <Upload className="w-8 h-8 text-slate-400 mx-auto mb-4 group-hover:text-emerald-500 transition-colors" />
                            <div className="font-bold text-slate-700">Click to upload CSV</div>
                            <div className="text-xs font-semibold text-slate-400 mt-2">Supports columns: phone, first_name, company, variables</div>
                        </div>

                        <div className="relative">
                            <div className="absolute inset-0 flex items-center"><div className="w-full border-t border-slate-200" /></div>
                            <div className="relative flex justify-center"><span className="bg-white px-4 text-xs font-bold text-slate-400 uppercase tracking-wider">OR</span></div>
                        </div>

                        <textarea 
                            className="w-full h-32 bg-slate-50 border border-slate-200 rounded-2xl p-4 text-xs font-mono focus:ring-emerald-500 focus:border-emerald-500"
                            placeholder="Paste numbers here (one per line)..."
                        />
                    </div>
                )}

                {step === 3 && (
                    <div className="space-y-6 animate-in fade-in slide-in-from-right-4">
                        <h3 className="text-lg font-black text-slate-900">Compose Message</h3>
                        <p className="text-xs font-semibold text-slate-500">Draft your message using personalization variables.</p>
                        
                        <div className="flex gap-2 overflow-x-auto pb-2">
                            {['{{first_name}}', '{{company}}', '{{invoice_id}}'].map(tag => (
                                <button key={tag} className="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-3 py-1.5 rounded-lg font-mono transition-colors shrink-0">
                                    {tag}
                                </button>
                            ))}
                        </div>

                        <textarea 
                            className="w-full h-48 bg-slate-50 border border-slate-200 rounded-2xl p-4 text-sm font-semibold focus:ring-emerald-500 focus:border-emerald-500"
                            placeholder="Hi {{first_name}}, welcome to {{company}}!..."
                        />
                    </div>
                )}

                {step === 4 && (
                    <div className="space-y-6 animate-in fade-in slide-in-from-right-4">
                        <h3 className="text-lg font-black text-slate-900">Delivery Profile & Launch</h3>
                        <p className="text-xs font-semibold text-slate-500">Choose how the runtime orchestrates the delivery.</p>
                        
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                            {[
                                { id: 'safe', label: 'Safe', desc: 'Slow & steady. Maximum ban protection.', color: 'text-emerald-600', border: 'border-emerald-500', bg: 'bg-emerald-50' },
                                { id: 'balanced', label: 'Balanced', desc: 'Standard speed with natural variations.', color: 'text-blue-600', border: 'border-blue-500', bg: 'bg-blue-50' },
                                { id: 'fast', label: 'Fast', desc: 'Maximum speed for trusted nodes.', color: 'text-amber-600', border: 'border-amber-500', bg: 'bg-amber-50' }
                            ].map(p => (
                                <label key={p.id} className={`p-4 border-2 rounded-2xl cursor-pointer transition-all ${p.id === 'balanced' ? p.border + ' ' + p.bg : 'border-slate-200 hover:border-slate-300'}`}>
                                    <div className="flex items-center justify-between mb-2">
                                        <div className={`font-black uppercase tracking-wider text-xs ${p.id === 'balanced' ? p.color : 'text-slate-700'}`}>{p.label}</div>
                                        <input type="radio" name="profile" defaultChecked={p.id === 'balanced'} className={`w-4 h-4 ${p.color}`} />
                                    </div>
                                    <p className="text-[10px] font-semibold text-slate-500">{p.desc}</p>
                                </label>
                            ))}
                        </div>

                        <div className="bg-slate-50 border border-slate-100 rounded-2xl p-6">
                            <h4 className="font-bold text-slate-900 mb-4">Summary</h4>
                            <ul className="space-y-2 text-sm font-semibold text-slate-600">
                                <li className="flex justify-between"><span>Selected Accounts:</span> <span className="font-bold text-slate-900">2 Nodes</span></li>
                                <li className="flex justify-between"><span>Total Audience:</span> <span className="font-bold text-slate-900">1,450 Contacts</span></li>
                                <li className="flex justify-between"><span>Estimated Time:</span> <span className="font-bold text-slate-900">4 Hours</span></li>
                            </ul>
                        </div>
                    </div>
                )}
            </div>

            <div className="flex justify-between">
                {step > 1 ? (
                    <button onClick={() => setStep(s => s - 1)} className="px-6 py-3 bg-white border border-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-50 transition-colors">
                        Back
                    </button>
                ) : <div />}

                {step < 4 ? (
                    <button onClick={() => setStep(s => s + 1)} className="px-6 py-3 bg-slate-900 text-white font-bold rounded-xl hover:bg-slate-800 transition-colors flex items-center gap-2">
                        Next Step <ArrowRight className="w-4 h-4" />
                    </button>
                ) : (
                    <button onClick={onClose} className="px-8 py-3 bg-emerald-500 text-white font-black rounded-xl hover:bg-emerald-600 shadow-lg shadow-emerald-200 transition-all active:scale-95 flex items-center gap-2">
                        <Play className="w-4 h-4 fill-white" />
                        Launch Campaign
                    </button>
                )}
            </div>
        </div>
    );
}

import React, { useState } from 'react';
import {
    Users, MessageSquare, Rocket, ChevronRight, ChevronLeft,
    Trash2, Plus, CheckCircle2, Image, Video, FileArchive, Mic, FileText,
    Loader2, UsersRound, Zap
} from 'lucide-react';

const STEPS = ['Account & Group', 'Add Contacts', 'Compose Message', 'Launch'];
const MEDIA_TYPES = [
    { value: 'text',     label: 'Text',    icon: FileText },
    { value: 'image',    label: 'Image',   icon: Image },
    { value: 'video',    label: 'Video',   icon: Video },
    { value: 'document', label: 'Doc',     icon: FileArchive },
    { value: 'audio',    label: 'Audio',   icon: Mic },
];

function StepIndicator({ currentStep }: { currentStep: number }) {
    return (
        <div className="flex items-center gap-0 mb-8">
            {STEPS.map((label, i) => (
                <React.Fragment key={i}>
                    <div className="flex flex-col items-center gap-1.5">
                        <div className={`w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all duration-300 ${
                            i < currentStep  ? 'bg-emerald-500 text-white shadow-[0_4px_10px_rgb(52,211,153,0.4)]' :
                            i === currentStep? 'bg-gradient-to-br from-teal-400 to-emerald-500 text-white shadow-[0_4px_10px_rgb(52,211,153,0.4)]' :
                                               'bg-slate-100 text-slate-400'
                        }`}>
                            {i < currentStep ? <CheckCircle2 className="w-4 h-4" /> : i + 1}
                        </div>
                        <span className={`text-[10px] font-bold uppercase tracking-wider whitespace-nowrap ${i === currentStep ? 'text-teal-600' : 'text-slate-400'}`}>{label}</span>
                    </div>
                    {i < STEPS.length - 1 && (
                        <div className={`flex-1 h-0.5 mx-2 mb-5 transition-all duration-300 ${i < currentStep ? 'bg-emerald-400' : 'bg-slate-200'}`} />
                    )}
                </React.Fragment>
            ))}
        </div>
    );
}

export default function GroupCampaignWorkspace({ callRPC, sessions }: any) {
    const [step, setStep]               = useState(0);
    const [accountId, setAccountId]     = useState(sessions.find((s: any) => s.state === 'connected')?.accountId || '');
    const [groupName, setGroupName]     = useState('');
    const [deleteAfter, setDeleteAfter] = useState(false);
    const [contactsText, setContactsText] = useState('');
    const [message, setMessage]         = useState('');
    const [mediaType, setMediaType]     = useState('text');
    const [mediaUrl, setMediaUrl]       = useState('');
    const [launching, setLaunching]     = useState(false);
    const [launched, setLaunched]       = useState<any>(null);

    const connectedSessions = sessions.filter((s: any) => s.state === 'connected');

    const parseContacts = () => {
        return contactsText.split('\n').map(line => {
            const parts = line.split(',');
            return {
                phone:   parts[0]?.trim().replace(/[^0-9+]/g, '') || '',
                name:    parts[1]?.trim() || '',
                company: parts[2]?.trim() || ''
            };
        }).filter(c => c.phone.length >= 7);
    };

    const contacts = parseContacts();

    const canNext = () => {
        if (step === 0) return accountId && groupName.trim().length >= 2;
        if (step === 1) return contacts.length >= 1;
        if (step === 2) return message.trim() || (mediaType !== 'text' && mediaUrl.trim());
        return true;
    };

    const handleLaunch = async () => {
        setLaunching(true);
        try {
            const res: any = await callRPC('createGroupCampaign', {
                accountId,
                groupName,
                contacts,
                message,
                mediaUrl: mediaUrl || null,
                mediaType,
                deleteGroupAfter: deleteAfter,
            });
            setLaunched(res);
        } catch (e: any) {
            alert(`Group Campaign Error: ${e.message}`);
        }
        setLaunching(false);
    };

    if (launched) {
        return (
            <div className="flex flex-col items-center justify-center py-16 gap-6 animate-in fade-in duration-300">
                <div className="w-20 h-20 rounded-3xl bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center shadow-[0_8px_30px_rgb(52,211,153,0.4)]">
                    <Zap className="w-10 h-10 text-white" />
                </div>
                <div className="text-center">
                    <h2 className="text-2xl font-black text-slate-800">Group Campaign Launched!</h2>
                    <p className="text-slate-400 mt-2 text-sm">Creating group <strong>{groupName}</strong> and sending to {contacts.length} contacts…</p>
                    <p className="text-xs font-mono text-slate-300 mt-2">{launched.campaignId}</p>
                </div>
                <button onClick={() => { setLaunched(null); setStep(0); setGroupName(''); setContactsText(''); setMessage(''); }} className="px-6 py-3 bg-gradient-to-r from-slate-700 to-slate-800 text-white rounded-xl font-bold text-sm hover:from-slate-600 hover:to-slate-700 transition-all active:scale-95">
                    Start Another Group Campaign
                </button>
            </div>
        );
    }

    return (
        <div className="space-y-6 animate-in fade-in duration-300">
            <div>
                <h2 className="text-xl font-bold tracking-tight text-slate-800">Group Campaign</h2>
                <p className="text-xs text-slate-400 mt-1">Create a WhatsApp group, add contacts, send a message — all in one flow.</p>
            </div>

            <div className="bg-white/70 backdrop-blur-xl border border-white/60 rounded-3xl p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
                <StepIndicator currentStep={step} />

                {/* Step 0: Account & Group */}
                {step === 0 && (
                    <div className="space-y-5 animate-in fade-in duration-200">
                        <div>
                            <label className="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">WhatsApp Account</label>
                            {connectedSessions.length === 0 ? (
                                <div className="text-sm text-amber-600 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                                    ⚠️ No connected accounts. Please connect a WhatsApp account first.
                                </div>
                            ) : (
                                <select value={accountId} onChange={e => setAccountId(e.target.value)} className="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-400/40 bg-white">
                                    {connectedSessions.map((s: any) => (
                                        <option key={s.accountId} value={s.accountId}>{s.accountId}{s.phone ? ` (+${s.phone})` : ''}</option>
                                    ))}
                                </select>
                            )}
                        </div>

                        <div>
                            <label className="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Group Name</label>
                            <input value={groupName} onChange={e => setGroupName(e.target.value)} placeholder="e.g. VIP Customers Q2 2025" className="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 transition-all" />
                        </div>

                        <label className="flex items-center gap-3 p-4 bg-slate-50 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors">
                            <input type="checkbox" checked={deleteAfter} onChange={e => setDeleteAfter(e.target.checked)} className="w-4 h-4 accent-teal-500 rounded" />
                            <div>
                                <span className="font-bold text-slate-700 text-sm">Delete group after sending</span>
                                <p className="text-xs text-slate-400 mt-0.5">The bot will leave the group automatically once the message is delivered.</p>
                            </div>
                        </label>
                    </div>
                )}

                {/* Step 1: Contacts */}
                {step === 1 && (
                    <div className="space-y-4 animate-in fade-in duration-200">
                        <div>
                            <div className="flex items-center justify-between mb-2">
                                <label className="text-xs font-bold text-slate-500 uppercase tracking-wider">Contact List</label>
                                {contacts.length > 0 && (
                                    <span className="text-xs font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full">{contacts.length} valid contacts</span>
                                )}
                            </div>
                            <textarea
                                value={contactsText}
                                onChange={e => setContactsText(e.target.value)}
                                placeholder={`One per line — phone,name,company format:\n966501234567,Ahmed Al-Rashid,Aramco\n966507654321,Fatima,\n971501234567`}
                                rows={12}
                                className="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm text-slate-700 font-mono focus:outline-none focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 transition-all resize-none"
                            />
                            <p className="text-[10px] text-slate-400 mt-1">WhatsApp limits groups to 1024 participants.</p>
                        </div>

                        {contacts.length > 0 && (
                            <div className="max-h-40 overflow-y-auto border border-slate-100 rounded-xl divide-y divide-slate-50">
                                {contacts.slice(0, 8).map((c, i) => (
                                    <div key={i} className="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50">
                                        <div className="w-6 h-6 rounded-full bg-gradient-to-br from-teal-400 to-emerald-500 flex items-center justify-center text-white text-[10px] font-bold">{(c.name || c.phone)[0]}</div>
                                        <span className="text-sm font-mono text-slate-600">{c.phone}</span>
                                        {c.name && <span className="text-xs text-slate-400">{c.name}</span>}
                                    </div>
                                ))}
                                {contacts.length > 8 && <div className="px-4 py-2 text-xs text-slate-400 text-center">+ {contacts.length - 8} more</div>}
                            </div>
                        )}
                    </div>
                )}

                {/* Step 2: Compose */}
                {step === 2 && (
                    <div className="space-y-5 animate-in fade-in duration-200">
                        <div>
                            <label className="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Message Type</label>
                            <div className="grid grid-cols-5 gap-2">
                                {MEDIA_TYPES.map(({ value, label, icon: Icon }) => (
                                    <button key={value} onClick={() => setMediaType(value)} className={`flex flex-col items-center gap-1.5 py-3 px-2 rounded-xl border transition-all ${mediaType === value ? 'border-teal-400 bg-teal-50 text-teal-600' : 'border-slate-200 hover:border-slate-300 text-slate-500'}`}>
                                        <Icon className="w-5 h-5" />
                                        <span className="text-[9px] font-bold uppercase tracking-wider">{label}</span>
                                    </button>
                                ))}
                            </div>
                        </div>

                        {mediaType !== 'text' && (
                            <div>
                                <label className="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Media URL</label>
                                <input value={mediaUrl} onChange={e => setMediaUrl(e.target.value)} placeholder="https://example.com/promo.jpg" className="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm font-mono text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 transition-all" />
                            </div>
                        )}

                        <div>
                            <label className="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Message / Caption</label>
                            <textarea value={message} onChange={e => setMessage(e.target.value)} placeholder="Hello group members! We have an exclusive offer for you…" rows={6} className="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 transition-all resize-none" />
                        </div>
                    </div>
                )}

                {/* Step 3: Review & Launch */}
                {step === 3 && (
                    <div className="space-y-5 animate-in fade-in duration-200">
                        <div className="bg-gradient-to-br from-teal-50 to-emerald-50 border border-teal-200/60 rounded-2xl p-6 space-y-4">
                            <h3 className="font-bold text-slate-700 text-sm uppercase tracking-wider">Campaign Summary</h3>
                            <div className="grid grid-cols-2 gap-4 text-sm">
                                <div><span className="text-slate-400 text-xs block mb-0.5">Account</span><span className="font-bold text-slate-700">{accountId}</span></div>
                                <div><span className="text-slate-400 text-xs block mb-0.5">Group Name</span><span className="font-bold text-slate-700">{groupName}</span></div>
                                <div><span className="text-slate-400 text-xs block mb-0.5">Recipients</span><span className="font-bold text-slate-700">{contacts.length} contacts</span></div>
                                <div><span className="text-slate-400 text-xs block mb-0.5">Media Type</span><span className="font-bold text-slate-700 capitalize">{mediaType}</span></div>
                                <div><span className="text-slate-400 text-xs block mb-0.5">Delete Group After</span><span className={`font-bold ${deleteAfter ? 'text-rose-600' : 'text-slate-700'}`}>{deleteAfter ? 'Yes' : 'No'}</span></div>
                            </div>
                            {message && (
                                <div>
                                    <span className="text-slate-400 text-xs block mb-1">Message Preview</span>
                                    <div className="bg-white/70 rounded-xl px-4 py-3 text-sm text-slate-700 border border-white line-clamp-4">{message}</div>
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {/* Navigation */}
                <div className="flex items-center justify-between mt-8 pt-6 border-t border-slate-100">
                    <button
                        onClick={() => setStep(s => s - 1)}
                        disabled={step === 0}
                        className="flex items-center gap-2 px-5 py-2.5 border border-slate-200 text-slate-600 rounded-xl text-sm font-bold hover:bg-slate-50 transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        <ChevronLeft className="w-4 h-4" /> Back
                    </button>

                    {step < STEPS.length - 1 ? (
                        <button
                            onClick={() => setStep(s => s + 1)}
                            disabled={!canNext()}
                            className="flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-teal-500 to-emerald-600 text-white rounded-xl text-sm font-bold hover:from-teal-400 hover:to-emerald-500 transition-all shadow-[0_4px_10px_rgb(52,211,153,0.3)] active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Next <ChevronRight className="w-4 h-4" />
                        </button>
                    ) : (
                        <button
                            onClick={handleLaunch}
                            disabled={launching || !canNext()}
                            className="flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl text-sm font-bold hover:from-emerald-400 hover:to-teal-500 transition-all shadow-[0_4px_15px_rgb(52,211,153,0.4)] active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {launching ? (
                                <><Loader2 className="w-4 h-4 animate-spin" /> Launching…</>
                            ) : (
                                <><Rocket className="w-4 h-4" /> Launch Group Campaign</>
                            )}
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
}

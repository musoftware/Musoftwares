import React, { useState, useEffect, useRef } from 'react';
import {
    Bot, Brain, MessageSquare, Settings, Plus, Play, Square,
    Zap, Globe, Key, RefreshCw, AlertCircle, CheckCircle2,
    ChevronDown, X, Mic
} from 'lucide-react';
import { Switch } from '@/Components/ui/switch';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Textarea } from '@/Components/ui/textarea';

const getRuntimeHost = () =>
    typeof window !== 'undefined' ? (window.localStorage.getItem('musoftware_runtime_host') || '127.0.0.1') : '127.0.0.1';
const getWsUrl = () => `ws://${getRuntimeHost()}:18401/ws`;

// ── Dialect badge ─────────────────────────────────────────────────────────────
function DialectBadge({ dialect }: { dialect: string }) {
    const map: Record<string, string> = { gulf: '🇸🇦 Gulf', egyptian: '🇪🇬 Egyptian', levantine: '🇯🇴 Levantine', msa: '📖 MSA', english: '🇺🇸 English' };
    return <Badge variant="outline" className="text-[10px] font-bold bg-indigo-500/15 text-indigo-400 border-indigo-500/25 px-2 py-0.5 rounded-full">{map[dialect] ?? dialect}</Badge>;
}

// ── Agent card ────────────────────────────────────────────────────────────────
function AgentCard({ agent, onToggle, onEdit }: { agent: any; onToggle: () => void; onEdit: () => void }) {
    return (
        <div className={`border rounded-2xl p-5 transition-all ${agent.active ? 'border-indigo-500/30 bg-indigo-500/5' : 'border-slate-800 bg-slate-900'}`}>
            <div className="flex items-start justify-between mb-3">
                <div>
                    <div className="flex items-center gap-2 mb-1">
                        <Bot className={`w-4 h-4 ${agent.active ? 'text-indigo-400' : 'text-slate-600'}`} />
                        <span className="text-sm font-bold text-white">{agent.name}</span>
                        {agent.active && <span className="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse" />}
                    </div>
                    <p className="text-xs text-slate-500 line-clamp-1">{agent.number}</p>
                </div>
                <Switch 
                    checked={agent.active}
                    onCheckedChange={onToggle}
                />
            </div>

            <div className="flex flex-wrap gap-1.5 mb-4">
                <DialectBadge dialect={agent.dialect ?? 'gulf'} />
                <Badge variant="outline" className="text-[10px] font-bold bg-slate-800 text-slate-400 border-slate-700 px-2 py-0.5 rounded-full">{agent.tone ?? 'Professional'}</Badge>
            </div>

            {/* Stats */}
            <div className="grid grid-cols-3 gap-2 mb-4">
                <div className="bg-slate-800/60 rounded-xl p-2.5 text-center">
                    <p className="text-sm font-black text-white">{agent.conversations_today ?? 0}</p>
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-600">Convos Today</p>
                </div>
                <div className="bg-slate-800/60 rounded-xl p-2.5 text-center">
                    <p className="text-sm font-black text-emerald-400">{agent.qualified_leads ?? 0}</p>
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-600">Qualified</p>
                </div>
                <div className="bg-slate-800/60 rounded-xl p-2.5 text-center">
                    <p className="text-sm font-black text-indigo-400">{agent.response_rate ?? '—'}%</p>
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-600">Reply Rate</p>
                </div>
            </div>

            <Button variant="outline" onClick={onEdit} className="w-full h-9 bg-slate-800 border-slate-700 text-slate-400 hover:bg-slate-700 hover:text-slate-300">
                <Settings className="w-3 h-3 mr-1.5" /> Configure Agent
            </Button>
        </div>
    );
}

// ── Add Agent modal ───────────────────────────────────────────────────────────
function AddAgentModal({ onClose, onAdd }: { onClose: () => void; onAdd: (a: any) => void }) {
    const [name, setName]       = useState('');
    const [number, setNumber]   = useState('');
    const [dialect, setDialect] = useState('gulf');
    const [tone, setTone]       = useState('Professional');
    const [context, setContext] = useState('');
    const [goal, setGoal]       = useState('');
    const [apiKey, setApiKey]   = useState('');

    return (
        <div className="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
            <div className="w-full max-w-lg bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5 my-4">
                <div className="flex items-center justify-between">
                    <h3 className="text-sm font-bold text-white flex items-center gap-2"><Bot className="w-4 h-4 text-indigo-400" /> Create AI Sales Agent</h3>
                    <Button variant="ghost" size="icon" onClick={onClose} className="h-6 w-6 text-slate-600 hover:text-white hover:bg-transparent"><X className="w-4 h-4" /></Button>
                </div>

                <div className="grid grid-cols-2 gap-3">
                    <div>
                        <label className="text-[10px] font-black uppercase tracking-wider text-slate-500 block mb-1.5">Agent Name</label>
                        <Input value={name} onChange={e => setName(e.target.value)} placeholder="Sales Bot, Support Agent..."
                            className="h-10 text-sm bg-slate-800 border-slate-700 focus-visible:ring-indigo-500 text-white" />
                    </div>
                    <div>
                        <label className="text-[10px] font-black uppercase tracking-wider text-slate-500 block mb-1.5">WhatsApp Number</label>
                        <Input value={number} onChange={e => setNumber(e.target.value)} placeholder="+962 7..."
                            className="h-10 text-sm bg-slate-800 border-slate-700 focus-visible:ring-indigo-500 text-white font-mono" />
                    </div>
                    <div>
                        <label className="text-[10px] font-black uppercase tracking-wider text-slate-500 block mb-1.5">Arabic Dialect</label>
                        <select value={dialect} onChange={e => setDialect(e.target.value)} className="w-full h-10 px-3 text-sm bg-slate-800 border border-slate-700 focus:border-indigo-500 rounded-md outline-none text-white">
                            {['gulf', 'egyptian', 'levantine', 'msa', 'english'].map(d => <option key={d} value={d}>{d.charAt(0).toUpperCase() + d.slice(1)}</option>)}
                        </select>
                    </div>
                    <div>
                        <label className="text-[10px] font-black uppercase tracking-wider text-slate-500 block mb-1.5">Tone</label>
                        <select value={tone} onChange={e => setTone(e.target.value)} className="w-full h-10 px-3 text-sm bg-slate-800 border border-slate-700 focus:border-indigo-500 rounded-md outline-none text-white">
                            {['Professional', 'Friendly', 'Persuasive', 'Formal', 'Casual'].map(t => <option key={t}>{t}</option>)}
                        </select>
                    </div>
                </div>

                <div>
                    <label className="text-[10px] font-black uppercase tracking-wider text-slate-500 block mb-1.5">Business Context</label>
                    <Textarea value={context} onChange={e => setContext(e.target.value)} rows={3} placeholder="Describe your business, products, pricing, and FAQs. The AI uses this to answer customer questions..."
                        className="text-sm bg-slate-800 border-slate-700 focus-visible:ring-indigo-500 text-white resize-none" />
                </div>

                <div>
                    <label className="text-[10px] font-black uppercase tracking-wider text-slate-500 block mb-1.5">Sales Goal</label>
                    <Input value={goal} onChange={e => setGoal(e.target.value)} placeholder="Book a meeting, Share price list, Collect email..."
                        className="h-10 text-sm bg-slate-800 border-slate-700 focus-visible:ring-indigo-500 text-white" />
                </div>

                <div>
                    <label className="text-[10px] font-black uppercase tracking-wider text-slate-500 block mb-1.5">OpenAI API Key</label>
                    <div className="relative">
                        <Key className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-600" />
                        <Input value={apiKey} onChange={e => setApiKey(e.target.value)} type="password" placeholder="sk-..."
                            className="pl-9 h-10 text-sm bg-slate-800 border-slate-700 focus-visible:ring-indigo-500 text-white font-mono" />
                    </div>
                </div>

                <div className="flex gap-3 pt-2 border-t border-slate-800">
                    <Button variant="outline" onClick={onClose} className="flex-1 h-10 border-slate-800 text-slate-400 hover:bg-slate-800 hover:text-slate-400">Cancel</Button>
                    <Button onClick={() => { if (!name.trim() || !number.trim()) return; onAdd({ name, number, dialect, tone, context, goal, apiKey, active: false, conversations_today: 0, qualified_leads: 0, response_rate: 0 }); onClose(); }}
                        disabled={!name.trim() || !number.trim()}
                        className="flex-1 h-10 bg-indigo-600 text-white hover:bg-indigo-500">
                        Deploy Agent
                    </Button>
                </div>
            </div>
        </div>
    );
}

// ── Main ──────────────────────────────────────────────────────────────────────
export default function WaAiAgentRunner({ tool }: any) {
    const [agents, setAgents]     = useState<any[]>([]);
    const [showAdd, setShowAdd]   = useState(false);
    const [connected, setConnected] = useState(false);
    const wsRef = useRef<WebSocket | null>(null);

    useEffect(() => {
        let ws: WebSocket; let retry: ReturnType<typeof setTimeout>;
        const connect = () => {
            ws = new WebSocket(getWsUrl()); wsRef.current = ws;
            ws.onopen  = () => setConnected(true);
            ws.onclose = () => { setConnected(false); retry = setTimeout(connect, 3000); };
            ws.onerror = () => ws.close();
            ws.onmessage = (e) => {
                try {
                    const msg = JSON.parse(e.data);
                    if (msg.event === 'agent.conversation.update') {
                        setAgents(prev => prev.map(a => a.id === msg.data.agentId ? { ...a, conversations_today: (a.conversations_today ?? 0) + 1 } : a));
                    }
                    if (msg.type === 'plugin_rpc_res') {
                        const r = (ws as any)._pending?.get(msg.requestId);
                        if (r) { r.resolve(msg.payload); (ws as any)._pending?.delete(msg.requestId); }
                    }
                } catch {}
            };
        };
        connect();
        return () => { clearTimeout(retry); ws?.close(); };
    }, []);

    const handleToggle = (idx: number) => {
        setAgents(prev => prev.map((a, i) => i === idx ? { ...a, active: !a.active } : a));
    };

    const totalConvos = agents.reduce((a, ag) => a + (ag.conversations_today ?? 0), 0);
    const totalQualified = agents.reduce((a, ag) => a + (ag.qualified_leads ?? 0), 0);
    const activeAgents = agents.filter(a => a.active).length;

    if (!connected) return (
        <div className="min-h-screen bg-slate-950 flex items-center justify-center">
            <div className="text-center space-y-3">
                <div className="w-8 h-8 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin mx-auto" />
                <p className="text-sm font-semibold text-slate-500">Connecting to Runtime...</p>
            </div>
        </div>
    );

    return (
        <div className="min-h-screen bg-slate-950 font-sans text-slate-100">
            {showAdd && <AddAgentModal onClose={() => setShowAdd(false)} onAdd={a => setAgents(prev => [...prev, a])} />}

            {/* Header */}
            <div className="h-14 bg-slate-900/80 backdrop-blur border-b border-slate-800 flex items-center justify-between px-6 sticky top-0 z-10">
                <div className="flex items-center gap-2.5">
                    <div className="w-7 h-7 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-lg flex items-center justify-center shadow-lg shadow-indigo-500/20">
                        <Brain className="w-4 h-4 text-white" />
                    </div>
                    <span className="font-bold text-sm">WhatsApp AI Sales Agent</span>
                    <span className="text-[9px] font-black uppercase tracking-widest text-indigo-400 bg-indigo-500/10 rounded px-1.5 py-0.5 border border-indigo-500/20">GPT-4o Powered</span>
                </div>
                <Button onClick={() => setShowAdd(true)} className="gap-1.5 h-9 bg-indigo-600 text-white hover:bg-indigo-500 shadow-lg shadow-indigo-500/20">
                    <Plus className="w-3.5 h-3.5" /> Deploy Agent
                </Button>
            </div>

            <div className="max-w-5xl mx-auto px-4 py-8 space-y-6">
                {/* Stats */}
                {agents.length > 0 && (
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        {[
                            { label: 'Active Agents', value: activeAgents, icon: Bot, color: 'text-indigo-400' },
                            { label: 'Convos Today', value: totalConvos, icon: MessageSquare, color: 'text-blue-400' },
                            { label: 'Qualified Leads', value: totalQualified, icon: CheckCircle2, color: 'text-emerald-400' },
                            { label: 'Avg Reply Time', value: '< 2s', icon: Zap, color: 'text-yellow-400' },
                        ].map(s => (
                            <div key={s.label} className="bg-slate-900 border border-slate-800 rounded-2xl p-4">
                                <s.icon className={`w-4 h-4 ${s.color} mb-2`} />
                                <p className="text-xl font-black text-white">{s.value}</p>
                                <p className="text-[9px] font-black uppercase tracking-wider text-slate-600">{s.label}</p>
                            </div>
                        ))}
                    </div>
                )}

                {/* Dialect info banner */}
                <div className="bg-gradient-to-r from-indigo-500/10 to-violet-500/5 border border-indigo-500/20 rounded-2xl p-4 flex items-start gap-3">
                    <Globe className="w-5 h-5 text-indigo-400 shrink-0 mt-0.5" />
                    <div>
                        <p className="text-sm font-bold text-indigo-300">Arabic-First AI with Regional Dialect Support</p>
                        <p className="text-xs text-slate-400 mt-0.5">Each agent understands and responds naturally in Gulf, Egyptian, or Levantine Arabic. The AI adapts its language and tone based on how the customer writes — no rigid rules needed.</p>
                    </div>
                </div>

                {/* Agent cards */}
                {agents.length > 0 ? (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {agents.map((a, i) => (
                            <AgentCard key={i} agent={a} onToggle={() => handleToggle(i)} onEdit={() => {}} />
                        ))}
                    </div>
                ) : (
                    <div className="py-24 text-center border border-dashed border-slate-800 rounded-2xl">
                        <Brain className="w-12 h-12 text-slate-700 mx-auto mb-4" />
                        <h3 className="text-sm font-bold text-slate-400">No AI Agents deployed yet</h3>
                        <p className="text-xs text-slate-600 mt-2 max-w-md mx-auto">Deploy an autonomous Arabic-speaking AI agent that monitors your WhatsApp number, engages inbound leads 24/7, and qualifies them using your business context — powered by GPT-4o.</p>
                        <Button onClick={() => setShowAdd(true)} className="mt-6 gap-2 h-10 bg-indigo-600 text-white hover:bg-indigo-500 mx-auto shadow-lg shadow-indigo-500/20">
                            <Bot className="w-4 h-4" /> Deploy First Agent
                        </Button>
                    </div>
                )}
            </div>
        </div>
    );
}

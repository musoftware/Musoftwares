import React, { useState, useEffect, useRef } from 'react';
import {
    Smartphone, Shield, TrendingUp, Play, Square, Plus,
    RefreshCw, AlertCircle, CheckCircle2, Activity,
    Thermometer, Zap, Clock, BarChart3, Wifi, X
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';

const getRuntimeHost = () =>
    typeof window !== 'undefined' ? (window.localStorage.getItem('musoftware_runtime_host') || '127.0.0.1') : '127.0.0.1';
const getWsUrl = () => `ws://${getRuntimeHost()}:18401/ws`;

// ── Trust grade badge ─────────────────────────────────────────────────────────
function TrustBadge({ grade }: { grade: string }) {
    const colors: Record<string, string> = {
        'A+': 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        'A':  'bg-emerald-500/15 text-emerald-400 border-emerald-500/20',
        'B':  'bg-blue-500/20 text-blue-400 border-blue-500/30',
        'C':  'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
        'D':  'bg-orange-500/20 text-orange-400 border-orange-500/30',
        'F':  'bg-rose-500/20 text-rose-400 border-rose-500/30',
    };
    return (
        <Badge variant="outline" className={`text-[10px] font-black px-2 py-0.5 rounded-full ${colors[grade] || 'bg-slate-700 text-slate-400 border-slate-600'}`}>
            {grade}
        </Badge>
    );
}

// ── Number Card ───────────────────────────────────────────────────────────────
function NumberCard({ number, onRemove }: { number: any; onRemove: () => void }) {
    const day = number.warmup_day ?? 1;
    const total = 14;
    const pct = Math.min((day / total) * 100, 100);
    const statusColor = number.status === 'running'
        ? 'border-green-500/30 bg-green-500/5'
        : number.status === 'paused'
        ? 'border-yellow-500/30 bg-yellow-500/5'
        : 'border-slate-700 bg-slate-900';

    return (
        <div className={`border rounded-2xl p-5 transition-all ${statusColor}`}>
            <div className="flex items-start justify-between mb-4">
                <div>
                    <div className="flex items-center gap-2 mb-1">
                        <Smartphone className="w-4 h-4 text-slate-400" />
                        <span className="text-sm font-bold text-white">{number.phone}</span>
                        {number.status === 'running' && <span className="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse" />}
                    </div>
                    <p className="text-xs text-slate-500">{number.label || 'Unnamed Number'}</p>
                </div>
                <div className="flex items-center gap-2">
                    <TrustBadge grade={number.trust_grade || 'C'} />
                    <Button variant="ghost" size="icon" onClick={onRemove} className="h-6 w-6 text-slate-600 hover:text-rose-400 hover:bg-transparent">
                        <X className="w-3.5 h-3.5" />
                    </Button>
                </div>
            </div>

            {/* Warmup progress */}
            <div className="mb-3">
                <div className="flex justify-between text-[10px] text-slate-500 mb-1.5">
                    <span>Day {day} of {total} warmup</span>
                    <span>{Math.round(pct)}% complete</span>
                </div>
                <div className="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden">
                    <div className="h-full bg-gradient-to-r from-green-500 to-emerald-400 rounded-full transition-all duration-700" style={{ width: `${pct}%` }} />
                </div>
            </div>

            {/* Stats row */}
            <div className="grid grid-cols-3 gap-3">
                <div className="text-center">
                    <p className="text-sm font-black text-white">{number.trust_score ?? '—'}</p>
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-600">Trust Score</p>
                </div>
                <div className="text-center">
                    <p className="text-sm font-black text-white">{number.msgs_today ?? '0'}</p>
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-600">Msgs Today</p>
                </div>
                <div className="text-center">
                    <p className={`text-sm font-black ${(number.ban_risk ?? 0) > 50 ? 'text-rose-400' : 'text-emerald-400'}`}>{number.ban_risk ?? '0'}%</p>
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-600">Ban Risk</p>
                </div>
            </div>

            {/* Actions */}
            <div className="flex gap-2 mt-4">
                {number.status === 'running' ? (
                    <Button variant="outline" className="flex-1 h-9 bg-yellow-500/10 border-yellow-500/20 text-yellow-400 hover:bg-yellow-500/15 hover:text-yellow-400">
                        <Square className="w-3 h-3 mr-1.5" /> Pause
                    </Button>
                ) : (
                    <Button variant="outline" className="flex-1 h-9 bg-green-500/10 border-green-500/20 text-green-400 hover:bg-green-500/15 hover:text-green-400">
                        <Play className="w-3 h-3 mr-1.5" /> Resume Warmup
                    </Button>
                )}
                <Button variant="outline" size="icon" className="h-9 w-10 bg-slate-800 border-slate-700 text-slate-400 hover:bg-slate-700 hover:text-slate-300">
                    <Activity className="w-3.5 h-3.5" />
                </Button>
            </div>
        </div>
    );
}

// ── Add Number modal ──────────────────────────────────────────────────────────
function AddNumberModal({ onClose, onAdd }: { onClose: () => void; onAdd: (n: any) => void }) {
    const [phone, setPhone] = useState('');
    const [label, setLabel] = useState('');
    return (
        <div className="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div className="w-full max-w-sm bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5">
                <div className="flex items-center justify-between">
                    <h3 className="text-sm font-bold text-white flex items-center gap-2"><Plus className="w-4 h-4 text-green-400" /> Add Number to Warmup</h3>
                    <Button variant="ghost" size="icon" onClick={onClose} className="h-6 w-6 text-slate-600 hover:text-white hover:bg-transparent"><X className="w-4 h-4" /></Button>
                </div>
                <div className="space-y-3">
                    <div>
                        <label className="text-[10px] font-black uppercase tracking-wider text-slate-500 block mb-1.5">Phone Number</label>
                        <Input type="text" value={phone} onChange={e => setPhone(e.target.value)} placeholder="+1 555 000 0000"
                            className="h-10 text-sm bg-slate-800 border-slate-700 focus-visible:ring-green-500 text-white font-mono" />
                    </div>
                    <div>
                        <label className="text-[10px] font-black uppercase tracking-wider text-slate-500 block mb-1.5">Label (optional)</label>
                        <Input type="text" value={label} onChange={e => setLabel(e.target.value)} placeholder="Marketing #1, Agency client..."
                            className="h-10 text-sm bg-slate-800 border-slate-700 focus-visible:ring-green-500 text-white" />
                    </div>
                </div>
                <div className="flex gap-3 pt-2 border-t border-slate-800">
                    <Button variant="outline" onClick={onClose} className="flex-1 h-10 border-slate-800 text-slate-400 hover:bg-slate-800 hover:text-slate-400">Cancel</Button>
                    <Button onClick={() => { if (!phone.trim()) return; onAdd({ phone: phone.trim(), label: label.trim(), status: 'idle', warmup_day: 0, trust_grade: 'C', trust_score: 40, ban_risk: 5, msgs_today: 0 }); onClose(); }}
                        disabled={!phone.trim()}
                        className="flex-1 h-10 bg-green-500 text-white hover:bg-green-600">
                        Start Warmup
                    </Button>
                </div>
            </div>
        </div>
    );
}

// ── Main ──────────────────────────────────────────────────────────────────────
export default function WaWarmupRunner({ tool }: any) {
    const [numbers, setNumbers] = useState<any[]>([]);
    const [showAdd, setShowAdd] = useState(false);
    const [connected, setConnected] = useState(false);
    const wsRef = useRef<WebSocket | null>(null);

    useEffect(() => {
        let ws: WebSocket; let retry: ReturnType<typeof setTimeout>;
        const connect = () => {
            ws = new WebSocket(getWsUrl()); wsRef.current = ws;
            ws.onopen  = () => { setConnected(true); loadNumbers(ws); };
            ws.onclose = () => { setConnected(false); retry = setTimeout(connect, 3000); };
            ws.onerror = () => ws.close();
            ws.onmessage = (e) => {
                try {
                    const msg = JSON.parse(e.data);
                    if (msg.event === 'warmup.health.update') {
                        setNumbers(prev => prev.map(n => n.phone === msg.data.phone ? { ...n, ...msg.data } : n));
                    }
                    if (msg.type === 'plugin_rpc_res' || msg.type === 'plugin_rpc_error') {
                        const r = (ws as any)._pending?.get(msg.requestId);
                        if (r) { msg.type === 'plugin_rpc_error' ? r.reject(new Error(msg.payload?.error)) : r.resolve(msg.payload); (ws as any)._pending?.delete(msg.requestId); }
                    }
                } catch {}
            };
        };
        connect();
        return () => { clearTimeout(retry); ws?.close(); };
    }, []);

    const callRPC = (ws: WebSocket, action: string, data: any = {}): Promise<any> => new Promise((resolve, reject) => {
        if (!(ws as any)._pending) (ws as any)._pending = new Map();
        const requestId = Math.random().toString(36).slice(2);
        (ws as any)._pending.set(requestId, { resolve, reject });
        ws.send(JSON.stringify({ type: 'plugin_rpc', requestId, payload: { plugin: 'wa-warmup', action, data } }));
        setTimeout(() => { (ws as any)._pending?.get(requestId)?.reject(new Error('Timeout')); (ws as any)._pending?.delete(requestId); }, 15000);
    });

    const loadNumbers = async (ws: WebSocket) => {
        try {
            const result = await callRPC(ws, 'warmup.numbers.list', {});
            setNumbers(result?.numbers ?? []);
        } catch {
            // Plugin not yet connected — show empty state
        }
    };

    const handleAdd = (number: any) => {
        setNumbers(prev => [...prev, number]);
        if (wsRef.current?.readyState === WebSocket.OPEN) {
            callRPC(wsRef.current, 'warmup.number.add', number).catch(() => {});
        }
    };

    const totalRunning = numbers.filter(n => n.status === 'running').length;
    const avgTrust = numbers.length ? Math.round(numbers.reduce((a, n) => a + (n.trust_score ?? 40), 0) / numbers.length) : 0;

    if (!connected) return (
        <div className="min-h-screen bg-slate-950 flex items-center justify-center">
            <div className="text-center space-y-3">
                <div className="w-8 h-8 border-2 border-green-500 border-t-transparent rounded-full animate-spin mx-auto" />
                <p className="text-sm font-semibold text-slate-500">Connecting to Runtime...</p>
            </div>
        </div>
    );

    return (
        <div className="min-h-screen bg-slate-950 font-sans text-slate-100">
            {showAdd && <AddNumberModal onClose={() => setShowAdd(false)} onAdd={handleAdd} />}

            {/* Header */}
            <div className="h-14 bg-slate-900/80 backdrop-blur border-b border-slate-800 flex items-center justify-between px-6 sticky top-0 z-10">
                <div className="flex items-center gap-2.5">
                    <div className="w-7 h-7 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center shadow-lg shadow-green-500/20">
                        <Thermometer className="w-4 h-4 text-white" />
                    </div>
                    <span className="font-bold text-sm">WhatsApp Number Warmup</span>
                </div>
                <Button onClick={() => setShowAdd(true)} className="gap-1.5 h-9 bg-green-500 text-white hover:bg-green-600 shadow-lg shadow-green-500/20">
                    <Plus className="w-3.5 h-3.5" /> Add Number
                </Button>
            </div>

            <div className="max-w-5xl mx-auto px-4 py-8 space-y-6">
                {/* Stats */}
                {numbers.length > 0 && (
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        {[
                            { label: 'Numbers Active', value: totalRunning, icon: Wifi, color: 'text-green-400' },
                            { label: 'Avg Trust Score', value: avgTrust, icon: Shield, color: 'text-blue-400' },
                            { label: 'Warming Up', value: numbers.filter(n => n.warmup_day > 0 && n.warmup_day < 14).length, icon: Thermometer, color: 'text-orange-400' },
                            { label: 'Campaign Ready', value: numbers.filter(n => n.warmup_day >= 14 && (n.trust_score ?? 0) >= 70).length, icon: CheckCircle2, color: 'text-emerald-400' },
                        ].map(s => (
                            <div key={s.label} className="bg-slate-900 border border-slate-800 rounded-2xl p-4">
                                <s.icon className={`w-4 h-4 ${s.color} mb-2`} />
                                <p className="text-xl font-black text-white">{s.value}</p>
                                <p className="text-[9px] font-black uppercase tracking-wider text-slate-600">{s.label}</p>
                            </div>
                        ))}
                    </div>
                )}

                {/* Schedule info banner */}
                <div className="bg-gradient-to-r from-green-500/10 to-emerald-500/5 border border-green-500/20 rounded-2xl p-4 flex items-start gap-3">
                    <Clock className="w-5 h-5 text-green-400 shrink-0 mt-0.5" />
                    <div>
                        <p className="text-sm font-bold text-green-300">14-Day Graduated Warmup Schedule</p>
                        <p className="text-xs text-slate-400 mt-0.5">Day 1: 5 messages → Day 7: 50 messages → Day 14: 200 messages. The engine runs automatically during active hours (9AM–9PM), simulating natural human conversation patterns with pool numbers.</p>
                    </div>
                </div>

                {/* Number cards */}
                {numbers.length > 0 ? (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {numbers.map((n, i) => (
                            <NumberCard key={n.phone + i} number={n} onRemove={() => setNumbers(prev => prev.filter((_, idx) => idx !== i))} />
                        ))}
                    </div>
                ) : (
                    <div className="py-24 text-center border border-dashed border-slate-800 rounded-2xl">
                        <Thermometer className="w-10 h-10 text-slate-700 mx-auto mb-4" />
                        <h3 className="text-sm font-bold text-slate-400">No numbers in warmup yet</h3>
                        <p className="text-xs text-slate-600 mt-2 max-w-sm mx-auto">Add a WhatsApp number to start the automated 14-day trust-building schedule. New numbers need warmup before bulk campaigns to avoid bans.</p>
                        <Button onClick={() => setShowAdd(true)} className="mt-6 gap-2 h-10 bg-green-500 text-white hover:bg-green-600 mx-auto shadow-lg shadow-green-500/20">
                            <Plus className="w-4 h-4" /> Add First Number
                        </Button>
                    </div>
                )}
            </div>
        </div>
    );
}

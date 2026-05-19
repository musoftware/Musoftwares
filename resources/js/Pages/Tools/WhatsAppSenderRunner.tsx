import React, { useState, useRef, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import ToolsPublicLayout from '@/Layouts/ToolsPublicLayout';
import { Button } from '@/Components/ui/button';
import {
    ArrowLeft, Play, Square, Settings, Upload, CheckCircle2,
    AlertCircle, Wifi, WifiOff, Users, MessageCircle, FileText,
    Activity, Clock, ShieldCheck, ChevronDown, ChevronUp
} from 'lucide-react';

interface Props {
    tool:         { slug: string; title: string; icon_url: string | null; short_description: string; category: string; runner_component?: string };
    subscription: { plan_name: string; expires_at: string | null };
    runtimePort:  number;
    pluginSlug:   string;
}

export default function WhatsAppSenderRunner({ tool, subscription, runtimePort, pluginSlug }: Props) {
    const base = `http://127.0.0.1:${runtimePort}`;

    const [status, setStatus] = useState<'idle'|'running'|'done'|'error'>('idle');
    const [rtStatus, setRtStatus] = useState<'checking'|'ok'|'offline'>('checking');
    
    // Config state
    const [sessionName, setSessionName] = useState('default');
    const [campaignName, setCampaignName] = useState('');
    const [contactsRaw, setContactsRaw] = useState('');
    const [messagesRaw, setMessagesRaw] = useState('');
    const [preset, setPreset] = useState<'conservative'|'moderate'|'aggressive'>('moderate');
    const [maxBlockRate, setMaxBlockRate] = useState(5);
    
    // Runtime state
    const [taskId, setTaskId] = useState<string | null>(null);
    const [logs, setLogs] = useState<string[]>([]);
    const [progress, setProgress] = useState(0);
    const [errMsg, setErrMsg] = useState('');
    const [result, setResult] = useState<any>(null);
    
    const wsRef = useRef<WebSocket | null>(null);
    const pollRef = useRef<any>(null);
    const logsEnd = useRef<HTMLDivElement>(null);

    useEffect(() => {
        fetch(`${base}/health`).then(r => setRtStatus(r.ok ? 'ok' : 'offline')).catch(() => setRtStatus('offline'));
    }, [base]);

    useEffect(() => { logsEnd.current?.scrollIntoView({ behavior: 'smooth' }); }, [logs]);

    const connectWs = (tid: string) => {
        const ws = new WebSocket(`ws://127.0.0.1:${runtimePort + 1}/ws`);
        ws.onmessage = (ev) => {
            try {
                const msg = JSON.parse(ev.data);
                const d = msg.data ?? {};
                if (d.taskId && d.taskId !== tid) return;
                
                if (msg.event === 'task.log') setLogs(l => [...l, d.message ?? '']);
                if (msg.event === 'task.progress') setProgress(d.percent ?? 0);
                
                if (msg.event === 'task.done') {
                    setResult(d.result ?? {});
                    setStatus('done');
                    setProgress(100);
                }
                if (msg.event === 'task.error') {
                    setErrMsg(d.error ?? 'Unknown error');
                    setStatus('error');
                }
            } catch {}
        };
        wsRef.current = ws;
    };

    const startPolling = (tid: string) => {
        pollRef.current = setInterval(async () => {
            try {
                const r = await fetch(`${base}/tasks/${tid}`);
                const d = await r.json();
                setLogs(d.logs?.map((l: any) => l.message ?? l) ?? []);
                if (d.status === 'done') {
                    setResult(d.result ?? {});
                    setStatus('done');
                    setProgress(100);
                    clearInterval(pollRef.current);
                }
                if (d.status === 'error' || d.status === 'failed') {
                    setErrMsg(d.error ?? 'Task failed');
                    setStatus('error');
                    clearInterval(pollRef.current);
                }
            } catch {}
        }, 1500);
    };

    const handleRun = async () => {
        if (!contactsRaw.trim() || !messagesRaw.trim()) {
            setErrMsg('Contacts and Messages are required.');
            setStatus('error');
            return;
        }

        const contacts = contactsRaw.split('\\n').map(c => c.trim()).filter(c => c);
        const messages = messagesRaw.split('---').map(m => m.trim()).filter(m => m);

        setStatus('running'); setLogs([]); setResult(null); setErrMsg(''); setProgress(0);

        try {
            const res = await fetch(`${base}/plugins/${pluginSlug}/run`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    params: {
                        session_name: sessionName,
                        campaign_name: campaignName || `Campaign ${new Date().toISOString().slice(0,10)}`,
                        contacts,
                        messages,
                        aggressiveness: preset,
                        max_block_rate: maxBlockRate / 100
                    }
                }),
            });
            const data = await res.json();
            if (!res.ok) { setErrMsg(data.message || data.error || 'Runtime error'); setStatus('error'); return; }
            setTaskId(data.taskId);
            connectWs(data.taskId);
            startPolling(data.taskId);
        } catch (e: any) {
            setErrMsg('Cannot reach runtime. Is it running?');
            setStatus('error');
        }
    };

    const handleStop = async () => {
        if (taskId) await fetch(`${base}/tasks/${taskId}/stop`, { method: 'POST' });
        clearInterval(pollRef.current);
        wsRef.current?.close();
        setStatus('idle');
    };

    return (
        <ToolsPublicLayout title={tool.title} activeNav="downloads">
            <Head title={`${tool.title} — Runner`} />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Header */}
                <div className="flex items-center justify-between mb-8">
                    <div className="flex items-center gap-4">
                        <Link href={route('tools.show', tool.slug)} className="text-slate-400 hover:text-slate-600">
                            <ArrowLeft className="h-5 w-5" />
                        </Link>
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center">
                                <MessageCircle className="h-5 w-5 text-green-600" />
                            </div>
                            <div>
                                <h1 className="text-lg font-bold text-slate-900">{tool.title}</h1>
                                <p className="text-xs text-slate-400">{subscription.plan_name} plan</p>
                            </div>
                        </div>
                    </div>
                    <div className={`flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-full border ${
                        rtStatus === 'ok' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                        rtStatus === 'offline' ? 'bg-red-50 text-red-600 border-red-200' : 'bg-slate-50 text-slate-500 border-slate-200'
                    }`}>
                        {rtStatus === 'ok' ? <Wifi className="h-3.5 w-3.5" /> : <WifiOff className="h-3.5 w-3.5" />}
                        {rtStatus === 'ok' ? 'Runtime connected' : rtStatus === 'offline' ? 'Runtime offline' : 'Checking...'}
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Left: Configuration */}
                    <div className="lg:col-span-2 space-y-6">
                        <div className="bg-white border border-slate-200 rounded-xl p-6">
                            <h2 className="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2">
                                <Activity className="h-4 w-4 text-slate-400" /> Campaign Setup
                            </h2>
                            
                            <div className="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label className="block text-xs font-medium text-slate-700 mb-1">Session Name</label>
                                    <input type="text" value={sessionName} onChange={e => setSessionName(e.target.value)}
                                        className="w-full text-sm border-slate-200 rounded-lg focus:ring-green-500 focus:border-green-500" 
                                        placeholder="e.g. Sales_Number_1" />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-slate-700 mb-1">Campaign Name</label>
                                    <input type="text" value={campaignName} onChange={e => setCampaignName(e.target.value)}
                                        className="w-full text-sm border-slate-200 rounded-lg focus:ring-green-500 focus:border-green-500" 
                                        placeholder="Optional" />
                                </div>
                            </div>

                            <div className="mb-4">
                                <label className="block text-xs font-medium text-slate-700 mb-1 flex justify-between">
                                    <span>Contacts (one per line, with country code)</span>
                                    <span className="text-slate-400">{contactsRaw.split('\\n').filter(x=>x.trim()).length} numbers</span>
                                </label>
                                <textarea 
                                    value={contactsRaw} onChange={e => setContactsRaw(e.target.value)}
                                    className="w-full h-32 text-sm font-mono border-slate-200 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    placeholder="201001234567&#10;201007654321"
                                />
                            </div>

                            <div className="mb-4">
                                <label className="block text-xs font-medium text-slate-700 mb-1 flex justify-between">
                                    <span>Message Variations (separate with '---')</span>
                                    <span className="text-slate-400">{messagesRaw.split('---').filter(x=>x.trim()).length} variations</span>
                                </label>
                                <textarea 
                                    value={messagesRaw} onChange={e => setMessagesRaw(e.target.value)}
                                    className="w-full h-32 text-sm border-slate-200 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    placeholder="Hello! Are you interested in our offer?&#10;---&#10;Hi there, checking if you need our services."
                                />
                                <p className="text-[10px] text-slate-500 mt-1">Spin syntax supported: {`{Hello|Hi|Hey}`} there!</p>
                            </div>

                        </div>

                        {/* Error Message */}
                        {status === 'error' && (
                            <div className="flex items-start gap-2.5 bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700">
                                <AlertCircle className="h-4 w-4 shrink-0 mt-0.5" /><span>{errMsg}</span>
                            </div>
                        )}

                        {/* Progress and Logs */}
                        {(status === 'running' || logs.length > 0) && (
                            <div className="bg-slate-900 border border-slate-800 rounded-xl p-4 space-y-3">
                                {status === 'running' && (
                                    <div className="space-y-1.5">
                                        <div className="flex justify-between text-xs text-slate-400">
                                            <span>Campaign Running...</span>
                                            <span>{progress}%</span>
                                        </div>
                                        <div className="h-1.5 bg-slate-700 rounded-full overflow-hidden">
                                            <div className="h-full bg-green-500 rounded-full transition-all duration-500" style={{ width: `${progress}%` }} />
                                        </div>
                                    </div>
                                )}
                                <div className="max-h-64 overflow-y-auto font-mono text-[11px] text-slate-300 space-y-1">
                                    {logs.map((l, i) => <div key={i}>{l}</div>)}
                                    <div ref={logsEnd} />
                                </div>
                            </div>
                        )}
                        
                        {/* Results */}
                        {result && (
                            <div className="bg-green-50 border border-green-200 rounded-xl p-6">
                                <h3 className="text-sm font-bold text-green-800 mb-4 flex items-center gap-2">
                                    <ShieldCheck className="h-4 w-4" /> Campaign Finished
                                </h3>
                                <div className="grid grid-cols-4 gap-4 mb-6">
                                    <div className="bg-white p-3 rounded-lg border border-green-100 text-center">
                                        <div className="text-2xl font-black text-slate-800">{result.stats?.total || 0}</div>
                                        <div className="text-[10px] text-slate-500 uppercase tracking-wider">Processed</div>
                                    </div>
                                    <div className="bg-white p-3 rounded-lg border border-green-100 text-center">
                                        <div className="text-2xl font-black text-green-600">{result.stats?.sent || 0}</div>
                                        <div className="text-[10px] text-slate-500 uppercase tracking-wider">Sent</div>
                                    </div>
                                    <div className="bg-white p-3 rounded-lg border border-green-100 text-center">
                                        <div className="text-2xl font-black text-amber-600">{result.stats?.failed || 0}</div>
                                        <div className="text-[10px] text-slate-500 uppercase tracking-wider">Failed</div>
                                    </div>
                                    <div className="bg-white p-3 rounded-lg border border-green-100 text-center">
                                        <div className="text-2xl font-black text-red-600">{result.stats?.blocked || 0}</div>
                                        <div className="text-[10px] text-slate-500 uppercase tracking-wider">Blocks</div>
                                    </div>
                                </div>
                                
                                {result.health_report && (
                                    <div className="bg-white p-4 rounded-lg border border-green-100">
                                        <h4 className="text-xs font-bold text-slate-800 mb-2">Post-Campaign Health</h4>
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-3">
                                                <div className={`text-xl font-black ${result.health_report.grade === 'A+' || result.health_report.grade === 'A' ? 'text-green-600' : 'text-amber-500'}`}>
                                                    Grade: {result.health_report.grade}
                                                </div>
                                                <div className="text-sm text-slate-600">
                                                    Score: {result.health_report.score}/100
                                                </div>
                                            </div>
                                            <div className="text-xs text-slate-500">
                                                Ban Prob: {(result.health_report.ban_probability * 100).toFixed(1)}%
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>

                    {/* Right: Settings & Actions */}
                    <div className="space-y-6">
                        <div className="bg-white border border-slate-200 rounded-xl p-6">
                            <h2 className="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2">
                                <Settings className="h-4 w-4 text-slate-400" /> Humanizer Settings
                            </h2>
                            
                            <div className="space-y-5">
                                <div>
                                    <label className="block text-xs font-medium text-slate-700 mb-2">Aggressiveness Preset</label>
                                    <div className="space-y-2">
                                        {[
                                            { id: 'conservative', label: 'Conservative', desc: 'Slow typing (35-55 WPM), long delays' },
                                            { id: 'moderate', label: 'Moderate', desc: 'Normal typing (40-70 WPM), avg delays' },
                                            { id: 'aggressive', label: 'Aggressive', desc: 'Fast typing (55-85 WPM), short delays' }
                                        ].map(p => (
                                            <label key={p.id} className={`flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-colors ${preset === p.id ? 'border-green-500 bg-green-50' : 'border-slate-200 hover:border-green-200'}`}>
                                                <input type="radio" name="preset" value={p.id} checked={preset === p.id} onChange={() => setPreset(p.id as any)} className="mt-1 text-green-600 focus:ring-green-500" />
                                                <div>
                                                    <div className={`text-sm font-medium ${preset === p.id ? 'text-green-900' : 'text-slate-900'}`}>{p.label}</div>
                                                    <div className={`text-xs ${preset === p.id ? 'text-green-700' : 'text-slate-500'}`}>{p.desc}</div>
                                                </div>
                                            </label>
                                        ))}
                                    </div>
                                </div>
                                
                                <div>
                                    <div className="flex justify-between text-xs font-medium text-slate-700 mb-1.5">
                                        <span>Auto-Stop Block Rate</span>
                                        <span className="text-red-600 font-semibold">{maxBlockRate}%</span>
                                    </div>
                                    <input type="range" min={1} max={20} step={1} value={maxBlockRate} onChange={e => setMaxBlockRate(Number(e.target.value))} className="w-full accent-red-600" />
                                    <div className="flex justify-between text-[10px] text-slate-400 mt-0.5">
                                        <span>Strict (1%)</span><span>Risky (20%)</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-slate-50 border border-slate-200 rounded-xl p-6">
                            {status === 'running' ? (
                                <Button onClick={handleStop} variant="outline" className="w-full gap-2 border-red-200 text-red-600 hover:bg-red-50">
                                    <Square className="h-4 w-4" /> Stop Campaign
                                </Button>
                            ) : (
                                <Button onClick={handleRun} disabled={rtStatus === 'offline'} className="w-full gap-2 bg-green-600 hover:bg-green-700 text-white h-12 text-base">
                                    <Play className="h-5 w-5" /> Start Campaign
                                </Button>
                            )}
                            <p className="text-center text-[10px] text-slate-400 mt-3">
                                Make sure your WhatsApp is connected in the runtime app before starting.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </ToolsPublicLayout>
    );
}

import React, { useState, useRef, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import ToolsPublicLayout from '@/Layouts/ToolsPublicLayout';
import { Button } from '@/Components/ui/button';
import {
    ArrowLeft, Play, Square, Settings, MessageSquare, Bot,
    AlertCircle, Wifi, WifiOff, Key, Save, PlayCircle,
    Activity, ShieldCheck, Cpu
} from 'lucide-react';

interface Props {
    tool:         { slug: string; title: string; icon_url: string | null; short_description: string; category: string; runner_component?: string };
    subscription: { plan_name: string; expires_at: string | null };
    runtimePort:  number;
    pluginSlug:   string;
}

export default function WaAiAgentRunner({ tool, subscription, runtimePort, pluginSlug }: Props) {
    const base = `http://127.0.0.1:${runtimePort}`;

    const [status, setStatus] = useState<'idle'|'running'|'done'|'error'>('idle');
    const [rtStatus, setRtStatus] = useState<'checking'|'ok'|'offline'>('checking');
    
    // Config state - loaded from localStorage if available
    const [sessionName, setSessionName] = useState(() => localStorage.getItem('wa_ai_session') || 'agent_1');
    const [apiKey, setApiKey] = useState(() => localStorage.getItem('wa_ai_api_key') || '');
    const [systemPrompt, setSystemPrompt] = useState(() => localStorage.getItem('wa_ai_system_prompt') || 'You are an AI sales agent for a real estate agency in Dubai. You sell luxury apartments.');
    const [goal, setGoal] = useState(() => localStorage.getItem('wa_ai_goal') || 'Qualify the lead budget and get them to book a viewing appointment.');
    const [aggressiveness, setAggressiveness] = useState<'conservative'|'moderate'|'aggressive'>(() => (localStorage.getItem('wa_ai_aggressiveness') as any) || 'moderate');
    
    // Runtime state
    const [taskId, setTaskId] = useState<string | null>(null);
    const [logs, setLogs] = useState<{timestamp: number, text: string, type: 'info'|'msg_in'|'msg_out'|'error'|'sys'}[]>([]);
    const [progress, setProgress] = useState(0);
    const [errMsg, setErrMsg] = useState('');
    const [qrCode, setQrCode] = useState<string | null>(null);
    
    const wsRef = useRef<WebSocket | null>(null);
    const pollRef = useRef<any>(null);
    const logsEnd = useRef<HTMLDivElement>(null);

    useEffect(() => {
        fetch(`${base}/health`).then(r => setRtStatus(r.ok ? 'ok' : 'offline')).catch(() => setRtStatus('offline'));
    }, [base]);

    useEffect(() => { logsEnd.current?.scrollIntoView({ behavior: 'smooth' }); }, [logs]);

    // Save settings to local storage when they change
    useEffect(() => {
        localStorage.setItem('wa_ai_session', sessionName);
        localStorage.setItem('wa_ai_api_key', apiKey);
        localStorage.setItem('wa_ai_system_prompt', systemPrompt);
        localStorage.setItem('wa_ai_goal', goal);
        localStorage.setItem('wa_ai_aggressiveness', aggressiveness);
    }, [sessionName, apiKey, systemPrompt, goal, aggressiveness]);

    const connectWs = (tid: string) => {
        const ws = new WebSocket(`ws://127.0.0.1:${runtimePort + 1}/ws`);
        ws.onmessage = (ev) => {
            try {
                const msg = JSON.parse(ev.data);
                const d = msg.data ?? {};
                if (d.taskId && d.taskId !== tid) return;
                
                if (msg.event === 'task.progress') {
                    setProgress(d.percent ?? 0);
                    if (d.qr) setQrCode(d.qr);
                    if (d.percent >= 60) setQrCode(null); // auth done
                }
                
                if (msg.event === 'task.log') {
                    const text = d.message ?? '';
                    let type: 'info'|'msg_in'|'msg_out'|'error'|'sys' = 'info';
                    
                    if (text.includes('📨 Received message')) type = 'msg_in';
                    else if (text.includes('💬 Replying to') || text.includes('✅ Message sent')) type = 'msg_out';
                    else if (text.includes('Failed') || text.includes('Error')) type = 'error';
                    else if (text.includes('🤖') || text.includes('✅')) type = 'sys';
                    
                    setLogs(l => [...l, { timestamp: Date.now(), text, type }]);
                }
                
                if (msg.event === 'task.done') {
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
                if (d.status === 'done') {
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
        if (!apiKey.trim() || !systemPrompt.trim()) {
            setErrMsg('OpenAI API Key and Business Context are required.');
            setStatus('error');
            return;
        }

        setStatus('running'); setLogs([]); setErrMsg(''); setProgress(0); setQrCode(null);

        try {
            const res = await fetch(`${base}/plugins/${pluginSlug}/run`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    params: {
                        session_name: sessionName,
                        api_key: apiKey.trim(),
                        system_prompt: systemPrompt.trim(),
                        goal: goal.trim(),
                        aggressiveness
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
                            <div className="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center">
                                <Bot className="h-5 w-5 text-purple-600" />
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

                <div className="grid grid-cols-1 xl:grid-cols-3 gap-6">
                    {/* Left: Configuration */}
                    <div className="xl:col-span-1 space-y-6">
                        <div className="bg-white border border-slate-200 rounded-xl p-6">
                            <h2 className="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2">
                                <Settings className="h-4 w-4 text-slate-400" /> Agent Settings
                            </h2>
                            
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-xs font-medium text-slate-700 mb-1 flex items-center gap-1.5">
                                        <Key className="h-3 w-3" /> OpenAI API Key
                                    </label>
                                    <input type="password" value={apiKey} onChange={e => setApiKey(e.target.value)}
                                        className="w-full text-sm border-slate-200 rounded-lg focus:ring-purple-500 focus:border-purple-500 font-mono" 
                                        placeholder="sk-..." />
                                    <p className="text-[10px] text-slate-400 mt-1">Saved locally in your browser.</p>
                                </div>
                                
                                <div>
                                    <label className="block text-xs font-medium text-slate-700 mb-1">Session Name</label>
                                    <input type="text" value={sessionName} onChange={e => setSessionName(e.target.value)}
                                        className="w-full text-sm border-slate-200 rounded-lg focus:ring-purple-500 focus:border-purple-500" />
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-700 mb-1">Business Context</label>
                                    <textarea 
                                        value={systemPrompt} onChange={e => setSystemPrompt(e.target.value)}
                                        className="w-full h-32 text-sm border-slate-200 rounded-lg focus:ring-purple-500 focus:border-purple-500"
                                        placeholder="Who are you? What are your prices? What services do you offer?"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-700 mb-1">Conversion Goal</label>
                                    <textarea 
                                        value={goal} onChange={e => setGoal(e.target.value)}
                                        className="w-full h-16 text-sm border-slate-200 rounded-lg focus:ring-purple-500 focus:border-purple-500"
                                        placeholder="Get the user to book a meeting at link..."
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-700 mb-2">Typing Speed</label>
                                    <select value={aggressiveness} onChange={e => setAggressiveness(e.target.value as any)}
                                        className="w-full text-sm border-slate-200 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                                        <option value="conservative">Slow (35-55 WPM)</option>
                                        <option value="moderate">Normal (40-70 WPM)</option>
                                        <option value="aggressive">Fast (55-85 WPM)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div className="bg-purple-50 border border-purple-200 rounded-xl p-6">
                            {status === 'running' ? (
                                <Button onClick={handleStop} variant="outline" className="w-full gap-2 border-red-200 text-red-600 hover:bg-red-50">
                                    <Square className="h-4 w-4" /> Stop Agent
                                </Button>
                            ) : (
                                <Button onClick={handleRun} disabled={rtStatus === 'offline'} className="w-full gap-2 bg-purple-600 hover:bg-purple-700 text-white h-12 text-base">
                                    <PlayCircle className="h-5 w-5" /> Activate Agent
                                </Button>
                            )}
                        </div>
                    </div>

                    {/* Right: Live Monitor */}
                    <div className="xl:col-span-2 space-y-6">
                        {/* Error Message */}
                        {status === 'error' && (
                            <div className="flex items-start gap-2.5 bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700">
                                <AlertCircle className="h-4 w-4 shrink-0 mt-0.5" /><span>{errMsg}</span>
                            </div>
                        )}

                        {/* Status Bar */}
                        <div className="bg-white border border-slate-200 rounded-xl p-6 flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className={`relative flex h-3 w-3`}>
                                    {status === 'running' && <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-purple-400 opacity-75"></span>}
                                    <span className={`relative inline-flex rounded-full h-3 w-3 ${status === 'running' ? 'bg-purple-500' : 'bg-slate-300'}`}></span>
                                </div>
                                <div>
                                    <h3 className="text-sm font-bold text-slate-900">{status === 'running' ? 'Agent Online & Listening' : 'Agent Offline'}</h3>
                                    <p className="text-xs text-slate-500">
                                        {status === 'running' ? 'The AI is actively monitoring inbound messages.' : 'Configure settings and click Activate to start.'}
                                    </p>
                                </div>
                            </div>
                            <Cpu className={`h-8 w-8 ${status === 'running' ? 'text-purple-500 animate-pulse' : 'text-slate-300'}`} />
                        </div>

                        {/* QR Code display */}
                        {qrCode && (
                            <div className="bg-white border border-slate-200 rounded-xl p-8 flex flex-col items-center justify-center text-center">
                                <h3 className="text-sm font-bold text-slate-900 mb-2">WhatsApp Authentication Required</h3>
                                <p className="text-xs text-slate-500 mb-6 max-w-sm">Scan this QR code with the WhatsApp app on your phone to connect the AI Agent.</p>
                                <div className="p-4 bg-white rounded-xl shadow-sm border border-slate-100">
                                    <img src={qrCode} alt="WhatsApp QR Code" className="w-64 h-64" />
                                </div>
                            </div>
                        )}

                        {/* Live Log View */}
                        <div className="bg-slate-900 border border-slate-800 rounded-xl flex flex-col overflow-hidden" style={{ height: '600px' }}>
                            <div className="border-b border-slate-800 bg-slate-950 p-4 flex justify-between items-center shrink-0">
                                <h3 className="text-xs font-bold text-slate-300 flex items-center gap-2">
                                    <Activity className="h-4 w-4" /> Live Operation Feed
                                </h3>
                                {status === 'running' && (
                                    <div className="flex items-center gap-2 text-[10px] text-purple-400 bg-purple-500/10 px-2 py-1 rounded-full">
                                        <div className="w-1.5 h-1.5 rounded-full bg-purple-400 animate-pulse" /> Live
                                    </div>
                                )}
                            </div>
                            
                            <div className="flex-1 overflow-y-auto p-4 space-y-3 font-mono text-[11px]">
                                {logs.length === 0 ? (
                                    <div className="flex flex-col items-center justify-center h-full text-slate-600">
                                        <MessageSquare className="h-8 w-8 mb-2 opacity-50" />
                                        <p>No activity yet. Logs will appear here.</p>
                                    </div>
                                ) : (
                                    logs.map((l, i) => {
                                        const time = new Date(l.timestamp).toLocaleTimeString([], { hour12: false });
                                        
                                        if (l.type === 'msg_in') {
                                            return (
                                                <div key={i} className="flex flex-col items-start max-w-[80%]">
                                                    <span className="text-[9px] text-slate-500 mb-0.5">{time} - Inbound</span>
                                                    <div className="bg-slate-800 text-emerald-400 px-3 py-2 rounded-lg rounded-tl-none border border-slate-700">
                                                        {l.text}
                                                    </div>
                                                </div>
                                            );
                                        }
                                        
                                        if (l.type === 'msg_out') {
                                            return (
                                                <div key={i} className="flex flex-col items-end self-end max-w-[80%] ml-auto">
                                                    <span className="text-[9px] text-slate-500 mb-0.5">{time} - AI Reply</span>
                                                    <div className="bg-purple-900/50 text-purple-200 px-3 py-2 rounded-lg rounded-tr-none border border-purple-800/50">
                                                        {l.text}
                                                    </div>
                                                </div>
                                            );
                                        }

                                        if (l.type === 'error') {
                                            return <div key={i} className="text-red-400 flex gap-2"><span className="text-slate-600">[{time}]</span> {l.text}</div>;
                                        }

                                        return <div key={i} className="text-slate-400 flex gap-2"><span className="text-slate-600">[{time}]</span> {l.text}</div>;
                                    })
                                )}
                                <div ref={logsEnd} />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </ToolsPublicLayout>
    );
}

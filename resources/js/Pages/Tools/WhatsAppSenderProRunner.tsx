import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import ToolsPublicLayout from '@/Layouts/ToolsPublicLayout';
import { Button } from '@/Components/ui/button';
import { Play, Square, Settings, Send, Phone, UserPlus, Upload, ShieldCheck, Activity } from 'lucide-react';
import { useRuntimeTask } from '@/hooks/useRuntimeTask';

interface Props {
    tool: { slug: string; title: string; icon_url: string | null; short_description: string; category: string; runner_component?: string };
    subscription: { plan_name: string; expires_at: string | null };
    runtimePort: number;
    pluginSlug: string;
}

export default function WhatsAppSenderProRunner({ tool, subscription, runtimePort, pluginSlug }: Props) {
    const [actionTab, setActionTab] = useState<'session' | 'campaign' | 'ai'>('session');
    const [targetNumber, setTargetNumber] = useState('');
    const [message, setMessage] = useState('');
    const [qrCodeData, setQrCodeData] = useState<string | null>(null);

    const {
        runTask,
        stopTask,
        isRunning,
        status,
        logs,
        progress,
        result,
        error
    } = useRuntimeTask(pluginSlug, { runtimePort });

    // Listen to custom IPC events emitted by worker.js
    useEffect(() => {
        const ws = new WebSocket(`ws://127.0.0.1:${runtimePort}/ws`);
        ws.onmessage = (ev) => {
            try {
                const msg = JSON.parse(ev.data);
                const data = msg.data || {};
                
                if (msg.event === 'task.custom') {
                    if (data.type === 'qr_updated') {
                        setQrCodeData(data.qr_data);
                    }
                }
            } catch (e) {}
        };
        return () => ws.close();
    }, [runtimePort]);

    const handleCreateSession = () => {
        setQrCodeData(null);
        runTask({ action: 'create_session', payload: { session_id: 'default' } });
    };

    const handleSendMessage = () => {
        if (!targetNumber || !message) return;
        runTask({ action: 'send_message', payload: { to: targetNumber, message, session_id: 'default' } });
    };

    return (
        <ToolsPublicLayout title={tool.title} activeNav="downloads">
            <Head title={`${tool.title} — Runner`} />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Header */}
                <div className="flex items-center gap-4 mb-8 border-b pb-6">
                    <div className="w-14 h-14 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                        <Phone className="w-8 h-8" />
                    </div>
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">{tool.title}</h1>
                        <p className="text-sm text-slate-500">{tool.short_description}</p>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-[300px_1fr] gap-6">
                    
                    {/* Left Sidebar Menu */}
                    <div className="space-y-2">
                        <button
                            onClick={() => setActionTab('session')}
                            className={`w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors ${actionTab === 'session' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-white border text-slate-600 hover:bg-slate-50'}`}
                        >
                            <ShieldCheck className="w-4 h-4" /> Session & Devices
                        </button>
                        <button
                            onClick={() => setActionTab('campaign')}
                            className={`w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors ${actionTab === 'campaign' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-white border text-slate-600 hover:bg-slate-50'}`}
                        >
                            <Send className="w-4 h-4" /> Campaigns
                        </button>
                        <button
                            onClick={() => setActionTab('ai')}
                            className={`w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors ${actionTab === 'ai' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-white border text-slate-600 hover:bg-slate-50'}`}
                        >
                            <Settings className="w-4 h-4" /> AI Auto-Replies
                        </button>
                    </div>

                    {/* Main Workspace */}
                    <div className="space-y-6">
                        {/* Tab Content */}
                        <div className="bg-white border rounded-xl p-6 shadow-sm min-h-[400px]">
                            {actionTab === 'session' && (
                                <div className="space-y-6">
                                    <h2 className="text-lg font-bold">Device Sessions</h2>
                                    <p className="text-sm text-slate-500 mb-4">Connect a WhatsApp device to start automating.</p>
                                    
                                    <div className="flex gap-4">
                                        <Button onClick={handleCreateSession} disabled={isRunning} className="gap-2 bg-emerald-600 hover:bg-emerald-500">
                                            <Phone className="w-4 h-4" /> Initialize Session
                                        </Button>
                                        {isRunning && (
                                            <Button onClick={stopTask} variant="outline" className="gap-2 text-red-600">
                                                <Square className="w-4 h-4" /> Stop Engine
                                            </Button>
                                        )}
                                    </div>

                                    {qrCodeData && (
                                        <div className="mt-6 p-6 border rounded-xl bg-slate-50 flex flex-col items-center">
                                            <h3 className="text-md font-bold mb-2">Scan QR to connect</h3>
                                            <p className="text-xs text-slate-500 mb-4">Open WhatsApp on your phone &gt; Linked Devices &gt; Link a Device</p>
                                            <img src={qrCodeData} alt="WhatsApp QR Code" className="w-64 h-64 border rounded" />
                                        </div>
                                    )}
                                </div>
                            )}

                            {actionTab === 'campaign' && (
                                <div className="space-y-6">
                                    <h2 className="text-lg font-bold">Quick Send & Campaigns</h2>
                                    
                                    <div className="space-y-4 max-w-md">
                                        <div>
                                            <label className="text-xs font-bold text-slate-500">Target Number (with country code)</label>
                                            <input 
                                                value={targetNumber}
                                                onChange={e => setTargetNumber(e.target.value)}
                                                className="w-full mt-1 px-3 py-2 border rounded-lg focus:ring focus:ring-indigo-200 outline-none"
                                                placeholder="+1234567890"
                                            />
                                        </div>
                                        <div>
                                            <label className="text-xs font-bold text-slate-500">Message</label>
                                            <textarea 
                                                value={message}
                                                onChange={e => setMessage(e.target.value)}
                                                rows={4}
                                                className="w-full mt-1 px-3 py-2 border rounded-lg focus:ring focus:ring-indigo-200 outline-none"
                                                placeholder="Hello, this is an automated message."
                                            />
                                        </div>
                                        <Button onClick={handleSendMessage} disabled={isRunning} className="w-full gap-2 bg-indigo-600">
                                            <Send className="w-4 h-4" /> Send Message
                                        </Button>
                                    </div>
                                </div>
                            )}

                            {actionTab === 'ai' && (
                                <div className="space-y-6">
                                    <h2 className="text-lg font-bold">AI Auto-Reply Configuration</h2>
                                    <p className="text-sm text-slate-500">Configure GPT-4 behavior for incoming messages.</p>
                                    <div className="p-4 bg-amber-50 text-amber-800 rounded-lg border border-amber-200 text-sm">
                                        Enable session persistence and connect a valid OpenAI key in global settings to activate this feature.
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Runtime Terminal */}
                        <div className="bg-slate-900 border border-slate-800 rounded-xl p-4">
                            <div className="flex items-center justify-between mb-2">
                                <h3 className="text-slate-400 text-xs font-bold uppercase tracking-wider flex items-center gap-2">
                                    <Activity className="w-3 h-3" /> Runtime Output
                                </h3>
                                {isRunning && <span className="text-emerald-400 text-xs animate-pulse">● Live</span>}
                            </div>
                            
                            {progress > 0 && progress < 100 && (
                                <div className="h-1 bg-slate-700 rounded-full mb-3">
                                    <div className="h-full bg-emerald-500 rounded-full" style={{ width: `${progress}%` }}></div>
                                </div>
                            )}

                            <div className="h-48 overflow-y-auto font-mono text-[11px] text-slate-300 space-y-1">
                                {logs.length === 0 ? (
                                    <span className="text-slate-600">No active tasks...</span>
                                ) : (
                                    logs.map((log, i) => (
                                        <div key={i}>{log}</div>
                                    ))
                                )}
                                {error && <div className="text-red-400 mt-2">Error: {error}</div>}
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </ToolsPublicLayout>
    );
}

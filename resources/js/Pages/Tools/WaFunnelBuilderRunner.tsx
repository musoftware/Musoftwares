import React, { useState, useCallback, useRef, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import ToolsPublicLayout from '@/Layouts/ToolsPublicLayout';
import { Button } from '@/Components/ui/button';
import {
    ArrowLeft, Play, Square, Settings, Save, AlertCircle, Wifi, WifiOff, Box, MessageCircle, Clock
} from 'lucide-react';
import ReactFlow, {
    MiniMap, Controls, Background, useNodesState, useEdgesState, addEdge,
    Handle, Position
} from 'reactflow';
import 'reactflow/dist/style.css';

// ── Custom Nodes ─────────────────────────────────────────────────────────────
const MessageNode = ({ data }: any) => (
    <div className="bg-white border-2 border-green-500 rounded-lg shadow-sm min-w-[200px]">
        <Handle type="target" position={Position.Top} className="w-3 h-3 !bg-green-500" />
        <div className="bg-green-50 px-3 py-2 border-b border-green-100 rounded-t-md flex items-center gap-2">
            <MessageCircle className="h-4 w-4 text-green-600" />
            <span className="text-xs font-bold text-green-900">Send Message</span>
        </div>
        <div className="p-3">
            <textarea 
                className="w-full text-[10px] p-2 border border-slate-200 rounded resize-none focus:border-green-500 focus:ring-1 focus:ring-green-500" 
                rows={3} 
                defaultValue={data.message}
                onChange={(e) => data.onChange(e.target.value)}
                placeholder="Message text..."
            />
        </div>
        <Handle type="source" position={Position.Bottom} className="w-3 h-3 !bg-green-500" />
    </div>
);

const DelayNode = ({ data }: any) => (
    <div className="bg-white border-2 border-orange-400 rounded-lg shadow-sm min-w-[150px]">
        <Handle type="target" position={Position.Top} className="w-3 h-3 !bg-orange-400" />
        <div className="bg-orange-50 px-3 py-2 border-b border-orange-100 rounded-t-md flex items-center gap-2">
            <Clock className="h-4 w-4 text-orange-600" />
            <span className="text-xs font-bold text-orange-900">Wait / Delay</span>
        </div>
        <div className="p-3 flex items-center gap-2">
            <input 
                type="number" className="w-16 text-xs p-1 border border-slate-200 rounded" 
                defaultValue={data.minutes} 
                onChange={(e) => data.onChange(e.target.value)}
            />
            <span className="text-xs text-slate-600">Minutes</span>
        </div>
        <Handle type="source" position={Position.Bottom} className="w-3 h-3 !bg-orange-400" />
    </div>
);

const nodeTypes = {
    messageNode: MessageNode,
    delayNode: DelayNode,
};

const initialNodes = [
    { id: 'start', type: 'input', data: { label: 'Start Campaign' }, position: { x: 250, y: 50 }, className: 'border-2 border-slate-800 bg-slate-900 text-white font-bold rounded-lg px-6 py-3' },
    { id: 'node-1', type: 'messageNode', position: { x: 250, y: 150 }, data: { message: 'Hi {name}! Are you still looking for properties in Dubai?' } },
];
const initialEdges = [
    { id: 'e-start-1', source: 'start', target: 'node-1', animated: true },
];

interface Props {
    tool:         { slug: string; title: string; icon_url: string | null; short_description: string; category: string; runner_component?: string };
    subscription: { plan_name: string; expires_at: string | null };
    runtimePort:  number;
    pluginSlug:   string;
}

export default function WaFunnelBuilderRunner({ tool, subscription, runtimePort, pluginSlug }: Props) {
    const base = `http://127.0.0.1:${runtimePort}`;

    const [status, setStatus] = useState<'idle'|'running'|'done'|'error'>('idle');
    const [rtStatus, setRtStatus] = useState<'checking'|'ok'|'offline'>('checking');
    
    // ReactFlow state
    const [nodes, setNodes, onNodesChange] = useNodesState(initialNodes as any);
    const [edges, setEdges, onEdgesChange] = useEdgesState(initialEdges);

    // Engine state
    const [sessionName, setSessionName] = useState('funnel_engine_1');
    const [contactsRaw, setContactsRaw] = useState('');
    const [taskId, setTaskId] = useState<string | null>(null);
    const [logs, setLogs] = useState<string[]>([]);
    const [progress, setProgress] = useState(0);
    const [errMsg, setErrMsg] = useState('');
    
    const wsRef = useRef<WebSocket | null>(null);
    const pollRef = useRef<any>(null);

    useEffect(() => {
        fetch(`${base}/health`).then(r => setRtStatus(r.ok ? 'ok' : 'offline')).catch(() => setRtStatus('offline'));
    }, [base]);

    const onConnect = useCallback((params: any) => setEdges((eds) => addEdge({ ...params, animated: true }, eds)), [setEdges]);

    const addNode = (type: 'messageNode' | 'delayNode') => {
        const id = `node-${Date.now()}`;
        const newNode = {
            id,
            type,
            position: { x: Math.random() * 200 + 100, y: Math.random() * 200 + 100 },
            data: { 
                message: type === 'messageNode' ? 'New Message' : undefined,
                minutes: type === 'delayNode' ? '60' : undefined,
                onChange: (val: string) => {
                    setNodes((nds) => nds.map((n) => {
                        if (n.id === id) {
                            n.data = { ...n.data, [type === 'messageNode' ? 'message' : 'minutes']: val };
                        }
                        return n;
                    }));
                }
            },
        };
        setNodes((nds) => nds.concat(newNode as any));
    };

    const handleRun = async () => {
        if (!contactsRaw.trim()) { setErrMsg('Enter at least one contact.'); setStatus('error'); return; }

        const contacts = contactsRaw.split('\\n').map(c => c.trim()).filter(c => c);
        setStatus('running'); setLogs([]); setErrMsg(''); setProgress(0);

        try {
            const res = await fetch(`${base}/plugins/${pluginSlug}/run`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    params: {
                        session_name: sessionName,
                        nodes,
                        edges,
                        contacts,
                        aggressiveness: 'moderate'
                    }
                }),
            });
            const data = await res.json();
            if (!res.ok) { setErrMsg(data.message || 'Runtime error'); setStatus('error'); return; }
            setTaskId(data.taskId);
            
            // Connect WS
            const ws = new WebSocket(`ws://127.0.0.1:${runtimePort + 1}/ws`);
            ws.onmessage = (ev) => {
                const msg = JSON.parse(ev.data);
                const d = msg.data ?? {};
                if (d.taskId && d.taskId !== data.taskId) return;
                
                if (msg.event === 'task.log') setLogs(l => [d.message ?? '', ...l].slice(0, 50));
                if (msg.event === 'task.progress') setProgress(d.percent ?? 0);
                if (msg.event === 'task.done') { setStatus('done'); setProgress(100); }
                if (msg.event === 'task.error') { setErrMsg(d.error ?? 'Error'); setStatus('error'); }
            };
            wsRef.current = ws;
        } catch (e) {
            setErrMsg('Cannot reach runtime. Is it running?');
            setStatus('error');
        }
    };

    const handleStop = async () => {
        if (taskId) await fetch(`${base}/tasks/${taskId}/stop`, { method: 'POST' });
        wsRef.current?.close();
        setStatus('idle');
    };

    return (
        <ToolsPublicLayout title={tool.title} activeNav="downloads">
            <Head title={`${tool.title} — Runner`} />

            <div className="max-w-[1600px] mx-auto px-4 sm:px-6 py-4 h-[calc(100vh-64px)] flex flex-col">
                {/* Header */}
                <div className="flex items-center justify-between mb-4 shrink-0">
                    <div className="flex items-center gap-4">
                        <Link href={route('tools.show', tool.slug)} className="text-slate-400 hover:text-slate-600">
                            <ArrowLeft className="h-5 w-5" />
                        </Link>
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                                <Box className="h-5 w-5 text-blue-600" />
                            </div>
                            <div>
                                <h1 className="text-lg font-bold text-slate-900">{tool.title}</h1>
                                <p className="text-xs text-slate-400">Drag & Drop Workflow Builder</p>
                            </div>
                        </div>
                    </div>
                    <div className="flex items-center gap-3">
                        <div className={`flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-full border ${
                            rtStatus === 'ok' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                            rtStatus === 'offline' ? 'bg-red-50 text-red-600 border-red-200' : 'bg-slate-50 text-slate-500 border-slate-200'
                        }`}>
                            {rtStatus === 'ok' ? <Wifi className="h-3.5 w-3.5" /> : <WifiOff className="h-3.5 w-3.5" />}
                            {rtStatus === 'ok' ? 'Runtime connected' : 'Runtime offline'}
                        </div>
                        <Button variant="outline" className="gap-2 h-9 text-xs"><Save className="h-3.5 w-3.5" /> Save Funnel</Button>
                        {status === 'running' ? (
                            <Button onClick={handleStop} variant="destructive" className="gap-2 h-9 text-xs bg-red-600">
                                <Square className="h-3.5 w-3.5" /> Stop
                            </Button>
                        ) : (
                            <Button onClick={handleRun} disabled={rtStatus !== 'ok'} className="gap-2 h-9 text-xs bg-blue-600 hover:bg-blue-700 text-white">
                                <Play className="h-3.5 w-3.5" /> Run Funnel
                            </Button>
                        )}
                    </div>
                </div>

                <div className="flex gap-4 flex-1 min-h-0">
                    {/* Left Panel: Tools & Config */}
                    <div className="w-64 bg-white border border-slate-200 rounded-xl p-4 flex flex-col shrink-0">
                        <h3 className="text-xs font-bold text-slate-900 mb-3 uppercase tracking-wider">Nodes</h3>
                        <div className="space-y-2 mb-6">
                            <button onClick={() => addNode('messageNode')} className="w-full flex items-center gap-2 p-2 bg-slate-50 border border-slate-200 rounded-lg hover:border-green-400 hover:bg-green-50 transition-colors text-left">
                                <MessageCircle className="h-4 w-4 text-green-600" />
                                <span className="text-xs font-medium text-slate-700">Send Message</span>
                            </button>
                            <button onClick={() => addNode('delayNode')} className="w-full flex items-center gap-2 p-2 bg-slate-50 border border-slate-200 rounded-lg hover:border-orange-400 hover:bg-orange-50 transition-colors text-left">
                                <Clock className="h-4 w-4 text-orange-600" />
                                <span className="text-xs font-medium text-slate-700">Time Delay</span>
                            </button>
                        </div>

                        <h3 className="text-xs font-bold text-slate-900 mb-3 uppercase tracking-wider">Campaign Target</h3>
                        <div className="flex-1 flex flex-col">
                            <label className="block text-xs font-medium text-slate-700 mb-1">Session Name</label>
                            <input type="text" value={sessionName} onChange={e => setSessionName(e.target.value)}
                                className="w-full text-xs border-slate-200 rounded-lg mb-3" />
                            
                            <label className="block text-xs font-medium text-slate-700 mb-1">Contacts (1 per line)</label>
                            <textarea 
                                value={contactsRaw} onChange={e => setContactsRaw(e.target.value)}
                                className="w-full flex-1 text-xs font-mono border-slate-200 rounded-lg resize-none"
                                placeholder="201001234567&#10;201007654321"
                            />
                        </div>
                    </div>

                    {/* Center: Canvas */}
                    <div className="flex-1 bg-slate-50 border border-slate-200 rounded-xl overflow-hidden relative">
                        <ReactFlow
                            nodes={nodes}
                            edges={edges}
                            onNodesChange={onNodesChange}
                            onEdgesChange={onEdgesChange}
                            onConnect={onConnect}
                            nodeTypes={nodeTypes}
                            fitView
                        >
                            <Controls />
                            <MiniMap />
                            <Background gap={12} size={1} />
                        </ReactFlow>

                        {/* Error Overlay */}
                        {errMsg && (
                            <div className="absolute top-4 left-1/2 -translate-x-1/2 z-10 flex items-center gap-2 bg-red-50 text-red-600 px-4 py-2 rounded-lg shadow border border-red-200 text-sm">
                                <AlertCircle className="h-4 w-4" /> {errMsg}
                            </div>
                        )}
                    </div>

                    {/* Right Panel: Live Logs */}
                    <div className="w-80 bg-slate-900 border border-slate-800 rounded-xl flex flex-col shrink-0 overflow-hidden">
                        <div className="p-4 border-b border-slate-800 bg-slate-950">
                            <h3 className="text-xs font-bold text-slate-300">Live Execution Engine</h3>
                            {status === 'running' && (
                                <div className="mt-2">
                                    <div className="h-1 bg-slate-800 rounded-full overflow-hidden">
                                        <div className="h-full bg-blue-500 transition-all" style={{width: `${progress}%`}} />
                                    </div>
                                </div>
                            )}
                        </div>
                        <div className="flex-1 p-4 font-mono text-[10px] text-slate-400 overflow-y-auto space-y-1">
                            {logs.length === 0 ? 'Ready.' : logs.map((l, i) => <div key={i}>{l}</div>)}
                        </div>
                    </div>
                </div>
            </div>
        </ToolsPublicLayout>
    );
}

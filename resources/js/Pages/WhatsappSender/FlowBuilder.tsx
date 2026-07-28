import React, { useState, useRef, useEffect } from 'react';
import { useForm } from '@inertiajs/react';

interface Node {
    id: string;
    type: 'trigger' | 'message' | 'delay' | 'condition' | 'action';
    x: number;
    y: number;
    data: {
        message_text?: string;
        buttons?: Array<{ label: string; target_node_id: string; value: string }>;
        seconds?: number;
        condition_type?: 'wallet_balance' | 'custom_field';
        field_name?: string;
        operator?: string;
        value?: string;
        action_type?: 'set_field' | 'webhook';
        field_name_action?: string;
        field_value_action?: string;
        webhook_url?: string;
    };
}

interface Edge {
    id: string;
    source: string;
    target: string;
    sourceHandle?: 'out' | 'true' | 'false';
    targetHandle?: 'in';
}

interface BotFlow {
    id?: number;
    name: string;
    channel: 'whatsapp' | 'telegram';
    telegram_bot_id?: number | null;
    is_active: boolean;
    trigger_type: 'keyword' | 'start_bot' | 'default';
    trigger_keywords: string[];
    nodes: Node[];
    edges: Edge[];
}

interface FlowBuilderProps {
    flow?: BotFlow | null;
    whatsappBusinessId: number;
    channel: 'whatsapp' | 'telegram';
    telegramBotId?: number | null;
    bots: Array<{ id: number; name: string }>;
    onClose: () => void;
}

export default function FlowBuilder({
    flow,
    whatsappBusinessId,
    channel,
    telegramBotId,
    bots,
    onClose
}: FlowBuilderProps) {
    // Generate UUID helper
    const uuid = () => Math.random().toString(36).substring(2, 9);

    const isEdit = !!flow;

    const { data, setData, post, put, processing } = useForm({
        whatsapp_business_id: whatsappBusinessId,
        channel: channel,
        telegram_bot_id: telegramBotId || (flow?.telegram_bot_id ?? null),
        name: flow?.name ?? 'New Chatbot Flow',
        is_active: flow?.is_active ?? false,
        trigger_type: flow?.trigger_type ?? 'keyword',
        trigger_keywords: flow?.trigger_keywords ?? [],
        nodes: flow?.nodes ?? [
            { id: 'node_trigger', type: 'trigger', x: 50, y: 150, data: {} }
        ],
        edges: flow?.edges ?? []
    });

    const [selectedNodeId, setSelectedNodeId] = useState<string | null>(null);
    const [draggingNodeId, setDraggingNodeId] = useState<string | null>(null);
    const [dragStart, setDragStart] = useState({ x: 0, y: 0 });
    const [connectingSource, setConnectingSource] = useState<{ nodeId: string; handle: 'out' | 'true' | 'false' } | null>(null);
    const [newKeyword, setNewKeyword] = useState('');

    const canvasRef = useRef<HTMLDivElement>(null);

    // Save Flow
    const handleSave = () => {
        if (isEdit && flow.id) {
            put(`/whatsapp-sender/bot-flows/${flow.id}`, {
                onSuccess: () => onClose()
            });
        } else {
            post('/whatsapp-sender/bot-flows', {
                onSuccess: () => onClose()
            });
        }
    };

    // Node interactions
    const handleNodeMouseDown = (nodeId: string, e: React.MouseEvent) => {
        if (e.button !== 0) return; // Left click only
        e.stopPropagation();
        setSelectedNodeId(nodeId);
        setDraggingNodeId(nodeId);
        const node = data.nodes.find(n => n.id === nodeId);
        if (node) {
            setDragStart({ x: e.clientX - node.x, y: e.clientY - node.y });
        }
    };

    const handleCanvasMouseMove = (e: React.MouseEvent) => {
        if (draggingNodeId) {
            const updatedNodes = data.nodes.map(node => {
                if (node.id === draggingNodeId) {
                    // Snap to grid of 10px
                    const newX = Math.round((e.clientX - dragStart.x) / 10) * 10;
                    const newY = Math.round((e.clientY - dragStart.y) / 10) * 10;
                    return {
                        ...node,
                        x: Math.max(0, Math.min(1800, newX)),
                        y: Math.max(0, Math.min(1800, newY))
                    };
                }
                return node;
            });
            setData('nodes', updatedNodes);
        }
    };

    const handleCanvasMouseUp = () => {
        setDraggingNodeId(null);
    };

    // Add Nodes
    const addNode = (type: 'message' | 'delay' | 'condition' | 'action') => {
        const id = `node_${type}_${uuid()}`;
        const defaultData = type === 'message'
            ? { message_text: 'Hello! How can we help you today?', buttons: [] }
            : type === 'delay'
            ? { seconds: 2 }
            : type === 'condition'
            ? { condition_type: 'wallet_balance' as const, operator: '>', value: '5' }
            : { action_type: 'set_field' as const, field_name: '', field_value: '' };

        const newNode: Node = {
            id,
            type,
            x: 350 + Math.random() * 50,
            y: 100 + Math.random() * 50,
            data: defaultData
        };

        setData('nodes', [...data.nodes, newNode]);
        setSelectedNodeId(id);
    };

    const deleteNode = (nodeId: string) => {
        if (nodeId === 'node_trigger') return; // Cannot delete trigger
        setData({
            ...data,
            nodes: data.nodes.filter(n => n.id !== nodeId),
            edges: data.edges.filter(e => e.source !== nodeId && e.target !== nodeId)
        });
        if (selectedNodeId === nodeId) {
            setSelectedNodeId(null);
        }
    };

    // Connection helpers
    const handleStartConnection = (nodeId: string, handle: 'out' | 'true' | 'false', e: React.MouseEvent) => {
        e.stopPropagation();
        setConnectingSource({ nodeId, handle });
    };

    const handleEndConnection = (nodeId: string) => {
        if (!connectingSource || connectingSource.nodeId === nodeId) {
            setConnectingSource(null);
            return;
        }

        // Check if connection already exists
        const edgeExists = data.edges.some(
            e => e.source === connectingSource.nodeId && 
                 e.sourceHandle === connectingSource.handle && 
                 e.target === nodeId
        );

        if (!edgeExists) {
            // Remove existing output edge from this source handle since single output is standard
            const filteredEdges = data.edges.filter(
                e => !(e.source === connectingSource.nodeId && e.sourceHandle === connectingSource.handle)
            );

            const newEdge: Edge = {
                id: `edge_${connectingSource.nodeId}_${connectingSource.handle}_${nodeId}`,
                source: connectingSource.nodeId,
                target: nodeId,
                sourceHandle: connectingSource.handle,
                targetHandle: 'in'
            };

            setData('edges', [...filteredEdges, newEdge]);
        }

        setConnectingSource(null);
    };

    const deleteEdge = (edgeId: string) => {
        setData('edges', data.edges.filter(e => e.id !== edgeId));
    };

    // Trigger Keywords
    const handleAddKeyword = (e: React.FormEvent) => {
        e.preventDefault();
        const kw = newKeyword.trim();
        if (kw && !data.trigger_keywords.includes(kw)) {
            setData('trigger_keywords', [...data.trigger_keywords, kw]);
            setNewKeyword('');
        }
    };

    const handleRemoveKeyword = (kw: string) => {
        setData('trigger_keywords', data.trigger_keywords.filter(k => k !== kw));
    };

    // Node values update
    const updateNodeData = (nodeId: string, updatedData: any) => {
        const updatedNodes = data.nodes.map(n => {
            if (n.id === nodeId) {
                return { ...n, data: { ...n.data, ...updatedData } };
            }
            return n;
        });
        setData('nodes', updatedNodes);
    };

    // Node component helpers
    const selectedNode = data.nodes.find(n => n.id === selectedNodeId);

    // Calculate curve paths
    const getEdgePath = (edge: Edge) => {
        const sourceNode = data.nodes.find(n => n.id === edge.source);
        const targetNode = data.nodes.find(n => n.id === edge.target);

        if (!sourceNode || !targetNode) return '';

        // Node card dimensions: width=260px, height varies roughly 140px
        let startX = sourceNode.x + 260;
        let startY = sourceNode.y + 70;

        if (edge.sourceHandle === 'true') {
            startY = sourceNode.y + 55;
        } else if (edge.sourceHandle === 'false') {
            startY = sourceNode.y + 115;
        }

        const endX = targetNode.x;
        const endY = targetNode.y + 50;

        const controlX1 = startX + 80;
        const controlY1 = startY;
        const controlX2 = endX - 80;
        const controlY2 = endY;

        return `M ${startX} ${startY} C ${controlX1} ${controlY1}, ${controlX2} ${controlY2}, ${endX} ${endY}`;
    };

    return (
        <div className="fixed inset-0 z-50 flex flex-col bg-slate-950 text-slate-100 font-sans">
            {/* Header */}
            <header className="flex items-center justify-between px-6 py-4 border-b border-slate-800 bg-slate-900/60 backdrop-blur-md">
                <div className="flex items-center space-x-4">
                    <button
                        onClick={onClose}
                        className="px-3 py-1.5 text-sm font-medium border border-slate-700 rounded-lg hover:bg-slate-800 transition"
                    >
                        ← Back
                    </button>
                    <div>
                        <input
                            type="text"
                            value={data.name}
                            onChange={e => setData('name', e.target.value)}
                            className="bg-transparent border-b border-transparent hover:border-slate-600 focus:border-blue-500 focus:outline-none text-lg font-bold px-1"
                        />
                        <div className="text-xs text-slate-400 mt-0.5">
                            Channel: <span className="capitalize text-slate-200">{data.channel}</span>
                        </div>
                    </div>
                </div>

                <div className="flex items-center space-x-4">
                    {/* Active Toggle */}
                    <div className="flex items-center space-x-2 bg-slate-800/80 px-3 py-1.5 rounded-lg border border-slate-700">
                        <span className="text-xs font-semibold text-slate-400">Flow Status:</span>
                        <button
                            onClick={() => setData('is_active', !data.is_active)}
                            className={`relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none ${
                                data.is_active ? 'bg-emerald-500' : 'bg-slate-700'
                            }`}
                        >
                            <span
                                className={`pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ${
                                    data.is_active ? 'translate-x-4' : 'translate-x-0'
                                }`}
                            />
                        </button>
                        <span className={`text-xs font-bold ${data.is_active ? 'text-emerald-400' : 'text-slate-400'}`}>
                            {data.is_active ? 'Active' : 'Inactive'}
                        </span>
                    </div>

                    <button
                        onClick={handleSave}
                        disabled={processing}
                        className="px-5 py-2 text-sm font-bold bg-blue-600 text-white rounded-lg hover:bg-blue-500 transition shadow-lg shadow-blue-500/10 disabled:opacity-50"
                    >
                        {processing ? 'Saving...' : 'Save Flow'}
                    </button>
                </div>
            </header>

            {/* Editor Workspace */}
            <div className="flex flex-1 overflow-hidden relative">
                {/* Node Palette Sidebar */}
                <div className="w-64 bg-slate-900 border-r border-slate-800 p-5 flex flex-col space-y-4">
                    <h3 className="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Node Palette</h3>
                    
                    <button
                        onClick={() => addNode('message')}
                        className="flex items-center space-x-3 w-full p-3 rounded-xl border border-slate-800 bg-slate-800/30 hover:bg-slate-800 hover:border-slate-700 transition text-left group"
                    >
                        <span className="w-8 h-8 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold text-lg group-hover:scale-105 transition">💬</span>
                        <div>
                            <div className="text-sm font-bold">Send Message</div>
                            <div className="text-xs text-slate-400">Send text/buttons</div>
                        </div>
                    </button>

                    <button
                        onClick={() => addNode('delay')}
                        className="flex items-center space-x-3 w-full p-3 rounded-xl border border-slate-800 bg-slate-800/30 hover:bg-slate-800 hover:border-slate-700 transition text-left group"
                    >
                        <span className="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold text-lg group-hover:scale-105 transition">⏳</span>
                        <div>
                            <div className="text-sm font-bold">Delay Timer</div>
                            <div className="text-xs text-slate-400">Wait X seconds</div>
                        </div>
                    </button>

                    <button
                        onClick={() => addNode('condition')}
                        className="flex items-center space-x-3 w-full p-3 rounded-xl border border-slate-800 bg-slate-800/30 hover:bg-slate-800 hover:border-slate-700 transition text-left group"
                    >
                        <span className="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold text-lg group-hover:scale-105 transition">🔀</span>
                        <div>
                            <div className="text-sm font-bold">Condition Branch</div>
                            <div className="text-xs text-slate-400">If / Else routing</div>
                        </div>
                    </button>

                    <button
                        onClick={() => addNode('action')}
                        className="flex items-center space-x-3 w-full p-3 rounded-xl border border-slate-800 bg-slate-800/30 hover:bg-slate-800 hover:border-slate-700 transition text-left group"
                    >
                        <span className="w-8 h-8 rounded-lg bg-purple-500/20 text-purple-400 flex items-center justify-center font-bold text-lg group-hover:scale-105 transition">⚙️</span>
                        <div>
                            <div className="text-sm font-bold">Action Event</div>
                            <div className="text-xs text-slate-400">Set tag, trigger webhook</div>
                        </div>
                    </button>

                    <div className="mt-8 border-t border-slate-800 pt-6 text-xs text-slate-500 leading-relaxed">
                        <span className="font-bold text-slate-400">Tips:</span><br />
                        - Drag nodes by their title bars.<br />
                        - Click a node's output dot, then click another node to connect.<br />
                        - Selected node properties open on the right panel.
                    </div>
                </div>

                {/* Canvas Area */}
                <div
                    ref={canvasRef}
                    onMouseMove={handleCanvasMouseMove}
                    onMouseUp={handleCanvasMouseUp}
                    className="flex-1 bg-slate-950 overflow-auto relative select-none"
                    style={{
                        backgroundImage: 'radial-gradient(#334155 1.5px, transparent 1.5px)',
                        backgroundSize: '24px 24px'
                    }}
                >
                    {/* SVG connection lines */}
                    <svg className="absolute inset-0 w-[2000px] h-[2000px] pointer-events-none z-0">
                        <defs>
                            <linearGradient id="edge-grad" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stopColor="#3b82f6" />
                                <stop offset="100%" stopColor="#818cf8" />
                            </linearGradient>
                        </defs>
                        {data.edges.map(edge => (
                            <g key={edge.id} className="pointer-events-auto group">
                                <path
                                    d={getEdgePath(edge)}
                                    stroke="transparent"
                                    strokeWidth="12"
                                    fill="none"
                                    className="cursor-pointer"
                                    onClick={(e) => { e.stopPropagation(); deleteEdge(edge.id); }}
                                />
                                <path
                                    d={getEdgePath(edge)}
                                    stroke="url(#edge-grad)"
                                    strokeWidth="3.5"
                                    fill="none"
                                    className="group-hover:stroke-red-500 transition duration-150"
                                />
                            </g>
                        ))}
                    </svg>

                    {/* Rendering Nodes */}
                    {data.nodes.map(node => (
                        <div
                            key={node.id}
                            style={{ left: node.x, top: node.y }}
                            onMouseDown={(e) => handleNodeMouseDown(node.id, e)}
                            className={`absolute w-[260px] rounded-xl border backdrop-blur-md transition-shadow z-10 ${
                                selectedNodeId === node.id 
                                    ? 'border-blue-500 ring-2 ring-blue-500/20 shadow-xl shadow-blue-500/5 bg-slate-900/90' 
                                    : 'border-slate-800 shadow-md bg-slate-900/80'
                            }`}
                        >
                            {/* Input Port (all nodes except trigger have it) */}
                            {node.type !== 'trigger' && (
                                <div
                                    onMouseUp={(e) => { e.stopPropagation(); handleEndConnection(node.id); }}
                                    className="absolute -left-2 top-[46px] w-4 h-4 rounded-full border-2 border-slate-700 bg-slate-900 hover:bg-indigo-400 cursor-pointer flex items-center justify-center transition"
                                    title="Connect Input"
                                >
                                    <div className="w-1.5 h-1.5 rounded-full bg-slate-400" />
                                </div>
                            )}

                            {/* Node Header */}
                            <div className={`px-4 py-2.5 rounded-t-xl font-bold text-xs flex items-center justify-between border-b cursor-grab ${
                                node.type === 'trigger' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' :
                                node.type === 'message' ? 'bg-blue-500/10 text-blue-400 border-blue-500/20' :
                                node.type === 'delay' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' :
                                node.type === 'condition' ? 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20' :
                                'bg-purple-500/10 text-purple-400 border-purple-500/20'
                            }`}>
                                <div className="flex items-center space-x-2">
                                    <span>
                                        {node.type === 'trigger' ? '🟢' :
                                         node.type === 'message' ? '💬' :
                                         node.type === 'delay' ? '⏳' :
                                         node.type === 'condition' ? '🔀' : '⚙️'}
                                    </span>
                                    <span className="uppercase tracking-wider">
                                        {node.type === 'trigger' ? 'Flow Trigger' :
                                         node.type === 'message' ? 'Send Message' :
                                         node.type === 'delay' ? 'Delay Timer' :
                                         node.type === 'condition' ? 'Condition' : 'Action'}
                                    </span>
                                </div>
                                {node.type !== 'trigger' && (
                                    <button 
                                        onClick={(e) => { e.stopPropagation(); deleteNode(node.id); }}
                                        className="text-slate-500 hover:text-red-400 transition"
                                    >
                                        ✕
                                    </button>
                                )}
                            </div>

                            {/* Node Body */}
                            <div className="p-4 text-xs text-slate-300">
                                {node.type === 'trigger' && (
                                    <div>
                                        <div className="font-semibold text-slate-400 uppercase tracking-wider text-[10px]">Type</div>
                                        <div className="text-sm font-bold text-emerald-400 mt-1 capitalize">{data.trigger_type}</div>
                                        {data.trigger_type === 'keyword' && (
                                            <div className="mt-2 text-slate-400 truncate">
                                                Keywords: {data.trigger_keywords.join(', ') || 'none'}
                                            </div>
                                        )}
                                    </div>
                                )}

                                {node.type === 'message' && (
                                    <div>
                                        <p className="line-clamp-2 italic text-slate-400 mb-2">"{node.data.message_text}"</p>
                                        {node.data.buttons && node.data.buttons.length > 0 && (
                                            <div className="flex flex-wrap gap-1 mt-1">
                                                {node.data.buttons.map((btn, idx) => (
                                                    <span key={idx} className="bg-slate-800 border border-slate-700 px-2 py-0.5 rounded text-[10px]">
                                                        🔘 {btn.label}
                                                    </span>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                )}

                                {node.type === 'delay' && (
                                    <div className="text-slate-400">
                                        Wait for <span className="font-bold text-amber-400 text-sm">{node.data.seconds ?? 2}</span> seconds
                                    </div>
                                )}

                                {node.type === 'condition' && (
                                    <div>
                                        <div className="text-slate-400">
                                            If <span className="font-bold text-indigo-400">{node.data.condition_type === 'wallet_balance' ? 'Wallet Balance' : node.data.field_name}</span>
                                        </div>
                                        <div className="text-slate-200 mt-1 font-mono">
                                            {node.data.operator} {node.data.value}
                                        </div>
                                    </div>
                                )}

                                {node.type === 'action' && (
                                    <div>
                                        <div className="text-slate-400 uppercase tracking-wider text-[10px] font-semibold">Action</div>
                                        <div className="font-bold mt-1 text-purple-400">
                                            {node.data.action_type === 'set_field' ? 'Set Custom Field' : 'Trigger Webhook'}
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Output Ports */}
                            {node.type === 'condition' ? (
                                <div className="flex flex-col space-y-2 pb-3 pr-3 text-right">
                                    <div className="relative">
                                        <span className="text-[10px] text-emerald-400 font-bold mr-1">TRUE</span>
                                        <div
                                            onMouseDown={(e) => handleStartConnection(node.id, 'true', e)}
                                            className="absolute -right-5 top-0.5 w-4 h-4 rounded-full border-2 border-emerald-500 bg-slate-900 hover:bg-emerald-400 cursor-pointer flex items-center justify-center transition"
                                            title="True output path"
                                        >
                                            <div className="w-1.5 h-1.5 rounded-full bg-emerald-400" />
                                        </div>
                                    </div>
                                    <div className="relative">
                                        <span className="text-[10px] text-red-400 font-bold mr-1">FALSE</span>
                                        <div
                                            onMouseDown={(e) => handleStartConnection(node.id, 'false', e)}
                                            className="absolute -right-5 top-0.5 w-4 h-4 rounded-full border-2 border-red-500 bg-slate-900 hover:bg-red-400 cursor-pointer flex items-center justify-center transition"
                                            title="False output path"
                                        >
                                            <div className="w-1.5 h-1.5 rounded-full bg-red-400" />
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <div
                                    onMouseDown={(e) => handleStartConnection(node.id, 'out', e)}
                                    className="absolute -right-2 top-[46px] w-4 h-4 rounded-full border-2 border-slate-700 bg-slate-900 hover:bg-blue-400 cursor-pointer flex items-center justify-center transition z-20"
                                    title="Connect Output"
                                >
                                    <div className="w-1.5 h-1.5 rounded-full bg-slate-400" />
                                </div>
                            )}
                        </div>
                    ))}
                </div>

                {/* Floating Properties Side Panel */}
                <div className="w-96 bg-slate-900 border-l border-slate-800 p-6 overflow-y-auto">
                    {selectedNode ? (
                        <div className="flex flex-col space-y-6">
                            <div className="flex justify-between items-center border-b border-slate-800 pb-4">
                                <h3 className="font-bold text-sm uppercase tracking-wider text-slate-300">
                                    Node Configuration
                                </h3>
                                <span className="text-[10px] bg-slate-800 border border-slate-700 px-2.5 py-1 rounded text-slate-400 font-mono">
                                    {selectedNode.id}
                                </span>
                            </div>

                            {/* Trigger Node config */}
                            {selectedNode.type === 'trigger' && (
                                <div className="space-y-4">
                                    <div>
                                        <label className="block text-xs font-bold text-slate-400 mb-1.5 uppercase">Trigger Event</label>
                                        <select
                                            value={data.trigger_type}
                                            onChange={e => setData('trigger_type', e.target.value as any)}
                                            className="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"
                                        >
                                            <option value="keyword">On Keyword Match</option>
                                            {data.channel === 'telegram' && <option value="start_bot">On Bot Start (/start)</option>}
                                            <option value="default">Fallback Default Reply</option>
                                        </select>
                                    </div>

                                    {data.trigger_type === 'keyword' && (
                                        <div>
                                            <label className="block text-xs font-bold text-slate-400 mb-1.5 uppercase">Trigger Keywords</label>
                                            <form onSubmit={handleAddKeyword} className="flex space-x-2">
                                                <input
                                                    type="text"
                                                    value={newKeyword}
                                                    onChange={e => setNewKeyword(e.target.value)}
                                                    placeholder="e.g. price, support"
                                                    className="flex-1 bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"
                                                />
                                                <button
                                                    type="submit"
                                                    className="bg-blue-600 text-white hover:bg-blue-500 transition px-4 rounded-lg text-sm font-bold"
                                                >
                                                    + Add
                                                </button>
                                            </form>
                                            
                                            <div className="flex flex-wrap gap-1.5 mt-3">
                                                {data.trigger_keywords.map(kw => (
                                                    <span 
                                                        key={kw} 
                                                        className="flex items-center space-x-1.5 bg-slate-800 border border-slate-700 text-slate-300 px-2.5 py-1 rounded-md text-xs font-medium"
                                                    >
                                                        <span>{kw}</span>
                                                        <button 
                                                            onClick={() => handleRemoveKeyword(kw)}
                                                            className="text-slate-500 hover:text-red-400 font-bold ml-1"
                                                        >
                                                            ✕
                                                        </button>
                                                    </span>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Message Node config */}
                            {selectedNode.type === 'message' && (
                                <div className="space-y-5">
                                    <div>
                                        <label className="block text-xs font-bold text-slate-400 mb-1.5 uppercase">Message Body</label>
                                        <textarea
                                            value={selectedNode.data.message_text || ''}
                                            onChange={e => updateNodeData(selectedNode.id, { message_text: e.target.value })}
                                            rows={4}
                                            className="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 font-sans"
                                            placeholder="Write message content..."
                                        />
                                    </div>

                                    {/* Buttons */}
                                    <div>
                                        <label className="block text-xs font-bold text-slate-400 mb-1.5 uppercase">
                                            Quick Reply Buttons {data.channel === 'whatsapp' ? '(Max 3)' : ''}
                                        </label>
                                        
                                        <div className="space-y-2 mt-2">
                                            {(selectedNode.data.buttons || []).map((btn, idx) => (
                                                <div key={idx} className="flex items-center space-x-2 bg-slate-800 border border-slate-700 p-2.5 rounded-lg">
                                                    <span className="text-slate-400 text-xs">#{idx+1}</span>
                                                    <input
                                                        type="text"
                                                        value={btn.label}
                                                        onChange={e => {
                                                            const newBtns = [...(selectedNode.data.buttons || [])];
                                                            newBtns[idx].label = e.target.value;
                                                            updateNodeData(selectedNode.id, { buttons: newBtns });
                                                        }}
                                                        placeholder="Button Label"
                                                        className="flex-1 bg-slate-900 border border-slate-700 rounded px-2 py-1 text-xs text-slate-200 focus:outline-none"
                                                    />
                                                    <button
                                                        onClick={() => {
                                                            const newBtns = (selectedNode.data.buttons || []).filter((_, i) => i !== idx);
                                                            updateNodeData(selectedNode.id, { buttons: newBtns });
                                                        }}
                                                        className="text-red-400 hover:text-red-300 text-xs px-1"
                                                    >
                                                        Delete
                                                    </button>
                                                </div>
                                            ))}
                                        </div>

                                        {(data.channel === 'telegram' || (selectedNode.data.buttons || []).length < 3) && (
                                            <button
                                                onClick={() => {
                                                    const targetId = `node_btn_target_${uuid()}`;
                                                    const newBtn = { label: 'New Button', target_node_id: targetId, value: targetId };
                                                    updateNodeData(selectedNode.id, { buttons: [...(selectedNode.data.buttons || []), newBtn] });
                                                    // Spawn a placeholder message node that is connected automatically
                                                    const newPlaceholderNode: Node = {
                                                        id: targetId,
                                                        type: 'message',
                                                        x: selectedNode.x + 350,
                                                        y: selectedNode.y + ((selectedNode.data.buttons || []).length * 80) - 20,
                                                        data: { message_text: 'Thank you for selecting this button!', buttons: [] }
                                                    };
                                                    setData('nodes', [...data.nodes, newPlaceholderNode]);
                                                }}
                                                className="w-full mt-3 py-2 text-xs font-bold border border-dashed border-slate-700 hover:border-slate-500 rounded-lg text-slate-400 hover:text-slate-300 transition"
                                            >
                                                + Add Response Button
                                            </button>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Delay Node config */}
                            {selectedNode.type === 'delay' && (
                                <div>
                                    <label className="block text-xs font-bold text-slate-400 mb-1.5 uppercase">Delay Duration (Seconds)</label>
                                    <input
                                        type="number"
                                        min={1}
                                        max={5}
                                        value={selectedNode.data.seconds ?? 2}
                                        onChange={e => updateNodeData(selectedNode.id, { seconds: parseInt(e.target.value) || 2 })}
                                        className="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"
                                    />
                                    <p className="text-[10px] text-slate-500 mt-1.5 leading-relaxed">
                                        For webhook execution safety, delay is limited between 1 and 5 seconds.
                                    </p>
                                </div>
                            )}

                            {/* Condition Node config */}
                            {selectedNode.type === 'condition' && (
                                <div className="space-y-4">
                                    <div>
                                        <label className="block text-xs font-bold text-slate-400 mb-1.5 uppercase">Condition Source</label>
                                        <select
                                            value={selectedNode.data.condition_type}
                                            onChange={e => updateNodeData(selectedNode.id, { condition_type: e.target.value })}
                                            className="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"
                                        >
                                            <option value="wallet_balance">Wallet Balance</option>
                                            <option value="custom_field">Subscriber Custom Field</option>
                                        </select>
                                    </div>

                                    {selectedNode.data.condition_type === 'custom_field' && (
                                        <div>
                                            <label className="block text-xs font-bold text-slate-400 mb-1.5 uppercase">Custom Field Name</label>
                                            <input
                                                type="text"
                                                value={selectedNode.data.field_name || ''}
                                                onChange={e => updateNodeData(selectedNode.id, { field_name: e.target.value })}
                                                placeholder="e.g. tag, country"
                                                className="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"
                                            />
                                        </div>
                                    )}

                                    <div className="grid grid-cols-2 gap-3">
                                        <div>
                                            <label className="block text-xs font-bold text-slate-400 mb-1.5 uppercase">Operator</label>
                                            <select
                                                value={selectedNode.data.operator}
                                                onChange={e => updateNodeData(selectedNode.id, { operator: e.target.value })}
                                                className="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"
                                            >
                                                <option value="=">Equals (=)</option>
                                                <option value=">">Greater Than (&gt;)</option>
                                                <option value="<">Less Than (&lt;)</option>
                                                <option value="contains">Contains</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label className="block text-xs font-bold text-slate-400 mb-1.5 uppercase">Value</label>
                                            <input
                                                type="text"
                                                value={selectedNode.data.value || ''}
                                                onChange={e => updateNodeData(selectedNode.id, { value: e.target.value })}
                                                placeholder="Value to check"
                                                className="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"
                                            />
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Action Node config */}
                            {selectedNode.type === 'action' && (
                                <div className="space-y-4">
                                    <div>
                                        <label className="block text-xs font-bold text-slate-400 mb-1.5 uppercase">Action Event Type</label>
                                        <select
                                            value={selectedNode.data.action_type}
                                            onChange={e => updateNodeData(selectedNode.id, { action_type: e.target.value })}
                                            className="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"
                                        >
                                            <option value="set_field">Set Custom Field / Tag</option>
                                            <option value="webhook">Trigger External Webhook</option>
                                        </select>
                                    </div>

                                    {selectedNode.data.action_type === 'set_field' ? (
                                        <div className="space-y-3">
                                            <div>
                                                <label className="block text-xs font-bold text-slate-400 mb-1.5 uppercase">Field Key Name</label>
                                                <input
                                                    type="text"
                                                    value={selectedNode.data.field_name || ''}
                                                    onChange={e => updateNodeData(selectedNode.id, { field_name: e.target.value })}
                                                    placeholder="e.g. current_status, preference"
                                                    className="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-xs font-bold text-slate-400 mb-1.5 uppercase">Field Value</label>
                                                <input
                                                    type="text"
                                                    value={selectedNode.data.field_value || ''}
                                                    onChange={e => updateNodeData(selectedNode.id, { field_value: e.target.value })}
                                                    placeholder="e.g. subscribed, premium"
                                                    className="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"
                                                />
                                            </div>
                                        </div>
                                    ) : (
                                        <div>
                                            <label className="block text-xs font-bold text-slate-400 mb-1.5 uppercase">Webhook HTTP POST URL</label>
                                            <input
                                                type="url"
                                                value={selectedNode.data.webhook_url || ''}
                                                onChange={e => updateNodeData(selectedNode.id, { webhook_url: e.target.value })}
                                                placeholder="https://example.com/webhook"
                                                className="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"
                                            />
                                        </div>
                                    )}
                                </div>
                            )}

                        </div>
                    ) : (
                        <div className="flex flex-col items-center justify-center h-64 text-slate-500 text-center">
                            <span className="text-3xl mb-2">👈</span>
                            <p className="text-xs">
                                Click any node on the canvas to configure its settings.
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

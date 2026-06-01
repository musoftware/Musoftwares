import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
    GitBranch, Plus, Play, Square, Trash2, Save, Copy,
    MessageSquare, Clock, Split, Bot, Webhook, BarChart3,
    ChevronRight, Users, Zap, CheckCircle2, AlertCircle,
    ArrowRight, RefreshCw, Settings, X, Layers
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Textarea } from '@/Components/ui/textarea';
import { __ } from '@/lib/i18n';

const getRuntimeHost = () =>
    typeof window !== 'undefined' ? ((window as any).MUSOFTWARE_RUNTIME_HOST || '127.0.0.1') : '127.0.0.1';
const getWsUrl = () => `ws://${getRuntimeHost()}:18401/ws`;

// ── Node types ────────────────────────────────────────────────────────────────
const NODE_TYPES = [
    { type: 'message',   label: 'Send Message',   icon: MessageSquare, color: 'from-blue-500 to-blue-600',    desc: 'Send a WhatsApp message with optional media' },
    { type: 'delay',     label: 'Wait / Delay',   icon: Clock,         color: 'from-amber-500 to-orange-500', desc: 'Wait N hours or days before the next step' },
    { type: 'condition', label: 'Condition',       icon: Split,         color: 'from-purple-500 to-violet-600', desc: 'Route based on reply keywords or contact data' },
    { type: 'ai_reply',  label: 'AI Auto-Reply',  icon: Bot,           color: 'from-indigo-500 to-violet-500', desc: 'GPT-4o powered contextual reply' },
    { type: 'webhook',   label: 'Webhook',         icon: Webhook,       color: 'from-teal-500 to-cyan-500',    desc: 'POST data to your CRM or external system' },
    { type: 'goal',      label: 'Goal Reached',   icon: CheckCircle2,  color: 'from-emerald-500 to-green-600', desc: 'Mark contact as converted and end the funnel' },
];

// ── Visual canvas node ────────────────────────────────────────────────────────
function CanvasNode({ node, selected, onSelect, onDelete, style }: any) {
    const def = NODE_TYPES.find(t => t.type === node.type) ?? NODE_TYPES[0];
    const Icon = def.icon;
    return (
        <div
            onClick={() => onSelect(node.id)}
            className={`absolute cursor-pointer select-none w-52 rounded-2xl border-2 transition-all ${selected ? 'border-blue-500 shadow-xl shadow-blue-500/20' : 'border-slate-700 hover:border-slate-500'} bg-slate-900`}
            style={style}
        >
            <div className={`flex items-center gap-2.5 p-3 rounded-t-xl bg-gradient-to-r ${def.color}`}>
                <Icon className="w-3.5 h-3.5 text-white" />
                <span className="text-xs font-bold text-white">{def.label}</span>
            </div>
            <div className="p-3">
                <p className="text-[10px] text-slate-400 leading-relaxed">{node.config?.preview || def.desc}</p>
            </div>
            <Button variant="ghost" size="icon" onClick={e => { e.stopPropagation(); onDelete(node.id); }}
                className="absolute -top-2 -right-2 w-5 h-5 bg-rose-500 rounded-full text-white opacity-0 hover:opacity-100 group-hover:opacity-100 transition-opacity hover:bg-rose-600">
                <X className="w-3 h-3" />
            </Button>
        </div>
    );
}

// ── Funnel list card ──────────────────────────────────────────────────────────
function FunnelCard({ funnel, onOpen, onDelete, onToggle }: any) {
    return (
        <div className="bg-slate-900 border border-slate-800 rounded-2xl p-5 hover:border-blue-500/30 transition-all cursor-pointer group" onClick={() => onOpen(funnel)}>
            <div className="flex items-start justify-between mb-3">
                <div>
                    <h3 className="text-sm font-bold text-white mb-0.5">{funnel.name}</h3>
                    <p className="text-xs text-slate-500">{funnel.nodes?.length ?? 0} nodes • {funnel.active_contacts ?? 0} active contacts</p>
                </div>
                <Badge variant="outline" className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${funnel.status === 'active' ? 'bg-emerald-500/15 text-emerald-400 border-emerald-500/25' : funnel.status === 'paused' ? 'bg-yellow-500/15 text-yellow-400 border-yellow-500/25' : 'bg-slate-800 text-slate-500 border-slate-700'}`}>
                    {funnel.status ?? 'draft'}
                </Badge>
            </div>
            <div className="grid grid-cols-3 gap-2 mb-4">
                <div className="bg-slate-800/60 rounded-xl p-2.5 text-center">
                    <p className="text-sm font-black text-white">{funnel.entered ?? 0}</p>
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-600">Entered</p>
                </div>
                <div className="bg-slate-800/60 rounded-xl p-2.5 text-center">
                    <p className="text-sm font-black text-emerald-400">{funnel.converted ?? 0}</p>
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-600">Converted</p>
                </div>
                <div className="bg-slate-800/60 rounded-xl p-2.5 text-center">
                    <p className="text-sm font-black text-indigo-400">{funnel.conversion_rate ?? '0%'}</p>
                    <p className="text-[9px] font-black uppercase tracking-wider text-slate-600">Rate</p>
                </div>
            </div>
            <div className="flex gap-2">
                <Button variant="outline" onClick={e => { e.stopPropagation(); onToggle(funnel); }}
                    className={`flex-1 h-9 rounded-xl text-xs font-bold transition-all ${funnel.status === 'active' ? 'bg-yellow-500/10 border-yellow-500/20 text-yellow-400 hover:bg-yellow-500/15 hover:text-yellow-400' : 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400 hover:bg-emerald-500/15 hover:text-emerald-400'}`}>
                    {funnel.status === 'active' ? <><Square className="w-3 h-3 inline mr-1" />Pause</> : <><Play className="w-3 h-3 inline mr-1" />Activate</>}
                </Button>
                <Button variant="outline" size="icon" onClick={e => { e.stopPropagation(); onOpen(funnel); }}
                    className="h-9 w-10 bg-blue-500/10 border-blue-500/20 text-blue-400 rounded-xl hover:bg-blue-500/15 hover:text-blue-400 transition-all">
                    <Settings className="w-3.5 h-3.5" />
                </Button>
                <Button variant="outline" size="icon" onClick={e => { e.stopPropagation(); onDelete(funnel.id); }}
                    className="h-9 w-10 bg-rose-500/10 border-rose-500/20 text-rose-400 rounded-xl hover:bg-rose-500/15 hover:text-rose-400 transition-all">
                    <Trash2 className="w-3.5 h-3.5" />
                </Button>
            </div>
        </div>
    );
}

// ── Builder panel ─────────────────────────────────────────────────────────────
function FunnelBuilder({ funnel, onClose, onSave }: { funnel: any; onClose: () => void; onSave: (f: any) => void }) {
    const [nodes, setNodes]   = useState<any[]>(funnel.nodes ?? []);
    const [selected, setSelected] = useState<string | null>(null);
    const nextPos = useRef({ x: 100, y: 80 });

    const addNode = (type: string) => {
        const id = `node_${Date.now()}`;
        setNodes(prev => [...prev, { id, type, config: {}, x: nextPos.current.x, y: nextPos.current.y }]);
        nextPos.current = { x: nextPos.current.x, y: nextPos.current.y + 160 };
    };

    const deleteNode = (id: string) => {
        setNodes(prev => prev.filter(n => n.id !== id));
        if (selected === id) setSelected(null);
    };

    return (
        <div className="fixed inset-0 z-50 bg-slate-950 flex flex-col">
            {/* Builder header */}
            <div className="h-12 bg-slate-900 border-b border-slate-800 flex items-center justify-between px-4 shrink-0">
                <div className="flex items-center gap-2">
                    <GitBranch className="w-4 h-4 text-blue-400" />
                    <span className="text-sm font-bold text-white">{funnel.name}</span>
                </div>
                <div className="flex items-center gap-2">
                    <Button onClick={() => onSave({ ...funnel, nodes })}
                        className="gap-1.5 h-8 bg-blue-500 text-white rounded-lg text-xs font-bold hover:bg-blue-400 transition-all">
                        <Save className="w-3 h-3" />{__('general.save_funnel')}</Button>
                    <Button variant="ghost" size="icon" onClick={onClose} className="h-8 w-8 text-slate-500 hover:text-white hover:bg-transparent transition-colors"><X className="w-4 h-4" /></Button>
                </div>
            </div>

            <div className="flex flex-1 overflow-hidden">
                {/* Node sidebar */}
                <div className="w-52 bg-slate-900 border-r border-slate-800 p-3 space-y-1.5 overflow-y-auto shrink-0">
                    <p className="text-[9px] font-black uppercase tracking-widest text-slate-600 mb-2 px-1">{__('general.add_node')}</p>
                    {NODE_TYPES.map(t => {
                        const Icon = t.icon;
                        return (
                            <Button variant="outline" key={t.type} onClick={() => addNode(t.type)}
                                className="w-full h-auto justify-start p-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 border-slate-700 hover:border-slate-600 transition-all text-left">
                                <div className={`w-6 h-6 rounded-lg bg-gradient-to-br ${t.color} flex items-center justify-center shrink-0 mr-2`}>
                                    <Icon className="w-3 h-3 text-white" />
                                </div>
                                <span className="text-xs font-semibold text-slate-300">{t.label}</span>
                            </Button>
                        );
                    })}
                </div>

                {/* Canvas */}
                <div className="flex-1 relative overflow-hidden bg-[#0a0f1a] bg-[radial-gradient(#1e293b_1px,transparent_1px)] bg-[size:24px_24px]">
                    {nodes.length === 0 ? (
                        <div className="absolute inset-0 flex items-center justify-center">
                            <div className="text-center">
                                <GitBranch className="w-10 h-10 text-slate-800 mx-auto mb-3" />
                                <p className="text-xs text-slate-600">{__('general.add_nodes_from_the_sidebar_to_build_your_funnel')}</p>
                            </div>
                        </div>
                    ) : (
                        nodes.map((node, i) => (
                            <CanvasNode
                                key={node.id}
                                node={node}
                                selected={selected === node.id}
                                onSelect={setSelected}
                                onDelete={deleteNode}
                                style={{ left: node.x, top: node.y }}
                            />
                        ))
                    )}
                </div>

                {/* Properties panel */}
                {selected && (
                    <div className="w-64 bg-slate-900 border-l border-slate-800 p-4 shrink-0 overflow-y-auto">
                        <p className="text-[9px] font-black uppercase tracking-widest text-slate-600 mb-3">{__('general.node_properties')}</p>
                        {(() => {
                            const node = nodes.find(n => n.id === selected);
                            if (!node) return null;
                            const def = NODE_TYPES.find(t => t.type === node.type) ?? NODE_TYPES[0];
                            const Icon = def.icon;
                            return (
                                <div className="space-y-3">
                                    <div className={`flex items-center gap-2 p-2.5 rounded-xl bg-gradient-to-r ${def.color}`}>
                                        <Icon className="w-4 h-4 text-white" />
                                        <span className="text-xs font-bold text-white">{def.label}</span>
                                    </div>
                                    {node.type === 'message' && (
                                        <div>
                                            <label className="text-[10px] font-black uppercase tracking-wider text-slate-500 block mb-1.5">{__('general.message_text')}</label>
                                            <Textarea rows={4} placeholder={__('general.hello_name_we_have_a_special_offer')}
                                                className="text-xs bg-slate-800 border-slate-700 text-white resize-none focus-visible:ring-blue-500"
                                                onChange={e => setNodes(prev => prev.map(n => n.id === selected ? { ...n, config: { ...n.config, preview: e.target.value.slice(0, 40) + '...', text: e.target.value } } : n))} />
                                            <p className="text-[10px] text-slate-600 mt-1">Use {'{{name}}'}, {'{{phone}}'} for personalization</p>
                                        </div>
                                    )}
                                    {node.type === 'delay' && (
                                        <div>
                                            <label className="text-[10px] font-black uppercase tracking-wider text-slate-500 block mb-1.5">{__('general.wait_duration')}</label>
                                            <div className="flex gap-2">
                                                <Input type="number" min={1} defaultValue={1} className="h-9 text-xs bg-slate-800 border-slate-700 text-white focus-visible:ring-blue-500" />
                                                <select className="h-9 px-3 text-xs bg-slate-800 border border-slate-700 rounded-md outline-none text-white focus:border-blue-500">
                                                    <option>Hours</option><option>Days</option>
                                                </select>
                                            </div>
                                        </div>
                                    )}
                                    {(node.type === 'condition') && (
                                        <div>
                                            <label className="text-[10px] font-black uppercase tracking-wider text-slate-500 block mb-1.5">{__('general.if_reply_contains')}</label>
                                            <Input type="text" placeholder={__('general.yes_interested_price')}
                                                className="h-9 text-xs bg-slate-800 border-slate-700 text-white focus-visible:ring-blue-500" />
                                        </div>
                                    )}
                                </div>
                            );
                        })()}
                    </div>
                )}
            </div>
        </div>
    );
}

// ── Main ──────────────────────────────────────────────────────────────────────
export default function WaFunnelEngineRunner({ tool }: any) {
    const [funnels, setFunnels]   = useState<any[]>([]);
    const [editing, setEditing]   = useState<any | null>(null);
    const [showNew, setShowNew]   = useState(false);
    const [newName, setNewName]   = useState('');
    const [connected, setConnected] = useState(false);
    const wsRef = useRef<WebSocket | null>(null);

    useEffect(() => {
        let ws: WebSocket; let retry: ReturnType<typeof setTimeout>;
        const connect = () => {
            ws = new WebSocket(getWsUrl()); wsRef.current = ws;
            ws.onopen  = () => setConnected(true);
            ws.onclose = () => { setConnected(false); retry = setTimeout(connect, 3000); };
            ws.onerror = () => ws.close();
        };
        connect();
        return () => { clearTimeout(retry); ws?.close(); };
    }, []);

    const createFunnel = () => {
        if (!newName.trim()) return;
        setFunnels(prev => [...prev, { id: `funnel_${Date.now()}`, name: newName.trim(), status: 'draft', nodes: [], edges: [], entered: 0, converted: 0, conversion_rate: '0%', active_contacts: 0 }]);
        setNewName(''); setShowNew(false);
    };

    const saveFunnel = (updated: any) => {
        setFunnels(prev => prev.map(f => f.id === updated.id ? updated : f));
        setEditing(null);
    };

    const totalActive = funnels.filter(f => f.status === 'active').length;
    const totalContacts = funnels.reduce((a, f) => a + (f.active_contacts ?? 0), 0);
    const totalConverted = funnels.reduce((a, f) => a + (f.converted ?? 0), 0);

    if (!connected) return (
        <div className="min-h-screen bg-slate-950 flex items-center justify-center">
            <div className="text-center space-y-3">
                <div className="w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto" />
                <p className="text-sm font-semibold text-slate-500">{__('general.connecting_to_runtime')}</p>
            </div>
        </div>
    );

    if (editing) return <FunnelBuilder funnel={editing} onClose={() => setEditing(null)} onSave={saveFunnel} />;

    return (
        <div className="min-h-screen bg-slate-950 font-sans text-slate-100">
            {/* New funnel modal */}
            {showNew && (
                <div className="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
                    <div className="w-full max-w-sm bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4">
                        <h3 className="text-sm font-bold text-white flex items-center gap-2"><GitBranch className="w-4 h-4 text-blue-400" />{__('general.new_funnel')}</h3>
                        <div>
                            <label className="text-[10px] font-black uppercase tracking-wider text-slate-500 block mb-1.5">{__('general.funnel_name')}</label>
                            <Input value={newName} onChange={e => setNewName(e.target.value)} onKeyDown={e => e.key === 'Enter' && createFunnel()} placeholder={__('general.welcome_sequence_product_launch')}
                                className="h-10 text-sm bg-slate-800 border-slate-700 focus-visible:ring-blue-500 text-white" autoFocus />
                        </div>
                        <div className="flex gap-3">
                            <Button variant="outline" onClick={() => setShowNew(false)} className="flex-1 h-10 border-slate-800 text-slate-400 hover:bg-slate-800 hover:text-slate-400 transition-all">Cancel</Button>
                            <Button onClick={createFunnel} disabled={!newName.trim()} className="flex-1 h-10 bg-blue-500 text-white hover:bg-blue-400 transition-all">Create</Button>
                        </div>
                    </div>
                </div>
            )}

            {/* Header */}
            <div className="h-14 bg-slate-900/80 backdrop-blur border-b border-slate-800 flex items-center justify-between px-6 sticky top-0 z-10">
                <div className="flex items-center gap-2.5">
                    <div className="w-7 h-7 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center shadow-lg shadow-blue-500/20">
                        <GitBranch className="w-4 h-4 text-white" />
                    </div>
                    <span className="font-bold text-sm">{__('general.whatsapp_campaigns')}</span>
                </div>
                <Button onClick={() => setShowNew(true)} className="gap-1.5 h-9 bg-blue-500 text-white hover:bg-blue-400 shadow-lg shadow-blue-500/20">
                    <Plus className="w-3.5 h-3.5" />{__('general.new_funnel')}</Button>
            </div>

            <div className="max-w-5xl mx-auto px-4 py-8 space-y-6">
                {/* Stats */}
                {funnels.length > 0 && (
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        {[
                            { label: 'Active Funnels', value: totalActive, icon: Zap, color: 'text-blue-400' },
                            { label: 'Active Contacts', value: totalContacts, icon: Users, color: 'text-indigo-400' },
                            { label: 'Total Converted', value: totalConverted, icon: CheckCircle2, color: 'text-emerald-400' },
                            { label: 'Total Funnels', value: funnels.length, icon: Layers, color: 'text-slate-400' },
                        ].map(s => (
                            <div key={s.label} className="bg-slate-900 border border-slate-800 rounded-2xl p-4">
                                <s.icon className={`w-4 h-4 ${s.color} mb-2`} />
                                <p className="text-xl font-black text-white">{s.value}</p>
                                <p className="text-[9px] font-black uppercase tracking-wider text-slate-600">{s.label}</p>
                            </div>
                        ))}
                    </div>
                )}

                {/* Feature callout */}
                <div className="bg-gradient-to-r from-blue-500/10 to-indigo-500/5 border border-blue-500/20 rounded-2xl p-4 flex items-start gap-3">
                    <GitBranch className="w-5 h-5 text-blue-400 shrink-0 mt-0.5" />
                    <div>
                        <p className="text-sm font-bold text-blue-300">{__('general.visual_drag_and_drop_funnel_builder')}</p>
                        <p className="text-xs text-slate-400 mt-0.5">{__('general.build_automated_whatsapp_workflows_with_message_nodes_time_delays_conditional_branches_and_ai_auto_reply_nodes_the_engine_manages_thousands_of_contacts_concurrently_using_a_persistent_state_machine')}</p>
                    </div>
                </div>

                {/* Funnel grid */}
                {funnels.length > 0 ? (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {funnels.map(f => (
                            <FunnelCard key={f.id} funnel={f}
                                onOpen={setEditing}
                                onDelete={id => setFunnels(prev => prev.filter(fn => fn.id !== id))}
                                onToggle={f => setFunnels(prev => prev.map(fn => fn.id === f.id ? { ...fn, status: fn.status === 'active' ? 'paused' : 'active' } : fn))}
                            />
                        ))}
                    </div>
                ) : (
                    <div className="py-24 text-center border border-dashed border-slate-800 rounded-2xl">
                        <GitBranch className="w-12 h-12 text-slate-800 mx-auto mb-4" />
                        <h3 className="text-sm font-bold text-slate-400">{__('general.no_funnels_created_yet')}</h3>
                        <p className="text-xs text-slate-600 mt-2 max-w-md mx-auto">{__('general.create_your_first_whatsapp_funnel_with_drag_and_drop_nodes_build_multi_step_sequences_with_time_delays_conditional_routing_and_ai_powered_auto_replies_that_run_24_7_on_your_local_machine')}</p>
                        <Button onClick={() => setShowNew(true)} className="mt-6 gap-2 h-10 bg-blue-500 text-white hover:bg-blue-400 mx-auto shadow-lg shadow-blue-500/20">
                            <Plus className="w-4 h-4" />{__('general.create_first_funnel')}</Button>
                    </div>
                )}
            </div>
        </div>
    );
}

import React, { useState, useEffect, useRef } from 'react';
import { LayoutDashboard, Image as ImageIcon, MessageSquareText, Activity, AlertCircle, Plus, Upload, MoreHorizontal, CheckCircle2, ChevronRight, X } from 'lucide-react';

// A simple local WebSocket client to interface with the Generic Layer
function useRuntimeWS(pluginSlug: string) {
    const [ws, setWs] = useState<WebSocket | null>(null);
    const [connected, setConnected] = useState(false);
    const pendingRequests = useRef(new Map());

    useEffect(() => {
        const socket = new WebSocket('ws://127.0.0.1:18401/ws');
        
        socket.onopen = () => setConnected(true);
        socket.onclose = () => setConnected(false);
        
        socket.onmessage = (event) => {
            try {
                const msg = JSON.parse(event.data);
                if (msg.type === 'plugin_rpc_res' || msg.type === 'plugin_rpc_error') {
                    const resolver = pendingRequests.current.get(msg.requestId);
                    if (resolver) {
                        if (msg.type === 'plugin_rpc_error') resolver.reject(new Error(msg.payload.error));
                        else resolver.resolve(msg.payload);
                        pendingRequests.current.delete(msg.requestId);
                    }
                }
            } catch (err) {}
        };
        
        setWs(socket);
        return () => socket.close();
    }, []);

    const callRPC = async (action: string, data: any = {}) => {
        if (!ws || ws.readyState !== WebSocket.OPEN) throw new Error('Not connected to runtime');
        
        return new Promise((resolve, reject) => {
            const requestId = Math.random().toString(36).substring(7);
            pendingRequests.current.set(requestId, { resolve, reject });
            
            ws.send(JSON.stringify({
                type: 'plugin_rpc',
                requestId,
                payload: { plugin: pluginSlug, action, data }
            }));
            
            // Timeout after 15s
            setTimeout(() => {
                if (pendingRequests.current.has(requestId)) {
                    pendingRequests.current.get(requestId).reject(new Error('RPC Timeout'));
                    pendingRequests.current.delete(requestId);
                }
            }, 15000);
        });
    };

    return { connected, callRPC };
}

export default function ScreenshotFeedbackRunner({ tool, subscription, runtimePort, pluginSlug }: any) {
    const [activeTab, setActiveTab] = useState('projects');
    const { connected, callRPC } = useRuntimeWS('screenshot-feedback');
    const [projects, setProjects] = useState<any[]>([]);
    const [screenshots, setScreenshots] = useState<any[]>([]);
    const [activities, setActivities] = useState<any[]>([]);
    const [selectedProjectId, setSelectedProjectId] = useState<string | null>(null);
    const [selectedScreenshotId, setSelectedScreenshotId] = useState<string | null>(null);
    const [pins, setPins] = useState<any[]>([]);

    // Initial load
    useEffect(() => {
        if (connected) {
            fetchProjects();
        }
    }, [connected]);

    useEffect(() => {
        if (selectedProjectId && connected) {
            fetchScreenshots();
            fetchActivities();
        }
    }, [selectedProjectId, connected]);

    useEffect(() => {
        if (activeTab === 'review' && selectedScreenshotId && connected) {
            fetchPins();
        }
    }, [activeTab, selectedScreenshotId, connected]);

    const fetchPins = async () => {
        try {
            const res: any = await callRPC('list_pins', { screenshotId: selectedScreenshotId });
            setPins(res.pins);
        } catch (err) { console.error(err); }
    };

    const fetchProjects = async () => {
        try {
            const res: any = await callRPC('list_projects');
            setProjects(res.projects);
        } catch (err) { console.error(err); }
    };

    const fetchScreenshots = async () => {
        if (!selectedProjectId) return;
        try {
            const res: any = await callRPC('list_screenshots', { projectId: selectedProjectId });
            // For each screenshot, we also need to load its image data
            const loaded = await Promise.all(res.screenshots.map(async (s: any) => {
                try {
                    const imgRes: any = await callRPC('get_screenshot_image', { file_url: s.file_url });
                    return { ...s, base64: imgRes.base64 };
                } catch {
                    return s;
                }
            }));
            setScreenshots(loaded);
        } catch (err) { console.error(err); }
    };

    const fetchActivities = async () => {
        if (!selectedProjectId) return;
        try {
            const res: any = await callRPC('get_activity', { projectId: selectedProjectId });
            setActivities(res.activities);
        } catch (err) { console.error(err); }
    };

    const handleCreateProject = async () => {
        const name = prompt('Enter project name:');
        if (!name) return;
        try {
            await callRPC('create_project', { name });
            fetchProjects();
        } catch (err) { alert('Failed to create project'); }
    };

    const handleUploadScreenshot = () => {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/png, image/jpeg, image/webp';
        input.onchange = async (e: any) => {
            const file = e.target.files[0];
            if (!file || !selectedProjectId) return;
            
            const reader = new FileReader();
            reader.onload = async () => {
                try {
                    await callRPC('upload_screenshot', {
                        projectId: selectedProjectId,
                        filename: file.name,
                        base64: reader.result
                    });
                    fetchScreenshots();
                    fetchActivities();
                } catch (err: any) {
                    alert('Upload failed: ' + err.message);
                }
            };
            reader.readAsDataURL(file);
        };
        input.click();
    };

    const handleReviewClick = (screenshotId: string) => {
        setSelectedScreenshotId(screenshotId);
        setActiveTab('review');
    };

    const handleImageClick = async (e: React.MouseEvent<HTMLImageElement>) => {
        const rect = e.currentTarget.getBoundingClientRect();
        const x = ((e.clientX - rect.left) / rect.width) * 100;
        const y = ((e.clientY - rect.top) / rect.height) * 100;
        
        const comment = prompt('Enter your comment for this pin:');
        if (!comment) return;
        
        try {
            await callRPC('add_pin', { screenshotId: selectedScreenshotId, x, y, comment });
            fetchPins();
        } catch (err) {
            alert('Failed to add pin');
        }
    };

    const handleResolvePin = async (pinId: string) => {
        try {
            await callRPC('resolve_pin', { pinId });
            fetchPins();
        } catch (err) {
            alert('Failed to resolve pin');
        }
    };

    if (!connected) {
        return (
            <div className="min-h-screen bg-[#FDFDFD] flex items-center justify-center font-sans">
                <div className="text-center space-y-4">
                    <div className="w-8 h-8 border-2 border-slate-900 border-t-transparent rounded-full animate-spin mx-auto" />
                    <p className="text-sm font-medium text-slate-500">Connecting to Runtime Engine...</p>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-[#FAFAFA] text-slate-900 font-sans flex flex-col selection:bg-black selection:text-white">
            {/* Top Navigation Bar - Clean, calm, minimal */}
            <div className="h-14 border-b border-slate-200 bg-white flex items-center justify-between px-6 sticky top-0 z-10">
                <div className="flex items-center gap-6">
                    <div className="flex items-center gap-2">
                        <div className="w-5 h-5 bg-black rounded-md flex items-center justify-center">
                            <ImageIcon className="w-3 h-3 text-white" />
                        </div>
                        <span className="font-semibold text-sm tracking-tight">Feedback</span>
                    </div>
                    
                    <div className="h-4 w-px bg-slate-200" />
                    
                    <div className="flex items-center gap-1">
                        <button 
                            onClick={() => setActiveTab('projects')}
                            className={`px-3 py-1.5 rounded-md text-sm font-medium transition-colors ${activeTab === 'projects' ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50'}`}
                        >
                            Projects
                        </button>
                        {selectedProjectId && (
                            <>
                                <button 
                                    onClick={() => setActiveTab('screenshots')}
                                    className={`px-3 py-1.5 rounded-md text-sm font-medium transition-colors ${(activeTab === 'screenshots' || activeTab === 'review') ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50'}`}
                                >
                                    Screenshots
                                </button>
                                <button 
                                    onClick={() => setActiveTab('activity')}
                                    className={`px-3 py-1.5 rounded-md text-sm font-medium transition-colors ${activeTab === 'activity' ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50'}`}
                                >
                                    Activity Feed
                                </button>
                            </>
                        )}
                    </div>
                </div>

                <div className="flex items-center gap-3">
                    <div className="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-100">
                        <div className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
                        <span className="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">Engine Linked</span>
                    </div>
                </div>
            </div>

            {/* Main Content Area */}
            <main className="flex-1 max-w-6xl w-full mx-auto p-8">
                {activeTab === 'projects' && (
                    <div className="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight">Workspaces</h1>
                                <p className="text-sm text-slate-500 mt-1">Select a project to view and annotate screenshots.</p>
                            </div>
                            <button onClick={handleCreateProject} className="flex items-center gap-1.5 px-4 py-2 bg-black text-white rounded-lg text-sm font-medium hover:bg-slate-800 transition-colors shadow-sm active:scale-95">
                                <Plus className="w-4 h-4" /> New Project
                            </button>
                        </div>

                        {projects.length === 0 ? (
                            <div className="py-20 text-center border border-dashed border-slate-200 rounded-2xl bg-white">
                                <LayoutDashboard className="w-8 h-8 text-slate-300 mx-auto mb-3" />
                                <h3 className="text-sm font-semibold text-slate-900">No projects yet</h3>
                                <p className="text-sm text-slate-500 mt-1">Create a project to start organizing feedback.</p>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                {projects.map(p => (
                                    <div 
                                        key={p.id} 
                                        onClick={() => { setSelectedProjectId(p.id); setActiveTab('screenshots'); }}
                                        className="group bg-white border border-slate-200 rounded-2xl p-5 hover:border-slate-300 hover:shadow-sm transition-all cursor-pointer relative"
                                    >
                                        <div className="flex items-start justify-between mb-8">
                                            <div className="w-10 h-10 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center">
                                                <LayoutDashboard className="w-5 h-5 text-slate-600" />
                                            </div>
                                            <button className="opacity-0 group-hover:opacity-100 p-1 hover:bg-slate-50 rounded-md transition-all text-slate-400 hover:text-slate-900">
                                                <MoreHorizontal className="w-4 h-4" />
                                            </button>
                                        </div>
                                        
                                        <div>
                                            <h3 className="font-semibold text-slate-900 leading-none mb-1.5">{p.name}</h3>
                                            <div className="flex items-center gap-3 text-xs text-slate-500">
                                                <span className="flex items-center gap-1"><ImageIcon className="w-3 h-3" /> {p.screenshotCount}</span>
                                                {p.unresolvedIssues > 0 && (
                                                    <span className="flex items-center gap-1 text-amber-600 font-medium">
                                                        <AlertCircle className="w-3 h-3" /> {p.unresolvedIssues} open
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}

                {activeTab === 'screenshots' && selectedProjectId && (
                    <div className="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                        <div className="flex items-center gap-2 text-sm text-slate-500 mb-2">
                            <button onClick={() => setActiveTab('projects')} className="hover:text-slate-900 transition-colors">Workspaces</button>
                            <ChevronRight className="w-3 h-3" />
                            <span className="font-medium text-slate-900">{projects.find(p => p.id === selectedProjectId)?.name}</span>
                        </div>
                        
                        <div className="flex items-center justify-between">
                            <h1 className="text-2xl font-bold tracking-tight">Screenshots</h1>
                            <button onClick={handleUploadScreenshot} className="flex items-center gap-1.5 px-4 py-2 bg-black text-white rounded-lg text-sm font-medium hover:bg-slate-800 transition-colors shadow-sm active:scale-95">
                                <Upload className="w-4 h-4" /> Upload
                            </button>
                        </div>

                        {screenshots.length === 0 ? (
                            <div className="py-20 text-center border border-dashed border-slate-200 rounded-2xl bg-white">
                                <ImageIcon className="w-8 h-8 text-slate-300 mx-auto mb-3" />
                                <h3 className="text-sm font-semibold text-slate-900">No screenshots</h3>
                                <p className="text-sm text-slate-500 mt-1">Upload a design or UI to begin reviewing.</p>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {screenshots.map(s => (
                                    <div key={s.id} className="bg-white border border-slate-200 rounded-2xl overflow-hidden group">
                                        <div className="aspect-video bg-slate-50 border-b border-slate-100 relative">
                                            {s.base64 ? (
                                                <img src={s.base64} alt={s.filename} className="w-full h-full object-contain" />
                                            ) : (
                                                <div className="absolute inset-0 flex items-center justify-center text-slate-400"><ImageIcon className="w-6 h-6 animate-pulse" /></div>
                                            )}
                                        </div>
                                        <div className="p-4 flex items-center justify-between">
                                            <div className="truncate">
                                                <h4 className="font-medium text-sm text-slate-900 truncate">{s.filename}</h4>
                                                <p className="text-xs text-slate-500 mt-0.5">{new Date(s.created_at * 1000).toLocaleDateString()}</p>
                                            </div>
                                            <button 
                                                onClick={() => handleReviewClick(s.id)}
                                                className="px-3 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 rounded-md text-xs font-medium transition-colors border border-slate-200"
                                            >
                                                Review
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}

                {activeTab === 'activity' && selectedProjectId && (
                    <div className="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300 max-w-2xl mx-auto">
                        <div className="flex items-center justify-between mb-8">
                            <h1 className="text-2xl font-bold tracking-tight">Timeline</h1>
                        </div>
                        
                        <div className="space-y-4">
                            {activities.map((act, i) => (
                                <div key={act.id} className="flex gap-4 group">
                                    <div className="flex flex-col items-center">
                                        <div className="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center shrink-0">
                                            <Activity className="w-3.5 h-3.5 text-slate-500" />
                                        </div>
                                        {i !== activities.length - 1 && <div className="w-px h-full bg-slate-100 my-1" />}
                                    </div>
                                    <div className="pb-6 pt-1">
                                        <p className="text-sm font-medium text-slate-900">{act.message}</p>
                                        <p className="text-xs text-slate-400 mt-1">{new Date(act.created_at * 1000).toLocaleString()}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </main>
        </div>
    );
}

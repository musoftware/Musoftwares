import React, { useState, useEffect, useRef } from 'react';
import { LayoutDashboard, Image as ImageIcon, MessageSquareText, Activity, AlertCircle, Plus, Upload, MoreHorizontal, CheckCircle2, ChevronRight, X } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Textarea } from '@/Components/ui/textarea';
import { useRuntimeWS } from '@/hooks/useRuntimeWS';
import { RuntimePluginModals } from '@/Components/Tools/RuntimePluginModals';

export default function ScreenshotFeedbackRunner({ tool, subscription, runtimePort, pluginSlug }: any) {
    const [activeTab, setActiveTab] = useState('projects');
    const { connected, callRPC, installingPlugin, loginRequired, setLoginRequired } = useRuntimeWS('screenshot-feedback');
    const [projects, setProjects] = useState<any[]>([]);
    const [screenshots, setScreenshots] = useState<any[]>([]);
    const [activities, setActivities] = useState<any[]>([]);
    const [selectedProjectId, setSelectedProjectId] = useState<string | null>(null);
    const [selectedScreenshotId, setSelectedScreenshotId] = useState<string | null>(null);
    const [pins, setPins] = useState<any[]>([]);

    // Custom Annotator States
    const [pendingPin, setPendingPin] = useState<{ x: number, y: number } | null>(null);
    const [newPinComment, setNewPinComment] = useState('');
    const [selectedPinId, setSelectedPinId] = useState<string | null>(null);
    const [filterStatus, setFilterStatus] = useState<'all' | 'open' | 'resolved'>('all');

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
        setPendingPin(null);
    };

    const handleImageClick = (e: React.MouseEvent<HTMLImageElement>) => {
        const rect = e.currentTarget.getBoundingClientRect();
        const x = ((e.clientX - rect.left) / rect.width) * 100;
        const y = ((e.clientY - rect.top) / rect.height) * 100;
        
        setPendingPin({ x, y });
        setNewPinComment('');
    };

    const handleSavePin = async (e?: React.FormEvent) => {
        if (e) e.preventDefault();
        if (!pendingPin || !newPinComment.trim() || !selectedScreenshotId) return;
        try {
            await callRPC('add_pin', { 
                screenshotId: selectedScreenshotId, 
                x: pendingPin.x, 
                y: pendingPin.y, 
                comment: newPinComment.trim() 
            });
            setNewPinComment('');
            setPendingPin(null);
            fetchPins();
            fetchActivities();
        } catch (err) {
            alert('Failed to add pin');
        }
    };

    const handleResolvePin = async (pinId: string) => {
        try {
            await callRPC('resolve_pin', { pinId });
            fetchPins();
            fetchActivities();
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
            <RuntimePluginModals 
                installingPlugin={installingPlugin} 
                loginRequired={loginRequired} 
                setLoginRequired={setLoginRequired} 
            />

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
                        <Button 
                            variant="ghost"
                            onClick={() => setActiveTab('projects')}
                            className={`h-8 px-3 text-sm font-medium transition-colors ${activeTab === 'projects' ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50'}`}
                        >
                            Projects
                        </Button>
                        {selectedProjectId && (
                            <>
                                <Button 
                                    variant="ghost"
                                    onClick={() => setActiveTab('screenshots')}
                                    className={`h-8 px-3 text-sm font-medium transition-colors ${(activeTab === 'screenshots' || activeTab === 'review') ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50'}`}
                                >
                                    Screenshots
                                </Button>
                                <Button 
                                    variant="ghost"
                                    onClick={() => setActiveTab('activity')}
                                    className={`h-8 px-3 text-sm font-medium transition-colors ${activeTab === 'activity' ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50'}`}
                                >
                                    Activity Feed
                                </Button>
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
                            <Button onClick={handleCreateProject} className="gap-1.5 h-9 bg-black text-white hover:bg-slate-800 shadow-sm">
                                <Plus className="w-4 h-4" /> New Project
                            </Button>
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
                                            <Button variant="ghost" size="icon" className="opacity-0 group-hover:opacity-100 h-8 w-8 text-slate-400 hover:text-slate-900">
                                                <MoreHorizontal className="w-4 h-4" />
                                            </Button>
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
                            <Button variant="link" onClick={() => setActiveTab('projects')} className="h-auto p-0 text-slate-500 hover:text-slate-900 transition-colors font-normal">Workspaces</Button>
                            <ChevronRight className="w-3 h-3" />
                            <span className="font-medium text-slate-900">{projects.find(p => p.id === selectedProjectId)?.name}</span>
                        </div>
                        
                        <div className="flex items-center justify-between">
                            <h1 className="text-2xl font-bold tracking-tight">Screenshots</h1>
                            <Button onClick={handleUploadScreenshot} className="gap-1.5 h-9 bg-black text-white hover:bg-slate-800 shadow-sm">
                                <Upload className="w-4 h-4" /> Upload
                            </Button>
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
                                            <Button 
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleReviewClick(s.id)}
                                                className="h-8 text-xs font-medium bg-slate-50 hover:bg-slate-100"
                                            >
                                                Review
                                            </Button>
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

                {activeTab === 'review' && selectedScreenshotId && (() => {
                    const currentScreenshot = screenshots.find(s => s.id === selectedScreenshotId);
                    return (
                        <div className="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                            {/* Breadcrumbs / Back navigation */}
                            <div className="flex items-center gap-2 text-sm text-slate-500 mb-2">
                                <Button variant="link" onClick={() => setActiveTab('projects')} className="h-auto p-0 text-slate-500 hover:text-slate-900 transition-colors font-normal">Workspaces</Button>
                                <ChevronRight className="w-3 h-3" />
                                <Button variant="link" onClick={() => setActiveTab('screenshots')} className="h-auto p-0 text-slate-500 hover:text-slate-900 transition-colors font-normal">{projects.find(p => p.id === selectedProjectId)?.name}</Button>
                                <ChevronRight className="w-3 h-3" />
                                <span className="font-medium text-slate-900 truncate max-w-[200px]">{currentScreenshot?.filename}</span>
                            </div>

                            {/* Top bar with screenshot name and close button */}
                            <div className="flex items-center justify-between border-b border-slate-200 pb-4">
                                <div>
                                    <h1 className="text-xl font-bold tracking-tight">{currentScreenshot?.filename}</h1>
                                    <p className="text-xs text-slate-400 mt-0.5">Click anywhere on the screenshot to drop a feedback pin.</p>
                                </div>
                                <Button 
                                    variant="outline"
                                    size="icon"
                                    onClick={() => { setActiveTab('screenshots'); setPendingPin(null); }}
                                    className="h-8 w-8 text-slate-400 hover:text-slate-900"
                                >
                                    <X className="w-4 h-4" />
                                </Button>
                            </div>

                            {/* Split Workspace Layout */}
                            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                                {/* Left Side: Dynamic Canvas Workspace (2/3 width) */}
                                <div className="lg:col-span-2 space-y-4">
                                    <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex items-center justify-center relative overflow-hidden group">
                                        <div className="relative max-w-full inline-block rounded-xl border border-slate-100 shadow-sm overflow-hidden select-none">
                                            {currentScreenshot?.base64 ? (
                                                <img 
                                                    src={currentScreenshot.base64} 
                                                    alt={currentScreenshot.filename} 
                                                    onClick={handleImageClick}
                                                    className="max-h-[65vh] w-auto object-contain cursor-crosshair transition-all"
                                                />
                                            ) : (
                                                <div className="w-[500px] h-[300px] flex items-center justify-center bg-slate-50 text-slate-400">
                                                    <ImageIcon className="w-8 h-8 animate-pulse" />
                                                </div>
                                            )}

                                            {/* Pins Layer */}
                                            {pins
                                                .filter(pin => {
                                                    if (filterStatus === 'open') return pin.status === 'open';
                                                    if (filterStatus === 'resolved') return pin.status === 'resolved';
                                                    return true;
                                                })
                                                .map((pin, idx) => {
                                                    const isOpen = pin.status === 'open';
                                                    const isFocused = selectedPinId === pin.id;
                                                    return (
                                                        <div 
                                                            key={pin.id}
                                                            onClick={(e) => {
                                                                e.stopPropagation();
                                                                setSelectedPinId(pin.id);
                                                                const el = document.getElementById(`pin-card-${pin.id}`);
                                                                if (el) {
                                                                    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                                                                }
                                                            }}
                                                            onMouseEnter={() => setSelectedPinId(pin.id)}
                                                            onMouseLeave={() => setSelectedPinId(null)}
                                                            className={`absolute cursor-pointer transition-all duration-200 z-20 flex items-center justify-center -translate-x-1/2 -translate-y-1/2 rounded-full font-sans text-xs font-bold shadow-md
                                                                ${isOpen 
                                                                    ? 'w-7 h-7 bg-slate-900 border-2 border-white text-white' 
                                                                    : 'w-6 h-6 bg-emerald-500 border-2 border-white text-white'
                                                                }
                                                                ${isFocused ? 'scale-125 ring-4 ring-slate-900/10' : 'hover:scale-115'}
                                                            `}
                                                            style={{ left: `${pin.x}%`, top: `${pin.y}%` }}
                                                        >
                                                            {isOpen ? (
                                                                <>
                                                                    {idx + 1}
                                                                    <span className="absolute -inset-1 rounded-full border border-slate-900 animate-ping opacity-35 pointer-events-none" />
                                                                </>
                                                            ) : (
                                                                <CheckCircle2 className="w-3.5 h-3.5" />
                                                            )}
                                                        </div>
                                                    );
                                                })
                                            }

                                            {/* Floating dynamic popup input form */}
                                            {pendingPin && (
                                                <div 
                                                    className="absolute z-30 -translate-x-1/2 mt-4 bg-white border border-slate-200 rounded-xl shadow-xl p-4 w-72 animate-in zoom-in-95 duration-150"
                                                    style={{ left: `${pendingPin.x}%`, top: `${pendingPin.y}%` }}
                                                    onClick={(e) => e.stopPropagation()}
                                                >
                                                    {/* Visual arrow pointer */}
                                                    <div className="absolute -top-1.5 left-1/2 -translate-x-1/2 w-3 h-3 bg-white border-t border-l border-slate-200 rotate-45" />

                                                    <form onSubmit={handleSavePin} className="space-y-3 relative z-10">
                                                        <div className="flex items-center justify-between">
                                                            <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Add Comment</span>
                                                            <Button 
                                                                variant="ghost"
                                                                size="icon"
                                                                type="button" 
                                                                onClick={() => setPendingPin(null)}
                                                                className="h-6 w-6 text-slate-400 hover:text-slate-900 hover:bg-slate-50"
                                                            >
                                                                <X className="w-3.5 h-3.5" />
                                                            </Button>
                                                        </div>
                                                        <Textarea 
                                                            autoFocus
                                                            rows={3}
                                                            value={newPinComment}
                                                            onChange={(e) => setNewPinComment(e.target.value)}
                                                            placeholder="What needs feedback at this exact spot?"
                                                            className="text-xs bg-slate-50 resize-none font-sans"
                                                        />
                                                        <div className="flex justify-end gap-2 text-xs">
                                                            <Button 
                                                                variant="outline"
                                                                size="sm"
                                                                type="button" 
                                                                onClick={() => setPendingPin(null)}
                                                                className="h-7 text-xs font-medium text-slate-600"
                                                            >
                                                                Cancel
                                                            </Button>
                                                            <Button 
                                                                size="sm"
                                                                type="submit"
                                                                disabled={!newPinComment.trim()}
                                                                className="h-7 text-xs bg-black text-white hover:bg-slate-800 shadow-sm"
                                                            >
                                                                Save Pin
                                                            </Button>
                                                        </div>
                                                    </form>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                {/* Right Side: Annotations Sidebar (1/3 width) */}
                                <div className="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden flex flex-col h-[65vh]">
                                    {/* Header with total and count */}
                                    <div className="p-4 border-b border-slate-100 bg-slate-50/50">
                                        <h3 className="font-bold text-sm tracking-tight text-slate-800">Annotations</h3>
                                        
                                        {/* Sidebar Navigation Tabs */}
                                        <div className="flex bg-slate-100 rounded-lg p-0.5 mt-3 text-xs">
                                            <Button 
                                                variant="ghost"
                                                onClick={() => setFilterStatus('all')}
                                                className={`flex-1 h-7 rounded-md font-medium transition-colors ${filterStatus === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900 hover:bg-transparent'}`}
                                            >
                                                All ({pins.length})
                                            </Button>
                                            <Button 
                                                variant="ghost"
                                                onClick={() => setFilterStatus('open')}
                                                className={`flex-1 h-7 rounded-md font-medium transition-colors ${filterStatus === 'open' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900 hover:bg-transparent'}`}
                                            >
                                                Open ({pins.filter(p => p.status === 'open').length})
                                            </Button>
                                            <Button 
                                                variant="ghost"
                                                onClick={() => setFilterStatus('resolved')}
                                                className={`flex-1 h-7 rounded-md font-medium transition-colors ${filterStatus === 'resolved' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900 hover:bg-transparent'}`}
                                            >
                                                Resolved ({pins.filter(p => p.status === 'resolved').length})
                                            </Button>
                                        </div>
                                    </div>

                                    {/* Comments List */}
                                    <div className="flex-1 overflow-y-auto p-4 space-y-3 scrollbar-thin">
                                        {pins
                                            .filter(pin => {
                                                if (filterStatus === 'open') return pin.status === 'open';
                                                if (filterStatus === 'resolved') return pin.status === 'resolved';
                                                return true;
                                            })
                                            .map((pin, idx) => {
                                                const isOpen = pin.status === 'open';
                                                const isFocused = selectedPinId === pin.id;
                                                
                                                return (
                                                    <div 
                                                        key={pin.id}
                                                        id={`pin-card-${pin.id}`}
                                                        onMouseEnter={() => setSelectedPinId(pin.id)}
                                                        onMouseLeave={() => setSelectedPinId(null)}
                                                        className={`border rounded-xl p-3.5 transition-all duration-200 group/card relative
                                                            ${isFocused 
                                                                ? 'border-slate-900 bg-slate-50/70 shadow-sm' 
                                                                : 'border-slate-100 bg-white hover:border-slate-200 hover:shadow-xs'
                                                            }
                                                        `}
                                                    >
                                                        <div className="flex items-start justify-between gap-3">
                                                            <div className="flex items-center gap-2">
                                                                <div className={`w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold shadow-xs
                                                                    ${isOpen 
                                                                        ? 'bg-slate-900 text-white' 
                                                                        : 'bg-emerald-100 text-emerald-700'
                                                                    }
                                                                `}>
                                                                    {isOpen ? idx + 1 : <CheckCircle2 className="w-3.5 h-3.5" />}
                                                                </div>
                                                                <span className="text-[10px] font-semibold text-slate-400 tracking-tight">
                                                                    {new Date(pin.created_at * 1000).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                                                </span>
                                                            </div>

                                                            {isOpen && (
                                                                <Button 
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    onClick={() => handleResolvePin(pin.id)}
                                                                    className="opacity-0 group-hover/card:opacity-100 h-6 w-6 hover:bg-emerald-50 text-slate-400 hover:text-emerald-600 transition-all border border-transparent hover:border-emerald-100"
                                                                    title="Mark resolved"
                                                                >
                                                                    <CheckCircle2 className="w-3.5 h-3.5" />
                                                                </Button>
                                                            )}
                                                        </div>

                                                        <p className="text-xs text-slate-700 mt-2 font-sans leading-relaxed whitespace-pre-wrap">
                                                            {pin.comment}
                                                        </p>
                                                        
                                                        {!isOpen && (
                                                            <div className="mt-2.5 flex items-center gap-1 text-[10px] text-emerald-600 font-medium bg-emerald-50/50 w-fit px-2 py-0.5 rounded-md border border-emerald-100/50">
                                                                <CheckCircle2 className="w-3 h-3" /> Resolved
                                                            </div>
                                                        )}
                                                    </div>
                                                );
                                            })
                                        }

                                        {pins.filter(pin => {
                                            if (filterStatus === 'open') return pin.status === 'open';
                                            if (filterStatus === 'resolved') return pin.status === 'resolved';
                                            return true;
                                        }).length === 0 && (
                                            <div className="py-12 text-center text-slate-400">
                                                <MessageSquareText className="w-6 h-6 text-slate-200 mx-auto mb-2" />
                                                <p className="text-xs">No annotations in this category</p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    );
                })()}
            </main>
        </div>
    );
}

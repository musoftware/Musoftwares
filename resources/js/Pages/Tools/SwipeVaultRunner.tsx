import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
    Bookmark, Plus, Search, Tag, Trash2, Edit3, FolderOpen, Image as ImageIcon,
    Link as LinkIcon, X, ChevronRight, Grid3X3, LayoutList, Upload, MoreHorizontal,
    ExternalLink, StickyNote, FolderPlus, MoveRight, Hash, Palette, Check
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogFooter, DialogDescription
} from '@/Components/ui/dialog';
import { useRuntimeWS } from '@/hooks/useRuntimeWS';
import { RuntimePluginModals } from '@/Components/Tools/RuntimePluginModals';
import { __ } from '@/lib/i18n';

const COLLECTION_COLORS = [
    '#f59e0b', '#ef4444', '#3b82f6', '#10b981', '#8b5cf6',
    '#ec4899', '#06b6d4', '#f97316', '#14b8a6', '#6366f1',
];

interface Collection {
    id: string;
    name: string;
    color: string;
    swipeCount: number;
    previews: { id: string; file_url: string }[];
    created_at: number;
    updated_at: number;
}

interface Swipe {
    id: string;
    collection_id: string | null;
    title: string;
    source_url: string;
    notes: string;
    tags: string[];
    file_url: string;
    type: string;
    created_at: number;
    base64?: string;
}

interface TagInfo {
    name: string;
    count: number;
}

export default function SwipeVaultRunner({ tool, subscription, runtimePort, pluginSlug }: any) {
    const { connected, callRPC, installingPlugin, loginRequired, setLoginRequired } = useRuntimeWS('swipe-vault');

    // State
    const [collections, setCollections] = useState<Collection[]>([]);
    const [swipes, setSwipes] = useState<Swipe[]>([]);
    const [allTags, setAllTags] = useState<TagInfo[]>([]);
    const [stats, setStats] = useState<any>(null);
    const [activeView, setActiveView] = useState<'collections' | 'swipes' | 'detail'>('collections');
    const [selectedCollectionId, setSelectedCollectionId] = useState<string | null>(null);
    const [selectedSwipe, setSelectedSwipe] = useState<Swipe | null>(null);
    const [searchQuery, setSearchQuery] = useState('');
    const [activeTag, setActiveTag] = useState<string | null>(null);
    const [viewMode, setViewMode] = useState<'grid' | 'list'>('grid');
    const [isLoading, setIsLoading] = useState(false);

    // Modals
    const [showAddSwipe, setShowAddSwipe] = useState(false);
    const [showNewCollection, setShowNewCollection] = useState(false);
    const [showEditSwipe, setShowEditSwipe] = useState(false);
    const [showMoveSwipe, setShowMoveSwipe] = useState(false);

    // Form state
    const [newCollectionName, setNewCollectionName] = useState('');
    const [newCollectionColor, setNewCollectionColor] = useState(COLLECTION_COLORS[0]);
    const [addTitle, setAddTitle] = useState('');
    const [addUrl, setAddUrl] = useState('');
    const [addNotes, setAddNotes] = useState('');
    const [addTags, setAddTags] = useState('');
    const [addBase64, setAddBase64] = useState<string | null>(null);
    const [addFilename, setAddFilename] = useState('');

    // Debounce search
    const searchTimeout = useRef<ReturnType<typeof setTimeout> | null>(null);

    // ── Data Fetching ──

    const fetchCollections = useCallback(async () => {
        if (!connected) return;
        try {
            const res: any = await callRPC('list_collections', {});
            setCollections(res.collections || []);
        } catch (err) { console.error('Failed to fetch collections:', err); }
    }, [connected, callRPC]);

    const fetchSwipes = useCallback(async (collectionId?: string | null, search?: string, tag?: string | null) => {
        if (!connected) return;
        setIsLoading(true);
        try {
            const res: any = await callRPC('list_swipes', {
                collectionId: collectionId || undefined,
                search: search || undefined,
                tag: tag || undefined,
                limit: 200,
                offset: 0,
            });

            // Load images
            const loaded = await Promise.all((res.swipes || []).map(async (s: Swipe) => {
                if (s.file_url) {
                    try {
                        const imgRes: any = await callRPC('get_swipe_image', { file_url: s.file_url });
                        return { ...s, base64: imgRes.base64 };
                    } catch { return s; }
                }
                return s;
            }));

            setSwipes(loaded);
        } catch (err) { console.error('Failed to fetch swipes:', err); }
        setIsLoading(false);
    }, [connected, callRPC]);

    const fetchTags = useCallback(async () => {
        if (!connected) return;
        try {
            const res: any = await callRPC('get_all_tags', {});
            setAllTags(res.tags || []);
        } catch (err) { console.error('Failed to fetch tags:', err); }
    }, [connected, callRPC]);

    const fetchStats = useCallback(async () => {
        if (!connected) return;
        try {
            const res: any = await callRPC('get_stats', {});
            setStats(res);
        } catch (err) { console.error('Failed to fetch stats:', err); }
    }, [connected, callRPC]);

    useEffect(() => {
        if (connected) {
            fetchCollections();
            fetchTags();
            fetchStats();
        }
    }, [connected]);

    useEffect(() => {
        if (activeView === 'swipes') {
            fetchSwipes(selectedCollectionId, searchQuery, activeTag);
        }
    }, [activeView, selectedCollectionId, activeTag]);

    // Debounced search
    useEffect(() => {
        if (activeView !== 'swipes') return;
        if (searchTimeout.current) clearTimeout(searchTimeout.current);
        searchTimeout.current = setTimeout(() => {
            fetchSwipes(selectedCollectionId, searchQuery, activeTag);
        }, 350);
        return () => { if (searchTimeout.current) clearTimeout(searchTimeout.current); };
    }, [searchQuery]);

    // ── Actions ──

    const handleOpenCollection = (collectionId: string | null) => {
        setSelectedCollectionId(collectionId);
        setActiveView('swipes');
        setSearchQuery('');
        setActiveTag(null);
    };

    const handleCreateCollection = async () => {
        if (!newCollectionName.trim()) return;
        try {
            await callRPC('create_collection', { name: newCollectionName.trim(), color: newCollectionColor });
            setNewCollectionName('');
            setNewCollectionColor(COLLECTION_COLORS[0]);
            setShowNewCollection(false);
            fetchCollections();
            fetchStats();
        } catch (err) { console.error(err); }
    };

    const handleDeleteCollection = async (id: string) => {
        if (!confirm('Delete this collection? Swipes inside will become uncategorized.')) return;
        try {
            await callRPC('delete_collection', { id });
            fetchCollections();
            fetchStats();
        } catch (err) { console.error(err); }
    };

    const handleFileSelect = () => {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/png,image/jpeg,image/webp,image/gif';
        input.onchange = (e: any) => {
            const file = e.target.files[0];
            if (!file) return;
            setAddFilename(file.name);
            const reader = new FileReader();
            reader.onload = () => { setAddBase64(reader.result as string); };
            reader.readAsDataURL(file);
        };
        input.click();
    };

    const handleAddSwipe = async () => {
        if (!addBase64 && !addUrl.trim() && !addTitle.trim()) return;
        try {
            const tags = addTags.split(',').map(t => t.trim()).filter(Boolean);
            await callRPC('add_swipe', {
                collectionId: selectedCollectionId,
                title: addTitle.trim() || addFilename || 'Untitled',
                sourceUrl: addUrl.trim(),
                notes: addNotes.trim(),
                tags,
                base64: addBase64,
                type: 'image',
            });
            resetAddForm();
            setShowAddSwipe(false);
            fetchSwipes(selectedCollectionId, searchQuery, activeTag);
            fetchCollections();
            fetchTags();
            fetchStats();
        } catch (err: any) { alert('Failed to save: ' + err.message); }
    };

    const handleDeleteSwipe = async (id: string) => {
        try {
            await callRPC('delete_swipe', { id });
            fetchSwipes(selectedCollectionId, searchQuery, activeTag);
            fetchCollections();
            fetchStats();
            if (selectedSwipe?.id === id) {
                setSelectedSwipe(null);
                setActiveView('swipes');
            }
        } catch (err) { console.error(err); }
    };

    const handleUpdateSwipe = async () => {
        if (!selectedSwipe) return;
        try {
            const tags = addTags.split(',').map(t => t.trim()).filter(Boolean);
            await callRPC('update_swipe', {
                id: selectedSwipe.id,
                title: addTitle.trim(),
                notes: addNotes.trim(),
                tags,
                collectionId: selectedSwipe.collection_id,
            });
            setShowEditSwipe(false);
            fetchSwipes(selectedCollectionId, searchQuery, activeTag);
            fetchTags();
        } catch (err) { console.error(err); }
    };

    const handleMoveSwipe = async (swipeId: string, collectionId: string | null) => {
        try {
            await callRPC('move_swipe', { id: swipeId, collectionId });
            fetchSwipes(selectedCollectionId, searchQuery, activeTag);
            fetchCollections();
            setShowMoveSwipe(false);
        } catch (err) { console.error(err); }
    };

    const resetAddForm = () => {
        setAddTitle('');
        setAddUrl('');
        setAddNotes('');
        setAddTags('');
        setAddBase64(null);
        setAddFilename('');
    };

    const openEditSwipe = (swipe: Swipe) => {
        setSelectedSwipe(swipe);
        setAddTitle(swipe.title);
        setAddNotes(swipe.notes);
        setAddTags(swipe.tags.join(', '));
        setShowEditSwipe(true);
    };

    const getCollectionName = (id: string | null) => {
        if (!id) return 'Uncategorized';
        return collections.find(c => c.id === id)?.name || 'Unknown';
    };

    const getCollectionColor = (id: string | null) => {
        if (!id) return '#6b7280';
        return collections.find(c => c.id === id)?.color || '#f59e0b';
    };

    // ── Rendering ──

    if (!connected) {
        return (
            <div className="min-h-screen bg-[#0c0c0e] flex items-center justify-center font-sans">
                <div className="text-center space-y-4">
                    <div className="w-8 h-8 border-2 border-amber-500 border-t-transparent rounded-full animate-spin mx-auto" />
                    <p className="text-sm font-medium text-slate-500">{__('general.connecting_to_runtime_engine')}</p>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-[#0c0c0e] text-slate-100 font-sans flex flex-col selection:bg-amber-500/30 selection:text-white">
            <RuntimePluginModals
                installingPlugin={installingPlugin}
                loginRequired={loginRequired}
                setLoginRequired={setLoginRequired}
            />

            {/* Top Navigation */}
            <div className="h-14 border-b border-white/[0.06] bg-[#111113] flex items-center justify-between px-4 md:px-6 sticky top-0 z-30 backdrop-blur-xl">
                <div className="flex items-center gap-4">
                    <div className="flex items-center gap-2.5">
                        <div className="w-7 h-7 bg-gradient-to-br from-amber-500 to-orange-600 rounded-lg flex items-center justify-center shadow-lg shadow-amber-500/20">
                            <Bookmark className="w-3.5 h-3.5 text-white" />
                        </div>
                        <span className="font-bold text-sm tracking-tight text-white">{__('general.ads_library')}</span>
                    </div>

                    <div className="h-4 w-px bg-white/10" />

                    <div className="flex items-center gap-0.5">
                        <Button
                            variant="ghost"
                            onClick={() => { setActiveView('collections'); setSelectedCollectionId(null); }}
                            className={`h-8 px-3 text-xs font-medium rounded-lg transition-all ${
                                activeView === 'collections'
                                    ? 'bg-white/10 text-white'
                                    : 'text-slate-400 hover:text-white hover:bg-white/5'
                            }`}
                        >
                            Collections
                        </Button>
                        {activeView !== 'collections' && (
                            <Button
                                variant="ghost"
                                onClick={() => setActiveView('swipes')}
                                className={`h-8 px-3 text-xs font-medium rounded-lg transition-all ${
                                    activeView === 'swipes' || activeView === 'detail'
                                        ? 'bg-white/10 text-white'
                                        : 'text-slate-400 hover:text-white hover:bg-white/5'
                                }`}
                            >
                                {getCollectionName(selectedCollectionId)}
                            </Button>
                        )}
                    </div>
                </div>

                <div className="flex items-center gap-3">
                    {stats && (
                        <div className="hidden md:flex items-center gap-2 text-[10px] text-slate-500 font-medium">
                            <span>{stats.totalSwipes} swipes</span>
                            <span>·</span>
                            <span>{stats.totalCollections} collections</span>
                        </div>
                    )}
                    <div className="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20">
                        <div className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
                        <span className="text-[10px] font-bold text-emerald-400 uppercase tracking-wider">Online</span>
                    </div>
                </div>
            </div>

            {/* Main Content */}
            <main className="flex-1 w-full max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8">

                {/* ── Collections View ── */}
                {activeView === 'collections' && (
                    <div className="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                        <div className="flex items-center justify-between flex-wrap gap-4">
                            <div>
                                <h1 className="text-2xl md:text-3xl font-black tracking-tight text-white">Collections</h1>
                                <p className="text-sm text-slate-500 mt-1">{__('general.organize_your_creative_inspiration_into_themed_boards')}</p>
                            </div>
                            <Button
                                onClick={() => setShowNewCollection(true)}
                                className="gap-1.5 h-9 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white border-0 shadow-lg shadow-amber-500/20 font-bold text-xs"
                            >
                                <Plus className="w-4 h-4" />{__('general.new_collection')}</Button>
                        </div>

                        {/* Quick Access: All + Uncategorized */}
                        <div className="grid grid-cols-2 gap-3">
                            <button
                                onClick={() => { setSelectedCollectionId(null); setActiveView('swipes'); }}
                                className="group bg-white/[0.03] border border-white/[0.06] rounded-2xl p-5 hover:bg-white/[0.06] hover:border-white/10 transition-all text-left"
                            >
                                <div className="flex items-center gap-3 mb-3">
                                    <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-700 to-slate-800 flex items-center justify-center border border-white/10">
                                        <Grid3X3 className="w-5 h-5 text-slate-300" />
                                    </div>
                                    <div>
                                        <h3 className="text-sm font-bold text-white">{__('general.all_swipes')}</h3>
                                        <p className="text-xs text-slate-500">{stats?.totalSwipes || 0} items</p>
                                    </div>
                                </div>
                            </button>
                            <button
                                onClick={() => handleOpenCollection('__uncategorized__')}
                                className="group bg-white/[0.03] border border-white/[0.06] rounded-2xl p-5 hover:bg-white/[0.06] hover:border-white/10 transition-all text-left"
                            >
                                <div className="flex items-center gap-3 mb-3">
                                    <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-700 to-slate-800 flex items-center justify-center border border-white/10">
                                        <FolderOpen className="w-5 h-5 text-slate-300" />
                                    </div>
                                    <div>
                                        <h3 className="text-sm font-bold text-white">Uncategorized</h3>
                                        <p className="text-xs text-slate-500">{stats?.uncategorized || 0} items</p>
                                    </div>
                                </div>
                            </button>
                        </div>

                        {/* Collections Grid */}
                        {collections.length === 0 ? (
                            <div className="py-20 text-center border border-dashed border-white/10 rounded-2xl bg-white/[0.02]">
                                <Bookmark className="w-8 h-8 text-slate-700 mx-auto mb-3" />
                                <h3 className="text-sm font-bold text-slate-300">{__('general.no_collections_yet')}</h3>
                                <p className="text-xs text-slate-500 mt-1">{__('general.create_a_collection_to_start_organizing_your_swipe_file')}</p>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                {collections.map(c => (
                                    <div
                                        key={c.id}
                                        onClick={() => handleOpenCollection(c.id)}
                                        className="group bg-white/[0.03] border border-white/[0.06] rounded-2xl overflow-hidden hover:bg-white/[0.06] hover:border-white/10 transition-all cursor-pointer relative"
                                    >
                                        {/* Preview mosaic */}
                                        <div className="aspect-[16/9] bg-black/30 relative overflow-hidden">
                                            {c.previews && c.previews.length > 0 ? (
                                                <div className="grid grid-cols-2 gap-0.5 w-full h-full">
                                                    {c.previews.slice(0, 4).map((p, i) => (
                                                        <div key={p.id} className="bg-white/5 overflow-hidden">
                                                            <PreviewThumb fileUrl={p.file_url} callRPC={callRPC} />
                                                        </div>
                                                    ))}
                                                    {c.previews.length < 4 && Array.from({ length: 4 - c.previews.length }).map((_, i) => (
                                                        <div key={`empty-${i}`} className="bg-white/[0.02]" />
                                                    ))}
                                                </div>
                                            ) : (
                                                <div className="w-full h-full flex items-center justify-center">
                                                    <ImageIcon className="w-8 h-8 text-white/10" />
                                                </div>
                                            )}
                                            {/* Color bar */}
                                            <div className="absolute bottom-0 left-0 right-0 h-1" style={{ backgroundColor: c.color }} />
                                        </div>

                                        <div className="p-4 flex items-center justify-between">
                                            <div className="min-w-0">
                                                <div className="flex items-center gap-2">
                                                    <div className="w-3 h-3 rounded-full shrink-0" style={{ backgroundColor: c.color }} />
                                                    <h3 className="text-sm font-bold text-white truncate">{c.name}</h3>
                                                </div>
                                                <p className="text-xs text-slate-500 mt-1 ml-5">{c.swipeCount} swipes</p>
                                            </div>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={(e) => { e.stopPropagation(); handleDeleteCollection(c.id); }}
                                                className="opacity-0 group-hover:opacity-100 h-8 w-8 text-slate-500 hover:text-red-400 hover:bg-red-500/10 transition-all"
                                            >
                                                <Trash2 className="w-3.5 h-3.5" />
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}

                        {/* Tags Cloud */}
                        {allTags.length > 0 && (
                            <div className="pt-4 border-t border-white/[0.06]">
                                <h3 className="text-xs font-bold text-slate-500 uppercase tracking-widest mb-3">{__('general.popular_tags')}</h3>
                                <div className="flex flex-wrap gap-2">
                                    {allTags.slice(0, 20).map(t => (
                                        <button
                                            key={t.name}
                                            onClick={() => { setActiveTag(t.name); setSelectedCollectionId(null); setActiveView('swipes'); }}
                                            className="flex items-center gap-1.5 px-3 py-1.5 bg-white/[0.04] border border-white/[0.08] rounded-full text-xs text-slate-400 hover:text-white hover:bg-white/[0.08] hover:border-white/15 transition-all"
                                        >
                                            <Hash className="w-3 h-3" />
                                            {t.name}
                                            <span className="text-slate-600 ml-0.5">{t.count}</span>
                                        </button>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {/* ── Swipes Grid View ── */}
                {activeView === 'swipes' && (
                    <div className="space-y-5 animate-in fade-in slide-in-from-bottom-2 duration-300">
                        {/* Breadcrumb */}
                        <div className="flex items-center gap-1.5 text-xs text-slate-500">
                            <button onClick={() => setActiveView('collections')} className="hover:text-white transition-colors">Collections</button>
                            <ChevronRight className="w-3 h-3" />
                            <span className="text-white font-medium">{getCollectionName(selectedCollectionId)}</span>
                        </div>

                        {/* Toolbar */}
                        <div className="flex items-center justify-between flex-wrap gap-3">
                            <div className="flex items-center gap-2 flex-1 min-w-0 max-w-md">
                                <div className="relative flex-1">
                                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
                                    <Input
                                        type="text"
                                        placeholder={__('general.search_swipes')}
                                        value={searchQuery}
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                        className="pl-9 h-9 bg-white/[0.04] border-white/[0.08] text-white placeholder:text-slate-600 text-sm focus:border-amber-500/50 focus:ring-amber-500/20"
                                    />
                                </div>
                                {activeTag && (
                                    <Badge
                                        variant="secondary"
                                        className="bg-amber-500/10 text-amber-400 border-amber-500/20 gap-1 cursor-pointer hover:bg-amber-500/20 transition-colors"
                                        onClick={() => setActiveTag(null)}
                                    >
                                        <Hash className="w-3 h-3" /> {activeTag} <X className="w-3 h-3" />
                                    </Badge>
                                )}
                            </div>

                            <div className="flex items-center gap-2">
                                <div className="flex items-center bg-white/[0.04] border border-white/[0.08] rounded-lg p-0.5">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => setViewMode('grid')}
                                        className={`h-7 w-7 rounded-md ${viewMode === 'grid' ? 'bg-white/10 text-white' : 'text-slate-500 hover:text-white'}`}
                                    >
                                        <Grid3X3 className="w-3.5 h-3.5" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => setViewMode('list')}
                                        className={`h-7 w-7 rounded-md ${viewMode === 'list' ? 'bg-white/10 text-white' : 'text-slate-500 hover:text-white'}`}
                                    >
                                        <LayoutList className="w-3.5 h-3.5" />
                                    </Button>
                                </div>
                                <Button
                                    onClick={() => { resetAddForm(); setShowAddSwipe(true); }}
                                    className="gap-1.5 h-9 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white border-0 shadow-lg shadow-amber-500/20 font-bold text-xs"
                                >
                                    <Plus className="w-4 h-4" />{__('general.add_swipe')}</Button>
                            </div>
                        </div>

                        {/* Content */}
                        {isLoading ? (
                            <div className="py-20 flex justify-center">
                                <div className="w-6 h-6 border-2 border-amber-500 border-t-transparent rounded-full animate-spin" />
                            </div>
                        ) : swipes.length === 0 ? (
                            <div className="py-20 text-center border border-dashed border-white/10 rounded-2xl bg-white/[0.02]">
                                <ImageIcon className="w-8 h-8 text-slate-700 mx-auto mb-3" />
                                <h3 className="text-sm font-bold text-slate-300">{__('general.no_swipes_found')}</h3>
                                <p className="text-xs text-slate-500 mt-1">{__('general.upload_creative_inspiration_to_build_your_swipe_file')}</p>
                                <Button
                                    onClick={() => { resetAddForm(); setShowAddSwipe(true); }}
                                    className="mt-4 gap-1.5 h-9 bg-white/10 hover:bg-white/15 text-white border border-white/10 font-medium text-xs"
                                >
                                    <Upload className="w-4 h-4" />{__('general.add_your_first_swipe')}</Button>
                            </div>
                        ) : viewMode === 'grid' ? (
                            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
                                {swipes.map(s => (
                                    <SwipeCard
                                        key={s.id}
                                        swipe={s}
                                        onOpen={() => { setSelectedSwipe(s); setActiveView('detail'); }}
                                        onEdit={() => openEditSwipe(s)}
                                        onDelete={() => handleDeleteSwipe(s.id)}
                                        onMove={() => { setSelectedSwipe(s); setShowMoveSwipe(true); }}
                                        collectionColor={getCollectionColor(s.collection_id)}
                                    />
                                ))}
                            </div>
                        ) : (
                            <div className="space-y-2">
                                {swipes.map(s => (
                                    <SwipeRow
                                        key={s.id}
                                        swipe={s}
                                        onOpen={() => { setSelectedSwipe(s); setActiveView('detail'); }}
                                        onEdit={() => openEditSwipe(s)}
                                        onDelete={() => handleDeleteSwipe(s.id)}
                                        collectionColor={getCollectionColor(s.collection_id)}
                                    />
                                ))}
                            </div>
                        )}
                    </div>
                )}

                {/* ── Detail View ── */}
                {activeView === 'detail' && selectedSwipe && (
                    <div className="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                        <div className="flex items-center gap-1.5 text-xs text-slate-500">
                            <button onClick={() => setActiveView('collections')} className="hover:text-white transition-colors">Collections</button>
                            <ChevronRight className="w-3 h-3" />
                            <button onClick={() => setActiveView('swipes')} className="hover:text-white transition-colors">{getCollectionName(selectedCollectionId)}</button>
                            <ChevronRight className="w-3 h-3" />
                            <span className="text-white font-medium truncate max-w-[200px]">{selectedSwipe.title || 'Untitled'}</span>
                        </div>

                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                            {/* Image */}
                            <div className="lg:col-span-2">
                                <div className="bg-white/[0.03] border border-white/[0.06] rounded-2xl overflow-hidden">
                                    {selectedSwipe.base64 ? (
                                        <img src={selectedSwipe.base64} alt={selectedSwipe.title} className="w-full object-contain max-h-[70vh]" />
                                    ) : (
                                        <div className="aspect-video flex items-center justify-center">
                                            <ImageIcon className="w-12 h-12 text-white/10" />
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Sidebar */}
                            <div className="space-y-4">
                                <div className="bg-white/[0.03] border border-white/[0.06] rounded-2xl p-5 space-y-4">
                                    <h2 className="text-lg font-bold text-white">{selectedSwipe.title || 'Untitled'}</h2>

                                    {selectedSwipe.source_url && (
                                        <a
                                            href={selectedSwipe.source_url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="flex items-center gap-2 text-xs text-amber-400 hover:text-amber-300 transition-colors"
                                        >
                                            <ExternalLink className="w-3.5 h-3.5" />
                                            <span className="truncate">{selectedSwipe.source_url}</span>
                                        </a>
                                    )}

                                    {selectedSwipe.notes && (
                                        <div className="pt-3 border-t border-white/[0.06]">
                                            <div className="flex items-center gap-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">
                                                <StickyNote className="w-3 h-3" /> Notes
                                            </div>
                                            <p className="text-sm text-slate-300 leading-relaxed whitespace-pre-wrap">{selectedSwipe.notes}</p>
                                        </div>
                                    )}

                                    {selectedSwipe.tags && selectedSwipe.tags.length > 0 && (
                                        <div className="pt-3 border-t border-white/[0.06]">
                                            <div className="flex items-center gap-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">
                                                <Tag className="w-3 h-3" /> Tags
                                            </div>
                                            <div className="flex flex-wrap gap-1.5">
                                                {selectedSwipe.tags.map(t => (
                                                    <Badge key={t} variant="secondary" className="bg-white/[0.06] text-slate-300 border-white/10 text-[10px]">
                                                        #{t}
                                                    </Badge>
                                                ))}
                                            </div>
                                        </div>
                                    )}

                                    <div className="pt-3 border-t border-white/[0.06] text-xs text-slate-600">
                                        Saved {new Date(selectedSwipe.created_at * 1000).toLocaleDateString()}
                                    </div>
                                </div>

                                <div className="flex gap-2">
                                    <Button variant="outline" size="sm" onClick={() => openEditSwipe(selectedSwipe)} className="flex-1 h-9 text-xs bg-white/[0.04] border-white/10 text-slate-300 hover:text-white hover:bg-white/[0.08] gap-1.5">
                                        <Edit3 className="w-3.5 h-3.5" /> Edit
                                    </Button>
                                    <Button variant="outline" size="sm" onClick={() => handleDeleteSwipe(selectedSwipe.id)} className="h-9 text-xs bg-white/[0.04] border-white/10 text-red-400 hover:text-red-300 hover:bg-red-500/10 gap-1.5">
                                        <Trash2 className="w-3.5 h-3.5" /> Delete
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </main>

            {/* ── New Collection Dialog ── */}
            <Dialog open={showNewCollection} onOpenChange={setShowNewCollection}>
                <DialogContent className="bg-[#1a1a1e] border-white/10 text-white max-w-sm">
                    <DialogHeader>
                        <DialogTitle className="text-white">{__('general.new_collection')}</DialogTitle>
                        <DialogDescription className="text-slate-400 text-xs">{__('general.create_a_themed_board_to_organize_your_swipes')}</DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 pt-2">
                        <div>
                            <Label className="text-xs text-slate-400 mb-1.5 block">Name</Label>
                            <Input
                                value={newCollectionName}
                                onChange={(e) => setNewCollectionName(e.target.value)}
                                placeholder={__('general.e_g_landing_pages_ad_creatives')}
                                className="bg-white/[0.04] border-white/[0.08] text-white placeholder:text-slate-600"
                                onKeyDown={(e) => e.key === 'Enter' && handleCreateCollection()}
                            />
                        </div>
                        <div>
                            <Label className="text-xs text-slate-400 mb-1.5 block">Color</Label>
                            <div className="flex gap-2 flex-wrap">
                                {COLLECTION_COLORS.map(c => (
                                    <button
                                        key={c}
                                        onClick={() => setNewCollectionColor(c)}
                                        className={`w-7 h-7 rounded-full transition-all ${newCollectionColor === c ? 'ring-2 ring-white ring-offset-2 ring-offset-[#1a1a1e] scale-110' : 'hover:scale-110'}`}
                                        style={{ backgroundColor: c }}
                                    />
                                ))}
                            </div>
                        </div>
                    </div>
                    <DialogFooter className="pt-4">
                        <Button variant="ghost" onClick={() => setShowNewCollection(false)} className="text-slate-400 hover:text-white">Cancel</Button>
                        <Button onClick={handleCreateCollection} disabled={!newCollectionName.trim()} className="bg-gradient-to-r from-amber-500 to-orange-600 text-white border-0 font-bold">Create</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* ── Add Swipe Dialog ── */}
            <Dialog open={showAddSwipe} onOpenChange={setShowAddSwipe}>
                <DialogContent className="bg-[#1a1a1e] border-white/10 text-white max-w-lg">
                    <DialogHeader>
                        <DialogTitle className="text-white">{__('general.add_swipe')}</DialogTitle>
                        <DialogDescription className="text-slate-400 text-xs">{__('general.save_a_piece_of_creative_inspiration_to_your_vault')}</DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 pt-2 max-h-[60vh] overflow-y-auto">
                        {/* Image upload */}
                        <div>
                            <Label className="text-xs text-slate-400 mb-1.5 block">Image</Label>
                            {addBase64 ? (
                                <div className="relative rounded-xl overflow-hidden border border-white/10">
                                    <img src={addBase64} alt="Preview" className="w-full max-h-48 object-contain bg-black/30" />
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => { setAddBase64(null); setAddFilename(''); }}
                                        className="absolute top-2 right-2 h-7 w-7 bg-black/60 hover:bg-black/80 text-white"
                                    >
                                        <X className="w-3.5 h-3.5" />
                                    </Button>
                                </div>
                            ) : (
                                <button
                                    onClick={handleFileSelect}
                                    className="w-full py-8 border-2 border-dashed border-white/10 rounded-xl text-center hover:border-amber-500/30 hover:bg-amber-500/5 transition-all"
                                >
                                    <Upload className="w-6 h-6 text-slate-600 mx-auto mb-2" />
                                    <p className="text-xs text-slate-500">{__('general.click_to_upload_an_image')}</p>
                                    <p className="text-[10px] text-slate-600 mt-0.5">{__('general.png_jpg_webp_or_gif')}</p>
                                </button>
                            )}
                        </div>

                        <div>
                            <Label className="text-xs text-slate-400 mb-1.5 block">Title</Label>
                            <Input
                                value={addTitle}
                                onChange={(e) => setAddTitle(e.target.value)}
                                placeholder={__('general.e_g_nike_landing_page_hero_section')}
                                className="bg-white/[0.04] border-white/[0.08] text-white placeholder:text-slate-600"
                            />
                        </div>

                        <div>
                            <Label className="text-xs text-slate-400 mb-1.5 block">Source URL (optional)</Label>
                            <Input
                                value={addUrl}
                                onChange={(e) => setAddUrl(e.target.value)}
                                placeholder={__('general.https')}
                                className="bg-white/[0.04] border-white/[0.08] text-white placeholder:text-slate-600"
                            />
                        </div>

                        <div>
                            <Label className="text-xs text-slate-400 mb-1.5 block">Notes (optional)</Label>
                            <Textarea
                                value={addNotes}
                                onChange={(e) => setAddNotes(e.target.value)}
                                placeholder={__('general.why_did_this_catch_your_eye_what_pattern_or_technique_is_used')}
                                rows={3}
                                className="bg-white/[0.04] border-white/[0.08] text-white placeholder:text-slate-600 resize-none"
                            />
                        </div>

                        <div>
                            <Label className="text-xs text-slate-400 mb-1.5 block">Tags (comma separated)</Label>
                            <Input
                                value={addTags}
                                onChange={(e) => setAddTags(e.target.value)}
                                placeholder={__('general.e_g_landing_page_hero_dark_mode')}
                                className="bg-white/[0.04] border-white/[0.08] text-white placeholder:text-slate-600"
                            />
                        </div>
                    </div>
                    <DialogFooter className="pt-4">
                        <Button variant="ghost" onClick={() => setShowAddSwipe(false)} className="text-slate-400 hover:text-white">Cancel</Button>
                        <Button onClick={handleAddSwipe} disabled={!addBase64 && !addUrl.trim() && !addTitle.trim()} className="bg-gradient-to-r from-amber-500 to-orange-600 text-white border-0 font-bold">{__('general.save_swipe')}</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* ── Edit Swipe Dialog ── */}
            <Dialog open={showEditSwipe} onOpenChange={setShowEditSwipe}>
                <DialogContent className="bg-[#1a1a1e] border-white/10 text-white max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-white">{__('general.edit_swipe')}</DialogTitle>
                        <DialogDescription className="text-slate-400 text-xs">{__('general.update_this_swipe_s_metadata')}</DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 pt-2">
                        <div>
                            <Label className="text-xs text-slate-400 mb-1.5 block">Title</Label>
                            <Input value={addTitle} onChange={(e) => setAddTitle(e.target.value)} className="bg-white/[0.04] border-white/[0.08] text-white" />
                        </div>
                        <div>
                            <Label className="text-xs text-slate-400 mb-1.5 block">Notes</Label>
                            <Textarea value={addNotes} onChange={(e) => setAddNotes(e.target.value)} rows={3} className="bg-white/[0.04] border-white/[0.08] text-white resize-none" />
                        </div>
                        <div>
                            <Label className="text-xs text-slate-400 mb-1.5 block">Tags (comma separated)</Label>
                            <Input value={addTags} onChange={(e) => setAddTags(e.target.value)} className="bg-white/[0.04] border-white/[0.08] text-white" />
                        </div>
                    </div>
                    <DialogFooter className="pt-4">
                        <Button variant="ghost" onClick={() => setShowEditSwipe(false)} className="text-slate-400 hover:text-white">Cancel</Button>
                        <Button onClick={handleUpdateSwipe} className="bg-gradient-to-r from-amber-500 to-orange-600 text-white border-0 font-bold">{__('general.save_changes')}</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* ── Move Swipe Dialog ── */}
            <Dialog open={showMoveSwipe} onOpenChange={setShowMoveSwipe}>
                <DialogContent className="bg-[#1a1a1e] border-white/10 text-white max-w-xs">
                    <DialogHeader>
                        <DialogTitle className="text-white">{__('general.move_to_collection')}</DialogTitle>
                        <DialogDescription className="text-slate-400 text-xs">{__('general.choose_a_destination_collection')}</DialogDescription>
                    </DialogHeader>
                    <div className="space-y-1.5 pt-2 max-h-[40vh] overflow-y-auto">
                        <button
                            onClick={() => selectedSwipe && handleMoveSwipe(selectedSwipe.id, null as any)}
                            className="w-full flex items-center gap-3 p-3 rounded-xl hover:bg-white/[0.06] transition-all text-left"
                        >
                            <div className="w-3 h-3 rounded-full bg-slate-500" />
                            <span className="text-sm text-slate-300">Uncategorized</span>
                        </button>
                        {collections.map(c => (
                            <button
                                key={c.id}
                                onClick={() => selectedSwipe && handleMoveSwipe(selectedSwipe.id, c.id)}
                                className="w-full flex items-center gap-3 p-3 rounded-xl hover:bg-white/[0.06] transition-all text-left"
                            >
                                <div className="w-3 h-3 rounded-full shrink-0" style={{ backgroundColor: c.color }} />
                                <span className="text-sm text-slate-300 truncate">{c.name}</span>
                                <span className="text-xs text-slate-600 ml-auto">{c.swipeCount}</span>
                            </button>
                        ))}
                    </div>
                </DialogContent>
            </Dialog>
        </div>
    );
}

// ── Sub-Components ──

function PreviewThumb({ fileUrl, callRPC }: { fileUrl: string; callRPC: any }) {
    const [src, setSrc] = useState<string | null>(null);

    useEffect(() => {
        if (!fileUrl) return;
        callRPC('get_swipe_image', { file_url: fileUrl })
            .then((res: any) => setSrc(res.base64))
            .catch(() => {});
    }, [fileUrl]);

    if (!src) return <div className="w-full h-full bg-white/[0.02]" />;
    return <img src={src} alt="" className="w-full h-full object-cover" />;
}

function SwipeCard({ swipe, onOpen, onEdit, onDelete, onMove, collectionColor }: {
    swipe: Swipe; onOpen: () => void; onEdit: () => void; onDelete: () => void; onMove: () => void; collectionColor: string;
}) {
    const [showMenu, setShowMenu] = useState(false);

    return (
        <div className="group bg-white/[0.03] border border-white/[0.06] rounded-xl overflow-hidden hover:bg-white/[0.06] hover:border-white/10 transition-all cursor-pointer relative" onClick={onOpen}>
            {/* Image */}
            <div className="aspect-square bg-black/20 relative overflow-hidden">
                {swipe.base64 ? (
                    <img src={swipe.base64} alt={swipe.title} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                ) : (
                    <div className="w-full h-full flex items-center justify-center">
                        <LinkIcon className="w-6 h-6 text-white/10" />
                    </div>
                )}

                {/* Gradient overlay */}
                <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity" />

                {/* Action menu */}
                <div className="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity z-10">
                    <Dialog>
                        <DialogTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={(e) => e.stopPropagation()}
                                className="h-7 w-7 bg-black/50 hover:bg-black/70 text-white backdrop-blur-sm rounded-lg"
                            >
                                <MoreHorizontal className="w-3.5 h-3.5" />
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="bg-[#1a1a1e] border-white/10 text-white sm:max-w-xs" onClick={(e) => e.stopPropagation()}>
                            <DialogHeader>
                                <DialogTitle className="text-white text-sm">Actions</DialogTitle>
                            </DialogHeader>
                            <div className="flex flex-col gap-2 py-2">
                                <Button variant="outline" className="justify-start bg-white/[0.04] border-white/10 text-slate-300 hover:text-white hover:bg-white/[0.08] gap-2" onClick={(e) => { e.stopPropagation(); onEdit(); }}>
                                    <Edit3 className="w-4 h-4" /> Edit
                                </Button>
                                <Button variant="outline" className="justify-start bg-white/[0.04] border-white/10 text-slate-300 hover:text-white hover:bg-white/[0.08] gap-2" onClick={(e) => { e.stopPropagation(); onMove(); }}>
                                    <MoveRight className="w-4 h-4" />{__('general.move_to_collection')}</Button>
                                <Button variant="destructive" className="justify-start gap-2" onClick={(e) => { e.stopPropagation(); onDelete(); }}>
                                    <Trash2 className="w-4 h-4" /> Delete
                                </Button>
                            </div>
                        </DialogContent>
                    </Dialog>
                </div>

                {/* Color indicator */}
                <div className="absolute bottom-0 left-0 right-0 h-0.5" style={{ backgroundColor: collectionColor }} />
            </div>

            {/* Title */}
            <div className="p-3">
                <h4 className="text-xs font-semibold text-white truncate">{swipe.title || 'Untitled'}</h4>
                {swipe.tags && swipe.tags.length > 0 && (
                    <div className="flex items-center gap-1 mt-1.5 flex-wrap">
                        {swipe.tags.slice(0, 3).map(t => (
                            <span key={t} className="text-[9px] px-1.5 py-0.5 rounded-full bg-white/[0.06] text-slate-500">#{t}</span>
                        ))}
                        {swipe.tags.length > 3 && <span className="text-[9px] text-slate-600">+{swipe.tags.length - 3}</span>}
                    </div>
                )}
            </div>
        </div>
    );
}

function SwipeRow({ swipe, onOpen, onEdit, onDelete, collectionColor }: {
    swipe: Swipe; onOpen: () => void; onEdit: () => void; onDelete: () => void; collectionColor: string;
}) {
    return (
        <div
            className="flex items-center gap-4 p-3 bg-white/[0.03] border border-white/[0.06] rounded-xl hover:bg-white/[0.06] hover:border-white/10 transition-all cursor-pointer group"
            onClick={onOpen}
        >
            <div className="w-14 h-14 rounded-lg overflow-hidden shrink-0 bg-black/20 border border-white/[0.06]">
                {swipe.base64 ? (
                    <img src={swipe.base64} alt="" className="w-full h-full object-cover" />
                ) : (
                    <div className="w-full h-full flex items-center justify-center"><ImageIcon className="w-4 h-4 text-white/10" /></div>
                )}
            </div>
            <div className="flex-1 min-w-0">
                <h4 className="text-sm font-semibold text-white truncate">{swipe.title || 'Untitled'}</h4>
                <div className="flex items-center gap-2 mt-1">
                    {swipe.tags && swipe.tags.slice(0, 3).map(t => (
                        <span key={t} className="text-[10px] px-1.5 py-0.5 rounded-full bg-white/[0.06] text-slate-500">#{t}</span>
                    ))}
                    <span className="text-[10px] text-slate-600">{new Date(swipe.created_at * 1000).toLocaleDateString()}</span>
                </div>
            </div>
            <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                <Button variant="ghost" size="icon" onClick={(e) => { e.stopPropagation(); onEdit(); }} className="h-8 w-8 text-slate-500 hover:text-white hover:bg-white/10">
                    <Edit3 className="w-3.5 h-3.5" />
                </Button>
                <Button variant="ghost" size="icon" onClick={(e) => { e.stopPropagation(); onDelete(); }} className="h-8 w-8 text-slate-500 hover:text-red-400 hover:bg-red-500/10">
                    <Trash2 className="w-3.5 h-3.5" />
                </Button>
            </div>
            <div className="w-1 h-8 rounded-full shrink-0" style={{ backgroundColor: collectionColor }} />
        </div>
    );
}

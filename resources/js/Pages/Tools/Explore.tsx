import React, { useState, useEffect, useRef } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { DesktopIcon } from '@/Components/Tools/DesktopIcon';
import { DesktopFolder } from '@/Components/Tools/DesktopFolder';
import { FolderModal } from '@/Components/Tools/FolderModal';
import { WindowModal } from '@/Components/Tools/WindowModal';
import { CheckCircle2, Shield, Maximize, Minimize, FolderPlus } from 'lucide-react';
import ApplicationLogo from '@/Components/ApplicationLogo';

import { SettingsModal } from '@/Components/Tools/SettingsModal';
import axios from 'axios';

const DEFAULT_WALLPAPER_URL = 'https://images.unsplash.com/photo-1506744626753-143d63428987?q=80&w=2560&auto=format&fit=crop';
const CELL_WIDTH = 100; // Pixels per grid column
const CELL_HEIGHT = 110; // Pixels per grid row
const MAX_ROWS = 6; // Rough estimate, could calculate dynamically

const SLUG_EMOJI_MAP: Record<string, string> = {
    'tiktok-intelligence': '📱',
    'b2b-prospector': '💼',
    'competitor-intel': '🕵️',
    'whatsapp-sender-pro': '💬',
    'viral-autopsy': '🧬',
    'hook-analyzer': '🪝',
    'format-extractor': '📋',
    'iptv-downloader': '📺',
    'screenshot-feedback': '📸',
    'opensooq': '🛍️',
    'email-sender': '✉️',
    'facebook-extractor': '👥',
    'content-researcher': '🔍',
    'data-filter': '📊',
    'facebook-publisher': '📢',
    'domain-intelligence': '🌐'
};

interface PricingPlan {
    id: string;
    name: string;
    price_monthly: number;
    price_yearly: number;
    features: string[];
    is_popular: boolean;
    yearly_savings: number;
}

interface Tool {
    id: number;
    slug: string;
    title: string;
    short_description: string;
    icon_url: string | null;
    category: string;
    category_label: string;
    supported_os: string[];
    current_version: string;
    is_featured: boolean;
    starting_price: number;
    is_free: boolean;
    pricing_plans: PricingPlan[];
}

interface Props {
    tools: { data: Tool[]; links: any[] };
    categories: Record<string, string>;
    subscribedSlugs: string[];
    hasBrowserSubscription: boolean;
    filters: { search?: string; category?: string };
    workspaceSettings: any;
}

type ItemType = 'tool' | 'folder';

interface DesktopItem {
    id: string;
    type: ItemType;
    name: string;
    toolSlug?: string;
    childrenSlugs?: string[];
    x?: number;
    y?: number;
}

export default function Explore({ tools, categories, subscribedSlugs, hasBrowserSubscription, filters, workspaceSettings }: Props) {
    const [currentTime, setCurrentTime] = useState(new Date());
    const [selectedTool, setSelectedTool] = useState<Tool | null>(null);
    const [isSubscribeModalOpen, setSubscribeModalOpen] = useState(false);
    const [isFullscreen, setIsFullscreen] = useState(false);

    // Desktop Settings Sync Wrapper
    const saveSettings = (newItems: DesktopItem[], prayerTimes: boolean, newWallpaper: string) => {
        axios.post(route('tools.workspace.settings.save'), {
            settings: {
                desktopItems: newItems,
                showPrayerTimes: prayerTimes,
                wallpaperUrl: newWallpaper
            }
        }).catch(err => console.error("Failed to save workspace settings:", err));
    };

    // Desktop State
    const [desktopItems, setDesktopItems] = useState<DesktopItem[]>(workspaceSettings?.desktopItems || []);
    const [draggedItemId, setDraggedItemId] = useState<string | null>(null);
    const [dragOverItemId, setDragOverItemId] = useState<string | null>(null);
    const [openFolderId, setOpenFolderId] = useState<string | null>(null);
    const [isStartMenuOpen, setIsStartMenuOpen] = useState(false);
    const [isSettingsModalOpen, setIsSettingsModalOpen] = useState(false);
    const [wallpaperUrl, setWallpaperUrl] = useState(workspaceSettings?.wallpaperUrl || DEFAULT_WALLPAPER_URL);
    
    // Prayer Times State
    const [showPrayerTimes, setShowPrayerTimes] = useState(() => {
        if (workspaceSettings && workspaceSettings.showPrayerTimes !== undefined) {
            return workspaceSettings.showPrayerTimes;
        }
        return true;
    });
    const [prayerTimes, setPrayerTimes] = useState<Record<string, string> | null>(null);

    const toggleFullscreen = () => {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch((err) => {
                console.error(`Error attempting to enable fullscreen: ${err.message}`);
            });
            setIsFullscreen(true);
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
                setIsFullscreen(false);
            }
        }
    };

    useEffect(() => {
        const timer = setInterval(() => setCurrentTime(new Date()), 1000);
        return () => clearInterval(timer);
    }, []);

    // Fetch Prayer Times
    useEffect(() => {
        if (!showPrayerTimes) return;
        fetch('https://api.aladhan.com/v1/timingsByCity?city=Cairo&country=Egypt&method=5')
            .then(res => res.json())
            .then(data => {
                if (data.code === 200) {
                    setPrayerTimes({
                        Fajr: data.data.timings.Fajr,
                        Dhuhr: data.data.timings.Dhuhr,
                        Asr: data.data.timings.Asr,
                        Maghrib: data.data.timings.Maghrib,
                        Isha: data.data.timings.Isha,
                    });
                }
            }).catch(err => console.error("Failed to fetch prayer times", err));
    }, [showPrayerTimes]);

    const handleHidePrayerTimes = (e: React.MouseEvent) => {
        e.preventDefault();
        setShowPrayerTimes(false);
        saveSettings(desktopItems, false, wallpaperUrl);
    };

    const togglePrayerTimes = () => {
        const newValue = !showPrayerTimes;
        setShowPrayerTimes(newValue);
        saveSettings(desktopItems, newValue, wallpaperUrl);
    };

    const handleWallpaperChange = (url: string) => {
        setWallpaperUrl(url);
        saveSettings(desktopItems, showPrayerTimes, url);
    };

    // Helper: Find first empty grid cell
    const findNextAvailableCell = (items: DesktopItem[]): { x: number, y: number } => {
        let x = 0;
        let y = 0;
        while (true) {
            if (!items.some(i => i.x === x && i.y === y)) {
                return { x, y };
            }
            y++;
            if (y >= MAX_ROWS) {
                y = 0;
                x++;
            }
        }
    };

    // Initialize layout adding missing tools
    useEffect(() => {
        const initialItems = workspaceSettings?.desktopItems || [];

        const allToolSlugsInLayout = new Set<string>();
        initialItems.forEach((item: DesktopItem) => {
            if (item.type === 'tool' && item.toolSlug) allToolSlugsInLayout.add(item.toolSlug);
            if (item.type === 'folder' && item.childrenSlugs) {
                item.childrenSlugs.forEach(slug => allToolSlugsInLayout.add(slug));
            }
        });

        const missingTools = tools.data.filter(t => !allToolSlugsInLayout.has(t.slug));
        
        // Sequentially assign coordinates to missing tools
        if (missingTools.length > 0) {
            const updatedItems = [...initialItems];
            missingTools.forEach(t => {
                const { x, y } = findNextAvailableCell(updatedItems);
                updatedItems.push({
                    id: `tool-${t.slug}`,
                    type: 'tool' as ItemType,
                    name: t.title,
                    toolSlug: t.slug,
                    x,
                    y
                });
            });
            setDesktopItems(updatedItems);
        }
    }, [tools.data]);

    // Persist layout on change
    const isFirstRender = useRef(true);
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }
        if (desktopItems.length > 0) {
            saveSettings(desktopItems, showPrayerTimes, wallpaperUrl);
        }
    }, [desktopItems]);

    const handleCreateFolder = () => {
        const { x, y } = findNextAvailableCell(desktopItems);
        const newFolder: DesktopItem = {
            id: `folder-${Date.now()}`,
            type: 'folder',
            name: 'New Folder',
            childrenSlugs: [],
            x,
            y
        };
        setDesktopItems([...desktopItems, newFolder]);
    };

    const handleToolClick = (toolSlug: string) => {
        const tool = tools.data.find(t => t.slug === toolSlug);
        if (!tool) return;

        const isOwned = subscribedSlugs.includes(tool.slug);
        if (isOwned) {
            router.visit(route('tools.run', tool.slug));
        } else {
            setSelectedTool(tool);
            setSubscribeModalOpen(true);
        }
    };

    const handleSubscribeAction = () => {
        if (!selectedTool) return;
        const plan = selectedTool.pricing_plans[0];
        if (plan) {
            router.visit(route('tools.checkout', { slug: selectedTool.slug, planId: plan.id }));
        } else {
            router.visit(route('tools.show', selectedTool.slug));
        }
    };

    // Drag and Drop Logic
    const handleDragStart = (e: React.DragEvent, id: string, sourceFolderId?: string) => {
        e.stopPropagation();
        const payload = sourceFolderId ? `fromFolder:${sourceFolderId}:${id}` : id;
        e.dataTransfer.setData('text/plain', payload);
        e.dataTransfer.effectAllowed = 'move';
        setDraggedItemId(id);
    };

    const handleDragOver = (e: React.DragEvent, id: string) => {
        e.preventDefault();
        e.stopPropagation();
        if (draggedItemId && draggedItemId !== id) {
            setDragOverItemId(id);
        }
    };

    const handleDragLeave = (e: React.DragEvent) => {
        e.preventDefault();
        setDragOverItemId(null);
    };

    const handleDropOnItem = (e: React.DragEvent, targetId: string) => {
        e.preventDefault();
        e.stopPropagation();
        setDragOverItemId(null);
        
        const payload = e.dataTransfer.getData('text/plain');
        if (!payload || payload === targetId) return;

        if (payload.startsWith('fromFolder:')) {
            const [, folderId, toolSlug] = payload.split(':');
            setDesktopItems(prevItems => {
                const items = [...prevItems];
                const sourceFolder = items.find(i => i.id === folderId);
                const targetIndex = items.findIndex(i => i.id === targetId);
                const targetItem = items[targetIndex];

                if (sourceFolder && sourceFolder.childrenSlugs) {
                    sourceFolder.childrenSlugs = sourceFolder.childrenSlugs.filter(s => s !== toolSlug);
                    
                    if (targetItem && targetItem.type === 'folder') {
                        targetItem.childrenSlugs = [...(targetItem.childrenSlugs || []), toolSlug];
                    } else if (targetItem && targetItem.type === 'tool') {
                        const newFolder: DesktopItem = {
                            id: `folder-${Date.now()}`,
                            type: 'folder',
                            name: 'Folder',
                            childrenSlugs: [targetItem.toolSlug!, toolSlug],
                            x: targetItem.x,
                            y: targetItem.y
                        };
                        items.splice(targetIndex, 1, newFolder);
                    }
                }
                return items;
            });
            setDraggedItemId(null);
            return;
        }

        setDesktopItems(prevItems => {
            const items = [...prevItems];
            const sourceIndex = items.findIndex(i => i.id === payload);
            const targetIndex = items.findIndex(i => i.id === targetId);

            if (sourceIndex === -1 || targetIndex === -1) return prevItems;

            const sourceItem = items[sourceIndex];
            const targetItem = items[targetIndex];

            if (sourceItem.type === 'tool' && targetItem.type === 'tool') {
                const newFolder: DesktopItem = {
                    id: `folder-${Date.now()}`,
                    type: 'folder',
                    name: 'Folder',
                    childrenSlugs: [targetItem.toolSlug!, sourceItem.toolSlug!],
                    x: targetItem.x,
                    y: targetItem.y
                };
                items.splice(Math.max(sourceIndex, targetIndex), 1);
                items.splice(Math.min(sourceIndex, targetIndex), 1, newFolder);
            } else if (sourceItem.type === 'tool' && targetItem.type === 'folder') {
                items.splice(sourceIndex, 1);
                targetItem.childrenSlugs = [...(targetItem.childrenSlugs || []), sourceItem.toolSlug!];
            } else {
                // Just swap positions
                const tempX = targetItem.x;
                const tempY = targetItem.y;
                targetItem.x = sourceItem.x;
                targetItem.y = sourceItem.y;
                sourceItem.x = tempX;
                sourceItem.y = tempY;
            }

            return items;
        });
        setDraggedItemId(null);
    };

    const handleDesktopDrop = (e: React.DragEvent) => {
        e.preventDefault();
        setDragOverItemId(null);

        const container = e.currentTarget as HTMLElement;
        const rect = container.getBoundingClientRect();
        
        // Calculate Grid Position
        const dropX = e.clientX - rect.left;
        const dropY = e.clientY - rect.top;
        const gridX = Math.max(0, Math.floor(dropX / CELL_WIDTH));
        const gridY = Math.max(0, Math.floor(dropY / CELL_HEIGHT));

        const payload = e.dataTransfer.getData('text/plain');
        
        if (payload.startsWith('fromFolder:')) {
            const [, folderId, toolSlug] = payload.split(':');
            setDesktopItems(prevItems => {
                const items = [...prevItems];
                const sourceFolder = items.find(i => i.id === folderId);
                
                if (sourceFolder && sourceFolder.childrenSlugs) {
                    sourceFolder.childrenSlugs = sourceFolder.childrenSlugs.filter(s => s !== toolSlug);
                    const toolData = tools.data.find(t => t.slug === toolSlug);
                    
                    // Collision check
                    let finalX = gridX, finalY = gridY;
                    const occupantIndex = items.findIndex(i => i.x === finalX && i.y === finalY);
                    if (occupantIndex > -1) {
                        const { x, y } = findNextAvailableCell(items);
                        finalX = x;
                        finalY = y;
                    }

                    if (toolData) {
                        items.push({
                            id: `tool-${toolSlug}`,
                            type: 'tool',
                            name: toolData.title,
                            toolSlug: toolSlug,
                            x: finalX,
                            y: finalY
                        });
                    }
                }
                return items;
            });
        } else {
            // Move item to grid position
            setDesktopItems(prevItems => {
                const items = [...prevItems];
                const itemIndex = items.findIndex(i => i.id === payload);
                if (itemIndex > -1) {
                    const occupantIndex = items.findIndex(i => i.x === gridX && i.y === gridY && i.id !== payload);
                    if (occupantIndex > -1) {
                        // Swap with occupant
                        const occupant = items[occupantIndex];
                        occupant.x = items[itemIndex].x;
                        occupant.y = items[itemIndex].y;
                    }
                    items[itemIndex] = { ...items[itemIndex], x: gridX, y: gridY };
                }
                return items;
            });
        }
        setDraggedItemId(null);
    };

    const handleRenameFolder = (folderId: string, newName: string) => {
        setDesktopItems(prev => prev.map(item => item.id === folderId ? { ...item, name: newName } : item));
    };

    const formatTime = (date: Date) => date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const formatDate = (date: Date) => date.toLocaleDateString([], { month: 'numeric', day: 'numeric', year: 'numeric' });

    const openFolder = desktopItems.find(i => i.id === openFolderId);

    return (
        <div 
            className="h-screen w-screen overflow-hidden bg-slate-900 bg-cover bg-center flex flex-col font-['Inter',sans-serif]"
            style={{ backgroundImage: `url(${wallpaperUrl})` }}
            onClick={() => setIsStartMenuOpen(false)}
        >
            <Head title="Tools Workspace" />

            {/* Desktop Area */}
            <div 
                className="flex-1 p-4 md:p-6 overflow-hidden relative"
                onDragOver={(e) => e.preventDefault()}
                onDrop={handleDesktopDrop}
            >
                {desktopItems.map((item) => {
                    const style: React.CSSProperties = {
                        position: 'absolute',
                        left: (item.x || 0) * CELL_WIDTH,
                        top: (item.y || 0) * CELL_HEIGHT,
                    };

                    if (item.type === 'tool' && item.toolSlug) {
                        const toolData = tools.data.find(t => t.slug === item.toolSlug);
                        if (!toolData) return null;
                        return (
                            <DesktopIcon
                                key={item.id}
                                id={item.id}
                                title={item.name}
                                iconUrl={toolData.icon_url}
                                emojiFallback={SLUG_EMOJI_MAP[toolData.slug] ?? '📦'}
                                isOwned={subscribedSlugs.includes(toolData.slug)}
                                isFeatured={toolData.is_featured}
                                onClick={() => handleToolClick(toolData.slug)}
                                draggable
                                onDragStart={handleDragStart}
                                onDragOver={handleDragOver}
                                onDragLeave={handleDragLeave}
                                onDrop={handleDropOnItem}
                                isDragOver={dragOverItemId === item.id}
                                style={style}
                            />
                        );
                    } else if (item.type === 'folder') {
                        const childrenTools = (item.childrenSlugs || []).map(slug => {
                            const t = tools.data.find(t => t.slug === slug);
                            return {
                                iconUrl: t?.icon_url || null,
                                emoji: SLUG_EMOJI_MAP[slug] ?? '📦'
                            };
                        });
                        return (
                            <DesktopFolder
                                key={item.id}
                                id={item.id}
                                name={item.name}
                                childrenTools={childrenTools}
                                onClick={() => setOpenFolderId(item.id)}
                                onDragStart={handleDragStart}
                                onDragOver={handleDragOver}
                                onDragLeave={handleDragLeave}
                                onDrop={handleDropOnItem}
                                isDragOver={dragOverItemId === item.id}
                                style={style}
                            />
                        );
                    }
                    return null;
                })}
            </div>

            {/* Taskbar */}
            <div className="h-12 bg-[#1c1c1c]/90 backdrop-blur-md border-t border-white/10 flex items-center justify-between px-2 shrink-0 select-none z-40 relative">
                
                {/* Start Menu Popup */}
                {isStartMenuOpen && (
                    <div 
                        className="absolute bottom-14 left-2 w-80 bg-[#1c1c1c]/95 backdrop-blur-xl border border-white/10 rounded-xl shadow-2xl p-4 flex flex-col gap-4 animate-in fade-in slide-in-from-bottom-4 duration-200"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div className="flex items-center gap-3 pb-4 border-b border-white/10">
                            <div className="w-10 h-10 rounded-full bg-blue-500/20 flex items-center justify-center">
                                <ApplicationLogo className="w-6 h-6 text-blue-400 fill-current" />
                            </div>
                            <div>
                                <h3 className="text-white font-medium">Musoftware</h3>
                                <p className="text-xs text-slate-400">Tools Workspace</p>
                            </div>
                        </div>
                        
                        <div className="flex flex-col gap-1">
                            <Link 
                                href={route('dashboard')} 
                                className="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-white/10 text-slate-200 transition-colors"
                            >
                                <span className="text-lg">📊</span>
                                <span className="text-sm font-medium">Main Dashboard</span>
                            </Link>
                            <Link 
                                href={route('tools.downloads')} 
                                className="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-white/10 text-slate-200 transition-colors"
                            >
                                <span className="text-lg">⬇️</span>
                                <span className="text-sm font-medium">Downloads & History</span>
                            </Link>
                            <Link 
                                href="/" 
                                className="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-white/10 text-slate-200 transition-colors"
                            >
                                <span className="text-lg">🏠</span>
                                <span className="text-sm font-medium">Back to Website</span>
                            </Link>
                            <div className="h-px bg-white/10 my-1 mx-2"></div>
                            <button 
                                onClick={togglePrayerTimes}
                                className="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-white/10 text-slate-200 transition-colors text-left"
                            >
                                <span className="text-lg">🕌</span>
                                <span className="text-sm font-medium">{showPrayerTimes ? 'Hide Prayer Times' : 'Show Prayer Times'}</span>
                            </button>
                            <button 
                                onClick={() => setIsSettingsModalOpen(true)}
                                className="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-white/10 text-slate-200 transition-colors text-left mt-1"
                            >
                                <span className="text-lg">⚙️</span>
                                <span className="text-sm font-medium">Desktop Settings</span>
                            </button>
                        </div>
                    </div>
                )}

                <div className="flex items-center h-full">
                    {/* Start Button */}
                    <button 
                        onClick={() => setIsStartMenuOpen(!isStartMenuOpen)}
                        className={`h-full px-3 flex items-center justify-center hover:bg-white/10 transition-colors group ${isStartMenuOpen ? 'bg-white/10' : ''}`}
                    >
                        <div className="w-6 h-6 rounded bg-blue-500 flex items-center justify-center group-hover:bg-blue-400 transition-colors">
                            <ApplicationLogo className="w-3.5 h-3.5 text-white fill-current" />
                        </div>
                    </button>
                    
                    <div className="flex items-center h-full ml-2 space-x-1 border-r border-white/10 pr-2 mr-2">
                        <button 
                            onClick={handleCreateFolder}
                            className="h-10 px-3 flex items-center justify-center text-slate-300 hover:bg-white/10 rounded transition-colors text-xs font-medium gap-2"
                        >
                            <FolderPlus className="w-4 h-4 text-yellow-400" />
                            New Folder
                        </button>
                    </div>

                    {/* Running Apps */}
                    <div className="flex items-center h-full space-x-1">
                        <Link href={route('dashboard')} className="h-10 px-3 flex items-center justify-center text-slate-300 hover:bg-white/10 rounded transition-colors text-xs font-medium">
                            Dashboard
                        </Link>
                        <Link href={route('tools.downloads')} className="h-10 px-3 flex items-center justify-center text-slate-300 hover:bg-white/10 rounded transition-colors text-xs font-medium">
                            Downloads
                        </Link>
                    </div>
                </div>

                <div className="flex items-center h-full text-white text-xs">
                    {showPrayerTimes && prayerTimes && (
                        <div 
                            onContextMenu={handleHidePrayerTimes}
                            className="flex items-center gap-3 px-3 border-r border-white/10 mr-2 h-full cursor-pointer hover:bg-white/5 transition-colors"
                            title="Right-click to hide"
                        >
                            <div className="flex flex-col items-center justify-center">
                                <span className="text-[9px] text-slate-400">الفجر</span>
                                <span className="font-semibold text-emerald-400">{prayerTimes.Fajr}</span>
                            </div>
                            <div className="flex flex-col items-center justify-center">
                                <span className="text-[9px] text-slate-400">الظهر</span>
                                <span className="font-semibold">{prayerTimes.Dhuhr}</span>
                            </div>
                            <div className="flex flex-col items-center justify-center">
                                <span className="text-[9px] text-slate-400">العصر</span>
                                <span className="font-semibold">{prayerTimes.Asr}</span>
                            </div>
                            <div className="flex flex-col items-center justify-center">
                                <span className="text-[9px] text-slate-400">المغرب</span>
                                <span className="font-semibold text-amber-400">{prayerTimes.Maghrib}</span>
                            </div>
                            <div className="flex flex-col items-center justify-center">
                                <span className="text-[9px] text-slate-400">العشاء</span>
                                <span className="font-semibold">{prayerTimes.Isha}</span>
                            </div>
                        </div>
                    )}
                    
                    <button 
                        onClick={toggleFullscreen}
                        className="h-full px-3 flex items-center justify-center hover:bg-white/10 transition-colors text-slate-300"
                        title="Toggle Fullscreen"
                    >
                        {isFullscreen ? <Minimize className="w-4 h-4" /> : <Maximize className="w-4 h-4" />}
                    </button>
                    <div className="flex flex-col items-end justify-center px-3 hover:bg-white/10 h-full transition-colors cursor-default">
                        <span>{formatTime(currentTime)}</span>
                        <span>{formatDate(currentTime)}</span>
                    </div>
                </div>
            </div>

            {/* Folder Modal */}
            <FolderModal
                isOpen={!!openFolderId}
                onClose={() => setOpenFolderId(null)}
                folderName={openFolder?.name || ''}
                onRename={(newName) => {
                    if (openFolderId) handleRenameFolder(openFolderId, newName);
                }}
            >
                {openFolder?.childrenSlugs?.map(slug => {
                    const toolData = tools.data.find(t => t.slug === slug);
                    if (!toolData) return null;
                    return (
                        <DesktopIcon
                            key={slug}
                            id={slug}
                            title={toolData.title}
                            iconUrl={toolData.icon_url}
                            emojiFallback={SLUG_EMOJI_MAP[toolData.slug] ?? '📦'}
                            isOwned={subscribedSlugs.includes(toolData.slug)}
                            isFeatured={toolData.is_featured}
                            onClick={() => handleToolClick(toolData.slug)}
                            draggable
                            onDragStart={(e) => handleDragStart(e, slug, openFolder.id)}
                        />
                    );
                })}
                {openFolder?.childrenSlugs?.length === 0 && (
                    <div className="w-full h-full flex items-center justify-center text-white/50 text-sm">
                        Empty Folder
                    </div>
                )}
            </FolderModal>

            {/* Subscribe Modal / App Window */}
            <WindowModal
                isOpen={isSubscribeModalOpen}
                onClose={() => {
                    setSubscribeModalOpen(false);
                    setSelectedTool(null);
                }}
                title={selectedTool ? `Subscribe to ${selectedTool.title}` : 'Subscribe'}
                icon={
                    selectedTool?.icon_url ? 
                    <img src={selectedTool.icon_url} alt="" className="w-full h-full object-contain" /> : 
                    <span className="text-[10px]">{selectedTool ? SLUG_EMOJI_MAP[selectedTool.slug] || '📦' : '📦'}</span>
                }
                width="w-[500px]"
            >
                {selectedTool && (
                    <div className="flex flex-col h-full bg-slate-50">
                        {/* Header Info */}
                        <div className="p-6 bg-white border-b border-slate-200 flex gap-4 items-start">
                            <div className="w-16 h-16 rounded-xl bg-slate-100 flex items-center justify-center flex-shrink-0 border border-slate-200">
                                {selectedTool.icon_url ? (
                                    <img src={selectedTool.icon_url} alt={selectedTool.title} className="w-10 h-10 object-contain" />
                                ) : (
                                    <span className="text-3xl">{SLUG_EMOJI_MAP[selectedTool.slug] || '📦'}</span>
                                )}
                            </div>
                            <div>
                                <h2 className="text-xl font-bold text-slate-900">{selectedTool.title}</h2>
                                <p className="text-sm text-slate-500 mt-1">{selectedTool.short_description}</p>
                                <div className="mt-2 flex items-center gap-2">
                                    <span className="px-2 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded text-xs font-medium capitalize">
                                        {selectedTool.category_label}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Plan Info */}
                        <div className="p-6 space-y-4 flex-1">
                            {selectedTool.pricing_plans && selectedTool.pricing_plans.length > 0 ? (
                                <div className="bg-white rounded-xl border border-slate-200 overflow-hidden">
                                    <div className="bg-slate-900 px-4 py-3 flex items-center justify-between">
                                        <span className="text-white font-semibold">{selectedTool.pricing_plans[0].name} Plan</span>
                                        <span className="text-white text-lg font-bold">
                                            {selectedTool.pricing_plans[0].price_monthly <= 0 
                                                ? 'Free' 
                                                : `$${selectedTool.pricing_plans[0].price_monthly}/mo`}
                                        </span>
                                    </div>
                                    <div className="p-4">
                                        <ul className="space-y-2">
                                            {selectedTool.pricing_plans[0].features.slice(0, 4).map((f, i) => (
                                                <li key={i} className="flex items-start gap-2 text-sm text-slate-600">
                                                    <CheckCircle2 className="h-4 w-4 text-emerald-500 flex-shrink-0 mt-0.5" />
                                                    {f}
                                                </li>
                                            ))}
                                            {selectedTool.pricing_plans[0].features.length > 4 && (
                                                <li className="text-xs text-slate-400 pl-6">
                                                    + {selectedTool.pricing_plans[0].features.length - 4} more features
                                                </li>
                                            )}
                                        </ul>
                                    </div>
                                </div>
                            ) : (
                                <div className="bg-white rounded-xl border border-slate-200 p-4 flex items-center justify-between">
                                    <span className="font-semibold text-slate-900">Starting at</span>
                                    <span className="text-lg font-bold text-slate-900">
                                        {selectedTool.starting_price <= 0 ? 'Free' : `$${selectedTool.starting_price}/mo`}
                                    </span>
                                </div>
                            )}
                            
                            <div className="pt-2">
                                <Button 
                                    onClick={handleSubscribeAction}
                                    className="w-full h-11 bg-slate-900 hover:bg-slate-800 text-white"
                                >
                                    Proceed to Subscribe
                                </Button>
                            </div>

                            <p className="text-xs text-slate-400 text-center flex items-center justify-center gap-1 mt-2">
                                <Shield className="h-3 w-3" />
                                Safe & Secure Checkout
                            </p>
                        </div>
                    </div>
                )}
            </WindowModal>

            <SettingsModal 
                isOpen={isSettingsModalOpen}
                onClose={() => setIsSettingsModalOpen(false)}
                showPrayerTimes={showPrayerTimes}
                onTogglePrayerTimes={togglePrayerTimes}
                wallpaperUrl={wallpaperUrl}
                onWallpaperChange={handleWallpaperChange}
            />
        </div>
    );
}

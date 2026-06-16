import React, { useState, useEffect, useRef } from 'react';
import { Head, router, Link, usePage } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { DesktopIcon } from '@/Components/Tools/DesktopIcon';
import { DesktopFolder } from '@/Components/Tools/DesktopFolder';
import { FolderModal } from '@/Components/Tools/FolderModal';
import { WindowModal } from '@/Components/Tools/WindowModal';
import { CheckCircle2, Shield, Maximize, Minimize, FolderPlus } from 'lucide-react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { formatMoney } from '@/lib/utils';

import { SettingsModal } from '@/Components/Tools/SettingsModal';
import { ContextMenu, ContextMenuState } from '@/Components/Tools/ContextMenu';
import axios from 'axios';
import { __ } from '@/lib/i18n';

const DEFAULT_WALLPAPER_URL = 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?q=80&w=2560&auto=format&fit=crop';
const CELL_WIDTH = 100; // Pixels per grid column
const CELL_HEIGHT = 110; // Pixels per grid row
const MAX_ROWS = 6; // Rough estimate, could calculate dynamically

const formatTime = (date: Date) => date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
const formatPrayerTime = (timeStr: string) => {
    if (!timeStr) return '';
    const [h, m] = timeStr.split(':').map(Number);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const hours12 = h % 12 || 12;
    return `${hours12}:${m.toString().padStart(2, '0')} ${ampm}`;
};
const formatDate = (date: Date) => date.toISOString().split('T')[0];

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
    'domain-intelligence': '🌐',
    'domain-prospector': '🏷️',
    'tiktok-booster': '🚀',
    'telegram-downloader': '⬇️',
    'olx-b2c-finder': '🏪',
    'google-maps': '📍',
    'snap-downloader': '👻',
    'haraj': '🏬',
    'wa-ai-agent': '🤖',
    'wa-funnel-engine': '🔄',
    'wa-warmup': '🔥',
    'fb-inbox-sender': '📨',
    'instagram': '📷',
    'propertyfinder': '🏠',
    'telegram-tool': '✈️',
    'article-maker': '✍️',
    'competitor-tracker': '🎯',
    'ad-library-monitor': '📡',
    'ugc-creators-search': '🧑‍🎨',
    'swipe-vault': '🗂️',
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
    businessCurrency: string;
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

export default function Explore({ tools, categories, subscribedSlugs, hasBrowserSubscription, filters, workspaceSettings, businessCurrency }: Props) {
    const [currentTime, setCurrentTime] = useState(new Date());
    const [selectedTool, setSelectedTool] = useState<Tool | null>(null);
    const [isSubscribeModalOpen, setSubscribeModalOpen] = useState(false);
    const [isFullscreen, setIsFullscreen] = useState(false);
    const [isMobile, setIsMobile] = useState(false);

    useEffect(() => {
        const checkMobile = () => setIsMobile(window.innerWidth < 768);
        checkMobile();
        window.addEventListener('resize', checkMobile);
        return () => window.removeEventListener('resize', checkMobile);
    }, []);

    const [prayerCity, setPrayerCity] = useState(workspaceSettings?.prayerCity || 'Cairo');
    const [prayerCountry, setPrayerCountry] = useState(workspaceSettings?.prayerCountry || 'Egypt');
    const [prayerMethod, setPrayerMethod] = useState(workspaceSettings?.prayerMethod || '5');

    const saveSettings = (
        newItems: DesktopItem[], 
        prayerTimes: boolean, 
        newWallpaper: string,
        city = prayerCity,
        country = prayerCountry,
        method = prayerMethod,
        oneClick = openWithOneClick,
        host = runtimeHost,
        windows = activeWindows
    ) => {
        axios.post(route('tools.workspace.settings.save'), {
            settings: {
                desktopItems: newItems,
                showPrayerTimes: prayerTimes,
                wallpaperUrl: newWallpaper,
                prayerCity: city,
                prayerCountry: country,
                prayerMethod: method,
                openWithOneClick: oneClick,
                runtimeHost: host,
                activeWindows: windows
            }
        }).catch(err => console.error("Failed to save workspace settings:", err));
    };

    const handlePrayerSettingsChange = (city: string, country: string, method: string) => {
        setPrayerCity(city);
        setPrayerCountry(country);
        setPrayerMethod(method);
        saveSettings(desktopItems, showPrayerTimes, wallpaperUrl, city, country, method);
    };

    const handleToggleOneClick = () => {
        const newValue = !openWithOneClick;
        setOpenWithOneClick(newValue);
        saveSettings(desktopItems, showPrayerTimes, wallpaperUrl, prayerCity, prayerCountry, prayerMethod, newValue);
    };

    // Desktop State
    const [desktopItems, setDesktopItems] = useState<DesktopItem[]>(workspaceSettings?.desktopItems || []);
    const [draggedItemId, setDraggedItemId] = useState<string | null>(null);
    const [dragOverItemId, setDragOverItemId] = useState<string | null>(null);
    const [openFolderId, setOpenFolderId] = useState<string | null>(null);
    const [isStartMenuOpen, setIsStartMenuOpen] = useState(false);
    const [isSettingsModalOpen, setIsSettingsModalOpen] = useState(false);
    const [settingsZIndex, setSettingsZIndex] = useState(10);
    const [wallpaperUrl, setWallpaperUrl] = useState(workspaceSettings?.wallpaperUrl || DEFAULT_WALLPAPER_URL);
    const [openWithOneClick, setOpenWithOneClick] = useState(workspaceSettings?.openWithOneClick || false);
    const [runtimeHost, setRuntimeHost] = useState(workspaceSettings?.runtimeHost || '127.0.0.1');
    const touchTimer = useRef<NodeJS.Timeout | null>(null);
    
    // Window Manager State
    const [activeWindows, setActiveWindows] = useState<{ id: string; slug: string; title: string; iconUrl?: string | null; isMinimized: boolean; isMaximized: boolean; zIndex: number }[]>(workspaceSettings?.activeWindows || []);
    const [maxZIndex, setMaxZIndex] = useState(() => {
        const windows = workspaceSettings?.activeWindows || [];
        if (windows.length === 0) return 10;
        return Math.max(10, ...windows.map((w: any) => w.zIndex || 10));
    });
    
    // Advanced Desktop Features State
    const [selectedItemIds, setSelectedItemIds] = useState<string[]>([]);
    const [contextMenu, setContextMenu] = useState<ContextMenuState | null>(null);
    const [editingItemId, setEditingItemId] = useState<string | null>(null);
    const [clipboard, setClipboard] = useState<DesktopItem[]>([]);

    // Lasso Selection State
    const [lassoStart, setLassoStart] = useState<{ x: number, y: number } | null>(null);
    const [lassoEnd, setLassoEnd] = useState<{ x: number, y: number } | null>(null);
    const [isLassoing, setIsLassoing] = useState(false);
    const wasLassoingRef = useRef(false);

    // Prayer Times State
    const [showPrayerTimes, setShowPrayerTimes] = useState(() => {
        if (workspaceSettings && workspaceSettings.showPrayerTimes !== undefined) {
            return workspaceSettings.showPrayerTimes;
        }
        return true;
    });
    const [prayerTimes, setPrayerTimes] = useState<Record<string, string> | null>(null);
    const [lastFetchedDate, setLastFetchedDate] = useState<string | null>(null);

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

    const fetchPrayerTimes = () => {
        if (!prayerCity || !prayerCountry) return;
        fetch(`https://api.aladhan.com/v1/timingsByCity?city=${encodeURIComponent(prayerCity)}&country=${encodeURIComponent(prayerCountry)}&method=${prayerMethod}`)
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
    };

    useEffect(() => {
        if (!showPrayerTimes) return;
        const checkMidnight = () => {
            const dateStr = formatDate(new Date());
            if (dateStr !== lastFetchedDate) {
                fetchPrayerTimes();
                setLastFetchedDate(dateStr);
            }
        };

        checkMidnight();
        const interval = setInterval(checkMidnight, 60000);
        return () => clearInterval(interval);
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [showPrayerTimes, lastFetchedDate, prayerCity, prayerCountry, prayerMethod]);

    useEffect(() => {
        if (showPrayerTimes) {
            fetchPrayerTimes();
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [prayerCity, prayerCountry, prayerMethod]);

    // Fetch Bing image if using default Unsplash wallpaper
    useEffect(() => {
        if (wallpaperUrl === DEFAULT_WALLPAPER_URL) {
            fetch('/api/bing-daily-images')
                .then(res => res.json())
                .then(data => {
                    if (data && (data as any).length > 0) {
                        setWallpaperUrl(data[0]);
                    }
                })
                .catch(err => console.error("Failed to fetch bing images", err));
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    // Next Prayer Logic
    const nextPrayer = React.useMemo(() => {
        if (!prayerTimes) return null;
        const currentHours = currentTime.getHours();
        const currentMinutes = currentTime.getMinutes();
        const currentTotalMinutes = currentHours * 60 + currentMinutes;

        const parseTime = (timeStr: string) => {
            const [h, m] = timeStr.split(':').map(Number);
            return h * 60 + m;
        };

        const prayers = [
            { name: 'الفجر', time: prayerTimes.Fajr },
            { name: 'الظهر', time: prayerTimes.Dhuhr },
            { name: 'العصر', time: prayerTimes.Asr },
            { name: 'المغرب', time: prayerTimes.Maghrib },
            { name: 'العشاء', time: prayerTimes.Isha }
        ];

        for (const prayer of prayers) {
            if (parseTime(prayer.time) > currentTotalMinutes) {
                return prayer;
            }
        }
        return { name: 'الفجر (غداً)', time: prayerTimes.Fajr };
    }, [prayerTimes, currentTime]);

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

    // Keyboard Shortcuts
    useEffect(() => {
        const handleKeyDown = (e: KeyboardEvent) => {
            if (isStartMenuOpen || isSubscribeModalOpen || selectedTool || isSettingsModalOpen || editingItemId) return;

            // Delete
            if (e.key === 'Delete' && selectedItemIds.length > 0) {
                const newItems = desktopItems.filter(item => !selectedItemIds.includes(item.id));
                setDesktopItems(newItems);
                setSelectedItemIds([]);
            }

            // Select All (Ctrl+A)
            if (e.ctrlKey && e.key === 'a') {
                e.preventDefault();
                setSelectedItemIds(desktopItems.map(i => i.id));
            }

            // Copy (Ctrl+C)
            if (e.ctrlKey && e.key === 'c' && selectedItemIds.length > 0) {
                const copied = desktopItems.filter(i => selectedItemIds.includes(i.id));
                setClipboard(copied);
            }

            // Paste (Ctrl+V)
            if (e.ctrlKey && e.key === 'v' && clipboard.length > 0) {
                const newItems = [...desktopItems];
                const pastedIds: string[] = [];
                clipboard.forEach(item => {
                    const { x, y } = findNextAvailableCell(newItems);
                    const newItem = { ...item, id: `${item.type}-${Date.now()}-${Math.random()}`, x, y };
                    if (item.type === 'tool' && item.name) newItem.name = item.name + ' - Copy';
                    if (item.type === 'folder' && item.name) newItem.name = item.name + ' - Copy';
                    newItems.push(newItem);
                    pastedIds.push(newItem.id);
                });
                setDesktopItems(newItems);
                setSelectedItemIds(pastedIds);
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [desktopItems, selectedItemIds, clipboard, isStartMenuOpen, isSubscribeModalOpen, selectedTool, isSettingsModalOpen, editingItemId]);

    // Context Menu Logic
    const handleDesktopContextMenu = (e: React.MouseEvent) => {
        e.preventDefault();
        setContextMenu({
            x: e.clientX,
            y: e.clientY,
            type: 'desktop',
            targetId: null
        });
    };

    const handleDesktopTouchStart = (e: React.TouchEvent) => {
        if (touchTimer.current) clearTimeout(touchTimer.current);
        const touch = e.touches[0];
        touchTimer.current = setTimeout(() => {
            setContextMenu({
                x: touch.clientX,
                y: touch.clientY,
                type: 'desktop',
                targetId: null
            });
        }, 500);
    };

    const handleDesktopTouchMove = () => {
        if (touchTimer.current) clearTimeout(touchTimer.current);
    };

    const handleDesktopTouchEnd = () => {
        if (touchTimer.current) clearTimeout(touchTimer.current);
    };

    // Lasso Logic
    const handleDesktopMouseDown = (e: React.MouseEvent) => {
        if (e.button !== 0 || isMobile) return;
        if ((e.target as HTMLElement).closest('.desktop-item-container')) return;
        
        const container = e.currentTarget as HTMLElement;
        const rect = container.getBoundingClientRect();
        const startX = e.clientX - rect.left;
        const startY = e.clientY - rect.top;
        
        setLassoStart({ x: startX, y: startY });
        setLassoEnd({ x: startX, y: startY });
        setIsLassoing(true);
        
        if (!e.ctrlKey) {
            setSelectedItemIds([]);
        }
    };

    const handleDesktopMouseMove = (e: React.MouseEvent) => {
        if (!isLassoing || !lassoStart) return;
        
        const container = e.currentTarget as HTMLElement;
        const rect = container.getBoundingClientRect();
        const currentX = Math.max(0, Math.min(e.clientX - rect.left, rect.width));
        const currentY = Math.max(0, Math.min(e.clientY - rect.top, rect.height));
        
        setLassoEnd({ x: currentX, y: currentY });
        wasLassoingRef.current = true;
        
        const minX = Math.min(lassoStart.x, currentX);
        const maxX = Math.max(lassoStart.x, currentX);
        const minY = Math.min(lassoStart.y, currentY);
        const maxY = Math.max(lassoStart.y, currentY);
        
        const newSelectedIds = desktopItems.filter(item => {
            const itemLeft = (item.x || 0) * CELL_WIDTH;
            const itemTop = (item.y || 0) * CELL_HEIGHT;
            const itemRight = itemLeft + CELL_WIDTH;
            const itemBottom = itemTop + CELL_HEIGHT;
            
            return !(itemRight < minX || itemLeft > maxX || itemBottom < minY || itemTop > maxY);
        }).map(i => i.id);
        
        // In a real app we'd merge if ctrlKey is held, but this is simple override
        setSelectedItemIds(newSelectedIds);
    };

    const handleDesktopMouseUp = (e: React.MouseEvent) => {
        if (isLassoing) {
            setIsLassoing(false);
            setLassoStart(null);
            setLassoEnd(null);
        }
    };

    const handleItemTouchStart = (e: React.TouchEvent, id: string, type: 'icon' | 'folder') => {
        e.stopPropagation();
        if (touchTimer.current) clearTimeout(touchTimer.current);
        const touch = e.touches[0];
        touchTimer.current = setTimeout(() => {
            if (!selectedItemIds.includes(id)) {
                setSelectedItemIds([id]);
            }
            setContextMenu({
                x: touch.clientX,
                y: touch.clientY,
                type,
                targetId: id
            });
        }, 500);
    };

    const handleIconContextMenu = (e: React.MouseEvent, id: string, type: 'icon' | 'folder') => {
        e.preventDefault();
        e.stopPropagation();
        if (!selectedItemIds.includes(id)) {
            setSelectedItemIds([id]);
        }
        setContextMenu({
            x: e.clientX,
            y: e.clientY,
            type,
            targetId: id
        });
    };

    const handleContextMenuAction = (action: string, targetId: string | null) => {
        switch (action) {
            case 'new_folder':
                handleCreateFolder();
                break;
            case 'auto_arrange':
                handleAutoArrange();
                break;
            case 'categorize_icons':
                if (window.confirm('Group all loose desktop tools into category folders?')) {
                    handleCategorizeIcons();
                }
                break;
            case 'reset_positions':
                if (window.confirm('Are you sure you want to reset all icon positions? This will rearrange them sequentially.')) {
                    handleResetPositions();
                }
                break;
            case 'settings':
                setIsSettingsModalOpen(true);
                setMaxZIndex(prev => {
                    const nextZ = prev + 1;
                    setSettingsZIndex(nextZ);
                    return nextZ;
                });
                break;
            case 'open':
                if (targetId) {
                    const item = desktopItems.find(i => i.id === targetId);
                    if (item?.type === 'folder') setOpenFolderId(targetId);
                    else if (item?.type === 'tool' && item.toolSlug) handleToolClick(item.toolSlug);
                }
                break;
            case 'rename':
                if (targetId) setEditingItemId(targetId);
                break;
            case 'duplicate':
                if (targetId) {
                    const item = desktopItems.find(i => i.id === targetId);
                    if (item) {
                        const { x, y } = findNextAvailableCell(desktopItems);
                        const newItem = { ...item, id: `${item.type}-${Date.now()}`, x, y, name: item.name + ' - Copy' };
                        setDesktopItems([...desktopItems, newItem]);
                    }
                }
                break;
            case 'delete':
                if (selectedItemIds.length > 0) {
                    setDesktopItems(desktopItems.filter(i => !selectedItemIds.includes(i.id)));
                    setSelectedItemIds([]);
                }
                break;
            case 'move_to_desktop':
                // Handled specifically inside FolderModal right-click, but keeping placeholder
                break;
        }
    };

    const handleAutoArrange = () => {
        const sorted = [...desktopItems].sort((a, b) => {
            if (a.type !== b.type) return a.type === 'folder' ? -1 : 1;
            return a.name.localeCompare(b.name);
        });
        const rearranged = sorted.map((item, index) => {
            const x = Math.floor(index / MAX_ROWS);
            const y = index % MAX_ROWS;
            return { ...item, x, y };
        });
        setDesktopItems(rearranged);
    };

    const handleResetPositions = () => {
        const rearranged = [...desktopItems].map((item, index) => {
            const x = Math.floor(index / MAX_ROWS);
            const y = index % MAX_ROWS;
            return { ...item, x, y };
        });
        setDesktopItems(rearranged);
    };

    const handleCategorizeIcons = () => {
        let items = [...desktopItems];
        
        // Find all loose tools
        const looseTools = items.filter(i => i.type === 'tool');
        
        looseTools.forEach(toolItem => {
            if (!toolItem.toolSlug) return;
            const toolData = tools.data.find(t => t.slug === toolItem.toolSlug);
            if (!toolData || !toolData.category_label) return;
            
            const categoryName = toolData.category_label;
            
            // Check if folder exists
            let folderIndex = items.findIndex(i => i.type === 'folder' && i.name === categoryName);
            
            if (folderIndex === -1) {
                const { x, y } = findNextAvailableCell(items);
                const newFolder: DesktopItem = {
                    id: `folder-cat-${Date.now()}-${Math.random()}`,
                    type: 'folder',
                    name: categoryName,
                    childrenSlugs: [],
                    x,
                    y
                };
                items.push(newFolder);
                folderIndex = items.length - 1;
            }
            
            // Move tool to folder
            const folder = items[folderIndex];
            if (!folder.childrenSlugs) folder.childrenSlugs = [];
            
            // Avoid duplicates
            if (!folder.childrenSlugs.includes(toolItem.toolSlug)) {
                folder.childrenSlugs.push(toolItem.toolSlug);
            }
            
            // Remove from desktop
            items = items.filter(i => i.id !== toolItem.id);
            // Re-find folder index because items array length changed
            folderIndex = items.findIndex(i => i.id === folder.id);
        });
        
        // Clean up empty spaces by auto-arranging the remaining items
        const sorted = [...items].sort((a, b) => {
            if (a.type !== b.type) return a.type === 'folder' ? -1 : 1;
            return a.name.localeCompare(b.name);
        });
        const rearranged = sorted.map((item, index) => {
            const x = Math.floor(index / MAX_ROWS);
            const y = index % MAX_ROWS;
            return { ...item, x, y };
        });
        
        setDesktopItems(rearranged);
    };

    // Helper: Find first empty grid cell
    const findNextAvailableCell = (items: DesktopItem[]): { x: number, y: number } => {
        let x = 0;
        let y = 0;
        // eslint-disable-next-line no-constant-condition
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
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [tools.data]);

    // Persist layout and windows on change
    const isFirstRender = useRef(true);
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }
        saveSettings(desktopItems, showPrayerTimes, wallpaperUrl, prayerCity, prayerCountry, prayerMethod, openWithOneClick, runtimeHost, activeWindows);
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [desktopItems, activeWindows, runtimeHost]);

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
            const existingWindow = activeWindows.find(w => w.slug === tool.slug);
            if (existingWindow) {
                setActiveWindows(prev => prev.map(w => 
                    w.id === existingWindow.id 
                    ? { ...w, isMinimized: false, zIndex: maxZIndex + 1 } 
                    : w
                ));
                setMaxZIndex(prev => prev + 1);
            } else {
                const newZIndex = maxZIndex + 1;
                setMaxZIndex(newZIndex);
                setActiveWindows(prev => [...prev, {
                    id: `win-${Date.now()}`,
                    slug: tool.slug,
                    title: tool.title,
                    iconUrl: tool.icon_url,
                    isMinimized: false,
                    isMaximized: false,
                    zIndex: newZIndex
                }]);
            }
        } else {
            setSelectedTool(tool);
            setSubscribeModalOpen(true);
        }
    };

    const handleWindowFocus = (id: string) => {
        setMaxZIndex(prev => {
            const nextZ = prev + 1;
            setActiveWindows(windows => windows.map(w => w.id === id ? { ...w, zIndex: nextZ } : w));
            return nextZ;
        });
    };

    const handleWindowClose = (id: string) => {
        setActiveWindows(prev => prev.filter(w => w.id !== id));
    };

    const handleWindowToggleMinimize = (id: string) => {
        setActiveWindows(prev => prev.map(w => w.id === id ? { ...w, isMinimized: !w.isMinimized } : w));
    };

    const handleWindowToggleMaximize = (id: string) => {
        setActiveWindows(prev => prev.map(w => w.id === id ? { ...w, isMaximized: !w.isMaximized } : w));
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
        
        let dragIds = selectedItemIds;
        if (!selectedItemIds.includes(id)) {
            dragIds = [id];
            setSelectedItemIds([id]);
        }
        
        const payload = JSON.stringify({
            ids: dragIds,
            sourceFolderId: sourceFolderId || null
        });
        
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
        
        try {
            const payloadStr = e.dataTransfer.getData('text/plain');
            
            // legacy single ID fallback
            if (!payloadStr.startsWith('{')) {
                // handle legacy drop (if needed) but we can just return for safety
                if (!payloadStr.startsWith('fromFolder:')) return; 
            }

            let payload: { ids: string[], sourceFolderId: string | null } = { ids: [], sourceFolderId: null };
            
            if (payloadStr.startsWith('fromFolder:')) {
                const [, folderId, toolSlug] = payloadStr.split(':');
                payload = { ids: [toolSlug], sourceFolderId: folderId };
            } else {
                payload = JSON.parse(payloadStr);
            }
            
            if (!payload || !payload.ids) return;

            const { ids, sourceFolderId } = payload;
            
            // if dropping an item on itself, ignore
            if (ids.includes(targetId)) return;
            
            setDesktopItems(prevItems => {
                const items = [...prevItems];
                const targetIndex = items.findIndex(i => i.id === targetId);
                if (targetIndex === -1) return prevItems;
                const targetItem = items[targetIndex];
                
                if (sourceFolderId) {
                    const sourceFolderIndex = items.findIndex(i => i.id === sourceFolderId);
                    if (sourceFolderIndex > -1) {
                        const sourceFolder = items[sourceFolderIndex];
                        items[sourceFolderIndex] = {
                            ...sourceFolder,
                            childrenSlugs: (sourceFolder.childrenSlugs || []).filter(s => !ids.includes(s))
                        };
                        
                        if (targetItem.type === 'folder') {
                            items[targetIndex] = {
                                ...targetItem,
                                childrenSlugs: [...(targetItem.childrenSlugs || []), ...ids]
                            };
                        } else if (targetItem.type === 'tool') {
                            const newFolder: DesktopItem = {
                                id: `folder-${Date.now()}`,
                                type: 'folder',
                                name: 'New Folder',
                                childrenSlugs: [targetItem.toolSlug!, ...ids],
                                x: targetItem.x,
                                y: targetItem.y
                            };
                            items.splice(targetIndex, 1, newFolder);
                        }
                    }
                } else {
                    const sourceItems = items.filter(i => ids.includes(i.id) && i.type === 'tool');
                    if (sourceItems.length === 0) return prevItems;
                    
                    const remainingItems = items.filter(i => !ids.includes(i.id));
                    const newTargetIndex = remainingItems.findIndex(i => i.id === targetId);
                    
                    if (targetItem.type === 'folder') {
                        remainingItems[newTargetIndex] = {
                            ...targetItem,
                            childrenSlugs: [...(targetItem.childrenSlugs || []), ...sourceItems.map(i => i.toolSlug!)]
                        };
                    } else if (targetItem.type === 'tool') {
                        remainingItems[newTargetIndex] = {
                            ...targetItem,
                            type: 'folder',
                            name: 'New Folder',
                            childrenSlugs: [targetItem.toolSlug!, ...sourceItems.map(i => i.toolSlug!)]
                        };
                    }
                    return remainingItems;
                }
                return items;
            });
        } catch(err) {
            console.error(err);
        }
        setDraggedItemId(null);
    };

    const handleDesktopDrop = (e: React.DragEvent) => {
        e.preventDefault();
        setDragOverItemId(null);

        const container = e.currentTarget as HTMLElement;
        const rect = container.getBoundingClientRect();
        
        const dropX = e.clientX - rect.left;
        const dropY = e.clientY - rect.top;
        const gridX = Math.max(0, Math.floor(dropX / CELL_WIDTH));
        const gridY = Math.max(0, Math.floor(dropY / CELL_HEIGHT));

        try {
            const payloadStr = e.dataTransfer.getData('text/plain');
            
            let payload: { ids: string[], sourceFolderId: string | null } = { ids: [], sourceFolderId: null };
            
            if (payloadStr.startsWith('fromFolder:')) {
                const [, folderId, toolSlug] = payloadStr.split(':');
                payload = { ids: [toolSlug], sourceFolderId: folderId };
            } else if (payloadStr.startsWith('{')) {
                payload = JSON.parse(payloadStr);
            } else {
                payload = { ids: [payloadStr], sourceFolderId: null };
            }

            if (!payload || !payload.ids) return;

            const { ids, sourceFolderId } = payload;
            
            setDesktopItems(prevItems => {
                const items = [...prevItems];
                
                if (sourceFolderId) {
                    const sourceFolderIndex = items.findIndex(i => i.id === sourceFolderId);
                    if (sourceFolderIndex > -1) {
                        const sourceFolder = items[sourceFolderIndex];
                        items[sourceFolderIndex] = {
                            ...sourceFolder,
                            childrenSlugs: (sourceFolder.childrenSlugs || []).filter(s => !ids.includes(s))
                        };
                        
                        let currentGridX = gridX;
                        let currentGridY = gridY;
                        
                        ids.forEach((slug: string) => {
                            const toolData = tools.data.find(t => t.slug === slug);
                            if (toolData) {
                                let finalX = currentGridX, finalY = currentGridY;
                                const occupantIndex = items.findIndex(i => i.x === finalX && i.y === finalY);
                                if (occupantIndex > -1) {
                                    const { x, y } = findNextAvailableCell(items);
                                    finalX = x;
                                    finalY = y;
                                }
                                items.push({
                                    id: `tool-${slug}-${Date.now()}-${Math.random()}`,
                                    type: 'tool',
                                    name: toolData.title,
                                    toolSlug: slug,
                                    x: finalX,
                                    y: finalY
                                });
                                currentGridX++;
                                if (currentGridX >= Math.max(1, Math.floor(rect.width / CELL_WIDTH))) {
                                    currentGridX = 0;
                                    currentGridY++;
                                }
                            }
                        });
                    }
                } else {
                    let currentGridX = gridX;
                    let currentGridY = gridY;
                    
                    ids.forEach((id: string) => {
                        const itemIndex = items.findIndex(i => i.id === id);
                        if (itemIndex > -1) {
                            let finalX = currentGridX;
                            let finalY = currentGridY;
                            const occupantIndex = items.findIndex(i => i.x === finalX && i.y === finalY && !ids.includes(i.id));
                            
                            if (occupantIndex > -1) {
                                const { x, y } = findNextAvailableCell(items);
                                finalX = x;
                                finalY = y;
                            }
                            
                            items[itemIndex] = { ...items[itemIndex], x: finalX, y: finalY };
                            
                            currentGridX++;
                            if (currentGridX >= Math.max(1, Math.floor(rect.width / CELL_WIDTH))) {
                                currentGridX = 0;
                                currentGridY++;
                            }
                        }
                    });
                }
                return items;
            });
            
        } catch (err) {
           console.error(err);
        }
        setDraggedItemId(null);
    };

    const handleRenameFolder = (folderId: string, newName: string) => {
        setDesktopItems(prev => prev.map(item => item.id === folderId ? { ...item, name: newName } : item));
    };

    const openFolder = desktopItems.find(i => i.id === openFolderId);

    return (
        <div 
            className="h-screen w-screen overflow-hidden bg-slate-900 bg-cover bg-center flex flex-col font-['Inter',sans-serif]"
            style={{ backgroundImage: wallpaperUrl ? `url(${wallpaperUrl})` : 'none' }}
            onClick={(e) => {
                setIsStartMenuOpen(false);
                setContextMenu(null);
                setEditingItemId(null);
                
                if (wasLassoingRef.current) {
                    wasLassoingRef.current = false;
                    return;
                }
                
                // Only clear selection if we directly clicked the desktop background
                if (!(e.target as HTMLElement).closest('.desktop-item-container')) {
                    setSelectedItemIds([]);
                }
            }}
            onContextMenu={handleDesktopContextMenu}
            onTouchStart={handleDesktopTouchStart}
            onTouchMove={handleDesktopTouchMove}
            onTouchEnd={handleDesktopTouchEnd}
        >
            <Head title={__('general.tools_workspace')} />

            {/* Desktop Area */}
            <div 
                className={`flex-1 p-4 md:p-6 overflow-x-hidden relative ${isMobile ? 'grid grid-cols-3 sm:grid-cols-4 gap-y-6 gap-x-2 overflow-y-auto content-start z-10' : 'overflow-hidden'}`}
                onDragOver={(e) => { if (!isMobile) e.preventDefault(); }}
                onDrop={(e) => { if (!isMobile) handleDesktopDrop(e); }}
                onMouseDown={handleDesktopMouseDown}
                onMouseMove={handleDesktopMouseMove}
                onMouseUp={handleDesktopMouseUp}
                onMouseLeave={handleDesktopMouseUp}
            >
                {/* Lasso Box */}
                {isLassoing && lassoStart && lassoEnd && !isMobile && (
                    <div 
                        className="absolute bg-blue-500/30 border border-blue-400 pointer-events-none z-50"
                        style={{
                            left: Math.min(lassoStart.x, lassoEnd.x),
                            top: Math.min(lassoStart.y, lassoEnd.y),
                            width: Math.abs(lassoEnd.x - lassoStart.x),
                            height: Math.abs(lassoEnd.y - lassoStart.y),
                        }}
                    />
                )}
                {desktopItems.map((item) => {
                    const style: React.CSSProperties = isMobile ? { position: 'relative' } : {
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
                                title={item.name || toolData.title}
                                iconUrl={toolData.icon_url}
                                emojiFallback={SLUG_EMOJI_MAP[toolData.slug] ?? '📦'}
                                isOwned={subscribedSlugs.includes(toolData.slug)}
                                isFeatured={toolData.is_featured}
                                onClick={(e) => {
                                    e.stopPropagation();
                                    if (e.ctrlKey) {
                                        setSelectedItemIds(prev => prev.includes(item.id) ? prev.filter(i => i !== item.id) : [...prev, item.id]);
                                    } else {
                                        if (openWithOneClick || isMobile) {
                                            handleToolClick(toolData.slug);
                                            setSelectedItemIds([item.id]);
                                        } else {
                                            setSelectedItemIds([item.id]);
                                        }
                                    }
                                }}
                                onDoubleClick={() => {
                                    if (!openWithOneClick && !isMobile) handleToolClick(toolData.slug);
                                }}
                                onDragStart={(e) => handleDragStart(e, item.id)}
                                onDragEnd={() => setDraggedItemId(null)}
                                style={style}
                                className={`desktop-item-container ${draggedItemId === item.id ? 'opacity-50 scale-95' : 'hover:scale-105 transition-transform'}`}
                                isSelected={selectedItemIds.includes(item.id)}
                                isEditing={editingItemId === item.id}
                                onRenameSubmit={(newName) => {
                                    setDesktopItems(desktopItems.map(i => i.id === item.id ? { ...i, name: newName } : i));
                                    setEditingItemId(null);
                                }}
                                onContextMenu={(e) => handleIconContextMenu(e, item.id, 'icon')}
                                onTouchStart={(e) => handleItemTouchStart(e, item.id, 'icon')}
                                draggable
                                onDragOver={handleDragOver}
                                onDragLeave={handleDragLeave}
                                onDrop={handleDropOnItem}
                                isDragOver={dragOverItemId === item.id}
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
                                onClick={(e) => {
                                    e.stopPropagation();
                                    if (e.ctrlKey) {
                                        setSelectedItemIds(prev => prev.includes(item.id) ? prev.filter(i => i !== item.id) : [...prev, item.id]);
                                    } else {
                                        if (openWithOneClick || isMobile) {
                                            setOpenFolderId(item.id);
                                            setSelectedItemIds([item.id]);
                                        } else {
                                            setSelectedItemIds([item.id]);
                                        }
                                    }
                                }}
                                onDoubleClick={() => {
                                    if (!openWithOneClick && !isMobile) setOpenFolderId(item.id);
                                }}
                                onDragStart={(e) => handleDragStart(e, item.id)}
                                onDragEnd={() => setDraggedItemId(null)}
                                onDragOver={(e) => handleDragOver(e, item.id)}
                                onDragLeave={handleDragLeave}
                                onDrop={(e) => handleDropOnItem(e, item.id)}
                                isDragOver={dragOverItemId === item.id}
                                style={style}
                                className={`desktop-item-container ${draggedItemId === item.id ? 'opacity-50 scale-95' : 'hover:scale-105 transition-transform'}`}
                                isSelected={selectedItemIds.includes(item.id)}
                                isEditing={editingItemId === item.id}
                                onRenameSubmit={(newName) => handleRenameFolder(item.id, newName)}
                                onContextMenu={(e) => handleIconContextMenu(e, item.id, 'folder')}
                                onTouchStart={(e) => handleItemTouchStart(e, item.id, 'folder')}
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
                                <p className="text-xs text-slate-400">{__('general.tools_workspace')}</p>
                            </div>
                        </div>
                        
                        <div className="flex flex-col gap-1">
                            <Link 
                                href={route('dashboard')} 
                                className="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-white/10 text-slate-200 transition-colors"
                            >
                                <span className="text-lg">📊</span>
                                <span className="text-sm font-medium">{__('general.main_dashboard')}</span>
                            </Link>
                            <Link 
                                href={route('tools.downloads')} 
                                className="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-white/10 text-slate-200 transition-colors"
                            >
                                <span className="text-lg">⬇️</span>
                                <span className="text-sm font-medium">{__('general.downloads_history')}</span>
                            </Link>
                            <Link 
                                href="/" 
                                className="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-white/10 text-slate-200 transition-colors"
                            >
                                <span className="text-lg">🏠</span>
                                <span className="text-sm font-medium">{__('general.back_to_website')}</span>
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
                                onClick={() => { 
                                    setIsSettingsModalOpen(true); 
                                    setIsStartMenuOpen(false);
                                    setMaxZIndex(prev => {
                                        const nextZ = prev + 1;
                                        setSettingsZIndex(nextZ);
                                        return nextZ;
                                    });
                                }}
                                className="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-white/10 text-slate-200 transition-colors text-left mt-1"
                            >
                                <span className="text-lg">⚙️</span>
                                <span className="text-sm font-medium">{__('general.desktop_settings')}</span>
                            </button>
                        </div>
                    </div>
                )}

                <div className="flex items-center h-full">
                    {/* Start Button */}
                    <button 
                        onClick={(e) => { e.stopPropagation(); setIsStartMenuOpen(!isStartMenuOpen); }}
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
                            <span className="hidden md:inline">{__('general.new_folder')}</span>
                        </button>
                    </div>

                    {/* Running Apps */}
                    <div className="flex items-center h-full space-x-1 flex-1 overflow-x-auto px-2">
                        {activeWindows.map(win => (
                            <button
                                key={win.id}
                                onClick={() => {
                                    if (win.isMinimized) {
                                        handleWindowToggleMinimize(win.id);
                                        handleWindowFocus(win.id);
                                    } else {
                                        // If it's not minimized but not focused, focus it. Otherwise minimize it.
                                        if (win.zIndex !== maxZIndex) {
                                            handleWindowFocus(win.id);
                                        } else {
                                            handleWindowToggleMinimize(win.id);
                                        }
                                    }
                                }}
                                className={`h-10 px-3 flex items-center justify-center rounded transition-colors text-xs font-medium gap-2 border-b-2 ${!win.isMinimized && win.zIndex === maxZIndex ? 'bg-white/20 border-blue-400 text-white' : 'hover:bg-white/10 border-transparent text-slate-300'}`}
                            >
                                {win.iconUrl ? (
                                    <img src={win.iconUrl} alt={win.title} className="w-4 h-4 object-contain" draggable={false} />
                                ) : (
                                    <span className="w-4 h-4 flex items-center justify-center">📦</span>
                                )}
                                <span className="max-w-[100px] truncate">{win.title}</span>
                            </button>
                        ))}
                    </div>
                </div>

                <div className="flex items-center h-full text-white text-xs">
                    {showPrayerTimes && nextPrayer && (
                        <div 
                            onContextMenu={handleHidePrayerTimes}
                            className="hidden md:flex items-center gap-3 px-4 border-r border-white/10 mr-2 h-full cursor-pointer hover:bg-white/5 transition-colors"
                            title={__('general.right_click_to_hide')}
                        >
                            <span className="text-lg">🕌</span>
                            <div className="flex flex-col items-start justify-center">
                                <span className="text-[10px] text-slate-400 font-medium tracking-wide uppercase">{__('general.next_prayer')}</span>
                                <div className="flex items-center gap-2">
                                    <span className="font-bold text-amber-400">{nextPrayer.name}</span>
                                    <span className="text-white font-semibold">{formatPrayerTime(nextPrayer.time)}</span>
                                </div>
                            </div>
                        </div>
                    )}
                    
                    <button 
                        onClick={toggleFullscreen}
                        className="hidden md:flex h-full px-3 items-center justify-center hover:bg-white/10 transition-colors text-slate-300"
                        title={__('general.toggle_fullscreen')}
                    >
                        {isFullscreen ? <Minimize className="w-4 h-4" /> : <Maximize className="w-4 h-4" />}
                    </button>
                    <div className="hidden md:flex flex-col items-end justify-center px-3 hover:bg-white/10 h-full transition-colors cursor-default">
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
                            onClick={() => {
                                if (openWithOneClick) handleToolClick(toolData.slug);
                            }}
                            onDoubleClick={() => {
                                if (!openWithOneClick) handleToolClick(toolData.slug);
                            }}
                            draggable
                            onDragStart={(e) => handleDragStart(e, slug, openFolder.id)}
                            onDragEnd={() => setDraggedItemId(null)}
                            onContextMenu={(e) => handleIconContextMenu(e, slug, 'icon')}
                        />
                    );
                })}
                {openFolder?.childrenSlugs?.length === 0 && (
                    <div className="w-full h-full flex items-center justify-center text-white/50 text-sm">{__('general.empty_folder')}</div>
                )}
            </FolderModal>

            {/* Subscribe Modal / App Window */}
            <WindowModal
                isOpen={isSubscribeModalOpen}
                isMobile={isMobile}
                onClose={() => {
                    setSubscribeModalOpen(false);
                    setSelectedTool(null);
                }}
                title={selectedTool ? `Subscribe to ${selectedTool.title}` : 'Subscribe'}
                iconUrl={selectedTool?.icon_url}
                isMaximized={false}
                onMinimize={() => {}}
                onMaximize={() => {}}
                zIndex={9999}
                onFocus={() => {}}
                width="w-[500px]"
                height="h-[600px]"
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
                                                : `${formatMoney(selectedTool.pricing_plans[0].price_monthly, businessCurrency)}/mo`}
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
                                    <span className="font-semibold text-slate-900">{__('general.starting_at')}</span>
                                    <span className="text-lg font-bold text-slate-900">
                                        {selectedTool.starting_price <= 0 ? 'Free' : `${formatMoney(selectedTool.starting_price, businessCurrency)}/mo`}
                                    </span>
                                </div>
                            )}
                            
                            <div className="pt-2">
                                <Button 
                                    onClick={handleSubscribeAction}
                                    className="w-full h-11 bg-slate-900 hover:bg-slate-800 text-white"
                                >{__('general.proceed_to_subscribe')}</Button>
                            </div>

                            <p className="text-xs text-slate-400 text-center flex items-center justify-center gap-1 mt-2">
                                <Shield className="h-3 w-3" />{__('general.safe_secure_checkout')}</p>
                        </div>
                    </div>
                )}
            </WindowModal>

            <SettingsModal 
                isOpen={isSettingsModalOpen}
                onClose={() => setIsSettingsModalOpen(false)}
                zIndex={settingsZIndex}
                onFocus={() => {
                    setMaxZIndex(prev => {
                        const nextZ = prev + 1;
                        setSettingsZIndex(nextZ);
                        return nextZ;
                    });
                }}
                showPrayerTimes={showPrayerTimes}
                onTogglePrayerTimes={() => {
                    const newValue = !showPrayerTimes;
                    setShowPrayerTimes(newValue);
                    saveSettings(desktopItems, newValue, wallpaperUrl);
                }}
                wallpaperUrl={wallpaperUrl}
                onWallpaperChange={(url) => {
                    setWallpaperUrl(url);
                    saveSettings(desktopItems, showPrayerTimes, url);
                }}
                prayerCity={prayerCity}
                prayerCountry={prayerCountry}
                prayerMethod={prayerMethod}
                onPrayerSettingsChange={handlePrayerSettingsChange}
                openWithOneClick={openWithOneClick}
                onToggleOneClick={handleToggleOneClick}
                runtimeHost={runtimeHost}
                onRuntimeHostChange={(host) => {
                    setRuntimeHost(host);
                    saveSettings(desktopItems, showPrayerTimes, wallpaperUrl, prayerCity, prayerCountry, prayerMethod, openWithOneClick, host);
                }}
            />

            <ContextMenu 
                menu={contextMenu}
                onClose={() => setContextMenu(null)}
                onAction={handleContextMenuAction}
            />
            {/* Active Windows */}
            {activeWindows.map((win, index) => (
                <WindowModal
                    key={win.id}
                    isMobile={isMobile}
                    isOpen={!win.isMinimized}
                    onClose={() => handleWindowClose(win.id)}
                    title={win.title}
                    iconUrl={win.iconUrl}
                    isMaximized={win.isMaximized}
                    onMinimize={() => handleWindowToggleMinimize(win.id)}
                    onMaximize={() => handleWindowToggleMaximize(win.id)}
                    onNewTab={() => {
                        window.open(route('tools.run', win.slug), '_blank');
                        handleWindowClose(win.id);
                    }}
                    zIndex={win.zIndex}
                    onFocus={() => handleWindowFocus(win.id)}
                    initialX={100 + (index * 30)}
                    initialY={50 + (index * 30)}
                >
                    <iframe 
                        src={route('tools.run', win.slug)} 
                        className="w-full h-full border-none bg-white"
                        title={win.title}
                    />
                </WindowModal>
            ))}

        </div>
    );
}

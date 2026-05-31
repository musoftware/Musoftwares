import React, { useState, useEffect } from 'react';
import { X, Image as ImageIcon, Settings as SettingsIcon } from 'lucide-react';

interface SettingsModalProps {
    isOpen: boolean;
    onClose: () => void;
    showPrayerTimes: boolean;
    onTogglePrayerTimes: () => void;
    wallpaperUrl: string;
    onWallpaperChange: (url: string) => void;
    prayerCity: string;
    prayerCountry: string;
    prayerMethod: string;
    onPrayerSettingsChange: (city: string, country: string, method: string) => void;
    openWithOneClick: boolean;
    onToggleOneClick: () => void;
}

const DEFAULT_WALLPAPERS = [
    'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?q=80&w=2560&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1472214103451-9374bd1c798e?q=80&w=2560&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1518837695005-2083093ee35b?q=80&w=2560&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1493246507139-91e8fad9978e?q=80&w=2560&auto=format&fit=crop',
];

export function SettingsModal({ isOpen, onClose, showPrayerTimes, onTogglePrayerTimes, wallpaperUrl, onWallpaperChange, prayerCity, prayerCountry, prayerMethod, onPrayerSettingsChange, openWithOneClick, onToggleOneClick }: SettingsModalProps) {
    const [wallpapers, setWallpapers] = useState<string[]>([]);

    useEffect(() => {
        if (isOpen && wallpapers.length === 0) {
            fetch('/api/bing-daily-images')
                .then(res => res.json())
                .then(data => {
                    if (data && data.length > 0) {
                        setWallpapers(data.slice(0, 4));
                    }
                })
                .catch(err => console.error("Failed to fetch bing images", err));
        }
    }, [isOpen]);

    if (!isOpen) return null;

    const displayWallpapers = wallpapers.length > 0 ? wallpapers : DEFAULT_WALLPAPERS;

    return (
        <div 
            className="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
            onClick={onClose}
        >
            <div 
                className="w-full max-w-2xl bg-[#1c1c1c] border border-white/10 rounded-2xl shadow-2xl flex flex-col overflow-hidden animate-in fade-in zoom-in duration-200"
                onClick={(e) => e.stopPropagation()}
            >
                <div className="h-12 border-b border-white/10 flex items-center justify-between px-4 bg-white/5">
                    <div className="flex items-center gap-2 text-white font-medium">
                        <SettingsIcon className="w-4 h-4 text-slate-400" />
                        Desktop Settings
                    </div>
                    <button onClick={onClose} className="p-1 hover:bg-white/10 rounded-md text-slate-400 hover:text-white transition-colors">
                        <X className="w-5 h-5" />
                    </button>
                </div>

                <div className="p-6 flex flex-col gap-8 text-white">
                    {/* Widget Settings */}
                    <div>
                        <h3 className="text-lg font-semibold mb-4 flex items-center gap-2">
                            <span className="text-blue-400">#</span> Widgets & Behavior
                        </h3>
                        <div className="flex flex-col gap-3 bg-white/5 p-4 rounded-xl border border-white/5">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h4 className="font-medium">Open with Single Click</h4>
                                    <p className="text-xs text-slate-400 mt-1">Open tools and folders with a single click instead of double click</p>
                                </div>
                                <label className="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" className="sr-only peer" checked={openWithOneClick} onChange={onToggleOneClick} />
                                    <div className="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                            
                            <div className="flex items-center justify-between mt-2 pt-3 border-t border-white/10">
                                <div>
                                    <h4 className="font-medium">Prayer Times</h4>
                                    <p className="text-xs text-slate-400 mt-1">Display local prayer times in the taskbar</p>
                                </div>
                                <label className="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" className="sr-only peer" checked={showPrayerTimes} onChange={onTogglePrayerTimes} />
                                    <div className="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                            
                            {showPrayerTimes && (
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3 pt-4 border-t border-white/10">
                                    <div>
                                        <label className="text-xs text-slate-400 mb-1 block">City</label>
                                        <input 
                                            type="text"
                                            value={prayerCity}
                                            onChange={(e) => onPrayerSettingsChange(e.target.value, prayerCountry, prayerMethod)}
                                            className="w-full bg-[#1c1c1c] border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500 transition-colors"
                                            placeholder="Cairo"
                                        />
                                    </div>
                                    <div>
                                        <label className="text-xs text-slate-400 mb-1 block">Country</label>
                                        <input 
                                            type="text"
                                            value={prayerCountry}
                                            onChange={(e) => onPrayerSettingsChange(prayerCity, e.target.value, prayerMethod)}
                                            className="w-full bg-[#1c1c1c] border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500 transition-colors"
                                            placeholder="Egypt"
                                        />
                                    </div>
                                    <div>
                                        <label className="text-xs text-slate-400 mb-1 block">Calculation Method</label>
                                        <select 
                                            value={prayerMethod}
                                            onChange={(e) => onPrayerSettingsChange(prayerCity, prayerCountry, e.target.value)}
                                            className="w-full bg-[#1c1c1c] border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500 transition-colors"
                                        >
                                            <option value="1">University of Islamic Sciences, Karachi</option>
                                            <option value="2">Islamic Society of North America</option>
                                            <option value="3">Muslim World League</option>
                                            <option value="4">Umm Al-Qura University, Makkah</option>
                                            <option value="5">Egyptian General Authority of Survey</option>
                                            <option value="8">Gulf Region</option>
                                            <option value="9">Kuwait</option>
                                            <option value="10">Qatar</option>
                                            <option value="11">Majlis Ugama Islam Singapura</option>
                                            <option value="12">Union Organization islamic de France</option>
                                            <option value="13">Diyanet İşleri Başkanlığı, Turkey</option>
                                            <option value="14">Spiritual Administration of Muslims of Russia</option>
                                            <option value="15">Moonsighting Committee Worldwide</option>
                                            <option value="16">Dubai</option>
                                        </select>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Wallpaper Settings */}
                    <div>
                        <h3 className="text-lg font-semibold mb-4 flex items-center gap-2">
                            <ImageIcon className="w-5 h-5 text-purple-400" /> Wallpaper
                        </h3>
                        <div className="grid grid-cols-2 gap-4">
                            {displayWallpapers.map((url, idx) => (
                                <div 
                                    key={idx}
                                    onClick={() => onWallpaperChange(url)}
                                    className={`relative h-24 rounded-xl overflow-hidden cursor-pointer group border-2 transition-all ${wallpaperUrl === url ? 'border-blue-500 shadow-[0_0_15px_rgba(59,130,246,0.5)]' : 'border-transparent hover:border-white/20'}`}
                                >
                                    <img src={url} alt={`Wallpaper ${idx + 1}`} className="w-full h-full object-cover group-hover:scale-105 transition-transform" />
                                </div>
                            ))}
                        </div>
                        <div className="mt-4">
                            <label className="text-xs text-slate-400 mb-1 block">Custom URL</label>
                            <input 
                                type="text"
                                value={wallpaperUrl}
                                onChange={(e) => onWallpaperChange(e.target.value)}
                                className="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500 transition-colors"
                                placeholder="https://..."
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

import React, { useState, useEffect } from 'react';
import { X, Image as ImageIcon, Settings as SettingsIcon } from 'lucide-react';
import { __ } from '@/lib/i18n';

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
    runtimeHost: string;
    onRuntimeHostChange: (host: string) => void;
}

const DEFAULT_WALLPAPERS = [
    'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?q=80&w=2560&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1472214103451-9374bd1c798e?q=80&w=2560&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1518837695005-2083093ee35b?q=80&w=2560&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1493246507139-91e8fad9978e?q=80&w=2560&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1506744626753-143d4eb2c842?q=80&w=2560&auto=format&fit=crop',
];

export function SettingsModal({ isOpen, onClose, showPrayerTimes, onTogglePrayerTimes, wallpaperUrl, onWallpaperChange, prayerCity, prayerCountry, prayerMethod, onPrayerSettingsChange, openWithOneClick, onToggleOneClick, runtimeHost, onRuntimeHostChange }: SettingsModalProps) {
    const [wallpapers, setWallpapers] = useState<string[]>([]);

    useEffect(() => {
        if (isOpen && wallpapers.length === 0) {
            fetch('/api/bing-daily-images')
                .then(res => res.json())
                .then(data => {
                    if (data && data.length > 0) {
                        setWallpapers(data.slice(0, 5));
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
                        <SettingsIcon className="w-4 h-4 text-slate-400" />{__('general.desktop_settings')}</div>
                    <button onClick={onClose} className="p-1 hover:bg-white/10 rounded-md text-slate-400 hover:text-white transition-colors">
                        <X className="w-5 h-5" />
                    </button>
                </div>

                <div className="p-6 flex flex-col gap-8 text-white">
                    {/* Widget Settings */}
                    <div>
                        <h3 className="text-lg font-semibold mb-4 flex items-center gap-2">
                            <span className="text-blue-400">#</span>{__('general.widgets_behavior')}</h3>
                        <div className="flex flex-col gap-3 bg-white/5 p-4 rounded-xl border border-white/5">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h4 className="font-medium">{__('general.open_with_single_click')}</h4>
                                    <p className="text-xs text-slate-400 mt-1">{__('general.open_tools_and_folders_with_a_single_click_instead_of_double_click')}</p>
                                </div>
                                <label className="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" className="sr-only peer" checked={openWithOneClick} onChange={onToggleOneClick} />
                                    <div className="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                            
                            <div className="flex items-center justify-between mt-2 pt-3 border-t border-white/10">
                                <div>
                                    <h4 className="font-medium">{__('general.prayer_times')}</h4>
                                    <p className="text-xs text-slate-400 mt-1">{__('general.display_local_prayer_times_in_the_taskbar')}</p>
                                </div>
                                <label className="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" className="sr-only peer" checked={showPrayerTimes} onChange={onTogglePrayerTimes} />
                                    <div className="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>

                            <div className="flex items-center justify-between mt-2 pt-3 border-t border-white/10">
                                <div>
                                    <h4 className="font-medium">{__('general.runtime_host_ip')}</h4>
                                    <p className="text-xs text-slate-400 mt-1">{__('general.the_local_ip_address_of_your_musoftware_runtime_agent')}</p>
                                </div>
                                <div className="w-48">
                                    <input 
                                        type="text"
                                        value={runtimeHost}
                                        onChange={(e) => onRuntimeHostChange(e.target.value)}
                                        className="w-full bg-[#1c1c1c] border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500 transition-colors text-right"
                                        placeholder="127.0.0.1"
                                    />
                                </div>
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
                                        <label className="text-xs text-slate-400 mb-1 block">{__('general.calculation_method')}</label>
                                        <select 
                                            value={prayerMethod}
                                            onChange={(e) => onPrayerSettingsChange(prayerCity, prayerCountry, e.target.value)}
                                            className="w-full bg-[#1c1c1c] border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500 transition-colors"
                                        >
                                            <option value="1">{__('general.university_of_islamic_sciences_karachi')}</option>
                                            <option value="2">{__('general.islamic_society_of_north_america')}</option>
                                            <option value="3">{__('general.muslim_world_league')}</option>
                                            <option value="4">{__('general.umm_al_qura_university_makkah')}</option>
                                            <option value="5">{__('general.egyptian_general_authority_of_survey')}</option>
                                            <option value="8">{__('general.gulf_region')}</option>
                                            <option value="9">Kuwait</option>
                                            <option value="10">Qatar</option>
                                            <option value="11">{__('general.majlis_ugama_islam_singapura')}</option>
                                            <option value="12">{__('general.union_organization_islamic_de_france')}</option>
                                            <option value="13">{__('general.diyanet_i_leri_ba_kanl_turkey')}</option>
                                            <option value="14">{__('general.spiritual_administration_of_muslims_of_russia')}</option>
                                            <option value="15">{__('general.moonsighting_committee_worldwide')}</option>
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
                        <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <div 
                                onClick={() => onWallpaperChange('')}
                                className={`relative h-24 rounded-xl overflow-hidden cursor-pointer group border-2 transition-all flex flex-col items-center justify-center bg-[#1c1c1c] ${!wallpaperUrl ? 'border-blue-500 shadow-[0_0_15px_rgba(59,130,246,0.5)]' : 'border-white/10 hover:border-white/20'}`}
                            >
                                <X className="w-6 h-6 text-slate-400 group-hover:text-white transition-colors mb-1" />
                                <span className="text-xs font-medium text-slate-400 group-hover:text-white transition-colors">None</span>
                            </div>
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
                            <label className="text-xs text-slate-400 mb-1 block">{__('general.custom_url')}</label>
                            <input 
                                type="text"
                                value={wallpaperUrl}
                                onChange={(e) => onWallpaperChange(e.target.value)}
                                className="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500 transition-colors"
                                placeholder={__('general.https')}
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

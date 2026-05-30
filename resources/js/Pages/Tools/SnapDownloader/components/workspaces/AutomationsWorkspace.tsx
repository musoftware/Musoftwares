import React, { useState } from 'react';
import { Play, Clock, RefreshCw, Users, MoreHorizontal, Activity } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Switch } from '@/Components/ui/switch';
import { Dialog, DialogTrigger, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { __ } from '@/lib/i18n';

export function AutomationsWorkspace({
    callRPC,
    loadAll,
    automations
}: {
    callRPC: (action: string, data?: any) => Promise<any>;
    loadAll: () => Promise<void>;
    automations: any[];
}) {
    const [autoForm, setAutoForm] = useState({
        name: '',
        targets: '',
        frequency: 'daily',
        filters: { stories: true, spotlights: true, highlights: false, episodes: false },
        ffmpegOptimize: false,
        ffmpegAddLogo: false,
        watermarkPath: '',
        watermarkPosition: 'bottom_right',
        minDuration: 0,
        maxDuration: 0,
        globalDeduplication: true,
        exportMetadata: true,
        muteAudio: false,
        smartSync: true,
        pacing: '1_min',
    });
    const [isCreatingAuto, setIsCreatingAuto] = useState(false);
    const [showAutoForm, setShowAutoForm] = useState(false);
    const [editingAutoId, setEditingAutoId] = useState<string | null>(null);

    const handleCreateAutomation = async () => {
        if (!autoForm.name.trim() || !autoForm.targets.trim()) return;
        setIsCreatingAuto(true);
        try {
            const payload = {
                name: autoForm.name,
                targets: autoForm.targets.split('\n').map(t => t.trim()).filter(t => t),
                frequency: autoForm.frequency,
                filters: autoForm.filters,
                executionRules: {
                    smartSync: autoForm.smartSync,
                    pacing: autoForm.pacing,
                    minDuration: autoForm.minDuration,
                    maxDuration: autoForm.maxDuration,
                    globalDeduplication: autoForm.globalDeduplication,
                    exportMetadata: autoForm.exportMetadata,
                },
                pipeline: {
                    ffmpegOptimize: autoForm.ffmpegOptimize,
                    ffmpegAddLogo: autoForm.ffmpegAddLogo,
                    watermarkPath: autoForm.watermarkPath,
                    watermarkPosition: autoForm.watermarkPosition,
                    muteAudio: autoForm.muteAudio,
                }
            };
            if (editingAutoId) {
                await callRPC('update_automation', { ...payload, id: editingAutoId });
            } else {
                await callRPC('create_automation', payload);
            }
            setShowAutoForm(false);
            setEditingAutoId(null);
            setAutoForm({ ...autoForm, name: '', targets: '' });
            loadAll();
        } catch (err: any) {
            alert(err.message);
        } finally {
            setIsCreatingAuto(false);
        }
    };

    const handleEditAutomation = (auto: any) => {
        setAutoForm({
            name: auto.name || '',
            targets: Array.isArray(auto.targets) ? auto.targets.join('\n') : '',
            frequency: auto.frequency || 'daily',
            filters: auto.filters || { stories: true, spotlights: true, highlights: false, episodes: false },
            ffmpegOptimize: auto.pipeline?.ffmpegOptimize || false,
            ffmpegAddLogo: auto.pipeline?.ffmpegAddLogo || false,
            watermarkPath: auto.pipeline?.watermarkPath || '',
            watermarkPosition: auto.pipeline?.watermarkPosition || 'bottom_right',
            minDuration: auto.executionRules?.minDuration || 0,
            maxDuration: auto.executionRules?.maxDuration || 0,
            globalDeduplication: auto.executionRules?.globalDeduplication ?? true,
            exportMetadata: auto.executionRules?.exportMetadata ?? true,
            muteAudio: auto.pipeline?.muteAudio || false,
            smartSync: auto.executionRules?.smartSync ?? true,
            pacing: auto.executionRules?.pacing || '1_min',
        });
        setEditingAutoId(auto.id);
        setShowAutoForm(true);
    };

    const handleToggleAutomation = async (id: string, active: boolean) => {
        try { await callRPC('toggle_automation', { id, active }); loadAll(); }
        catch (err: any) { alert(err.message); }
    };

    const handleDeleteAutomation = async (id: string) => {
        try { await callRPC('delete_automation', { id }); loadAll(); }
        catch (err: any) { alert(err.message); }
    };

    return (
        <div className="max-w-3xl mx-auto space-y-5">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-xl sm:text-2xl font-black text-white">{__('Automations')}</h1>
                    <p className="text-xs mt-1" style={{ color: 'rgba(255,255,255,0.35)' }}>{__('Recurring campaigns that run automatically in the background')}</p>
                </div>
                {!showAutoForm && (
                    <Button onClick={() => {
                        setEditingAutoId(null);
                        setAutoForm({
                            name: '', targets: '', frequency: 'daily',
                            filters: { stories: true, spotlights: true, highlights: false, episodes: false },
                            ffmpegOptimize: false, ffmpegAddLogo: false, watermarkPath: '', watermarkPosition: 'bottom_right',
                            minDuration: 0, maxDuration: 0, globalDeduplication: true, exportMetadata: true,
                            muteAudio: false, smartSync: true, pacing: '1_min',
                        });
                        setShowAutoForm(true);
                    }} className="gap-2 h-11 text-xs font-bold" style={{ background: 'rgba(245,158,11,0.15)', color: '#f59e0b' }}>
                        <Play className="w-3.5 h-3.5 fill-current" /> {__('New Campaign')}
                    </Button>
                )}
            </div>

            {showAutoForm ? (
                <div className="rounded-2xl border p-4 sm:p-6 space-y-5" style={{ background: '#13161f', borderColor: 'rgba(255,255,255,0.06)' }}>
                    <div className="flex items-center justify-between mb-4 border-b pb-4" style={{ borderColor: 'rgba(255,255,255,0.06)' }}>
                        <h2 className="text-lg font-bold text-white">{editingAutoId ? __('Edit Automation Campaign') : __('Create Automation Campaign')}</h2>
                        <Button variant="ghost" size="sm" onClick={() => { setShowAutoForm(false); setEditingAutoId(null); }} className="text-slate-400 hover:text-white">{__('Cancel')}</Button>
                    </div>

                    {/* Name & Frequency */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <label className="text-[10px] font-black uppercase tracking-widest" style={{ color: 'rgba(255,255,255,0.35)' }}>{__('Campaign Name')}</label>
                            <Input
                                value={autoForm.name}
                                onChange={e => setAutoForm(p => ({ ...p, name: e.target.value }))}
                                placeholder={__('e.g. Competitor Tracking')}
                                className="h-11 bg-transparent border-white/10 text-white"
                            />
                        </div>
                        <div className="space-y-2">
                            <label className="text-[10px] font-black uppercase tracking-widest" style={{ color: 'rgba(255,255,255,0.35)' }}>{__('Run Frequency')}</label>
                            <select 
                                className="w-full h-11 rounded-md px-3 text-sm bg-transparent border outline-none"
                                style={{ borderColor: 'rgba(255,255,255,0.1)', color: '#fff' }}
                                value={autoForm.frequency}
                                onChange={e => setAutoForm(p => ({ ...p, frequency: e.target.value }))}
                            >
                                <option value="hourly" className="bg-slate-900">{__('Every Hour')}</option>
                                <option value="every_6_hours" className="bg-slate-900">{__('Every 6 Hours')}</option>
                                <option value="daily" className="bg-slate-900">{__('Daily')}</option>
                                <option value="weekly" className="bg-slate-900">{__('Weekly')}</option>
                            </select>
                        </div>
                    </div>

                    {/* Targets */}
                    <div className="space-y-2">
                        <label className="text-[10px] font-black uppercase tracking-widest" style={{ color: 'rgba(255,255,255,0.35)' }}>{__('Target Profiles')}</label>
                        <Textarea
                            value={autoForm.targets}
                            onChange={e => setAutoForm(p => ({ ...p, targets: e.target.value }))}
                            placeholder={__('Paste up to 1000 usernames (one per line)')}
                            className="w-full p-3 text-sm min-h-[120px] bg-transparent border-white/10 resize-y text-white"
                        />
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {/* Execution Rules */}
                        <div className="space-y-3">
                            <label className="text-[10px] font-black uppercase tracking-widest" style={{ color: 'rgba(255,255,255,0.35)' }}>{__('Execution Rules')}</label>
                            <div className="space-y-2">
                                <div className="flex items-center justify-between p-3 rounded-lg border" style={{ background: 'rgba(255,255,255,0.02)', borderColor: 'rgba(255,255,255,0.06)' }}>
                                    <div>
                                        <div className="text-xs font-bold text-white">{__('Smart Sync')}</div>
                                        <div className="text-[10px] text-slate-500 mt-0.5">{__('Only download new media')}</div>
                                    </div>
                                    <Switch checked={autoForm.smartSync} onCheckedChange={v => setAutoForm(p => ({ ...p, smartSync: v }))} />
                                </div>
                                <div className="flex items-center justify-between p-3 rounded-lg border" style={{ background: 'rgba(255,255,255,0.02)', borderColor: 'rgba(255,255,255,0.06)' }}>
                                    <span className="text-xs font-bold text-white">{__('Delay between Profiles')}</span>
                                    <select 
                                        className="h-7 rounded text-xs bg-transparent border outline-none text-white text-right"
                                        style={{ borderColor: 'rgba(255,255,255,0.1)' }}
                                        value={autoForm.pacing}
                                        onChange={e => setAutoForm(p => ({ ...p, pacing: e.target.value }))}
                                    >
                                        <option value="none" className="bg-slate-900">{__('No delay')}</option>
                                        <option value="1_min" className="bg-slate-900">{__('1 Minute')}</option>
                                        <option value="5_min" className="bg-slate-900">{__('5 Minutes')}</option>
                                        <option value="random" className="bg-slate-900">{__('Random (1-5m)')}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {/* Pipeline Rules */}
                        <div className="space-y-3">
                            <label className="text-[10px] font-black uppercase tracking-widest" style={{ color: 'rgba(255,255,255,0.35)' }}>{__('Pipeline Rules')}</label>
                            <div className="space-y-2">
                                <div className="flex items-center justify-between p-3 rounded-lg border" style={{ background: 'rgba(255,255,255,0.02)', borderColor: 'rgba(255,255,255,0.06)' }}>
                                    <span className="text-xs font-bold text-white">{__('Optimize / Compress')}</span>
                                    <Switch checked={autoForm.ffmpegOptimize} onCheckedChange={v => setAutoForm(p => ({ ...p, ffmpegOptimize: v }))} />
                                </div>
                                <div className="flex items-center justify-between p-3 rounded-lg border" style={{ background: 'rgba(255,255,255,0.02)', borderColor: 'rgba(255,255,255,0.06)' }}>
                                    <span className="text-xs font-bold text-white">{__('Mute Audio')}</span>
                                    <Switch checked={autoForm.muteAudio} onCheckedChange={v => setAutoForm(p => ({ ...p, muteAudio: v }))} />
                                </div>
                                <div className="p-3 rounded-lg border space-y-3" style={{ background: 'rgba(255,255,255,0.02)', borderColor: 'rgba(255,255,255,0.06)' }}>
                                    <div className="flex items-center justify-between">
                                        <span className="text-xs font-bold text-white">{__('Add Watermark')}</span>
                                        <Switch checked={autoForm.ffmpegAddLogo} onCheckedChange={v => setAutoForm(p => ({ ...p, ffmpegAddLogo: v }))} />
                                    </div>
                                    {autoForm.ffmpegAddLogo && (
                                        <div className="space-y-2 pt-2 border-t border-white/10">
                                            <Input 
                                                value={autoForm.watermarkPath} 
                                                onChange={e => setAutoForm(p => ({ ...p, watermarkPath: e.target.value }))}
                                                placeholder={__('Logo Path (e.g. C:\\logo.png)')}
                                                className="h-8 text-xs bg-transparent border-white/10 text-white"
                                            />
                                            <select 
                                                className="w-full h-8 rounded-md px-2 text-xs bg-transparent border outline-none text-white"
                                                style={{ borderColor: 'rgba(255,255,255,0.1)' }}
                                                value={autoForm.watermarkPosition}
                                                onChange={e => setAutoForm(p => ({ ...p, watermarkPosition: e.target.value }))}
                                            >
                                                <option value="top_left" className="bg-slate-900">{__('Top Left')}</option>
                                                <option value="top_right" className="bg-slate-900">{__('Top Right')}</option>
                                                <option value="bottom_left" className="bg-slate-900">{__('Bottom Left')}</option>
                                                <option value="bottom_right" className="bg-slate-900">{__('Bottom Right')}</option>
                                            </select>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Smart Filters & Data */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                        <div className="space-y-3">
                            <label className="text-[10px] font-black uppercase tracking-widest" style={{ color: 'rgba(255,255,255,0.35)' }}>{__('Smart Content Filters')}</label>
                            <div className="space-y-2">
                                <div className="flex items-center justify-between p-3 rounded-lg border" style={{ background: 'rgba(255,255,255,0.02)', borderColor: 'rgba(255,255,255,0.06)' }}>
                                    <span className="text-xs font-bold text-white">{__('Min Duration (sec)')}</span>
                                    <Input type="number" value={autoForm.minDuration} onChange={e => setAutoForm(p => ({ ...p, minDuration: parseInt(e.target.value) || 0 }))} className="w-16 h-7 text-xs text-right bg-transparent border-white/10 text-white" />
                                </div>
                                <div className="flex items-center justify-between p-3 rounded-lg border" style={{ background: 'rgba(255,255,255,0.02)', borderColor: 'rgba(255,255,255,0.06)' }}>
                                    <span className="text-xs font-bold text-white">{__('Max Duration (sec)')}</span>
                                    <Input type="number" value={autoForm.maxDuration} onChange={e => setAutoForm(p => ({ ...p, maxDuration: parseInt(e.target.value) || 0 }))} className="w-16 h-7 text-xs text-right bg-transparent border-white/10 text-white" />
                                </div>
                                <div className="flex items-center justify-between p-3 rounded-lg border" style={{ background: 'rgba(255,255,255,0.02)', borderColor: 'rgba(255,255,255,0.06)' }}>
                                    <span className="text-xs font-bold text-white">{__('Global Deduplication')}</span>
                                    <Switch checked={autoForm.globalDeduplication} onCheckedChange={v => setAutoForm(p => ({ ...p, globalDeduplication: v }))} />
                                </div>
                            </div>
                        </div>

                        <div className="space-y-3">
                            <label className="text-[10px] font-black uppercase tracking-widest" style={{ color: 'rgba(255,255,255,0.35)' }}>{__('Data Mining')}</label>
                            <div className="space-y-2">
                                <div className="flex items-center justify-between p-3 rounded-lg border" style={{ background: 'rgba(255,255,255,0.02)', borderColor: 'rgba(255,255,255,0.06)' }}>
                                    <div>
                                        <div className="text-xs font-bold text-white">{__('Export Metadata CSV')}</div>
                                        <div className="text-[10px] text-slate-500 mt-0.5">{__('Saves urls, timestamps, and details')}</div>
                                    </div>
                                    <Switch checked={autoForm.exportMetadata} onCheckedChange={v => setAutoForm(p => ({ ...p, exportMetadata: v }))} />
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Media Filters */}
                    <div className="space-y-3 pt-2">
                        <label className="text-[10px] font-black uppercase tracking-widest" style={{ color: 'rgba(255,255,255,0.35)' }}>{__('Media Types')}</label>
                        <div className="flex flex-wrap gap-2">
                            {['stories', 'spotlights', 'highlights', 'episodes'].map(f => {
                                const active = (autoForm.filters as any)[f];
                                return (
                                    <Button
                                        variant="outline"
                                        key={f}
                                        onClick={() => setAutoForm(p => ({ ...p, filters: { ...p.filters, [f]: !active } }))}
                                        className="h-8 text-xs capitalize"
                                        style={{
                                            background: active ? 'rgba(245,158,11,0.1)' : 'transparent',
                                            borderColor: active ? 'rgba(245,158,11,0.4)' : 'rgba(255,255,255,0.1)',
                                            color: active ? '#f59e0b' : 'rgba(255,255,255,0.4)',
                                        }}
                                    >
                                        {__(f)}
                                    </Button>
                                );
                            })}
                        </div>
                    </div>

                    <Button
                        onClick={handleCreateAutomation}
                        disabled={isCreatingAuto || !autoForm.name || !autoForm.targets}
                        className="w-full h-12 font-black text-sm uppercase tracking-widest gap-2 mt-4"
                        style={{ background: 'linear-gradient(135deg, #f59e0b, #b45309)', color: '#000' }}
                    >
                        {isCreatingAuto ? <RefreshCw className="w-4 h-4 animate-spin" /> : <Play className="w-4 h-4 fill-current" />}
                        {__('Save & Schedule Campaign')}
                    </Button>
                </div>
            ) : (
                <>
                    {automations.length === 0 ? (
                        <div className="text-center py-16 px-4 rounded-2xl border border-dashed" style={{ borderColor: 'rgba(255,255,255,0.1)', background: 'rgba(255,255,255,0.02)' }}>
                            <div className="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style={{ background: 'rgba(255,255,255,0.05)' }}>
                                <Clock className="w-6 h-6 text-slate-400" />
                            </div>
                            <h3 className="text-white font-bold mb-2">{__('No automations running')}</h3>
                            <p className="text-xs text-slate-400 max-w-sm mx-auto mb-6">
                                {__('Create a campaign to automatically scrape profiles in the background.')}
                            </p>
                            <Button onClick={() => setShowAutoForm(true)} className="gap-2" style={{ background: 'rgba(245,158,11,0.15)', color: '#f59e0b' }}>
                                <Play className="w-4 h-4 fill-current" /> {__('Create Campaign')}
                            </Button>
                        </div>
                    ) : (
                        <div className="space-y-3">
                            {automations.map(auto => (
                                <div key={auto.id} className="p-4 rounded-2xl border" style={{ background: '#13161f', borderColor: 'rgba(255,255,255,0.06)' }}>
                                    <div className="flex items-start justify-between">
                                        <div>
                                            <div className="font-bold text-white text-sm">{auto.name}</div>
                                            <div className="flex items-center gap-3 mt-1.5 text-xs" style={{ color: 'rgba(255,255,255,0.4)' }}>
                                                <span className="capitalize text-amber-500 font-semibold flex items-center gap-1">
                                                    <RefreshCw className="w-3 h-3" /> {__(auto.frequency.replace(/_/g, ' '))}
                                                </span>
                                                <span>·</span>
                                                <span className="flex items-center gap-1">
                                                    <Users className="w-3 h-3" /> {auto.targets?.length || 0} {__('Targets')}
                                                </span>
                                                <span>·</span>
                                                <span className={`flex items-center gap-1 font-semibold ${auto.status === 'active' ? 'text-emerald-400' : 'text-slate-500'}`}>
                                                    <Activity className="w-3 h-3" /> {auto.status === 'active' ? __('Running') : __('Paused')}
                                                </span>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Switch 
                                                checked={auto.status === 'active'} 
                                                onCheckedChange={v => handleToggleAutomation(auto.id, v)} 
                                            />
                                            <Dialog>
                                                <DialogTrigger className="h-8 w-8 p-0 text-slate-400 hover:text-white hover:bg-white/10 ml-2 rounded-md inline-flex items-center justify-center transition-colors">
                                                    <span className="sr-only">{__('Open menu')}</span>
                                                    <MoreHorizontal className="h-4 w-4" />
                                                </DialogTrigger>
                                                <DialogContent className="sm:max-w-xs bg-slate-900 border-white/10">
                                                    <DialogHeader>
                                                        <DialogTitle className="text-white">{__('Actions')}</DialogTitle>
                                                    </DialogHeader>
                                                    <div className="flex flex-col gap-2 py-2">
                                                        <Button variant="outline" className="justify-start border-white/10 text-white hover:bg-white/10" onClick={() => handleEditAutomation(auto)}>
                                                            {__('Edit')}
                                                        </Button>
                                                        <Button variant="destructive" className="justify-start" onClick={() => handleDeleteAutomation(auto.id)}>
                                                            {__('Delete')}
                                                        </Button>
                                                    </div>
                                                </DialogContent>
                                            </Dialog>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </>
            )}
        </div>
    );
}

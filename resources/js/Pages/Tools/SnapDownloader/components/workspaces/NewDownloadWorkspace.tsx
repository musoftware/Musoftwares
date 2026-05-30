import React, { useState } from 'react';
import { Search, Play, RefreshCw, Lock, Video, Zap, Film, FileVideo, CheckCircle2 } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Textarea } from '@/Components/ui/textarea';
import { __ } from '@/lib/i18n';
import { WorkspaceType } from '../../types/snapdownloader.types';

export function NewDownloadWorkspace({
    callRPC,
    loadAll,
    setActiveWorkspace
}: {
    callRPC: (action: string, data?: any) => Promise<any>;
    loadAll: () => Promise<void>;
    setActiveWorkspace: (ws: WorkspaceType) => void;
}) {
    const [targetUrl, setTargetUrl] = useState('');
    const [filters, setFilters] = useState({ stories: true, spotlights: true, highlights: false, episodes: false });
    const [isQueuing, setIsQueuing] = useState(false);
    const [formError, setFormError] = useState('');

    const handleQueueAdd = async () => {
        if (!targetUrl.trim()) return;
        setFormError('');
        setIsQueuing(true);
        try {
            const targets = targetUrl.split('\n').map(t => t.trim()).filter(t => t);
            for (const target of targets) {
                await callRPC('queue_add', { target, filters, concurrent: 5 });
            }
            setTargetUrl('');
            setActiveWorkspace('active');
            loadAll();
        } catch (err: any) {
            setFormError(err.message);
        } finally {
            setIsQueuing(false);
        }
    };

    return (
        <div className="max-w-2xl mx-auto space-y-5">
            <div>
                <h1 className="text-xl sm:text-2xl font-black text-white">{__('New Download')}</h1>
                <p className="text-xs mt-1" style={{ color: 'rgba(255,255,255,0.35)' }}>{__('Queue a Snapchat profile for saving')}</p>
            </div>

            {/* Form Card */}
            <div className="rounded-2xl border p-4 sm:p-6 space-y-5" style={{ background: '#13161f', borderColor: 'rgba(255,255,255,0.06)' }}>
                {/* URL Input */}
                <div className="space-y-2">
                    <label className="text-[10px] font-black uppercase tracking-widest" style={{ color: 'rgba(255,255,255,0.35)' }}>{__('Target Profile')}</label>
                    <div className="relative">
                        <Search className="absolute left-4 top-4 w-4 h-4" style={{ color: 'rgba(255,255,255,0.25)' }} />
                        <Textarea
                            value={targetUrl}
                            onChange={e => { setTargetUrl(e.target.value); setFormError(''); }}
                            placeholder={__('Usernames or Snapchat URLs (one per line)')}
                            className="w-full pl-11 py-3 text-sm font-medium min-h-[100px] resize-y"
                            style={{
                                background: 'rgba(255,255,255,0.04)',
                                borderColor: formError ? '#f43f5e' : 'rgba(255,255,255,0.08)',
                                color: '#fff',
                                fontSize: '16px', // prevents iOS zoom on focus
                            }}
                        />
                    </div>
                    {formError && <p className="text-xs text-rose-500">{formError}</p>}
                </div>

                {/* Media Filters */}
                <div className="space-y-3">
                    <label className="text-[10px] font-black uppercase tracking-widest" style={{ color: 'rgba(255,255,255,0.35)' }}>{__('Media Types')}</label>
                    <div className="grid grid-cols-2 gap-2.5">
                        {[
                            { id: 'stories',    label: __('Stories'),    icon: <Video className="w-4 h-4" /> },
                            { id: 'spotlights', label: __('Spotlights'), icon: <Zap className="w-4 h-4" /> },
                            { id: 'highlights', label: __('Highlights'), icon: <Film className="w-4 h-4" /> },
                            { id: 'episodes',   label: __('Episodes'),   icon: <FileVideo className="w-4 h-4" /> },
                        ].map(f => {
                            const active = (filters as any)[f.id];
                            return (
                                <Button
                                    variant="outline"
                                    key={f.id}
                                    onClick={() => setFilters(p => ({ ...p, [f.id]: !(p as any)[f.id] }))}
                                    className="justify-start gap-3 h-auto p-3.5"
                                    style={{
                                        background: active ? 'rgba(245,158,11,0.1)' : 'rgba(255,255,255,0.02)',
                                        borderColor: active ? 'rgba(245,158,11,0.4)' : 'rgba(255,255,255,0.06)',
                                        color: active ? '#f59e0b' : 'rgba(255,255,255,0.4)',
                                    }}
                                >
                                    {f.icon}
                                    <span className="text-xs font-bold">{f.label}</span>
                                    {active && <CheckCircle2 className="w-3.5 h-3.5 ml-auto" />}
                                </Button>
                            );
                        })}
                    </div>
                </div>

                {/* CTA */}
                <Button
                    onClick={handleQueueAdd}
                    disabled={isQueuing || !targetUrl.trim()}
                    className="w-full h-14 font-black text-sm uppercase tracking-widest gap-3"
                    style={{ background: 'linear-gradient(135deg, #f59e0b, #b45309)', color: '#000' }}
                >
                    {isQueuing ? <RefreshCw className="w-4 h-4 animate-spin" /> : <Play className="w-4 h-4 fill-current" />}
                    {isQueuing ? __('Adding...') : __('Start Download')}
                </Button>
            </div>

            {/* Privacy Banner */}
            <div className="rounded-2xl p-4 sm:p-5 flex items-start gap-4" style={{ background: 'rgba(245,158,11,0.07)', border: '1px solid rgba(245,158,11,0.15)' }}>
                <div className="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style={{ background: 'rgba(245,158,11,0.15)' }}>
                    <Lock className="w-4 h-4" style={{ color: '#f59e0b' }} />
                </div>
                <div>
                    <div className="font-bold text-white text-xs">{__('Fast & Private')}</div>
                    <p className="text-[11px] mt-1 leading-relaxed" style={{ color: 'rgba(255,255,255,0.4)' }}>
                        {__('Your files are saved directly to your chosen folder. Nothing leaves your computer.')}
                    </p>
                </div>
            </div>
        </div>
    );
}

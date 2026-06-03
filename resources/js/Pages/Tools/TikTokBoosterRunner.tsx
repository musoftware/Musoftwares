import React, { useState, useEffect, useRef } from 'react';
import { Database, Activity, RefreshCw, AlertCircle, Play, Settings as SettingsIcon, Trash2, CheckCircle, Video, Share2, Heart, Star } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';

import { useRuntimeWS } from '@/hooks/useRuntimeWS';
import { RuntimePluginModals } from '@/Components/Tools/RuntimePluginModals';
import { __ } from '@/lib/i18n';

export default function TikTokBoosterRunner({ tool }: any) {
    const { connected: agentConnected, callRPC, installingPlugin, loginRequired, setLoginRequired } = useRuntimeWS('tiktok-booster');
    
    const [url, setUrl] = useState('');
    const [type, setType] = useState<'views' | 'shares' | 'favorites' | 'hearts'>('views');
    const [campaigns, setCampaigns] = useState<any[]>([]);
    const [loading, setLoading] = useState(false);
    
    const fetchCampaigns = async () => {
        try {
            const res: any = await callRPC('list_campaigns');
            if (res && res.campaigns) {
                setCampaigns(res.campaigns);
            }
        } catch (err) {
            console.error('Failed to fetch campaigns', err);
        }
    };

    useEffect(() => {
        if (agentConnected) {
            fetchCampaigns();
            const interval = setInterval(fetchCampaigns, 5000);
            return () => clearInterval(interval);
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [agentConnected]);

    const handleStartCampaign = async () => {
        if (!url) return;
        setLoading(true);
        try {
            await callRPC('add_campaign', { url, type });
            setUrl('');
            await fetchCampaigns();
        } catch (err) {
            console.error('Failed to start campaign', err);
        } finally {
            setLoading(false);
        }
    };

    const handleDeleteCampaign = async (id: number) => {
        try {
            await callRPC('remove_campaign', { id });
            await fetchCampaigns();
        } catch (err) {
            console.error('Failed to delete campaign', err);
        }
    };

    if (!agentConnected) {
        return (
            <div className="min-h-screen bg-[#F8FAFC] flex items-center justify-center font-sans">
                <div className="text-center space-y-4">
                    <div className="w-10 h-10 border-2 border-slate-900 border-t-transparent rounded-full animate-spin mx-auto" />
                    <p className="text-sm font-semibold text-slate-600">{__('general.syncing_with_local_runtime_agent')}</p>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-[#F8FAFC] text-slate-900 font-sans flex flex-col antialiased">
            <RuntimePluginModals 
                installingPlugin={installingPlugin} 
                loginRequired={loginRequired} 
                setLoginRequired={setLoginRequired} 
            />
            <header className="h-16 border-b border-slate-200 bg-white/80 backdrop-blur-md flex items-center justify-between px-8 sticky top-0 z-30">
                <div className="flex items-center gap-6">
                    <div className="flex items-center gap-2.5">
                        <div className="w-6.5 h-6.5 bg-gradient-to-tr from-pink-500 to-rose-500 rounded-lg flex items-center justify-center shadow-md shadow-pink-500/20">
                            <Activity className="w-3.5 h-3.5 text-white" />
                        </div>
                        <span className="font-bold text-sm tracking-tight bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent">{__('general.tiktok_views_booster')}</span>
                    </div>
                </div>
                <div className="flex items-center gap-4">
                    <div className="flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-100 text-emerald-800">
                        <div className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
                        <span className="text-[10px] font-bold uppercase tracking-wider font-mono">{__('general.engine_ready')}</span>
                    </div>
                </div>
            </header>

            <div className="flex-1 overflow-y-auto p-8 max-w-5xl mx-auto w-full space-y-8">
                <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-6">
                    <h2 className="text-lg font-bold">{__('general.new_campaign')}</h2>
                    
                    <div className="flex border border-slate-200 rounded-xl overflow-hidden p-1 gap-1 w-fit bg-slate-50 flex-wrap">
                        {(['views', 'shares', 'favorites', 'hearts'] as const).map(m => (
                            <Button
                                variant={type === m ? 'default' : 'ghost'}
                                key={m}
                                onClick={() => setType(m)}
                                className={`h-8 px-4 text-xs font-bold transition-all ${type === m ? 'bg-slate-900 text-white shadow-sm hover:bg-slate-800' : 'text-slate-500 hover:text-slate-900 hover:bg-white'}`}
                            >
                                {m === 'views' && <Video className="w-3.5 h-3.5 mr-2" />}
                                {m === 'shares' && <Share2 className="w-3.5 h-3.5 mr-2" />}
                                {m === 'favorites' && <Star className="w-3.5 h-3.5 mr-2" />}
                                {m === 'hearts' && <Heart className="w-3.5 h-3.5 mr-2" />}
                                {m.charAt(0).toUpperCase() + m.slice(1)}
                            </Button>
                        ))}
                    </div>

                    <div className="space-y-2">
                        <label className="text-xs font-bold text-slate-700 uppercase tracking-wide">{__('general.tiktok_video_url')}</label>
                        <Input
                            placeholder={__('general.https_www_tiktok_com_user_video_1234567890')}
                            value={url}
                            onChange={(e) => setUrl(e.target.value)}
                            className="font-mono text-sm h-11 bg-slate-50 w-full"
                        />
                    </div>
                    
                    <Button
                        onClick={handleStartCampaign}
                        disabled={loading || !url}
                        className="h-11 px-8 bg-gradient-to-r from-pink-500 to-rose-500 text-white rounded-xl text-sm font-bold hover:opacity-90 shadow-md shadow-pink-500/20 gap-2"
                    >
                        {loading ? <RefreshCw className="w-4 h-4 animate-spin" /> : <Play className="w-4 h-4 fill-white" />}
                        Start Campaign
                    </Button>
                </div>

                <div className="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                    <div className="p-6 border-b border-slate-100 flex items-center justify-between">
                        <h2 className="text-lg font-bold">{__('general.active_past_campaigns')}</h2>
                        <Button variant="ghost" size="sm" onClick={fetchCampaigns}>
                            <RefreshCw className="w-4 h-4 mr-2" /> Refresh
                        </Button>
                    </div>
                    
                    {campaigns.length === 0 ? (
                        <div className="p-12 text-center text-slate-500">{__('general.no_campaigns_yet_1')}</div>
                    ) : (
                        <table className="w-full text-sm text-left">
                            <thead className="bg-slate-50 border-b border-slate-200 text-xs text-slate-500 font-semibold uppercase">
                                <tr>
                                    <th className="px-6 py-4 tracking-wider">URL</th>
                                    <th className="px-6 py-4 tracking-wider">Type</th>
                                    <th className="px-6 py-4 tracking-wider">Status</th>
                                    <th className="px-6 py-4 tracking-wider">Counter</th>
                                    <th className="px-6 py-4 tracking-wider">Cooldown</th>
                                    <th className="px-6 py-4 tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {campaigns.map((camp) => (
                                    <tr key={camp.id} className="hover:bg-slate-50 transition-colors">
                                        <td className="px-6 py-4 whitespace-nowrap text-slate-600 font-mono text-xs overflow-hidden text-ellipsis max-w-[200px]" title={camp.url}>
                                            {camp.url}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-slate-800 font-medium text-xs capitalize">
                                            {camp.type}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <Badge variant="outline" className={camp.status === 'processing' ? 'bg-blue-50 text-blue-700 border-blue-200 animate-pulse' : camp.status === 'cooldown' ? 'bg-orange-50 text-orange-700 border-orange-200' : 'bg-slate-100 text-slate-600'}>
                                                {camp.status}
                                            </Badge>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap font-bold text-emerald-600">
                                            +{camp.counter}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-xs text-slate-500">
                                            {camp.cooldown > 0 ? `${camp.cooldown}s` : '-'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-right">
                                            <Button variant="ghost" size="sm" onClick={() => handleDeleteCampaign(camp.id)} className="text-rose-500 hover:text-rose-700 hover:bg-rose-50">
                                                <Trash2 className="w-4 h-4" />
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>
        </div>
    );
}

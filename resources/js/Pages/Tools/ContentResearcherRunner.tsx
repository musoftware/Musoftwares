import React, { useState, useEffect, useCallback } from 'react';
import {
    Search, Play, Square, Download, Database,
    AlertCircle, CheckCircle, RefreshCw, ChevronDown, ChevronUp, Settings, Trash2, ExternalLink, Loader2
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Label } from '@/Components/ui/label';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/Components/ui/tabs';

import { useRuntimeWS } from '@/hooks/useRuntimeWS';
import { RuntimePluginModals } from '@/Components/Tools/RuntimePluginModals';
import { __ } from '@/lib/i18n';

const getRuntimeHost = () => typeof window !== 'undefined' ? (window.localStorage.getItem('musoftware_runtime_host') || '127.0.0.1') : '127.0.0.1';
const getRuntimeHttp = () => `http://${getRuntimeHost()}:18400`;

export default function ContentResearcherRunner({ tool, subscription, runtimePort, pluginSlug }: any) {
    const [keyword, setKeyword] = useState('');
    const [engine, setEngine] = useState<'duckduckgo' | 'google' | 'bing'>('duckduckgo');
    const [recursive, setRecursive] = useState(false);
    
    const [status, setStatus] = useState<'idle' | 'running' | 'done' | 'error'>('idle');
    const [progress, setProgress] = useState(0);
    const [progressMsg, setProgressMsg] = useState('');
    
    const [results, setResults] = useState<any[]>([]);
    const [logs, setLogs] = useState<string[]>([]);
    
    const [errorMsg, setError] = useState('');
    const [taskId, setTaskId] = useState<string | null>(null);

    const [showAdvanced, setShowAdvanced] = useState(false);
    
    // DB History State
    const [campaigns, setCampaigns] = useState<any[]>([]);
    const [selectedCampaign, setSelectedCampaign] = useState<any>(null);
    const [campaignData, setCampaignData] = useState<any>(null);
    const [loadingCampaigns, setLoadingCampaigns] = useState(false);
    const [loadingCampaignData, setLoadingCampaignData] = useState(false);

    // Broadcast event handler for real-time updates
    const onBroadcast = useCallback((event: string, data: any) => {
        if (event === 'link_scraped' && data?.result) {
            setResults(prev => [data.result, ...prev]);
        }
        if (event === 'task.progress' && data?.taskId === taskId) {
            if (data.percent !== undefined) setProgress(data.percent);
            if (data.message) {
                setProgressMsg(data.message);
                setLogs(prev => [data.message, ...prev].slice(0, 50));
            }
        }
        if (event === 'task.done' && data?.taskId === taskId) {
            setStatus('done');
            setProgressMsg('Campaign completed!');
            loadCampaigns();
        }
        if ((event === 'task.failed' || event === 'task.error') && data?.taskId === taskId) {
            setStatus('error');
            setError(data?.error || 'Task failed unexpectedly.');
        }
    }, [taskId]);

    const { connected: agentConnected, callRPC, installingPlugin, loginRequired, setLoginRequired } = useRuntimeWS('content-researcher', onBroadcast);

    // Load campaigns on connect
    useEffect(() => {
        if (agentConnected) {
            loadCampaigns();
        }
    }, [agentConnected]);

    const loadCampaigns = async () => {
        setLoadingCampaigns(true);
        try {
            const data: any = await callRPC('get_campaigns', { limit: 100 });
            if (data?.campaigns) setCampaigns(data.campaigns);
        } catch (e) {
            console.error('Failed to load campaigns:', e);
        } finally {
            setLoadingCampaigns(false);
        }
    };

    const loadCampaignData = async (id: number) => {
        setLoadingCampaignData(true);
        try {
            const data: any = await callRPC('get_campaign_data', { id });
            setSelectedCampaign(data?.campaign || null);
            setCampaignData(data || null);
        } catch (e) {
            console.error('Failed to load campaign data:', e);
        } finally {
            setLoadingCampaignData(false);
        }
    };

    const deleteCampaign = async (id: number, e: React.MouseEvent) => {
        e.stopPropagation();
        if (!confirm('Are you sure you want to delete this campaign?')) return;
        try {
            await callRPC('delete_campaign', { id });
            if (selectedCampaign?.id === id) {
                setSelectedCampaign(null);
                setCampaignData(null);
            }
            loadCampaigns();
        } catch (e) {
            console.error('Delete failed:', e);
        }
    };

    const handleStart = async () => {
        if (!keyword.trim()) { setError('Keyword is required'); setStatus('error'); return; }
        try {
            setStatus('running');
            setProgress(0);
            setProgressMsg('Initializing...');
            setResults([]);
            setError('');
            setSelectedCampaign(null);
            setCampaignData(null);
            
            // Task run uses HTTP endpoint (not RPC)
            const res = await fetch(`${getRuntimeHttp()}/plugins/content-researcher/run`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ params: { keyword, engine, recursive } }),
            });
            
            const data = await res.json();
            if (res.ok && data.taskId) {
                setTaskId(data.taskId);
            } else {
                setStatus('error');
                setError(data.error || 'Failed to start task.');
            }
        } catch (e: any) {
            setStatus('error');
            setError('Runtime disconnected or error occurred.');
        }
    };

    const handleStop = async () => {
        if (!taskId) return;
        try {
            await fetch(`${getRuntimeHttp()}/tasks/${taskId}/stop`, { method: 'POST' });
            setStatus('done');
            setProgressMsg('Stopped by user');
            loadCampaigns();
        } catch (e) { console.error(e); }
    };

    const handleExportCSV = () => {
        const rows = selectedCampaign ? (campaignData?.pages || []) : results;
        if (rows.length === 0) return alert('No data to export.');
        
        const headers = ['Domain', 'URL', 'Title', 'H1', 'Word Count', 'FAQ Schema', 'Snippet'];
        const csvRows = [headers.join(',')];
        
        for (const r of rows) {
            const m = r.metrics || r;
            const row = [
                `"${(r.domain || '').replace(/"/g, '""')}"`,
                `"${(r.url || '').replace(/"/g, '""')}"`,
                `"${(m.title || '').replace(/"/g, '""')}"`,
                `"${(Array.isArray(m.h1) ? m.h1.join(' | ') : (m.h1 || r.h1 || '')).replace(/[\[\]"]/g, '')}"`,
                m.word_count || 0,
                m.has_faq_schema ? 'Yes' : 'No',
                `"${(r.snippet || m.meta_description || '').replace(/"/g, '""')}"`,
            ];
            csvRows.push(row.join(','));
        }
        
        const blob = new Blob([csvRows.join('\n')], { type: 'text/csv' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `content-research-${selectedCampaign?.keyword || keyword || 'export'}.csv`;
        a.click();
        URL.revokeObjectURL(url);
    };

    const formatDate = (dateStr: string) => {
        try {
            // SQLite returns 'YYYY-MM-DD HH:MM:SS', ensure it parses correctly
            const d = new Date(dateStr.includes('T') ? dateStr : dateStr.replace(' ', 'T') + 'Z');
            return isNaN(d.getTime()) ? dateStr : d.toLocaleDateString();
        } catch { return dateStr; }
    };

    // Display data: either from selected campaign or live results
    const displayData = selectedCampaign ? (campaignData?.pages || []) : results;
    const hasData = displayData.length > 0;
    const showTable = hasData || status === 'running';

    if (!agentConnected) {
        return (
            <div className="min-h-screen bg-[#F8FAFC] flex items-center justify-center font-sans">
                <div className="text-center space-y-4">
                    <div className="w-10 h-10 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin mx-auto" />
                    <p className="text-sm font-semibold text-slate-600">{__('general.syncing_with_local_runtime_agent')}</p>
                    <p className="text-xs text-slate-400">{__('general.make_sure_your_musoftware_runtime_is_running_on_your_machine')}</p>
                </div>
            </div>
        );
    }

    return (
        <div className="flex h-screen bg-slate-50 overflow-hidden font-sans antialiased text-slate-900">
            <RuntimePluginModals 
                installingPlugin={installingPlugin} 
                loginRequired={loginRequired} 
                setLoginRequired={setLoginRequired} 
            />

            {/* Sidebar */}
            <div className="w-80 bg-white border-r border-slate-200 shadow-[2px_0_10px_rgba(0,0,0,0.02)] flex flex-col z-10 overflow-y-auto">
                <div className="px-6 py-8">
                    <div className="w-12 h-12 bg-indigo-500 rounded-2xl flex items-center justify-center shadow-inner mb-6 shadow-indigo-200">
                        <Search className="w-6 h-6 text-white" />
                    </div>
                    <h1 className="text-xl font-black tracking-tight text-slate-900 mb-2">{__('general.content_search')}</h1>
                    <p className="text-sm font-medium text-slate-500 mb-8 leading-relaxed">{__('general.deep_serp_analysis_and_intelligent_seo_data_extraction')}</p>

                    <Tabs defaultValue="new" className="w-full">
                        <TabsList className="w-full grid grid-cols-2 mb-6 p-1 bg-slate-100 rounded-xl">
                            <TabsTrigger value="new" className="rounded-lg text-xs font-bold data-[state=active]:bg-white data-[state=active]:shadow-sm">{__('general.new_campaign')}</TabsTrigger>
                            <TabsTrigger value="history" className="rounded-lg text-xs font-bold data-[state=active]:bg-white data-[state=active]:shadow-sm">Database</TabsTrigger>
                        </TabsList>

                        <TabsContent value="new" className="space-y-6">
                            <div className="space-y-2">
                                <Label className="text-xs font-bold uppercase tracking-wider text-slate-500 ml-1">{__('general.target_keyword')}</Label>
                                <Input 
                                    placeholder="e.g. 'Best SEO tools 2026'" 
                                    value={keyword}
                                    onChange={(e) => setKeyword(e.target.value)}
                                    className="bg-slate-50/50 border-slate-200 focus-visible:ring-indigo-500/20 shadow-sm h-11 text-sm font-medium"
                                />
                            </div>

                            <div className="space-y-2">
                                <Label className="text-xs font-bold uppercase tracking-wider text-slate-500 ml-1">{__('general.search_engine')}</Label>
                                <select 
                                    value={engine}
                                    onChange={(e) => setEngine(e.target.value as any)}
                                    className="w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50/50 text-sm font-medium focus:outline-none focus:ring-4 focus:ring-indigo-500/20 transition-colors"
                                >
                                    <option value="duckduckgo">DuckDuckGo (Recommended)</option>
                                    <option value="google">Google (May require fallback)</option>
                                    <option value="bing">Bing</option>
                                </select>
                            </div>

                            <div className="space-y-2">
                                <button
                                    onClick={() => setShowAdvanced(!showAdvanced)}
                                    className="flex items-center gap-2 text-xs font-bold text-slate-600 hover:text-slate-900 transition-colors w-full p-2 bg-slate-50 rounded-lg border border-slate-100"
                                >
                                    <Settings className="w-3.5 h-3.5" />
                                    Advanced Crawl Options
                                    {showAdvanced ? <ChevronUp className="w-3.5 h-3.5 ml-auto" /> : <ChevronDown className="w-3.5 h-3.5 ml-auto" />}
                                </button>
                                
                                {showAdvanced && (
                                    <div className="p-4 bg-slate-50 rounded-xl border border-slate-100 space-y-4 animate-in slide-in-from-top-2 fade-in duration-200 mt-2">
                                        <label className="flex items-start gap-3 cursor-pointer group">
                                            <div className="relative flex items-center justify-center mt-0.5">
                                                <input 
                                                    type="checkbox" 
                                                    checked={recursive}
                                                    onChange={(e) => setRecursive(e.target.checked)}
                                                    className="peer sr-only" 
                                                />
                                                <div className="w-5 h-5 rounded border border-slate-300 bg-white peer-checked:bg-indigo-500 peer-checked:border-indigo-500 transition-colors"></div>
                                                <CheckCircle className="w-3.5 h-3.5 text-white absolute opacity-0 peer-checked:opacity-100 transition-opacity" />
                                            </div>
                                            <div className="flex flex-col">
                                                <span className="text-sm font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">{__('general.recursive_discovery')}</span>
                                                <span className="text-xs font-medium text-slate-500 leading-relaxed mt-0.5">{__('general.automatically_crawl_paa_and_related_searches')}</span>
                                            </div>
                                        </label>
                                    </div>
                                )}
                            </div>

                            {status === 'running' ? (
                                <Button 
                                    onClick={handleStop}
                                    variant="destructive"
                                    className="w-full h-12 rounded-xl font-bold shadow-lg shadow-rose-500/20 gap-2 mt-4"
                                >
                                    <Square className="w-4 h-4 fill-current" />{__('general.stop_crawler')}</Button>
                            ) : (
                                <Button 
                                    onClick={handleStart}
                                    className="w-full h-12 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold shadow-lg shadow-indigo-500/25 gap-2 mt-4 transition-all"
                                >
                                    <Play className="w-4 h-4 fill-current" />{__('general.start_campaign')}</Button>
                            )}

                            {status === 'error' && (
                                <div className="flex items-start gap-2.5 bg-rose-50 border border-rose-200 rounded-xl p-3">
                                    <AlertCircle className="w-4 h-4 text-rose-500 mt-0.5 shrink-0" />
                                    <p className="text-xs text-rose-700 font-medium leading-relaxed">{errorMsg}</p>
                                </div>
                            )}

                            {status === 'done' && results.length > 0 && (
                                <div className="flex items-start gap-2.5 bg-emerald-50 border border-emerald-200 rounded-xl p-3">
                                    <CheckCircle className="w-4 h-4 text-emerald-500 mt-0.5 shrink-0" />
                                    <p className="text-xs text-emerald-700 font-medium leading-relaxed">
                                        Campaign complete! {results.length} pages analyzed.
                                    </p>
                                </div>
                            )}
                        </TabsContent>

                        <TabsContent value="history">
                            <div className="space-y-3">
                                {loadingCampaigns ? (
                                    <div className="flex items-center justify-center py-8 gap-2 text-slate-400">
                                        <Loader2 className="w-4 h-4 animate-spin" />
                                        <span className="text-xs font-medium">{__('general.loading_campaigns')}</span>
                                    </div>
                                ) : campaigns.length === 0 ? (
                                    <p className="text-xs text-slate-500 text-center py-8">{__('general.no_campaigns_found_start_a_new_research_to_see_results_here')}</p>
                                ) : campaigns.map((c) => (
                                    <div 
                                        key={c.id} 
                                        onClick={() => loadCampaignData(c.id)}
                                        className={`p-3 rounded-xl border cursor-pointer transition-all ${
                                            selectedCampaign?.id === c.id 
                                            ? 'bg-indigo-50 border-indigo-200 shadow-sm' 
                                            : 'bg-white border-slate-200 hover:border-indigo-300 hover:shadow-sm'
                                        }`}
                                    >
                                        <div className="flex justify-between items-start mb-2">
                                            <p className="text-xs font-bold text-slate-800 line-clamp-1 flex-1 pr-2">{c.name}</p>
                                            <Badge variant="outline" className="text-[9px] px-1.5 py-0 h-4">{c.engine}</Badge>
                                        </div>
                                        <div className="flex justify-between items-center">
                                            <p className="text-[10px] text-slate-500">{formatDate(c.created_at)}</p>
                                            <button 
                                                onClick={(e) => deleteCampaign(c.id, e)}
                                                className="text-slate-400 hover:text-rose-500 transition-colors p-1"
                                            >
                                                <Trash2 className="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </TabsContent>
                    </Tabs>
                </div>
            </div>

            {/* Main Workspace Area */}
            <div className="flex-1 flex flex-col min-w-0 bg-slate-50">
                {/* Live Progress Bar */}
                {status === 'running' && (
                    <div className="bg-white border-b border-slate-200 px-6 py-3 shrink-0 flex items-center gap-4 animate-in fade-in slide-in-from-top-2 duration-300">
                        <div className="w-8 h-8 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center shrink-0">
                            <RefreshCw className="w-4 h-4 text-indigo-600 animate-spin" />
                        </div>
                        <div className="flex-1 min-w-0">
                            <div className="flex justify-between items-end mb-1.5">
                                <p className="text-xs font-bold text-slate-800 truncate">{progressMsg || 'Processing...'}</p>
                                <span className="text-[10px] font-black text-indigo-600">{progress}%</span>
                            </div>
                            <div className="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div 
                                    className="h-full bg-gradient-to-r from-indigo-500 to-blue-500 transition-all duration-300 rounded-full" 
                                    style={{ width: `${progress}%` }} 
                                />
                            </div>
                        </div>
                    </div>
                )}

                {/* Content Area */}
                <div className="flex-1 overflow-auto p-6">
                    {/* Loading campaign data overlay */}
                    {loadingCampaignData && (
                        <div className="h-full flex flex-col items-center justify-center text-slate-400 space-y-4">
                            <Loader2 className="w-8 h-8 animate-spin text-indigo-400" />
                            <p className="text-sm font-medium">{__('general.loading_campaign_data')}</p>
                        </div>
                    )}

                    {/* Empty / Initial State */}
                    {!loadingCampaignData && !showTable && (
                        <div className="h-full flex flex-col items-center justify-center text-slate-400 space-y-4">
                            <div className="w-16 h-16 rounded-2xl bg-white border border-slate-200 shadow-sm flex items-center justify-center">
                                <Database className="w-8 h-8 text-slate-300" />
                            </div>
                            <div className="text-center max-w-sm">
                                <p className="text-sm font-bold text-slate-600 mb-1">{__('general.no_data_yet')}</p>
                                <p className="text-xs text-slate-400 leading-relaxed">
                                    {campaigns.length > 0 
                                        ? 'Select a campaign from the Database tab or start a new research run.' 
                                        : 'Enter a keyword and click "Start Campaign" to begin your first SERP analysis.'
                                    }
                                </p>
                            </div>
                        </div>
                    )}

                    {/* Results Table */}
                    {!loadingCampaignData && showTable && (
                        <div className="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden flex flex-col h-full animate-in fade-in duration-500">
                            
                            {/* DB View Header */}
                            {(selectedCampaign || (status !== 'running' && hasData)) && (
                                <div className="bg-slate-50 border-b border-slate-200 px-6 py-4 flex justify-between items-center">
                                    <div>
                                        <h2 className="text-lg font-black text-slate-900">
                                            {selectedCampaign?.name || `Research: ${keyword}`}
                                        </h2>
                                        <p className="text-xs text-slate-500 mt-1">
                                            {displayData.length} pages • Engine: {selectedCampaign?.engine || engine}
                                        </p>
                                    </div>
                                    <Button 
                                        onClick={handleExportCSV}
                                        variant="outline" 
                                        size="sm" 
                                        className="gap-2 font-bold h-9 bg-white shadow-sm"
                                        disabled={displayData.length === 0}
                                    >
                                        <Download className="w-3.5 h-3.5" />{__('general.export_csv')}</Button>
                                </div>
                            )}

                            <div className="overflow-x-auto flex-1">
                                <table className="w-full text-left border-collapse min-w-[1200px]">
                                    <thead>
                                        <tr className="bg-slate-50 border-b border-slate-200 text-[10px] uppercase tracking-wider font-black text-slate-500 sticky top-0 z-10">
                                            <th className="px-4 py-3 whitespace-nowrap">Domain</th>
                                            <th className="px-4 py-3 whitespace-nowrap">{__('general.page_title')}</th>
                                            <th className="px-4 py-3 w-64">{__('general.h1_snippet')}</th>
                                            <th className="px-4 py-3 whitespace-nowrap text-right">Metrics</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {displayData.length === 0 && status === 'running' && (
                                            <tr>
                                                <td colSpan={4} className="px-4 py-16 text-center">
                                                    <div className="flex flex-col items-center gap-3 text-slate-400">
                                                        <Loader2 className="w-6 h-6 animate-spin text-indigo-400" />
                                                        <p className="text-xs font-medium">{__('general.waiting_for_results_to_stream_in')}</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        )}
                                        {displayData.map((r: any, i: number) => {
                                            const metrics = r.metrics || r;
                                            return (
                                                <tr key={i} className="hover:bg-slate-50/50 transition-colors group cursor-pointer">
                                                    <td className="px-4 py-3 align-top">
                                                        <p className="text-xs font-bold text-slate-900 truncate max-w-[150px]" title={r.domain}>{r.domain}</p>
                                                        <a href={r.url} target="_blank" rel="noopener noreferrer" className="text-[10px] text-blue-600 hover:underline truncate max-w-[150px] flex items-center gap-1 mt-1">{__('general.open_link')}<ExternalLink className="w-3 h-3" />
                                                        </a>
                                                    </td>
                                                    <td className="px-4 py-3 align-top">
                                                        <p className="text-xs font-medium text-slate-800 line-clamp-2" title={metrics.title}>{metrics.title || '-'}</p>
                                                    </td>
                                                    <td className="px-4 py-3 align-top">
                                                        <p className="text-[11px] font-bold text-slate-700 line-clamp-1 mb-1">
                                                            {(Array.isArray(metrics.h1) ? metrics.h1.join(' | ') : (metrics.h1 || r.h1 || '')).replace(/[\[\]"]/g, '') || 'No H1'}
                                                        </p>
                                                        <p className="text-[10px] text-slate-500 line-clamp-2">{r.snippet || metrics.meta_description || '-'}</p>
                                                    </td>
                                                    <td className="px-4 py-3 align-top text-right">
                                                        <div className="flex flex-col items-end gap-1.5">
                                                            <Badge variant="secondary" className="bg-indigo-50 text-indigo-700 hover:bg-indigo-50 text-[9px] font-bold px-1.5 py-0 h-5 border border-indigo-100 shadow-sm">
                                                                {metrics.word_count || 0} Words
                                                            </Badge>
                                                            {(metrics.has_faq_schema || (r.schemas && r.schemas.includes('FAQ'))) && (
                                                                <Badge variant="secondary" className="bg-emerald-50 text-emerald-700 hover:bg-emerald-50 text-[9px] font-bold px-1.5 py-0 h-5 border border-emerald-100 shadow-sm">{__('general.faq_schema')}</Badge>
                                                            )}
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

import React, { useState, useEffect, useRef } from 'react';
import { Database, Filter, Layers, CheckCircle, RefreshCw, AlertCircle, Play, FileText, Settings as SettingsIcon, HelpCircle, Download, FolderOpen } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';

import { useRuntimeWS } from '@/hooks/useRuntimeWS';
import { RuntimePluginModals } from '@/Components/Tools/RuntimePluginModals';
import { __ } from '@/lib/i18n';

export default function DataFilterRunner({ tool }: any) {
    const { connected: agentConnected, callRPC, installingPlugin, loginRequired, setLoginRequired } = useRuntimeWS('data-filter');
    const [activeTab, setActiveTab] = useState<'engine' | 'history'>('engine');

    // UI States
    const [filePath, setFilePath] = useState('');
    const [targetPath, setTargetPath] = useState('');
    const [mode, setMode] = useState<'distinct_all' | 'distinct_column' | 'split_file' | 'combine_files' | 'extract_columns' | 'filter_keyword'>('distinct_all');
    const [columnIndex, setColumnIndex] = useState(0);
    const [linesPerFile, setLinesPerFile] = useState(100000);
    const [preserveHeader, setPreserveHeader] = useState(true);
    const [combineInput, setCombineInput] = useState('');
    const [targetFolder, setTargetFolder] = useState('');
    const [extractIndices, setExtractIndices] = useState('');
    const [keyword, setKeyword] = useState('');

    // Job States
    const [jobId, setJobId] = useState<string | null>(null);
    const [status, setStatus] = useState<'idle' | 'running' | 'completed' | 'error'>('idle');
    const [totalRows, setTotalRows] = useState(0);
    const [writtenRows, setWrittenRows] = useState(0);
    const [errorMsg, setErrorMsg] = useState('');
    
    // Live Feed
    const [realtimeLogs, setRealtimeLogs] = useState<{id: string, message: string, time: string}[]>([]);

    const handleBrowseInput = async (multiselect = false) => {
        try {
            const res: any = await callRPC('browse_file', { title: "Select Input CSV", multiselect });
            if (res.paths && res.paths.length > 0) {
                if (multiselect) {
                    setCombineInput(res.paths.join(', '));
                } else {
                    setFilePath(res.paths[0]);
                }
            }
        } catch (e) { /* empty */ }
    };

    const handleBrowseOutputFolder = async () => {
        try {
            const res: any = await callRPC('browse_folder', { title: "Select Output Folder" });
            if (res.path) setTargetFolder(res.path);
        } catch (e) { /* empty */ }
    };

    const handleBrowseOutput = async () => {
        try {
            const res: any = await callRPC('browse_save_file', { title: "Save Output CSV" });
            if (res.path) setTargetPath(res.path);
        } catch (e) { /* empty */ }
    };

    // History
    const [jobHistory, setJobHistory] = useState<any[]>([]);
    const [loadingHistory, setLoadingHistory] = useState(false);

    const fetchHistory = async () => {
        setLoadingHistory(true);
        try {
            const res: any = await callRPC('get_history');
            setJobHistory(res || []);
        } catch (err) {
            console.error('Failed to fetch history', err);
        } finally {
            setLoadingHistory(false);
        }
    };

    const clearHistory = async () => {
        try {
            await callRPC('clear_history');
            setJobHistory([]);
        } catch (err) {
            console.error('Failed to clear history', err);
        }
    };

    useEffect(() => {
        if (activeTab === 'history') {
            fetchHistory();
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [activeTab]);

    const addRealtimeLog = (message: string) => {
        setRealtimeLogs(prev => [{ id: Math.random().toString(), message, time: new Date().toLocaleTimeString() }, ...prev].slice(0, 50));
    };

    // Polling effect
    useEffect(() => {
        if (!jobId || status !== 'running') return;

        const interval = setInterval(async () => {
            try {
                const res: any = await callRPC('get_progress', { jobId });
                if (res.error) {
                    setStatus('error');
                    setErrorMsg(res.error);
                    clearInterval(interval);
                    return;
                }

                setTotalRows(res.totalRows || 0);
                setWrittenRows(res.writtenRows || 0);

                if (res.logs && res.logs.length > 0) {
                    res.logs.forEach((log: string) => addRealtimeLog(log));
                }

                if (res.status === 'completed') {
                    setStatus('completed');
                    clearInterval(interval);
                    addRealtimeLog('Filter engine shut down successfully.');
                }
                
                if (res.status === 'error') {
                    setStatus('error');
                    setErrorMsg(res.error || 'Unknown error occurred in runtime.');
                    clearInterval(interval);
                }

            } catch (err) {
                // Silently ignore polling errors to not interrupt the UI aggressively
            }
        }, 1500);

        return () => clearInterval(interval);
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [jobId, status]);

    const handleRun = async () => {
        if (mode === 'combine_files' && (!combineInput || !targetPath)) return;
        if (mode === 'split_file' && (!filePath || !targetFolder)) return;
        if (mode === 'extract_columns' && (!filePath || !targetPath || !extractIndices)) return;
        if (mode === 'filter_keyword' && (!filePath || !targetPath || !keyword)) return;
        if ((mode === 'distinct_all' || mode === 'distinct_column') && (!filePath || !targetPath)) return;
        
        setStatus('running');
        setErrorMsg('');
        setTotalRows(0);
        setWrittenRows(0);
        setJobId(null);
        addRealtimeLog('Warming up local CSV processing engine...');

        try {
            let data: any = {};
            if (mode === 'combine_files') {
                const paths = combineInput.split(',').map(s => s.trim()).filter(Boolean);
                data = { filePaths: paths, targetPath };
            } else if (mode === 'split_file') {
                data = { filePath, targetFolder, linesPerFile, preserveHeader };
            } else if (mode === 'extract_columns') {
                const indices = extractIndices.split(',').map(s => Number(s.trim())).filter(n => !isNaN(n));
                data = { filePath, targetPath, columnIndices: indices };
            } else if (mode === 'filter_keyword') {
                data = { filePath, targetPath, columnIndex, keyword };
            } else {
                data = { filePath, targetPath, ...(mode === 'distinct_column' ? { columnIndex } : {}) };
            }
            const res: any = await callRPC(mode, data);
            
            if (res.jobId) {
                setJobId(res.jobId);
                addRealtimeLog(`Job dispatched to runtime (ID: ${res.jobId.substring(0, 6)}...)`);
            } else {
                throw new Error('Failed to get Job ID from runtime.');
            }
        } catch (err: any) {
            setStatus('error');
            setErrorMsg(err.message);
            addRealtimeLog(`Error dispatching job: ${err.message}`);
        }
    };

    if (!agentConnected) {
        return (
            <div className="min-h-screen bg-[#F8FAFC] flex items-center justify-center font-sans">
                <div className="text-center space-y-4">
                    <div className="w-10 h-10 border-2 border-slate-900 border-t-transparent rounded-full animate-spin mx-auto" />
                    <p className="text-sm font-semibold text-slate-600">{__('general.syncing_with_local_runtime_agent')}</p>
                    <p className="text-xs text-slate-400">{__('general.make_sure_your_musoftware_runtime_is_running_on_your_machine')}</p>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-[#F8FAFC] text-slate-900 font-sans flex flex-col antialiased selection:bg-blue-500 selection:text-white">
            <RuntimePluginModals 
                installingPlugin={installingPlugin} 
                loginRequired={loginRequired} 
                setLoginRequired={setLoginRequired} 
            />
            {/* Topbar Navigation Bar - Clean, Glassmorphism aesthetic */}
            <header className="h-16 border-b border-slate-200 bg-white/80 backdrop-blur-md flex items-center justify-between px-8 sticky top-0 z-30">
                <div className="flex items-center gap-6">
                    <div className="flex items-center gap-2.5">
                        <div className="w-6.5 h-6.5 bg-gradient-to-tr from-blue-600 to-cyan-500 rounded-lg flex items-center justify-center shadow-md shadow-blue-500/20">
                            <Database className="w-3.5 h-3.5 text-white" />
                        </div>
                        <span className="font-bold text-sm tracking-tight bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent">{__('general.csv_data_filter')}</span>
                    </div>
                    
                    <div className="h-5 w-px bg-slate-200" />
                    
                    <nav className="flex items-center gap-1.5">
                        <Button 
                            variant={activeTab === 'engine' ? 'default' : 'ghost'}
                            size="sm"
                            onClick={() => setActiveTab('engine')}
                        >{__('general.processing_engine')}</Button>
                        <Button 
                            variant={activeTab === 'history' ? 'default' : 'ghost'}
                            size="sm"
                            onClick={() => setActiveTab('history')}
                        >{__('general.job_history')}</Button>
                    </nav>
                </div>

                <div className="flex items-center gap-4">
                    <div className="flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-100 text-emerald-800">
                        <div className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
                        <span className="text-[10px] font-bold uppercase tracking-wider font-mono">{__('general.stream_engine_ready')}</span>
                    </div>
                </div>
            </header>

            {/* Main Multi-Workspace Layout */}
            <div className="flex-1 flex overflow-hidden">
                {/* Real-time Side Stream panel (Operational UX) */}
                <aside className="w-72 border-r border-slate-200 bg-white flex flex-col justify-between hidden lg:flex shrink-0">
                    <div className="p-5 flex-1 flex flex-col min-h-0">
                        <div className="flex items-center gap-2 mb-4">
                            <span className="w-1.5 h-1.5 rounded-full bg-blue-500 animate-ping" />
                            <h3 className="font-bold text-xs uppercase tracking-wider text-slate-400">{__('general.live_engine_feed')}</h3>
                        </div>
                        <div className="flex-1 overflow-y-auto space-y-3 pr-1 font-sans scrollbar-thin">
                            {realtimeLogs.length === 0 ? (
                                <div className="text-center py-20 text-slate-400 text-xs flex flex-col items-center gap-2">
                                    <Layers className="w-6 h-6 text-slate-300" />
                                    <span>{__('general.engine_is_idle')}</span>
                                    <span>{__('general.launch_a_job_to_see_streaming_logs')}</span>
                                </div>
                            ) : (
                                realtimeLogs.map(log => (
                                    <div key={log.id} className="p-3 bg-slate-50 rounded-xl border border-slate-100 hover:border-slate-200 transition-colors">
                                        <p className="text-slate-800 text-xs leading-relaxed font-medium">{log.message}</p>
                                        <span className="text-[10px] text-slate-400 font-mono mt-1 block">{log.time}</span>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                    
                    <div className="p-5 border-t border-slate-100 bg-slate-50/50">
                        <div className="space-y-3">
                            <div className="flex items-center justify-between text-xs">
                                <span className="text-slate-500 font-semibold">Active Jobs:</span>
                                <span className={`font-bold ${status === 'running' ? 'text-blue-600 animate-pulse' : 'text-slate-400'}`}>
                                    {status === 'running' ? '1 running' : 'Idle'}
                                </span>
                            </div>
                            <div className="flex items-center justify-between text-xs">
                                <span className="text-slate-500 font-semibold">Processed Today:</span>
                                <span className="font-bold text-slate-900">{totalRows.toLocaleString()} rows</span>
                            </div>
                        </div>
                    </div>
                </aside>

                {/* Main Workspace content */}
                <main className="flex-1 overflow-y-auto p-8 max-w-5xl mx-auto w-full">
                    {/* WORKSPACE 1: ENGINE */}
                    {activeTab === 'engine' && (
                        <div className="space-y-8 animate-in fade-in slide-in-from-bottom-2 duration-300">
                            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div>
                                    <h1 className="text-2xl font-bold tracking-tight bg-gradient-to-r from-slate-950 to-slate-800 bg-clip-text text-transparent">{__('general.stream_processing_engine')}</h1>
                                    <p className="text-xs text-slate-500 mt-1">{__('general.configure_your_files_and_run_lightning_fast_local_deduplication_and_filtering_operations')}</p>
                                </div>
                            </div>

                            <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-6">
                                <div className="space-y-4">
                                    <div className="flex border border-slate-200 rounded-xl overflow-hidden p-1 gap-1 w-fit bg-slate-50 flex-wrap">
                                        {(['distinct_all', 'distinct_column', 'split_file', 'combine_files', 'extract_columns', 'filter_keyword'] as const).map(m => (
                                            <Button
                                                variant={mode === m ? 'default' : 'ghost'}
                                                key={m}
                                                onClick={() => setMode(m)}
                                                className={`h-8 px-4 text-xs font-bold transition-all ${mode === m ? 'bg-slate-900 text-white shadow-sm hover:bg-slate-800' : 'text-slate-500 hover:text-slate-900 hover:bg-white'}`}
                                            >
                                                {m === 'distinct_all' ? 'Remove Duplicate Rows' : m === 'distinct_column' ? 'Extract Distinct by Column' : m === 'split_file' ? 'Split File' : m === 'combine_files' ? 'Combine Files' : m === 'extract_columns' ? 'Extract Columns' : 'Filter by Keyword'}
                                            </Button>
                                        ))}
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <label className="text-xs font-bold text-slate-700 uppercase tracking-wide">
                                                {mode === 'combine_files' ? 'Input Datasets (Comma Separated)' : 'Input Dataset path'}
                                            </label>
                                            <div className="flex gap-2">
                                                <Input
                                                    placeholder={mode === 'combine_files' ? "C:\\data\\file1.csv, C:\\data\\file2.csv" : "C:\\data\\massive_list.csv"}
                                                    value={mode === 'combine_files' ? combineInput : filePath}
                                                    onChange={(e) => mode === 'combine_files' ? setCombineInput(e.target.value) : setFilePath(e.target.value)}
                                                    className="font-mono text-sm h-11 bg-slate-50 flex-1"
                                                />
                                                <Button type="button" variant="outline" className="h-11 px-4 text-slate-600 bg-white" onClick={() => handleBrowseInput(mode === 'combine_files')}>
                                                    <FolderOpen className="w-4 h-4" />
                                                </Button>
                                            </div>
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-xs font-bold text-slate-700 uppercase tracking-wide">
                                                {mode === 'split_file' ? 'Output Folder path' : 'Output Dataset path'}
                                            </label>
                                            <div className="flex gap-2">
                                                <Input
                                                    placeholder={mode === 'split_file' ? "C:\\data\\chunks\\" : "C:\\data\\clean_list.csv"}
                                                    value={mode === 'split_file' ? targetFolder : targetPath}
                                                    onChange={(e) => mode === 'split_file' ? setTargetFolder(e.target.value) : setTargetPath(e.target.value)}
                                                    className="font-mono text-sm h-11 bg-slate-50 flex-1"
                                                />
                                                <Button type="button" variant="outline" className="h-11 px-4 text-slate-600 bg-white" onClick={mode === 'split_file' ? handleBrowseOutputFolder : handleBrowseOutput}>
                                                    <FolderOpen className="w-4 h-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    </div>

                                    {mode === 'distinct_column' && (
                                        <div className="space-y-2 max-w-xs">
                                            <label className="text-xs font-bold text-slate-700 uppercase tracking-wide">{__('general.target_column_index')}</label>
                                            <Input
                                                type="number"
                                                placeholder="0 for first column"
                                                value={columnIndex}
                                                onChange={(e) => setColumnIndex(Number(e.target.value))}
                                                className="font-mono text-sm h-11 bg-slate-50"
                                                min={0}
                                            />
                                            <p className="text-[10px] text-slate-500">0-based index. The engine will extract the first unique row for each distinct value in this column.</p>
                                        </div>
                                    )}

                                    {mode === 'split_file' && (
                                        <div className="space-y-4 max-w-xs">
                                            <div className="space-y-2">
                                                <label className="text-xs font-bold text-slate-700 uppercase tracking-wide">{__('general.lines_per_file')}</label>
                                                <Input
                                                    type="number"
                                                    placeholder="100000"
                                                    value={linesPerFile}
                                                    onChange={(e) => setLinesPerFile(Number(e.target.value))}
                                                    className="font-mono text-sm h-11 bg-slate-50"
                                                    min={1}
                                                />
                                                <p className="text-[10px] text-slate-500">{__('general.the_engine_will_create_a_new_file_every_time_this_many_lines_are_written')}</p>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <input 
                                                    type="checkbox" 
                                                    id="preserveHeader" 
                                                    checked={preserveHeader} 
                                                    onChange={(e) => setPreserveHeader(e.target.checked)}
                                                    className="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                                />
                                                <label htmlFor="preserveHeader" className="text-xs font-bold text-slate-700">{__('general.preserve_first_line_header')}</label>
                                            </div>
                                        </div>
                                    )}

                                    {mode === 'extract_columns' && (
                                        <div className="space-y-2 max-w-xs">
                                            <label className="text-xs font-bold text-slate-700 uppercase tracking-wide">{__('general.columns_to_keep')}</label>
                                            <Input
                                                type="text"
                                                placeholder="0, 2, 4"
                                                value={extractIndices}
                                                onChange={(e) => setExtractIndices(e.target.value)}
                                                className="font-mono text-sm h-11 bg-slate-50"
                                            />
                                            <p className="text-[10px] text-slate-500">{__('general.comma_separated_list_of_0_based_column_indices_e_g_0_1_5')}</p>
                                        </div>
                                    )}

                                    {mode === 'filter_keyword' && (
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div className="space-y-2 max-w-xs">
                                                <label className="text-xs font-bold text-slate-700 uppercase tracking-wide">{__('general.target_column_index')}</label>
                                                <Input
                                                    type="number"
                                                    placeholder="0 for first column"
                                                    value={columnIndex}
                                                    onChange={(e) => setColumnIndex(Number(e.target.value))}
                                                    className="font-mono text-sm h-11 bg-slate-50"
                                                    min={0}
                                                />
                                            </div>
                                            <div className="space-y-2">
                                                <label className="text-xs font-bold text-slate-700 uppercase tracking-wide">{__('general.keyword_to_match')}</label>
                                                <Input
                                                    type="text"
                                                    placeholder={__('general.e_g_gmail')}
                                                    value={keyword}
                                                    onChange={(e) => setKeyword(e.target.value)}
                                                    className="font-mono text-sm h-11 bg-slate-50"
                                                />
                                                <p className="text-[10px] text-slate-500">{__('general.case_insensitive_only_rows_containing_this_keyword_in_the_target_column_will_be_kept')}</p>
                                            </div>
                                        </div>
                                    )}
                                </div>

                                <div className="border-t border-slate-100 pt-6">
                                    <Button
                                        onClick={handleRun}
                                        disabled={status === 'running' || (mode === 'combine_files' ? !combineInput || !targetPath : mode === 'split_file' ? !filePath || !targetFolder : mode === 'extract_columns' ? !filePath || !targetPath || !extractIndices : mode === 'filter_keyword' ? !filePath || !targetPath || !keyword : !filePath || !targetPath)}
                                        className="w-full md:w-auto h-12 px-8 bg-gradient-to-r from-blue-600 to-cyan-500 text-white rounded-xl text-sm font-bold hover:opacity-90 shadow-md shadow-blue-500/20 gap-2"
                                    >
                                        {status === 'running' ? <RefreshCw className="w-4 h-4 animate-spin" /> : <Play className="w-4 h-4 fill-white" />}
                                        {status === 'running' ? 'Engine Running...' : 'Start Execution'}
                                    </Button>
                                </div>

                                {status === 'error' && (
                                    <div className="flex items-start gap-2.5 bg-rose-50 border border-rose-200 rounded-xl p-4 animate-in fade-in">
                                        <AlertCircle className="w-4 h-4 text-rose-500 mt-0.5 shrink-0" />
                                        <div>
                                            <p className="text-sm text-rose-800 font-bold">{__('general.engine_error')}</p>
                                            <p className="text-xs text-rose-600 mt-1">{errorMsg}</p>
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Live Progress Stats */}
                            {(status === 'running' || status === 'completed') && (
                                <div className="grid grid-cols-2 gap-4 animate-in fade-in slide-in-from-bottom-2">
                                    <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm relative overflow-hidden">
                                        {status === 'running' && <div className="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-400 to-cyan-400 animate-pulse" />}
                                        <p className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">{__('general.rows_read_from_input')}</p>
                                        <p className="text-3xl font-black text-slate-800">{totalRows.toLocaleString()}</p>
                                    </div>
                                    <div className="bg-white border border-emerald-200 rounded-2xl p-6 shadow-sm bg-emerald-50/30">
                                        <p className="text-xs font-bold text-emerald-600 uppercase tracking-wider mb-1">{__('general.distinct_rows_written')}</p>
                                        <p className="text-3xl font-black text-emerald-700">{writtenRows.toLocaleString()}</p>
                                    </div>
                                </div>
                            )}

                            {status === 'completed' && (
                                <div className="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm flex items-start gap-3 animate-in fade-in">
                                    <CheckCircle className="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" />
                                    <div>
                                        <p className="font-bold">{__('general.execution_completed_successfully')}</p>
                                        <p className="text-xs opacity-90 mt-1">Output has been saved to: <span className="font-mono bg-emerald-100/50 px-1 rounded">{mode === 'split_file' ? targetFolder : targetPath}</span></p>
                                    </div>
                                </div>
                            )}
                        </div>
                    )}

                    {/* WORKSPACE 2: HISTORY */}
                    {activeTab === 'history' && (
                        <div className="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h1 className="text-2xl font-bold tracking-tight bg-gradient-to-r from-slate-950 to-slate-800 bg-clip-text text-transparent">{__('general.job_history')}</h1>
                                    <p className="text-xs text-slate-500 mt-1">{__('general.review_previously_executed_datasets_and_exports')}</p>
                                </div>
                                <Button variant="outline" size="sm" onClick={clearHistory} className="text-rose-600 hover:text-rose-700 hover:bg-rose-50 border-rose-200">{__('general.clear_history')}</Button>
                            </div>
                            
                            {loadingHistory ? (
                                <div className="py-20 text-center"><RefreshCw className="w-6 h-6 animate-spin mx-auto text-slate-400" /></div>
                            ) : jobHistory.length === 0 ? (
                                <div className="bg-white border border-slate-200 rounded-2xl p-12 shadow-sm text-center">
                                    <FileText className="w-12 h-12 text-slate-200 mx-auto mb-4" />
                                    <h3 className="font-bold text-slate-700">{__('general.history_vault_is_empty')}</h3>
                                    <p className="text-xs text-slate-400 mt-1">{__('general.run_your_first_data_filter_job_to_see_it_here')}</p>
                                </div>
                            ) : (
                                <div className="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                                    <table className="w-full text-sm text-left">
                                        <thead className="bg-slate-50 border-b border-slate-200 text-xs text-slate-500 font-semibold uppercase">
                                            <tr>
                                                <th className="px-6 py-4 tracking-wider">Date</th>
                                                <th className="px-6 py-4 tracking-wider">Mode</th>
                                                <th className="px-6 py-4 tracking-wider">{__('general.rows_read')}</th>
                                                <th className="px-6 py-4 tracking-wider">{__('general.rows_written')}</th>
                                                <th className="px-6 py-4 tracking-wider">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {jobHistory.map((job) => (
                                                <tr key={job.id} className="hover:bg-slate-50 transition-colors">
                                                    <td className="px-6 py-4 whitespace-nowrap text-slate-600 font-mono text-[11px]">{new Date(job.created_at).toLocaleString()}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-slate-800 font-medium text-xs">{job.mode === 'distinct_all' ? 'Duplicate Removal' : job.mode === 'distinct_column' ? 'Column Distinct' : job.mode === 'split_file' ? 'Split File' : job.mode === 'combine_files' ? 'Combine Files' : job.mode === 'extract_columns' ? 'Extract Columns' : 'Filter by Keyword'}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-slate-600 font-mono text-xs">{job.total_rows.toLocaleString()}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-emerald-600 font-mono font-medium text-xs">{job.written_rows.toLocaleString()}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <Badge variant="outline" className={job.status === 'completed' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : job.status === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-blue-50 text-blue-700 border-blue-200'}>
                                                            {job.status}
                                                        </Badge>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </div>
                    )}
                </main>
            </div>
        </div>
    );
}

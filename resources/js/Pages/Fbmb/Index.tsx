import React, { useState, useRef, useCallback, useEffect } from 'react';
import axios from 'axios';
import { Head, usePage, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import {
    AlertCircle, UploadCloud, Download, FileText, CheckCircle2,
    Wallet, Sparkles, Loader2, X, Database, Zap, ArrowRight,
    FileSearch, Info, History, Clock, ChevronRight
} from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from "@/Components/ui/alert";
import { useToast } from "@/Components/ui/use-toast";
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';

interface PageProps {
    pointsBalance: number;
    currency: string;
    history: HistoryRecord[];
}

interface HistoryRecord {
    id: number;
    total_ids: number;
    found_count: number;
    credits_used: number;
    remaining_balance: number;
    download_token: string;
    expired: boolean;
    file_exists: boolean;
    created_at: string;
    expires_at: string;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    error_message?: string;
}

type Phase = 'upload' | 'processing' | 'results';
type ActiveTab = 'lookup' | 'history';

interface LookupResult {
    total_ids?: number;
    found_count?: number;
    credits_used?: number;
    remaining_balance?: number;
    download_token: string;
    status?: 'pending' | 'completed' | 'failed';
}

export default function ISaasIndex() {
    const { toast } = useToast();
    const { pointsBalance = 0, currency = 'USD', history = [] } = usePage<{ props: PageProps }>().props as any;
    const fileInputRef = useRef<HTMLInputElement>(null);

    const [activeTab, setActiveTab] = useState<ActiveTab>('lookup');
    const [file, setFile] = useState<File | null>(null);
    const [isDragging, setIsDragging] = useState(false);
    const [phase, setPhase] = useState<Phase>('upload');
    const [processing, setProcessing] = useState(false);
    const [result, setResult] = useState<LookupResult | null>(null);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);
    const [estimatedIds, setEstimatedIds] = useState<number>(0);

    const hasBalance = pointsBalance > 0;

    // -- File parsing to estimate ID count ----------------------------------
    const estimateIdCount = useCallback((f: File) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const text = e.target?.result as string;
            if (!text) return;
            const lines = text.split('\n');
            const ids = new Set();
            lines.forEach(l => {
                const parts = l.split(',');
                const val = parts[0]?.trim();
                if (val && /^\d+$/.test(val)) {
                    ids.add(val);
                }
            });
            setEstimatedIds(ids.size);
        };
        reader.readAsText(f);
    }, []);

    // -- File selection handlers --------------------------------------------
    const handleFileSelect = useCallback((f: File) => {
        const ext = f.name.split('.').pop()?.toLowerCase();
        if (ext !== 'txt' && ext !== 'csv') {
            toast({
                title: "Invalid file type",
                description: "Only .txt and .csv files are accepted.",
                variant: "destructive"
            });
            return;
        }
        if (f.size > 10 * 1024 * 1024) {
            toast({
                title: "File too large",
                description: "Maximum file size is 10 MB.",
                variant: "destructive"
            });
            return;
        }
        setFile(f);
        setErrorMessage(null);
        setResult(null);
        setPhase('upload');
        estimateIdCount(f);
    }, [toast, estimateIdCount]);

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files?.[0]) {
            handleFileSelect(e.target.files[0]);
        }
    };

    // -- Drag and drop handlers ---------------------------------------------
    const handleDragEnter = useCallback((e: React.DragEvent) => {
        e.preventDefault(); e.stopPropagation(); setIsDragging(true);
    }, []);

    const handleDragOver = useCallback((e: React.DragEvent) => {
        e.preventDefault(); e.stopPropagation(); setIsDragging(true);
    }, []);

    const handleDragLeave = useCallback((e: React.DragEvent) => {
        e.preventDefault(); e.stopPropagation();
        if (e.currentTarget.contains(e.relatedTarget as Node)) return;
        setIsDragging(false);
    }, []);

    const handleDrop = useCallback((e: React.DragEvent) => {
        e.preventDefault(); e.stopPropagation(); setIsDragging(false);
        const droppedFile = e.dataTransfer.files?.[0];
        if (droppedFile) handleFileSelect(droppedFile);
    }, [handleFileSelect]);

    const removeFile = () => {
        setFile(null); setEstimatedIds(0); setErrorMessage(null);
        setResult(null); setPhase('upload');
        if (fileInputRef.current) fileInputRef.current.value = '';
    };

    // -- Process submission -------------------------------------------------
    const submit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!file) {
            toast({ title: "File required", description: "Please select a file to upload first.", variant: "destructive" });
            return;
        }
        if (!hasBalance) {
            toast({ title: "Insufficient points", description: "You have 0 points. Please get points first.", variant: "destructive" });
            return;
        }
        setPhase('processing'); setProcessing(true); setErrorMessage(null);
        const formData = new FormData();
        formData.append('file', file);
        try {
            const response = await axios.post(route('fbmb.process'), formData);
            setResult(response.data);
            setPhase('results');
            if (response.data.status === 'pending') {
                toast({
                    title: __('general.lookup_queued'),
                    description: __('general.lookup_queued_successfully')
                });
            } else {
                toast({
                    title: "Lookup complete",
                    description: `Found ${response.data.found_count} matches from ${response.data.total_ids} IDs.`
                });
            }
        } catch (error: any) {
            setPhase('upload');
            let message = "An error occurred while processing your file.";
            if (error.response?.data?.message) message = error.response.data.message;
            setErrorMessage(message);
            toast({ title: "Processing failed", description: message, variant: "destructive" });
        } finally {
            setProcessing(false);
        }
    };

    const triggerDownload = (token: string) => {
        window.open(route('fbmb.download', { token }), '_blank');
    };

    const startNewLookup = () => {
        setFile(null); setEstimatedIds(0); setResult(null);
        setErrorMessage(null); setPhase('upload');
        if (fileInputRef.current) fileInputRef.current.value = '';
        setActiveTab('lookup');
    };

    // Polling active lookup status
    useEffect(() => {
        if (phase !== 'results' || !result || (result.status !== 'pending' && result.status !== 'processing')) {
            return;
        }

        let intervalId = setInterval(async () => {
            try {
                const response = await axios.get(route('fbmb.status', { token: result.download_token }));
                const currentStatus = response.data.status;
                
                // Update our active result state
                setResult(response.data);

                if (currentStatus === 'completed' || currentStatus === 'failed') {
                    clearInterval(intervalId);
                    
                    // Reload page props to refresh user points balance & history list
                    router.reload({
                        only: ['pointsBalance', 'history'],
                        onSuccess: () => {
                            if (currentStatus === 'completed') {
                                toast({
                                    title: __('general.lookup_complete'),
                                    description: response.data.found_count > 0 
                                        ? `Found ${response.data.found_count} matches from ${response.data.total_ids} IDs.`
                                        : __('general.no_matches_were_found_no_points_were_deducted')
                                });
                            } else {
                                toast({
                                    title: __('general.failed_to_process'),
                                    description: response.data.error_message || 'An error occurred.',
                                    variant: "destructive"
                                });
                            }
                        }
                    });
                }
            } catch (err: any) {
                console.error("Polling status failed:", err);
            }
        }, 3000);

        return () => clearInterval(intervalId);
    }, [phase, result?.download_token, result?.status, toast]);

    // Polling background scan items in history tab
    useEffect(() => {
        const hasPendingHistory = history.some((h: HistoryRecord) => h.status === 'pending' || h.status === 'processing');
        if (!hasPendingHistory) {
            return;
        }

        const intervalId = setInterval(() => {
            router.reload({
                only: ['pointsBalance', 'history']
            });
        }, 4000);

        return () => clearInterval(intervalId);
    }, [history]);

    // -- Format helpers -----------------------------------------------------
    const formatFileSize = (bytes: number) => {
        if (bytes < 1024) return `${bytes} B`;
        if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
        return `${(bytes / 1048576).toFixed(1)} MB`;
    };

    const formatDate = (iso: string) => {
        const d = new Date(iso);
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
            + ' · '
            + d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
    };

    const timeLeft = (expiresAt: string) => {
        const diff = new Date(expiresAt).getTime() - Date.now();
        if (diff <= 0) return 'Expired';
        const h = Math.floor(diff / 3600000);
        const m = Math.floor((diff % 3600000) / 60000);
        return h > 0 ? `${h}h ${m}m left` : `${m}m left`;
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">{__('general.isaas_database_lookup')}</h2>}
        >
            <Head title={__('general.isaas_database_lookup')} />

            <div className="py-8 md:py-12">
                <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

                    {/* -- Hero Header -- */}
                    <div className="relative mb-6">
                        <div className="absolute -top-4 -left-4 w-24 h-24 bg-indigo-500/10 rounded-full blur-2xl" />
                        <div className="absolute -top-2 right-8 w-16 h-16 bg-purple-500/10 rounded-full blur-xl" />
                        <div className="relative flex items-center gap-3">
                            <div className="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 shadow-lg shadow-indigo-500/25">
                                <Database className="w-5 h-5 text-white" />
                            </div>
                            <div>
                                <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">{__('general.isaas_database_lookup')}</h1>
                                <p className="text-sm text-slate-500">{__('general.upload_facebook_ids_get_mobile_numbers_instantly')}</p>
                            </div>
                        </div>
                    </div>

                    {/* -- Stats Bar -- */}
                    <div className="grid grid-cols-2 gap-3 mb-6">
                        <div className={cn(
                            "relative overflow-hidden rounded-xl border p-4 transition-all",
                            hasBalance
                                ? "border-emerald-200 bg-gradient-to-br from-emerald-50 to-white"
                                : "border-amber-200 bg-gradient-to-br from-amber-50 to-white"
                        )}>
                            <div className="flex items-center gap-2 mb-1">
                                <Wallet className={cn("w-4 h-4", hasBalance ? "text-emerald-500" : "text-amber-500")} />
                                <span className="text-xs font-medium text-slate-500 uppercase tracking-wider">Points</span>
                            </div>
                            <p className={cn("text-2xl font-bold font-mono tracking-tight", hasBalance ? "text-emerald-700" : "text-amber-700")}>
                                {Number(pointsBalance || 0).toLocaleString()} <span className="text-sm font-normal text-slate-400">Pts</span>
                            </p>
                            {!hasBalance && (
                                <p className="text-xs text-amber-600 mt-1 flex items-center gap-1">
                                    <AlertCircle className="w-3 h-3" />{__('general.get_points_to_start_lookups')}</p>
                            )}
                            <div className={cn("absolute -right-2 -bottom-2 w-16 h-16 rounded-full opacity-10", hasBalance ? "bg-emerald-500" : "bg-amber-500")} />
                        </div>

                        <div className="relative overflow-hidden rounded-xl border border-indigo-200 bg-gradient-to-br from-indigo-50 to-white p-4">
                            <div className="flex items-center gap-2 mb-1">
                                <Zap className="w-4 h-4 text-indigo-500" />
                                <span className="text-xs font-medium text-slate-500 uppercase tracking-wider">{__('general.cost_per_match')}</span>
                            </div>
                            <p className="text-2xl font-bold font-mono tracking-tight text-indigo-700">
                                1 <span className="text-sm font-normal text-slate-400">Point</span>
                            </p>
                            <p className="text-xs text-slate-500 mt-1">{__('general.only_charged_for_successful_matches')}</p>
                            <div className="absolute -right-2 -bottom-2 w-16 h-16 rounded-full bg-indigo-500 opacity-10" />
                        </div>
                    </div>

                    {/* -- Tabs -- */}
                    <div className="flex items-center gap-1 mb-6 bg-slate-100 p-1 rounded-xl">
                        <button
                            onClick={() => setActiveTab('lookup')}
                            className={cn(
                                "flex-1 flex items-center justify-center gap-2 py-2 px-4 rounded-lg text-sm font-medium transition-all duration-200",
                                activeTab === 'lookup'
                                    ? "bg-white text-slate-900 shadow-sm"
                                    : "text-slate-500 hover:text-slate-700"
                            )}
                        >
                            <UploadCloud className="w-4 h-4" />{__('general.new_lookup')}</button>
                        <button
                            onClick={() => setActiveTab('history')}
                            className={cn(
                                "flex-1 flex items-center justify-center gap-2 py-2 px-4 rounded-lg text-sm font-medium transition-all duration-200",
                                activeTab === 'history'
                                    ? "bg-white text-slate-900 shadow-sm"
                                    : "text-slate-500 hover:text-slate-700"
                            )}
                        >
                            <History className="w-4 h-4" />
                            {__('general.history')}
                            {history.length > 0 && (
                                <span className={cn(
                                    "text-[10px] font-semibold px-1.5 py-0.5 rounded-full",
                                    activeTab === 'history'
                                        ? "bg-indigo-100 text-indigo-700"
                                        : "bg-slate-200 text-slate-600"
                                )}>
                                    {history.length}
                                </span>
                            )}
                        </button>
                    </div>

                    {/* ------------------------------------------------------ */}
                    {/* TAB: NEW LOOKUP                                        */}
                    {/* ------------------------------------------------------ */}
                    {activeTab === 'lookup' && (
                        <>
                            {/* -- Step Indicator -- */}
                            <div className="flex items-center gap-2 mb-6 px-1">
                                {[
                                    { label: 'Upload', icon: UploadCloud, step: 'upload' },
                                    { label: 'Processing', icon: Loader2, step: 'processing' },
                                    { label: 'Results', icon: CheckCircle2, step: 'results' },
                                ].map((s, i) => {
                                    const isActive = s.step === phase;
                                    const isPast = (phase === 'processing' && s.step === 'upload')
                                        || (phase === 'results' && (s.step === 'upload' || s.step === 'processing'));
                                    return (
                                        <React.Fragment key={s.step}>
                                            {i > 0 && (
                                                <div className={cn("flex-1 h-px transition-colors duration-500", isPast ? "bg-indigo-400" : "bg-slate-200")} />
                                            )}
                                            <div className={cn(
                                                "flex items-center gap-1.5 text-xs font-medium transition-all duration-300",
                                                isActive ? "text-indigo-600 scale-105" : isPast ? "text-indigo-400" : "text-slate-400"
                                            )}>
                                                <div className={cn(
                                                    "flex items-center justify-center w-6 h-6 rounded-full transition-all duration-300",
                                                    isActive ? "bg-indigo-100 ring-2 ring-indigo-500 ring-offset-1"
                                                        : isPast ? "bg-indigo-100" : "bg-slate-100"
                                                )}>
                                                    <s.icon className={cn("w-3 h-3", isActive && s.step === 'processing' && "animate-spin")} />
                                                </div>
                                                <span className="hidden sm:inline">{s.label}</span>
                                            </div>
                                        </React.Fragment>
                                    );
                                })}
                            </div>

                            {/* -- Error Alert -- */}
                            {errorMessage && (
                                <Alert variant="destructive" className="mb-6 animate-in fade-in slide-in-from-top-2 duration-300">
                                    <AlertCircle className="h-4 w-4" />
                                    <AlertTitle>Error</AlertTitle>
                                    <AlertDescription>{errorMessage}</AlertDescription>
                                </Alert>
                            )}

                            {/* -- Main Card -- */}
                            <div className="relative">
                                <div className={cn(
                                    "absolute inset-0 rounded-2xl transition-opacity duration-500",
                                    isDragging ? "opacity-100" : "opacity-0",
                                    "bg-gradient-to-r from-indigo-500/20 via-purple-500/20 to-indigo-500/20 blur-xl"
                                )} />

                                <Card className={cn(
                                    "relative rounded-2xl border-2 transition-all duration-300 overflow-visible",
                                    isDragging
                                        ? "border-indigo-400 shadow-xl shadow-indigo-500/10 scale-[1.01]"
                                        : "border-slate-200 shadow-sm hover:shadow-md"
                                )}>
                                    <CardContent className="p-6 sm:p-8">
                                        {/* UPLOAD PHASE */}
                                        {phase === 'upload' && (
                                            <form onSubmit={submit}>
                                                <div
                                                    onDragEnter={handleDragEnter}
                                                    onDragOver={handleDragOver}
                                                    onDragLeave={handleDragLeave}
                                                    onDrop={handleDrop}
                                                    onClick={() => !file && fileInputRef.current?.click()}
                                                    className={cn(
                                                        "relative flex flex-col items-center justify-center rounded-xl border-2 border-dashed p-8 sm:p-10 transition-all duration-300 cursor-pointer group",
                                                        isDragging ? "border-indigo-400 bg-indigo-50/80 scale-[1.02]"
                                                            : file ? "border-emerald-300 bg-emerald-50/50"
                                                                : "border-slate-300 bg-slate-50/50 hover:border-indigo-300 hover:bg-indigo-50/30"
                                                    )}
                                                >
                                                    <input ref={fileInputRef} type="file" accept=".txt,.csv" className="sr-only" onChange={handleInputChange} />

                                                    {!file ? (
                                                        <>
                                                            <div className={cn(
                                                                "flex items-center justify-center w-16 h-16 rounded-2xl mb-4 transition-all duration-300",
                                                                isDragging ? "bg-indigo-100 scale-110" : "bg-slate-100 group-hover:bg-indigo-100 group-hover:scale-105"
                                                            )}>
                                                                <UploadCloud className={cn("w-8 h-8 transition-colors duration-300", isDragging ? "text-indigo-500" : "text-slate-400 group-hover:text-indigo-500")} />
                                                            </div>
                                                            <p className="text-sm font-medium text-slate-700 mb-1">
                                                                {isDragging ? 'Drop your file here' : 'Click to upload or drag and drop'}
                                                            </p>
                                                            <p className="text-xs text-slate-500">
                                                                Supports <span className="font-medium">.txt</span> and <span className="font-medium">.csv</span>{__('general.files_up_to_10_mb')}</p>
                                                        </>
                                                    ) : (
                                                        <div className="flex items-center gap-4 w-full" onClick={(e) => e.stopPropagation()}>
                                                            <div className="flex items-center justify-center w-12 h-12 rounded-xl bg-emerald-100 shrink-0">
                                                                <FileText className="w-6 h-6 text-emerald-600" />
                                                            </div>
                                                            <div className="flex-1 min-w-0">
                                                                <p className="text-sm font-semibold text-slate-800 truncate">{file.name}</p>
                                                                <div className="flex items-center gap-3 mt-0.5">
                                                                    <span className="text-xs text-slate-500">{formatFileSize(file.size)}</span>
                                                                    {estimatedIds > 0 && (
                                                                        <span className="text-xs text-indigo-600 font-medium flex items-center gap-1">
                                                                            <FileSearch className="w-3 h-3" />
                                                                            ~{estimatedIds.toLocaleString()} IDs detected
                                                                        </span>
                                                                    )}
                                                                </div>
                                                            </div>
                                                            <button type="button" onClick={removeFile}
                                                                className="flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 transition-all shrink-0">
                                                                <X className="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                    )}
                                                </div>

                                                {file && estimatedIds > 0 && (
                                                    <div className="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3 animate-in fade-in slide-in-from-bottom-2 duration-300">
                                                        <div className="flex items-start gap-2.5">
                                                            <Info className="w-4 h-4 text-indigo-500 mt-0.5 shrink-0" />
                                                            <div className="text-xs text-slate-600 space-y-1">
                                                                <p><span className="font-medium text-slate-700">Estimated IDs:</span> {estimatedIds.toLocaleString()}</p>
                                                                <p>
                                                                    <span className="font-medium text-slate-700">Max cost:</span>{' '}
                                                                    <span className="font-mono">{estimatedIds.toLocaleString()}</span> points
                                                                    <span className="text-slate-400 ml-1">(only matched IDs are charged)</span>
                                                                </p>
                                                                {estimatedIds > pointsBalance && (
                                                                    <p className="text-amber-600 flex items-center gap-1 font-medium">
                                                                        <AlertCircle className="w-3 h-3" />{__('general.your_points_may_not_cover_all_matches_partial_results_may_be_available')}</p>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                )}

                                                <div className="mt-6 flex flex-col sm:flex-row items-center gap-3">
                                                    <Button
                                                        type="submit"
                                                        disabled={!file || processing || !hasBalance}
                                                        className={cn(
                                                            "w-full sm:w-auto h-11 px-6 text-sm font-semibold rounded-xl transition-all duration-300",
                                                            file && hasBalance
                                                                ? "bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white shadow-lg shadow-indigo-500/25 hover:shadow-xl hover:shadow-indigo-500/30 hover:-translate-y-px"
                                                                : ""
                                                        )}
                                                    >
                                                        <Sparkles className="w-4 h-4 mr-2" />{__('general.start_lookup')}</Button>
                                                    {!hasBalance && (
                                                        <p className="text-xs text-amber-600 flex items-center gap-1">
                                                            <AlertCircle className="w-3 h-3 shrink-0" />{__('general.get_points_to_use_this_feature')}</p>
                                                    )}
                                                </div>
                                            </form>
                                        )}

                                        {/* PROCESSING PHASE */}
                                        {phase === 'processing' && (
                                            <div className="flex flex-col items-center justify-center py-12 animate-in fade-in duration-500">
                                                <div className="relative">
                                                    <div className="absolute inset-0 rounded-full bg-indigo-500/20 animate-ping" />
                                                    <div className="relative flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100">
                                                        <Loader2 className="w-8 h-8 text-indigo-600 animate-spin" />
                                                    </div>
                                                </div>
                                                <h3 className="mt-6 text-lg font-semibold text-slate-800">{__('general.processing_your_file')}</h3>
                                                <p className="mt-1 text-sm text-slate-500 text-center max-w-xs">
                                                    Searching {estimatedIds > 0 ? `${estimatedIds.toLocaleString()} IDs` : 'your IDs'} across the intelligence database. This may take a moment.
                                                </p>
                                                <div className="mt-6 flex items-center gap-2">
                                                    <div className="w-2 h-2 rounded-full bg-indigo-500 animate-bounce" style={{ animationDelay: '0ms' }} />
                                                    <div className="w-2 h-2 rounded-full bg-indigo-400 animate-bounce" style={{ animationDelay: '150ms' }} />
                                                    <div className="w-2 h-2 rounded-full bg-indigo-300 animate-bounce" style={{ animationDelay: '300ms' }} />
                                                </div>
                                            </div>
                                        )}

                                        {/* RESULTS PHASE */}
                                        {phase === 'results' && result && (result.status === 'pending' || result.status === 'processing') ? (
                                            <div className="flex flex-col items-center justify-center py-12 animate-in fade-in duration-500">
                                                <div className="relative mb-6">
                                                    <div className="absolute inset-0 rounded-full bg-indigo-500/10 animate-ping duration-1000" />
                                                    <div className="relative flex items-center justify-center w-24 h-24 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 shadow-md">
                                                        <Loader2 className="w-10 h-10 text-indigo-600 animate-spin" />
                                                    </div>
                                                </div>
                                                <h3 className="text-xl font-bold text-slate-800 tracking-tight">
                                                    {__('general.scanning_in_progress')}
                                                </h3>
                                                <p className="mt-2 text-sm text-slate-600 font-medium bg-indigo-50 text-indigo-700 px-3.5 py-1 rounded-full border border-indigo-100">
                                                    {__('general.processing_ids_count', { count: (result.total_ids || estimatedIds || 0).toLocaleString() })}
                                                </p>
                                                <p className="mt-3 text-xs text-slate-500 text-center max-w-sm leading-relaxed">
                                                    {__('general.scanning_database_desc')}
                                                </p>

                                                <div className="mt-8 flex flex-col sm:flex-row gap-3 w-full max-w-md">
                                                    <Button 
                                                        onClick={() => setActiveTab('history')} 
                                                        className="flex-1 h-11 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-semibold shadow-sm hover:shadow animate-in fade-in slide-in-from-bottom-2 duration-350"
                                                    >
                                                        <History className="w-4 h-4 mr-2" />
                                                        {__('general.view_history')}
                                                    </Button>
                                                    <Button 
                                                        onClick={startNewLookup} 
                                                        variant="outline" 
                                                        className="flex-1 h-11 rounded-xl"
                                                    >
                                                        <ArrowRight className="w-4 h-4 mr-2" />
                                                        {__('general.new_lookup')}
                                                    </Button>
                                                </div>
                                            </div>
                                        ) : phase === 'results' && result && (
                                            <div className="animate-in fade-in slide-in-from-bottom-4 duration-500">
                                                <div className="flex items-center gap-3 p-4 rounded-xl bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200 mb-6">
                                                    <div className="flex items-center justify-center w-10 h-10 rounded-full bg-emerald-100">
                                                        <CheckCircle2 className="w-5 h-5 text-emerald-600" />
                                                    </div>
                                                    <div>
                                                        <h3 className="text-sm font-semibold text-emerald-800">{__('general.lookup_complete')}</h3>
                                                        <p className="text-xs text-emerald-600">{__('general.your_results_are_ready_to_download_this_lookup_is_also_saved_in_history')}</p>
                                                    </div>
                                                </div>

                                                <div className="grid grid-cols-3 gap-3 mb-6">
                                                    <div className="rounded-xl border border-slate-200 bg-white p-4 text-center">
                                                        <p className="text-xs text-slate-500 font-medium uppercase tracking-wider mb-1">{__('general.total_ids')}</p>
                                                        <p className="text-2xl font-bold font-mono text-slate-800">{result.total_ids.toLocaleString()}</p>
                                                    </div>
                                                    <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-center">
                                                        <p className="text-xs text-emerald-600 font-medium uppercase tracking-wider mb-1">Matches</p>
                                                        <p className="text-2xl font-bold font-mono text-emerald-700">{result.found_count.toLocaleString()}</p>
                                                    </div>
                                                    <div className="rounded-xl border border-indigo-200 bg-indigo-50 p-4 text-center">
                                                        <p className="text-xs text-indigo-600 font-medium uppercase tracking-wider mb-1">{__('general.points_used')}</p>
                                                        <p className="text-2xl font-bold font-mono text-indigo-700">{result.credits_used.toLocaleString()}</p>
                                                    </div>
                                                </div>

                                                <div className="mb-6">
                                                    <div className="flex items-center justify-between text-xs text-slate-500 mb-1.5">
                                                        <span>{__('general.match_rate')}</span>
                                                        <span className="font-mono font-medium text-slate-700">
                                                            {result.total_ids > 0 ? ((result.found_count / result.total_ids) * 100).toFixed(1) : 0}%
                                                        </span>
                                                    </div>
                                                    <div className="h-2 rounded-full bg-slate-100 overflow-hidden">
                                                        <div
                                                            className="h-full rounded-full bg-gradient-to-r from-indigo-500 to-emerald-500 transition-all duration-1000 ease-out"
                                                            style={{ width: `${result.total_ids > 0 ? (result.found_count / result.total_ids) * 100 : 0}%` }}
                                                        />
                                                    </div>
                                                </div>

                                                <div className="rounded-lg border border-slate-200 bg-slate-50 p-3 mb-6">
                                                    <div className="flex items-center justify-between text-sm">
                                                        <span className="text-slate-500 flex items-center gap-1.5">
                                                            <Wallet className="w-3.5 h-3.5" />{__('general.remaining_points')}</span>
                                                        <span className="font-mono font-semibold text-slate-800">{result.remaining_balance.toLocaleString()} Pts</span>
                                                    </div>
                                                </div>

                                                <div className="flex flex-col sm:flex-row gap-3">
                                                    {result.found_count > 0 && (
                                                        <Button
                                                            onClick={() => triggerDownload(result.download_token)}
                                                            className="flex-1 h-11 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white shadow-lg shadow-emerald-500/25 hover:shadow-xl hover:shadow-emerald-500/30 hover:-translate-y-px transition-all font-semibold"
                                                        >
                                                            <Download className="w-4 h-4 mr-2" />
                                                            Download Results ({result.found_count} records)
                                                        </Button>
                                                    )}
                                                    <Button onClick={startNewLookup} variant="outline" className="h-11 rounded-xl">
                                                        <ArrowRight className="w-4 h-4 mr-2" />{__('general.new_lookup')}</Button>
                                                </div>

                                                {result.found_count === 0 && (
                                                    <div className="mt-4 p-4 rounded-xl border border-amber-200 bg-amber-50">
                                                        <p className="text-sm text-amber-700 flex items-center gap-2">
                                                            <AlertCircle className="w-4 h-4 shrink-0" />{__('general.no_matches_were_found_no_points_were_deducted')}</p>
                                                    </div>
                                                )}
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            </div>
                        </>
                    )}

                    {/* ------------------------------------------------------ */}
                    {/* TAB: HISTORY                                           */}
                    {/* ------------------------------------------------------ */}
                    {activeTab === 'history' && (
                        <div className="animate-in fade-in slide-in-from-bottom-2 duration-300">
                            {history.length === 0 ? (
                                <div className="flex flex-col items-center justify-center py-20 text-center">
                                    <div className="flex items-center justify-center w-16 h-16 rounded-2xl bg-slate-100 mb-4">
                                        <History className="w-8 h-8 text-slate-400" />
                                    </div>
                                    <h3 className="text-base font-semibold text-slate-700 mb-1">{__('general.no_lookups_yet')}</h3>
                                    <p className="text-sm text-slate-500 max-w-xs">{__('general.once_you_run_a_lookup_it_will_appear_here_for_24_hours')}</p>
                                    <Button
                                        onClick={() => setActiveTab('lookup')}
                                        className="mt-6 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl h-10 px-5 text-sm font-semibold"
                                    >
                                        <Sparkles className="w-4 h-4 mr-2" />{__('general.start_your_first_lookup')}</Button>
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {history.map((record) => {
                                        const isPending = record.status === 'pending' || record.status === 'processing';
                                        const isFailed = record.status === 'failed';
                                        const isCompleted = record.status === 'completed' || !record.status;
                                        const canDownload = isCompleted && !record.expired && record.file_exists && record.found_count > 0;
                                        return (
                                            <div
                                                key={record.id}
                                                className={cn(
                                                    "rounded-2xl border p-4 sm:p-5 transition-all duration-200",
                                                    record.expired
                                                        ? "border-slate-200 bg-slate-50 opacity-60"
                                                        : "border-slate-200 bg-white shadow-sm hover:shadow-md hover:border-indigo-200"
                                                )}
                                            >
                                                <div className="flex items-start gap-4">
                                                    {/* Icon */}
                                                    <div className={cn(
                                                        "flex items-center justify-center w-10 h-10 rounded-xl shrink-0",
                                                        record.expired ? "bg-slate-100" : "bg-gradient-to-br from-indigo-50 to-purple-50"
                                                    )}>
                                                        <Database className={cn("w-5 h-5", record.expired ? "text-slate-400" : "text-indigo-500")} />
                                                    </div>

                                                    {/* Info */}
                                                    <div className="flex-1 min-w-0">
                                                        <div className="flex items-center gap-2 flex-wrap mb-2">
                                                            {isPending && (
                                                                <span className="text-sm font-semibold text-slate-800 flex items-center gap-1.5">
                                                                    <Loader2 className="w-3.5 h-3.5 animate-spin text-indigo-500" />
                                                                    {record.status === 'processing' ? __('general.processing') : __('general.pending')}
                                                                    <span className="font-normal text-slate-400"> / {record.total_ids.toLocaleString()} IDs</span>
                                                                </span>
                                                            )}
                                                            {isFailed && (
                                                                <span className="text-sm font-semibold text-red-600 flex items-center gap-1.5">
                                                                    <AlertCircle className="w-3.5 h-3.5 text-red-500" />
                                                                    {__('general.failed')}
                                                                    <span className="font-normal text-slate-400"> / {record.total_ids.toLocaleString()} IDs</span>
                                                                </span>
                                                            )}                                                            {isCompleted && (
                                                                <span className="text-sm font-semibold text-slate-800">
                                                                    {__('general.matches_out_of_ids', {
                                                                        found: record.found_count.toLocaleString(),
                                                                        total: record.total_ids.toLocaleString()
                                                                    })}
                                                                </span>
                                                            )}
                                                            
                                                            {!isPending && !isFailed && (record.expired ? (
                                                                <span className="text-[10px] font-medium px-2 py-0.5 rounded-full bg-slate-200 text-slate-500">
                                                                    {__('general.expired')}
                                                                </span>
                                                            ) : (
                                                                <span className="text-[10px] font-medium px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 flex items-center gap-1">
                                                                    <Clock className="w-2.5 h-2.5" />
                                                                    {timeLeft(record.expires_at)}
                                                                </span>
                                                            ))}
                                                        </div>
 
                                                        {/* Stats row */}
                                                        <div className="flex items-center gap-4 text-xs text-slate-500 flex-wrap">
                                                            {!isPending && !isFailed && (
                                                                <>
                                                                    <span className="flex items-center gap-1">
                                                                        <Zap className="w-3 h-3 text-indigo-400" />
                                                                        {__('general.pts_used', { count: record.credits_used.toLocaleString() })}
                                                                    </span>
                                                                    <span className="flex items-center gap-1">
                                                                        <Wallet className="w-3 h-3 text-emerald-400" />
                                                                        {__('general.pts_remaining', { count: record.remaining_balance.toLocaleString() })}
                                                                    </span>
                                                                </>
                                                            )}
                                                            <span className="flex items-center gap-1 text-slate-400">
                                                                <Clock className="w-3 h-3" />
                                                                {formatDate(record.created_at)}
                                                            </span>
                                                        </div>
                                                        
                                                        {isFailed && (
                                                            <p className="text-xs text-red-500 mt-1 font-medium italic">
                                                                {record.error_message || 'Unknown error'}
                                                            </p>
                                                        )}
                                                    </div>

                                                    {/* Download button */}
                                                    <div className="shrink-0">
                                                        {isPending && (
                                                            <div className="h-9 px-4 rounded-xl flex items-center gap-1.5 text-xs font-medium bg-slate-100 text-slate-400">
                                                                <Loader2 className="w-3.5 h-3.5 animate-spin text-slate-400" />
                                                                {record.status === 'processing' ? __('general.processing') : __('general.pending')}
                                                            </div>
                                                        )}
                                                        {isFailed && (
                                                            <div className="h-9 px-4 rounded-xl flex items-center gap-1.5 text-xs font-medium bg-red-50 text-red-500">
                                                                <X className="w-3 h-3" />
                                                                {__('general.failed')}
                                                            </div>
                                                        )}
                                                        {isCompleted && (canDownload ? (
                                                            <Button
                                                                size="sm"
                                                                onClick={() => triggerDownload(record.download_token)}
                                                                className="h-9 px-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white text-xs font-semibold shadow-sm hover:shadow-md hover:-translate-y-px transition-all"
                                                            >
                                                                <Download className="w-3.5 h-3.5 mr-1.5" />
                                                                {__('general.download')}
                                                            </Button>
                                                        ) : (
                                                            <div className={cn(
                                                                "h-9 px-4 rounded-xl flex items-center gap-1.5 text-xs font-medium",
                                                                record.expired
                                                                    ? "bg-slate-100 text-slate-400"
                                                                    : record.found_count === 0
                                                                        ? "bg-amber-50 text-amber-500"
                                                                        : "bg-slate-100 text-slate-400"
                                                            )}>
                                                                {record.found_count === 0 ? (
                                                                    <><AlertCircle className="w-3 h-3" />{__('general.no_matches')}</>
                                                                ) : (
                                                                    <><X className="w-3 h-3" /> {__('general.expired')}</>
                                                                )}
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}

                                    <p className="text-center text-xs text-slate-400 pt-2">{__('general.lookup_results_are_kept_for_24_hours_then_automatically_removed')}</p>
                                </div>
                            )}
                        </div>
                    )}

                    {/* -- Info Footer -- */}
                    {activeTab === 'lookup' && (
                        <div className="mt-6 text-center">
                            <p className="text-xs text-slate-400">{__('general.results_are_saved_for_24_hours_switch_to_the_history_tab_to_re_download_anytime')}</p>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}


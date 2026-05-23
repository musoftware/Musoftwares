import React, { useState, useRef, useCallback } from 'react';
import axios from 'axios';
import { Head, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import {
    AlertCircle, UploadCloud, Download, FileText, CheckCircle2,
    Wallet, Sparkles, Loader2, X, Database, Zap, ArrowRight,
    FileSearch, Info
} from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from "@/Components/ui/alert";
import { useToast } from "@/Components/ui/use-toast";
import { cn } from '@/lib/utils';

interface PageProps {
    pointsBalance: number;
    currency: string;
}

type Phase = 'upload' | 'processing' | 'results';

interface LookupResult {
    total_ids: number;
    found_count: number;
    credits_used: number;
    remaining_balance: number;
    download_token: string;
}

export default function ISaasIndex() {
    const { toast } = useToast();
    const { pointsBalance = 0, currency = 'USD' } = usePage<{ props: PageProps }>().props as any;
    const fileInputRef = useRef<HTMLInputElement>(null);

    const [file, setFile] = useState<File | null>(null);
    const [isDragging, setIsDragging] = useState(false);
    const [phase, setPhase] = useState<Phase>('upload');
    const [processing, setProcessing] = useState(false);
    const [result, setResult] = useState<LookupResult | null>(null);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);
    const [estimatedIds, setEstimatedIds] = useState<number>(0);

    const hasBalance = pointsBalance > 0;

    // ── File parsing to estimate ID count ──────────────────────────────────
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

    // ── File selection handlers ────────────────────────────────────────────
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

    // ── Drag and drop handlers ─────────────────────────────────────────────
    const handleDragEnter = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        setIsDragging(true);
    }, []);

    const handleDragOver = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        setIsDragging(true);
    }, []);

    const handleDragLeave = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        // Only un-highlight if leaving the drop zone entirely
        if (e.currentTarget.contains(e.relatedTarget as Node)) return;
        setIsDragging(false);
    }, []);

    const handleDrop = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        setIsDragging(false);
        const droppedFile = e.dataTransfer.files?.[0];
        if (droppedFile) {
            handleFileSelect(droppedFile);
        }
    }, [handleFileSelect]);

    const removeFile = () => {
        setFile(null);
        setEstimatedIds(0);
        setErrorMessage(null);
        setResult(null);
        setPhase('upload');
        if (fileInputRef.current) fileInputRef.current.value = '';
    };

    // ── Process submission ─────────────────────────────────────────────────
    const submit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!file) {
            toast({
                title: "File required",
                description: "Please select a file to upload first.",
                variant: "destructive"
            });
            return;
        }

        if (!hasBalance) {
            toast({
                title: "Insufficient points",
                description: "You have 0 points. Please get points first.",
                variant: "destructive"
            });
            return;
        }

        setPhase('processing');
        setProcessing(true);
        setErrorMessage(null);

        const formData = new FormData();
        formData.append('file', file);

        try {
            const response = await axios.post(route('fbmb.process'), formData);
            setResult(response.data);
            setPhase('results');

            toast({
                title: "Lookup complete",
                description: `Found ${response.data.found_count} matches from ${response.data.total_ids} IDs.`,
            });
        } catch (error: any) {
            setPhase('upload');
            let message = "An error occurred while processing your file.";
            if (error.response?.data?.message) {
                message = error.response.data.message;
            }
            setErrorMessage(message);
            toast({
                title: "Processing failed",
                description: message,
                variant: "destructive"
            });
        } finally {
            setProcessing(false);
        }
    };

    const triggerDownload = () => {
        if (!result?.download_token) return;
        window.open(route('fbmb.download', { token: result.download_token }), '_blank');
    };

    const startNewLookup = () => {
        setFile(null);
        setEstimatedIds(0);
        setResult(null);
        setErrorMessage(null);
        setPhase('upload');
        if (fileInputRef.current) fileInputRef.current.value = '';
    };

    // ── Format helpers ─────────────────────────────────────────────────────
    const formatFileSize = (bytes: number) => {
        if (bytes < 1024) return `${bytes} B`;
        if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
        return `${(bytes / 1048576).toFixed(1)} MB`;
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">iSAAS Database Lookup</h2>}
        >
            <Head title="iSAAS Database Lookup" />

            <div className="py-8 md:py-12">
                <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

                    {/* ── Hero Header ── */}
                    <div className="relative mb-8">
                        <div className="absolute -top-4 -left-4 w-24 h-24 bg-indigo-500/10 rounded-full blur-2xl" />
                        <div className="absolute -top-2 right-8 w-16 h-16 bg-purple-500/10 rounded-full blur-xl" />
                        <div className="relative">
                            <div className="flex items-center gap-3 mb-2">
                                <div className="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 shadow-lg shadow-indigo-500/25">
                                    <Database className="w-5 h-5 text-white" />
                                </div>
                                <div>
                                    <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
                                        iSAAS Database Lookup
                                    </h1>
                                    <p className="text-sm text-slate-500">
                                        Upload Facebook IDs → Get Mobile Numbers instantly
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* ── Stats Bar ── */}
                    <div className="grid grid-cols-2 gap-3 mb-6">
                        {/* Wallet Balance */}
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
                            <p className={cn(
                                "text-2xl font-bold font-mono tracking-tight",
                                hasBalance ? "text-emerald-700" : "text-amber-700"
                            )}>
                                {Number(pointsBalance || 0).toLocaleString()} <span className="text-sm font-normal text-slate-400">Pts</span>
                            </p>
                            {!hasBalance && (
                                <p className="text-xs text-amber-600 mt-1 flex items-center gap-1">
                                    <AlertCircle className="w-3 h-3" /> Get points to start lookups
                                </p>
                            )}
                            <div className={cn(
                                "absolute -right-2 -bottom-2 w-16 h-16 rounded-full opacity-10",
                                hasBalance ? "bg-emerald-500" : "bg-amber-500"
                            )} />
                        </div>

                        {/* Cost Info */}
                        <div className="relative overflow-hidden rounded-xl border border-indigo-200 bg-gradient-to-br from-indigo-50 to-white p-4">
                            <div className="flex items-center gap-2 mb-1">
                                <Zap className="w-4 h-4 text-indigo-500" />
                                <span className="text-xs font-medium text-slate-500 uppercase tracking-wider">Cost Per Match</span>
                            </div>
                            <p className="text-2xl font-bold font-mono tracking-tight text-indigo-700">
                                1 <span className="text-sm font-normal text-slate-400">Point</span>
                            </p>
                            <p className="text-xs text-slate-500 mt-1">Only charged for successful matches</p>
                            <div className="absolute -right-2 -bottom-2 w-16 h-16 rounded-full bg-indigo-500 opacity-10" />
                        </div>
                    </div>

                    {/* ── Step Indicator ── */}
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
                                        <div className={cn(
                                            "flex-1 h-px transition-colors duration-500",
                                            isPast ? "bg-indigo-400" : "bg-slate-200"
                                        )} />
                                    )}
                                    <div className={cn(
                                        "flex items-center gap-1.5 text-xs font-medium transition-all duration-300",
                                        isActive ? "text-indigo-600 scale-105" : isPast ? "text-indigo-400" : "text-slate-400"
                                    )}>
                                        <div className={cn(
                                            "flex items-center justify-center w-6 h-6 rounded-full transition-all duration-300",
                                            isActive
                                                ? "bg-indigo-100 ring-2 ring-indigo-500 ring-offset-1"
                                                : isPast ? "bg-indigo-100" : "bg-slate-100"
                                        )}>
                                            <s.icon className={cn(
                                                "w-3 h-3",
                                                isActive && s.step === 'processing' && "animate-spin"
                                            )} />
                                        </div>
                                        <span className="hidden sm:inline">{s.label}</span>
                                    </div>
                                </React.Fragment>
                            );
                        })}
                    </div>

                    {/* ── Error Alert ── */}
                    {errorMessage && (
                        <Alert variant="destructive" className="mb-6 animate-in fade-in slide-in-from-top-2 duration-300">
                            <AlertCircle className="h-4 w-4" />
                            <AlertTitle>Error</AlertTitle>
                            <AlertDescription>{errorMessage}</AlertDescription>
                        </Alert>
                    )}

                    {/* ── Main Card ── */}
                    <div className="relative">
                        {/* Glow effect */}
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
                                {/* ── UPLOAD PHASE ── */}
                                {phase === 'upload' && (
                                    <form onSubmit={submit}>
                                        {/* Drop zone */}
                                        <div
                                            onDragEnter={handleDragEnter}
                                            onDragOver={handleDragOver}
                                            onDragLeave={handleDragLeave}
                                            onDrop={handleDrop}
                                            onClick={() => !file && fileInputRef.current?.click()}
                                            className={cn(
                                                "relative flex flex-col items-center justify-center rounded-xl border-2 border-dashed p-8 sm:p-10 transition-all duration-300 cursor-pointer group",
                                                isDragging
                                                    ? "border-indigo-400 bg-indigo-50/80 scale-[1.02]"
                                                    : file
                                                        ? "border-emerald-300 bg-emerald-50/50"
                                                        : "border-slate-300 bg-slate-50/50 hover:border-indigo-300 hover:bg-indigo-50/30"
                                            )}
                                        >
                                            <input
                                                ref={fileInputRef}
                                                type="file"
                                                accept=".txt,.csv"
                                                className="sr-only"
                                                onChange={handleInputChange}
                                            />

                                            {!file ? (
                                                <>
                                                    <div className={cn(
                                                        "flex items-center justify-center w-16 h-16 rounded-2xl mb-4 transition-all duration-300",
                                                        isDragging
                                                            ? "bg-indigo-100 scale-110"
                                                            : "bg-slate-100 group-hover:bg-indigo-100 group-hover:scale-105"
                                                    )}>
                                                        <UploadCloud className={cn(
                                                            "w-8 h-8 transition-colors duration-300",
                                                            isDragging
                                                                ? "text-indigo-500"
                                                                : "text-slate-400 group-hover:text-indigo-500"
                                                        )} />
                                                    </div>
                                                    <p className="text-sm font-medium text-slate-700 mb-1">
                                                        {isDragging ? 'Drop your file here' : 'Click to upload or drag and drop'}
                                                    </p>
                                                    <p className="text-xs text-slate-500">
                                                        Supports <span className="font-medium">.txt</span> and <span className="font-medium">.csv</span> files up to 10 MB
                                                    </p>
                                                </>
                                            ) : (
                                                <div className="flex items-center gap-4 w-full" onClick={(e) => e.stopPropagation()}>
                                                    <div className="flex items-center justify-center w-12 h-12 rounded-xl bg-emerald-100 shrink-0">
                                                        <FileText className="w-6 h-6 text-emerald-600" />
                                                    </div>
                                                    <div className="flex-1 min-w-0">
                                                        <p className="text-sm font-semibold text-slate-800 truncate">
                                                            {file.name}
                                                        </p>
                                                        <div className="flex items-center gap-3 mt-0.5">
                                                            <span className="text-xs text-slate-500">
                                                                {formatFileSize(file.size)}
                                                            </span>
                                                            {estimatedIds > 0 && (
                                                                <span className="text-xs text-indigo-600 font-medium flex items-center gap-1">
                                                                    <FileSearch className="w-3 h-3" />
                                                                    ~{estimatedIds.toLocaleString()} IDs detected
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        onClick={removeFile}
                                                        className="flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 transition-all shrink-0"
                                                    >
                                                        <X className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            )}
                                        </div>

                                        {/* Pre-processing estimate */}
                                        {file && estimatedIds > 0 && (
                                            <div className="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3 animate-in fade-in slide-in-from-bottom-2 duration-300">
                                                <div className="flex items-start gap-2.5">
                                                    <Info className="w-4 h-4 text-indigo-500 mt-0.5 shrink-0" />
                                                    <div className="text-xs text-slate-600 space-y-1">
                                                        <p>
                                                            <span className="font-medium text-slate-700">Estimated IDs:</span> {estimatedIds.toLocaleString()}
                                                        </p>
                                                        <p>
                                                            <span className="font-medium text-slate-700">Max cost:</span>{' '}
                                                            <span className="font-mono">{estimatedIds.toLocaleString()}</span> points
                                                        <span className="text-slate-400 ml-1">(only matched IDs are charged)</span>
                                                    </p>
                                                    {estimatedIds > pointsBalance && (
                                                        <p className="text-amber-600 flex items-center gap-1 font-medium">
                                                            <AlertCircle className="w-3 h-3" />
                                                            Your points may not cover all matches. Partial results may be available.
                                                        </p>
                                                    )}
                                                    </div>
                                                </div>
                                            </div>
                                        )}

                                        {/* Submit button */}
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
                                                <Sparkles className="w-4 h-4 mr-2" />
                                                Start Lookup
                                            </Button>

                                            {!hasBalance && (
                                                <p className="text-xs text-amber-600 flex items-center gap-1">
                                                    <AlertCircle className="w-3 h-3 shrink-0" />
                                                    Get points to use this feature
                                                </p>
                                            )}
                                        </div>
                                    </form>
                                )}

                                {/* ── PROCESSING PHASE ── */}
                                {phase === 'processing' && (
                                    <div className="flex flex-col items-center justify-center py-12 animate-in fade-in duration-500">
                                        <div className="relative">
                                            <div className="absolute inset-0 rounded-full bg-indigo-500/20 animate-ping" />
                                            <div className="relative flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100">
                                                <Loader2 className="w-8 h-8 text-indigo-600 animate-spin" />
                                            </div>
                                        </div>
                                        <h3 className="mt-6 text-lg font-semibold text-slate-800">Processing your file...</h3>
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

                                {/* ── RESULTS PHASE ── */}
                                {phase === 'results' && result && (
                                    <div className="animate-in fade-in slide-in-from-bottom-4 duration-500">
                                        {/* Success banner */}
                                        <div className="flex items-center gap-3 p-4 rounded-xl bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200 mb-6">
                                            <div className="flex items-center justify-center w-10 h-10 rounded-full bg-emerald-100">
                                                <CheckCircle2 className="w-5 h-5 text-emerald-600" />
                                            </div>
                                            <div>
                                                <h3 className="text-sm font-semibold text-emerald-800">Lookup Complete</h3>
                                                <p className="text-xs text-emerald-600">Your results are ready to download.</p>
                                            </div>
                                        </div>

                                        {/* Stats grid */}
                                        <div className="grid grid-cols-3 gap-3 mb-6">
                                            <div className="rounded-xl border border-slate-200 bg-white p-4 text-center">
                                                <p className="text-xs text-slate-500 font-medium uppercase tracking-wider mb-1">Total IDs</p>
                                                <p className="text-2xl font-bold font-mono text-slate-800">
                                                    {result.total_ids.toLocaleString()}
                                                </p>
                                            </div>
                                            <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-center">
                                                <p className="text-xs text-emerald-600 font-medium uppercase tracking-wider mb-1">Matches</p>
                                                <p className="text-2xl font-bold font-mono text-emerald-700">
                                                    {result.found_count.toLocaleString()}
                                                </p>
                                            </div>
                                            <div className="rounded-xl border border-indigo-200 bg-indigo-50 p-4 text-center">
                                                <p className="text-xs text-indigo-600 font-medium uppercase tracking-wider mb-1">Points Used</p>
                                                <p className="text-2xl font-bold font-mono text-indigo-700">
                                                    {result.credits_used.toLocaleString()}
                                                </p>
                                            </div>
                                        </div>

                                        {/* Match rate */}
                                        <div className="mb-6">
                                            <div className="flex items-center justify-between text-xs text-slate-500 mb-1.5">
                                                <span>Match Rate</span>
                                                <span className="font-mono font-medium text-slate-700">
                                                    {result.total_ids > 0 ? ((result.found_count / result.total_ids) * 100).toFixed(1) : 0}%
                                                </span>
                                            </div>
                                            <div className="h-2 rounded-full bg-slate-100 overflow-hidden">
                                                <div
                                                    className="h-full rounded-full bg-gradient-to-r from-indigo-500 to-emerald-500 transition-all duration-1000 ease-out"
                                                    style={{
                                                        width: `${result.total_ids > 0 ? (result.found_count / result.total_ids) * 100 : 0}%`
                                                    }}
                                                />
                                            </div>
                                        </div>

                                        {/* Remaining balance */}
                                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-3 mb-6">
                                            <div className="flex items-center justify-between text-sm">
                                                <span className="text-slate-500 flex items-center gap-1.5">
                                                    <Wallet className="w-3.5 h-3.5" /> Remaining Points
                                                </span>
                                                <span className="font-mono font-semibold text-slate-800">
                                                    {result.remaining_balance.toLocaleString()} Pts
                                                </span>
                                            </div>
                                        </div>

                                        {/* Action buttons */}
                                        <div className="flex flex-col sm:flex-row gap-3">
                                            {result.found_count > 0 && (
                                                <Button
                                                    onClick={triggerDownload}
                                                    className="flex-1 h-11 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white shadow-lg shadow-emerald-500/25 hover:shadow-xl hover:shadow-emerald-500/30 hover:-translate-y-px transition-all font-semibold"
                                                >
                                                    <Download className="w-4 h-4 mr-2" />
                                                    Download Results ({result.found_count} records)
                                                </Button>
                                            )}
                                            <Button
                                                onClick={startNewLookup}
                                                variant="outline"
                                                className="h-11 rounded-xl"
                                            >
                                                <ArrowRight className="w-4 h-4 mr-2" />
                                                New Lookup
                                            </Button>
                                        </div>

                                        {result.found_count === 0 && (
                                            <div className="mt-4 p-4 rounded-xl border border-amber-200 bg-amber-50">
                                                <p className="text-sm text-amber-700 flex items-center gap-2">
                                                    <AlertCircle className="w-4 h-4 shrink-0" />
                                                    No matches were found. No points were deducted.
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* ── Info Footer ── */}
                    <div className="mt-6 text-center">
                        <p className="text-xs text-slate-400">
                            Data is processed securely and results are not stored permanently.
                        </p>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

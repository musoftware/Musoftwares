import React, { useState, useRef, useEffect } from 'react';
import {
    Users, MessageSquare, Sparkles, ShieldCheck, Send,
    Upload, FileText, FileSpreadsheet, Trash2, Edit3, FileArchive,
    Clock, Zap, Sliders, Check, HelpCircle, AlertTriangle, AlertCircle,
    Info, HeartPulse, ChevronDown, ChevronUp, Settings, Play, CheckCircle2,
    UserCheck, Timer, Tag, ChevronLeft, ChevronRight, ArrowRight, ArrowLeft,
    Rocket, Eye, CalendarClock, Contact, Search
} from 'lucide-react';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';
import { __ } from '@/lib/i18n';

// ── Step indicator component ─────────────────────────────────────────────
function StepIndicator({ steps, currentStep, locale }: { steps: { label: string; icon: any }[]; currentStep: number; locale: string }) {
    const isRtl = locale === 'ar';
    return (
        <div className="flex items-center justify-center gap-0 w-full select-none">
            {steps.map((step, idx) => {
                const StepIcon = step.icon;
                const isActive = idx === currentStep;
                const isCompleted = idx < currentStep;
                const isLast = idx === steps.length - 1;

                return (
                    <React.Fragment key={idx}>
                        <div className="flex items-center gap-2.5">
                            <div className={`flex items-center justify-center size-10 rounded-xl transition-all duration-500 shrink-0 ${
                                isActive
                                    ? 'bg-teal-600 text-white shadow-lg shadow-teal-500/20 scale-105'
                                    : isCompleted
                                        ? 'bg-emerald-100 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400'
                                        : 'bg-muted/50 text-muted-foreground'
                            }`}>
                                {isCompleted ? (
                                    <Check className="w-4.5 h-4.5 stroke-[3]" />
                                ) : (
                                    <StepIcon className="w-4.5 h-4.5" />
                                )}
                            </div>
                            <span className={`hidden sm:block text-xs font-bold transition-colors duration-300 whitespace-nowrap ${
                                isActive ? 'text-teal-600 dark:text-teal-400' : isCompleted ? 'text-emerald-600 dark:text-emerald-400' : 'text-muted-foreground'
                            }`}>
                                {step.label}
                            </span>
                        </div>
                        {!isLast && (
                            <div className={`w-8 sm:w-14 h-0.5 mx-1.5 rounded-full transition-all duration-500 shrink-0 ${
                                isCompleted ? 'bg-emerald-400 dark:bg-emerald-600' : 'bg-muted'
                            }`} />
                        )}
                    </React.Fragment>
                );
            })}
        </div>
    );
}

export default function CampaignWorkspace({
    t, locale, contactsText, setContactsText, getParsedRecipients,
    templates, selectedTemplateId, setSelectedTemplateId,
    minWpm, setMinWpm, maxWpm, setMaxWpm, typoChance, setTypoChance,
    useSynonyms, setUseSynonyms, bellCurve, setBellCurve,
    trackDelivery, setTrackDelivery, stopOnBlock, setStopOnBlock, maxBlockRate, setMaxBlockRate,
    campaignName, setCampaignName, selectedAccount, setSelectedAccount, sessions,
    handleLaunchCampaign, handleSendTestMessage, isCampaignRunning, onEditTemplate,
    callRPC, followUpData, clearFollowUpData
}: any) {
    const [currentStep, setCurrentStep] = useState(0);
    const [dripSteps, setDripSteps] = useState<any[]>([]);
    const [inputMethod, setInputMethod]   = useState<'paste' | 'file' | 'contacts'>('paste');
    const [uploadedFile, setUploadedFile] = useState<{ name: string; size: number; type: string } | null>(null);
    const [isDragging, setIsDragging]     = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);
    const dragCounter = useRef(0);

    // Contacts DB integration
    const [dbContacts, setDbContacts] = useState<any[]>([]);
    const [dbTags, setDbTags] = useState<string[]>([]);
    const [dbSearch, setDbSearch] = useState('');
    const [dbTagFilter, setDbTagFilter] = useState('');
    const [dbSelected, setDbSelected] = useState<Set<string>>(new Set());
    const [dbLoading, setDbLoading] = useState(false);

    const [showTestInput, setShowTestInput] = useState(false);
    const [testNumber, setTestNumber] = useState('');
    const [isSendingTest, setIsSendingTest] = useState(false);
    const [testSendStatus, setTestSendStatus] = useState<{ type: 'success' | 'error'; message: string } | null>(null);

    // Schedule state
    const [scheduleMode, setScheduleMode] = useState(false);
    const [scheduleDate, setScheduleDate] = useState('');
    const [scheduleTime, setScheduleTime] = useState('');
    const [isScheduling, setIsScheduling] = useState(false);

    // Recurring state
    const [isRecurring, setIsRecurring] = useState(false);
    const [recurrencePattern, setRecurrencePattern] = useState('daily');
    const [selectedDays, setSelectedDays] = useState<number[]>([]);

    // A/B Testing state
    const [abEnabled, setAbEnabled] = useState(false);
    const [abSplitRatio, setAbSplitRatio] = useState(50);
    const [abVariantBMessage, setAbVariantBMessage] = useState('');

    const isRtl = locale === 'ar';

    // ── Follow-up data injection ─────────────────────────────────────────
    useEffect(() => {
        if (followUpData?.followUp && followUpData.contacts?.length > 0) {
            const name = `${isRtl ? 'متابعة' : 'Follow-up'}: ${followUpData.originalCampaignName || ''}`.trim();
            setCampaignName(name);
            // Convert follow-up contacts to CSV format
            const csv = followUpData.contacts.map((c: any) => `${c.phone},${c.name || ''}`).join('\n');
            setContactsText(csv);
            clearFollowUpData?.();
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [followUpData]);

    // ── Contacts DB functions ────────────────────────────────────────────
    const fetchDbContacts = async () => {
        setDbLoading(true);
        try {
            const res: any = await callRPC('getContacts', {
                search: dbSearch || undefined,
                tag: dbTagFilter || undefined,
                limit: 500,
                offset: 0,
            });
            setDbContacts(res.contacts || []);
        } catch (err: any) {
            console.error('Failed to fetch contacts:', err);
        }
        setDbLoading(false);
    };

    const fetchDbTags = async () => {
        try {
            const res: any = await callRPC('getContactTags', {});
            setDbTags(res.tags || []);
        } catch { /* empty */ }
    };

    useEffect(() => {
        if (inputMethod === 'contacts') {
            fetchDbContacts();
            fetchDbTags();
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [inputMethod, dbSearch, dbTagFilter]);

    const toggleContactSelection = (phone: string) => {
        setDbSelected(prev => {
            const next = new Set(prev);
            if (next.has(phone)) next.delete(phone); else next.add(phone);
            return next;
        });
    };

    const selectAllContacts = () => {
        if (dbSelected.size === dbContacts.length) {
            setDbSelected(new Set());
        } else {
            setDbSelected(new Set(dbContacts.map(c => c.phone_number)));
        }
    };

    const importSelectedContacts = () => {
        const selected = dbContacts.filter(c => dbSelected.has(c.phone_number));
        if (selected.length === 0) return;
        const lines = selected.map(c => `${c.phone_number},${c.name || ''}`);
        const existing = contactsText.trim();
        setContactsText(existing ? existing + '\n' + lines.join('\n') : lines.join('\n'));
        setInputMethod('paste');
        setDbSelected(new Set());
    };

    const executeSendTest = async () => {
        if (!testNumber.trim()) {
            alert(isRtl ? 'يرجى إدخال رقم هاتف تجريبي صحيح.' : 'Please enter a valid test phone number.');
            return;
        }
        setIsSendingTest(true);
        setTestSendStatus(null);
        try {
            await handleSendTestMessage(testNumber.trim());
            setTestSendStatus({
                type: 'success',
                message: isRtl ? 'تم إرسال الرسالة التجريبية بنجاح!' : 'Test message sent successfully!'
            });
            setTimeout(() => setTestSendStatus(null), 4000);
        } catch (err: any) {
            setTestSendStatus({
                type: 'error',
                message: isRtl ? `فشل الإرسال: ${err.message}` : `Send failed: ${err.message}`
            });
        } finally {
            setIsSendingTest(false);
        }
    };

    const [customMin, setCustomMin] = useState(() => Math.round(600 / (maxWpm || 75)));
    const [customMax, setCustomMax] = useState(() => Math.round(600 / (minWpm || 40)));

    React.useEffect(() => {
        const calculatedMin = Math.round(600 / maxWpm);
        const calculatedMax = Math.round(600 / minWpm);
        setCustomMin(calculatedMin);
        setCustomMax(calculatedMax);
    }, [minWpm, maxWpm]);

    const updateCustomMin = (val: number) => {
        const minVal = Math.max(1, val);
        setCustomMin(minVal);
        const maxVal = Math.max(minVal + 1, customMax);
        setCustomMax(maxVal);
        const calculatedMinWpm = Math.round(600 / maxVal);
        const calculatedMaxWpm = Math.round(600 / minVal);
        setMinWpm(calculatedMinWpm);
        setMaxWpm(calculatedMaxWpm);
    };

    const updateCustomMax = (val: number) => {
        const maxVal = Math.max(customMin + 1, val);
        setCustomMax(maxVal);
        const calculatedMinWpm = Math.round(600 / maxVal);
        const calculatedMaxWpm = Math.round(600 / customMin);
        setMinWpm(calculatedMinWpm);
        setMaxWpm(calculatedMaxWpm);
    };

    const formatFileSize = (bytes: number) => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    const processFile = (file: File) => {
        setUploadedFile({
            name: file.name,
            size: file.size,
            type: file.name.endsWith('.csv') ? 'csv' : 'txt'
        });

        const reader = new FileReader();
        reader.onload = (event) => {
            const text = event.target?.result as string;
            if (!text) return;

            const parsedRows: string[] = [];
            const lines = text.split(/\r?\n/);

            if (file.name.endsWith('.csv')) {
                let isFirst = true;
                for (let line of lines) {
                    line = line.trim();
                    if (!line) continue;
                    
                    const parts = line.split(',').map(p => p.replace(/^["']|["']$/g, '').trim());
                    if (isFirst) {
                        isFirst = false;
                        const firstPartClean = parts[0]?.toLowerCase().replace(/[^a-z]/g, '');
                        if (firstPartClean === 'phone' || firstPartClean === 'number' || isNaN(Number(parts[0]?.replace(/[^0-9+]/g, '')))) {
                            continue; // Skip header
                        }
                    }
                    
                    const phone = parts[0] || '';
                    const name = parts[1] || '';
                    const company = parts[2] || '';
                    if (phone) {
                        parsedRows.push(`${phone},${name},${company}`);
                    }
                }
            } else {
                for (let line of lines) {
                    line = line.trim();
                    if (!line) continue;
                    const separator = line.includes(';') ? ';' : ',';
                    const parts = line.split(separator).map(p => p.trim());
                    const phone = parts[0] || '';
                    const name = parts[1] || '';
                    const company = parts[2] || '';
                    if (phone) {
                        parsedRows.push(`${phone},${name},${company}`);
                    }
                }
            }

            setContactsText(parsedRows.join('\n'));
        };
        reader.readAsText(file);
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) processFile(file);
    };

    const handleClearFile = () => {
        setUploadedFile(null);
        setContactsText('');
        if (fileInputRef.current) fileInputRef.current.value = '';
    };

    const handleDragEnter = (e: React.DragEvent) => {
        e.preventDefault();
        dragCounter.current++;
        if (dragCounter.current === 1) {
            setIsDragging(true);
        }
    };

    const handleDragLeave = (e: React.DragEvent) => {
        e.preventDefault();
        dragCounter.current--;
        if (dragCounter.current === 0) {
            setIsDragging(false);
        }
    };

    const handleDragOver = (e: React.DragEvent) => {
        e.preventDefault();
    };

    const handleDrop = (e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(false);
        dragCounter.current = 0;
        const file = e.dataTransfer.files?.[0];
        if (file) {
            setInputMethod('file');
            processFile(file);
        }
    };

    const renderMessageText = (text: string) => {
        if (!text) return '';
        const parts = text.split(/(\{name\}|\{phone\}|\{company\})/i);
        return parts.map((part, index) => {
            const lower = part.toLowerCase();
            if (lower === '{name}') {
                return (
                    <span key={index} className="inline-block px-1.5 py-0.5 rounded bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-200 font-bold border border-teal-200 dark:border-teal-800 text-[10px] mx-0.5 shadow-sm">
                        {isRtl ? 'الاسم' : '{name}'}
                    </span>
                );
            }
            if (lower === '{phone}') {
                return (
                    <span key={index} className="inline-block px-1.5 py-0.5 rounded bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-200 font-bold border border-orange-200 dark:border-orange-800 text-[10px] mx-0.5 shadow-sm">
                        {isRtl ? 'الهاتف' : '{phone}'}
                    </span>
                );
            }
            if (lower === '{company}') {
                return (
                    <span key={index} className="inline-block px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200 font-bold border border-blue-200 dark:border-blue-800 text-[10px] mx-0.5 shadow-sm">
                        {isRtl ? 'الشركة' : '{company}'}
                    </span>
                );
            }
            return part;
        });
    };

    const selectedTemplate = templates.find((tpl: any) => tpl.id === selectedTemplateId);

    // ── Step definitions ─────────────────────────────────────────────────
    const steps = [
        { label: isRtl ? 'جهات الاتصال والقالب' : 'Contacts & Template', icon: Users },
        { label: isRtl ? 'حملات المتابعة' : 'Drip Sequences',           icon: Timer },
        { label: isRtl ? 'سرعة الإرسال والأمان' : 'Pacing & Safety',     icon: ShieldCheck },
        { label: isRtl ? 'المراجعة والإطلاق' : 'Review & Launch',       icon: Rocket },
    ];

    const canGoNext = () => {
        if (currentStep === 0) return getParsedRecipients.length > 0 && !!selectedTemplateId;
        if (currentStep === 1) return true;
        return true;
    };

    // ── Active Preset calculation (reused) ───────────────────────────────
    const activePreset = ((minWpm === 45 || minWpm === 40) && maxWpm === 75) ? 'safe' :
                         (minWpm === 20 && maxWpm === 40) ? 'cautious' :
                         (minWpm === 100 && maxWpm === 200) ? 'turbo' : 'custom';

    const getPresetLabel = () => {
        if (activePreset === 'safe') return isRtl ? 'آمن وطبيعي (8-15 ث)' : 'Safe & Natural (8-15s)';
        if (activePreset === 'cautious') return isRtl ? 'حذر للغاية (15-30 ث)' : 'Ultra Cautious (15-30s)';
        if (activePreset === 'turbo') return isRtl ? 'سريع جداً (3-6 ث) ⚠' : 'Turbo Speed (3-6s) ⚠';
        return isRtl ? `مخصص (${customMin}-${customMax} ث)` : `Custom (${customMin}-${customMax}s)`;
    };

    return (
        <div className="space-y-6 animate-in fade-in duration-300">
            {/* ── Step Header ──────────────────────────────────────────────────── */}
            <div className="space-y-5">
                <div className="text-center">
                    <h2 className="text-xl font-bold tracking-tight">
                        {isRtl ? 'إنشاء حملة جديدة' : 'Create New Campaign'}
                    </h2>
                    <p className="text-sm text-muted-foreground mt-1">
                        {isRtl ? 'اتبع الخطوات لإطلاق حملتك بأمان وفعالية' : 'Follow the steps to launch your campaign safely and effectively'}
                    </p>
                </div>

                {/* Step indicator bar */}
                <Card className="border-none shadow-none bg-muted/30 rounded-2xl">
                    <CardContent className="p-4">
                        <StepIndicator steps={steps} currentStep={currentStep} locale={locale} />
                    </CardContent>
                </Card>
            </div>

            {/* ══════════════════════════════════════════════════════════════════ */}
            {/* STEP 1: Contacts & Template                                      */}
            {/* ══════════════════════════════════════════════════════════════════ */}
            {currentStep === 0 && (
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 animate-in fade-in slide-in-from-bottom-2 duration-400">
                    {/* Left: Contacts */}
                    <Card
                        onDragEnter={handleDragEnter}
                        onDragOver={handleDragOver}
                        onDragLeave={handleDragLeave}
                        onDrop={handleDrop}
                        className={`rounded-2xl transition-all duration-300 relative overflow-hidden ${
                            isDragging 
                                ? 'ring-2 ring-teal-500 border-teal-500 bg-teal-50/5 dark:bg-teal-950/5 scale-[1.01] shadow-lg' 
                                : ''
                        }`}
                    >
                        <CardHeader className="pb-4 border-b">
                            <div className="flex items-center justify-between">
                                <CardTitle className="text-base flex items-center gap-2">
                                    <div className="size-8 rounded-xl bg-teal-100/60 dark:bg-teal-950/40 flex items-center justify-center">
                                        <Users className="w-4 h-4 text-teal-600" />
                                    </div>
                                    {t.campaign.contactsLabel}
                                </CardTitle>
                                {getParsedRecipients.length > 0 && (
                                    <Badge variant="secondary" className="bg-teal-50 text-teal-700 hover:bg-teal-100 dark:bg-teal-950/30 dark:text-teal-300 font-bold">
                                        {getParsedRecipients.length} {t.campaign.parsedContacts}
                                    </Badge>
                                )}
                            </div>
                        </CardHeader>
                        <CardContent className="pt-6 space-y-4 relative">
                            {isDragging && (
                                <div className="absolute inset-0 bg-background/90 backdrop-blur-[2px] z-50 flex flex-col items-center justify-center gap-3 animate-in fade-in duration-200 pointer-events-none select-none">
                                    <div className="size-16 rounded-full bg-teal-50 dark:bg-teal-950/40 border border-teal-200 dark:border-teal-800 flex items-center justify-center text-teal-600 animate-bounce">
                                        <Upload className="size-7" />
                                    </div>
                                    <div className="text-center space-y-1">
                                        <p className="text-base font-bold text-teal-600">
                                            {isRtl ? 'أفلت ملف الأرقام هنا فوراً' : 'Drop your contacts file here'}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {isRtl ? 'سيتم تلقائياً استخراج جهات الاتصال' : 'Contacts will be automatically imported'}
                                        </p>
                                    </div>
                                </div>
                            )}

                            {/* Selector Tabs */}
                            <div className="grid grid-cols-3 p-1 bg-muted rounded-xl text-xs font-semibold">
                                <button
                                    type="button"
                                    onClick={() => setInputMethod('paste')}
                                    className={`py-2 rounded-lg transition-all flex items-center justify-center gap-1.5 ${inputMethod === 'paste' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'}`}
                                >
                                    <Users className="size-3.5" />
                                    {isRtl ? 'لصق يدوي' : 'Paste'}
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setInputMethod('file')}
                                    className={`py-2 rounded-lg transition-all flex items-center justify-center gap-1.5 ${inputMethod === 'file' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'}`}
                                >
                                    <Upload className="size-3.5" />
                                    {isRtl ? 'ملف' : 'File'}
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setInputMethod('contacts')}
                                    className={`py-2 rounded-lg transition-all flex items-center justify-center gap-1.5 ${inputMethod === 'contacts' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'}`}
                                >
                                    <Contact className="size-3.5" />
                                    {isRtl ? 'جهات الاتصال' : 'Contacts'}
                                </button>
                            </div>

                            {inputMethod === 'paste' && (
                                <Textarea
                                    rows={7}
                                    value={contactsText}
                                    onChange={e => setContactsText(e.target.value)}
                                    placeholder={t.campaign.contactsPlaceholder}
                                    className="font-mono text-xs resize-none focus-visible:ring-teal-500 rounded-xl"
                                />
                            )}

                            {inputMethod === 'file' && (
                                <div className="space-y-4">
                                    {!uploadedFile ? (
                                        <div
                                            onClick={() => fileInputRef.current?.click()}
                                            className="border-2 border-dashed rounded-2xl p-8 text-center cursor-pointer transition-all duration-200 flex flex-col items-center justify-center gap-3 border-muted-foreground/20 hover:border-teal-400 hover:bg-muted/30"
                                        >
                                            <div className="size-14 rounded-2xl bg-teal-50 dark:bg-teal-950/30 flex items-center justify-center text-teal-600">
                                                <Upload className="size-6" />
                                            </div>
                                            <div className="space-y-1">
                                                <p className="text-sm font-bold">
                                                    {isRtl ? 'انقر لتصفح ملف الأرقام' : 'Click to browse contacts file'}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {isRtl ? 'يدعم ملفات TXT أو CSV' : 'Supports TXT or CSV files'}
                                                </p>
                                            </div>
                                            <input
                                                type="file"
                                                ref={fileInputRef}
                                                onChange={handleFileChange}
                                                accept=".txt,.csv"
                                                className="hidden"
                                            />
                                        </div>
                                    ) : (
                                        <div className="border rounded-2xl p-4 bg-muted/30 flex items-center gap-3.5 relative overflow-hidden group">
                                            <div className="absolute top-0 start-0 w-1.5 h-full bg-teal-500" />
                                            <div className="size-10 rounded-lg bg-teal-50 dark:bg-teal-950/40 flex items-center justify-center text-teal-600 shrink-0">
                                                {uploadedFile.type === 'csv' 
                                                    ? <FileSpreadsheet className="size-5" /> 
                                                    : <FileText className="size-5" />
                                                }
                                            </div>
                                            <div className="flex-1 min-w-0 space-y-0.5">
                                                <p className="text-sm font-bold truncate pe-6 text-start">{uploadedFile.name}</p>
                                                <p className="text-xs text-muted-foreground flex items-center gap-2">
                                                    <span>{formatFileSize(uploadedFile.size)}</span>
                                                    <span>•</span>
                                                    <span className="capitalize font-semibold text-teal-600">{uploadedFile.type} format</span>
                                                </p>
                                            </div>
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                onClick={handleClearFile}
                                                className="size-8 text-muted-foreground hover:text-destructive hover:bg-destructive/10 rounded-lg shrink-0"
                                            >
                                                <Trash2 className="size-4" />
                                            </Button>
                                        </div>
                                    )}
                                </div>
                            )}

                            {inputMethod === 'contacts' && (
                                <div className="space-y-3 animate-in fade-in duration-200">
                                    {/* Search + Tag Filter */}
                                    <div className="flex gap-2">
                                        <div className="relative flex-1">
                                            <Search className="absolute start-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-muted-foreground" />
                                            <Input
                                                value={dbSearch}
                                                onChange={e => setDbSearch(e.target.value)}
                                                placeholder={isRtl ? 'بحث...' : 'Search...'}
                                                className="ps-9 h-9 rounded-lg text-xs text-start"
                                            />
                                        </div>
                                        {dbTags.length > 0 && (
                                            <select
                                                value={dbTagFilter}
                                                onChange={e => setDbTagFilter(e.target.value)}
                                                className="h-9 rounded-lg border bg-background px-2 text-xs font-bold min-w-[100px]"
                                            >
                                                <option value="">{isRtl ? 'كل التاغات' : 'All Tags'}</option>
                                                {dbTags.map(tag => (
                                                    <option key={tag} value={tag}>{tag}</option>
                                                ))}
                                            </select>
                                        )}
                                    </div>

                                    {/* Select All + Count */}
                                    <div className="flex items-center justify-between">
                                        <button
                                            type="button"
                                            onClick={selectAllContacts}
                                            className="text-[11px] font-bold text-teal-600 hover:underline"
                                        >
                                            {dbSelected.size === dbContacts.length && dbContacts.length > 0
                                                ? (isRtl ? 'إلغاء تحديد الكل' : 'Deselect All')
                                                : (isRtl ? 'تحديد الكل' : 'Select All')
                                            }
                                        </button>
                                        <Badge variant="secondary" className="text-[10px] font-bold">
                                            {dbSelected.size}/{dbContacts.length} {isRtl ? 'محدد' : 'selected'}
                                        </Badge>
                                    </div>

                                    {/* Contact List */}
                                    <div className="max-h-52 overflow-y-auto border rounded-xl divide-y">
                                        {dbLoading ? (
                                            <div className="py-8 flex justify-center">
                                                <span className="size-6 border-2 border-teal-500 border-t-transparent rounded-full animate-spin" />
                                            </div>
                                        ) : dbContacts.length === 0 ? (
                                            <div className="py-6 text-center">
                                                <p className="text-xs text-muted-foreground">{isRtl ? 'لا توجد جهات اتصال' : 'No contacts found'}</p>
                                            </div>
                                        ) : (
                                            dbContacts.map(c => (
                                                <label
                                                    key={c.id || c.phone_number}
                                                    className={`flex items-center gap-3 px-3 py-2.5 cursor-pointer transition-colors hover:bg-muted/50 ${dbSelected.has(c.phone_number) ? 'bg-teal-50/50 dark:bg-teal-950/20' : ''}`}
                                                >
                                                    <input
                                                        type="checkbox"
                                                        checked={dbSelected.has(c.phone_number)}
                                                        onChange={() => toggleContactSelection(c.phone_number)}
                                                        className="rounded border-muted-foreground/30 text-teal-600 focus:ring-teal-500 size-3.5"
                                                    />
                                                    <div className="flex-1 min-w-0">
                                                        <div className="flex items-center gap-2">
                                                            <span className="font-mono text-[11px] font-bold">{c.phone_number}</span>
                                                            {c.name && <span className="text-[10px] text-muted-foreground truncate">{c.name}</span>}
                                                        </div>
                                                        {c.tags && (
                                                            <div className="flex gap-1 mt-0.5">
                                                                {(Array.isArray(c.tags) ? c.tags : String(c.tags).split(',')).filter(Boolean).slice(0, 3).map((tag: string) => (
                                                                    <span key={tag} className="text-[8px] bg-muted px-1.5 py-0.5 rounded font-bold">{String(tag).trim()}</span>
                                                                ))}
                                                            </div>
                                                        )}
                                                    </div>
                                                </label>
                                            ))
                                        )}
                                    </div>

                                    {/* Import Button */}
                                    {dbSelected.size > 0 && (
                                        <Button
                                            onClick={importSelectedContacts}
                                            className="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-xl gap-2 h-10 text-xs"
                                        >
                                            <CheckCircle2 className="w-4 h-4" />
                                            {isRtl
                                                ? `استيراد ${dbSelected.size} جهة اتصال`
                                                : `Import ${dbSelected.size} Contact${dbSelected.size > 1 ? 's' : ''}`
                                            }
                                        </Button>
                                    )}
                                </div>
                            )}

                            {/* Parsed recipients preview */}
                            {getParsedRecipients.length > 0 && (
                                <div className="max-h-36 overflow-y-auto border rounded-xl divide-y">
                                    {getParsedRecipients.slice(0, 8).map((c: any, idx: number) => (
                                        <div key={idx} className="p-2.5 flex items-center justify-between text-[11px] font-medium bg-muted/50">
                                            <span className="font-mono font-bold">{c.phone}</span>
                                            <span className="truncate max-w-[120px]">{c.name || '—'}</span>
                                            <span className="truncate max-w-[120px] text-muted-foreground">{c.company || '—'}</span>
                                        </div>
                                    ))}
                                    {getParsedRecipients.length > 8 && (
                                        <div className="p-2 text-center text-[10px] text-muted-foreground font-bold bg-muted/20">
                                            + {getParsedRecipients.length - 8} {isRtl ? 'رقم إضافي' : 'more recipients'}
                                        </div>
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Right: Template Selector + Preview */}
                    <Card className="rounded-2xl">
                        <CardHeader className="pb-4 border-b">
                            <CardTitle className="text-base flex items-center gap-2">
                                <div className="size-8 rounded-xl bg-teal-100/60 dark:bg-teal-950/40 flex items-center justify-center">
                                    <MessageSquare className="w-4 h-4 text-teal-600" />
                                </div>
                                {isRtl ? 'اختر قالب الرسالة' : 'Select Message Template'}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="pt-6 space-y-4">
                            {templates.length === 0 ? (
                                <div className="text-sm text-muted-foreground bg-muted p-6 rounded-2xl text-center space-y-2">
                                    <MessageSquare className="w-8 h-8 mx-auto text-muted-foreground/40" />
                                    <p className="font-bold">{isRtl ? 'لم يتم العثور على قوالب' : 'No templates found'}</p>
                                    <p className="text-xs">{isRtl ? 'يرجى إنشاء قالب في علامة تبويب القوالب أولاً!' : 'Please create one in the Templates tab first!'}</p>
                                </div>
                            ) : (
                                <div className="space-y-2.5 max-h-[300px] overflow-y-auto pe-1">
                                    {templates.map((tpl: any) => (
                                        <label key={tpl.id} className={`flex items-start gap-3 p-3.5 rounded-2xl border cursor-pointer transition-all duration-200 ${selectedTemplateId === tpl.id ? 'border-teal-500 bg-teal-50/50 dark:bg-teal-950/20 shadow-sm ring-1 ring-teal-500/20' : 'hover:border-teal-300 hover:bg-muted/30'}`}>
                                            <input
                                                type="radio"
                                                name="template_select"
                                                checked={selectedTemplateId === tpl.id}
                                                onChange={() => setSelectedTemplateId(tpl.id)}
                                                className="mt-1 text-teal-600 focus:ring-teal-500"
                                            />
                                            <div className="flex-1 min-w-0">
                                                <h4 className="font-bold text-sm">{tpl.name}</h4>
                                                <p className="text-xs text-muted-foreground line-clamp-2 mt-0.5">
                                                    {(tpl.parts || []).find((p: any) => p.type === 'text')?.message || tpl.message}
                                                </p>
                                                <div className="flex flex-wrap gap-1 mt-1.5">
                                                    {(tpl.parts || []).map((p: any, idx: number) => (
                                                        <Badge key={idx} variant="outline" className="text-[10px] gap-0.5 bg-teal-50/50 text-teal-700 dark:bg-teal-950/30 dark:text-teal-300">
                                                            {p.type === 'text' ? '📝' : p.type === 'image' ? '🖼️' : p.type === 'video' ? '🎬' : p.type === 'document' ? '📄' : p.type === 'audio' ? '🎵' : '📎'}
                                                            {t.templates.partTypes?.[p.type] || p.type}
                                                        </Badge>
                                                    ))}
                                                </div>
                                            </div>
                                        </label>
                                    ))}
                                </div>
                            )}

                            {/* WhatsApp Preview */}
                            {selectedTemplate && (
                                <div className="mt-4 border rounded-2xl overflow-hidden shadow-md">
                                    {/* Preview Header */}
                                    <div className="bg-teal-700 text-white px-4 py-3 flex items-center justify-between border-b border-teal-800">
                                        <div className="flex items-center gap-3">
                                            <div className="size-9 rounded-full bg-teal-600 border border-teal-500 flex items-center justify-center font-black text-xs shadow-sm">
                                                {__('general.wa')}</div>
                                            <div>
                                                <p className="text-xs font-black">{selectedTemplate.name}</p>
                                                <p className="text-[10px] text-teal-100">{isRtl ? 'معاينة الرسالة' : 'Live Preview'}</p>
                                            </div>
                                        </div>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            type="button"
                                            onClick={() => onEditTemplate && onEditTemplate(selectedTemplate.id)}
                                            className="text-white hover:bg-teal-800 hover:text-white h-8 gap-1.5 text-xs font-semibold px-3 rounded-lg border border-white/20"
                                        >
                                            <Edit3 className="size-3.5" />
                                            {isRtl ? 'تعديل' : 'Edit'}
                                        </Button>
                                    </div>

                                    {/* Chat Body */}
                                    <div className="p-4 bg-[#efeae2] dark:bg-[#0b141a] flex flex-col space-y-2 max-h-[350px] overflow-y-auto relative" style={{ backgroundImage: 'radial-gradient(circle, rgba(0, 0, 0, 0.03) 1px, transparent 1px)', backgroundSize: '20px 20px' }}>
                                        {(selectedTemplate.parts && selectedTemplate.parts.length > 0
                                            ? selectedTemplate.parts
                                            : [
                                                ...(selectedTemplate.message ? [{ type: 'text', message: selectedTemplate.message }] : []),
                                                ...(selectedTemplate.media_url ? [{ type: selectedTemplate.media_type, media_url: selectedTemplate.media_url }] : [])
                                              ]
                                        ).map((part: any, idx: number) => (
                                            <div key={idx} className={`max-w-[85%] ${isRtl ? 'self-start' : 'self-end'} relative rounded-2xl px-3.5 py-2.5 bg-[#d9fdd3] text-[#111b21] dark:bg-[#005c4b] dark:text-[#e9edef] shadow-md text-xs space-y-1 border border-[#d9fdd3]/20 dark:border-[#005c4b]/20`}>
                                                {part.type === 'text' && part.message && (
                                                    <p className="whitespace-pre-wrap leading-relaxed break-words font-sans text-[12px]">
                                                        {renderMessageText(part.message)}
                                                    </p>
                                                )}
                                                {part.type === 'image' && part.media_url && (
                                                    <div className="rounded-lg overflow-hidden border border-black/5 bg-black/5 dark:bg-black/20">
                                                        <img src={part.media_url} alt="" className="w-full max-h-48 object-cover" onError={(e) => { e.currentTarget.src = 'https://placehold.co/400x300?text=Image'; }} />
                                                        {part.caption && <p className="px-1 py-1 text-[11px]">{part.caption}</p>}
                                                    </div>
                                                )}
                                                {part.type === 'video' && part.media_url && (
                                                    <div className="rounded-lg overflow-hidden border border-black/5 bg-black/5 dark:bg-black/20">
                                                        <video src={part.media_url} className="w-full max-h-48 object-cover" controls />
                                                        {part.caption && <p className="px-1 py-1 text-[11px]">{part.caption}</p>}
                                                    </div>
                                                )}
                                                {part.type === 'audio' && part.media_url && (
                                                    <audio src={part.media_url} className="w-full p-1 scale-90" controls />
                                                )}
                                                {part.type === 'document' && part.media_url && (
                                                    <div className="p-3 flex items-center gap-2 bg-muted/40 rounded-lg text-start">
                                                        <FileArchive className="size-5 text-red-500 shrink-0" />
                                                        <div className="min-w-0">
                                                            <span className="font-mono text-[10px] truncate block max-w-[180px] font-bold">{part.media_url.split('/').pop()}</span>
                                                            {part.caption && <span className="text-[10px] text-muted-foreground">{part.caption}</span>}
                                                        </div>
                                                    </div>
                                                )}
                                                <div className="flex items-center justify-end gap-1 text-[9px] text-muted-foreground/80 self-end select-none">
                                                    <span>{new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                                                    <span className="text-teal-600 dark:text-teal-400 font-bold">✓✓</span>
                                                </div>
                                                {idx === 0 && (
                                                    <div 
                                                        className="absolute end-[-5px] origin-top-left top-0 w-3 h-3 bg-[#d9fdd3] dark:bg-[#005c4b] rotate-45 transform" 
                                                        style={{ clipPath: 'polygon(100% 0, 0 0, 100% 100%)' }} 
                                                    />
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            )}

            {/* ══════════════════════════════════════════════════════════════════ */}
            {/* STEP 1.5: Drip sequences builder (Milestone 3)                    */}
            {/* ══════════════════════════════════════════════════════════════════ */}
            {currentStep === 1 && (
                <div className="max-w-3xl mx-auto space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-400">
                    <Card className="rounded-2xl overflow-hidden border-teal-500/20">
                        <CardHeader className="pb-4 border-b bg-muted/20">
                            <CardTitle className="text-base flex items-center justify-between">
                                <span className="flex items-center gap-2">
                                    <div className="size-8 rounded-xl bg-teal-100/60 dark:bg-teal-950/40 flex items-center justify-center">
                                        <Timer className="w-4 h-4 text-teal-600" />
                                    </div>
                                    {isRtl ? 'بناء حملات المتابعة المتسلسلة (Drip)' : 'AI Drip Sequence & Follow-ups'}
                                </span>
                                <Badge variant="outline" className="border-teal-500/30 text-teal-600 bg-teal-50/50 dark:bg-teal-950/20 text-[10px] font-bold">
                                    {isRtl ? 'حملات تفاعلية' : 'Autonomous Sequences'}
                                </Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="pt-6 space-y-6 text-start">
                            {/* Explanatory Info Card */}
                            <div className="p-4 rounded-2xl bg-teal-50/40 dark:bg-teal-950/10 border border-teal-500/20 flex items-start gap-3">
                                <Info className="w-4.5 h-4.5 text-teal-600 shrink-0 mt-0.5" />
                                <div className="space-y-1">
                                    <p className="text-xs font-bold text-teal-800 dark:text-teal-400">
                                        {isRtl ? 'كيف تعمل المتابعة المتسلسلة التلقائية؟' : 'How does Automated Drip Sequences work?'}
                                    </p>
                                    <p className="text-[11px] text-slate-500 dark:text-slate-450 leading-relaxed font-semibold">
                                        {isRtl
                                            ? 'سيتم إرسال رسائل المتابعة تلقائياً في التواقيت المحددة للعملاء الذين لم يردوا على رسالتك الأساسية. يتم إيقاف وتجميد السلسلة فوراً بمجرد أن يقوم العميل بالرد لتجنب إزعاجه.'
                                            : 'Follow-up messages are automatically sent to target contacts at specified intervals if they haven\'t replied to your main outreach. The sequence immediately halts the moment a reply is intercepted to preserve customer comfort.'
                                        }
                                    </p>
                                </div>
                            </div>

                            {/* Opener message preview card */}
                            <div className="p-4 rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-900/10 space-y-2">
                                <div className="flex items-center justify-between">
                                    <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{isRtl ? 'الرسالة الافتتاحية الأساسية' : 'Opener Outreach Message'}</span>
                                    <Badge variant="secondary" className="text-[9px] font-bold bg-slate-100 dark:bg-slate-800">
                                        {isRtl ? 'الرسالة الافتتاحية' : 'Opener'}
                                    </Badge>
                                </div>
                                <p className="text-xs font-semibold text-slate-700 dark:text-slate-300 line-clamp-2">
                                    {selectedTemplate
                                        ? ((selectedTemplate.parts || []).find((p: any) => p.type === 'text')?.message || selectedTemplate.message || '')
                                        : (isRtl ? 'لم يتم تحديد قالب رسالة بعد.' : 'No template selected yet.')
                                    }
                                </p>
                            </div>

                            {/* Steps list flow */}
                            {dripSteps.length > 0 && (
                                <div className="space-y-5 relative ps-4 border-s-2 border-dashed border-teal-500/20 ms-2">
                                    {dripSteps.map((step, index) => (
                                        <div key={index} className="relative space-y-3 bg-white dark:bg-slate-950 p-4 border border-slate-100 dark:border-slate-800 rounded-2xl shadow-sm">
                                            {/* Dotted indicator dot */}
                                            <div className="absolute -start-[23px] top-7 w-3.5 h-3.5 rounded-full bg-teal-500 border-2 border-white dark:border-slate-950 flex items-center justify-center shadow" />
                                            
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-2">
                                                    <Badge className="bg-teal-500 text-white font-bold text-[9px] px-2 py-0.5 rounded">
                                                        {isRtl ? `خطوة المتابعة #${index + 1}` : `Follow-up Step #${index + 1}`}
                                                    </Badge>
                                                    <span className="text-[9px] uppercase font-bold text-rose-500 flex items-center gap-1">
                                                        <Zap className="w-3 h-3 fill-current animate-pulse" />
                                                        {isRtl ? 'إذا لم يرد العميل' : 'If No Reply'}
                                                    </span>
                                                </div>

                                                <Button
                                                    size="icon"
                                                    variant="ghost"
                                                    type="button"
                                                    onClick={() => {
                                                        const updated = dripSteps.filter((_, i) => i !== index).map((s, i) => ({ ...s, step_number: i + 1 }));
                                                        setDripSteps(updated);
                                                    }}
                                                    className="size-7 text-slate-400 hover:text-rose-500 rounded-lg"
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </div>

                                            {/* Delay and type parameters */}
                                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <div className="space-y-1.5">
                                                    <Label className="text-[10px] font-bold text-slate-500">{isRtl ? 'أرسل المتابعة بعد:' : 'Send follow-up after:'}</Label>
                                                    <select
                                                        value={step.delay_hours}
                                                        onChange={(e) => {
                                                            const val = Number(e.target.value);
                                                            setDripSteps(prev => prev.map((s, i) => i === index ? { ...s, delay_hours: val } : s));
                                                        }}
                                                        className="flex h-9 w-full rounded-lg border border-slate-200 dark:border-slate-800 bg-background px-3 py-1 text-xs ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 font-bold"
                                                    >
                                                        <option value={1}>{isRtl ? 'ساعة واحدة' : '1 Hour'}</option>
                                                        <option value={3}>{isRtl ? '3 ساعات' : '3 Hours'}</option>
                                                        <option value={6}>{isRtl ? '6 ساعات' : '6 Hours'}</option>
                                                        <option value={12}>{isRtl ? '12 ساعة' : '12 Hours'}</option>
                                                        <option value={24}>{isRtl ? '24 ساعة (يوم 1)' : '24 Hours (1 Day)'}</option>
                                                        <option value={48}>{isRtl ? '48 ساعة (يومين)' : '48 Hours (2 Days)'}</option>
                                                        <option value={72}>{isRtl ? '72 ساعة (3 أيام)' : '72 Hours (3 Days)'}</option>
                                                        <option value={120}>{isRtl ? '5 أيام' : '5 Days'}</option>
                                                    </select>
                                                </div>

                                                <div className="space-y-1.5">
                                                    <Label className="text-[10px] font-bold text-slate-500">{isRtl ? 'نوع الرسالة:' : 'Message Attachment:'}</Label>
                                                    <select
                                                        value={step.media_type}
                                                        onChange={(e) => {
                                                            const val = e.target.value;
                                                            setDripSteps(prev => prev.map((s, i) => i === index ? { ...s, media_type: val, media_url: val === 'text' ? '' : s.media_url } : s));
                                                        }}
                                                        className="flex h-9 w-full rounded-lg border border-slate-200 dark:border-slate-800 bg-background px-3 py-1 text-xs ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 font-bold"
                                                    >
                                                        <option value="text">{isRtl ? 'نص فقط' : 'Text only'}</option>
                                                        <option value="image">{isRtl ? 'صورة مرفقة' : 'Image attachment'}</option>
                                                        <option value="video">{isRtl ? 'فيديو مرفق' : 'Video attachment'}</option>
                                                        <option value="document">{isRtl ? 'ملف/مستند' : 'Document attachment'}</option>
                                                    </select>
                                                </div>
                                            </div>

                                            {/* Message Content Textarea */}
                                            <div className="space-y-1.5">
                                                <div className="flex items-center justify-between">
                                                    <Label className="text-[10px] font-bold text-slate-500">{isRtl ? 'محتوى رسالة المتابعة:' : 'Follow-up Message Content:'}</Label>
                                                    <span className="text-[9px] text-muted-foreground font-semibold">
                                                        {isRtl ? 'المتغيرات: {name}, {company}' : 'Variables: {name}, {company}'}
                                                    </span>
                                                </div>
                                                <Textarea
                                                    rows={3}
                                                    value={step.message}
                                                    onChange={(e) => {
                                                        const val = e.target.value;
                                                        setDripSteps(prev => prev.map((s, i) => i === index ? { ...s, message: val } : s));
                                                    }}
                                                    placeholder={isRtl ? 'أهلا {name}، أردت فقط التأكد من وصول عرضنا...' : 'Hey {name}, just checking in to see if you had any questions on our offer...'}
                                                    className="text-xs resize-none rounded-xl focus-visible:ring-teal-500 font-medium"
                                                />
                                            </div>

                                            {/* Media URL if not text */}
                                            {step.media_type !== 'text' && (
                                                <div className="space-y-1.5 animate-in slide-in-from-top-1 duration-200">
                                                    <Label className="text-[10px] font-bold text-slate-500">{isRtl ? 'رابط ملف الميديا المرفق:' : 'Attached Media URL:'}</Label>
                                                    <Input
                                                        type="text"
                                                        value={step.media_url}
                                                        onChange={(e) => {
                                                            const val = e.target.value;
                                                            setDripSteps(prev => prev.map((s, i) => i === index ? { ...s, media_url: val } : s));
                                                        }}
                                                        placeholder={__('general.https')}
                                                        className="h-9 text-xs focus-visible:ring-teal-500 border-slate-200 rounded-xl"
                                                    />
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* Add follow up button */}
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => {
                                    setDripSteps(prev => [
                                        ...prev,
                                        {
                                            step_number: prev.length + 1,
                                            delay_hours: 24,
                                            message: '',
                                            media_url: '',
                                            media_type: 'text'
                                        }
                                    ]);
                                }}
                                className="w-full h-11 border-dashed hover:border-teal-500/50 hover:bg-teal-500/[0.02] text-teal-600 rounded-2xl flex items-center justify-center gap-1.5 text-xs font-extrabold transition-all duration-300"
                            >
                                <Timer className="w-4 h-4 text-teal-600" />
                                {isRtl ? 'إضافة خطوة متابعة جديدة (Follow-up Step)' : 'Add Follow-up Drip Step'}
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            )}

            {/* ══════════════════════════════════════════════════════════════════ */}
            {/* STEP 2: Pacing & Safety                                          */}
            {/* ══════════════════════════════════════════════════════════════════ */}
            {currentStep === 2 && (
                <div className="max-w-3xl mx-auto space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-400">
                    {/* Speed Preset Card */}
                    <Card className="rounded-2xl overflow-hidden">
                        <CardHeader className="pb-4 border-b bg-muted/20">
                            <CardTitle className="text-base flex items-center justify-between">
                                <span className="flex items-center gap-2">
                                    <div className="size-8 rounded-xl bg-teal-100/60 dark:bg-teal-950/40 flex items-center justify-center">
                                        <Zap className="w-4 h-4 text-teal-600" />
                                    </div>
                                    {isRtl ? 'سرعة إرسال الرسائل' : 'Message Dispatch Pacing'}
                                </span>
                                <span className="font-mono text-muted-foreground bg-muted px-2.5 py-0.5 rounded-md text-[10px] font-bold">
                                    {(() => {
                                        const calculatedMin = Math.round(600 / maxWpm);
                                        const calculatedMax = Math.round(600 / minWpm);
                                        return isRtl ? `${calculatedMin} - ${calculatedMax} ثانية` : `${calculatedMin} - ${calculatedMax}s Delay`;
                                    })()}
                                </span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="pt-6 space-y-5">
                            {(() => {
                                const handleSelectPreset = (presetId: string) => {
                                    if (presetId === 'safe') { setMinWpm(40); setMaxWpm(75); }
                                    else if (presetId === 'cautious') { setMinWpm(20); setMaxWpm(40); }
                                    else if (presetId === 'turbo') { setMinWpm(100); setMaxWpm(200); }
                                    else {
                                        const calculatedMin = Math.round(600 / maxWpm);
                                        const calculatedMax = Math.round(600 / minWpm);
                                        setCustomMin(calculatedMin);
                                        setCustomMax(calculatedMax);
                                    }
                                };

                                const presetsList = [
                                    { id: 'safe', title: isRtl ? 'آمن وطبيعي' : 'Safe & Natural', delayText: '8-15s', icon: ShieldCheck, colorClass: 'emerald',
                                      badge: isRtl ? 'آمن جداً' : 'Excellent Safety', rateText: isRtl ? '~300 رسالة/س' : '~300 msgs/hr',
                                      description: isRtl ? 'محاكاة الكتابة البشرية والانتظار العشوائي الطبيعي.' : 'Simulates natural keyboard pacing & human variance.' },
                                    { id: 'cautious', title: isRtl ? 'شديد الحذر' : 'Ultra Cautious', delayText: '15-30s', icon: Clock, colorClass: 'amber',
                                      badge: isRtl ? 'حماية قصوى' : 'Max Shield', rateText: isRtl ? '~150 رسالة/س' : '~150 msgs/hr',
                                      description: isRtl ? 'تأخيرات ممتدة. مثالي للحسابات الجديدة.' : 'Extended delays. Perfect for fresh channels.' },
                                    { id: 'turbo', title: isRtl ? 'سريع جداً' : 'Turbo Speed', delayText: '3-6s', icon: Zap, colorClass: 'rose',
                                      badge: isRtl ? 'مخاطرة عالية' : 'High Risk', rateText: isRtl ? '~800 رسالة/س' : '~800 msgs/hr',
                                      description: isRtl ? 'فواصل زمنية ضيقة. للحسابات القديمة فقط.' : 'Rapid dispatch. Only for mature channels.' },
                                    { id: 'custom', title: isRtl ? 'تأخير يدوي' : 'Custom Delay', delayText: isRtl ? 'مخصص' : 'Manual', icon: Sliders, colorClass: 'teal',
                                      badge: isRtl ? 'تخصيص كامل' : 'Customizable', rateText: isRtl ? 'معدل مرن' : 'Flexible Rate',
                                      description: isRtl ? 'حدد نطاق الثواني المفضل يدوياً.' : 'Directly set custom interval ranges.' },
                                ];

                                const selectedPresetObj = presetsList.find(p => p.id === activePreset) || presetsList[0];
                                const ActiveIcon = selectedPresetObj.icon;

                                return (
                                    <>
                                        {/* Preset selector grid */}
                                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 bg-muted/30 p-1.5 rounded-2xl border border-muted-foreground/5">
                                            {presetsList.map((preset) => {
                                                const isSelected = activePreset === preset.id;
                                                const Icon = preset.icon;
                                                let btnStyles = "";
                                                if (isSelected) {
                                                    if (preset.colorClass === 'emerald') btnStyles = "bg-emerald-500 text-white shadow-md shadow-emerald-500/10";
                                                    else if (preset.colorClass === 'amber') btnStyles = "bg-amber-500 text-white shadow-md shadow-amber-500/10";
                                                    else if (preset.colorClass === 'rose') btnStyles = "bg-rose-500 text-white shadow-md shadow-rose-500/10";
                                                    else btnStyles = "bg-teal-500 text-white shadow-md shadow-teal-500/10";
                                                } else {
                                                    btnStyles = "text-muted-foreground hover:text-foreground hover:bg-muted/50 bg-transparent";
                                                }

                                                return (
                                                    <button
                                                        key={preset.id}
                                                        type="button"
                                                        onClick={() => handleSelectPreset(preset.id)}
                                                        className={`py-2.5 px-1 rounded-xl transition-all duration-300 flex flex-col items-center justify-center gap-1 text-center select-none ${btnStyles}`}
                                                    >
                                                        <Icon className="size-4 shrink-0" />
                                                        <span className="text-[11px] font-bold leading-tight">{preset.title}</span>
                                                        <span className={`text-[8px] font-medium leading-none ${isSelected ? 'text-white/80' : 'text-muted-foreground/70'}`}>
                                                            {preset.delayText}
                                                        </span>
                                                    </button>
                                                );
                                            })}
                                        </div>

                                        {/* Selected preset detail */}
                                        <div className={`p-4 rounded-2xl border transition-all duration-300 text-start flex gap-3.5 relative overflow-hidden ${
                                            selectedPresetObj.colorClass === 'emerald' ? 'border-emerald-500/20 bg-emerald-500/[0.02]' :
                                            selectedPresetObj.colorClass === 'amber' ? 'border-amber-500/20 bg-amber-500/[0.02]' :
                                            selectedPresetObj.colorClass === 'rose' ? 'border-rose-500/20 bg-rose-500/[0.02]' :
                                            'border-teal-500/20 bg-teal-500/[0.02]'
                                        }`}>
                                            <div className={`absolute start-0 top-0 bottom-0 w-1 ${
                                                selectedPresetObj.colorClass === 'emerald' ? 'bg-emerald-500' :
                                                selectedPresetObj.colorClass === 'amber' ? 'bg-amber-500' :
                                                selectedPresetObj.colorClass === 'rose' ? 'bg-rose-500' : 'bg-teal-500'
                                            }`} />
                                            <div className={`p-2 rounded-xl h-fit shrink-0 ${
                                                selectedPresetObj.colorClass === 'emerald' ? 'bg-emerald-100/50 text-emerald-600' :
                                                selectedPresetObj.colorClass === 'amber' ? 'bg-amber-100/50 text-amber-600' :
                                                selectedPresetObj.colorClass === 'rose' ? 'bg-rose-100/50 text-rose-600' :
                                                'bg-teal-100/50 text-teal-600'
                                            }`}>
                                                <ActiveIcon className="size-5 shrink-0" />
                                            </div>
                                            <div className="flex-1 space-y-1.5 min-w-0">
                                                <div className="flex items-center justify-between flex-wrap gap-1.5">
                                                    <h4 className="font-extrabold text-xs text-foreground flex items-center gap-1.5">
                                                        {selectedPresetObj.title}
                                                        <Badge variant="outline" className={`text-[9px] font-bold px-2 py-0 h-5 border-none ${
                                                            selectedPresetObj.colorClass === 'emerald' ? 'bg-emerald-500/10 text-emerald-600' :
                                                            selectedPresetObj.colorClass === 'amber' ? 'bg-amber-500/10 text-amber-600' :
                                                            selectedPresetObj.colorClass === 'rose' ? 'bg-rose-500/10 text-rose-600' :
                                                            'bg-teal-500/10 text-teal-600'
                                                        }`}>
                                                            {selectedPresetObj.badge}
                                                        </Badge>
                                                    </h4>
                                                    <span className="text-[10px] font-black text-muted-foreground flex items-center gap-1">
                                                        <Zap className="size-3.5 text-muted-foreground/60" />
                                                        {selectedPresetObj.rateText}
                                                    </span>
                                                </div>
                                                <p className="text-[11px] text-muted-foreground leading-relaxed text-start">
                                                    {selectedPresetObj.description}
                                                </p>

                                                {/* Custom delay inputs */}
                                                {activePreset === 'custom' && (
                                                    <div className="pt-3.5 mt-3 border-t border-dashed border-muted-foreground/15 space-y-3.5">
                                                        <div className="grid grid-cols-2 gap-4">
                                                            <div className="space-y-1.5">
                                                                <Label className="text-[10px] text-muted-foreground font-bold">{isRtl ? 'الحد الأدنى للتأخير' : 'Min Delay (seconds)'}</Label>
                                                                <Input type="number" min={1} max={120} value={customMin} onChange={e => updateCustomMin(Number(e.target.value))} className="font-mono text-center font-bold text-xs h-9 focus-visible:ring-teal-500" />
                                                            </div>
                                                            <div className="space-y-1.5">
                                                                <Label className="text-[10px] text-muted-foreground font-bold">{isRtl ? 'الحد الأقصى للتأخير' : 'Max Delay (seconds)'}</Label>
                                                                <Input type="number" min={customMin + 1} max={180} value={customMax} onChange={e => updateCustomMax(Number(e.target.value))} className="font-mono text-center font-bold text-xs h-9 focus-visible:ring-teal-500" />
                                                            </div>
                                                        </div>
                                                        <div className="flex items-start gap-2 text-[10px] text-muted-foreground bg-background/40 p-2.5 rounded-xl border border-muted">
                                                            <Info className="size-3.5 text-teal-500 shrink-0 mt-0.5" />
                                                            <span className="leading-relaxed text-start">
                                                                {isRtl
                                                                    ? `سيتم الانتظار بين ${customMin} و ${customMax} ثانية بين كل رسالة.`
                                                                    : `Pacing engine will wait ${customMin}s to ${customMax}s between each message.`
                                                                }
                                                            </span>
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </>
                                );
                            })()}
                        </CardContent>
                    </Card>

                    {/* Advanced Safety Card */}
                    <Card className="rounded-2xl overflow-hidden">
                        <CardHeader className="pb-4 border-b bg-muted/20">
                            <CardTitle className="text-base flex items-center justify-between">
                                <span className="flex items-center gap-2">
                                    <div className="size-8 rounded-xl bg-teal-100/60 dark:bg-teal-950/40 flex items-center justify-center">
                                        <ShieldCheck className="w-4 h-4 text-teal-600" />
                                    </div>
                                    {isRtl ? 'إعدادات الحماية المتقدمة' : 'Advanced Protection Settings'}
                                </span>
                                <Badge variant="secondary" className="bg-teal-50 text-teal-700 dark:bg-teal-950/40 dark:text-teal-300 border-teal-200 font-bold">
                                    {isRtl ? 'حماية نشطة' : 'Active Shields'}
                                </Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="pt-6 space-y-4">
                            {/* Typo Simulation Slider */}
                            <div className="space-y-2 p-3.5 rounded-2xl border border-muted bg-muted/5 text-start">
                                <div className="flex items-center justify-between text-xs">
                                    <div className="flex items-center gap-1.5">
                                        <HeartPulse className="size-4 text-teal-600 shrink-0" />
                                        <Label className="font-bold text-foreground">{isRtl ? 'نسبة محاكاة الكتابة البشرية' : 'Human Typo Simulation'}</Label>
                                    </div>
                                    <Badge variant="outline" className="font-black px-2.5 py-0.5 rounded-lg border-teal-500/20 text-teal-600 bg-teal-50/50 dark:bg-teal-950/20 text-[10px]">
                                        {typoChance}%
                                    </Badge>
                                </div>
                                <p className="text-[10px] text-muted-foreground leading-normal">
                                    {isRtl ? 'يحاكي حدوث أخطاء إملائية ومسحها ليبدو النشاط بشرياً.' : 'Simulates occasional keyboard errors and backspacing corrections.'}
                                </p>
                                <div className="flex items-center gap-3">
                                    <span className="text-[9px] font-bold text-muted-foreground select-none">0%</span>
                                    <Input type="range" min={0} max={15} value={typoChance} onChange={e => setTypoChance(Number(e.target.value))} className="p-0 h-auto border-none cursor-pointer accent-teal-600 flex-1" />
                                    <span className="text-[9px] font-bold text-muted-foreground select-none">15%</span>
                                </div>
                            </div>

                            {/* Toggles grid */}
                            <div className="grid grid-cols-1 gap-2.5">
                                {/* AI Synonyms Switch */}
                                <div className="flex items-start justify-between p-3 rounded-xl border border-muted bg-background hover:border-teal-100 transition-all select-none">
                                    <div className="space-y-0.5 text-start max-w-[82%]">
                                        <div className="flex items-center gap-1.5">
                                            <Sparkles className="size-3.5 text-teal-500 shrink-0" />
                                            <Label htmlFor="useSynonyms" className="font-bold text-xs cursor-pointer">{isRtl ? 'مرادفات الذكاء الاصطناعي' : 'AI Synonym Spin'}</Label>
                                        </div>
                                        <p className="text-[10px] text-muted-foreground leading-normal">
                                            {isRtl ? 'تغيير صياغة الكلمات لتفادي كشف الرسائل المتكررة.' : 'Autospin text phrases to disrupt spam fingerprint filters.'}
                                        </p>
                                    </div>
                                    <Switch id="useSynonyms" checked={useSynonyms} onCheckedChange={setUseSynonyms} />
                                </div>

                                {/* Bell Curve Switch */}
                                <div className="flex items-start justify-between p-3 rounded-xl border border-muted bg-background hover:border-teal-100 transition-all select-none">
                                    <div className="space-y-0.5 text-start max-w-[82%]">
                                        <div className="flex items-center gap-1.5">
                                            <Clock className="size-3.5 text-teal-500 shrink-0" />
                                            <Label htmlFor="bellCurve" className="font-bold text-xs cursor-pointer">{isRtl ? 'تأخيرات منحنى الجرس' : 'Human Bell Curve'}</Label>
                                        </div>
                                        <p className="text-[10px] text-muted-foreground leading-normal">
                                            {isRtl ? 'توزيع تأخيرات الإرسال بشكل منحنى طبيعي.' : 'Pacing follows natural probability curves for unpredictable intervals.'}
                                        </p>
                                    </div>
                                    <Switch id="bellCurve" checked={bellCurve} onCheckedChange={setBellCurve} />
                                </div>

                                {/* Track Delivery Switch */}
                                <div className="flex items-start justify-between p-3 rounded-xl border border-muted bg-background hover:border-teal-100 transition-all select-none">
                                    <div className="space-y-0.5 text-start max-w-[82%]">
                                        <div className="flex items-center gap-1.5">
                                            <Check className="size-3.5 text-teal-500 shrink-0" />
                                            <Label htmlFor="trackDelivery" className="font-bold text-xs cursor-pointer">{isRtl ? 'تتبع حالة التسليم' : 'Track Delivery Status'}</Label>
                                        </div>
                                        <p className="text-[10px] text-muted-foreground leading-normal">
                                            {isRtl ? 'فحص واسترجاع صحة الاستلام والقراءة لكل جهة اتصال.' : 'Fetch real-time read and delivery status to log campaigns.'}
                                        </p>
                                    </div>
                                    <Switch id="trackDelivery" checked={trackDelivery} onCheckedChange={setTrackDelivery} />
                                </div>

                                {/* Emergency Stop Switch */}
                                <div className={`flex items-start justify-between p-3 rounded-xl border transition-all select-none ${
                                    stopOnBlock
                                        ? 'border-rose-200 dark:border-rose-950 bg-rose-500/[0.02] dark:bg-rose-950/[0.03]'
                                        : 'border-muted bg-background hover:border-rose-100'
                                }`}>
                                    <div className="space-y-0.5 text-start max-w-[82%]">
                                        <div className="flex items-center gap-1.5">
                                            <AlertTriangle className={`size-3.5 shrink-0 ${stopOnBlock ? 'text-rose-500 animate-pulse' : 'text-muted-foreground'}`} />
                                            <Label htmlFor="stopOnBlock" className={`font-bold text-xs cursor-pointer ${stopOnBlock ? 'text-rose-600 dark:text-rose-400' : 'text-foreground'}`}>{isRtl ? 'درع الحظر وإيقاف الطوارئ' : 'Auto Kill-Switch'}</Label>
                                        </div>
                                        <p className="text-[10px] text-muted-foreground leading-normal">
                                            {isRtl ? 'إيقاف الإرسال فوراً إذا ارتفعت نسبة الفشل.' : 'Instantly halt sending if failure rates exceed limit.'}
                                        </p>
                                    </div>
                                    <Switch id="stopOnBlock" checked={stopOnBlock} onCheckedChange={setStopOnBlock} className="data-[state=checked]:bg-rose-500" />
                                </div>
                            </div>

                            {/* Risk Threshold */}
                            {stopOnBlock && (
                                <div className="p-4 rounded-2xl border border-rose-200 dark:border-rose-900 bg-rose-50/20 dark:bg-rose-950/10 space-y-3 animate-in slide-in-from-top-2 duration-300 text-start">
                                    <div className="flex items-center justify-between text-xs">
                                        <div className="flex items-center gap-2">
                                            <AlertCircle className="size-4 text-rose-500 shrink-0" />
                                            <Label className="text-rose-700 dark:text-rose-300 font-bold">{isRtl ? 'عتبة الخطر القصوى' : 'Maximum Risk Threshold'}</Label>
                                        </div>
                                        <Badge variant="destructive" className="font-black px-2.5 py-0.5 rounded-lg bg-rose-500 text-white text-[10px]">
                                            {maxBlockRate}%
                                        </Badge>
                                    </div>
                                    <p className="text-[10px] text-muted-foreground leading-normal">
                                        {isRtl
                                            ? `سيتم إيقاف الحملة إذا تجاوزت نسبة الفشل ${maxBlockRate}%.`
                                            : `Halt the campaign if failed messages exceed ${maxBlockRate}% of total batch.`
                                        }
                                    </p>
                                    <div className="flex items-center gap-3">
                                        <span className="text-[9px] font-bold text-rose-500/70 select-none">2%</span>
                                        <Input type="range" min={2} max={20} value={maxBlockRate} onChange={e => setMaxBlockRate(Number(e.target.value))} className="p-0 h-auto border-none cursor-pointer accent-rose-600 flex-1" />
                                        <span className="text-[9px] font-bold text-rose-500/70 select-none">20%</span>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                    {/* ── A/B Testing Card ──────────────────────────────────────── */}
                    <Card className="rounded-2xl overflow-hidden">
                        <CardHeader className="pb-4 border-b bg-muted/20">
                            <CardTitle className="text-base flex items-center justify-between">
                                <span className="flex items-center gap-2">
                                    <div className="size-8 rounded-xl bg-indigo-100/60 dark:bg-indigo-950/40 flex items-center justify-center">
                                        <Sparkles className="w-4 h-4 text-indigo-600" />
                                    </div>
                                    {isRtl ? 'اختبار A/B' : 'A/B Testing'}
                                </span>
                                <Switch
                                    checked={abEnabled}
                                    onCheckedChange={setAbEnabled}
                                    className="data-checked:bg-indigo-600"
                                />
                            </CardTitle>
                        </CardHeader>
                        {abEnabled && (
                            <CardContent className="pt-5 space-y-4">
                                <div className="flex items-center gap-2 px-3 py-2 bg-indigo-50/80 dark:bg-indigo-950/20 border border-indigo-200/50 dark:border-indigo-800/30 rounded-lg">
                                    <Info className="w-3.5 h-3.5 text-indigo-600 shrink-0" />
                                    <p className="text-[10px] text-indigo-700 dark:text-indigo-400 font-medium">
                                        {isRtl 
                                            ? 'سيتم تقسيم جهات الاتصال عشوائياً بين نسختين من الرسالة لمعرفة أيهما يحقق أداءً أفضل.' 
                                            : 'Contacts will be randomly split between two message variants to determine which performs better.'}
                                    </p>
                                </div>

                                {/* Split Ratio Slider */}
                                <div className="space-y-2">
                                    <Label className="text-xs font-bold text-start block">
                                        {isRtl ? 'نسبة التقسيم' : 'Split Ratio'}
                                    </Label>
                                    <div className="flex items-center gap-3">
                                        <div className="flex items-center gap-1.5">
                                            <div className="w-3 h-3 rounded-full bg-teal-500" />
                                            <span className="text-xs font-bold text-teal-600">A: {abSplitRatio}%</span>
                                        </div>
                                        <Input 
                                            type="range" 
                                            min={10} max={90} 
                                            value={abSplitRatio} 
                                            onChange={e => setAbSplitRatio(Number(e.target.value))}
                                            className="p-0 h-auto border-none cursor-pointer accent-indigo-600 flex-1" 
                                        />
                                        <div className="flex items-center gap-1.5">
                                            <div className="w-3 h-3 rounded-full bg-orange-500" />
                                            <span className="text-xs font-bold text-orange-600">B: {100 - abSplitRatio}%</span>
                                        </div>
                                    </div>
                                </div>

                                {/* Variant B Message */}
                                <div className="space-y-2">
                                    <Label className="text-xs font-bold text-start block">
                                        <span className="inline-flex items-center gap-1.5">
                                            <div className="w-4 h-4 rounded bg-orange-500 flex items-center justify-center text-white text-[9px] font-black">B</div>
                                            {isRtl ? 'رسالة النسخة B' : 'Variant B Message'}
                                        </span>
                                    </Label>
                                    <Textarea
                                        value={abVariantBMessage}
                                        onChange={e => setAbVariantBMessage(e.target.value)}
                                        placeholder={isRtl ? 'اكتب النسخة البديلة من الرسالة هنا...' : 'Write the alternative message variant here...'}
                                        rows={3}
                                        className="resize-none text-sm text-start"
                                    />
                                    <p className="text-[10px] text-muted-foreground">
                                        {isRtl 
                                            ? 'النسخة A ستكون الرسالة الأصلية من القالب المختار. هذه هي النسخة البديلة.' 
                                            : 'Variant A will be the original template message. This is the alternative variant.'}
                                    </p>
                                </div>
                            </CardContent>
                        )}
                    </Card>
                </div>
            )}

            {/* ══════════════════════════════════════════════════════════════════ */}
            {/* STEP 3: Review & Launch                                          */}
            {/* ══════════════════════════════════════════════════════════════════ */}
            {currentStep === 3 && (
                <div className="max-w-3xl mx-auto space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-400">
                    {/* Campaign Identity */}
                    <Card className="rounded-2xl overflow-hidden border-teal-500/20">
                        <div className="absolute start-0 top-0 bottom-0 w-1 bg-gradient-to-b from-teal-500 to-emerald-600" />
                        <CardHeader className="pb-4 border-b bg-muted/20">
                            <CardTitle className="text-base flex items-center justify-between">
                                <span className="flex items-center gap-2">
                                    <div className="size-8 rounded-xl bg-teal-100/60 dark:bg-teal-950/40 flex items-center justify-center">
                                        <Rocket className="w-4 h-4 text-teal-600" />
                                    </div>
                                    {isRtl ? 'تجهيز وإطلاق الحملة' : 'Launch & Dispatch Control'}
                                </span>
                                <Badge variant="outline" className="border-teal-500/30 text-teal-600 bg-teal-50/50 dark:bg-teal-950/20 text-[10px] font-bold">
                                    {isRtl ? 'جاهزية الإطلاق' : 'Ready'}
                                </Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="pt-5 space-y-5">
                            {/* Campaign Name & Account */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div className="space-y-1.5 text-start">
                                    <Label className="text-[11px] font-bold text-muted-foreground flex items-center gap-1">
                                        <Tag className="size-3 text-teal-500" />
                                        {isRtl ? 'اسم الحملة' : 'Campaign Name'}
                                    </Label>
                                    <Input
                                        type="text"
                                        value={campaignName}
                                        onChange={e => setCampaignName(e.target.value)}
                                        placeholder={isRtl ? 'مثال: حملة المبيعات - مايو 2026' : 'e.g. Sales Campaign - May 2026'}
                                        className="h-10 focus-visible:ring-teal-500 border-muted font-medium rounded-xl"
                                    />
                                </div>

                                <div className="space-y-1.5 text-start">
                                    <Label className="text-[11px] font-bold text-muted-foreground flex items-center gap-1">
                                        <UserCheck className="size-3 text-teal-500" />
                                        {isRtl ? 'حساب الإرسال' : 'Sending Account'}
                                    </Label>
                                    <select
                                        value={selectedAccount}
                                        onChange={e => setSelectedAccount(e.target.value)}
                                        className="flex h-10 w-full rounded-xl border border-muted bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 font-bold text-foreground"
                                    >
                                        <option value="" className="text-muted-foreground font-medium">{isRtl ? 'اختر الحساب...' : 'Select account...'}</option>
                                        {sessions.filter((s: any) => s.state === 'connected').map((s: any) => (
                                            <option key={s.accountId} value={s.accountId} className="py-2 font-bold">
                                                {s.accountId} {s.state === 'connected' ? (isRtl ? '● (متصل)' : '● (Connected)') : `● (${s.state})`}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            {/* Pre-Dispatch Summary Checklist */}
                            <div className="p-4 rounded-2xl bg-muted/20 border border-muted/80 space-y-3.5 text-start">
                                <h5 className="text-[10px] font-black uppercase tracking-wider text-muted-foreground/80 flex items-center gap-1">
                                    <CheckCircle2 className="size-3 text-teal-600" />
                                    {isRtl ? 'مراجعة معايير ما قبل الإطلاق' : 'Pre-Dispatch Summary'}
                                </h5>
                                <ul className="space-y-2.5 text-[11px] font-medium text-foreground">
                                    <li className="flex items-center justify-between">
                                        <span className="text-muted-foreground">{isRtl ? 'المستهدفين:' : 'Recipients:'}</span>
                                        <span className="font-bold flex items-center gap-1">
                                            {getParsedRecipients.length > 0 ? (
                                                <><span className="text-emerald-600 font-black">✓</span> {getParsedRecipients.length} {isRtl ? 'جهة اتصال' : 'contacts'}</>
                                            ) : (
                                                <span className="text-rose-500 font-bold">⚠ {isRtl ? 'لا يوجد أرقام' : 'No contacts'}</span>
                                            )}
                                        </span>
                                    </li>

                                    <li className="flex items-center justify-between">
                                        <span className="text-muted-foreground">{isRtl ? 'قالب الرسالة:' : 'Message Template:'}</span>
                                        <span className="font-bold flex items-center gap-1">
                                            {selectedTemplateId ? (
                                                <><span className="text-emerald-600 font-black">✓</span> {templates.find((t: any) => t.id === selectedTemplateId)?.name || (isRtl ? 'محدد' : 'Selected')}</>
                                            ) : (
                                                <span className="text-rose-500 font-bold">⚠ {isRtl ? 'يرجى تحديد قالب' : 'No template'}</span>
                                            )}
                                        </span>
                                    </li>

                                    <li className="flex items-center justify-between">
                                        <span className="text-muted-foreground">{isRtl ? 'سرعة الإرسال:' : 'Dispatch Pacing:'}</span>
                                        <span className="font-bold">{getPresetLabel()}</span>
                                    </li>

                                    <li className="flex items-center justify-between">
                                        <span className="text-muted-foreground">{isRtl ? 'حالة الحماية:' : 'Safety Status:'}</span>
                                        <span className={`font-bold flex items-center gap-1 ${stopOnBlock ? 'text-emerald-600' : 'text-amber-500'}`}>
                                            {stopOnBlock ? (
                                                <><span className="font-black">✓</span> {isRtl ? 'نشطة' : 'Active'}</>
                                            ) : (
                                                <><span className="font-black">⚠</span> {isRtl ? 'غير نشطة' : 'Inactive'}</>
                                            )}
                                        </span>
                                    </li>

                                    {dripSteps.length > 0 && (
                                        <li className="flex items-center justify-between pt-2 border-t border-dashed border-muted-foreground/10">
                                            <span className="text-muted-foreground font-bold flex items-center gap-1">
                                                <Timer className="size-3.5 text-teal-600" />
                                                {isRtl ? 'خطوات المتابعة (Drip Steps):' : 'Scheduled Follow-ups (Drips):'}
                                            </span>
                                            <Badge className="bg-teal-500 text-white font-bold text-[10px] px-2 py-0.5 rounded-lg border-none">
                                                {dripSteps.length} {isRtl ? 'خطوات متابعة' : 'steps'}
                                            </Badge>
                                        </li>
                                    )}

                                    {getParsedRecipients.length > 0 && (
                                        <li className="flex items-center justify-between pt-2.5 mt-1 border-t border-dashed border-muted-foreground/10 text-xs">
                                            <span className="text-muted-foreground font-bold flex items-center gap-1">
                                                <Timer className="size-3.5 text-teal-600" />
                                                {isRtl ? 'الوقت المقدر:' : 'Estimated Duration:'}
                                            </span>
                                            <span className="font-black text-teal-600 dark:text-teal-400">
                                                {(() => {
                                                    const avgDelay = (customMin + customMax) / 2;
                                                    const totalSec = getParsedRecipients.length * avgDelay;
                                                    const minTotal = Math.ceil(totalSec / 60);
                                                    return isRtl ? `~${minTotal} دقيقة` : `~${minTotal} minutes`;
                                                })()}
                                            </span>
                                        </li>
                                    )}
                                </ul>
                            </div>

                            {/* Test Send Panel */}
                            {showTestInput && (
                                <div className="p-4 rounded-2xl bg-muted/40 border border-muted/80 space-y-3.5 text-start animate-in slide-in-from-bottom-2 duration-300 relative">
                                    <Label className="text-[11px] font-bold text-muted-foreground flex items-center gap-1.5">
                                        <Send className="size-3 text-teal-500" />
                                        {isRtl ? 'رقم الاختبار (مع رمز الدولة)' : 'Test Phone Number (with country code)'}
                                    </Label>
                                    <div className="flex gap-2">
                                        <Input
                                            type="text"
                                            value={testNumber}
                                            onChange={e => setTestNumber(e.target.value.replace(/[^0-9]/g, ''))}
                                            placeholder={__('general.e_g_966500000000')}
                                            className="h-10 focus-visible:ring-teal-500 border-muted font-mono text-xs flex-1 rounded-xl"
                                        />
                                        <Button
                                            onClick={executeSendTest}
                                            disabled={isSendingTest || !testNumber || !selectedTemplateId || !selectedAccount}
                                            className="h-10 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl flex items-center gap-1.5 text-xs"
                                        >
                                            {isSendingTest ? (
                                                <span className="size-3.5 border-2 border-white border-t-transparent rounded-full animate-spin shrink-0" />
                                            ) : (
                                                <Send className="size-3" />
                                            )}
                                            {isRtl ? 'إرسال' : 'Send'}
                                        </Button>
                                        <Button variant="outline" onClick={() => { setShowTestInput(false); setTestSendStatus(null); }} className="h-10 px-3 border-muted font-bold rounded-xl text-xs">
                                            {isRtl ? 'إلغاء' : 'Cancel'}
                                        </Button>
                                    </div>
                                    {testSendStatus && (
                                        <div className={`p-2.5 rounded-xl border flex items-center gap-2 text-[10px] font-bold ${
                                            testSendStatus.type === 'success'
                                                ? 'border-emerald-500/25 bg-emerald-500/5 text-emerald-600'
                                                : 'border-rose-500/25 bg-rose-500/5 text-rose-600'
                                        }`}>
                                            {testSendStatus.type === 'success' ? <Check className="size-4 text-emerald-500 shrink-0" /> : <AlertCircle className="size-4 text-rose-500 shrink-0 animate-bounce" />}
                                            <span>{testSendStatus.message}</span>
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Schedule Section */}
                            <div className={`p-4 rounded-2xl border transition-all duration-300 text-start ${
                                scheduleMode
                                    ? 'border-amber-300 bg-amber-50/30 dark:bg-amber-950/10 dark:border-amber-800/30 shadow-sm'
                                    : 'border-muted bg-muted/5'
                            }`}>
                                <div className="flex items-center justify-between mb-3">
                                    <div className="flex items-center gap-2">
                                        <CalendarClock className="w-4 h-4 text-amber-600 animate-pulse" />
                                        <span className="text-sm font-black">{isRtl ? 'جدولة وإعداد تكرار الحملة' : 'Schedule & Recurrence Options'}</span>
                                    </div>
                                    <Switch
                                        checked={scheduleMode}
                                        onCheckedChange={setScheduleMode}
                                    />
                                </div>
                                {scheduleMode && (
                                    <div className="space-y-4 mt-3.5 animate-in slide-in-from-top-1 duration-300">
                                        {/* Date and Time pickers */}
                                        <div className="flex gap-3">
                                            <div className="flex-1 space-y-1.5">
                                                <Label className="text-[11px] font-bold text-muted-foreground">{isRtl ? 'تاريخ البدء' : 'Start Date'}</Label>
                                                <Input
                                                    type="date"
                                                    value={scheduleDate}
                                                    onChange={e => setScheduleDate(e.target.value)}
                                                    min={new Date().toISOString().split('T')[0]}
                                                    className="rounded-xl text-sm h-10 border-muted focus-visible:ring-amber-500 font-medium"
                                                />
                                            </div>
                                            <div className="flex-1 space-y-1.5">
                                                <Label className="text-[11px] font-bold text-muted-foreground">{isRtl ? 'وقت البدء' : 'Start Time'}</Label>
                                                <Input
                                                    type="time"
                                                    value={scheduleTime}
                                                    onChange={e => setScheduleTime(e.target.value)}
                                                    className="rounded-xl text-sm h-10 border-muted focus-visible:ring-amber-500 font-medium"
                                                />
                                            </div>
                                        </div>

                                        {/* Recurrence Switch */}
                                        <div className="pt-3 border-t border-dashed border-amber-300/40 dark:border-amber-800/40 flex items-start justify-between">
                                            <div className="space-y-0.5 text-start max-w-[80%]">
                                                <Label htmlFor="isRecurring" className="font-extrabold text-xs cursor-pointer flex items-center gap-1.5 text-foreground">
                                                    <Timer className="w-3.5 h-3.5 text-amber-500" />
                                                    {isRtl ? 'تكرار دوري تلقائي' : 'Recurring Campaign'}
                                                </Label>
                                                <p className="text-[10px] text-muted-foreground leading-normal">
                                                    {isRtl 
                                                        ? 'إعادة تشغيل الحملة دورياً بمجرد اكتمالها بعد جدولة وقت جديد للمستقبل.' 
                                                        : 'Automatically reset, re-queue contacts, and schedule the next execution window on completion.'
                                                    }
                                                </p>
                                            </div>
                                            <Switch id="isRecurring" checked={isRecurring} onCheckedChange={setIsRecurring} className="data-[state=checked]:bg-amber-500" />
                                        </div>

                                        {/* Recurrence Configuration Panels */}
                                        {isRecurring && (
                                            <div className="p-3.5 rounded-xl bg-background/50 border border-amber-200 dark:border-amber-900/30 space-y-3.5 animate-in slide-in-from-top-2 duration-300">
                                                <div className="space-y-1.5">
                                                    <Label className="text-[10px] font-bold text-muted-foreground">{isRtl ? 'نمط التكرار' : 'Recurrence Pattern'}</Label>
                                                    <select
                                                        value={recurrencePattern}
                                                        onChange={e => setRecurrencePattern(e.target.value)}
                                                        className="flex h-9 w-full rounded-xl border border-muted bg-background px-3 py-1 text-xs ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 font-bold"
                                                    >
                                                        <option value="daily">{isRtl ? 'يومياً' : 'Daily'}</option>
                                                        <option value="weekly">{isRtl ? 'أسبوعياً' : 'Weekly'}</option>
                                                        <option value="monthly">{isRtl ? 'شهرياً' : 'Monthly'}</option>
                                                        <option value="weekdays">{isRtl ? 'أيام العمل فقط (Mon-Fri)' : 'Weekdays Only (Mon-Fri)'}</option>
                                                        <option value="custom">{isRtl ? 'تخصيص أيام معينة' : 'Custom Days'}</option>
                                                    </select>
                                                </div>

                                                {/* Weekday Checklist for Custom */}
                                                {recurrencePattern === 'custom' && (
                                                    <div className="space-y-2 animate-in fade-in duration-350">
                                                        <Label className="text-[10px] font-bold text-muted-foreground">{isRtl ? 'اختر أيام الإرسال من الأسبوع:' : 'Select days of the week:'}</Label>
                                                        <div className="flex flex-wrap gap-1.5 justify-center sm:justify-start">
                                                            {[
                                                                { label: isRtl ? 'أحد' : 'Sun', value: 0 },
                                                                { label: isRtl ? 'اثنين' : 'Mon', value: 1 },
                                                                { label: isRtl ? 'ثلاثاء' : 'Tue', value: 2 },
                                                                { label: isRtl ? 'أربعاء' : 'Wed', value: 3 },
                                                                { label: isRtl ? 'خميس' : 'Thu', value: 4 },
                                                                { label: isRtl ? 'جمعة' : 'Fri', value: 5 },
                                                                { label: isRtl ? 'سبت' : 'Sat', value: 6 }
                                                            ].map(day => {
                                                                const active = selectedDays.includes(day.value);
                                                                return (
                                                                    <button
                                                                        type="button"
                                                                        key={day.value}
                                                                        onClick={() => setSelectedDays(prev => prev.includes(day.value) ? prev.filter(d => d !== day.value) : [...prev, day.value])}
                                                                        className={`px-3 py-1.5 text-[10px] font-black rounded-lg border transition-all select-none ${
                                                                            active
                                                                                ? 'bg-amber-500 text-white border-amber-600 shadow shadow-amber-500/20'
                                                                                : 'bg-background hover:bg-muted text-muted-foreground hover:text-foreground'
                                                                        }`}
                                                                    >
                                                                        {day.label}
                                                                    </button>
                                                                );
                                                            })}
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>

                            {/* Action Buttons */}
                            <div className="flex flex-col sm:flex-row gap-2.5">
                                {scheduleMode ? (
                                    <Button
                                        onClick={async () => {
                                            if (!scheduleDate || !scheduleTime) {
                                                alert(isRtl ? 'اختر التاريخ والوقت' : 'Select date and time');
                                                return;
                                            }
                                            setIsScheduling(true);
                                            try {
                                                const scheduledAt = `${scheduleDate} ${scheduleTime}:00`;
                                                await handleLaunchCampaign(dripSteps, {
                                                    scheduledAt,
                                                    isRecurring,
                                                    recurrencePattern,
                                                    recurrenceDays: selectedDays.join(','),
                                                    abEnabled,
                                                    abSplitRatio,
                                                    abVariantBMessage
                                                });
                                                alert(isRtl 
                                                    ? `تم جدولة الحملة بنجاح وتكرارها! ستنطلق في ${scheduleDate} ${scheduleTime}`
                                                    : `Campaign scheduled and recurring successfully! Starts at ${scheduleDate} ${scheduleTime}`
                                                );
                                                setScheduleMode(false);
                                                setScheduleDate('');
                                                setScheduleTime('');
                                                setIsRecurring(false);
                                            } catch (err: any) {
                                                alert(`Schedule failed: ${err.message}`);
                                            }
                                            setIsScheduling(false);
                                        }}
                                        disabled={isScheduling || isCampaignRunning || getParsedRecipients.length === 0 || !selectedTemplateId || !selectedAccount || !scheduleDate || !scheduleTime}
                                        size="lg"
                                        className="flex-1 bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 active:scale-[0.98] transition-all duration-300 text-white font-bold h-12 shadow-md hover:shadow-amber-500/20 flex items-center justify-center gap-2 rounded-2xl group border-none"
                                    >
                                        {isScheduling ? (
                                            <>
                                                <span className="size-4 border-2 border-white border-t-transparent rounded-full animate-spin shrink-0" />
                                                {isRtl ? 'جاري الجدولة...' : 'Scheduling...'}
                                            </>
                                        ) : (
                                            <>
                                                <CalendarClock className="w-3.5 h-3.5 group-hover:scale-110 transition-transform shrink-0" />
                                                {isRtl ? `جدولة: ${scheduleDate} ${scheduleTime}` : `Schedule: ${scheduleDate} ${scheduleTime}`}
                                            </>
                                        )}
                                    </Button>
                                ) : (
                                 <Button
                                     onClick={() => handleLaunchCampaign(dripSteps, { abEnabled, abSplitRatio, abVariantBMessage })}
                                    disabled={isCampaignRunning || getParsedRecipients.length === 0 || !selectedTemplateId || !selectedAccount}
                                    size="lg"
                                    className="flex-1 bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 active:scale-[0.98] transition-all duration-300 text-white font-bold h-12 shadow-md hover:shadow-teal-500/20 flex items-center justify-center gap-2 rounded-2xl group border-none"
                                >
                                    {isCampaignRunning ? (
                                        <>
                                            <span className="size-4 border-2 border-white border-t-transparent rounded-full animate-spin shrink-0" />
                                            {isRtl ? 'جاري بدء الحملة...' : 'Launching Campaign...'}
                                        </>
                                    ) : (
                                        <>
                                            <Play className="w-3.5 h-3.5 fill-current group-hover:scale-110 transition-transform shrink-0" />
                                            {isRtl ? 'إطلاق الحملة الآن' : 'Launch Campaign Now'}
                                        </>
                                    )}
                                </Button>
                                )}

                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setShowTestInput(p => !p)}
                                    disabled={isCampaignRunning || !selectedTemplateId || !selectedAccount}
                                    className={`sm:w-1/3 h-12 border-muted hover:border-teal-500/50 transition-all duration-300 rounded-2xl font-bold flex items-center justify-center gap-2 ${
                                        showTestInput ? 'border-teal-500 bg-teal-50/20 text-teal-600' : 'text-foreground'
                                    }`}
                                >
                                    <Send className="w-3.5 h-3.5 shrink-0" />
                                    {isRtl ? 'إرسال تجريبي' : 'Send Test'}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            )}

            {/* ── Navigation Footer ────────────────────────────────────────────── */}
            <div className="flex items-center justify-between pt-2">
                <Button
                    variant="outline"
                    onClick={() => setCurrentStep(s => Math.max(0, s - 1))}
                    disabled={currentStep === 0}
                    className="rounded-xl gap-2 font-bold h-11 px-5"
                >
                    {isRtl ? <ChevronRight className="w-4 h-4" /> : <ChevronLeft className="w-4 h-4" />}
                    {isRtl ? 'الخطوة السابقة' : 'Previous'}
                </Button>

                {/* Step dots indicator */}
                <div className="flex items-center gap-1.5">
                    {steps.map((_, idx) => (
                        <div
                            key={idx}
                            className={`rounded-full transition-all duration-300 ${
                                idx === currentStep
                                    ? 'w-6 h-2 bg-teal-500'
                                    : idx < currentStep
                                        ? 'w-2 h-2 bg-emerald-400'
                                        : 'w-2 h-2 bg-muted'
                            }`}
                        />
                    ))}
                </div>

                {currentStep < steps.length - 1 ? (
                    <Button
                        onClick={() => setCurrentStep(s => Math.min(steps.length - 1, s + 1))}
                        disabled={!canGoNext()}
                        className="rounded-xl gap-2 font-bold h-11 px-5 bg-teal-600 hover:bg-teal-700 text-white"
                    >
                        {isRtl ? 'الخطوة التالية' : 'Next Step'}
                        {isRtl ? <ChevronLeft className="w-4 h-4" /> : <ChevronRight className="w-4 h-4" />}
                    </Button>
                ) : (
                    <div className="w-[140px]" /> // Spacer to keep layout balanced (launch button is already in the card above)
                )}
            </div>
        </div>
    );
}

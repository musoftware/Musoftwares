import React, { useState, useRef } from 'react';
import { 
    Users, MessageSquare, Sparkles, ShieldCheck, Send,
    Upload, FileText, FileSpreadsheet, Trash2, Edit3, FileArchive,
    Clock, Zap, Sliders, Check, HelpCircle, AlertTriangle, AlertCircle,
    Info, HeartPulse, ChevronDown, ChevronUp, Settings, Play, CheckCircle2,
    UserCheck, Timer, Tag
} from 'lucide-react';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';

export default function CampaignWorkspace({
    t, locale, contactsText, setContactsText, getParsedRecipients,
    templates, selectedTemplateId, setSelectedTemplateId,
    minWpm, setMinWpm, maxWpm, setMaxWpm, typoChance, setTypoChance,
    useSynonyms, setUseSynonyms, bellCurve, setBellCurve,
    trackDelivery, setTrackDelivery, stopOnBlock, setStopOnBlock, maxBlockRate, setMaxBlockRate,
    campaignName, setCampaignName, selectedAccount, setSelectedAccount, sessions,
    handleLaunchCampaign, handleSendTestMessage, isCampaignRunning, onEditTemplate
}: any) {
    const [inputMethod, setInputMethod]   = useState<'paste' | 'file'>('paste');
    const [uploadedFile, setUploadedFile] = useState<{ name: string; size: number; type: string } | null>(null);
    const [isDragging, setIsDragging]     = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);
    const dragCounter = useRef(0);
    const [showAdvancedSafety, setShowAdvancedSafety] = useState(false);

    const [showTestInput, setShowTestInput] = useState(false);
    const [testNumber, setTestNumber] = useState('');
    const [isSendingTest, setIsSendingTest] = useState(false);
    const [testSendStatus, setTestSendStatus] = useState<{ type: 'success' | 'error'; message: string } | null>(null);

    const executeSendTest = async () => {
        if (!testNumber.trim()) {
            alert(locale === 'ar' ? 'يرجى إدخال رقم هاتف تجريبي صحيح.' : 'Please enter a valid test phone number.');
            return;
        }
        setIsSendingTest(true);
        setTestSendStatus(null);
        try {
            await handleSendTestMessage(testNumber.trim());
            setTestSendStatus({
                type: 'success',
                message: locale === 'ar' ? 'تم إرسال الرسالة التجريبية بنجاح!' : 'Test message sent successfully!'
            });
            setTimeout(() => setTestSendStatus(null), 4000);
        } catch (err: any) {
            setTestSendStatus({
                type: 'error',
                message: locale === 'ar' ? `فشل الإرسال: ${err.message}` : `Send failed: ${err.message}`
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
        
        // Convert to WPM
        const calculatedMinWpm = Math.round(600 / maxVal);
        const calculatedMaxWpm = Math.round(600 / minVal);
        setMinWpm(calculatedMinWpm);
        setMaxWpm(calculatedMaxWpm);
    };

    const updateCustomMax = (val: number) => {
        const maxVal = Math.max(customMin + 1, val);
        setCustomMax(maxVal);
        
        // Convert to WPM
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

            let parsedRows: string[] = [];
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
                        {locale === 'ar' ? 'الاسم' : '{name}'}
                    </span>
                );
            }
            if (lower === '{phone}') {
                return (
                    <span key={index} className="inline-block px-1.5 py-0.5 rounded bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-200 font-bold border border-orange-200 dark:border-orange-800 text-[10px] mx-0.5 shadow-sm">
                        {locale === 'ar' ? 'الهاتف' : '{phone}'}
                    </span>
                );
            }
            if (lower === '{company}') {
                return (
                    <span key={index} className="inline-block px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200 font-bold border border-blue-200 dark:border-blue-800 text-[10px] mx-0.5 shadow-sm">
                        {locale === 'ar' ? 'الشركة' : '{company}'}
                    </span>
                );
            }
            return part;
        });
    };

    const selectedTemplate = templates.find((tpl: any) => tpl.id === selectedTemplateId);

    return (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 animate-in fade-in duration-300">
            {/* Left Column */}
            <div className="space-y-6">
                {/* Contacts Parser card */}
                <Card
                    onDragEnter={handleDragEnter}
                    onDragOver={handleDragOver}
                    onDragLeave={handleDragLeave}
                    onDrop={handleDrop}
                    className={`transition-all duration-300 relative overflow-hidden ${
                        isDragging 
                            ? 'ring-2 ring-teal-500 border-teal-500 bg-teal-50/5 dark:bg-teal-950/5 scale-[1.01] shadow-lg' 
                            : ''
                    }`}
                >
                    <CardHeader className="pb-4 border-b">
                        <div className="flex items-center justify-between">
                            <CardTitle className="text-base flex items-center gap-2">
                                <Users className="w-4.5 h-4.5 text-teal-600" />
                                {t.campaign.contactsLabel}
                            </CardTitle>
                            {getParsedRecipients.length > 0 && (
                                <Badge variant="secondary" className="bg-teal-50 text-teal-700 hover:bg-teal-100">
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
                                        {locale === 'ar' ? 'أفلت ملف الأرقام هنا فوراً' : 'Drop your contacts file here'}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {locale === 'ar' ? 'سيتم تلقائياً استخراج جهات الاتصال' : 'Contacts will be automatically imported'}
                                    </p>
                                </div>
                            </div>
                        )}

                        {/* Selector Tabs */}
                        <div className="grid grid-cols-2 p-1 bg-muted rounded-xl text-xs font-semibold">
                            <button
                                type="button"
                                onClick={() => setInputMethod('paste')}
                                className={`py-1.5 rounded-lg transition-all flex items-center justify-center gap-1.5 ${inputMethod === 'paste' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'}`}
                            >
                                <Users className="size-3.5" />
                                {locale === 'ar' ? 'لصق يدوي للأرقام' : 'Manual Paste'}
                            </button>
                            <button
                                type="button"
                                onClick={() => setInputMethod('file')}
                                className={`py-1.5 rounded-lg transition-all flex items-center justify-center gap-1.5 ${inputMethod === 'file' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'}`}
                            >
                                <Upload className="size-3.5" />
                                {locale === 'ar' ? 'رفع ملف (TXT / CSV)' : 'Upload File (TXT / CSV)'}
                            </button>
                        </div>

                        {inputMethod === 'paste' ? (
                            <Textarea
                                rows={6}
                                value={contactsText}
                                onChange={e => setContactsText(e.target.value)}
                                placeholder={t.campaign.contactsPlaceholder}
                                className="font-mono text-xs resize-none focus-visible:ring-teal-500"
                            />
                        ) : (
                            <div className="space-y-4">
                                {!uploadedFile ? (
                                    <div
                                        onClick={() => fileInputRef.current?.click()}
                                        className="border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-all duration-200 flex flex-col items-center justify-center gap-3 border-muted-foreground/20 hover:border-teal-400 hover:bg-muted/30"
                                    >
                                        <div className="size-12 rounded-full bg-teal-50 dark:bg-teal-950/30 flex items-center justify-center text-teal-600">
                                            <Upload className="size-5" />
                                        </div>
                                        <div className="space-y-1">
                                            <p className="text-sm font-semibold">
                                                {locale === 'ar' ? 'انقر لتصفح ملف الأرقام' : 'Click to browse contacts file'}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {locale === 'ar' ? 'يدعم ملفات TXT أو CSV' : 'Supports TXT or CSV files'}
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
                                    <div className="border rounded-xl p-4 bg-muted/30 flex items-center gap-3.5 relative overflow-hidden group">
                                        <div className="absolute top-0 left-0 w-1.5 h-full bg-teal-500" />
                                        <div className="size-10 rounded-lg bg-teal-50 dark:bg-teal-950/40 flex items-center justify-center text-teal-600 shrink-0">
                                            {uploadedFile.type === 'csv' 
                                                ? <FileSpreadsheet className="size-5" /> 
                                                : <FileText className="size-5" />
                                            }
                                        </div>
                                        <div className="flex-1 min-w-0 space-y-0.5">
                                            <p className="text-sm font-bold truncate pr-6">{uploadedFile.name}</p>
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

                        {/* Parsed recipients visual grid preview */}
                        {getParsedRecipients.length > 0 && (
                            <div className="max-h-36 overflow-y-auto border rounded-xl divide-y">
                                {getParsedRecipients.slice(0, 10).map((c: any, idx: number) => (
                                    <div key={idx} className="p-2.5 flex items-center justify-between text-[11px] font-medium bg-muted/50">
                                        <span className="font-mono font-bold">{c.phone}</span>
                                        <span className="truncate max-w-[120px]">{c.name || '—'}</span>
                                        <span className="truncate max-w-[120px] text-muted-foreground">{c.company || '—'}</span>
                                    </div>
                                ))}
                                {getParsedRecipients.length > 10 && (
                                    <div className="p-2 text-center text-[10px] text-muted-foreground font-bold bg-muted/20">
                                        + {getParsedRecipients.length - 10} more recipients
                                    </div>
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Template Selector card */}
                <Card>
                    <CardHeader className="pb-4 border-b">
                        <CardTitle className="text-base flex items-center gap-2">
                            <MessageSquare className="w-4.5 h-4.5 text-teal-600" />
                            Select Message Template
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6 space-y-4">
                        {templates.length === 0 ? (
                            <div className="text-sm text-muted-foreground bg-muted p-4 rounded-xl text-center">
                                No templates found. Please create one in the Templates tab first!
                            </div>
                        ) : (
                            <div className="space-y-3">
                                {templates.map((tpl: any) => (
                                    <label key={tpl.id} className={`flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all ${selectedTemplateId === tpl.id ? 'border-teal-500 bg-teal-50/50 dark:bg-teal-950/20 shadow-sm' : 'hover:border-teal-300'}`}>
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
                                                        {p.type}
                                                    </Badge>
                                                ))}
                                            </div>
                                        </div>
                                    </label>
                                ))}
                            </div>
                        )}

                        {selectedTemplate && (
                            <div className="mt-6 border rounded-2xl overflow-hidden shadow-md">
                                {/* Preview Header */}
                                <div className="bg-teal-700 text-white px-4 py-3 flex items-center justify-between border-b border-teal-800">
                                    <div className="flex items-center gap-3">
                                        <div className="size-9 rounded-full bg-teal-600 border border-teal-500 flex items-center justify-center font-black text-xs shadow-sm">
                                            WA
                                        </div>
                                        <div>
                                            <p className="text-xs font-black">{selectedTemplate.name}</p>
                                            <p className="text-[10px] text-teal-100">{locale === 'ar' ? 'معاينة الرسالة' : 'Live Preview'}</p>
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
                                        {locale === 'ar' ? 'تعديل القالب' : 'Edit Template'}
                                    </Button>
                                </div>

                                {/* Chat Body — renders each part as a separate WhatsApp bubble */}
                                <div className="p-4 bg-[#efeae2] dark:bg-[#0b141a] flex flex-col space-y-2 max-h-[400px] overflow-y-auto relative" style={{ backgroundImage: 'radial-gradient(circle, rgba(0, 0, 0, 0.03) 1px, transparent 1px)', backgroundSize: '20px 20px' }}>
                                    {(selectedTemplate.parts && selectedTemplate.parts.length > 0
                                        ? selectedTemplate.parts
                                        : [
                                            ...(selectedTemplate.message ? [{ type: 'text', message: selectedTemplate.message }] : []),
                                            ...(selectedTemplate.media_url ? [{ type: selectedTemplate.media_type, media_url: selectedTemplate.media_url }] : [])
                                          ]
                                    ).map((part: any, idx: number) => (
                                        <div key={idx} className="max-w-[85%] self-end relative rounded-2xl px-3.5 py-2.5 bg-[#d9fdd3] text-[#111b21] dark:bg-[#005c4b] dark:text-[#e9edef] shadow-md text-xs space-y-1 border border-[#d9fdd3]/20 dark:border-[#005c4b]/20">
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
                                                <div className="p-3 flex items-center gap-2 bg-muted/40 rounded-lg text-left">
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
                                            {idx === 0 && <div className="absolute right-[-5px] top-0 w-3 h-3 bg-[#d9fdd3] dark:bg-[#005c4b] rotate-45 transform origin-top-left" style={{ clipPath: 'polygon(100% 0, 0 0, 100% 100%)' }} />}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>

            </div>

            {/* Right Column: Safety Settings & Launch Control */}
            <div className="space-y-6">
                {/* Safety configuration card */}
                <Card className="border-muted shadow-sm rounded-3xl overflow-hidden hover:shadow-md transition-all duration-300">
                    <CardHeader className="pb-4 border-b bg-muted/20">
                        <CardTitle className="text-base flex items-center justify-between">
                            <span className="flex items-center gap-2">
                                <ShieldCheck className="w-5 h-5 text-teal-600" />
                                {locale === 'ar' ? 'ضبط فترات التأخير والأمان' : 'Pacing Speed & Safety'}
                            </span>
                            <Badge variant="secondary" className="bg-teal-50 text-teal-700 dark:bg-teal-950/40 dark:text-teal-300 border-teal-200 font-bold">
                                {locale === 'ar' ? 'حماية نشطة' : 'Active Shields'}
                            </Badge>
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6 space-y-6">
                        
                        {/* Speed Delay Presets Grid */}
                        <div className="space-y-3.5">
                            <div className="flex items-center justify-between text-xs">
                                <Label className="font-black text-foreground">
                                    {locale === 'ar' ? 'سرعة إرسال الرسائل' : 'Message Dispatch Pacing'}
                                </Label>
                                <span className="font-mono text-muted-foreground bg-muted px-2.5 py-0.5 rounded-md text-[10px] font-bold">
                                    {(() => {
                                        const calculatedMin = Math.round(600 / maxWpm);
                                        const calculatedMax = Math.round(600 / minWpm);
                                        return locale === 'ar' ? `${calculatedMin} - ${calculatedMax} ثانية` : `${calculatedMin} - ${calculatedMax}s Delay`;
                                    })()}
                                </span>
                            </div>
                            
                            {(() => {
                                const activePreset = ((minWpm === 45 || minWpm === 40) && maxWpm === 75) ? 'safe' :
                                                     (minWpm === 20 && maxWpm === 40) ? 'cautious' :
                                                     (minWpm === 100 && maxWpm === 200) ? 'turbo' : 'custom';
                                
                                const handleSelectPreset = (presetId: string) => {
                                    if (presetId === 'safe') {
                                        setMinWpm(40);
                                        setMaxWpm(75);
                                    } else if (presetId === 'cautious') {
                                        setMinWpm(20);
                                        setMaxWpm(40);
                                    } else if (presetId === 'turbo') {
                                        setMinWpm(100);
                                        setMaxWpm(200);
                                    } else {
                                        // Set to custom range values
                                        const calculatedMin = Math.round(600 / maxWpm);
                                        const calculatedMax = Math.round(600 / minWpm);
                                        setCustomMin(calculatedMin);
                                        setCustomMax(calculatedMax);
                                    }
                                };

                                const presetsList = [
                                    {
                                        id: 'safe',
                                        title: locale === 'ar' ? 'آمن وطبيعي' : 'Safe & Natural',
                                        delayText: '8-15s',
                                        icon: ShieldCheck,
                                        colorClass: 'emerald',
                                        badge: locale === 'ar' ? 'آمن جداً' : 'Excellent Safety',
                                        rateText: locale === 'ar' ? '~300 رسالة/س' : '~300 msgs/hr',
                                        description: locale === 'ar' 
                                            ? 'محاكاة الكتابة البشرية والانتظار العشوائي الطبيعي. الخيار الأكثر أماناً لحماية حسابك من الحظر.' 
                                            : 'Simulates natural keyboard pacing & human variance. Highly recommended for anti-ban safety.'
                                    },
                                    {
                                        id: 'cautious',
                                        title: locale === 'ar' ? 'شديد الحذر' : 'Ultra Cautious',
                                        delayText: '15-30s',
                                        icon: Clock,
                                        colorClass: 'amber',
                                        badge: locale === 'ar' ? 'حماية قصوى' : 'Max Shield',
                                        rateText: locale === 'ar' ? '~150 رسالة/س' : '~150 msgs/hr',
                                        description: locale === 'ar' 
                                            ? 'تأخيرات ممتدة جداً. مثالي للحسابات الجديدة أو عند مراسلة أرقام لأول مرة.' 
                                            : 'Extended delays. Perfect for fresh channels and completely cold outreach.'
                                    },
                                    {
                                        id: 'turbo',
                                        title: locale === 'ar' ? 'سريع جداً' : 'Turbo Speed',
                                        delayText: '3-6s',
                                        icon: Zap,
                                        colorClass: 'rose',
                                        badge: locale === 'ar' ? 'مخاطرة عالية' : 'High Risk',
                                        rateText: locale === 'ar' ? '~800 رسالة/س' : '~800 msgs/hr',
                                        description: locale === 'ar' 
                                            ? 'فواصل زمنية ضيقة جداً. ينصح به فقط للحسابات القديمة وقوائم العملاء المتفاعلين.' 
                                            : 'Rapid dispatch intervals. Recommended only for mature, highly responsive channels.'
                                    },
                                    {
                                        id: 'custom',
                                        title: locale === 'ar' ? 'تأخير يدوي' : 'Custom Delay',
                                        delayText: locale === 'ar' ? 'مخصص' : 'Manual',
                                        icon: Sliders,
                                        colorClass: 'teal',
                                        badge: locale === 'ar' ? 'تخصيص كامل' : 'Customizable',
                                        rateText: locale === 'ar' ? 'معدل مرن' : 'Flexible Rate',
                                        description: locale === 'ar' 
                                            ? 'حدد نطاق الثواني الفعلي المفضل لديك يدوياً بين كل رسالة إرسال.' 
                                            : 'Directly establish custom interval ranges between message batches manually.'
                                    }
                                ];

                                const selectedPreset = presetsList.find(p => p.id === activePreset) || presetsList[0];
                                const ActiveIcon = selectedPreset.icon;

                                return (
                                    <div className="space-y-4">
                                        {/* Horizontal Premium Segmented Selector */}
                                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 bg-muted/30 p-1.5 rounded-2xl border border-muted-foreground/5">
                                            {presetsList.map((preset) => {
                                                const isSelected = activePreset === preset.id;
                                                const Icon = preset.icon;
                                                let btnStyles = "";
                                                
                                                if (isSelected) {
                                                    if (preset.colorClass === 'emerald') {
                                                        btnStyles = "bg-emerald-500 text-white shadow-md shadow-emerald-500/10";
                                                    } else if (preset.colorClass === 'amber') {
                                                        btnStyles = "bg-amber-500 text-white shadow-md shadow-amber-500/10";
                                                    } else if (preset.colorClass === 'rose') {
                                                        btnStyles = "bg-rose-500 text-white shadow-md shadow-rose-500/10";
                                                    } else {
                                                        btnStyles = "bg-teal-500 text-white shadow-md shadow-teal-500/10";
                                                    }
                                                } else {
                                                    btnStyles = "text-muted-foreground hover:text-foreground hover:bg-muted/50 bg-transparent";
                                                }

                                                return (
                                                    <button
                                                        key={preset.id}
                                                        type="button"
                                                        onClick={() => handleSelectPreset(preset.id)}
                                                        className={`py-2 px-1 rounded-xl transition-all duration-300 flex flex-col items-center justify-center gap-1 text-center select-none ${btnStyles}`}
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

                                        {/* Dynamic details card explaining the selected speed */}
                                        <div className={`p-4 rounded-2xl border transition-all duration-300 text-left flex gap-3.5 relative overflow-hidden ${
                                            selectedPreset.colorClass === 'emerald' ? 'border-emerald-500/20 bg-emerald-500/[0.02] dark:bg-emerald-950/[0.02]' :
                                            selectedPreset.colorClass === 'amber' ? 'border-amber-500/20 bg-amber-500/[0.02] dark:bg-amber-950/[0.02]' :
                                            selectedPreset.colorClass === 'rose' ? 'border-rose-500/20 bg-rose-500/[0.02] dark:bg-rose-950/[0.02]' :
                                            'border-teal-500/20 bg-teal-500/[0.02] dark:bg-teal-950/[0.02]'
                                        }`}>
                                            {/* Colored left indicator */}
                                            <div className={`absolute left-0 top-0 bottom-0 w-1 ${
                                                selectedPreset.colorClass === 'emerald' ? 'bg-emerald-500' :
                                                selectedPreset.colorClass === 'amber' ? 'bg-amber-500' :
                                                selectedPreset.colorClass === 'rose' ? 'bg-rose-500' :
                                                'bg-teal-500'
                                            }`} />
                                            
                                            <div className={`p-2 rounded-xl h-fit shrink-0 ${
                                                selectedPreset.colorClass === 'emerald' ? 'bg-emerald-100/50 text-emerald-600 dark:bg-emerald-950/50' :
                                                selectedPreset.colorClass === 'amber' ? 'bg-amber-100/50 text-amber-600 dark:bg-amber-950/50' :
                                                selectedPreset.colorClass === 'rose' ? 'bg-rose-100/50 text-rose-600 dark:bg-rose-950/50' :
                                                'bg-teal-100/50 text-teal-600 dark:bg-teal-950/50'
                                            }`}>
                                                <ActiveIcon className="size-5 shrink-0" />
                                            </div>
                                            
                                            <div className="flex-1 space-y-1.5 min-w-0">
                                                <div className="flex items-center justify-between flex-wrap gap-1.5">
                                                    <h4 className="font-extrabold text-xs text-foreground flex items-center gap-1.5">
                                                        {selectedPreset.title}
                                                        <Badge variant="outline" className={`text-[9px] font-bold px-2 py-0 h-5 border-none ${
                                                            selectedPreset.colorClass === 'emerald' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' :
                                                            selectedPreset.colorClass === 'amber' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400' :
                                                            selectedPreset.colorClass === 'rose' ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400' :
                                                            'bg-teal-500/10 text-teal-600 dark:text-teal-400'
                                                        }`}>
                                                            {selectedPreset.badge}
                                                        </Badge>
                                                    </h4>
                                                    <span className="text-[10px] font-black text-muted-foreground flex items-center gap-1">
                                                        <Zap className="size-3.5 text-muted-foreground/60" />
                                                        {selectedPreset.rateText}
                                                    </span>
                                                </div>
                                                
                                                <p className="text-[11px] text-muted-foreground leading-relaxed text-left">
                                                    {selectedPreset.description}
                                                </p>

                                                {/* Custom delay subcard inputs if Custom mode is selected */}
                                                {activePreset === 'custom' && (
                                                    <div className="pt-3.5 mt-3 border-t border-dashed border-muted-foreground/15 space-y-3.5">
                                                        <div className="grid grid-cols-2 gap-4">
                                                            <div className="space-y-1.5">
                                                                <Label className="text-[10px] text-muted-foreground font-bold">{locale === 'ar' ? 'الحد الأدنى للتأخير' : 'Min Delay (seconds)'}</Label>
                                                                <Input
                                                                    type="number"
                                                                    min={1}
                                                                    max={120}
                                                                    value={customMin}
                                                                    onChange={e => updateCustomMin(Number(e.target.value))}
                                                                    className="font-mono text-center font-bold text-xs h-9 focus-visible:ring-teal-500"
                                                                />
                                                            </div>
                                                            <div className="space-y-1.5">
                                                                <Label className="text-[10px] text-muted-foreground font-bold">{locale === 'ar' ? 'الحد الأقصى للتأخير' : 'Max Delay (seconds)'}</Label>
                                                                <Input
                                                                    type="number"
                                                                    min={customMin + 1}
                                                                    max={180}
                                                                    value={customMax}
                                                                    onChange={e => updateCustomMax(Number(e.target.value))}
                                                                    className="font-mono text-center font-bold text-xs h-9 focus-visible:ring-teal-500"
                                                                />
                                                            </div>
                                                        </div>
                                                        <div className="flex items-start gap-2 text-[10px] text-muted-foreground bg-background/40 p-2.5 rounded-xl border border-muted">
                                                            <Info className="size-3.5 text-teal-500 shrink-0 mt-0.5" />
                                                            <span className="leading-relaxed text-left">
                                                                {locale === 'ar' 
                                                                    ? `سيتم الانتظار لفترة انتظار عشوائية تتراوح بين ${customMin} و ${customMax} ثانية بين كل رسالة لمنع كشف أي نمط متكرر.` 
                                                                    : `Pacing engine will wait a random number of seconds between ${customMin}s and ${customMax}s before sending each message.`
                                                                }
                                                            </span>
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                );
                            })()}
                        </div>

                        {/* Expandable Advanced Anti-Ban Settings Section */}
                        <div className="pt-2 border-t border-muted/50">
                            {!showAdvancedSafety ? (
                                <div 
                                    onClick={() => setShowAdvancedSafety(true)}
                                    className="p-3.5 rounded-2xl border border-teal-500/20 bg-teal-500/[0.02] hover:bg-teal-500/[0.04] cursor-pointer flex items-center justify-between transition-all duration-300 group select-none"
                                >
                                    <div className="flex items-center gap-2.5 text-left">
                                        <div className="size-8 rounded-xl bg-teal-50 dark:bg-teal-950/40 border border-teal-200/50 dark:border-teal-800/50 flex items-center justify-center text-teal-600">
                                            <ShieldCheck className="size-4" />
                                        </div>
                                        <div>
                                            <p className="text-xs font-bold text-foreground">
                                                {locale === 'ar' ? 'خيارات الحماية النشطة مفعّلة' : 'Smart Protection Shields Active'}
                                            </p>
                                            <p className="text-[10px] text-muted-foreground mt-0.5">
                                                {locale === 'ar' 
                                                    ? `محاكاة الكتابة (${typoChance}%) • حماية الحظر التلقائي مفعلة`
                                                    : `Typo Sim (${typoChance}%) • Anti-Ban Shields Active`
                                                }
                                            </p>
                                        </div>
                                    </div>
                                    <Button 
                                        size="sm" 
                                        variant="ghost" 
                                        type="button"
                                        className="text-teal-600 hover:text-teal-700 hover:bg-teal-50/50 text-[11px] font-bold gap-1 px-2.5 h-8 rounded-lg"
                                    >
                                        {locale === 'ar' ? 'تعديل الحماية' : 'Configure'}
                                        <ChevronDown className="size-3.5 group-hover:translate-y-0.5 transition-transform" />
                                    </Button>
                                </div>
                            ) : (
                                <div className="space-y-4 pt-2 animate-in fade-in slide-in-from-top-2 duration-300">
                                    <div className="flex items-center justify-between border-b pb-2">
                                        <span className="text-xs font-bold text-foreground flex items-center gap-1.5">
                                            <Settings className="size-3.5 text-teal-500" />
                                            {locale === 'ar' ? 'تعديل خيارات الأمان المتقدمة' : 'Advanced Safety Options'}
                                        </span>
                                        <Button 
                                            size="sm" 
                                            variant="ghost" 
                                            type="button"
                                            onClick={() => setShowAdvancedSafety(false)}
                                            className="text-muted-foreground hover:text-foreground text-[10px] font-bold gap-1 h-7 px-2"
                                        >
                                            {locale === 'ar' ? 'إخفاء الإعدادات' : 'Hide Settings'}
                                            <ChevronUp className="size-3.5" />
                                        </Button>
                                    </div>
                                    
                                    {/* Typo Simulation Pacing Slider */}
                                    <div className="space-y-2 p-3.5 rounded-2xl border border-muted bg-muted/5 text-left">
                                        <div className="flex items-center justify-between text-xs">
                                            <div className="flex items-center gap-1.5">
                                                <HeartPulse className="size-4 text-teal-600 shrink-0" />
                                                <Label className="font-bold text-foreground">{locale === 'ar' ? 'نسبة محاكاة الكتابة البشرية' : 'Human Typo Simulation'}</Label>
                                            </div>
                                            <Badge variant="outline" className="font-black px-2.5 py-0.5 rounded-lg border-teal-500/20 text-teal-600 bg-teal-50/50 dark:bg-teal-950/20 text-[10px]">
                                                {typoChance}%
                                            </Badge>
                                        </div>
                                        <p className="text-[10px] text-muted-foreground leading-normal">
                                            {locale === 'ar' 
                                                ? 'يحاكي حدوث بعض الأخطاء الإملائية ومسحها فوراً ليبدو النشاط بشرياً بالكامل.'
                                                : 'Simulates occasional keyboard errors and backspacing corrections dynamically.'
                                            }
                                        </p>
                                        <div className="flex items-center gap-3">
                                            <span className="text-[9px] font-bold text-muted-foreground select-none">0%</span>
                                            <Input
                                                type="range"
                                                min={0}
                                                max={15}
                                                value={typoChance}
                                                onChange={e => setTypoChance(Number(e.target.value))}
                                                className="p-0 h-auto border-none cursor-pointer accent-teal-600 flex-1"
                                            />
                                            <span className="text-[9px] font-bold text-muted-foreground select-none">15%</span>
                                        </div>
                                    </div>

                                    {/* Toggles grid */}
                                    <div className="grid grid-cols-1 gap-2.5">
                                        {/* AI Synonyms Switch */}
                                        <div className="flex items-start justify-between p-3 rounded-xl border border-muted bg-background hover:border-teal-100 transition-all select-none">
                                            <div className="space-y-0.5 text-left max-w-[82%]">
                                                <div className="flex items-center gap-1.5">
                                                    <Sparkles className="size-3.5 text-teal-500 shrink-0" />
                                                    <Label htmlFor="useSynonyms" className="font-bold text-xs cursor-pointer">{locale === 'ar' ? 'مرادفات الذكاء الاصطناعي' : 'AI Synonym Spin'}</Label>
                                                </div>
                                                <p className="text-[10px] text-muted-foreground leading-normal">
                                                    {locale === 'ar' ? 'تغيير صياغة الكلمات تلقائياً لتفادي كشف الرسائل المتكررة بنمط ثابت.' : 'Autospin text phrases contextually to disrupt spam fingerprint filters.'}
                                                </p>
                                            </div>
                                            <Switch
                                                id="useSynonyms"
                                                checked={useSynonyms}
                                                onCheckedChange={setUseSynonyms}
                                            />
                                        </div>

                                        {/* Bell Curve Delay Switch */}
                                        <div className="flex items-start justify-between p-3 rounded-xl border border-muted bg-background hover:border-teal-100 transition-all select-none">
                                            <div className="space-y-0.5 text-left max-w-[82%]">
                                                <div className="flex items-center gap-1.5">
                                                    <Clock className="size-3.5 text-teal-500 shrink-0" />
                                                    <Label htmlFor="bellCurve" className="font-bold text-xs cursor-pointer">{locale === 'ar' ? 'تأخيرات منحنى الجرس' : 'Human Bell Curve'}</Label>
                                                </div>
                                                <p className="text-[10px] text-muted-foreground leading-normal">
                                                    {locale === 'ar' ? 'توزيع تأخيرات الإرسال بشكل منحنى طبيعي للحصول على فواصل زمنية غير متوقعة.' : 'Pacing follows natural probability curves for unpredictable intervals.'}
                                                </p>
                                            </div>
                                            <Switch
                                                id="bellCurve"
                                                checked={bellCurve}
                                                onCheckedChange={setBellCurve}
                                            />
                                        </div>

                                        {/* Track Delivery Switch */}
                                        <div className="flex items-start justify-between p-3 rounded-xl border border-muted bg-background hover:border-teal-100 transition-all select-none">
                                            <div className="space-y-0.5 text-left max-w-[82%]">
                                                <div className="flex items-center gap-1.5">
                                                    <Check className="size-3.5 text-teal-500 shrink-0" />
                                                    <Label htmlFor="trackDelivery" className="font-bold text-xs cursor-pointer">{locale === 'ar' ? 'تتبع حالة التسليم' : 'Track Delivery Status'}</Label>
                                                </div>
                                                <p className="text-[10px] text-muted-foreground leading-normal">
                                                    {locale === 'ar' ? 'فحص واسترجاع صحة الاستلام والقراءة لكل جهة اتصال بالخلفية.' : 'Fetch real-time read and delivery status to log campaigns.'}
                                                </p>
                                            </div>
                                            <Switch
                                                id="trackDelivery"
                                                checked={trackDelivery}
                                                onCheckedChange={setTrackDelivery}
                                            />
                                        </div>

                                        {/* Emergency Stop Switch */}
                                        <div className={`flex items-start justify-between p-3 rounded-xl border transition-all select-none ${
                                            stopOnBlock 
                                                ? 'border-rose-200 dark:border-rose-950 bg-rose-500/[0.02] dark:bg-rose-950/[0.03]' 
                                                : 'border-muted bg-background hover:border-rose-100'
                                        }`}>
                                            <div className="space-y-0.5 text-left max-w-[82%]">
                                                <div className="flex items-center gap-1.5">
                                                    <AlertTriangle className={`size-3.5 shrink-0 ${stopOnBlock ? 'text-rose-500 animate-pulse' : 'text-muted-foreground'}`} />
                                                    <Label htmlFor="stopOnBlock" className={`font-bold text-xs cursor-pointer ${stopOnBlock ? 'text-rose-600 dark:text-rose-400' : 'text-foreground'}`}>{locale === 'ar' ? 'درع الحظر وإيقاف الطوارئ' : 'Auto Kill-Switch'}</Label>
                                                </div>
                                                <p className="text-[10px] text-muted-foreground leading-normal">
                                                    {locale === 'ar' ? 'إيقاف وتجميد الإرسال فوراً إذا ارتفعت نسبة فشل الإرسال لحماية قنواتك.' : 'Instantly halt bulk sending if session failure rates exceed limit.'}
                                                </p>
                                            </div>
                                            <Switch
                                                id="stopOnBlock"
                                                checked={stopOnBlock}
                                                onCheckedChange={setStopOnBlock}
                                                className="data-[state=checked]:bg-rose-500"
                                            />
                                        </div>
                                    </div>

                                    {/* Emergency Risk Threshold */}
                                    {stopOnBlock && (
                                        <div className="p-4 rounded-2xl border border-rose-200 dark:border-rose-900 bg-rose-50/20 dark:bg-rose-950/10 space-y-3 animate-in slide-in-from-top-2 duration-300 text-left">
                                            <div className="flex items-center justify-between text-xs">
                                                <div className="flex items-center gap-2">
                                                    <AlertCircle className="size-4 text-rose-500 shrink-0" />
                                                    <Label className="text-rose-700 dark:text-rose-300 font-bold">{locale === 'ar' ? 'عتبة الخطر القصوى للفشل' : 'Maximum Risk Threshold'}</Label>
                                                </div>
                                                <Badge variant="destructive" className="font-black px-2.5 py-0.5 rounded-lg bg-rose-500 text-white text-[10px]">
                                                    {maxBlockRate}%
                                                </Badge>
                                            </div>
                                            <p className="text-[10px] text-muted-foreground leading-normal">
                                                {locale === 'ar' 
                                                    ? `سيتم إيقاف وتجميد الحملة فوراً إذا تجاوزت نسبة الرسائل الفاشلة أو المحظورة ${maxBlockRate}% لحماية بقية جهات الاتصال.`
                                                    : `Halt the campaign completely if failed/blocked messages exceed ${maxBlockRate}% of total batch.`
                                                }
                                            </p>
                                            <div className="flex items-center gap-3">
                                                <span className="text-[9px] font-bold text-rose-500/70 select-none">2%</span>
                                                <Input
                                                    type="range"
                                                    min={2}
                                                    max={20}
                                                    value={maxBlockRate}
                                                    onChange={e => setMaxBlockRate(Number(e.target.value))}
                                                    className="p-0 h-auto border-none cursor-pointer accent-rose-600 flex-1"
                                                />
                                                <span className="text-[9px] font-bold text-rose-500/70 select-none">20%</span>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Campaign Launch Control Card */}
                <Card className="border-teal-500/20 dark:border-teal-500/10 shadow-lg bg-card overflow-hidden relative rounded-3xl hover:shadow-md transition-all duration-300">
                    {/* Teal gradient left border accent */}
                    <div className="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-teal-500 to-emerald-600" />
                    
                    <CardHeader className="pb-3 border-b bg-muted/20">
                        <CardTitle className="text-base flex items-center justify-between">
                            <span className="flex items-center gap-2">
                                <Settings className="w-5 h-5 text-teal-600 dark:text-teal-400" />
                                {locale === 'ar' ? 'تجهيز وإطلاق الحملة التسويقية' : 'Launch & Dispatch Control'}
                            </span>
                            <Badge variant="outline" className="border-teal-500/30 text-teal-600 bg-teal-50/50 dark:bg-teal-950/20 text-[10px] font-bold">
                                {locale === 'ar' ? 'جاهزية الإطلاق' : 'Ready'}
                            </Badge>
                        </CardTitle>
                    </CardHeader>

                    <CardContent className="pt-5 space-y-5">
                        {/* Campaign Inputs */}
                        <div className="space-y-4">
                            <div className="space-y-1.5 text-left">
                                <Label className="text-[11px] font-bold text-muted-foreground flex items-center gap-1">
                                    <Tag className="size-3 text-teal-500" />
                                    {locale === 'ar' ? 'اسم الحملة الإعلانية (لتمييز التقارير)' : 'Campaign Identifier Name'}
                                </Label>
                                <Input
                                    type="text"
                                    value={campaignName}
                                    onChange={e => setCampaignName(e.target.value)}
                                    placeholder={locale === 'ar' ? 'مثال: حملة المبيعات - مايو 2026' : 'e.g. Sales Campaign - May 2026'}
                                    className="h-10 focus-visible:ring-teal-500 border-muted font-medium"
                                />
                            </div>

                            <div className="space-y-1.5 text-left">
                                <Label className="text-[11px] font-bold text-muted-foreground flex items-center gap-1">
                                    <UserCheck className="size-3 text-teal-500" />
                                    {locale === 'ar' ? 'حساب الإرسال النشط' : 'Active Dispatching Session'}
                                </Label>
                                <select
                                    value={selectedAccount}
                                    onChange={e => setSelectedAccount(e.target.value)}
                                    className="flex h-10 w-full rounded-md border border-muted bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 font-bold text-foreground"
                                >
                                    <option value="" className="text-muted-foreground font-medium">{locale === 'ar' ? 'اختر جلسة الإرسال النشطة...' : 'Select active sending session...'}</option>
                                    {sessions.filter((s: any) => s.state === 'connected').map((s: any) => (
                                        <option key={s.accountId} value={s.accountId} className="py-2 font-bold">
                                            {s.accountId} {s.state === 'connected' ? (locale === 'ar' ? '● (متصل بنجاح)' : '● (Connected)') : `● (${s.state})`}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        {/* Live Summary Checklist Panel */}
                        <div className="p-4 rounded-2xl bg-muted/20 border border-muted/80 space-y-3.5 text-left">
                            <h5 className="text-[10px] font-black uppercase tracking-wider text-muted-foreground/80 flex items-center gap-1">
                                <CheckCircle2 className="size-3 text-teal-600" />
                                {locale === 'ar' ? 'مراجعة معايير ما قبل الإطلاق' : 'Pre-Dispatch Summary'}
                            </h5>
                            <ul className="space-y-2 text-[11px] font-medium text-foreground">
                                <li className="flex items-center justify-between">
                                    <span className="text-muted-foreground">{locale === 'ar' ? 'المستهدفين النشطين:' : 'Valid Target Recipients:'}</span>
                                    <span className="font-bold flex items-center gap-1">
                                        {getParsedRecipients.length > 0 ? (
                                            <>
                                                <span className="text-emerald-600 font-black">✓</span> {getParsedRecipients.length} {locale === 'ar' ? 'جهة اتصال' : 'contacts'}
                                            </>
                                        ) : (
                                            <span className="text-rose-500 font-bold">⚠ {locale === 'ar' ? 'لا يوجد أرقام' : 'No contacts'}</span>
                                        )}
                                    </span>
                                </li>
                                
                                <li className="flex items-center justify-between">
                                    <span className="text-muted-foreground">{locale === 'ar' ? 'قالب الرسالة المختار:' : 'Selected Message Template:'}</span>
                                    <span className="font-bold flex items-center gap-1">
                                        {selectedTemplateId ? (
                                            <>
                                                <span className="text-emerald-600 font-black">✓</span> {templates.find((t: any) => t.id === selectedTemplateId)?.name || (locale === 'ar' ? 'محدد' : 'Selected')}
                                            </>
                                        ) : (
                                            <span className="text-rose-500 font-bold">⚠ {locale === 'ar' ? 'يرجى تحديد قالب' : 'No template selected'}</span>
                                        )}
                                    </span>
                                </li>

                                <li className="flex items-center justify-between">
                                    <span className="text-muted-foreground">{locale === 'ar' ? 'نمط سرعة الإرسال:' : 'Dispatch Pacing:'}</span>
                                    <span className="font-bold">
                                        {(() => {
                                            const activePreset = ((minWpm === 45 || minWpm === 40) && maxWpm === 75) ? 'safe' :
                                                                 (minWpm === 20 && maxWpm === 40) ? 'cautious' :
                                                                 (minWpm === 100 && maxWpm === 200) ? 'turbo' : 'custom';
                                            if (activePreset === 'safe') return locale === 'ar' ? 'آمن وطبيعي (8-15 ث)' : 'Safe & Natural (8-15s)';
                                            if (activePreset === 'cautious') return locale === 'ar' ? 'حذر للغاية (15-30 ث)' : 'Ultra Cautious (15-30s)';
                                            if (activePreset === 'turbo') return locale === 'ar' ? 'سريع جداً (3-6 ث) ⚠' : 'Turbo Speed (3-6s) ⚠';
                                            return locale === 'ar' ? `مخصص (${customMin}-${customMax} ث)` : `Custom (${customMin}-${customMax}s)`;
                                        })()}
                                    </span>
                                </li>

                                <li className="flex items-center justify-between">
                                    <span className="text-muted-foreground">{locale === 'ar' ? 'حالة الحماية والأمان:' : 'Safety Shield Status:'}</span>
                                    <span className={`font-bold flex items-center gap-1 ${stopOnBlock ? 'text-emerald-600' : 'text-amber-500'}`}>
                                        {stopOnBlock ? (
                                            <>
                                                <span className="font-black">✓</span> {locale === 'ar' ? 'نشطة بالكامل' : 'Fully Active'}
                                            </>
                                        ) : (
                                            <>
                                                <span className="font-black">⚠</span> {locale === 'ar' ? 'غير نشطة' : 'Inactive'}
                                            </>
                                        )}
                                    </span>
                                </li>

                                {getParsedRecipients.length > 0 && (
                                    <li className="flex items-center justify-between pt-2.5 mt-1 border-t border-dashed border-muted-foreground/10 text-xs">
                                        <span className="text-muted-foreground font-bold flex items-center gap-1">
                                            <Timer className="size-3.5 text-teal-600" />
                                            {locale === 'ar' ? 'الوقت الإجمالي المقدر:' : 'Estimated Campaign Duration:'}
                                        </span>
                                        <span className="font-black text-teal-600 dark:text-teal-400">
                                            {(() => {
                                                const avgDelay = (customMin + customMax) / 2;
                                                const totalSec = getParsedRecipients.length * avgDelay;
                                                const minTotal = Math.ceil(totalSec / 60);
                                                return locale === 'ar' ? `~${minTotal} دقيقة` : `~${minTotal} minutes`;
                                            })()}
                                        </span>
                                    </li>
                                )}
                            </ul>
                        </div>

                        {/* Interactive Test Send Panel */}
                        {showTestInput && (
                            <div className="p-4 rounded-2xl bg-muted/40 border border-muted/80 space-y-3.5 text-left animate-in slide-in-from-bottom-2 duration-300 relative">
                                <Label className="text-[11px] font-bold text-muted-foreground flex items-center gap-1.5">
                                    <Send className="size-3 text-teal-500" />
                                    {locale === 'ar' ? 'أدخل رقم الهاتف للتجربة (مع رمز الدولة بدون +)' : 'Test Phone Number (with country code, no +)'}
                                </Label>
                                <div className="flex gap-2">
                                    <Input
                                        type="text"
                                        value={testNumber}
                                        onChange={e => setTestNumber(e.target.value.replace(/[^0-9]/g, ''))}
                                        placeholder="e.g. 966500000000"
                                        className="h-10 focus-visible:ring-teal-500 border-muted font-mono text-xs flex-1"
                                    />
                                    <Button
                                        onClick={executeSendTest}
                                        disabled={isSendingTest || !testNumber || !selectedTemplateId || !selectedAccount}
                                        className="h-10 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl flex items-center gap-1.5 transition-all text-xs border-none"
                                    >
                                        {isSendingTest ? (
                                            <span className="size-3.5 border-2 border-white border-t-transparent rounded-full animate-spin shrink-0" />
                                        ) : (
                                            <Send className="size-3" />
                                        )}
                                        {locale === 'ar' ? 'إرسال الآن' : 'Send Now'}
                                    </Button>
                                    <Button
                                        variant="outline"
                                        onClick={() => {
                                            setShowTestInput(false);
                                            setTestSendStatus(null);
                                        }}
                                        className="h-10 px-3 border-muted text-muted-foreground hover:bg-muted font-bold rounded-xl text-xs"
                                    >
                                        {locale === 'ar' ? 'إلغاء' : 'Cancel'}
                                    </Button>
                                </div>
                                {testSendStatus && (
                                    <div className={`p-2.5 rounded-xl border flex items-center gap-2 text-[10px] font-bold ${
                                        testSendStatus.type === 'success' 
                                            ? 'border-emerald-500/25 bg-emerald-500/5 text-emerald-600 dark:text-emerald-400' 
                                            : 'border-rose-500/25 bg-rose-500/5 text-rose-600 dark:text-rose-400'
                                    }`}>
                                        {testSendStatus.type === 'success' ? (
                                            <Check className="size-4 text-emerald-500 shrink-0" />
                                        ) : (
                                            <AlertCircle className="size-4 text-rose-500 shrink-0 animate-bounce" />
                                        )}
                                        <span>{testSendStatus.message}</span>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Action Buttons Row */}
                        <div className="flex flex-col sm:flex-row gap-2.5">
                            {/* Premium Gradient Launch Button */}
                            <Button
                                onClick={handleLaunchCampaign}
                                disabled={isCampaignRunning || getParsedRecipients.length === 0 || !selectedTemplateId || !selectedAccount}
                                size="lg"
                                className="flex-1 bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 active:scale-[0.98] transition-all duration-300 text-white font-bold h-12 shadow-md hover:shadow-teal-500/20 flex items-center justify-center gap-2 rounded-2xl group border-none"
                            >
                                {isCampaignRunning ? (
                                    <>
                                        <span className="size-4 border-2 border-white border-t-transparent rounded-full animate-spin shrink-0" />
                                        {locale === 'ar' ? 'جاري بدء الحملة...' : 'Launching Campaign...'}
                                    </>
                                ) : (
                                    <>
                                        <Play className="w-3.5 h-3.5 fill-current group-hover:scale-110 transition-transform shrink-0" />
                                        {locale === 'ar' ? 'إطلاق وبدء الحملة الآن' : 'Launch & Start Campaign Now'}
                                    </>
                                )}
                            </Button>

                            {/* Secondary Test Send Trigger Button */}
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setShowTestInput(p => !p)}
                                disabled={isCampaignRunning || !selectedTemplateId || !selectedAccount}
                                className={`sm:w-1/3 h-12 border-muted hover:border-teal-500/50 hover:bg-teal-500/[0.02] dark:hover:bg-teal-950/[0.05] transition-all duration-300 rounded-2xl font-bold flex items-center justify-center gap-2 ${
                                    showTestInput ? 'border-teal-500 bg-teal-50/20 dark:bg-teal-950/20 text-teal-600 dark:text-teal-400' : 'text-foreground'
                                }`}
                            >
                                <Send className="w-3.5 h-3.5 shrink-0" />
                                {locale === 'ar' ? 'إرسال تجريبي' : 'Send Test'}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}

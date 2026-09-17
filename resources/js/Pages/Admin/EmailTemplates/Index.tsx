import React, { useState, useMemo, useRef } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { useToast } from '@/Components/ui/use-toast';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/Components/ui/dialog';
import {
    Mail,
    Monitor,
    Smartphone,
    ExternalLink,
    Copy,
    Check,
    RefreshCw,
    Send,
    Code,
    FileText,
    Search,
    Layers,
    Tag,
    ChevronRight,
    SlidersHorizontal,
    CheckCircle2,
    Eye,
} from 'lucide-react';

interface TemplateItem {
    key: string;
    name: string;
    name_ar: string;
    description: string;
    description_ar: string;
    category: string;
    category_label: string;
    category_label_ar: string;
    blade_view: string;
    blade_path: string;
    subject: string;
    sample_data: Record<string, any>;
    blade_source: string;
}

interface Props {
    templates: TemplateItem[];
    selectedKey: string;
    adminEmail: string;
}

export default function Index({ templates, selectedKey: initialSelectedKey, adminEmail }: Props) {
    const { toast } = useToast();
    const [selectedKey, setSelectedKey] = useState<string>(initialSelectedKey || templates[0]?.key || '');
    const [activeTab, setActiveTab] = useState<'preview' | 'source' | 'variables'>('preview');
    const [viewportMode, setViewportMode] = useState<'desktop' | 'mobile'>('desktop');
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedCategory, setSelectedCategory] = useState<string>('all');
    const [copiedSource, setCopiedSource] = useState(false);
    const [copiedVars, setCopiedVars] = useState(false);
    const [iframeKey, setIframeKey] = useState(1);
    const [loadingIframe, setLoadingIframe] = useState(true);

    // Send Test Email State
    const [testModalOpen, setTestModalOpen] = useState(false);
    const [testRecipient, setTestRecipient] = useState(adminEmail);
    const [sendingTest, setSendingTest] = useState(false);

    // Filter categories
    const categories = useMemo(() => {
        const cats = new Map<string, string>();
        templates.forEach((t) => {
            if (!cats.has(t.category)) {
                cats.set(t.category, t.category_label_ar || t.category_label);
            }
        });
        return Array.from(cats.entries()).map(([slug, label]) => ({ slug, label }));
    }, [templates]);

    // Filtered templates list
    const filteredTemplates = useMemo(() => {
        return templates.filter((t) => {
            const matchesCategory = selectedCategory === 'all' || t.category === selectedCategory;
            const query = searchQuery.toLowerCase().trim();
            const matchesSearch =
                !query ||
                t.name.toLowerCase().includes(query) ||
                t.name_ar.toLowerCase().includes(query) ||
                t.blade_view.toLowerCase().includes(query) ||
                t.blade_path.toLowerCase().includes(query) ||
                t.subject.toLowerCase().includes(query);

            return matchesCategory && matchesSearch;
        });
    }, [templates, selectedCategory, searchQuery]);

    // Currently active template
    const currentTemplate = useMemo(() => {
        return templates.find((t) => t.key === selectedKey) || templates[0];
    }, [templates, selectedKey]);

    const handleSelectTemplate = (key: string) => {
        setSelectedKey(key);
        setLoadingIframe(true);
        setIframeKey((prev) => prev + 1);
    };

    const handleRefreshPreview = () => {
        setLoadingIframe(true);
        setIframeKey((prev) => prev + 1);
    };

    const handleCopySource = () => {
        if (!currentTemplate?.blade_source) return;
        navigator.clipboard.writeText(currentTemplate.blade_source);
        setCopiedSource(true);
        toast({ title: 'تم نسخ كود الـ Blade بنجاح' });
        setTimeout(() => setCopiedSource(false), 2000);
    };

    const handleCopyVariables = () => {
        if (!currentTemplate?.sample_data) return;
        navigator.clipboard.writeText(JSON.stringify(currentTemplate.sample_data, null, 2));
        setCopiedVars(true);
        toast({ title: 'تم نسخ المتغيرات التجريبية بصيغة JSON' });
        setTimeout(() => setCopiedVars(false), 2000);
    };

    const handleSendTestEmail = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!testRecipient || !testRecipient.includes('@')) {
            toast({ title: 'يرجى إدخال عنوان بريد إلكتروني صحيح', variant: 'destructive' });
            return;
        }

        setSendingTest(true);
        try {
            const response = await fetch(`/admin/email-templates/${currentTemplate.key}/send-test`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ email: testRecipient }),
            });

            const data = await response.json();
            if (response.ok && data.success) {
                toast({ title: data.message || 'تم إرسال البريد التجريبي بنجاح!' });
                setTestModalOpen(false);
            } else {
                toast({
                    title: data.message || 'حدث خطأ أثناء إرسال البريد التجريبي',
                    variant: 'destructive',
                });
            }
        } catch (error: any) {
            toast({
                title: 'تعذر الاتصال بالسيرفر لإرسال البريد: ' + (error?.message || ''),
                variant: 'destructive',
            });
        } finally {
            setSendingTest(false);
        }
    };

    const previewUrl = currentTemplate ? `/admin/email-templates/${currentTemplate.key}/preview` : '';

    return (
        <AdminSidebarLayout header="معرض وقوالب البريد الإلكتروني (Email Templates)">
            <Head title="معرض وقوالب البريد الإلكتروني — Musoftwares Studio" />

            <div className="space-y-6">
                {/* Header Banner */}
                <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <div className="space-y-1">
                            <div className="flex items-center gap-2">
                                <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900 text-white shadow-sm">
                                    <Mail className="h-5 w-5" />
                                </div>
                                <h1 className="text-xl font-bold text-slate-900">
                                    معرض واستوديو قوالب البريد (Email Templates & Views)
                                </h1>
                            </div>
                            <p className="text-xs text-slate-500 max-w-2xl">
                                استعراض وفحص جميع تصاميم وقوالب الـ Blade الخاصة بالبريد الإلكتروني على السيستم، محاكاة العرض على الهواتف والشاشات المكتبية، واختبار الإرسال المباشر.
                            </p>
                        </div>

                        {/* Top quick stats */}
                        <div className="flex items-center gap-3">
                            <div className="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-center">
                                <span className="block text-xs font-semibold text-slate-500">إجمالي القوالب</span>
                                <span className="text-lg font-bold text-slate-900 font-mono">{templates.length}</span>
                            </div>
                            <Button
                                onClick={() => setTestModalOpen(true)}
                                className="bg-slate-900 hover:bg-slate-800 text-white text-xs gap-1.5 h-10 px-4 shadow-sm"
                            >
                                <Send className="h-3.5 w-3.5" />
                                إرسال بريد تجريبي
                            </Button>
                        </div>
                    </div>

                    {/* Filter & Search Bar */}
                    <div className="mt-5 flex flex-col md:flex-row items-center justify-between gap-3 pt-4 border-t border-slate-100">
                        {/* Categories pills */}
                        <div className="flex items-center gap-1.5 overflow-x-auto w-full md:w-auto pb-1 md:pb-0 scrollbar-none">
                            <button
                                onClick={() => setSelectedCategory('all')}
                                className={`px-3 py-1.5 rounded-lg text-xs font-medium transition-colors whitespace-nowrap ${
                                    selectedCategory === 'all'
                                        ? 'bg-slate-900 text-white'
                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                }`}
                            >
                                الكل ({templates.length})
                            </button>
                            {categories.map((cat) => {
                                const count = templates.filter((t) => t.category === cat.slug).length;
                                return (
                                    <button
                                        key={cat.slug}
                                        onClick={() => setSelectedCategory(cat.slug)}
                                        className={`px-3 py-1.5 rounded-lg text-xs font-medium transition-colors whitespace-nowrap ${
                                            selectedCategory === cat.slug
                                                ? 'bg-slate-900 text-white'
                                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                        }`}
                                    >
                                        {cat.label} ({count})
                                    </button>
                                );
                            })}
                        </div>

                        {/* Search Input */}
                        <div className="relative w-full md:w-72 shrink-0">
                            <Search className="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                            <Input
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                placeholder="بحث باسم القالب أو المسار..."
                                className="pl-9 h-9 text-xs rounded-lg border-slate-200"
                            />
                        </div>
                    </div>
                </div>

                {/* Main Two-Panel Studio Layout */}
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    {/* ════ Left Panel: Templates Directory (4 Cols) ════ */}
                    <div className="lg:col-span-4 rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden flex flex-col">
                        <div className="border-b border-slate-100 px-4 py-3 bg-slate-50/50 flex items-center justify-between">
                            <span className="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                                <Layers className="h-3.5 w-3.5 text-slate-700" />
                                دليل قوالب النظام ({filteredTemplates.length})
                            </span>
                        </div>

                        <div className="divide-y divide-slate-100 max-h-[750px] overflow-y-auto">
                            {filteredTemplates.length === 0 ? (
                                <div className="p-8 text-center text-xs text-slate-400">
                                    لا توجد قوالب تطابق معايير البحث
                                </div>
                            ) : (
                                filteredTemplates.map((t) => {
                                    const isSelected = t.key === selectedKey;
                                    return (
                                        <button
                                            key={t.key}
                                            onClick={() => handleSelectTemplate(t.key)}
                                            className={`w-full text-start p-4 transition-all flex flex-col gap-1.5 relative ${
                                                isSelected
                                                    ? 'bg-slate-50/90 border-r-4 border-slate-900 shadow-inner'
                                                    : 'hover:bg-slate-50/50'
                                            }`}
                                        >
                                            <div className="flex items-center justify-between gap-2">
                                                <span className="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200/80">
                                                    {t.category_label_ar || t.category_label}
                                                </span>
                                                <span className="text-[10px] font-mono text-slate-400 truncate max-w-[140px]">
                                                    {t.blade_view}
                                                </span>
                                            </div>

                                            <h3 className="text-sm font-bold text-slate-900 leading-snug">
                                                {t.name_ar}
                                            </h3>
                                            <p className="text-xs text-slate-500 font-medium">
                                                {t.name}
                                            </p>

                                            <p className="text-[11px] text-slate-400 line-clamp-2 mt-0.5">
                                                {t.description_ar}
                                            </p>

                                            <div className="mt-2 flex items-center justify-between text-[11px] text-slate-400 pt-2 border-t border-slate-100">
                                                <span className="truncate max-w-[220px]">
                                                    {t.subject}
                                                </span>
                                                <ChevronRight className={`h-3.5 w-3.5 transition-transform ${isSelected ? 'text-slate-900 translate-x-1' : 'text-slate-300'}`} />
                                            </div>
                                        </button>
                                    );
                                })
                            )}
                        </div>
                    </div>

                    {/* ════ Right Panel: Preview & Inspection Studio (8 Cols) ════ */}
                    <div className="lg:col-span-8 space-y-4">
                        {currentTemplate && (
                            <div className="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden flex flex-col">
                                {/* Studio Toolbar */}
                                <div className="border-b border-slate-100 p-4 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div className="min-w-0">
                                        <div className="flex items-center gap-2">
                                            <h2 className="text-base font-bold text-slate-900 truncate">
                                                {currentTemplate.name_ar}
                                            </h2>
                                            <span className="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                                {currentTemplate.blade_path}
                                            </span>
                                        </div>
                                        <p className="text-xs text-slate-500 mt-0.5 truncate max-w-xl">
                                            الموضوع الافتراضي: <span className="font-semibold text-slate-700">{currentTemplate.subject}</span>
                                        </p>
                                    </div>

                                    {/* Action buttons & Viewport toggles */}
                                    <div className="flex items-center gap-2 shrink-0 self-end sm:self-auto">
                                        {/* Viewport switcher (Only active on preview tab) */}
                                        {activeTab === 'preview' && (
                                            <div className="flex items-center bg-slate-100 p-0.5 rounded-lg border border-slate-200">
                                                <button
                                                    onClick={() => setViewportMode('desktop')}
                                                    className={`px-2.5 py-1 rounded-md text-xs font-semibold flex items-center gap-1.5 transition-colors ${
                                                        viewportMode === 'desktop'
                                                            ? 'bg-white text-slate-900 shadow-xs'
                                                            : 'text-slate-500 hover:text-slate-900'
                                                    }`}
                                                    title="معاينة شاشات الكمبيوتر"
                                                >
                                                    <Monitor className="h-3.5 w-3.5" />
                                                    Desktop
                                                </button>
                                                <button
                                                    onClick={() => setViewportMode('mobile')}
                                                    className={`px-2.5 py-1 rounded-md text-xs font-semibold flex items-center gap-1.5 transition-colors ${
                                                        viewportMode === 'mobile'
                                                            ? 'bg-white text-slate-900 shadow-xs'
                                                            : 'text-slate-500 hover:text-slate-900'
                                                    }`}
                                                    title="معاينة الهاتف المحمول (375px)"
                                                >
                                                    <Smartphone className="h-3.5 w-3.5" />
                                                    Mobile
                                                </button>
                                            </div>
                                        )}

                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={handleRefreshPreview}
                                            className="h-8 px-2.5 text-xs text-slate-600 border-slate-200 hover:bg-slate-50"
                                            title="إعادة تحميل المعاينة"
                                        >
                                            <RefreshCw className="h-3.5 w-3.5" />
                                        </Button>

                                        <a
                                            href={previewUrl}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex items-center justify-center h-8 px-2.5 text-xs font-medium rounded-lg border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 transition-colors"
                                            title="فتح المعاينة في تبويب مستقل"
                                        >
                                            <ExternalLink className="h-3.5 w-3.5" />
                                        </a>

                                        <Button
                                            size="sm"
                                            onClick={() => setTestModalOpen(true)}
                                            className="h-8 px-3 text-xs bg-slate-900 hover:bg-slate-800 text-white gap-1.5"
                                        >
                                            <Send className="h-3.5 w-3.5" />
                                            إرسال تجريبي
                                        </Button>
                                    </div>
                                </div>

                                {/* Inspector Tabs Navigation */}
                                <div className="border-b border-slate-100 bg-slate-50/70 px-4 flex items-center gap-2">
                                    <button
                                        onClick={() => setActiveTab('preview')}
                                        className={`px-4 py-2.5 text-xs font-bold border-b-2 transition-all flex items-center gap-1.5 ${
                                            activeTab === 'preview'
                                                ? 'border-slate-900 text-slate-900 bg-white'
                                                : 'border-transparent text-slate-500 hover:text-slate-900'
                                        }`}
                                    >
                                        <Eye className="h-3.5 w-3.5" />
                                        المعاينة الحية (Live Viewport)
                                    </button>

                                    <button
                                        onClick={() => setActiveTab('source')}
                                        className={`px-4 py-2.5 text-xs font-bold border-b-2 transition-all flex items-center gap-1.5 ${
                                            activeTab === 'source'
                                                ? 'border-slate-900 text-slate-900 bg-white'
                                                : 'border-transparent text-slate-500 hover:text-slate-900'
                                        }`}
                                    >
                                        <Code className="h-3.5 w-3.5" />
                                        كود الـ Blade ({currentTemplate.blade_view})
                                    </button>

                                    <button
                                        onClick={() => setActiveTab('variables')}
                                        className={`px-4 py-2.5 text-xs font-bold border-b-2 transition-all flex items-center gap-1.5 ${
                                            activeTab === 'variables'
                                                ? 'border-slate-900 text-slate-900 bg-white'
                                                : 'border-transparent text-slate-500 hover:text-slate-900'
                                        }`}
                                    >
                                        <SlidersHorizontal className="h-3.5 w-3.5" />
                                        المتغيرات المحقونة (Sample Data)
                                    </button>
                                </div>

                                {/* Studio Content Area */}
                                <div className="p-4 bg-slate-100/50 min-h-[620px] flex items-center justify-center">
                                    {/* Tab 1: Live Viewport Preview */}
                                    {activeTab === 'preview' && (
                                        <div className="w-full flex flex-col items-center justify-center py-2">
                                            {viewportMode === 'desktop' ? (
                                                /* Desktop Container */
                                                <div className="w-full max-w-3xl rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden transition-all">
                                                    {/* Simulated Browser Address Bar */}
                                                    <div className="bg-slate-100 border-b border-slate-200 px-4 py-2 flex items-center gap-2">
                                                        <div className="flex items-center gap-1.5">
                                                            <div className="w-2.5 h-2.5 rounded-full bg-slate-300" />
                                                            <div className="w-2.5 h-2.5 rounded-full bg-slate-300" />
                                                            <div className="w-2.5 h-2.5 rounded-full bg-slate-300" />
                                                        </div>
                                                        <div className="flex-1 bg-white border border-slate-200 rounded px-2.5 py-0.5 text-[11px] text-slate-500 font-mono truncate">
                                                            {previewUrl}
                                                        </div>
                                                    </div>

                                                    <iframe
                                                        key={`${selectedKey}-${iframeKey}-desktop`}
                                                        src={previewUrl}
                                                        title={currentTemplate.name}
                                                        className="w-full h-[650px] border-none bg-white"
                                                        onLoad={() => setLoadingIframe(false)}
                                                    />
                                                </div>
                                            ) : (
                                                /* Mobile Device Mockup */
                                                <div className="relative w-[375px] rounded-[36px] border-8 border-slate-900 bg-slate-900 shadow-2xl overflow-hidden transition-all">
                                                    {/* Mobile Notch */}
                                                    <div className="bg-slate-900 h-6 w-full flex items-center justify-center">
                                                        <div className="w-24 h-4 bg-black rounded-b-xl" />
                                                    </div>

                                                    <iframe
                                                        key={`${selectedKey}-${iframeKey}-mobile`}
                                                        src={previewUrl}
                                                        title={currentTemplate.name}
                                                        className="w-full h-[640px] border-none bg-white"
                                                        onLoad={() => setLoadingIframe(false)}
                                                    />

                                                    {/* Bottom Indicator */}
                                                    <div className="bg-slate-900 h-5 w-full flex items-center justify-center pb-1">
                                                        <div className="w-28 h-1 bg-slate-600 rounded-full" />
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    {/* Tab 2: Blade Source Code */}
                                    {activeTab === 'source' && (
                                        <div className="w-full bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                                            <div className="border-b border-slate-100 bg-slate-50 px-4 py-2.5 flex items-center justify-between">
                                                <span className="text-xs font-mono text-slate-500">
                                                    {currentTemplate.blade_path}
                                                </span>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={handleCopySource}
                                                    className="h-7 text-xs gap-1 border-slate-200"
                                                >
                                                    {copiedSource ? (
                                                        <>
                                                            <Check className="h-3.5 w-3.5 text-emerald-600" />
                                                            تم النسخ!
                                                        </>
                                                    ) : (
                                                        <>
                                                            <Copy className="h-3.5 w-3.5" />
                                                            نسخ الكود
                                                        </>
                                                    )}
                                                </Button>
                                            </div>

                                            <pre className="p-4 text-xs font-mono bg-slate-900 text-slate-100 overflow-x-auto max-h-[640px] leading-relaxed select-all">
                                                <code>{currentTemplate.blade_source || 'No source content found.'}</code>
                                            </pre>
                                        </div>
                                    )}

                                    {/* Tab 3: Injected Variables */}
                                    {activeTab === 'variables' && (
                                        <div className="w-full bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                                            <div className="border-b border-slate-100 bg-slate-50 px-4 py-2.5 flex items-center justify-between">
                                                <span className="text-xs font-semibold text-slate-700">
                                                    المتغيرات المحقونة لتوليد المعاينة (JSON Payload)
                                                </span>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={handleCopyVariables}
                                                    className="h-7 text-xs gap-1 border-slate-200"
                                                >
                                                    {copiedVars ? (
                                                        <>
                                                            <Check className="h-3.5 w-3.5 text-emerald-600" />
                                                            تم النسخ!
                                                        </>
                                                    ) : (
                                                        <>
                                                            <Copy className="h-3.5 w-3.5" />
                                                            نسخ JSON
                                                        </>
                                                    )}
                                                </Button>
                                            </div>

                                            <pre className="p-4 text-xs font-mono bg-slate-900 text-emerald-400 overflow-x-auto max-h-[640px] leading-relaxed select-all">
                                                <code>{JSON.stringify(currentTemplate.sample_data, null, 2)}</code>
                                            </pre>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* ════ Send Test Email Dialog ════ */}
            <Dialog open={testModalOpen} onOpenChange={setTestModalOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Send className="h-5 w-5 text-slate-900" />
                            إرسال بريد تجريبي لقالب {currentTemplate?.name_ar}
                        </DialogTitle>
                    </DialogHeader>

                    <form onSubmit={handleSendTestEmail} className="space-y-4 py-3">
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 mb-1">
                                البريد الإلكتروني للمستلم *
                            </label>
                            <Input
                                type="email"
                                required
                                placeholder="name@domain.com"
                                value={testRecipient}
                                onChange={(e) => setTestRecipient(e.target.value)}
                                className="w-full text-sm font-mono"
                            />
                            <p className="text-[11px] text-slate-400 mt-1">
                                سيتم إرسال هذا البريد فوراً باستخدام إعدادات الـ SMTP مع وسم [TEST PREVIEW] في العنوان.
                            </p>
                        </div>

                        <div className="rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600 space-y-1">
                            <p className="font-bold text-slate-900">معلومات القالب المرسل:</p>
                            <p><strong className="text-slate-700">القالب:</strong> {currentTemplate?.blade_view}</p>
                            <p><strong className="text-slate-700">العنوان:</strong> [TEST PREVIEW] {currentTemplate?.subject}</p>
                        </div>

                        <DialogFooter className="gap-2 sm:gap-0 pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setTestModalOpen(false)}
                                disabled={sendingTest}
                            >
                                إلغاء
                            </Button>
                            <Button
                                type="submit"
                                disabled={sendingTest}
                                className="bg-slate-900 hover:bg-slate-800 text-white gap-1.5"
                            >
                                <Send className="h-3.5 w-3.5" />
                                {sendingTest ? 'جاري الإرسال...' : 'إرسال البريد التجريبي الآن'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AdminSidebarLayout>
    );
}

import React, { useState, useEffect } from 'react';
import { Bot, Plus, Trash2, Power, Zap, Hash, Type, Regex, MessageSquare, Clock, AlertCircle, Pencil, BarChart3, Vote, X, GitBranch, ArrowRight, ChevronDown, ChevronUp } from 'lucide-react';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';
import { Switch } from '@/Components/ui/switch';

interface AutoReplyRule {
    id: string;
    session_id: string | null;
    name: string;
    trigger_type: 'contains' | 'exact' | 'keyword' | 'regex';
    trigger_value: string;
    response_message: string;
    delay_seconds: number;
    is_active: number;
    priority: number;
    match_count: number;
}

const TRIGGER_TYPES = [
    { value: 'contains', label: 'Contains', labelAr: 'يحتوي على', icon: Type, desc: 'Matches if message contains the keyword anywhere', descAr: 'يتطابق إذا احتوت الرسالة على الكلمة' },
    { value: 'exact', label: 'Exact Match', labelAr: 'تطابق تام', icon: Hash, desc: 'Matches only if message is exactly the keyword', descAr: 'يتطابق فقط إذا كانت الرسالة هي الكلمة بالضبط' },
    { value: 'keyword', label: 'Whole Word', labelAr: 'كلمة كاملة', icon: MessageSquare, desc: 'Matches whole word only (not partial)', descAr: 'يتطابق ككلمة كاملة فقط' },
    { value: 'regex', label: 'Regex', labelAr: 'تعبير منتظم', icon: Regex, desc: 'Advanced pattern matching', descAr: 'مطابقة نمط متقدمة' },
];

export default function AutoReplyWorkspace({ t, locale, callRPC, selectedAccount, sessions, daemonConnected }: any) {
    const isRtl = locale === 'ar';
    const [rules, setRules] = useState<AutoReplyRule[]>([]);
    const [loading, setLoading] = useState(false);
    const [stats, setStats] = useState({ totalRules: 0, activeRules: 0, totalMatches: 0 });
    const [showForm, setShowForm] = useState(false);
    const [editingRule, setEditingRule] = useState<AutoReplyRule | null>(null);

    // Form state
    const [formName, setFormName] = useState('');
    const [formTriggerType, setFormTriggerType] = useState<string>('contains');
    const [formTriggerValue, setFormTriggerValue] = useState('');
    const [formResponse, setFormResponse] = useState('');
    const [formDelay, setFormDelay] = useState(3);
    const [formPriority, setFormPriority] = useState(0);
    const [formResponseType, setFormResponseType] = useState<'text' | 'poll'>('text');
    const [formPollName, setFormPollName] = useState('');
    const [formPollOptions, setFormPollOptions] = useState<string[]>(['', '']);
    const [formPollSelectable, setFormPollSelectable] = useState(1);

    // Flow follow-up state
    const [expandedFlows, setExpandedFlows] = useState<Record<string, boolean>>({});
    const [flowEditing, setFlowEditing] = useState<{parentId: string, option: string} | null>(null);
    const [flowResponseType, setFlowResponseType] = useState<'text' | 'poll'>('text');
    const [flowResponse, setFlowResponse] = useState('');
    const [flowPollName, setFlowPollName] = useState('');
    const [flowPollOptions, setFlowPollOptions] = useState<string[]>(['', '']);
    const [flowPollSelectable, setFlowPollSelectable] = useState(1);
    const [flowDelay, setFlowDelay] = useState(2);

    const fetchRules = async () => {
        if (!daemonConnected) return;
        setLoading(true);
        try {
            const res: any = await callRPC('getAutoReplies', { sessionId: selectedAccount || null });
            setRules(res.rules || []);
            const statsRes: any = await callRPC('getAutoReplyStats', { sessionId: selectedAccount || null });
            setStats(statsRes || { totalRules: 0, activeRules: 0, totalMatches: 0 });
        } catch (err: any) {
            console.error('Failed to fetch auto-replies:', err);
        }
        setLoading(false);
    };

    useEffect(() => {
        if (daemonConnected) fetchRules();
    }, [daemonConnected, selectedAccount]);

    const resetForm = () => {
        setFormName('');
        setFormTriggerType('contains');
        setFormTriggerValue('');
        setFormResponse('');
        setFormDelay(3);
        setFormPriority(0);
        setFormResponseType('text');
        setFormPollName('');
        setFormPollOptions(['', '']);
        setFormPollSelectable(1);
        setEditingRule(null);
        setShowForm(false);
    };

    const handleEdit = (rule: any) => {
        setFormName(rule.name);
        setFormTriggerType(rule.trigger_type);
        setFormTriggerValue(rule.trigger_value);
        setFormResponse(rule.response_message);
        setFormDelay(rule.delay_seconds);
        setFormPriority(rule.priority);
        setFormResponseType(rule.response_type === 'poll' ? 'poll' : 'text');
        setFormPollName(rule.response_poll_name || '');
        try {
            const opts = JSON.parse(rule.response_poll_options || '[]');
            setFormPollOptions(opts.length >= 2 ? opts : ['', '']);
        } catch { setFormPollOptions(['', '']); }
        setFormPollSelectable(rule.response_poll_selectable || 1);
        setEditingRule(rule);
        setShowForm(true);
    };

    const handleSave = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!formTriggerValue.trim()) {
            alert(isRtl ? 'الكلمة المفتاحية مطلوبة' : 'Trigger keyword is required');
            return;
        }
        if (formResponseType === 'text' && !formResponse.trim()) {
            alert(isRtl ? 'رسالة الرد مطلوبة' : 'Response message is required');
            return;
        }
        if (formResponseType === 'poll') {
            const validOpts = formPollOptions.filter(o => o.trim());
            if (!formPollName.trim() || validOpts.length < 2) {
                alert(isRtl ? 'السؤال و خيارين على الأقل مطلوبين' : 'Poll question and at least 2 options required');
                return;
            }
        }
        try {
            await callRPC('saveAutoReply', {
                rule: {
                    id: editingRule?.id || undefined,
                    session_id: selectedAccount || null,
                    name: formName.trim() || (isRtl ? 'قاعدة بدون اسم' : 'Unnamed Rule'),
                    trigger_type: formTriggerType,
                    trigger_value: formTriggerValue.trim(),
                    response_message: formResponseType === 'poll' ? formPollName.trim() : formResponse.trim(),
                    response_type: formResponseType,
                    delay_seconds: formDelay,
                    priority: formPriority,
                    is_active: 1,
                    // Poll fields
                    response_poll_name: formResponseType === 'poll' ? formPollName.trim() : null,
                    response_poll_options: formResponseType === 'poll' ? formPollOptions.filter(o => o.trim()) : null,
                    response_poll_selectable: formResponseType === 'poll' ? formPollSelectable : 1
                }
            });
            resetForm();
            fetchRules();
        } catch (err: any) {
            alert(`Save failed: ${err.message}`);
        }
    };

    const handleDelete = async (id: string) => {
        if (!confirm(isRtl ? 'هل تريد حذف هذه القاعدة؟' : 'Delete this rule?')) return;
        try {
            await callRPC('deleteAutoReply', { id });
            fetchRules();
        } catch (err: any) {
            alert(`Delete failed: ${err.message}`);
        }
    };

    const handleToggle = async (id: string, active: boolean) => {
        try {
            await callRPC('toggleAutoReply', { id, active });
            setRules(prev => prev.map(r => r.id === id ? { ...r, is_active: active ? 1 : 0 } : r));
        } catch (err: any) {
            alert(`Toggle failed: ${err.message}`);
        }
    };

    const triggerTypeInfo = (type: string) => TRIGGER_TYPES.find(t => t.value === type) || TRIGGER_TYPES[0];

    return (
        <div className="space-y-6 animate-in fade-in duration-300">
            {/* Header */}
            <div className="flex items-center justify-between">
                <div className="text-start">
                    <h2 className="text-xl font-bold tracking-tight flex items-center gap-2">
                        <div className="size-9 rounded-xl bg-violet-100/60 dark:bg-violet-950/40 flex items-center justify-center">
                            <Bot className="w-5 h-5 text-violet-600" />
                        </div>
                        {isRtl ? 'الرد التلقائي' : 'Auto-Reply'}
                    </h2>
                    <p className="text-sm text-muted-foreground mt-1">
                        {isRtl ? 'أنشئ ردود تلقائية ذكية على الرسائل الواردة' : 'Create smart auto-replies for incoming messages'}
                    </p>
                </div>
                <Button
                    onClick={() => { resetForm(); setShowForm(true); }}
                    className="bg-violet-600 hover:bg-violet-700 text-white font-bold rounded-xl gap-2"
                >
                    <Plus className="w-4 h-4" />
                    {isRtl ? 'قاعدة جديدة' : 'New Rule'}
                </Button>
            </div>

            {/* Stats Cards */}
            <div className="grid grid-cols-3 gap-4">
                <Card className="rounded-2xl">
                    <CardContent className="p-4 text-center">
                        <div className="text-2xl font-black text-violet-600">{stats.totalRules || 0}</div>
                        <p className="text-[11px] text-muted-foreground font-medium mt-1">
                            {isRtl ? 'إجمالي القواعد' : 'Total Rules'}
                        </p>
                    </CardContent>
                </Card>
                <Card className="rounded-2xl">
                    <CardContent className="p-4 text-center">
                        <div className="text-2xl font-black text-emerald-600">{stats.activeRules || 0}</div>
                        <p className="text-[11px] text-muted-foreground font-medium mt-1">
                            {isRtl ? 'القواعد النشطة' : 'Active Rules'}
                        </p>
                    </CardContent>
                </Card>
                <Card className="rounded-2xl">
                    <CardContent className="p-4 text-center">
                        <div className="text-2xl font-black text-amber-600">{stats.totalMatches || 0}</div>
                        <p className="text-[11px] text-muted-foreground font-medium mt-1">
                            {isRtl ? 'ردود أُرسلت' : 'Replies Sent'}
                        </p>
                    </CardContent>
                </Card>
            </div>

            {/* Add/Edit Form */}
            {showForm && (
                <Card className="rounded-2xl border-violet-200/50 dark:border-violet-800/30 animate-in slide-in-from-top-2 duration-300">
                    <CardHeader className="pb-4 border-b">
                        <CardTitle className="text-base flex items-center gap-2">
                            <Zap className="w-4 h-4 text-violet-600" />
                            {editingRule
                                ? (isRtl ? 'تعديل القاعدة' : 'Edit Rule')
                                : (isRtl ? 'قاعدة جديدة' : 'New Rule')
                            }
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <form onSubmit={handleSave} className="space-y-5">
                            {/* Rule Name */}
                            <div className="space-y-2 text-start">
                                <Label>{isRtl ? 'اسم القاعدة' : 'Rule Name'}</Label>
                                <Input
                                    value={formName}
                                    onChange={e => setFormName(e.target.value)}
                                    placeholder={isRtl ? 'مثال: رد ترحيبي' : 'e.g. Welcome Reply'}
                                    className="rounded-xl text-start"
                                />
                            </div>

                            {/* Trigger Type */}
                            <div className="space-y-2 text-start">
                                <Label>{isRtl ? 'نوع المطابقة' : 'Match Type'}</Label>
                                <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                    {TRIGGER_TYPES.map(tt => (
                                        <button
                                            key={tt.value}
                                            type="button"
                                            onClick={() => setFormTriggerType(tt.value)}
                                            className={`flex flex-col items-center gap-1.5 p-3 rounded-xl border text-xs font-bold transition-all ${
                                                formTriggerType === tt.value
                                                    ? 'border-violet-500 bg-violet-50 text-violet-700 dark:bg-violet-950/30 dark:text-violet-300 dark:border-violet-600'
                                                    : 'border-border hover:border-muted-foreground/30 text-muted-foreground'
                                            }`}
                                        >
                                            <tt.icon className="w-4 h-4" />
                                            {isRtl ? tt.labelAr : tt.label}
                                        </button>
                                    ))}
                                </div>
                                <p className="text-[11px] text-muted-foreground">
                                    {isRtl ? triggerTypeInfo(formTriggerType).descAr : triggerTypeInfo(formTriggerType).desc}
                                </p>
                            </div>

                            {/* Trigger Value */}
                            <div className="space-y-2 text-start">
                                <Label>{isRtl ? 'الكلمة المفتاحية / النمط' : 'Keyword / Pattern'}</Label>
                                <Input
                                    value={formTriggerValue}
                                    onChange={e => setFormTriggerValue(e.target.value)}
                                    placeholder={isRtl ? 'مثال: مرحبا، سعر، أهلا' : 'e.g. hello, price, hi'}
                                    className="rounded-xl text-start font-mono text-sm"
                                    required
                                />
                            </div>

                            {/* Response Type Toggle */}
                            <div className="space-y-2 text-start">
                                <Label>{isRtl ? 'نوع الرد' : 'Response Type'}</Label>
                                <div className="flex gap-2">
                                    <button type="button" onClick={() => setFormResponseType('text')}
                                        className={`flex items-center gap-2 px-4 py-2.5 rounded-xl border text-sm font-bold transition-all ${
                                            formResponseType === 'text'
                                                ? 'border-violet-500 bg-violet-50 text-violet-700 dark:bg-violet-950/30 dark:text-violet-300'
                                                : 'border-border text-muted-foreground hover:border-muted-foreground/30'
                                        }`}>
                                        <MessageSquare className="w-4 h-4" />
                                        {isRtl ? 'نص' : 'Text'}
                                    </button>
                                    <button type="button" onClick={() => setFormResponseType('poll')}
                                        className={`flex items-center gap-2 px-4 py-2.5 rounded-xl border text-sm font-bold transition-all ${
                                            formResponseType === 'poll'
                                                ? 'border-pink-500 bg-pink-50 text-pink-700 dark:bg-pink-950/30 dark:text-pink-300'
                                                : 'border-border text-muted-foreground hover:border-muted-foreground/30'
                                        }`}>
                                        <Vote className="w-4 h-4" />
                                        {isRtl ? 'تصويت' : 'Poll'}
                                    </button>
                                </div>
                            </div>

                            {formResponseType === 'text' ? (
                                /* Text Response */
                                <div className="space-y-2 text-start">
                                    <Label>{isRtl ? 'رسالة الرد' : 'Response Message'}</Label>
                                    <Textarea
                                        value={formResponse}
                                        onChange={e => setFormResponse(e.target.value)}
                                        placeholder={isRtl ? 'أهلاً وسهلاً! كيف نقدر نساعدك؟' : 'Hello! How can we help you?'}
                                        className="h-28 resize-none rounded-xl text-start"
                                    />
                                </div>
                            ) : (
                                /* Poll Response Builder */
                                <div className="space-y-3 p-4 rounded-xl border border-pink-200 dark:border-pink-800 bg-pink-50/50 dark:bg-pink-950/20">
                                    <div className="space-y-2 text-start">
                                        <Label className="flex items-center gap-1.5">
                                            <Vote className="w-3.5 h-3.5 text-pink-500" />
                                            {isRtl ? 'سؤال التصويت' : 'Poll Question'}
                                        </Label>
                                        <Input
                                            value={formPollName}
                                            onChange={e => setFormPollName(e.target.value)}
                                            placeholder={isRtl ? 'ما رأيك في خدمتنا؟' : 'What do you think of our service?'}
                                            className="rounded-xl text-start"
                                        />
                                    </div>
                                    <div className="space-y-2 text-start">
                                        <Label>{isRtl ? 'خيارات التصويت' : 'Poll Options'}</Label>
                                        {formPollOptions.map((opt, idx) => (
                                            <div key={idx} className="flex items-center gap-2">
                                                <span className="text-xs font-bold text-muted-foreground w-5">{idx + 1}</span>
                                                <Input
                                                    value={opt}
                                                    onChange={e => {
                                                        const newOpts = [...formPollOptions];
                                                        newOpts[idx] = e.target.value;
                                                        setFormPollOptions(newOpts);
                                                    }}
                                                    placeholder={isRtl ? `خيار ${idx + 1}` : `Option ${idx + 1}`}
                                                    className="flex-1 rounded-xl text-start text-sm"
                                                />
                                                {formPollOptions.length > 2 && (
                                                    <button type="button" onClick={() => setFormPollOptions(formPollOptions.filter((_, i) => i !== idx))}
                                                        className="p-1.5 rounded-lg text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors">
                                                        <X className="w-3.5 h-3.5" />
                                                    </button>
                                                )}
                                            </div>
                                        ))}
                                        {formPollOptions.length < 12 && (
                                            <button type="button" onClick={() => setFormPollOptions([...formPollOptions, ''])}
                                                className="text-xs font-bold text-pink-600 hover:text-pink-700 dark:text-pink-400">
                                                + {isRtl ? 'إضافة خيار' : 'Add Option'}
                                            </button>
                                        )}
                                    </div>
                                    <div className="space-y-2 text-start">
                                        <Label>{isRtl ? 'عدد الاختيارات المسموح' : 'Selectable Options'}</Label>
                                        <Input
                                            type="number" min={1} max={formPollOptions.length || 2}
                                            value={formPollSelectable}
                                            onChange={e => setFormPollSelectable(parseInt(e.target.value) || 1)}
                                            className="w-24 rounded-xl text-start"
                                        />
                                    </div>
                                </div>
                            )}

                            {/* Delay & Priority */}
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2 text-start">
                                    <Label className="flex items-center gap-1.5">
                                        <Clock className="w-3.5 h-3.5" />
                                        {isRtl ? 'تأخير الرد (ثواني)' : 'Reply Delay (sec)'}
                                    </Label>
                                    <Input
                                        type="number"
                                        min={1}
                                        max={60}
                                        value={formDelay}
                                        onChange={e => setFormDelay(parseInt(e.target.value) || 3)}
                                        className="rounded-xl text-start"
                                    />
                                </div>
                                <div className="space-y-2 text-start">
                                    <Label className="flex items-center gap-1.5">
                                        <BarChart3 className="w-3.5 h-3.5" />
                                        {isRtl ? 'الأولوية' : 'Priority'}
                                    </Label>
                                    <Input
                                        type="number"
                                        min={0}
                                        max={100}
                                        value={formPriority}
                                        onChange={e => setFormPriority(parseInt(e.target.value) || 0)}
                                        className="rounded-xl text-start"
                                    />
                                    <p className="text-[10px] text-muted-foreground">
                                        {isRtl ? 'الأعلى يُفحص أولاً' : 'Higher = checked first'}
                                    </p>
                                </div>
                            </div>

                            {/* Buttons */}
                            <div className="flex gap-3">
                                <Button type="submit" className="flex-1 bg-violet-600 hover:bg-violet-700 text-white font-bold rounded-xl">
                                    {editingRule ? (isRtl ? 'حفظ التعديلات' : 'Save Changes') : (isRtl ? 'إنشاء القاعدة' : 'Create Rule')}
                                </Button>
                                <Button type="button" variant="outline" onClick={resetForm} className="rounded-xl">
                                    {isRtl ? 'إلغاء' : 'Cancel'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            )}

            {/* Rules List */}
            <Card className="rounded-2xl text-start">
                <CardHeader className="pb-4 border-b">
                    <CardTitle className="text-base flex items-center gap-2">
                        <div className="size-8 rounded-xl bg-violet-100/60 dark:bg-violet-950/40 flex items-center justify-center">
                            <Zap className="w-4 h-4 text-violet-600" />
                        </div>
                        {isRtl ? 'القواعد' : 'Rules'} ({rules.length})
                    </CardTitle>
                </CardHeader>
                <CardContent className="pt-6">
                    <div className="space-y-3">
                        {rules.filter((r: any) => !r.parent_rule_id).map(rule => {
                            const tt = triggerTypeInfo(rule.trigger_type);
                            const childRules = rules.filter((r: any) => r.parent_rule_id === rule.id);
                            let pollOptions: string[] = [];
                            try { pollOptions = JSON.parse((rule as any).response_poll_options || '[]'); } catch {}
                            const isFlowExpanded = expandedFlows[rule.id];
                            return (
                                <div
                                    key={rule.id}
                                    className={`p-4 border rounded-2xl transition-all duration-200 hover:shadow-sm ${
                                        rule.is_active
                                            ? 'bg-muted/30 hover:bg-muted/50 border-border'
                                            : 'bg-muted/10 opacity-60 border-dashed'
                                    }`}
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center gap-2 flex-wrap">
                                                <h4 className="font-bold text-sm">{rule.name || (isRtl ? 'بدون اسم' : 'Unnamed')}</h4>
                                                <Badge variant="outline" className="text-[9px] font-bold px-1.5 py-0 h-4 bg-violet-50 text-violet-600 border-violet-200/50 dark:bg-violet-950/30 dark:text-violet-400">
                                                    {isRtl ? tt.labelAr : tt.label}
                                                </Badge>
                                                {rule.match_count > 0 && (
                                                    <Badge variant="secondary" className="text-[9px] font-bold px-1.5 py-0 h-4">
                                                        {rule.match_count} {isRtl ? 'مطابقة' : 'matches'}
                                                    </Badge>
                                                )}
                                                {(rule as any).response_type === 'poll' && (
                                                    <Badge className="text-[9px] font-bold px-1.5 py-0 h-4 bg-pink-50 text-pink-600 border-pink-200/50 dark:bg-pink-950/30 dark:text-pink-400">
                                                        🗳️ {isRtl ? 'تصويت' : 'Poll'}
                                                    </Badge>
                                                )}
                                                {rule.session_id && !sessions?.find((s: any) => s.accountId === rule.session_id) && (
                                                    <Badge variant="destructive" className="text-[9px] font-bold px-1.5 py-0 h-4 flex items-center gap-1 animate-pulse">
                                                        <AlertCircle className="w-2.5 h-2.5" />
                                                        {isRtl ? 'حساب محذوف (يرجى التعديل)' : 'Deleted Account (Please Edit)'}
                                                    </Badge>
                                                )}
                                            </div>
                                            <div className="mt-2 space-y-1">
                                                <p className="text-xs text-muted-foreground flex items-center gap-1.5">
                                                    <span className="font-bold text-foreground">{isRtl ? 'عند:' : 'When:'}</span>
                                                    <code className="bg-muted px-1.5 py-0.5 rounded text-[11px] font-mono">{rule.trigger_value}</code>
                                                </p>
                                                <p className="text-xs text-muted-foreground flex items-start gap-1.5">
                                                    <span className="font-bold text-foreground shrink-0">{isRtl ? 'رد:' : 'Reply:'}</span>
                                                    <span className="line-clamp-2">
                                                        {(rule as any).response_type === 'poll' 
                                                            ? `📊 ${(rule as any).response_poll_name || rule.response_message}`
                                                            : rule.response_message}
                                                    </span>
                                                </p>
                                                <p className="text-[10px] text-muted-foreground/60 flex items-center gap-2 mt-1">
                                                    <Clock className="w-3 h-3" /> {rule.delay_seconds}s delay
                                                    {rule.priority > 0 && <> · Priority: {rule.priority}</>}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2 shrink-0">
                                            <Switch
                                                checked={!!rule.is_active}
                                                onCheckedChange={(checked) => handleToggle(rule.id, checked)}
                                            />
                                            <Button variant="ghost" size="icon" className="h-8 w-8 rounded-lg" onClick={() => handleEdit(rule)}>
                                                <Pencil className="w-3.5 h-3.5" />
                                            </Button>
                                            <Button variant="ghost" size="icon" className="h-8 w-8 rounded-lg text-destructive hover:text-destructive" onClick={() => handleDelete(rule.id)}>
                                                <Trash2 className="w-3.5 h-3.5" />
                                            </Button>
                                        </div>
                                    </div>

                                    {/* ── POLL FLOW: Follow-up per option ── */}
                                    {(rule as any).response_type === 'poll' && pollOptions.length > 0 && (
                                        <div className="mt-3 pt-3 border-t border-dashed">
                                            <button
                                                onClick={() => setExpandedFlows(prev => ({...prev, [rule.id]: !prev[rule.id]}))}
                                                className="flex items-center gap-1.5 text-[11px] font-bold text-violet-600 dark:text-violet-400 hover:text-violet-700 transition-colors w-full"
                                            >
                                                <GitBranch className="w-3.5 h-3.5" />
                                                {isRtl ? 'ردود المتابعة حسب التصويت' : 'Follow-up Flow by Vote'}
                                                {childRules.length > 0 && (
                                                    <Badge className="text-[8px] px-1 py-0 h-3.5 bg-violet-100 text-violet-600 dark:bg-violet-950/40 ms-1">
                                                        {childRules.length}
                                                    </Badge>
                                                )}
                                                {isFlowExpanded ? <ChevronUp className="w-3 h-3 ms-auto" /> : <ChevronDown className="w-3 h-3 ms-auto" />}
                                            </button>

                                            {isFlowExpanded && (
                                                <div className="mt-2 space-y-2">
                                                    {pollOptions.filter(o => o.trim()).map((option, idx) => {
                                                        const child = childRules.find((r: any) => r.trigger_poll_option === option);
                                                        const isEditingThis = flowEditing?.parentId === rule.id && flowEditing?.option === option;
                                                        return (
                                                            <div key={idx} className="ps-3 border-s-2 border-violet-300/50 dark:border-violet-700/50">
                                                                <div className="flex items-center gap-2">
                                                                    <ArrowRight className="w-3 h-3 text-violet-400 shrink-0" />
                                                                    <span className="text-[11px] font-bold text-foreground">"{ option }"</span>
                                                                    {child ? (
                                                                        <div className="flex items-center gap-1.5 flex-1">
                                                                            <Badge className="text-[8px] px-1.5 py-0 h-4 bg-emerald-50 text-emerald-600 border-emerald-200/50 dark:bg-emerald-950/30 dark:text-emerald-400">
                                                                                {(child as any).response_type === 'poll' ? '🗳️' : '💬'} {isRtl ? 'مربوط' : 'Linked'}
                                                                            </Badge>
                                                                            <span className="text-[10px] text-muted-foreground truncate flex-1">
                                                                                {(child as any).response_type === 'poll'
                                                                                    ? `📊 ${(child as any).response_poll_name}`
                                                                                    : child.response_message?.substring(0, 40)}
                                                                            </span>
                                                                            <Button
                                                                                variant="ghost" size="icon"
                                                                                className="h-5 w-5 rounded text-destructive hover:text-destructive"
                                                                                onClick={async () => {
                                                                                    await callRPC('deleteAutoReply', { id: child.id });
                                                                                    fetchRules();
                                                                                }}
                                                                            >
                                                                                <Trash2 className="w-2.5 h-2.5" />
                                                                            </Button>
                                                                        </div>
                                                                    ) : (
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            className="h-5 text-[10px] font-bold text-violet-600 hover:text-violet-700 px-2 gap-1"
                                                                            onClick={() => {
                                                                                setFlowEditing({ parentId: rule.id, option });
                                                                                setFlowResponseType('text');
                                                                                setFlowResponse('');
                                                                                setFlowPollName('');
                                                                                setFlowPollOptions(['', '']);
                                                                                setFlowPollSelectable(1);
                                                                                setFlowDelay(2);
                                                                            }}
                                                                        >
                                                                            <Plus className="w-3 h-3" />
                                                                            {isRtl ? 'أضف متابعة' : 'Add follow-up'}
                                                                        </Button>
                                                                    )}
                                                                </div>

                                                                {/* Inline follow-up editor */}
                                                                {isEditingThis && (
                                                                    <div className="mt-2 p-3 rounded-xl bg-violet-50/50 dark:bg-violet-950/20 border border-violet-200/30 space-y-3">
                                                                        <div className="flex items-center gap-2">
                                                                            <button
                                                                                onClick={() => setFlowResponseType('text')}
                                                                                className={`px-3 py-1 rounded-lg text-[10px] font-bold transition-all ${
                                                                                    flowResponseType === 'text'
                                                                                        ? 'bg-violet-600 text-white shadow-sm'
                                                                                        : 'bg-muted text-muted-foreground hover:bg-muted/80'
                                                                                }`}
                                                                            >
                                                                                💬 {isRtl ? 'نص' : 'Text'}
                                                                            </button>
                                                                            <button
                                                                                onClick={() => setFlowResponseType('poll')}
                                                                                className={`px-3 py-1 rounded-lg text-[10px] font-bold transition-all ${
                                                                                    flowResponseType === 'poll'
                                                                                        ? 'bg-pink-600 text-white shadow-sm'
                                                                                        : 'bg-muted text-muted-foreground hover:bg-muted/80'
                                                                                }`}
                                                                            >
                                                                                🗳️ {isRtl ? 'تصويت' : 'Poll'}
                                                                            </button>
                                                                        </div>

                                                                        {flowResponseType === 'text' ? (
                                                                            <Textarea
                                                                                value={flowResponse}
                                                                                onChange={e => setFlowResponse(e.target.value)}
                                                                                placeholder={isRtl ? 'رسالة المتابعة...' : 'Follow-up message...'}
                                                                                className="text-xs rounded-lg min-h-[60px] text-start"
                                                                            />
                                                                        ) : (
                                                                            <div className="space-y-2">
                                                                                <Input
                                                                                    value={flowPollName}
                                                                                    onChange={e => setFlowPollName(e.target.value)}
                                                                                    placeholder={isRtl ? 'سؤال التصويت...' : 'Poll question...'}
                                                                                    className="text-xs rounded-lg h-8 text-start"
                                                                                />
                                                                                {flowPollOptions.map((opt, i) => (
                                                                                    <div key={i} className="flex items-center gap-1.5">
                                                                                        <span className="text-[10px] text-muted-foreground font-bold w-4">{i+1}.</span>
                                                                                        <Input
                                                                                            value={opt}
                                                                                            onChange={e => {
                                                                                                const c = [...flowPollOptions];
                                                                                                c[i] = e.target.value;
                                                                                                setFlowPollOptions(c);
                                                                                            }}
                                                                                            placeholder={isRtl ? `خيار ${i+1}` : `Option ${i+1}`}
                                                                                            className="text-xs rounded-lg h-7 flex-1 text-start"
                                                                                        />
                                                                                        {flowPollOptions.length > 2 && (
                                                                                            <Button
                                                                                                type="button" variant="ghost" size="icon"
                                                                                                className="h-5 w-5 text-muted-foreground"
                                                                                                onClick={() => setFlowPollOptions(flowPollOptions.filter((_, j) => j !== i))}
                                                                                            >
                                                                                                <X className="w-3 h-3" />
                                                                                            </Button>
                                                                                        )}
                                                                                    </div>
                                                                                ))}
                                                                                {flowPollOptions.length < 8 && (
                                                                                    <Button
                                                                                        type="button" variant="outline" size="sm"
                                                                                        className="h-6 text-[10px] rounded-lg"
                                                                                        onClick={() => setFlowPollOptions([...flowPollOptions, ''])}
                                                                                    >
                                                                                        <Plus className="w-3 h-3 me-1" />
                                                                                        {isRtl ? 'خيار' : 'Option'}
                                                                                    </Button>
                                                                                )}
                                                                            </div>
                                                                        )}

                                                                        <div className="flex items-center gap-2">
                                                                            <Input
                                                                                type="number" min={1} max={30}
                                                                                value={flowDelay}
                                                                                onChange={e => setFlowDelay(parseInt(e.target.value) || 2)}
                                                                                className="w-16 h-7 text-xs rounded-lg text-start"
                                                                            />
                                                                            <span className="text-[10px] text-muted-foreground">{isRtl ? 'ثانية تأخير' : 'sec delay'}</span>
                                                                        </div>

                                                                        <div className="flex gap-2">
                                                                            <Button
                                                                                size="sm"
                                                                                className="h-7 text-[10px] font-bold bg-violet-600 hover:bg-violet-700 text-white rounded-lg flex-1"
                                                                                onClick={async () => {
                                                                                    try {
                                                                                        const flowRule: any = {
                                                                                            session_id: rule.session_id || selectedAccount || null,
                                                                                            name: `${rule.name} → ${option}`,
                                                                                            trigger_type: 'contains',
                                                                                            trigger_value: option,
                                                                                            response_type: flowResponseType,
                                                                                            response_message: flowResponseType === 'poll' ? flowPollName : flowResponse,
                                                                                            delay_seconds: flowDelay,
                                                                                            priority: 0,
                                                                                            is_active: 1,
                                                                                            parent_rule_id: rule.id,
                                                                                            trigger_poll_option: option,
                                                                                        };
                                                                                        if (flowResponseType === 'poll') {
                                                                                            flowRule.response_poll_name = flowPollName;
                                                                                            flowRule.response_poll_options = flowPollOptions.filter(o => o.trim());
                                                                                            flowRule.response_poll_selectable = flowPollSelectable;
                                                                                        }
                                                                                        await callRPC('saveAutoReply', { rule: flowRule });
                                                                                        setFlowEditing(null);
                                                                                        fetchRules();
                                                                                    } catch (err: any) {
                                                                                        alert(`Save failed: ${err.message}`);
                                                                                    }
                                                                                }}
                                                                            >
                                                                                {isRtl ? '💾 حفظ' : '💾 Save'}
                                                                            </Button>
                                                                            <Button
                                                                                size="sm" variant="outline"
                                                                                className="h-7 text-[10px] rounded-lg"
                                                                                onClick={() => setFlowEditing(null)}
                                                                            >
                                                                                {isRtl ? 'إلغاء' : 'Cancel'}
                                                                            </Button>
                                                                        </div>
                                                                    </div>
                                                                )}
                                                            </div>
                                                        );
                                                    })}
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                        {rules.length === 0 && !loading && (
                            <div className="flex flex-col items-center justify-center py-16 text-center">
                                <div className="size-16 rounded-2xl bg-violet-50/50 dark:bg-violet-950/20 flex items-center justify-center mb-4">
                                    <Bot className="w-7 h-7 text-violet-400/50" />
                                </div>
                                <p className="text-sm font-bold">{isRtl ? 'لا توجد قواعد رد تلقائي' : 'No auto-reply rules yet'}</p>
                                <p className="text-xs text-muted-foreground mt-1 max-w-xs">
                                    {isRtl
                                        ? 'أنشئ قاعدة جديدة لتفعيل الردود التلقائية على رسائل عملائك'
                                        : 'Create a new rule to auto-respond to your customer messages'}
                                </p>
                            </div>
                        )}
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}

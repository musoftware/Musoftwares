import React, { useState, useEffect } from 'react';
import { Bot, Plus, Trash2, Power, Zap, Hash, Type, Regex, MessageSquare, Clock, AlertCircle, Pencil, BarChart3 } from 'lucide-react';
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
        setEditingRule(null);
        setShowForm(false);
    };

    const handleEdit = (rule: AutoReplyRule) => {
        setFormName(rule.name);
        setFormTriggerType(rule.trigger_type);
        setFormTriggerValue(rule.trigger_value);
        setFormResponse(rule.response_message);
        setFormDelay(rule.delay_seconds);
        setFormPriority(rule.priority);
        setEditingRule(rule);
        setShowForm(true);
    };

    const handleSave = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!formTriggerValue.trim() || !formResponse.trim()) {
            alert(isRtl ? 'الكلمة المفتاحية والرد مطلوبان' : 'Trigger and response are required');
            return;
        }
        try {
            await callRPC('saveAutoReply', {
                rule: {
                    id: editingRule?.id || undefined,
                    session_id: selectedAccount || null,
                    name: formName.trim() || (isRtl ? 'قاعدة بدون اسم' : 'Unnamed Rule'),
                    trigger_type: formTriggerType,
                    trigger_value: formTriggerValue.trim(),
                    response_message: formResponse.trim(),
                    delay_seconds: formDelay,
                    priority: formPriority,
                    is_active: 1
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

                            {/* Response Message */}
                            <div className="space-y-2 text-start">
                                <Label>{isRtl ? 'رسالة الرد' : 'Response Message'}</Label>
                                <Textarea
                                    value={formResponse}
                                    onChange={e => setFormResponse(e.target.value)}
                                    placeholder={isRtl ? 'أهلاً وسهلاً! كيف نقدر نساعدك؟' : 'Hello! How can we help you?'}
                                    className="h-28 resize-none rounded-xl text-start"
                                    required
                                />
                            </div>

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
                        {rules.map(rule => {
                            const tt = triggerTypeInfo(rule.trigger_type);
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
                                            </div>
                                            <div className="mt-2 space-y-1">
                                                <p className="text-xs text-muted-foreground flex items-center gap-1.5">
                                                    <span className="font-bold text-foreground">{isRtl ? 'عند:' : 'When:'}</span>
                                                    <code className="bg-muted px-1.5 py-0.5 rounded text-[11px] font-mono">{rule.trigger_value}</code>
                                                </p>
                                                <p className="text-xs text-muted-foreground flex items-start gap-1.5">
                                                    <span className="font-bold text-foreground shrink-0">{isRtl ? 'رد:' : 'Reply:'}</span>
                                                    <span className="line-clamp-2">{rule.response_message}</span>
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

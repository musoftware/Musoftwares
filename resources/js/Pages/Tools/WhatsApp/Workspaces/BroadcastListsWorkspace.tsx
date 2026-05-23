import React, { useState, useEffect } from 'react';
import { Radio, Plus, Trash2, Users, Phone, ChevronRight, ChevronLeft, X, Upload, Copy, Check, Send, FolderOpen, Download, Search, Edit2, Loader2, UserPlus } from 'lucide-react';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';

interface BroadcastList {
    id: string;
    name: string;
    description: string;
    member_count: number;
    created_at: string;
}

interface Member {
    id: number;
    phone_number: string;
    name: string;
}

interface ContactFolder {
    id: number;
    name: string;
    icon: string;
    contact_count: number;
}

export default function BroadcastListsWorkspace({ t, locale, callRPC, daemonConnected, onSendCampaign }: any) {
    const isRtl = locale === 'ar';
    const [lists, setLists] = useState<BroadcastList[]>([]);
    const [loading, setLoading] = useState(false);
    const [showForm, setShowForm] = useState(false);
    const [formName, setFormName] = useState('');
    const [formDesc, setFormDesc] = useState('');
    const [editList, setEditList] = useState<BroadcastList | null>(null);

    // Detail view
    const [selectedList, setSelectedList] = useState<BroadcastList | null>(null);
    const [members, setMembers] = useState<Member[]>([]);
    const [addMembersText, setAddMembersText] = useState('');
    const [showAddForm, setShowAddForm] = useState(false);
    const [copiedId, setCopiedId] = useState<string | null>(null);
    const [searchMembers, setSearchMembers] = useState('');

    // Import from contacts
    const [showImportContacts, setShowImportContacts] = useState(false);
    const [folders, setFolders] = useState<ContactFolder[]>([]);
    const [importingFolder, setImportingFolder] = useState<number | null>(null);

    // Broadcast Send
    const [showCompose, setShowCompose] = useState(false);
    const [broadcastMsg, setBroadcastMsg] = useState('');
    const [sessions, setSessions] = useState<any[]>([]);
    const [selectedSession, setSelectedSession] = useState('');
    const [sending, setSending] = useState(false);
    const [sendProgress, setSendProgress] = useState<{ sent: number; failed: number; total: number; percent: number } | null>(null);

    const fetchLists = async () => {
        if (!daemonConnected) return;
        setLoading(true);
        try {
            const res: any = await callRPC('getBroadcastLists', {});
            setLists(res.lists || []);
        } catch (err: any) {
            console.error('Broadcast lists error:', err);
        }
        setLoading(false);
    };

    const fetchMembers = async (listId: string) => {
        try {
            const res: any = await callRPC('getBroadcastMembers', { listId });
            setMembers(res.members || []);
        } catch (err: any) {
            console.error('Members fetch error:', err);
        }
    };

    const fetchFolders = async () => {
        try {
            const res: any = await callRPC('getContactFolders', {});
            setFolders(res.folders || []);
        } catch (_) {}
    };

    const fetchSessions = async () => {
        try {
            const res: any = await callRPC('getSessions', {});
            const all = res.sessions || [];
            const connected = all.filter((s: any) => s.state === 'connected');
            setSessions(connected);
            if (connected.length > 0 && !selectedSession) setSelectedSession(connected[0].accountId);
        } catch (_) {}
    };

    useEffect(() => { if (daemonConnected) { fetchLists(); fetchSessions(); } }, [daemonConnected]);

    const resetForm = () => { setFormName(''); setFormDesc(''); setEditList(null); setShowForm(false); };

    const handleSendBroadcast = async () => {
        if (!selectedList || !selectedSession || !broadcastMsg.trim()) {
            alert(isRtl ? 'اختر حساب واكتب رسالة' : 'Select an account and write a message');
            return;
        }
        if (!confirm(isRtl ? `إرسال لـ ${members.length} عضو؟` : `Send to ${members.length} members?`)) return;
        
        setSending(true);
        setSendProgress({ sent: 0, failed: 0, total: members.length, percent: 0 });
        try {
            const res: any = await callRPC('sendBroadcast', {
                listId: selectedList.id,
                sessionId: selectedSession,
                message: broadcastMsg.trim(),
                mediaType: 'text'
            });
            setSendProgress({ sent: res.sent, failed: res.failed, total: res.total, percent: 100 });
            alert(isRtl 
                ? `✅ تم إرسال ${res.sent} من ${res.total} (${res.failed} فشل)` 
                : `✅ Sent ${res.sent} of ${res.total} (${res.failed} failed)`
            );
        } catch (err: any) {
            alert(`Broadcast failed: ${err.message}`);
        }
        setSending(false);
    };

    const handleSave = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!formName.trim()) return;
        try {
            await callRPC('saveBroadcastList', { id: editList?.id, name: formName.trim(), description: formDesc.trim() });
            resetForm();
            fetchLists();
        } catch (err: any) { alert(`Save failed: ${err.message}`); }
    };

    const handleEdit = (list: BroadcastList, e: React.MouseEvent) => {
        e.stopPropagation();
        setFormName(list.name);
        setFormDesc(list.description || '');
        setEditList(list);
        setShowForm(true);
    };

    const handleDelete = async (id: string, e?: React.MouseEvent) => {
        e?.stopPropagation();
        if (!confirm(isRtl ? 'حذف القائمة وجميع الأعضاء؟' : 'Delete list and all members?')) return;
        try {
            await callRPC('deleteBroadcastList', { id });
            if (selectedList?.id === id) { setSelectedList(null); setMembers([]); }
            fetchLists();
        } catch (err: any) { alert(`Delete failed: ${err.message}`); }
    };

    const handleAddMembers = async () => {
        if (!addMembersText.trim() || !selectedList) return;
        const lines = addMembersText.split('\n').filter(l => l.trim());
        const newMembers = lines.map(line => {
            const parts = line.split(',').map(p => p.trim());
            return { phone_number: parts[0], name: parts[1] || '' };
        }).filter(m => m.phone_number);

        try {
            const res: any = await callRPC('addBroadcastMembers', { listId: selectedList.id, members: newMembers });
            alert(isRtl ? `تمت إضافة ${res.added} عضو (${res.skipped} مكرر)` : `Added ${res.added} members (${res.skipped} skipped)`);
            setAddMembersText('');
            setShowAddForm(false);
            fetchMembers(selectedList.id);
            fetchLists();
        } catch (err: any) { alert(`Add failed: ${err.message}`); }
    };

    const handleImportFromFolder = async (folderId: number) => {
        if (!selectedList) return;
        setImportingFolder(folderId);
        try {
            // Get contacts from folder
            const res: any = await callRPC('getContacts', { folderId, limit: 10000 });
            const contacts = res.contacts || [];
            if (contacts.length === 0) {
                alert(isRtl ? 'المجلد فارغ' : 'Folder is empty');
                setImportingFolder(null);
                return;
            }
            const members = contacts.map((c: any) => ({ phone_number: c.phone_number, name: c.name || '' }));
            const addRes: any = await callRPC('addBroadcastMembers', { listId: selectedList.id, members });
            alert(isRtl ? `تمت إضافة ${addRes.added} عضو من المجلد (${addRes.skipped} مكرر)` : `Added ${addRes.added} from folder (${addRes.skipped} duplicates)`);
            fetchMembers(selectedList.id);
            fetchLists();
            setShowImportContacts(false);
        } catch (err: any) { alert(`Import failed: ${err.message}`); }
        setImportingFolder(null);
    };

    const handleRemoveMember = async (phone: string) => {
        if (!selectedList) return;
        try {
            await callRPC('removeBroadcastMember', { listId: selectedList.id, phone });
            fetchMembers(selectedList.id);
            fetchLists();
        } catch (err: any) { alert(`Remove failed: ${err.message}`); }
    };

    const handleOpenList = (list: BroadcastList) => {
        setSelectedList(list);
        fetchMembers(list.id);
    };

    const handleCopyPhones = () => {
        const phones = members.map(m => m.phone_number).join('\n');
        navigator.clipboard.writeText(phones);
        setCopiedId('copy');
        setTimeout(() => setCopiedId(null), 2000);
    };

    const handleDownloadCSV = () => {
        const csv = ['phone_number,name', ...members.map(m => `${m.phone_number},${m.name}`)].join('\n');
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${selectedList?.name || 'broadcast'}_members.csv`;
        a.click();
        URL.revokeObjectURL(url);
    };

    const filteredMembers = searchMembers
        ? members.filter(m => m.phone_number.includes(searchMembers) || m.name.toLowerCase().includes(searchMembers.toLowerCase()))
        : members;

    // Detail view
    if (selectedList) {
        return (
            <div className="space-y-5 animate-in fade-in duration-300">
                {/* Header */}
                <div className="flex items-center gap-3 flex-wrap">
                    <Button variant="ghost" size="icon" className="h-9 w-9 rounded-xl" onClick={() => { setSelectedList(null); setMembers([]); setSearchMembers(''); }}>
                        {isRtl ? <ChevronRight className="w-5 h-5" /> : <ChevronLeft className="w-5 h-5" />}
                    </Button>
                    <div className="text-start flex-1 min-w-0">
                        <h2 className="text-lg font-bold flex items-center gap-2">
                            <div className="size-8 rounded-lg bg-cyan-100/60 dark:bg-cyan-950/40 flex items-center justify-center">
                                <Radio className="w-4 h-4 text-cyan-600" />
                            </div>
                            <span className="truncate">{selectedList.name}</span>
                            <Badge variant="secondary" className="text-xs font-bold shrink-0">{members.length}</Badge>
                        </h2>
                        {selectedList.description && <p className="text-xs text-muted-foreground mt-0.5">{selectedList.description}</p>}
                    </div>
                    <div className="flex gap-1.5 flex-wrap">
                        <Button variant="outline" size="sm" className="rounded-xl gap-1.5 text-xs font-bold" onClick={handleCopyPhones} disabled={members.length === 0}>
                            {copiedId ? <Check className="w-3 h-3 text-emerald-500" /> : <Copy className="w-3 h-3" />}
                            {copiedId ? (isRtl ? 'تم!' : 'Copied!') : (isRtl ? 'نسخ' : 'Copy')}
                        </Button>
                        <Button variant="outline" size="sm" className="rounded-xl gap-1.5 text-xs font-bold" onClick={handleDownloadCSV} disabled={members.length === 0}>
                            <Download className="w-3 h-3" /> CSV
                        </Button>
                        <Button variant="outline" size="sm" className="rounded-xl gap-1.5 text-xs font-bold" onClick={() => { setShowImportContacts(true); fetchFolders(); }}>
                            <FolderOpen className="w-3 h-3" />
                            {isRtl ? 'استيراد من مجلد' : 'Import from Folder'}
                        </Button>
                        <Button size="sm" onClick={() => setShowAddForm(!showAddForm)} className="bg-cyan-600 hover:bg-cyan-700 text-white font-bold rounded-xl gap-1.5 text-xs">
                            <UserPlus className="w-3.5 h-3.5" />
                            {isRtl ? 'إضافة أعضاء' : 'Add Members'}
                        </Button>
                        {onSendCampaign && (
                            <Button 
                                size="sm" 
                                onClick={() => setShowCompose(!showCompose)} 
                                disabled={members.length === 0}
                                className="bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl gap-1.5 text-xs shadow-md shadow-emerald-600/20"
                            >
                                <Send className="w-3.5 h-3.5" />
                                {isRtl ? `بدء الإرسال المباشر (${members.length})` : `Direct Send (${members.length})`}
                            </Button>
                        )}
                    </div>
                </div>

                {/* ── Compose Broadcast ────────────────────────────── */}
                {showCompose && (
                    <Card className="rounded-2xl border-emerald-200/50 dark:border-emerald-800/30 animate-in slide-in-from-top-2 duration-300 overflow-hidden">
                        <div className="bg-gradient-to-r from-emerald-600 to-teal-600 px-5 py-3">
                            <h3 className="text-white font-bold text-sm flex items-center gap-2">
                                <Send className="w-4 h-4" />
                                {isRtl ? 'إرسال جماعي مباشر' : 'Direct Send'}
                                <Badge className="bg-white/20 text-white text-[10px]">{members.length} {isRtl ? 'عضو' : 'recipients'}</Badge>
                            </h3>
                        </div>
                        <CardContent className="pt-4 space-y-4 text-start">
                            {/* Session Selector */}
                            <div className="space-y-1.5">
                                <Label className="text-xs font-bold">{isRtl ? 'الحساب المرسل' : 'Send From Account'}</Label>
                                {sessions.length === 0 ? (
                                    <p className="text-xs text-destructive">{isRtl ? 'لا يوجد حساب متصل' : 'No connected accounts'}</p>
                                ) : (
                                    <select
                                        value={selectedSession}
                                        onChange={e => setSelectedSession(e.target.value)}
                                        className="w-full rounded-xl border bg-background px-3 py-2 text-xs font-bold"
                                    >
                                        {sessions.map((s: any) => (
                                            <option key={s.accountId} value={s.accountId}>
                                                {s.displayName || s.accountId} {s.phoneNumber ? `(${s.phoneNumber})` : ''}
                                            </option>
                                        ))}
                                    </select>
                                )}
                            </div>

                            {/* Message */}
                            <div className="space-y-1.5">
                                <Label className="text-xs font-bold">{isRtl ? 'الرسالة' : 'Message'}</Label>
                                <Textarea
                                    value={broadcastMsg}
                                    onChange={e => setBroadcastMsg(e.target.value)}
                                    placeholder={isRtl ? 'اكتب رسالتك هنا... (سيتم إرسالها فردياً لكل عميل في القائمة)' : 'Type your message here... (Sent directly to each contact 1-to-1)'}
                                    className="h-28 resize-none rounded-xl text-sm text-start"
                                    disabled={sending}
                                />
                            </div>

                            {/* Progress */}
                            {sendProgress && (
                                <div className="space-y-2">
                                    <div className="flex items-center justify-between text-xs">
                                        <span className="font-bold text-emerald-600">
                                            {sending ? (isRtl ? 'جاري الإرسال...' : 'Sending...') : (isRtl ? 'اكتمل!' : 'Complete!')}
                                        </span>
                                        <span className="text-muted-foreground">
                                            ✅ {sendProgress.sent} {isRtl ? 'نجح' : 'sent'} · ❌ {sendProgress.failed} {isRtl ? 'فشل' : 'failed'} · {sendProgress.total} {isRtl ? 'إجمالي' : 'total'}
                                        </span>
                                    </div>
                                    <div className="h-2 bg-muted rounded-full overflow-hidden">
                                        <div 
                                            className="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full transition-all duration-500"
                                            style={{ width: `${sendProgress.percent}%` }}
                                        />
                                    </div>
                                </div>
                            )}

                            {/* Actions */}
                            <div className="flex gap-2 pt-1">
                                <Button 
                                    onClick={handleSendBroadcast} 
                                    disabled={sending || !broadcastMsg.trim() || sessions.length === 0}
                                    className="bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs gap-1.5 flex-1"
                                >
                                    {sending ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Send className="w-3.5 h-3.5" />}
                                    {sending 
                                        ? (isRtl ? 'جاري الإرسال...' : 'Sending...') 
                                        : (isRtl ? `إرسال لـ ${members.length} عضو` : `Send to ${members.length} members`)
                                    }
                                </Button>
                                <Button variant="outline" onClick={() => { setShowCompose(false); setSendProgress(null); }} className="rounded-xl text-xs" disabled={sending}>
                                    {isRtl ? 'إلغاء' : 'Cancel'}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                )}
                {showImportContacts && (
                    <Card className="rounded-2xl border-emerald-200/50 dark:border-emerald-800/30 animate-in slide-in-from-top-2 duration-300">
                        <CardHeader className="pb-3 border-b">
                            <CardTitle className="text-sm flex items-center gap-2">
                                <FolderOpen className="w-4 h-4 text-emerald-600" />
                                {isRtl ? 'استيراد من مجلدات الكونتاكت' : 'Import from Contact Folders'}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="pt-4">
                            {folders.length === 0 ? (
                                <p className="text-xs text-muted-foreground text-center py-4">
                                    {isRtl ? 'لا توجد مجلدات. استخرج أعضاء من المجموعات أولاً.' : 'No folders found. Extract group members first.'}
                                </p>
                            ) : (
                                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                                    {folders.map(f => (
                                        <button
                                            key={f.id}
                                            onClick={() => handleImportFromFolder(f.id)}
                                            disabled={importingFolder !== null}
                                            className="flex items-center gap-2 px-3 py-2.5 rounded-xl border bg-muted/30 hover:bg-emerald-50 dark:hover:bg-emerald-950/20 border-border hover:border-emerald-300 transition-all text-start text-xs font-bold disabled:opacity-50"
                                        >
                                            {importingFolder === f.id ? <Loader2 className="w-4 h-4 animate-spin text-emerald-600" /> : <span className="text-base">{f.icon}</span>}
                                            <div className="flex-1 min-w-0">
                                                <p className="truncate">{f.name}</p>
                                                <p className="text-[10px] text-muted-foreground font-normal">{f.contact_count} {isRtl ? 'جهة اتصال' : 'contacts'}</p>
                                            </div>
                                        </button>
                                    ))}
                                </div>
                            )}
                            <div className="mt-3 flex justify-end">
                                <Button variant="outline" size="sm" onClick={() => setShowImportContacts(false)} className="rounded-xl text-xs">
                                    {isRtl ? 'إغلاق' : 'Close'}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Add Members Form */}
                {showAddForm && (
                    <Card className="rounded-2xl border-cyan-200/50 dark:border-cyan-800/30 animate-in slide-in-from-top-2 duration-300">
                        <CardContent className="pt-5 space-y-3 text-start">
                            <p className="text-xs text-muted-foreground">
                                {isRtl ? 'صيغة: رقم,اسم (سطر لكل عضو)' : 'Format: phone,name (one per line)'}
                            </p>
                            <Textarea
                                value={addMembersText}
                                onChange={e => setAddMembersText(e.target.value)}
                                placeholder="201012345678,Ahmed&#10;201098765432,Sara"
                                className="h-28 resize-none font-mono text-xs rounded-xl text-start"
                            />
                            <div className="flex gap-2">
                                <Button onClick={handleAddMembers} className="bg-cyan-600 hover:bg-cyan-700 text-white font-bold rounded-xl text-xs">
                                    {isRtl ? 'إضافة' : 'Add'}
                                </Button>
                                <Button variant="outline" onClick={() => setShowAddForm(false)} className="rounded-xl text-xs">
                                    {isRtl ? 'إلغاء' : 'Cancel'}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Search */}
                {members.length > 10 && (
                    <div className="relative">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <Input
                            value={searchMembers}
                            onChange={e => setSearchMembers(e.target.value)}
                            placeholder={isRtl ? 'بحث في الأعضاء...' : 'Search members...'}
                            className="pl-10 rounded-xl text-start"
                        />
                    </div>
                )}

                {/* Members Table */}
                <Card className="rounded-2xl text-start overflow-hidden">
                    <div className="overflow-x-auto max-h-[500px] overflow-y-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/30 sticky top-0">
                                <tr className="border-b">
                                    <th className="p-3 text-start font-bold text-xs text-muted-foreground w-10">#</th>
                                    <th className="p-3 text-start font-bold text-xs text-muted-foreground">{isRtl ? 'الهاتف' : 'Phone'}</th>
                                    <th className="p-3 text-start font-bold text-xs text-muted-foreground">{isRtl ? 'الاسم' : 'Name'}</th>
                                    <th className="p-3 text-start font-bold text-xs text-muted-foreground w-16"></th>
                                </tr>
                            </thead>
                            <tbody>
                                {filteredMembers.map((m, i) => (
                                    <tr key={m.id} className="border-b hover:bg-muted/20 transition-colors">
                                        <td className="p-3 text-xs text-muted-foreground">{i + 1}</td>
                                        <td className="p-3 font-mono text-xs">{m.phone_number}</td>
                                        <td className="p-3 text-xs font-medium">{m.name || <span className="text-muted-foreground">—</span>}</td>
                                        <td className="p-3">
                                            <Button variant="ghost" size="icon" className="h-7 w-7 rounded-lg text-destructive hover:text-destructive" onClick={() => handleRemoveMember(m.phone_number)}>
                                                <Trash2 className="w-3 h-3" />
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    {members.length === 0 && (
                        <div className="py-16 text-center">
                            <div className="size-14 rounded-2xl bg-cyan-50/50 dark:bg-cyan-950/20 flex items-center justify-center mx-auto mb-3">
                                <Users className="w-6 h-6 text-cyan-400/50" />
                            </div>
                            <p className="text-sm font-bold">{isRtl ? 'لا أعضاء بعد' : 'No members yet'}</p>
                            <p className="text-xs text-muted-foreground mt-1">
                                {isRtl ? 'أضف أعضاء يدوياً أو استورد من مجلدات الكونتاكت' : 'Add members manually or import from contact folders'}
                            </p>
                        </div>
                    )}
                    {members.length > 0 && filteredMembers.length === 0 && (
                        <div className="py-8 text-center">
                            <p className="text-xs text-muted-foreground">{isRtl ? 'لا نتائج' : 'No results'}</p>
                        </div>
                    )}
                </Card>
            </div>
        );
    }

    // Lists view
    return (
        <div className="space-y-6 animate-in fade-in duration-300">
            <div className="flex items-center justify-between flex-wrap gap-3">
                <div className="text-start">
                    <h2 className="text-xl font-bold tracking-tight flex items-center gap-2">
                        <div className="size-9 rounded-xl bg-cyan-100/60 dark:bg-cyan-950/40 flex items-center justify-center">
                            <Radio className="w-5 h-5 text-cyan-600" />
                        </div>
                        {isRtl ? 'قوائم الإرسال المباشر' : 'Direct Send Lists'}
                        <Badge variant="secondary" className="text-xs font-bold">{lists.length}</Badge>
                    </h2>
                    <p className="text-sm text-muted-foreground mt-1">
                        {isRtl ? 'أنشئ وأدر قوائم إرسال مباشر لحملاتك' : 'Create and manage direct send lists for your campaigns'}
                    </p>
                </div>
                <Button onClick={() => { resetForm(); setShowForm(true); }} className="bg-cyan-600 hover:bg-cyan-700 text-white font-bold rounded-xl gap-2">
                    <Plus className="w-4 h-4" />
                    {isRtl ? 'قائمة جديدة' : 'New List'}
                </Button>
            </div>

            {showForm && (
                <Card className="rounded-2xl border-cyan-200/50 dark:border-cyan-800/30 animate-in slide-in-from-top-2 duration-300">
                    <CardContent className="pt-5">
                        <form onSubmit={handleSave} className="space-y-4 text-start">
                            <div className="space-y-2">
                                <Label>{isRtl ? 'اسم القائمة' : 'List Name'}</Label>
                                <Input value={formName} onChange={e => setFormName(e.target.value)} placeholder={isRtl ? 'عملاء VIP' : 'VIP Customers'} className="rounded-xl text-start" required />
                            </div>
                            <div className="space-y-2">
                                <Label>{isRtl ? 'الوصف' : 'Description'}</Label>
                                <Input value={formDesc} onChange={e => setFormDesc(e.target.value)} placeholder={isRtl ? 'اختياري' : 'Optional'} className="rounded-xl text-start" />
                            </div>
                            <div className="flex gap-2">
                                <Button type="submit" className="bg-cyan-600 hover:bg-cyan-700 text-white font-bold rounded-xl text-xs flex-1">
                                    {editList ? (isRtl ? 'حفظ' : 'Save') : (isRtl ? 'إنشاء' : 'Create')}
                                </Button>
                                <Button type="button" variant="outline" onClick={resetForm} className="rounded-xl text-xs">{isRtl ? 'إلغاء' : 'Cancel'}</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            )}

            {/* Lists Grid */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                {lists.map(list => (
                    <Card key={list.id} className="rounded-2xl hover:shadow-md transition-all duration-200 cursor-pointer group border-border/50 hover:border-cyan-200 dark:hover:border-cyan-800" onClick={() => handleOpenList(list)}>
                        <CardContent className="p-5 text-start">
                            <div className="flex items-start justify-between gap-2">
                                <div className="flex-1 min-w-0">
                                    <h3 className="font-bold text-sm truncate group-hover:text-cyan-600 transition-colors">{list.name}</h3>
                                    {list.description && <p className="text-[11px] text-muted-foreground mt-0.5 truncate">{list.description}</p>}
                                </div>
                                <div className="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                                    <Button variant="ghost" size="icon" className="h-7 w-7 rounded-lg" onClick={e => handleEdit(list, e)}>
                                        <Edit2 className="w-3 h-3" />
                                    </Button>
                                    <Button variant="ghost" size="icon" className="h-7 w-7 rounded-lg text-destructive" onClick={e => handleDelete(list.id, e)}>
                                        <Trash2 className="w-3 h-3" />
                                    </Button>
                                </div>
                            </div>
                            <div className="flex items-center gap-3 mt-4">
                                <div className="flex items-center gap-1.5 bg-muted/40 px-2.5 py-1 rounded-lg">
                                    <Users className="w-3.5 h-3.5 text-muted-foreground" />
                                    <span className="text-xs font-bold">{list.member_count}</span>
                                    <span className="text-[10px] text-muted-foreground">{isRtl ? 'عضو' : 'members'}</span>
                                </div>
                                <ChevronRight className={`w-4 h-4 text-muted-foreground/50 ml-auto group-hover:text-cyan-600 transition-colors ${isRtl ? 'rotate-180' : ''}`} />
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>

            {lists.length === 0 && !loading && (
                <Card className="rounded-2xl">
                    <div className="flex flex-col items-center justify-center py-16 text-center">
                        <div className="size-16 rounded-2xl bg-cyan-50/50 dark:bg-cyan-950/20 flex items-center justify-center mb-4">
                            <Radio className="w-7 h-7 text-cyan-400/50" />
                        </div>
                        <p className="text-sm font-bold">{isRtl ? 'لا توجد قوائم إرسال مباشر بعد' : 'No direct send lists yet'}</p>
                        <p className="text-xs text-muted-foreground mt-1 max-w-xs">
                            {isRtl ? 'أنشئ قوائم لتنظيم جهات اتصالك وبدء الإرسال المباشر السريع' : 'Create lists to organize contacts for quick direct sending'}
                        </p>
                        <Button onClick={() => { resetForm(); setShowForm(true); }} className="mt-4 bg-cyan-600 hover:bg-cyan-700 text-white font-bold rounded-xl gap-2 text-xs">
                            <Plus className="w-3.5 h-3.5" />
                            {isRtl ? 'قائمة جديدة' : 'Create Your First List'}
                        </Button>
                    </div>
                </Card>
            )}
        </div>
    );
}

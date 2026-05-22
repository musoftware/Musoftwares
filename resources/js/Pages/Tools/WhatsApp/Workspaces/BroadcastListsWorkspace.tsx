import React, { useState, useEffect } from 'react';
import { Radio, Plus, Trash2, Users, Phone, ChevronRight, ChevronLeft, X, Upload, Copy, Check } from 'lucide-react';
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

export default function BroadcastListsWorkspace({ t, locale, callRPC, daemonConnected }: any) {
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

    useEffect(() => { if (daemonConnected) fetchLists(); }, [daemonConnected]);

    const resetForm = () => { setFormName(''); setFormDesc(''); setEditList(null); setShowForm(false); };

    const handleSave = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!formName.trim()) return;
        try {
            await callRPC('saveBroadcastList', { id: editList?.id, name: formName.trim(), description: formDesc.trim() });
            resetForm();
            fetchLists();
        } catch (err: any) { alert(`Save failed: ${err.message}`); }
    };

    const handleDelete = async (id: string) => {
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
            alert(isRtl ? `تمت إضافة ${res.added} عضو` : `Added ${res.added} members`);
            setAddMembersText('');
            setShowAddForm(false);
            fetchMembers(selectedList.id);
            fetchLists();
        } catch (err: any) { alert(`Add failed: ${err.message}`); }
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

    // Detail view
    if (selectedList) {
        return (
            <div className="space-y-6 animate-in fade-in duration-300">
                <div className="flex items-center gap-3">
                    <Button variant="ghost" size="icon" className="h-9 w-9 rounded-xl" onClick={() => { setSelectedList(null); setMembers([]); }}>
                        {isRtl ? <ChevronRight className="w-5 h-5" /> : <ChevronLeft className="w-5 h-5" />}
                    </Button>
                    <div className="text-start flex-1">
                        <h2 className="text-lg font-bold flex items-center gap-2">
                            <Radio className="w-5 h-5 text-cyan-600" />
                            {selectedList.name}
                            <Badge variant="secondary" className="text-xs font-bold">{members.length}</Badge>
                        </h2>
                        {selectedList.description && <p className="text-xs text-muted-foreground">{selectedList.description}</p>}
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" className="rounded-xl gap-1.5 text-xs font-bold" onClick={handleCopyPhones}>
                            {copiedId ? <Check className="w-3 h-3" /> : <Copy className="w-3 h-3" />}
                            {isRtl ? 'نسخ الأرقام' : 'Copy Phones'}
                        </Button>
                        <Button size="sm" onClick={() => setShowAddForm(!showAddForm)} className="bg-cyan-600 hover:bg-cyan-700 text-white font-bold rounded-xl gap-1.5 text-xs">
                            <Plus className="w-3.5 h-3.5" />
                            {isRtl ? 'إضافة أعضاء' : 'Add Members'}
                        </Button>
                    </div>
                </div>

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

                {/* Members Table */}
                <Card className="rounded-2xl text-start overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/30">
                                    <th className="p-3 text-start font-bold text-xs text-muted-foreground w-10">#</th>
                                    <th className="p-3 text-start font-bold text-xs text-muted-foreground">{isRtl ? 'الهاتف' : 'Phone'}</th>
                                    <th className="p-3 text-start font-bold text-xs text-muted-foreground">{isRtl ? 'الاسم' : 'Name'}</th>
                                    <th className="p-3 text-start font-bold text-xs text-muted-foreground w-16"></th>
                                </tr>
                            </thead>
                            <tbody>
                                {members.map((m, i) => (
                                    <tr key={m.id} className="border-b hover:bg-muted/20 transition-colors">
                                        <td className="p-3 text-xs text-muted-foreground">{i + 1}</td>
                                        <td className="p-3 font-mono text-xs">{m.phone_number}</td>
                                        <td className="p-3 text-xs font-medium">{m.name || '—'}</td>
                                        <td className="p-3">
                                            <Button variant="ghost" size="icon" className="h-7 w-7 rounded-lg text-destructive" onClick={() => handleRemoveMember(m.phone_number)}>
                                                <Trash2 className="w-3 h-3" />
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    {members.length === 0 && (
                        <div className="py-12 text-center">
                            <Users className="w-8 h-8 text-muted-foreground/30 mx-auto mb-2" />
                            <p className="text-xs text-muted-foreground">{isRtl ? 'لا أعضاء بعد' : 'No members yet'}</p>
                        </div>
                    )}
                </Card>
            </div>
        );
    }

    // Lists view
    return (
        <div className="space-y-6 animate-in fade-in duration-300">
            <div className="flex items-center justify-between">
                <div className="text-start">
                    <h2 className="text-xl font-bold tracking-tight flex items-center gap-2">
                        <div className="size-9 rounded-xl bg-cyan-100/60 dark:bg-cyan-950/40 flex items-center justify-center">
                            <Radio className="w-5 h-5 text-cyan-600" />
                        </div>
                        {isRtl ? 'قوائم البث' : 'Broadcast Lists'}
                        <Badge variant="secondary" className="text-xs font-bold">{lists.length}</Badge>
                    </h2>
                    <p className="text-sm text-muted-foreground mt-1">
                        {isRtl ? 'أنشئ وأدر قوائم بث لحملاتك' : 'Create and manage broadcast lists for your campaigns'}
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
                    <Card key={list.id} className="rounded-2xl hover:shadow-md transition-all duration-200 cursor-pointer group" onClick={() => handleOpenList(list)}>
                        <CardContent className="p-5 text-start">
                            <div className="flex items-start justify-between">
                                <div className="flex-1 min-w-0">
                                    <h3 className="font-bold text-sm truncate group-hover:text-cyan-600 transition-colors">{list.name}</h3>
                                    {list.description && <p className="text-[11px] text-muted-foreground mt-0.5 truncate">{list.description}</p>}
                                </div>
                                <Button variant="ghost" size="icon" className="h-7 w-7 rounded-lg text-destructive opacity-0 group-hover:opacity-100 transition-opacity" onClick={e => { e.stopPropagation(); handleDelete(list.id); }}>
                                    <Trash2 className="w-3 h-3" />
                                </Button>
                            </div>
                            <div className="flex items-center gap-3 mt-4">
                                <div className="flex items-center gap-1.5">
                                    <Users className="w-3.5 h-3.5 text-muted-foreground" />
                                    <span className="text-xs font-bold">{list.member_count}</span>
                                    <span className="text-[10px] text-muted-foreground">{isRtl ? 'عضو' : 'members'}</span>
                                </div>
                                <ChevronRight className="w-4 h-4 text-muted-foreground/50 ml-auto group-hover:text-cyan-600 transition-colors" />
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
                        <p className="text-sm font-bold">{isRtl ? 'لا توجد قوائم بث' : 'No broadcast lists yet'}</p>
                        <p className="text-xs text-muted-foreground mt-1">
                            {isRtl ? 'أنشئ قوائم بث لتنظيم جهات اتصالك وإرسال حملات سريعة' : 'Create broadcast lists to organize contacts for quick campaigns'}
                        </p>
                    </div>
                </Card>
            )}
        </div>
    );
}

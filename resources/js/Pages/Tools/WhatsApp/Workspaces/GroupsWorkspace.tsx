import React, { useState, useEffect } from 'react';
import { Users, Plus, UserPlus, RefreshCw, AlertCircle, Download, Copy, Check, Search, X, Shield, ShieldCheck, BookUser, Loader2 } from 'lucide-react';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';
import { __ } from '@/lib/i18n';

export default function GroupsWorkspace({ t, locale, callRPC, selectedAccount, sessions, daemonConnected }: any) {
    const isRtl = locale === 'ar';

    // ── Groups State ─────────────────────────────────────────────────────
    const [groups, setGroups] = useState<any[]>([]);
    const [loadingGroups, setLoadingGroups] = useState(false);
    const [newGroupName, setNewGroupName] = useState('');
    const [newGroupParticipants, setNewGroupParticipants] = useState('');
    const [selectedGroupId, setSelectedGroupId] = useState('');
    const [newMembers, setNewMembers] = useState('');

    // ── Member Extraction State ──────────────────────────────────────────
    const [extractingGroup, setExtractingGroup] = useState<any>(null);
    const [members, setMembers] = useState<any[]>([]);
    const [loadingMembers, setLoadingMembers] = useState(false);
    const [memberSearch, setMemberSearch] = useState('');
    const [copied, setCopied] = useState(false);
    const [filterAdmin, setFilterAdmin] = useState<'all' | 'admin' | 'member'>('all');
    const [unresolvedCount, setUnresolvedCount] = useState(0);
    const [saving, setSaving] = useState(false);
    const [saved, setSaved] = useState(false);

    // ── Fetch Groups ─────────────────────────────────────────────────────
    const fetchGroups = async () => {
        if (!selectedAccount || !daemonConnected) return;
        setLoadingGroups(true);
        try {
            const res: any = await callRPC('listGroups', { accountId: selectedAccount });
            setGroups(res.groups || []);
        } catch (err: any) {
            alert(`Failed to fetch groups: ${err.message}`);
        }
        setLoadingGroups(false);
    };

    // ── Extract Members ──────────────────────────────────────────────────
    const extractMembers = async (group: any) => {
        setExtractingGroup(group);
        setLoadingMembers(true);
        setMembers([]);
        setMemberSearch('');
        setFilterAdmin('all');
        try {
            const res: any = await callRPC('getGroupMembers', {
                accountId: selectedAccount,
                groupId: group.id
            });
            setMembers(res.members || []);
            setUnresolvedCount(res.unresolvedCount || 0);
        } catch (err: any) {
            alert(`Failed to extract members: ${err.message}`);
        }
        setLoadingMembers(false);
    };

    // ── Copy to Clipboard ────────────────────────────────────────────────
    const copyMembers = (format: 'phones' | 'csv') => {
        const filtered = getFilteredMembers();
        let text = '';
        if (format === 'phones') {
            text = filtered.map(m => m.phone).join('\n');
        } else {
            text = 'Phone,Name,Role\n' + filtered.map(m =>
                `${m.phone},${m.displayName || ''},${m.admin || 'member'}`
            ).join('\n');
        }
        navigator.clipboard.writeText(text);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    // ── Download as file ─────────────────────────────────────────────────
    const downloadMembers = (format: 'txt' | 'csv') => {
        const filtered = getFilteredMembers();
        let content = '';
        let filename = '';
        const groupSlug = (extractingGroup?.name || 'group').replace(/[^a-zA-Z0-9\u0600-\u06FF]/g, '_').substring(0, 30);

        if (format === 'txt') {
            content = filtered.map(m => m.phone).join('\n');
            filename = `${groupSlug}_members_${filtered.length}.txt`;
        } else {
            content = 'Phone,Name,Role\n' + filtered.map(m =>
                `${m.phone},${m.displayName || ''},${m.admin || 'member'}`
            ).join('\n');
            filename = `${groupSlug}_members_${filtered.length}.csv`;
        }

        const blob = new Blob([content], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        URL.revokeObjectURL(url);
    };

    // ── Save to Contacts ─────────────────────────────────────────────────
    const saveToContacts = async () => {
        const filtered = getFilteredMembers();
        if (filtered.length === 0) return;
        
        const groupName = extractingGroup?.name || 'Group';
        setSaving(true);
        try {
            const contacts = filtered.map(m => ({
                phone_number: m.phone,
                name: m.displayName || '',
                company: '',
                tags: '',
                notes: `Extracted from WhatsApp group: ${groupName}`
            }));
            const res: any = await callRPC('importContactsToFolder', { 
                contacts, 
                folderName: groupName,
                source: `group:${groupName}` 
            });
            setSaved(true);
            setTimeout(() => setSaved(false), 3000);
            alert(isRtl 
                ? `✅ تم حفظ ${res.imported} جهة اتصال في مجلد "${groupName}" (${res.skipped} مكررة)` 
                : `✅ Saved ${res.imported} contacts to folder "${groupName}" (${res.skipped} duplicates skipped)`
            );
        } catch (err: any) {
            alert(`Save failed: ${err.message}`);
        }
        setSaving(false);
    };

    // ── Filter Members ───────────────────────────────────────────────────
    const getFilteredMembers = () => {
        return members.filter(m => {
            const matchSearch = !memberSearch ||
                m.phone.includes(memberSearch) ||
                (m.displayName || '').toLowerCase().includes(memberSearch.toLowerCase());
            const matchRole = filterAdmin === 'all' ||
                (filterAdmin === 'admin' && (m.admin === 'admin' || m.admin === 'superadmin')) ||
                (filterAdmin === 'member' && !m.admin);
            return matchSearch && matchRole;
        });
    };

    // ── Create Group ─────────────────────────────────────────────────────
    const handleCreateGroup = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedAccount) return alert(t.groups.selectAccountError);

        const participants = newGroupParticipants.split(/[\n,]+/).map(p => p.trim()).filter(Boolean);
        if (!newGroupName || participants.length === 0) return alert(t.groups.nameAndParticipantError);

        try {
            await callRPC('createGroup', {
                accountId: selectedAccount,
                group_name: newGroupName,
                participants
            });
            alert(t.groups.createSuccess);
            setNewGroupName('');
            setNewGroupParticipants('');
            fetchGroups();
        } catch (err: any) {
            alert(`Create Group Failed: ${err.message}`);
        }
    };

    // ── Add Members ──────────────────────────────────────────────────────
    const handleAddMembers = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedAccount || !selectedGroupId) return alert(t.groups.selectAccountGroupError);

        const participants = newMembers.split(/[\n,]+/).map(p => p.trim()).filter(Boolean);
        if (participants.length === 0) return alert(t.groups.noParticipantsError);

        try {
            await callRPC('add_group_members', {
                accountId: selectedAccount,
                group_id: selectedGroupId,
                participants
            });
            alert(t.groups.addSuccess);
            setNewMembers('');
            fetchGroups();
        } catch (err: any) {
            alert(`Add Members Failed: ${err.message}`);
        }
    };

    // ── Auto-fetch on mount ──────────────────────────────────────────────
    useEffect(() => {
        if (selectedAccount && daemonConnected) {
            fetchGroups();
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [selectedAccount, daemonConnected]);

    const filteredMembers = getFilteredMembers();

    // ── No account selected state ────────────────────────────────────────
    if (!selectedAccount) {
        return (
            <Card className="border-dashed rounded-2xl">
                <CardContent className="py-16 text-center space-y-4">
                    <AlertCircle className="w-10 h-10 text-amber-400 mx-auto" />
                    <div className="max-w-md mx-auto space-y-1">
                        <h3 className="text-sm font-bold">{t.groups.noAccountTitle}</h3>
                        <p className="text-xs text-muted-foreground">{t.groups.noAccountSub}</p>
                    </div>
                </CardContent>
            </Card>
        );
    }

    return (
        <div className="space-y-6 animate-in fade-in duration-300">
            {/* ── Header ─────────────────────────────────────────────────── */}
            <div className="flex items-center justify-between">
                <div className="text-start">
                    <h2 className="text-xl font-bold tracking-tight">
                        {isRtl ? 'المجموعات' : 'Groups'}
                    </h2>
                    <p className="text-sm text-muted-foreground mt-1">
                        {isRtl ? 'إدارة مجموعاتك واستخراج الأعضاء' : 'Manage groups & extract members'}
                    </p>
                </div>
                <Button
                    variant="outline"
                    size="icon"
                    onClick={() => fetchGroups()}
                    disabled={loadingGroups}
                    className="rounded-xl"
                >
                    <RefreshCw className={`w-4 h-4 ${loadingGroups ? 'animate-spin' : ''}`} />
                </Button>
            </div>

            {/* ── Member Extraction Panel ────────────────────────────────── */}
            {extractingGroup && (
                <Card className="rounded-2xl border-2 border-emerald-500/30 bg-gradient-to-br from-emerald-50/50 to-teal-50/50 dark:from-emerald-950/20 dark:to-teal-950/20 animate-in slide-in-from-top-2 duration-300">
                    <CardHeader className="pb-4 border-b border-emerald-200/50 dark:border-emerald-800/30">
                        <div className="flex items-center justify-between">
                            <CardTitle className="text-base flex items-center gap-2">
                                <div className="size-8 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 flex items-center justify-center">
                                    <Download className="w-4 h-4 text-emerald-600" />
                                </div>
                                <div>
                                    <span>{isRtl ? 'استخراج أعضاء: ' : 'Extract Members: '}</span>
                                    <span className="text-emerald-600 font-bold">{extractingGroup.name}</span>
                                </div>
                            </CardTitle>
                            <div className="flex items-center gap-2">
                                {members.length > 0 && (
                                    <Badge variant="secondary" className="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 font-bold">
                                        {filteredMembers.length} / {members.length} {isRtl ? 'عضو' : 'members'}
                                    </Badge>
                                )}
                                {unresolvedCount > 0 && (
                                    <Badge variant="secondary" className="bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 text-[10px]">
                                        {unresolvedCount} {isRtl ? 'مخفي (LID)' : 'hidden (LID)'}
                                    </Badge>
                                )}
                                <Button variant="ghost" size="icon" onClick={() => { setExtractingGroup(null); setMembers([]); }} className="h-8 w-8 rounded-xl">
                                    <X className="w-4 h-4" />
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="pt-4">
                        {loadingMembers ? (
                            <div className="flex flex-col items-center justify-center py-12 gap-3">
                                <div className="size-10 rounded-full border-2 border-emerald-500 border-t-transparent animate-spin" />
                                <p className="text-sm text-muted-foreground">{isRtl ? 'جاري استخراج الأعضاء...' : 'Extracting members...'}</p>
                            </div>
                        ) : members.length > 0 ? (
                            <div className="space-y-4">
                                {/* Toolbar */}
                                <div className="flex flex-wrap items-center gap-2">
                                    <div className="relative flex-1 min-w-[200px]">
                                        <Search className="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                                        <Input
                                            value={memberSearch}
                                            onChange={e => setMemberSearch(e.target.value)}
                                            placeholder={isRtl ? 'بحث بالرقم أو الاسم...' : 'Search by phone or name...'}
                                            className="ps-10 rounded-xl h-9 text-sm"
                                        />
                                    </div>
                                    <div className="flex items-center gap-1 border rounded-xl p-0.5 bg-muted/30">
                                        {(['all', 'admin', 'member'] as const).map(f => (
                                            <button
                                                key={f}
                                                onClick={() => setFilterAdmin(f)}
                                                className={`px-3 py-1.5 text-xs font-medium rounded-lg transition-all ${filterAdmin === f ? 'bg-background shadow-sm text-foreground' : 'text-muted-foreground hover:text-foreground'}`}
                                            >
                                                {f === 'all' ? (isRtl ? 'الكل' : 'All') : f === 'admin' ? (isRtl ? 'مشرفين' : 'Admins') : (isRtl ? 'أعضاء' : 'Members')}
                                            </button>
                                        ))}
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <Button variant="outline" size="sm" className="rounded-xl h-9 text-xs gap-1.5" onClick={() => copyMembers('phones')}>
                                            {copied ? <Check className="w-3.5 h-3.5 text-emerald-500" /> : <Copy className="w-3.5 h-3.5" />}
                                            {copied ? (isRtl ? 'تم!' : 'Copied!') : (isRtl ? 'نسخ الأرقام' : 'Copy Phones')}
                                        </Button>
                                        <Button variant="outline" size="sm" className="rounded-xl h-9 text-xs gap-1.5" onClick={() => downloadMembers('txt')}>
                                            <Download className="w-3.5 h-3.5" />
                                            {__('general.txt')}</Button>
                                        <Button variant="outline" size="sm" className="rounded-xl h-9 text-xs gap-1.5" onClick={() => downloadMembers('csv')}>
                                            <Download className="w-3.5 h-3.5" />
                                            {__('general.csv')}</Button>
                                        <Button 
                                            size="sm" 
                                            className="rounded-xl h-9 text-xs gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold" 
                                            onClick={saveToContacts}
                                            disabled={saving || filteredMembers.length === 0}
                                        >
                                            {saving ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : saved ? <Check className="w-3.5 h-3.5" /> : <BookUser className="w-3.5 h-3.5" />}
                                            {saving ? (isRtl ? 'جاري الحفظ...' : 'Saving...') : saved ? (isRtl ? 'تم الحفظ!' : 'Saved!') : (isRtl ? 'حفظ في الكونتاكت' : 'Save to Contacts')}
                                        </Button>
                                    </div>
                                </div>

                                {/* Members Table */}
                                <div className="border rounded-xl overflow-hidden">
                                    <div className="max-h-[400px] overflow-y-auto">
                                        <table className="w-full text-sm">
                                            <thead className="bg-muted/50 sticky top-0">
                                                <tr>
                                                    <th className="text-start px-4 py-2.5 text-xs font-semibold text-muted-foreground">#</th>
                                                    <th className="text-start px-4 py-2.5 text-xs font-semibold text-muted-foreground">{isRtl ? 'الرقم' : 'Phone'}</th>
                                                    <th className="text-start px-4 py-2.5 text-xs font-semibold text-muted-foreground">{isRtl ? 'الاسم' : 'Name'}</th>
                                                    <th className="text-start px-4 py-2.5 text-xs font-semibold text-muted-foreground">{isRtl ? 'الدور' : 'Role'}</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y">
                                                {filteredMembers.map((m, i) => (
                                                    <tr key={m.jid} className="hover:bg-muted/30 transition-colors">
                                                        <td className="px-4 py-2 text-xs text-muted-foreground font-mono">{i + 1}</td>
                                                        <td className="px-4 py-2 font-mono text-xs font-medium">{m.phone}</td>
                                                        <td className="px-4 py-2 text-xs">{m.displayName || <span className="text-muted-foreground/50">—</span>}</td>
                                                        <td className="px-4 py-2">
                                                            {m.admin === 'superadmin' ? (
                                                                <Badge className="bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 text-[10px] gap-1 font-medium">
                                                                    <ShieldCheck className="w-3 h-3" />
                                                                    {isRtl ? 'مالك' : 'Owner'}
                                                                </Badge>
                                                            ) : m.admin === 'admin' ? (
                                                                <Badge className="bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 text-[10px] gap-1 font-medium">
                                                                    <Shield className="w-3 h-3" />
                                                                    {isRtl ? 'مشرف' : 'Admin'}
                                                                </Badge>
                                                            ) : (
                                                                <span className="text-xs text-muted-foreground">{isRtl ? 'عضو' : 'Member'}</span>
                                                            )}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        ) : (
                            <div className="text-center py-8 text-sm text-muted-foreground">
                                {isRtl ? 'لم يتم العثور على أعضاء' : 'No members found'}
                            </div>
                        )}
                    </CardContent>
                </Card>
            )}

            {/* ── Groups Content ──────────────────────────────────────────── */}
            <div className="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Create Group */}
                    <Card className="text-start rounded-2xl">
                        <CardHeader className="pb-4 border-b">
                            <CardTitle className="text-base flex items-center gap-2">
                                <div className="size-8 rounded-xl bg-teal-100/60 dark:bg-teal-950/40 flex items-center justify-center">
                                    <Plus className="w-4 h-4 text-teal-600" />
                                </div>
                                {t.groups.createNew}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="pt-6">
                            <form onSubmit={handleCreateGroup} className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="groupName">{t.groups.groupName}</Label>
                                    <Input
                                        id="groupName"
                                        type="text"
                                        value={newGroupName}
                                        onChange={e => setNewGroupName(e.target.value)}
                                        placeholder={t.groups.groupNamePlaceholder}
                                        className="text-start rounded-xl"
                                        required
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="groupParticipants">{t.groups.participants}</Label>
                                    <Textarea
                                        id="groupParticipants"
                                        value={newGroupParticipants}
                                        onChange={e => setNewGroupParticipants(e.target.value)}
                                        placeholder={t.groups.participantsPlaceholder}
                                        className="h-24 resize-none font-mono text-xs text-start rounded-xl"
                                        required
                                    />
                                </div>
                                <Button type="submit" className="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-xl">
                                    {t.groups.createBtn}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    {/* Add Members */}
                    <Card className="text-start rounded-2xl">
                        <CardHeader className="pb-4 border-b">
                            <CardTitle className="text-base flex items-center gap-2">
                                <div className="size-8 rounded-xl bg-indigo-100/60 dark:bg-indigo-950/40 flex items-center justify-center">
                                    <UserPlus className="w-4 h-4 text-indigo-600" />
                                </div>
                                {t.groups.bulkAdd}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="pt-6">
                            <form onSubmit={handleAddMembers} className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="targetGroup">{t.groups.selectGroup}</Label>
                                    <select
                                        id="targetGroup"
                                        value={selectedGroupId}
                                        onChange={e => setSelectedGroupId(e.target.value)}
                                        className="flex h-10 w-full rounded-xl border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 font-medium text-start"
                                        required
                                    >
                                        <option value="" disabled>{t.groups.chooseGroupPlaceholder}</option>
                                        {groups.map(g => (
                                            <option key={g.id} value={g.id}>{g.name} ({g.participantsCount} {isRtl ? 'عضو' : 'members'})</option>
                                        ))}
                                    </select>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="newMembers">{t.groups.newMembers}</Label>
                                    <Textarea
                                        id="newMembers"
                                        value={newMembers}
                                        onChange={e => setNewMembers(e.target.value)}
                                        placeholder={t.groups.newMembersPlaceholder}
                                        className="h-24 resize-none font-mono text-xs text-start rounded-xl"
                                        required
                                    />
                                </div>
                                <Button type="submit" className="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl">
                                    {t.groups.addBtn}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                {/* Groups List */}
                <Card className="text-start rounded-2xl">
                    <CardHeader className="pb-4 border-b">
                        <CardTitle className="text-base flex items-center gap-2">
                            <div className="size-8 rounded-xl bg-teal-100/60 dark:bg-teal-950/40 flex items-center justify-center">
                                <Users className="w-4 h-4 text-teal-600" />
                            </div>
                            {t.groups.yourGroups} ({groups.length})
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            {groups.map(g => (
                                <div key={g.id} className="p-4 border rounded-2xl bg-muted/30 hover:bg-muted/50 transition-all duration-200 text-start group hover:shadow-sm">
                                    <div className="flex items-start gap-3">
                                        <div className="size-10 rounded-xl bg-teal-100/60 dark:bg-teal-950/40 flex items-center justify-center text-teal-600 shrink-0">
                                            <Users className="size-4.5" />
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <h4 className="font-bold text-sm truncate">{g.name}</h4>
                                            <p className="text-xs text-muted-foreground mt-0.5 flex items-center gap-1.5">
                                                <Users className="w-3 h-3" />
                                                {g.participantsCount} {isRtl ? 'عضو' : 'members'}
                                            </p>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => extractMembers(g)}
                                                className="mt-2 h-7 text-xs gap-1.5 px-2.5 rounded-lg border-emerald-200 dark:border-emerald-800 text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 dark:hover:bg-emerald-950/30"
                                            >
                                                <Download className="w-3 h-3" />
                                                {isRtl ? 'استخراج الأعضاء' : 'Extract Members'}
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            ))}
                            {groups.length === 0 && !loadingGroups && (
                                <div className="col-span-full flex flex-col items-center justify-center py-12 text-center">
                                    <div className="size-14 rounded-2xl bg-muted/50 flex items-center justify-center mb-4">
                                        <Users className="w-6 h-6 text-muted-foreground/40" />
                                    </div>
                                    <p className="text-sm font-bold text-foreground">{t.groups.noGroups}</p>
                                    <p className="text-xs text-muted-foreground mt-1">
                                        {isRtl ? 'أنشئ مجموعة جديدة من النموذج أعلاه' : 'Create a new group using the form above'}
                                    </p>
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}

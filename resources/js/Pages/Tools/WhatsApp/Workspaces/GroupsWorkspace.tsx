import React, { useState, useEffect } from 'react';
import { Users, Plus, UserPlus, RefreshCw, AlertCircle } from 'lucide-react';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';

export default function GroupsWorkspace({ t, locale, callRPC, selectedAccount, sessions, daemonConnected }: any) {
    const isRtl = locale === 'ar';

    // ── Groups State ─────────────────────────────────────────────────────
    const [groups, setGroups] = useState<any[]>([]);
    const [loadingGroups, setLoadingGroups] = useState(false);
    const [newGroupName, setNewGroupName] = useState('');
    const [newGroupParticipants, setNewGroupParticipants] = useState('');
    const [selectedGroupId, setSelectedGroupId] = useState('');
    const [newMembers, setNewMembers] = useState('');

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
    }, [selectedAccount, daemonConnected]);

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
                        {isRtl ? 'إدارة مجموعاتك على واتساب' : 'Manage your WhatsApp groups'}
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
                                            <p className="text-[9px] text-muted-foreground/60 mt-1.5 font-mono truncate">{g.id}</p>
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

import React, { useState, useEffect } from 'react';
import { Users, Plus, UserPlus, RefreshCw, AlertCircle } from 'lucide-react';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';

export default function GroupsWorkspace({ t, callRPC, selectedAccount, sessions }: any) {
    const [groups, setGroups] = useState<any[]>([]);
    const [loading, setLoading] = useState(false);
    
    // Create Group State
    const [newGroupName, setNewGroupName] = useState('');
    const [newGroupParticipants, setNewGroupParticipants] = useState('');
    
    // Add Members State
    const [selectedGroupId, setSelectedGroupId] = useState('');
    const [newMembers, setNewMembers] = useState('');

    const fetchGroups = async () => {
        if (!selectedAccount) return;
        setLoading(true);
        try {
            const res: any = await callRPC('listGroups', { accountId: selectedAccount });
            setGroups(res.groups || []);
        } catch (err: any) {
            alert(`Failed to fetch groups: ${err.message}`);
        }
        setLoading(false);
    };

    const handleCreateGroup = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedAccount) return alert('Select an account first');
        
        const participants = newGroupParticipants.split(/[\n,]+/).map(p => p.trim()).filter(Boolean);
        if (!newGroupName || participants.length === 0) return alert('Name and at least 1 participant required');

        try {
            await callRPC('createGroup', {
                accountId: selectedAccount,
                group_name: newGroupName,
                participants
            });
            alert('Group created successfully!');
            setNewGroupName('');
            setNewGroupParticipants('');
            fetchGroups();
        } catch (err: any) {
            alert(`Create Group Failed: ${err.message}`);
        }
    };

    const handleAddMembers = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedAccount || !selectedGroupId) return alert('Select account and group');
        
        const participants = newMembers.split(/[\n,]+/).map(p => p.trim()).filter(Boolean);
        if (participants.length === 0) return alert('No participants provided');

        try {
            await callRPC('add_group_members', {
                accountId: selectedAccount,
                group_id: selectedGroupId,
                participants
            });
            alert('Members added successfully!');
            setNewMembers('');
            fetchGroups();
        } catch (err: any) {
            alert(`Add Members Failed: ${err.message}`);
        }
    };

    useEffect(() => {
        if (selectedAccount) fetchGroups();
    }, [selectedAccount]);

    if (!selectedAccount) {
        return (
            <Card className="border-dashed">
                <CardContent className="py-16 text-center space-y-4">
                    <AlertCircle className="w-10 h-10 text-amber-400 mx-auto" />
                    <div className="max-w-md mx-auto space-y-1">
                        <h3 className="text-sm font-bold">No Account Selected</h3>
                        <p className="text-xs text-muted-foreground">Please connect and select a WhatsApp account from the Accounts tab first.</p>
                    </div>
                </CardContent>
            </Card>
        );
    }

    return (
        <div className="space-y-6 animate-in fade-in duration-300">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-xl font-bold tracking-tight">Groups Management</h2>
                    <p className="text-sm text-muted-foreground mt-1">Manage, create, and add members to groups.</p>
                </div>
                <Button variant="outline" size="icon" onClick={fetchGroups} disabled={loading}>
                    <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
                </Button>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Create Group */}
                <Card>
                    <CardHeader className="pb-4 border-b">
                        <CardTitle className="text-base flex items-center gap-2">
                            <Plus className="w-4.5 h-4.5 text-teal-600" />
                            Create New Group
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <form onSubmit={handleCreateGroup} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="groupName">Group Name</Label>
                                <Input 
                                    id="groupName"
                                    type="text" 
                                    value={newGroupName} 
                                    onChange={e => setNewGroupName(e.target.value)}
                                    placeholder="My Awesome Group" 
                                    required
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="groupParticipants">Initial Participants (Comma/Newline separated)</Label>
                                <Textarea 
                                    id="groupParticipants"
                                    value={newGroupParticipants} 
                                    onChange={e => setNewGroupParticipants(e.target.value)}
                                    placeholder="20101234567, 20109876543" 
                                    className="h-24 resize-none font-mono text-xs" 
                                    required
                                />
                            </div>
                            <Button type="submit" className="w-full bg-teal-600 hover:bg-teal-700 text-white">
                                Create Group
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                {/* Add Members */}
                <Card>
                    <CardHeader className="pb-4 border-b">
                        <CardTitle className="text-base flex items-center gap-2">
                            <UserPlus className="w-4.5 h-4.5 text-teal-600" />
                            Bulk Add Members
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <form onSubmit={handleAddMembers} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="targetGroup">Select Target Group</Label>
                                <select 
                                    id="targetGroup"
                                    value={selectedGroupId} 
                                    onChange={e => setSelectedGroupId(e.target.value)}
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 font-medium"
                                    required
                                >
                                    <option value="" disabled>Choose a group...</option>
                                    {groups.map(g => (
                                        <option key={g.id} value={g.id}>{g.name} ({g.participantsCount} members)</option>
                                    ))}
                                </select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="newMembers">New Members (Comma/Newline separated)</Label>
                                <Textarea 
                                    id="newMembers"
                                    value={newMembers} 
                                    onChange={e => setNewMembers(e.target.value)}
                                    placeholder="20101234567, 20109876543" 
                                    className="h-24 resize-none font-mono text-xs" 
                                    required
                                />
                            </div>
                            <Button type="submit" className="w-full bg-indigo-600 hover:bg-indigo-700 text-white">
                                Add Members
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>

            {/* Groups List */}
            <Card>
                <CardHeader className="pb-4 border-b">
                    <CardTitle className="text-base flex items-center gap-2">
                        <Users className="w-4.5 h-4.5 text-teal-600" />
                        Your Groups ({groups.length})
                    </CardTitle>
                </CardHeader>
                <CardContent className="pt-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {groups.map(g => (
                            <div key={g.id} className="p-4 border rounded-xl bg-muted/50 hover:bg-muted transition-colors">
                                <h4 className="font-bold text-sm mb-1">{g.name}</h4>
                                <p className="text-xs text-muted-foreground">{g.participantsCount} participants</p>
                                <p className="text-[10px] text-muted-foreground/70 mt-2 font-mono truncate">{g.id}</p>
                            </div>
                        ))}
                        {groups.length === 0 && !loading && (
                            <div className="col-span-full py-8 text-center text-muted-foreground text-sm">No groups found for this account.</div>
                        )}
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}

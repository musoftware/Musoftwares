import React, { useState, useEffect } from 'react';
import { Users, Plus, UserPlus, RefreshCw, AlertCircle } from 'lucide-react';

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
            <div className="py-16 text-center border border-dashed border-slate-300 rounded-2xl bg-white space-y-4 px-6">
                <AlertCircle className="w-10 h-10 text-amber-400 mx-auto" />
                <h3 className="text-sm font-bold text-slate-700">No Account Selected</h3>
                <p className="text-xs text-slate-400">Please connect and select a WhatsApp account from the Accounts tab first.</p>
            </div>
        );
    }

    return (
        <div className="space-y-6 animate-in fade-in duration-300">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-xl font-bold tracking-tight text-slate-850">Groups Management</h2>
                    <p className="text-xs text-slate-400 mt-1">Manage, create, and add members to groups.</p>
                </div>
                <button onClick={fetchGroups} disabled={loading} className="p-2 hover:bg-slate-100 border border-slate-200 text-slate-500 rounded-xl transition-all">
                    <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
                </button>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Create Group */}
                <div className="bg-white/60 backdrop-blur-xl border border-white/60 rounded-3xl p-7 shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:bg-white/80 transition-all">
                    <div className="flex items-center gap-2.5 pb-4 border-b border-slate-100 mb-4">
                        <Plus className="w-5 h-5 text-teal-600" />
                        <h3 className="font-bold text-slate-800 text-sm">Create New Group</h3>
                    </div>
                    <form onSubmit={handleCreateGroup} className="space-y-4">
                        <div>
                            <label className="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Group Name</label>
                            <input 
                                type="text" value={newGroupName} onChange={e => setNewGroupName(e.target.value)}
                                placeholder="My Awesome Group" className="w-full text-sm border border-slate-200 focus:border-teal-500 rounded-xl px-4 py-2.5 outline-none" required
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Initial Participants (Comma/Newline separated)</label>
                            <textarea 
                                value={newGroupParticipants} onChange={e => setNewGroupParticipants(e.target.value)}
                                placeholder="20101234567, 20109876543" className="w-full text-sm border border-slate-200 focus:border-teal-500 rounded-xl px-4 py-2.5 outline-none h-24 resize-none" required
                            />
                        </div>
                        <button type="submit" className="w-full py-3.5 bg-gradient-to-r from-teal-500 to-emerald-600 hover:from-teal-400 hover:to-emerald-500 text-white rounded-2xl text-sm font-extrabold transition-all shadow-[0_8px_20px_rgb(20,184,166,0.3)] hover:shadow-[0_12px_25px_rgb(20,184,166,0.4)] active:scale-95">
                            Create Group
                        </button>
                    </form>
                </div>

                {/* Add Members */}
                <div className="bg-white/60 backdrop-blur-xl border border-white/60 rounded-3xl p-7 shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:bg-white/80 transition-all">
                    <div className="flex items-center gap-2.5 pb-4 border-b border-slate-100 mb-4">
                        <UserPlus className="w-5 h-5 text-teal-600" />
                        <h3 className="font-bold text-slate-800 text-sm">Bulk Add Members</h3>
                    </div>
                    <form onSubmit={handleAddMembers} className="space-y-4">
                        <div>
                            <label className="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Select Target Group</label>
                            <select 
                                value={selectedGroupId} onChange={e => setSelectedGroupId(e.target.value)}
                                className="w-full text-sm border border-slate-200 focus:border-teal-500 rounded-xl px-4 py-2.5 outline-none" required
                            >
                                <option value="" disabled>Choose a group...</option>
                                {groups.map(g => (
                                    <option key={g.id} value={g.id}>{g.name} ({g.participantsCount} members)</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">New Members (Comma/Newline separated)</label>
                            <textarea 
                                value={newMembers} onChange={e => setNewMembers(e.target.value)}
                                placeholder="20101234567, 20109876543" className="w-full text-sm border border-slate-200 focus:border-teal-500 rounded-xl px-4 py-2.5 outline-none h-24 resize-none" required
                            />
                        </div>
                        <button type="submit" className="w-full py-3.5 bg-gradient-to-r from-indigo-500 to-blue-600 hover:from-indigo-400 hover:to-blue-500 text-white rounded-2xl text-sm font-extrabold transition-all shadow-[0_8px_20px_rgb(99,102,241,0.3)] hover:shadow-[0_12px_25px_rgb(99,102,241,0.4)] active:scale-95">
                            Add Members
                        </button>
                    </form>
                </div>
            </div>

            {/* Groups List */}
            <div className="bg-white/60 backdrop-blur-xl border border-white/60 rounded-3xl p-7 shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:bg-white/80 transition-all">
                <div className="flex items-center gap-2.5 pb-4 border-b border-slate-100 mb-4">
                    <Users className="w-5 h-5 text-teal-600" />
                    <h3 className="font-bold text-slate-800 text-sm">Your Groups ({groups.length})</h3>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {groups.map(g => (
                        <div key={g.id} className="p-4 border border-slate-100 rounded-xl bg-slate-50 hover:bg-slate-100 transition-colors">
                            <h4 className="font-bold text-slate-800 text-sm mb-1">{g.name}</h4>
                            <p className="text-xs text-slate-500">{g.participantsCount} participants</p>
                            <p className="text-[10px] text-slate-400 mt-2 font-mono truncate">{g.id}</p>
                        </div>
                    ))}
                    {groups.length === 0 && !loading && (
                        <div className="col-span-full py-8 text-center text-slate-500 text-sm">No groups found for this account.</div>
                    )}
                </div>
            </div>
        </div>
    );
}

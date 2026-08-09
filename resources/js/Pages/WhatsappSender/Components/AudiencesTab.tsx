import React, { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import {
    Users,
    UserPlus,
    Upload,
    FileSpreadsheet,
    Trash2,
    Search,
    Filter,
    Plus,
    CheckCircle2
} from 'lucide-react';

interface ContactGroup {
    id: number;
    name: string;
    description: string | null;
    contacts_count: number;
}

interface Props {
    businessId: number;
    contactGroups: ContactGroup[];
}

export default function AudiencesTab({ businessId, contactGroups }: Props) {
    const [searchQuery, setSearchQuery] = useState('');
    const [showCreateModal, setShowCreateModal] = useState(false);

    const groupForm = useForm({
        whatsapp_business_id: businessId,
        name: '',
        description: '',
    });

    const handleCreateGroup = (e: React.FormEvent) => {
        e.preventDefault();
        groupForm.post('/whatsapp-sender/contact-groups', {
            onSuccess: () => {
                setShowCreateModal(false);
                groupForm.reset();
            }
        });
    };

    const filteredGroups = contactGroups.filter(g =>
        g.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        (g.description && g.description.toLowerCase().includes(searchQuery.toLowerCase()))
    );

    return (
        <div className="space-y-6 text-slate-100">
            {/* Top Toolbar */}
            <div className="bg-slate-900/90 border border-slate-800 rounded-2xl p-4 flex flex-col md:flex-row items-center justify-between gap-4 shadow-lg">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-600 to-indigo-600 flex items-center justify-center text-white shadow-lg">
                        <Users className="w-5 h-5" />
                    </div>
                    <div>
                        <h2 className="text-base font-bold text-white">Contacts & Audience Segments</h2>
                        <p className="text-xs text-slate-400">Manage target customer groups, subscriber lists, and CSV imports</p>
                    </div>
                </div>

                <div className="flex items-center gap-3 w-full md:w-auto">
                    <div className="relative flex-1 md:w-64">
                        <Search className="w-4 h-4 absolute left-3 top-3 text-slate-500" />
                        <input
                            type="text"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            placeholder="Search audiences..."
                            className="w-full pl-9 pr-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-sky-500"
                        />
                    </div>

                    <button
                        onClick={() => setShowCreateModal(true)}
                        className="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs rounded-xl flex items-center gap-2 transition-all shadow-md shrink-0"
                    >
                        <Plus className="w-4 h-4" />
                        Create Segment
                    </button>
                </div>
            </div>

            {/* Audiences Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {filteredGroups.length === 0 ? (
                    <div className="col-span-full bg-slate-900/40 border border-slate-800 rounded-2xl p-12 text-center text-slate-500 space-y-2">
                        <Users className="w-10 h-10 text-slate-600 mx-auto" />
                        <h3 className="text-sm font-bold text-slate-300">No Audience Segments Created</h3>
                        <p className="text-xs max-w-sm mx-auto text-slate-500">
                            Create audience segments to send targeted broadcast campaigns to specific customer groups.
                        </p>
                    </div>
                ) : (
                    filteredGroups.map(group => (
                        <div key={group.id} className="bg-slate-900/80 hover:bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4 shadow-md transition-all">
                            <div className="flex items-start justify-between">
                                <div>
                                    <h3 className="text-sm font-bold text-white">{group.name}</h3>
                                    <p className="text-xs text-slate-400 mt-1 line-clamp-2">{group.description || 'No description provided.'}</p>
                                </div>
                                <button
                                    onClick={() => {
                                        if (confirm(`Are you sure you want to delete audience segment "${group.name}"?`)) {
                                            router.delete(`/whatsapp-sender/contact-groups/${group.id}`);
                                        }
                                    }}
                                    className="text-slate-500 hover:text-rose-400 p-1 transition-colors"
                                    title="Delete Segment"
                                >
                                    <Trash2 className="w-4 h-4" />
                                </button>
                            </div>

                            <div className="pt-3 border-t border-slate-800 flex items-center justify-between text-xs">
                                <span className="text-slate-400">Total Contacts:</span>
                                <span className="text-sky-400 font-bold font-mono text-sm">{group.contacts_count}</span>
                            </div>
                        </div>
                    ))
                )}
            </div>

            {/* Create Audience Modal */}
            {showCreateModal && (
                <div className="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div className="bg-slate-900 border border-slate-800 rounded-2xl p-6 max-w-md w-full space-y-4 shadow-2xl">
                        <h3 className="text-base font-bold text-white">Create Audience Segment</h3>

                        <form onSubmit={handleCreateGroup} className="space-y-4">
                            <div>
                                <label className="text-xs font-semibold text-slate-400 block mb-1">Segment Name</label>
                                <input
                                    type="text"
                                    value={groupForm.data.name}
                                    onChange={e => groupForm.setData('name', e.target.value)}
                                    placeholder="e.g. VIP Customers, July Leads..."
                                    required
                                    className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-sky-500"
                                />
                            </div>

                            <div>
                                <label className="text-xs font-semibold text-slate-400 block mb-1">Description (Optional)</label>
                                <textarea
                                    value={groupForm.data.description}
                                    onChange={e => groupForm.setData('description', e.target.value)}
                                    placeholder="Segment criteria or audience details..."
                                    rows={3}
                                    className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-sky-500 resize-none"
                                />
                            </div>

                            <div className="flex items-center justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setShowCreateModal(false)}
                                    className="px-4 py-2 bg-slate-800 text-slate-300 text-xs font-semibold rounded-xl hover:bg-slate-700"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={groupForm.processing}
                                    className="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold rounded-xl shadow-md"
                                >
                                    Create Segment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

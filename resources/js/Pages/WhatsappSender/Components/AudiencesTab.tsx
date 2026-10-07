import { __ } from '@/lib/i18n';
import React, { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import { useConfirm } from '@/hooks/useConfirm';
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
    const { confirm, confirmDialog } = useConfirm();

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
                        <h2 className="text-base font-bold text-white">{__('whatsapp.audiences_title')}</h2>
                        <p className="text-xs text-slate-400">{__('whatsapp.audiences_desc')}</p>
                    </div>
                </div>

                <div className="flex items-center gap-3 w-full md:w-auto">
                    <div className="relative flex-1 md:w-64">
                        <Search className="w-4 h-4 absolute left-3 top-3 text-slate-500" />
                        <input
                            type="text"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            placeholder={__('whatsapp.audiences_search')}
                            aria-label={__('whatsapp.audiences_search')}
                            className="w-full pl-9 pr-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-sky-500"
                        />
                    </div>

                    <button
                        onClick={() => setShowCreateModal(true)}
                        className="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs rounded-xl flex items-center gap-2 transition-all shadow-md shrink-0"
                    >
                        <Plus className="w-4 h-4" />
                        {__('whatsapp.audiences_create')}
                    </button>
                </div>
            </div>

            {/* Audiences Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {filteredGroups.length === 0 ? (
                    <div className="col-span-full bg-slate-900/40 border border-slate-800 rounded-2xl p-12 text-center text-slate-500 space-y-2">
                        <Users className="w-10 h-10 text-slate-600 mx-auto" />
                        <h3 className="text-sm font-bold text-slate-300">{__('whatsapp.audiences_empty')}</h3>
                        <p className="text-xs max-w-sm mx-auto text-slate-500">
                            {__('whatsapp.audiences_empty_desc')}
                        </p>
                    </div>
                ) : (
                    filteredGroups.map(group => (
                        <div key={group.id} className="bg-slate-900/80 hover:bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4 shadow-md transition-all">
                            <div className="flex items-start justify-between">
                                <div>
                                    <h3 className="text-sm font-bold text-white">{group.name}</h3>
                                    <p className="text-xs text-slate-400 mt-1 line-clamp-2">{group.description || __('whatsapp.audiences_no_description')}</p>
                                </div>
                                <button
                                    onClick={async () => {
                                        if (!(await confirm({ title: __('whatsapp.audiences_delete'), description: __('whatsapp.audiences_delete_desc', { name: group.name }), variant: 'danger' }))) return;
                                        router.delete(`/whatsapp-sender/contact-groups/${group.id}`);
                                    }}
                                    className="text-slate-500 hover:text-rose-400 p-1 transition-colors"
                                    title={__('whatsapp.audiences_delete')}
                                    aria-label={__('whatsapp.audiences_delete')}
                                >
                                    <Trash2 className="w-4 h-4" />
                                </button>
                            </div>

                            <div className="pt-3 border-t border-slate-800 flex items-center justify-between text-xs">
                                <span className="text-slate-400">{__('whatsapp.audiences_total_contacts')}</span>
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
                        <h3 className="text-base font-bold text-white">{__('whatsapp.audiences_create_title')}</h3>

                        <form onSubmit={handleCreateGroup} className="space-y-4">
                            <div>
                                <label htmlFor="audience-name" className="text-xs font-semibold text-slate-400 block mb-1">{__('whatsapp.audiences_name')}</label>
                                <input
                                    id="audience-name"
                                    type="text"
                                    value={groupForm.data.name}
                                    onChange={e => groupForm.setData('name', e.target.value)}
                                    placeholder={__('whatsapp.audiences_name_placeholder')}
                                    required
                                    className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-sky-500"
                                />
                            </div>

                            <div>
                                <label htmlFor="audience-description" className="text-xs font-semibold text-slate-400 block mb-1">{__('whatsapp.audiences_description')}</label>
                                <textarea
                                    id="audience-description"
                                    value={groupForm.data.description}
                                    onChange={e => groupForm.setData('description', e.target.value)}
                                    placeholder={__('whatsapp.audiences_description_placeholder')}
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
                                    {__('general.cancel')}
                                </button>
                                <button
                                    type="submit"
                                    disabled={groupForm.processing}
                                    className="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold rounded-xl shadow-md"
                                >
                                    {__('whatsapp.audiences_create')}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
            {confirmDialog}
        </div>
    );
}

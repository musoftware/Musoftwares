import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Store, Search, ChevronRight, Users } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function ResellersCreate() {
    const [form, setForm] = useState({
        user_id: '',
        name: '',
        currency: 'USD',
        notes: '',
    });
    const [userSearch, setUserSearch] = useState('');
    const [userResults, setUserResults] = useState<any[]>([]);
    const [selectedUser, setSelectedUser] = useState<any>(null);
    const [searching, setSearching] = useState(false);
    const [errors, setErrors] = useState<any>({});
    const [submitting, setSubmitting] = useState(false);

    async function searchUsers(q: string) {
        setUserSearch(q);
        if (q.length < 2) { setUserResults([]); return; }
        setSearching(true);
        try {
            const res = await fetch(`/admin/resellers/search-users?q=${encodeURIComponent(q)}`);
            const data = await res.json();
            setUserResults(data);
        } finally {
            setSearching(false);
        }
    }

    function selectUser(u: any) {
        setSelectedUser(u);
        setForm(f => ({ ...f, user_id: String(u.id) }));
        setUserResults([]);
        setUserSearch(u.name);
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        setSubmitting(true);
        router.post('/admin/resellers', form, {
            onError: errs => { setErrors(errs); setSubmitting(false); },
        });
    }

    const menuItems = [
        { id: 'resellers', label: 'Resellers', icon: Store, href: '/admin/resellers', isActive: true },
    ];

    return (
        <WorkspaceLayout title={__('general.new_reseller')} workspaceName="Musoftware Admin" tenantId="SYS-ADMIN" menuItems={menuItems}>
            <Head title={__('general.new_reseller')} />
            <div className="max-w-2xl mx-auto space-y-6">
                <ModulePageHeader
                    title={__('general.create_reseller_account')}
                    description={__('general.designate_a_platform_user_as_a_reseller_they_ll_get_a_unique_portal_url_for_their_sub_users')}
                />

                <OperationalCard title={__('general.reseller_details')}>
                    <form onSubmit={submit} className="space-y-5">
                        {/* User Search */}
                        <div className="space-y-1.5">
                            <Label htmlFor="user_search">{__('general.platform_user')}<span className="text-red-500">*</span></Label>
                            <div className="relative">
                                <Search className="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted" />
                                <Input
                                    id="user_search"
                                    className="ps-9"
                                    placeholder={__('general.search_by_name_or_email_1')}
                                    value={userSearch}
                                    onChange={e => searchUsers(e.target.value)}
                                />
                            </div>
                            {userResults.length > 0 && (
                                <div className="border border-border rounded-lg shadow-sm bg-surface overflow-hidden">
                                    {userResults.map((u: any) => (
                                        <button
                                            key={u.id}
                                            type="button"
                                            onClick={() => selectUser(u)}
                                            className="w-full text-start px-4 py-2.5 hover:bg-surface-raised transition-colors flex items-center gap-3"
                                        >
                                            <div className="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xs">
                                                {u.name[0]?.toUpperCase()}
                                            </div>
                                            <div>
                                                <p className="text-sm font-semibold text-text-primary">{u.name}</p>
                                                <p className="text-xs text-text-muted">{u.email}</p>
                                            </div>
                                        </button>
                                    ))}
                                </div>
                            )}
                            {selectedUser && (
                                <div className="flex items-center gap-2 mt-1 text-xs text-emerald-600">
                                    <Users className="w-3.5 h-3.5" />
                                    Selected: <span className="font-semibold">{selectedUser.name}</span> ({selectedUser.email})
                                </div>
                            )}
                            {errors.user_id && <p className="text-xs text-red-500 mt-1">{errors.user_id}</p>}
                        </div>

                        {/* Reseller Name */}
                        <div className="space-y-1.5">
                            <Label htmlFor="name">{__('general.reseller_company_name')}<span className="text-red-500">*</span></Label>
                            <Input
                                id="name"
                                placeholder={__('general.e_g_acme_digital_services')}
                                value={form.name}
                                onChange={e => setForm(f => ({ ...f, name: e.target.value }))}
                            />
                            {errors.name && <p className="text-xs text-red-500 mt-1">{errors.name}</p>}
                        </div>

                        {/* Currency */}
                        <div className="space-y-1.5">
                            <Label htmlFor="currency">{__('general.currency')}</Label>
                            <select
                                id="currency"
                                value={form.currency}
                                onChange={e => setForm(f => ({ ...f, currency: e.target.value }))}
                                className="flex h-9 w-full rounded-md border border-border bg-surface px-3 py-1 text-sm text-text-primary shadow-sm focus:outline-none focus:ring-1 focus:ring-primary"
                            >
                                <option value="USD">{__('general.usd_us_dollar')}</option>
                                <option value="EGP">{__('general.egp_egyptian_pound')}</option>
                                <option value="EUR">{__('general.eur_euro')}</option>
                                <option value="SAR">{__('general.sar_saudi_riyal')}</option>
                                <option value="AED">{__('general.aed_uae_dirham')}</option>
                            </select>
                        </div>

                        {/* Notes */}
                        <div className="space-y-1.5">
                            <Label htmlFor="notes">{__('general.internal_notes_1')}</Label>
                            <Textarea
                                id="notes"
                                placeholder={__('general.optional_notes_about_this_reseller')}
                                rows={3}
                                value={form.notes}
                                onChange={e => setForm(f => ({ ...f, notes: e.target.value }))}
                            />
                        </div>

                        <div className="flex items-center justify-between pt-2 border-t border-border">
                            <p className="text-xs text-text-muted">{__('general.a_unique_portal_url_will_be_generated_automatically_balance_starts_at_0')}</p>
                            <Button type="submit" disabled={submitting || !form.user_id || !form.name} className="gap-1.5">
                                {submitting ? 'Creating...' : 'Create Reseller'} <ChevronRight className="w-3.5 h-3.5" />
                            </Button>
                        </div>
                    </form>
                </OperationalCard>
            </div>
        </WorkspaceLayout>
    );
}

import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { __ } from '@/lib/i18n';
import { toast } from 'sonner';
import { useConfirm } from '@/hooks/useConfirm';

const ALIAS_KIND_LABEL_KEYS = {
    primary: 'admin.user_email_kind_primary',
    alias: 'admin.user_email_kind_alias',
    candidate: 'admin.user_email_kind_candidate',
};

export default function UserEmails({ user, primary, emails = [], suggestions = [], search = '' }) {
    const { confirm, confirmDialog } = useConfirm();
    const [newEmail, setNewEmail] = useState('');
    const [verified, setVerified] = useState(true);
    const [adding, setAdding] = useState(false);
    const [removingId, setRemovingId] = useState(null);
    const [searchTerm, setSearchTerm] = useState(search);
    const [selectedDuplicates, setSelectedDuplicates] = useState([]);

    const submit = (e) => {
        e.preventDefault();
        if (!newEmail) return;
        setAdding(true);
        router.post(`/admin/users/${user.id}/emails`, {
            email: newEmail.trim(),
            verified_at: verified ? 1 : 0,
        }, {
            onFinish: () => {
                setAdding(false);
                setNewEmail('');
            },
            preserveScroll: true,
        });
    };

    const removeAlias = async (alias) => {
        const accepted = await confirm({
            title: __('general.confirm_remove_alias'),
            description: alias.email,
            variant: 'danger',
            confirmLabel: __('admin.remove'),
        });
        if (!accepted) return;
        setRemovingId(alias.id);
        router.delete(`/admin/users/${user.id}/emails/${alias.id}`, {
            onFinish: () => setRemovingId(null),
            preserveScroll: true,
        });
    };

    const markVerified = (alias) => {
        router.post(`/admin/users/${user.id}/emails/${alias.id}/verify`, {}, {
            preserveScroll: true,
        });
    };

    const makePrimary = async (alias) => {
        const accepted = await confirm({
            title: __('general.confirm_make_primary_alias'),
            description: alias.email,
            confirmLabel: __('general.make_primary'),
        });
        if (!accepted) return;
        router.post(`/admin/users/${user.id}/emails/${alias.id}/make-primary`, {}, {
            preserveScroll: true,
        });
    };

    const searchDuplicates = (e) => {
        e.preventDefault();
        router.get(`/admin/users/${user.id}/emails`, { search: searchTerm }, {
            preserveState: true,
            replace: true,
        });
    };

    const toggleDup = (id) => {
        setSelectedDuplicates((prev) =>
            prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]
        );
    };

    const startMergeFlow = () => {
        if (selectedDuplicates.length === 0) {
            toast.error(__('general.select_at_least_one_duplicate'));
            return;
        }
        const url = `/admin/users/${user.id}/merge?` + selectedDuplicates
            .map((id) => `duplicate_ids[]=${id}`)
            .join('&');
        router.get(url);
    };

    const allKnown = [
        { id: user.id, name: user.name, email: primary.email, kind: 'primary' },
        ...emails.map((e) => ({ ...e, kind: 'alias' })),
        ...suggestions.map((s) => ({ ...s, kind: 'candidate' })),
    ];

    return (
        <AdminSidebarLayout auth={{ user }}>
            <Head title={__('admin.user_email_aliases_title', { id: user.id })} />
            {confirmDialog}
            <div className="p-6 space-y-6 max-w-4xl">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">{__('admin.user_email_aliases_title', { id: user.id })}</h1>
                        <p className="text-sm text-muted-foreground">
                            {__('admin.user_email_aliases_description')}
                        </p>
                    </div>
                    <Button asChild variant="outline">
                        <Link href={`/admin/users/${user.id}`}>{__('general.back_to_user')}</Link>
                    </Button>
                </div>

                <section className="rounded border p-4 bg-white">
                    <h2 className="text-lg font-medium mb-2">{__('admin.primary_email')}</h2>
                    <div className="flex items-center gap-3">
                        <div className="font-mono">{primary.email}</div>
                        <span className="text-xs uppercase tracking-wide text-muted-foreground">
                            {primary.verified_at ? __('general.verified') : __('admin.not_verified')}
                        </span>
                    </div>
                </section>

                <section className="rounded border p-4 bg-white">
                    <h2 className="text-lg font-medium mb-2">{__('admin.add_email_alias')}</h2>
                    <form onSubmit={submit} className="flex flex-wrap items-end gap-3">
                        <div className="grow min-w-[260px]">
                            <Label htmlFor="alias-email">{__('general.email')}</Label>
                            <Input
                                id="alias-email"
                                type="email"
                                value={newEmail}
                                onChange={(e) => setNewEmail(e.target.value)}
                                placeholder="alias@example.com"
                                required
                            />
                        </div>
                        <label className="inline-flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={verified}
                                onChange={(e) => setVerified(e.target.checked)}
                            />
                            {__('admin.mark_verified')}
                        </label>
                        <Button type="submit" disabled={adding || !newEmail}>
                            {adding ? __('general.adding') : __('admin.add_email_alias')}
                        </Button>
                    </form>
                </section>

                <section className="rounded border p-4 bg-white">
                    <div className="flex items-center justify-between mb-2">
                        <h2 className="text-lg font-medium">{__('admin.existing_email_aliases', { count: emails.length })}</h2>
                    </div>
                    {emails.length === 0 ? (
                        <p className="text-sm text-muted-foreground">{__('admin.no_email_aliases')}</p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead className="text-xs uppercase text-muted-foreground text-start">
                                <tr>
                                    <th className="text-start py-2">{__('general.email')}</th>
                                    <th className="text-start py-2">{__('general.status')}</th>
                                    <th className="text-start py-2">{__('admin.source')}</th>
                                    <th className="text-start py-2">{__('admin.added_on')}</th>
                                    <th className="text-end py-2">{__('general.actions')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {emails.map((alias) => (
                                    <tr key={alias.id} className="border-t">
                                        <td className="py-2 font-mono">{alias.email}</td>
                                        <td className="py-2">
                                            {alias.verified ? (
                                                <span className="text-emerald-700 text-xs uppercase">{__('general.verified')}</span>
                                            ) : (
                                                <span className="text-amber-700 text-xs uppercase">{__('admin.pending')}</span>
                                            )}
                                        </td>
                                        <td className="py-2 text-xs uppercase text-muted-foreground">{alias.source}</td>
                                        <td className="py-2 text-xs text-muted-foreground">{alias.created_at?.substring(0, 10)}</td>
                                        <td className="py-2 text-end">
                                            {!alias.verified && (
                                                <Button variant="ghost" size="sm" onClick={() => markVerified(alias)}>
                                                    {__('admin.verify')}
                                                </Button>
                                            )}
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="text-blue-600 dark:text-blue-400"
                                                onClick={() => makePrimary(alias)}
                                            >
                                                {__('general.make_primary')}
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="text-red-600"
                                                disabled={removingId === alias.id}
                                                onClick={() => removeAlias(alias)}
                                            >
                                                {__('admin.remove')}
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </section>

                <section className="rounded border p-4 bg-white">
                    <h2 className="text-lg font-medium mb-2">{__('admin.merge_duplicates_into_account')}</h2>
                    <p className="text-sm text-muted-foreground mb-3">
                        {__('admin.merge_duplicates_into_account_description')}
                    </p>
                    <form onSubmit={searchDuplicates} className="flex gap-2 mb-3">
                        <Input
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            placeholder={__('admin.search_users_by_name_or_email')}
                            aria-label={__('admin.search_users_by_name_or_email')}
                        />
                        <Button type="submit" variant="outline">{__('general.search')}</Button>
                    </form>
                    {suggestions.length === 0 ? (
                        <p className="text-sm text-muted-foreground">{__('admin.no_suggestions')}</p>
                    ) : (
                        <ul className="divide-y">
                            {suggestions.map((s) => {
                                const checked = selectedDuplicates.includes(s.id);
                                return (
                                    <li key={s.id} className="flex items-center gap-3 py-2">
                                        <input
                                            type="checkbox"
                                            checked={checked}
                                            onChange={() => toggleDup(s.id)}
                                            aria-label={s.email}
                                        />
                                        <span className="font-mono text-sm">#{s.id}</span>
                                        <span className="grow truncate">{s.name || '—'}</span>
                                        <span className="font-mono text-xs text-muted-foreground">{s.email}</span>
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                    {selectedDuplicates.length > 0 && (
                        <div className="mt-3 flex items-center gap-3">
                            <span className="text-sm">{__('admin.selected_count', { count: selectedDuplicates.length })}</span>
                            <Button onClick={startMergeFlow} variant="destructive">
                                {__('admin.merge_selected_duplicates')}
                            </Button>
                        </div>
                    )}
                </section>

                <details className="rounded border p-4 bg-white">
                    <summary className="cursor-pointer text-sm font-medium">{__('admin.all_known_emails_on_account', { count: allKnown.length })}</summary>
                    <ul className="text-sm mt-2 space-y-1">
                        {allKnown.map((row) => (
                            <li key={`${row.kind}-${row.id}`} className="flex items-center gap-2">
                                <span className="text-xs uppercase text-muted-foreground w-16">{__(ALIAS_KIND_LABEL_KEYS[row.kind])}</span>
                                <span className="font-mono truncate">{row.email}</span>
                            </li>
                        ))}
                    </ul>
                </details>
            </div>
        </AdminSidebarLayout>
    );
}

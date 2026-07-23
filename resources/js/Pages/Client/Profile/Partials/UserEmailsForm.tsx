import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { useForm, usePage, router } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import { Trash2, CheckCircle2, Mail, Plus, Edit2, Check, X } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface SecondaryEmail {
    id: number;
    email: string;
    verified_at: string | null;
    source: string;
}

export default function UserEmailsForm({
    emails = [],
    className = '',
}: {
    emails?: SecondaryEmail[];
    className?: string;
}) {
    const user = usePage().props.auth.user;
    const { data, setData, post, delete: destroy, errors, processing, reset } = useForm({
        email: '',
    });

    const [editingId, setEditingId] = useState<number | null>(null);
    const [editingValue, setEditingValue] = useState<string>('');
    const [updating, setUpdating] = useState<boolean>(false);

    const submitAddEmail: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('profile.emails.store'), {
            onSuccess: () => reset('email'),
        });
    };

    const handleDeleteEmail = (id: number) => {
        if (confirm(__('general.are_you_sure_you_want_to_delete_this_item'))) {
            destroy(route('profile.emails.destroy', id));
        }
    };

    const handleMakePrimary = (id: number) => {
        post(route('profile.emails.make-primary', id));
    };

    const startEdit = (item: SecondaryEmail) => {
        setEditingId(item.id);
        setEditingValue(item.email);
    };

    const cancelEdit = () => {
        setEditingId(null);
        setEditingValue('');
    };

    const saveEdit = (id: number) => {
        if (!editingValue.trim()) return;
        setUpdating(true);
        router.patch(route('profile.emails.update', id), {
            email: editingValue.trim(),
        }, {
            onSuccess: () => {
                setEditingId(null);
                setEditingValue('');
            },
            onFinish: () => setUpdating(false),
            preserveScroll: true,
        });
    };

    return (
        <section className={className}>
            <header className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 className="text-lg font-medium text-gray-900 dark:text-zinc-100">
                        {__('general.linked_emails_and_google_accounts', {}, 'Linked Emails & Google Login')}
                    </h2>
                    <p className="mt-1 text-sm text-gray-600 dark:text-zinc-400">
                        {__('general.manage_emails_desc', {}, 'You can log in using your primary email or any of your linked emails via Google OAuth.')}
                    </p>
                </div>
                <div>
                    <a
                        href={route('social.google.redirect')}
                        className="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs font-medium text-zinc-900 shadow-xs hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800 transition-colors"
                    >
                        <svg className="w-4 h-4" viewBox="0 0 24 24">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                        <span>{__('general.link_google_account', {}, 'Link Google Account')}</span>
                    </a>
                </div>
            </header>

            <div className="mt-6 space-y-3">
                {/* Primary Email */}
                <div className="flex items-center justify-between p-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
                    <div className="flex items-center gap-3">
                        <Mail className="w-4 h-4 text-zinc-500" />
                        <div>
                            <span className="text-sm font-medium text-zinc-900 dark:text-zinc-100">{user.email}</span>
                            <span className="ms-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                {__('general.primary', {}, 'Primary')}
                            </span>
                        </div>
                    </div>
                    <CheckCircle2 className="w-4 h-4 text-emerald-500" />
                </div>

                {/* Secondary Emails */}
                {emails.map((item) => (
                    <div key={item.id} className="flex items-center justify-between p-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950">
                        {editingId === item.id ? (
                            <div className="flex items-center gap-2 grow me-4">
                                <TextInput
                                    type="email"
                                    value={editingValue}
                                    onChange={(e) => setEditingValue(e.target.value)}
                                    className="block w-full text-xs py-1"
                                    autoFocus
                                />
                                <button
                                    type="button"
                                    onClick={() => saveEdit(item.id)}
                                    disabled={updating}
                                    className="p-1.5 text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                                    title={__('general.save', {}, 'Save')}
                                >
                                    <Check className="w-4 h-4" />
                                </button>
                                <button
                                    type="button"
                                    onClick={cancelEdit}
                                    className="p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200"
                                    title={__('general.cancel', {}, 'Cancel')}
                                >
                                    <X className="w-4 h-4" />
                                </button>
                            </div>
                        ) : (
                            <>
                                <div className="flex items-center gap-3">
                                    <Mail className="w-4 h-4 text-zinc-400" />
                                    <div>
                                        <span className="text-sm font-medium text-zinc-900 dark:text-zinc-100">{item.email}</span>
                                        <span className="ms-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                            {item.source === 'google' || item.source === 'self' ? __('general.linked_alias', {}, 'Linked Email') : item.source}
                                        </span>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    <button
                                        type="button"
                                        onClick={() => handleMakePrimary(item.id)}
                                        className="px-2.5 py-1 text-xs font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-950/50 rounded-md transition-colors"
                                    >
                                        {__('general.make_primary', {}, 'Make Primary')}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => startEdit(item)}
                                        className="p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors"
                                        title={__('general.edit', {}, 'Edit')}
                                    >
                                        <Edit2 className="w-4 h-4" />
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => handleDeleteEmail(item.id)}
                                        className="p-1.5 text-zinc-400 hover:text-red-600 dark:hover:text-red-400 transition-colors"
                                        title={__('general.delete', {}, 'Delete')}
                                    >
                                        <Trash2 className="w-4 h-4" />
                                    </button>
                                </div>
                            </>
                        )}
                    </div>
                ))}
            </div>

            {/* Add Secondary Email Form */}
            <form onSubmit={submitAddEmail} className="mt-6 space-y-4">
                <div>
                    <InputLabel htmlFor="secondary_email" value={__('general.add_secondary_email', {}, 'Add Secondary Email')} />
                    <div className="mt-1 flex gap-2 sm:max-w-md">
                        <TextInput
                            id="secondary_email"
                            type="email"
                            className="block w-full"
                            placeholder="secondary@example.com"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                        />
                        <PrimaryButton disabled={processing} className="shrink-0 flex items-center gap-1">
                            <Plus className="w-4 h-4" />
                            <span>{__('general.add')}</span>
                        </PrimaryButton>
                    </div>
                    <InputError className="mt-2" message={errors.email} />
                </div>
            </form>
        </section>
    );
}

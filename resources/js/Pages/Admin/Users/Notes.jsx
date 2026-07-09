import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ShieldAlert,
    ShieldCheck,
    Key,
    Monitor,
    FileText,
    Archive,
    Pin,
    Trash2,
    Upload,
    RefreshCw,
    Copy,
    Check,
    AlertTriangle,
    Plus,
    X,
    Pencil,
    Eye,
    EyeOff,
} from 'lucide-react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/Components/ui/dialog';
import { Textarea } from '@/Components/ui/textarea';
import { Checkbox } from '@/Components/ui/checkbox';
import SimpleCrypto from '@/lib/SimpleCrypto';
import { __ } from '@/lib/i18n';

const CLIPBOARD_AUTOCLEAR_MS = 30_000;

export default function Notes({ user, notes, stats }) {
    const { props } = usePage();

    const flash = props?.flash ?? {};

    const [title, setTitle] = useState('');
    const [content, setContent] = useState('');
    const [category, setCategory] = useState('notes');
    const [expiresAt, setExpiresAt] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const [password, setPassword] = useState('');
    const [isPasswordSet, setIsPasswordSet] = useState(false);
    const [cryptoInstance, setCryptoInstance] = useState(null);
    const [showMasterPwd, setShowMasterPwd] = useState(false);

    const [selectedNote, setSelectedNote] = useState(null);
    const [isViewModalOpen, setIsViewModalOpen] = useState(false);
    const [copied, setCopied] = useState(false);
    const [copiedId, setCopiedId] = useState(null);
    const [revealAutoHideAt, setRevealAutoHideAt] = useState(null);

    const [filterCategory, setFilterCategory] = useState('all');
    const [searchQuery, setSearchQuery] = useState('');
    const [showOnlyPinned, setShowOnlyPinned] = useState(false);

    const [selectedIds, setSelectedIds] = useState([]);
    const [showBulkBar, setShowBulkBar] = useState(false);

    const [editingNote, setEditingNote] = useState(null);

    const [showCreateSheet, setShowCreateSheet] = useState(false);

    const idleTimerRef = useRef(null);

    const isNoteArchiveable = (cat) => cat !== 'archived';
    const isNoteUnarchiveable = (cat) => cat === 'archived';

    useEffect(() => {
        if (!isPasswordSet) return;

        const reset = () => {
            if (idleTimerRef.current) clearTimeout(idleTimerRef.current);
            idleTimerRef.current = setTimeout(() => {
                handleClearPassword();
            }, 15 * 60 * 1000);
        };
        const events = ['mousemove', 'keydown', 'click', 'touchstart'];
        events.forEach((e) => window.addEventListener(e, reset, { passive: true }));
        reset();
        return () => {
            events.forEach((e) => window.removeEventListener(e, reset));
            if (idleTimerRef.current) clearTimeout(idleTimerRef.current);
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [isPasswordSet]);

    useEffect(() => {
        if (!isViewModalOpen || !revealAutoHideAt) return;
        const remaining = Math.max(0, revealAutoHideAt - Date.now());
        const t = setTimeout(() => {
            setIsViewModalOpen(false);
            setRevealAutoHideAt(null);
            setSelectedNote(null);
        }, remaining);
        return () => clearTimeout(t);
    }, [isViewModalOpen, revealAutoHideAt]);

    useEffect(() => {
        const savedPwd = sessionStorage.getItem('notes_pwd');
        if (savedPwd) {
            setPassword(savedPwd);
            setIsPasswordSet(true);
            try {
                setCryptoInstance(new SimpleCrypto(savedPwd));
            } catch (_) {
                sessionStorage.removeItem('notes_pwd');
            }
        }
        const flashKeys = Object.keys(flash ?? {});
        if (flashKeys.length) {
            const t = setTimeout(() => {
                Object.keys(props.flash ?? {}).forEach((k) => router.reload({ only: ['flash'] }));
            }, 4000);
            return () => clearTimeout(t);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const handleSetPassword = (e) => {
        e.preventDefault();
        const pwd = password.trim();
        if (!pwd) return;

        setError(null);
        let tempCrypto;
        try {
            tempCrypto = new SimpleCrypto(pwd);
        } catch (err) {
            setError('Invalid encryption password format.');
            return;
        }

        // Validate password against existing encrypted notes (if any)
        const encryptedCandidate = items.find(
            (n) =>
                (n.title && !/\s/.test(n.title) && n.title.length >= 128) ||
                (n.content && !/\s/.test(n.content) && n.content.length >= 128)
        );

        if (encryptedCandidate) {
            let decryptedVal = null;
            const targetText = (encryptedCandidate.title && !/\s/.test(encryptedCandidate.title) && encryptedCandidate.title.length >= 128)
                ? encryptedCandidate.title
                : encryptedCandidate.content;

            try {
                decryptedVal = tempCrypto.decrypt(targetText);
            } catch (_) {
                decryptedVal = null;
            }

            if (decryptedVal === null || decryptedVal === undefined || decryptedVal === '' || String(decryptedVal).startsWith('🔒')) {
                setError(__('general.invalid_master_password_please_try_again') || 'Invalid master password. Please try again.');
                return;
            }
        }

        sessionStorage.setItem('notes_pwd', pwd);
        setCryptoInstance(tempCrypto);
        setIsPasswordSet(true);
    };

    const handleClearPassword = useCallback(() => {
        sessionStorage.removeItem('notes_pwd');
        setPassword('');
        setIsPasswordSet(false);
        setCryptoInstance(null);
        setSelectedNote(null);
        setIsViewModalOpen(false);
    }, []);

    const decryptText = useCallback((text) => {
        if (text === null || text === undefined || text === '') return '';
        if (!cryptoInstance) return null;
        if (typeof text !== 'string') return '';

        // Plaintext short-circuits: anything containing whitespace, or too short
        // to be a SimpleCrypto blob (salt 32 + iv 32 + hmac 64 = 128 hex chars minimum),
        // is treated as already-decrypted user input.
        if (/\s/.test(text) || text.length < 128) {
            return text;
        }

        try {
            const dec = cryptoInstance.decrypt(text);
            if (dec === null || dec === undefined || dec === '') {
                return '🔒 [Encrypted Data - Invalid Password]';
            }
            return typeof dec === 'string' ? dec : String(dec);
        } catch (_) {
            return '🔒 [Encrypted Data - Invalid Password]';
        }
    }, [cryptoInstance]);

    const encryptText = useCallback((text) => {
        if (!text || !cryptoInstance) return text;
        return cryptoInstance.encrypt(text.trim());
    }, [cryptoInstance]);

    const submitNote = (mode = 'create') => {
        if (!isPasswordSet || !cryptoInstance) {
            setError('Please set your master password first.');
            return;
        }
        if (!title.trim() || !content.trim()) {
            setError('Title and content are required.');
            return;
        }

        setLoading(true);
        setError(null);

        const cipherTitle = encryptText(title);
        const cipherContent = encryptText(content);

        const payload = {
            title: cipherTitle,
            content: cipherContent,
            category,
            ...(expiresAt ? { expires_at: expiresAt } : {}),
        };

        const url = mode === 'edit' && editingNote
            ? `/admin/users/${user.id}/notes/${editingNote.id}`
            : `/admin/users/${user.id}/notes`;

        const onSuccess = () => {
            setTitle('');
            setContent('');
            setExpiresAt('');
            setEditingNote(null);
            setShowCreateSheet(false);
            setLoading(false);
        };

        if (mode === 'edit') {
            router.put(url, payload, { preserveScroll: true, onSuccess, onError: () => setLoading(false) });
        } else {
            router.post(url, payload, { preserveScroll: true, onSuccess, onError: () => setLoading(false) });
        }
    };

    const handleTogglePin = (noteId) => {
        router.post(`/admin/users/${user.id}/notes/${noteId}/toggle-pin`, {}, { preserveScroll: true });
    };

    const handleViewNote = (note) => {
        setSelectedNote(note);
        setIsViewModalOpen(true);
        setCopied(false);
        setRevealAutoHideAt(Date.now() + CLIPBOARD_AUTOCLEAR_MS + 5_000);

        router.post(`/admin/users/${user.id}/notes/${note.id}/reveal`, {}, { preserveScroll: true });
    };

    const handleEditNote = (note) => {
        const decTitle = decryptText(note.title);
        const decContent = decryptText(note.content);
        setEditingNote(note);
        setTitle(decTitle && !decTitle.startsWith('🔒') ? decTitle : '');
        setContent(decContent && !decContent.startsWith('🔒') ? decContent : '');
        setCategory(note.category === 'archived' ? 'notes' : note.category);
        setExpiresAt(note.expires_at ? note.expires_at.slice(0, 10) : '');
        
        if (window.innerWidth < 1024) {
            setShowCreateSheet(true);
        } else {
            const formElement = document.getElementById('category');
            if (formElement) {
                formElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                // Focus the title input for editing immediate convenience
                setTimeout(() => {
                    const titleInput = document.getElementById('title');
                    if (titleInput) titleInput.focus();
                }, 300);
            }
        }
    };

    const handleArchive = (noteId, currentCategory) => {
        const url = currentCategory === 'archived'
            ? `/admin/users/${user.id}/notes/${noteId}/unarchive`
            : `/admin/users/${user.id}/notes/${noteId}/archive`;
        router.post(url, {}, { preserveScroll: true });
    };

    const handleDelete = (noteId) => {
        if (!confirm('Are you sure you want to delete this note permanently?')) return;
        router.delete(`/admin/users/${user.id}/notes/${noteId}`, { preserveScroll: true });
    };

    const handleBulk = (action) => {
        if (selectedIds.length === 0) return;
        if (action === 'delete' && !confirm(`Permanently delete ${selectedIds.length} note(s)?`)) return;
        router.post(`/admin/users/${user.id}/notes/bulk`, { action, note_ids: selectedIds }, {
            preserveScroll: true,
            onSuccess: () => setSelectedIds([]),
        });
    };

    const handleCopy = async () => {
        if (!selectedNote) return;
        const dec = selectedNote.decryptedContent
            ?? decryptText(selectedNote.content);
        if (!dec || (typeof dec === 'string' && dec.startsWith('🔒'))) return;
        try {
            await navigator.clipboard.writeText(String(dec));
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
            setTimeout(async () => {
                try {
                    await navigator.clipboard.writeText('');
                } catch (clipErr) {
                    // Clipboard unavailable or permission revoked; ignore.
                }
            }, CLIPBOARD_AUTOCLEAR_MS);
        } catch (_) {
            setError('Clipboard not available.');
        }
    };

    const handleCopyCard = async (text, id) => {
        if (!text || (typeof text === 'string' && text.startsWith('🔒'))) return;
        try {
            await navigator.clipboard.writeText(String(text));
            setCopiedId(id);
            setTimeout(() => setCopiedId(null), 2000);
            setTimeout(async () => {
                try {
                    await navigator.clipboard.writeText('');
                } catch (clipErr) {
                    // Clipboard unavailable or permission revoked; ignore.
                }
            }, CLIPBOARD_AUTOCLEAR_MS);
        } catch (_) {
            setError('Clipboard not available.');
        }
    };

    const handlePageChange = (newPage) => {
        router.get(
            window.location.pathname,
            {
                page: newPage,
                category: filterCategory !== 'all' ? filterCategory : undefined,
                pinned: showOnlyPinned ? 1 : undefined,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    const items = useMemo(
        () => (Array.isArray(notes) ? notes : notes?.items ?? []),
        [notes]
    );

    const decryptedNotes = useMemo(() => {
        return items.map((n) => ({
            ...n,
            decryptedTitle: decryptText(n.title),
            decryptedContent: decryptText(n.content),
        }));
    }, [items, decryptText]);

    const filteredNotes = useMemo(() => {
        let list = decryptedNotes;
        if (filterCategory !== 'all') {
            list = list.filter((n) => n.category === filterCategory);
        }
        if (showOnlyPinned) {
            list = list.filter((n) => n.is_pinned);
        }
        if (searchQuery.trim()) {
            const q = searchQuery.toLowerCase();
            list = list.filter((n) =>
                (n.decryptedTitle && String(n.decryptedTitle).toLowerCase().includes(q)) ||
                (n.decryptedContent && String(n.decryptedContent).toLowerCase().includes(q)) ||
                (n.title && String(n.title).toLowerCase().includes(q)) ||
                (n.content && String(n.content).toLowerCase().includes(q))
            );
        }
        return [...list].sort((a, b) => {
            const pinDiff = Number(!!b.is_pinned) - Number(!!a.is_pinned);
            if (pinDiff !== 0) return pinDiff;
            return new Date(b.created_at) - new Date(a.created_at);
        });
    }, [decryptedNotes, filterCategory, showOnlyPinned, searchQuery]);

    const getCategoryIcon = (cat) => {
        switch (cat) {
            case 'password': return <Key size={14} className="text-yellow-600" />;
            case 'anydesk': return <Monitor size={14} className="text-slate-700" />;
            case 'archived': return <Archive size={14} className="text-gray-500" />;
            default: return <FileText size={14} className="text-slate-500" />;
        }
    };

    const toggleSelect = (id) => {
        setSelectedIds((prev) => prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]);
    };

    const allSelected = filteredNotes.length > 0 && filteredNotes.every((n) => selectedIds.includes(n.id));

    const flashBanner = (() => {
        if (flash?.success) return { type: 'success', text: flash.success };
        if (flash?.error) return { type: 'error', text: flash.error };
        if (flash?.warning) return { type: 'warning', text: flash.warning };
        return null;
    })();

    return (
        <AdminSidebarLayout title={`Secure Notes: ${user.name}`} header="Secure Notes">
            <Head title={`Secure Notes - ${user.name}`} />

            <div className="flex flex-col md:flex-row md:justify-between md:items-center gap-3 mb-6">
                <div>
                    <h1 className="text-2xl md:text-3xl font-bold font-sora flex items-center gap-2">
                        {__('general.secure_notes')}
                        <span className="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-medium">
                            {stats?.total ?? 0}
                        </span>
                    </h1>
                    <p className="text-slate-500 text-sm">{__('general.encrypted_notes_for_user', { name: user.name })}</p>
                </div>
                <div className="flex flex-wrap gap-2">
                    <Button asChild variant="outline" className="border-slate-200">
                        <Link href={`/admin/users/${user.id}/files`}>{__('general.files')}</Link>
                    </Button>
                    <Button asChild className="bg-slate-900 hover:bg-slate-800 text-white">
                        <Link href={`/admin/users/${user.id}`}>{__('general.profile')}</Link>
                    </Button>
                </div>
            </div>

            {flashBanner && (
                <div
                    role="status"
                    aria-live="polite"
                    className={`mb-4 p-3 rounded-lg border text-sm font-medium ${
                        flashBanner.type === 'success' ? 'bg-green-50 border-green-200 text-green-800' :
                        flashBanner.type === 'error' ? 'bg-red-50 border-red-200 text-red-800' :
                        'bg-yellow-50 border-yellow-200 text-yellow-800'
                    }`}
                >
                    {flashBanner.text}
                </div>
            )}

            <div className={`p-4 rounded-xl border mb-6 flex flex-col md:flex-row md:items-center gap-4 ${isPasswordSet ? 'bg-green-50 border-green-200' : 'bg-yellow-50 border-yellow-200'}`}>
                <div className="flex items-start gap-4 flex-1">
                    {isPasswordSet
                        ? <ShieldCheck className="text-green-600 shrink-0 mt-1" size={24} />
                        : <ShieldAlert className="text-yellow-600 shrink-0 mt-1" size={24} />}
                    <div className="flex-1">
                        <h4 className={`font-bold mb-1 ${isPasswordSet ? 'text-green-900' : 'text-yellow-900'}`}>
                            {isPasswordSet ? __('general.encryption_active') : __('general.encryption_password_required')}
                        </h4>
                        <p className={`text-sm ${isPasswordSet ? 'text-green-700' : 'text-yellow-700'}`}>
                            {__('general.all_notes_are_encrypted_client_side_before_being_saved_the_server_cannot_read_your_data')}
                        </p>
                    </div>
                </div>
                <div className="md:ms-auto">
                    {!isPasswordSet ? (
                        <form onSubmit={handleSetPassword} className="flex gap-2">
                            <div className="relative">
                                <Input
                                    type={showMasterPwd ? 'text' : 'password'}
                                    placeholder={__('general.master_password')}
                                    aria-label={__('general.master_password')}
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    className="w-48 bg-white border-yellow-300 pe-9"
                                    required
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowMasterPwd((v) => !v)}
                                    aria-label={showMasterPwd ? __('general.hide_password') : __('general.show_password')}
                                    className="absolute end-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700"
                                >
                                    {showMasterPwd ? <EyeOff size={16} /> : <Eye size={16} />}
                                </button>
                            </div>
                            <Button type="submit" className="bg-yellow-600 hover:bg-yellow-700 text-white">
                                {__('general.unlock')}
                            </Button>
                        </form>
                    ) : (
                        <Button variant="outline" onClick={handleClearPassword} className="border-green-300 text-green-700 hover:bg-green-100">
                            {__('general.lock_clear_session')}
                        </Button>
                    )}
                </div>
            </div>

            <div className="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                <StatChip label={__('general.passwords')} value={stats?.password ?? 0} icon={<Key size={16} className="text-yellow-600" />} />
                <StatChip label={__('general.anydesk')} value={stats?.anydesk ?? 0} icon={<Monitor size={16} className="text-slate-700" />} />
                <StatChip label={__('general.general_notes')} value={stats?.notes ?? 0} icon={<FileText size={16} className="text-slate-500" />} />
                <StatChip label={__('general.archived')} value={stats?.archived ?? 0} icon={<Archive size={16} className="text-gray-500" />} />
            </div>

            {stats?.expired > 0 && (
                <div className="mb-4 p-3 rounded-lg border border-red-200 bg-red-50 text-red-700 text-sm font-medium flex items-center gap-2">
                    <AlertTriangle size={16} />
                    {__('general.n_passwords_have_expired', { count: stats.expired })}
                </div>
            )}

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2 space-y-4">
                    <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-wrap gap-4 items-center justify-between">
                        <div className="flex flex-wrap items-center gap-3 flex-1">
                            <Input
                                placeholder={__('general.search_decrypted_notes')}
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="max-w-xs"
                                disabled={!isPasswordSet}
                                aria-label={__('general.search')}
                            />
                            <select
                                className="border-slate-300 rounded-md text-sm focus:border-slate-900 focus:ring-slate-900"
                                value={filterCategory}
                                onChange={(e) => setFilterCategory(e.target.value)}
                                aria-label={__('general.filter_by_category')}
                            >
                                <option value="all">{__('general.all_categories')}</option>
                                <option value="password">{__('general.passwords')}</option>
                                <option value="anydesk">{__('general.anydesk')}</option>
                                <option value="notes">{__('general.general_notes')}</option>
                                <option value="archived">{__('general.archived')}</option>
                            </select>
                            <label className="inline-flex items-center gap-2 text-sm text-slate-700">
                                <Checkbox checked={showOnlyPinned} onCheckedChange={setShowOnlyPinned} />
                                {__('general.pinned_only')}
                            </label>
                        </div>
                        <div className="text-sm font-medium text-slate-500">
                            {__('general.total_count', { count: filteredNotes.length })}
                        </div>
                    </div>

                    {selectedIds.length > 0 && (
                        <div className="bg-slate-900 text-white rounded-xl p-3 flex items-center justify-between text-sm">
                            <span>{__('general.n_selected', { count: selectedIds.length })}</span>
                            <div className="flex gap-2">
                                <Button size="sm" variant="outline" className="text-slate-900 bg-white" onClick={() => handleBulk('archive')}>
                                    <Archive size={14} className="me-1" /> {__('general.archive')}
                                </Button>
                                <Button size="sm" variant="outline" className="text-slate-900 bg-white" onClick={() => handleBulk('unarchive')}>
                                    <Upload size={14} className="me-1" /> {__('general.unarchive')}
                                </Button>
                                <Button size="sm" variant="outline" className="bg-red-50 text-red-700 border-red-200 hover:bg-red-100" onClick={() => handleBulk('delete')}>
                                    <Trash2 size={14} className="me-1" /> {__('general.delete')}
                                </Button>
                                <Button size="sm" variant="ghost" className="text-white hover:bg-slate-800" onClick={() => setSelectedIds([])}>
                                    <X size={14} className="me-1" /> {__('general.clear')}
                                </Button>
                            </div>
                        </div>
                    )}

                    <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        {filteredNotes.map((note) => (
                            <article key={note.id} className={`bg-white p-5 rounded-xl border shadow-sm transition hover:shadow-md flex flex-col ${selectedIds.includes(note.id) ? 'border-slate-900 ring-2 ring-slate-900/20' : 'border-slate-200'}`}>
                                <div className="flex justify-between items-start mb-3 gap-3">
                                    <div className="flex flex-col flex-1 min-w-0">
                                        <div className="flex items-center gap-2 mb-1 flex-wrap">
                                            <Checkbox
                                                checked={selectedIds.includes(note.id)}
                                                onCheckedChange={() => toggleSelect(note.id)}
                                                aria-label={__('general.select_note')}
                                            />
                                            {note.is_pinned && (
                                                <span className="bg-slate-800 text-white px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">
                                                    {__('general.pinned')}
                                                </span>
                                            )}
                                            <span className="bg-slate-100 text-slate-700 px-2 py-0.5 rounded text-xs font-medium capitalize flex items-center gap-1 border border-slate-200 shrink-0">
                                                {getCategoryIcon(note.category)}
                                                {note.category}
                                            </span>
                                            {note.is_expired && (
                                                <span className="bg-red-100 text-red-700 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">
                                                    {__('general.expired')}
                                                </span>
                                            )}
                                            {!note.is_expired && note.is_expiring_soon && (
                                                <span className="bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">
                                                    {__('general.expiring_soon')}
                                                </span>
                                            )}
                                        </div>
                                        {note.decryptedTitle && !String(note.decryptedTitle).startsWith('🔒') && (
                                            <h3 className="text-lg font-bold text-slate-900 break-words">{note.decryptedTitle}</h3>
                                        )}
                                        <span className="text-[11px] text-slate-400 font-medium">
                                            {new Date(note.created_at).toLocaleString()}
                                            {note.author?.name ? ` · ${note.author.name}` : ''}
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-1 shrink-0">
                                        <IconButton title={note.is_pinned ? __('general.unpin') : __('general.pin')} active={note.is_pinned} onClick={() => handleTogglePin(note.id)}>
                                            <Pin size={14} />
                                        </IconButton>
                                        <IconButton
                                            title={
                                                note.category === 'archived'
                                                    ? __('general.unarchive')
                                                    : __('general.archive')
                                            }
                                            onClick={() => handleArchive(note.id, note.category)}
                                        >
                                            {note.category === 'archived' ? <Upload size={14} /> : <Archive size={14} />}
                                        </IconButton>
                                        <IconButton title={__('general.edit')} onClick={() => handleEditNote(note)}>
                                            <Pencil size={14} />
                                        </IconButton>
                                        <IconButton title={__('general.delete')} danger onClick={() => handleDelete(note.id)}>
                                            <Trash2 size={14} />
                                        </IconButton>
                                    </div>
                                </div>
                                <div className="prose prose-sm max-w-none text-slate-600 bg-slate-50 p-4 rounded-lg border border-slate-100 flex justify-between items-center gap-3">
                                    {isPasswordSet && note.decryptedContent !== null && !String(note.decryptedContent).startsWith('🔒') ? (
                                        <div className="flex items-center justify-between flex-1 min-w-0 gap-2">
                                            <span className="text-slate-500 line-clamp-1 font-mono text-xs overflow-hidden text-ellipsis whitespace-nowrap">
                                                {note.decryptedContent}
                                            </span>
                                            <button
                                                type="button"
                                                title={__('general.copy_text')}
                                                onClick={() => handleCopyCard(note.decryptedContent, note.id)}
                                                className={`p-1 rounded hover:bg-slate-200 text-slate-400 hover:text-slate-700 shrink-0 transition-colors ${copiedId === note.id ? 'text-green-600 hover:text-green-700' : ''}`}
                                            >
                                                {copiedId === note.id ? <Check size={14} /> : <Copy size={14} />}
                                            </button>
                                        </div>
                                    ) : (
                                        <div className="flex items-center text-slate-400 font-medium text-sm">
                                            <Key size={16} className="me-2" />
                                            {__('general.encrypted_content_hidden')}
                                        </div>
                                    )}
                                    {isPasswordSet && note.decryptedContent && !String(note.decryptedContent).startsWith('🔒') && (
                                        <Button variant="outline" size="sm" className="ms-2 shrink-0 shadow-sm border-slate-200" onClick={() => handleViewNote(note)}>
                                            <FileText size={14} className="me-2 text-slate-400" />
                                            {__('general.view_note')}
                                        </Button>
                                    )}
                                </div>
                            </article>
                        ))}
                        {filteredNotes.length === 0 && (
                            <div className="bg-white p-12 rounded-xl border border-slate-200 text-center flex flex-col items-center col-span-full">
                                <div className="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-300 mb-4">
                                    <FileText size={32} />
                                </div>
                                <h3 className="text-lg font-bold text-slate-900 mb-1">{__('general.no_notes_found_1')}</h3>
                                <p className="text-slate-500 text-sm mb-4">
                                    {isPasswordSet
                                        ? __('general.create_first_note_or_adjust_filters')
                                        : __('general.unlock_to_view_notes')}
                                </p>
                                {isPasswordSet && (
                                    <Button onClick={() => setShowCreateSheet(true)} className="bg-slate-900 hover:bg-slate-800 text-white">
                                        <Plus size={16} className="me-2" /> {__('general.add_first_note')}
                                    </Button>
                                )}
                            </div>
                        )}
                    </div>

                    {notes && notes.last_page > 1 && (
                        <div className="flex items-center justify-between border-t border-slate-200 bg-white px-4 py-3 sm:px-6 mt-6 rounded-xl shadow-sm">
                            <div className="flex flex-1 justify-between sm:hidden">
                                <Button
                                    variant="outline"
                                    onClick={() => handlePageChange(notes.current_page - 1)}
                                    disabled={notes.current_page === 1}
                                    className="relative inline-flex items-center rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                                >
                                    {__('general.previous') || 'Previous'}
                                </Button>
                                <Button
                                    variant="outline"
                                    onClick={() => handlePageChange(notes.current_page + 1)}
                                    disabled={notes.current_page === notes.last_page}
                                    className="relative ml-3 inline-flex items-center rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                                >
                                    {__('general.next') || 'Next'}
                                </Button>
                            </div>
                            <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                                <div>
                                    <p className="text-sm text-slate-700">
                                        Showing <span className="font-medium">{((notes.current_page - 1) * notes.per_page) + 1}</span> to{' '}
                                        <span className="font-medium">{Math.min(notes.current_page * notes.per_page, notes.total)}</span> of{' '}
                                        <span className="font-medium">{notes.total}</span> results
                                    </p>
                                </div>
                                <div>
                                    <nav className="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                                        <Button
                                            variant="outline"
                                            onClick={() => handlePageChange(notes.current_page - 1)}
                                            disabled={notes.current_page === 1}
                                            className="relative inline-flex items-center rounded-l-md px-3 py-2 text-slate-400 hover:bg-slate-50 disabled:opacity-50 border-slate-200"
                                        >
                                            <span className="sr-only">Previous</span>
                                            &larr;
                                        </Button>
                                        {Array.from({ length: notes.last_page }, (_, i) => i + 1).map((p) => (
                                            <button
                                                key={p}
                                                onClick={() => handlePageChange(p)}
                                                aria-current={p === notes.current_page ? 'page' : undefined}
                                                className={`relative inline-flex items-center px-4 py-2 text-sm font-semibold transition-all ${
                                                    p === notes.current_page
                                                        ? 'z-10 bg-slate-900 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-900 rounded-md mx-0.5'
                                                        : 'text-slate-900 border border-slate-200 hover:bg-slate-50 rounded-md mx-0.5'
                                                }`}
                                            >
                                                {p}
                                            </button>
                                        ))}
                                        <Button
                                            variant="outline"
                                            onClick={() => handlePageChange(notes.current_page + 1)}
                                            disabled={notes.current_page === notes.last_page}
                                            className="relative inline-flex items-center rounded-r-md px-3 py-2 text-slate-400 hover:bg-slate-50 disabled:opacity-50 border-slate-200"
                                        >
                                            <span className="sr-only">Next</span>
                                            &rarr;
                                        </Button>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                <div className="lg:col-span-1 hidden lg:block">
                    <CreateNoteForm
                        title={title}
                        content={content}
                        category={category}
                        expiresAt={expiresAt}
                        loading={loading}
                        error={error}
                        editingNote={editingNote}
                        isPasswordSet={isPasswordSet}
                        setTitle={setTitle}
                        setContent={setContent}
                        setCategory={setCategory}
                        setExpiresAt={setExpiresAt}
                        onSubmit={() => submitNote(editingNote ? 'edit' : 'create')}
                        onCancel={() => {
                            setEditingNote(null);
                            setTitle('');
                            setContent('');
                            setExpiresAt('');
                        }}
                    />
                </div>
            </div>

            <div className="lg:hidden fixed bottom-6 inset-x-0 z-30 px-6">
                <Button onClick={() => setShowCreateSheet(true)} className="w-full bg-slate-900 hover:bg-slate-800 text-white shadow-lg">
                    <Plus size={16} className="me-2" /> {__('general.add_note')}
                </Button>
            </div>

            <Dialog open={isViewModalOpen} onOpenChange={setIsViewModalOpen}>
                <DialogContent className="sm:max-w-2xl bg-white p-0 overflow-hidden border-0 shadow-2xl">
                    <div className="bg-slate-50 border-b border-slate-100 p-6 flex flex-col gap-2">
                        <DialogTitle className="flex items-center gap-2 text-xl font-bold text-slate-900 font-sora">
                            {selectedNote && getCategoryIcon(selectedNote.category)}
                            {selectedNote?.decryptedTitle || __('general.encrypted_content_hidden')}
                        </DialogTitle>
                        <DialogDescription className="text-slate-500 text-xs font-medium flex items-center gap-2">
                            <span className="bg-slate-200 text-slate-700 px-2 py-0.5 rounded capitalize">
                                {selectedNote?.category}
                            </span>
                            {selectedNote && new Date(selectedNote.created_at).toLocaleString()}
                        </DialogDescription>
                    </div>

                    <div className="p-6 relative bg-white">
                        <div className="absolute top-8 end-8 z-10">
                            <Button
                                variant="outline"
                                size="sm"
                                className={`h-8 px-3 shadow-sm transition-all ${copied ? 'bg-green-50 text-green-700 border-green-200 hover:bg-green-50 hover:text-green-700' : 'bg-white hover:bg-slate-50 text-slate-600'}`}
                                onClick={handleCopy}
                            >
                                {copied
                                    ? <><Check size={14} className="me-1.5" /> {__('general.copied')}</>
                                    : <><Copy size={14} className="me-1.5" /> {__('general.copy_text')}</>}
                            </Button>
                        </div>
                        {selectedNote && (
                            <Textarea
                                readOnly
                                value={
                                    !isPasswordSet
                                        ? __('general.unlock_to_view_content')
                                        : (selectedNote.decryptedContent ?? __('general.encrypted_content_hidden'))
                                }
                                aria-label={__('general.secure_content')}
                                className="min-h-[250px] font-mono text-sm bg-slate-50/50 border-slate-200 resize-y p-5 pt-12 text-slate-700 leading-relaxed focus-visible:ring-slate-300"
                            />
                        )}
                        <p className="mt-2 text-[11px] text-slate-400">
                            {__('general.clipboard_auto_clear_hint', { seconds: CLIPBOARD_AUTOCLEAR_MS / 1000 })}
                        </p>
                    </div>

                    <div className="flex justify-end p-4 border-t border-slate-100 bg-slate-50">
                        <Button variant="outline" onClick={() => setIsViewModalOpen(false)}>
                            {__('general.close')}
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>

            <Dialog open={showCreateSheet} onOpenChange={setShowCreateSheet}>
                <DialogContent className="sm:max-w-lg bg-white p-0 overflow-hidden">
                    <CreateNoteForm
                        title={title}
                        content={content}
                        category={category}
                        expiresAt={expiresAt}
                        loading={loading}
                        error={error}
                        editingNote={editingNote}
                        isPasswordSet={isPasswordSet}
                        embedded
                        setTitle={setTitle}
                        setContent={setContent}
                        setCategory={setCategory}
                        setExpiresAt={setExpiresAt}
                        onSubmit={() => submitNote(editingNote ? 'edit' : 'create')}
                        onCancel={() => {
                            setShowCreateSheet(false);
                            setEditingNote(null);
                            setTitle('');
                            setContent('');
                            setExpiresAt('');
                        }}
                    />
                </DialogContent>
            </Dialog>
        </AdminSidebarLayout>
    );
}

function StatChip({ label, value, icon }) {
    return (
        <div className="bg-white border border-slate-200 rounded-xl p-3 flex items-center gap-3">
            <div className="w-9 h-9 rounded-lg bg-slate-50 flex items-center justify-center">{icon}</div>
            <div className="flex flex-col">
                <span className="text-[11px] uppercase tracking-wider text-slate-500 font-bold">{label}</span>
                <span className="text-xl font-bold text-slate-900">{value}</span>
            </div>
        </div>
    );
}

function IconButton({ children, onClick, title, danger, active }) {
    return (
        <button
            type="button"
            onClick={onClick}
            title={title}
            aria-label={title}
            className={`p-1.5 rounded-md border transition ${
                danger
                    ? 'border-red-100 bg-red-50 text-red-500 hover:bg-red-100'
                    : active
                        ? 'bg-slate-100 border-slate-300 text-slate-700'
                        : 'border-slate-200 text-slate-400 hover:text-slate-700'
            }`}
        >
            {children}
        </button>
    );
}

function CreateNoteForm({
    title, content, category, expiresAt, loading, error, editingNote, isPasswordSet, embedded,
    setTitle, setContent, setCategory, setExpiresAt, onSubmit, onCancel,
}) {
    return (
        <div className={`bg-white p-6 ${embedded ? '' : 'rounded-xl border border-slate-200 shadow-sm sticky top-6'}`}>
            <div className="flex items-center gap-3 mb-6 border-b pb-4">
                <div className="w-10 h-10 rounded-lg bg-slate-900 text-white flex items-center justify-center">
                    <ShieldCheck size={20} />
                </div>
                <div className="flex-1">
                    <h3 className="font-bold text-slate-900 font-sora">
                        {editingNote ? __('general.edit_note') : __('general.create_note')}
                    </h3>
                    <p className="text-xs text-slate-500">{__('general.encrypted_before_saving')}</p>
                </div>
                {embedded && (
                    <button type="button" onClick={onCancel} className="text-slate-400 hover:text-slate-700" aria-label={__('general.close')}>
                        <X size={18} />
                    </button>
                )}
            </div>

            <form onSubmit={(e) => { e.preventDefault(); onSubmit(); }} className="space-y-4">
                {error && (
                    <div role="alert" className="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-2">
                        {error}
                    </div>
                )}

                <div>
                    <Label htmlFor="category" className="text-slate-700 font-bold mb-1.5 block">{__('general.category')}</Label>
                    <select
                        id="category"
                        className="w-full border-slate-300 rounded-lg text-sm focus:border-slate-900 focus:ring-slate-900 bg-slate-50"
                        value={category}
                        onChange={(e) => setCategory(e.target.value)}
                        disabled={loading || !isPasswordSet}
                    >
                        <option value="password">{__('general.password')}</option>
                        <option value="notes">{__('general.notes')}</option>
                        <option value="anydesk">{__('general.anydesk')}</option>
                    </select>
                </div>

                <div>
                    <Label htmlFor="title" className="text-slate-700 font-bold mb-1.5 block">{__('general.title')}</Label>
                    <Input
                        id="title"
                        type="text"
                        className="w-full border-slate-300 rounded-lg shadow-sm text-sm focus:border-slate-900 focus:ring-slate-900 bg-slate-50"
                        placeholder={__('general.e_g_database_credentials')}
                        value={title}
                        onChange={(e) => setTitle(e.target.value)}
                        required
                        disabled={loading || !isPasswordSet}
                    />
                </div>

                <div>
                    <Label htmlFor="content" className="text-slate-700 font-bold mb-1.5 flex justify-between">
                        <span>{__('general.secure_content')}</span>
                        <span className="text-[10px] font-bold uppercase tracking-wider text-green-600 bg-green-50 px-2 py-0.5 rounded border border-green-200 flex items-center gap-1">
                            <Key size={10} /> {__('general.encrypted')}
                        </span>
                    </Label>
                    <textarea
                        id="content"
                        aria-label={__('general.secure_content')}
                        className="w-full border-slate-300 rounded-lg shadow-sm text-sm focus:border-slate-900 focus:ring-slate-900 font-mono bg-slate-50"
                        rows={8}
                        placeholder={__('general.enter_sensitive_information_here')}
                        value={content}
                        onChange={(e) => setContent(e.target.value)}
                        required
                        disabled={loading || !isPasswordSet}
                    />
                </div>

                {category === 'password' && (
                    <div>
                        <Label htmlFor="expires-at" className="text-slate-700 font-bold mb-1.5 block">
                            {__('general.expires_optional')}
                        </Label>
                        <Input
                            id="expires-at"
                            type="date"
                            className="w-full border-slate-300 rounded-lg text-sm focus:border-slate-900 focus:ring-slate-900 bg-slate-50"
                            value={expiresAt}
                            onChange={(e) => setExpiresAt(e.target.value)}
                            disabled={loading || !isPasswordSet}
                        />
                    </div>
                )}

                <div className="flex gap-2">
                    {editingNote && (
                        <Button type="button" variant="outline" onClick={onCancel} className="flex-1">
                            {__('general.cancel')}
                        </Button>
                    )}
                    <Button
                        type="submit"
                        disabled={loading || !isPasswordSet || !content.trim() || !title.trim()}
                        className={`${editingNote ? 'flex-1' : 'w-full'} py-6 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-lg shadow-sm text-base transition-all`}
                    >
                        {loading
                            ? <RefreshCw className="animate-spin me-2" size={18} />
                            : <Key className="me-2" size={18} />}
                        {loading
                            ? __('general.encrypting')
                            : editingNote
                                ? __('general.save_changes')
                                : __('general.encrypt_and_save')}
                    </Button>
                </div>
            </form>
        </div>
    );
}

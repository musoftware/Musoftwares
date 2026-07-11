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
    LayoutGrid,
    List,
    Grid,
    Columns,
    ExternalLink,
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

    const [viewMode, setViewMode] = useState(() => localStorage.getItem('notes_view_mode') || 'card');

    useEffect(() => {
        localStorage.setItem('notes_view_mode', viewMode);
    }, [viewMode]);

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
        return items.map((n) => {
            const decTitle = decryptText(n.title);
            const decContent = decryptText(n.content);
            return {
                ...n,
                decryptedTitle: decTitle,
                decryptedContent: decContent,
                parsed: decContent && !decContent.startsWith('🔒') ? parseNoteContent(decContent, n.category) : null,
            };
        });
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
                        <div className="flex items-center gap-3 shrink-0">
                            <div className="flex items-center gap-1 border border-slate-200 rounded-lg p-0.5 bg-slate-50">
                                <button
                                    type="button"
                                    className={`h-8 w-8 rounded flex items-center justify-center transition-all ${viewMode === 'card' ? 'bg-white text-slate-900 shadow-sm border border-slate-200 font-bold' : 'text-slate-500 hover:text-slate-900'}`}
                                    onClick={() => setViewMode('card')}
                                    title={__('general.card_view') || 'Card View'}
                                >
                                    <LayoutGrid size={15} />
                                </button>
                                <button
                                    type="button"
                                    className={`h-8 w-8 rounded flex items-center justify-center transition-all ${viewMode === 'list' ? 'bg-white text-slate-900 shadow-sm border border-slate-200 font-bold' : 'text-slate-500 hover:text-slate-900'}`}
                                    onClick={() => setViewMode('list')}
                                    title={__('general.list_view') || 'List View'}
                                >
                                    <List size={15} />
                                </button>
                                <button
                                    type="button"
                                    className={`h-8 w-8 rounded flex items-center justify-center transition-all ${viewMode === 'compact' ? 'bg-white text-slate-900 shadow-sm border border-slate-200 font-bold' : 'text-slate-500 hover:text-slate-900'}`}
                                    onClick={() => setViewMode('compact')}
                                    title={__('general.compact_view') || 'Compact Grid'}
                                >
                                    <Grid size={15} />
                                </button>
                                <button
                                    type="button"
                                    className={`h-8 w-8 rounded flex items-center justify-center transition-all ${viewMode === 'split' ? 'bg-white text-slate-900 shadow-sm border border-slate-200 font-bold' : 'text-slate-500 hover:text-slate-900'}`}
                                    onClick={() => setViewMode('split')}
                                    title={__('general.split_view') || 'Split View'}
                                >
                                    <Columns size={15} />
                                </button>
                            </div>
                            <div className="text-sm font-medium text-slate-500">
                                {__('general.total_count', { count: filteredNotes.length })}
                            </div>
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

                    {viewMode === 'card' && (
                        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                            {filteredNotes.map((note) => (
                                <CardViewItem
                                    key={note.id}
                                    note={note}
                                    selectedIds={selectedIds}
                                    toggleSelect={toggleSelect}
                                    isPasswordSet={isPasswordSet}
                                    copiedId={copiedId}
                                    handleCopyCard={handleCopyCard}
                                    handleViewNote={handleViewNote}
                                    handleTogglePin={handleTogglePin}
                                    handleArchive={handleArchive}
                                    handleEditNote={handleEditNote}
                                    handleDelete={handleDelete}
                                    getCategoryIcon={getCategoryIcon}
                                />
                            ))}
                        </div>
                    )}

                    {viewMode === 'compact' && (
                        <div className="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
                            {filteredNotes.map((note) => (
                                <CompactViewItem
                                    key={note.id}
                                    note={note}
                                    selectedIds={selectedIds}
                                    toggleSelect={toggleSelect}
                                    isPasswordSet={isPasswordSet}
                                    handleViewNote={handleViewNote}
                                    handleTogglePin={handleTogglePin}
                                    handleArchive={handleArchive}
                                    handleEditNote={handleEditNote}
                                    handleDelete={handleDelete}
                                    getCategoryIcon={getCategoryIcon}
                                />
                            ))}
                        </div>
                    )}

                    {viewMode === 'list' && (
                        <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                            <div className="overflow-x-auto">
                                <table className="w-full text-left border-collapse text-sm">
                                    <thead>
                                        <tr className="border-b border-slate-200 bg-slate-50 text-slate-550 font-semibold text-xs uppercase">
                                            <th className="p-3 w-10">
                                                <Checkbox
                                                    checked={allSelected}
                                                    onCheckedChange={(checked) => {
                                                        if (checked) {
                                                            setSelectedIds(filteredNotes.map((n) => n.id));
                                                        } else {
                                                            setSelectedIds([]);
                                                        }
                                                    }}
                                                    aria-label={__('general.select_all_notes')}
                                                />
                                            </th>
                                            <th className="p-3">{__('general.title') || 'Title'}</th>
                                            <th className="p-3">{__('general.category') || 'Category'}</th>
                                            <th className="p-3">{__('general.details') || 'Details'}</th>
                                            <th className="p-3">{__('general.created_at') || 'Created At'}</th>
                                            <th className="p-3 text-right">{__('general.actions') || 'Actions'}</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {filteredNotes.map((note) => (
                                            <ListViewRow
                                                key={note.id}
                                                note={note}
                                                selectedIds={selectedIds}
                                                toggleSelect={toggleSelect}
                                                isPasswordSet={isPasswordSet}
                                                copiedId={copiedId}
                                                handleCopyCard={handleCopyCard}
                                                handleViewNote={handleViewNote}
                                                handleTogglePin={handleTogglePin}
                                                handleArchive={handleArchive}
                                                handleEditNote={handleEditNote}
                                                handleDelete={handleDelete}
                                                getCategoryIcon={getCategoryIcon}
                                            />
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}

                    {viewMode === 'split' && (
                        <SplitViewLayout
                            filteredNotes={filteredNotes}
                            selectedIds={selectedIds}
                            toggleSelect={toggleSelect}
                            isPasswordSet={isPasswordSet}
                            copiedId={copiedId}
                            handleCopyCard={handleCopyCard}
                            handleViewNote={handleViewNote}
                            handleTogglePin={handleTogglePin}
                            handleArchive={handleArchive}
                            handleEditNote={handleEditNote}
                            handleDelete={handleDelete}
                            decryptText={decryptText}
                            getCategoryIcon={getCategoryIcon}
                        />
                    )}

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

                    <div className="p-6 relative bg-white space-y-4">
                        {isPasswordSet && selectedNote?.parsed && (
                            <ParsedCredentials
                                parsed={selectedNote.parsed}
                                noteId={selectedNote.id}
                                onCopyCard={handleCopyCard}
                            />
                        )}
                        <div className="relative">
                            <div className="absolute top-2.5 end-2.5 z-10">
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
                                    className="min-h-[200px] font-mono text-sm bg-slate-50/50 border-slate-200 resize-y p-5 pt-12 text-slate-700 leading-relaxed focus-visible:ring-slate-300"
                                />
                            )}
                        </div>
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

// Client-side Credential Parser
const parseNoteContent = (content, category) => {
    if (!content || typeof content !== 'string') return null;
    const lines = content.split('\n');
    const result = {
        username: '',
        password: '',
        url: '',
        anydeskId: '',
        anydeskPassword: '',
        customFields: [],
    };

    const usernameRegex = /^(username|user|login|email|اسم\s*المستخدم|اليوزر|الحساب)\s*[:=]\s*(.+)$/i;
    const passwordRegex = /^(password|pass|pwd|كلمة\s*المرور|الباسورد|الرقم\s*السري)\s*[:=]\s*(.+)$/i;
    const urlRegex = /^(url|link|website|رابط|الموقع)\s*[:=]\s*(.+)$/i;
    const anydeskIdRegex = /^(anydesk\s*id|anydesk|id|العنوان|عنوان\s*اني\s*ديسك)\s*[:=]\s*(.+)$/i;
    const anydeskPasswordRegex = /^(anydesk\s*password|anydesk\s*pass|anydesk\s*pwd)\s*[:=]\s*(.+)$/i;
    const generalKvRegex = /^\s*([^:=]+)\s*[:=]\s*(.+)$/;
    const labelRegex = /^(username|user\s*name|user|password|pass|pwd|server\s*\/\s*host|server|host|port|protocol|anydesk|anydesk\s*id|id|url|link|website|اسم\s*المستخدم|اليوزر|كلمة\s*المرور|الباسورد)\s*[:=]?$/i;

    let hasParsedAnything = false;

    for (let i = 0; i < lines.length; i++) {
        const trimmed = lines[i].trim();
        if (!trimmed) continue;

        let match;
        if ((match = trimmed.match(usernameRegex))) {
            result.username = match[2].trim();
            hasParsedAnything = true;
        } else if ((match = trimmed.match(passwordRegex))) {
            result.password = match[2].trim();
            hasParsedAnything = true;
        } else if ((match = trimmed.match(urlRegex))) {
            result.url = match[2].trim();
            hasParsedAnything = true;
        } else if ((match = trimmed.match(anydeskIdRegex))) {
            result.anydeskId = match[2].trim();
            hasParsedAnything = true;
        } else if ((match = trimmed.match(anydeskPasswordRegex))) {
            result.anydeskPassword = match[2].trim();
            hasParsedAnything = true;
        } else if ((match = trimmed.match(generalKvRegex))) {
            const key = match[1].trim();
            const val = match[2].trim();
            
            // Validate that it's a realistic key-value pair:
            // 1. Key shouldn't be too long (e.g. > 40 chars)
            // 2. Key shouldn't contain URL markers like '://' or '//'
            // 3. Key shouldn't be 'http', 'https', 'ftp'
            const isInvalidKey = 
                key.length > 40 || 
                key.includes('//') || 
                key.includes(':/') || 
                ['http', 'https', 'ftp'].includes(key.toLowerCase());
                
            if (!isInvalidKey) {
                result.customFields.push({ key, val });
                hasParsedAnything = true;
            }
        } else if (i < lines.length - 1) {
            // Check if the current line is a label and the next line contains the value
            const labelMatch = trimmed.match(labelRegex);
            if (labelMatch) {
                const label = labelMatch[1].trim();
                const nextLineVal = lines[i + 1].trim();
                
                if (nextLineVal && !nextLineVal.match(labelRegex)) {
                    const lowerLabel = label.toLowerCase();
                    if (lowerLabel.includes('anydesk') && lowerLabel.includes('id')) {
                        result.anydeskId = nextLineVal;
                    } else if (lowerLabel.includes('anydesk') && (lowerLabel.includes('pass') || lowerLabel.includes('pwd'))) {
                        result.anydeskPassword = nextLineVal;
                    } else if (lowerLabel.includes('user')) {
                        result.username = nextLineVal;
                    } else if (lowerLabel.includes('pass') || lowerLabel.includes('pwd')) {
                        result.password = nextLineVal;
                    } else if (lowerLabel.includes('url') || lowerLabel.includes('link') || lowerLabel.includes('website')) {
                        result.url = nextLineVal;
                    } else {
                        result.customFields.push({ key: trimmed, val: nextLineVal });
                    }
                    
                    hasParsedAnything = true;
                    // Skip the next line as it was consumed as a value
                    i++;
                }
            }
        }

        // Extract URL if the line contains a link and we don't have a URL set yet
        const currentCheckVal = lines[i] ? lines[i].trim() : '';
        if (!result.url && currentCheckVal) {
            const urlMatch = currentCheckVal.match(/((?:https?:)?\/\/[^\s]+|www\.[^\s]+)/i);
            if (urlMatch) {
                let parsedUrl = urlMatch[1];
                if (parsedUrl.startsWith('//')) {
                    parsedUrl = 'https:' + parsedUrl;
                } else if (parsedUrl.toLowerCase().startsWith('www.')) {
                    parsedUrl = 'https://' + parsedUrl;
                }
                if (parsedUrl.endsWith(':')) {
                    parsedUrl = parsedUrl.slice(0, -1);
                }
                result.url = parsedUrl;
                hasParsedAnything = true;
            } else {
                // Check if it looks like a raw domain/hostname (e.g. sub.domain.com)
                const hostMatch = currentCheckVal.match(/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/i);
                if (hostMatch) {
                    result.url = 'https://' + currentCheckVal;
                    hasParsedAnything = true;
                }
            }
        }
    }

    if (!hasParsedAnything && content.trim()) {
        const nonEmptyLines = lines.map(l => l.trim()).filter(Boolean);
        if (category === 'password') {
            if (nonEmptyLines.length === 1) {
                result.password = nonEmptyLines[0];
                hasParsedAnything = true;
            } else if (nonEmptyLines.length === 2) {
                result.username = nonEmptyLines[0];
                result.password = nonEmptyLines[1];
                hasParsedAnything = true;
            }
        } else if (category === 'anydesk') {
            if (nonEmptyLines.length === 1) {
                result.anydeskId = nonEmptyLines[0];
                hasParsedAnything = true;
            } else if (nonEmptyLines.length === 2) {
                result.anydeskId = nonEmptyLines[0];
                result.anydeskPassword = nonEmptyLines[1];
                hasParsedAnything = true;
            }
        }
    }

    return hasParsedAnything ? result : null;
};

// Parsed Credentials Widget Component
function ParsedCredentials({ parsed, noteId, onCopyCard, showLabels = true, compact = false }) {
    const [revealMap, setRevealMap] = useState({});

    if (!parsed) return null;

    const toggleReveal = (field) => {
        setRevealMap(prev => ({ ...prev, [field]: !prev[field] }));
    };

    const renderField = (label, val, isSecret = false, isLink = false, fieldKey) => {
        if (!val) return null;
        const isRevealed = revealMap[fieldKey];

        return (
            <div className={`flex items-center justify-between gap-2 border-b border-slate-100 last:border-0 pb-1.5 mb-1.5 last:pb-0 last:mb-0 ${compact ? 'text-xs' : 'text-sm'}`}>
                <div className="flex-1 min-w-0">
                    {showLabels && (
                        <span className="text-[10px] uppercase tracking-wider text-slate-400 font-bold block mb-0.5">
                            {label}
                        </span>
                    )}
                    {isSecret ? (
                        <span className="font-mono text-slate-700 break-all select-all font-semibold">
                            {isRevealed ? val : '••••••••'}
                        </span>
                    ) : isLink ? (
                        <a
                            href={val.startsWith('http') ? val : `https://${val}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-slate-900 hover:text-black hover:underline inline-flex items-center gap-1 font-mono font-semibold truncate max-w-full"
                        >
                            {val} <ExternalLink size={12} className="inline opacity-60" />
                        </a>
                    ) : (
                        <span className="font-mono text-slate-700 break-all font-semibold select-all">
                            {val}
                        </span>
                    )}
                </div>
                <div className="flex items-center gap-1 shrink-0">
                    {isSecret && (
                        <button
                            type="button"
                            onClick={() => toggleReveal(fieldKey)}
                            className="p-1 rounded hover:bg-slate-200/60 text-slate-400 hover:text-slate-700 transition"
                            title={isRevealed ? __('general.hide') || 'Hide' : __('general.show') || 'Show'}
                        >
                            {isRevealed ? <EyeOff size={13} /> : <Eye size={13} />}
                        </button>
                    )}
                    <button
                        type="button"
                        onClick={() => onCopyCard(val, `${noteId}-${fieldKey}`)}
                        className="p-1 rounded hover:bg-slate-200/60 text-slate-400 hover:text-slate-700 transition"
                        title={__('general.copy_text') || 'Copy Text'}
                    >
                        <Copy size={13} />
                    </button>
                </div>
            </div>
        );
    };

    return (
        <div className="bg-slate-50 border border-slate-200 rounded-lg p-3 w-full text-left">
            {renderField(__('general.username') || 'Username', parsed.username, false, false, 'username')}
            {renderField(__('general.password') || 'Password', parsed.password, true, false, 'password')}
            {renderField(__('general.url') || 'URL', parsed.url, false, true, 'url')}
            {renderField(__('general.anydesk_id') || 'AnyDesk ID', parsed.anydeskId, false, false, 'anydeskId')}
            {renderField(__('general.anydesk_password') || 'AnyDesk Password', parsed.anydeskPassword, true, false, 'anydeskPassword')}
            {parsed.customFields && parsed.customFields.map((f, i) =>
                renderField(
                    f.key,
                    f.val,
                    f.key.toLowerCase().includes('pass') || f.key.toLowerCase().includes('secret') || f.key.toLowerCase().includes('key') || f.key.toLowerCase().includes('token'),
                    f.val.startsWith('http') || f.val.includes('.com') || f.val.includes('.net'),
                    `custom-${i}`
                )
            )}
        </div>
    );
}

// Layout components
function CardViewItem({
    note,
    selectedIds,
    toggleSelect,
    isPasswordSet,
    copiedId,
    handleCopyCard,
    handleViewNote,
    handleTogglePin,
    handleArchive,
    handleEditNote,
    handleDelete,
    getCategoryIcon,
}) {
    return (
        <article
            className={`bg-white p-5 rounded-xl border shadow-sm transition hover:shadow-md flex flex-col justify-between ${
                selectedIds.includes(note.id)
                    ? 'border-slate-900 ring-2 ring-slate-900/20'
                    : 'border-slate-200'
            }`}
        >
            <div>
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
                        <IconButton
                            title={note.is_pinned ? __('general.unpin') : __('general.pin')}
                            active={note.is_pinned}
                            onClick={() => handleTogglePin(note.id)}
                        >
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

                {isPasswordSet && note.parsed ? (
                    <div className="mb-3">
                        <ParsedCredentials
                            parsed={note.parsed}
                            noteId={note.id}
                            onCopyCard={handleCopyCard}
                            compact
                        />
                    </div>
                ) : null}
            </div>

            <div className="prose prose-sm max-w-none text-slate-600 bg-slate-50 p-4 rounded-lg border border-slate-100 flex justify-between items-center gap-3 mt-auto">
                {isPasswordSet && note.decryptedContent !== null && !String(note.decryptedContent).startsWith('🔒') ? (
                    <div className="flex items-center justify-between flex-1 min-w-0 gap-2">
                        <span className="text-slate-500 line-clamp-1 font-mono text-xs overflow-hidden text-ellipsis whitespace-nowrap">
                            {note.decryptedContent}
                        </span>
                        <button
                            type="button"
                            title={__('general.copy_text')}
                            onClick={() => handleCopyCard(note.decryptedContent, note.id)}
                            className={`p-1 rounded hover:bg-slate-200 text-slate-400 hover:text-slate-700 shrink-0 transition-colors ${
                                copiedId === note.id ? 'text-green-600 hover:text-green-700' : ''
                            }`}
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
                    <Button
                        variant="outline"
                        size="sm"
                        className="ms-2 shrink-0 shadow-sm border-slate-200 bg-white"
                        onClick={() => handleViewNote(note)}
                    >
                        <FileText size={14} className="me-2 text-slate-400" />
                        {__('general.view_note')}
                    </Button>
                )}
            </div>
        </article>
    );
}

function CompactViewItem({
    note,
    selectedIds,
    toggleSelect,
    isPasswordSet,
    handleViewNote,
    handleTogglePin,
    handleArchive,
    handleEditNote,
    handleDelete,
    getCategoryIcon,
}) {
    return (
        <article
            className={`bg-white p-3 rounded-lg border shadow-sm transition hover:shadow-md flex flex-col justify-between ${
                selectedIds.includes(note.id)
                    ? 'border-slate-900 ring-2 ring-slate-900/20'
                    : 'border-slate-200'
            }`}
        >
            <div className="flex items-start gap-2 mb-2 justify-between">
                <div className="flex items-center gap-1.5 min-w-0">
                    <Checkbox
                        checked={selectedIds.includes(note.id)}
                        onCheckedChange={() => toggleSelect(note.id)}
                        aria-label={__('general.select_note')}
                    />
                    <div className="text-slate-700 truncate font-semibold text-sm" title={note.decryptedTitle}>
                        {note.decryptedTitle || note.title}
                    </div>
                </div>
                <div className="flex items-center gap-0.5 shrink-0">
                    {note.is_pinned && <Pin size={10} className="text-slate-800 fill-slate-800" />}
                    <span className="p-0.5 bg-slate-50 border border-slate-200 rounded">
                        {getCategoryIcon(note.category)}
                    </span>
                </div>
            </div>

            <div className="flex justify-between items-center mt-2 pt-2 border-t border-slate-100">
                <span className="text-[10px] text-slate-400">
                    {new Date(note.created_at).toLocaleDateString()}
                </span>
                <div className="flex items-center gap-1">
                    {isPasswordSet && note.decryptedContent && !String(note.decryptedContent).startsWith('🔒') ? (
                        <button
                            type="button"
                            onClick={() => handleViewNote(note)}
                            className="p-1 rounded text-slate-600 hover:bg-slate-100 transition"
                            title={__('general.view_note')}
                        >
                            <Eye size={12} />
                        </button>
                    ) : (
                        <Key size={12} className="text-slate-300" />
                    )}
                    <button
                        type="button"
                        onClick={() => handleEditNote(note)}
                        className="p-1 rounded text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition"
                        title={__('general.edit')}
                    >
                        <Pencil size={12} />
                    </button>
                    <button
                        type="button"
                        onClick={() => handleDelete(note.id)}
                        className="p-1 rounded text-red-400 hover:text-red-650 hover:bg-red-50 transition"
                        title={__('general.delete')}
                    >
                        <Trash2 size={12} />
                    </button>
                </div>
            </div>
        </article>
    );
}

function ListViewRow({
    note,
    selectedIds,
    toggleSelect,
    isPasswordSet,
    copiedId,
    handleCopyCard,
    handleViewNote,
    handleTogglePin,
    handleArchive,
    handleEditNote,
    handleDelete,
    getCategoryIcon,
}) {
    const [pwdReveal, setPwdReveal] = useState(false);

    const renderInlineParsed = () => {
        if (!isPasswordSet || !note.parsed) {
            return (
                <span className="text-xs text-slate-400 font-medium">
                    {isPasswordSet ? note.decryptedContent || '-' : '[Encrypted]'}
                </span>
            );
        }
        
        const { username, password, anydeskId, anydeskPassword } = note.parsed;
        
        return (
            <div className="flex flex-wrap gap-2 text-xs">
                {username && (
                    <span className="inline-flex items-center gap-1 bg-slate-50 border border-slate-200 px-1.5 py-0.5 rounded text-slate-700">
                        <span className="text-[9px] font-bold text-slate-400 uppercase">U:</span>
                        <span className="font-mono font-semibold">{username}</span>
                        <button
                            type="button"
                            onClick={() => handleCopyCard(username, `${note.id}-u`)}
                            className="text-slate-400 hover:text-slate-600"
                        >
                            {copiedId === `${note.id}-u` ? <Check size={10} /> : <Copy size={10} />}
                        </button>
                    </span>
                )}
                {password && (
                    <span className="inline-flex items-center gap-1 bg-slate-50 border border-slate-200 px-1.5 py-0.5 rounded text-slate-700">
                        <span className="text-[9px] font-bold text-slate-400 uppercase">P:</span>
                        <span className="font-mono font-semibold">{pwdReveal ? password : '••••••••'}</span>
                        <button
                            type="button"
                            onClick={() => setPwdReveal(!pwdReveal)}
                            className="text-slate-400 hover:text-slate-600"
                        >
                            {pwdReveal ? <EyeOff size={10} /> : <Eye size={10} />}
                        </button>
                        <button
                            type="button"
                            onClick={() => handleCopyCard(password, `${note.id}-p`)}
                            className="text-slate-400 hover:text-slate-600"
                        >
                            {copiedId === `${note.id}-p` ? <Check size={10} /> : <Copy size={10} />}
                        </button>
                    </span>
                )}
                {anydeskId && (
                    <span className="inline-flex items-center gap-1 bg-slate-50 border border-slate-200 px-1.5 py-0.5 rounded text-slate-700">
                        <span className="text-[9px] font-bold text-slate-400 uppercase">AD ID:</span>
                        <span className="font-mono font-semibold">{anydeskId}</span>
                        <button
                            type="button"
                            onClick={() => handleCopyCard(anydeskId, `${note.id}-ad-id`)}
                            className="text-slate-400 hover:text-slate-600"
                        >
                            {copiedId === `${note.id}-ad-id` ? <Check size={10} /> : <Copy size={10} />}
                        </button>
                    </span>
                )}
                {anydeskPassword && (
                    <span className="inline-flex items-center gap-1 bg-slate-50 border border-slate-200 px-1.5 py-0.5 rounded text-slate-700">
                        <span className="text-[9px] font-bold text-slate-400 uppercase">AD P:</span>
                        <span className="font-mono font-semibold">{pwdReveal ? anydeskPassword : '••••••••'}</span>
                        <button
                            type="button"
                            onClick={() => setPwdReveal(!pwdReveal)}
                            className="text-slate-400 hover:text-slate-600"
                        >
                            {pwdReveal ? <EyeOff size={10} /> : <Eye size={10} />}
                        </button>
                        <button
                            type="button"
                            onClick={() => handleCopyCard(anydeskPassword, `${note.id}-ad-p`)}
                            className="text-slate-400 hover:text-slate-600"
                        >
                            {copiedId === `${note.id}-ad-p` ? <Check size={10} /> : <Copy size={10} />}
                        </button>
                    </span>
                )}
            </div>
        );
    };

    return (
        <tr className={`hover:bg-slate-50/50 transition-colors ${selectedIds.includes(note.id) ? 'bg-slate-50' : ''}`}>
            <td className="p-3">
                <Checkbox
                    checked={selectedIds.includes(note.id)}
                    onCheckedChange={() => toggleSelect(note.id)}
                    aria-label={__('general.select_note')}
                />
            </td>
            <td className="p-3 font-semibold text-slate-900">
                <div className="flex items-center gap-1.5">
                    {note.is_pinned && <Pin size={12} className="text-slate-800 fill-slate-800 shrink-0" />}
                    <span className="truncate max-w-[200px]" title={note.decryptedTitle}>
                        {note.decryptedTitle || note.title}
                    </span>
                </div>
            </td>
            <td className="p-3">
                <span className="inline-flex items-center gap-1 bg-slate-100 text-slate-700 px-2 py-0.5 rounded text-xs font-medium capitalize border border-slate-200">
                    {getCategoryIcon(note.category)}
                    {note.category}
                </span>
            </td>
            <td className="p-3">
                {renderInlineParsed()}
            </td>
            <td className="p-3 text-slate-400 text-xs">
                {new Date(note.created_at).toLocaleDateString()}
            </td>
            <td className="p-3 text-right">
                <div className="inline-flex items-center gap-1">
                    <IconButton
                        title={note.is_pinned ? __('general.unpin') : __('general.pin')}
                        active={note.is_pinned}
                        onClick={() => handleTogglePin(note.id)}
                    >
                        <Pin size={12} />
                    </IconButton>
                    <IconButton
                        title={
                            note.category === 'archived'
                                ? __('general.unarchive')
                                : __('general.archive')
                        }
                        onClick={() => handleArchive(note.id, note.category)}
                    >
                        {note.category === 'archived' ? <Upload size={12} /> : <Archive size={12} />}
                    </IconButton>
                    <IconButton title={__('general.edit')} onClick={() => handleEditNote(note)}>
                        <Pencil size={12} />
                    </IconButton>
                    <IconButton title={__('general.delete')} danger onClick={() => handleDelete(note.id)}>
                        <Trash2 size={12} />
                    </IconButton>
                    {isPasswordSet && note.decryptedContent && !String(note.decryptedContent).startsWith('🔒') && (
                        <IconButton title={__('general.view_note')} onClick={() => handleViewNote(note)}>
                            <FileText size={12} />
                        </IconButton>
                    )}
                </div>
            </td>
        </tr>
    );
}

function SplitViewLayout({
    filteredNotes,
    selectedIds,
    toggleSelect,
    isPasswordSet,
    copiedId,
    handleCopyCard,
    handleViewNote,
    handleTogglePin,
    handleArchive,
    handleEditNote,
    handleDelete,
    decryptText,
    getCategoryIcon,
}) {
    const [activeId, setActiveId] = useState(null);

    const activeNote = useMemo(() => {
        if (filteredNotes.length === 0) return null;
        return filteredNotes.find(n => n.id === activeId) || filteredNotes[0];
    }, [filteredNotes, activeId]);

    useEffect(() => {
        if (activeNote && activeNote.id !== activeId) {
            setActiveId(activeNote.id);
        }
    }, [activeNote, activeId]);

    const activeDecryptedContent = useMemo(() => {
        if (!activeNote) return '';
        return activeNote.decryptedContent || decryptText(activeNote.content);
    }, [activeNote, decryptText]);

    return (
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-4 border border-slate-200 rounded-xl overflow-hidden shadow-sm bg-white min-h-[500px]">
            {/* Left list pane (4/12 columns) */}
            <div className="lg:col-span-4 border-r border-slate-200 flex flex-col bg-slate-50/50">
                <div className="p-3 border-b border-slate-200 bg-white font-bold text-xs uppercase tracking-wider text-slate-500 flex justify-between items-center">
                    <span>{__('general.notes_list') || 'Notes List'}</span>
                    <span className="bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded text-[10px]">
                        {filteredNotes.length}
                    </span>
                </div>
                <div className="overflow-y-auto flex-1 max-h-[500px]">
                    {filteredNotes.map((note) => (
                        <button
                            key={note.id}
                            type="button"
                            onClick={() => setActiveId(note.id)}
                            className={`w-full text-left p-3.5 border-b border-slate-100 transition-colors flex flex-col gap-1.5 ${
                                activeId === note.id ? 'bg-white border-l-4 border-l-slate-900 shadow-sm font-semibold' : 'hover:bg-slate-100/50'
                            }`}
                        >
                            <div className="flex justify-between items-start gap-2 w-full">
                                <div className="flex items-center gap-1.5 min-w-0">
                                    {note.is_pinned && <Pin size={11} className="text-slate-800 fill-slate-800 shrink-0" />}
                                    <span className="font-semibold text-sm text-slate-900 truncate">
                                        {note.decryptedTitle || note.title}
                                    </span>
                                </div>
                                <span className="bg-slate-200/70 text-slate-700 px-1.5 py-0.5 rounded text-[10px] uppercase font-bold shrink-0">
                                    {note.category}
                                </span>
                            </div>
                            <div className="flex justify-between items-center text-[10px] text-slate-400 w-full">
                                <span>{new Date(note.created_at).toLocaleDateString()}</span>
                                {note.is_expired && <span className="text-red-500 font-bold">{__('general.expired')}</span>}
                            </div>
                        </button>
                    ))}
                    {filteredNotes.length === 0 && (
                        <div className="p-8 text-center text-slate-400 text-xs">
                            {__('general.no_notes_found_1')}
                        </div>
                    )}
                </div>
            </div>

            {/* Right detail pane (8/12 columns) */}
            <div className="lg:col-span-8 flex flex-col min-w-0 bg-white">
                {activeNote ? (
                    <div className="p-6 flex flex-col h-full justify-between">
                        <div>
                            {/* Header */}
                            <div className="flex justify-between items-start border-b border-slate-100 pb-4 mb-4 gap-4">
                                <div>
                                    <div className="flex items-center gap-2 flex-wrap mb-1">
                                        <span className="bg-slate-900 text-white px-2 py-0.5 rounded text-xs font-semibold uppercase tracking-wider flex items-center gap-1">
                                            {getCategoryIcon(activeNote.category)}
                                            {activeNote.category}
                                        </span>
                                        {activeNote.is_pinned && (
                                            <span className="bg-slate-100 border border-slate-200 text-slate-800 px-2 py-0.5 rounded text-xs font-semibold">
                                                {__('general.pinned')}
                                            </span>
                                        )}
                                        {activeNote.is_expired && (
                                            <span className="bg-red-100 text-red-700 px-2 py-0.5 rounded text-xs font-bold uppercase tracking-wider">
                                                {__('general.expired')}
                                            </span>
                                        )}
                                    </div>
                                    <h2 className="text-xl font-bold text-slate-900 break-words">
                                        {activeNote.decryptedTitle || activeNote.title}
                                    </h2>
                                    <p className="text-xs text-slate-400 font-medium mt-1">
                                        {__('general.created_at')}: {new Date(activeNote.created_at).toLocaleString()}
                                        {activeNote.author?.name ? ` · Author: ${activeNote.author.name}` : ''}
                                    </p>
                                </div>
                                <div className="flex items-center gap-1.5 shrink-0">
                                    <IconButton
                                        title={activeNote.is_pinned ? __('general.unpin') : __('general.pin')}
                                        active={activeNote.is_pinned}
                                        onClick={() => handleTogglePin(activeNote.id)}
                                    >
                                        <Pin size={14} />
                                    </IconButton>
                                    <IconButton
                                        title={
                                            activeNote.category === 'archived'
                                                ? __('general.unarchive')
                                                : __('general.archive')
                                        }
                                        onClick={() => handleArchive(activeNote.id, activeNote.category)}
                                    >
                                        {activeNote.category === 'archived' ? <Upload size={14} /> : <Archive size={14} />}
                                    </IconButton>
                                    <IconButton title={__('general.edit')} onClick={() => handleEditNote(activeNote)}>
                                        <Pencil size={14} />
                                    </IconButton>
                                    <IconButton title={__('general.delete')} danger onClick={() => handleDelete(activeNote.id)}>
                                        <Trash2 size={14} />
                                    </IconButton>
                                </div>
                            </div>

                            {/* Parsed credentials block if present */}
                            {isPasswordSet && activeNote.parsed && (
                                <div className="mb-4">
                                    <h4 className="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
                                        {__('general.parsed_credentials') || 'Parsed Credentials'}
                                    </h4>
                                    <ParsedCredentials
                                        parsed={activeNote.parsed}
                                        noteId={activeNote.id}
                                        onCopyCard={handleCopyCard}
                                    />
                                </div>
                            )}

                            {/* Full Secure Content Textarea */}
                            <div>
                                <h4 className="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
                                    {__('general.secure_content') || 'Secure Content'}
                                </h4>
                                <div className="relative">
                                    <div className="absolute top-2 end-2 z-10">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            className="h-8 px-3 shadow-sm bg-white hover:bg-slate-50 text-slate-600"
                                            onClick={() => handleCopyCard(activeDecryptedContent, activeNote.id)}
                                        >
                                            {copiedId === activeNote.id ? (
                                                <><Check size={14} className="me-1.5 text-green-600" /> {__('general.copied')}</>
                                            ) : (
                                                <><Copy size={14} className="me-1.5" /> {__('general.copy_text')}</>
                                            )}
                                        </Button>
                                    </div>
                                    <textarea
                                        readOnly
                                        value={
                                            !isPasswordSet
                                                ? __('general.unlock_to_view_content')
                                                : (activeDecryptedContent ?? __('general.encrypted_content_hidden'))
                                        }
                                        className="w-full min-h-[180px] font-mono text-sm bg-slate-50 border border-slate-200 rounded-lg p-4 pt-12 text-slate-700 resize-none outline-none focus:ring-0"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                ) : (
                    <div className="flex-1 flex flex-col items-center justify-center p-12 text-slate-400 text-sm">
                        <FileText size={48} className="mb-3 text-slate-200" />
                        <span>{__('general.select_note_to_view_details') || 'Select a note from the left to view details'}</span>
                    </div>
                )}
            </div>
        </div>
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
                        className="w-full border-slate-300 rounded-lg text-sm focus:border-slate-900 focus:ring-slate-900 bg-slate-50 px-3 py-2"
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
                        className="w-full border-slate-300 rounded-lg shadow-sm text-sm focus:border-slate-900 focus:ring-slate-900 font-mono bg-slate-50 p-3"
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

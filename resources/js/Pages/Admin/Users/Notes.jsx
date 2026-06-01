import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ShieldAlert, ShieldCheck, Key, Monitor, FileText, Archive, Pin, Trash2, Upload, RefreshCw } from 'lucide-react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import SimpleCrypto from 'simple-crypto-js';
import { marked } from 'marked';
import DOMPurify from 'dompurify';

export default function Notes({ user, notes: initialNotes, stats }) {
    const [notes, setNotes] = useState(initialNotes || []);
    const [title, setTitle] = useState('');
    const [content, setContent] = useState('');
    const [category, setCategory] = useState('notes');
    const [loading, setLoading] = useState(false);
    
    const [password, setPassword] = useState('');
    const [isPasswordSet, setIsPasswordSet] = useState(false);
    const [cryptoInstance, setCryptoInstance] = useState(null);

    // Filter states
    const [filterCategory, setFilterCategory] = useState('all');
    const [searchQuery, setSearchQuery] = useState('');

    useEffect(() => {
        const savedPwd = sessionStorage.getItem('pwd');
        if (savedPwd) {
            setPassword(savedPwd);
            setIsPasswordSet(true);
            setCryptoInstance(new SimpleCrypto(savedPwd));
        }
    }, []);

    const handleSetPassword = (e) => {
        e.preventDefault();
        if (password.trim()) {
            sessionStorage.setItem('pwd', password);
            setIsPasswordSet(true);
            setCryptoInstance(new SimpleCrypto(password));
        }
    };

    const handleClearPassword = () => {
        sessionStorage.removeItem('pwd');
        setPassword('');
        setIsPasswordSet(false);
        setCryptoInstance(null);
    };

    const decryptText = (text) => {
        if (!text || !cryptoInstance) return text;
        // Check if it looks like a SimpleCrypto cipher (length usually > 50 and no spaces if fully encrypted)
        if (text.includes(' ') && text.length < 50) return text; 
        
        try {
            const dec = cryptoInstance.decrypt(text);
            return dec ? dec.toString() : text;
        } catch (e) {
            return "🔒 [Encrypted Data - Invalid Password]";
        }
    };

    const encryptText = (text) => {
        if (!text || !cryptoInstance) return text;
        return cryptoInstance.encrypt(text.trim());
    };

    const handleAddNote = (e) => {
        e.preventDefault();
        if (!isPasswordSet || !cryptoInstance) {
            alert('Please set your encryption password first.');
            return;
        }

        setLoading(true);

        const cipherTitle = encryptText(title);
        const cipherContent = encryptText(content);

        router.post(`/admin/users/${user.id}/notes`, {
            title: cipherTitle,
            content: cipherContent,
            category
        }, {
            preserveScroll: true,
            onSuccess: (page) => {
                setNotes(page.props.notes);
                setTitle('');
                setContent('');
                setLoading(false);
            },
            onError: () => setLoading(false)
        });
    };

    const handleTogglePin = (noteId) => {
        window.axios.post(`/admin/users/${user.id}/notes/${noteId}/pin`)
            .then(res => {
                if (res.data.success) {
                    router.reload({ only: ['notes', 'stats'] });
                }
            });
    };

    const handleArchive = (noteId, currentCategory) => {
        const isArchived = currentCategory === 'archived';
        const url = isArchived 
            ? `/admin/users/${user.id}/notes/${noteId}/unarchive`
            : `/admin/users/${user.id}/notes/${noteId}/archive`;
            
        router.post(url, {}, { preserveScroll: true });
    };

    const handleDelete = (noteId) => {
        if (!confirm("Are you sure you want to delete this note permanently?")) return;
        router.delete(`/admin/users/${user.id}/notes/${noteId}`, { preserveScroll: true });
    };

    const renderMarkdown = (text) => {
        const html = marked(text);
        return { __html: DOMPurify.sanitize(html) };
    };

    const getCategoryIcon = (cat) => {
        switch (cat) {
            case 'password': return <Key size={14} className="text-amber-500" />;
            case 'anydesk': return <Monitor size={14} className="text-blue-500" />;
            case 'archived': return <Archive size={14} className="text-gray-500" />;
            default: return <FileText size={14} className="text-slate-500" />;
        }
    };

    // Derived notes
    let filteredNotes = notes.map(n => ({
        ...n,
        decryptedTitle: decryptText(n.title),
        decryptedContent: decryptText(n.content)
    }));

    if (filterCategory !== 'all') {
        filteredNotes = filteredNotes.filter(n => n.category === filterCategory);
    }
    
    if (searchQuery.trim()) {
        const q = searchQuery.toLowerCase();
        filteredNotes = filteredNotes.filter(n => 
            (n.decryptedTitle && n.decryptedTitle.toLowerCase().includes(q)) || 
            (n.decryptedContent && n.decryptedContent.toLowerCase().includes(q))
        );
    }

    return (
        <AdminSidebarLayout title={`Secure Notes: ${user.name}`} header="Secure Notes">
            <Head title={`Secure Notes - ${user.name}`} />

            <div className="flex justify-between items-center mb-6">
                <div>
                    <h1 className="text-3xl font-bold font-sora">{__('general.secure_notes')}</h1>
                    <p className="text-slate-500">Manage encrypted notes for {user.name}</p>
                </div>
                <div className="flex space-x-2">
                    <Link href={`/admin/users/${user.id}/files`} className="bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 px-4 py-2 rounded-lg text-sm font-semibold transition">
                        Files
                    </Link>
                    <Link href={`/admin/users/${user.id}`} className="bg-slate-900 text-white hover:bg-slate-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
                        Profile
                    </Link>
                </div>
            </div>

            {/* Security Banner */}
            <div className={`p-4 rounded-xl border mb-6 flex items-start gap-4 ${isPasswordSet ? 'bg-green-50 border-green-200' : 'bg-amber-50 border-amber-200'}`}>
                {isPasswordSet ? <ShieldCheck className="text-green-600 shrink-0 mt-1" size={24} /> : <ShieldAlert className="text-amber-600 shrink-0 mt-1" size={24} />}
                <div className="flex-1">
                    <h4 className={`font-bold mb-1 ${isPasswordSet ? 'text-green-900' : 'text-amber-900'}`}>
                        {isPasswordSet ? 'End-to-End Encryption Active' : 'Encryption Password Required'}
                    </h4>
                    <p className={`text-sm mb-0 ${isPasswordSet ? 'text-green-700' : 'text-amber-700'}`}>{__('general.all_notes_are_encrypted_client_side_before_being_saved_the_server_cannot_read_your_data')}</p>
                </div>
                <div>
                    {!isPasswordSet ? (
                        <form onSubmit={handleSetPassword} className="flex gap-2">
                            <Input 
                                type="password" 
                                placeholder={__('general.master_password')} 
                                value={password} 
                                onChange={e => setPassword(e.target.value)} 
                                className="w-48 bg-white border-amber-300"
                                required
                            />
                            <Button type="submit" className="bg-amber-600 hover:bg-amber-700 text-white">Unlock</Button>
                        </form>
                    ) : (
                        <Button variant="outline" onClick={handleClearPassword} className="border-green-300 text-green-700 hover:bg-green-100">{__('general.lock_clear_session')}</Button>
                    )}
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                {/* Notes List & Filters */}
                <div className="lg:col-span-2 space-y-4">
                    <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-wrap gap-4 items-center justify-between">
                        <div className="flex items-center gap-4 flex-1">
                            <Input 
                                placeholder={__('general.search_decrypted_notes')} 
                                value={searchQuery}
                                onChange={e => setSearchQuery(e.target.value)}
                                className="max-w-xs"
                                disabled={!isPasswordSet}
                            />
                            <select 
                                className="border-slate-300 rounded-md text-sm focus:border-slate-900 focus:ring-slate-900"
                                value={filterCategory}
                                onChange={e => setFilterCategory(e.target.value)}
                            >
                                <option value="all">{__('general.all_categories')}</option>
                                <option value="password">Passwords</option>
                                <option value="anydesk">AnyDesk</option>
                                <option value="notes">{__('general.general_notes')}</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                        <div className="text-sm font-medium text-slate-500">
                            Total: {filteredNotes.length}
                        </div>
                    </div>

                    <div className="space-y-4">
                        {filteredNotes.map(note => (
                            <div key={note.id} className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm transition hover:shadow-md">
                                <div className="flex justify-between items-start mb-3">
                                    <div className="flex flex-col">
                                        <div className="flex items-center gap-2 mb-1">
                                            {note.is_pinned && <span className="bg-slate-800 text-white px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">Pinned</span>}
                                            <span className="bg-slate-100 text-slate-700 px-2 py-0.5 rounded text-xs font-medium capitalize flex items-center gap-1 border border-slate-200">
                                                {getCategoryIcon(note.category)}
                                                {note.category}
                                            </span>
                                            <span className="text-xs text-slate-400 font-medium">
                                                {new Date(note.created_at).toLocaleString()}
                                            </span>
                                        </div>
                                        {note.decryptedTitle && <h3 className="text-lg font-bold text-slate-900">{note.decryptedTitle}</h3>}
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <button onClick={() => handleTogglePin(note.id)} className={`p-1.5 rounded-md border transition ${note.is_pinned ? 'bg-slate-100 border-slate-300 text-slate-700' : 'border-slate-200 text-slate-400 hover:text-slate-700'}`} title={note.is_pinned ? "Unpin" : "Pin"}>
                                            <Pin size={14} />
                                        </button>
                                        <button onClick={() => handleArchive(note.id, note.category)} className="p-1.5 rounded-md border border-slate-200 text-slate-400 hover:text-slate-700 transition" title={note.category === 'archived' ? 'Unarchive' : 'Archive'}>
                                            {note.category === 'archived' ? <Upload size={14} /> : <Archive size={14} />}
                                        </button>
                                        <button onClick={() => handleDelete(note.id)} className="p-1.5 rounded-md border border-red-100 bg-red-50 text-red-500 hover:bg-red-100 transition" title="Delete">
                                            <Trash2 size={14} />
                                        </button>
                                    </div>
                                </div>
                                <div className="prose prose-sm max-w-none text-slate-600 bg-slate-50 p-4 rounded-lg border border-slate-100">
                                    {isPasswordSet ? (
                                        note.decryptedContent.startsWith('🔒') 
                                            ? <span className="text-red-500 font-medium"><Key size={14} className="inline mr-1"/> {note.decryptedContent}</span>
                                            : <div dangerouslySetInnerHTML={renderMarkdown(note.decryptedContent)} />
                                    ) : (
                                        <div className="flex items-center justify-center py-4 text-slate-400 font-medium text-sm">
                                            <Key size={16} className="mr-2" />{__('general.encrypted_content_hidden')}</div>
                                    )}
                                </div>
                            </div>
                        ))}
                        {filteredNotes.length === 0 && (
                            <div className="bg-white p-12 rounded-xl border border-slate-200 text-center flex flex-col items-center">
                                <div className="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-300 mb-4">
                                    <FileText size={32} />
                                </div>
                                <h3 className="text-lg font-bold text-slate-900 mb-1">{__('general.no_notes_found_1')}</h3>
                                <p className="text-slate-500 text-sm">
                                    {isPasswordSet ? "Create a new note or adjust your filters." : "Unlock with your master password to view notes."}
                                </p>
                            </div>
                        )}
                    </div>
                </div>

                {/* Create Note Form */}
                <div className="lg:col-span-1">
                    <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm sticky top-6">
                        <div className="flex items-center gap-3 mb-6 border-b pb-4">
                            <div className="w-10 h-10 rounded-lg bg-slate-900 text-white flex items-center justify-center">
                                <ShieldCheck size={20} />
                            </div>
                            <div>
                                <h3 className="font-bold text-slate-900 font-sora">{__('general.create_note')}</h3>
                                <p className="text-xs text-slate-500">{__('general.encrypted_before_saving')}</p>
                            </div>
                        </div>

                        <form onSubmit={handleAddNote} className="space-y-4">
                            <div>
                                <Label htmlFor="category" className="text-slate-700 font-bold mb-1.5 block">Category</Label>
                                <select 
                                    id="category"
                                    className="w-full border-slate-300 rounded-lg text-sm focus:border-slate-900 focus:ring-slate-900 bg-slate-50"
                                    value={category} 
                                    onChange={e => setCategory(e.target.value)} 
                                    disabled={loading || !isPasswordSet}
                                >
                                    <option value="password">Password</option>
                                    <option value="notes">Notes</option>
                                    <option value="anydesk">Anydesk</option>
                                </select>
                            </div>

                            <div>
                                <Label htmlFor="title" className="text-slate-700 font-bold mb-1.5 block">Title</Label>
                                <Input
                                    id="title"
                                    type="text"
                                    className="w-full border-slate-300 rounded-lg shadow-sm text-sm focus:border-slate-900 focus:ring-slate-900 bg-slate-50"
                                    placeholder={__('general.e_g_database_credentials')}
                                    value={title}
                                    onChange={e => setTitle(e.target.value)}
                                    required
                                    disabled={loading || !isPasswordSet}
                                />
                            </div>

                            <div>
                                <Label htmlFor="content" className="text-slate-700 font-bold mb-1.5 flex justify-between">
                                    <span>{__('general.secure_content')}</span>
                                    <span className="text-[10px] font-bold uppercase tracking-wider text-green-600 bg-green-50 px-2 py-0.5 rounded border border-green-200 flex items-center gap-1">
                                        <Key size={10} /> Encrypted
                                    </span>
                                </Label>
                                <textarea
                                    id="content"
                                    className="w-full border-slate-300 rounded-lg shadow-sm text-sm focus:border-slate-900 focus:ring-slate-900 font-mono bg-slate-50"
                                    rows={8}
                                    placeholder={__('general.enter_sensitive_information_here')}
                                    value={content}
                                    onChange={e => setContent(e.target.value)}
                                    required
                                    disabled={loading || !isPasswordSet}
                                />
                            </div>

                            <Button 
                                type="submit" 
                                disabled={loading || !isPasswordSet || !content.trim() || !title.trim()} 
                                className="w-full py-6 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-lg shadow-sm text-base transition-all"
                            >
                                {loading ? <RefreshCw className="animate-spin mr-2" size={18} /> : <Key className="mr-2" size={18} />}
                                {loading ? 'Encrypting...' : 'Encrypt & Save Note'}
                            </Button>
                        </form>
                    </div>
                </div>

            </div>
        </AdminSidebarLayout>
    );
}

import React, { useState, useEffect, useRef } from 'react';
import { Contact, Search, Plus, Trash2, Upload, Download, Tag, X, ChevronLeft, ChevronRight, Users, Building2, Phone, StickyNote, AlertCircle, FolderOpen, Folder, FolderPlus } from 'lucide-react';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';

interface ContactItem {
    id: number;
    phone_number: string;
    name: string;
    company: string;
    tags: string[];
    notes: string;
    source: string;
    folder_id: number | null;
    created_at: string;
}

interface ContactFolder {
    id: number;
    name: string;
    icon: string;
    color: string;
    source: string;
    contact_count: number;
    created_at: string;
}

export default function ContactsWorkspace({ t, locale, callRPC, daemonConnected }: any) {
    const isRtl = locale === 'ar';
    const [contacts, setContacts] = useState<ContactItem[]>([]);
    const [total, setTotal] = useState(0);
    const [pages, setPages] = useState(1);
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const [activeTag, setActiveTag] = useState('');
    const [tags, setTags] = useState<{ name: string; count: number }[]>([]);
    const [loading, setLoading] = useState(false);
    const [selected, setSelected] = useState<Set<number>>(new Set());

    // Folders
    const [folders, setFolders] = useState<ContactFolder[]>([]);
    const [activeFolder, setActiveFolder] = useState<number | null>(null);

    // Form state
    const [showForm, setShowForm] = useState(false);
    const [editContact, setEditContact] = useState<ContactItem | null>(null);
    const [formPhone, setFormPhone] = useState('');
    const [formName, setFormName] = useState('');
    const [formCompany, setFormCompany] = useState('');
    const [formTags, setFormTags] = useState('');
    const [formNotes, setFormNotes] = useState('');

    // Import state
    const [showImport, setShowImport] = useState(false);
    const [importText, setImportText] = useState('');
    const fileInputRef = useRef<HTMLInputElement>(null);

    const fetchContacts = async () => {
        if (!daemonConnected) return;
        setLoading(true);
        try {
            const res: any = await callRPC('getContacts', { 
                search, 
                tag: activeTag, 
                folderId: activeFolder,
                page, 
                limit: 50 
            });
            setContacts(res.contacts || []);
            setTotal(res.total || 0);
            setPages(res.pages || 1);
        } catch (err: any) {
            console.error('Failed to fetch contacts:', err);
        }
        setLoading(false);
    };

    const fetchTags = async () => {
        try {
            const res: any = await callRPC('getTags', {});
            setTags(res.tags || []);
        } catch (_) {}
    };

    const fetchFolders = async () => {
        try {
            const res: any = await callRPC('getContactFolders', {});
            setFolders(res.folders || []);
        } catch (_) {}
    };

    useEffect(() => {
        if (daemonConnected) { fetchContacts(); fetchTags(); fetchFolders(); }
    }, [daemonConnected, page, search, activeTag, activeFolder]);

    // Debounce search
    const searchTimeoutRef = useRef<any>(null);
    const handleSearch = (val: string) => {
        setSearch(val);
        setPage(1);
        if (searchTimeoutRef.current) clearTimeout(searchTimeoutRef.current);
        searchTimeoutRef.current = setTimeout(() => fetchContacts(), 300);
    };

    const resetForm = () => {
        setFormPhone(''); setFormName(''); setFormCompany(''); setFormTags(''); setFormNotes('');
        setEditContact(null); setShowForm(false);
    };

    const handleEdit = (c: ContactItem) => {
        setFormPhone(c.phone_number);
        setFormName(c.name);
        setFormCompany(c.company || '');
        setFormTags(c.tags?.join(', ') || '');
        setFormNotes(c.notes || '');
        setEditContact(c);
        setShowForm(true);
    };

    const handleSave = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!formPhone.trim()) { alert(isRtl ? 'رقم الهاتف مطلوب' : 'Phone number required'); return; }
        try {
            await callRPC('saveContact', {
                id: editContact?.id || undefined,
                phone_number: formPhone.trim(),
                name: formName.trim(),
                company: formCompany.trim(),
                tags: formTags.split(',').map(t => t.trim()).filter(Boolean),
                notes: formNotes.trim(),
                source: 'manual'
            });
            resetForm();
            fetchContacts();
            fetchTags();
        } catch (err: any) {
            alert(`Save failed: ${err.message}`);
        }
    };

    const handleDelete = async (id: number) => {
        if (!confirm(isRtl ? 'حذف جهة الاتصال؟' : 'Delete contact?')) return;
        try {
            await callRPC('deleteContact', { id });
            fetchContacts();
        } catch (err: any) { alert(`Delete failed: ${err.message}`); }
    };

    const handleBulkDelete = async () => {
        if (selected.size === 0) return;
        if (!confirm(isRtl ? `حذف ${selected.size} جهة اتصال؟` : `Delete ${selected.size} contacts?`)) return;
        try {
            await callRPC('deleteContacts', { ids: Array.from(selected) });
            setSelected(new Set());
            fetchContacts();
        } catch (err: any) { alert(`Bulk delete failed: ${err.message}`); }
    };

    const handleBulkTag = async () => {
        const tag = prompt(isRtl ? 'أدخل اسم العلامة:' : 'Enter tag name:');
        if (!tag || selected.size === 0) return;
        try {
            await callRPC('bulkTag', { ids: Array.from(selected), tag: tag.trim() });
            setSelected(new Set());
            fetchContacts();
            fetchTags();
        } catch (err: any) { alert(`Tag failed: ${err.message}`); }
    };

    const handleDeleteFolder = async (folderId: number, e: React.MouseEvent) => {
        e.stopPropagation();
        if (!confirm(isRtl ? 'حذف المجلد؟ (جهات الاتصال لن تحذف)' : 'Delete folder? (contacts will be kept)')) return;
        try {
            await callRPC('deleteContactFolder', { folderId });
            if (activeFolder === folderId) setActiveFolder(null);
            fetchFolders();
            fetchContacts();
        } catch (err: any) { alert(`Delete failed: ${err.message}`); }
    };

    const handleImport = async () => {
        if (!importText.trim()) return;
        const lines = importText.split('\n').filter(l => l.trim());
        const contacts = lines.map(line => {
            const parts = line.split(',').map(p => p.trim());
            return {
                phone_number: parts[0] || '',
                name: parts[1] || '',
                company: parts[2] || '',
                tags: parts[3] || ''
            };
        }).filter(c => c.phone_number);

        try {
            const res: any = await callRPC('importContacts', { contacts, source: 'import' });
            alert(isRtl ? `تم استيراد ${res.imported} جهة اتصال (${res.skipped} مكررة)` : `Imported ${res.imported} contacts (${res.skipped} skipped)`);
            setShowImport(false);
            setImportText('');
            fetchContacts();
            fetchTags();
        } catch (err: any) { alert(`Import failed: ${err.message}`); }
    };

    const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (ev) => {
            setImportText(ev.target?.result as string || '');
            setShowImport(true);
        };
        reader.readAsText(file);
    };

    const handleExport = async () => {
        try {
            const res: any = await callRPC('exportContacts', { tag: activeTag });
            const csv = ['phone_number,name,company,tags,notes']
                .concat((res.contacts || []).map((c: any) => `${c.phone_number},${c.name},${c.company},${c.tags},${c.notes}`))
                .join('\n');
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `contacts_${Date.now()}.csv`;
            a.click();
            URL.revokeObjectURL(url);
        } catch (err: any) { alert(`Export failed: ${err.message}`); }
    };

    const toggleSelect = (id: number) => {
        setSelected(prev => {
            const next = new Set(prev);
            next.has(id) ? next.delete(id) : next.add(id);
            return next;
        });
    };

    const toggleSelectAll = () => {
        if (selected.size === contacts.length) {
            setSelected(new Set());
        } else {
            setSelected(new Set(contacts.map(c => c.id)));
        }
    };

    const activeFolderName = folders.find(f => f.id === activeFolder)?.name || null;

    return (
        <div className="space-y-6 animate-in fade-in duration-300">
            {/* Header */}
            <div className="flex items-center justify-between flex-wrap gap-3">
                <div className="text-start">
                    <h2 className="text-xl font-bold tracking-tight flex items-center gap-2">
                        <div className="size-9 rounded-xl bg-blue-100/60 dark:bg-blue-950/40 flex items-center justify-center">
                            <Contact className="w-5 h-5 text-blue-600" />
                        </div>
                        {isRtl ? 'جهات الاتصال' : 'Contacts'}
                        <Badge variant="secondary" className="text-xs font-bold">{total}</Badge>
                    </h2>
                    <p className="text-sm text-muted-foreground mt-1">
                        {isRtl ? 'إدارة جهات اتصالك واستيراد وتصدير' : 'Manage, import, and export your contacts'}
                    </p>
                </div>
                <div className="flex gap-2">
                    <input ref={fileInputRef} type="file" accept=".csv,.txt" className="hidden" onChange={handleFileUpload} />
                    <Button variant="outline" onClick={() => fileInputRef.current?.click()} className="rounded-xl gap-1.5 text-xs font-bold">
                        <Upload className="w-3.5 h-3.5" /> {isRtl ? 'استيراد' : 'Import'}
                    </Button>
                    <Button variant="outline" onClick={handleExport} className="rounded-xl gap-1.5 text-xs font-bold">
                        <Download className="w-3.5 h-3.5" /> {isRtl ? 'تصدير' : 'Export'}
                    </Button>
                    <Button onClick={() => { resetForm(); setShowForm(true); }} className="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl gap-1.5 text-xs">
                        <Plus className="w-3.5 h-3.5" /> {isRtl ? 'جديد' : 'New'}
                    </Button>
                </div>
            </div>

            {/* ── Folders Strip ──────────────────────────────────────────── */}
            {folders.length > 0 && (
                <div className="space-y-2">
                    <h3 className="text-xs font-bold text-muted-foreground flex items-center gap-1.5">
                        <FolderOpen className="w-3.5 h-3.5" />
                        {isRtl ? 'المجلدات' : 'Folders'}
                    </h3>
                    <div className="flex gap-2 flex-wrap">
                        {/* All Contacts button */}
                        <button
                            onClick={() => { setActiveFolder(null); setPage(1); }}
                            className={`flex items-center gap-2 px-4 py-2.5 rounded-xl border text-xs font-bold transition-all duration-200 ${
                                activeFolder === null 
                                    ? 'bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-600/20' 
                                    : 'bg-muted/30 hover:bg-muted/60 border-border text-foreground'
                            }`}
                        >
                            <Users className="w-4 h-4" />
                            <span>{isRtl ? 'الكل' : 'All'}</span>
                        </button>
                        
                        {folders.map(folder => (
                            <div
                                key={folder.id}
                                role="button"
                                tabIndex={0}
                                onClick={() => { setActiveFolder(activeFolder === folder.id ? null : folder.id); setPage(1); }}
                                className={`group flex items-center gap-2 px-4 py-2.5 rounded-xl border text-xs font-bold transition-all duration-200 relative cursor-pointer select-none ${
                                    activeFolder === folder.id 
                                        ? 'bg-emerald-600 text-white border-emerald-600 shadow-md shadow-emerald-600/20' 
                                        : 'bg-muted/30 hover:bg-muted/60 border-border text-foreground'
                                }`}
                            >
                                <span className="text-base">{folder.icon}</span>
                                <span>{folder.name}</span>
                                <Badge 
                                    variant="secondary" 
                                    className={`text-[9px] px-1.5 py-0 h-4 font-bold ${
                                        activeFolder === folder.id 
                                            ? 'bg-white/20 text-white' 
                                            : 'bg-muted text-muted-foreground'
                                    }`}
                                >
                                    {folder.contact_count}
                                </Badge>
                                <span
                                    role="button"
                                    onClick={(e) => handleDeleteFolder(folder.id, e)}
                                    className={`ml-1 rounded-full p-0.5 transition-opacity cursor-pointer ${
                                        activeFolder === folder.id 
                                            ? 'opacity-60 hover:opacity-100 text-white' 
                                            : 'opacity-0 group-hover:opacity-60 hover:!opacity-100 text-destructive'
                                    }`}
                                >
                                    <X className="w-3 h-3" />
                                </span>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* Active folder indicator */}
            {activeFolderName && (
                <div className="flex items-center gap-2 px-3 py-2 bg-emerald-50/50 dark:bg-emerald-950/20 rounded-xl border border-emerald-200/50 dark:border-emerald-800/30">
                    <Folder className="w-4 h-4 text-emerald-600" />
                    <span className="text-xs font-bold text-emerald-700 dark:text-emerald-400">
                        {isRtl ? `عرض مجلد: ${activeFolderName}` : `Viewing folder: ${activeFolderName}`}
                    </span>
                    <button onClick={() => { setActiveFolder(null); setPage(1); }} className="ml-auto">
                        <X className="w-3.5 h-3.5 text-emerald-600 hover:text-emerald-800" />
                    </button>
                </div>
            )}

            {/* Search & Tags */}
            <div className="flex flex-col sm:flex-row gap-3">
                <div className="relative flex-1">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                    <Input
                        value={search}
                        onChange={e => handleSearch(e.target.value)}
                        placeholder={isRtl ? 'بحث بالاسم أو الرقم أو الشركة...' : 'Search by name, phone, or company...'}
                        className="pl-10 rounded-xl text-start"
                    />
                </div>
                {tags.length > 0 && (
                    <div className="flex items-center gap-1.5 flex-wrap">
                        {activeTag && (
                            <Badge
                                variant="default"
                                className="cursor-pointer bg-blue-600 text-white gap-1 rounded-lg"
                                onClick={() => { setActiveTag(''); setPage(1); }}
                            >
                                {activeTag} <X className="w-3 h-3" />
                            </Badge>
                        )}
                        {tags.filter(t => t.name !== activeTag).slice(0, 6).map(tag => (
                            <Badge
                                key={tag.name}
                                variant="outline"
                                className="cursor-pointer hover:bg-muted rounded-lg text-xs"
                                onClick={() => { setActiveTag(tag.name); setPage(1); }}
                            >
                                <Tag className="w-3 h-3 mr-1" />{tag.name} ({tag.count})
                            </Badge>
                        ))}
                    </div>
                )}
            </div>

            {/* Import Modal */}
            {showImport && (
                <Card className="rounded-2xl border-blue-200/50 dark:border-blue-800/30 animate-in slide-in-from-top-2 duration-300">
                    <CardHeader className="pb-3 border-b">
                        <CardTitle className="text-sm flex items-center gap-2">
                            <Upload className="w-4 h-4 text-blue-600" />
                            {isRtl ? 'استيراد جهات الاتصال' : 'Import Contacts'}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-4 space-y-3 text-start">
                        <p className="text-xs text-muted-foreground">
                            {isRtl ? 'صيغة: رقم,اسم,شركة,علامات (سطر لكل جهة اتصال)' : 'Format: phone,name,company,tags (one per line)'}
                        </p>
                        <Textarea
                            value={importText}
                            onChange={e => setImportText(e.target.value)}
                            placeholder="201012345678,Ahmed,Company,vip&#10;201098765432,Sara,Agency,lead"
                            className="h-32 resize-none font-mono text-xs rounded-xl text-start"
                        />
                        <div className="flex gap-2">
                            <Button onClick={handleImport} className="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs">
                                {isRtl ? 'استيراد' : 'Import'}
                            </Button>
                            <Button variant="outline" onClick={() => setShowImport(false)} className="rounded-xl text-xs">
                                {isRtl ? 'إلغاء' : 'Cancel'}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* Add/Edit Form */}
            {showForm && (
                <Card className="rounded-2xl border-blue-200/50 dark:border-blue-800/30 animate-in slide-in-from-top-2 duration-300">
                    <CardHeader className="pb-3 border-b">
                        <CardTitle className="text-sm">
                            {editContact ? (isRtl ? 'تعديل جهة الاتصال' : 'Edit Contact') : (isRtl ? 'جهة اتصال جديدة' : 'New Contact')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-4">
                        <form onSubmit={handleSave} className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-start">
                            <div className="space-y-2">
                                <Label className="flex items-center gap-1"><Phone className="w-3 h-3" /> {isRtl ? 'رقم الهاتف' : 'Phone'}</Label>
                                <Input value={formPhone} onChange={e => setFormPhone(e.target.value)} placeholder="201012345678" className="rounded-xl font-mono text-sm text-start" required />
                            </div>
                            <div className="space-y-2">
                                <Label className="flex items-center gap-1"><Users className="w-3 h-3" /> {isRtl ? 'الاسم' : 'Name'}</Label>
                                <Input value={formName} onChange={e => setFormName(e.target.value)} placeholder={isRtl ? 'أحمد' : 'Ahmed'} className="rounded-xl text-start" />
                            </div>
                            <div className="space-y-2">
                                <Label className="flex items-center gap-1"><Building2 className="w-3 h-3" /> {isRtl ? 'الشركة' : 'Company'}</Label>
                                <Input value={formCompany} onChange={e => setFormCompany(e.target.value)} placeholder={isRtl ? 'اختياري' : 'Optional'} className="rounded-xl text-start" />
                            </div>
                            <div className="space-y-2">
                                <Label className="flex items-center gap-1"><Tag className="w-3 h-3" /> {isRtl ? 'العلامات' : 'Tags'}</Label>
                                <Input value={formTags} onChange={e => setFormTags(e.target.value)} placeholder="VIP, Lead, Customer" className="rounded-xl text-start text-xs" />
                            </div>
                            <div className="space-y-2 sm:col-span-2">
                                <Label className="flex items-center gap-1"><StickyNote className="w-3 h-3" /> {isRtl ? 'ملاحظات' : 'Notes'}</Label>
                                <Textarea value={formNotes} onChange={e => setFormNotes(e.target.value)} placeholder={isRtl ? 'اختياري' : 'Optional'} className="h-16 resize-none rounded-xl text-start text-xs" />
                            </div>
                            <div className="sm:col-span-2 flex gap-2">
                                <Button type="submit" className="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs flex-1">
                                    {editContact ? (isRtl ? 'حفظ' : 'Save') : (isRtl ? 'إضافة' : 'Add')}
                                </Button>
                                <Button type="button" variant="outline" onClick={resetForm} className="rounded-xl text-xs">{isRtl ? 'إلغاء' : 'Cancel'}</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            )}

            {/* Bulk Actions */}
            {selected.size > 0 && (
                <div className="flex items-center gap-3 p-3 bg-blue-50/80 dark:bg-blue-950/20 rounded-2xl border border-blue-200/50 dark:border-blue-800/30 animate-in slide-in-from-top-1 duration-200">
                    <Badge className="bg-blue-600 text-white font-bold">{selected.size} {isRtl ? 'محدد' : 'selected'}</Badge>
                    <Button variant="outline" size="sm" onClick={handleBulkTag} className="rounded-lg text-xs gap-1 font-bold">
                        <Tag className="w-3 h-3" /> {isRtl ? 'تصنيف' : 'Tag'}
                    </Button>
                    <Button variant="outline" size="sm" onClick={handleBulkDelete} className="rounded-lg text-xs gap-1 font-bold text-destructive border-destructive/30 hover:bg-destructive/10">
                        <Trash2 className="w-3 h-3" /> {isRtl ? 'حذف' : 'Delete'}
                    </Button>
                    <Button variant="ghost" size="sm" onClick={() => setSelected(new Set())} className="rounded-lg text-xs">
                        {isRtl ? 'إلغاء التحديد' : 'Clear'}
                    </Button>
                </div>
            )}

            {/* Contacts Table */}
            <Card className="rounded-2xl text-start overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b bg-muted/30">
                                <th className="p-3 text-start w-10">
                                    <input type="checkbox" checked={selected.size === contacts.length && contacts.length > 0} onChange={toggleSelectAll} className="rounded" />
                                </th>
                                <th className="p-3 text-start font-bold text-xs text-muted-foreground">{isRtl ? 'الهاتف' : 'Phone'}</th>
                                <th className="p-3 text-start font-bold text-xs text-muted-foreground">{isRtl ? 'الاسم' : 'Name'}</th>
                                <th className="p-3 text-start font-bold text-xs text-muted-foreground hidden md:table-cell">{isRtl ? 'الشركة' : 'Company'}</th>
                                <th className="p-3 text-start font-bold text-xs text-muted-foreground hidden lg:table-cell">{isRtl ? 'المجلد' : 'Folder'}</th>
                                <th className="p-3 text-start font-bold text-xs text-muted-foreground hidden lg:table-cell">{isRtl ? 'العلامات' : 'Tags'}</th>
                                <th className="p-3 text-start font-bold text-xs text-muted-foreground w-20"></th>
                            </tr>
                        </thead>
                        <tbody>
                            {contacts.map(c => {
                                const contactFolder = folders.find(f => f.id === c.folder_id);
                                return (
                                    <tr key={c.id} className="border-b hover:bg-muted/20 transition-colors">
                                        <td className="p-3">
                                            <input type="checkbox" checked={selected.has(c.id)} onChange={() => toggleSelect(c.id)} className="rounded" />
                                        </td>
                                        <td className="p-3 font-mono text-xs">{c.phone_number}</td>
                                        <td className="p-3 font-medium text-xs">{c.name || <span className="text-muted-foreground">—</span>}</td>
                                        <td className="p-3 text-xs text-muted-foreground hidden md:table-cell">{c.company || '—'}</td>
                                        <td className="p-3 hidden lg:table-cell">
                                            {contactFolder ? (
                                                <Badge 
                                                    variant="outline" 
                                                    className="text-[9px] px-1.5 py-0 h-4 rounded gap-1 cursor-pointer hover:bg-emerald-50 dark:hover:bg-emerald-950/20 border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400"
                                                    onClick={() => { setActiveFolder(contactFolder.id); setPage(1); }}
                                                >
                                                    <span>{contactFolder.icon}</span> {contactFolder.name}
                                                </Badge>
                                            ) : (
                                                <span className="text-muted-foreground text-[10px]">—</span>
                                            )}
                                        </td>
                                        <td className="p-3 hidden lg:table-cell">
                                            <div className="flex gap-1 flex-wrap">
                                                {c.tags?.map(tag => (
                                                    <Badge key={tag} variant="outline" className="text-[9px] px-1.5 py-0 h-4 rounded">{tag}</Badge>
                                                ))}
                                            </div>
                                        </td>
                                        <td className="p-3">
                                            <div className="flex gap-1">
                                                <Button variant="ghost" size="icon" className="h-7 w-7 rounded-lg" onClick={() => handleEdit(c)}>
                                                    <StickyNote className="w-3 h-3" />
                                                </Button>
                                                <Button variant="ghost" size="icon" className="h-7 w-7 rounded-lg text-destructive" onClick={() => handleDelete(c.id)}>
                                                    <Trash2 className="w-3 h-3" />
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
                {contacts.length === 0 && !loading && (
                    <div className="flex flex-col items-center justify-center py-16 text-center">
                        <div className="size-16 rounded-2xl bg-blue-50/50 dark:bg-blue-950/20 flex items-center justify-center mb-4">
                            <Contact className="w-7 h-7 text-blue-400/50" />
                        </div>
                        <p className="text-sm font-bold">{isRtl ? 'لا توجد جهات اتصال' : 'No contacts yet'}</p>
                        <p className="text-xs text-muted-foreground mt-1">
                            {activeFolder 
                                ? (isRtl ? 'هذا المجلد فارغ' : 'This folder is empty')
                                : (isRtl ? 'أضف جهات اتصال يدوياً أو استورد ملف CSV' : 'Add contacts manually or import a CSV file')
                            }
                        </p>
                    </div>
                )}

                {/* Pagination */}
                {pages > 1 && (
                    <div className="flex items-center justify-between p-4 border-t">
                        <p className="text-xs text-muted-foreground">
                            {isRtl ? `صفحة ${page} من ${pages}` : `Page ${page} of ${pages}`} · {total} {isRtl ? 'جهة اتصال' : 'contacts'}
                        </p>
                        <div className="flex gap-1">
                            <Button variant="outline" size="icon" className="h-8 w-8 rounded-lg" disabled={page <= 1} onClick={() => setPage(p => p - 1)}>
                                <ChevronLeft className="w-4 h-4" />
                            </Button>
                            <Button variant="outline" size="icon" className="h-8 w-8 rounded-lg" disabled={page >= pages} onClick={() => setPage(p => p + 1)}>
                                <ChevronRight className="w-4 h-4" />
                            </Button>
                        </div>
                    </div>
                )}
            </Card>
        </div>
    );
}

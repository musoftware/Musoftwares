import React, { useState, useMemo } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Switch } from '@/Components/ui/switch';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogDescription,
} from '@/Components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { toastSuccess, toastError } from '@/Components/ui/use-toast';
import { __ } from '@/lib/i18n';
import {
    Plus,
    Search,
    Copy,
    Check,
    Pencil,
    Trash2,
    Eye,
    Terminal,
    Sparkles,
    MoreVertical,
    Layers,
    Bookmark,
    SlidersHorizontal,
    TrendingUp,
    FolderKanban,
    FilterX,
} from 'lucide-react';

// ─── Interfaces ─────────────────────────────────────────────────────────────

interface PromptUser {
    id: number;
    name: string;
    email: string;
}

interface PromptItem {
    id: number;
    title: string;
    category: string;
    prompt: string;
    description: string | null;
    is_featured: boolean;
    copy_count: number;
    tags: string[] | null;
    user_id: number | null;
    user?: PromptUser | null;
    created_at: string;
    updated_at: string;
}

interface PromptPagination {
    data: PromptItem[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface PromptStats {
    total_prompts: number;
    total_copies: number;
    featured_count: number;
    categories_count: number;
}

interface Props {
    prompts: PromptPagination;
    categoryCounts: Record<string, number>;
    filters: {
        search?: string;
        category?: string;
        sort?: string;
    };
    stats: PromptStats;
}

const COMMON_CATEGORIES = [
    'UI',
    'Backend',
    'Security',
    'Database',
    'DevOps',
    'Architecture',
    'AI & Prompts',
    'Testing',
];

export default function PromptGallery({
    prompts,
    categoryCounts,
    filters,
    stats,
}: Props) {
    // ─── Local State ────────────────────────────────────────────────────────
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [selectedCategory, setSelectedCategory] = useState(filters.category || 'all');
    const [sortBy, setSortBy] = useState(filters.sort || 'latest');

    // Copying state tracking (prompt id -> copied boolean)
    const [copiedId, setCopiedId] = useState<number | null>(null);

    // Modal dialogs
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [editingPrompt, setEditingPrompt] = useState<PromptItem | null>(null);
    const [viewingPrompt, setViewingPrompt] = useState<PromptItem | null>(null);
    const [deletingPrompt, setDeletingPrompt] = useState<PromptItem | null>(null);

    // Inertia form for create & edit
    const form = useForm({
        title: '',
        category: 'UI',
        description: '',
        prompt: '',
        is_featured: false,
        tags_input: '',
    });

    // ─── Search & Category Filtering ────────────────────────────────────────
    const filteredPrompts = useMemo(() => {
        let list = prompts.data || [];

        if (selectedCategory && selectedCategory !== 'all') {
            list = list.filter((p) => p.category.toLowerCase() === selectedCategory.toLowerCase());
        }

        if (searchQuery.trim()) {
            const query = searchQuery.toLowerCase().trim();
            list = list.filter(
                (p) =>
                    p.title.toLowerCase().includes(query) ||
                    p.category.toLowerCase().includes(query) ||
                    p.prompt.toLowerCase().includes(query) ||
                    (p.description && p.description.toLowerCase().includes(query)) ||
                    (p.tags && p.tags.some((t) => t.toLowerCase().includes(query)))
            );
        }

        if (sortBy === 'popular') {
            list = [...list].sort((a, b) => b.copy_count - a.copy_count);
        } else if (sortBy === 'title') {
            list = [...list].sort((a, b) => a.title.localeCompare(b.title));
        }

        return list;
    }, [prompts.data, selectedCategory, searchQuery, sortBy]);

    // ─── Copy to Clipboard Handler ──────────────────────────────────────────
    const handleCopy = async (promptItem: PromptItem) => {
        try {
            if (navigator?.clipboard?.writeText) {
                await navigator.clipboard.writeText(promptItem.prompt);
            } else {
                // Fallback for older contexts
                const textarea = document.createElement('textarea');
                textarea.value = promptItem.prompt;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
            }

            setCopiedId(promptItem.id);
            setTimeout(() => setCopiedId(null), 2200);

            // Increment backend count seamlessly
            const token = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content;
            if (token) {
                fetch(route('admin.prompts.copy', promptItem.id), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        Accept: 'application/json',
                    },
                })
                    .then((res) => res.json())
                    .then((data) => {
                        if (data?.copy_count) {
                            promptItem.copy_count = data.copy_count;
                        }
                    })
                    .catch(() => {});
            }

            toastSuccess(__('admin.prompts_copied_toast', { title: promptItem.title }), __('admin.copied_to_clipboard'));
        } catch {
            toastError(__('admin.prompts_copy_failed'));
        }
    };

    // ─── Modal Actions ──────────────────────────────────────────────────────
    const openCreateDialog = () => {
        form.reset();
        form.setData({
            title: '',
            category: 'UI',
            description: '',
            prompt: '',
            is_featured: false,
            tags_input: '',
        });
        form.clearErrors();
        setIsCreateOpen(true);
    };

    const openEditDialog = (item: PromptItem) => {
        setEditingPrompt(item);
        form.setData({
            title: item.title,
            category: item.category,
            description: item.description || '',
            prompt: item.prompt,
            is_featured: item.is_featured,
            tags_input: item.tags ? item.tags.join(', ') : '',
        });
        form.clearErrors();
    };

    const handleCreateSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const tags = form.data.tags_input
            .split(',')
            .map((t) => t.trim())
            .filter((t) => t.length > 0);

        form.transform((data) => ({
            ...data,
            tags,
        }));

        form.post(route('admin.prompts.store'), {
            preserveScroll: true,
            onSuccess: () => {
                setIsCreateOpen(false);
                form.reset();
                toastSuccess(__('admin.prompts_created'));
            },
            onError: () => {
                toastError(__('admin.prompts_fix_form_errors'));
            },
        });
    };

    const handleEditSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingPrompt) return;

        const tags = form.data.tags_input
            .split(',')
            .map((t) => t.trim())
            .filter((t) => t.length > 0);

        form.transform((data) => ({
            ...data,
            tags,
        }));

        form.put(route('admin.prompts.update', editingPrompt.id), {
            preserveScroll: true,
            onSuccess: () => {
                setEditingPrompt(null);
                form.reset();
                toastSuccess(__('admin.prompts_updated'));
            },
            onError: () => {
                toastError(__('admin.prompts_fix_form_errors'));
            },
        });
    };

    const handleDelete = () => {
        if (!deletingPrompt) return;

        router.delete(route('admin.prompts.destroy', deletingPrompt.id), {
            preserveScroll: true,
            onSuccess: () => {
                setDeletingPrompt(null);
                toastSuccess(__('admin.prompts_deleted'));
            },
            onError: () => {
                toastError(__('admin.prompts_delete_failed'));
            },
        });
    };

    return (
        <AdminSidebarLayout
            title={__('admin.prompts_gallery')}
            header={
                <div className="flex items-center gap-3">
                    <div className="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 dark:border-white/10 bg-slate-100 dark:bg-zinc-800 text-slate-900 dark:text-slate-100">
                        <Terminal className="h-5 w-5" />
                    </div>
                    <div>
                        <h1 className="text-lg font-bold tracking-tight text-slate-900 dark:text-white">
                            {__('admin.prompts_gallery')}
                        </h1>
                        <p className="text-xs text-slate-500 dark:text-slate-400">
                            {__('admin.prompts_gallery_subheading')}
                        </p>
                    </div>
                </div>
            }
            actions={
                <Button
                    onClick={openCreateDialog}
                    className="h-9 gap-2 bg-slate-900 hover:bg-black text-white dark:bg-white dark:hover:bg-slate-100 dark:text-slate-900 font-medium shadow-sm transition-all"
                >
                    <Plus className="h-4 w-4" />
                    <span>{__('admin.prompts_new_prompt')}</span>
                </Button>
            }
        >
            <Head title={__('admin.prompts_gallery')} />

            <div className="space-y-6">
                {/* ─── Metric Summary Strip ─────────────────────────────────────── */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div className="p-4 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-zinc-900/70 shadow-xs">
                        <div className="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-1">
                            <span className="text-xs font-medium uppercase tracking-wider">{__('admin.prompts_total_prompts')}</span>
                            <FolderKanban className="h-4 w-4 text-slate-400" />
                        </div>
                        <div className="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                            {stats.total_prompts}
                        </div>
                    </div>

                    <div className="p-4 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-zinc-900/70 shadow-xs">
                        <div className="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-1">
                            <span className="text-xs font-medium uppercase tracking-wider">{__('admin.prompts_total_copies')}</span>
                            <TrendingUp className="h-4 w-4 text-slate-400" />
                        </div>
                        <div className="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                            {stats.total_copies}
                        </div>
                    </div>

                    <div className="p-4 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-zinc-900/70 shadow-xs">
                        <div className="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-1">
                            <span className="text-xs font-medium uppercase tracking-wider">{__('admin.prompts_featured_prompts')}</span>
                            <Bookmark className="h-4 w-4 text-slate-400" />
                        </div>
                        <div className="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                            {stats.featured_count}
                        </div>
                    </div>

                    <div className="p-4 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-zinc-900/70 shadow-xs">
                        <div className="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-1">
                            <span className="text-xs font-medium uppercase tracking-wider">{__('general.categories')}</span>
                            <Layers className="h-4 w-4 text-slate-400" />
                        </div>
                        <div className="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                            {stats.categories_count}
                        </div>
                    </div>
                </div>

                {/* ─── Search Bar & Controls ───────────────────────────────────── */}
                <div className="rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-zinc-900/70 p-4 space-y-4 shadow-xs">
                    <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        <div className="relative flex-1">
                            <Search className="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                            <Input
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                placeholder={__('admin.prompts_search_placeholder')}
                                className="pl-10 h-10 bg-slate-50/70 dark:bg-zinc-800/50 border-slate-200 dark:border-white/10 text-sm focus-visible:ring-1 focus-visible:ring-slate-400 dark:focus-visible:ring-white/20"
                            />
                            {searchQuery && (
                                <button
                                    type="button"
                                    onClick={() => setSearchQuery('')}
                                    aria-label={__('admin.prompts_clear_search')}
                                    className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 text-xs font-medium"
                                >
                                    {__('general.clear')}
                                </button>
                            )}
                        </div>

                        {/* Sort Selector */}
                        <div className="flex items-center gap-2 shrink-0">
                            <SlidersHorizontal className="h-4 w-4 text-slate-400 shrink-0" />
                            <select
                                value={sortBy}
                                onChange={(e) => setSortBy(e.target.value)}
                                aria-label={__('admin.sort_by')}
                                className="h-10 px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-200 dark:border-white/10 bg-white dark:bg-zinc-800 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-slate-400"
                            >
                                <option value="latest">{__('admin.prompts_sort_latest')}</option>
                                <option value="popular">{__('admin.prompts_sort_popular')}</option>
                                <option value="title">{__('admin.prompts_sort_title')}</option>
                            </select>
                        </div>
                    </div>

                    {/* Category Filter Tabs */}
                    <div className="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none text-xs">
                        <button
                            type="button"
                            onClick={() => setSelectedCategory('all')}
                            className={`px-3 py-1.5 rounded-lg font-medium transition-all shrink-0 ${
                                selectedCategory === 'all'
                                    ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 shadow-xs'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200/80 dark:bg-zinc-800 dark:text-slate-300 dark:hover:bg-zinc-700'
                            }`}
                        >
                            {__('admin.prompts_all_count', { count: stats.total_prompts })}
                        </button>

                        {Object.entries(categoryCounts).map(([catName, count]) => {
                            const isSelected = selectedCategory.toLowerCase() === catName.toLowerCase();
                            return (
                                <button
                                    key={catName}
                                    type="button"
                                    onClick={() => setSelectedCategory(isSelected ? 'all' : catName)}
                                    className={`px-3 py-1.5 rounded-lg font-medium transition-all shrink-0 flex items-center gap-1.5 ${
                                        isSelected
                                            ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 shadow-xs'
                                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200/80 dark:bg-zinc-800 dark:text-slate-300 dark:hover:bg-zinc-700'
                                    }`}
                                >
                                    <span>{catName}</span>
                                    <span
                                        className={`text-[10px] px-1.5 py-0.2 rounded-full ${
                                            isSelected
                                                ? 'bg-white/20 text-white dark:bg-slate-900/20 dark:text-slate-900'
                                                : 'bg-slate-200/80 text-slate-600 dark:bg-zinc-700 dark:text-slate-300'
                                        }`}
                                    >
                                        {count}
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                </div>

                {/* ─── Prompts Grid ────────────────────────────────────────────── */}
                {filteredPrompts.length === 0 ? (
                    <div className="rounded-2xl border border-dashed border-slate-300 dark:border-white/10 bg-white/50 dark:bg-zinc-900/30 p-12 text-center">
                        <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-400 mb-4">
                            <FilterX className="h-6 w-6" />
                        </div>
                        <h3 className="text-base font-semibold text-slate-900 dark:text-white">
                            {__('admin.prompts_empty_title')}
                        </h3>
                        <p className="mt-1 text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
                            {searchQuery || selectedCategory !== 'all'
                                ? __('admin.prompts_empty_filtered')
                                : __('admin.prompts_empty_gallery')}
                        </p>
                        <div className="mt-5 flex justify-center gap-3">
                            {(searchQuery || selectedCategory !== 'all') && (
                                <Button
                                    variant="outline"
                                    onClick={() => {
                                        setSearchQuery('');
                                        setSelectedCategory('all');
                                    }}
                                    className="text-xs"
                                >
                                    {__('admin.clear_filters')}
                                </Button>
                            )}
                            <Button onClick={openCreateDialog} className="text-xs gap-1.5">
                                <Plus className="h-3.5 w-3.5" />
                                {__('admin.prompts_add_prompt')}
                            </Button>
                        </div>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                        {filteredPrompts.map((item) => {
                            const isCopied = copiedId === item.id;
                            return (
                                <div
                                    key={item.id}
                                    className="group relative flex flex-col justify-between rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-zinc-900/80 p-5 shadow-xs hover:border-slate-300 dark:hover:border-white/20 hover:shadow-md transition-all duration-200"
                                >
                                    {/* Card Header */}
                                    <div>
                                        <div className="flex items-start justify-between gap-3 mb-2.5">
                                            <div className="flex items-center gap-2 flex-wrap">
                                                <span className="text-[11px] font-semibold uppercase tracking-wider px-2 py-0.5 rounded-md bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-white/5">
                                                    {item.category}
                                                </span>
                                                {item.is_featured && (
                                                    <span className="flex items-center gap-1 text-[11px] font-medium text-slate-800 dark:text-slate-200">
                                                        <Sparkles className="h-3 w-3" />
                                                        {__('admin.featured')}
                                                    </span>
                                                )}
                                            </div>

                                            {/* Action Dropdown Menu */}
                                            <DropdownMenu>
                                                <DropdownMenuTrigger aria-label={__('general.actions')} className="p-1 rounded-md text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors">
                                                    <MoreVertical className="h-4 w-4" />
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent
                                                    align="end"
                                                    className="w-36 rounded-lg border border-slate-200 dark:border-white/10 bg-white dark:bg-zinc-900 p-1 shadow-lg"
                                                >
                                                    <DropdownMenuItem
                                                        onClick={() => setViewingPrompt(item)}
                                                        className="gap-2 text-xs cursor-pointer text-slate-700 dark:text-slate-300"
                                                    >
                                                        <Eye className="h-3.5 w-3.5" />
                                                        {__('admin.prompts_view_full')}
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        onClick={() => openEditDialog(item)}
                                                        className="gap-2 text-xs cursor-pointer text-slate-700 dark:text-slate-300"
                                                    >
                                                        <Pencil className="h-3.5 w-3.5" />
                                                        {__('admin.prompts_edit_prompt')}
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        onClick={() => setDeletingPrompt(item)}
                                                        className="gap-2 text-xs cursor-pointer text-red-600 dark:text-red-400 focus:bg-red-50 dark:focus:bg-red-950/30"
                                                    >
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                        {__('general.delete')}
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </div>

                                        {/* Title */}
                                        <h3 className="text-base font-bold text-slate-900 dark:text-white leading-snug line-clamp-1">
                                            {item.title}
                                        </h3>

                                        {/* Description */}
                                        {item.description && (
                                            <p className="mt-1 text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
                                                {item.description}
                                            </p>
                                        )}

                                        {/* Truncated Prompt Preview */}
                                        <div
                                            onClick={() => setViewingPrompt(item)}
                                            className="mt-3.5 relative rounded-lg border border-slate-200/90 dark:border-white/10 bg-slate-50 dark:bg-black/40 p-3 font-mono text-[11.5px] leading-relaxed text-slate-700 dark:text-slate-300 cursor-pointer hover:border-slate-300 dark:hover:border-white/20 transition-all select-none"
                                            title={__('admin.prompts_click_to_view_full')}
                                        >
                                            <div className="line-clamp-4 whitespace-pre-line font-mono">
                                                {item.prompt}
                                            </div>
                                            <div className="mt-2 flex items-center justify-between text-[10px] text-slate-400 dark:text-slate-500 pt-2 border-t border-slate-200/60 dark:border-white/5 font-sans">
                                                <span>{__('admin.prompts_click_to_inspect')}</span>
                                                <Eye className="h-3 w-3" />
                                            </div>
                                        </div>

                                        {/* Tags */}
                                        {item.tags && item.tags.length > 0 && (
                                            <div className="mt-3 flex flex-wrap gap-1">
                                                {item.tags.slice(0, 4).map((tag, idx) => (
                                                    <span
                                                        key={idx}
                                                        className="text-[10px] font-medium px-1.5 py-0.5 rounded bg-slate-100 dark:bg-zinc-800 text-slate-500 dark:text-slate-400"
                                                    >
                                                        #{tag}
                                                    </span>
                                                ))}
                                                {item.tags.length > 4 && (
                                                    <span className="text-[10px] text-slate-400 px-1 py-0.5">
                                                        +{item.tags.length - 4}
                                                    </span>
                                                )}
                                            </div>
                                        )}
                                    </div>

                                    {/* Card Footer: One-click Copy Button & Metrics */}
                                    <div className="mt-5 pt-3 border-t border-slate-100 dark:border-white/5 flex items-center justify-between gap-3">
                                        <div className="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                                            <Copy className="h-3.5 w-3.5 text-slate-400" />
                                            <span>
                                                {__(item.copy_count === 1 ? 'admin.prompts_copy_count_one' : 'admin.prompts_copy_count_other', { count: item.copy_count })}
                                            </span>
                                        </div>

                                        {/* Highly Visible One-Click Copy Button */}
                                        <Button
                                            type="button"
                                            onClick={() => handleCopy(item)}
                                            className={`h-8 px-3 text-xs font-semibold gap-1.5 transition-all duration-150 rounded-lg shadow-2xs ${
                                                isCopied
                                                    ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 ring-2 ring-slate-800 dark:ring-white'
                                                    : 'bg-slate-900 hover:bg-black text-white dark:bg-white dark:hover:bg-slate-200 dark:text-slate-900'
                                            }`}
                                        >
                                            {isCopied ? (
                                                <>
                                                    <Check className="h-3.5 w-3.5" />
                                                    <span>{__('general.copied')}</span>
                                                </>
                                            ) : (
                                                <>
                                                    <Copy className="h-3.5 w-3.5" />
                                                    <span>{__('admin.prompts_copy_prompt')}</span>
                                                </>
                                            )}
                                        </Button>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>

            {/* ─── Modal: Create Prompt ────────────────────────────────────────── */}
            <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                <DialogContent className="sm:max-w-xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle className="text-lg font-bold text-slate-900 dark:text-white">
                            {__('admin.prompts_create_title')}
                        </DialogTitle>
                        <DialogDescription className="text-xs text-slate-500 dark:text-slate-400">
                            {__('admin.prompts_create_description')}
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleCreateSubmit} className="space-y-4 py-2">
                        {/* Title */}
                        <div className="space-y-1.5">
                            <Label htmlFor="title" className="text-xs font-semibold">
                                {__('general.title')} <span className="text-red-500">*</span>
                            </Label>
                            <Input
                                id="title"
                                value={form.data.title}
                                onChange={(e) => form.setData('title', e.target.value)}
                                placeholder={__('admin.prompts_title_placeholder')}
                                className="h-9 text-sm"
                                required
                            />
                            {form.errors.title && (
                                <p className="text-xs text-red-500">{form.errors.title}</p>
                            )}
                        </div>

                        {/* Category & Featured */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="category" className="text-xs font-semibold">
                                    {__('general.category')} <span className="text-red-500">*</span>
                                </Label>
                                <div className="flex gap-2">
                                    <Input
                                        id="category"
                                        value={form.data.category}
                                        onChange={(e) => form.setData('category', e.target.value)}
                                        placeholder={__('admin.prompts_category_placeholder')}
                                        className="h-9 text-sm"
                                        required
                                    />
                                </div>
                                <div className="flex flex-wrap gap-1 mt-1.5">
                                    {COMMON_CATEGORIES.map((cat) => (
                                        <button
                                            type="button"
                                            key={cat}
                                            onClick={() => form.setData('category', cat)}
                                            className={`text-[10px] px-1.5 py-0.5 rounded border transition-colors ${
                                                form.data.category === cat
                                                    ? 'bg-slate-900 text-white border-slate-900 dark:bg-white dark:text-slate-900'
                                                    : 'border-slate-200 dark:border-white/10 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-zinc-800'
                                            }`}
                                        >
                                            {cat}
                                        </button>
                                    ))}
                                </div>
                                {form.errors.category && (
                                    <p className="text-xs text-red-500">{form.errors.category}</p>
                                )}
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">{__('admin.prompts_settings_label')}</Label>
                                <div className="flex items-center justify-between rounded-lg border border-slate-200 dark:border-white/10 p-2.5 h-9">
                                    <span className="text-xs text-slate-700 dark:text-slate-300">
                                        {__('admin.prompts_mark_featured')}
                                    </span>
                                    <Switch
                                        checked={form.data.is_featured}
                                        onCheckedChange={(checked) => form.setData('is_featured', checked)}
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Description */}
                        <div className="space-y-1.5">
                            <Label htmlFor="description" className="text-xs font-semibold">
                                {__('admin.prompts_description_label')}
                            </Label>
                            <Input
                                id="description"
                                value={form.data.description}
                                onChange={(e) => form.setData('description', e.target.value)}
                                placeholder={__('admin.prompts_description_placeholder')}
                                className="h-9 text-sm"
                            />
                            {form.errors.description && (
                                <p className="text-xs text-red-500">{form.errors.description}</p>
                            )}
                        </div>

                        {/* Prompt Body */}
                        <div className="space-y-1.5">
                            <Label htmlFor="prompt_body" className="text-xs font-semibold">
                                {__('admin.prompts_content_label')} <span className="text-red-500">*</span>
                            </Label>
                            <Textarea
                                id="prompt_body"
                                rows={8}
                                value={form.data.prompt}
                                onChange={(e) => form.setData('prompt', e.target.value)}
                                placeholder={__('admin.prompts_content_placeholder')}
                                className="font-mono text-xs leading-relaxed"
                                required
                            />
                            {form.errors.prompt && (
                                <p className="text-xs text-red-500">{form.errors.prompt}</p>
                            )}
                        </div>

                        {/* Tags */}
                        <div className="space-y-1.5">
                            <Label htmlFor="tags_input" className="text-xs font-semibold">
                                {__('admin.prompts_tags_label')}
                            </Label>
                            <Input
                                id="tags_input"
                                value={form.data.tags_input}
                                onChange={(e) => form.setData('tags_input', e.target.value)}
                                placeholder={__('admin.prompts_tags_placeholder')}
                                className="h-9 text-sm"
                            />
                        </div>

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsCreateOpen(false)}
                                className="text-xs"
                            >
                                {__('general.cancel')}
                            </Button>
                            <Button
                                type="submit"
                                disabled={form.processing}
                                className="text-xs bg-slate-900 text-white hover:bg-black dark:bg-white dark:text-slate-900"
                            >
                                {form.processing ? __('general.creating') : __('admin.prompts_save_prompt')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* ─── Modal: Edit Prompt ──────────────────────────────────────────── */}
            <Dialog open={!!editingPrompt} onOpenChange={(open) => !open && setEditingPrompt(null)}>
                <DialogContent className="sm:max-w-xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle className="text-lg font-bold text-slate-900 dark:text-white">
                            {__('admin.prompts_edit_prompt')}
                        </DialogTitle>
                        <DialogDescription className="text-xs text-slate-500 dark:text-slate-400">
                            {__('admin.prompts_edit_description')}
                        </DialogDescription>
                    </DialogHeader>

                    {editingPrompt && (
                        <form onSubmit={handleEditSubmit} className="space-y-4 py-2">
                            <div className="space-y-1.5">
                                <Label htmlFor="edit_title" className="text-xs font-semibold">
                                    {__('general.title')} <span className="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="edit_title"
                                    value={form.data.title}
                                    onChange={(e) => form.setData('title', e.target.value)}
                                    className="h-9 text-sm"
                                    required
                                />
                                {form.errors.title && (
                                    <p className="text-xs text-red-500">{form.errors.title}</p>
                                )}
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div className="space-y-1.5">
                                    <Label htmlFor="edit_category" className="text-xs font-semibold">
                                        {__('general.category')} <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="edit_category"
                                        value={form.data.category}
                                        onChange={(e) => form.setData('category', e.target.value)}
                                        className="h-9 text-sm"
                                        required
                                    />
                                    <div className="flex flex-wrap gap-1 mt-1.5">
                                        {COMMON_CATEGORIES.map((cat) => (
                                            <button
                                                type="button"
                                                key={cat}
                                                onClick={() => form.setData('category', cat)}
                                                className={`text-[10px] px-1.5 py-0.5 rounded border transition-colors ${
                                                    form.data.category === cat
                                                        ? 'bg-slate-900 text-white border-slate-900 dark:bg-white dark:text-slate-900'
                                                        : 'border-slate-200 dark:border-white/10 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-zinc-800'
                                                }`}
                                            >
                                                {cat}
                                            </button>
                                        ))}
                                    </div>
                                    {form.errors.category && (
                                        <p className="text-xs text-red-500">{form.errors.category}</p>
                                    )}
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">{__('admin.prompts_settings_label')}</Label>
                                    <div className="flex items-center justify-between rounded-lg border border-slate-200 dark:border-white/10 p-2.5 h-9">
                                        <span className="text-xs text-slate-700 dark:text-slate-300">
                                            {__('admin.prompts_mark_featured')}
                                        </span>
                                        <Switch
                                            checked={form.data.is_featured}
                                            onCheckedChange={(checked) => form.setData('is_featured', checked)}
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="edit_description" className="text-xs font-semibold">
                                    {__('admin.prompts_description_label')}
                                </Label>
                                <Input
                                    id="edit_description"
                                    value={form.data.description}
                                    onChange={(e) => form.setData('description', e.target.value)}
                                    className="h-9 text-sm"
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="edit_prompt_body" className="text-xs font-semibold">
                                    {__('admin.prompts_content_label')} <span className="text-red-500">*</span>
                                </Label>
                                <Textarea
                                    id="edit_prompt_body"
                                    rows={8}
                                    value={form.data.prompt}
                                    onChange={(e) => form.setData('prompt', e.target.value)}
                                    className="font-mono text-xs leading-relaxed"
                                    required
                                />
                                {form.errors.prompt && (
                                    <p className="text-xs text-red-500">{form.errors.prompt}</p>
                                )}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="edit_tags" className="text-xs font-semibold">
                                    {__('admin.prompts_tags_label')}
                                </Label>
                                <Input
                                    id="edit_tags"
                                    value={form.data.tags_input}
                                    onChange={(e) => form.setData('tags_input', e.target.value)}
                                    className="h-9 text-sm"
                                />
                            </div>

                            <DialogFooter className="pt-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setEditingPrompt(null)}
                                    className="text-xs"
                                >
                                    {__('general.cancel')}
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                    className="text-xs bg-slate-900 text-white hover:bg-black dark:bg-white dark:text-slate-900"
                                >
                                    {form.processing ? __('general.saving') : __('admin.prompts_update_prompt')}
                                </Button>
                            </DialogFooter>
                        </form>
                    )}
                </DialogContent>
            </Dialog>

            {/* ─── Modal: View Full Prompt ─────────────────────────────────────── */}
            <Dialog open={!!viewingPrompt} onOpenChange={(open) => !open && setViewingPrompt(null)}>
                <DialogContent className="sm:max-w-2xl max-h-[90vh] flex flex-col">
                    <DialogHeader>
                        <div className="flex items-center gap-2 mb-1">
                            <span className="text-[11px] font-semibold uppercase tracking-wider px-2 py-0.5 rounded-md bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-white/5">
                                {viewingPrompt?.category}
                            </span>
                            {viewingPrompt?.is_featured && (
                                <span className="flex items-center gap-1 text-[11px] font-medium text-slate-800 dark:text-slate-200">
                                    <Sparkles className="h-3 w-3" />
                                    {__('admin.featured')}
                                </span>
                            )}
                        </div>
                        <DialogTitle className="text-xl font-bold text-slate-900 dark:text-white">
                            {viewingPrompt?.title}
                        </DialogTitle>
                        {viewingPrompt?.description && (
                            <DialogDescription className="text-xs text-slate-500 dark:text-slate-400">
                                {viewingPrompt.description}
                            </DialogDescription>
                        )}
                    </DialogHeader>

                    {viewingPrompt && (
                        <div className="flex-1 overflow-y-auto my-2 space-y-3">
                            <div className="relative rounded-xl border border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-black/50 p-4 font-mono text-xs leading-relaxed text-slate-800 dark:text-slate-200 whitespace-pre-wrap select-all">
                                {viewingPrompt.prompt}
                            </div>

                            {viewingPrompt.tags && viewingPrompt.tags.length > 0 && (
                                <div className="flex flex-wrap gap-1.5 pt-1">
                                    {viewingPrompt.tags.map((tag, i) => (
                                        <span
                                            key={i}
                                            className="text-xs font-medium px-2 py-0.5 rounded-md bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-slate-300"
                                        >
                                            #{tag}
                                        </span>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}

                    <DialogFooter className="flex flex-row justify-between items-center sm:justify-between w-full pt-2 border-t border-slate-100 dark:border-white/5">
                        <div className="text-xs text-slate-400">
                            {__('admin.prompts_copied_times', { count: viewingPrompt?.copy_count || 0 })}
                        </div>
                        <div className="flex items-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setViewingPrompt(null)}
                                className="text-xs"
                            >
                                {__('general.close')}
                            </Button>
                            {viewingPrompt && (
                                <Button
                                    type="button"
                                    onClick={() => handleCopy(viewingPrompt)}
                                    className="text-xs gap-1.5 bg-slate-900 text-white hover:bg-black dark:bg-white dark:text-slate-900 font-medium"
                                >
                                    {copiedId === viewingPrompt.id ? (
                                        <>
                                            <Check className="h-3.5 w-3.5" />
                                            <span>{__('general.copied')}</span>
                                        </>
                                    ) : (
                                        <>
                                            <Copy className="h-3.5 w-3.5" />
                                            <span>{__('admin.prompts_copy_prompt')}</span>
                                        </>
                                    )}
                                </Button>
                            )}
                        </div>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* ─── Modal: Delete Confirmation ──────────────────────────────────── */}
            <Dialog open={!!deletingPrompt} onOpenChange={(open) => !open && setDeletingPrompt(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-base font-bold text-slate-900 dark:text-white">
                            {__('admin.prompts_delete_prompt')}
                        </DialogTitle>
                        <DialogDescription className="text-xs text-slate-500 dark:text-slate-400">
                            {__('admin.prompts_delete_description', { title: deletingPrompt?.title ?? '' })}
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter className="pt-3">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setDeletingPrompt(null)}
                            className="text-xs"
                        >
                            {__('general.cancel')}
                        </Button>
                        <Button
                            type="button"
                            onClick={handleDelete}
                            className="text-xs bg-red-600 hover:bg-red-700 text-white"
                        >
                            {__('admin.prompts_delete_prompt')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminSidebarLayout>
    );
}

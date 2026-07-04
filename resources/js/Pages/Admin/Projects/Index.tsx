import React, { useMemo, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button, buttonVariants } from '@/Components/ui/button';
import { User, AlertCircle, Download, Archive, ArchiveRestore, Trash2, Edit, Plus, LayoutDashboard, SlidersHorizontal, LayoutGrid, Table as TableIcon, ChevronLeft, ChevronRight, FolderKanban } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/Components/ui/dialog';
import { DataTable } from '@/Components/ui/DataTable';
import { Checkbox } from '@/Components/ui/checkbox';
import { EmptyState } from '@/Components/ui/EmptyState';
import ProjectActionsSheet from './ProjectActionsSheet';
import { ProjectFormFields, EMPTY_PROJECT_FORM, formToPayload, projectToForm } from './Components/ProjectFormFields';
import { ProjectCard } from './Components/ProjectCard';
import { ProjectFiltersPanel, type FilterPartial } from './Components/ProjectFiltersPanel';
import { cn, formatMoney } from '@/lib/utils';
import type { ProjectsIndexProps, Project, ProjectViewMode } from '@/types/project';

type FormState = ReturnType<typeof projectToForm>;

export default function Index(props: ProjectsIndexProps) {
    const { projects, currentTab, sort, dir, perPage, perPageOptions, owners, filters } = props;
    const statusFilter = filters?.status_filter ?? null;
    const view: ProjectViewMode = filters?.view ?? 'grid';

    const [isEditOpen, setIsEditOpen] = useState(false);
    const [editingProject, setEditingProject] = useState<Project | null>(null);
    const [editForm, setEditForm] = useState<FormState>(EMPTY_PROJECT_FORM);

    const [isSheetOpen, setIsSheetOpen] = useState(false);
    const [selectedProject, setSelectedProject] = useState<Project | null>(null);

    const [selectedIds, setSelectedIds] = useState<number[]>([]);
    const [filtersOpen, setFiltersOpen] = useState(false);

    const openProjectSheet = (project: Project) => {
        setSelectedProject(project);
        setIsSheetOpen(true);
    };

    const openFinance = (project: Project) => {
        router.visit(route('admin.projects.finance.index', project.id));
    };

    const openEditModal = (project: Project) => {
        setEditingProject(project);
        setEditForm(projectToForm(project));
        setIsEditOpen(true);
    };

    const handleEditSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingProject) return;
        router.put(route('admin.projects.update', editingProject.id), formToPayload(editForm), {
            onSuccess: () => {
                setIsEditOpen(false);
                setEditingProject(null);
                setEditForm(EMPTY_PROJECT_FORM);
            },
        });
    };

    const handleArchive = (id: number) => {
        if (confirm(__('general.confirm_archive_project'))) {
            router.post(route('admin.projects.archive', id));
        }
    };

    const handleRestore = (id: number) => {
        if (confirm(__('general.confirm_restore_project'))) {
            router.post(route('admin.projects.restore', id));
        }
    };

    const handleDelete = (id: number) => {
        if (confirm(__('general.confirm_delete_project'))) {
            router.delete(route('admin.projects.destroy', id));
        }
    };

    const toggleSelected = (id: number) => {
        setSelectedIds((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
    };

    const toggleAllOnPage = () => {
        const pageIds = projects.data.map((p) => p.id);
        const allSelected = pageIds.every((id) => selectedIds.includes(id));
        if (allSelected) {
            setSelectedIds((prev) => prev.filter((id) => !pageIds.includes(id)));
        } else {
            setSelectedIds((prev) => Array.from(new Set([...prev, ...pageIds])));
        }
    };

    const submitBulk = (action: 'archive' | 'restore' | 'delete') => {
        if (selectedIds.length === 0) return;
        if (!confirm(__('general.confirm_bulk_project_action', { count: selectedIds.length, action }))) return;
        router.post(
            route('admin.projects.bulk-action'),
            { ids: selectedIds, action },
            {
                onSuccess: () => setSelectedIds([]),
            },
        );
    };

    const navigate = (overrides: Record<string, string | number | string[] | null | undefined>) => {
        const base: Record<string, string | number | string[] | null | undefined> = {
            status: currentTab,
            status_filter: filters?.status_filter ?? statusFilter,
            sort,
            dir,
            per_page: perPage,
            view,
            search: filters?.search ?? null,
            client_id: filters?.client_id ?? null,
            owner_id: filters?.owner_id ?? null,
            statuses: filters?.statuses ?? null,
            budget_min: filters?.budget_min ?? null,
            budget_max: filters?.budget_max ?? null,
            percent_min: filters?.percent_min ?? null,
            percent_max: filters?.percent_max ?? null,
            start_from: filters?.start_from ?? null,
            start_to: filters?.start_to ?? null,
            created_from: filters?.created_from ?? null,
            created_to: filters?.created_to ?? null,
            has_unpaid: filters?.has_unpaid ?? null,
            page: null,
        };
        const merged = { ...base, ...overrides };
        Object.keys(merged).forEach((k) => {
            const v = merged[k];
            if (v === null || v === undefined || v === '') delete merged[k];
            else if (Array.isArray(v) && v.length === 0) delete merged[k];
        });
        router.get(route('admin.projects.index'), merged, { preserveState: true, replace: true });
    };

    const onSort = (key: string) => {
        const newDir = sort === key && dir === 'asc' ? 'desc' : 'asc';
        navigate({ sort: key, dir: newDir, page: 1 });
    };

    const onPageChange = (page: number | string) => {
        navigate({ page: Number(page) });
    };

    const onPerPageChange = (n: number) => {
        navigate({ per_page: n, page: 1 });
    };

    const onSearch = (search: string) => {
        navigate({ search, page: 1 });
    };

    const onFilterChange = (partial: FilterPartial) => {
        navigate(partial);
    };

    const clearFilters = () => {
        navigate({
            search: null,
            client_id: null,
            owner_id: null,
            statuses: null,
            status_filter: null,
            budget_min: null,
            budget_max: null,
            percent_min: null,
            percent_max: null,
            start_from: null,
            start_to: null,
            created_from: null,
            created_to: null,
            has_unpaid: null,
            page: 1,
        });
    };

    const activeFilterCount = useMemo(() => {
        const f = filters;
        if (!f) return 0;
        let n = 0;
        if (f.search) n++;
        if (f.client_id) n++;
        if (f.owner_id) n++;
        if ((f.statuses?.length ?? 0) > 0) n++;
        if (f.status_filter) n++;
        if (f.budget_min || f.budget_max) n++;
        if (f.percent_min || f.percent_max) n++;
        if (f.start_from || f.start_to) n++;
        if (f.created_from || f.created_to) n++;
        if (f.has_unpaid) n++;
        return n;
    }, [filters]);

    const meta = projects.meta;
    const currentPage = meta?.current_page ?? 1;
    const lastPage = meta?.last_page ?? 1;
    const onGridPage = (page: number) => {
        if (page < 1 || page > lastPage) return;
        navigate({ page });
    };

    const exportHref = useMemo(() => {
        const params = new URLSearchParams({
            status: currentTab,
            sort: String(sort),
            dir,
        });
        const f = filters;
        const set = (key: string, val: string | null | undefined) => {
            if (val !== null && val !== undefined && val !== '') params.set(key, val);
        };
        set('search', f?.search);
        set('client_id', f?.client_id);
        set('owner_id', f?.owner_id);
        set('status_filter', f?.status_filter);
        (f?.statuses ?? []).forEach((s) => params.append('statuses[]', s));
        set('budget_min', f?.budget_min);
        set('budget_max', f?.budget_max);
        set('percent_min', f?.percent_min);
        set('percent_max', f?.percent_max);
        set('start_from', f?.start_from);
        set('start_to', f?.start_to);
        set('created_from', f?.created_from);
        set('created_to', f?.created_to);
        if (f?.has_unpaid) params.set('has_unpaid', '1');
        return `${route('admin.projects.export')}?${params.toString()}`;
    }, [currentTab, sort, dir, filters]);

    const allOnPageSelected = projects.data.length > 0 && projects.data.every((p) => selectedIds.includes(p.id));

    const columns = [
        {
            key: 'select',
            label: '',
            sortable: false,
            className: 'w-10',
            render: (project: Project) => (
                <Checkbox
                    checked={selectedIds.includes(project.id)}
                    onCheckedChange={() => toggleSelected(project.id)}
                    aria-label={__('general.select_project', { name: project.project_name })}
                />
            ),
        },
        {
            key: 'project_name',
            label: __('general.name'),
            sortable: true,
            render: (project: Project) => (
                <div className={cn('flex flex-col gap-1', project.archived && 'opacity-60')}>
                    <Link
                        href={route('admin.projects.board.index', project.id)}
                        className="hover:text-slate-900 hover:underline text-start font-semibold cursor-pointer outline-none focus:ring-2 focus:ring-slate-900 rounded"
                    >
                        {project.project_name}
                    </Link>
                    {project.description && (
                        <p className="text-xs text-slate-500 line-clamp-1">{project.description}</p>
                    )}
                    {(project.counts?.invoices_unpaid ?? 0) > 0 && (
                        <div className="mt-1 flex items-center gap-1 text-xs font-semibold text-red-700 bg-red-50 border border-red-200 px-2 py-0.5 rounded w-fit" title={__('general.unpaid_invoices_count', { count: project.counts?.invoices_unpaid ?? 0 })}>
                            <AlertCircle className="w-3 h-3" /> {project.counts?.invoices_unpaid} {__('general.unpaid_dues')}
                        </div>
                    )}
                </div>
            ),
        },
        {
            key: 'user_id',
            label: __('general.client'),
            sortable: false,
            render: (project: Project) =>
                project.client ? (
                    <div className="flex items-center gap-3">
                        <Avatar className="h-10 w-10 border border-slate-200">
                            <AvatarImage src={project.client.name ?? ''} alt={project.client.name} />
                            <AvatarFallback className="bg-slate-50 text-slate-900">
                                <User className="h-5 w-5" />
                            </AvatarFallback>
                        </Avatar>
                        <div className="flex flex-col text-start">
                            <span className="font-semibold text-slate-900">{project.client.name}</span>
                            <span className="text-sm text-slate-500">{project.client.email}</span>
                        </div>
                    </div>
                ) : (
                    'Unknown'
                ),
        },
        {
            key: 'budget',
            label: __('general.budget'),
            sortable: true,
            render: (project: Project) => (project.budget && Number(project.budget) > 0 ? formatMoney(project.budget, project.currency) : '—'),
        },
        {
            key: 'status',
            label: __('general.status'),
            sortable: true,
            render: (project: Project) => {
                if (project.archived) {
                    return <span className="inline-flex rounded-full px-2 text-xs font-semibold leading-5 bg-slate-200 text-slate-700">{__('general.archived')}</span>;
                }
                const status = project.status ?? 'open';
                const styles: Record<string, string> = {
                    open: 'bg-emerald-100 text-emerald-800',
                    hold_on: 'bg-amber-100 text-amber-800',
                    closed: 'bg-slate-200 text-slate-700',
                };
                return <span className={cn('inline-flex rounded-full px-2 text-xs font-semibold leading-5 capitalize', styles[status] ?? 'bg-slate-100 text-slate-700')}>{status.replace('_', ' ')}</span>;
            },
        },
        {
            key: 'date_start',
            label: __('general.start_date'),
            sortable: true,
            render: (project: Project) => project.date_start ?? '—',
        },
        {
            key: 'actions',
            label: __('general.actions'),
            className: 'text-end',
            render: (project: Project) => (
                <div className="flex justify-end space-x-2">
                    <Button variant="outline" size="sm" onClick={() => openProjectSheet(project)} aria-label={__('general.actions')} title={__('general.actions')}>
                        <LayoutDashboard className="h-3.5 w-3.5" />
                    </Button>
                    <Button variant="outline" size="sm" onClick={() => openEditModal(project)} aria-label={__('general.edit')}>
                        <Edit className="h-3.5 w-3.5" />
                    </Button>
                    {project.archived ? (
                        <Button variant="outline" size="sm" onClick={() => handleRestore(project.id)} aria-label={__('general.restore')}>
                            <ArchiveRestore className="h-3.5 w-3.5" />
                        </Button>
                    ) : (
                        <Button variant="outline" size="sm" onClick={() => handleArchive(project.id)} aria-label={__('general.archive')}>
                            <Archive className="h-3.5 w-3.5" />
                        </Button>
                    )}
                    <Button variant="destructive" size="sm" onClick={() => handleDelete(project.id)} aria-label={__('general.delete')}>
                        <Trash2 className="h-3.5 w-3.5" />
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AdminSidebarLayout title={__('general.projects')} header="Projects Manager">
            <Head title={__('general.projects')} />
            <div className="mb-4 flex flex-wrap items-center justify-between gap-4">
                <div className="flex flex-wrap items-center gap-2">
                    <div className="flex space-x-2">
                        {(['active', 'archived', 'all'] as const).map((tab) => (
                            <Link
                                key={tab}
                                href={route('admin.projects.index', { status: tab })}
                                className={cn(
                                    'rounded-md px-3 py-1.5 text-sm font-medium',
                                    currentTab === tab ? 'bg-slate-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                                )}
                            >
                                {tab === 'active' && __('general.active_projects')}
                                {tab === 'archived' && __('general.archived_projects')}
                                {tab === 'all' && __('general.all_projects')}
                            </Link>
                        ))}
                    </div>

                    <Button
                        variant={filtersOpen ? 'default' : 'outline'}
                        size="sm"
                        className="gap-1.5"
                        onClick={() => setFiltersOpen((v) => !v)}
                    >
                        <SlidersHorizontal className="h-3.5 w-3.5" />
                        {__('general.filters')}
                        {activeFilterCount > 0 && (
                            <span className="inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-indigo-600 px-1 text-[10px] font-bold text-white">
                                {activeFilterCount}
                            </span>
                        )}
                    </Button>

                    {/* View toggle */}
                    <div className="flex items-center rounded-md border border-slate-200 bg-white p-0.5">
                        <button
                            type="button"
                            onClick={() => navigate({ view: 'grid' })}
                            className={cn(
                                'inline-flex h-7 items-center gap-1 rounded px-2 text-xs font-medium transition-colors',
                                view === 'grid' ? 'bg-slate-900 text-white' : 'text-slate-500 hover:text-slate-900',
                            )}
                            aria-label={__('general.grid_view')}
                            title={__('general.grid_view')}
                        >
                            <LayoutGrid className="h-3.5 w-3.5" />
                        </button>
                        <button
                            type="button"
                            onClick={() => navigate({ view: 'table' })}
                            className={cn(
                                'inline-flex h-7 items-center gap-1 rounded px-2 text-xs font-medium transition-colors',
                                view === 'table' ? 'bg-slate-900 text-white' : 'text-slate-500 hover:text-slate-900',
                            )}
                            aria-label={__('general.table_view')}
                            title={__('general.table_view')}
                        >
                            <TableIcon className="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>

                <div className="flex items-center gap-2">
                    <a href={exportHref} className={cn(buttonVariants({ variant: 'outline' }), 'gap-2')}>
                        <Download className="h-4 w-4" /> {__('general.export_csv')}
                    </a>
                    <Button asChild className="gap-2">
                        <Link
                            href={route('admin.projects.create', {
                                user_id: typeof window !== 'undefined'
                                    ? new URLSearchParams(window.location.search).get('user_id')
                                    : null,
                            })}
                        >
                            <Plus className="h-4 w-4" /> {__('general.create_project')}
                        </Link>
                    </Button>
                </div>
            </div>

            {filtersOpen && (
                <div className="mb-4">
                    <ProjectFiltersPanel
                        filters={filters ?? ({} as typeof filters)}
                        owners={owners ?? []}
                        perPageOptions={perPageOptions ?? [15]}
                        sort={sort}
                        dir={dir}
                        perPage={perPage}
                        onChange={onFilterChange}
                        onClear={clearFilters}
                    />
                </div>
            )}

            {selectedIds.length > 0 && (
                <div className="mb-3 flex items-center justify-between rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm text-indigo-900">
                    <span>{__('general.selected_count', { count: selectedIds.length })}</span>
                    <div className="flex items-center gap-2">
                        {currentTab !== 'archived' && (
                            <Button size="sm" variant="outline" onClick={() => submitBulk('archive')}>
                                <Archive className="h-3.5 w-3.5" /> {__('general.archive')}
                            </Button>
                        )}
                        {currentTab === 'archived' && (
                            <Button size="sm" variant="outline" onClick={() => submitBulk('restore')}>
                                <ArchiveRestore className="h-3.5 w-3.5" /> {__('general.restore')}
                            </Button>
                        )}
                        <Button size="sm" variant="destructive" onClick={() => submitBulk('delete')}>
                            <Trash2 className="h-3.5 w-3.5" /> {__('general.delete')}
                        </Button>
                        <Button size="sm" variant="ghost" onClick={() => setSelectedIds([])}>
                            {__('general.cancel')}
                        </Button>
                    </div>
                </div>
            )}

            {view === 'grid' ? (
                projects.data.length > 0 ? (
                    <div className="mb-4">
                        <div className="mb-4 flex items-center justify-between gap-3">
                            <button
                                onClick={toggleAllOnPage}
                                className={cn(buttonVariants({ variant: 'outline', size: 'sm' }), 'gap-1')}
                            >
                                {allOnPageSelected ? __('general.deselect_all') : __('general.select_all_on_page')}
                            </button>
                            {meta && (
                                <span className="text-xs text-slate-400">
                                    {meta.from ?? 0}–{meta.to ?? 0} / <span className="font-medium text-slate-600">{meta.total}</span>
                                </span>
                            )}
                        </div>
                        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                            {projects.data.map((project) => (
                                <ProjectCard
                                    key={project.id}
                                    project={project}
                                    isSelected={selectedIds.includes(project.id)}
                                    onSelect={toggleSelected}
                                    onEdit={openEditModal}
                                    onOpenBoard={openProjectSheet}
                                    onAnalyze={openFinance}
                                    onArchive={handleArchive}
                                    onRestore={handleRestore}
                                    onDelete={handleDelete}
                                />
                            ))}
                        </div>

                        {lastPage > 1 && (
                            <div className="mt-6 flex items-center justify-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={currentPage <= 1}
                                    onClick={() => onGridPage(currentPage - 1)}
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                </Button>
                                <span className="text-sm text-slate-600">
                                    {__('general.page')} <span className="font-semibold text-slate-900">{currentPage}</span> / {lastPage}
                                </span>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={currentPage >= lastPage}
                                    onClick={() => onGridPage(currentPage + 1)}
                                >
                                    <ChevronRight className="h-4 w-4" />
                                </Button>
                            </div>
                        )}
                    </div>
                ) : (
                    <div className="mb-4">
                        <EmptyState
                            icon={FolderKanban}
                            title={__('general.no_projects_found')}
                            description={__('general.create_first_project_cta')}
                            action={route('admin.projects.create')}
                            actionLabel={__('general.create_project')}
                            actionIcon={Plus}
                        />
                    </div>
                )
            ) : (
                <div className="mb-4">
                    <DataTable
                        columns={columns}
                        data={projects.data}
                        pagination={projects}
                        filters={{
                            ...(filters ?? {}),
                            extra: (
                                <button
                                    onClick={toggleAllOnPage}
                                    className={cn(buttonVariants({ variant: 'outline', size: 'sm' }), 'gap-1')}
                                >
                                    {allOnPageSelected ? __('general.deselect_all') : __('general.select_all_on_page')}
                                </button>
                            ),
                            sort,
                            dir,
                        }}
                        onSearch={onSearch}
                        onSort={onSort}
                        onPageChange={onPageChange}
                        onPerPageChange={onPerPageChange}
                        emptyTitle={__('general.no_projects_found')}
                        emptyDescription={__('general.create_first_project_cta')}
                    />
                </div>
            )}

            {/* Edit Modal */}
            <Dialog open={isEditOpen} onOpenChange={setIsEditOpen}>
                <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>{__('general.edit_project')}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleEditSubmit} className="space-y-4">
                        <ProjectFormFields
                            form={editForm}
                            setForm={setEditForm}
                            includeClient
                        />
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsEditOpen(false)}>
                                {__('general.cancel')}
                            </Button>
                            <Button type="submit">{__('general.save_changes')}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <ProjectActionsSheet
                project={selectedProject}
                isOpen={isSheetOpen}
                onClose={() => setIsSheetOpen(false)}
                onEdit={(proj) => openEditModal(proj as Project)}
            />
        </AdminSidebarLayout>
    );
}

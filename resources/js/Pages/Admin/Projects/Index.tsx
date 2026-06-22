import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { User, AlertCircle } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
    DialogFooter,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { DataTable } from '@/Components/ui/DataTable';
import { ClientAutocomplete } from '@/Components/ClientAutocomplete';
import ProjectActionsSheet from './ProjectActionsSheet';
import { formatMoney } from '@/lib/utils';
import { __ } from '@/lib/i18n';

export default function Index({ projects, currentTab }) {
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [editingProject, setEditingProject] = useState<any>(null);
    const [formData, setFormData] = useState({ project_name: '', project_balance: '', user_id: '' });

    const [isSheetOpen, setIsSheetOpen] = useState(false);
    const [selectedProject, setSelectedProject] = useState<any>(null);

    const openProjectSheet = (project) => {
        setSelectedProject(project);
        setIsSheetOpen(true);
    };

    const handleCreateSubmit = (e) => {
        e.preventDefault();
        router.post(route('admin.projects.store'), formData, {
            onSuccess: () => {
                setIsCreateOpen(false);
                setFormData({ project_name: '', project_balance: '', user_id: '' });
            },
        });
    };

    const handleEditSubmit = (e) => {
        e.preventDefault();
        router.put(route('admin.projects.update', editingProject?.id), formData, {
            onSuccess: () => {
                setIsEditOpen(false);
                setEditingProject(null);
                setFormData({ project_name: '', project_balance: '', user_id: '' });
            },
        });
    };

    const openEditModal = (project) => {
        setEditingProject(project);
        setFormData({
            project_name: project.project_name,
            project_balance: project.project_balance || '',
            user_id: project.user_id,
        });
        setIsEditOpen(true);
    };

    const handleArchive = (id) => {
        if (confirm('Are you sure you want to archive this project?')) {
            router.post(route('admin.projects.archive', id));
        }
    };

    const handleRestore = (id) => {
        if (confirm('Are you sure you want to restore this project?')) {
            router.post(route('admin.projects.restore', id));
        }
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this project permanently?')) {
            router.delete(route('admin.projects.destroy', id));
        }
    };

    const columns = [
        {
            key: 'name',
            label: __('general.name'),
            render: (project) => (
                <>
                    <button 
                        onClick={() => openProjectSheet(project)}
                        className="hover:text-blue-600 hover:underline text-start font-semibold cursor-pointer outline-none focus:ring-2 focus:ring-blue-500 rounded"
                    >
                        {project.project_name}
                    </button>
                    {project.invoices && project.invoices.length > 0 && (
                        <div className="mt-1 flex items-center gap-1 text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded w-fit" title="Unpaid Invoices / Milestones">
                            <AlertCircle className="w-3 h-3" /> {__('general.unpaid_dues')}
                        </div>
                    )}
                </>
            )
        },
        {
            key: 'client',
            label: __('general.client'),
            render: (project) => (
                project.client ? (
                    <div className="flex items-center gap-3">
                        <Avatar className="h-10 w-10 border border-slate-200">
                            <AvatarImage src={project.client.avatar_url || ''} alt={project.client.name} />
                            <AvatarFallback className="bg-blue-50 text-blue-500">
                                <User className="h-5 w-5" />
                            </AvatarFallback>
                        </Avatar>
                        <div className="flex flex-col text-start">
                            <span className="font-semibold text-slate-900">
                                {project.client.name}
                            </span>
                            <span className="text-sm text-slate-500">
                                {project.client.email}
                            </span>
                        </div>
                    </div>
                ) : (
                    'Unknown'
                )
            )
        },
        {
            key: 'budget',
            label: __('general.budget'),
            render: (project) => project.project_balance ? formatMoney(project.project_balance, 'USD') : '-'
        },
        {
            key: 'status',
            label: __('general.status'),
            render: (project) => (
                <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 ${project.archived === 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>
                    {project.archived === 0 ? project.status : 'Archived'}
                </span>
            )
        },
        {
            key: 'actions',
            label: __('general.actions'),
            className: 'text-end',
            render: (project) => (
                <div className="flex justify-end space-x-2">
                    <Button variant="outline" size="sm" onClick={() => openEditModal(project)}>
                        {__('general.edit')}
                    </Button>
                    {project.archived === 0 ? (
                        <Button variant="outline" size="sm" onClick={() => handleArchive(project.id)}>
                            {__('general.archive')}
                        </Button>
                    ) : (
                        <Button variant="outline" size="sm" onClick={() => handleRestore(project.id)}>
                            {__('general.restore')}
                        </Button>
                    )}
                    <Button variant="destructive" size="sm" onClick={() => handleDelete(project.id)}>
                        {__('general.delete')}
                    </Button>
                </div>
            )
        }
    ];

    return (
        <AdminSidebarLayout title={__('general.projects')} header="Projects Manager">
            <div className="mb-6 flex items-center justify-between">
                <div className="flex space-x-4">
                    <Link
                        href={route('admin.projects.index', { status: 'active' })}
                        className={`rounded-md px-4 py-2 text-sm font-medium ${currentTab === 'active' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`}
                    >{__('general.active_projects')}</Link>
                    <Link
                        href={route('admin.projects.index', { status: 'archived' })}
                        className={`rounded-md px-4 py-2 text-sm font-medium ${currentTab === 'archived' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`}
                    >{__('general.archived_projects')}</Link>
                </div>
                <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                    <DialogTrigger asChild>
                        <Button>{__('general.create_project')}</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>{__('general.create_new_project')}</DialogTitle>
                        </DialogHeader>
                        <form onSubmit={handleCreateSubmit} className="space-y-4">
                            <div className="z-50 relative">
                                <Label htmlFor="client_id">{__('general.client')}</Label>
                                <ClientAutocomplete 
                                    value={formData.user_id}
                                    onChange={(val) => setFormData({ ...formData, user_id: val })}
                                    searchEndpoint={route('admin.projects.search-clients')}
                                    className="mt-1"
                                />
                            </div>
                            <div>
                                <Label htmlFor="project_name">{__('general.project_name')}</Label>
                                <Input
                                    id="project_name"
                                    value={formData.project_name}
                                    onChange={(e) => setFormData({ ...formData, project_name: e.target.value })}
                                    required
                                />
                            </div>
                            <div>
                                <Label htmlFor="project_balance">Budget (Optional)</Label>
                                <Input
                                    id="project_balance"
                                    type="number"
                                    step="0.01"
                                    value={formData.project_balance}
                                    onChange={(e) => setFormData({ ...formData, project_balance: e.target.value })}
                                />
                            </div>
                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setIsCreateOpen(false)}>
                                    {__('general.cancel')}</Button>
                                <Button type="submit">{__('general.save')}</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <div className="mb-4">
                <DataTable
                    columns={columns}
                    data={projects.data}
                    pagination={projects}
                    onPageChange={(page) => router.get(route('admin.projects.index', { status: currentTab, page }))}
                />
            </div>

            {/* Edit Modal */}
            <Dialog open={isEditOpen} onOpenChange={setIsEditOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{__('general.edit_project')}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleEditSubmit} className="space-y-4">
                        <div>
                            <Label htmlFor="edit_name">{__('general.project_name')}</Label>
                            <Input
                                id="edit_name"
                                value={formData.project_name}
                                onChange={(e) => setFormData({ ...formData, project_name: e.target.value })}
                                required
                            />
                        </div>
                        <div>
                            <Label htmlFor="edit_budget">Budget (Optional)</Label>
                            <Input
                                id="edit_budget"
                                type="number"
                                step="0.01"
                                value={formData.project_balance}
                                onChange={(e) => setFormData({ ...formData, project_balance: e.target.value })}
                            />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsEditOpen(false)}>
                                {__('general.cancel')}</Button>
                            <Button type="submit">{__('general.save_changes')}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <ProjectActionsSheet 
                project={selectedProject} 
                isOpen={isSheetOpen} 
                onClose={() => setIsSheetOpen(false)} 
                onEdit={(proj) => openEditModal(proj)}
            />
        </AdminSidebarLayout>
    );
}

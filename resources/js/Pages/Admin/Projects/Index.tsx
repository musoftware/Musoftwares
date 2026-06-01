import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { User } from 'lucide-react';
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
import ProjectActionsSheet from './ProjectActionsSheet';
import { formatMoney } from '@/lib/utils';
import { __ } from '@/lib/i18n';

export default function Index({ projects, clients, currentTab }) {
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [editingProject, setEditingProject] = useState(null);
    const [formData, setFormData] = useState({ project_name: '', project_balance: '', user_id: '' });

    const [isSheetOpen, setIsSheetOpen] = useState(false);
    const [selectedProject, setSelectedProject] = useState(null);

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
        router.put(route('admin.projects.update', editingProject.id), formData, {
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

    return (
        <AdminSidebarLayout title="Projects" header="Projects Manager">
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
                            <div>
                                <Label htmlFor="client_id">Client</Label>
                                <select
                                    id="user_id"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                    value={formData.user_id}
                                    onChange={(e) => setFormData({ ...formData, user_id: e.target.value })}
                                    required
                                >
                                    <option value="">{__('general.select_a_client')}</option>
                                    {clients.map((client) => (
                                        <option key={client.id} value={client.id}>
                                            {client.name} ({client.email})
                                        </option>
                                    ))}
                                </select>
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
                                    Cancel
                                </Button>
                                <Button type="submit">Save</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <div className="overflow-hidden rounded-lg bg-white shadow">
                <table className="w-full text-left text-sm">
                    <thead className="border-b bg-gray-50">
                        <tr>
                            <th className="p-4 font-medium text-gray-600">Name</th>
                            <th className="p-4 font-medium text-gray-600">Client</th>
                            <th className="p-4 font-medium text-gray-600">Budget</th>
                            <th className="p-4 font-medium text-gray-600">Status</th>
                            <th className="p-4 font-medium text-gray-600 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {projects.map((project) => (
                            <tr key={project.id} className="border-b hover:bg-gray-50">
                                <td className="p-4 font-medium text-gray-900">
                                    <button 
                                        onClick={() => openProjectSheet(project)}
                                        className="hover:text-blue-600 hover:underline text-left font-semibold cursor-pointer outline-none focus:ring-2 focus:ring-blue-500 rounded"
                                    >
                                        {project.project_name}
                                    </button>
                                </td>
                                <td className="p-4">
                                    {project.client ? (
                                        <div className="flex items-center gap-3">
                                            <Avatar className="h-10 w-10 border border-slate-200">
                                                <AvatarImage src={project.client.avatar_url || ''} alt={project.client.name} />
                                                <AvatarFallback className="bg-blue-50 text-blue-500">
                                                    <User className="h-5 w-5" />
                                                </AvatarFallback>
                                            </Avatar>
                                            <div className="flex flex-col text-left">
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
                                    )}
                                </td>
                                <td className="p-4">{project.project_balance ? formatMoney(project.project_balance, 'USD') : '-'}</td>
                                <td className="p-4">
                                    <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 ${project.archived === 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>
                                        {project.archived === 0 ? project.status : 'Archived'}
                                    </span>
                                </td>
                                <td className="p-4 space-x-2 text-right">
                                    <Button variant="outline" size="sm" onClick={() => openEditModal(project)}>
                                        Edit
                                    </Button>
                                    {project.archived === 0 ? (
                                        <Button variant="outline" size="sm" onClick={() => handleArchive(project.id)}>
                                            Archive
                                        </Button>
                                    ) : (
                                        <Button variant="outline" size="sm" onClick={() => handleRestore(project.id)}>
                                            Restore
                                        </Button>
                                    )}
                                    <Button variant="destructive" size="sm" onClick={() => handleDelete(project.id)}>
                                        Delete
                                    </Button>
                                </td>
                            </tr>
                        ))}
                        {projects.length === 0 && (
                            <tr>
                                <td colSpan="5" className="p-4 text-center text-gray-500">{__('general.no_projects_found')}</td>
                            </tr>
                        )}
                    </tbody>
                </table>
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
                                Cancel
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
                onEdit={(proj) => openEditModal(proj)}
            />
        </AdminSidebarLayout>
    );
}

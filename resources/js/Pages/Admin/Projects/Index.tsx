import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
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

export default function Index({ projects, clients, currentTab }) {
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [editingProject, setEditingProject] = useState(null);
    const [formData, setFormData] = useState({ name: '', budget: '', client_id: '' });

    const handleCreateSubmit = (e) => {
        e.preventDefault();
        router.post(route('admin.projects.store'), formData, {
            onSuccess: () => {
                setIsCreateOpen(false);
                setFormData({ name: '', budget: '', client_id: '' });
            },
        });
    };

    const handleEditSubmit = (e) => {
        e.preventDefault();
        router.put(route('admin.projects.update', editingProject.id), formData, {
            onSuccess: () => {
                setIsEditOpen(false);
                setEditingProject(null);
                setFormData({ name: '', budget: '', client_id: '' });
            },
        });
    };

    const openEditModal = (project) => {
        setEditingProject(project);
        setFormData({
            name: project.name,
            budget: project.budget || '',
            client_id: project.client_id,
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
                    >
                        Active Projects
                    </Link>
                    <Link
                        href={route('admin.projects.index', { status: 'archived' })}
                        className={`rounded-md px-4 py-2 text-sm font-medium ${currentTab === 'archived' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`}
                    >
                        Archived Projects
                    </Link>
                </div>
                <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                    <DialogTrigger asChild>
                        <Button>Create Project</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Create New Project</DialogTitle>
                        </DialogHeader>
                        <form onSubmit={handleCreateSubmit} className="space-y-4">
                            <div>
                                <Label htmlFor="client_id">Client</Label>
                                <select
                                    id="client_id"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                    value={formData.client_id}
                                    onChange={(e) => setFormData({ ...formData, client_id: e.target.value })}
                                    required
                                >
                                    <option value="">Select a client...</option>
                                    {clients.map((client) => (
                                        <option key={client.id} value={client.id}>
                                            {client.name} ({client.email})
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="name">Project Name</Label>
                                <Input
                                    id="name"
                                    value={formData.name}
                                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                    required
                                />
                            </div>
                            <div>
                                <Label htmlFor="budget">Budget (Optional)</Label>
                                <Input
                                    id="budget"
                                    type="number"
                                    step="0.01"
                                    value={formData.budget}
                                    onChange={(e) => setFormData({ ...formData, budget: e.target.value })}
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
                                <td className="p-4 font-medium text-gray-900">{project.name}</td>
                                <td className="p-4">
                                    {project.platform_client?.name || project.tenant_client?.name || 'Unknown'}
                                </td>
                                <td className="p-4">{project.budget ? `$${parseFloat(project.budget).toFixed(2)}` : '-'}</td>
                                <td className="p-4">
                                    <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 ${project.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>
                                        {project.status}
                                    </span>
                                </td>
                                <td className="p-4 space-x-2 text-right">
                                    <Button variant="outline" size="sm" onClick={() => openEditModal(project)}>
                                        Edit
                                    </Button>
                                    {project.status === 'active' ? (
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
                                <td colSpan="5" className="p-4 text-center text-gray-500">
                                    No projects found.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {/* Edit Modal */}
            <Dialog open={isEditOpen} onOpenChange={setIsEditOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit Project</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleEditSubmit} className="space-y-4">
                        <div>
                            <Label htmlFor="edit_name">Project Name</Label>
                            <Input
                                id="edit_name"
                                value={formData.name}
                                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                required
                            />
                        </div>
                        <div>
                            <Label htmlFor="edit_budget">Budget (Optional)</Label>
                            <Input
                                id="edit_budget"
                                type="number"
                                step="0.01"
                                value={formData.budget}
                                onChange={(e) => setFormData({ ...formData, budget: e.target.value })}
                            />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsEditOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit">Save Changes</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AdminSidebarLayout>
    );
}

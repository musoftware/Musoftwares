import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
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

export default function Index({ skills, filters }: any) {
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [editingSkill, setEditingSkill] = useState<any>(null);
    const [formData, setFormData] = useState({
        name: '',
        description: '',
    });
    const [search, setSearch] = useState(filters.search || '');

    const handleSearch = (e: any) => {
        e.preventDefault();
        router.get(route('admin.freelance.skills.index'), { search }, { preserveState: true });
    };

    const handleCreateSubmit = (e: any) => {
        e.preventDefault();
        router.post(route('admin.freelance.skills.store'), formData, {
            onSuccess: () => {
                setIsCreateOpen(false);
                resetForm();
            },
        });
    };

    const handleEditSubmit = (e: any) => {
        e.preventDefault();
        if (!editingSkill) return;
        router.put(route('admin.freelance.skills.update', editingSkill.id), formData, {
            onSuccess: () => {
                setIsEditOpen(false);
                setEditingSkill(null);
                resetForm();
            },
        });
    };

    const resetForm = () => {
        setFormData({
            name: '',
            description: '',
        });
    };

    const openEditModal = (skill: any) => {
        setEditingSkill(skill);
        setFormData({
            name: skill.name,
            description: skill.description || '',
        });
        setIsEditOpen(true);
    };

    const handleDelete = (id: any) => {
        if (confirm('Are you sure you want to delete this skill permanently?')) {
            router.delete(route('admin.freelance.skills.destroy', id));
        }
    };

    const renderFormFields = () => (
        <div className="space-y-4 p-1">
            <div>
                <Label htmlFor="name">Skill Name</Label>
                <Input
                    id="name"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    required
                />
            </div>

            <div>
                <Label htmlFor="description">Description (Optional)</Label>
                <Input
                    id="description"
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                />
            </div>
        </div>
    );

    return (
        <AdminSidebarLayout title="Freelance Skills" header="Manage Freelance Skills">
            <div className="mb-6 flex items-center justify-between">
                <form onSubmit={handleSearch} className="flex space-x-2">
                    <Input 
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search skills..."
                        className="w-64"
                    />
                    <Button type="submit" variant="secondary">Search</Button>
                    {search && (
                        <Button type="button" variant="ghost" onClick={() => { setSearch(''); router.get(route('admin.freelance.skills.index')); }}>
                            Clear
                        </Button>
                    )}
                </form>

                <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                    <DialogTrigger render={<Button>Create Skill</Button>} />
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Create New Skill</DialogTitle>
                        </DialogHeader>
                        <form onSubmit={handleCreateSubmit}>
                            {renderFormFields()}
                            <DialogFooter className="mt-6">
                                <Button type="button" variant="outline" onClick={() => setIsCreateOpen(false)}>
                                    Cancel
                                </Button>
                                <Button type="submit">Save Skill</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <div className="overflow-hidden rounded-lg bg-white shadow">
                <table className="w-full text-left text-sm">
                    <thead className="border-b bg-gray-50">
                        <tr>
                            <th className="p-4 font-medium text-gray-600">ID</th>
                            <th className="p-4 font-medium text-gray-600">Name</th>
                            <th className="p-4 font-medium text-gray-600">Description</th>
                            <th className="p-4 font-medium text-gray-600 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {skills.data.map((skill: any) => (
                            <tr key={skill.id} className="border-b hover:bg-gray-50">
                                <td className="p-4 font-medium text-gray-900">{skill.id}</td>
                                <td className="p-4 font-medium text-gray-900">{skill.name}</td>
                                <td className="p-4 text-gray-500">{skill.description || '-'}</td>
                                <td className="p-4 space-x-2 text-right">
                                    <Button variant="outline" size="sm" onClick={() => openEditModal(skill)}>
                                        Edit
                                    </Button>
                                    <Button variant="destructive" size="sm" onClick={() => handleDelete(skill.id)}>
                                        Delete
                                    </Button>
                                </td>
                            </tr>
                        ))}
                        {skills.data.length === 0 && (
                            <tr>
                                <td colSpan={4} className="p-4 text-center text-gray-500">
                                    No skills found.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {skills.links && skills.links.length > 3 && (
                <div className="mt-4 flex justify-between items-center">
                    <div className="text-sm text-gray-500">
                        Showing {skills.from || 0} to {skills.to || 0} of {skills.total} results
                    </div>
                    <div className="flex space-x-1">
                        {skills.links.map((link: any, idx: number) => (
                            <Link 
                                key={idx}
                                href={link.url || '#'}
                                className={`px-3 py-1 rounded text-sm transition ${link.active ? 'bg-slate-900 text-white shadow-sm' : !link.url ? 'cursor-not-allowed opacity-50 text-slate-300 pointer-events-none' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                </div>
            )}

            {/* Edit Modal */}
            <Dialog open={isEditOpen} onOpenChange={setIsEditOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit Skill</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleEditSubmit}>
                        {renderFormFields()}
                        <DialogFooter className="mt-6">
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

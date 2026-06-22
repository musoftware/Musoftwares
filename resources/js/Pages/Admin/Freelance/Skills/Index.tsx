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
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger, DropdownMenuSeparator, DropdownMenuLabel } from '@/Components/ui/dropdown-menu';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { MoreHorizontal, Edit, Trash2, CheckCircle, XCircle, Ban } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function Index({ skills, filters }: any) {
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [isBulkCreateOpen, setIsBulkCreateOpen] = useState(false);
    const [editingSkill, setEditingSkill] = useState<any>(null);
    const [formData, setFormData] = useState({
        name: '',
        description: '',
    });
    const [bulkSkills, setBulkSkills] = useState('');
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

    const handleBulkCreateSubmit = (e: any) => {
        e.preventDefault();
        router.post(route('admin.freelance.skills.bulkStore'), { skills: bulkSkills }, {
            onSuccess: () => {
                setIsBulkCreateOpen(false);
                setBulkSkills('');
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

    const handleApprove = (id: any) => {
        router.post(route('admin.freelance.skills.approve', id), {}, { preserveScroll: true });
    };

    const handleReject = (id: any) => {
        router.post(route('admin.freelance.skills.reject', id), {}, { preserveScroll: true });
    };

    const handleBlockUser = (userId: any, userName: string) => {
        if (confirm(`Are you sure you want to block ${userName} from adding new skills?`)) {
            router.post(route('admin.freelance.skills.block-user', userId), {}, { preserveScroll: true });
        }
    };

    const renderFormFields = () => (
        <div className="space-y-4 p-1">
            <div>
                <Label htmlFor="name">{__('freelance.skill_name', undefined, 'Skill Name')}</Label>
                <Input
                    id="name"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    required
                />
            </div>

            <div>
                <Label htmlFor="description">{__('freelance.description_optional', undefined, 'Description (Optional)')}</Label>
                <Input
                    id="description"
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                />
            </div>
        </div>
    );

    return (
        <AdminSidebarLayout title={__('freelance.admin_skills', undefined, 'Freelance Skills')} header={__('freelance.manage_skills', undefined, 'Manage Freelance Skills')}>
            <div className="mb-6 flex items-center justify-end gap-4">
                <form onSubmit={handleSearch} className="flex space-x-2">
                    <Input 
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder={__('freelance.search_skills', undefined, 'Search skills...')}
                        className="w-64"
                    />
                    <Button type="submit" variant="secondary">{__('freelance.search')}</Button>
                    {search && (
                        <Button type="button" variant="ghost" onClick={() => { setSearch(''); router.get(route('admin.freelance.skills.index')); }}>
                            {__('freelance.clear')}
                        </Button>
                    )}
                </form>

                <div className="flex gap-2">
                    <Dialog open={isBulkCreateOpen} onOpenChange={setIsBulkCreateOpen}>
                        <DialogTrigger asChild>
                            <Button variant="outline">{__('freelance.add_bulk_skills', undefined, 'Add Bulk Skills')}</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>{__('freelance.add_bulk_skills', undefined, 'Add Bulk Skills')}</DialogTitle>
                            </DialogHeader>
                            <form onSubmit={handleBulkCreateSubmit}>
                                <div className="space-y-4 p-1">
                                    <div>
                                        <Label htmlFor="bulkSkills">{__('freelance.bulk_skills_placeholder', undefined, 'Enter skills, one per line')}</Label>
                                        <textarea
                                            id="bulkSkills"
                                            className="flex min-h-[150px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                            value={bulkSkills}
                                            onChange={(e) => setBulkSkills(e.target.value)}
                                            placeholder="PHP&#10;Laravel&#10;ReactJS"
                                            required
                                        />
                                    </div>
                                </div>
                                <DialogFooter className="mt-6">
                                    <Button type="button" variant="outline" onClick={() => setIsBulkCreateOpen(false)}>
                                        {__('freelance.cancel')}
                                    </Button>
                                    <Button type="submit">{__('freelance.save_skills', undefined, 'Save Skills')}</Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>

                    <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                        <DialogTrigger asChild>
                            <Button>{__('freelance.create_skill', undefined, 'Create Skill')}</Button>
                        </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>{__('freelance.create_new_skill', undefined, 'Create New Skill')}</DialogTitle>
                        </DialogHeader>
                        <form onSubmit={handleCreateSubmit}>
                            {renderFormFields()}
                            <DialogFooter className="mt-6">
                                <Button type="button" variant="outline" onClick={() => setIsCreateOpen(false)}>
                                    {__('freelance.cancel')}
                                </Button>
                                <Button type="submit">{__('freelance.save_skill', undefined, 'Save Skill')}</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
                </div>
            </div>

            <div className="overflow-hidden rounded-lg bg-white shadow">
                <table className="w-full text-start text-sm">
                    <thead className="border-b bg-gray-50">
                        <tr>
                            <th className="p-4 font-medium text-gray-600">ID</th>
                            <th className="p-4 font-medium text-gray-600">{__('freelance.skill_name', undefined, 'Name')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('freelance.description')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('freelance.status')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('freelance.created_by', undefined, 'Created By')}</th>
                            <th className="p-4 font-medium text-gray-600 text-end">{__('freelance.actions')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {(skills.data as any).map((skill: any) => (
                            <tr key={skill.id} className="border-b hover:bg-gray-50">
                                <td className="p-4 font-medium text-gray-900">{skill.id}</td>
                                <td className="p-4 font-medium text-gray-900">{skill.name}</td>
                                <td className="p-4 text-gray-500">{skill.description || '-'}</td>
                                <td className="p-4">
                                    <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${
                                        skill.status === 'approved' ? 'bg-green-100 text-green-800' :
                                        skill.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                        'bg-red-100 text-red-800'
                                    }`}>
                                        {skill.status.charAt(0).toUpperCase() + skill.status.slice(1)}
                                    </span>
                                </td>
                                <td className="p-4 text-gray-500">
                                    {skill.creator ? skill.creator.name : __('freelance.system', undefined, 'System')}
                                </td>
                                <td className="p-4 text-end">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" className="h-8 w-8 p-0">
                                                <span className="sr-only">{__('general.open_menu')}</span>
                                                <MoreHorizontal className="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end" className="w-56">
                                            <DropdownMenuLabel>{__('freelance.actions')}</DropdownMenuLabel>
                                            <DropdownMenuSeparator />
                                            {skill.status === 'pending' && (
                                                <>
                                                    <DropdownMenuItem onClick={() => handleApprove(skill.id)} className="cursor-pointer">
                                                        <CheckCircle className="me-2 h-4 w-4 text-green-600" />
                                                        <span>{__('freelance.approve_skill', undefined, 'Approve')}</span>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem onClick={() => handleReject(skill.id)} className="cursor-pointer text-yellow-600">
                                                        <XCircle className="me-2 h-4 w-4" />
                                                        <span>{__('freelance.decline_skill', undefined, 'Decline')}</span>
                                                    </DropdownMenuItem>
                                                </>
                                            )}
                                            <DropdownMenuItem onClick={() => openEditModal(skill)} className="cursor-pointer">
                                                <Edit className="me-2 h-4 w-4 text-slate-900" />
                                                <span>{__('freelance.edit')}</span>
                                            </DropdownMenuItem>
                                            
                                            {skill.creator && (
                                                <DropdownMenuItem onClick={() => handleBlockUser(skill.creator.id, skill.creator.name)} className="cursor-pointer text-orange-600">
                                                    <Ban className="me-2 h-4 w-4" />
                                                    <span>{__('freelance.block_user_skills', undefined, 'Block User')}</span>
                                                </DropdownMenuItem>
                                            )}

                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem onClick={() => handleDelete(skill.id)} className="text-red-600 focus:text-red-600 focus:bg-red-50 cursor-pointer">
                                                <Trash2 className="me-2 h-4 w-4" />
                                                <span>{__('freelance.delete')}</span>
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </td>
                            </tr>
                        ))}
                        {(skills.data as any).length === 0 && (
                            <tr>
                                <td colSpan={6} className="p-4 text-center text-gray-500">
                                    {__('freelance.no_skills_found', undefined, 'No skills found.')}
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
                        <DialogTitle>{__('freelance.edit_skill', undefined, 'Edit Skill')}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleEditSubmit}>
                        {renderFormFields()}
                        <DialogFooter className="mt-6">
                            <Button type="button" variant="outline" onClick={() => setIsEditOpen(false)}>
                                {__('freelance.cancel')}
                            </Button>
                            <Button type="submit">{__('freelance.save_changes')}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AdminSidebarLayout>
    );
}

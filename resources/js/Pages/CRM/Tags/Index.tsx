import React, { useState } from 'react';
import CrmLayout from '@/Layouts/CrmLayout';
import { __ } from '@/lib/i18n';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Tag, Plus, Trash2, Edit } from 'lucide-react';
import { router } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog';
import { Label } from '@/Components/ui/label';

export default function TagsIndex({ tags }: { tags: any[] }) {
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        color: '#4f46e5' // default indigo
    });

    const presetColors = [
        '#ef4444', '#f97316', '#f59e0b', '#10b981', '#06b6d4', 
        '#3b82f6', '#4f46e5', '#8b5cf6', '#d946ef', '#f43f5e',
        '#64748b', '#334155'
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('crm.tags.store'), {
            onSuccess: () => {
                setIsCreateModalOpen(false);
                reset();
            }
        });
    };

    const handleDelete = (id: number) => {
        if (confirm(__('Are you sure you want to delete this tag?'))) {
            router.delete(route('crm.tags.destroy', id));
        }
    };

    return (
        <CrmLayout title={__('Tags & Attributes')} activeMenu="tags">
            <div className="flex flex-col h-full gap-6 p-8 pt-6">
                
                {/* Header */}
                <div className="flex justify-between items-end">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">{__('Tags & Attributes')}</h1>
                        <p className="text-sm text-muted-foreground mt-1">{__('Manage tags used to categorize leads and contacts.')}</p>
                    </div>
                    <Button onClick={() => setIsCreateModalOpen(true)} className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white">
                        <Plus size={16} />
                        {__('Create Tag')}
                    </Button>
                </div>

                {/* Content */}
                <Card className="flex-1 overflow-hidden border-slate-200 shadow-sm">
                    <CardHeader className="p-4 border-b border-slate-100 bg-slate-50/50">
                        <CardTitle className="font-semibold text-slate-800">{__('All Tags')}</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0 overflow-y-auto">
                        <table className="w-full text-sm text-left">
                            <thead className="text-xs text-slate-500 uppercase bg-slate-50/80 border-b">
                                <tr>
                                    <th className="px-6 py-4 font-medium">{__('Tag Name')}</th>
                                    <th className="px-6 py-4 font-medium">{__('Color')}</th>
                                    <th className="px-6 py-4 font-medium">{__('Created At')}</th>
                                    <th className="px-6 py-4 font-medium text-right">{__('Actions')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {tags && tags.length > 0 ? (
                                    tags.map((tag) => (
                                        <tr key={tag.id} className="border-b hover:bg-slate-50/50 transition-colors">
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-2 font-medium text-slate-900">
                                                    <Tag size={14} style={{ color: tag.color || '#4f46e5' }} />
                                                    {tag.name}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-2">
                                                    <div className="w-4 h-4 rounded-full border border-slate-200" style={{ backgroundColor: tag.color || '#4f46e5' }} />
                                                    <span className="font-mono text-xs text-slate-500">{tag.color || '#4f46e5'}</span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-slate-500">
                                                {new Date(tag.created_at).toLocaleDateString()}
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <Button 
                                                    variant="ghost" 
                                                    size="sm" 
                                                    onClick={() => handleDelete(tag.id)}
                                                    className="text-red-600 hover:text-red-700 hover:bg-red-50"
                                                >
                                                    <Trash2 size={16} />
                                                </Button>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={4} className="px-6 py-12 text-center text-slate-500">
                                            <div className="flex flex-col items-center justify-center gap-2">
                                                <Tag size={32} className="text-slate-300" />
                                                <p>{__('No tags found.')}</p>
                                                <Button variant="outline" size="sm" onClick={() => setIsCreateModalOpen(true)} className="mt-2">
                                                    {__('Create your first tag')}
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

            </div>

            <Dialog open={isCreateModalOpen} onOpenChange={setIsCreateModalOpen}>
                <DialogContent>
                    <form onSubmit={handleSubmit}>
                        <DialogHeader>
                            <DialogTitle>{__('Create New Tag')}</DialogTitle>
                        </DialogHeader>
                        
                        <div className="space-y-4 py-4">
                            <div className="space-y-2">
                                <Label htmlFor="name">{__('Tag Name')}</Label>
                                <Input 
                                    id="name" 
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder={__('e.g. VIP Customer')}
                                    required
                                />
                                {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
                            </div>
                            
                            <div className="space-y-2">
                                <Label>{__('Color')}</Label>
                                <div className="flex flex-wrap gap-2 mt-2">
                                    {presetColors.map((color) => (
                                        <button
                                            key={color}
                                            type="button"
                                            onClick={() => setData('color', color)}
                                            className={`w-8 h-8 rounded-full border-2 transition-transform hover:scale-110 ${data.color === color ? 'border-slate-900 ring-2 ring-slate-200' : 'border-transparent'}`}
                                            style={{ backgroundColor: color }}
                                        />
                                    ))}
                                </div>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsCreateModalOpen(false)}>
                                {__('Cancel')}
                            </Button>
                            <Button type="submit" disabled={processing} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                                {__('Save Tag')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </CrmLayout>
    );
}

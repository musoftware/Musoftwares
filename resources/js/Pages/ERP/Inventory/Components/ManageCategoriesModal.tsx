import React, { useState, useEffect } from 'react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Tags, Plus, Trash2, Loader2 } from 'lucide-react';
import TextInput from '@/Components/TextInput';
import axios from 'axios';
import { toast } from 'sonner';
import { __ } from '@/lib/i18n';

export function ManageCategoriesModal() {
    const [open, setOpen] = useState(false);
    const [categories, setCategories] = useState<any[]>([]);
    const [loading, setLoading] = useState(false);
    const [newCategoryName, setNewCategoryName] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    const t = (key: string, fallback: string) => {
        if (typeof window !== 'undefined' && typeof window.__ === 'function') {
            return window.__(key);
        }
        return fallback;
    };

    useEffect(() => {
        if (open) {
            fetchCategories();
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open]);

    const fetchCategories = async () => {
        setLoading(true);
        try {
            const response = await axios.get(route('erp.inventory.categories.index'));
            setCategories(response.data);
        } catch (error: any) {
            toast.error(t('erp.error_fetching_categories', 'Error fetching categories'));
        } finally {
            setLoading(false);
        }
    };

    const handleAdd = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!newCategoryName.trim()) return;

        setIsSubmitting(true);
        try {
            const response = await axios.post(route('erp.inventory.categories.store'), {
                name: newCategoryName
            });
            setCategories([...categories, response.data.category]);
            setNewCategoryName('');
            toast.success(response.data.message);
        } catch (error: any) {
            toast.error((error as any).response?.data?.message || t('erp.error_creating_category', 'Error creating category'));
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleDelete = async (id: number) => {
        if (!confirm(t('erp.confirm_delete_category', 'Are you sure you want to delete this category?'))) return;

        try {
            const response = await axios.delete(route('erp.inventory.categories.destroy', id));
            setCategories(categories.filter(c => c.id !== id));
            toast.success(response.data.message);
        } catch (error: any) {
            toast.error((error as any).response?.data?.message || t('erp.error_deleting_category', 'Error deleting category'));
        }
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" className="w-full sm:w-auto shadow-sm border-slate-200 text-slate-700 hover:bg-slate-50">
                    <Tags className="me-2 h-4 w-4" />
                    <span className="whitespace-nowrap">{t('erp.manage_categories', 'Categories')}</span>
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{t('erp.manage_categories', 'Manage Categories')}</DialogTitle>
                </DialogHeader>
                
                <div className="py-4">
                    <form onSubmit={handleAdd} className="flex gap-2 mb-6">
                        <TextInput
                            type="text"
                            value={newCategoryName}
                            onChange={(e) => setNewCategoryName(e.target.value)}
                            placeholder={t('erp.new_category_name', 'New category name...')}
                            className="flex-1"
                            required
                        />
                        <Button type="submit" disabled={isSubmitting || !newCategoryName.trim()}>
                            {isSubmitting ? <Loader2 className="h-4 w-4 animate-spin" /> : <Plus className="h-4 w-4 me-2" />}
                            {t('erp.add', 'Add')}
                        </Button>
                    </form>

                    <div className="max-h-[300px] overflow-y-auto space-y-2">
                        {loading ? (
                            <div className="flex justify-center py-4">
                                <Loader2 className="h-6 w-6 animate-spin text-gray-400" />
                            </div>
                        ) : categories.length === 0 ? (
                            <div className="text-center py-4 text-gray-500 text-sm">
                                {t('erp.no_categories', 'No categories found')}
                            </div>
                        ) : (
                            categories.map(category => (
                                <div key={category.id} className="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-100">
                                    <span className="font-medium text-sm text-slate-700">{category.name}</span>
                                    <Button variant="ghost" size="sm" className="h-8 w-8 p-0 text-rose-500 hover:text-rose-700 hover:bg-rose-50" onClick={() => handleDelete(category.id)}>
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </div>
                            ))
                        )}
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}

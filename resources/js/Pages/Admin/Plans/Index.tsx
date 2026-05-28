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
import { Plus, Trash2 } from 'lucide-react';
import { formatMoney } from '@/lib/utils';

export default function Index({ plans }) {
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [editingPlan, setEditingPlan] = useState(null);
    const [formData, setFormData] = useState({
        module: 'erp',
        name: '',
        price: '',
        billing: 'monthly',
        features: [],
        is_active: true,
    });
    const [newFeature, setNewFeature] = useState('');

    const handleCreateSubmit = (e) => {
        e.preventDefault();
        router.post(route('admin.plans.store'), formData, {
            onSuccess: () => {
                setIsCreateOpen(false);
                resetForm();
            },
        });
    };

    const handleEditSubmit = (e) => {
        e.preventDefault();
        router.put(route('admin.plans.update', editingPlan.id), formData, {
            onSuccess: () => {
                setIsEditOpen(false);
                setEditingPlan(null);
                resetForm();
            },
        });
    };

    const resetForm = () => {
        setFormData({
            module: 'erp',
            name: '',
            price: '',
            billing: 'monthly',
            features: [],
            is_active: true,
        });
        setNewFeature('');
    };

    const openEditModal = (plan) => {
        setEditingPlan(plan);
        setFormData({
            module: plan.module,
            name: plan.name,
            price: plan.price,
            billing: plan.billing,
            features: Array.isArray(plan.features) ? plan.features : [],
            is_active: plan.is_active,
        });
        setIsEditOpen(true);
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this plan permanently?')) {
            router.delete(route('admin.plans.destroy', id));
        }
    };

    const addFeature = () => {
        if (newFeature.trim() !== '') {
            setFormData({ ...formData, features: [...formData.features, newFeature.trim()] });
            setNewFeature('');
        }
    };

    const removeFeature = (index) => {
        const updatedFeatures = formData.features.filter((_, i) => i !== index);
        setFormData({ ...formData, features: updatedFeatures });
    };

    const renderFormFields = () => (
        <div className="space-y-4 max-h-[60vh] overflow-y-auto p-1">
            <div className="grid grid-cols-2 gap-4">
                <div>
                    <Label htmlFor="module">Module</Label>
                    <select
                        id="module"
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                        value={formData.module}
                        onChange={(e) => setFormData({ ...formData, module: e.target.value })}
                        required
                    >
                        <option value="erp">ERP</option>
                        <option value="marketplace">Marketplace</option>
                        <option value="core">Core</option>
                    </select>
                </div>
                <div>
                    <Label htmlFor="billing">Billing Cycle</Label>
                    <select
                        id="billing"
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                        value={formData.billing}
                        onChange={(e) => setFormData({ ...formData, billing: e.target.value })}
                        required
                    >
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                        <option value="once">Once</option>
                    </select>
                </div>
            </div>
            
            <div>
                <Label htmlFor="name">Plan Name</Label>
                <Input
                    id="name"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    required
                />
            </div>

            <div>
                <Label htmlFor="price">Price</Label>
                <Input
                    id="price"
                    type="number"
                    step="0.01"
                    value={formData.price}
                    onChange={(e) => setFormData({ ...formData, price: e.target.value })}
                    required
                />
            </div>

            <div>
                <Label>Features</Label>
                <div className="flex space-x-2 mt-1 mb-2">
                    <Input
                        value={newFeature}
                        onChange={(e) => setNewFeature(e.target.value)}
                        placeholder="Add a new feature..."
                        onKeyDown={(e) => {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                addFeature();
                            }
                        }}
                    />
                    <Button type="button" onClick={addFeature} variant="secondary" size="icon">
                        <Plus className="h-4 w-4" />
                    </Button>
                </div>
                {formData.features.length > 0 ? (
                    <ul className="space-y-2 mt-2">
                        {formData.features.map((feature, index) => (
                            <li key={index} className="flex justify-between items-center bg-gray-50 px-3 py-2 rounded-md border text-sm">
                                <span>{feature}</span>
                                <button type="button" onClick={() => removeFeature(index)} className="text-red-500 hover:text-red-700">
                                    <Trash2 className="h-4 w-4" />
                                </button>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <p className="text-sm text-gray-500 mt-2">No features added yet.</p>
                )}
            </div>

            <div className="flex items-center space-x-2 mt-4">
                <input
                    type="checkbox"
                    id="is_active"
                    checked={formData.is_active}
                    onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                    className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <Label htmlFor="is_active">Plan is Active</Label>
            </div>
        </div>
    );

    return (
        <AdminSidebarLayout title="Subscription Plans" header="Plans Manager">
            <div className="mb-6 flex items-center justify-between">
                <div></div>
                <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                    <DialogTrigger asChild>
                        <Button>Create Plan</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Create New Plan</DialogTitle>
                        </DialogHeader>
                        <form onSubmit={handleCreateSubmit}>
                            {renderFormFields()}
                            <DialogFooter className="mt-6">
                                <Button type="button" variant="outline" onClick={() => setIsCreateOpen(false)}>
                                    Cancel
                                </Button>
                                <Button type="submit">Save Plan</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <div className="overflow-hidden rounded-lg bg-white shadow">
                <table className="w-full text-left text-sm">
                    <thead className="border-b bg-gray-50">
                        <tr>
                            <th className="p-4 font-medium text-gray-600">Module</th>
                            <th className="p-4 font-medium text-gray-600">Name</th>
                            <th className="p-4 font-medium text-gray-600">Price</th>
                            <th className="p-4 font-medium text-gray-600">Billing</th>
                            <th className="p-4 font-medium text-gray-600">Status</th>
                            <th className="p-4 font-medium text-gray-600 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {plans.map((plan) => (
                            <tr key={plan.id} className="border-b hover:bg-gray-50">
                                <td className="p-4 font-medium text-gray-900 uppercase">{plan.module}</td>
                                <td className="p-4 font-medium text-gray-900">{plan.name}</td>
                                <td className="p-4">{formatMoney(plan.price, 'USD')}</td>
                                <td className="p-4 capitalize">{plan.billing}</td>
                                <td className="p-4">
                                    <span className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 ${plan.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                                        {plan.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                </td>
                                <td className="p-4 space-x-2 text-right">
                                    <Button variant="outline" size="sm" onClick={() => openEditModal(plan)}>
                                        Edit
                                    </Button>
                                    <Button variant="destructive" size="sm" onClick={() => handleDelete(plan.id)}>
                                        Delete
                                    </Button>
                                </td>
                            </tr>
                        ))}
                        {plans.length === 0 && (
                            <tr>
                                <td colSpan="6" className="p-4 text-center text-gray-500">
                                    No plans found.
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
                        <DialogTitle>Edit Plan</DialogTitle>
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

import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Trash2, Edit, Plus, DollarSign, TrendingDown, TrendingUp, Users, CheckCircle2, AlertCircle, Clock } from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/Components/ui/dialog";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

export default function Index({ entries, categories, users, currentTab, stats }) {
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [newEntry, setNewEntry] = useState({
        title: '',
        amount: '',
        category_id: categories.length > 0 ? categories[0].id : '',
        user_id: '',
        is_recurring: false,
        recurrence_interval: 'monthly',
        status: 'pending',
        type: currentTab === 'salaries' ? 'salary' : currentTab
    });

    const handleTabChange = (tab) => {
        router.get(route('finance.index'), { tab }, { preserveState: true });
    };

    const handleCreate = (e) => {
        e.preventDefault();
        router.post(route('finance.store'), {
            ...newEntry,
            currency: 'EGP',
            transaction_date: newEntry.is_recurring ? null : new Date().toISOString().slice(0, 10),
            next_due_date: newEntry.is_recurring ? new Date().toISOString().slice(0, 10) : null
        }, {
            onSuccess: () => {
                setIsCreateOpen(false);
                setNewEntry({ ...newEntry, title: '', amount: '' });
            }
        });
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this record?')) {
            router.delete(route('finance.destroy', id));
        }
    };

    const handleMarkPaid = (id) => {
        router.post(route('finance.mark-paid', id));
    };

    const getStatusBadge = (status) => {
        switch (status) {
            case 'completed': return <span className="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-medium flex items-center w-fit"><CheckCircle2 className="w-3 h-3 mr-1"/> Paid</span>;
            case 'pending': return <span className="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-medium flex items-center w-fit"><Clock className="w-3 h-3 mr-1"/> Pending</span>;
            case 'overdue': return <span className="bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-medium flex items-center w-fit"><AlertCircle className="w-3 h-3 mr-1"/> Overdue</span>;
            case 'cancelled': return <span className="bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs font-medium">Cancelled</span>;
            default: return null;
        }
    };

    return (
        <AdminSidebarLayout title="Financial Operations" header="Financial Ledger">
            
            {/* Top Stats Cards */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div className="bg-white p-6 rounded-xl border shadow-sm flex items-center">
                    <div className="bg-blue-100 p-4 rounded-full mr-4">
                        <TrendingUp className="w-6 h-6 text-blue-600" />
                    </div>
                    <div>
                        <p className="text-sm text-gray-500 font-medium uppercase">Monthly Income</p>
                        <h3 className="text-2xl font-bold text-gray-900">{parseFloat(stats.total_monthly_income).toLocaleString()} EGP</h3>
                    </div>
                </div>
                <div className="bg-white p-6 rounded-xl border shadow-sm flex items-center">
                    <div className="bg-red-100 p-4 rounded-full mr-4">
                        <TrendingDown className="w-6 h-6 text-red-600" />
                    </div>
                    <div>
                        <p className="text-sm text-gray-500 font-medium uppercase">Monthly Expenses</p>
                        <h3 className="text-2xl font-bold text-gray-900">{parseFloat(stats.total_monthly_expenses).toLocaleString()} EGP</h3>
                    </div>
                </div>
                <div className="bg-white p-6 rounded-xl border shadow-sm flex items-center">
                    <div className="bg-orange-100 p-4 rounded-full mr-4">
                        <Users className="w-6 h-6 text-orange-600" />
                    </div>
                    <div>
                        <p className="text-sm text-gray-500 font-medium uppercase">Monthly Payroll</p>
                        <h3 className="text-2xl font-bold text-gray-900">{parseFloat(stats.total_monthly_salaries).toLocaleString()} EGP</h3>
                    </div>
                </div>
            </div>

            {/* Tabs */}
            <div className="flex border-b border-gray-200 mb-6">
                <button
                    onClick={() => handleTabChange('expenses')}
                    className={`py-3 px-6 font-medium text-sm border-b-2 ${currentTab === 'expenses' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'}`}
                >
                    Costs & Expenses
                </button>
                <button
                    onClick={() => handleTabChange('income')}
                    className={`py-3 px-6 font-medium text-sm border-b-2 ${currentTab === 'income' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'}`}
                >
                    Income Streams
                </button>
                <button
                    onClick={() => handleTabChange('salaries')}
                    className={`py-3 px-6 font-medium text-sm border-b-2 ${currentTab === 'salaries' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'}`}
                >
                    Employee Payroll
                </button>
            </div>

            {/* Actions */}
            <div className="flex justify-between items-center mb-6">
                <h3 className="text-lg font-bold text-gray-900 capitalize">{currentTab} Ledger</h3>
                
                <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                    <DialogTrigger asChild>
                        <Button className="bg-gray-900"><Plus className="w-4 h-4 mr-2" /> Add {currentTab === 'salaries' ? 'Salary' : currentTab === 'expenses' ? 'Expense' : 'Income'}</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <form onSubmit={handleCreate}>
                            <DialogHeader>
                                <DialogTitle>Add New Record</DialogTitle>
                            </DialogHeader>
                            <div className="space-y-4 py-4">
                                <div className="space-y-2">
                                    <Label>Title / Name</Label>
                                    <Input required value={newEntry.title} onChange={e => setNewEntry({...newEntry, title: e.target.value})} placeholder="e.g. AWS Hosting" />
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label>Amount (EGP)</Label>
                                        <Input type="number" step="0.01" required value={newEntry.amount} onChange={e => setNewEntry({...newEntry, amount: e.target.value})} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Category</Label>
                                        <select className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" value={newEntry.category_id} onChange={e => setNewEntry({...newEntry, category_id: e.target.value})}>
                                            {categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                                        </select>
                                    </div>
                                </div>
                                
                                {currentTab === 'salaries' && (
                                    <div className="space-y-2">
                                        <Label>Employee</Label>
                                        <select className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" value={newEntry.user_id} onChange={e => setNewEntry({...newEntry, user_id: e.target.value})}>
                                            <option value="">Select Employee...</option>
                                            {users.map(u => <option key={u.id} value={u.id}>{u.name} ({u.email})</option>)}
                                        </select>
                                    </div>
                                )}
                            </div>
                            <DialogFooter>
                                <Button type="submit">Save Record</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            {/* Data Table */}
            <div className="bg-white border rounded-xl shadow-sm overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {entries.data.map((entry) => (
                            <tr key={entry.id} className="hover:bg-gray-50">
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <div className="text-sm font-medium text-gray-900">{entry.title}</div>
                                    {entry.user && <div className="text-xs text-gray-500 mt-1">For: {entry.user.name}</div>}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <div className="text-sm font-bold text-gray-900">{parseFloat(entry.amount).toLocaleString()} {entry.currency || 'EGP'}</div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <div className="text-sm text-gray-500">{entry.category?.name || 'Uncategorized'}</div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <span className="text-sm text-gray-900">
                                        {new Date(entry.created_at).toLocaleDateString()}
                                    </span>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    {getStatusBadge(entry.status)}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    {entry.status === 'pending' && (
                                        <Button variant="outline" size="sm" className="mr-2 border-green-200 text-green-700 hover:bg-green-50" onClick={() => handleMarkPaid(entry.id)}>
                                            <CheckCircle2 className="w-4 h-4 mr-1" /> Mark Paid
                                        </Button>
                                    )}
                                    <Button variant="ghost" size="sm" className="text-red-600 hover:text-red-900 hover:bg-red-50" onClick={() => handleDelete(entry.id)}>
                                        <Trash2 className="w-4 h-4" />
                                    </Button>
                                </td>
                            </tr>
                        ))}
                        {entries.data.length === 0 && (
                            <tr>
                                <td colSpan="6" className="px-6 py-12 text-center text-gray-500">
                                    <DollarSign className="w-12 h-12 mx-auto text-gray-300 mb-3" />
                                    <h3 className="text-lg font-medium text-gray-900">No records found</h3>
                                    <p className="mt-1">Click "Add Record" to start tracking your finances.</p>
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {/* Pagination Controls could go here */}
            
        </AdminSidebarLayout>
    );
}

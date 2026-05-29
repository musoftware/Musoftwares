import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { ArrowDownLeft, ArrowUpRight, Undo2, Receipt, Coins, Plus, Trash2 } from 'lucide-react';

interface Props {
    user: any;
    selectedProject: any;
    type: string;
    currencies: any[];
    businessCurrency: any;
}

export default function Create({ user, selectedProject, type, currencies, businessCurrency }: Props) {
    const defaultTab = () => {
        if (type === 'receive') return 'timer-received';
        if (type === 'send-money' || type === 'send') return 'send';
        if (type === 'refund') return 'refund';
        if (type === 'charge') return 'timer-due';
        if (type === 'earn' || type === 'earned') return 'earned';
        return 'timer-received';
    };

    const [activeTab, setActiveTab] = useState<string>(defaultTab());

    const { data, setData, post, processing, errors, reset } = useForm({
        user: user.id,
        project: selectedProject?.id || '',
        type: activeTab,
        data: [
            { amount: '', reason: '', currency: user.currency || businessCurrency.id }
        ]
    });

    const handleTabChange = (value: string) => {
        setActiveTab(value);
        setData('type', value);
        // Also update URL query param without full reload
        router.visit(route('transactions.create', { 
            user: user.id, 
            project: selectedProject?.id, 
            type: value.replace('timer-received', 'receive').replace('timer-due', 'charge').replace('earned', 'earn') 
        }), { preserveState: true, preserveScroll: true, replace: true });
    };

    const addRow = () => {
        setData('data', [...data.data, { amount: '', reason: '', currency: user.currency || businessCurrency.id }]);
    };

    const removeRow = (index: number) => {
        if (data.data.length > 1) {
            const newData = [...data.data];
            newData.splice(index, 1);
            setData('data', newData);
        }
    };

    const updateRow = (index: number, field: string, value: any) => {
        const newData = [...data.data];
        newData[index] = { ...newData[index], [field]: value };
        setData('data', newData);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('transactions.store'), {
            onSuccess: () => {
                reset('data');
                setData('data', [{ amount: '', reason: '', currency: user.currency || businessCurrency.id }]);
            }
        });
    };

    return (
        <AdminLayout>
            <Head title={`Adjust Wallet: ${user.name}`} />

            <div className="max-w-4xl mx-auto py-6">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Wallet Adjustments</h1>
                        <p className="text-muted-foreground">Manage transactions for {user.name} {selectedProject ? `(Project: ${selectedProject.project_name})` : ''}</p>
                    </div>
                </div>

                <Tabs value={activeTab} onValueChange={handleTabChange} className="w-full">
                    <TabsList className="grid w-full grid-cols-5 mb-6">
                        <TabsTrigger value="timer-received" className="flex items-center gap-2">
                            <ArrowDownLeft className="h-4 w-4 text-green-500" />
                            <span className="hidden sm:inline">Receive</span>
                        </TabsTrigger>
                        <TabsTrigger value="timer-due" className="flex items-center gap-2">
                            <Receipt className="h-4 w-4 text-orange-500" />
                            <span className="hidden sm:inline">Charge</span>
                        </TabsTrigger>
                        <TabsTrigger value="send" className="flex items-center gap-2">
                            <ArrowUpRight className="h-4 w-4 text-blue-500" />
                            <span className="hidden sm:inline">Send</span>
                        </TabsTrigger>
                        <TabsTrigger value="refund" className="flex items-center gap-2">
                            <Undo2 className="h-4 w-4 text-red-500" />
                            <span className="hidden sm:inline">Refund</span>
                        </TabsTrigger>
                        <TabsTrigger value="earned" className="flex items-center gap-2">
                            <Coins className="h-4 w-4 text-purple-500" />
                            <span className="hidden sm:inline">Add Earn</span>
                        </TabsTrigger>
                    </TabsList>

                    <Card>
                        <CardHeader>
                            <CardTitle>
                                {activeTab === 'timer-received' && 'Receive Payment'}
                                {activeTab === 'timer-due' && 'Create Charge (Due Amount)'}
                                {activeTab === 'send' && 'Send Money'}
                                {activeTab === 'refund' && 'Refund Client'}
                                {activeTab === 'earned' && 'Add Earnings'}
                            </CardTitle>
                            <CardDescription>
                                Add one or more transaction entries to {user.name}'s wallet.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-6">
                                {data.data.map((row, index) => (
                                    <div key={index} className="flex flex-col sm:flex-row gap-4 items-start sm:items-end p-4 border rounded-lg bg-muted/20">
                                        <div className="flex-1 w-full space-y-2">
                                            <Label>Reason / Note</Label>
                                            <Input
                                                required
                                                value={row.reason}
                                                onChange={(e) => updateRow(index, 'reason', e.target.value)}
                                                placeholder="e.g. Deposit for Project X"
                                            />
                                        </div>
                                        <div className="w-full sm:w-32 space-y-2">
                                            <Label>Amount</Label>
                                            <Input
                                                required
                                                type="number"
                                                min="0.01"
                                                step="0.01"
                                                value={row.amount}
                                                onChange={(e) => updateRow(index, 'amount', e.target.value)}
                                                placeholder="0.00"
                                            />
                                        </div>
                                        <div className="w-full sm:w-40 space-y-2">
                                            <Label>Currency</Label>
                                            <Select
                                                value={String(row.currency)}
                                                onValueChange={(val) => updateRow(index, 'currency', val)}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Currency" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {currencies.map((c) => (
                                                        <SelectItem key={c.id} value={String(c.id)}>
                                                            {c.currency} ({c.symbol})
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        
                                        {data.data.length > 1 && (
                                            <Button 
                                                type="button" 
                                                variant="destructive" 
                                                size="icon" 
                                                onClick={() => removeRow(index)}
                                                className="shrink-0"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        )}
                                    </div>
                                ))}

                                <div className="flex justify-between items-center">
                                    <Button type="button" variant="outline" onClick={addRow} size="sm">
                                        <Plus className="h-4 w-4 mr-2" /> Add Another Entry
                                    </Button>

                                    <Button type="submit" disabled={processing}>
                                        {processing ? 'Processing...' : 'Save Transactions'}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </Tabs>
            </div>
        </AdminLayout>
    );
}

import React, { useState } from 'react';
import ERPLayout from '@/Layouts/ERPLayout';
import { Head, useForm } from '@inertiajs/react';
import { CheckSquare, Plus, Edit2, Trash2 } from 'lucide-react';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import { toast } from 'sonner';

export default function WorkflowIndex({ workflows }: { workflows: any[] }) {
    const [isAddOpen, setIsAddOpen] = useState(false);
    
    const { data, setData, post, processing, reset, errors } = useForm({
        name: '',
        description: '',
        module_type: 'invoice',
        is_active: true,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('erp.approvals.workflows.store'), {
            onSuccess: () => {
                setIsAddOpen(false);
                reset();
                toast.success('Workflow created successfully');
            },
        });
    };

    return (
        <ERPLayout title="Workflows">
            <Head title="Workflows" />
            <div className="space-y-6">
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="text-2xl font-bold tracking-tight">Approval Workflows</h2>
                        <p className="text-muted-foreground">Manage multi-step approval workflows across different modules.</p>
                    </div>
                    <Button onClick={() => setIsAddOpen(true)}>
                        <Plus className="mr-2 h-4 w-4" /> Add Workflow
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Workflows</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Workflow Name</TableHead>
                                    <TableHead>Module Type</TableHead>
                                    <TableHead>Steps Count</TableHead>
                                    <TableHead>Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {workflows.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={4} className="text-center py-8 text-muted-foreground">
                                            No workflows configured.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    workflows.map((wf) => (
                                        <TableRow key={wf.id}>
                                            <TableCell className="font-medium">
                                                {wf.name}
                                                <div className="text-xs text-muted-foreground">{wf.description}</div>
                                            </TableCell>
                                            <TableCell className="capitalize">{wf.module_type}</TableCell>
                                            <TableCell>{wf.steps?.length || 0} step(s)</TableCell>
                                            <TableCell>
                                                <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${wf.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                                                    {wf.is_active ? 'Active' : 'Inactive'}
                                                </span>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>

            <Dialog open={isAddOpen} onOpenChange={setIsAddOpen}>
                <DialogContent>
                    <form onSubmit={submit}>
                        <DialogHeader>
                            <DialogTitle>Add New Workflow</DialogTitle>
                        </DialogHeader>
                        <div className="grid gap-4 py-4">
                            <div className="space-y-2">
                                <Label>Workflow Name</Label>
                                <Input value={data.name} onChange={e => setData('name', e.target.value)} required />
                            </div>
                            <div className="space-y-2">
                                <Label>Module Type</Label>
                                <select 
                                    className="w-full border rounded p-2"
                                    value={data.module_type}
                                    onChange={e => setData('module_type', e.target.value)}
                                >
                                    <option value="invoice">Invoice</option>
                                    <option value="expense">Expense</option>
                                    <option value="asset_disposal">Asset Disposal</option>
                                    <option value="leave_request">Leave Request</option>
                                </select>
                            </div>
                            <div className="space-y-2">
                                <Label>Description</Label>
                                <Input value={data.description} onChange={e => setData('description', e.target.value)} />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsAddOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={processing}>Save</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </ERPLayout>
    );
}

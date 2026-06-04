import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { toast } from 'sonner';
import { Plus, CheckCircle, FileText, Settings2, Edit } from 'lucide-react';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { __ } from '@/lib/i18n';

export default function Index({ members, payslips, filters, auth, paymentMethods }: any) {
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('payroll');
    const currency = auth?.user?.tenant?.currency || { code: 'USD', symbol: '$' };

    const [isGenerateModalOpen, setIsGenerateModalOpen] = useState(false);
    const [isContractModalOpen, setIsContractModalOpen] = useState(false);
    const [isItemsModalOpen, setIsItemsModalOpen] = useState(false);
    const [isPayModalOpen, setIsPayModalOpen] = useState(false);
    const [selectedMember, setSelectedMember] = useState<any>(null);
    const [selectedPayslip, setSelectedPayslip] = useState<any>(null);
    const [payslipItems, setPayslipItems] = useState<any[]>([]);
    const [workedDays, setWorkedDays] = useState(30);
    const [absentDays, setAbsentDays] = useState(0);
    const [paymentMethodId, setPaymentMethodId] = useState('');
    const [baseSalary, setBaseSalary] = useState('');
    const [isProcessing, setIsProcessing] = useState(false);

    const openItemsModal = (payslip: any) => {
        setSelectedPayslip(payslip);
        setPayslipItems(payslip.items || []);
        setWorkedDays(payslip.worked_days || 30);
        setAbsentDays(payslip.absent_days || 0);
        setIsItemsModalOpen(true);
    };

    const openPayModal = (payslip: any) => {
        setSelectedPayslip(payslip);
        if (paymentMethods && paymentMethods.length > 0) {
            setPaymentMethodId(paymentMethods[0].id.toString());
        }
        setIsPayModalOpen(true);
    };

    const handleSaveItems = () => {
        setIsProcessing(true);
        window.axios.post(route('erp.payroll.payslips.items.update', selectedPayslip.id), {
            worked_days: workedDays,
            absent_days: absentDays,
            items: payslipItems,
        })
        .then(res => {
            toast.success(res.data.message || __('general.payslip_items_updated'));
            setIsItemsModalOpen(false);
            router.reload({ only: ['payslips'] });
        })
        .catch(err => {
            toast.error(err.response?.data?.message || __('general.failed_to_update_items'));
        })
        .finally(() => {
            setIsProcessing(false);
        });
    };

    const addPayslipItem = (type: 'bonus' | 'deduction') => {
        setPayslipItems([...payslipItems, { type, amount: 0, description: '' }]);
    };

    const updatePayslipItem = (index: number, field: string, value: any) => {
        const newItems = [...payslipItems];
        newItems[index][field] = value;
        setPayslipItems(newItems);
    };

    const removePayslipItem = (index: number) => {
        setPayslipItems(payslipItems.filter((_, i) => i !== index));
    };

    const openContractModal = (member: any) => {
        setSelectedMember(member);
        setBaseSalary(member.contract?.base_salary || '');
        setIsContractModalOpen(true);
    };

    const handleSaveContract = () => {
        setIsProcessing(true);
        window.axios.post(route('erp.payroll.contract.update'), {
            member_id: selectedMember.id,
            base_salary: baseSalary,
        })
        .then(res => {
            toast.success(res.data.message || __('freelance.contract_updated'));
            setIsContractModalOpen(false);
            router.reload({ only: ['members'] });
        })
        .catch(err => {
            toast.error(err.response?.data?.message || __('freelance.failed_to_update_contract'));
        })
        .finally(() => {
            setIsProcessing(false);
        });
    };

    const handleGeneratePayroll = (e: React.FormEvent) => {
        e.preventDefault();
        const formData = new FormData(e.target as HTMLFormElement);
        setIsProcessing(true);

        window.axios.post(route('erp.payroll.generate'), {
            month: formData.get('month'),
            year: formData.get('year'),
        })
        .then(res => {
            toast.success(res.data.message || __('general.payroll_generated'));
            setIsGenerateModalOpen(false);
            router.reload({ only: ['payslips'] });
        })
        .catch(err => {
            toast.error(err.response?.data?.message || __('general.generation_failed'));
        })
        .finally(() => {
            setIsProcessing(false);
        });
    };

    const handleMarkPaid = () => {
        if (!paymentMethodId) {
            toast.error(__('payment.please_select_a_payment_method'));
            return;
        }
        setIsProcessing(true);
        window.axios.post(route('erp.payroll.payslips.mark_paid', selectedPayslip.id), {
            payment_method_id: paymentMethodId
        })
        .then(res => {
            toast.success(res.data.message || __('general.marked_as_paid'));
            setIsPayModalOpen(false);
            router.reload({ only: ['payslips'] });
        })
        .catch(err => {
            toast.error(err.response?.data?.message || __('payment.payment_failed_2'));
        })
        .finally(() => {
            setIsProcessing(false);
        });
    };

    return (
        <ERPLayout
            title={__('general.payroll_system')}
            workspaceName={workspaceName}
            tenantId={tenantId}
            menuItems={menuItems}
            lockedAddons={lockedAddons}
        >
            <Head title={__('general.payroll_system')} />
            
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-semibold text-slate-900">{__('general.payroll_system')}</h1>
                <Button onClick={() => setIsGenerateModalOpen(true)}>
                    <Plus className="w-4 h-4 mr-2" />
                    {__('general.run_payroll')}
                </Button>
            </div>

            <Tabs defaultValue="payslips" className="w-full">
                <TabsList className="mb-4">
                    <TabsTrigger value="payslips">{__('general.payslips')}</TabsTrigger>
                    <TabsTrigger value="contracts">{__('freelance.employee_contracts')}</TabsTrigger>
                </TabsList>

                <TabsContent value="payslips">
                    <Card>
                        <CardHeader>
                            <CardTitle>{__('general.monthly_payslips')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>{__('general.employee')}</TableHead>
                                        <TableHead>{__('general.period')}</TableHead>
                                        <TableHead>{__('general.base')}</TableHead>
                                        <TableHead>{__('payment.net_pay')}</TableHead>
                                        <TableHead>{__('general.status')}</TableHead>
                                        <TableHead className="text-right">{__('general.actions')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {payslips.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="text-center text-slate-500 py-8">
                                                {__('general.no_payslips_found_for_this')}
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        payslips.map((payslip: any) => (
                                            <TableRow key={payslip.id}>
                                                <TableCell className="font-medium">{payslip.member?.name}</TableCell>
                                                <TableCell>{payslip.month} / {payslip.year}</TableCell>
                                                <TableCell>{formatCurrency(payslip.base_amount, payslip.currency || currency)}</TableCell>
                                                <TableCell className="font-bold">{formatCurrency(payslip.net_amount, payslip.currency || currency)}</TableCell>
                                                <TableCell>
                                                    <StatusBadge status={payslip.status} />
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {payslip.status === 'draft' && (
                                                        <div className="flex items-center justify-end gap-2">
                                                            <Button size="sm" variant="outline" onClick={() => openItemsModal(payslip)} disabled={isProcessing}>
                                                                <Edit className="w-4 h-4 mr-1" />
                                                                {__('general.edit')}
                                                            </Button>
                                                            <Button size="sm" variant="default" onClick={() => openPayModal(payslip)} disabled={isProcessing}>
                                                                <CheckCircle className="w-4 h-4 mr-1" />
                                                                {__('general.mark_paid')}
                                                            </Button>
                                                        </div>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="contracts">
                    <Card>
                        <CardHeader>
                            <CardTitle>{__('general.team_base_salaries')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>{__('general.employee')}</TableHead>
                                        <TableHead>{__('general.role')}</TableHead>
                                        <TableHead>{__('general.base_salary')}</TableHead>
                                        <TableHead className="text-right">{__('general.actions')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {members.map((member: any) => (
                                        <TableRow key={member.id}>
                                            <TableCell className="font-medium">
                                                {member.name}
                                                <span className="block text-xs text-slate-500">{member.email}</span>
                                            </TableCell>
                                            <TableCell className="capitalize">{member.role}</TableCell>
                                            <TableCell>
                                                {member.contract ? (
                                                    <span className="font-semibold">{formatCurrency(member.contract.base_salary, member.contract.currency || currency)}</span>
                                                ) : (
                                                    <span className="text-slate-400 italic">{__('general.not_set')}</span>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button size="sm" variant="ghost" onClick={() => openContractModal(member)}>
                                                    <Settings2 className="w-4 h-4 mr-2" />
                                                    {__('freelance.manage_contract')}
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>

            {/* Contract Modal */}
            <Dialog open={isContractModalOpen} onOpenChange={setIsContractModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{__('freelance.manage_salary_contract')}</DialogTitle>
                        <DialogDescription>{selectedMember?.name}</DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                        <div className="space-y-2">
                            <Label>{__('general.base_salary_monthly')}</Label>
                            <Input 
                                type="number" 
                                min="0" 
                                value={baseSalary} 
                                onChange={(e) => setBaseSalary(e.target.value)} 
                                placeholder={__('general.e_g_5000')}
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsContractModalOpen(false)}>{__('general.cancel')}</Button>
                        <Button onClick={handleSaveContract} disabled={isProcessing}>{__('freelance.save_contract')}</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Run Payroll Modal */}
            <Dialog open={isGenerateModalOpen} onOpenChange={setIsGenerateModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{__('general.run_payroll')}</DialogTitle>
                        <DialogDescription>{__('general.generate_draft_payslips_for_all')}</DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleGeneratePayroll}>
                        <div className="grid grid-cols-2 gap-4 py-4">
                            <div className="space-y-2">
                                <Label>{__('general.month')}</Label>
                                <Input type="number" name="month" min="1" max="12" defaultValue={filters.month} required />
                            </div>
                            <div className="space-y-2">
                                <Label>{__('general.year')}</Label>
                                <Input type="number" name="year" min="2000" max="2100" defaultValue={filters.year} required />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsGenerateModalOpen(false)}>{__('general.cancel')}</Button>
                            <Button type="submit" disabled={isProcessing}>{__('general.generate_payslips')}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Manage Payslip Items Modal */}
            <Dialog open={isItemsModalOpen} onOpenChange={setIsItemsModalOpen}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>{__('general.manage_payslip_items')}</DialogTitle>
                        <DialogDescription>{selectedPayslip?.member?.name} - {selectedPayslip?.month}/{selectedPayslip?.year}</DialogDescription>
                    </DialogHeader>
                    
                    <div className="py-4 space-y-4 max-h-[60vh] overflow-y-auto">
                        <div className="grid grid-cols-2 gap-4 mb-4">
                            <div className="space-y-2">
                                <Label>{__('general.days_worked')}</Label>
                                <Input type="number" min="0" value={workedDays} onChange={(e) => setWorkedDays(Number(e.target.value))} />
                            </div>
                            <div className="space-y-2">
                                <Label>{__('general.days_absent')}</Label>
                                <Input type="number" min="0" value={absentDays} onChange={(e) => setAbsentDays(Number(e.target.value))} />
                            </div>
                        </div>

                        {payslipItems.length === 0 ? (
                            <div className="text-center text-slate-500 py-4">{__('general.no_bonuses_or_deductions')}</div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>{__('general.type')}</TableHead>
                                        <TableHead>{__('general.description')}</TableHead>
                                        <TableHead>{__('general.amount')}</TableHead>
                                        <TableHead></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {payslipItems.map((item, index) => (
                                        <TableRow key={index}>
                                            <TableCell>
                                                <select 
                                                    className="w-full text-sm border-slate-200 rounded-md"
                                                    value={item.type} 
                                                    onChange={(e) => updatePayslipItem(index, 'type', e.target.value)}
                                                >
                                                    <option value="bonus">{__('general.bonus')}</option>
                                                    <option value="deduction">{__('general.deduction')}</option>
                                                </select>
                                            </TableCell>
                                            <TableCell>
                                                <Input 
                                                    type="text" 
                                                    value={item.description} 
                                                    onChange={(e) => updatePayslipItem(index, 'description', e.target.value)}
                                                    placeholder={__('general.reason')}
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <Input 
                                                    type="number" 
                                                    min="0.01"
                                                    step="0.01"
                                                    value={item.amount} 
                                                    onChange={(e) => updatePayslipItem(index, 'amount', e.target.value)}
                                                />
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button size="sm" variant="destructive" onClick={() => removePayslipItem(index)}>{__('general.remove')}</Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                        <div className="flex justify-end gap-2">
                            <Button size="sm" variant="outline" onClick={() => addPayslipItem('bonus')}><Plus className="w-4 h-4 mr-1"/> {__('general.add_bonus')}</Button>
                            <Button size="sm" variant="outline" onClick={() => addPayslipItem('deduction')}><Plus className="w-4 h-4 mr-1"/> {__('general.add_deduction')}</Button>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsItemsModalOpen(false)}>{__('general.cancel')}</Button>
                        <Button onClick={handleSaveItems} disabled={isProcessing}>{__('general.save_items')}</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Pay Modal */}
            <Dialog open={isPayModalOpen} onOpenChange={setIsPayModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{__('general.mark_payslip_as_paid')}</DialogTitle>
                        <DialogDescription>{selectedPayslip?.member?.name} - {selectedPayslip?.month}/{selectedPayslip?.year}</DialogDescription>
                    </DialogHeader>
                    
                    <div className="py-4 space-y-4">
                        <div className="bg-slate-50 p-4 rounded-lg flex justify-between items-center mb-4">
                            <span className="font-medium">{__('payment.total_net_pay')}:</span>
                            <span className="text-xl font-bold">{selectedPayslip && formatCurrency(selectedPayslip.net_amount, selectedPayslip.currency || currency)}</span>
                        </div>
                        
                        <div className="space-y-2">
                            <Label>{__('payment.payment_method')}</Label>
                            <select 
                                className="w-full text-sm border-slate-200 rounded-md p-2"
                                value={paymentMethodId}
                                onChange={(e) => setPaymentMethodId(e.target.value)}
                            >
                                <option value="" disabled>{__('payment.select_payment_method')}</option>
                                {paymentMethods?.map((pm: any) => (
                                    <option key={pm.id} value={pm.id}>{pm.name}</option>
                                ))}
                            </select>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsPayModalOpen(false)}>{__('general.cancel')}</Button>
                        <Button onClick={handleMarkPaid} disabled={isProcessing || !paymentMethodId}>{__('payment.confirm_payment')}</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </ERPLayout>
    );
}

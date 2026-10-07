import React, { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { MoreHorizontal, Plus, Wallet, Trash2, CreditCard } from 'lucide-react';
import { __ } from '@/lib/i18n';
import { useConfirm } from '@/hooks/useConfirm';

export default function UserLoansTab({ client, loans }) {
    const { props } = usePage();
    const { confirm, confirmDialog } = useConfirm();
    const currencies = props.currencies || [];
    
    const [isAddLoanOpen, setIsAddLoanOpen] = useState(false);
    const [addLoanForm, setAddLoanForm] = useState({
        amount: '',
        currency_id: client.currency_id || (currencies.length > 0 ? currencies[0].id : ''),
        type: 'on_client',
        date: new Date().toISOString().split('T')[0],
        note: ''
    });

    const [isRepayOpen, setIsRepayOpen] = useState(false);
    const [activeLoan, setActiveLoan] = useState(null);
    const [repayForm, setRepayForm] = useState({
        amount: '',
        date: new Date().toISOString().split('T')[0],
        note: ''
    });

    const [isRechargeOpen, setIsRechargeOpen] = useState(false);
    const [rechargeLoan, setRechargeLoan] = useState(null);
    const [rechargeForm, setRechargeForm] = useState({
        amount: '',
        date: new Date().toISOString().split('T')[0],
        note: ''
    });

    const formatCurrencyFallback = (amount, currencyObj) => {
        if (!currencyObj) return amount;
        return `${amount} ${currencyObj.currency}`;
    };

    const submitAddLoan = (e) => {
        e.preventDefault();
        router.post(`/admin/users/${client.id}/loans`, addLoanForm, {
            onSuccess: () => {
                setIsAddLoanOpen(false);
                setAddLoanForm({
                    amount: '',
                    currency_id: client.currency_id || (currencies.length > 0 ? currencies[0].id : ''),
                    type: 'on_client',
                    date: new Date().toISOString().split('T')[0],
                    note: ''
                });
            }
        });
    };

    const openRepayModal = (loan) => {
        setActiveLoan(loan);
        setRepayForm({
            amount: '',
            date: new Date().toISOString().split('T')[0],
            note: ''
        });
        setIsRepayOpen(true);
    };

    const submitRepay = (e) => {
        e.preventDefault();
        router.post(`/admin/users/${client.id}/loans/${activeLoan.id}/repayments`, repayForm, {
            onSuccess: () => {
                setIsRepayOpen(false);
                setActiveLoan(null);
            }
        });
    };

    const openRechargeModal = (loan) => {
        setRechargeLoan(loan);
        const remaining = (parseFloat(loan.amount) - parseFloat(loan.paid_amount)).toFixed(2);
        setRechargeForm({
            amount: remaining,
            date: new Date().toISOString().split('T')[0],
            note: ''
        });
        setIsRechargeOpen(true);
    };

    const submitRecharge = (e) => {
        e.preventDefault();
        router.post(`/admin/users/${client.id}/loans/${rechargeLoan.id}/recharge-balance`, rechargeForm, {
            onSuccess: () => {
                setIsRechargeOpen(false);
                setRechargeLoan(null);
            }
        });
    };

    const deleteLoan = async (loanId) => {
        const accepted = await confirm({
            title: __('admin.loan_delete_confirm_title'),
            description: __('admin.loan_delete_confirm_description'),
            variant: 'danger',
            confirmLabel: __('general.delete'),
        });
        if (!accepted) return;
        router.delete(`/admin/users/${client.id}/loans/${loanId}`);
    };

    return (
        <div className="bg-white p-6 rounded-[12px] shadow-sm border border-slate-200 mb-6">
            {confirmDialog}
            <div className="flex justify-between items-center mb-4 border-b pb-2">
                <h2 className="text-lg font-bold font-sora text-slate-900 flex items-center gap-2">
                    <Wallet size={18} className="text-slate-400" /> {__('admin.loans')}
                </h2>
                <Button size="sm" onClick={() => setIsAddLoanOpen(true)}>
                    <Plus size={16} className="me-2" /> {__('admin.add_loan')}
                </Button>
            </div>

            {loans && loans.length > 0 ? (
                <div className="overflow-x-auto">
                    <table className="w-full text-sm text-start">
                        <thead className="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-xs">
                            <tr>
                                <th className="px-4 py-3">{__('admin.date')}</th>
                                <th className="px-4 py-3">{__('admin.loan_type')}</th>
                                <th className="px-4 py-3">{__('admin.amount')}</th>
                                <th className="px-4 py-3">{__('admin.paid_amount')}</th>
                                <th className="px-4 py-3">{__('admin.remaining')}</th>
                                <th className="px-4 py-3">{__('admin.status')}</th>
                                <th className="px-4 py-3 text-end">{__('general.actions')}</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {loans.map(loan => {
                                const remaining = parseFloat(loan.amount) - parseFloat(loan.paid_amount);
                                return (
                                    <tr key={loan.id} className="hover:bg-slate-50 transition-colors">
                                        <td className="px-4 py-3 text-slate-600">{new Date(loan.date).toLocaleDateString()}</td>
                                        <td className="px-4 py-3">
                                            {loan.type === 'on_business' ? (
                                                <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-900 text-white border border-slate-900">
                                                    {__('admin.loan_on_business')}
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-800 border border-slate-200">
                                                    {__('admin.loan_on_client')}
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 font-semibold text-slate-900">{formatCurrencyFallback(loan.amount, loan.currency)}</td>
                                        <td className="px-4 py-3 text-green-600 font-medium">{formatCurrencyFallback(loan.paid_amount, loan.currency)}</td>
                                        <td className="px-4 py-3 text-red-600 font-medium">{formatCurrencyFallback(remaining, loan.currency)}</td>
                                        <td className="px-4 py-3">
                                            {loan.status === 'paid' ? (
                                                <span className="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-bold uppercase">{__('admin.paid')}</span>
                                            ) : (
                                                <span className="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs font-bold uppercase">{__('admin.active')}</span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-end">
                                            <div className="flex items-center justify-end gap-1.5">
                                                {loan.type === 'on_business' && loan.status !== 'paid' && (
                                                    <Button 
                                                        type="button"
                                                        variant="outline" 
                                                        size="sm" 
                                                        className="h-8 px-2.5 text-xs font-medium border-slate-300 hover:bg-slate-100 hover:text-black"
                                                        onClick={() => openRechargeModal(loan)}
                                                        title={__('admin.recharge_client_balance')}
                                                    >
                                                        <CreditCard size={14} className="me-1 text-slate-700" />
                                                        {__('admin.recharge_client_balance')}
                                                    </Button>
                                                )}
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" className="h-8 w-8 p-0" aria-label={__('general.open_menu')} title={__('general.open_menu')}>
                                                            <MoreHorizontal className="h-4 w-4 text-slate-500" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        {loan.status !== 'paid' && (
                                                            <>
                                                                {loan.type === 'on_business' && (
                                                                    <DropdownMenuItem onClick={() => openRechargeModal(loan)}>
                                                                        <CreditCard className="me-2 h-4 w-4 text-slate-700" /> {__('admin.recharge_client_balance')}
                                                                    </DropdownMenuItem>
                                                                )}
                                                                <DropdownMenuItem onClick={() => openRepayModal(loan)}>
                                                                    <Wallet className="me-2 h-4 w-4 text-slate-700" /> {__('admin.add_repayment')}
                                                                </DropdownMenuItem>
                                                            </>
                                                        )}
                                                        <DropdownMenuItem className="text-red-600" onClick={() => deleteLoan(loan.id)}>
                                                            <Trash2 className="me-2 h-4 w-4" /> {__('general.delete')}
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            ) : (
                <div className="text-center py-6 text-slate-500 text-sm">
                    {__('admin.no_loans_found')}
                </div>
            )}

            {/* Add Loan Modal */}
            <Dialog open={isAddLoanOpen} onOpenChange={setIsAddLoanOpen}>
                <DialogContent>
                    <form onSubmit={submitAddLoan}>
                        <DialogHeader>
                            <DialogTitle>{__('admin.add_loan')}</DialogTitle>
                            <DialogDescription>{__('admin.add_loan_desc')}</DialogDescription>
                        </DialogHeader>
                        <div className="py-4 space-y-4">
                            <div>
                                <Label>{__('admin.loan_type')}</Label>
                                <div className="grid grid-cols-2 gap-3 mt-1.5">
                                    <button
                                        type="button"
                                        onClick={() => setAddLoanForm({ ...addLoanForm, type: 'on_client' })}
                                        className={`p-3 text-start border rounded-lg transition-all ${
                                            addLoanForm.type === 'on_client'
                                                ? 'border-slate-900 bg-slate-50 text-slate-900 font-semibold ring-1 ring-slate-900'
                                                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                                        }`}
                                    >
                                        <div className="text-sm font-medium">{__('admin.loan_on_client')}</div>
                                        <div className="text-xs text-slate-500 font-normal mt-0.5">{__('admin.loan_on_client_hint')}</div>
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setAddLoanForm({ ...addLoanForm, type: 'on_business' })}
                                        className={`p-3 text-start border rounded-lg transition-all ${
                                            addLoanForm.type === 'on_business'
                                                ? 'border-slate-900 bg-slate-50 text-slate-900 font-semibold ring-1 ring-slate-900'
                                                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                                        }`}
                                    >
                                        <div className="text-sm font-medium">{__('admin.loan_on_business')}</div>
                                        <div className="text-xs text-slate-500 font-normal mt-0.5">{__('admin.loan_on_business_hint')}</div>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <Label>{__('admin.amount')}</Label>
                                <Input type="number" step="0.01" min="0.01" required value={addLoanForm.amount} onChange={e => setAddLoanForm({...addLoanForm, amount: e.target.value})} />
                            </div>
                            <div>
                                <Label>{__('admin.currency')}</Label>
                                <select className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background" value={addLoanForm.currency_id} onChange={e => setAddLoanForm({...addLoanForm, currency_id: e.target.value})} required>
                                    <option value="">-- {__('general.select')} --</option>
                                    {currencies.map(c => (
                                        <option key={c.id} value={c.id}>{c.currency}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label>{__('admin.date')}</Label>
                                <Input type="date" required value={addLoanForm.date} onChange={e => setAddLoanForm({...addLoanForm, date: e.target.value})} />
                            </div>
                            <div>
                                <Label>{__('admin.note')}</Label>
                                <Input type="text" value={addLoanForm.note} onChange={e => setAddLoanForm({...addLoanForm, note: e.target.value})} />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsAddLoanOpen(false)}>{__('general.cancel')}</Button>
                            <Button type="submit">{__('general.save')}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Add Repayment Modal */}
            <Dialog open={isRepayOpen} onOpenChange={setIsRepayOpen}>
                <DialogContent>
                    <form onSubmit={submitRepay}>
                        <DialogHeader>
                            <DialogTitle>{__('admin.add_repayment')}</DialogTitle>
                            <DialogDescription>{__('admin.add_repayment_desc')}</DialogDescription>
                        </DialogHeader>
                        <div className="py-4 space-y-4">
                            {activeLoan && (
                                <div className="p-3 bg-slate-50 border border-slate-200 rounded text-sm text-slate-600 mb-2">
                                    {__('admin.loan_remaining_amount')}: <strong>{parseFloat(activeLoan.amount) - parseFloat(activeLoan.paid_amount)} {activeLoan.currency?.currency}</strong>
                                </div>
                            )}
                            <div>
                                <Label>{__('admin.amount')}</Label>
                                <Input type="number" step="0.01" min="0.01" required value={repayForm.amount} onChange={e => setRepayForm({...repayForm, amount: e.target.value})} />
                            </div>
                            <div>
                                <Label>{__('admin.date')}</Label>
                                <Input type="date" required value={repayForm.date} onChange={e => setRepayForm({...repayForm, date: e.target.value})} />
                            </div>
                            <div>
                                <Label>{__('admin.note')}</Label>
                                <Input type="text" value={repayForm.note} onChange={e => setRepayForm({...repayForm, note: e.target.value})} />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsRepayOpen(false)}>{__('general.cancel')}</Button>
                            <Button type="submit">{__('general.save')}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Recharge Client Balance Modal */}
            <Dialog open={isRechargeOpen} onOpenChange={setIsRechargeOpen}>
                <DialogContent>
                    <form onSubmit={submitRecharge}>
                        <DialogHeader>
                            <DialogTitle>{__('admin.recharge_client_balance')}</DialogTitle>
                            <DialogDescription>
                                {__('admin.recharge_client_balance_desc')}
                            </DialogDescription>
                        </DialogHeader>
                        <div className="py-4 space-y-4">
                            {rechargeLoan && (
                                <div className="p-3 bg-slate-50 border border-slate-200 rounded text-sm text-slate-700">
                                    <div className="flex justify-between items-center mb-1">
                                        <span className="text-slate-500">{__('admin.loan_remaining_amount')}:</span>
                                        <strong className="font-semibold text-slate-900">
                                            {formatCurrencyFallback(
                                                (parseFloat(rechargeLoan.amount) - parseFloat(rechargeLoan.paid_amount)).toFixed(2),
                                                rechargeLoan.currency
                                            )}
                                        </strong>
                                    </div>
                                    <div className="text-xs text-slate-500 mt-2">
                                        {__('admin.loan_recharge_note')}
                                    </div>
                                </div>
                            )}
                            <div>
                                <Label>{__('admin.amount')}</Label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    max={rechargeLoan ? (parseFloat(rechargeLoan.amount) - parseFloat(rechargeLoan.paid_amount)) : undefined}
                                    required
                                    value={rechargeForm.amount}
                                    onChange={e => setRechargeForm({ ...rechargeForm, amount: e.target.value })}
                                />
                            </div>
                            <div>
                                <Label>{__('admin.date')}</Label>
                                <Input
                                    type="date"
                                    required
                                    value={rechargeForm.date}
                                    onChange={e => setRechargeForm({ ...rechargeForm, date: e.target.value })}
                                />
                            </div>
                            <div>
                                <Label>{__('admin.note')}</Label>
                                <Input
                                    type="text"
                                    placeholder={__('admin.loan_recharge_note_placeholder')}
                                    value={rechargeForm.note}
                                    onChange={e => setRechargeForm({ ...rechargeForm, note: e.target.value })}
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsRechargeOpen(false)}>{__('general.cancel')}</Button>
                            <Button type="submit">{__('admin.recharge_client_balance')}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
}

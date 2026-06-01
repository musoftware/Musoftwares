import React, { useState } from 'react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head, useForm, router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetFooter,
    SheetTrigger,
} from '@/Components/ui/sheet';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import {
    Plus,
    Building,
    User,
    CreditCard,
    Globe,
    MoreVertical,
    Trash2,
    Check,
    AlertCircle,
    Banknote
} from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger
} from '@/Components/ui/dropdown-menu';
import { useToast } from '@/Components/ui/use-toast';

export default function Index({ auth, paymentMethods, currencies = [] }) {
    const { toast } = useToast();
    const [isAddOpen, setIsAddOpen] = useState(false);

    const { data, setData, post, processing, reset, errors } = useForm({
        bank_name: '',
        account_holder_name: '',
        account_number: '',
        iban: '',
        swift_code: '',
        bank_country: 'EG',
        bank_currency: 'EGP',
        branch_name: '',
        notes: '',
        is_default: false,
    });

    const onSubmit = (e) => {
        e.preventDefault();
        post(route('erp.payment-methods.store'), {
            onSuccess: () => {
                setIsAddOpen(false);
                reset();
                toast({ title: "Submitted", description: "Bank account submitted for approval." });
            }
        });
    };

    const setDefault = (pm) => {
        router.patch(route('erp.payment-methods.update', pm.id), { is_default: true }, {
            onSuccess: () => toast({ title: "Updated", description: "Default payment method changed." })
        });
    };

    const deletePM = (pm) => {
        if (confirm('Are you sure you want to delete this bank account?')) {
            router.delete(route('erp.payment-methods.destroy', pm.id), {
                onSuccess: () => toast({ title: "Deleted", description: "Bank account removed." })
            });
        }
    };

    const maskIBAN = (iban) => {
        if (!iban) return '';
        return iban.substring(0, 4) + ' .... ' + iban.substring(iban.length - 4);
    };
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('settings');

    return (
        <ERPLayout title={__('general.payment_methods')} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>

            <div className="py-12">
                <div className="max-w-[1000px] mx-auto px-4 sm:px-6 lg:px-8 space-y-10 font-sans">

                    {/* ──────────────────────────────────────────────────────── */}
                    {/* HEADER & ADD BUTTON */}
                    {/* ──────────────────────────────────────────────────────── */}
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-6">
                        <div>
                            <h1 className="text-2xl font-semibold text-slate-900 tracking-tight">{__('general.bank_accounts')}</h1>
                            <p className="text-sm text-slate-500 mt-1">{__('general.manage_where_you_receive_your_automated_withdrawals')}</p>
                        </div>

                        <Sheet open={isAddOpen} onOpenChange={setIsAddOpen}>
                            <SheetTrigger asChild>
                                <Button className="shadow-sm bg-slate-900 text-white hover:bg-slate-800 transition-colors">
                                    <Plus className="w-4 h-4 mr-2" />{__('general.add_bank_account')}</Button>
                            </SheetTrigger>
                            <SheetContent className="sm:max-w-lg overflow-y-auto">
                                <SheetHeader>
                                    <SheetTitle>{__('general.add_bank_account')}</SheetTitle>
                                    <SheetDescription>{__('general.enter_your_bank_details_accurately_accounts_require_manual_approval_by_our_team')}</SheetDescription>
                                </SheetHeader>

                                <form onSubmit={onSubmit} className="space-y-4 py-6">
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="grid gap-2 col-span-2">
                                            <Label htmlFor="bank_name">{__('general.bank_name')}</Label>
                                            <Input id="bank_name" className="shadow-none border-slate-200" value={data.bank_name} onChange={e => setData('bank_name', e.target.value)} required />
                                            {errors.bank_name && <p className="text-xs text-rose-500">{errors.bank_name}</p>}
                                        </div>
                                        <div className="grid gap-2 col-span-2">
                                            <Label htmlFor="holder">{__('general.account_holder_name')}</Label>
                                            <Input id="holder" className="shadow-none border-slate-200" value={data.account_holder_name} onChange={e => setData('account_holder_name', e.target.value)} required />
                                            {errors.account_holder_name && <p className="text-xs text-rose-500">{errors.account_holder_name}</p>}
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="acc_num">{__('general.account_number')}</Label>
                                            <Input id="acc_num" className="shadow-none border-slate-200" value={data.account_number} onChange={e => setData('account_number', e.target.value)} required />
                                            {errors.account_number && <p className="text-xs text-rose-500">{errors.account_number}</p>}
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="iban">IBAN</Label>
                                            <Input id="iban" className="shadow-none border-slate-200" value={data.iban} onChange={e => setData('iban', e.target.value)} required />
                                            {errors.iban && <p className="text-xs text-rose-500">{errors.iban}</p>}
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="swift">{__('general.swift_bic')}</Label>
                                            <Input id="swift" className="shadow-none border-slate-200" value={data.swift_code} onChange={e => setData('swift_code', e.target.value)} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="branch">{__('general.branch_name')}</Label>
                                            <Input id="branch" className="shadow-none border-slate-200" value={data.branch_name} onChange={e => setData('branch_name', e.target.value)} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="country">{__('general.bank_country')}</Label>
                                            <select
                                                id="country"
                                                className="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm shadow-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                                value={data.bank_country}
                                                onChange={e => setData('bank_country', e.target.value)}
                                            >
                                                <option value="EG">Egypt</option>
                                                <option value="US">{__('general.united_states')}</option>
                                                <option value="GB">{__('general.united_kingdom')}</option>
                                                <option value="AE">UAE</option>
                                                <option value="SA">{__('general.saudi_arabia')}</option>
                                            </select>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="currency">Currency</Label>
                                            <select
                                                id="currency"
                                                className="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm shadow-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                                value={data.bank_currency}
                                                onChange={e => setData('bank_currency', e.target.value)}
                                            >
                                                {currencies.map((c) => (
                                                    <option key={c.id} value={c.currency}>
                                                        {c.currency}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="notes">{__('general.additional_notes')}</Label>
                                        <Textarea id="notes" className="shadow-none border-slate-200" value={data.notes} onChange={e => setData('notes', e.target.value)} />
                                    </div>

                                    <div className="flex items-center space-x-2 pt-2">
                                        <input
                                            type="checkbox"
                                            id="def"
                                            checked={data.is_default}
                                            onChange={e => setData('is_default', e.target.checked)}
                                            className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-600"
                                        />
                                        <Label htmlFor="def" className="text-sm font-medium text-slate-700">{__('general.set_as_default_for_withdrawals')}</Label>
                                    </div>

                                    <SheetFooter className="pt-4">
                                        <Button type="submit" className="w-full shadow-sm bg-slate-900 text-white hover:bg-slate-800" disabled={processing}>{__('general.save_bank_account')}</Button>
                                    </SheetFooter>
                                </form>
                            </SheetContent>
                        </Sheet>
                    </div>

                    {/* ──────────────────────────────────────────────────────── */}
                    {/* BANK ACCOUNTS LIST */}
                    {/* ──────────────────────────────────────────────────────── */}
                    <div className="space-y-4">
                        {paymentMethods.map((pm) => (
                            <div key={pm.id} className={`relative bg-white rounded-2xl border ${pm.is_default ? 'border-indigo-200 shadow-sm ring-1 ring-indigo-50' : 'border-slate-100 shadow-sm'} p-6 transition-all`}>
                                {pm.is_default && (
                                    <div className="absolute -top-3 -right-3 bg-indigo-100 text-indigo-700 text-[10px] font-bold px-3 py-1 rounded-full flex items-center border border-indigo-200">
                                        <Check className="w-3 h-3 mr-1" /> DEFAULT
                                    </div>
                                )}
                                <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
                                    
                                    <div className="flex items-start gap-4">
                                        <div className="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                            <Building className="w-6 h-6 text-slate-400" />
                                        </div>
                                        <div>
                                            <h3 className="text-lg font-semibold text-slate-900">{pm.bank_name}</h3>
                                            <div className="flex items-center text-sm text-slate-500 mt-1">
                                                <User className="w-3.5 h-3.5 mr-1.5" /> {pm.account_holder_name}
                                            </div>
                                        </div>
                                    </div>

                                    <div className="flex-1 md:max-w-xs space-y-1">
                                        <p className="text-[10px] uppercase font-bold text-slate-400 tracking-wider">IBAN</p>
                                        <p className="font-mono text-sm tracking-tighter bg-slate-50 p-2 rounded-lg border border-slate-100 text-slate-700">
                                            {maskIBAN(pm.iban)}
                                        </p>
                                    </div>

                                    <div className="flex items-center gap-6">
                                        <div className="text-right">
                                            <p className="text-[10px] uppercase font-bold text-slate-400 tracking-wider mb-1">Status</p>
                                            <StatusBadge status={pm.status} />
                                        </div>
                                        <div className="text-right">
                                            <p className="text-[10px] uppercase font-bold text-slate-400 tracking-wider mb-1">Currency</p>
                                            <div className="flex items-center text-sm font-medium text-slate-700">
                                                <Globe className="w-3.5 h-3.5 mr-1.5 text-slate-400" /> {pm.bank_country} • {pm.bank_currency}
                                            </div>
                                        </div>

                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="icon" className="h-8 w-8 text-slate-400 hover:text-slate-600">
                                                    <MoreVertical className="w-4 h-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                {!pm.is_default && pm.status === 'approved' && (
                                                    <DropdownMenuItem onClick={() => setDefault(pm)} className="cursor-pointer">
                                                        <Check className="w-4 h-4 mr-2 text-indigo-600" />{__('general.set_default')}</DropdownMenuItem>
                                                )}
                                                <DropdownMenuItem className="text-rose-600 focus:bg-rose-50 cursor-pointer" onClick={() => deletePM(pm)}>
                                                    <Trash2 className="w-4 h-4 mr-2 text-rose-600" /> Delete
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>

                                {pm.status === 'rejected' && pm.rejection_note && (
                                    <div className="mt-4 p-3 bg-rose-50 border border-rose-100 rounded-lg flex items-start gap-2">
                                        <AlertCircle className="w-4 h-4 text-rose-500 mt-0.5 shrink-0" />
                                        <p className="text-sm text-rose-700">{pm.rejection_note}</p>
                                    </div>
                                )}
                            </div>
                        ))}

                        {paymentMethods.length === 0 && (
                            <div className="border border-dashed border-slate-200 bg-slate-50/50 rounded-2xl p-12 text-center flex flex-col items-center justify-center space-y-4">
                                <div className="p-4 bg-white rounded-full border border-slate-100 shadow-sm">
                                    <Banknote className="w-8 h-8 text-slate-400" />
                                </div>
                                <div>
                                    <p className="font-semibold text-slate-900 text-lg">{__('general.no_bank_accounts_added')}</p>
                                    <p className="text-sm text-slate-500 mt-1">{__('general.add_a_bank_account_to_start_receiving_withdrawals')}</p>
                                </div>
                                <Button variant="outline" onClick={() => setIsAddOpen(true)} className="shadow-sm border-slate-200 mt-2">{__('general.add_your_first_account')}</Button>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </ERPLayout>
    );
}

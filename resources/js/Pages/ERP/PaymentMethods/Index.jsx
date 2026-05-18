import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
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

export default function Index({ auth, paymentMethods }) {
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

    return (
        <AuthenticatedLayout header={undefined}>
            <Head title="Payment Methods" />

            <div className="py-12 px-4 sm:px-6 lg:px-8 max-w-[1200px] mx-auto space-y-8 font-sans">
                
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-100">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Bank Accounts</h1>
                        <p className="text-sm text-slate-500 mt-1">Manage where you receive your withdrawals securely.</p>
                    </div>

                    <Sheet open={isAddOpen} onOpenChange={setIsAddOpen}>
                        <SheetTrigger asChild>
                            <Button className="shadow-sm bg-slate-900 text-white hover:bg-slate-800">
                                <Plus className="w-4 h-4 mr-2" /> Add Bank Account
                            </Button>
                        </SheetTrigger>
                        <SheetContent className="sm:max-w-lg overflow-y-auto font-sans">
                            <SheetHeader>
                                <SheetTitle className="text-xl font-semibold">Add Bank Account</SheetTitle>
                                <SheetDescription className="text-slate-500">
                                    Enter your bank details accurately. Accounts require manual approval by our team.
                                </SheetDescription>
                            </SheetHeader>

                            <form onSubmit={onSubmit} className="space-y-5 py-6">
                                <div className="grid grid-cols-2 gap-5">
                                    <div className="grid gap-2 col-span-2">
                                        <Label htmlFor="bank_name" className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Bank Name</Label>
                                        <Input id="bank_name" className="h-10 border-slate-200 bg-slate-50/50 focus:bg-white" value={data.bank_name} onChange={e => setData('bank_name', e.target.value)} required />
                                        {errors.bank_name && <p className="text-xs text-rose-500">{errors.bank_name}</p>}
                                    </div>
                                    <div className="grid gap-2 col-span-2">
                                        <Label htmlFor="holder" className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Account Holder Name</Label>
                                        <Input id="holder" className="h-10 border-slate-200 bg-slate-50/50 focus:bg-white" value={data.account_holder_name} onChange={e => setData('account_holder_name', e.target.value)} required />
                                        {errors.account_holder_name && <p className="text-xs text-rose-500">{errors.account_holder_name}</p>}
                                    </div>
                                    <div className="grid gap-2 col-span-2 sm:col-span-1">
                                        <Label htmlFor="acc_num" className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Account Number</Label>
                                        <Input id="acc_num" className="h-10 border-slate-200 bg-slate-50/50 focus:bg-white" value={data.account_number} onChange={e => setData('account_number', e.target.value)} required />
                                        {errors.account_number && <p className="text-xs text-rose-500">{errors.account_number}</p>}
                                    </div>
                                    <div className="grid gap-2 col-span-2 sm:col-span-1">
                                        <Label htmlFor="iban" className="text-xs font-semibold text-slate-500 uppercase tracking-wider">IBAN</Label>
                                        <Input id="iban" className="h-10 border-slate-200 bg-slate-50/50 focus:bg-white" value={data.iban} onChange={e => setData('iban', e.target.value)} required />
                                        {errors.iban && <p className="text-xs text-rose-500">{errors.iban}</p>}
                                    </div>
                                    <div className="grid gap-2 col-span-2 sm:col-span-1">
                                        <Label htmlFor="swift" className="text-xs font-semibold text-slate-500 uppercase tracking-wider">SWIFT / BIC</Label>
                                        <Input id="swift" className="h-10 border-slate-200 bg-slate-50/50 focus:bg-white" value={data.swift_code} onChange={e => setData('swift_code', e.target.value)} />
                                    </div>
                                    <div className="grid gap-2 col-span-2 sm:col-span-1">
                                        <Label htmlFor="branch" className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Branch Name</Label>
                                        <Input id="branch" className="h-10 border-slate-200 bg-slate-50/50 focus:bg-white" value={data.branch_name} onChange={e => setData('branch_name', e.target.value)} />
                                    </div>
                                    <div className="grid gap-2 col-span-2 sm:col-span-1">
                                        <Label htmlFor="country" className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Bank Country</Label>
                                        <select
                                            id="country"
                                            className="flex h-10 w-full rounded-md border-slate-200 bg-slate-50/50 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                            value={data.bank_country}
                                            onChange={e => setData('bank_country', e.target.value)}
                                        >
                                            <option value="EG">Egypt</option>
                                            <option value="US">United States</option>
                                            <option value="GB">United Kingdom</option>
                                            <option value="AE">UAE</option>
                                            <option value="SA">Saudi Arabia</option>
                                        </select>
                                    </div>
                                    <div className="grid gap-2 col-span-2 sm:col-span-1">
                                        <Label htmlFor="currency" className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Currency</Label>
                                        <select
                                            id="currency"
                                            className="flex h-10 w-full rounded-md border-slate-200 bg-slate-50/50 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                            value={data.bank_currency}
                                            onChange={e => setData('bank_currency', e.target.value)}
                                        >
                                            <option value="EGP">EGP</option>
                                            <option value="USD">USD</option>
                                            <option value="EUR">EUR</option>
                                            <option value="SAR">SAR</option>
                                            <option value="AED">AED</option>
                                        </select>
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="notes" className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Additional Notes</Label>
                                    <Textarea id="notes" className="border-slate-200 bg-slate-50/50 focus:bg-white resize-none" value={data.notes} onChange={e => setData('notes', e.target.value)} />
                                </div>

                                <div className="flex items-center space-x-3 pt-2 bg-slate-50 p-4 rounded-xl border border-slate-100">
                                    <input
                                        type="checkbox"
                                        id="def"
                                        checked={data.is_default}
                                        onChange={e => setData('is_default', e.target.checked)}
                                        className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600"
                                    />
                                    <Label htmlFor="def" className="text-sm font-medium text-slate-700 cursor-pointer">Set as default for withdrawals</Label>
                                </div>

                                <SheetFooter className="pt-6">
                                    <Button type="submit" className="w-full bg-slate-900 text-white hover:bg-slate-800" disabled={processing}>Save Bank Account</Button>
                                </SheetFooter>
                            </form>
                        </SheetContent>
                    </Sheet>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {paymentMethods.map((pm) => (
                        <div key={pm.id} className={`group relative bg-white p-6 rounded-2xl shadow-sm transition-all duration-200 border ${pm.is_default ? 'border-indigo-600 ring-1 ring-indigo-600' : 'border-slate-100 hover:border-slate-300'}`}>
                            {pm.is_default && (
                                <div className="absolute top-0 right-0 bg-indigo-600 text-white text-[10px] font-bold px-3 py-1 rounded-bl-xl rounded-tr-xl flex items-center tracking-wider">
                                    <Check className="w-3 h-3 mr-1" /> DEFAULT
                                </div>
                            )}
                            
                            <div className="flex justify-between items-start mb-6">
                                <div className="w-12 h-12 bg-slate-50 rounded-xl flex items-center justify-center border border-slate-100 text-slate-400 group-hover:text-slate-600 transition-colors">
                                    <Building className="w-5 h-5" />
                                </div>
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="ghost" size="icon" className="h-8 w-8 text-slate-400 hover:text-slate-900 hover:bg-slate-100">
                                            <MoreVertical className="w-4 h-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" className="font-sans min-w-[160px]">
                                        {!pm.is_default && pm.status === 'approved' && (
                                            <DropdownMenuItem onClick={() => setDefault(pm)} className="text-sm cursor-pointer">
                                                <Check className="w-4 h-4 mr-2 text-indigo-600" /> Set Default
                                            </DropdownMenuItem>
                                        )}
                                        <DropdownMenuItem className="text-rose-600 focus:bg-rose-50 cursor-pointer text-sm" onClick={() => deletePM(pm)}>
                                            <Trash2 className="w-4 h-4 mr-2 text-rose-600" /> Remove Account
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                            
                            <div className="space-y-6">
                                <div>
                                    <h3 className="text-lg font-bold text-slate-900 tracking-tight">{pm.bank_name}</h3>
                                    <div className="flex items-center text-sm text-slate-500 mt-1">
                                        <User className="w-3.5 h-3.5 mr-1.5" /> {pm.account_holder_name}
                                    </div>
                                </div>

                                <div className="space-y-1.5">
                                    <p className="text-[10px] uppercase font-bold text-slate-400 tracking-wider">IBAN</p>
                                    <p className="font-mono text-sm font-medium text-slate-700 bg-slate-50 px-3 py-2 rounded-lg border border-slate-100 flex items-center">
                                        {maskIBAN(pm.iban)}
                                    </p>
                                </div>

                                <div className="flex justify-between items-center pt-2">
                                    <div className="flex items-center space-x-2 bg-slate-50 px-2.5 py-1 rounded-md border border-slate-100">
                                        <Globe className="w-3.5 h-3.5 text-slate-400" />
                                        <span className="text-xs font-semibold text-slate-600">{pm.bank_country} • {pm.bank_currency}</span>
                                    </div>
                                    <StatusBadge status={pm.status} size="sm" />
                                </div>

                                {pm.status === 'rejected' && pm.rejection_note && (
                                    <div className="p-3 bg-rose-50 border border-rose-100 rounded-xl flex items-start space-x-2 mt-4">
                                        <AlertCircle className="w-4 h-4 text-rose-600 shrink-0 mt-0.5" />
                                        <p className="text-xs text-rose-700 font-medium">{pm.rejection_note}</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    ))}

                    {paymentMethods.length === 0 && (
                        <div className="col-span-full border-2 border-dashed border-slate-200 bg-slate-50/50 rounded-3xl p-12 text-center flex flex-col items-center justify-center space-y-4">
                            <div className="p-4 bg-white rounded-full border border-slate-100 shadow-sm text-slate-400 mb-2">
                                <Banknote className="w-8 h-8" />
                            </div>
                            <div>
                                <h3 className="text-lg font-bold text-slate-900 tracking-tight">No bank accounts added</h3>
                                <p className="text-sm text-slate-500 mt-1 max-w-sm mx-auto">Add a verified bank account to start receiving your balance withdrawals.</p>
                            </div>
                            <Button onClick={() => setIsAddOpen(true)} className="bg-slate-900 text-white hover:bg-slate-800 shadow-sm mt-4">
                                <Plus className="w-4 h-4 mr-2" /> Add your first account
                            </Button>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

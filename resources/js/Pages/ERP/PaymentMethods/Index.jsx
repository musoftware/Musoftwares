import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
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
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl leading-tight">Payment Methods</h2>}
        >
            <Head title="Payment Methods" />

            <div className="py-12">
                <div className="max-w-[1200px] mx-auto sm:px-6 lg:px-8 space-y-6">

                    <div className="flex justify-between items-center">
                        <div>
                            <h3 className="text-lg font-bold">Bank Accounts</h3>
                            <p className="text-sm text-muted-foreground">Manage where you receive your withdrawals.</p>
                        </div>

                        <Sheet open={isAddOpen} onOpenChange={setIsAddOpen}>
                            <SheetTrigger asChild>
                                <Button className="shadow-none">
                                    <Plus className="w-4 h-4 mr-2" /> Add Bank Account
                                </Button>
                            </SheetTrigger>
                            <SheetContent className="sm:max-w-lg overflow-y-auto">
                                <SheetHeader>
                                    <SheetTitle>Add Bank Account</SheetTitle>
                                    <SheetDescription>
                                        Enter your bank details accurately. Accounts require manual approval by our team.
                                    </SheetDescription>
                                </SheetHeader>

                                <form onSubmit={onSubmit} className="space-y-4 py-6">
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="grid gap-2 col-span-2">
                                            <Label htmlFor="bank_name">Bank Name</Label>
                                            <Input id="bank_name" className="shadow-none" value={data.bank_name} onChange={e => setData('bank_name', e.target.value)} required />
                                            {errors.bank_name && <p className="text-xs text-destructive">{errors.bank_name}</p>}
                                        </div>
                                        <div className="grid gap-2 col-span-2">
                                            <Label htmlFor="holder">Account Holder Name</Label>
                                            <Input id="holder" className="shadow-none" value={data.account_holder_name} onChange={e => setData('account_holder_name', e.target.value)} required />
                                            {errors.account_holder_name && <p className="text-xs text-destructive">{errors.account_holder_name}</p>}
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="acc_num">Account Number</Label>
                                            <Input id="acc_num" className="shadow-none" value={data.account_number} onChange={e => setData('account_number', e.target.value)} required />
                                            {errors.account_number && <p className="text-xs text-destructive">{errors.account_number}</p>}
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="iban">IBAN</Label>
                                            <Input id="iban" className="shadow-none" value={data.iban} onChange={e => setData('iban', e.target.value)} required />
                                            {errors.iban && <p className="text-xs text-destructive">{errors.iban}</p>}
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="swift">SWIFT / BIC</Label>
                                            <Input id="swift" className="shadow-none" value={data.swift_code} onChange={e => setData('swift_code', e.target.value)} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="branch">Branch Name</Label>
                                            <Input id="branch" className="shadow-none" value={data.branch_name} onChange={e => setData('branch_name', e.target.value)} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="country">Bank Country</Label>
                                            <select
                                                id="country"
                                                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-none"
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
                                        <div className="grid gap-2">
                                            <Label htmlFor="currency">Currency</Label>
                                            <select
                                                id="currency"
                                                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-none"
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
                                        <Label htmlFor="notes">Additional Notes</Label>
                                        <Textarea id="notes" className="shadow-none" value={data.notes} onChange={e => setData('notes', e.target.value)} />
                                    </div>

                                    <div className="flex items-center space-x-2 pt-2">
                                        <input
                                            type="checkbox"
                                            id="def"
                                            checked={data.is_default}
                                            onChange={e => setData('is_default', e.target.checked)}
                                            className="rounded border-input text-primary focus:ring-primary"
                                        />
                                        <Label htmlFor="def" className="text-sm font-medium">Set as default for withdrawals</Label>
                                    </div>

                                    <SheetFooter className="pt-4">
                                        <Button type="submit" className="w-full shadow-none" disabled={processing}>Save Bank Account</Button>
                                    </SheetFooter>
                                </form>
                            </SheetContent>
                        </Sheet>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {paymentMethods.map((pm) => (
                            <Card key={pm.id} className={`relative overflow-hidden border-2 transition-all shadow-none ${pm.is_default ? 'border-primary' : 'border-border'}`}>
                                {pm.is_default && (
                                    <div className="absolute top-0 right-0 bg-primary text-primary-foreground text-[10px] font-bold px-3 py-1 rounded-bl-lg flex items-center">
                                        <Check className="w-3 h-3 mr-1" /> DEFAULT
                                    </div>
                                )}
                                <CardHeader className="pb-2">
                                    <div className="flex justify-between items-start">
                                        <div className="p-2 bg-muted rounded-lg">
                                            <Building className="w-5 h-5 text-muted-foreground" />
                                        </div>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="icon" className="h-8 w-8">
                                                    <MoreVertical className="w-4 h-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                {!pm.is_default && pm.status === 'approved' && (
                                                    <DropdownMenuItem onClick={() => setDefault(pm)}>
                                                        <Check className="w-4 h-4 mr-2" /> Set Default
                                                    </DropdownMenuItem>
                                                )}
                                                <DropdownMenuItem className="text-destructive focus:bg-destructive/10" onClick={() => deletePM(pm)}>
                                                    <Trash2 className="w-4 h-4 mr-2 text-destructive" /> Delete
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                    <CardTitle className="text-lg mt-2">{pm.bank_name}</CardTitle>
                                    <div className="flex items-center text-sm text-muted-foreground">
                                        <User className="w-3 h-3 mr-1" /> {pm.account_holder_name}
                                    </div>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="space-y-1">
                                        <p className="text-[10px] uppercase font-bold text-muted-foreground tracking-wider">IBAN</p>
                                        <p className="font-mono text-sm tracking-tighter bg-muted/50 p-2 rounded border border-border">
                                            {maskIBAN(pm.iban)}
                                        </p>
                                    </div>

                                    <div className="flex justify-between items-center">
                                        <div className="flex items-center space-x-2">
                                            <Globe className="w-3 h-3 text-muted-foreground" />
                                            <span className="text-xs font-medium uppercase text-muted-foreground">{pm.bank_country} • {pm.bank_currency}</span>
                                        </div>
                                        <StatusBadge status={pm.status} />
                                    </div>

                                    {pm.status === 'rejected' && pm.rejection_note && (
                                        <div className="p-3 bg-destructive/10 border border-destructive/20 rounded-lg flex items-start space-x-2">
                                            <AlertCircle className="w-4 h-4 text-destructive mt-0.5 shrink-0" />
                                            <p className="text-xs text-destructive">{pm.rejection_note}</p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        ))}

                        {paymentMethods.length === 0 && (
                            <Card className="col-span-full border-dashed border-2 bg-muted/30 shadow-none">
                                <CardContent className="flex flex-col items-center justify-center py-16 space-y-4">
                                    <div className="p-4 bg-background rounded-full border border-border">
                                        <Banknote className="w-8 h-8 text-muted-foreground" />
                                    </div>
                                    <div className="text-center">
                                        <p className="font-bold">No bank accounts added</p>
                                        <p className="text-sm text-muted-foreground">Add a bank account to start receiving withdrawals.</p>
                                    </div>
                                    <Button variant="outline" onClick={() => setIsAddOpen(true)} className="shadow-none">
                                        Add your first account
                                    </Button>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

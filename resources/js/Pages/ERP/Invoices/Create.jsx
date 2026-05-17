import React, { useState, useEffect } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import { PageHeader } from '@/Components/ui/PageHeader';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Plus, Trash2, GripVertical, Play, Clock, Save, Send, ChevronDown, ChevronUp, DollarSign } from 'lucide-react';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { Badge } from '@/components/ui/badge';

export default function CreateEdit({ invoice, clients, currencies, business_currency }) {
    const isEdit = !!invoice;
    const [showCosts, setShowCosts] = useState(false);

    const { data, setData, post, put, processing, errors } = useForm({
        client_id: invoice?.client_id || '',
        invoice_number: invoice?.invoice_number || `INV-${Date.now()}`,
        issued_at: invoice?.issued_at?.split('T')[0] || new Date().toISOString().split('T')[0],
        due_date: invoice?.due_date?.split('T')[0] || new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
        amount_currency: invoice?.amount_currency || 'USD',
        items: invoice?.items || [
            { type: 'simple', title: '', description: '', unit_price: 0, quantity: 1 }
        ],
        costs: invoice?.costs || [],
        discount_amount: invoice?.discount_amount || 0,
        tax_rate: invoice?.tax_rate || 0,
        notes: invoice?.notes || '',
    });

    const [exchangeRate, setExchangeRate] = useState(invoice?.exchange_rate || 1);

    // Auto-fill currency when client changes
    useEffect(() => {
        if (data.client_id) {
            const client = clients.find(c => String(c.id) === String(data.client_id));
            if (client && client.currency && !isEdit) {
                setData('amount_currency', client.currency);
            }
        }
    }, [data.client_id]);

    const addItem = (type = 'simple') => {
        setData('items', [
            ...data.items,
            { type, title: '', description: '', unit_price: 0, quantity: 1 }
        ]);
    };

    const removeItem = (index) => {
        const newItems = [...data.items];
        newItems.splice(index, 1);
        setData('items', newItems);
    };

    const updateItem = (index, field, value) => {
        const newItems = [...data.items];
        newItems[index] = { ...newItems[index], [field]: value };
        setData('items', newItems);
    };

    const addCost = () => {
        setData('costs', [
            ...data.costs,
            { title: '', amount: 0, payment_status: 'unpaid' }
        ]);
    };

    const removeCost = (index) => {
        const newCosts = [...data.costs];
        newCosts.splice(index, 1);
        setData('costs', newCosts);
    };

    const updateCost = (index, field, value) => {
        const newCosts = [...data.costs];
        newCosts[index] = { ...newCosts[index], [field]: value };
        setData('costs', newCosts);
    };

    const subtotal = data.items.reduce((acc, item) => acc + (item.unit_price * item.quantity), 0);
    const tax = (subtotal - data.discount_amount) * (data.tax_rate / 100);
    const total = subtotal - data.discount_amount + tax;

    const handleSubmit = (e) => {
        e.preventDefault();

        // Hardened Frontend Validation
        if (!data.client_id) {
            alert('Please select a client for this invoice.');
            return;
        }
        
        if (data.items.length === 0) {
            alert('An invoice must contain at least one line item.');
            return;
        }

        for (const item of data.items) {
            if (!item.title || !item.title.trim()) {
                alert('All line items must have a valid title.');
                return;
            }
            if (item.unit_price < 0 || item.quantity <= 0) {
                alert('Line items must have a positive quantity and valid price.');
                return;
            }
        }

        if (isEdit) {
            put(route('erp.invoices.update', invoice.id));
        } else {
            post(route('erp.invoices.store'));
        }
    };

    return (
        <AuthenticatedLayout header={isEdit ? 'Edit Invoice' : 'New Invoice'}>
            <Head title={isEdit ? 'Edit Invoice' : 'New Invoice'} />
            <form onSubmit={handleSubmit} className="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 space-y-6">
                <PageHeader title={isEdit ? `Edit Invoice ${invoice.invoice_number}` : 'Create New Invoice'}>
                    <Button type="button" variant="outline" asChild className="shadow-none">
                        <Link href={route('erp.invoices.index')}>Cancel</Link>
                    </Button>
                    <Button type="submit" disabled={processing} className="shadow-none">
                        <Save className="mr-2 h-4 w-4" /> {isEdit ? 'Update Invoice' : 'Save Draft'}
                    </Button>
                </PageHeader>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div className="lg:col-span-2 space-y-6">
                        <Card className="shadow-none">
                            <CardHeader>
                                <CardTitle>Invoice Details</CardTitle>
                            </CardHeader>
                            <CardContent className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label>Client</Label>
                                    <select
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 shadow-none"
                                        value={data.client_id}
                                        onChange={(e) => setData('client_id', e.target.value)}
                                    >
                                        <option value="">Search/Select Client</option>
                                        {clients.map((c) => (
                                            <option key={c.id} value={c.id}>{c.name} ({c.currency})</option>
                                        ))}
                                    </select>
                                    {errors.client_id && <div className="text-sm text-destructive font-medium">{errors.client_id}</div>}
                                </div>
                                <div className="space-y-2">
                                    <Label>Invoice Number</Label>
                                    <Input
                                        className="shadow-none"
                                        value={data.invoice_number}
                                        onChange={e => setData('invoice_number', e.target.value)}
                                        placeholder="INV-001"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label>Issue Date</Label>
                                    <Input
                                        className="shadow-none"
                                        type="date"
                                        value={data.issued_at}
                                        onChange={e => setData('issued_at', e.target.value)}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label>Due Date</Label>
                                    <Input
                                        className="shadow-none"
                                        type="date"
                                        value={data.due_date}
                                        onChange={e => setData('due_date', e.target.value)}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label>Currency</Label>
                                    <select
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 shadow-none"
                                        value={data.amount_currency}
                                        onChange={(e) => setData('amount_currency', e.target.value)}
                                    >
                                        {currencies.map((c) => (
                                            <option key={c.code} value={c.code}>{c.code} - {c.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div className="md:col-span-2 p-3 bg-muted/50 rounded-xl border text-sm text-muted-foreground">
                                    Rate: 1 {data.amount_currency} = {exchangeRate} {business_currency} (Auto-fetched)
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="shadow-none">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-4">
                                <CardTitle>Line Items</CardTitle>
                                <div className="flex gap-2">
                                    <Button type="button" variant="outline" size="sm" onClick={() => addItem('simple')} className="shadow-none">
                                        + Simple
                                    </Button>
                                    <Button type="button" variant="outline" size="sm" onClick={() => addItem('quantity')} className="shadow-none">
                                        + Quantity
                                    </Button>
                                    <Button type="button" variant="outline" size="sm" onClick={() => addItem('timer')} className="shadow-none">
                                        + Timer
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    {data.items.map((item, index) => (
                                        <div key={index} className="flex gap-4 items-start p-4 border rounded-xl bg-card transition-colors hover:bg-muted/10">
                                            <div className="mt-2 cursor-grab text-muted-foreground/50 hover:text-muted-foreground">
                                                <GripVertical className="h-5 w-5" />
                                            </div>
                                            <div className="flex-1 grid grid-cols-12 gap-4">
                                                <div className="col-span-12 md:col-span-6 space-y-2">
                                                    <div className="flex items-center gap-2">
                                                        <Badge variant="secondary" className={
                                                            item.type === 'quantity' ? 'bg-primary/10 text-primary hover:bg-primary/20' :
                                                            item.type === 'timer' ? 'bg-indigo-500/10 text-indigo-600 hover:bg-indigo-500/20' : ''
                                                        }>
                                                            {item.type}
                                                        </Badge>
                                                        <Input
                                                            className="shadow-none"
                                                            placeholder="Item title"
                                                            value={item.title}
                                                            onChange={e => updateItem(index, 'title', e.target.value)}
                                                        />
                                                    </div>
                                                    <textarea
                                                        placeholder="Description (optional)"
                                                        className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                                        value={item.description}
                                                        onChange={e => updateItem(index, 'description', e.target.value)}
                                                    />
                                                </div>
                                                <div className="col-span-4 md:col-span-2 space-y-2">
                                                    <Label className="text-xs">Qty</Label>
                                                    <Input
                                                        className="shadow-none"
                                                        type="number"
                                                        value={item.quantity}
                                                        onChange={e => updateItem(index, 'quantity', parseFloat(e.target.value))}
                                                    />
                                                </div>
                                                <div className="col-span-4 md:col-span-2 space-y-2">
                                                    <Label className="text-xs">Price</Label>
                                                    <Input
                                                        className="shadow-none"
                                                        type="number"
                                                        value={item.unit_price}
                                                        onChange={e => updateItem(index, 'unit_price', parseFloat(e.target.value))}
                                                    />
                                                </div>
                                                <div className="col-span-4 md:col-span-2 space-y-2">
                                                    <Label className="text-xs">Total</Label>
                                                    <div className="h-10 flex items-center font-bold px-3 bg-muted/50 rounded-md border text-foreground">
                                                        {(item.unit_price * item.quantity).toFixed(2)}
                                                    </div>
                                                </div>
                                                {item.type === 'timer' && (
                                                    <div className="col-span-12 flex items-center gap-4 p-3 bg-indigo-500/5 rounded-lg border border-indigo-500/20 text-indigo-700 text-sm">
                                                        <Clock className="h-4 w-4" />
                                                        <span className="font-medium">Timer Item: Rates are per unit of time (e.g. per hour).</span>
                                                        <Button type="button" size="sm" variant="ghost" className="ml-auto text-indigo-700 hover:text-indigo-800 hover:bg-indigo-500/10">
                                                            <Play className="mr-2 h-3 w-3" /> Start Session
                                                        </Button>
                                                    </div>
                                                )}
                                            </div>
                                            <Button type="button" variant="ghost" size="icon" className="text-destructive hover:bg-destructive/10 hover:text-destructive" onClick={() => removeItem(index)}>
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="shadow-none border-destructive/20 bg-destructive/5">
                            <CardHeader
                                className="flex flex-row items-center justify-between cursor-pointer select-none space-y-0"
                                onClick={() => setShowCosts(!showCosts)}
                            >
                                <CardTitle className="text-sm font-bold flex items-center gap-2 text-destructive">
                                    <DollarSign className="h-4 w-4" />
                                    Internal Costs (Admin Only)
                                </CardTitle>
                                <Button type="button" variant="ghost" size="sm" className="h-8 w-8 p-0 text-destructive hover:text-destructive hover:bg-destructive/10">
                                    {showCosts ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
                                </Button>
                            </CardHeader>
                            {showCosts && (
                                <CardContent className="space-y-4">
                                    {data.costs.map((cost, index) => (
                                        <div key={index} className="flex gap-4 items-center bg-card p-3 border rounded-xl shadow-sm">
                                            <div className="flex-1 grid grid-cols-12 gap-4">
                                                <div className="col-span-7">
                                                    <Input
                                                        className="shadow-none"
                                                        placeholder="Cost title (e.g. Outsourcing)"
                                                        value={cost.title}
                                                        onChange={e => updateCost(index, 'title', e.target.value)}
                                                    />
                                                </div>
                                                <div className="col-span-3">
                                                    <Input
                                                        className="shadow-none"
                                                        type="number"
                                                        placeholder="Amount"
                                                        value={cost.amount}
                                                        onChange={e => updateCost(index, 'amount', parseFloat(e.target.value))}
                                                    />
                                                </div>
                                                <div className="col-span-2">
                                                    <select
                                                        className="flex h-10 w-full rounded-md border border-input bg-background px-2 py-1 text-xs ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 shadow-none"
                                                        value={cost.payment_status}
                                                        onChange={e => updateCost(index, 'payment_status', e.target.value)}
                                                    >
                                                        <option value="unpaid">Unpaid</option>
                                                        <option value="paid">Paid</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <Button type="button" variant="ghost" size="icon" className="text-destructive hover:bg-destructive/10 hover:text-destructive" onClick={() => removeCost(index)}>
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    ))}
                                    <Button type="button" variant="outline" size="sm" className="w-full text-destructive border-destructive/20 hover:bg-destructive/10 shadow-none" onClick={addCost}>
                                        + Add Cost Row
                                    </Button>
                                </CardContent>
                            )}
                        </Card>

                        <Card className="shadow-none">
                            <CardHeader>
                                <CardTitle>Notes</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <textarea
                                    placeholder="Additional notes for the client..."
                                    className="flex min-h-[128px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    value={data.notes}
                                    onChange={e => setData('notes', e.target.value)}
                                />
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        <Card className="sticky top-6 shadow-none">
                            <CardHeader>
                                <CardTitle>Summary</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground font-medium">Subtotal</span>
                                    <span className="font-semibold text-foreground"><CurrencyDisplay amount={subtotal} currency={data.amount_currency} /></span>
                                </div>
                                <div className="space-y-2">
                                    <div className="flex justify-between text-sm items-center">
                                        <span className="text-muted-foreground font-medium">Discount</span>
                                        <div className="flex items-center gap-2">
                                            <Input
                                                type="number"
                                                className="w-20 h-8 shadow-none"
                                                value={data.discount_amount}
                                                onChange={e => setData('discount_amount', parseFloat(e.target.value))}
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <div className="flex justify-between text-sm items-center">
                                        <span className="text-muted-foreground font-medium">Tax (%)</span>
                                        <Input
                                            type="number"
                                            className="w-20 h-8 shadow-none"
                                            value={data.tax_rate}
                                            onChange={e => setData('tax_rate', parseFloat(e.target.value))}
                                        />
                                    </div>
                                </div>
                                <div className="pt-4 border-t flex justify-between items-center">
                                    <span className="font-bold text-base">Total</span>
                                    <div className="text-right">
                                        <div className="text-2xl font-bold tracking-tight text-primary">
                                            <CurrencyDisplay amount={total} currency={data.amount_currency} />
                                        </div>
                                        <div className="text-xs font-medium text-muted-foreground">
                                            ≈ <CurrencyDisplay amount={total * exchangeRate} currency={business_currency} />
                                        </div>
                                    </div>
                                </div>
                                <div className="pt-6 space-y-3">
                                    <Button className="w-full shadow-none" disabled={processing} type="submit">
                                        {isEdit ? 'Save Changes' : 'Create Invoice'}
                                    </Button>
                                    {!isEdit && (
                                        <Button variant="outline" className="w-full shadow-none" type="button">
                                            <Send className="mr-2 h-4 w-4" /> Save & Send
                                        </Button>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}

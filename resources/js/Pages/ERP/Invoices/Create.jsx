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
            
            <form onSubmit={handleSubmit} className="max-w-[900px] mx-auto px-4 sm:px-6 lg:px-8 py-10 font-sans">
                
                {/* ──────────────────────────────────────────────────────── */}
                {/* PAGE HEADER */}
                {/* ──────────────────────────────────────────────────────── */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-12">
                    <div>
                        <h1 className="text-2xl font-semibold text-slate-900 tracking-tight">
                            {isEdit ? `Edit Invoice ${invoice.invoice_number}` : 'New Invoice'}
                        </h1>
                        <p className="text-sm text-slate-500 mt-1">Draft a new invoice to bill your client directly.</p>
                    </div>
                    <div className="flex items-center gap-3">
                        <Button type="button" variant="ghost" asChild className="hover:bg-slate-100 text-slate-600 transition-colors">
                            <Link href={route('erp.invoices.index')}>Cancel</Link>
                        </Button>
                        <Button type="submit" disabled={processing} className="shadow-sm bg-slate-900 text-white hover:bg-slate-800 transition-colors">
                            {isEdit ? 'Update Invoice' : 'Save Draft'}
                        </Button>
                        {!isEdit && (
                            <Button type="button" disabled={processing} className="shadow-sm bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">
                                <Send className="mr-2 h-4 w-4" /> Save & Send
                            </Button>
                        )}
                    </div>
                </div>

                <div className="space-y-12">
                    
                    {/* ──────────────────────────────────────────────────────── */}
                    {/* 1. CLIENT & DETAILS */}
                    {/* ──────────────────────────────────────────────────────── */}
                    <section className="grid grid-cols-1 md:grid-cols-12 gap-8">
                        <div className="md:col-span-4">
                            <h2 className="text-sm font-semibold text-slate-900 uppercase tracking-wider">1. Client Details</h2>
                            <p className="text-sm text-slate-500 mt-2 leading-relaxed">Select the client, adjust billing dates, and choose the correct currency.</p>
                        </div>
                        <div className="md:col-span-8 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm space-y-6">
                            <div className="space-y-2">
                                <Label className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Client</Label>
                                <select
                                    className="flex h-11 w-full rounded-lg border-slate-200 bg-slate-50/50 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors"
                                    value={data.client_id}
                                    onChange={(e) => setData('client_id', e.target.value)}
                                >
                                    <option value="">Select a client...</option>
                                    {clients.map((c) => (
                                        <option key={c.id} value={c.id}>{c.name} ({c.currency})</option>
                                    ))}
                                </select>
                                {errors.client_id && <div className="text-xs text-rose-500 font-medium mt-1">{errors.client_id}</div>}
                            </div>
                            
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div className="space-y-2">
                                    <Label className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Invoice Number</Label>
                                    <Input
                                        className="h-11 rounded-lg border-slate-200 bg-slate-50/50 focus:bg-white transition-colors"
                                        value={data.invoice_number}
                                        onChange={e => setData('invoice_number', e.target.value)}
                                        placeholder="INV-001"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Currency</Label>
                                    <select
                                        className="flex h-11 w-full rounded-lg border-slate-200 bg-slate-50/50 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors"
                                        value={data.amount_currency}
                                        onChange={(e) => setData('amount_currency', e.target.value)}
                                    >
                                        {currencies.map((c) => (
                                            <option key={c.code} value={c.code}>{c.code} - {c.name}</option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div className="space-y-2">
                                    <Label className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Issue Date</Label>
                                    <Input
                                        className="h-11 rounded-lg border-slate-200 bg-slate-50/50 focus:bg-white transition-colors"
                                        type="date"
                                        value={data.issued_at}
                                        onChange={e => setData('issued_at', e.target.value)}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Due Date</Label>
                                    <Input
                                        className="h-11 rounded-lg border-slate-200 bg-slate-50/50 focus:bg-white transition-colors"
                                        type="date"
                                        value={data.due_date}
                                        onChange={e => setData('due_date', e.target.value)}
                                    />
                                </div>
                            </div>
                            
                            {data.amount_currency !== business_currency && (
                                <div className="p-3 bg-slate-50 rounded-lg border border-slate-100 text-xs text-slate-500 flex items-center gap-2">
                                    <Clock className="h-3.5 w-3.5 text-slate-400" />
                                    <span>Exchange Rate: 1 {data.amount_currency} = {exchangeRate} {business_currency}</span>
                                </div>
                            )}
                        </div>
                    </section>

                    <hr className="border-slate-100" />

                    {/* ──────────────────────────────────────────────────────── */}
                    {/* 2. LINE ITEMS (FAST INLINE EDITING) */}
                    {/* ──────────────────────────────────────────────────────── */}
                    <section className="space-y-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <h2 className="text-sm font-semibold text-slate-900 uppercase tracking-wider">2. Line Items</h2>
                                <p className="text-sm text-slate-500 mt-1">Add the products or services provided. Use Tab to navigate quickly.</p>
                            </div>
                        </div>

                        <div className="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                            {/* Table Header */}
                            <div className="flex items-center px-6 py-3 border-b border-slate-100 bg-slate-50/50 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <div className="flex-1">Item Description</div>
                                <div className="w-24 text-right">Qty</div>
                                <div className="w-32 text-right">Price</div>
                                <div className="w-32 text-right">Total</div>
                                <div className="w-10"></div>
                            </div>

                            {/* Table Rows */}
                            <div className="divide-y divide-slate-50">
                                {data.items.map((item, index) => (
                                    <div key={index} className="flex items-start gap-4 p-4 group hover:bg-slate-50/50 transition-colors">
                                        <div className="flex-1 space-y-1">
                                            <Input
                                                className="h-9 font-medium text-slate-900 shadow-none border-transparent bg-transparent hover:border-slate-200 focus:border-indigo-500 focus:bg-white transition-all px-2 placeholder:text-slate-300"
                                                placeholder="Service or product name..."
                                                value={item.title}
                                                onChange={e => updateItem(index, 'title', e.target.value)}
                                            />
                                            <Input
                                                className="h-8 text-sm text-slate-500 shadow-none border-transparent bg-transparent hover:border-slate-200 focus:border-indigo-500 focus:bg-white transition-all px-2 placeholder:text-slate-300"
                                                placeholder="Optional description..."
                                                value={item.description}
                                                onChange={e => updateItem(index, 'description', e.target.value)}
                                            />
                                        </div>
                                        <div className="w-24 pt-0.5">
                                            <Input
                                                className="h-8 text-right shadow-none border-transparent bg-transparent hover:border-slate-200 focus:border-indigo-500 focus:bg-white transition-all px-2"
                                                type="number"
                                                min="0.01"
                                                step="0.01"
                                                placeholder="0"
                                                value={item.quantity || ''}
                                                onChange={e => updateItem(index, 'quantity', parseFloat(e.target.value) || 0)}
                                            />
                                        </div>
                                        <div className="w-32 pt-0.5">
                                            <Input
                                                className="h-8 text-right shadow-none border-transparent bg-transparent hover:border-slate-200 focus:border-indigo-500 focus:bg-white transition-all px-2"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                placeholder="0.00"
                                                value={item.unit_price || ''}
                                                onChange={e => updateItem(index, 'unit_price', parseFloat(e.target.value) || 0)}
                                            />
                                        </div>
                                        <div className="w-32 text-right pt-2 font-medium text-slate-700 px-2">
                                            {new Intl.NumberFormat('en-US', { style: 'currency', currency: data.amount_currency }).format(item.unit_price * item.quantity)}
                                        </div>
                                        <div className="w-10 pt-1.5 opacity-0 group-hover:opacity-100 transition-opacity flex justify-end">
                                            <button 
                                                type="button" 
                                                onClick={() => removeItem(index)}
                                                className="text-slate-400 hover:text-rose-500 p-1 rounded hover:bg-rose-50 transition-colors"
                                            >
                                                <Trash2 className="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                            
                            {/* Add Item Actions */}
                            <div className="p-4 border-t border-slate-100 bg-slate-50/30">
                                <Button type="button" variant="ghost" size="sm" onClick={() => addItem('simple')} className="text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 font-medium">
                                    <Plus className="mr-1.5 h-4 w-4" /> Add Line Item
                                </Button>
                            </div>
                        </div>
                    </section>

                    <hr className="border-slate-100" />

                    {/* ──────────────────────────────────────────────────────── */}
                    {/* 3. PAYMENT DETAILS & SUMMARY */}
                    {/* ──────────────────────────────────────────────────────── */}
                    <section className="grid grid-cols-1 md:grid-cols-12 gap-8">
                        <div className="md:col-span-6 space-y-6">
                            <div className="space-y-2">
                                <Label className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Invoice Notes</Label>
                                <textarea
                                    placeholder="Payment instructions, thank you message, or additional details..."
                                    className="flex min-h-[120px] w-full rounded-xl border-slate-200 bg-white px-4 py-3 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors resize-none placeholder:text-slate-400"
                                    value={data.notes}
                                    onChange={e => setData('notes', e.target.value)}
                                />
                            </div>

                            {/* Collapsible Advanced Settings (Internal Costs) */}
                            <div className="border border-slate-100 rounded-xl overflow-hidden bg-white">
                                <button
                                    type="button"
                                    className="w-full px-4 py-3 flex items-center justify-between text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors"
                                    onClick={() => setShowCosts(!showCosts)}
                                >
                                    <div className="flex items-center gap-2">
                                        <DollarSign className="h-4 w-4 text-slate-400" />
                                        Advanced: Internal Costs
                                    </div>
                                    {showCosts ? <ChevronUp className="h-4 w-4 text-slate-400" /> : <ChevronDown className="h-4 w-4 text-slate-400" />}
                                </button>
                                {showCosts && (
                                    <div className="p-4 border-t border-slate-100 bg-slate-50 space-y-3">
                                        <p className="text-xs text-slate-500 mb-3">Track internal expenses (e.g. outsourcing, software) associated with this invoice.</p>
                                        {data.costs.map((cost, index) => (
                                            <div key={index} className="flex gap-3 items-center">
                                                <Input
                                                    className="h-8 bg-white"
                                                    placeholder="Cost description..."
                                                    value={cost.title}
                                                    onChange={e => updateCost(index, 'title', e.target.value)}
                                                />
                                                <Input
                                                    className="h-8 w-24 bg-white"
                                                    type="number"
                                                    placeholder="0.00"
                                                    value={cost.amount}
                                                    onChange={e => updateCost(index, 'amount', parseFloat(e.target.value))}
                                                />
                                                <button type="button" onClick={() => removeCost(index)} className="text-slate-400 hover:text-rose-500">
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            </div>
                                        ))}
                                        <Button type="button" variant="outline" size="sm" className="w-full h-8 text-xs bg-white shadow-sm" onClick={addCost}>
                                            <Plus className="mr-1.5 h-3 w-3" /> Add Cost
                                        </Button>
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="md:col-span-6 md:pl-12">
                            <div className="bg-slate-50 rounded-2xl p-6 border border-slate-100 space-y-4">
                                <div className="flex justify-between items-center text-sm">
                                    <span className="text-slate-500 font-medium">Subtotal</span>
                                    <span className="font-semibold text-slate-900">
                                        {new Intl.NumberFormat('en-US', { style: 'currency', currency: data.amount_currency }).format(subtotal)}
                                    </span>
                                </div>
                                <div className="flex justify-between items-center text-sm">
                                    <span className="text-slate-500 font-medium">Discount Amount</span>
                                    <div className="w-24">
                                        <Input
                                            type="number"
                                            className="h-8 text-right bg-white shadow-sm border-slate-200"
                                            value={data.discount_amount || ''}
                                            placeholder="0.00"
                                            onChange={e => setData('discount_amount', parseFloat(e.target.value) || 0)}
                                        />
                                    </div>
                                </div>
                                <div className="flex justify-between items-center text-sm">
                                    <span className="text-slate-500 font-medium">Tax Rate (%)</span>
                                    <div className="w-24">
                                        <Input
                                            type="number"
                                            className="h-8 text-right bg-white shadow-sm border-slate-200"
                                            value={data.tax_rate || ''}
                                            placeholder="0%"
                                            onChange={e => setData('tax_rate', parseFloat(e.target.value) || 0)}
                                        />
                                    </div>
                                </div>
                                <div className="pt-4 mt-2 border-t border-slate-200 flex justify-between items-end">
                                    <span className="font-semibold text-slate-900">Total Due</span>
                                    <div className="text-right">
                                        <div className="text-3xl font-bold tracking-tight text-indigo-600">
                                            {new Intl.NumberFormat('en-US', { style: 'currency', currency: data.amount_currency }).format(total)}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                </div>
            </form>
        </AuthenticatedLayout>
    );
}

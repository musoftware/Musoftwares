import React, { useState, useEffect } from 'react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head, useForm, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { useToast } from '@/Components/ui/use-toast';
import { Plus, Trash2, Clock, Send, ChevronDown, ChevronUp, DollarSign, AlertCircle } from 'lucide-react';
import { cn } from '@/lib/utils';

function FieldError({ message }) {
    if (!message) return null;

    return (
        <div className="flex items-center gap-1.5 mt-1.5">
            <AlertCircle className="h-3.5 w-3.5 text-red-500 shrink-0" />
            <p className="text-xs text-red-600 font-medium">{message}</p>
        </div>
    );
}

export default function CreateEdit({ invoice, clients = [], projects = [], products = [], has_inventory_addon, currencies, business_currency, pre_selected_client_id, pre_selected_project_id }) {
    const isEdit = !!invoice;
    const [showCosts, setShowCosts] = useState(false);
    const [clientError, setClientError] = useState('');
    const [itemErrors, setItemErrors] = useState({});
    const { toast } = useToast();

    const { data, setData, post, put, processing, errors } = useForm({
        client_id: invoice?.client_id || pre_selected_client_id || '',
        project_id: invoice?.project_id || pre_selected_project_id || '',
        invoice_number: invoice?.invoice_number || `INV-${Date.now()}`,
        issued_at: invoice?.issued_at?.split('T')[0] || new Date().toISOString().split('T')[0],
        due_date: invoice?.due_date?.split('T')[0] || new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
        amount_currency: invoice?.currency || invoice?.client?.currency || null,
        items: invoice?.items || [
            { type: 'simple', title: '', description: '', unit_price: 0, quantity: 1 }
        ],
        costs: invoice?.costs || [],
        discount_amount: invoice?.discount_amount || 0,
        tax_rate: invoice?.tax_rate || 0,
        notes: invoice?.notes || '',
    });


    useEffect(() => {
        if (data.client_id) {
            const client = clients.find(c => String(c.id) === String(data.client_id));
            if (client) {
                setData('amount_currency', client.currency);
            }

            if (data.project_id) {
                const project = projects.find(p => String(p.id) === String(data.project_id));
                if (project && String(project.client_id) !== String(data.client_id)) {
                    setData('project_id', '');
                }
            }
        } else {
            setData('project_id', '');
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [data.client_id, clients, projects]);

    const filteredProjects = data.client_id
        ? projects.filter(p => String(p.client_id) === String(data.client_id))
        : [];

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
        // Clear item error when user types
        if (itemErrors[index]) {
            setItemErrors(prev => ({ ...prev, [index]: undefined }));
        }
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

    /** Inline validation — no alert() calls */
    const validate = () => {
        let valid = true;

        if (!data.client_id) {
            setClientError('Please select a client for this invoice.');
            valid = false;
        } else {
            setClientError('');
        }

        if (data.items.length === 0) {
            toast({ variant: 'destructive', title: 'No line items', description: 'An invoice must contain at least one line item.' });
            valid = false;
        }

        const newItemErrors = {};
        data.items.forEach((item, i) => {
            if (!item.title?.trim()) {
                newItemErrors[i] = 'Item title is required.';
                valid = false;
            } else if (item.unit_price < 0 || item.quantity <= 0) {
                newItemErrors[i] = 'Quantity must be > 0 and price must be ≥ 0.';
                valid = false;
            }
        });
        setItemErrors(newItemErrors);

        return valid;
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (!validate()) return;

        const options = {
            onError: (errs) => {
                const firstError = Object.values(errs)[0];
                if (firstError) {
                    toast({ variant: 'destructive', title: 'Validation error', description: firstError });
                }
            },
        };

        if (isEdit) {
            put(route('erp.invoices.update', invoice.id), options);
        } else {
            post(route('erp.invoices.store'), options);
        }
    };
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('invoices');

    return (
        <ERPLayout title={isEdit ? 'Edit Invoice' : 'New Invoice'} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>

            <form onSubmit={handleSubmit} className="max-w-[900px] mx-auto px-4 sm:px-6 lg:px-8 py-10 font-sans">

                {/* ── Page Header ─────────────────────────────────────────── */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-12">
                    <div>
                        <h1 className="text-2xl font-semibold text-slate-900 tracking-tight">
                            {isEdit ? `Edit Invoice ${invoice.invoice_number}` : 'New Invoice'}
                        </h1>
                        <p className="text-sm text-slate-500 mt-1">
                            {isEdit ? 'Update invoice details and line items.' : 'Draft a new invoice to bill your client directly.'}
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        <Button type="button" variant="ghost" asChild className="hover:bg-slate-100 text-slate-600">
                            <Link href={route('erp.invoices.index')}>Cancel</Link>
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing}
                            className="shadow-sm bg-slate-900 text-white hover:bg-slate-800"
                        >
                            {processing ? 'Saving...' : isEdit ? 'Update Invoice' : 'Save Draft'}
                        </Button>

                    </div>
                </div>

                <div className="space-y-12">

                    {/* ── 1. Client Details ───────────────────────────────── */}
                    <section className="grid grid-cols-1 md:grid-cols-12 gap-8">
                        <div className="md:col-span-4">
                            <h2 className="text-sm font-semibold text-slate-900 uppercase tracking-wider">1. Client Details</h2>
                            <p className="text-sm text-slate-500 mt-2 leading-relaxed">{__('general.select_the_client_adjust_billing_dates_and_choose_the_correct_currency')}</p>
                        </div>
                        <div className="md:col-span-8 bg-white p-6 rounded-xl border border-slate-100 shadow-sm space-y-6">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    Client <span className="text-red-500">*</span>
                                </Label>
                                <select
                                    className={cn(
                                        "flex h-11 w-full rounded-lg border bg-slate-50/50 px-3 py-2 text-sm transition-colors",
                                        "focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:bg-white",
                                        clientError ? "border-red-300 bg-red-50/30" : "border-slate-200"
                                    )}
                                    value={data.client_id}
                                    onChange={(e) => {
                                        setData('client_id', e.target.value);
                                        setClientError('');
                                    }}
                                >
                                    <option value="">{__('general.select_a_client')}</option>
                                    {clients.map((c) => (
                                        <option key={c.id} value={c.id}>
                                            {c.name} ({c.currency?.currency || c.currency_code || 'N/A'})
                                        </option>
                                    ))}
                                </select>
                                <FieldError message={clientError || errors.client_id} />
                            </div>

                            {data.client_id && filteredProjects.length > 0 && (
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        Associated Project (Optional)
                                    </Label>
                                    <select
                                        className="flex h-11 w-full rounded-lg border border-slate-200 bg-slate-50/50 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:bg-white transition-colors"
                                        value={data.project_id}
                                        onChange={(e) => setData('project_id', e.target.value)}
                                    >
                                        <option value="">None (Independent Invoice)</option>
                                        {filteredProjects.map((p) => (
                                            <option key={p.id} value={p.id}>
                                                {p.name}
                                            </option>
                                        ))}
                                    </select>
                                    <FieldError message={errors.project_id} />
                                </div>
                            )}

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold text-slate-500 uppercase tracking-wider">{__('general.invoice_number')}</Label>
                                    <Input
                                        className="h-11 rounded-lg border-slate-200 bg-slate-50/50 focus:bg-white"
                                        value={data.invoice_number}
                                        onChange={e => setData('invoice_number', e.target.value)}
                                        placeholder={__('general.inv_001')}
                                    />
                                    <FieldError message={errors.invoice_number} />
                                </div>
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        Currency
                                    </Label>
                                    <Input
                                        className="h-11 rounded-lg border-slate-200 bg-slate-100/50 text-slate-700 font-medium"
                                        value={data.amount_currency}
                                        disabled
                                        readOnly
                                    />
                                    <p className="text-[10px] text-slate-400">{__('general.inherited_from_client_profile')}</p>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold text-slate-500 uppercase tracking-wider">{__('general.issue_date')}</Label>
                                    <Input
                                        className="h-11 rounded-lg border-slate-200 bg-slate-50/50 focus:bg-white"
                                        type="date"
                                        value={data.issued_at}
                                        onChange={e => setData('issued_at', e.target.value)}
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold text-slate-500 uppercase tracking-wider">{__('general.due_date')}</Label>
                                    <Input
                                        className="h-11 rounded-lg border-slate-200 bg-slate-50/50 focus:bg-white"
                                        type="date"
                                        value={data.due_date}
                                        onChange={e => setData('due_date', e.target.value)}
                                    />
                                </div>
                            </div>

                        </div>
                    </section>

                    <hr className="border-slate-100" />

                    {/* ── 2. Line Items ───────────────────────────────────── */}
                    <section className="space-y-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <h2 className="text-sm font-semibold text-slate-900 uppercase tracking-wider">2. Line Items</h2>
                                <p className="text-sm text-slate-500 mt-1">{__('general.add_the_products_or_services_provided_use_tab_to_navigate_quickly')}</p>
                            </div>
                        </div>

                        <div className="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                            {/* Table Header */}
                            <div className="flex items-center px-6 py-3 border-b border-slate-100 bg-slate-50/50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                                <div className="flex-1">{__('general.item_description')}</div>
                                <div className="w-24 text-end">Qty</div>
                                <div className="w-32 text-end">Price</div>
                                <div className="w-32 text-end">Total</div>
                                <div className="w-10" />
                            </div>

                            <div className="divide-y divide-slate-50">
                                {data.items.map((item, index) => (
                                    <div key={index} className="flex items-start gap-4 p-4 group hover:bg-slate-50/50 transition-colors">
                                        <div className="flex-1 space-y-1">
                                            {has_inventory_addon && (
                                                <select
                                                    className="mb-1 h-8 w-full text-sm text-slate-600 shadow-none border-slate-200 bg-slate-50 px-2 rounded hover:border-slate-300 focus:border-indigo-500 transition-all"
                                                    value={item.product_id || ''}
                                                    onChange={e => {
                                                        const productId = e.target.value;
                                                        const product = products.find(p => String(p.id) === String(productId));
                                                        const newItems = [...data.items];
                                                        if (product) {
                                                            newItems[index] = { 
                                                                ...newItems[index], 
                                                                product_id: productId,
                                                                title: product.name,
                                                                unit_price: parseFloat(product.price),
                                                                description: product.sku ? `SKU: ${product.sku}` : '',
                                                                uom: product.uom
                                                            };
                                                        } else {
                                                            newItems[index] = { ...newItems[index], product_id: '', uom: null };
                                                        }
                                                        setData('items', newItems);
                                                        if (itemErrors[index]) {
                                                            setItemErrors(prev => ({ ...prev, [index]: undefined }));
                                                        }
                                                    }}
                                                >
                                                    <option value="">+ Load from Inventory...</option>
                                                    {products.map(p => (
                                                        <option key={p.id} value={p.id}>{p.name} ({p.price})</option>
                                                    ))}
                                                </select>
                                            )}
                                            <Input
                                                className={cn(
                                                    "h-9 font-medium text-slate-900 shadow-none border-transparent bg-transparent px-2",
                                                    "hover:border-slate-200 focus:border-indigo-500 focus:bg-white transition-all placeholder:text-slate-300",
                                                    itemErrors[index] ? "border-red-300" : ""
                                                )}
                                                placeholder={__('general.service_or_product_name')}
                                                value={item.title}
                                                onChange={e => updateItem(index, 'title', e.target.value)}
                                            />
                                            {itemErrors[index] && (
                                                <FieldError message={itemErrors[index]} />
                                            )}
                                            <Input
                                                className="h-8 text-sm text-slate-500 shadow-none border-transparent bg-transparent hover:border-slate-200 focus:border-indigo-500 focus:bg-white transition-all px-2 placeholder:text-slate-300"
                                                placeholder={__('general.optional_description')}
                                                value={item.description || ''}
                                                onChange={e => updateItem(index, 'description', e.target.value)}
                                            />
                                        </div>
                                        <div className="w-24 pt-0.5 relative">
                                            <Input
                                                className="h-8 text-end shadow-none border-transparent bg-transparent hover:border-slate-200 focus:border-indigo-500 focus:bg-white transition-all px-2 pe-8"
                                                type="number"
                                                min="0.01"
                                                step="0.01"
                                                placeholder="0"
                                                value={item.quantity || ''}
                                                onChange={e => updateItem(index, 'quantity', parseFloat(e.target.value) || 0)}
                                            />
                                            {item.uom && (
                                                <span className="absolute end-2 top-2 text-[10px] text-slate-400 font-medium select-none pointer-events-none uppercase">
                                                    {item.uom}
                                                </span>
                                            )}
                                        </div>
                                        <div className="w-32 pt-0.5">
                                            <Input
                                                className="h-8 text-end shadow-none border-transparent bg-transparent hover:border-slate-200 focus:border-indigo-500 focus:bg-white transition-all px-2"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                placeholder="0.00"
                                                value={item.unit_price || ''}
                                                onChange={e => updateItem(index, 'unit_price', parseFloat(e.target.value) || 0)}
                                            />
                                        </div>
                                        <div className="w-32 text-end pt-2 font-medium text-slate-700 px-2 text-[13px]">
                                            <CurrencyDisplay amount={item.unit_price * item.quantity} currency={data.amount_currency} />
                                        </div>
                                        <div className="w-10 pt-1.5 opacity-0 group-hover:opacity-100 transition-opacity flex justify-end">
                                            <button
                                                type="button"
                                                onClick={() => removeItem(index)}
                                                className="text-slate-400 hover:text-red-500 p-1 rounded hover:bg-red-50 transition-colors"
                                            >
                                                <Trash2 className="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            <div className="p-4 border-t border-slate-100 bg-slate-50/30">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => addItem('simple')}
                                    className="text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 font-medium"
                                >
                                    <Plus className="me-1.5 h-4 w-4" />{__('general.add_line_item')}</Button>
                            </div>
                        </div>
                    </section>

                    <hr className="border-slate-100" />

                    {/* ── 3. Payment Details & Summary ────────────────────── */}
                    <section className="grid grid-cols-1 md:grid-cols-12 gap-8">
                        <div className="md:col-span-6 space-y-6">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold text-slate-500 uppercase tracking-wider">{__('general.invoice_notes')}</Label>
                                <textarea
                                    placeholder={__('general.payment_instructions_thank_you_message_or_additional_details')}
                                    className="flex min-h-[120px] w-full rounded-xl border-slate-200 bg-white px-4 py-3 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors resize-none placeholder:text-slate-400"
                                    value={data.notes}
                                    onChange={e => setData('notes', e.target.value)}
                                />
                            </div>

                            {/* Collapsible Internal Costs */}
                            <div className="border border-slate-100 rounded-xl overflow-hidden bg-white">
                                <button
                                    type="button"
                                    className="w-full px-4 py-3 flex items-center justify-between text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors"
                                    onClick={() => setShowCosts(!showCosts)}
                                >
                                    <div className="flex items-center gap-2">
                                        <DollarSign className="h-4 w-4 text-slate-400" />
                                        Internal Costs (Optional)
                                    </div>
                                    {showCosts
                                        ? <ChevronUp className="h-4 w-4 text-slate-400" />
                                        : <ChevronDown className="h-4 w-4 text-slate-400" />}
                                </button>
                                {showCosts && (
                                    <div className="p-4 border-t border-slate-100 bg-slate-50 space-y-3">
                                        <p className="text-xs text-slate-500">{__('general.track_internal_expenses_e_g_outsourcing_software_associated_with_this_invoice')}</p>
                                        {data.costs.map((cost, index) => (
                                            <div key={index} className="flex gap-3 items-center">
                                                <Input
                                                    className="h-8 bg-white"
                                                    placeholder={__('general.cost_description')}
                                                    value={cost.title}
                                                    onChange={e => updateCost(index, 'title', e.target.value)}
                                                />
                                                <Input
                                                    className="h-8 w-28 bg-white text-end"
                                                    type="number"
                                                    placeholder="0.00"
                                                    value={cost.amount}
                                                    onChange={e => updateCost(index, 'amount', parseFloat(e.target.value))}
                                                />
                                                <button
                                                    type="button"
                                                    onClick={() => removeCost(index)}
                                                    className="text-slate-400 hover:text-red-500 transition-colors"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            </div>
                                        ))}
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            className="w-full h-8 text-xs bg-white shadow-sm"
                                            onClick={addCost}
                                        >
                                            <Plus className="me-1.5 h-3 w-3" />{__('general.add_cost')}</Button>
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="md:col-span-6 md:ps-12">
                            <div className="bg-slate-50 rounded-xl p-6 border border-slate-100 space-y-4">
                                <div className="flex justify-between items-center text-sm">
                                    <span className="text-slate-500 font-medium">Subtotal</span>
                                    <CurrencyDisplay amount={subtotal} currency={data.amount_currency} />
                                </div>
                                <div className="flex justify-between items-center text-sm">
                                    <span className="text-slate-500 font-medium">Discount</span>
                                    <div className="w-24">
                                        <Input
                                            type="number"
                                            className="h-8 text-end bg-white shadow-sm border-slate-200"
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
                                            className="h-8 text-end bg-white shadow-sm border-slate-200"
                                            value={data.tax_rate || ''}
                                            placeholder="0%"
                                            onChange={e => setData('tax_rate', parseFloat(e.target.value) || 0)}
                                        />
                                    </div>
                                </div>
                                <div className="pt-4 mt-2 border-t border-slate-200 flex justify-between items-end">
                                    <span className="font-semibold text-slate-900">{__('general.total_due')}</span>
                                    <div className="text-end">
                                        <div className="text-3xl font-bold tracking-tight text-indigo-600">
                                            <CurrencyDisplay amount={total} currency={data.amount_currency} />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                </div>
            </form>
        </ERPLayout>
    );
}

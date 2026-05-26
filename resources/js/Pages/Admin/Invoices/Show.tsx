import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/Components/ui/card';
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { formatMoney as formatCurrency } from '@/lib/utils';
import { 
    Printer, Download, Share2, User, MapPin, Phone, Folder, Receipt, 
    Clock, Layers, Plus, CreditCard, List, Edit2, Check, X, Trash2,
    ChartLine, AlertCircle, Network, Calculator, Merge
} from 'lucide-react';

export default function Show({ invoice }) {
    // Editable state
    const [isEditing, setIsEditing] = useState(false);
    const [isSaving, setIsSaving] = useState(false);
    
    // Local copy of items for inline editing
    const [items, setItems] = useState([]);
    const [deletedItems, setDeletedItems] = useState([]);
    const [discount, setDiscount] = useState(0);
    const [discountPercentage, setDiscountPercentage] = useState(0);
    
    const [costLines, setCostLines] = useState([]);
    const [deletedCostLines, setDeletedCostLines] = useState([]);
    
    const [selectedItemsForMerge, setSelectedItemsForMerge] = useState([]);
    const [showPricingInsights, setShowPricingInsights] = useState(false);

    useEffect(() => {
        if (invoice && invoice.items) {
            setItems(invoice.items.map(item => ({ ...item, isNew: false })));
        }
        if (invoice && invoice.cost_lines) {
            setCostLines(invoice.cost_lines.map(line => ({ ...line, isNew: false })));
        }
        setDiscount(invoice.discount || 0);
        setDiscountPercentage(0);
        setDeletedItems([]);
        setDeletedCostLines([]);
        setSelectedItemsForMerge([]);
        setIsEditing(false);
    }, [invoice]);

    const isUnpaid = invoice.status === 'unpaid';
    
    const handleAddQtyItem = () => {
        setIsEditing(true);
        setItems([...items, {
            id: 'new-' + Date.now(),
            isNew: true,
            item_title: '',
            item_type: 'quantity',
            amount: 0,
            qty: 1,
            currency: invoice.currency
        }]);
    };

    const handleAddSimpleItem = () => {
        setIsEditing(true);
        setItems([...items, {
            id: 'new-' + Date.now(),
            isNew: true,
            item_title: '',
            item_type: 'simple',
            amount: 0,
            qty: 1,
            currency: invoice.currency
        }]);
    };

    const handleDeleteItem = (index) => {
        const item = items[index];
        if (!item.isNew) {
            setDeletedItems([...deletedItems, item.id]);
        }
        const newItems = [...items];
        newItems.splice(index, 1);
        setItems(newItems);
    };

    const handleItemChange = (index, field, value) => {
        const newItems = [...items];
        newItems[index][field] = value;
        setItems(newItems);
    };

    const handleMergeSelected = () => {
        if (selectedItemsForMerge.length < 2) return;
        
        // Simple merge: combine titles, sum prices and qtys
        const itemsToMerge = items.filter((_, idx) => selectedItemsForMerge.includes(idx));
        const mergedTitle = itemsToMerge.map(i => i.item_title).join(' + ');
        const mergedAmount = itemsToMerge.reduce((acc, i) => acc + parseFloat(i.amount || 0), 0);
        const mergedQty = itemsToMerge.reduce((acc, i) => acc + parseInt(i.qty || 1), 0);
        
        const mergedItem = {
            id: 'new-' + Date.now(),
            isNew: true,
            item_title: mergedTitle,
            item_type: 'simple',
            amount: mergedAmount,
            qty: 1, // Usually we reset qty to 1 and combine the total amount, or sum qty. Let's do 1 and sum amount.
            currency: invoice.currency
        };

        // Mark old non-new items for deletion
        itemsToMerge.forEach(item => {
            if (!item.isNew) {
                setDeletedItems(prev => [...prev, item.id]);
            }
        });

        const remainingItems = items.filter((_, idx) => !selectedItemsForMerge.includes(idx));
        setItems([...remainingItems, mergedItem]);
        setSelectedItemsForMerge([]);
    };

    const toggleItemForMerge = (index) => {
        if (selectedItemsForMerge.includes(index)) {
            setSelectedItemsForMerge(selectedItemsForMerge.filter(i => i !== index));
        } else {
            setSelectedItemsForMerge([...selectedItemsForMerge, index]);
        }
    };

    const handleSave = () => {
        setIsSaving(true);
        
        // Format for backend
        const payload = {
            discount: parseFloat(discount),
            deleted_items: deletedItems,
            deleted_cost_lines: deletedCostLines,
            cost_lines: costLines.map(line => ({
                id: line.isNew ? null : line.id,
                line_type: line.line_type,
                amount: line.amount,
                description: line.description,
                credit_user_id: line.credit_user_id
            })),
            items: items.map(item => ({
                id: item.isNew ? null : item.id,
                item_title: item.item_title,
                amount: item.amount,
                qty: item.qty,
                item_type: item.item_type
            }))
        };

        router.put(route('admin.invoices.update', invoice.id), payload, {
            preserveScroll: true,
            onSuccess: () => {
                setIsEditing(false);
                setIsSaving(false);
            },
            onError: () => {
                setIsSaving(false);
            }
        });
    };

    const handleCancel = () => {
        // Reset local state to prop data
        if (invoice && invoice.items) {
            setItems(invoice.items.map(item => ({ ...item, isNew: false })));
        }
        setDiscount(invoice.discount || 0);
        setDeletedItems([]);
        setIsEditing(false);
    };

    const getStatusBadge = (status) => {
        switch (status) {
            case 'paid': return <span className="inline-flex items-center rounded-full bg-green-500 px-3 py-1 text-sm font-medium text-white">Paid</span>;
            case 'partially_paid': return <span className="inline-flex items-center rounded-full bg-yellow-400 px-3 py-1 text-sm font-medium text-black">Partially Paid</span>;
            case 'cancelled': return <span className="inline-flex items-center rounded-full bg-gray-500 px-3 py-1 text-sm font-medium text-white">Cancelled</span>;
            case 'unpaid':
            default: return <span className="inline-flex items-center rounded-full bg-red-500 px-3 py-1 text-sm font-medium text-white">Unpaid</span>;
        }
    };

    const getJobStatusBadge = (status) => {
        switch (status) {
            case 'done': return <span className="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">Done</span>;
            case 'processing': return <span className="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-sm font-medium text-yellow-800">Processing</span>;
            default: return <span className="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-800">Pending</span>;
        }
    };

    // Derived local totals for UI while editing
    const currentSubtotal = items.reduce((acc, item) => acc + (parseFloat(item.amount) * parseInt(item.qty || 1) || 0), 0);
    const currentTotal = currentSubtotal - parseFloat(discount || 0);

    return (
        <AdminSidebarLayout title={`Invoice #${invoice.invoice_number}`} header="Invoice Management">
            {/* Header Section */}
            <div className="mb-6 flex flex-col md:flex-row md:items-start justify-between gap-4 border-b pb-4">
                <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-3 flex-wrap mb-2">
                        <h2 className="text-2xl font-bold text-gray-900 tracking-tight">
                            #{invoice.invoice_number}
                        </h2>
                        {getStatusBadge(invoice.status)}
                        {getJobStatusBadge(invoice.job_status)}
                    </div>
                    <div className="flex items-center gap-4 text-sm text-gray-500 mt-2">
                        <span className="flex items-center gap-1">
                            <Clock className="w-4 h-4 opacity-70" />
                            {new Date(invoice.created_at).toLocaleDateString()}
                        </span>
                        <span className="flex items-center gap-1">
                            <span className="font-bold opacity-70">$</span>
                            {invoice.currency}
                        </span>
                    </div>
                </div>
                <div className="flex flex-wrap items-center gap-2 md:justify-end">
                    <div className="flex bg-gray-100 rounded-md p-1">
                        <Button variant="ghost" size="sm" className="h-8 hover:bg-white">
                            <Printer className="w-4 h-4" />
                        </Button>
                        <Button variant="ghost" size="sm" className="h-8 hover:bg-white">
                            <Download className="w-4 h-4" />
                        </Button>
                    </div>
                    <Button className="bg-blue-600 hover:bg-blue-700 h-10 px-4">
                        <Share2 className="w-4 h-4 mr-2" /> Share
                    </Button>
                </div>
            </div>

            {/* Info Cards Row */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                {/* Client Card */}
                <Card className="shadow-sm border-gray-200">
                    <CardHeader className="pb-3 border-b border-gray-100 bg-gray-50/50">
                        <CardTitle className="text-base flex items-center text-gray-700">
                            <User className="w-4 h-4 mr-2 text-gray-400" /> Client Profile
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-4">
                        <div className="flex items-center gap-3 mb-4">
                            <div className="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                <User className="w-5 h-5" />
                            </div>
                            <div>
                                {invoice.user?.id ? (
                                    <Link href={route('admin.users.show', invoice.user.id)} className="font-bold text-gray-900 hover:underline">
                                        {invoice.user.name || 'Unknown Client'}
                                    </Link>
                                ) : (
                                    <span className="font-bold text-gray-900">
                                        {invoice.user?.name || 'Unknown Client'}
                                    </span>
                                )}
                                <div className="text-sm text-gray-500">{invoice.user?.email}</div>
                            </div>
                        </div>
                        <div className="space-y-2 text-sm text-gray-600">
                            {invoice.user?.address && (
                                <div className="flex items-start gap-2">
                                    <MapPin className="w-4 h-4 opacity-50 mt-0.5" />
                                    <span>{invoice.user.address}</span>
                                </div>
                            )}
                            {invoice.user?.phone_number && (
                                <div className="flex items-center gap-2">
                                    <Phone className="w-4 h-4 opacity-50" />
                                    <span className="font-medium text-gray-900">{invoice.user.phone_number}</span>
                                </div>
                            )}
                            {invoice.project && (
                                <div className="inline-flex items-center rounded bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700 mt-2">
                                    <Folder className="w-3 h-3 mr-1" /> {invoice.project.project_name}
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Summary Card */}
                <Card className="shadow-sm border-gray-200">
                    <CardHeader className="pb-3 border-b border-gray-100 bg-gray-50/50">
                        <CardTitle className="text-base flex items-center text-gray-700">
                            <Receipt className="w-4 h-4 mr-2 text-gray-400" /> Invoice Summary
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-4">
                        <div className="bg-gray-50 rounded-lg p-4 mb-4">
                            <div className="flex justify-between items-baseline">
                                <span className="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Payable</span>
                                <span className="text-2xl font-black text-gray-900">{formatCurrency(invoice.amount, invoice.currency)}</span>
                            </div>
                            {invoice.status === 'partially_paid' && (
                                <div className="flex justify-between items-center mt-3 pt-3 border-t border-gray-200">
                                    <span className="text-xs font-bold text-red-600 flex items-center"><Clock className="w-3 h-3 mr-1" /> Remaining Due</span>
                                    <span className="font-bold text-red-600">{formatCurrency(invoice.amount - invoice.paid_amount, invoice.currency)}</span>
                                </div>
                            )}
                        </div>
                        
                        <div className="space-y-2 text-sm">
                            {(invoice.tax > 0 || invoice.discount > 0) && (
                                <div className="flex justify-between items-center py-1">
                                    <span className="text-gray-500">Subtotal</span>
                                    <span className="font-medium">{formatCurrency(invoice.sub_total, invoice.currency)}</span>
                                </div>
                            )}
                            {invoice.discount > 0 && (
                                <div className="flex justify-between items-center py-1">
                                    <span className="text-gray-500">Total Discount</span>
                                    <span className="font-medium text-orange-600">-{formatCurrency(invoice.discount, invoice.currency)}</span>
                                </div>
                            )}
                            {invoice.tax > 0 && (
                                <div className="flex justify-between items-center py-1">
                                    <span className="text-gray-500">Tax Value</span>
                                    <span className="font-medium">{formatCurrency(invoice.tax, invoice.currency)}</span>
                                </div>
                            )}
                            {invoice.cost > 0 && (
                                <div className="flex justify-between items-center py-1">
                                    <span className="text-gray-500">Net Revenue</span>
                                    <span className="font-medium text-emerald-600">{formatCurrency(invoice.revenue, invoice.currency)}</span>
                                </div>
                            )}
                        </div>
                        
                        {invoice.status !== 'paid' && (
                            <div className="mt-4 pt-4 border-t border-gray-100 flex justify-between items-center">
                                <span className="text-xs font-bold text-gray-500 uppercase tracking-wider">Delivery Status</span>
                                <Button variant="outline" size="sm" className="h-7 text-xs border-blue-200 text-blue-700 bg-blue-50 hover:bg-blue-100">
                                    Update Status
                                </Button>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Timer Card */}
                <Card className="shadow-sm border-gray-200">
                    <CardHeader className="pb-3 border-b border-gray-100 bg-gray-50/50">
                        <CardTitle className="text-base flex items-center text-gray-700">
                            <Clock className="w-4 h-4 mr-2 text-gray-400" /> Time Tracking
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-4 flex flex-col items-center justify-center h-full min-h-[140px]">
                        <div className="text-3xl font-black text-gray-900 mb-1 flex items-center">
                            <Clock className="w-6 h-6 text-blue-500 opacity-20 mr-2" />
                            {invoice.total_timer_str || '00:00:00'}
                        </div>
                        <div className="text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Total Billable Hours</div>
                        
                        <Button variant="link" className="mt-4 text-blue-600 font-bold hover:text-blue-800">
                            <Plus className="w-3 h-3 mr-1" /> Add Manual Entry
                        </Button>
                    </CardContent>
                </Card>

                {/* Affiliate Card */}
                {invoice.affiliate_data && (
                    <Card className="shadow-sm border-gray-200">
                        <CardHeader className="pb-3 border-b border-gray-100 bg-gray-50/50">
                            <CardTitle className="text-base flex items-center text-gray-700">
                                <Network className="w-4 h-4 mr-2 text-gray-400" /> Affiliate
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="pt-4">
                            <div className="flex items-center gap-3 mb-4">
                                <div className="h-10 w-10 rounded-full bg-orange-100 flex items-center justify-center text-orange-600">
                                    <Network className="w-5 h-5" />
                                </div>
                                <div>
                                    <Link href={route('admin.users.show', invoice.affiliate_data.affiliate_id)} className="font-bold text-gray-900 hover:underline">
                                        {invoice.affiliate_data.name}
                                    </Link>
                                    <div className="text-sm text-gray-500">{invoice.affiliate_data.email}</div>
                                </div>
                            </div>
                            
                            <div className="bg-gray-50 rounded p-3 mb-2 space-y-2">
                                <div className="flex justify-between items-center">
                                    <span className="text-xs font-bold text-gray-500">Commission Rate</span>
                                    <span className="font-bold text-blue-600">{invoice.affiliate_data.commission_percent}%</span>
                                </div>
                                
                                {invoice.affiliate_data.is_paid && (
                                    <div className="flex justify-between items-center">
                                        <span className="text-xs font-bold text-gray-500">Commission Earned</span>
                                        <span className="font-bold text-green-600">{invoice.affiliate_data.actual_earned_str}</span>
                                    </div>
                                )}
                                
                                <div className="flex justify-between items-center">
                                    <span className="text-xs font-bold text-gray-500">
                                        {invoice.affiliate_data.is_paid ? 'Commission Estimated' : 'Commission Amount'}
                                    </span>
                                    <span className={`font-bold ${invoice.affiliate_data.is_paid ? 'text-gray-400' : 'text-gray-900'}`}>
                                        {invoice.affiliate_data.estimated_amount_str}
                                    </span>
                                </div>
                            </div>

                            {invoice.affiliate_data.adds_to_total && (
                                <div className="inline-flex items-center rounded bg-yellow-50 px-2 py-1 text-xs font-semibold text-yellow-800 border border-yellow-200">
                                    <AlertCircle className="w-3 h-3 mr-1" /> Added to invoice total
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}
            </div>

            {/* Toolbar (Unpaid only) */}
            {isUnpaid && !isEditing && (
                <div className="bg-white border rounded-lg p-4 mb-6 flex flex-col md:flex-row items-center justify-between gap-4 shadow-sm">
                    <div className="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                        <span className="text-xs font-bold text-gray-500 uppercase tracking-wider mr-2">Quick Build:</span>
                        <div className="flex gap-2 w-full sm:w-auto">
                            <Button onClick={handleAddQtyItem} variant="outline" size="sm" className="flex-1 sm:flex-none border-dashed hover:border-blue-300 hover:text-blue-600 hover:bg-blue-50">
                                <Layers className="w-4 h-4 mr-2 text-blue-500" /> Qty Item
                            </Button>
                            <Button onClick={handleAddSimpleItem} variant="outline" size="sm" className="flex-1 sm:flex-none border-dashed hover:border-blue-300 hover:text-blue-600 hover:bg-blue-50">
                                <Plus className="w-4 h-4 mr-2 text-blue-500" /> Simple Item
                            </Button>
                            <Button variant="secondary" size="sm" className="hidden sm:flex bg-gray-100 text-gray-700 hover:bg-gray-200">
                                <Clock className="w-4 h-4 mr-2" /> Log Time
                            </Button>
                        </div>
                    </div>
                    <Button variant="outline" size="sm" className="w-full md:w-auto border-dashed border-green-300 text-green-700 hover:bg-green-50">
                        <CreditCard className="w-4 h-4 mr-2" /> External Payment
                    </Button>
                </div>
            )}

            {/* Items Table */}
            <Card className="shadow-sm border-gray-200 mb-8 overflow-hidden">
                <div className="bg-gray-50 border-b px-4 py-3 flex items-center justify-between">
                    <div className="flex items-center text-gray-700 font-semibold">
                        <List className="w-5 h-5 text-gray-400 mr-2" /> Invoice Items
                    </div>
                    {isUnpaid && !isEditing && (
                        <Button variant="outline" size="sm" onClick={() => setIsEditing(true)}>
                            <Edit2 className="w-4 h-4 mr-2" /> Edit Rows
                        </Button>
                    )}
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm text-left">
                        <thead className="bg-gray-50/50 text-gray-500 border-b">
                            <tr>
                                {isEditing && <th className="px-4 py-3 font-medium w-12 text-center">Merge</th>}
                                <th className="px-4 py-3 font-medium w-12 text-center">#</th>
                                <th className="px-4 py-3 font-medium w-24">Type</th>
                                <th className="px-4 py-3 font-medium">Description</th>
                                <th className="px-4 py-3 font-medium text-right w-32">Price</th>
                                <th className="px-4 py-3 font-medium text-center w-24">Quantity</th>
                                <th className="px-4 py-3 font-medium text-right w-32">Total</th>
                                {isEditing && <th className="px-4 py-3 font-medium text-center w-16"></th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {items.map((item, index) => (
                                <tr key={item.id || index} className="hover:bg-gray-50/50 transition-colors">
                                    {isEditing && (
                                        <td className="px-4 py-3 text-center">
                                            <input 
                                                type="checkbox" 
                                                checked={selectedItemsForMerge.includes(index)}
                                                onChange={() => toggleItemForMerge(index)}
                                                className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                            />
                                        </td>
                                    )}
                                    <td className="px-4 py-3 text-center text-gray-400 text-xs">
                                        {index + 1}
                                    </td>
                                    <td className="px-4 py-3">
                                        {item.item_type === 'timer' && item.id ? (
                                            <a href={route('admin.invoices.timer_details', { item_id: item.id })} className="hover:opacity-80 transition-opacity">
                                                <span className="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-yellow-100 text-yellow-800">
                                                    <Clock className="w-3 h-3 mr-1" />
                                                    <span className="capitalize">{item.item_type}</span>
                                                </span>
                                            </a>
                                        ) : (
                                            <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ${
                                                item.item_type === 'timer' ? 'bg-yellow-100 text-yellow-800' :
                                                item.item_type === 'quantity' ? 'bg-blue-100 text-blue-800' :
                                                'bg-gray-100 text-gray-800'
                                            }`}>
                                                {item.item_type === 'timer' && <Clock className="w-3 h-3 mr-1" />}
                                                <span className="capitalize">{item.item_type}</span>
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 font-medium text-gray-900">
                                        {isEditing ? (
                                            <Input 
                                                value={item.item_title} 
                                                onChange={(e) => handleItemChange(index, 'item_title', e.target.value)}
                                                className="h-8 shadow-none"
                                                placeholder="Item Name"
                                            />
                                        ) : (
                                            item.item_title
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        {(isEditing && item.item_type !== 'timer') ? (
                                            <div className="flex items-center justify-end">
                                                <span className="text-gray-400 mr-1 text-xs">{item.currency}</span>
                                                <Input 
                                                    type="number"
                                                    value={item.amount} 
                                                    onChange={(e) => handleItemChange(index, 'amount', e.target.value)}
                                                    className="h-8 w-24 text-right shadow-none"
                                                />
                                            </div>
                                        ) : (
                                            <span className="text-gray-900">{formatCurrency(item.amount, item.currency)}</span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-center">
                                        {(isEditing && item.item_type === 'quantity') ? (
                                            <Input 
                                                type="number"
                                                value={item.qty} 
                                                onChange={(e) => handleItemChange(index, 'qty', e.target.value)}
                                                className="h-8 w-16 text-center mx-auto shadow-none"
                                                min="1"
                                            />
                                        ) : (
                                            <span className="inline-block bg-white border rounded px-2 py-0.5 text-xs font-medium text-gray-700">
                                                {item.qty}
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-right font-bold text-gray-900">
                                        {formatCurrency((parseFloat(item.amount) || 0) * (parseInt(item.qty) || 1), item.currency)}
                                    </td>
                                    {isEditing && (
                                        <td className="px-4 py-3 text-center">
                                            <Button type="button" variant="ghost" size="sm" onClick={() => handleDeleteItem(index)} className="h-8 w-8 p-0 text-red-500 hover:text-red-700 hover:bg-red-50">
                                                <Trash2 className="w-4 h-4" />
                                            </Button>
                                        </td>
                                    )}
                                </tr>
                            ))}
                            {items.length === 0 && !isEditing && (
                                <tr>
                                    <td colSpan="6" className="px-4 py-8 text-center text-gray-500">
                                        No items added to this invoice yet.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                        {isEditing && (
                            <tfoot className="bg-gray-50 border-t">
                                <tr>
                                    <td colSpan="7" className="px-4 py-3">
                                        <div className="flex justify-between items-center">
                                            <div className="text-sm text-gray-500">
                                                Draft Total: <span className="font-bold text-gray-900">{formatCurrency(currentTotal, invoice.currency)}</span>
                                            </div>
                                            <div className="flex gap-2">
                                                {selectedItemsForMerge.length > 1 && (
                                                    <Button type="button" onClick={handleMergeSelected} variant="secondary" size="sm" className="bg-yellow-100 text-yellow-800 hover:bg-yellow-200">
                                                        <Merge className="w-4 h-4 mr-1" /> Merge ({selectedItemsForMerge.length})
                                                    </Button>
                                                )}
                                                <Button type="button" onClick={handleCancel} variant="outline" size="sm" disabled={isSaving}>
                                                    <X className="w-4 h-4 mr-1" /> Cancel
                                                </Button>
                                                <Button type="button" onClick={handleSave} size="sm" disabled={isSaving} className="bg-blue-600 hover:bg-blue-700">
                                                    <Check className="w-4 h-4 mr-1" /> Save Changes
                                                </Button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        )}
                    </table>
                </div>
            </Card>

            {/* Adjustments Section */}
            {(isUnpaid || invoice.status === 'partially_paid') && (
                <div className="mb-8">
                    <div className="flex items-center justify-between mb-4">
                        <div className="flex items-center gap-2">
                            <ChartLine className="w-5 h-5 text-gray-400" />
                            <h3 className="text-lg font-bold text-gray-900">Adjustments & Pricing</h3>
                        </div>
                        <Button 
                            variant="ghost" 
                            size="sm" 
                            onClick={() => setShowPricingInsights(!showPricingInsights)}
                            className="text-blue-600 bg-blue-50 hover:bg-blue-100"
                        >
                            <ChartLine className="w-4 h-4 mr-2" />
                            {showPricingInsights ? 'Hide Pricing Insights' : 'Show Pricing Insights'}
                        </Button>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {/* Discount Card */}
                        <Card className="shadow-sm border-gray-200 h-full">
                            <CardContent className="p-5">
                                <div className="flex items-center gap-2 font-bold text-gray-700 mb-2">
                                    <AlertCircle className="w-4 h-4 text-orange-500" /> Apply Discount
                                </div>
                                <p className="text-sm text-gray-500 mb-4">Enter a fixed discount amount to reduce the total payable.</p>
                                
                                <div className="flex items-end gap-3 flex-wrap">
                                    <div className="flex-1 space-y-1 min-w-[150px]">
                                        <Label className="text-xs text-gray-500 uppercase">Amount</Label>
                                        <div className="flex items-center">
                                            <span className="bg-gray-100 border border-r-0 border-gray-300 rounded-l-md px-3 py-2 text-sm text-gray-500 h-10">{invoice.currency}</span>
                                            <Input 
                                                type="number"
                                                className="rounded-l-none h-10 shadow-none focus-visible:ring-blue-500"
                                                value={discount}
                                                onChange={(e) => {
                                                    setDiscount(e.target.value);
                                                    setDiscountPercentage(0);
                                                }}
                                                disabled={!isEditing}
                                            />
                                        </div>
                                    </div>
                                    <div className="flex-1 space-y-1 min-w-[120px]">
                                        <Label className="text-xs text-gray-500 uppercase">Percent</Label>
                                        <div className="flex items-center">
                                            <Input 
                                                type="number"
                                                className="rounded-r-none h-10 shadow-none focus-visible:ring-blue-500"
                                                value={discountPercentage}
                                                onChange={(e) => {
                                                    const pct = parseFloat(e.target.value) || 0;
                                                    setDiscountPercentage(pct);
                                                    const base = parseFloat(invoice.sub_total) + parseFloat(invoice.tax || 0);
                                                    setDiscount((base * pct / 100).toFixed(2));
                                                }}
                                                disabled={!isEditing}
                                                placeholder="%"
                                            />
                                            <span className="bg-gray-100 border border-l-0 border-gray-300 rounded-r-md px-3 py-2 text-sm text-gray-500 h-10">%</span>
                                        </div>
                                    </div>
                                    {isEditing && (
                                        <Button type="button" onClick={handleSave} disabled={isSaving} className="h-10 bg-gray-900 text-white hover:bg-gray-800 w-full sm:w-auto">
                                            Update
                                        </Button>
                                    )}
                                </div>
                                <div className="text-xs text-gray-400 mt-2">
                                    Base amount before discount: {formatCurrency(parseFloat(invoice.sub_total) + parseFloat(invoice.tax || 0), invoice.currency)}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Pricing Insights Card */}
                        {showPricingInsights && (
                            <Card className="shadow-sm border-0 bg-gray-900 text-white">
                                <CardContent className="p-5">
                                    <div className="flex items-center gap-2 font-bold text-gray-400 mb-4 uppercase text-xs tracking-wider">
                                        <ChartLine className="w-4 h-4" /> Pricing Health
                                    </div>
                                    <div className="space-y-4">
                                        <div className="flex justify-between items-center">
                                            <span className="text-sm text-gray-400">Target Margin</span>
                                            <span className={`font-bold ${invoice.margin_percentage < 30 ? 'text-yellow-400' : 'text-green-400'}`}>
                                                {invoice.margin_percentage}%
                                            </span>
                                        </div>
                                        <div className="w-full bg-gray-800 rounded-full h-2">
                                            <div 
                                                className={`h-2 rounded-full ${invoice.margin_percentage < 30 ? 'bg-yellow-400' : 'bg-green-400'}`} 
                                                style={{ width: `${Math.min(100, Math.max(0, invoice.margin_percentage))}%` }}
                                            ></div>
                                        </div>
                                        
                                        <div className="pt-4 border-t border-gray-800 space-y-2">
                                            <div className="flex justify-between items-center">
                                                <span className="text-sm text-gray-400">Min Price</span>
                                                <span className="text-sm font-bold">{formatCurrency(invoice.min_price, invoice.currency)}</span>
                                            </div>
                                            <div className="flex justify-between items-center">
                                                <span className="text-sm text-gray-400">Fair Market</span>
                                                <span className="text-sm font-bold">{formatCurrency(invoice.fair_price, invoice.currency)}</span>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                    
                    {/* Cost Lines Card */}
                    <Card className="shadow-sm border-gray-200 mt-6">
                        <CardHeader className="pb-3 border-b border-gray-100 bg-gray-50/50 flex flex-row items-center justify-between">
                            <CardTitle className="text-base flex items-center text-gray-700">
                                <Calculator className="w-4 h-4 mr-2 text-gray-400" /> Internal Cost Lines
                            </CardTitle>
                            {isEditing && (
                                <Button 
                                    variant="outline" 
                                    size="sm" 
                                    onClick={() => {
                                        setCostLines([...costLines, { id: 'new-' + Date.now(), isNew: true, line_type: 'direct', amount: 0, description: '' }]);
                                    }}
                                    className="h-7 text-xs flex items-center"
                                >
                                    <Plus className="w-3 h-3 mr-1" /> Add Cost Line
                                </Button>
                            )}
                        </CardHeader>
                        <CardContent className="pt-4">
                            <p className="text-sm text-gray-500 mb-4">Record any third-party costs or internal credits associated with this invoice to correctly calculate Net Revenue.</p>
                            
                            <div className="space-y-3">
                                {costLines.map((line, index) => (
                                    <div key={line.id || index} className="p-3 bg-gray-50 border border-gray-200 rounded-md">
                                        {line.locked || !isEditing ? (
                                            <div>
                                                <div className="flex justify-between items-center mb-1">
                                                    <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ${
                                                        line.line_type === 'user_credit' ? 'bg-purple-100 text-purple-800' : 'bg-gray-200 text-gray-800'
                                                    }`}>
                                                        {line.line_type === 'user_credit' ? 'User Credit' : 'Direct Cost'}
                                                    </span>
                                                    <span className="font-bold text-gray-900">{formatCurrency(line.amount, invoice.currency)}</span>
                                                </div>
                                                {line.description && <div className="text-sm text-gray-700 mt-1">{line.description}</div>}
                                                {line.credit_user_name && <div className="text-xs text-gray-500 mt-1 flex items-center"><User className="w-3 h-3 mr-1" /> {line.credit_user_name}</div>}
                                            </div>
                                        ) : (
                                            <div className="flex flex-col gap-3">
                                                <div className="flex flex-col sm:flex-row gap-3">
                                                    <div className="w-full sm:w-1/4">
                                                        <Label className="text-xs text-gray-500">Type</Label>
                                                        <select 
                                                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                                            value={line.line_type}
                                                            onChange={(e) => {
                                                                const newLines = [...costLines];
                                                                newLines[index].line_type = e.target.value;
                                                                setCostLines(newLines);
                                                            }}
                                                        >
                                                            <option value="direct">Direct Cost</option>
                                                            <option value="user_credit">User Credit</option>
                                                        </select>
                                                    </div>
                                                    <div className="w-full sm:w-1/4">
                                                        <Label className="text-xs text-gray-500">Amount</Label>
                                                        <Input 
                                                            type="number" 
                                                            className="h-9" 
                                                            value={line.amount}
                                                            onChange={(e) => {
                                                                const newLines = [...costLines];
                                                                newLines[index].amount = e.target.value;
                                                                setCostLines(newLines);
                                                            }}
                                                        />
                                                    </div>
                                                    <div className="w-full sm:w-2/4">
                                                        <Label className="text-xs text-gray-500">Description</Label>
                                                        <div className="flex items-center gap-2">
                                                            <Input 
                                                                type="text" 
                                                                className="h-9 flex-1" 
                                                                value={line.description || ''}
                                                                onChange={(e) => {
                                                                    const newLines = [...costLines];
                                                                    newLines[index].description = e.target.value;
                                                                    setCostLines(newLines);
                                                                }}
                                                                placeholder="Note..."
                                                            />
                                                            <Button 
                                                                type="button" 
                                                                variant="ghost" 
                                                                size="icon" 
                                                                onClick={() => {
                                                                    if (!line.isNew) setDeletedCostLines([...deletedCostLines, line.id]);
                                                                    const newLines = [...costLines];
                                                                    newLines.splice(index, 1);
                                                                    setCostLines(newLines);
                                                                }}
                                                                className="h-9 w-9 text-red-500 hover:text-red-700 hover:bg-red-50"
                                                            >
                                                                <Trash2 className="w-4 h-4" />
                                                            </Button>
                                                        </div>
                                                    </div>
                                                </div>
                                                {line.line_type === 'user_credit' && (
                                                    <div className="flex sm:flex-row gap-3">
                                                        <div className="w-full sm:w-1/2">
                                                            <Label className="text-xs text-gray-500">Credit User ID</Label>
                                                            <Input 
                                                                type="number"
                                                                value={line.credit_user_id || ''}
                                                                onChange={(e) => {
                                                                    const newLines = [...costLines];
                                                                    newLines[index].credit_user_id = e.target.value;
                                                                    setCostLines(newLines);
                                                                }}
                                                                placeholder="Enter User ID..."
                                                                className="h-9"
                                                            />
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                ))}
                                
                                {costLines.length === 0 && !isEditing && (
                                    <div className="text-center py-6 text-sm text-gray-500 border border-dashed rounded-md bg-gray-50">
                                        No internal cost lines recorded.
                                    </div>
                                )}
                                
                                {isEditing && costLines.length > 0 && (
                                    <div className="flex justify-end mt-4">
                                        <Button type="button" onClick={handleSave} disabled={isSaving} className="bg-blue-600 hover:bg-blue-700">
                                            Save Costs
                                        </Button>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            )}
            
        </AdminSidebarLayout>
    );
}

import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import axios from 'axios';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/Components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/Components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/Components/ui/dropdown-menu";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { PremiumCombobox } from '@/Components/ui/PremiumCombobox';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import { 
    Printer, Download, Share2, User, MapPin, Phone, Folder, Receipt, 
    Clock, Layers, Plus, CreditCard, List, Edit2, Check, X, Trash2,
    ChartLine, AlertCircle, Network, Calculator, Merge, ChevronDown
} from 'lucide-react';

export default function Show({ invoice }: { invoice: any }) {
    // Editable state
    const [isEditing, setIsEditing] = useState(false);
    const [isSaving, setIsSaving] = useState(false);
    
    // Local copy of items for inline editing
    const [items, setItems] = useState<any[]>([]);
    const [costLines, setCostLines] = useState<any[]>([]);
    const [discount, setDiscount] = useState<number>(0);
    const [discountPercentage, setDiscountPercentage] = useState<number>(0);
    const [deletedItems, setDeletedItems] = useState<number[]>([]);
    const [deletedCostLines, setDeletedCostLines] = useState<number[]>([]);

    const handleShareLink = async (duration: string) => {
        try {
            const response = await axios.post(route('admin.invoices.share-link', { invoice: String(invoice.id) }), { duration });
            if (response.data?.url) {
                navigator.clipboard.writeText(response.data.url);
                alert(__('admin.link_copied') + '\n' + __('admin.expires_at') + ': ' + response.data.expires_at);
            }
        } catch (error: any) {
            console.error("Failed to generate share link", error);
            alert(__('general.error_occurred'));
        }
    };
    
    // Feature states
    const [selectedItemsForMerge, setSelectedItemsForMerge] = useState<number[]>([]);
    const [showPricingInsights, setShowPricingInsights] = useState(false);
    
    // Pay Service state
    const [showPayServiceModal, setShowPayServiceModal] = useState(false);
    const [payServiceForm, setPayServiceForm] = useState({
        service_amount: '0',
        currency: String(invoice.currency_id || 1), // Default to invoice's currency
        service_pay_source: 'wallet',
        service_pay_dest: 'cib_swype',
        service_revenue: '0'
    });
    const [payServicePreview, setPayServicePreview] = useState<{ cost: number, total: number, total_usd: number, invoice_currency?: string } | null>(null);
    const [isCalculatingPayService, setIsCalculatingPayService] = useState(false);
    const [isSubmittingPayService, setIsSubmittingPayService] = useState(false);

    // Action Modal state
    const [actionModal, setActionModal] = useState<{
        isOpen: boolean;
        type: 'bill_balance' | 'partial_pay' | null;
        amount: string;
    }>({
        isOpen: false,
        type: null,
        amount: ''
    });

    const [jobStatusModal, setJobStatusModal] = useState(false);
    const [jobStatusForm, setJobStatusForm] = useState(invoice.job_status || 'pending');

    const [transferModal, setTransferModal] = useState(false);
    const [transferProjectId, setTransferProjectId] = useState<string | number | null>(invoice.project?.id || null);

    const [rescheduleModal, setRescheduleModal] = useState(false);
    const [rescheduleForm, setRescheduleForm] = useState({
        new_date: invoice.created_at ? new Date(invoice.created_at).toISOString().split('T')[0] : '',
        notify_client: true
    });

    const resetState = () => {
        if (invoice && invoice.items) {
            const itemsSource = invoice.items.data ? invoice.items.data : invoice.items;
            const itemsArray = Array.isArray(itemsSource) ? itemsSource : Object.values(itemsSource);
            setItems(itemsArray.filter(Boolean).map((item: any) => ({ ...item, isNew: false })));
        }
        if (invoice && invoice.cost_lines) {
            const costLinesSource = invoice.cost_lines.data ? invoice.cost_lines.data : invoice.cost_lines;
            const costLinesArray = Array.isArray(costLinesSource) ? costLinesSource : Object.values(costLinesSource);
            setCostLines(costLinesArray.filter(Boolean).map((line: any) => ({ ...line, isNew: false })));
        }
        setDiscount(invoice.discount || 0);
        setDiscountPercentage(0);
        setDeletedItems([]);
        setDeletedCostLines([]);
        setSelectedItemsForMerge([]);
        setIsEditing(false);
    };

    useEffect(() => {
        resetState();
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [invoice]);

    const isUnpaid = invoice.status === 'unpaid';
    
    const handleAddQtyItem = () => {
        setIsEditing(true);
        const newItem = {
            id: 'new-' + crypto.randomUUID(),
            isNew: true,
            item_title: '',
            item_type: 'quantity',
            amount: 0,
            qty: 1,
            currency: invoice.currency
        };
        setItems(prevItems => {
            const validItems = prevItems.filter(i => i && i.item_type);
            return [...validItems, newItem];
        });
    };

    const handleAddSimpleItem = () => {
        setIsEditing(true);
        const newItem = {
            id: 'new-' + crypto.randomUUID(),
            isNew: true,
            item_title: '',
            item_type: 'simple',
            amount: 0,
            qty: 1,
            currency: invoice.currency
        };
        setItems(prevItems => {
            const validItems = prevItems.filter(i => i && i.item_type);
            return [...validItems, newItem];
        });
    };

    const handleAddTimerItem = () => {
        if (isEditing) {
            if (!confirm('You are in edit mode and may have unsaved changes. Do you want to continue and open the Timer page? Unsaved edits will be lost.')) {
                return;
            }
        }
        router.post(route('admin.invoices.create-timer', { invoice: String(invoice.id) }));
    };

    const handleDeleteItem = (index: number) => {
        const item = items[index];
        if (!item.isNew) {
            setDeletedItems([...deletedItems, item.id]);
        }
        setItems(prevItems => {
            const newItems = [...prevItems];
            newItems.splice(index, 1);
            return newItems;
        });
    };

    const handleItemChange = (index: number, field: string, value: any) => {
        setItems(prevItems => {
            const newItems = [...prevItems];
            newItems[index] = { ...newItems[index], [field]: value };
            return newItems;
        });
    };

    const handleMergeSelected = () => {
        if (selectedItemsForMerge.length < 2) return;
        
        const itemsToMerge = items.filter((_, idx) => selectedItemsForMerge.includes(idx));
        const hasTimer = itemsToMerge.some(i => i.item_type === 'timer');
        const mergedTitle = itemsToMerge.map(i => i.item_title).join(' + ');
        
        let mergedAmount = 0;
        let mergedType = 'simple';
        
        if (hasTimer) {
            mergedType = 'timer';
            // Sum total_amount for timer items and amount * qty for others
            mergedAmount = itemsToMerge.reduce((acc, i) => {
                const itemTotal = i.item_type === 'timer' ? (parseFloat(i.total_amount) || 0) : (parseFloat(i.amount || 0) * Number(i.qty || 1));
                return acc + itemTotal;
            }, 0);
        } else {
            mergedAmount = itemsToMerge.reduce((acc, i) => acc + parseFloat(i.amount || 0), 0);
        }
        
        const mergedItem = {
            id: 'new-' + Date.now(),
            isNew: true,
            item_title: mergedTitle,
            item_type: mergedType,
            amount: mergedAmount,
            total_amount: mergedAmount,
            qty: 1,
            currency: invoice.currency,
            merged_from: itemsToMerge.map(i => i.id).filter(id => !String(id).startsWith('new-'))
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

    useEffect(() => {
        if (!showPayServiceModal) return;
        
        const timeoutId = setTimeout(() => {
            setIsCalculatingPayService(true);
            window.axios.post(route('admin.invoices.pay-service.calculate', { invoice: String(invoice.id) }), payServiceForm)
                .then(res => {
                    setPayServicePreview(res.data);
                })
                .catch(err => {
                    console.error('Failed to calculate pay service', err);
                })
                .finally(() => {
                    setIsCalculatingPayService(false);
                });
        }, 500);
        
        return () => clearTimeout(timeoutId);
    }, [payServiceForm, showPayServiceModal, invoice.id]);

    const handlePayServiceSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmittingPayService(true);
        router.post(route('admin.invoices.pay-service.store', { invoice: String(invoice.id) }), payServiceForm as any, {
            preserveScroll: true,
            onSuccess: () => {
                setShowPayServiceModal(false);
                setPayServiceForm({
                    service_amount: '0',
                    currency: String(invoice.currency_id),
                    service_pay_source: 'wallet',
                    service_pay_dest: 'cib_swype',
                    service_revenue: '0'
                });
            },
            onFinish: () => setIsSubmittingPayService(false)
        });
    };

    const toggleItemForMerge = (index: number) => {
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
            discount: parseFloat(String(discount)),
            deleted_items: deletedItems,
            deleted_cost_lines: deletedCostLines,
            cost_lines: costLines.map(line => ({
                id: line.isNew ? null : line.id,
                line_type: line.line_type,
                amount: String(line.amount),
                description: line.description,
                credit_user_id: line.credit_user_id
            })),
            items: items.map(item => ({
                id: item.isNew ? null : item.id,
                item_title: item.item_title,
                amount: String(item.amount),
                qty: item.qty,
                item_type: item.item_type,
                merged_from: item.merged_from || []
            }))
        };

        router.put(route('admin.invoices.update', { invoice: String(invoice.id) }), payload, {
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
        resetState();
    };

    const handleRescheduleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(route('admin.invoices.reschedule', { invoice: String(invoice.id) }), rescheduleForm as any, {
            preserveScroll: true,
            onSuccess: () => {
                setRescheduleModal(false);
            }
        });
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'paid': return <span className="inline-flex items-center rounded-full bg-green-500 px-3 py-1 text-sm font-medium text-white">Paid</span>;
            case 'partially_paid': return <span className="inline-flex items-center rounded-full bg-yellow-400 px-3 py-1 text-sm font-medium text-black">{__('general.partially_paid')}</span>;
            case 'cancelled': return <span className="inline-flex items-center rounded-full bg-gray-500 px-3 py-1 text-sm font-medium text-white">Cancelled</span>;
            case 'unpaid':
            default: return <span className="inline-flex items-center rounded-full bg-red-500 px-3 py-1 text-sm font-medium text-white">Unpaid</span>;
        }
    };

    const getJobStatusBadge = (status: string) => {
        switch (status) {
            case 'done': return <span className="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">Done</span>;
            case 'processing': return <span className="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-sm font-medium text-yellow-800">Processing</span>;
            default: return <span className="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-800">Pending</span>;
        }
    };

    // Derived local totals for UI while editing
    const currentSubtotal = items.reduce((acc, item) => {
        if (item.item_type === 'timer') return acc + (parseFloat(item.total_amount) || 0);
        return acc + (parseFloat(item.amount) * Number(item.qty || 1) || 0);
    }, 0);
    const currentTotal = currentSubtotal + parseFloat(String(invoice.tax || 0)) - parseFloat(String(discount || 0));

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
                            <span className="font-bold opacity-70">{invoice.currency_symbol || '$'}</span>
                            {invoice.currency}
                        </span>
                    </div>
                </div>
                <div className="flex flex-wrap items-center gap-2 md:justify-end">
                    <div className="flex bg-gray-100 rounded-md p-1">
                        <Button variant="ghost" size="sm" className="h-8 hover:bg-white" onClick={() => window.open(route('admin.invoices.print-pdf', { invoice: String(invoice.id) }), '_blank')}>
                            <Printer className="w-4 h-4" />
                        </Button>
                        <Button variant="ghost" size="sm" className="h-8 hover:bg-white" onClick={() => window.location.href = route('admin.invoices.download-pdf', { invoice: String(invoice.id) })}>
                            <Download className="w-4 h-4" />
                        </Button>
                    </div>
                    {(isUnpaid || invoice.status === 'partially_paid') && (
                        <Button 
                            variant="outline" 
                            className="h-10 px-4 bg-white"
                            onClick={() => setRescheduleModal(true)}
                        >
                            <Clock className="w-4 h-4 mr-2" /> {__('admin.reschedule_invoice')}
                        </Button>
                    )}
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button className="bg-blue-600 hover:bg-blue-700 h-10 px-4">
                                <Share2 className="w-4 h-4 mr-2" /> {__('admin.share')} <ChevronDown className="w-4 h-4 ml-2" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem onClick={() => handleShareLink('24_hours')}>
                                {__('admin.share_24_hours')}
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => handleShareLink('3_days')}>
                                {__('admin.share_3_days')}
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => handleShareLink('1_month')}>
                                {__('admin.share_1_month')}
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>

                </div>
            </div>

            {/* Info Cards Row */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                {/* Client Card */}
                <Card className="shadow-sm border-gray-200">
                    <CardHeader className="pb-3 border-b border-gray-100 bg-gray-50/50">
                        <CardTitle className="text-base flex items-center text-gray-700">
                            <User className="w-4 h-4 mr-2 text-gray-400" />{__('general.client_profile')}</CardTitle>
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
                            <Receipt className="w-4 h-4 mr-2 text-gray-400" />{__('general.invoice_summary')}</CardTitle>
                    </CardHeader>
                    <CardContent className="pt-4">
                        <div className="bg-gray-50 rounded-lg p-4 mb-4">
                            <div className="flex justify-between items-baseline">
                                <span className="text-xs font-bold text-gray-500 uppercase tracking-wider">{__('general.total_payable')}</span>
                                <span className="text-2xl font-black text-gray-900">{formatCurrency(currentTotal, invoice.currency)}</span>
                            </div>
                            {invoice.status === 'partially_paid' && (
                                <div className="flex justify-between items-center mt-3 pt-3 border-t border-gray-200">
                                    <span className="text-xs font-bold text-red-600 flex items-center"><Clock className="w-3 h-3 mr-1" />{__('general.remaining_due')}</span>
                                    <span className="font-bold text-red-600">{formatCurrency(currentTotal - invoice.paid_amount, invoice.currency)}</span>
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
                                    <span className="text-gray-500">{__('general.total_discount')}</span>
                                    <span className="font-medium text-orange-600">-{formatCurrency(invoice.discount, invoice.currency)}</span>
                                </div>
                            )}
                            {invoice.tax > 0 && (
                                <div className="flex justify-between items-center py-1">
                                    <span className="text-gray-500">{__('general.tax_value')}</span>
                                    <span className="font-medium">{formatCurrency(invoice.tax, invoice.currency)}</span>
                                </div>
                            )}
                            {invoice.cost > 0 && (
                                <div className="flex justify-between items-center py-1">
                                    <span className="text-gray-500">{__('general.net_revenue')}</span>
                                    <span className="font-medium text-emerald-600">{formatCurrency(invoice.revenue, invoice.currency)}</span>
                                </div>
                            )}
                        </div>
                        
                        {invoice.status !== 'paid' && (
                            <div className="mt-4 pt-4 border-t border-gray-100 flex justify-between items-center">
                                <span className="text-xs font-bold text-gray-500 uppercase tracking-wider">{__('general.delivery_status')}</span>
                                <Button onClick={() => { setJobStatusForm(invoice.job_status || 'pending'); setJobStatusModal(true); }} variant="outline" size="sm" className="h-7 text-xs border-blue-200 text-blue-700 bg-blue-50 hover:bg-blue-100">{__('general.update_status')}</Button>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Timer Card */}
                <Card className="shadow-sm border-gray-200">
                    <CardHeader className="pb-3 border-b border-gray-100 bg-gray-50/50">
                        <CardTitle className="text-base flex items-center text-gray-700">
                            <Clock className="w-4 h-4 mr-2 text-gray-400" />{__('general.time_tracking')}</CardTitle>
                    </CardHeader>
                    <CardContent className="pt-4 flex flex-col items-center justify-center h-full min-h-[140px]">
                        <div className="text-3xl font-black text-gray-900 mb-1 flex items-center">
                            <Clock className="w-6 h-6 text-blue-500 opacity-20 mr-2" />
                            {invoice.total_timer_str || '00:00:00'}
                        </div>
                        <div className="text-xs font-bold text-gray-500 uppercase tracking-wider text-center">{__('general.total_billable_hours')}</div>
                        
                        {isUnpaid && (
                            <Button onClick={handleAddTimerItem} variant="link" className="mt-4 text-blue-600 font-bold hover:text-blue-800">
                                <Plus className="w-3 h-3 mr-1" />{__('general.add_manual_entry')}
                            </Button>
                        )}
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
                                    <span className="text-xs font-bold text-gray-500">{__('general.commission_rate')}</span>
                                    <span className="font-bold text-blue-600">{invoice.affiliate_data.commission_percent}%</span>
                                </div>
                                
                                {invoice.affiliate_data.is_paid && (
                                    <div className="flex justify-between items-center">
                                        <span className="text-xs font-bold text-gray-500">{__('general.commission_earned')}</span>
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
                                    <AlertCircle className="w-3 h-3 mr-1" />{__('general.added_to_invoice_total')}</div>
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
                                <Layers className="w-4 h-4 mr-2 text-blue-500" />{__('general.qty_item')}</Button>
                            <Button onClick={handleAddSimpleItem} variant="outline" size="sm" className="flex-1 sm:flex-none border-dashed hover:border-blue-300 hover:text-blue-600 hover:bg-blue-50">
                                <Plus className="w-4 h-4 mr-2 text-blue-500" />{__('general.simple_item')}</Button>
                            <Button onClick={() => setShowPayServiceModal(true)} variant="outline" size="sm" className="hidden sm:flex border-dashed hover:border-green-300 hover:text-green-600 hover:bg-green-50">
                                <CreditCard className="w-4 h-4 mr-2 text-green-500" />{__('general.pay_service')}</Button>
                            <Button onClick={handleAddTimerItem} variant="secondary" size="sm" className="hidden sm:flex bg-gray-100 text-gray-700 hover:bg-gray-200">
                                <Clock className="w-4 h-4 mr-2" />{__('general.log_time')}</Button>
                        </div>
                    </div>
                    <Button onClick={() => { if(confirm('Mark invoice as paid?')) router.post(route('admin.invoices.mark-paid', { invoice: String(invoice.id) })); }} variant="outline" size="sm" className="w-full md:w-auto border-dashed border-green-300 text-green-700 hover:bg-green-50">
                        <CreditCard className="w-4 h-4 mr-2" />{__('general.mark_as_paid')}</Button>
                </div>
            )}

            {/* Items Table */}
            <Card className="shadow-sm border-gray-200 mb-8 overflow-hidden">
                <div className="bg-gray-50 border-b px-4 py-3 flex items-center justify-between">
                    <div className="flex items-center text-gray-700 font-semibold">
                        <List className="w-5 h-5 text-gray-400 mr-2" />{__('general.invoice_items')}</div>
                    {isUnpaid && !isEditing && (
                        <Button variant="outline" size="sm" onClick={() => setIsEditing(true)}>
                            <Edit2 className="w-4 h-4 mr-2" />{__('general.edit_rows')}</Button>
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
                                        {item.item_type === 'timer' ? (
                                            item.isNew ? (
                                                <span className="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-yellow-100 text-yellow-800 cursor-not-allowed opacity-70" title={__('general.save_invoice_first_to_edit_timer_details')}>
                                                    <Clock className="w-3 h-3 mr-1" />
                                                    <span className="capitalize">{item.item_type} (Save First)</span>
                                                </span>
                                            ) : (
                                                <Link href={route('admin.invoices.timer-details', item.id)} className="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-yellow-100 text-yellow-800 hover:bg-yellow-200 transition-colors cursor-pointer" title={__('general.click_to_edit_timer_details')}>
                                                    <Clock className="w-3 h-3 mr-1" />
                                                    <span className="capitalize">{item.item_type}</span>
                                                </Link>
                                            )
                                        ) : (
                                            <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ${
                                                item.item_type === 'quantity' ? 'bg-blue-100 text-blue-800' :
                                                'bg-gray-100 text-gray-800'
                                            }`}>
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
                                                placeholder={__('general.item_name')}
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
                                            <span className="text-gray-900">{formatCurrency(item.item_type === 'timer' ? (item.total_amount || 0) : item.amount, item.currency)}</span>
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
                                        {formatCurrency(item.item_type === 'timer' ? (item.total_amount || 0) : (parseFloat(item.amount) || 0) * (Number(item.qty) || 1), item.currency)}
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
                                    <td colSpan={6} className="px-4 py-8 text-center text-gray-500">{__('general.no_items_added_to_this_invoice_yet')}</td>
                                </tr>
                            )}
                        </tbody>
                        {isEditing && (
                            <tfoot className="bg-gray-50 border-t">
                                <tr>
                                    <td colSpan={7} className="px-4 py-3">
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
                                                    <Check className="w-4 h-4 mr-1" />{__('general.save_changes')}</Button>
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
                            <h3 className="text-lg font-bold text-gray-900">{__('general.adjustments_pricing')}</h3>
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
                                    <AlertCircle className="w-4 h-4 text-orange-500" />{__('general.apply_discount')}</div>
                                <p className="text-sm text-gray-500 mb-4">{__('general.enter_a_fixed_discount_amount_to_reduce_the_total_payable')}</p>
                                
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
                                                    setDiscount(Number(e.target.value));
                                                    setDiscountPercentage(0);
                                                }}
                                                disabled={!isUnpaid}
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
                                                    const base = parseFloat(invoice.sub_total) + parseFloat(String(invoice.tax || 0));
                                                    setDiscount(Number((base * pct / 100).toFixed(2)));
                                                }}
                                                disabled={!isUnpaid}
                                                placeholder="%"
                                            />
                                            <span className="bg-gray-100 border border-l-0 border-gray-300 rounded-r-md px-3 py-2 text-sm text-gray-500 h-10">%</span>
                                        </div>
                                    </div>
                                    {isUnpaid && (
                                        <Button type="button" onClick={handleSave} disabled={isSaving} className="h-10 bg-gray-900 text-white hover:bg-gray-800 w-full sm:w-auto">
                                            Update
                                        </Button>
                                    )}
                                </div>
                                <div className="text-xs text-gray-400 mt-2">
                                    Base amount before discount: {formatCurrency(parseFloat(invoice.sub_total) + parseFloat(String(invoice.tax || 0)), invoice.currency)}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Pricing Insights Card */}
                        {showPricingInsights && (
                            <Card className="shadow-sm border-0 bg-gray-900 text-white">
                                <CardContent className="p-5">
                                    <div className="flex items-center gap-2 font-bold text-gray-400 mb-4 uppercase text-xs tracking-wider">
                                        <ChartLine className="w-4 h-4" />{__('general.pricing_health')}</div>
                                    <div className="space-y-4">
                                        <div className="flex justify-between items-center">
                                            <span className="text-sm text-gray-400">{__('general.target_margin')}</span>
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
                                                <span className="text-sm text-gray-400">{__('general.min_price')}</span>
                                                <span className="text-sm font-bold">{formatCurrency(invoice.min_price, invoice.currency)}</span>
                                            </div>
                                            <div className="flex justify-between items-center">
                                                <span className="text-sm text-gray-400">{__('general.fair_market')}</span>
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
                                <Calculator className="w-4 h-4 mr-2 text-gray-400" />{__('general.internal_cost_lines')}</CardTitle>
                            {isUnpaid && (
                                <div className="flex gap-2">
                                    <Button 
                                        variant="outline" 
                                        size="sm" 
                                        onClick={() => {
                                            setCostLines([...costLines, { id: 'new-' + crypto.randomUUID(), isNew: true, line_type: 'direct', amount: 0, description: '' }]);
                                        }}
                                        className="h-8 text-xs font-semibold uppercase tracking-wider bg-white"
                                    >{__('general.add_direct_cost')}</Button>
                                    <Button 
                                        variant="outline" 
                                        size="sm" 
                                        onClick={() => {
                                            setCostLines([...costLines, { id: 'new-' + crypto.randomUUID(), isNew: true, line_type: 'user_credit', amount: 0, description: '', credit_user_id: '' }]);
                                        }}
                                        className="h-8 text-xs font-semibold uppercase tracking-wider bg-white"
                                    >{__('general.add_team_credit')}</Button>
                                </div>
                            )}
                        </CardHeader>
                        <CardContent className="pt-4">
                            <p className="text-sm text-gray-500 mb-4">{__('general.record_any_third_party_costs_or_internal_credits_associated_with_this_invoice_to_correctly_calculate_net_revenue')}</p>
                            
                            <div className="space-y-3">
                                {costLines.map((line, index) => (
                                    <div key={line.id || index} className="p-3 bg-gray-50 border border-gray-200 rounded-md">
                                        {line.locked || !isUnpaid ? (
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
                                                            <option value="direct">{__('general.direct_cost')}</option>
                                                            <option value="user_credit">{__('general.user_credit')}</option>
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
                                                                placeholder={__('general.note')}
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
                                                            <Label className="text-xs text-gray-500">{__('general.credit_user')}</Label>
                                                            <PremiumCombobox
                                                                value={line.credit_user_id || ''}
                                                                options={line.credit_user_id && line.credit_user_name ? [{ value: line.credit_user_id, label: line.credit_user_name }] : []}
                                                                onChange={(val, opt) => {
                                                                    const newLines = [...costLines];
                                                                    newLines[index].credit_user_id = val as string;
                                                                    if (opt && opt.name) {
                                                                        newLines[index].credit_user_name = opt.name;
                                                                    }
                                                                    setCostLines(newLines);
                                                                }}
                                                                asyncEndpoint={route('admin.resellers.search-users')}
                                                                placeholder={__('general.search_user')}
                                                                searchPlaceholder="Search by name or email..."
                                                                className="h-9"
                                                            />
                                                        </div>
                                                    </div>
                                                )}
                                                {line.line_type === 'direct' && !line.locked && !line.isNew && invoice.status !== 'paid' && invoice.status !== 'cancelled' && (
                                                    <div className="mt-3 flex items-center gap-3">
                                                        <Button 
                                                            type="button"
                                                            onClick={(e) => {
                                                                e.preventDefault();
                                                                router.post(route('invoices.cost-lines.record-paid', { invoice: invoice.id, line: line.id }));
                                                            }}
                                                            className="bg-emerald-500 hover:bg-emerald-600 text-white h-8 px-3 text-xs font-semibold"
                                                        >
                                                            <Receipt className="w-3.5 h-3.5 mr-1.5" />{__('general.record_as_paid')}</Button>
                                                        <span className="text-xs text-gray-500 font-medium">{__('general.creates_a_client_payment_for_this_amount')}</span>
                                                    </div>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                ))}
                                
                                {costLines.length === 0 && (
                                    <div className="text-center py-6 text-sm text-gray-500 border border-dashed rounded-md bg-gray-50">{__('general.no_internal_cost_lines_recorded')}</div>
                                )}
                                
                                {isUnpaid && (costLines.length > 0 || deletedCostLines.length > 0) && (
                                    <div className="mt-4">
                                        <Button type="button" onClick={handleSave} disabled={isSaving} className="w-full h-10 bg-black text-white hover:bg-gray-900 font-bold tracking-wider flex items-center justify-center">
                                            <Calculator className="w-4 h-4 mr-2" />{__('general.save_cost_lines')}</Button>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            )}

            {/* Sticky Action Bar */}
            <div className="bg-white border rounded-lg p-4 mb-6 shadow-sm">
                <div className="flex flex-col md:flex-row items-center justify-between gap-4">
                    {/* Totals */}
                    <div className="flex flex-wrap items-center gap-6 text-center md:text-left">
                        <div className="flex flex-col">
                            <span className="text-xs font-bold text-gray-500 uppercase tracking-wider">{__('general.total_amount')}</span>
                            <span className="text-xl font-black text-gray-900">{formatCurrency(invoice.amount, invoice.currency)}</span>
                        </div>
                        {invoice.status === 'partially_paid' && (
                            <div className="flex flex-col">
                                <span className="text-xs font-bold text-red-600 uppercase tracking-wider">{__('general.remaining_due')}</span>
                                <span className="text-xl font-black text-red-600">{formatCurrency(invoice.amount - invoice.paid_amount, invoice.currency)}</span>
                            </div>
                        )}
                    </div>

                    {/* Action Buttons */}
                    <div className="flex flex-wrap gap-2 items-center justify-center md:justify-end">
                        {invoice.status === 'unpaid' && (
                            <>
                                <Button 
                                    onClick={() => setActionModal({ isOpen: true, type: 'bill_balance', amount: '' })}
                                    className="bg-blue-600 hover:bg-blue-700 text-white"
                                >
                                    <Check className="w-4 h-4 mr-2" />{__('general.bill_from_balance')}</Button>
                                <Button 
                                    onClick={() => setActionModal({ isOpen: true, type: 'partial_pay', amount: String(invoice.amount) })}
                                    variant="outline"
                                >
                                    <CreditCard className="w-4 h-4 mr-2" />{__('general.partial_pay')}</Button>
                            </>
                        )}
                        {invoice.status === 'partially_paid' && (
                            <Button 
                                onClick={() => {
                                    const remaining = invoice.amount - (invoice.paid_amount || 0);
                                    setActionModal({ isOpen: true, type: 'partial_pay', amount: String(remaining) });
                                }}
                                className="bg-blue-600 hover:bg-blue-700 text-white"
                            >
                                <Plus className="w-4 h-4 mr-2" />{__('general.add_payment')}</Button>
                        )}

                        {invoice.status !== 'cancelled' && (
                            <div className="hidden md:block w-px h-8 bg-gray-200 mx-1"></div>
                        )}

                        {invoice.user?.id && invoice.user.projects && invoice.user.projects.length > 0 && (
                            <Button 
                                variant="outline"
                                onClick={() => {
                                    setTransferProjectId(invoice.project?.id || null);
                                    setTransferModal(true);
                                }}
                            >
                                Transfer
                            </Button>
                        )}

                        {invoice.status !== 'cancelled' && !invoice.is_published && (
                            <Link href={route('admin.invoices.notify', { invoice: String(invoice.id) })}>
                                <Button variant="outline" title={__('general.notify_client')}>
                                    <Share2 className="w-4 h-4" />
                                </Button>
                            </Link>
                        )}
                        {invoice.is_published === 1 && (
                            <span className="inline-flex items-center text-green-600" title={__('general.notification_sent')}>
                                <Check className="w-4 h-4" />
                            </span>
                        )}
                    </div>
                </div>
            </div>

            {/* Danger Zone */}
            {(invoice.status === 'paid' || invoice.status === 'partially_paid' || (items.length === 0 && invoice.status === 'unpaid')) && (
                <div className="border border-red-200 bg-red-50 rounded-lg overflow-hidden mb-6">
                    <div className="px-4 py-3 border-b border-red-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                        <div className="flex items-center gap-2">
                            <div className="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                                <AlertCircle className="w-4 h-4 text-red-600" />
                            </div>
                            <span className="font-bold text-red-600 uppercase text-xs tracking-wider">{__('general.danger_zone')}</span>
                        </div>
                        <span className="text-sm text-red-500 font-medium">{__('general.irreversible_actions')}</span>
                    </div>
                    <div className="p-4 flex flex-col sm:flex-row justify-end gap-3 items-stretch sm:items-center">
                        {(invoice.status === 'paid' || invoice.status === 'partially_paid') && (
                            <div className="sm:mr-auto text-sm text-gray-500">{__('general.cancelling_will_void_all_related_transactions')}</div>
                        )}
                        <Button 
                            variant="outline"
                            className="border-red-300 text-red-600 hover:bg-red-100 w-full sm:w-auto justify-center"
                            onClick={() => { if(confirm('Are you sure you want to cancel this invoice? This action is irreversible.')) router.post(route('admin.invoices.cancel', { invoice: String(invoice.id) })); }}
                        >
                            <X className="w-4 h-4 mr-2" />{__('general.cancel_invoice')}</Button>
                        <Button 
                            variant="outline"
                            className="border-red-300 text-red-600 hover:bg-red-100 w-full sm:w-auto justify-center"
                            onClick={() => { if(confirm('Are you sure you want to DELETE this invoice? This cannot be undone.')) router.post(route('admin.invoices.bulk-action'), { action: 'delete', invoices: [invoice.id] }); }}
                        >
                            <Trash2 className="w-4 h-4 mr-2" />{__('general.delete_invoice')}</Button>
                    </div>
                </div>
            )}
            
            {/* Pay Service Modal */}
            <Dialog open={showPayServiceModal} onOpenChange={setShowPayServiceModal}>
                <DialogContent className="sm:max-w-[600px] bg-white">
                    <DialogHeader>
                        <DialogTitle className="flex items-center text-green-700">
                            <CreditCard className="w-5 h-5 mr-2" />
                            {__('general.pay_service')}
                        </DialogTitle>
                        <DialogDescription>
                            {__('general.pay_service_desc', { default: 'Record a service payment for this invoice.' })}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label>{__('general.service_amount')}</Label>
                                <Input 
                                    type="number" 
                                    min="0"
                                    value={payServiceForm.service_amount}
                                    onChange={e => setPayServiceForm({...payServiceForm, service_amount: e.target.value})}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>{__('general.currency')}</Label>
                                <select 
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    value={payServiceForm.currency}
                                    onChange={e => setPayServiceForm({...payServiceForm, currency: e.target.value})}
                                >
                                    {invoice.currencies && Object.keys(invoice.currencies).map(cId => (
                                        <option key={cId} value={cId}>{invoice.currencies[cId]}</option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label>{__('general.service_pay_source')}</Label>
                                <select 
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    value={payServiceForm.service_pay_source}
                                    onChange={e => setPayServiceForm({...payServiceForm, service_pay_source: e.target.value})}
                                >
                                    <option value="wallet">{__('general.wallet')}</option>
                                    <option value="cash">{__('general.cash')}</option>
                                    <option value="cib_swype">{__('general.cib_swype')}</option>
                                    <option value="cib">CIB</option>
                                    <option value="bank_transfer">{__('general.bank_transfer')}</option>
                                    <option value="paypal">{__('general.paypal')}</option>
                                </select>
                            </div>
                            <div className="space-y-2">
                                <Label>{__('general.service_pay_dest')}</Label>
                                <select 
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    value={payServiceForm.service_pay_dest}
                                    onChange={e => setPayServiceForm({...payServiceForm, service_pay_dest: e.target.value})}
                                >
                                    <option value="wallet">{__('general.wallet')}</option>
                                    <option value="cash">{__('general.cash')}</option>
                                    <option value="cib_swype">{__('general.cib_swype')}</option>
                                    <option value="cib">CIB</option>
                                    <option value="bank_transfer">{__('general.bank_transfer')}</option>
                                    <option value="paypal">{__('general.paypal')}</option>
                                </select>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label>{__('general.revenue')}</Label>
                            <Input 
                                type="number" 
                                min="0"
                                value={payServiceForm.service_revenue}
                                onChange={e => setPayServiceForm({...payServiceForm, service_revenue: e.target.value})}
                            />
                        </div>

                        {/* Live Calculation Preview */}
                        <div className="mt-4 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                            <h4 className="text-sm font-semibold text-gray-700 mb-2 flex items-center">
                                <Calculator className="w-4 h-4 mr-2" />
                                {__('general.calculation_preview')}
                            </h4>
                            {isCalculatingPayService ? (
                                <div className="text-sm text-gray-500">{__('general.calculating')}...</div>
                            ) : payServicePreview ? (
                                <div className="space-y-2 text-sm">
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">{__('general.service_cost')}</span>
                                        <span className="font-medium text-red-600">
                                            {formatCurrency(payServicePreview.cost, { code: payServicePreview.invoice_currency })}
                                        </span>
                                    </div>
                                    <div className="flex justify-between font-semibold border-t pt-2 mt-2">
                                        <span className="text-gray-700">{__('general.invoice_total_addition')}</span>
                                        <span className="text-green-600">
                                            {formatCurrency(payServicePreview.total, { code: payServicePreview.invoice_currency })}
                                        </span>
                                    </div>
                                    <div className="flex justify-between text-xs text-gray-400 mt-1">
                                        <span>{__('general.business_currency_total')}</span>
                                        <span>
                                            {formatCurrency(payServicePreview.total_usd, { code: 'USD' })} {/* Assuming USD as base for display purpose here */}
                                        </span>
                                    </div>
                                </div>
                            ) : (
                                <div className="text-sm text-gray-500 italic">{__('general.enter_details_to_preview')}</div>
                            )}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowPayServiceModal(false)}>
                            {__('general.cancel')}
                        </Button>
                        <Button 
                            onClick={handlePayServiceSubmit} 
                            disabled={isSubmittingPayService || isCalculatingPayService}
                            className="bg-green-600 hover:bg-green-700 text-white"
                        >
                            {isSubmittingPayService ? __('general.saving') + '...' : __('general.save_service')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Action Modal */}
            <Dialog open={actionModal.isOpen} onOpenChange={(open) => setActionModal(prev => ({ ...prev, isOpen: open }))}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>
                            {actionModal.type === 'bill_balance' ? __('general.bill_from_balance') :
                             __('general.partial_pay')}
                        </DialogTitle>
                        <DialogDescription>
                            {actionModal.type === 'bill_balance' ? __('general.confirm_bill_balance') :
                             __('general.enter_payment_amount')}
                        </DialogDescription>
                    </DialogHeader>

                    {actionModal.type === 'partial_pay' && (
                        <div className="py-4">
                            <Label htmlFor="payment_amount" className="mb-2 block">{__('general.amount')}</Label>
                            <Input
                                id="payment_amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                value={actionModal.amount}
                                onChange={(e) => setActionModal(prev => ({ ...prev, amount: e.target.value }))}
                            />
                        </div>
                    )}

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setActionModal(prev => ({ ...prev, isOpen: false }))}>
                            {__('general.cancel')}
                        </Button>
                        <Button
                            className="bg-blue-600 hover:bg-blue-700 text-white"
                            onClick={() => {
                                if (actionModal.type === 'bill_balance') {
                                    router.post(route('admin.invoices.mark-paid', { invoice: String(invoice.id) }));
                                } else if (actionModal.type === 'partial_pay') {
                                    const amt = parseFloat(actionModal.amount);
                                    if (amt > 0) {
                                        router.post(route('admin.invoices.partial-pay', { invoice: String(invoice.id) }), { amount: amt });
                                    }
                                }
                                setActionModal(prev => ({ ...prev, isOpen: false }));
                            }}
                        >
                            {__('general.confirm')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Reschedule Modal */}
            <Dialog open={rescheduleModal} onOpenChange={setRescheduleModal}>
                <DialogContent>
                    <form onSubmit={handleRescheduleSubmit}>
                        <DialogHeader>
                            <DialogTitle>{__('admin.reschedule_invoice')}</DialogTitle>
                            <DialogDescription>{__('general.enter_new_date')}</DialogDescription>
                        </DialogHeader>
                        <div className="py-4 space-y-4">
                            <div>
                                <Label htmlFor="new_date" className="mb-2 block">{__('admin.new_invoice_date')}</Label>
                                <Input
                                    id="new_date"
                                    type="date"
                                    required
                                    value={rescheduleForm.new_date}
                                    onChange={(e) => setRescheduleForm(prev => ({ ...prev, new_date: e.target.value }))}
                                />
                            </div>
                            <div className="flex items-center space-x-2">
                                <input 
                                    type="checkbox" 
                                    id="notify_client" 
                                    className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    checked={rescheduleForm.notify_client}
                                    onChange={(e) => setRescheduleForm(prev => ({ ...prev, notify_client: e.target.checked }))}
                                />
                                <Label htmlFor="notify_client" className="font-normal cursor-pointer">
                                    {__('admin.notify_client')}
                                </Label>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setRescheduleModal(false)}>
                                {__('general.cancel')}
                            </Button>
                            <Button type="submit" className="bg-blue-600 hover:bg-blue-700 text-white">
                                {__('general.confirm')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>


            <Dialog open={jobStatusModal} onOpenChange={setJobStatusModal}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{__('general.update_status') || 'Update Job Status'}</DialogTitle>
                        <DialogDescription>{__('general.select_job_status') || 'Select the current job status for this invoice.'}</DialogDescription>
                    </DialogHeader>
                    <div className="py-4">
                        <PremiumCombobox
                            value={jobStatusForm}
                            onChange={(val) => setJobStatusForm(val as string)}
                            options={[
                                { value: 'pending', label: 'Pending' },
                                { value: 'processing', label: 'Processing' },
                                { value: 'done', label: 'Done' }
                            ]}
                            placeholder="Select job status"
                        />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setJobStatusModal(false)}>{__('general.cancel') || 'Cancel'}</Button>
                        <Button onClick={() => {
                            router.post(route('admin.invoices.change-job-status', { invoice: String(invoice.id) }), { job_status: jobStatusForm }, {
                                onSuccess: () => setJobStatusModal(false),
                                preserveScroll: true
                            });
                        }} className="bg-blue-600 hover:bg-blue-700 text-white">{__('general.save_changes') || 'Save Status'}</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={transferModal} onOpenChange={setTransferModal}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Transfer to Project</DialogTitle>
                        <DialogDescription>Select the project to transfer this invoice to.</DialogDescription>
                    </DialogHeader>
                    <div className="py-4">
                        <PremiumCombobox
                            value={transferProjectId || ''}
                            onChange={(val) => setTransferProjectId(val)}
                            options={invoice.user?.projects?.map((p: any) => ({
                                value: p.id,
                                label: p.project_name
                            })) || []}
                            placeholder="Select project"
                        />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setTransferModal(false)}>{__('general.cancel') || 'Cancel'}</Button>
                        <Button onClick={() => {
                            router.post(route('admin.invoices.bulk-action'), { 
                                action: 'change_project', 
                                invoices: [invoice.id], 
                                project_id: transferProjectId 
                            }, {
                                onSuccess: () => setTransferModal(false),
                                preserveScroll: true
                            });
                        }} className="bg-blue-600 hover:bg-blue-700 text-white">Transfer</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminSidebarLayout>
    );
}


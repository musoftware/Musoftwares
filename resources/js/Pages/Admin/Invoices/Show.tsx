import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import axios from 'axios';
import { toast } from 'sonner';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/Components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/Components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/Components/ui/dropdown-menu";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { PremiumCombobox } from '@/Components/ui/PremiumCombobox';
import { formatMoney as formatCurrency, cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import {
    Printer, Download, Share2, User, MapPin, Phone, Folder, Receipt,
    Clock, Layers, Plus, CreditCard, List, Edit2, Check, X, Trash2,
    ChartLine, AlertCircle, Network, Calculator, Merge, ChevronDown,
    Bell, Mail, FolderKanban, ListTodo, StickyNote, Copy, ExternalLink, Link2
} from 'lucide-react';

const NOTE_COLORS: Record<string, { bg: string; border: string; text: string; swatch: string }> = {
    yellow: { bg: 'bg-amber-50', border: 'border-amber-200', text: 'text-amber-950', swatch: 'bg-amber-400' },
    green: { bg: 'bg-emerald-50', border: 'border-emerald-200', text: 'text-emerald-950', swatch: 'bg-emerald-400' },
    blue: { bg: 'bg-sky-50', border: 'border-sky-200', text: 'text-sky-950', swatch: 'bg-sky-400' },
    red: { bg: 'bg-rose-50', border: 'border-rose-200', text: 'text-rose-950', swatch: 'bg-rose-400' },
    purple: { bg: 'bg-violet-50', border: 'border-violet-200', text: 'text-violet-950', swatch: 'bg-violet-400' },
    pink: { bg: 'bg-pink-50', border: 'border-pink-200', text: 'text-pink-950', swatch: 'bg-pink-400' },
    slate: { bg: 'bg-slate-50', border: 'border-slate-200', text: 'text-slate-900', swatch: 'bg-slate-400' },
};

const LANE_META: Record<string, { labelKey: string; bg: string; text: string; border: string }> = {
    backlog: { labelKey: 'general.lane_backlog', bg: 'bg-indigo-50 text-indigo-700', text: 'text-indigo-700', border: 'border-indigo-100' },
    in_progress: { labelKey: 'general.lane_in_progress', bg: 'bg-amber-50 text-amber-700', text: 'text-amber-700', border: 'border-amber-100' },
    review: { labelKey: 'general.lane_review', bg: 'bg-purple-50 text-purple-700', text: 'text-purple-700', border: 'border-purple-100' },
    done: { labelKey: 'general.lane_done', bg: 'bg-emerald-50 text-emerald-700', text: 'text-emerald-700', border: 'border-emerald-100' },
};

const TYPE_META: Record<string, { icon: React.ElementType; color: string }> = {
    note: { icon: StickyNote, color: 'text-amber-700 bg-amber-50 ring-amber-200' },
    task: { icon: ListTodo, color: 'text-sky-700 bg-sky-50 ring-sky-200' },
    todo: { icon: List, color: 'text-violet-700 bg-violet-50 ring-violet-200' },
};

const PRIORITY_STYLES: Record<string, string> = {
    high: 'bg-rose-100 text-rose-700 ring-rose-200',
    urgent: 'bg-orange-100 text-orange-700 ring-orange-200',
    normal: 'bg-amber-100 text-amber-700 ring-amber-200',
    low: 'bg-slate-100 text-slate-600 ring-slate-200',
};

export default function Show({ 
    invoice, 
    boardCards = [], 
    categories = [], 
    lanes = [] 
}: { 
    invoice: any; 
    boardCards?: any[]; 
    categories?: any[]; 
    lanes?: string[]; 
}) {
    // Editable state
    const [isEditing, setIsEditing] = useState(false);
    const [isSaving, setIsSaving] = useState(false);

    const [activeTab, setActiveTab] = useState<'details' | 'board'>('details');
    const [localBoardCards, setLocalBoardCards] = useState<any[]>(boardCards);
    const [cardModal, setCardModal] = useState<{
        isOpen: boolean;
        mode: 'create' | 'edit';
        type: 'note' | 'task' | 'todo' | null;
        card?: any;
        initialLane?: string;
    }>({
        isOpen: false,
        mode: 'create',
        type: null
    });

    const handleMoveCard = async (card: any, targetLane: string) => {
        try {
            await axios.post(route('admin.invoices.board.move-card', { invoice: invoice.id }), {
                type: card.type,
                id: card.id,
                lane: targetLane
            });
            setLocalBoardCards(prev => prev.map(c => c.type === card.type && c.id === card.id ? { ...c, lane: targetLane } : c));
            toast.success('Card moved successfully');
        } catch (err) {
            toast.error('Failed to move card');
        }
    };

    const handleAddCard = async (type: 'note' | 'task' | 'todo', payload: any) => {
        try {
            const res = await axios.post(route(`admin.invoices.board.store-${type}`, { invoice: invoice.id }), payload);
            if (res.data?.ok && res.data?.card) {
                setLocalBoardCards(prev => [...prev, res.data.card]);
                toast.success(`${type} added successfully`);
                setCardModal({ isOpen: false, mode: 'create', type: null });
            }
        } catch (err) {
            toast.error(`Failed to add ${type}`);
        }
    };

    const handleUpdateCard = async (type: 'note' | 'task' | 'todo', id: number, payload: any) => {
        try {
            await axios.put(route(`admin.invoices.board.update-${type}`, { invoice: invoice.id, [type]: id }), payload);
            setLocalBoardCards(prev => prev.map(c => c.type === type && c.id === id ? { ...c, ...payload, title: payload.title || payload.task_name || c.title } : c));
            toast.success(`${type} updated successfully`);
            setCardModal({ isOpen: false, mode: 'create', type: null });
        } catch (err) {
            toast.error(`Failed to update ${type}`);
        }
    };

    const handleDeleteCard = async (card: any) => {
        if (!confirm(`Are you sure you want to delete this ${card.type}?`)) return;
        try {
            await axios.delete(route(`admin.invoices.board.destroy-${card.type}`, { invoice: invoice.id, [card.type]: card.id }));
            setLocalBoardCards(prev => prev.filter(c => !(c.type === card.type && c.id === card.id)));
            toast.success(`${card.type} deleted successfully`);
        } catch (err) {
            toast.error(`Failed to delete ${card.type}`);
        }
    };
    
    // Local copy of items for inline editing
    const [items, setItems] = useState<any[]>([]);
    const [costLines, setCostLines] = useState<any[]>([]);
    const [discount, setDiscount] = useState<number>(0);
    const [discountPercentage, setDiscountPercentage] = useState<number>(0);
    const [deletedItems, setDeletedItems] = useState<number[]>([]);
    const [deletedCostLines, setDeletedCostLines] = useState<number[]>([]);

    const [shareModalState, setShareModalState] = useState<{
        isOpen: boolean;
        duration: string;
        loading: boolean;
        shortUrl: string;
        destinationUrl: string;
        expiresAt: string | null;
    }>({
        isOpen: false,
        duration: '1_month',
        loading: false,
        shortUrl: '',
        destinationUrl: '',
        expiresAt: null,
    });

    const handleOpenShareModal = async (duration: string = '1_month') => {
        setShareModalState(prev => ({ ...prev, isOpen: true, duration, loading: true }));
        try {
            const response = await axios.post(route('admin.invoices.share-link', { invoice: String(invoice.id) }), { duration });
            if (response.data?.success) {
                const sUrl = response.data.short_url || response.data.url;
                setShareModalState({
                    isOpen: true,
                    duration,
                    loading: false,
                    shortUrl: sUrl,
                    destinationUrl: response.data.destination_url,
                    expiresAt: response.data.expires_at,
                });
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(sUrl);
                    toast.success(__('admin.link_copied') || 'Shortlink copied to clipboard!');
                }
            } else {
                toast.error(__('general.error_occurred') || 'Failed to generate shortlink');
                setShareModalState(prev => ({ ...prev, loading: false }));
            }
        } catch (error: any) {
            console.error("Failed to generate shortlink", error);
            toast.error(__('general.error_occurred') || 'Failed to generate shortlink');
            setShareModalState(prev => ({ ...prev, loading: false }));
        }
    };

    const handleCopyText = (text: string, label: string = 'Link') => {
        if (!text) return;
        navigator.clipboard.writeText(text);
        toast.success(`${label} ${__('general.copied_to_clipboard') || 'copied to clipboard!'}`);
    };

    const [isSendingNotification, setIsSendingNotification] = useState<'fcm' | 'email' | null>(null);

    const handleSendNotification = (channel: 'fcm' | 'email') => {
        setIsSendingNotification(channel);
        router.post(
            route('admin.invoices.notify', { invoice: String(invoice.id) }),
            { channel },
            {
                preserveScroll: true,
                onFinish: () => setIsSendingNotification(null),
            }
        );
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
        service_revenue: '0',
        tasks_details: ''
    });
    const [payServicePreview, setPayServicePreview] = useState<{ cost: number, total: number, total_usd: number, invoice_currency?: string, business_currency?: string } | null>(null);
    const [isCalculatingPayService, setIsCalculatingPayService] = useState(false);
    const [isSubmittingPayService, setIsSubmittingPayService] = useState(false);

    // Action Modal state
    const [actionModal, setActionModal] = useState<{
        isOpen: boolean;
        type: 'bill_balance' | 'partial_pay' | 'external_pay' | null;
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
                    service_revenue: '0',
                    tasks_details: ''
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

    const handleToggleSuspend = () => {
        router.post(route('admin.invoices.toggle-suspend', { invoice: String(invoice.id) }), {}, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(invoice.is_suspended 
                    ? (__('admin.invoice_unsuspended') || 'Invoice unsuspended')
                    : (__('admin.invoice_suspended') || 'Invoice suspended')
                );
            }
        });
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
            case 'paid': return <span className="inline-flex items-center rounded-full bg-green-500 px-3 py-1 text-sm font-medium text-white">{__('general.paid')}</span>;
            case 'partially_paid': return <span className="inline-flex items-center rounded-full bg-yellow-400 px-3 py-1 text-sm font-medium text-black">{__('general.partially_paid')}</span>;
            case 'cancelled': return <span className="inline-flex items-center rounded-full bg-gray-500 px-3 py-1 text-sm font-medium text-white">{__('general.cancelled')}</span>;
            case 'unpaid':
            default: return <span className="inline-flex items-center rounded-full bg-red-500 px-3 py-1 text-sm font-medium text-white">{__('general.unpaid')}</span>;
        }
    };

    const getJobStatusBadge = (status: string) => {
        switch (status) {
            case 'done': return <span className="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">{__('general.done')}</span>;
            case 'processing': return <span className="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-sm font-medium text-yellow-800">{__('general.processing')}</span>;
            default: return <span className="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-800">{__('general.pending')}</span>;
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
                        {invoice.is_suspended && (
                            <span className="inline-flex items-center rounded-full bg-amber-500 px-3 py-1 text-sm font-medium text-white">
                                {__('admin.suspended') || 'Suspended'}
                            </span>
                        )}
                        {getJobStatusBadge(invoice.job_status)}
                        {invoice.is_editable === false && (
                            <span className="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                <AlertCircle className="w-3.5 h-3.5 text-amber-600" />
                                {invoice.status === 'unpaid' 
                                    ? (__('admin.cannot_add_timers_to_old_invoices', { days: 3 }) || 'Locked (Created > 3 days ago)')
                                    : (__('general.locked') || 'Locked')}
                            </span>
                        )}
                    </div>
                    <div className="flex items-center gap-4 text-sm text-gray-500 mt-2">
                        <span className="flex items-center gap-1">
                            <Clock className="w-4 h-4 opacity-70" />
                            {new Date(invoice.created_at).toLocaleDateString()}
                        </span>
                        <span className="flex items-center gap-1">
                            <span className="font-bold opacity-70">{invoice.currency_symbol || invoice.currency}</span>
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
                            <Clock className="w-4 h-4 me-2" /> {__('admin.reschedule_invoice')}
                        </Button>
                    )}
                    <Button
                        variant="outline"
                        className="h-10 px-4 bg-white"
                        onClick={() => router.visit(route('admin.invoices.linked-transactions', { invoice: String(invoice.id) }))}
                    >
                        <Network className="w-4 h-4 me-2" /> {__('admin.view_linked_transactions')}
                    </Button>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button className="bg-slate-900 hover:bg-slate-900 h-10 px-4 text-white font-semibold">
                                <Layers className="w-4 h-4 me-2" /> {__('admin.actions') || 'Actions'} <ChevronDown className="w-4 h-4 ms-2" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-56">
                            {invoice.status !== 'paid' && invoice.status !== 'cancelled' && (
                                <DropdownMenuItem onClick={handleToggleSuspend} className="cursor-pointer">
                                    {invoice.is_suspended ? (
                                        <>
                                            <Check className="w-4 h-4 me-2 text-emerald-600" />
                                            {__('admin.unsuspend') || 'Unsuspend'}
                                        </>
                                    ) : (
                                        <>
                                            <X className="w-4 h-4 me-2 text-rose-600" />
                                            {__('admin.suspend') || 'Suspend'}
                                        </>
                                    )}
                                </DropdownMenuItem>
                            )}
                            <DropdownMenuItem onClick={() => handleOpenShareModal('1_month')} className="cursor-pointer">
                                <CreditCard className="w-4 h-4 me-2" /> {__('admin.share_signed_invoice_link') || 'Share Signed Invoice Link'}
                            </DropdownMenuItem>
                            {invoice.status !== 'paid' && (
                                <DropdownMenuItem onClick={() => setActionModal({ isOpen: true, type: 'bill_balance', amount: '' })} className="cursor-pointer">
                                    <Receipt className="w-4 h-4 me-2" /> {__('general.bill_from_balance')}
                                </DropdownMenuItem>
                            )}
                            <DropdownMenuItem onClick={() => window.location.href = route('admin.transactions.create', { user: invoice.user?.id, type: 'receive' })} className="cursor-pointer">
                                <Plus className="w-4 h-4 me-2" /> {__('general.receive_money') || 'Receive Money'}
                            </DropdownMenuItem>
                            {invoice.status !== 'paid' && (
                                <DropdownMenuItem onClick={() => setActionModal({ isOpen: true, type: 'external_pay', amount: '' })} className="cursor-pointer">
                                    <Check className="w-4 h-4 me-2" /> {__('general.mark_as_paid')}
                                </DropdownMenuItem>
                            )}
                            <div className="h-px bg-slate-100 my-1" />
                            <div className="px-2 py-1 text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center justify-between">
                                <span>{__('admin.share') || 'Share Shortlink'}</span>
                                <span className="text-[10px] text-emerald-600 font-semibold uppercase">Shortlink</span>
                            </div>
                            <DropdownMenuItem onClick={() => handleOpenShareModal('24_hours')} className="cursor-pointer">
                                <Clock className="w-3.5 h-3.5 me-2 text-slate-500" />
                                {__('admin.share_24_hours')}
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => handleOpenShareModal('3_days')} className="cursor-pointer">
                                <Clock className="w-3.5 h-3.5 me-2 text-slate-500" />
                                {__('admin.share_3_days')}
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => handleOpenShareModal('1_month')} className="cursor-pointer">
                                <Clock className="w-3.5 h-3.5 me-2 text-slate-500" />
                                {__('admin.share_1_month')}
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => handleOpenShareModal('never')} className="cursor-pointer">
                                <Link2 className="w-3.5 h-3.5 me-2 text-slate-500" />
                                {__('general.no_expiry') || 'No Expiry'}
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>

                </div>
            </div>

            {/* Tabs Navigation */}
            <div className="mb-6 border-b border-gray-200">
                <nav className="-mb-px flex space-x-8" aria-label="Tabs">
                    <button
                        onClick={() => setActiveTab('details')}
                        className={cn(
                            "border-b-2 py-4 px-1 text-sm font-medium transition-colors cursor-pointer",
                            activeTab === 'details'
                                ? "border-slate-900 text-slate-950 font-semibold"
                                : "border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700"
                        )}
                    >
                        {__('admin.invoice_details') || 'Invoice Details'}
                    </button>
                    {invoice.project_id && (
                        <button
                            onClick={() => setActiveTab('board')}
                            className={cn(
                                "border-b-2 py-4 px-1 text-sm font-medium transition-colors flex items-center gap-2 cursor-pointer",
                                activeTab === 'board'
                                    ? "border-slate-900 text-slate-950 font-semibold"
                                    : "border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700"
                            )}
                        >
                            <FolderKanban className="w-4 h-4" />
                            {__('admin.invoice_board') || 'Invoice Board'}
                            {localBoardCards.length > 0 && (
                                <span className="bg-slate-100 text-slate-700 px-2 py-0.5 rounded-full text-xs font-bold">
                                    {localBoardCards.length}
                                </span>
                            )}
                        </button>
                    )}
                </nav>
            </div>

            {activeTab === 'details' && (
                <>
                    {/* Info Cards Row */}
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                {/* Client Card */}
                <Card className="shadow-sm border-gray-200">
                    <CardHeader className="pb-3 border-b border-gray-100 bg-gray-50/50">
                        <CardTitle className="text-base flex items-center text-gray-700">
                            <User className="w-4 h-4 me-2 text-gray-400" />{__('general.client_profile')}</CardTitle>
                    </CardHeader>
                    <CardContent className="pt-4">
                        <div className="flex items-center gap-3 mb-4">
                            <div className="h-10 w-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-900">
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
                            {invoice.project ? (
                                <div className="flex items-center gap-2 mt-2">
                                    <div className="inline-flex items-center rounded bg-slate-50 px-2 py-1 text-xs font-semibold text-slate-900">
                                        <Folder className="w-3 h-3 me-1 text-slate-500" /> {invoice.project.project_name}
                                    </div>
                                    {invoice.user?.id && (
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setTransferProjectId(invoice.project?.id || null);
                                                setTransferModal(true);
                                            }}
                                            className="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800 hover:underline flex items-center gap-0.5"
                                        >
                                            <Edit2 className="w-3 h-3" /> {__('general.change') || 'Change'}
                                        </button>
                                    )}
                                </div>
                            ) : (
                                invoice.user?.id && (
                                    <div className="mt-2">
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setTransferProjectId(null);
                                                setTransferModal(true);
                                            }}
                                            className="inline-flex items-center gap-1.5 rounded-lg border border-dashed border-slate-300 hover:border-slate-400 bg-slate-50 hover:bg-slate-100/50 px-2.5 py-1 text-xs font-medium text-slate-600 transition-colors"
                                        >
                                            <Folder className="w-3.5 h-3.5 text-slate-400" />
                                            {__('admin.assign_to_project') || 'Assign to Project'}
                                        </button>
                                    </div>
                                )
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Summary Card */}
                <Card className="shadow-sm border-gray-200">
                    <CardHeader className="pb-3 border-b border-gray-100 bg-gray-50/50">
                        <CardTitle className="text-base flex items-center text-gray-700">
                            <Receipt className="w-4 h-4 me-2 text-gray-400" />{__('general.invoice_summary')}</CardTitle>
                    </CardHeader>
                    <CardContent className="pt-4">
                        <div className="bg-gray-50 rounded-lg p-4 mb-4">
                            <div className="flex justify-between items-baseline">
                                <span className="text-xs font-bold text-gray-500 uppercase tracking-wider">{__('general.total_payable')}</span>
                                <span className="text-2xl font-black text-gray-900">{formatCurrency(currentTotal, invoice.currency)}</span>
                            </div>
                            {invoice.status === 'partially_paid' && (
                                <div className="flex justify-between items-center mt-3 pt-3 border-t border-gray-200">
                                    <span className="text-xs font-bold text-red-600 flex items-center"><Clock className="w-3 h-3 me-1" />{__('general.remaining_due')}</span>
                                    <span className="font-bold text-red-600">{formatCurrency(currentTotal - invoice.paid_amount, invoice.currency)}</span>
                                </div>
                            )}
                        </div>
                        
                        <div className="space-y-2 text-sm">
                            {(invoice.tax > 0 || invoice.discount > 0) && (
                                <div className="flex justify-between items-center py-1">
                                    <span className="text-gray-500">{__('general.subtotal')}</span>
                                    <span className="font-medium">{formatCurrency(invoice.sub_total, invoice.currency)}</span>
                                </div>
                            )}
                            {invoice.discount > 0 && (
                                <div className="flex justify-between items-center py-1">
                                    <span className="text-gray-500">{__('general.total_discount')}</span>
                                    <span className="font-medium text-yellow-600">-{formatCurrency(invoice.discount, invoice.currency)}</span>
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
                                    <span className="font-medium text-slate-900">{formatCurrency(invoice.revenue, invoice.currency)}</span>
                                </div>
                            )}
                        </div>
                        
                        {invoice.status !== 'paid' && (
                            <div className="mt-4 pt-4 border-t border-gray-100 flex justify-between items-center">
                                <span className="text-xs font-bold text-gray-500 uppercase tracking-wider">{__('general.delivery_status')}</span>
                                <Button onClick={() => { setJobStatusForm(invoice.job_status || 'pending'); setJobStatusModal(true); }} variant="outline" size="sm" className="h-7 text-xs border-slate-200 text-slate-900 bg-slate-50 hover:bg-slate-50">{__('general.update_status')}</Button>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Timer Card */}
                <Card className="shadow-sm border-gray-200">
                    <CardHeader className="pb-3 border-b border-gray-100 bg-gray-50/50">
                        <CardTitle className="text-base flex items-center text-gray-700">
                            <Clock className="w-4 h-4 me-2 text-gray-400" />{__('general.time_tracking')}</CardTitle>
                    </CardHeader>
                    <CardContent className="pt-4 flex flex-col items-center justify-center">
                        <div className="text-3xl font-black text-gray-900 mb-1 flex items-center">
                            <Clock className="w-6 h-6 text-slate-900 opacity-20 me-2" />
                            {invoice.timer_metrics?.total_timer_str || invoice.total_timer_str || '00:00:00'}
                        </div>
                        <div className="text-xs font-bold text-gray-500 uppercase tracking-wider text-center mb-4">{__('general.total_billable_hours')}</div>

                        {invoice.timer_metrics && (
                            <div className="w-full bg-gray-50 rounded-lg p-3 space-y-2 text-xs border border-gray-100 mb-2">
                                <div className="flex justify-between items-center">
                                    <span className="text-gray-500 font-medium">{__('general.full_real_value') || 'القيمة الفعلية بسعر الساعة'}</span>
                                    <span className="font-bold text-blue-700">{invoice.timer_metrics.full_real_value_str}</span>
                                </div>
                                <div className="flex justify-between items-center">
                                    <span className="text-gray-500 font-medium">{__('general.billed_amount') || 'المبلغ الصافي بالفاتورة'}</span>
                                    <span className="font-bold text-emerald-700">{invoice.timer_metrics.billed_amount_str}</span>
                                </div>
                                {invoice.timer_metrics.has_discount && (
                                    <div className="flex justify-between items-center pt-1 border-t border-gray-200">
                                        <span className="text-purple-700 font-bold">{__('general.discount_savings') || 'إجمالي الخصم / الوفر'}</span>
                                        <span className="font-black text-purple-700">-{invoice.timer_metrics.discount_savings_str}</span>
                                    </div>
                                )}
                            </div>
                        )}
                        
                        {isUnpaid && invoice.is_editable !== false && (
                            <Button onClick={handleAddTimerItem} variant="link" className="mt-2 text-slate-900 font-bold hover:text-slate-900">
                                <Plus className="w-3 h-3 me-1" />{__('general.add_manual_entry')}
                            </Button>
                        )}
                    </CardContent>
                </Card>

                {/* Affiliate Card */}
                {invoice.affiliate_data && (
                    <Card className="shadow-sm border-gray-200">
                        <CardHeader className="pb-3 border-b border-gray-100 bg-gray-50/50">
                            <CardTitle className="text-base flex items-center text-gray-700">
                                <Network className="w-4 h-4 me-2 text-gray-400" /> {__('general.affiliate')}</CardTitle>
                        </CardHeader>
                        <CardContent className="pt-4">
                            <div className="flex items-center gap-3 mb-4">
                                <div className="h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-600">
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
                                    <span className="font-bold text-slate-900">{invoice.affiliate_data.commission_percent}%</span>
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
                                    <AlertCircle className="w-3 h-3 me-1" />{__('general.added_to_invoice_total')}</div>
                            )}
                        </CardContent>
                    </Card>
                )}
            </div>

            {/* Toolbar (Unpaid & Editable only) */}
            {isUnpaid && invoice.is_editable !== false && !isEditing && (
                <div className="bg-white border rounded-lg p-4 mb-6 flex flex-col md:flex-row items-center justify-between gap-4 shadow-sm">
                    <div className="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                        <span className="text-xs font-bold text-gray-500 uppercase tracking-wider me-2">Quick Build:</span>
                        <div className="flex gap-2 w-full sm:w-auto">
                            <Button onClick={handleAddQtyItem} variant="outline" size="sm" className="flex-1 sm:flex-none border-dashed hover:border-slate-200 hover:text-slate-900 hover:bg-slate-50">
                                <Layers className="w-4 h-4 me-2 text-slate-900" />{__('general.qty_item')}</Button>
                            <Button onClick={handleAddSimpleItem} variant="outline" size="sm" className="flex-1 sm:flex-none border-dashed hover:border-slate-200 hover:text-slate-900 hover:bg-slate-50">
                                <Plus className="w-4 h-4 me-2 text-slate-900" />{__('general.simple_item')}</Button>
                            <Button onClick={() => setShowPayServiceModal(true)} variant="outline" size="sm" className="hidden sm:flex border-dashed hover:border-green-300 hover:text-green-600 hover:bg-green-50">
                                <CreditCard className="w-4 h-4 me-2 text-green-500" />{__('general.pay_service')}</Button>
                            <Button onClick={handleAddTimerItem} variant="secondary" size="sm" className="hidden sm:flex bg-gray-100 text-gray-700 hover:bg-gray-200">
                                <Clock className="w-4 h-4 me-2" />{__('general.log_time')}</Button>
                        </div>
                    </div>
                    <Button onClick={() => { if(confirm('Mark invoice as paid?')) router.post(route('admin.invoices.external-pay', { invoice: String(invoice.id) })); }} variant="outline" size="sm" className="w-full md:w-auto border-dashed border-green-300 text-green-700 hover:bg-green-50">
                        <CreditCard className="w-4 h-4 me-2" />{__('general.mark_as_paid')}</Button>
                </div>
            )}

            {/* Items Table */}
            <Card className="shadow-sm border-gray-200 mb-8 overflow-hidden">
                <div className="bg-gray-50 border-b px-4 py-3 flex items-center justify-between">
                    <div className="flex items-center text-gray-700 font-semibold">
                        <List className="w-5 h-5 text-gray-400 me-2" />{__('general.invoice_items')}</div>
                    {isUnpaid && invoice.is_editable !== false && !isEditing && (
                        <Button variant="outline" size="sm" onClick={() => setIsEditing(true)}>
                            <Edit2 className="w-4 h-4 me-2" />{__('general.edit_rows')}</Button>
                    )}
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm text-start">
                        <thead className="bg-gray-50/50 text-gray-500 border-b">
                            <tr>
                                {isEditing && <th className="px-4 py-3 font-medium w-12 text-center">{__('general.merge')}</th>}
                                <th className="px-4 py-3 font-medium w-12 text-center">#</th>
                                <th className="px-4 py-3 font-medium w-24">{__('general.type')}</th>
                                <th className="px-4 py-3 font-medium">{__('general.description')}</th>
                                <th className="px-4 py-3 font-medium text-end w-32">{__('general.price')}</th>
                                <th className="px-4 py-3 font-medium text-center w-24">{__('general.quantity')}</th>
                                <th className="px-4 py-3 font-medium text-end w-32">{__('general.total')}</th>
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
                                                className="rounded border-gray-300 text-slate-900 focus:ring-slate-900"
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
                                                    <Clock className="w-3 h-3 me-1" />
                                                    <span className="capitalize">{item.item_type} (Save First)</span>
                                                </span>
                                            ) : (
                                                <Link href={route('admin.invoices.timer-details', item.id)} className="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-yellow-100 text-yellow-800 hover:bg-yellow-200 transition-colors cursor-pointer" title={__('general.click_to_edit_timer_details')}>
                                                    <Clock className="w-3 h-3 me-1" />
                                                    <span className="capitalize">{item.item_type}</span>
                                                </Link>
                                            )
                                        ) : (
                                            <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ${
                                                item.item_type === 'quantity' ? 'bg-slate-50 text-slate-900' :
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
                                    <td className="px-4 py-3 text-end">
                                        {(isEditing && item.item_type !== 'timer') ? (
                                            <div className="flex items-center justify-end">
                                                <span className="text-gray-400 me-1 text-xs">{item.currency}</span>
                                                <Input 
                                                    type="number"
                                                    value={item.amount} 
                                                    onChange={(e) => handleItemChange(index, 'amount', e.target.value)}
                                                    className="h-8 w-24 text-end shadow-none"
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
                                    <td className="px-4 py-3 text-end font-bold text-gray-900">
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
                                        <div className="flex justify-end gap-4 items-center">
                                            <div className="text-sm text-gray-500">
                                                Draft Total: <span className="font-bold text-gray-900">{formatCurrency(currentTotal, invoice.currency)}</span>
                                            </div>
                                            <div className="flex gap-2">
                                                {selectedItemsForMerge.length > 1 && (
                                                    <Button type="button" onClick={handleMergeSelected} variant="secondary" size="sm" className="bg-yellow-100 text-yellow-800 hover:bg-yellow-200">
                                                        <Merge className="w-4 h-4 me-1" /> Merge ({selectedItemsForMerge.length})
                                                    </Button>
                                                )}
                                                <Button type="button" onClick={handleCancel} variant="outline" size="sm" disabled={isSaving}>
                                                    <X className="w-4 h-4 me-1" /> {__('general.cancel')}</Button>
                                                <Button type="button" onClick={handleSave} size="sm" disabled={isSaving} className="bg-slate-900 hover:bg-slate-900">
                                                    <Check className="w-4 h-4 me-1" />{__('general.save_changes')}</Button>
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
                            className="text-slate-900 bg-slate-50 hover:bg-slate-50"
                        >
                            <ChartLine className="w-4 h-4 me-2" />
                            {showPricingInsights ? 'Hide Pricing Insights' : 'Show Pricing Insights'}
                        </Button>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {/* Discount Card */}
                        <Card className="shadow-sm border-gray-200 h-full">
                            <CardContent className="p-5">
                                <div className="flex items-center gap-2 font-bold text-gray-700 mb-2">
                                    <AlertCircle className="w-4 h-4 text-yellow-600" />{__('general.apply_discount')}</div>
                                <p className="text-sm text-gray-500 mb-4">{__('general.enter_a_fixed_discount_amount_to_reduce_the_total_payable')}</p>
                                
                                <div className="flex items-end gap-3 flex-wrap">
                                    <div className="flex-1 space-y-1 min-w-[150px]">
                                        <Label className="text-xs text-gray-500 uppercase">{__('general.amount')}</Label>
                                        <div className="flex items-center">
                                            <span className="bg-gray-100 border border-e-0 border-gray-300 rounded-s-md px-3 py-2 text-sm text-gray-500 h-10">{invoice.currency}</span>
                                            <Input 
                                                type="number"
                                                className="rounded-s-none h-10 shadow-none focus-visible:ring-slate-900"
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
                                        <Label className="text-xs text-gray-500 uppercase">{__('general.percent')}</Label>
                                        <div className="flex items-center">
                                            <Input 
                                                type="number"
                                                className="rounded-e-none h-10 shadow-none focus-visible:ring-slate-900"
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
                                            <span className="bg-gray-100 border border-s-0 border-gray-300 rounded-e-md px-3 py-2 text-sm text-gray-500 h-10">%</span>
                                        </div>
                                    </div>
                                    {isUnpaid && (
                                        <Button type="button" onClick={handleSave} disabled={isSaving} className="h-10 bg-gray-900 text-white hover:bg-gray-800 w-full sm:w-auto">
                                            {__('general.update')}</Button>
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
                                <Calculator className="w-4 h-4 me-2 text-gray-400" />{__('general.internal_cost_lines')}</CardTitle>
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
                                                        line.line_type === 'user_credit' ? 'bg-slate-50 text-slate-900' : 'bg-gray-200 text-gray-800'
                                                    }`}>
                                                        {line.line_type === 'user_credit' ? 'User Credit' : 'Direct Cost'}
                                                    </span>
                                                    <span className="font-bold text-gray-900">{formatCurrency(line.amount, invoice.currency)}</span>
                                                </div>
                                                {line.description && <div className="text-sm text-gray-700 mt-1">{line.description}</div>}
                                                {line.credit_user_name && <div className="text-xs text-gray-500 mt-1 flex items-center"><User className="w-3 h-3 me-1" /> {line.credit_user_name}</div>}
                                            </div>
                                        ) : (
                                            <div className="flex flex-col gap-3">
                                                <div className="flex flex-col sm:flex-row gap-3">
                                                    <div className="w-full sm:w-1/4">
                                                        <Label className="text-xs text-gray-500">{__('general.type')}</Label>
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
                                                        <Label className="text-xs text-gray-500">{__('general.amount')}</Label>
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
                                                        <Label className="text-xs text-gray-500">{__('general.description')}</Label>
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
                                                                asyncEndpoint={route('admin.notifications.search_users')}
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
                                                                router.post(route('admin.invoices.cost-lines.record-paid', { invoice: invoice.id, line: line.id }));
                                                            }}
                                                            className="bg-slate-900 hover:bg-slate-900 text-white h-8 px-3 text-xs font-semibold"
                                                        >
                                                            <Receipt className="w-3.5 h-3.5 me-1.5" />{__('general.record_as_paid')}</Button>
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
                                            <Calculator className="w-4 h-4 me-2" />{__('general.save_cost_lines')}</Button>
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
                    <div className="flex flex-wrap items-center gap-6 text-center md:text-start">
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
                                    className="bg-slate-900 hover:bg-slate-900 text-white"
                                >
                                    <Check className="w-4 h-4 me-2" />{__('general.bill_from_balance')}</Button>
                                <Button 
                                    onClick={() => setActionModal({ isOpen: true, type: 'partial_pay', amount: String(invoice.amount) })}
                                    variant="outline"
                                >
                                    <CreditCard className="w-4 h-4 me-2" />{__('general.partial_pay')}</Button>
                            </>
                        )}
                        {invoice.status === 'partially_paid' && (
                            <Button 
                                onClick={() => {
                                    const remaining = invoice.amount - (invoice.paid_amount || 0);
                                    setActionModal({ isOpen: true, type: 'partial_pay', amount: String(remaining) });
                                }}
                                className="bg-slate-900 hover:bg-slate-900 text-white"
                            >
                                <Plus className="w-4 h-4 me-2" />{__('general.add_payment')}</Button>
                        )}

                        {invoice.status !== 'cancelled' && (
                            <div className="hidden md:block w-px h-8 bg-gray-200 mx-1"></div>
                        )}

                        {invoice.status !== 'cancelled' && invoice.user?.id && (
                            <Button
                                variant="outline"
                                onClick={() => {
                                    setTransferProjectId(invoice.project?.id || null);
                                    setTransferModal(true);
                                }}
                            >
                                <Folder className="w-4 h-4 me-2" />
                                {invoice.project ? __('general.transfer') : __('admin.assign_to_project')}
                            </Button>
                        )}

                        {invoice.status !== 'cancelled' && invoice.user?.id && (
                            <div className="inline-flex rounded-md shadow-sm" role="group">
                                <Button
                                    variant="outline"
                                    title={__('admin.send_fcm_notification')}
                                    disabled={isSendingNotification !== null}
                                    onClick={() => handleSendNotification('fcm')}
                                    className="rounded-e-none border-e-0"
                                >
                                    <Bell className="w-4 h-4 me-2" />
                                    {isSendingNotification === 'fcm' ? __('general.saving') + '...' : __('admin.send_fcm_notification')}
                                </Button>
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button
                                            variant="outline"
                                            disabled={isSendingNotification !== null}
                                            className="rounded-s-none px-2 border-s"
                                            title={__('admin.send_email_notification')}
                                            aria-label={__('admin.send_email_notification')}
                                        >
                                            <ChevronDown className="w-4 h-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem
                                            disabled={isSendingNotification !== null}
                                            onClick={() => handleSendNotification('email')}
                                        >
                                            <Mail className="w-4 h-4 me-2" />
                                            {isSendingNotification === 'email' ? __('general.saving') + '...' : __('admin.send_email_notification')}
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
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
                            <div className="sm:me-auto text-sm text-gray-500">{__('general.cancelling_will_void_all_related_transactions')}</div>
                        )}
                        <Button 
                            variant="outline"
                            className="border-red-300 text-red-600 hover:bg-red-100 w-full sm:w-auto justify-center"
                            onClick={() => { if(confirm('Are you sure you want to cancel this invoice? This action is irreversible.')) router.post(route('admin.invoices.cancel', { invoice: String(invoice.id) })); }}
                        >
                            <X className="w-4 h-4 me-2" />{__('general.cancel_invoice')}</Button>
                        <Button 
                            variant="outline"
                            className="border-red-300 text-red-600 hover:bg-red-100 w-full sm:w-auto justify-center"
                            onClick={() => { if(confirm('Are you sure you want to DELETE this invoice? This cannot be undone.')) router.post(route('admin.invoices.bulk-action'), { action: 'delete', invoices: [invoice.id] }); }}
                        >
                            <Trash2 className="w-4 h-4 me-2" />{__('general.delete_invoice')}</Button>
                    </div>
                </div>
            )}
                </>
            )}

            {activeTab === 'board' && invoice.project_id && (
                <div className="space-y-6">
                    {/* Board Header / Controls */}
                    <div className="flex flex-wrap items-center justify-between gap-4 bg-white p-4 rounded-lg border shadow-sm">
                        <div>
                            <h3 className="text-lg font-bold text-gray-900">{__('admin.invoice_board') || 'Invoice Board'}</h3>
                            <p className="text-xs text-gray-500 mt-1">
                                {__('admin.invoice_board_desc') || 'Manage sticky notes, tasks, and todos linked to this invoice. These items will also appear on the project board.'}
                            </p>
                        </div>
                        <div className="flex gap-2">
                            <Button onClick={() => setCardModal({ isOpen: true, mode: 'create', type: 'note', initialLane: 'backlog' })} variant="outline" size="sm" className="cursor-pointer">
                                <StickyNote className="w-4 h-4 me-1.5" /> + {__('general.sticky_note')}
                            </Button>
                            <Button onClick={() => setCardModal({ isOpen: true, mode: 'create', type: 'task', initialLane: 'backlog' })} variant="outline" size="sm" className="cursor-pointer">
                                <ListTodo className="w-4 h-4 me-1.5" /> + {__('general.task')}
                            </Button>
                            <Button onClick={() => setCardModal({ isOpen: true, mode: 'create', type: 'todo', initialLane: 'backlog' })} variant="outline" size="sm" className="cursor-pointer">
                                <Plus className="w-4 h-4 me-1.5" /> + {__('general.todo')}
                            </Button>
                        </div>
                    </div>

                    {/* Board Grid Layout (4 columns) */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        {lanes.map((lane) => {
                            const laneCards = localBoardCards.filter((c) => c.lane === lane);
                            return (
                                <div key={lane} className="bg-slate-50 border border-slate-200 rounded-xl p-4 flex flex-col min-h-[500px]">
                                    {/* Lane Header */}
                                    <div className="flex items-center justify-between mb-4 pb-2 border-b border-slate-200">
                                        <div className="flex items-center gap-2">
                                            <span className="font-bold text-sm text-slate-700 uppercase tracking-wider">
                                                {__(LANE_META[lane]?.labelKey) || lane.replace('_', ' ')}
                                            </span>
                                            <span className="bg-slate-200 text-slate-700 px-2 py-0.5 rounded-full text-xs font-bold">
                                                {laneCards.length}
                                            </span>
                                        </div>
                                    </div>

                                    {/* Cards Container */}
                                    <div className="space-y-3 flex-1 overflow-y-auto">
                                        {laneCards.length === 0 ? (
                                            <div className="border-2 border-dashed border-slate-200 rounded-xl p-6 text-center text-xs text-slate-400">
                                                {__('general.no_items') || 'No items'}
                                            </div>
                                        ) : (
                                            laneCards.map((card) => {
                                                const typeMeta = TYPE_META[card.type];
                                                const Icon = typeMeta?.icon || StickyNote;
                                                return (
                                                    <div 
                                                        key={`${card.type}-${card.id}`}
                                                        className={cn(
                                                            "bg-white border rounded-xl p-4 shadow-sm hover:shadow transition-shadow flex flex-col gap-2 relative group",
                                                            card.type === 'note' && NOTE_COLORS[card.color || 'yellow']?.bg,
                                                            card.type === 'note' && NOTE_COLORS[card.color || 'yellow']?.border
                                                        )}
                                                    >
                                                        {/* Card Header */}
                                                        <div className="flex items-center justify-between">
                                                            <span className={cn("text-[10px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wider flex items-center gap-1", typeMeta?.color)}>
                                                                <Icon className="w-3 h-3" />
                                                                {card.type}
                                                            </span>
                                                            <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                                <button 
                                                                    onClick={() => setCardModal({ isOpen: true, mode: 'edit', type: card.type, card })}
                                                                    className="p-1 hover:bg-slate-100 rounded text-slate-500 hover:text-slate-700 cursor-pointer"
                                                                >
                                                                    <Edit2 className="w-3.5 h-3.5" />
                                                                </button>
                                                                <button 
                                                                    onClick={() => handleDeleteCard(card)}
                                                                    className="p-1 hover:bg-red-50 rounded text-red-500 hover:text-red-700 cursor-pointer"
                                                                >
                                                                    <Trash2 className="w-3.5 h-3.5" />
                                                                </button>
                                                            </div>
                                                        </div>

                                                        {/* Card Body */}
                                                        <div>
                                                            <h4 className="font-bold text-sm text-slate-900 line-clamp-2">
                                                                {card.title}
                                                            </h4>
                                                            {card.description && (
                                                                <p className="text-xs text-slate-500 mt-1 line-clamp-3">
                                                                    {card.description}
                                                                </p>
                                                            )}
                                                            {card.type === 'note' && card.content && (
                                                                <p className="text-xs text-slate-800 mt-1 whitespace-pre-wrap line-clamp-4">
                                                                    {card.content}
                                                                </p>
                                                            )}
                                                        </div>

                                                        {/* Card Footer: Metadata / Lane Switcher */}
                                                        <div className="mt-2 pt-2 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2">
                                                            {card.type === 'task' && card.priority && (
                                                                <span className={cn("text-[10px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wider", PRIORITY_STYLES[card.priority])}>
                                                                    {card.priority}
                                                                </span>
                                                            )}
                                                            {card.type === 'todo' && (
                                                                <span className="text-[10px] font-medium text-slate-500">
                                                                    {card.completed ? '✓ Completed' : 'Pending'}
                                                                </span>
                                                            )}
                                                            
                                                            {/* Simple Lane Switcher */}
                                                            <select
                                                                value={card.lane}
                                                                onChange={(e) => handleMoveCard(card, e.target.value)}
                                                                className="text-xs border border-slate-200 bg-slate-50 hover:bg-slate-100 rounded px-1.5 py-1 text-slate-600 focus:ring-0 focus:outline-none cursor-pointer"
                                                            >
                                                                {lanes.map(l => (
                                                                    <option key={l} value={l}>
                                                                        {__(LANE_META[l]?.labelKey) || l.replace('_', ' ')}
                                                                    </option>
                                                                ))}
                                                            </select>
                                                        </div>
                                                    </div>
                                                );
                                            })
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}

            {/* Board Card Modal */}
            <Dialog open={cardModal.isOpen} onOpenChange={(open) => setCardModal(prev => ({ ...prev, isOpen: open }))}>
                <DialogContent className="sm:max-w-md bg-white">
                    <DialogHeader>
                        <DialogTitle>
                            {cardModal.mode === 'create' 
                                ? `${__('general.create') || 'Create'} ${cardModal.type}`
                                : `${__('general.edit') || 'Edit'} ${cardModal.type}`
                            }
                        </DialogTitle>
                        <DialogDescription>
                            {__('admin.invoice_board_modal_desc') || 'Enter the details for this board card.'}
                        </DialogDescription>
                    </DialogHeader>
                    
                    <form onSubmit={(e) => {
                        e.preventDefault();
                        const formData = new FormData(e.currentTarget);
                        const payload: Record<string, any> = {};
                        formData.forEach((value, key) => {
                            payload[key] = value;
                        });
                        
                        if (cardModal.mode === 'create') {
                            handleAddCard(cardModal.type!, { ...payload, lane: cardModal.initialLane });
                        } else {
                            handleUpdateCard(cardModal.type!, cardModal.card.id, payload);
                        }
                    }} className="space-y-4">
                        {/* Note Fields */}
                        {cardModal.type === 'note' && (
                            <>
                                <div className="space-y-1">
                                    <Label htmlFor="title">{__('general.title') || 'Title'}</Label>
                                    <Input id="title" name="title" defaultValue={cardModal.card?.title || ''} placeholder="e.g. Note title" className="focus-visible:ring-slate-900" />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="content">{__('general.content') || 'Content'}</Label>
                                    <textarea id="content" name="content" defaultValue={cardModal.card?.content || ''} rows={4} className="flex min-h-[80px] w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm ring-offset-white placeholder:text-slate-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-900 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" placeholder="Sticky note content..." required />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="color">{__('general.color') || 'Color'}</Label>
                                    <select id="color" name="color" defaultValue={cardModal.card?.color || 'yellow'} className="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-900 cursor-pointer">
                                        <option value="yellow">Yellow</option>
                                        <option value="green">Green</option>
                                        <option value="blue">Blue</option>
                                        <option value="red">Red</option>
                                        <option value="purple">Purple</option>
                                        <option value="pink">Pink</option>
                                        <option value="slate">Slate</option>
                                    </select>
                                </div>
                            </>
                        )}

                        {/* Task Fields */}
                        {cardModal.type === 'task' && (
                            <>
                                <div className="space-y-1">
                                    <Label htmlFor="task_name">{__('general.task_name') || 'Task Name'}</Label>
                                    <Input id="task_name" name="task_name" defaultValue={cardModal.card?.title || ''} placeholder="e.g. Implement feature" required className="focus-visible:ring-slate-900" />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="task_description">{__('general.task_description') || 'Description'}</Label>
                                    <textarea id="task_description" name="task_description" defaultValue={cardModal.card?.description || ''} rows={4} className="flex min-h-[80px] w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm ring-offset-white placeholder:text-slate-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-900 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" placeholder="Task details..." />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="priority">{__('general.priority') || 'Priority'}</Label>
                                    <select id="priority" name="priority" defaultValue={cardModal.card?.priority || 'normal'} className="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-900 cursor-pointer">
                                        <option value="low">Low</option>
                                        <option value="normal">Normal</option>
                                        <option value="high">High</option>
                                        <option value="urgent">Urgent</option>
                                    </select>
                                </div>
                            </>
                        )}

                        {/* Todo Fields */}
                        {cardModal.type === 'todo' && (
                            <>
                                <div className="space-y-1">
                                    <Label htmlFor="title">{__('general.title') || 'Title'}</Label>
                                    <Input id="title" name="title" defaultValue={cardModal.card?.title || ''} placeholder="e.g. Check list item" required className="focus-visible:ring-slate-900" />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="description">{__('general.description') || 'Description'}</Label>
                                    <textarea id="description" name="description" defaultValue={cardModal.card?.description || ''} rows={4} className="flex min-h-[80px] w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm ring-offset-white placeholder:text-slate-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-900 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" placeholder="Todo details..." />
                                </div>
                                {cardModal.mode === 'edit' && (
                                    <div className="flex items-center space-x-2">
                                        <input type="checkbox" id="completed" name="completed" defaultChecked={cardModal.card?.completed} className="rounded border-gray-300 text-slate-900 focus:ring-slate-900 cursor-pointer" value="1" />
                                        <Label htmlFor="completed">{__('general.completed') || 'Completed'}</Label>
                                    </div>
                                )}
                            </>
                        )}

                        <DialogFooter className="pt-4">
                            <Button type="button" variant="outline" onClick={() => setCardModal({ isOpen: false, mode: 'create', type: null })}>
                                {__('general.cancel') || 'Cancel'}
                            </Button>
                            <Button type="submit" className="bg-slate-900 hover:bg-slate-900 text-white font-semibold cursor-pointer">
                                {cardModal.mode === 'create' ? (__('general.create') || 'Create') : (__('general.save') || 'Save')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Pay Service Modal */}
            <Dialog open={showPayServiceModal} onOpenChange={setShowPayServiceModal}>
                <DialogContent className="sm:max-w-[600px] bg-white">
                    <DialogHeader>
                        <DialogTitle className="flex items-center text-green-700">
                            <CreditCard className="w-5 h-5 me-2" />
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

                        <div className="space-y-2">
                            <Label>{__('general.tasks_details') || 'Tasks Details (Optional)'}</Label>
                            <textarea 
                                className="flex min-h-[100px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                value={(payServiceForm as any).tasks_details || ''}
                                onChange={e => setPayServiceForm({...payServiceForm, tasks_details: e.target.value})}
                                placeholder="Task A: 5 points&#10;Task B: 10 points"
                                rows={4}
                            />
                        </div>

                        {/* Live Calculation Preview */}
                        <div className="mt-4 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                            <h4 className="text-sm font-semibold text-gray-700 mb-2 flex items-center">
                                <Calculator className="w-4 h-4 me-2" />
                                {__('general.calculation_preview')}
                            </h4>
                            {isCalculatingPayService ? (
                                <div className="text-sm text-gray-500">{__('general.calculating')}...</div>
                            ) : payServicePreview ? (
                                <div className="space-y-2 text-sm">
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">{__('general.service_cost')}</span>
                                        <span className="font-medium text-red-600">
                                            {formatCurrency(payServicePreview.cost, payServicePreview.invoice_currency)}
                                        </span>
                                    </div>
                                    <div className="flex justify-between font-semibold border-t pt-2 mt-2">
                                        <span className="text-gray-700">{__('general.invoice_total_addition')}</span>
                                        <span className="text-green-600">
                                            {formatCurrency(payServicePreview.total, payServicePreview.invoice_currency)}
                                        </span>
                                    </div>
                                    <div className="flex justify-end gap-4 text-xs text-gray-400 mt-1">
                                        <span>{__('general.business_currency_total')}</span>
                                        <span>
                                            {formatCurrency(payServicePreview.total_usd, (payServicePreview as any).business_currency || 'USD')}
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
                             actionModal.type === 'external_pay' ? __('general.mark_as_paid') :
                             __('general.partial_pay')}
                        </DialogTitle>
                        <DialogDescription>
                            {actionModal.type === 'bill_balance' ? __('general.confirm_bill_balance') :
                             actionModal.type === 'external_pay' ? __('general.confirm_mark_paid') || 'Are you sure you want to mark this invoice as paid?' :
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
                            className="bg-slate-900 hover:bg-slate-900 text-white"
                            onClick={() => {
                                if (actionModal.type === 'bill_balance') {
                                    router.post(route('admin.invoices.mark-paid', { invoice: String(invoice.id) }));
                                } else if (actionModal.type === 'external_pay') {
                                    router.post(route('admin.invoices.external-pay', { invoice: String(invoice.id) }));
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
                                    className="rounded border-gray-300 text-slate-900 focus:ring-slate-900"
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
                            <Button type="submit" className="bg-slate-900 hover:bg-slate-900 text-white">
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
                            placeholder={__('general.select_job_status')}
                        />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setJobStatusModal(false)}>{__('general.cancel') || 'Cancel'}</Button>
                        <Button onClick={() => {
                            router.post(route('admin.invoices.change-job-status', { invoice: String(invoice.id) }), { job_status: jobStatusForm }, {
                                onSuccess: () => setJobStatusModal(false),
                                preserveScroll: true
                            });
                        }} className="bg-slate-900 hover:bg-slate-900 text-white">{__('general.save_changes') || 'Save Status'}</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={transferModal} onOpenChange={setTransferModal}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{invoice.project ? __('general.transfer_to_project') : __('admin.assign_to_project')}</DialogTitle>
                        <DialogDescription>{__('general.select_the_project_to_transfer_this_invo')}</DialogDescription>
                    </DialogHeader>
                    <div className="py-4 space-y-3">
                        {invoice.user?.projects?.length ? (
                            <PremiumCombobox
                                value={transferProjectId || ''}
                                onChange={(val) => setTransferProjectId(val)}
                                options={invoice.user.projects.map((p: any) => ({
                                    value: p.id,
                                    label: p.project_name
                                }))}
                                placeholder={__('general.select_project')}
                            />
                        ) : (
                            <div className="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4 text-center">
                                <p className="text-sm font-medium text-slate-700">{__('general.no_projects_yet')}</p>
                                <p className="mt-1 text-xs text-slate-500">{__('general.no_projects_have_been_linked_to_this_client')}</p>
                                <Button asChild variant="outline" className="mt-3">
                                    <Link href={route('admin.projects.create', { user_id: invoice.user.id })}>
                                        <Plus className="w-4 h-4 me-2" />
                                        {__('general.create_new_project')}
                                    </Link>
                                </Button>
                            </div>
                        )}
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setTransferModal(false)}>{__('general.cancel') || 'Cancel'}</Button>
                        <Button
                            disabled={!invoice.user?.projects?.length}
                            onClick={() => {
                                router.post(route('admin.invoices.assign-project', { invoice: String(invoice.id) }), {
                                    project_id: transferProjectId || null,
                                }, {
                                    onSuccess: () => setTransferModal(false),
                                    preserveScroll: true,
                                });
                            }}
                            className="bg-slate-900 hover:bg-slate-900 text-white"
                        >
                            {__('general.transfer')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Share Signed Invoice Shortlink Modal */}
            <Dialog open={shareModalState.isOpen} onOpenChange={(open) => setShareModalState(prev => ({ ...prev, isOpen: open }))}>
                <DialogContent className="sm:max-w-[520px]">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-lg font-bold text-slate-900">
                            <CreditCard className="w-5 h-5 text-emerald-600" />
                            {__('admin.share_signed_invoice_link') || 'Share Signed Invoice Shortlink'}
                        </DialogTitle>
                        <DialogDescription className="text-xs text-slate-500">
                            {__('shortlink.share_invoice_shortlink_desc') || 'A secure, shortened link for the client to view and pay invoice'} #{invoice.invoice_number}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-3">
                        {/* Expiration selector */}
                        <div className="space-y-1.5">
                            <Label className="text-xs font-semibold text-slate-600">
                                {__('admin.duration') || 'Link Validity'}
                            </Label>
                            <div className="grid grid-cols-4 gap-2">
                                {[
                                    { id: '24_hours', label: __('admin.share_24_hours') || '24h' },
                                    { id: '3_days', label: __('admin.share_3_days') || '3 Days' },
                                    { id: '1_month', label: __('admin.share_1_month') || '1 Month' },
                                    { id: 'never', label: __('general.no_expiry') || 'No Expiry' },
                                ].map(opt => (
                                    <button
                                        key={opt.id}
                                        type="button"
                                        disabled={shareModalState.loading}
                                        onClick={() => handleOpenShareModal(opt.id)}
                                        className={cn(
                                            "h-9 px-2 text-xs font-medium rounded-lg border transition-all cursor-pointer text-center",
                                            shareModalState.duration === opt.id
                                                ? "border-slate-900 bg-slate-900 text-white font-semibold shadow-sm"
                                                : "border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                                        )}
                                    >
                                        {opt.label}
                                    </button>
                                ))}
                            </div>
                        </div>

                        {/* Short Link Display */}
                        <div className="space-y-1.5">
                            <div className="flex items-center justify-between">
                                <span className="text-xs font-semibold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                                    <Link2 className="w-3.5 h-3.5 text-emerald-600" />
                                    {__('shortlink.short_link') || 'Short Link'}
                                </span>
                                <span className="text-[10px] font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100">
                                    {__('shortlink.recommended_for_sharing') || 'Recommended'}
                                </span>
                            </div>
                            <div className="relative">
                                <Input
                                    readOnly
                                    value={shareModalState.loading ? 'Generating...' : (shareModalState.shortUrl || '')}
                                    className="h-10 pe-24 font-mono text-xs bg-slate-50 border-slate-300 text-slate-900 select-all"
                                />
                                <Button
                                    type="button"
                                    size="sm"
                                    disabled={shareModalState.loading || !shareModalState.shortUrl}
                                    onClick={() => handleCopyText(shareModalState.shortUrl, 'Shortlink')}
                                    className="absolute end-1.5 top-1.5 h-7 px-3 bg-slate-900 hover:bg-slate-800 text-white text-[11px] font-semibold"
                                >
                                    <Copy className="w-3.5 h-3.5 me-1.5" />
                                    {__('general.copy') || 'Copy'}
                                </Button>
                            </div>
                        </div>

                        {/* Full Destination Link Display */}
                        {shareModalState.destinationUrl && (
                            <div className="space-y-1">
                                <span className="text-[11px] font-medium text-slate-400">
                                    {__('shortlink.destination_url') || 'Full Signed URL'}:
                                </span>
                                <div className="p-2 bg-slate-50 border border-slate-100 rounded-md text-[10px] font-mono text-slate-500 break-all max-h-16 overflow-y-auto">
                                    {shareModalState.destinationUrl}
                                </div>
                            </div>
                        )}

                        {shareModalState.expiresAt && (
                            <div className="flex items-center gap-1.5 text-xs text-amber-700 bg-amber-50 border border-amber-100 px-3 py-2 rounded-lg">
                                <Clock className="w-4 h-4 text-amber-600 shrink-0" />
                                <span>
                                    {__('admin.expires_at') || 'Expires at'}: <strong>{shareModalState.expiresAt}</strong>
                                </span>
                            </div>
                        )}
                    </div>

                    <DialogFooter className="flex-row sm:justify-between items-center gap-2 pt-2 border-t">
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            disabled={!shareModalState.shortUrl}
                            onClick={() => window.open(shareModalState.shortUrl, '_blank')}
                            className="text-xs text-slate-600 hover:text-slate-900"
                        >
                            <ExternalLink className="w-3.5 h-3.5 me-1.5" />
                            {__('general.open_in_new_tab') || 'Test Link'}
                        </Button>
                        <Button
                            type="button"
                            onClick={() => setShareModalState(prev => ({ ...prev, isOpen: false }))}
                            className="bg-slate-900 hover:bg-slate-800 text-white text-xs h-9 px-4"
                        >
                            {__('general.done') || 'Done'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminSidebarLayout>
    );
}


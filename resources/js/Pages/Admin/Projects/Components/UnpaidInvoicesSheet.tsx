import React, { useState, useEffect } from 'react';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetDescription,
} from '@/Components/ui/sheet';
import { AlertCircle, Calendar, ExternalLink, Loader2 } from 'lucide-react';
import { Link } from '@inertiajs/react';
import axios from 'axios';
import { formatMoney } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import type { Project } from '@/types/project';

interface Invoice {
    id: number;
    invoice_number: string;
    amount: number;
    paid_amount: number;
    currency: string;
    currency_symbol: string;
    status: string;
    created_at: string;
    due_date: string | null;
}

interface UnpaidInvoicesSheetProps {
    project: Project | null;
    isOpen: boolean;
    onClose: () => void;
}

export function UnpaidInvoicesSheet({ project, isOpen, onClose }: UnpaidInvoicesSheetProps) {
    const [invoices, setInvoices] = useState<Invoice[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (!isOpen || !project) {
            setInvoices([]);
            return;
        }

        setLoading(true);
        setError(null);

        axios.get(`/admin/invoices/unpaid`, {
            params: {
                project_id: project.id,
                per_page: 100, // Load all unpaid invoices for this project
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then((response) => {
            const invoicesList = response.data?.invoices?.data || [];
            setInvoices(invoicesList);
        })
        .catch((err) => {
            console.error('Error fetching unpaid invoices:', err);
            setError(__('general.error_loading_invoices') || 'Error loading invoices');
        })
        .finally(() => {
            setLoading(false);
        });
    }, [isOpen, project]);

    if (!project) return null;

    return (
        <Sheet open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <SheetContent side="right" className="w-full sm:max-w-md flex flex-col h-full bg-slate-50 border-slate-200 p-0 shadow-2xl">
                <SheetHeader className="p-6 border-b border-slate-200 bg-white">
                    <SheetTitle className="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <AlertCircle className="h-5 w-5 text-red-600" />
                        {__('general.unpaid_dues') || 'Unpaid Dues'}
                    </SheetTitle>
                    <SheetDescription className="text-slate-500 text-sm mt-1">
                        <span className="font-semibold text-slate-800">{project.project_name}</span>
                        {project.client && (
                            <span className="block mt-0.5 text-xs text-slate-400">
                                {project.client.name} ({project.client.email})
                            </span>
                        )}
                    </SheetDescription>
                </SheetHeader>

                <div className="flex-1 overflow-y-auto p-6 space-y-4">
                    {loading ? (
                        <div className="flex flex-col items-center justify-center py-12 gap-2 text-slate-500">
                            <Loader2 className="h-8 w-8 animate-spin text-slate-400" />
                            <span className="text-sm font-medium">{__('general.loading') || 'Loading...'}</span>
                        </div>
                    ) : error ? (
                        <div className="p-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm flex items-start gap-2">
                            <AlertCircle className="h-4 w-4 shrink-0 mt-0.5" />
                            <span>{error}</span>
                        </div>
                    ) : invoices.length === 0 ? (
                        <div className="flex flex-col items-center justify-center py-12 text-slate-500 bg-white rounded-xl border border-slate-100 p-6 text-center">
                            <AlertCircle className="h-10 w-10 text-slate-300 mb-2" />
                            <p className="font-semibold text-slate-800 text-sm">{__('general.no_unpaid_invoices') || 'No Unpaid Invoices'}</p>
                            <p className="text-xs text-slate-400 mt-1">{__('general.project_all_invoices_paid') || 'All invoices for this project are fully paid.'}</p>
                        </div>
                    ) : (
                        <div className="space-y-3">
                            {invoices.map((invoice) => {
                                const isPartial = invoice.status === 'partially_paid';
                                const remainingAmount = invoice.amount - invoice.paid_amount;
                                
                                return (
                                    <div key={invoice.id} className="p-4 bg-white rounded-xl border border-slate-200 hover:border-slate-300 shadow-sm transition-all flex flex-col gap-3">
                                        <div className="flex items-center justify-between">
                                            <span className="font-mono text-xs font-bold text-slate-700">
                                                #{invoice.invoice_number}
                                            </span>
                                            <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold leading-5 border ${
                                                isPartial 
                                                    ? 'bg-amber-50 text-amber-700 border-amber-200' 
                                                    : 'bg-rose-50 text-rose-700 border-rose-200'
                                            }`}>
                                                {isPartial ? (__('general.partially_paid') || 'Partially Paid') : (__('general.unpaid') || 'Unpaid')}
                                            </span>
                                        </div>

                                        <div className="space-y-1">
                                            <div className="flex justify-between items-baseline">
                                                <span className="text-xs text-slate-400">{__('general.amount') || 'Amount'}</span>
                                                <span className="font-mono font-bold text-slate-800">
                                                    {formatMoney(invoice.amount, invoice.currency)}
                                                </span>
                                            </div>
                                            {isPartial && (
                                                <>
                                                    <div className="flex justify-between items-baseline text-xs">
                                                        <span className="text-slate-400">{__('general.paid') || 'Paid'}</span>
                                                        <span className="font-mono text-emerald-600 font-medium">
                                                            {formatMoney(invoice.paid_amount, invoice.currency)}
                                                        </span>
                                                    </div>
                                                    <div className="flex justify-between items-baseline text-xs border-t border-slate-100 pt-1 mt-1">
                                                        <span className="font-medium text-slate-500">{__('general.remaining') || 'Remaining'}</span>
                                                        <span className="font-mono text-rose-600 font-bold">
                                                            {formatMoney(remainingAmount, invoice.currency)}
                                                        </span>
                                                    </div>
                                                </>
                                            )}
                                        </div>

                                        <div className="flex items-center justify-between border-t border-slate-100 pt-3 text-[11px] text-slate-400">
                                            <div className="flex items-center gap-1">
                                                <Calendar className="h-3 w-3" />
                                                <span>
                                                    {invoice.due_date ? `${__('general.due')} ${invoice.due_date}` : (__('general.no_due_date') || 'No due date')}
                                                </span>
                                            </div>
                                            <Link
                                                href={`/admin/invoices/${invoice.id}`}
                                                className="inline-flex items-center gap-1 text-slate-900 font-semibold hover:underline"
                                            >
                                                <span>{__('general.view_invoice') || 'View'}</span>
                                                <ExternalLink className="h-3 w-3" />
                                            </Link>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            </SheetContent>
        </Sheet>
    );
}

import React from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, router, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import {
    ArrowLeft,
    Receipt,
    Edit,
    Copy,
    Trash2,
    RotateCcw,
    Calendar,
    User,
    Building2,
    CreditCard,
    Tag,
    FileText,
    Paperclip,
    Info,
    Link as LinkIcon,
} from 'lucide-react';
import { __ } from '@/lib/i18n';
import { formatCurrency } from '@/lib/utils';
import { useState } from 'react';

export default function CostsShow() {
    const { cost, related, business_currency_code } = usePage<any>().props;

    const [deleteOpen, setDeleteOpen] = useState(false);
    const [forceDeleteOpen, setForceDeleteOpen] = useState(false);
    const [isDeleting, setIsDeleting] = useState(false);

    const handleDelete = (force = false) => {
        setIsDeleting(true);
        router.delete(route('admin.costs.delete', cost.id) + (force ? '?force=1' : ''), {
            preserveScroll: true,
            onSuccess: () => { setIsDeleting(false); setDeleteOpen(false); setForceDeleteOpen(false); },
            onError: () => setIsDeleting(false),
        });
    };

    const handleRestore = () => {
        router.post(route('admin.costs.restore', cost.id), {}, { preserveScroll: true });
    };

    const handleDuplicate = () => {
        router.post(route('admin.costs.duplicate', cost.id));
    };

    return (
        <AdminSidebarLayout
            title={__('general.view_cost')}
            header={__('general.view_cost')}
        >
            <Head title={__('general.view_cost')} />

            <div className="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <div className="mb-6 flex items-center justify-between gap-3 flex-wrap">
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => router.visit(route('admin.costs.index'))}
                        className="hover:bg-slate-100"
                    >
                        <ArrowLeft className="w-4 h-4 me-2" />
                        {__('general.back_to_costs')}
                    </Button>
                    <div className="flex items-center gap-2">
                        {cost.deleted_at && (
                            <>
                                <Button size="sm" variant="outline" onClick={handleRestore} className="h-9">
                                    <RotateCcw className="h-4 w-4 me-1" /> {__('general.restore')}
                                </Button>
                                <Button size="sm" variant="destructive" onClick={() => setForceDeleteOpen(true)} className="h-9">
                                    <Trash2 className="h-4 w-4 me-1" /> {__('general.force_delete')}
                                </Button>
                            </>
                        )}
                        {!cost.deleted_at && (
                            <>
                                <Button size="sm" variant="outline" onClick={handleDuplicate} className="h-9">
                                    <Copy className="h-4 w-4 me-1" /> {__('general.duplicate')}
                                </Button>
                                <Button size="sm" variant="outline" asChild className="h-9">
                                    <Link href={route('admin.costs.edit', cost.id)}>
                                        <Edit className="h-4 w-4 me-1" /> {__('general.edit')}
                                    </Link>
                                </Button>
                                <Button size="sm" variant="destructive" onClick={() => setDeleteOpen(true)} className="h-9">
                                    <Trash2 className="h-4 w-4 me-1" /> {__('general.delete')}
                                </Button>
                            </>
                        )}
                    </div>
                </div>

                <Card className="border-none shadow-sm shadow-slate-200/50 mb-6">
                    <CardHeader className="pb-4 border-b border-slate-100 bg-slate-50/50 rounded-t-xl">
                        <div className="flex items-center gap-3">
                            <div className="p-2.5 bg-rose-100 text-rose-700 rounded-xl shadow-sm">
                                <Receipt className="w-5 h-5" />
                            </div>
                            <div className="flex-1">
                                <CardTitle className="text-xl font-bold text-slate-900 truncate">{cost.reason}</CardTitle>
                                <CardDescription className="text-sm mt-1 flex items-center gap-2">
                                    <Calendar className="h-3.5 w-3.5" />
                                    {new Date(cost.created_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' })}
                                    {cost.deleted_at && (
                                        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-rose-100 text-rose-700">
                                            {__('general.deleted')} · {new Date(cost.deleted_at).toLocaleDateString()}
                                        </span>
                                    )}
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-6">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div className="md:col-span-1 bg-rose-50 rounded-xl p-5">
                                <p className="text-xs font-medium uppercase tracking-wide text-slate-500">{__('general.amount')}</p>
                                <p className="text-3xl font-bold font-mono text-rose-700 mt-1">
                                    -{formatCurrency(Math.abs(cost.amount), cost.currency_code)}
                                </p>
                                {cost.currency_code !== business_currency_code && (
                                    <p className="text-xs text-slate-500 font-mono mt-1">
                                        ~ {formatCurrency(Math.abs(cost.business_amount), business_currency_code)} ({__('general.business_currency')})
                                    </p>
                                )}
                                {cost.tax_amount > 0 && (
                                    <p className="text-xs text-slate-500 mt-2">
                                        {__('general.tax')}: {formatCurrency(cost.tax_amount, cost.currency_code)} ({cost.tax_rate}%)
                                    </p>
                                )}
                                {cost.is_billable && (
                                    <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-amber-100 text-amber-700 mt-3">
                                        {__('general.billable')}
                                    </span>
                                )}
                            </div>

                            <div className="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <Field icon={<Tag className="h-4 w-4" />} label={__('general.category')}>
                                    {cost.category ? cost.category_label : <span className="text-slate-400">—</span>}
                                </Field>
                                <Field icon={<CreditCard className="h-4 w-4" />} label={__('general.payment_method')}>
                                    {cost.payment_method ? (cost.payment_methods.find((p: any) => p.value === cost.payment_method)?.label ?? cost.payment_method) : <span className="text-slate-400">—</span>}
                                </Field>
                                <Field icon={<User className="h-4 w-4" />} label={__('general.client')}>
                                    {cost.user ? <Link className="text-slate-900 underline" href="#">{cost.user.name}</Link> : <span className="text-slate-400">—</span>}
                                </Field>
                                <Field icon={<Building2 className="h-4 w-4" />} label={__('general.project')}>
                                    {cost.project ? <span>{cost.project.name}</span> : <span className="text-slate-400">—</span>}
                                </Field>
                                <Field icon={<LinkIcon className="h-4 w-4" />} label={__('general.recurring_source')}>
                                    {cost.recurring_sources.length > 0 ? (
                                        <Link className="text-indigo-700 underline" href={route('admin.recurring_costs.view', cost.recurring_sources[0].id)}>
                                            {cost.recurring_sources[0].title}
                                        </Link>
                                    ) : <span className="text-slate-400">—</span>}
                                </Field>
                                <Field icon={<Info className="h-4 w-4" />} label={__('general.created_by')}>
                                    {cost.creator ? cost.creator.name : <span className="text-slate-400">{__('general.system')}</span>}
                                </Field>
                            </div>
                        </div>

                        {cost.notes && (
                            <div className="mt-6 border-t border-slate-100 pt-6">
                                <h4 className="text-sm font-semibold text-slate-800 mb-2 flex items-center gap-2">
                                    <FileText className="w-4 h-4 text-slate-400" />
                                    {__('general.notes')}
                                </h4>
                                <p className="text-sm text-slate-700 whitespace-pre-wrap">{cost.notes}</p>
                            </div>
                        )}

                        {cost.attachment_url && (
                            <div className="mt-6 border-t border-slate-100 pt-6">
                                <h4 className="text-sm font-semibold text-slate-800 mb-2 flex items-center gap-2">
                                    <Paperclip className="w-4 h-4 text-slate-400" />
                                    {__('general.attachment')}
                                </h4>
                                {/\.(jpg|jpeg|png|webp)$/i.test(cost.attachment_path ?? '') ? (
                                    <a href={cost.attachment_url} target="_blank" rel="noreferrer">
                                        <img src={cost.attachment_url} alt="attachment" className="max-h-64 rounded-lg border border-slate-200" />
                                    </a>
                                ) : (
                                    <a href={cost.attachment_url} target="_blank" rel="noreferrer" className="text-slate-700 underline text-sm">
                                        {cost.attachment_path}
                                    </a>
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card className="border-none shadow-sm shadow-slate-200/50">
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base font-semibold">{__('general.related_entries')}</CardTitle>
                        <CardDescription>{__('general.recent_same_user_or_project')}</CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        {related.length === 0 ? (
                            <p className="px-6 py-8 text-sm text-slate-500 text-center">{__('general.no_related_entries')}</p>
                        ) : (
                            <div className="divide-y divide-slate-100">
                                {related.map((r: any) => (
                                    <div key={r.id} className="flex items-center justify-between px-6 py-3 hover:bg-slate-50">
                                        <div>
                                            <div className="font-medium text-slate-900 text-sm">{r.reason}</div>
                                            <div className="text-xs text-slate-500">
                                                {new Date(r.created_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}
                                                {r.project && <span className="ms-2">· {r.project.name}</span>}
                                            </div>
                                        </div>
                                        <div className="text-end">
                                            <div className="font-semibold font-mono text-rose-700 text-sm">
                                                -{formatCurrency(Math.abs(r.amount), r.currency_code)}
                                            </div>
                                            {r.currency_code !== business_currency_code && (
                                                <div className="text-xs text-slate-400">
                                                    ~ {formatCurrency(Math.abs(r.business_amount), business_currency_code)}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            <ConfirmModal
                isOpen={deleteOpen}
                title={__('general.delete_cost_transaction')}
                description={__('general.delete_cost_transaction_description')}
                confirmLabel={__('general.delete')}
                variant="danger"
                onConfirm={() => handleDelete(false)}
                onCancel={() => setDeleteOpen(false)}
                loading={isDeleting}
            />

            <ConfirmModal
                isOpen={forceDeleteOpen}
                title={__('general.force_delete_permanently')}
                description={__('general.force_delete_description')}
                confirmLabel={__('general.force_delete')}
                variant="danger"
                onConfirm={() => handleDelete(true)}
                onCancel={() => setForceDeleteOpen(false)}
                loading={isDeleting}
            />
        </AdminSidebarLayout>
    );
}

const Field = ({ icon, label, children }: { icon: React.ReactNode; label: string; children: React.ReactNode }) => (
    <div>
        <p className="text-xs font-medium uppercase tracking-wide text-slate-500 flex items-center gap-1.5 mb-1">{icon} {label}</p>
        <div className="text-sm text-slate-900 font-medium">{children}</div>
    </div>
);

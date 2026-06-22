import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { useToast } from '@/Components/ui/use-toast';
import { __ } from '@/lib/i18n';
import {
    ArrowLeft,
    CheckCircle,
    XCircle,
    CreditCard,
    User as UserIcon,
    Calendar,
    Building2,
    Phone,
    Mail,
    Hash,
    Clock,
    GitBranch,
    Wallet,
} from 'lucide-react';

// ─── Types ───────────────────────────────────────────────────────────────────

interface PaymentMethodUser {
    id: number;
    name: string;
    email: string;
}

interface PaymentMethod {
    id: number;
    name: string | null;
    status: string;
    type: string | null;
    type_name: string | null;
    summary: string | null;
    details: string | null;

    // Raw fields
    bank_name: string | null;
    bank_number: string | null;
    bank: string | null;
    bank_branch: string | null;
    mobile: string | null;
    payee_email: string | null;
    ewallet_provider: string | null;
    id_number: string | null;
    currency_id: number | null;

    user: PaymentMethodUser | null;
    created_at: string;
    updated_at: string;
}

interface Props {
    paymentMethod: PaymentMethod;
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

const statusStyles: Record<string, string> = {
    pending:  'bg-yellow-100 text-yellow-700 border-yellow-200',
    active:   'bg-green-100 text-slate-900 border-green-200',
    declined: 'bg-red-100 text-red-700 border-red-200',
};

const statusLabel: Record<string, string> = {
    pending:  'Pending Review',
    active:   'Approved',
    declined: 'Declined',
};

// ─── Detail row helper ────────────────────────────────────────────────────────

function DetailRow({ icon: Icon, label, value }: {
    icon: React.ElementType;
    label: string;
    value: string | number | null | undefined;
}) {
    if (!value) return null;
    return (
        <div className="flex items-start gap-3 py-2.5 border-b border-slate-50 last:border-0">
            <div className="flex-shrink-0 h-6 w-6 flex items-center justify-center text-slate-400">
                <Icon className="h-4 w-4" />
            </div>
            <div className="flex-1 min-w-0">
                <p className="text-xs font-medium text-slate-400 uppercase tracking-wider">{label}</p>
                <p className="text-sm text-slate-700 mt-0.5 break-all">{value}</p>
            </div>
        </div>
    );
}

// ─── Component ───────────────────────────────────────────────────────────────

export default function Show({ paymentMethod }: Props) {
    const { toast } = useToast();

    const handleUpdate = (status: string) => {
        router.put(
            route('admin.payment-methods.update', paymentMethod.id),
            { status },
            {
                preserveScroll: true,
                onSuccess: () => toast({ title: `Payment method marked as ${statusLabel[status] ?? status}.` }),
                onError:   () => toast({ title: 'Update failed.', variant: 'destructive' }),
            }
        );
    };

    // Build the structured detail rows based on type
    const isBank        = paymentMethod.type === 'bank';
    const isMobile      = ['mobile_wallet', 'wallet'].includes(paymentMethod.type ?? '');
    const isPaypal      = paymentMethod.type === 'paypal';
    const isInstapay    = paymentMethod.type?.toLowerCase() === 'instapay';

    return (
        <AdminSidebarLayout title={__('general.payment_method_detail')} header="Payment Method Detail">
            <Head title={`Payment Method #${paymentMethod.id}`} />

            {/* Back */}
            <div className="mb-6">
                <Link
                    href={route('admin.payment-methods.index')}
                    className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800 transition-colors"
                >
                    <ArrowLeft className="h-4 w-4" />{__('general.back_to_payment_methods')}</Link>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">

                {/* ── Main Details Card ─────────────────────────────────── */}
                <div className="md:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-1">

                    {/* Header */}
                    <div className="flex items-start justify-between pb-4 border-b border-slate-100">
                        <div className="flex items-center gap-3">
                            <div className="h-10 w-10 rounded-lg bg-slate-50 flex items-center justify-center">
                                <CreditCard className="h-5 w-5 text-slate-900" />
                            </div>
                            <div>
                                <h2 className="text-lg font-semibold text-slate-800">
                                    {paymentMethod.type_name ?? paymentMethod.type ?? 'Payment Method'}
                                    <span className="ms-2 text-slate-400 text-sm font-normal">#{paymentMethod.id}</span>
                                </h2>
                                {paymentMethod.name && (
                                    <p className="text-sm text-slate-500">{paymentMethod.name}</p>
                                )}
                            </div>
                        </div>
                        <span className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold border ${statusStyles[paymentMethod.status] ?? 'bg-slate-100 text-slate-600 border-slate-200'}`}>
                            {statusLabel[paymentMethod.status] ?? paymentMethod.status}
                        </span>
                    </div>

                    {/* Structured fields by type */}
                    <div className="pt-2">
                        {isBank && (
                            <>
                                <DetailRow icon={Building2} label={__('general.bank_name')}       value={paymentMethod.bank_name} />
                                <DetailRow icon={Hash}      label={__('general.account_number')}  value={paymentMethod.bank_number} />
                                <DetailRow icon={UserIcon}  label={__('general.account_holder')}  value={paymentMethod.bank} />
                                <DetailRow icon={GitBranch} label={__('general.branch')}          value={paymentMethod.bank_branch} />
                                <DetailRow icon={Mail}      label={__('general.payee_email')}     value={paymentMethod.payee_email} />
                                <DetailRow icon={Hash}      label={__('general.id_number')}       value={paymentMethod.id_number} />
                            </>
                        )}

                        {isMobile && (
                            <>
                                <DetailRow icon={Phone}    label={__('general.mobile_number')}   value={paymentMethod.mobile} />
                                <DetailRow icon={UserIcon} label={__('general.account_name')}    value={paymentMethod.name} />
                                <DetailRow icon={Hash}     label={__('general.id_number')}       value={paymentMethod.id_number} />
                            </>
                        )}

                        {isPaypal && (
                            <>
                                <DetailRow icon={Mail}     label={__('general.paypal_email')}    value={paymentMethod.payee_email} />
                                <DetailRow icon={UserIcon} label={__('general.account_name')}    value={paymentMethod.name} />
                            </>
                        )}

                        {isInstapay && (
                            <>
                                <DetailRow icon={Wallet}   label={__('general.wallet_provider')} value={paymentMethod.ewallet_provider} />
                                <DetailRow icon={Mail}     label={__('general.payee_email')}     value={paymentMethod.payee_email} />
                                <DetailRow icon={Phone}    label={__('general.mobile')}          value={paymentMethod.mobile} />
                                <DetailRow icon={Hash}     label={__('general.id_number')}       value={paymentMethod.id_number} />
                            </>
                        )}

                        {/* Fallback: raw details string */}
                        {!isBank && !isMobile && !isPaypal && !isInstapay && paymentMethod.details && (
                            <div className="rounded-lg bg-slate-50 border border-slate-100 p-4">
                                <p className="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">{__('general.details')}</p>
                                <pre className="text-sm text-slate-700 whitespace-pre-wrap font-sans leading-relaxed">
                                    {paymentMethod.details}
                                </pre>
                            </div>
                        )}
                    </div>

                    {/* Timestamps */}
                    <div className="flex items-center gap-6 text-sm text-slate-400 pt-4 border-t border-slate-100">
                        <span className="flex items-center gap-1.5">
                            <Calendar className="h-3.5 w-3.5" />
                            Submitted {new Date(paymentMethod.created_at).toLocaleDateString()}
                        </span>
                        <span className="flex items-center gap-1.5">
                            <Calendar className="h-3.5 w-3.5" />
                            Updated {new Date(paymentMethod.updated_at).toLocaleDateString()}
                        </span>
                    </div>
                </div>

                {/* ── Sidebar ───────────────────────────────────────────── */}
                <div className="space-y-4">

                    {/* User card */}
                    <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <h3 className="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">{__('general.submitted_by')}</h3>
                        {paymentMethod.user ? (
                            <>
                                <div className="flex items-center gap-3">
                                    <div className="h-9 w-9 rounded-full bg-slate-50 flex items-center justify-center flex-shrink-0">
                                        <UserIcon className="h-4 w-4 text-slate-900" />
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-slate-800">{paymentMethod.user.name}</p>
                                        <p className="text-xs text-slate-500">{paymentMethod.user.email}</p>
                                    </div>
                                </div>
                                <Link
                                    href={route('admin.users.show', paymentMethod.user.id)}
                                    className="mt-3 block text-center text-xs text-slate-900 hover:text-slate-900 transition-colors"
                                >
                                    View User Profile →
                                </Link>
                            </>
                        ) : (
                            <p className="text-sm text-slate-400 italic">{__('general.user_account_deleted')}</p>
                        )}
                    </div>

                    {/* Actions card */}
                    <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-2">
                        <h3 className="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">{__('general.actions')}</h3>

                        {paymentMethod.status !== 'active' && (
                            <Button
                                className="w-full justify-center gap-2 bg-slate-900 hover:bg-slate-900 text-white"
                                onClick={() => handleUpdate('active')}
                            >
                                <CheckCircle className="h-4 w-4" />{__('general.approve_method')}</Button>
                        )}

                        {paymentMethod.status !== 'declined' && (
                            <Button
                                variant="destructive"
                                className="w-full justify-center gap-2"
                                onClick={() => handleUpdate('declined')}
                            >
                                <XCircle className="h-4 w-4" />{__('general.decline_method')}</Button>
                        )}

                        {paymentMethod.status !== 'pending' && (
                            <Button
                                variant="outline"
                                className="w-full justify-center gap-2"
                                onClick={() => handleUpdate('pending')}
                            >
                                <Clock className="h-4 w-4" />{__('general.reset_to_pending')}</Button>
                        )}
                    </div>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}

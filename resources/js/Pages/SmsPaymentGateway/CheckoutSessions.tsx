import { __ } from '@/lib/i18n';
import React from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/Components/ui/card';
import { ExternalLink, Copy, CheckCircle2, XCircle, Clock } from 'lucide-react';
import { formatMoney } from '@/lib/utils';
import { toast } from 'sonner';

interface CheckoutSession {
    id: number;
    session_id: string;
    amount: string;
    status: string;
    customer_name: string | null;
    customer_phone: string | null;
    is_test: boolean;
    created_at: string;
    expires_at: string | null;
    currency: {
        currency: string;
        symbol: string;
    } | null;
}

interface Props {
    sessions: {
        data: CheckoutSession[];
        links: any[];
    };
}

export default function CheckoutSessions({ sessions }: Props) {
    const copyToClipboard = (url: string) => {
        navigator.clipboard.writeText(url);
        toast.success(__('general.copied_to_clipboard'));
    };

    return (
        <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">{__('general.checkout_sessions')}</h2>}>
            <Head title={__('general.checkout_sessions')} />

            <div className="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center mb-8">
                    <div>
                        <h1 className="text-3xl font-bold text-slate-900">{__('general.api_checkout_sessions')}</h1>
                        <p className="mt-2 text-sm text-slate-600">{__('general.view_all_payment_sessions_created_via_your_api_keys')}</p>
                    </div>
                    <Button variant="outline" onClick={() => router.visit(route('sms-payment-gateway.index'))}>
                        {__('general.back_to_dashboard')}
                    </Button>
                </div>

                <div className="bg-white rounded-xl shadow-sm overflow-hidden border border-slate-200">
                    <table className="min-w-full divide-y divide-slate-200">
                        <thead className="bg-slate-50">
                            <tr>
                                <th scope="col" className="px-6 py-4 text-start text-xs font-semibold text-slate-500 uppercase tracking-wider">{__('general.session_id')}</th>
                                <th scope="col" className="px-6 py-4 text-start text-xs font-semibold text-slate-500 uppercase tracking-wider">{__('general.amount')}</th>
                                <th scope="col" className="px-6 py-4 text-start text-xs font-semibold text-slate-500 uppercase tracking-wider">{__('general.customer')}</th>
                                <th scope="col" className="px-6 py-4 text-start text-xs font-semibold text-slate-500 uppercase tracking-wider">{__('general.status')}</th>
                                <th scope="col" className="px-6 py-4 text-start text-xs font-semibold text-slate-500 uppercase tracking-wider">{__('general.date')}</th>
                                <th scope="col" className="px-6 py-4 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">{__('general.actions')}</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-slate-100">
                            {(sessions.data as any).length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-6 py-12 text-center text-slate-500">{__('general.no_checkout_sessions_found_create_one_using_the_api')}</td>
                                </tr>
                            ) : (
                                (sessions.data as any).map((session) => {
                                    const checkoutUrl = route('sms-gateway.checkout.show', { sessionId: session.session_id });
                                    return (
                                        <tr key={session.id} className="hover:bg-slate-50 transition-colors">
                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-mono text-slate-600">
                                                {session.session_id}
                                                {session.is_test && <Badge variant="outline" className="ms-2 bg-amber-50 text-amber-600 border-amber-200">Test</Badge>}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">
                                                {formatMoney(session.amount, session.currency?.currency)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                                {session.customer_name || '—'}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {session.status === 'complete' && (
                                                    <Badge className="bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border-emerald-200"><CheckCircle2 className="w-3 h-3 me-1"/> {__('general.paid')}</Badge>
                                                )}
                                                {session.status === 'open' && (
                                                    <Badge className="bg-blue-50 text-blue-700 hover:bg-blue-100 border-blue-200"><Clock className="w-3 h-3 me-1"/> {__('general.open')}</Badge>
                                                )}
                                                {session.status === 'expired' && (
                                                    <Badge className="bg-slate-100 text-slate-600 hover:bg-slate-200 border-slate-200"><XCircle className="w-3 h-3 me-1"/> {__('general.expired')}</Badge>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                                {new Date(session.created_at).toLocaleString()}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                                <div className="flex items-center justify-center gap-2">
                                                    <Button variant="outline" size="sm" onClick={() => copyToClipboard(checkoutUrl)} className="flex items-center gap-1">
                                                        <Copy className="w-3 h-3" />{__('general.copy_url')}
                                                    </Button>
                                                    <a href={checkoutUrl} target="_blank" rel="noreferrer" className="text-slate-400 hover:text-indigo-600 transition">
                                                        <ExternalLink className="w-4 h-4" />
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}


import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/Components/ui/card';
import { formatMoney } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import { CheckCircle2, FileText, Download, Building2, User } from 'lucide-react';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { marked } from 'marked';
import DOMPurify from 'dompurify';

const createMarkup = (content: string) => {
    return { __html: DOMPurify.sanitize(marked.parse(content || '') as string) };
};

export default function Show({ contract, invoices, project, wallet_check }: any) {
    const [clientName, setClientName] = useState('');
    const [signature, setSignature] = useState('');
    const [isSigning, setIsSigning] = useState(false);

    const handleSign = (e: any) => {
        e.preventDefault();
        setIsSigning(true);
        router.post(`/c/${contract.uuid}/sign`, {
            client_name: clientName,
            signature: signature
        }, {
            onFinish: () => setIsSigning(false)
        });
    };

    return (
        <div className="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8 font-sans">
            <Head title={`Contract: ${contract.project_name}`} />
            
            <div className="max-w-7xl mx-auto">
                {/* Header branding */}
                <div className="flex justify-between items-center mb-8">
                    <div className="flex items-center gap-3">
                        <div className="h-10 w-10 bg-slate-900 rounded flex items-center justify-center text-white font-bold text-xl shadow-lg">
                            {__('general.m')}</div>
                        <h1 className="text-2xl font-bold text-slate-900 tracking-tight">{__('general.musoftware')}</h1>
                    </div>
                    {contract.status === 'signed' && (
                        <div className="flex items-center gap-2 bg-emerald-100 text-emerald-800 px-4 py-2 rounded-full font-semibold text-sm shadow-sm border border-emerald-200">
                            <CheckCircle2 className="w-5 h-5" />
                            {__('general.signed_accepted')}</div>
                    )}
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div className="md:col-span-2 space-y-6">
                        {/* Scope of Work */}
                        <Card className="shadow-sm overflow-hidden border-slate-200">
                            <CardHeader className="bg-white border-b pb-4">
                                <CardTitle className="text-xl flex items-center gap-2">
                                    <FileText className="w-5 h-5 text-amber-500" />
                                    {__('general.project_proposal_scope')}</CardTitle>
                            </CardHeader>
                            <CardContent className="pt-6 prose prose-slate max-w-none">
                                <h3>{contract.project_name}</h3>
                                {contract.description ? (
                                    <div 
                                        className="prose prose-slate max-w-none text-slate-600 prose-p:my-2 prose-headings:mb-2 prose-headings:mt-4 prose-ul:my-2 prose-pre:bg-transparent prose-pre:text-slate-600 prose-pre:p-0 prose-pre:m-0 prose-pre:font-sans prose-code:text-slate-600 prose-code:font-sans prose-code:bg-transparent prose-code:before:content-none prose-code:after:content-none prose-pre:whitespace-pre-wrap" 
                                        dangerouslySetInnerHTML={createMarkup(contract.description)} 
                                    />
                                ) : (
                                    <p className="text-slate-500 italic">No general description provided.</p>
                                )}

                                {contract.content?.key_features?.length > 0 && (
                                    <>
                                        <h4 className="text-slate-900 font-semibold mt-6 mb-3">{__('general.key_deliverables_features')}</h4>
                                        <ul className="space-y-2">
                                            {contract.content.key_features.map((feature: string, idx: number) => (
                                                <li key={idx} className="flex items-start gap-2 text-slate-700">
                                                    <div className="mt-1.5 w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0"></div>
                                                    <span>{feature}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    </>
                                )}
                            </CardContent>
                        </Card>

                        {/* Financials & Milestones */}
                        <Card className="shadow-sm overflow-hidden border-slate-200">
                            <CardHeader className="bg-white border-b pb-4">
                                <CardTitle className="text-xl">{__('general.financial_summary_milestones')}</CardTitle>
                            </CardHeader>
                            <CardContent className="pt-6">
                                <div className="bg-slate-50 p-6 rounded-lg mb-6 border border-slate-100 flex justify-between items-center">
                                    <div>
                                        <p className="text-sm font-medium text-slate-500 uppercase tracking-wide">{__('general.total_investment')}</p>
                                        <p className="text-3xl font-bold text-slate-900 mt-1">
                                            {formatMoney(contract.total_amount, contract.currency)}
                                        </p>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-xs font-bold text-emerald-600 uppercase tracking-wide">الدفعة الأولى (50% Deposit)</p>
                                        <p className="text-xl font-bold text-emerald-700 mt-1">
                                            {formatMoney(contract.deposit_amount || (contract.total_amount * 0.5), contract.currency)}
                                        </p>
                                    </div>
                                </div>

                                {contract.content?.pricing_items?.length > 0 && (
                                    <div className="mb-6">
                                        <h4 className="font-semibold text-slate-900 mb-4">{__('general.investment_breakdown')}</h4>
                                        <div className="overflow-x-auto">
                                            <table className="w-full text-start border-collapse">
                                                <thead>
                                                    <tr className="border-b border-slate-200 text-sm text-slate-500">
                                                        <th className="pb-3 font-medium">{__('general.item')}</th>
                                                        <th className="pb-3 font-medium text-end">{__('general.price')}</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="text-sm">
                                                    {contract.content.pricing_items.map((item: any, idx: number) => (
                                                        <tr key={idx} className="border-b border-slate-100 last:border-0">
                                                            <td className="py-3">
                                                                <p className="font-medium text-slate-900">{item.item}</p>
                                                                {item.description && <p className="text-slate-500 text-xs mt-0.5">{item.description}</p>}
                                                            </td>
                                                            <td className="py-3 text-end font-medium text-slate-900">{formatMoney(item.price || 0, contract.currency)}</td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                )}

                                <div>
                                    <h4 className="font-semibold text-slate-900 mb-2">{__('general.payment_terms')}</h4>
                                    <p className="whitespace-pre-line text-slate-600 text-sm bg-white p-4 border border-slate-200 rounded">
                                        {contract.payment_terms || 'Standard payment terms apply.'}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Terms & Conditions */}
                        {contract.content?.terms && (
                            <Card className="shadow-sm overflow-hidden border-slate-200">
                                <CardHeader className="bg-white border-b pb-4">
                                    <CardTitle className="text-xl">{__('general.terms_conditions')}</CardTitle>
                                </CardHeader>
                                <CardContent className="pt-6">
                                    <div className="prose prose-sm prose-slate max-w-none whitespace-pre-line text-slate-600">
                                        {contract.content.terms}
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    <div className="space-y-6">
                        {/* Status & Actions */}
                        <Card className="shadow-sm border-slate-200 sticky top-6">
                            <CardHeader className="bg-white border-b pb-4">
                                <CardTitle className="text-xl">{__('general.contract_status')}</CardTitle>
                            </CardHeader>
                            <CardContent className="pt-6">
                                {contract.status === 'signed' ? (
                                    <div className="space-y-4">
                                        <div className="bg-emerald-50 text-emerald-800 p-4 rounded-lg border border-emerald-100">
                                            <p className="font-bold text-emerald-900 mb-1">{__('general.contract_executed')}</p>
                                            <p className="text-sm">Signed on {new Date(contract.signed_at).toLocaleDateString()}</p>
                                        </div>
                                        
                                        <div>
                                            <p className="text-xs text-slate-500 uppercase tracking-wide font-semibold mb-1">{__('general.client_signature')}</p>
                                            <div className="font-signature text-2xl text-slate-900 p-4 bg-slate-50 border border-slate-200 rounded">
                                                {contract.content?.client_signature || contract.client_name}
                                            </div>
                                            <p className="text-sm text-slate-600 mt-2">{contract.client_name}</p>
                                        </div>

                                        <Button variant="outline" className="w-full gap-2 mt-4" onClick={() => window.print()}>
                                            <Download className="w-4 h-4" /> {__('general.download_pdf')}</Button>
                                    </div>
                                ) : !wallet_check?.is_logged_in ? (
                                    <div className="space-y-4 text-center p-4 bg-amber-50 rounded-xl border border-amber-200">
                                        <Building2 className="w-10 h-10 text-amber-600 mx-auto" />
                                        <p className="font-bold text-slate-900 text-sm">تطلب توقيع العقد تسجيل الدخول</p>
                                        <p className="text-xs text-slate-600">يرجى تسجيل الدخول بحسابك لموافاة توقيع العقد وسداد الدفعة الأولى توماتيكياً من المحفظة.</p>
                                        <Button asChild className="w-full bg-slate-900 text-white font-bold rounded-full">
                                            <a href="/login">تسجيل الدخول / إنشاء حساب</a>
                                        </Button>
                                    </div>
                                ) : !wallet_check?.has_sufficient_balance ? (
                                    <div className="space-y-4 p-4 bg-rose-50 rounded-xl border-2 border-rose-200">
                                        <div className="flex items-center gap-2 text-rose-700 font-bold text-sm">
                                            <span>⚠️ رصيد المحفظة غير كافٍ</span>
                                        </div>
                                        <p className="text-xs text-rose-800 leading-relaxed font-medium">
                                            رصيدك الحالي هو <strong>{wallet_check.user_balance} {wallet_check.currency_symbol}</strong>.
                                            المبلغ المطلوب لسداد الدفعة الأولى (50%) هو <strong>{wallet_check.deposit_amount} {wallet_check.currency_symbol}</strong>.
                                            الخصم المتبقي للشحن: <strong className="text-rose-900">{wallet_check.missing_amount} {wallet_check.currency_symbol}</strong>.
                                        </p>
                                        <Button asChild className="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs py-5">
                                            <a href="/wallet">شحن المحفظة الآن 💳</a>
                                        </Button>
                                    </div>
                                ) : (
                                    <form onSubmit={handleSign} className="space-y-4">
                                        <div className="bg-emerald-50 text-emerald-800 p-4 rounded-lg border border-emerald-100 mb-4 text-xs font-medium">
                                            ✓ رصيد محفظتك يكفي لسداد الدفعة الأولى (50%). سيتم الخصم تلقائياً وبدء عمل محرك الـ AI فور التوقيع.
                                        </div>
                                        
                                        <div>
                                            <Label htmlFor="client_name">الاسم الكامل / ممثل الشركة</Label>
                                            <Input 
                                                id="client_name" 
                                                value={clientName}
                                                onChange={e => setClientName(e.target.value)}
                                                required 
                                                placeholder="مثال: محمود أحمد"
                                                className="mt-1 text-sm"
                                            />
                                        </div>
                                        <div>
                                            <Label htmlFor="signature">التوقيع الرقمي (اكتب اسمك)</Label>
                                            <Input 
                                                id="signature" 
                                                value={signature}
                                                onChange={e => setSignature(e.target.value)}
                                                required 
                                                placeholder="اكتب اسمك لتأكيد التوقيع"
                                                className="mt-1 font-signature text-lg"
                                            />
                                        </div>
                                        <Button type="submit" className="w-full bg-slate-900 hover:bg-slate-800 text-white font-extrabold py-6 rounded-xl text-xs uppercase tracking-wider shadow-lg" disabled={isSigning || !clientName || !signature}>
                                            {isSigning ? 'جاري التوقيع والسداد...' : 'قبول العقد وسداد الدفعة الأولى (50%)'}
                                        </Button>
                                    </form>
                                )}
                            </CardContent>
                        </Card>

                        {/* Invoices linked to this contract */}
                        {invoices && invoices.length > 0 && (
                            <Card className="shadow-sm border-slate-200">
                                <CardHeader className="bg-slate-50 border-b pb-4">
                                    <CardTitle className="text-md">{__('general.related_invoices')}</CardTitle>
                                </CardHeader>
                                <div className="divide-y divide-slate-100">
                                    {invoices.map(invoice => (
                                        <div key={invoice.id} className="p-4 flex items-center justify-between hover:bg-slate-50">
                                            <div>
                                                <p className="font-medium text-sm text-slate-900">
                                                    {invoice.items[0]?.item || `Invoice #${invoice.id}`}
                                                </p>
                                                <div className="flex items-center gap-2 mt-1">
                                                    <span className={`text-xs px-2 py-0.5 rounded-full font-medium
                                                        ${invoice.status === 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'}
                                                    `}>
                                                        {invoice.status.toUpperCase()}
                                                    </span>
                                                    <span className="text-xs text-slate-500 font-medium">
                                                        {invoice.total_str}
                                                    </span>
                                                </div>
                                            </div>
                                            <Button variant="outline" size="sm" asChild>
                                                <a href={`/billing/invoices/${invoice.uuid}/pay`}>{__('general.view')}</a>
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
            
            <style dangerouslySetInnerHTML={{__html: `
                @import url('https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600&display=swap');
                .font-signature {
                    font-family: 'Dancing Script', cursive;
                }
                @media print {
                    body { background: white; }
                    .max-w-4xl { max-w: 100%; margin: 0; padding: 0; }
                    .shadow-sm { box-shadow: none !important; border: 1px solid #e2e8f0; }
                    button, .sticky { display: none !important; }
                }
            `}} />
        </div>
    );
}

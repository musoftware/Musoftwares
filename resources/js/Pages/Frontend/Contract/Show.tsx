import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/Components/ui/card';
import { formatMoney } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import { CheckCircle2, FileText, Download, Building2, User, AlertTriangle, CreditCard } from 'lucide-react';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import ApplicationLogo from '@/Components/ApplicationLogo';
import ThemeToggle from '@/Components/ThemeToggle';
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
        <div className="min-h-screen bg-background text-foreground py-12 px-4 sm:px-6 lg:px-8 font-sans transition-colors duration-200">
            <Head title={`Contract: ${contract.project_name}`} />
            
            <div className="max-w-7xl mx-auto">
                {/* Header branding */}
                <div className="flex justify-between items-center mb-8">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center p-2">
                            <ApplicationLogo className="w-full h-full object-contain" />
                        </div>
                        <h1 className="text-2xl font-bold text-foreground tracking-tight">{__('general.musoftware')}</h1>
                    </div>
                    <div className="flex items-center gap-3">
                        {contract.status === 'signed' && (
                            <div className="flex items-center gap-2 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 px-4 py-2 rounded-full font-semibold text-sm shadow-sm border border-emerald-500/20">
                                <CheckCircle2 className="w-5 h-5" />
                                {__('general.signed_accepted')}
                            </div>
                        )}
                        <ThemeToggle className="h-9 w-9" />
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div className="md:col-span-2 space-y-6">
                        {/* Scope of Work */}
                        <Card className="shadow-sm overflow-hidden border-border bg-card text-card-foreground">
                            <CardHeader className="bg-muted/20 border-b border-border pb-4">
                                <CardTitle className="text-xl flex items-center gap-2 text-foreground">
                                    <FileText className="w-5 h-5 text-primary" />
                                    {__('general.project_proposal_scope')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="pt-6 prose dark:prose-invert max-w-none text-foreground">
                                <h3 className="text-foreground font-bold">{contract.project_name}</h3>
                                {contract.description ? (
                                    <div 
                                        className="prose dark:prose-invert max-w-none text-muted-foreground prose-p:my-2 prose-headings:mb-2 prose-headings:mt-4 prose-ul:my-2 prose-pre:bg-transparent prose-pre:text-muted-foreground prose-pre:p-0 prose-pre:m-0 prose-pre:font-sans prose-code:text-muted-foreground prose-code:font-sans prose-code:bg-transparent prose-code:before:content-none prose-code:after:content-none prose-pre:whitespace-pre-wrap" 
                                        dangerouslySetInnerHTML={createMarkup(contract.description)} 
                                    />
                                ) : (
                                    <p className="text-muted-foreground italic">No general description provided.</p>
                                )}

                                {contract.content?.key_features?.length > 0 && (
                                    <>
                                        <h4 className="text-foreground font-semibold mt-6 mb-3">{__('general.key_deliverables_features')}</h4>
                                        <ul className="space-y-2">
                                            {contract.content.key_features.map((feature: string, idx: number) => (
                                                <li key={idx} className="flex items-start gap-2 text-muted-foreground">
                                                    <div className="mt-1.5 w-1.5 h-1.5 rounded-full bg-primary shrink-0"></div>
                                                    <span>{feature}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    </>
                                )}
                            </CardContent>
                        </Card>

                        {/* Financials & Milestones */}
                        <Card className="shadow-sm overflow-hidden border-border bg-card text-card-foreground">
                            <CardHeader className="bg-muted/20 border-b border-border pb-4">
                                <CardTitle className="text-xl text-foreground">{__('general.financial_summary_milestones')}</CardTitle>
                            </CardHeader>
                            <CardContent className="pt-6">
                                <div className="bg-muted/30 dark:bg-slate-800/40 p-6 rounded-2xl mb-6 border border-border flex justify-between items-center">
                                    <div>
                                        <p className="text-sm font-medium text-muted-foreground uppercase tracking-wide">{__('general.total_investment')}</p>
                                        <p className="text-3xl font-bold text-foreground mt-1">
                                            {formatMoney(contract.total_amount, contract.currency)}
                                        </p>
                                    </div>
                                    <div className="text-end">
                                        <p className="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wide">الدفعة الأولى (50% Deposit)</p>
                                        <p className="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">
                                            {formatMoney(contract.deposit_amount || (contract.total_amount * 0.5), contract.currency)}
                                        </p>
                                    </div>
                                </div>

                                {contract.content?.pricing_items?.length > 0 && (
                                    <div className="mb-6">
                                        <h4 className="font-semibold text-foreground mb-4">{__('general.investment_breakdown')}</h4>
                                        <div className="overflow-x-auto">
                                            <table className="w-full text-start border-collapse">
                                                <thead>
                                                    <tr className="border-b border-border text-sm text-muted-foreground">
                                                        <th className="pb-3 font-medium">{__('general.item')}</th>
                                                        <th className="pb-3 font-medium text-end">{__('general.price')}</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="text-sm">
                                                    {contract.content.pricing_items.map((item: any, idx: number) => (
                                                        <tr key={idx} className="border-b border-border last:border-0">
                                                            <td className="py-3">
                                                                <p className="font-medium text-foreground">{item.item}</p>
                                                                {item.description && <p className="text-muted-foreground text-xs mt-0.5">{item.description}</p>}
                                                            </td>
                                                            <td className="py-3 text-end font-medium text-foreground">{formatMoney(item.price || 0, contract.currency)}</td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                )}

                                <div>
                                    <h4 className="font-semibold text-foreground mb-2">{__('general.payment_terms')}</h4>
                                    <p className="whitespace-pre-line text-muted-foreground text-sm bg-muted/20 p-4 border border-border rounded-xl">
                                        {contract.payment_terms || 'Standard payment terms apply.'}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Terms & Conditions */}
                        {contract.content?.terms && (
                            <Card className="shadow-sm overflow-hidden border-border bg-card text-card-foreground">
                                <CardHeader className="bg-muted/20 border-b border-border pb-4">
                                    <CardTitle className="text-xl text-foreground">{__('general.terms_conditions')}</CardTitle>
                                </CardHeader>
                                <CardContent className="pt-6">
                                    <div className="prose prose-sm dark:prose-invert max-w-none whitespace-pre-line text-muted-foreground">
                                        {contract.content.terms}
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    <div className="space-y-6">
                        {/* Status & Actions */}
                        <Card className="shadow-sm border-border bg-card text-card-foreground sticky top-6">
                            <CardHeader className="bg-muted/20 border-b border-border pb-4">
                                <CardTitle className="text-xl text-foreground">{__('general.contract_status')}</CardTitle>
                            </CardHeader>
                            <CardContent className="pt-6">
                                {contract.status === 'signed' ? (
                                    <div className="space-y-4">
                                        <div className="bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 p-4 rounded-xl border border-emerald-500/20">
                                            <p className="font-bold text-foreground mb-1">{__('general.contract_executed')}</p>
                                            <p className="text-sm text-muted-foreground">Signed on {new Date(contract.signed_at).toLocaleDateString()}</p>
                                        </div>
                                        
                                        <div>
                                            <p className="text-xs text-muted-foreground uppercase tracking-wide font-semibold mb-1">{__('general.client_signature')}</p>
                                            <div className="font-signature text-2xl text-foreground p-4 bg-muted/30 border border-border rounded-xl">
                                                {contract.content?.client_signature || contract.client_name}
                                            </div>
                                            <p className="text-sm text-muted-foreground mt-2">{contract.client_name}</p>
                                        </div>

                                        <Button variant="outline" className="w-full gap-2 mt-4 border-border text-foreground hover:bg-muted" onClick={() => window.print()}>
                                            <Download className="w-4 h-4" /> {__('general.download_pdf')}
                                        </Button>
                                    </div>
                                ) : !wallet_check?.is_logged_in ? (
                                    <div className="space-y-4 text-center p-4 bg-amber-500/10 rounded-xl border border-amber-500/20">
                                        <Building2 className="w-10 h-10 text-amber-500 mx-auto" />
                                        <p className="font-bold text-foreground text-sm">تطلب توقيع العقد تسجيل الدخول</p>
                                        <p className="text-xs text-muted-foreground">يرجى تسجيل الدخول بحسابك لموافاة توقيع العقد وسداد الدفعة الأولى تلقائياً من المحفظة.</p>
                                        <Button asChild className="w-full bg-primary hover:bg-primary/90 text-primary-foreground font-bold rounded-full">
                                            <a href="/login">تسجيل الدخول / إنشاء حساب</a>
                                        </Button>
                                    </div>
                                ) : !wallet_check?.has_sufficient_balance ? (
                                    <div className="space-y-4 p-4 bg-rose-500/10 rounded-xl border border-rose-500/20">
                                        <div className="flex items-center gap-2 text-rose-600 dark:text-rose-400 font-bold text-sm">
                                            <AlertTriangle className="w-4 h-4" />
                                            <span>رصيد المحفظة غير كافٍ</span>
                                        </div>
                                        <p className="text-xs text-muted-foreground leading-relaxed font-medium">
                                            رصيدك الحالي هو <strong className="text-foreground">{wallet_check.user_balance} {wallet_check.currency_symbol}</strong>.
                                            المبلغ المطلوب لسداد الدفعة الأولى (50%) هو <strong className="text-foreground">{wallet_check.deposit_amount} {wallet_check.currency_symbol}</strong>.
                                            الخصم المتبقي للشحن: <strong className="text-rose-600 dark:text-rose-400">{wallet_check.missing_amount} {wallet_check.currency_symbol}</strong>.
                                        </p>
                                        <Button asChild className="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs py-5">
                                            <a href="/wallet" className="inline-flex items-center justify-center gap-1.5">
                                                <CreditCard className="w-4 h-4" />
                                                <span>شحن المحفظة الآن</span>
                                            </a>
                                        </Button>
                                    </div>
                                ) : (
                                    <form onSubmit={handleSign} className="space-y-4">
                                        <div className="bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 p-4 rounded-xl border border-emerald-500/20 mb-4 text-xs font-medium">
                                            رصيد محفظتك يكفي لسداد الدفعة الأولى (50%). سيتم الخصم تلقائياً وبدء عمل محرك الـ AI فور التوقيع.
                                        </div>
                                        
                                        <div>
                                            <Label htmlFor="client_name" className="text-foreground text-xs font-semibold">الاسم الكامل / ممثل الشركة</Label>
                                            <Input 
                                                id="client_name" 
                                                value={clientName}
                                                onChange={e => setClientName(e.target.value)}
                                                required 
                                                placeholder="مثال: محمود أحمد"
                                                className="mt-1 text-sm bg-background text-foreground border-input"
                                            />
                                        </div>
                                        <div>
                                            <Label htmlFor="signature" className="text-foreground text-xs font-semibold">التوقيع الرقمي (اكتب اسمك)</Label>
                                            <Input 
                                                id="signature" 
                                                value={signature}
                                                onChange={e => setSignature(e.target.value)}
                                                required 
                                                placeholder="اكتب اسمك لتأكيد التوقيع"
                                                className="mt-1 font-signature text-lg bg-background text-foreground border-input"
                                            />
                                        </div>
                                        <Button type="submit" className="w-full bg-primary hover:bg-primary/90 text-primary-foreground font-extrabold py-6 rounded-xl text-xs uppercase tracking-wider shadow-lg" disabled={isSigning || !clientName || !signature}>
                                            {isSigning ? 'جاري التوقيع والسداد...' : 'قبول العقد وسداد الدفعة الأولى (50%)'}
                                        </Button>
                                    </form>
                                )}
                            </CardContent>
                        </Card>

                        {/* Invoices linked to this contract */}
                        {invoices && invoices.length > 0 && (
                            <Card className="shadow-sm border-border bg-card text-card-foreground">
                                <CardHeader className="bg-muted/20 border-b border-border pb-4">
                                    <CardTitle className="text-md text-foreground">{__('general.related_invoices')}</CardTitle>
                                </CardHeader>
                                <div className="divide-y divide-border">
                                    {invoices.map((invoice: any) => (
                                        <div key={invoice.id} className="p-4 flex items-center justify-between hover:bg-muted/30 transition-colors">
                                            <div>
                                                <p className="font-medium text-sm text-foreground">
                                                    {invoice.items?.[0]?.item || `Invoice #${invoice.id}`}
                                                </p>
                                                <div className="flex items-center gap-2 mt-1">
                                                    <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${
                                                        invoice.status === 'paid' 
                                                            ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' 
                                                            : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20'
                                                    }`}>
                                                        {invoice.status?.toUpperCase()}
                                                    </span>
                                                    <span className="text-xs text-muted-foreground font-medium">
                                                        {invoice.total_str}
                                                    </span>
                                                </div>
                                            </div>
                                            <Button variant="outline" size="sm" asChild className="border-border text-foreground hover:bg-muted">
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

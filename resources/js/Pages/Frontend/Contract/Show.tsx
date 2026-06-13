import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/Components/ui/card';
import { formatMoney } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import { CheckCircle2, FileText, Download, Building2, User } from 'lucide-react';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

export default function Show({ contract, invoices, project }) {
    const [clientName, setClientName] = useState('');
    const [signature, setSignature] = useState('');
    const [isSigning, setIsSigning] = useState(false);

    const handleSign = (e) => {
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
            
            <div className="max-w-4xl mx-auto">
                {/* Header branding */}
                <div className="flex justify-between items-center mb-8">
                    <div className="flex items-center gap-3">
                        <div className="h-10 w-10 bg-indigo-600 rounded flex items-center justify-center text-white font-bold text-xl shadow-lg">
                            M
                        </div>
                        <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Musoftware</h1>
                    </div>
                    {contract.status === 'signed' && (
                        <div className="flex items-center gap-2 bg-emerald-100 text-emerald-800 px-4 py-2 rounded-full font-semibold text-sm shadow-sm border border-emerald-200">
                            <CheckCircle2 className="w-5 h-5" />
                            Signed & Accepted
                        </div>
                    )}
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div className="md:col-span-2 space-y-6">
                        {/* Scope of Work */}
                        <Card className="shadow-sm overflow-hidden border-slate-200">
                            <CardHeader className="bg-white border-b pb-4">
                                <CardTitle className="text-xl flex items-center gap-2">
                                    <FileText className="w-5 h-5 text-indigo-500" />
                                    Project Proposal & Scope
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="pt-6 prose prose-slate max-w-none">
                                <h3>{contract.project_name}</h3>
                                <p className="whitespace-pre-line text-slate-600">
                                    {contract.description || 'No general description provided.'}
                                </p>

                                {contract.content?.key_features?.length > 0 && (
                                    <>
                                        <h4 className="text-slate-900 font-semibold mt-6 mb-3">Key Deliverables & Features</h4>
                                        <ul className="space-y-2">
                                            {contract.content.key_features.map((feature, idx) => (
                                                <li key={idx} className="flex items-start gap-2 text-slate-700">
                                                    <div className="mt-1.5 w-1.5 h-1.5 rounded-full bg-indigo-500 shrink-0"></div>
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
                                <CardTitle className="text-xl">Financial Summary & Milestones</CardTitle>
                            </CardHeader>
                            <CardContent className="pt-6">
                                <div className="bg-slate-50 p-6 rounded-lg mb-6 border border-slate-100 flex justify-between items-center">
                                    <div>
                                        <p className="text-sm font-medium text-slate-500 uppercase tracking-wide">Total Investment</p>
                                        <p className="text-3xl font-bold text-slate-900 mt-1">
                                            {formatMoney(contract.total_amount, contract.currency)}
                                        </p>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-sm font-medium text-slate-500 uppercase tracking-wide">Duration</p>
                                        <p className="text-xl font-semibold text-slate-900 mt-1">
                                            {contract.content?.duration || 'TBD'}
                                        </p>
                                    </div>
                                </div>

                                {contract.content?.pricing_items?.length > 0 && (
                                    <div className="mb-6">
                                        <h4 className="font-semibold text-slate-900 mb-4">Investment Breakdown</h4>
                                        <div className="overflow-x-auto">
                                            <table className="w-full text-left border-collapse">
                                                <thead>
                                                    <tr className="border-b border-slate-200 text-sm text-slate-500">
                                                        <th className="pb-3 font-medium">Item</th>
                                                        <th className="pb-3 font-medium text-right">Hours/Qty</th>
                                                        <th className="pb-3 font-medium text-right">Rate</th>
                                                        <th className="pb-3 font-medium text-right">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="text-sm">
                                                    {contract.content.pricing_items.map((item, idx) => (
                                                        <tr key={idx} className="border-b border-slate-100 last:border-0">
                                                            <td className="py-3">
                                                                <p className="font-medium text-slate-900">{item.item}</p>
                                                                {item.description && <p className="text-slate-500 text-xs mt-0.5">{item.description}</p>}
                                                            </td>
                                                            <td className="py-3 text-right text-slate-600">{item.hours || '-'}</td>
                                                            <td className="py-3 text-right text-slate-600">{formatMoney(item.hourly_rate || 0, contract.currency)}</td>
                                                            <td className="py-3 text-right font-medium text-slate-900">{formatMoney(item.total || 0, contract.currency)}</td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                )}

                                <div>
                                    <h4 className="font-semibold text-slate-900 mb-2">Payment Terms</h4>
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
                                    <CardTitle className="text-xl">Terms & Conditions</CardTitle>
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
                            <CardHeader className="bg-slate-900 text-white rounded-t-lg">
                                <CardTitle className="text-lg">Contract Status</CardTitle>
                            </CardHeader>
                            <CardContent className="pt-6">
                                {contract.status === 'signed' ? (
                                    <div className="space-y-4">
                                        <div className="bg-emerald-50 text-emerald-800 p-4 rounded-lg border border-emerald-100">
                                            <p className="font-bold text-emerald-900 mb-1">Contract Executed</p>
                                            <p className="text-sm">Signed on {new Date(contract.signed_at).toLocaleDateString()}</p>
                                        </div>
                                        
                                        <div>
                                            <p className="text-xs text-slate-500 uppercase tracking-wide font-semibold mb-1">Client Signature</p>
                                            <div className="font-signature text-2xl text-slate-900 p-4 bg-slate-50 border border-slate-200 rounded">
                                                {contract.content?.client_signature || contract.client_name}
                                            </div>
                                            <p className="text-sm text-slate-600 mt-2">{contract.client_name}</p>
                                        </div>

                                        <Button variant="outline" className="w-full gap-2 mt-4" onClick={() => window.print()}>
                                            <Download className="w-4 h-4" /> Download PDF
                                        </Button>
                                    </div>
                                ) : (
                                    <form onSubmit={handleSign} className="space-y-4">
                                        <div className="bg-blue-50 text-blue-800 p-4 rounded-lg border border-blue-100 mb-4 text-sm">
                                            Please review the scope and terms. By typing your name and signing below, you agree to the conditions outlined in this document.
                                        </div>
                                        
                                        <div>
                                            <Label htmlFor="client_name">Full Name / Company Representative</Label>
                                            <Input 
                                                id="client_name" 
                                                value={clientName}
                                                onChange={e => setClientName(e.target.value)}
                                                required 
                                                placeholder="e.g. John Doe"
                                                className="mt-1"
                                            />
                                        </div>
                                        <div>
                                            <Label htmlFor="signature">Digital Signature (Type your full name)</Label>
                                            <Input 
                                                id="signature" 
                                                value={signature}
                                                onChange={e => setSignature(e.target.value)}
                                                required 
                                                placeholder="Type your name to sign"
                                                className="mt-1 font-signature text-lg"
                                            />
                                        </div>
                                        <Button type="submit" className="w-full bg-indigo-600 hover:bg-indigo-700 text-white" disabled={isSigning || !clientName || !signature}>
                                            {isSigning ? 'Processing...' : 'Accept & Sign Contract'}
                                        </Button>
                                    </form>
                                )}
                            </CardContent>
                        </Card>

                        {/* Invoices linked to this contract */}
                        {invoices && invoices.length > 0 && (
                            <Card className="shadow-sm border-slate-200">
                                <CardHeader className="bg-slate-50 border-b pb-4">
                                    <CardTitle className="text-md">Related Invoices</CardTitle>
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
                                                <a href={`/billing/invoices/${invoice.uuid}/pay`}>View</a>
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

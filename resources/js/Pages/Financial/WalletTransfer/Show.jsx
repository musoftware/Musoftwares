import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { CheckCircle, Printer, ArrowLeft, Send, ShieldCheck, Mail, Calendar, HelpCircle } from 'lucide-react';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';

export default function Show({ transfer }) {
    const handlePrint = () => {
        window.print();
    };

    const isSender = transfer.type === 'sent';

    return (
        <AuthenticatedLayout header="Transfer Receipt">
            <Head title={`Receipt #${transfer.id}`} />

            <div className="max-w-[700px] mx-auto px-4 py-12 space-y-8 print:py-0 print:px-0 print:max-w-full font-sans">
                
                {/* Print back button (hidden during print) */}
                <div className="flex items-center justify-between print:hidden pb-6 border-b border-slate-100">
                    <Button variant="ghost" size="sm" asChild className="hover:bg-slate-100 text-slate-600">
                        <Link href={route('financial.transfer.history')}>
                            <ArrowLeft className="w-4 h-4 mr-2" /> Back to History
                        </Link>
                    </Button>
                    <div className="flex items-center gap-3">
                        <Button variant="outline" size="sm" onClick={handlePrint} className="shadow-sm border-slate-200">
                            <Printer className="w-4 h-4 mr-2" /> Print Receipt
                        </Button>
                        <Button size="sm" asChild className="shadow-sm bg-slate-900 text-white hover:bg-slate-800">
                            <Link href={route('financial.transfer.create')}>
                                <Send className="w-4 h-4 mr-2" /> New Transfer
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Printable Receipt Wrapper */}
                <div className="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden relative print:border-none print:shadow-none">
                    
                    {/* Security Check Pattern */}
                    <div className="absolute top-0 right-0 p-8 opacity-[0.02] text-slate-900 select-none pointer-events-none print:hidden">
                        <ShieldCheck className="w-64 h-64" />
                    </div>

                    <div className="bg-slate-50/50 border-b border-slate-100 text-center py-10 space-y-4 relative z-10">
                        <div className="mx-auto w-14 h-14 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mb-2 shadow-sm">
                            <CheckCircle className="w-7 h-7" />
                        </div>
                        <div className="space-y-2">
                            <Badge variant="secondary" className="font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-50 uppercase tracking-wider text-[10px] px-3 py-1">
                                Transaction Successful
                            </Badge>
                            <h2 className="text-2xl font-bold tracking-tight text-slate-900">Wallet Transfer Receipt</h2>
                            <p className="text-sm text-slate-500 font-mono">Ledger Ref: #{transfer.id}</p>
                        </div>
                    </div>
                    
                    <div className="p-8 md:p-10 space-y-8 relative z-10">
                        
                        {/* Huge Amount Display */}
                        <div className="text-center py-8 bg-slate-50 rounded-2xl border border-slate-100">
                            <span className="text-xs text-slate-400 uppercase tracking-wider font-semibold block mb-2">
                                {isSender ? 'Amount Sent (Including Fees)' : 'Amount Received'}
                            </span>
                            <div className={`text-5xl font-extrabold tracking-tight ${isSender ? 'text-slate-900' : 'text-emerald-600'}`}>
                                {isSender ? (
                                    <>
                                        -{Number(transfer.amount + transfer.fee).toFixed(2)} <span className="text-2xl font-medium text-slate-400 ml-1">{transfer.currency}</span>
                                    </>
                                ) : (
                                    <>
                                        +{Number(transfer.converted_amount).toFixed(2)} <span className="text-2xl font-medium text-emerald-400 ml-1">{transfer.converted_currency}</span>
                                    </>
                                )}
                            </div>
                        </div>

                        {/* Double Entry Flow Detail */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 border-b border-slate-100 pb-8">
                            <div className="space-y-2">
                                <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">From (Sender)</span>
                                <div className="p-4 border border-slate-100 rounded-xl bg-white shadow-sm flex flex-col">
                                    <span className="font-semibold text-sm text-slate-900">{transfer.sender_name}</span>
                                    <span className="text-xs text-slate-500 flex items-center gap-1.5 mt-1">
                                        <Mail className="w-3 h-3 text-slate-400" /> {transfer.sender_email}
                                    </span>
                                </div>
                            </div>

                            <div className="space-y-2">
                                <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">To (Receiver)</span>
                                <div className="p-4 border border-slate-100 rounded-xl bg-white shadow-sm flex flex-col">
                                    <span className="font-semibold text-sm text-slate-900">{transfer.receiver_name}</span>
                                    <span className="text-xs text-slate-500 flex items-center gap-1.5 mt-1">
                                        <Mail className="w-3 h-3 text-slate-400" /> {transfer.receiver_email}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Detailed Ledger Audit Section */}
                        <div className="space-y-5">
                            <span className="text-xs font-semibold text-slate-900 uppercase tracking-wider block flex items-center gap-2">
                                <ShieldCheck className="w-4 h-4 text-slate-400" />
                                Financial Audit Details
                            </span>
                            
                            <div className="space-y-4 text-sm bg-slate-50 p-6 rounded-2xl border border-slate-100">
                                <div className="flex justify-between items-center">
                                    <span className="text-slate-500 font-medium">Principal Transfer Amount</span>
                                    <span className="font-semibold text-slate-900">
                                        {Number(transfer.amount).toFixed(2)} {transfer.currency}
                                    </span>
                                </div>

                                {isSender && (
                                    <div className="flex justify-between items-center">
                                        <span className="text-slate-500 font-medium">Calculated Transaction Fee (1%)</span>
                                        <span className="font-semibold text-slate-900">
                                            {Number(transfer.fee).toFixed(2)} {transfer.currency}
                                        </span>
                                    </div>
                                )}

                                {transfer.currency !== transfer.converted_currency && (
                                    <>
                                        <div className="flex justify-between items-center bg-indigo-50/50 px-3 py-2 rounded-lg border border-indigo-100/50 text-xs">
                                            <span className="text-indigo-600 font-semibold">Standard Conversion Rate</span>
                                            <span className="font-bold text-indigo-700">
                                                1 {transfer.currency} = {Number(transfer.exchange_rate).toFixed(4)} {transfer.converted_currency}
                                            </span>
                                        </div>
                                        <div className="flex justify-between items-center">
                                            <span className="text-slate-500 font-medium">Converted Total Received</span>
                                            <span className="font-bold text-emerald-600">
                                                {Number(transfer.converted_amount).toFixed(2)} {transfer.converted_currency}
                                            </span>
                                        </div>
                                    </>
                                )}

                                <div className="h-px bg-slate-200 w-full my-2" />

                                <div className="flex justify-between items-center">
                                    <span className="text-slate-500 font-medium flex items-center gap-1.5">
                                        <Calendar className="w-3.5 h-3.5" /> Timestamp
                                    </span>
                                    <span className="font-medium text-slate-900">
                                        {new Date(transfer.processed_at).toLocaleString(undefined, {
                                            year: 'numeric', month: 'long', day: 'numeric',
                                            hour: '2-digit', minute: '2-digit', second: '2-digit'
                                        })}
                                    </span>
                                </div>

                                <div className="flex justify-between items-center">
                                    <span className="text-slate-500 font-medium">Transaction Status</span>
                                    <Badge variant="secondary" className="font-semibold uppercase bg-emerald-50 text-emerald-700 hover:bg-emerald-50 text-[10px] px-2">
                                        {transfer.status}
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        {/* Memo / Reason details */}
                        {transfer.reason && (
                            <div className="p-4 border border-slate-100 rounded-xl bg-slate-50 space-y-2">
                                <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Transfer Memo / Note</span>
                                <p className="text-sm italic text-slate-700">"{transfer.reason}"</p>
                            </div>
                        )}

                    </div>
                    
                    <div className="bg-slate-50/50 border-t border-slate-100 py-4 px-8 flex items-center justify-between text-xs text-slate-400 font-medium relative z-10">
                        <span className="flex items-center gap-1.5 text-slate-500">
                            <ShieldCheck className="w-4 h-4 text-emerald-500" /> Fully Secured by Musoftware Ledger
                        </span>
                        <span>Standard P2P Transfer</span>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

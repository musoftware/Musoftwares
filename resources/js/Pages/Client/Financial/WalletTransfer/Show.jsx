import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { CheckCircle, Printer, ArrowLeft, Send, ShieldCheck, Mail, Calendar, HelpCircle } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/Components/ui/card';
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

            <div className="max-w-[650px] mx-auto px-4 py-8 space-y-6 print:py-0 print:px-0 print:max-w-full">
                
                {/* Print back button (hidden during print) */}
                <div className="flex items-center justify-between print:hidden">
                    <Button variant="outline" size="sm" asChild className="shadow-none">
                        <Link href={route('financial.transfer.history')}>
                            <ArrowLeft className="w-4 h-4 me-2" /> History
                        </Link>
                    </Button>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" onClick={handlePrint} className="shadow-none gap-1.5">
                            <Printer className="w-4 h-4" /> Print
                        </Button>
                        <Button size="sm" asChild className="shadow-none gap-1.5">
                            <Link href={route('financial.transfer.create')}>
                                <Send className="w-4 h-4" /> Transfer
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Printable Receipt Card */}
                <Card className="shadow-none border-primary/10 overflow-hidden relative print:border-none print:shadow-none">
                    
                    {/* Security Check Pattern */}
                    <div className="absolute top-0 end-0 p-6 opacity-[0.03] text-primary select-none pointer-events-none print:hidden">
                        <ShieldCheck className="w-48 h-48" />
                    </div>

                    <CardHeader className="bg-muted/15 border-b border-primary/5 text-center py-8 space-y-3">
                        <div className="mx-auto w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mb-1">
                            <CheckCircle className="w-6 h-6" />
                        </div>
                        <div className="space-y-1">
                            <Badge variant="outline" className="font-medium bg-emerald-50 text-emerald-700 border-emerald-200 uppercase tracking-wider text-[10px]">{__('general.transaction_successful')}</Badge>
                            <CardTitle className="text-xl font-bold tracking-tight">{__('general.wallet_transfer_receipt')}</CardTitle>
                            <CardDescription className="text-xs">Immutable Ledger Reference ID: #{transfer.id}</CardDescription>
                        </div>
                    </CardHeader>
                    
                    <CardContent className="pt-6 space-y-6">
                        
                        {/* Huge Amount Display */}
                        <div className="text-center py-4 bg-muted/5 rounded-lg border border-primary/5">
                            <span className="text-xs text-muted-foreground uppercase tracking-wider font-semibold block mb-1">
                                {isSender ? 'Amount Sent (Including Fees)' : 'Amount Received'}
                            </span>
                            <div className={`text-3xl font-extrabold tracking-tight ${isSender ? 'text-rose-600' : 'text-emerald-600'}`}>
                                {isSender ? (
                                    <>
                                        -{Number(transfer.amount + transfer.fee).toFixed(2)} <span className="text-lg font-normal text-muted-foreground">{transfer.currency}</span>
                                    </>
                                ) : (
                                    <>
                                        +{Number(transfer.converted_amount).toFixed(2)} <span className="text-lg font-normal text-muted-foreground">{transfer.converted_currency}</span>
                                    </>
                                )}
                            </div>
                        </div>

                        {/* Double Entry Flow Detail */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 border-b pb-6 border-dashed">
                            
                            <div className="space-y-1.5">
                                <span className="text-xs font-semibold text-muted-foreground uppercase tracking-wider block">From (Sender)</span>
                                <div className="p-3 border rounded-lg bg-background flex flex-col">
                                    <span className="font-semibold text-sm text-foreground">{transfer.sender_name}</span>
                                    <span className="text-xs text-muted-foreground flex items-center gap-1 mt-0.5">
                                        <Mail className="w-3 h-3 text-muted-foreground/60" /> {transfer.sender_email}
                                    </span>
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <span className="text-xs font-semibold text-muted-foreground uppercase tracking-wider block">To (Receiver)</span>
                                <div className="p-3 border rounded-lg bg-background flex flex-col">
                                    <span className="font-semibold text-sm text-foreground">{transfer.receiver_name}</span>
                                    <span className="text-xs text-muted-foreground flex items-center gap-1 mt-0.5">
                                        <Mail className="w-3 h-3 text-muted-foreground/60" /> {transfer.receiver_email}
                                    </span>
                                </div>
                            </div>

                        </div>

                        {/* Detailed Ledger Audit Section */}
                        <div className="space-y-4">
                            <span className="text-xs font-semibold text-muted-foreground uppercase tracking-wider block">{__('general.financial_audit_details')}</span>
                            
                            <div className="space-y-2.5 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Principal Transfer Amount:</span>
                                    <span className="font-medium text-foreground">
                                        {Number(transfer.amount).toFixed(2)} {transfer.currency}
                                    </span>
                                </div>

                                {isSender && (
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Calculated Transaction Fee (1%):</span>
                                        <span className="font-medium text-foreground">
                                            {Number(transfer.fee).toFixed(2)} {transfer.currency}
                                        </span>
                                    </div>
                                )}

                                {transfer.currency !== transfer.converted_currency && (
                                    <>
                                        <div className="flex justify-between bg-primary/5 px-2.5 py-1.5 rounded text-xs">
                                            <span className="text-primary font-semibold">Standard Conversion Rate:</span>
                                            <span className="font-bold text-primary">
                                                1 {transfer.currency} = {Number(transfer.exchange_rate).toFixed(4)} {transfer.converted_currency}
                                            </span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="text-muted-foreground">Converted Total Received:</span>
                                            <span className="font-bold text-emerald-600">
                                                {Number(transfer.converted_amount).toFixed(2)} {transfer.converted_currency}
                                            </span>
                                        </div>
                                    </>
                                )}

                                <div className="flex justify-between border-t border-dashed pt-3">
                                    <span className="text-muted-foreground flex items-center gap-1">
                                        <Calendar className="w-3.5 h-3.5 text-muted-foreground" /> Timestamp:
                                    </span>
                                    <span className="font-medium text-foreground">
                                        {new Date(transfer.processed_at).toLocaleString(undefined, {
                                            year: 'numeric', month: 'long', day: 'numeric',
                                            hour: '2-digit', minute: '2-digit', second: '2-digit'
                                        })}
                                    </span>
                                </div>

                                <div className="flex justify-between border-b pb-3 border-dashed">
                                    <span className="text-muted-foreground">Transaction Status:</span>
                                    <Badge variant="outline" className="font-medium uppercase bg-emerald-50 text-emerald-700 border-emerald-200 text-[10px]">
                                        {transfer.status}
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        {/* Memo / Reason details */}
                        {transfer.reason && (
                            <div className="p-3 border border-dashed rounded-lg bg-muted/5 space-y-1">
                                <span className="text-[10px] font-bold text-muted-foreground uppercase tracking-wider block">{__('general.transfer_memo_note')}</span>
                                <p className="text-sm italic text-foreground">"{transfer.reason}"</p>
                            </div>
                        )}

                    </CardContent>
                    
                    <CardFooter className="bg-muted/5 border-t border-primary/5 py-4 flex items-center justify-between text-xs text-muted-foreground">
                        <span className="flex items-center gap-1">
                            <ShieldCheck className="w-3.5 h-3.5 text-emerald-600" />{__('general.fully_secured_by_musoftware_ledger')}</span>
                        <span>{__('general.standard_p2p_transfer')}</span>
                    </CardFooter>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}

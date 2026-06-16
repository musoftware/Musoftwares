import React from 'react';
import { Head, Link } from '@inertiajs/react';
import WebToolsLayout from '@/Layouts/WebToolsLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { CheckCircle2, Lock, ArrowRight, Smartphone, AlertCircle } from 'lucide-react';

interface WithdrawInstapayPayLinkProps {
    payLink: string;
    userId: number;
    whatsappSent: boolean;
}

export default function WithdrawInstapayPayLink({ payLink, userId, whatsappSent }: WithdrawInstapayPayLinkProps) {
    return (
        <WebToolsLayout title="Complete Payment" activeNav="explore">
            <div className="max-w-3xl mx-auto py-16 px-4 sm:px-6">
                <Card className="border-slate-200 shadow-lg overflow-hidden border-t-4 border-t-purple-500">
                    <CardContent className="p-8 sm:p-12 text-center">
                        <div className="inline-flex items-center justify-center p-4 bg-purple-100 rounded-full text-purple-600 mb-6">
                            <CheckCircle2 className="w-12 h-12" />
                        </div>
                        
                        <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight mb-4">
                            Account Created Successfully!
                        </h1>

                        {whatsappSent ? (
                            <div className="bg-slate-50 border border-slate-100 rounded-xl p-6 mb-8 flex items-start gap-4 text-left max-w-xl mx-auto">
                                <Smartphone className="w-6 h-6 text-slate-500 flex-shrink-0 mt-1" />
                                <div>
                                    <h3 className="font-bold text-slate-900">Check your WhatsApp</h3>
                                    <p className="text-slate-600 mt-1">
                                        We have sent your secure password to your WhatsApp number. You will need it to log in and authorize the payment.
                                    </p>
                                </div>
                            </div>
                        ) : (
                            <div className="bg-amber-50 border border-amber-100 rounded-xl p-6 mb-8 flex items-start gap-4 text-left max-w-xl mx-auto">
                                <AlertCircle className="w-6 h-6 text-amber-500 flex-shrink-0 mt-1" />
                                <div>
                                    <h3 className="font-bold text-amber-900">Important Notice</h3>
                                    <p className="text-amber-800 mt-1">
                                        Your account was created, but we could not send the WhatsApp message. When you click continue, please use the "Forgot Password" link to set a password for your account, or contact support.
                                    </p>
                                </div>
                            </div>
                        )}

                        <div className="space-y-4 max-w-md mx-auto">
                            <Button asChild size="lg" className="w-full h-14 text-lg bg-purple-600 hover:bg-purple-700">
                                <a href={payLink}>
                                    <Lock className="w-5 h-5 mr-2" /> Log in & Pay <ArrowRight className="w-5 h-5 ml-2" />
                                </a>
                            </Button>
                            <p className="text-sm text-slate-500">
                                You will be redirected to our secure login portal to complete your InstaPay transaction.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </WebToolsLayout>
    );
}

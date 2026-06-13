import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { CheckCircle2, XCircle } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function PaymentResult({ status, message }: { status: 'success' | 'error', message: string }) {
    const isSuccess = status === 'success';

    return (
        <AuthenticatedLayout>
            <Head title={isSuccess ? __('general.payment_successful') : __('general.payment_failed')} />

            <div className="max-w-[800px] mx-auto px-4 sm:px-6 py-12 flex justify-center">
                <Card className="w-full max-w-md shadow-sm border border-slate-100 rounded-2xl overflow-hidden">
                    <div className={`h-2 w-full ${isSuccess ? 'bg-emerald-500' : 'bg-red-500'}`} />
                    <CardHeader className="text-center pt-8 pb-4">
                        <div className="flex justify-center mb-4">
                            {isSuccess ? (
                                <CheckCircle2 className="w-16 h-16 text-emerald-500" />
                            ) : (
                                <XCircle className="w-16 h-16 text-red-500" />
                            )}
                        </div>
                        <CardTitle className="text-2xl font-bold text-slate-900 tracking-tight">
                            {isSuccess ? __('general.payment_successful') : __('general.payment_failed')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="text-center pb-8">
                        <p className="text-slate-600 font-medium">
                            {message}
                        </p>
                    </CardContent>
                    <CardFooter className="bg-slate-50 border-t border-slate-100 p-6 flex justify-center">
                        <Link href={route('billing.invoices.index')}>
                            <Button variant={isSuccess ? "default" : "outline"} className={isSuccess ? "bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl" : "rounded-xl"}>
                                {__('erp.back_to_invoices')}
                            </Button>
                        </Link>
                    </CardFooter>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}

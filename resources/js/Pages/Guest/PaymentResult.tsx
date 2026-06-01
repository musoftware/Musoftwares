import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { CheckCircle2, XCircle } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function PaymentResult({ status, message }: { status: 'success' | 'error', message: string }) {
    const isSuccess = status === 'success';

    return (
        <div className="min-h-screen bg-gray-50 flex items-center justify-center p-4">
            <Head title={isSuccess ? __('general.payment_successful') : __('general.payment_failed')} />

            <Card className="w-full max-w-md shadow-lg border-0 rounded-2xl overflow-hidden">
                <div className={`h-2 w-full ${isSuccess ? 'bg-green-500' : 'bg-red-500'}`} />
                <CardHeader className="text-center pt-8 pb-4">
                    <div className="flex justify-center mb-4">
                        {isSuccess ? (
                            <CheckCircle2 className="w-16 h-16 text-green-500" />
                        ) : (
                            <XCircle className="w-16 h-16 text-red-500" />
                        )}
                    </div>
                    <CardTitle className="text-2xl font-bold text-gray-900">
                        {isSuccess ? __('general.payment_successful') : __('general.payment_failed')}
                    </CardTitle>
                </CardHeader>
                <CardContent className="text-center pb-8">
                    <p className="text-gray-600">
                        {message}
                    </p>
                </CardContent>
                <CardFooter className="bg-gray-50 border-t p-6 flex justify-center">
                    <Link href="/">
                        <Button variant={isSuccess ? "default" : "outline"} className={isSuccess ? "bg-green-600 hover:bg-green-700" : ""}>
                            {__('general.return_to_home')}
                        </Button>
                    </Link>
                </CardFooter>
            </Card>
        </div>
    );
}

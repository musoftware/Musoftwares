import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { CheckCircle2, XCircle } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function PaymentResult({ status, message }: { status: 'success' | 'error', message: string }) {
    const isSuccess = status === 'success';

    return (
        <div className="min-h-screen bg-background flex items-center justify-center p-4">
            <Head title={isSuccess ? __('general.payment_successful') : __('general.payment_failed')} />

            <Card className="w-full max-w-md shadow-lg border border-border rounded-2xl overflow-hidden bg-card text-card-foreground">
                <div className={`h-2 w-full ${isSuccess ? 'bg-emerald-500' : 'bg-destructive'}`} />
                <CardHeader className="text-center pt-8 pb-4">
                    <div className="flex justify-center mb-4">
                        {isSuccess ? (
                            <CheckCircle2 className="w-16 h-16 text-emerald-500" />
                        ) : (
                            <XCircle className="w-16 h-16 text-destructive" />
                        )}
                    </div>
                    <CardTitle className="text-2xl font-bold text-foreground">
                        {isSuccess ? __('general.payment_successful') : __('general.payment_failed')}
                    </CardTitle>
                </CardHeader>
                <CardContent className="text-center pb-8">
                    <p className="text-muted-foreground">
                        {message}
                    </p>
                </CardContent>
                <CardFooter className="bg-muted/40 border-t border-border p-6 flex justify-center">
                    <Link href="/">
                        <Button variant={isSuccess ? "default" : "outline"} className={isSuccess ? "bg-emerald-600 hover:bg-emerald-700 text-white" : ""}>
                            {__('general.return_to_home')}
                        </Button>
                    </Link>
                </CardFooter>
            </Card>
        </div>
    );
}

import React from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { ArrowLeft, BookOpen } from 'lucide-react';

export default function Documentation() {
    return (
        <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">API Documentation</h2>}>
            <Head title="Documentation - AutoSMS" />

            <div className="py-8 md:py-12">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
                                <BookOpen className="w-6 h-6 text-indigo-600" />
                                Integration Documentation
                            </h1>
                            <p className="text-slate-500 mt-1">Learn how to connect and automate your systems with AutoSMS.</p>
                        </div>
                        <Button variant="outline" onClick={() => router.visit(route('autosms.index'))}>
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Back to Dashboard
                        </Button>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Coming Soon</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-slate-600">The full AutoSMS developer API documentation is currently being ported to the new interface. In the meantime, please check the Integration Tester to understand the webhook payload structure.</p>
                            <Button className="mt-6 bg-indigo-600 hover:bg-indigo-700" onClick={() => router.visit(route('autosms.integration-tester'))}>
                                Go to Integration Tester
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}


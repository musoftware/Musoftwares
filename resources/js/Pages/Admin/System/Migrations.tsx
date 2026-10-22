import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { __ } from '@/lib/i18n';

interface Flash {
    success?: string;
    error?: string;
}

export default function Migrations({ pendingOutput }: { pendingOutput: string }) {
    const { flash } = usePage().props as { flash?: Flash };
    const [running, setRunning] = useState(false);

    const run = () => {
        if (!confirm('Run all pending database migrations now?')) {
            return;
        }
        router.post('/admin/system/migrate', {}, {
            preserveScroll: true,
            onStart: () => setRunning(true),
            onFinish: () => setRunning(false),
        });
    };

    return (
        <AdminSidebarLayout>
            <Head title={__('admin.sidebar_migrations')} />
            <div className="p-4 sm:p-6 space-y-6 overflow-x-clip max-w-3xl">
                <Card>
                    <CardHeader>
                        <CardTitle>{__('admin.sidebar_migrations')}</CardTitle>
                        <CardDescription>Pending database changes. Take a backup before you run them on a live site.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <pre className="rounded-md border p-3 text-xs whitespace-pre-wrap break-words" dir="ltr">{pendingOutput || 'No output.'}</pre>
                        <Button onClick={run} disabled={running}>
                            {running ? 'Running...' : 'Run migrate'}
                        </Button>
                        {flash?.success && <pre className="rounded-md border border-green-300 p-3 text-xs whitespace-pre-wrap break-words" dir="ltr">{flash.success}</pre>}
                        {flash?.error && <p className="text-sm text-red-600">{flash.error}</p>}
                    </CardContent>
                </Card>
            </div>
        </AdminSidebarLayout>
    );
}

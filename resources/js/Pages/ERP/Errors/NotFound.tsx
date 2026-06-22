import React from 'react';
import { Head, Link } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { AlertCircle, ArrowLeft } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface Props {
    message: string;
    section?: string;
}

export default function NotFound({ message, section = 'overview' }: Props) {
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu(section);

    return (
        <ERPLayout title={__('general.not_found')} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>
            <Head title={__('general.not_found')} />

            <div className="max-w-7xl mx-auto px-4 py-16">
                <Card className="bg-white border border-slate-200 shadow-sm">
                    <CardContent className="p-8 flex flex-col items-center text-center space-y-6">
                        <div className="w-16 h-16 rounded-full bg-rose-50 flex items-center justify-center border border-rose-100">
                            <AlertCircle className="w-8 h-8 text-rose-600" />
                        </div>
                        
                        <div className="space-y-2">
                            <h1 className="text-xl font-bold text-slate-900">{__('general.resource_not_found')}</h1>
                            <p className="text-slate-500 text-sm">{message}</p>
                        </div>

                        <div className="pt-4">
                            <Link href={route('erp.dashboard', { section })}>
                                <Button className="gap-2 bg-slate-900 hover:bg-slate-800 text-white shadow-none">
                                    <ArrowLeft className="w-4 h-4" />{__('general.go_back_to_dashboard')}</Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </ERPLayout>
    );
}

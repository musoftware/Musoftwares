import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ClientPageLayout from './Components/ClientPageLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { EmptyState } from '@/Components/ui/EmptyState';
import { DateDisplay } from '@/Components/ui/DateDisplay';
import { __ } from '@/lib/i18n';
import { File, FileText, Download, Trash2 } from 'lucide-react';

interface Props {
    client: any;
    balance: number;
    lockedBalance: number;
    totalRevenue: number;
    unpaidRevenue: number;
    projectsCount: number;
    ticketsCount: number;
    hasTickets: boolean;
    files: any[];
}

export default function ClientFiles({
    client, balance, lockedBalance, totalRevenue, unpaidRevenue, projectsCount, ticketsCount, hasTickets, files
}: Props) {

    const handleDeleteFile = (id: number) => {
        if (confirm(__('general.are_you_sure_you_want_to_delete_this_file'))) {
            router.delete(route('erp.files.destroy', id), { preserveScroll: true });
        }
    };

    return (
        <ClientPageLayout
            client={client}
            balance={balance}
            lockedBalance={lockedBalance}
            totalRevenue={totalRevenue}
            unpaidRevenue={unpaidRevenue}
            projectsCount={projectsCount}
            ticketsCount={ticketsCount}
            hasTickets={hasTickets}
            activeTab="files"
        >
            <Card className="bg-white border border-slate-200 shadow-sm">
                <CardHeader className="pb-3 flex flex-row items-center justify-between">
                    <div>
                        <CardTitle className="text-slate-900 text-lg font-semibold">{__('general.client_files')}</CardTitle>
                        <p className="text-sm text-slate-500 mt-1">{__('general.documents_and_files_shared_with_this_client')}</p>
                    </div>
                    <Link href={route('erp.files.create') + `?type=client_${client.id}`}>
                        <Button size="sm" className="gap-1.5 shadow-none bg-primary text-white">
                            <FileText className="w-3.5 h-3.5" /> {__('general.upload_file')}
                        </Button>
                    </Link>
                </CardHeader>
                <CardContent className="p-0">
                    {files.length === 0 ? (
                        <EmptyState icon={File} title={__('general.no_files')} description={__('general.no_files_uploaded_yet')} />
                    ) : (
                        <div className="divide-y divide-slate-100 border-t border-slate-100">
                            {files.map(file => (
                                <div key={file.id} className="flex items-center justify-between px-6 py-4 hover:bg-slate-50 transition-colors">
                                    <div className="flex items-center gap-4">
                                        <div className="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                                            <File className="w-5 h-5 text-blue-500" />
                                        </div>
                                        <div>
                                            <p className="text-sm font-semibold text-slate-900">{file.name}</p>
                                            <p className="text-xs text-slate-500 mt-0.5">
                                                {(file.size / 1024 / 1024).toFixed(2)} MB · <DateDisplay date={file.created_at} />
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex gap-2">
                                        <a href={route('erp.files.show', file.id)} target="_blank" rel="noopener noreferrer">
                                            <Button size="sm" variant="ghost" className="h-8 w-8 p-0 text-slate-500 hover:text-primary">
                                                <Download className="w-4 h-4" />
                                            </Button>
                                        </a>
                                        <Button size="sm" variant="ghost" className="h-8 w-8 p-0 text-slate-500 hover:text-red-600" onClick={() => handleDeleteFile(file.id)}>
                                            <Trash2 className="w-4 h-4" />
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>
        </ClientPageLayout>
    );
}

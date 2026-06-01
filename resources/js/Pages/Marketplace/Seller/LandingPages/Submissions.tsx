import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui/table";

export default function Submissions({ service, landingPage, submissions }: any) {
    return (
        <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">{__('general.landing_page_leads')}</h2>}>
            <Head title={__('general.landing_page_leads')} />
            <div className="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 className="text-lg font-medium mb-4">{__('general.leads_submissions')}</h3>
                    <div className="border rounded-md">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Date</TableHead>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead>Data</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {submissions?.data?.length > 0 ? (
                                    submissions.data.map((sub: any) => (
                                        <TableRow key={sub.id}>
                                            <TableCell>{new Date(sub.created_at).toLocaleDateString()}</TableCell>
                                            <TableCell>{sub.name || 'N/A'}</TableCell>
                                            <TableCell>{sub.email || 'N/A'}</TableCell>
                                            <TableCell>
                                                <pre className="text-xs text-gray-500">
                                                    {JSON.stringify(sub.data, null, 2)}
                                                </pre>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={4} className="text-center py-8 text-gray-500">{__('general.no_submissions_found')}</TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

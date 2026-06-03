import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { formatMoney } from '@/lib/utils';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { FileText, Plus } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface Proposal {
    id: number;
    project_name: string;
    client_name: string | null;
    total_amount: number;
    currency: any;
    status: string;
    created_at: string;
}

interface Props {
    proposals: {
        data: Proposal[];
        links: any[];
    };
    auth: any;
}

export default function Index({ proposals, auth }: Props) {
    const businessCurrency = auth?.admin_settings?.business_currency || 'USD';

    return (
        <AuthenticatedLayout>
            <Head title={__('general.my_proposals')} />

            <div className="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center mb-8">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight text-gray-900">{__('general.project_proposals')}</h1>
                        <p className="text-gray-500 mt-1">{__('general.manage_your_ai_generated_project_estimates_and_proposals')}</p>
                    </div>
                    <Link href={route('isaas.proposals.create')}>
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />{__('general.new_estimate')}</Button>
                    </Link>
                </div>

                {(proposals.data as any).length === 0 ? (
                    <Card className="text-center py-12">
                        <CardContent>
                            <div className="mx-auto w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <FileText className="h-6 w-6 text-gray-400" />
                            </div>
                            <h3 className="text-lg font-medium text-gray-900 mb-2">{__('general.no_proposals_yet')}</h3>
                            <p className="text-gray-500 mb-6 max-w-sm mx-auto">{__('general.create_your_first_ai_generated_proposal_in_seconds_to_impress_your_clients')}</p>
                            <Link href={route('isaas.proposals.create')}>
                                <Button variant="outline">{__('general.create_proposal')}</Button>
                            </Link>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {(proposals.data as any).map((proposal) => (
                            <Card key={proposal.id} className="hover:shadow-md transition-shadow">
                                <CardHeader className="pb-3">
                                    <div className="flex justify-between items-start">
                                        <CardTitle className="text-lg truncate pr-4" title={proposal.project_name}>
                                            {proposal.project_name}
                                        </CardTitle>
                                        <Badge variant={proposal.status === 'converted' ? 'default' : proposal.status === 'sent' ? 'secondary' : 'outline'}>
                                            {proposal.status}
                                        </Badge>
                                    </div>
                                    <CardDescription>
                                        {proposal.client_name || 'No Client Specified'}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex justify-between items-center mt-2">
                                        <span className="text-sm text-gray-500">{__('general.total_estimate')}</span>
                                        <span className="font-semibold text-gray-900">
                                            {formatMoney(proposal.total_amount, proposal.currency || businessCurrency)}
                                        </span>
                                    </div>
                                    <div className="mt-6">
                                        <Link href={route('isaas.proposals.show', proposal.id)}>
                                            <Button variant="secondary" className="w-full">{__('general.view_details')}</Button>
                                        </Link>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}

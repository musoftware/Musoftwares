import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { formatMoney } from '@/lib/utils';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import { ArrowLeft, CheckCircle, FileText } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface Proposal {
    id: number;
    project_name: string;
    client_name: string | null;
    client_email: string | null;
    requirements: string;
    ai_estimate: {
        items?: { name: string; cost: number; duration_days: number }[];
        timeline_weeks?: number;
        total_cost?: number;
    };
    total_amount: number;
    currency: any;
    status: string;
    created_at: string;
}

interface Props {
    proposal: Proposal;
    auth: any;
}

export default function Show({ proposal, auth }: Props) {
    const businessCurrency = auth?.admin_settings?.business_currency;
    const items = proposal.ai_estimate?.items || [];
    
    const { post, processing } = useForm({});

    const convertToContract = () => {
        post(route('isaas.proposals.convert', proposal.id));
    };

    return (
        <AuthenticatedLayout>
            <Head title={proposal.project_name} />

            <div className="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <div className="mb-6">
                    <Link href={route('isaas.proposals.index')} className="text-sm font-medium text-gray-500 hover:text-gray-900 flex items-center">
                        <ArrowLeft className="me-1 h-4 w-4" />{__('general.back_to_proposals')}</Link>
                </div>

                <div className="flex justify-between items-start mb-8">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight text-gray-900">{proposal.project_name}</h1>
                        <div className="flex items-center space-x-4 mt-2">
                            <Badge variant={proposal.status === 'converted' ? 'default' : 'secondary'}>{proposal.status}</Badge>
                            <span className="text-sm text-gray-500">Client: {proposal.client_name || 'N/A'}</span>
                            <span className="text-sm text-gray-500">Created: {new Date(proposal.created_at).toLocaleDateString()}</span>
                        </div>
                    </div>
                    {proposal.status !== 'converted' && (
                        <Button onClick={convertToContract} disabled={processing} className="bg-black text-white hover:bg-gray-800">
                            <CheckCircle className="me-2 h-4 w-4" />
                            1-Click Convert to Contract
                        </Button>
                    )}
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div className="md:col-span-2 space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-lg">{__('general.ai_estimate_breakdown')}</CardTitle>
                                <CardDescription>Estimated timeline: {proposal.ai_estimate?.timeline_weeks || 0} weeks</CardDescription>
                            </CardHeader>
                            <CardContent>
                                {items.length > 0 ? (
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-sm text-start">
                                            <thead className="text-xs text-gray-700 uppercase bg-gray-50">
                                                <tr>
                                                    <th className="px-4 py-3 rounded-ts-lg">{__('general.line_item')}</th>
                                                    <th className="px-4 py-3 text-end">{__('general.duration')}</th>
                                                    <th className="px-4 py-3 text-end rounded-te-lg">{__('general.cost')}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {items.map((item, index) => (
                                                    <tr key={index} className="border-b">
                                                        <td className="px-4 py-4 font-medium text-gray-900">{item.name}</td>
                                                        <td className="px-4 py-4 text-end text-gray-500">{item.duration_days} days</td>
                                                        <td className="px-4 py-4 text-end">{formatMoney(item.cost, proposal.currency || businessCurrency)}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                            <tfoot>
                                                <tr className="font-bold text-gray-900">
                                                    <td className="px-4 py-4" colSpan={2}>{__('general.total_estimate')}</td>
                                                    <td className="px-4 py-4 text-end text-lg text-purple-600">
                                                        {formatMoney(proposal.total_amount, proposal.currency || businessCurrency)}
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                ) : (
                                    <p className="text-gray-500 italic">{__('general.no_line_items_provided_in_the_estimate')}</p>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-lg flex items-center">
                                    <FileText className="h-5 w-5 me-2 text-gray-400" />{__('general.original_requirements')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm text-gray-600 whitespace-pre-wrap leading-relaxed">
                                    {proposal.requirements || 'No requirements provided.'}
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}


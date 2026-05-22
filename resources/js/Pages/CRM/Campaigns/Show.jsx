import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Code, Copy, CheckCircle2 } from 'lucide-react';
import { useState } from 'react';

export default function CampaignShow({ auth, campaign }) {
    const [copied, setCopied] = useState(false);
    
    // Construct the absolute URL to the iframe endpoint
    const embedUrl = `${window.location.origin}/crm/embed/capture/${campaign.embed_token}`;
    const iframeCode = `<iframe src="${embedUrl}" width="100%" height="600" frameborder="0" style="border:none; overflow:hidden;" scrolling="no"></iframe>`;

    const copyToClipboard = () => {
        navigator.clipboard.writeText(iframeCode);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <AuthenticatedLayout user={auth.user} header={<h2 className="font-semibold text-xl text-slate-800 leading-tight">Campaign: {campaign.name}</h2>}>
            <Head title={`Campaign - ${campaign.name}`} />

            <div className="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Left Column - Details & Embed Code */}
                    <div className="lg:col-span-1 space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Campaign Details</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <p className="text-sm text-slate-500">Status</p>
                                    <p className="font-medium text-emerald-600 capitalize">{campaign.status}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-slate-500">Total Leads</p>
                                    <p className="font-medium">{campaign.leads?.length || 0}</p>
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="border-indigo-100 bg-indigo-50/50">
                            <CardHeader>
                                <CardTitle className="flex items-center text-indigo-900">
                                    <Code className="w-5 h-5 mr-2" /> Embed Code
                                </CardTitle>
                                <CardDescription className="text-indigo-700/80">
                                    Copy and paste this HTML code into your external landing page to start capturing leads for this campaign.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="bg-slate-900 rounded-md p-4 mb-4 overflow-x-auto">
                                    <code className="text-emerald-400 text-sm whitespace-pre">
                                        {iframeCode}
                                    </code>
                                </div>
                                <Button onClick={copyToClipboard} className="w-full bg-indigo-600 hover:bg-indigo-700">
                                    {copied ? <><CheckCircle2 className="w-4 h-4 mr-2" /> Copied!</> : <><Copy className="w-4 h-4 mr-2" /> Copy HTML</>}
                                </Button>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right Column - Leads */}
                    <div className="lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Captured Leads</CardTitle>
                                <CardDescription>All leads generated through this campaign's iframe.</CardDescription>
                            </CardHeader>
                            <CardContent>
                                {(!campaign.leads || campaign.leads.length === 0) ? (
                                    <div className="text-center py-12 text-slate-500 border border-dashed rounded-lg border-slate-200">
                                        No leads captured yet. Embed the form to get started.
                                    </div>
                                ) : (
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-sm text-left">
                                            <thead className="text-xs text-slate-500 uppercase bg-slate-50">
                                                <tr>
                                                    <th className="px-4 py-3 rounded-tl-lg">Name</th>
                                                    <th className="px-4 py-3">Email</th>
                                                    <th className="px-4 py-3">Status</th>
                                                    <th className="px-4 py-3 rounded-tr-lg">Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {campaign.leads.map(lead => (
                                                    <tr key={lead.id} className="border-b border-slate-100 last:border-0 hover:bg-slate-50/50">
                                                        <td className="px-4 py-3 font-medium text-slate-900">{lead.name}</td>
                                                        <td className="px-4 py-3 text-slate-600">{lead.email}</td>
                                                        <td className="px-4 py-3">
                                                            <span className="bg-blue-50 text-blue-700 px-2 py-1 rounded-full text-xs font-semibold capitalize">
                                                                {lead.status}
                                                            </span>
                                                        </td>
                                                        <td className="px-4 py-3 text-slate-500">{new Date(lead.created_at).toLocaleDateString()}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>

            </div>
        </AuthenticatedLayout>
    );
}

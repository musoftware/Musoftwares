import React, { useState } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import { Loader2, Sparkles, ArrowLeft } from 'lucide-react';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        project_name: '',
        client_name: '',
        client_email: '',
        requirements: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('isaas.proposals.estimate'));
    };

    return (
        <AuthenticatedLayout>
            <Head title="Generate AI Proposal" />

            <div className="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <div className="mb-6">
                    <Link href={route('isaas.proposals.index')} className="text-sm font-medium text-gray-500 hover:text-gray-900 flex items-center">
                        <ArrowLeft className="mr-1 h-4 w-4" />
                        Back to Proposals
                    </Link>
                </div>

                <Card className="border-0 shadow-lg bg-white overflow-hidden">
                    <div className="h-2 w-full bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>
                    <CardHeader className="pb-4">
                        <CardTitle className="text-2xl flex items-center">
                            <Sparkles className="mr-2 h-6 w-6 text-purple-500" />
                            Generate AI Estimate
                        </CardTitle>
                        <CardDescription className="text-base">
                            Describe your client's project requirements in plain text, and our AI will generate a professional cost breakdown and timeline.
                        </CardDescription>
                    </CardHeader>
                    
                    <form onSubmit={submit}>
                        <CardContent className="space-y-6">
                            <div className="space-y-4 bg-gray-50 p-6 rounded-lg border border-gray-100">
                                <div>
                                    <Label htmlFor="project_name" className="text-sm font-semibold text-gray-700">Project Name <span className="text-red-500">*</span></Label>
                                    <Input
                                        id="project_name"
                                        value={data.project_name}
                                        onChange={e => setData('project_name', e.target.value)}
                                        placeholder="e.g., E-Commerce Website Redesign"
                                        className="mt-1"
                                        required
                                    />
                                    {errors.project_name && <p className="text-sm text-red-600 mt-1">{errors.project_name}</p>}
                                </div>
                                
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <Label htmlFor="client_name" className="text-sm font-semibold text-gray-700">Client Name (Optional)</Label>
                                        <Input
                                            id="client_name"
                                            value={data.client_name}
                                            onChange={e => setData('client_name', e.target.value)}
                                            placeholder="John Doe"
                                            className="mt-1"
                                        />
                                        {errors.client_name && <p className="text-sm text-red-600 mt-1">{errors.client_name}</p>}
                                    </div>
                                    <div>
                                        <Label htmlFor="client_email" className="text-sm font-semibold text-gray-700">Client Email (Optional)</Label>
                                        <Input
                                            id="client_email"
                                            type="email"
                                            value={data.client_email}
                                            onChange={e => setData('client_email', e.target.value)}
                                            placeholder="john@example.com"
                                            className="mt-1"
                                        />
                                        {errors.client_email && <p className="text-sm text-red-600 mt-1">{errors.client_email}</p>}
                                    </div>
                                </div>
                            </div>

                            <div>
                                <Label htmlFor="requirements" className="text-base font-semibold text-gray-900 mb-2 block">Project Requirements <span className="text-red-500">*</span></Label>
                                <p className="text-sm text-gray-500 mb-3">Be as detailed as possible to get a highly accurate estimate. Mention features, platforms, integrations, and design needs.</p>
                                <Textarea
                                    id="requirements"
                                    value={data.requirements}
                                    onChange={e => setData('requirements', e.target.value)}
                                    placeholder="The client needs a Shopify store with 5 custom pages, an integration with Mailchimp, and a custom product configurator. The design should be modern and dark-themed..."
                                    className="min-h-[200px] resize-y text-base p-4"
                                    required
                                />
                                {errors.requirements && <p className="text-sm text-red-600 mt-1">{errors.requirements}</p>}
                            </div>
                            
                            {errors.error && (
                                <div className="p-4 bg-red-50 border border-red-200 rounded-md text-red-600 text-sm">
                                    {errors.error}
                                </div>
                            )}
                        </CardContent>
                        
                        <CardFooter className="bg-gray-50 border-t px-6 py-4 flex justify-between items-center">
                            <span className="text-xs text-gray-500">Takes about 15 seconds</span>
                            <Button type="submit" disabled={processing} size="lg" className="w-full sm:w-auto bg-black text-white hover:bg-gray-800">
                                {processing ? (
                                    <>
                                        <Loader2 className="mr-2 h-5 w-5 animate-spin" />
                                        Generating Estimate...
                                    </>
                                ) : (
                                    <>
                                        Generate AI Proposal
                                    </>
                                )}
                            </Button>
                        </CardFooter>
                    </form>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}

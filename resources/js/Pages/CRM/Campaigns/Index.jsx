import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Plus, Link as LinkIcon } from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";

export default function CampaignsIndex({ auth, campaigns }) {
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        description: '',
        form_title: 'Get in Touch',
        form_description: 'Fill out the form below and we will contact you shortly.',
        button_text: 'Submit',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('crm.campaigns.store'), {
            onSuccess: () => {
                setIsCreateOpen(false);
                reset();
            },
        });
    };

    return (
        <AuthenticatedLayout user={auth.user} header={<h2 className="font-semibold text-xl text-slate-800 leading-tight">Campaigns</h2>}>
            <Head title="Campaigns" />

            <div className="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <div className="flex justify-between items-center mb-6">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">Your Lead Campaigns</h1>
                        <p className="text-slate-500">Create campaigns to generate unique embed codes for your landing pages.</p>
                    </div>

                    <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                        <DialogTrigger asChild>
                            <Button className="bg-indigo-600 hover:bg-indigo-700">
                                <Plus className="h-4 w-4 mr-2" /> New Campaign
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Create Lead Campaign</DialogTitle>
                                <DialogDescription>Configure the campaign and customize its embeddable form.</DialogDescription>
                            </DialogHeader>
                            <form onSubmit={submit} className="space-y-4">
                                <div>
                                    <Label>Campaign Name</Label>
                                    <Input value={data.name} onChange={e => setData('name', e.target.value)} required placeholder="e.g. Summer Sale 2024" />
                                    {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
                                </div>
                                <div>
                                    <Label>Form Title</Label>
                                    <Input value={data.form_title} onChange={e => setData('form_title', e.target.value)} placeholder="Displayed on the embed form" />
                                </div>
                                <div>
                                    <Label>Form Description</Label>
                                    <Input value={data.form_description} onChange={e => setData('form_description', e.target.value)} />
                                </div>
                                <div>
                                    <Label>Button Text</Label>
                                    <Input value={data.button_text} onChange={e => setData('button_text', e.target.value)} placeholder="Submit" />
                                </div>
                                <Button type="submit" disabled={processing} className="w-full">Create Campaign</Button>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {campaigns.data.map(campaign => (
                        <Card key={campaign.id} className="hover:border-indigo-200 transition-colors">
                            <CardHeader>
                                <CardTitle className="text-lg">{campaign.name}</CardTitle>
                                <CardDescription>Status: <span className="text-emerald-600 font-medium capitalize">{campaign.status}</span></CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="flex justify-between items-center mt-2">
                                    <div className="text-sm text-slate-500">
                                        <strong className="text-slate-900">{campaign.leads_count}</strong> Leads
                                    </div>
                                    <Link href={route('crm.campaigns.show', campaign.id)}>
                                        <Button variant="outline" size="sm">Manage & Embed</Button>
                                    </Link>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                    {campaigns.data.length === 0 && (
                        <div className="col-span-full text-center py-12 text-slate-500 bg-white rounded-xl border border-slate-200 border-dashed">
                            No campaigns found. Click "New Campaign" to get started.
                        </div>
                    )}
                </div>

            </div>
        </AuthenticatedLayout>
    );
}

import React from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { ArrowDownLeft, Save, Briefcase } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface Props {
    client: {
        id: number;
        name: string;
    }
}

export default function AssignTask({ client }: Props) {
    const { auth } = usePage().props as any;
    
    const { data, setData, post, processing, errors } = useForm({
        title: `${client.name}'s Task - ${new Date().toISOString().split('T')[0]}`,
        description: ''
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.users.tasks.store', client.id));
    };

    return (
        <AdminSidebarLayout title={__('general.assign_task')} header={__('general.assign_task')} user={auth?.user}>
            <Head title={`Assign Task: ${client.name}`} />

            <div className="w-full max-w-7xl mx-auto py-6 space-y-6">
                <header className="mb-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">{__('general.assign_task')}</h1>
                        <p className="text-muted-foreground mt-1">
                            {__('general.create_an_erp_task_for_this_client')} - {client.name}
                        </p>
                    </div>
                    <div>
                        <Button variant="outline" onClick={() => window.history.back()}>
                            <ArrowDownLeft className="h-4 w-4 me-2" style={{ transform: 'rotate(45deg)' }} /> {__('general.back')}</Button>
                    </div>
                </header>

                <Card>
                    <form onSubmit={handleSubmit}>
                        <CardHeader>
                            <CardTitle className="flex items-center">
                                <Briefcase className="h-5 w-5 me-2 text-primary" />
                                {__('general.task_details')}</CardTitle>
                            <CardDescription>
                                {__('general.this_will_create_a_new_task_named')} {client.name}'s Task {__('general.and_link_it_to_their_erp_account')}.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="title">{__('general.task_title')}<span className="text-red-500">*</span></Label>
                                <Input
                                    id="title"
                                    value={data.title}
                                    onChange={e => setData('title', e.target.value)}
                                    placeholder={__('general.enter_task_title')}
                                />
                                {errors.title && <p className="text-sm text-red-500">{errors.title}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">Description (Optional)</Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={e => setData('description', e.target.value)}
                                    placeholder={__('general.enter_task_description_or_notes')}
                                    rows={4}
                                />
                                {errors.description && <p className="text-sm text-red-500">{errors.description}</p>}
                            </div>

                            <div className="pt-4 flex justify-end">
                                <Button type="submit" disabled={processing} className="w-full md:w-auto">
                                    <Save className="h-4 w-4 me-2" />
                                    {__('general.create_task')}
                                </Button>
                            </div>
                        </CardContent>
                    </form>
                </Card>
            </div>
        </AdminSidebarLayout>
    );
}

import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Card, CardHeader, CardTitle, CardContent, CardDescription, CardFooter } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Button } from '@/Components/ui/button';
import { Textarea } from '@/Components/ui/textarea';
import { Send, AlertCircle } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from "@/Components/ui/alert";
import { __ } from '@/lib/i18n';

export default function Broadcast() {
    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        body: '',
        url: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.notifications.broadcast.send'), {
            onSuccess: () => reset(),
        });
    };

    return (
        <AdminSidebarLayout>
            <Head title={__('admin.broadcast_notification')} />

            <div className="max-w-2xl mx-auto py-8">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Send className="w-5 h-5 text-indigo-500" />
                            {__('admin.broadcast_notification')}
                        </CardTitle>
                        <CardDescription>
                            {__('admin.broadcast_notification_description')}
                        </CardDescription>
                    </CardHeader>

                    <form onSubmit={submit}>
                        <CardContent className="space-y-6">
                            <Alert variant="default" className="bg-blue-50 border-blue-200 text-blue-800">
                                <AlertCircle className="h-4 w-4 text-blue-600" />
                                <AlertTitle>{__('general.info')}</AlertTitle>
                                <AlertDescription className="text-sm">
                                    {__('admin.broadcast_info_message')}
                                </AlertDescription>
                            </Alert>

                            <div className="space-y-2">
                                <Label htmlFor="title">{__('general.title')}</Label>
                                <Input
                                    id="title"
                                    type="text"
                                    value={data.title}
                                    onChange={e => setData('title', e.target.value)}
                                    placeholder={__('admin.notification_title_placeholder')}
                                    required
                                />
                                {errors.title && <div className="text-sm text-red-500">{errors.title}</div>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="body">{__('admin.message')}</Label>
                                <Textarea
                                    id="body"
                                    value={data.body}
                                    onChange={e => setData('body', e.target.value)}
                                    placeholder={__('admin.notification_body_placeholder')}
                                    required
                                    rows={4}
                                />
                                {errors.body && <div className="text-sm text-red-500">{errors.body}</div>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="url">{__('admin.link_optional')}</Label>
                                <Input
                                    id="url"
                                    type="url"
                                    value={data.url}
                                    onChange={e => setData('url', e.target.value)}
                                    placeholder="https://example.com/page"
                                />
                                {errors.url && <div className="text-sm text-red-500">{errors.url}</div>}
                            </div>
                        </CardContent>

                        <CardFooter className="flex justify-end bg-gray-50 rounded-b-xl">
                            <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                                <Send className="w-4 h-4 mr-2" />
                                {__('general.send')}
                            </Button>
                        </CardFooter>
                    </form>
                </Card>
            </div>
        </AdminSidebarLayout>
    );
}
